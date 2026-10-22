# Exceptions — Error Handling

**Directory:** `src/Exceptions/`
**Namespace:** `Magrathea2\Exceptions`

MagratheaPHP2 uses a typed exception hierarchy to make error handling precise and predictable. All framework exceptions extend `MagratheaException`.

---

## Exception Hierarchy

```
\Exception
└── MagratheaException
    ├── MagratheaApiException
    ├── MagratheaDBException
    │   └── MagratheaDBConnectionLostException
    ├── MagratheaConfigException
    └── MagratheaModelException
```

---

## MagratheaException (Base)

**File:** `src/Exceptions/MagratheaException.php`

The base exception class. All framework exceptions extend this.

### Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `$killerError` | `bool` | `false` | Whether this error is fatal |
| `$msg` | `string` | | Exception message |
| `$_data` | `mixed` | `null` | Optional arbitrary data |

### Methods

#### `SetData(mixed $data): MagratheaException`
Attach arbitrary context data to the exception.

```php
throw (new MagratheaException("Something went wrong"))
    ->SetData(["context" => "order processing", "order_id" => 42]);
```

#### `GetData(): mixed`
Retrieve attached data.

#### `stackTrace(): string`
Returns the formatted stack trace as a string.

#### `getMsg(): string`
Returns the exception message.

#### `display(): void`
Echo a formatted representation of the exception.

#### `__toString(): string`
Returns the exception as a string.

---

## MagratheaApiException

**File:** `src/Exceptions/MagratheaApiException.php`
**Extends:** `MagratheaException`

Thrown by API controllers and the API router. Carries an error code, an optional HTTP status and an optional data payload, making it straightforward to return structured error responses.

`MagratheaApi` sends it with the HTTP status from `SetStatus()` if it's 400-599, otherwise derived from the code: `404` → 404, `4031` → 403, `40312` → 403, anything that doesn't map to 400-599 → 500 (see `MagratheaApi`, "Error responses and HTTP status").

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `$status` | `int\|null` | HTTP status set with `SetStatus()`; `null` (default): derived from the code |
| `$code` | `int` | Application error code |

### Constructor

```php
new MagratheaApiException(
    string $message = "Magrathea Api Error",
    int $code = 0,
    ?array $data = null,
    bool $kill = true,
    ?\Exception $previous = null
)
```

### Methods

#### `SetStatus(int $st): MagratheaApiException`
Set the HTTP status of the response, independently of the code. Only 400-599 is used; any other value is ignored and the code decides. Before 2.4.0 this was stored and never read.

```php
// code 400 stays in the body; the HTTP status is 401
throw (new MagratheaApiException("Token missing", 400))->SetStatus(401);
```

`$kill` (constructor) doesn't affect the status. It only matters in the admin API, where a killer error is sent with `ReturnApiException()` and a non-killer one with the shorter `ReturnFail()` body. (Before 2.4.0 it set `$status` to 500/200, which nothing read.)

#### `FromException(\Exception $ex, ?int $code = null, ?array $data = null): MagratheaApiException` (static)
Convert any exception into a `MagratheaApiException`.

```php
try {
    // something that throws a generic exception
} catch (\Exception $e) {
    throw MagratheaApiException::FromException($e, 500);
}
```

### Common Usage in Controllers

```php
use Magrathea2\Exceptions\MagratheaApiException;

class ProductApiControl extends MagratheaApiControl {

    public function Read($params = []): object|array {
        $id = $params["id"] ?? null;

        if (!$id) {
            throw new MagratheaApiException("Missing product ID", 400);
        }

        $product = ProductControl::GetRowWhere(["id" => $id]);

        if (!$product) {
            throw new MagratheaApiException("Product not found", 404);
        }

        return $product->ToJson();
    }
}
```

The API router catches `MagratheaApiException` and converts it to a JSON error response automatically:

```json
{
    "success": false,
    "error": "Product not found",
    "code": 404
}
```

---

## MagratheaDBException

**File:** `src/Exceptions/MagratheaDBException.php`
**Extends:** `MagratheaException`

