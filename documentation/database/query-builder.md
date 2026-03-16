# Query Builder

**Files:** `src/DB/Query.php`, `src/DB/QueryInsert.php`, `src/DB/QueryUpdate.php`, `src/DB/QueryDelete.php`
**Namespace:** `Magrathea2\DB`

A fluent, chainable SQL query builder. Supports SELECT, INSERT, UPDATE, and DELETE operations. Integrates with `MagratheaModel` for automatic field mapping.

---

## Query Types

| Class | SQL Type | Factory Method |
|-------|----------|---------------|
| `Query` | SELECT | `Query::Select()` |
| `QueryInsert` | INSERT | `Query::Insert()` |
| `QueryUpdate` | UPDATE | `Query::Update()` |
| `QueryDelete` | DELETE | `Query::Delete()` |

---

## Query Enum

```php
enum QueryType {
    case Unknown;
    case Select;
    case Insert;
    case Update;
    case Delete;
}
```

---

## SELECT Query

### Creating a SELECT

```php
use Magrathea2\DB\Query;

$query = Query::Select();
// or
$query = Query::Create(); // same thing
```

### Table & Object

#### `Table(string $t): Query`
Set the table name directly.

```php
$query->Table("users");
```

#### `Object(object|string $obj): Query` / `Obj(object|string $obj): Query`
Set the table and fields from a model class. The query builder reads `$dbTable` and `$dbValues` from the model.

```php
use App\Models\User;

$query = Query::Select()->Obj(User::class);
// Automatically uses `users` table and all declared fields
```

### Field Selection

#### `Fields(string|array $fields): Query`
Override the default field selection.

```php
$query->Fields("id, name, email");
$query->Fields(["id", "name", "email"]);
```

#### `SelectStr(string $sel): Query`
Set a raw SELECT string.

```php
$query->SelectStr("u.id, u.name, COUNT(o.id) AS order_count");
```

#### `SelectExtra(string $sel): Query`
Append extra columns to the existing select.

```php
$query->SelectExtra("(SELECT COUNT(*) FROM orders WHERE user_id = u.id) AS total_orders");
```

#### `SelectObj(object $obj): Query`
Add a model's fields prefixed with its table name (for JOINs).

#### `SelectArrObj(array $arrObj): Query`
Add multiple models' fields (for multi-table JOINs).

### WHERE Clauses

#### `Where(string|array $whereSql, string $condition = "AND"): Query`
Add a WHERE clause. Pass a raw SQL string or an associative array.

```php
// Raw SQL
$query->Where("status = 'active'");
$query->Where("created_at > '2024-01-01'");

// Associative array (automatically escaped and quoted)
$query->Where(["status" => "active", "role" => "admin"]);
```

#### `WhereArray(array $arr, string $condition = "AND"): Query`
Add multiple WHERE conditions from an array. Conditions are joined with the given connector.

```php
$query->WhereArray(["status" => "active", "age" => 18], "AND");
```

#### `WhereId(mixed $id): Query`
Add a WHERE on the primary key.

```php
$query->WhereId(42);
// Generates: WHERE id = 42
```

#### `W(string $where, string $field, string $condition = "AND"): Query`
Add a raw WHERE condition with a label (for chaining readability).

```php
$query
    ->W("status = 'active'", "status")
    ->W("age > 18", "age");
```

### Joins

#### `Inner(string $table, string $clause): Query`
Add an INNER JOIN.

```php
$query->Inner("orders o", "o.user_id = u.id");
```

#### `Left(string $table, string $clause): Query`
Add a LEFT JOIN.

```php
$query->Left("profiles p", "p.user_id = u.id");
```

#### `InnerObject(object $object, string $clause): Query`
Join using a model class (reads table name automatically).

```php
$query->InnerObject(new Order(), "orders.user_id = users.id");
```

#### `HasOne(object|string $object, string $field): Query`
Convenience JOIN for a "has one" relationship (LEFT JOIN).

#### `HasMany(object|string $object, string $field): Query`
Convenience JOIN for a "has many" relationship (LEFT JOIN).

#### `BelongsTo(object|string $object, string $field): Query`
Convenience JOIN for a "belongs to" relationship (LEFT JOIN).

