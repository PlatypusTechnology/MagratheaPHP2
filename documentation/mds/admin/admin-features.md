# Admin Features System

**Directory:** `src/Admin/Features/`
**Namespace:** `Magrathea2\Admin\Features`

The admin panel is organized around pluggable **features**. Each feature is a self-contained module that adds a section to the admin menu, manages its own routes, and renders its own views.

---

## Base: AdminFeature

All features extend the abstract `AdminFeature` class.

### Key Properties

| Property | Type | Description |
|----------|------|-------------|
| `$featureId` | `string` | Unique identifier used in URLs |
| `$label` | `string` | Display name in the menu |
| `$icon` | `string` | Bootstrap icon name |
| `$active` | `bool` | Whether this feature is enabled |

### Key Methods

#### `GetFeatureId(): string`
Returns the feature's URL-safe ID.

#### `GetLabel(): string`
Returns the menu label.

#### `IsActive(): bool`
Returns whether the feature is enabled.

#### `Handle(): void`
Called by the router when this feature's URL is matched. Override to implement page logic.

#### `Render(): void`
Outputs the feature's HTML view.

---

## Built-in Features

### AppConfig Feature

**Path:** `src/Admin/Features/AppConfig/`

Manages database-stored application configuration (via `ConfigApp`). Allows admin users to view and edit key-value settings at runtime without touching files.

**Menu item:** "App Config"
**Feature ID:** `app_config`

---

### Cache Feature

**Path:** `src/Admin/Features/Cache/`

Allows admin users to:
- View all cache files
- Clear individual cache entries
- Clear all cache at once
- Clear by pattern

**Menu item:** "Cache"
**Feature ID:** `cache`

---

### CRUD Object Feature

**Path:** `src/Admin/Features/CrudObject/`

The most powerful built-in feature. Automatically generates a full CRUD interface (list, create, read, update, delete) for any model.

#### Creating a CRUD Feature

```php
<?php
namespace App\Admin;

use Magrathea2\Admin\Features\CrudObject\AdminCrudObject;

class ProductAdminFeature extends AdminCrudObject {
    // Model to manage
    protected $modelName      = "Product";
    protected $modelNamespace = "App\\Models\\";

    // Control class for DB queries
    protected $controlName      = "ProductControl";
    protected $controlNamespace = "App\\Controls\\";

    // Display settings
    protected $label = "Products";
    protected $icon  = "box";        // Bootstrap icon name

    // Columns to show in the list view
    protected $listFields = ["id", "name", "price", "active"];

    // Fields shown in create/edit forms
    protected $formFields = ["name", "description", "price", "stock", "active"];
}
```

```php
// Register it
$admin->AddFeaturesArray([new ProductAdminFeature()]);
```

This generates these admin routes automatically:

| Route | Description |
|-------|-------------|
| `/admin/products` | List all products |
| `/admin/products/new` | Create form |
| `/admin/products/{id}` | View/edit |
| `/admin/products/{id}/delete` | Delete confirmation |

---

### File Editor Feature

**Path:** `src/Admin/Features/FileEditor/`

Provides a browser-based file viewer and editor for server-side files. Useful for editing config files, templates, or scripts without SSH access.

**Feature ID:** `file_editor`

> **Security note:** Restrict this feature to trusted admin users only. It grants write access to files on the server.

---

### User Feature

**Path:** `src/Admin/Features/User/`

Manages admin panel users (login, password reset, role management).

**Feature ID:** `user`

**AdminUser model properties:**
- `id` — int
- `name` — string
- `email` — string
- `password_hash` — string
- `role` — string
- `active` — boolean

---

### UserLogs Feature

**Path:** `src/Admin/Features/UserLogs/`

Displays a timeline of admin user actions. Every action logged via `AdminManager::Log()` appears here.

**Feature ID:** `user_logs`

---

### API Explorer Feature

**Path:** `src/Admin/Features/ApiExplorer/`

Renders an interactive documentation page for your registered API endpoints. Reads from `MagratheaApi::GetEndpointsDetail()`.

**Feature ID:** `api_explorer`

To get the most out of this feature, always add descriptions when registering routes:

```php
$api->Add("GET", "/products",     new ProductApiControl(), "List",   false, "List all products");
$api->Add("GET", "/products/{id}", new ProductApiControl(), "Read",   false, "Get a product by ID");
$api->Add("POST", "/products",     new ProductApiControl(), "Create", true,  "Create a new product (auth required)");
```

---

## Creating a Custom Feature

```php
<?php
namespace App\Admin;

use Magrathea2\Admin\AdminFeature;

class ReportsFeature extends AdminFeature {

    protected $featureId = "reports";
    protected $label     = "Reports";
    protected $icon      = "bar-chart";

    public function Handle(): void {
        // Process request, set data
        $this->data["sales"] = SalesControl::GetMonthlySummary();
    }

    public function Render(): void {
        // Output HTML
        include __DIR__ . "/views/reports.php";
    }
}
```

```php
// Register
$admin->AddFeaturesArray([new ReportsFeature()]);
```

---

## Feature Routing

The admin router maps the URL segment after `/admin/` to a feature ID:

```
GET /admin/          → default dashboard
GET /admin/products  → feature with featureId "products"
GET /admin/reports   → feature with featureId "reports"
```

`AdminManager::GetActiveFeature()` returns the matching feature for the current URL.

---

## Notes

- Features can have sub-routes — implement custom routing inside `Handle()`.
- The CRUD feature auto-generates forms based on model field types (`int`, `string`, `boolean`, etc.).
- All built-in feature views use Bootstrap 5 for styling.
