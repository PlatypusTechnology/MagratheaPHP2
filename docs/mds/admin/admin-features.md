# Admin Features System

**Directory:** `src/Admin/Features/`
**Namespace:** `Magrathea2\Admin\Features`

The admin panel is organized around pluggable **features**. Each feature is a self-contained module that adds a section to the admin menu, manages its own routes, and renders its own views.

---

## Base: AdminFeature

All features extend the `AdminFeature` class (and, by convention, implement `iAdminFeature`).

### Key Properties

| Property | Type | Description |
|----------|------|-------------|
| `$featureName` | `string` | Display name in the menu (defaults to `"Unknown"`) |
| `$featureId` | `string` | Unique identifier used in URLs and menu highlighting (defaults to `"some-feature"`; falls back to the class's basename if left empty) |
| `$featureIcon` | `mixed` | Bootstrap icon name, or `null` for no icon |
| `$featureClassPath` | `string` | Absolute directory the default `Index()` includes `index.php` from — set it via `SetClassPath()` |

### Key Methods

#### `Initialize(): void`
Hook called at the end of the constructor. Override to set up properties, register JS/CSS, etc. — no-op by default.

#### `AddJs(string $file): AdminFeature` / `AddCSS(string $file): AdminFeature`
Register a JS/CSS file (absolute path) with `AdminManager` so it's loaded on every admin page. Both return `$this` for chaining.

#### `SetClassPath($path): void`
Sets `$featureClassPath`, the directory the default `Index()` implementation includes `index.php` from.

#### `HasPermission($action=null): bool`
Permission check run before rendering the index page or any subpage; returns `true` by default. Override to restrict access — `GetPage()` calls `AdminManager::Instance()->PermissionDenied()` when this returns `false`.

#### `GetPage(): void`
Entry point called by the router. Reads `$_GET["magrathea_feature_subpage"]`: if present and permitted, calls that method on the feature (a "subpage"); otherwise calls `Index()`.

#### `Index(): void`
Default page renderer — `include($this->featureClassPath . "/index.php")`. Most built-in features override this directly instead of relying on `$featureClassPath` (see `OpenApiAdmin` below).

#### `GetSubpageUrl($subpage, $params=[])`
Builds a URL to one of this feature's subpages via `AdminUrls::Instance()->GetFeatureUrl()`.

#### `IsFeatureActive(): bool`
True when `$_GET["magrathea_feature"]` matches `$featureId` — used for menu highlighting.

#### `GetMenuItem(): array`
Returns `["title", "icon", "feature", "active"]`, consumed by `AdminMenu` when building the sidebar.

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

### OpenApi Feature

**Path:** `src/Admin/Features/OpenApi/`

Renders a [Swagger UI](https://swagger.io/tools/swagger-ui/) page inside the admin panel for a given OpenAPI/Swagger spec file (loaded client-side from `swagger-ui-dist` via CDN). Useful when you maintain a static `swagger.yaml`/`swagger.json` alongside the API and want a browsable, try-it-out UI without wiring it into `ApiExplorer` (which instead introspects routes registered on a `MagratheaApi` instance).

**Menu item:** "Open API"
**Feature ID:** `AdminOpenApi`

```php
use Magrathea2\Admin\Features\OpenApi\OpenApiAdmin;

// $fileUrl is passed straight to Swagger UI's `url` option — defaults to "swagger.yaml"
$this->AddFeature(new OpenApiAdmin("swagger.yaml"));
```

If the `app_url` config key is set (`Config::Instance()->Get("app_url")`), the feature rewrites every Swagger "try it out" request's host/scheme to that base URL, keeping the request path — handy when the admin panel and the API are served from different hosts.

---

## Creating a Custom Feature

```php
<?php
namespace App\Admin;

use Magrathea2\Admin\AdminFeature;
use Magrathea2\Admin\iAdminFeature;

class ReportsFeature extends AdminFeature implements iAdminFeature {

    public string $featureName = "Reports";
    public string $featureId   = "reports";
    public $featureIcon        = "bar-chart";

    public function __construct() {
        parent::__construct();
        $this->SetClassPath(__DIR__); // Index() will include __DIR__ . "/index.php"
    }

    // Optional: restrict access
    public function HasPermission($action = null): bool {
        return true;
    }
}
```

`__DIR__ . "/index.php"` then has access to `$this` as the feature instance (it's included from inside `Index()`), typically via a `$elements = AdminElements::Instance();` header plus whatever HTML/PHP the page needs — see `src/Admin/Features/OpenApi/index.php` for a minimal example, or override `Index()` directly instead of relying on `$featureClassPath` if the page needs pre-processing before rendering.

```php
// Register — AddFeature() is protected, so call it from inside your Admin subclass
// (e.g. in LoadFeatures(), see the Admin Class doc); AddFeaturesArray() is public
// if you need to register a batch of features from outside.
$this->AddFeature(new ReportsFeature());
```

---

## Feature Routing

The admin router reads two query-string params (see `AdminManager::GetActiveFeature()` and `AdminFeature::GetPage()`):

```
GET /admin.php?magrathea_feature=reports                              → ReportsFeature::GetPage() → Index()
GET /admin.php?magrathea_feature=reports&magrathea_feature_subpage=x   → ReportsFeature::GetPage() → $this->x()
```

`AdminManager::Instance()->GetActiveFeature()` returns the feature instance matching `magrathea_feature` (by `$featureId`). `AdminFeature::IsFeatureActive()` compares against the same param to highlight the current item in the menu.

---

## Notes

- Features can have sub-routes — implement custom routing inside `Handle()`.
- The CRUD feature auto-generates forms based on model field types (`int`, `string`, `boolean`, etc.).
- All built-in feature views use Bootstrap 5 for styling.
