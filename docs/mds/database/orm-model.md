# MagratheaModel — ORM Base Model

**File:** `src/MagratheaModel.php`
**Namespace:** `Magrathea2`
**Type:** Abstract Class
**Implements:** `iMagratheaModel`

The base class for all database-backed domain models. Provides field mapping, automatic CRUD operations, property access via `__get`/`__set`, serialization, and relationship definitions.

---

## Interface: iMagratheaModel

Every model must satisfy:

```php
interface iMagratheaModel {
    public function __construct($id);
    public function Save();
    public function Insert();
    public function Update();
    public function GetID();
}
```

---

## Defining a Model

```php
<?php
namespace App\Models;

use Magrathea2\MagratheaModel;

class Product extends MagratheaModel {

    // Required: database table name
    protected $dbTable = "products";

    // Optional: primary key column (default: "id")
    protected $dbPk = "id";

    // Recommended for new models: typed values on load (see "Strict typing" below)
    protected $strictTypes = true;

    // Required: column definitions [column_name => type]
    protected $dbValues = [
        "id"          => "int",
        "name"        => "string",
        "description" => "text",
        "price"       => "decimal",   // DECIMAL column: exact, kept as a string
        "stock"       => "int",
        "active"      => "boolean",
        "created_at"  => "datetime",
    ];

    // Optional: property aliases [alias => real_column]
    protected $dbAlias = [
        "title" => "name",   // $product->title is the same as $product->name
    ];

    // Optional: eager-loaded relations (loaded on construct)
    protected $autoLoad = null;
}
```

### Supported Field Types

| Type | PHP type | Notes |
|------|----------|-------|
| `int` | `int` | Integer values |
| `boolean` | `bool` | Stored as TINYINT(1) |
| `string` | `string` | VARCHAR, CHAR, etc. |
| `text` | `string` | TEXT columns |
| `float` | `float` | FLOAT, DOUBLE (approximate) |
| `decimal` | `string` | DECIMAL (exact, e.g. money) — never cast, see note below |
| `datetime` | `string` | MySQL datetime format (`Y-m-d H:i:s`) |
| `date` | `string` | MySQL date format (`Y-m-d`) — see note below |
| `uuid` | `string` | Auto-generated UUIDv7 on `Insert()` if left unset — see note below |

**`date` normalization:** on `Insert()`/`Update()`, a `date` field's value is normalized
to `Y-m-d` before binding. Accepted inputs: `"YYYY-MM-DD"`, `"YYYY-MM-DD HH:MM:SS"`, and
ISO-8601 (`"YYYY-MM-DDTHH:MM:SS.sssZ"`). Anything else throws a `MagratheaModelException`.
The date part is taken **as written** — no timezone conversion happens, so
`"1990-05-10T03:00:00.000Z"` stores `1990-05-10` regardless of server timezone. That is
the right behavior for birthdate-style fields, but clients east/west of UTC should prefer
sending a plain `"YYYY-MM-DD"` to avoid off-by-one-day surprises.

**`decimal` vs `float`:** use `decimal` for SQL `DECIMAL` columns. Its value is always the
exact string the driver returns (`"12345678901234567.89"`), with `$strictTypes` on or off, and
it's bound as a string on `Insert()`/`Update()`, so it never goes through a PHP float. A
`float` field under `$strictTypes` is cast with `(float)`, which rounds a large `DECIMAL`
on load, and a later `Update()` writes the rounded value back. Do the math with `bcmath` (or
in SQL), not with `+`/`*` on the string. Since 2.4.0, the admin's object generator detects
`DECIMAL` columns as `decimal` and `DOUBLE` as `float` (it used to leave both as `"unknown"`);
its "generate CREATE TABLE" helper emits `decimal(10,2)` with an `/* adjust precision */`
comment — a starting point, not a recommendation. Requires MagratheaPHP2 `2.4.0`+.

**`uuid` generation:** on `Insert()`/`InsertWithPk()`, any field declared `"uuid"` in
`$dbValues` that is still empty gets filled in with `Uuid::V7()` (`src/Uuid.php`) — a
time-ordered RFC 9562 UUIDv7: a 48-bit millisecond timestamp prefix followed by 10 bytes
from PHP's CSPRNG (`random_bytes()`), with the version/variant bits set per spec. A field
that already has a value (set explicitly before `Insert()`) is left alone and never
overwritten. Nothing regenerates it on `Update()` — UUIDs are treated as immutable once
assigned.

