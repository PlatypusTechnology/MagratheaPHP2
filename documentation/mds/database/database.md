# Database — MySQLi Connection Wrapper

**File:** `src/DB/Database.php`
**Namespace:** `Magrathea2\DB`
**Extends:** `Singleton`

The primary database access layer. Wraps a `mysqli` connection and provides query execution methods, transaction support, prepared statements, and file import.

---

## Constants (Fetch Modes)

| Constant | Value | Description |
|----------|-------|-------------|
| `FETCH_ASSOC` | 1 | Returns rows as associative arrays |
| `FETCH_OBJECT` | 2 | Returns rows as `stdClass` objects (default) |
| `FETCH_NUM` | 3 | Returns rows as numeric arrays |
| `FETCH_ARRAY` | 4 | Returns rows as both assoc + numeric |

---

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `$mysqli` | `mysqli` | The underlying MySQLi connection |
| `$connDetails` | `array` | Connection parameters |
| `$fetchmode` | `int` | Current fetch mode |
| `$count` | `int` | Number of queries executed |

---

## Connection Methods

### `SetConnection(string $host, string $database, string $username, string $password, ?int $port): Database`
Set connection parameters. Called internally by `MagratheaPHP::Connect()`.

```php
Database::Instance()->SetConnection("localhost", "mydb", "root", "pass", 3306);
```

### `SetConnectionArray(array $dsn_arr): Database`
Set connection from an array with keys `host`, `database`, `username`, `password`, `port`.

```php
Database::Instance()->SetConnectionArray([
    "host"     => "localhost",
    "database" => "mydb",
    "username" => "root",
    "password" => "secret",
]);
```

### `OpenConnectionPlease(): bool`
Opens the actual MySQLi connection. Throws `MagratheaDBException` on failure.

### `CloseConnectionThanks(): void`
Closes the active connection.

### `getDatabaseName(): string|null`
Returns the name of the currently connected database.

### `SetFetchMode(string $fetch): Database`
Change the default fetch mode. Accepts `"assoc"`, `"object"`, `"num"`, `"array"`.

```php
Database::Instance()->SetFetchMode("assoc");
```

---

## Query Methods

### `Query(string $sql): object`
Executes a raw SQL query. Returns a `mysqli_result` or throws `MagratheaDBException`.

```php
$result = Database::Instance()->Query("SELECT * FROM users WHERE active = 1");
```

### `QueryAll(string $sql): array`
Executes a SELECT and returns all rows as an array (format depends on fetch mode).

```php
$users = Database::Instance()->QueryAll("SELECT * FROM users");
foreach ($users as $user) {
    echo $user->name; // FETCH_OBJECT (default)
}
```

### `QueryRow(string $sql): array|object`
Executes a SELECT and returns **only the first row**.

```php
$user = Database::Instance()->QueryRow("SELECT * FROM users WHERE id = 1");
echo $user->email;
```

### `QueryOne(string $sql): mixed`
Executes a SELECT and returns **only the first column of the first row** (scalar value).

```php
$count = Database::Instance()->QueryOne("SELECT COUNT(*) FROM users");
echo $count; // "42"
```

### `QueryTransaction(array $query_array): void`
Executes an array of SQL statements as a single atomic transaction. Rolls back all on any failure.

```php
Database::Instance()->QueryTransaction([
    "INSERT INTO orders (user_id, total) VALUES (1, 99.99)",
    "UPDATE inventory SET stock = stock - 1 WHERE product_id = 5",
]);
```

### `QueryMulti(array|string $queries, bool $killable = true): array`
Executes multiple SQL statements. If `$queries` is a string, it splits on `;`. Returns an array of results.

```php
$results = Database::Instance()->QueryMulti([
    "SELECT * FROM users",
    "SELECT * FROM products",
]);
```

### `ImportFile(string $file_path, bool $killable = true): array`
Reads a `.sql` file and executes all statements within it.

```php
Database::Instance()->ImportFile("/migrations/001_create_users.sql");
```

---

## Prepared Statements

### `PrepareAndExecute(string $query, array $arrTypes, array $arrValues): mixed`
Safely executes a prepared statement. Use this for any query involving untrusted input.

```php
$result = Database::Instance()->PrepareAndExecute(
    "SELECT * FROM users WHERE email = ? AND active = ?",
    ["s", "i"],          // types: s=string, i=int, d=double, b=blob
    ["user@example.com", 1]
);
```

---

## Testing / Mock

### `Mock(): void`
Replaces the internal MySQLi connection with a mock that does nothing. Useful for unit tests.

```php
Database::Instance()->Mock();
```

---

## Full Usage Examples

### Basic SELECT

```php
use Magrathea2\DB\Database;

$db = Database::Instance();
$rows = $db->QueryAll("SELECT id, name, email FROM users WHERE active = 1");

foreach ($rows as $row) {
    printf("[%d] %s <%s>\n", $row->id, $row->name, $row->email);
}
```

### Counting rows

```php
$total = Database::Instance()->QueryOne("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
echo "Pending orders: $total";
```

### Transaction (multi-step insert)

```php
Database::Instance()->QueryTransaction([
    "INSERT INTO invoices (user_id, amount) VALUES (10, 250.00)",
    "INSERT INTO invoice_items (invoice_id, product_id) VALUES (LAST_INSERT_ID(), 3)",
    "UPDATE products SET stock = stock - 1 WHERE id = 3",
]);
```

### Safe input with prepared statement

```php
$email = $_POST["email"]; // untrusted user input

$user = Database::Instance()->PrepareAndExecute(
    "SELECT * FROM users WHERE email = ?",
    ["s"],
    [$email]
);
```

---

## Notes

- The framework usually manages the connection automatically via `MagratheaPHP::Connect()`.
- Always prefer `PrepareAndExecute` or the ORM for user-supplied values to avoid SQL injection.
- `QueryOne` is the fastest way to retrieve aggregate values (COUNT, MAX, SUM, etc.).
