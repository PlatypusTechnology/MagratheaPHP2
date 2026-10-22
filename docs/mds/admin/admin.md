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

MagratheaPHP::Instance()
    ->AppPath(__DIR__ . '/..')
    ->AddRootCodeFolder("models", "controls")
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

#### First-run setup

On every request, `Start()` decides what to show (`Admin\Start::GetInstallState()`):

- **No admin users at all** → the first-run setup form, which creates a **super admin**. This is
  the only case where it appears.
- **Database error** (server down, wrong credentials, admin tables not installed) → a 500 error
  page: "Database error: the admin can't be loaded right now." The exception message is added only
  in DEV, and the exception is logged. The setup form is never shown on a DB error.
- **Users exist** → login (or the admin, if logged in). If the `admin_install_date` config row is
  missing, the basic system configs are written on the way (`Install::SetBasicConfigs()`, which
  never overwrites existing values).

The setup form's POST re-checks that there are still no users (`Start::CanCreateFirstUser()`)
right before creating the account, so a setup page loaded earlier can't be replayed once a user
exists. (Since 2.4.0. Before that, a missing `admin_install_date` row or any DB error showed the
setup form to whoever opened the admin.)

```php
AdminManager::Instance()->StartDefault("My App", "#e63946");
```

#### Built-in pages and actions

Once logged in, `?magrathea_page=<name>` shows `src/Admin/views/pages/<name>.php` inside the admin layout, and
`?magrathea_subpage=<name>` runs `src/Admin/views/actions/<name>.php` alone (the AJAX actions). Only plain names
of files that exist in those two folders are accepted (`Admin\Start::GetViewFile()`): anything else (`../`,
sub-folders, a missing file) answers 404, "action not found" for an action and a "Page not found" alert for a
page. Before 2.4.0 the name was concatenated into an `include`, so `../` reached any PHP file on the server.
Your own pages belong in admin features (`?magrathea_feature=`), not in these folders.

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
Outputs the admin logo sized to `$logoSize` px — an `<img>` tag for
`.jpeg`/`.jpg`/`.png` logos, or the inlined SVG markup for `.svg` logos.

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

`Admin::SetFeatures()` (used by `StartDefault()`, and by any `Admin` subclass that calls
`parent::SetFeatures()`) registers these features:

| Feature ID | Description |
|-----------|-------------|
| `AdminFeatureAppConfig` | Database-stored app configuration |
| `AdminFeatureCache` | Cache management and clearing |
| `AdminFeatureUser` | Admin user management |
| `AdminFeatureUserLog` | User activity logs |

Not registered by default, opt-in from your `Admin` subclass:

| Feature ID | Description | How to enable |
|-----------|-------------|---------------|
| `AdminFeatureFileEditor` | View/edit files on the server | `$this->LoadFileEditor()` in `SetFeatures()` (see below) |
| `ApiExplorer` | Browse registered API endpoints | `new ApiExplorer()`, `SetApi(...)`, `AddFeature(...)` |

### Enabling the File Editor

Since 2.4.0 the File Editor is **off by default**. Any logged-in admin user can use it to edit
files on the server, so a leaked admin password becomes code execution. Enable it only where you
actually need it:

```php
class MyAppAdmin extends \Magrathea2\Admin\Admin implements \Magrathea2\Admin\iAdmin {
    public function SetFeatures() {
        parent::SetFeatures();
        $this->LoadFileEditor(); // or: $this->AddFeature(new \Magrathea2\Admin\Features\FileEditor\AdminFeatureFileEditor());
    }
}
```

When it's loaded, the Magrathea menu gets a "Tools → File Editor" entry, and the code generator's
"File already exists" note links to the file. Without it, both are hidden, and a direct
`?magrathea_feature=AdminFeatureFileEditor` URL shows "Feature not available".

### Objects and the code generator: "Strict types"