Collision-safety is **not** enforced by the framework: there is no pre-insert uniqueness
check and no catch/retry around a duplicate-key error on `INSERT` — a collision would
surface as a rethrown DB exception. In practice this is safe because `random_bytes(10)`
supplies enough entropy that a same-millisecond collision is statistically negligible, but
the real backstop is the database: the `uuid` column must actually be declared
`PRIMARY KEY`/`UNIQUE` in the schema for uniqueness to be *guaranteed* rather than merely
"very likely."

### Strict typing (`$strictTypes`)

By default, `LoadObjectFromTableRow()` assigns whatever the DB driver returns for each
column with no type coercion — mysqli/PDO return every column as a string regardless of
its SQL type, so numeric and boolean properties end up as strings unless the calling code
casts them.

Set `protected $strictTypes = true;` on a model (or its Base class) to opt into typed
hydration: values are cast to their declared `$dbValues` PHP type on load — `int` → `(int)`,
`boolean` → `(bool)`, `float` → `(float)`. `null` is always left as `null`, and
`string`/`text`/`uuid`/`date`/`datetime` fields are left untouched (the DB driver already
returns those as strings). `decimal` fields are checked but never cast: they stay the exact
string. A value that can't be cast — schema drift, e.g. a non-numeric string landing in an
`int`, `float` or `decimal` column — throws `MagratheaModelException` naming the field and
value.

`$strictTypes` defaults to `false` in the framework, so it never changes an existing model's
behavior. Requires MagratheaPHP2 `2.3.0`+.

**Recommended for new models**, generated or hand-written: declare `protected $strictTypes = true;`,
and use `decimal`, not `float`, for exact numbers. Since 2.4.0 the admin's object generator does
this by default for new objects (a "Strict types" select on the object form, saved as
`strict_types` in `magrathea_objects.conf`); objects created before 2.4.0 have no such key and
keep generating without the line. For an **existing** model, turn it on only after checking its
API consumers: the JSON changes from `"42"` to `42` and from `"1"` to `true`.

---

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `$dbTable` | `string` | Database table name |
| `$dbPk` | `string` | Primary key column name |
| `$dbValues` | `array` | Column → type definitions |
| `$dbAlias` | `array` | Alias → column mappings |
| `$relations` | `array` | Related object definitions |
| `$dirtyValues` | `array` | Fields set through `Set()`/`Assign()` since the last save (cleared after `Insert()`/`Update()`) |
| `$autoLoad` | `array\|null` | Relations loaded in constructor |
| `$strictTypes` | `bool` | Typed hydration on load — see [Strict typing](#strict-typing-stricttypes) above (default `false`; recommended `true` for new models) |

---

## Instantiation

```php
// Empty model
$product = new Product();

// Load by primary key
$product = new Product(42);
// Equivalent to: SELECT * FROM products WHERE id = 42
```

---

## Persistence Methods

### `Save(): int|bool`
Smart save: calls `Insert()` if the model has no PK set, or `Update()` if it does.

```php
// Create new record
$product = new Product();
$product->name  = "Widget";
$product->price = 9.99;
$newId = $product->Save(); // returns inserted ID

// Update existing record
$product = new Product(42);
$product->price = 12.99;
$product->Save(); // returns true
```

### `Insert(): int`
Executes an INSERT and sets the PK on the model. Returns the new auto-increment ID.
A failed INSERT (duplicate key, `NOT NULL` column…) throws `MagratheaDBException` and leaves the PK
untouched, whatever mysqli's error reporting mode (before 2.4.0, with reporting off, it set the PK to
`null` and returned it).

```php
$product = new Product();
$product->name = "New Product";
$id = $product->Insert();
```

### `InsertWithPk(): bool`
Inserts a record that already has a PK set (e.g., UUID or custom integer).

```php
$product = new Product();
$product->id   = 9999;
$product->name = "Special Product";
$product->InsertWithPk();
```

### `Update(): bool`
Executes an UPDATE for the current model (uses PK in WHERE clause).

```php
$product = new Product(42);
$product->name = "Updated Name";
$product->Update(); // UPDATE products SET name = 'Updated Name', updated_at = … WHERE id = 42
```