#### `Join(string $joinGlue): Query`
Set a raw JOIN string.

### Ordering, Limiting, Grouping

#### `OrderBy(string $o): Query` / `Order(string $o): Query`

```php
$query->Order("created_at DESC");
$query->Order("name ASC, created_at DESC");
```

#### `Limit(int $l): Query`

```php
$query->Limit(20);
```

#### `Page(int $p): Query`
Sets the OFFSET based on `page * limit`.

```php
$query->Limit(20)->Page(2); // LIMIT 20 OFFSET 40
```

#### `GroupBy(string $g): Query` / `Group(string $g): Query`

```php
$query->Group("category_id");
```

### Getting the SQL

#### `SQL(): string`
Build and return the full SQL string.

```php
$sql = $query->SQL();
echo $sql;
// SELECT u.id, u.name FROM users u WHERE status = 'active' ORDER BY name ASC LIMIT 20
```

#### `CountSQL(): string`
Generate a COUNT(*) version of the same query (ignores ORDER BY and LIMIT).

```php
$countSql = $query->CountSQL();
// SELECT COUNT(*) FROM users u WHERE status = 'active'
```

#### `__toString(): string`
Casting to string also returns the SQL.

```php
echo $query; // same as $query->SQL()
```

#### `Debug(): array`
Returns an array with internal state for debugging.

---

## Static Helpers

### `Query::Clean(string $query): string`
Escapes a value for safe SQL embedding (strips dangerous characters).

```php
$safe = Query::Clean($_GET["search"]);
$sql = "SELECT * FROM products WHERE name LIKE '%$safe%'";
```

> Prefer `PrepareAndExecute` for user input when possible.

### `Query::BuildWhere(array $arr, string $condition): string`
Static helper to build a WHERE clause string from an array.

```php
$where = Query::BuildWhere(["status" => "active", "role" => "admin"], "AND");
// "status = 'active' AND role = 'admin'"
```

---

## INSERT Query

```php
use Magrathea2\DB\Query;

$query = Query::Insert()
    ->Table("users")
    ->Values([
        "name"       => "John Doe",
        "email"      => "john@example.com",
        "created_at" => now(),
    ]);

$sql = $query->SQL();
// INSERT INTO users (name, email, created_at) VALUES ('John Doe', 'john@example.com', '...')
```

---

## UPDATE Query

```php
$query = Query::Update()
    ->Table("users")
    ->Set("name", "Jane Doe")
    ->Set("email", "jane@example.com")
    ->SetRaw("updated_at = NOW()")
    ->Where("id = 5");

$sql = $query->SQL();
// UPDATE users SET name = 'Jane Doe', email = 'jane@example.com', updated_at = NOW() WHERE id = 5
```

### `SetArray(array $arr): QueryUpdate`
Set multiple fields at once:

```php
$query = Query::Update()
    ->Table("users")
    ->SetArray(["name" => "Jane", "email" => "jane@example.com"])
    ->Where("id = 5");
```

---

## DELETE Query

```php
$query = Query::Delete()
    ->Table("users")
    ->Where("id = 5");

$sql = $query->SQL();
// DELETE FROM users WHERE id = 5
```

---

## Comprehensive SELECT Example

```php
use Magrathea2\DB\Query;
use Magrathea2\DB\Database;

// Build query
$query = Query::Select()
    ->Obj(User::class)                    // table: users, fields from model
    ->SelectExtra("COUNT(o.id) AS order_count")
    ->Left("orders o", "o.user_id = users.id")
    ->Where(["active" => 1])
    ->Where("users.created_at > '2024-01-01'")
    ->Group("users.id")
    ->Order("order_count DESC")
    ->Limit(10)
    ->Page(0);

// Execute
$rows = Database::Instance()->QueryAll($query->SQL());

// Get total count (for pagination)
$total = Database::Instance()->QueryOne($query->CountSQL());
```

---

## Notes

- `Query` only builds SQL strings — it does **not** execute them. Use `Database::QueryAll()` etc. to run them.
- When used with `MagratheaModelControl`, queries are built and executed together automatically.
- `Query::Clean()` is a basic sanitizer. For full protection against SQL injection use `PrepareAndExecute`.
