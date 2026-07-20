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

    // Required: column definitions [column_name => type]
    protected $dbValues = [
        "id"          => "int",
        "name"        => "string",
        "description" => "text",
        "price"       => "float",
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
| `float` | `float` | DECIMAL, FLOAT, DOUBLE |
| `datetime` | `string` | MySQL datetime format |

---

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `$dbTable` | `string` | Database table name |
| `$dbPk` | `string` | Primary key column name |
| `$dbValues` | `array` | Column → type definitions |
| `$dbAlias` | `array` | Alias → column mappings |
| `$relations` | `array` | Related object definitions |
| `$dirtyValues` | `array` | Modified-but-not-saved fields |
| `$autoLoad` | `array\|null` | Relations loaded in constructor |

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
$product->Update(); // UPDATE products SET name = 'Updated Name' WHERE id = 42
```

### `Delete(): bool`
Deletes the record from the database.

```php
$product = new Product(42);
$product->Delete(); // DELETE FROM products WHERE id = 42
```

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
Populates model properties from a database row (array or stdClass). Used internally by `MagratheaModelControl`.

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
Returns the PHP type string for a field name.

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
- `$dirtyValues` tracks which fields changed since last load, but the current implementation updates all fields on `Update()`.
