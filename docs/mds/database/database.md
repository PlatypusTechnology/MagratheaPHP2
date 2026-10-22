# Database — MySQLi Connection Wrapper

**File:** `src/DB/Database.php`
**Namespace:** `Magrathea2\DB`
**Extends:** `Singleton`

The primary database access layer. Wraps a `mysqli` connection and provides query execution methods, transaction support, prepared statements, and file import.

---

## Constants (Fetch Modes)

| Constant | Value | Description |
|----------|-------|-------------|
| `FETCH_ASSOC` | 1 | Returns rows as associative arrays, keys lowercased (**default**) |
| `FETCH_OBJECT` | 2 | Returns rows as `stdClass` objects (keys as MySQL returns them) |
| `FETCH_NUM` | 3 | Returns rows as numeric arrays (not settable with `SetFetchMode()`) |
| `FETCH_ARRAY` | 4 | Returns rows as both assoc + numeric (not settable with `SetFetchMode()`) |

Rows come back as **associative arrays** unless `SetFetchMode("object")` was called. Model loading
(`LoadObjectFromTableRow()`) accepts both, so switching modes doesn't break the ORM, but every raw
`QueryAll()`/`QueryRow()` caller in the request then gets objects: switch only if the whole app expects them.

---

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `$mysqli` | `mysqli` | This instance's connection (`null` until the first query) |
| `$connDetails` | `array` | Connection parameters |
| `$fetchmode` | `int` | Current fetch mode (`null` until `SetFetchMode()`, which reads as `FETCH_ASSOC`) |
| `$count` | `int` | Number of rows returned by the last SELECT |
| `$affectedRows` | `int` | Rows affected by the last query (see `LastAffectedRows()`) |

---

## Connection lifecycle

Since 2.4.0, each `Database` instance opens **one** connection, on its first query, and reuses it
for the rest of the request. It's closed automatically at the end of the request (a shutdown
function closes every open instance). Before 2.4.0, every query opened and closed its own connection.

So session state lasts the whole request: `SET @var`, `GET_LOCK()`, temporary tables and
`LAST_INSERT_ID()` survive between calls.

- **Long-running CLI workers:** call `Database::Instance()->Disconnect()` at the start of each job.
  The next query opens a fresh connection, so the worker never hits MySQL's `wait_timeout`
  (8 hours by default) on an idle connection.
- **A second database:** declare a subclass. `Singleton` keeps one instance per class, and each
  instance has its own connection:

  ```php
  class ReportsDb extends \Magrathea2\DB\Database {}

  ReportsDb::Instance()->SetConnection("reports.host", "reports", "user", "pass");
  $rows = ReportsDb::Instance()->QueryAll("SELECT * FROM daily_totals");
  ```

  Models, Controls and the query builder always use `Database::Instance()`; only your own
  calls go to the subclass.
- **Peak connections** become roughly the number of PHP-FPM workers, because a request holds its
  connection while it waits on anything slow. Check `max_connections` if it's low.

### Timezone sync

Since 2.4.0, when the config has a `timezone`, every new connection runs
`SET time_zone = '<PHP's timezone>'` right after `set_charset()`. MySQL's `NOW()`, `CURRENT_TIMESTAMP`,
column defaults, `ON UPDATE CURRENT_TIMESTAMP` and triggers fired by this connection then use the same
zone as PHP's `now()`.

- **On by default** when the active environment has `timezone`. `db_sync_timezone = false` (or `0`,
  `off`, `no`) in the same section turns it off. Without `timezone`, it's off whatever
  `db_sync_timezone` says.
- The zone sent is `date_default_timezone_get()`, not the raw config value: if `timezone` is invalid,
  PHP stays on its default zone and MySQL is synced to that one.
- If MySQL doesn't know the name (error 1298: its timezone tables aren't loaded, common in plain Docker
  images), the current offset (`date('P')`, e.g. `-03:00`) is set instead, and in DEV/DEBUG a debug
  item says so. An offset doesn't follow DST: a CLI worker running across a DST change is an hour off
  until it reconnects. Load the tables (`mysql_tzinfo_to_sql /usr/share/zoneinfo | mysql -u root mysql`)
  to avoid that.
