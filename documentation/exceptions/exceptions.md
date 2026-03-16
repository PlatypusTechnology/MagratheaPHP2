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

Thrown by API controllers and the API router. Carries an HTTP status code and optional data payload, making it straightforward to return structured error responses.

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `$status` | `int` | HTTP status code (e.g. 401, 404, 500) |
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
Set the HTTP status code.

```php
throw (new MagratheaApiException("Not Found", 404))->SetStatus(404);
```

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

    public function Read(array $params = []): object|array {
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

Thrown by the `Database` class on connection failures or query errors.

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
