# Design Patterns in MagratheaPHP2

This page documents the design patterns used across the framework, explains why they were chosen, and shows how to work with them effectively.

---

## 1. Singleton Pattern

**Used by:** `Config`, `Database`, `Debugger`, `Logger`, `MagratheaCache`, `AdminManager`, and more.

All service/manager classes extend `Singleton`, ensuring exactly one instance per process. Access is always via `ClassName::Instance()`.

### Why
- Prevents multiple database connections
- Shares configuration across the entire request
- Allows debug/log state to accumulate through the request lifecycle

### Usage

```php
// Always the same object
$db1 = Database::Instance();
$db2 = Database::Instance();
$db1 === $db2; // true
```

### Testing with MockClass

```php
// Swap in a fake for unit tests
Database::MockClass(new FakeDatabase());
// Now Database::Instance() returns FakeDatabase
```

---

## 2. Fluent Interface / Method Chaining

**Used by:** `MagratheaPHP`, `Config`, `MagratheaApi`, `Query`, `MagratheaModel`, `MagratheaMail`, `AdminManager`

Methods return `$this` so calls can be chained:

```php
MagratheaPHP::Instance()
    ->AppPath(__DIR__)
    ->MinVersion("2.1.0")
    ->Dev()
    ->Load()
    ->Connect();
```

```php
$query = Query::Select()
    ->Obj(User::class)
    ->Where(["active" => 1])
    ->Order("name ASC")
    ->Limit(20)
    ->Page(0);
```

### Why
Reduces temporary variables, makes configuration intent clearer, and groups related calls visually.

---

## 3. Repository Pattern (Static Control Classes)

**Used by:** Every `*Control` class extending `MagratheaModelControl`.

Data access is centralized in static control classes, keeping model classes focused on domain behavior.

```
┌────────────────────┐     ┌──────────────────────┐
│   ProductControl   │────▶│   MagratheaModel     │
│  (static queries)  │     │   (Product instance) │
└────────────────────┘     └──────────────────────┘
          │
          ▼
    Database::Instance()
```

```php
// Repository-style data access
$products = ProductControl::GetWhere(["active" => 1]);
$product  = ProductControl::GetRowWhere(["id" => $id]);
```

### Why
- Keeps SQL logic in one place per entity
- Decouples models from query construction
- Easy to test by mocking the control class

---

## 4. Factory Methods

**Used by:** `Query` class — `Query::Select()`, `Query::Insert()`, `Query::Update()`, `Query::Delete()`

Factory methods encapsulate the creation of the correct subtype:

```php
$select = Query::Select();  // returns Query instance
$insert = Query::Insert();  // returns QueryInsert instance
$update = Query::Update();  // returns QueryUpdate instance
$delete = Query::Delete();  // returns QueryDelete instance
```

### Why
The caller doesn't need to know which class to instantiate — the factory handles type selection.

---

## 5. Template Method Pattern

**Used by:** `MagratheaModel` (abstract methods), `MagratheaApiControl` (stub methods), `AdminFeature`

Base classes define the algorithm skeleton; subclasses fill in the specifics.

```php
// Base class defines the interface
abstract class MagratheaApiControl {
    public function List(): array {
        throw new \Exception("List not implemented");
    }
    public function Read($params = false): object|array {
        throw new \Exception("Read not implemented");
    }
    // ... etc
}

// Subclass provides the implementation
class ProductApiControl extends MagratheaApiControl {
    public function List(): array {
        return ProductControl::GetAll();
    }
}
```

---

## 6. Strategy Pattern (Authentication)

**Used by:** `MagratheaApi::BaseAuthorization()`, per-route `$auth` parameter

The API router accepts different auth strategies per route, or a single base strategy for all protected routes.

```php
// Global strategy
$api->BaseAuthorization(new AuthControl(), "ValidateToken");

// Per-route override
$api->Add("GET", "/admin/stats", new StatsControl(), "Get", "ValidateAdmin");
$api->Add("GET", "/public/info", new InfoControl(), "Get", false); // no auth
```

---

## 7. Observer / Event Log

**Used by:** `AdminManager::Log()`, `Logger`

Admin actions are recorded as events for audit trail purposes.

```php
AdminManager::Instance()->Log("product_deleted", $product->id, $product->ToArray(), $userId);
```

---

## 8. Decorator Pattern (Compressors)

**Used by:** `CssCompressor`, `JavascriptCompressor`

Files are added incrementally and then processed (compiled + minified) when output is requested. The original files are not modified.

```php
$css = new CssCompressor();
$css->AddFile("bootstrap.css")
    ->AddFile("app.scss");
$minified = $css->GetOutput(); // original files untouched
```

---

## 9. Null Object / Default Behavior

**Used by:** `Database::Mock()`, `MagratheaMail::Simulate()`

Rather than null checks everywhere, a "do-nothing" mode is built into the class:

```php
// In tests, don't actually connect to DB
Database::Instance()->Mock();

// In dev, don't actually send emails
$mail->Simulate()->Send(); // returns true but does nothing
```

---

## Common MVC-like Structure

While the framework doesn't enforce MVC, the conventional project layout follows an MVC-adjacent pattern:

```
app/
├── models/          # MagratheaModel subclasses (M)
├── controls/        # MagratheaModelControl subclasses (repository layer)
├── api/             # MagratheaApiControl subclasses (C in API context)
├── admin/           # AdminCrudObject and custom AdminFeature subclasses
├── views/           # PHP templates (V)
├── config/
│   └── magrathea.conf
└── index.php        # MagratheaApi bootstrap (entry point)
```

---

## Anti-Patterns to Avoid

### Don't call `new Database()` directly
Always use `Database::Instance()`.

### Don't write raw SQL in controllers
Use the Query Builder or Control class methods. Put SQL in Control classes at most.

### Don't store secrets in code
Use the `$=VAR_NAME` environment variable interpolation in `magrathea.conf`.

### Don't skip `Validate()` before `MagratheaMail::Send()`
Always call `Validate()` and check its return value before sending.