- Any other error on the `SET` throws `MagratheaDBException` and drops the connection.
- **Not covered:** MySQL scheduled events (they keep the time zone they were created with) and other
  clients (mysql CLI, BI tools, cron jobs connecting directly).
- It's set up by `MagratheaPHP::Connect()`. A second database (`ReportsDb`) configured with
  `SetConnection()` doesn't sync unless you call `ReportsDb::Instance()->SetTimezoneSync(true)`.

`TimezoneCheck::Run()` (shown on the admin's Server page and the Bootstrap wizard's config step) reports
the sync state and whether the offset fallback was used.

### Query errors

Since 2.4.0, every query method (`Query*()`, `PrepareAndExecute()`, `QueryTransaction()`,
`Transaction()`'s begin/commit) throws `MagratheaDBException` on a MySQL error, whatever mysqli's error
reporting mode:

- `getCode()` is MySQL's error number (`1062` duplicate entry, `1146` table doesn't exist…);
- `query` (and `values`, for prepared statements) hold the failed statement;
- with PHP 8.1+'s default reporting, mysqli's own `mysqli_sql_exception` is `getPrevious()` (for its
  SQLSTATE: `$ex->getPrevious()->getSqlState()`).

Before 2.4.0, with PHP 8.1+'s default reporting, most methods let mysqli's `mysqli_sql_exception` out
as is: code that catches `mysqli_sql_exception` around framework calls must catch `MagratheaDBException`
now. The exceptions are `QueryTransaction()` and `Query()` with mysqli reporting off: they still return
`false`.

In an API, a `MagratheaDBException` (or a `mysqli_sql_exception`) is always answered with HTTP **500**:
its code is an errno, not a status (`4031` would otherwise map to 403).

```php
use Magrathea2\Exceptions\MagratheaDBException;
use Magrathea2\Exceptions\MagratheaApiException;

try {
    $user->Insert();
} catch (MagratheaDBException $ex) {
    if ($ex->getCode() !== 1062) throw $ex;
    throw new MagratheaApiException("Email already registered", 409);
}
```

### Lost connections

When MySQL reports error **2006** ("server has gone away"), **2013** ("lost connection during
query") or **4031** (MySQL 8.0.24+: "disconnected by the server because of inactivity", sent to a
client idle past `wait_timeout`), the query methods throw `MagratheaDBConnectionLostException` (a `MagratheaDBException`, so
existing `catch(MagratheaDBException)` blocks still catch it). Before throwing, the dead connection is
dropped, so the next call on the same instance opens a new one.

The framework **never retries**. Whether a statement is safe to run twice depends on the statement,
and only the app knows that:

| Code | Meaning | Safe to retry? |
|------|---------|----------------|
| 2006 | Server has gone away: the statement was probably **not** executed | Usually |
| 2013 | Lost connection during query: the statement **may have** executed | Only if it's idempotent |
| 4031 | Disconnected for inactivity: the statement was **not** executed | Yes |

A reconnect is also a new MySQL session: locks, `@vars` and temporary tables from before are gone.

```php
use Magrathea2\DB\Database;
use Magrathea2\Exceptions\MagratheaDBConnectionLostException;

while (true) {
    Database::Instance()->Disconnect(); // fresh connection for every job
    $job = NextJob();
    try {
        ProcessJob($job);
    } catch (MagratheaDBConnectionLostException $ex) {
        // $ex->getCode() is 2006, 2013 or 4031; retry only if ProcessJob() is idempotent
        Requeue($job);
    }
}
```

---

## Connection Methods

### `SetConnection(string $host, string $database, string $username, string $password, int|string|null $port = null): Database`
Set connection parameters. Called internally by `MagratheaPHP::Connect()`, which reads `db_host`,
`db_name`, `db_user`, `db_pass` and `db_port` from the active environment's config section. An empty
`$port` (or no `db_port` in the config) uses mysqli's default port (php.ini `mysqli.default_port`,
normally 3306). With `localhost` as host, mysqli connects through the Unix socket and ignores the port.