The object form (create an object from a table, or edit an existing one) has a **Strict types**
select, saved as `strict_types` in the object's section of `magrathea_objects.conf`. When it's
`true`, the generated Base class declares `protected $strictTypes = true;` (typed values on load,
see "Strict typing" in the MagratheaModel docs); when it's `false` or missing, the line is left out and
the model keeps the framework default (`false`).

Since 2.4.0, the select defaults to `true` for a **new** object. An existing object saved before
2.4.0 has no `strict_types` key: the form shows `false` for it, so re-saving or regenerating it
doesn't change its Base class or its JSON. Turn it on deliberately, after checking the model's API
consumers (numbers stop being strings in the JSON).

### Code structure: `feature` or `mvc`

The "Generate Code" page sets where generated files go and their namespaces (`App` below is the
configured namespace):

| File | `feature` (default) | `mvc` |
|------|---------------------|-------|
| Model | `features/Product/Product.php`, `App\Product` | `Models/Product.php`, `App\Models` |
| Model Base | `features/Product/Base/ProductBase.php`, `App\Product\Base` | `Models/Base/ProductBase.php`, `App\Models\Base` |
| Control | `features/Product/ProductControl.php`, `App\Product` | `Controls/ProductControl.php`, `App\Controls` |
| Control Base | `features/Product/Base/ProductControlBase.php`, `App\Product\Base` | `Controls/Base/ProductControlBase.php`, `App\Controls\Base` |
| CRUD admin | `features/Product/ProductAdmin.php`, `App\Product` | `Controls/ProductAdmin.php`, `App\Controls` |
| API | `features/Product/ProductApi.php`, `App\Product` | `Controls/ProductApi.php`, `App\Controls` |

The generated CRUD admin and API classes use fully qualified model and control names, so they
work in both structures. Before 2.4.0, the `mvc` CRUD admin, API and the Base model's
`GetControl()` pointed at classes that don't exist.

---

## Adding Custom CRUD Features

The most common admin customization is adding CRUD management for your models:

```php
namespace App\Product;

use Magrathea2\Admin\Features\CrudObject\AdminCrudObject;

class ProductAdmin extends AdminCrudObject {
    public string $featureName = "Products";

    public function Initialize() {
        $this->SetObject(new Product()); // uses App\Product\ProductControl
    }
}
```

```php
// in your Admin subclass's SetFeatures(), after parent::SetFeatures():
$this->AddCrudFeature(new \App\Product\ProductAdmin());
```

Without a second argument, `SetObject()` uses the control at `<model class>Control`, in the model's
namespace. If the control lives elsewhere, pass it: `$this->SetObject(new Product(), new ProductControl())`.
See the CRUD Object Feature in Admin Features for the details.

---

## Notes

- The admin panel requires `session_start()` — call `MagratheaPHP::StartSession()` in your bootstrap.
- The admin is designed to live under a protected path (e.g., `/admin/`) separate from the public API.
- Admin user management uses its own user table, separate from your application's user table.
- The built-in API Explorer reads from `MagratheaApi::GetEndpointsDetail()` — make sure your API registers descriptions via the `$description` parameter of `Add()`.
- Every authenticated admin POST request is checked against a CSRF token (`Magrathea2\Admin\AdminCsrf`), enforced automatically — no opt-out, no config flag. Forms built with `AdminForm` and AJAX calls made through the shared `ajax()`/`callAction()`/`callApi()`/`callFeature()` JS helpers get the token attached for free. If you build a raw `<form>` or a custom AJAX call outside those helpers, add the token yourself: a hidden `magrathea_csrf_token` field (`AdminCsrf::Instance()->GetToken()`) or an `X-Magrathea-CSRF-Token` header. GET requests are never checked.
- Admin API errors (`AdminApi`, called with the `callApi()` JS helper) come with a 4xx/5xx HTTP status since 2.4.0. `callApi()` still resolves them with the `{"success": false, ...}` body, so `.then(rs => { if(!rs.success) ... })` keeps working; it rejects only on network errors or non-JSON responses. The lower-level `ajax()`/`ajaxApi()` helpers reject on any 4xx/5xx (the body is in `err.data`).