Which columns it writes depends on whether the object was **loaded**: loaded from the DB
(`new Product(42)`, `GetById()`, any Control query, `LoadObjectFromTableRow()`), or saved
once with `Insert()`/`InsertWithPk()`/`Update()`:

- **Loaded object:** the fields whose value changed since the load or the last save, by plain
  assignment (`$obj->name = …`) or `Set()`/`Assign()`, plus `updated_at`. If nothing changed,
  only `updated_at`. Columns this object didn't change are left alone, so a value changed by
  another process in the meantime isn't overwritten.
  - "Changed" ignores the type: `5` and `"5"`, `true` and `"1"`, `false` and `"0"` are the same
    value. `null` is only equal to `null` (`null` → `""` is a change).
  - A partial row (custom `SELECT` with some columns) is fine: columns it didn't have are
    written only if you assign them.
- **Never-loaded object** (`new Product(); $p->id = 42; … Update()`), as before 2.4.0: if
  anything was set through `Set()`/`Assign()`, only those fields (no `updated_at`, plain
  assignments are dropped); otherwise every field, including `null`s (except `null` ints and
  booleans, which are skipped).

**`null` in int/boolean fields:** a written int/boolean field that is `null` or `""` (an empty
form input) is written as SQL `NULL`, so a nullable FK can be cleared:

```php
$article = new Article(42);
$article->category_id = null; // or Assign(["category_id" => ""]) from a form
$article->Save();             // UPDATE … SET category_id = NULL …
```

A `NOT NULL` column gets a DB error instead. Other values go through `intval()` (`"7"` → `7`,
`true` → `1`). On a never-loaded object, `null` ints are still skipped (see above), but `""` is
written as `NULL`.

`LoadObjectFromTableRow()` is for DB rows: an object filled from input with it counts as
loaded, so its `Update()` writes nothing. Fill objects from input with `Assign()`.

**A field can hold a related model** (`$article->author_id = $author;`): `Insert()` and
`Update()` write that model's id. If it has no id yet, it's inserted first (its own `Insert()`).
On a loaded object it's compared by id, so assigning the model the field already points to isn't
a change.

### `Delete(): bool`
Deletes the record from the database.

```php
$product = new Product(42);
$product->Delete(); // DELETE FROM products WHERE id = 42
```

### Timestamps (`created_at` / `updated_at`)

When `created_at` / `updated_at` are declared in `$dbValues` (generated models always declare
them), the model handles them like this:

- **`Insert()` / `InsertWithPk()`:** a timestamp left unset (`null`) isn't sent, so the
  **database** sets it: its column default (`DEFAULT CURRENT_TIMESTAMP`) or a `BEFORE INSERT`
  trigger. A timestamp assigned on the object (`$obj->created_at = …`) is sent, but always as
  PHP's `now()`, never the assigned value — so an import can't keep original timestamps through
  the model; use `Query::Insert()` for that.
- **After `Insert()`:** the object's `created_at` / `updated_at` are set to PHP's `now()`, not
  read back from the row. They match the row only when PHP and MySQL agree on the time and
  timezone; with a trigger or a different MySQL timezone, reload the object
  (`GetById()`) if you need the stored values.
- **`Update()`:** writes `updated_at` with PHP's `now()` (this overrides
  `ON UPDATE CURRENT_TIMESTAMP` and any `BEFORE UPDATE` trigger that sets it), and never
  writes `created_at`. The one exception: a never-loaded object updated after a `Set()`
  doesn't write `updated_at` (see `Update()` above). The object's own `updated_at` isn't
  changed by `Update()`.
- **`Set()`** ignores both fields.

So the table's timestamp columns need a default (or a trigger): a `NOT NULL` column with no
default makes every insert fail, and a nullable one stays `NULL`. The admin's table generator
creates `TIMESTAMP DEFAULT CURRENT_TIMESTAMP` (plus `ON UPDATE CURRENT_TIMESTAMP` for
`updated_at`).

Inserts use MySQL's clock and updates use PHP's, so both must be on the same timezone, or
`updated_at` can come out earlier than `created_at`. Since 2.4.0 that's automatic when the config has
a `timezone`: each connection sets MySQL's session `time_zone` to PHP's (see the Database page,
"Timezone sync"), unless `db_sync_timezone = false`.

---

## Property Access

Models support both method-style and magic property access:

```php
// Magic access (recommended)
echo $product->name;
$product->price = 19.99;

// Method access
echo $product->Get("name");
$product->Set("price", 19.99);

// Suppress missing-field exceptions
$val = $product->Get("nonexistent", true); // returns null instead of throwing
```

### Setting the Primary Key

```php
$product->SetPK(42);
echo $product->GetPK(); // 42
echo $product->GetID(); // same
```

---

## Loading Methods

### `LoadObjectFromTableRow(array|object $row): void`
Populates model properties from a database row (array or stdClass). Used internally by `MagratheaModelControl`. If `$strictTypes` is enabled, values are cast to their declared `$dbValues` type — see [Strict typing](#strict-typing-stricttypes) above. The object then counts as loaded: `Update()` writes only the fields changed after this call. Don't use it for input data (use `Assign()`).

```php
$row = Database::Instance()->QueryRow("SELECT * FROM products WHERE id = 1");
$product = new Product();
$product->LoadObjectFromTableRow($row);
```

### `Assign(array $data): MagratheaModel`
Assign multiple properties from an associative array (e.g., from `$_POST`).

```php
$product = new Product();
$product->Assign([
    "name"  => "Widget",
    "price" => 9.99,
]);
$product->Save();
```

### `GetById(mixed $id): void|object`
Load the model data from the database by primary key. Throws `MagratheaModelException` if not found.

```php
$product = new Product();
$product->GetById(42);
```

---

## Introspection Methods

### `GetDbTable(): string`
Returns the table name.

### `GetPkName(): string`
Returns the primary key column name.

### `GetDbValues(): array`
Returns the column definitions array.

### `GetFields(): array`
Returns the list of column names.

### `GetFieldsForSelect(): string`
Returns a comma-separated SQL field list with table prefix.

### `GetProperties(): array`
Returns the model's current field values as an associative array.

### `IsEmpty(): bool`
Returns `true` if the PK is not set (model not loaded from DB).

### `ModelName(): string`
Returns the class short name.

### `Ref(): string`
Returns a human-readable reference string (e.g., `"Product#42"`).

---

## Serialization

### `ToArray(): array`
Returns all field values as an associative array.

```php
$arr = $product->ToArray();
// ["id" => 42, "name" => "Widget", "price" => 9.99, ...]
```

### `ToJson(): array`
Same as `ToArray()` but intended for JSON API responses. Relations are recursively serialized.

```php
echo json_encode($product->ToJson());
```

### `ToString(): string` / `__toString(): string`

```php
echo $product; // uses __toString
```

---

## Static Methods

### `GetDataTypeFromField(string $field): string`
Takes a field's **type** (a `$dbValues` value like `"int"`, not the field name) and returns the category
`Insert()`/`Update()`/`Delete()` pass to `PrepareAndExecute()`: `"integer"` (`int`, `boolean`),
`"decimal"` (`float`, `double`), `"text"` (`string`, `text`, `uuid`, `decimal`) or `"date"` (`date`, `datetime`);
`null` for an unknown type. `"integer"` is bound as an integer, the others as strings. Mind the names:
the `decimal` **field type** maps to `"text"`; the `"decimal"` **category** is the float one.

### `IncludeAllModels(): void`
Manually includes all model files (if needed outside the autoloader).

---

## Getting the Next Available ID

### `GetNextID(): int`
Returns `MAX(pk) + 1` for the table. Useful when you need to know the next ID before inserting.

```php
$nextId = $product->GetNextID();
```

---

## Full Lifecycle Example

```php
use App\Models\Product;

// 1. Create
$product = new Product();
$product->name  = "Gadget";
$product->price = 29.99;
$product->stock = 100;
$product->active = true;
$id = $product->Save();

echo "Created product #$id";

// 2. Read
$found = new Product($id);
echo $found->name;  // "Gadget"
echo $found->price; // 29.99

// 3. Update
$found->price = 24.99;
$found->Save();

// 4. Serialize
echo json_encode($found->ToJson());

// 5. Delete
$found->Delete();
```

---

## Notes

- The constructor accepts an optional `$id`. If provided, it immediately queries the database.
- `Save()` is the idiomatic method — it detects insert vs. update automatically based on whether the PK is set.
- Fields not in `$dbValues` are silently ignored during insert/update (they won't corrupt the DB).
- `Update()` on a loaded object writes only what changed since the load (see [`Update()`](#update-bool)).