```php
Database::Instance()->SetConnection("localhost", "mydb", "root", "pass", 3306);
```

### `SetConnectionArray(array $dsn_arr): Database`
Set connection from an array with keys `hostspec`, `database`, `username`, `password` and, optionally, `port`.

```php
Database::Instance()->SetConnectionArray([
    "hostspec" => "localhost",
    "database" => "mydb",
    "username" => "root",
    "password" => "secret",
]);
```

### `OpenConnectionPlease(): bool`
Opens this instance's connection if it isn't open yet (it reuses it otherwise). Every query method
calls it, so you don't need to. Throws `MagratheaDBException` on failure; a failed connection isn't
kept, so the next call tries again.

### `CloseConnectionThanks(): void`
Does nothing since 2.4.0; kept for compatibility. The connection stays open until the end of the
request. Use `Disconnect()` to close it.

### `Disconnect(): void`
Closes this instance's connection now. The next query opens a new one. Use it at the start of each
job in long-running workers, or before `pcntl_fork()` (a child must never share its parent's connection).

### `Database::DisconnectAll(): void` (static)
Closes the connection of every `Database` instance (including subclasses). Runs automatically at the
end of the request.

### `LastAffectedRows(): int`
Rows affected by the last `Query*()` or `PrepareAndExecute()` call (for a SELECT, the number of rows
returned). `PrepareAndExecute()`'s return value (the insert id, or `true`) doesn't change.

### `SetTimezoneSync(bool $sync): Database`
Sets whether new connections run `SET time_zone` with PHP's timezone (see "Timezone sync" above).
`MagratheaPHP::Connect()` calls it with `TimezoneCheck::SyncState() === TimezoneCheck::SYNC_ON`. Off
by default on a `Database` set up without `Connect()`.

### `GetSyncedTimezone(): ?array`
What the current connection's `time_zone` was set to: `["zone" => "America/Sao_Paulo", "fallback" => false]`,
or `["zone" => "-03:00", "fallback" => true]` when MySQL didn't know the name. `null` when there's no
connection or the sync is off. `DatabaseSimulate` always returns `null`.

### `CreateConnection(): \mysqli` (protected)
Creates the actual `mysqli` connection; `OpenConnectionPlease()` calls it. Override it in a subclass
to hand back a different connection object, e.g. a fake one in tests (the framework's own tests do
this, see `src/Tests/MagratheaTests/databaseConnectionTest.php`).

### `getDatabaseName(): string|null`
Returns the name of the currently connected database.

### `SetFetchMode(string $fetch): Database`
Changes the fetch mode for this instance's next queries. Accepts `"object"` and `"assoc"`; any other
value (including `"num"` and `"array"`) sets `"assoc"`.

```php
Database::Instance()->SetFetchMode("object"); // rows as stdClass from now on
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
    echo $user["name"]; // FETCH_ASSOC (default); $user->name after SetFetchMode("object")
}
```

### `QueryRow(string $sql): array|object`
Executes a SELECT and returns **only the first row**. With no rows it returns an empty array, in
every fetch mode.

```php
$user = Database::Instance()->QueryRow("SELECT * FROM users WHERE id = 1");
if (empty($user)) throw new MagratheaApiException("User not found", 404);
echo $user["email"];
```

### `QueryOne(string $sql): mixed`
Executes a SELECT and returns **only the first column of the first row** (scalar value).

```php
$count = Database::Instance()->QueryOne("SELECT COUNT(*) FROM users");
echo $count; // "42"
```

### `QueryTransaction(array $query_array): bool`
Executes an array of SQL statements as a single transaction. It stops at the first failing statement
(the rest aren't sent), rolls everything back and turns autocommit back on. How the failure reaches
you depends on mysqli's error reporting, as with every other query method:

- **PHP 8.1+ default** (mysqli throws): it throws `MagratheaDBException` (code = MySQL errno) after the
  rollback;
- **reporting off** (mysqli returns errors): it returns `false` (the error is logged to the Debugger).

It returns `true` once the commit succeeds. A failed `COMMIT` is handled like a failed statement. A
lost connection, on a statement or on the `COMMIT`, throws `MagratheaDBConnectionLostException` without
a rollback attempt (MySQL discards the transaction with the connection). If the rollback itself fails,
the connection is dropped, and the error you get is still the original one.

```php
try {
    Database::Instance()->QueryTransaction([
        "INSERT INTO orders (user_id, total) VALUES (1, 99.99)",
        "UPDATE inventory SET stock = stock - 1 WHERE product_id = 5",
    ]);
} catch (MagratheaDBException $e) {
    // nothing was written
}
```

It only takes fixed SQL strings. DDL (`CREATE`, `ALTER`, `TRUNCATE`) commits implicitly in MySQL, so a
DDL statement in the list ends the transaction early.

### `QueryMulti(array|string $queries, bool $killable = true): array`
Executes multiple SQL statements, in order, on the same connection. If `$queries` is a string, it's split on `;` followed by a newline. Returns one entry per statement: `["query" => …, "success" => bool, "result" => …]`, where `result` holds the rows (as assoc arrays) for a SELECT, `["success" => true]` for a `CREATE`, an empty array for other statements, or the error message when `success` is false. (Before 2.4.0, `result` was always empty for SELECTs.) `Query()` calls it when its SQL holds more than one statement.

With `$killable = true` (the default), the first failing statement throws `MagratheaDBException` and the rest aren't sent. With `false`, a failing statement is recorded (`success => false`, the error message in `result`) and the next statements still run, whatever the reporting mode (before 2.4.0, it only worked with reporting off). A lost connection (`MagratheaDBConnectionLostException`) always throws, even with `false`: the next statements can't run without it.

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
Executes a prepared statement, for **writes** (`INSERT`, `UPDATE`, `DELETE`) with untrusted values. It
returns the insert id, or `true` when there's none; it never returns rows, so don't use it for a
SELECT (use the query builder's array forms, or `Escape()`, for reads with untrusted input). It's what
the model's `Insert()`, `Update()` and `Delete()` run.

```php
$id = Database::Instance()->PrepareAndExecute(
    "INSERT INTO users (email, active) VALUES (?, ?)",
    ["string", "boolean"],
    ["user@example.com", true]
);
```

`$arrTypes` holds Magrathea field types, one per value, not mysqli letters: `"int"`, `"integer"` and
`"boolean"` are bound as integers, `"float"` as a double, and anything else (`"string"`, `"text"`,
`"datetime"`, `"decimal"`, …) as a string. A mysqli letter like `"i"` isn't recognized, so it's bound as a
string too.

The model's `Insert()`/`Update()`/`Delete()` pass `GetDataTypeFromField()`'s categories: `int`/`boolean`
fields (and an int PK) are bound as integers, and `float` fields as strings (`"decimal"`), so exact
values don't go through a PHP float. `decimal` fields (2.4.0) are bound as strings too (`"text"`). Before 2.4.0 every model value was bound as a string (MySQL
converted them).

A failed prepare or execute throws `MagratheaDBException` (code = MySQL errno), with the query and values
in its `query` and `values` properties; a lost connection throws `MagratheaDBConnectionLostException`
(see [Query errors](#query-errors)). Before 2.4.0, with mysqli error
reporting off, a failed execute printed `got error! ` and returned `null`, so a failed
`Insert()` set the object's PK to `null` and carried on.

---

## Escaping

### `Escape(mixed $value): string`
Escapes a value for safe interpolation into a raw SQL string (wraps `mysqli::real_escape_string`, opening a connection if needed). This is what the query builder uses internally for `Query::Where(array)`/`WhereArray()`/`W()`, `QueryUpdate::Set()`/`SetArray()`, and `QueryInsert::Values()` — you only need to call it yourself when building a raw SQL fragment by hand.

```php
$safe = Database::Instance()->Escape($_GET["search"]);
$sql = "SELECT * FROM products WHERE name LIKE '%$safe%'";
```

> Still prefer `PrepareAndExecute` over hand-built strings when possible.

---

## Transactions

### `Transaction(callable $fn): mixed`
Runs `$fn` inside a transaction and returns what `$fn` returns. Everything `$fn` does on the connection
(raw queries, model `Save()`/`Insert()`/`Update()`/`Delete()`, `GetWhere()`, the query builder,
`QueryRow("SELECT … FOR UPDATE")`) is committed together when `$fn` returns, or rolled back if it throws.

```php
use Magrathea2\DB\Database;

$orderId = Database::Instance()->Transaction(function() use ($cart) {
    $stock = Database::Instance()->QueryOne("SELECT stock FROM products WHERE id = 5 FOR UPDATE");
    if ($stock < 1) throw new MagratheaApiException("Out of stock", 409);

    $order = new Order();
    $order->user_id = $cart->user_id;
    $order->Insert();
    Database::Instance()->Query("UPDATE products SET stock = stock - 1 WHERE id = 5");
    return $order->id;
});
```

- **On a throw** (any `\Throwable`), it rolls back and rethrows the **same** exception, unchanged. If the
  rollback itself fails, that error is ignored (the connection is dropped instead), so you always get the
  original one.
- **Nested calls run inline.** A `Transaction()` inside another one doesn't begin anything; only the
  outermost call begins, commits or rolls back. There are no savepoints: if an inner call throws, the
  whole transaction is rolled back, **even if outer code catches the exception** and returns normally.
  In that case the outermost call throws `MagratheaDBException("transaction marked as failed by an inner call …")`.
- **A failed `COMMIT`** rolls back and throws `MagratheaDBException` (code = MySQL errno), whatever
  mysqli's error reporting mode.
- **Lost connection:** if the connection drops, `MagratheaDBConnectionLostException` comes out of
  `Transaction()` and MySQL has already discarded the transaction. A stale connection fails on
  `START TRANSACTION`, before `$fn` runs. **On `COMMIT`, code 2013 means the commit may have happened
  or not**: the only way to know is to check the data.
- `QueryTransaction()` can't be called inside `Transaction()` (it throws `MagratheaDBException`): run
  its statements directly in the callback.

**Things to know:**
- **Don't swallow DB exceptions inside the callback.** If a lost-connection exception is caught inside
  `$fn` and `$fn` carries on, the next query opens a **new** connection, in autocommit mode: those
  writes are committed immediately, outside the transaction, and `Transaction()` doesn't notice. Let
  DB exceptions out of `$fn`, or rethrow them.
- **DDL commits implicitly** in MySQL: a `CREATE`, `ALTER`, `DROP` or `TRUNCATE` inside `$fn` commits
  everything before it, and the rest of the callback runs outside the transaction.
- **Ending the request inside the callback** (`die`/`exit`, which `Json()`, `ReturnError()` and a cache
  hit's output do) leaves the transaction uncommitted: MySQL rolls it back when the connection closes
  at the end of the request.
- **Framework writes are part of it:** `AdminManager::Log()` entries written inside `$fn` roll back with it.
- **One connection only:** a transaction belongs to one `Database` instance. `ReportsDb::Instance()->Transaction(...)`
  covers only `ReportsDb`'s queries, and models always use `Database::Instance()`; a transaction never
  spans two connections.

---

## Testing / Mock

### `Mock(): void`
Replaces the internal MySQLi connection with a mock that does nothing. Useful for unit tests.
The mock's `Transaction()` follows the same nesting and failure rules, and logs `START TRANSACTION`,
`COMMIT` and `ROLLBACK` as simulated queries in the Debugger.

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
    printf("[%d] %s <%s>\n", $row["id"], $row["name"], $row["email"]);
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

Database::Instance()->PrepareAndExecute(
    "UPDATE users SET email = ? WHERE id = ?",
    ["string", "int"],
    [$email, $userId]
);
$changed = Database::Instance()->LastAffectedRows();
```

---

## Notes

- The framework usually manages the connection automatically via `MagratheaPHP::Connect()`.
- Never interpolate user-supplied values unescaped: writes go through `PrepareAndExecute` or the ORM, reads through the query builder's array forms or `Escape()`.
- `QueryOne` is the fastest way to retrieve aggregate values (COUNT, MAX, SUM, etc.).
