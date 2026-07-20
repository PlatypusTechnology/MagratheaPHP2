# AdminManager — Admin Runtime Singleton

**File:** `src/Admin/AdminManager.php`
**Namespace:** `Magrathea2\Admin`
**Extends:** `Singleton`

The runtime singleton for the admin panel. Manages initialization, authentication, session state, asset compilation, feature routing, and UI helpers. You interact with `AdminManager` throughout an admin request lifecycle.

---

## Starting the Admin

### `Start(Admin $admin): AdminManager`
Initialize with a custom configured `Admin` object.

```php
$admin = new Admin();
$admin->SetTitle("My App Admin")
      ->SetPrimaryColor("#3498DB")
      ->AddFeaturesArray([
          new ProductAdminFeature(),
          new OrderAdminFeature(),
      ]);

AdminManager::Instance()->Start($admin);
```

### `StartDefault(?string $title = null, ?string $color = null): AdminManager`
Start with default configuration and all built-in features enabled.

```php
AdminManager::Instance()->StartDefault("My App", "#e74c3c");
```

Built-in features included by default: user management, user logs, app config, cache, file editor, API explorer.

### `GetAdmin(): ?Admin`
Returns the current `Admin` configuration object.

---

## Authentication

### `Auth(): bool`
Check whether the current HTTP session contains a valid admin user.

```php
if (!AdminManager::Instance()->Auth()) {
    header("Location: /admin/login");
    exit;
}
```

### `GetLoggedUser(): AdminUser|null`
Return the currently logged-in `AdminUser` object.

```php
$user = AdminManager::Instance()->GetLoggedUser();
if ($user) {
    echo "Logged in as: " . $user->name;
}
```

### `PermissionDenied(): void`
Render the permission-denied error page and exit.

```php
if (!AdminManager::Instance()->Auth()) {
    AdminManager::Instance()->PermissionDenied();
    exit;
}
```

### `ErrorPage(string $message): void`
Render a generic error page with a custom message.

```php
AdminManager::Instance()->ErrorPage("Database connection failed.");
```

---

## UI Helpers

### `GetTitle(): string`
Returns the panel title string.

```php
echo "<title>" . AdminManager::Instance()->GetTitle() . "</title>";
```

### `GetColor(): string`
Returns the primary color as an RGB decimal string (e.g., `"58, 123, 213"`).

```php
$color = AdminManager::Instance()->GetColor();
echo "style='color: rgb($color)'";
```

### `PrintLogo(?int $logoSize = 200): void`
Outputs the `<img>` tag for the admin logo.

```php
AdminManager::Instance()->PrintLogo(150);
// <img src="/assets/logo.png" width="150" />
```

### `GetFaviconTag(): string`
Returns the `<link rel="icon">` tag string for the favicon.

```php
echo AdminManager::Instance()->GetFaviconTag();
```

---

## Feature Management

### `GetFeature(string $featureId): AdminFeature|null`
Retrieve a registered feature by its ID.

```php
$cacheFeature = AdminManager::Instance()->GetFeature("cache");
if ($cacheFeature) {
    $cacheFeature->Handle();
}
```

### `GetActiveFeature(): AdminFeature|null`
Returns the feature matching the current URL segment. Used by the admin router.

```php
$feature = AdminManager::Instance()->GetActiveFeature();
if ($feature) {
    $feature->Handle();
    $feature->Render();
} else {
    // show dashboard
}
```

---

## Menu

### `SetMenu(AdminMenu $m): AdminManager`
Set the current admin menu.

### `GetMenu(): AdminMenu`
Returns the current `AdminMenu` object for rendering.

```php
$menu = AdminManager::Instance()->GetMenu();
$menu->Render();
```

---

## Asset Management

### `AddJs(string $file): AdminManager`
Add a JavaScript file to the admin bundle.

```php
AdminManager::Instance()->AddJs("/admin/assets/charts.js");
```

### `GetJs(): string`
Returns all registered JS files bundled and minified.

```php
echo "<script>" . AdminManager::Instance()->GetJs() . "</script>";
```

### `GetJSManager(): JavascriptCompressor`
Returns the underlying `JavascriptCompressor` instance for advanced use.

### `AddCss(string $file): AdminManager`
Add a CSS/SCSS file to the admin bundle.

```php
AdminManager::Instance()->AddCss("/admin/assets/custom.scss");
```

### `GetCss(): string`
Returns all CSS/SCSS compiled and minified.

```php
echo "<style>" . AdminManager::Instance()->GetCss() . "</style>";
```

### `GetCSSManager(): CssCompressor`
Returns the underlying `CssCompressor` instance.

---

## Action Logging

### `Log(string $action, mixed $victim = null, mixed $data = null, mixed $user_id = false): void`
Record an admin action. All calls appear in the UserLogs feature.

```php
AdminManager::Instance()->Log(
    "product_deleted",
    $product->id,
    $product->ToArray()
);
```

---

## Full Admin Entry Point Example

```php
<?php
// admin/index.php
require_once __DIR__ . '/../vendor/autoload.php';

use Magrathea2\MagratheaPHP;
use Magrathea2\Admin\AdminManager;
use App\Admin\ProductAdminFeature;
use App\Admin\OrderAdminFeature;

MagratheaPHP::LoadVendor();

MagratheaPHP::Instance()
    ->AppPath(__DIR__ . '/..')
    ->AddCodeFolder("models", "controls", "admin")
    ->Prod()
    ->Load()
    ->Connect()
    ->StartSession();

$manager = AdminManager::Instance()
    ->StartDefault("My App Admin", "#2c3e50");

// Custom features
$manager->GetAdmin()->AddFeaturesArray([
    new ProductAdminFeature(),
    new OrderAdminFeature(),
]);

// Authentication gate
if (!$manager->Auth()) {
    header("Location: /admin/login.php");
    exit;
}

// Route to the active feature or show dashboard
$feature = $manager->GetActiveFeature();
if ($feature) {
    $feature->Handle();
}

// Build menu
$menu = $manager->GetAdmin()->BuildMenu();
$manager->SetMenu($menu);

// Render layout
include __DIR__ . '/layout.php';
```

---

## Notes

- `AdminManager` must be initialized before calling any other admin method.
- `StartDefault()` is the fastest way to get a functional admin panel. Use `Start()` for full customization.
- Asset methods (`GetJs()`, `GetCss()`) compile files on first call and cache the result for the request.
- Feature routing is URL-based — the first path segment after `/admin/` maps to a feature ID.