Thrown by the `Database` class on connection failures or query errors. Since 2.4.0, every query error
is one, whatever mysqli's error reporting mode:
- its code is MySQL's error number (`1062`, `1146`…); 0 for errors that don't come from MySQL
  (e.g. "DB not initialized", a failed connection);
- with PHP 8.1+'s default reporting, mysqli's `mysqli_sql_exception` is `getPrevious()`.

An API answers it with HTTP **500**, whatever its code: an errno isn't an HTTP status.

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `$query` | `string` | The SQL query that caused the error |
| `$values` | `mixed` | Query parameter values |
| `$fullMessage` | `string` | Combined message + query context |

### Constructor

```php
new MagratheaDBException(
    string $message = "Magrathea Database has failed...",
    ?string $query = null,
    int $code = 0,
    ?\Exception $previous = null
)
```

### Methods

#### `SetQueryData(string $query, array|string $values): MagratheaDBException`
Attach the failing query and its parameters.

#### `getFullMessage(): string`
Returns the full error message including query context.

### Handling DB Exceptions

```php
use Magrathea2\Exceptions\MagratheaDBException;

try {
    Database::Instance()->Query($sql);
} catch (MagratheaDBException $e) {
    Logger::Instance()->LogError($e);
    // Return 503 or similar
}
```

---

## MagratheaDBConnectionLostException

**File:** `src/Exceptions/MagratheaDBConnectionLostException.php`
**Extends:** `MagratheaDBException`

Thrown by the `Database` query methods when the MySQL connection is gone. Its code is the MySQL errno:

| Code | Meaning |
|------|---------|
| 2006 | Server has gone away: the statement was probably **not** executed |
| 2013 | Lost connection during query: the statement **may have** executed |
| 4031 | Disconnected by the server for inactivity (MySQL 8.0.24+, idle past `wait_timeout`): the statement was **not** executed |

The failing query and values are attached (`$query`, `$values`), and the original `mysqli_sql_exception`,
when mysqli threw one, is `getPrevious()`. The dead connection is already dropped when you catch it: the
next query on the same `Database` instance opens a new connection. The framework never retries; see
"Lost connections" in the Database page for when retrying is safe.

```php
use Magrathea2\Exceptions\MagratheaDBConnectionLostException;

try {
    $job->Save();
} catch (MagratheaDBConnectionLostException $e) {
    if ($e->getCode() === 2006 || $e->getCode() === 4031) {
        $job->Save(); // the first attempt (probably) never ran
    } else {
        throw $e;     // 2013: it may have run; don't repeat a non-idempotent write
    }
}
```

---

## MagratheaConfigException

**File:** `src/Exceptions/MagratheaConfigException.php`
**Extends:** `MagratheaException`

Thrown when a required configuration key is missing.

```php
// Triggered when:
Config::Instance()->GetConfigFromDefault("required_key", true);
// ↑ throws MagratheaConfigException if "required_key" is not in config
```

---

## MagratheaModelException

**File:** `src/Exceptions/MagratheaModelException.php`
**Extends:** `MagratheaException`

Thrown by the ORM layer when model operations fail (e.g., accessing undefined fields, loading a non-existent record).

```php
use Magrathea2\Exceptions\MagratheaModelException;

try {
    $product = new Product();
    $product->GetById(9999); // non-existent ID
} catch (MagratheaModelException $e) {
    echo "Product not found: " . $e->getMessage();
}
```

---

## Catching All Framework Exceptions

```php
use Magrathea2\Exceptions\MagratheaException;

try {
    // any framework operation
} catch (MagratheaException $e) {
    // handles any of: API, DB, Config, Model exceptions
    Logger::Instance()->LogError($e);
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
```

---

## Global Error Handler Integration

In your entry point, you can set a global handler to catch unhandled exceptions:

```php
set_exception_handler(function (\Throwable $e) {
    Logger::Instance()->LogError($e);

    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode(["error" => "Internal Server Error"]);
    exit;
});
```
