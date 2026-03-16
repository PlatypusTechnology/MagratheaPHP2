# MagratheaModelControl — Static ORM Query Interface

**File:** `src/MagratheaModelControl.php`
**Namespace:** `Magrathea2`
**Type:** Abstract Class

The companion to `MagratheaModel`. Provides a static, repository-style interface for querying the database and returning typed model objects. You define one `Control` class per model.

---

## Defining a Control

```php
<?php
namespace App\Controls;

use Magrathea2\MagratheaModelControl;

class ProductControl extends MagratheaModelControl {
    protected static $modelName      = "Product";
    protected static $modelNamespace = "App\\Models\\";
    protected static $dbTable        = "products";
}
```

### Required Static Properties

| Property | Type | Example |
|----------|------|---------|
| `$modelName` | `string` | `"Product"` |
| `$modelNamespace` | `string` | `"App\\Models\\"` |
| `$dbTable` | `string` | `"products"` |

---

## Static Query Methods

### `GetAll(): array<MagratheaModel>`
Returns all records in the table, ordered by PK descending.

```php
$products = ProductControl::GetAll();
foreach ($products as $product) {
    echo $product->name . "\n";
}
```

### `GetListPage(int $limit = 20, int $page = 0): array<MagratheaModel>`
Paginated list of all records.

```php
$page1 = ProductControl::GetListPage(20, 0); // first 20
$page2 = ProductControl::GetListPage(20, 1); // next 20
```

### `GetWhere(string|array $arr, string $condition = "AND"): array<MagratheaModel>`
Fetch records matching a WHERE condition. Pass a raw SQL string or an associative array.

```php
// Array form (recommended — safer)
$active = ProductControl::GetWhere(["active" => 1]);
$affordable = ProductControl::GetWhere(["active" => 1, "price <=" => "50.00"]);

// Raw SQL form
$recent = ProductControl::GetWhere("created_at > '2024-01-01'");

// OR condition
$either = ProductControl::GetWhere(["status" => "draft", "status" => "review"], "OR");
```

### `GetRowWhere(string|array $arr, string $condition = "AND"): object|array`
Same as `GetWhere` but returns only the **first matching record**.

```php
$product = ProductControl::GetRowWhere(["id" => 42]);
echo $product->name;
```

### `GetSimpleWhere(string $whereSql): array<MagratheaModel>`
Fetch records with a raw WHERE SQL string (no table prefix, no `WHERE` keyword).

```php
$results = ProductControl::GetSimpleWhere("price > 100 AND active = 1");
```

### `GetSelectArray(): array`
Returns an associative array suitable for populating `<select>` dropdowns: `[id => name]`.

```php
$options = ProductControl::GetSelectArray();
// [1 => "Widget", 2 => "Gadget", ...]
```

---

## Query Builder Integration

### `Run(Query $magQuery, bool $onlyFirst = false): array<MagratheaModel>`
Execute a `Query` object and return typed model instances.

```php
use Magrathea2\DB\Query;

$query = Query::Select()
    ->Obj(new Product())
    ->Where(["active" => 1])
    ->Order("price ASC")
    ->Limit(10);

$products = ProductControl::Run($query);
```

### `RunMagQuery(Query $magQuery): array<MagratheaModel>`
Alias for `Run`.

### `Count(Query $magQuery): int`
Execute a COUNT query for the given query builder.

```php
$query = Query::Select()->Obj(new Product())->Where(["active" => 1]);
$total = ProductControl::Count($query);
echo "Total active products: $total";
```

### `RunPagination(Query $magQuery, &$total, int $page = 0, int $limit = 20): array<MagratheaModel>`
Executes a query with pagination, also returning the total count by reference.

```php
$query = Query::Select()->Obj(new Product())->Where(["active" => 1]);

$total = 0;
$products = ProductControl::RunPagination($query, $total, page: 0, limit: 20);

echo "Showing " . count($products) . " of $total";
```

---

## Raw SQL Methods

### `RunQuery(string $sql): array<MagratheaModel>`
Execute a raw SQL SELECT and map results to model objects.

```php
$products = ProductControl::RunQuery(
    "SELECT * FROM products WHERE category_id IN (1,2,3) ORDER BY name"
);
```

### `RunRow(string $sql): object|null`
Execute raw SQL and return only the first result as a model object.

```php
$product = ProductControl::RunRow("SELECT * FROM products WHERE sku = 'WIDGET-001'");
```

### `QueryResult(string $sql): array`
Execute raw SQL and return raw result rows (not mapped to models).

### `QueryRow(string $sql): array`
Execute raw SQL and return the first row as an array.

### `QueryOne(string $sql): mixed`
Execute raw SQL and return the first column of the first row.

```php
$maxPrice = ProductControl::QueryOne("SELECT MAX(price) FROM products");
```

---

## Multi-Object Joins

### `GetMultipleObjects(array $array_objects, string $joinGlue, string $where = ""): array`
Build a multi-table query using multiple models and return combined results.

```php
$results = ProductControl::GetMultipleObjects(
    [new Product(), new Category()],
    "products.category_id = categories.id",
    "products.active = 1"
);
```

---

## Utility

### `GetModelName(): string`
Returns the model class name.

### `ShowAll(): void`
Dumps all records to output (debug helper).

---

## Full Example: Paginated Product List

```php
use Magrathea2\DB\Query;
use App\Controls\ProductControl;

// Build query
$query = Query::Select()
    ->Obj(new \App\Models\Product())
    ->Where(["active" => 1])
    ->Order("name ASC");

// Get page 2, 15 items per page
$total    = 0;
$products = ProductControl::RunPagination($query, $total, page: 1, limit: 15);

// Output
echo json_encode([
    "total"    => $total,
    "page"     => 1,
    "per_page" => 15,
    "data"     => array_map(fn($p) => $p->ToJson(), $products),
]);
```

---

## Full Example: Search & Filter

```php
use Magrathea2\DB\Query;
use App\Controls\ProductControl;

$search = Query::Clean($_GET["q"] ?? "");
$minPrice = (float)($_GET["min"] ?? 0);

$query = Query::Select()
    ->Obj(new \App\Models\Product())
    ->Where(["active" => 1]);

if ($search) {
    $query->Where("name LIKE '%$search%'");
}

if ($minPrice > 0) {
    $query->Where("price >= $minPrice");
}

$query->Order("name ASC")->Limit(20);

$results = ProductControl::Run($query);
```

---

## Notes

- Control classes are purely static — never instantiate them.
- Methods that return model arrays always instantiate the correct model type using `$modelNamespace . $modelName`.
- Use `Run()` with a `Query` object for complex queries; use simple helpers (`GetAll`, `GetWhere`) for quick lookups.
