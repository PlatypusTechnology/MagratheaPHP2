# Admin Panel System

**Files:** `src/Admin/Admin.php`, `src/Admin/AdminManager.php`
**Namespace:** `Magrathea2\Admin`

MagratheaPHP2 ships with a built-in admin panel system. It provides a configurable web UI for managing application data, users, configuration, cache, files, and API exploration. The admin panel is feature-based: you enable only what you need.

---

## Quick Start

```php
<?php
// admin/index.php
require_once __DIR__ . '/../vendor/autoload.php';

use Magrathea2\MagratheaPHP;
use Magrathea2\Admin\AdminManager;

MagratheaPHP::LoadVendor();
MagratheaPHP::Instance()
    ->AppPath(__DIR__ . '/..')
    ->AddCodeFolder("models", "controls")
    ->Prod()
    ->Load()
    ->Connect()
    ->StartSession();

$manager = AdminManager::Instance()->StartDefault("My App Admin");

// Check authentication
if (!$manager->Auth()) {
    $manager->PermissionDenied();
    exit;
}

// Build the menu
$admin = $manager->GetAdmin();
$menu  = $admin->BuildMenu();

// Include the admin view
include $manager->GetAdmin()->GetViewPath("main.php");
```

---

## Admin Class

**File:** `src/Admin/Admin.php`
**Implements:** `iAdmin`

The primary configuration object for the admin panel. You configure the panel and then pass it to `AdminManager::Start()`.

### Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `$title` | `string` | `"Magrathea Admin"` | Panel title |
| `$primaryColor` | `string` | `"203, 128, 8"` | Primary color (RGB decimal) |
| `$adminLogo` | `string` | | Path to logo image |
| `$favicon` | `string` | | Path to favicon |
| `$extraMenu` | `array` | | Extra menu items |
| `$adminFeatures` | `array` | | Registered admin features |
| `$crudFeatures` | `array` | | Registered CRUD features |

### Configuration Methods

#### `SetTitle(string $t): Admin`

```php
$admin->SetTitle("My App — Admin Panel");
```

#### `SetPrimaryColor(string $color): Admin`
Set primary color from a HEX value. Internally converts to RGB decimal.

```php
$admin->SetPrimaryColor("#3A7BD5");
```

#### `SetPrimaryColorDecimal(string $color): Admin`
Set primary color as an RGB decimal string.

```php
$admin->SetPrimaryColorDecimal("58, 123, 213");
```

#### `SetLogo(string $logo): Admin` / `SetAdminLogo(string $logo): Admin`
Set the logo image path.

```php
$admin->SetLogo("/assets/logo.png");
```

#### `AddFeaturesArray(array $arrFeatures): Admin`
Register multiple features at once.

```php
$admin->AddFeaturesArray([
    new UserFeature(),
    new ProductCrudFeature(),
]);
```

#### `AddJs(string $filePath): Admin`
Add a custom JavaScript file to the admin panel.

#### `AddMenuItem(array ...$item): Admin`
Add a custom menu item.

```php
$admin->AddMenuItem([
    "label" => "Reports",
    "url"   => "/admin/reports",
    "icon"  => "chart-bar",
]);
```

#### `BuildMenu(): AdminMenu`
Build and return the menu structure.

#### `Auth(AdminUser $user): bool`
Validate admin authentication for a user.

---

## AdminManager Class

**File:** `src/Admin/AdminManager.php`
**Extends:** `Singleton`

The runtime manager for the admin panel. Handles initialization, authentication, JS/CSS compilation, and feature routing.

### Starting the Admin

#### `Start(Admin $admin): AdminManager`
Initialize with a custom `Admin` configuration object.

```php
$admin = new Admin();
$admin->SetTitle("My App")
      ->SetPrimaryColor("#e63946")
      ->AddFeaturesArray([...]);

AdminManager::Instance()->Start($admin);
```

#### `StartDefault(?string $title = null, ?string $color = null): AdminManager`
Start with default built-in features (user management, cache, config, file editor).

```php
AdminManager::Instance()->StartDefault("My App", "#e63946");
```

### Authentication

#### `Auth(): bool`
Check if the current session has a valid admin user.

```php
if (!AdminManager::Instance()->Auth()) {
    header("Location: /admin/login");
    exit;
}
```

#### `GetLoggedUser(): AdminUser|null`
Returns the currently logged-in admin user.

```php
$user = AdminManager::Instance()->GetLoggedUser();
echo "Welcome, " . $user->name;
```

#### `PermissionDenied(): void`
Render a "permission denied" page.

#### `ErrorPage(string $message): void`
Render an error page with the given message.

### UI Helpers

#### `GetTitle(): string`
Returns the panel title.

#### `GetColor(): string`
Returns the primary color as RGB decimal string.

#### `PrintLogo(?int $logoSize = 200): void`
Outputs the logo `<img>` tag.

#### `GetFaviconTag(): string`
Returns the favicon `<link>` HTML tag.

### Feature Management

#### `GetFeature(string $featureId): AdminFeature|null`
Returns a registered feature by its ID.

```php
$cacheFeature = AdminManager::Instance()->GetFeature("cache");
```

#### `GetActiveFeature(): AdminFeature|null`
Returns the feature matching the current URL segment.

### Asset Management

#### `AddJs(string $file): AdminManager`
Add a JS file to the admin bundle.

#### `GetJs(): string`
Returns all bundled JS (minified).

#### `GetJSManager(): JavascriptCompressor`
Returns the underlying JS compressor instance.

#### `AddCss(string $file): AdminManager`
Add a CSS file to the admin bundle.

#### `GetCss(): string`
Returns all bundled CSS (minified/compiled).

#### `GetCSSManager(): CssCompressor`
Returns the underlying CSS compressor instance.

### Menu

#### `SetMenu(AdminMenu $m): AdminManager`
Set the current menu.

#### `GetMenu(): AdminMenu`
Returns the current menu object.

### Logging

#### `Log(string $action, mixed $victim = null, mixed $data = null, mixed $user_id = false): void`
Log an admin action.

```php
AdminManager::Instance()->Log("deleted_product", $product->id, $product->ToArray());
```

---

## Built-in Features

When using `StartDefault()`, these features are automatically registered:

| Feature ID | Description |
|-----------|-------------|
| `user` | Admin user management |
| `user_logs` | User activity logs |
| `app_config` | Database-stored app configuration |
| `cache` | Cache management and clearing |
| `file_editor` | View/edit files on the server |
| `api_explorer` | Browse registered API endpoints |

---

## Adding Custom CRUD Features

The most common admin customization is adding CRUD management for your models:

```php
use Magrathea2\Admin\Features\CrudObject\AdminCrudObject;

class ProductAdminFeature extends AdminCrudObject {
    protected $modelName      = "Product";
    protected $modelNamespace = "App\\Models\\";
    protected $controlName    = "ProductControl";
    protected $controlNamespace = "App\\Controls\\";
    protected $label          = "Products";
    protected $icon           = "box";
}
```

```php
$admin->AddFeaturesArray([new ProductAdminFeature()]);
```

---

## Notes

- The admin panel requires `session_start()` — call `MagratheaPHP::StartSession()` in your bootstrap.
- The admin is designed to live under a protected path (e.g., `/admin/`) separate from the public API.
- Admin user management uses its own user table, separate from your application's user table.
- The built-in API Explorer reads from `MagratheaApi::GetEndpointsDetail()` — make sure your API registers descriptions via the `$description` parameter of `Add()`.
