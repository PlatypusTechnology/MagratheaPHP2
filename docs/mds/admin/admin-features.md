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
| `$featureId` | `string` | Unique identifier used in URLs and menu highlighting (defaults to `"some-feature"`; if it's still empty after `Initialize()`, it becomes the class's basename) |
| `$featureIcon` | `mixed` | Put in the menu item as `icon`, but the menu doesn't render icons |
| `$featureClassPath` | `string` | Absolute directory the default `Index()` includes `index.php` from — set it via `SetClassPath()` |

### Key Methods

#### `Initialize(): void`
Hook called once by the constructor, before the `$featureId` fallback. Override to set up properties, register JS/CSS, etc. — no-op by default. (Before 2.4.0, `AdminCrudObject` subclasses had it called twice.)

#### `AddJs(string $file): AdminFeature` / `AddCSS(string $file): AdminFeature`
Register a JS/CSS file (absolute path) with `AdminManager` so it's loaded on every admin page. Both return `$this` for chaining.

#### `SetClassPath($path): void`
Sets `$featureClassPath`, the directory the default `Index()` implementation includes `index.php` from.

#### `HasPermission($action=null): bool`
Permission check run before rendering the index page, any subpage, or a direct feature action; returns `true` by default. Override to restrict access — all three admin dispatch paths (`GetPage()`, `Start::CheckFeature()`, and the explicit-subpage branch of `views/index.php`) call `AdminManager::Instance()->PermissionDenied()` when this returns `false`, passing the action/subpage name about to be invoked as `$action`. (Prior to v2.3.1, only `GetPage()` enforced this — the other two paths called the target method directly with no check, so a feature-specific override should not assume `$action` only ever holds subpage names reachable via `GetPage()`.)

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
- List the files in the cache folder (`cache_path`)
- View (raw or pretty-printed), edit and delete a cache file
- Clear all cache at once (`ClearCache`, POST only since 2.4.0)

Since 2.4.0, `View`/`Edit`/`Save`/`Remove` only accept a plain file name of an existing file
directly inside the cache folder (no `../`, sub-folders or symlinks pointing outside; anything
else throws "invalid file"), and cached content and file names are HTML-escaped when shown.
Before, any logged-in admin could write or delete any file the web server can reach.

**Menu item:** "Cache"
**Feature ID:** `AdminFeatureCache`

---

### CRUD Object Feature

**Path:** `src/Admin/Features/CrudObject/`

The most powerful built-in feature. Automatically generates a full CRUD interface (list, create, read, update, delete) for any model.

#### Creating a CRUD Feature

```php
<?php
// features/Product/ProductAdmin.php (what the admin's code generator writes)
namespace App\Product;

use Magrathea2\Admin\Features\CrudObject\AdminCrudObject;

class ProductAdmin extends AdminCrudObject {
    // Optional: title in the menu and page header (default: "Product CRUD")
    public string $featureName = "Products";

    public function Initialize() {
        $this->SetObject(new \App\Product\Product(), new \App\Product\ProductControl());
    }
}
```

```php
// Register it in your Admin subclass (AddCrudFeature() also puts it in AddFeaturesMenu())
public function SetFeatures() {
    parent::SetFeatures();
    $this->AddCrudFeature(new \App\Product\ProductAdmin());
}
```

**`SetObject(MagratheaModel $object, ?MagratheaModelControl $control = null)`** stores the model,
sets `featureName` (if empty) to `"<Model> CRUD"` and `featureId` (if empty) to `"CRUD<Model>"`, and
sets the control. Pass the control when the model and its control live in different namespaces
(the generator's `mvc` structure: `App\Models\Product` and `App\Controls\ProductControl`). Without
it, `SetObject()` instantiates `<model's full class name>Control`: `App\Product\Product` →
`App\Product\ProductControl`, which works when both share a namespace (the default `feature`
structure). If that class doesn't exist, it throws a `MagratheaModelException` naming the class it
looked for. `SetControl()` can replace the control afterwards.

There are no properties to choose columns or form fields: the list shows every field except
`created_at`/`updated_at` (`Columns()`), and the form is built from the model's field types
(`Fields()`). Override those methods to change them.

**Pages.** Everything is under the feature's URL, `?magrathea_feature=CRUDProduct` (the
`featureId`); `magrathea_feature_subpage` selects `List`, `Form` (with `id`), `Save` or `Delete`
(both POST, with `id`; since 2.4.0 a GET gets 405, it saved an empty object). `HasPermission($action)` receives that subpage name (`null` for the main
page): override it to restrict, e.g. `return $action !== "Delete";`.

---

### File Editor Feature

**Path:** `src/Admin/Features/FileEditor/`

Provides a browser-based file viewer and editor for server-side files. Useful for editing config files, templates, or scripts without SSH access.

**Feature ID:** `AdminFeatureFileEditor`

**Not loaded by default** (since 2.4.0). Enable it from your `Admin` subclass:

```php
public function SetFeatures() {
    parent::SetFeatures();
    $this->LoadFileEditor();
}
```

> **Security note:** any logged-in admin user can use it (`HasEditPermission()` only checks that someone is logged in), and it grants write access to files on the server. A leaked admin password becomes code execution. Don't enable it in production unless you have to.

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

### Tables and form elements: values are data, callables are HTML

Since 2.4.0, `AdminElements` treats what you pass as **values** as data and escapes it, so app data
(anything a user of your app could have typed: names, titles, comments) can't run as script in the admin:

- **`Table($rows, $cols)`:** every scalar value, from array rows and from models, is printed with
  `htmlspecialchars(…, ENT_QUOTES)`; `null` prints empty; arrays and objects print as escaped JSON.
  **A callable column (`"key" => function($row) {…}`) and a callable row value stay raw:** they return HTML
  you build, so escape the data you put in it. Column titles are printed as given.
- **`Input()`, `Textarea()`, `Select()`, `Checkbox()`** (and so `AdminForm`, the CRUD forms included) escape the
  value: the `value` attribute, the textarea's content, a select's option values and labels (`Ref()` for
  models), the checkbox's value. `AdminForm`'s `hidden` value and an `empty` field's value are escaped too,
  unless the value is a callable (its return is printed as HTML). Ids, names/labels, classes, placeholders
  and attributes are printed as given: they're yours, not data.

```php
AdminElements::Instance()->Table($products, [
    "name" => "Name",                         // escaped
    [
        "title" => "&nbsp;",
        "key" => function($p) {               // raw HTML: escape the data inside
            return '<a href="#" onclick="edit('.htmlspecialchars(json_encode($p->id)).')">'
                .htmlspecialchars($p->name).'</a>';
        },
    ],
]);
```

So: don't pass HTML as a value (a link, a badge, a button): it shows as text. Move it into a callable. And
don't escape values before passing them, or they show the entities (`&amp;lt;`).

A function name taken from the query string (e.g. an `onclose` callback) goes through
`AdminElements::JsCallback($fn, $args)`, which returns `"fn(args);"` only for a plain JS function name and `""`
for anything else.

---

## Feature Routing

The admin router reads query-string params across two independent dispatch paths (see `AdminManager::GetActiveFeature()`, `AdminFeature::GetPage()`, and `Start::CheckFeature()`):

```
GET /admin.php?magrathea_feature=reports                                        → ReportsFeature::GetPage() → Index()
GET /admin.php?magrathea_feature=reports&magrathea_feature_subpage=x            → ReportsFeature::GetPage() → $this->x()
GET /admin.php?magrathea_feature=reports&magrathea_feature_action=x             → Start::CheckFeature() → $this->x()
```

The first two rows go through `views/index.php`, which defaults `magrathea_feature_subpage` to `GetPage` and calls that method on the feature instance; `GetPage()` itself then reads the same `magrathea_feature_subpage` param to decide between `Index()` and a named subpage (an empty subpage, or `GetPage` itself, is `Index()`). The third row is a separate, earlier dispatch point — `Start::CheckFeature()` runs before `views/index.php` is even included, and calls the method named by `magrathea_feature_action` directly. Both the explicit-subpage branch of `views/index.php` and `CheckFeature()` call `HasPermission()` before dispatching (see above); the default `GetPage()`-via-`Index()` path enforces it internally instead.

**Which methods the URL can call** (since 2.4.0): all three dispatch points first check `AdminFeature::IsDispatchable($method)`. It accepts only a public, non-static method of the feature with no required parameters, named `^[A-Za-z]\w*$`, that isn't one of `AdminFeature`'s own methods (`AddJs`, `SetClassPath`, `HasPermission`, `GetMenuItem`, …, even when overridden), except `GetPage` and `Index`. Anything else answers 404 ("action not found" from `CheckFeature()`, a "Page not found" alert in the layout). Before 2.4.0, any public method could be called, setters included.

**Actions that change data must refuse GET.** The admin checks its CSRF token on POST only, so a GET link (or an `<img src>` in something an admin opens) can call any dispatchable action. The built-in ones that write answer 405 unless it's a POST (CRUD `Save`/`Delete`, App Config `Import`, Cache `ClearCache`), or do nothing without posted data (Cache `Save`/`Remove`, the password change). Do the same in your own features:

```php
public function Archive() {
    if(@$_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response_code(405);
        AdminElements::Instance()->Alert("Archiving requires a POST request", "danger");
        return;
    }
    // ... change data; call it with callFeature("reports", "Archive", "POST", {...})
}
```

`AdminManager::Instance()->GetActiveFeature()` returns the feature instance matching `magrathea_feature` (by `$featureId`). `AdminFeature::IsFeatureActive()` compares against the same param to highlight the current item in the menu.

---

## Notes

- Features can have sub-routes — implement custom routing inside `Handle()`.
- The CRUD feature auto-generates forms based on model field types (`int`, `string`, `boolean`, etc.).
- All built-in feature views use Bootstrap 5 for styling.
