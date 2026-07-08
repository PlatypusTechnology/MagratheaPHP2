---
name: magrathea-php2
description: How to correctly generate code using the MagratheaPHP2 framework. Read before writing any PHP code for a project that uses this framework.
---

# skills.MD — How to Use MagratheaPHP2 in a Project

This file teaches an AI assistant how to correctly generate code using the MagratheaPHP2 framework. It is a practical, task-oriented cookbook. Read it before writing any PHP code for a project that uses this framework.

Reference files:
- `instructions.MD` — project structure and rules
- `documentation/index.md` — full API reference

---

## Table of Contents

1. [Project Bootstrap](#1-project-bootstrap)
2. [Configuration](#2-configuration)
3. [Creating a Model](#3-creating-a-model)
4. [Creating a Control](#4-creating-a-control)
5. [Building Queries](#5-building-queries)
6. [Creating an API Class](#6-creating-an-api-class)
7. [Creating an API Controller](#7-creating-an-api-controller)
8. [JWT Authentication](#8-jwt-authentication)
9. [Caching](#9-caching)
10. [Logging & Debugging](#10-logging--debugging)
11. [Sending Email](#11-sending-email)
12. [Admin Panel — Entry Point](#12-admin-panel--entry-point)
13. [Admin Panel — Admin Class](#13-admin-panel--admin-class)
14. [Admin Panel — CRUD Features](#14-admin-panel--crud-features)
15. [Error Handling](#15-error-handling)
16. [Testing](#16-testing)
17. [Complete Application Skeleton](#17-complete-application-skeleton)
18. [Checklist Before Delivering Code](#18-checklist-before-delivering-code)

---

## 1. Project Bootstrap

**Every entry point starts the same way.** Never skip any step.

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Magrathea2\MagratheaPHP;

MagratheaPHP::LoadVendor(); // always first — registers the autoloader

$app = MagratheaPHP::Instance()
    ->AppPath(__DIR__)                          // absolute path to app root
    ->AddCodeFolder("models", "controls", "api") // folders the autoloader scans
    ->Dev()                                     // or ->Prod() in production
    ->Load()                                    // reads config/magrathea.conf
    ->Connect();                                // opens DB connection
```

**Rules:**
- `LoadVendor()` must be called before `Instance()`
- `AppPath()` must be called before `Load()`
- `AddCodeFolder()` paths are relative to `AppPath`
- Use `->Dev()` locally, `->Prod()` on servers
- Call `->StartSession()` before any session use (admin panel, etc.)

---

## 2. Configuration

**Config file location:** `<appRoot>/config/magrathea.conf`

Sections are named by **environment**, not by topic. `[general]` holds cross-environment settings, including `use_environment` (which section is active by default — usually overridden by `->Dev()` / `->Prod()`). Each environment section (`[dev]`, `[production]`, …) repeats the full flat set of keys it needs — there is no `[section:env]` override/inheritance syntax.

### Writing a config file

```ini
[general]
	use_environment = "default"
	time_zone = "America/Sao_Paulo"

[dev]
	db_host = "localhost"
	db_name = "my_db"
	db_user = "root"
	db_pass = "secret"
	db_port = "3306"
	site_path = "/var/www/html"
	logs_path = "../logs"
	cache_path = "../cache"
	timezone = "America/Sao_Paulo"
	server_url = "http://localhost:8080"
	jwt_key = "a-very-long-random-string-here"

[production]
	db_host = "db.prod.server"
	db_name = "my_db"
	db_user = "app_user"
	db_pass = "$=DB_PASSWORD"
	db_port = "3306"
	site_path = "/var/www/html"
	logs_path = "../logs"
	cache_path = "../cache"
	timezone = "America/Sao_Paulo"
	server_url = "https://myapp.example.com"
	jwt_key = "$=JWT_SECRET"
```

Keys are quoted strings; indentation with a tab under each section header is the convention seen in real configs (not required by the parser, but keep it for consistency).

### Reading config in code

```php
use Magrathea2\Config;

// Read a single key from the active environment section
$host = Config::Instance()->GetConfig("db_host");

// Read the full active-environment section
$env = Config::Instance()->GetConfigSection();
echo $env["db_host"];
```

**Never hardcode credentials.** Use `$=ENV_VAR_NAME` for secrets.

---

## 3. Creating a Model

A model maps to one database table. One class per table.

```php
<?php
namespace App\Models;

use Magrathea2\MagratheaModel;

class Article extends MagratheaModel {

    protected $dbTable = "articles";   // table name
    protected $dbPk    = "id";         // primary key column (default is "id")

    protected $dbValues = [
        "id"          => "int",
        "title"       => "string",
        "body"        => "text",
        "author_id"   => "int",
        "published"   => "boolean",
        "views"       => "int",
        "created_at"  => "datetime",
        "updated_at"  => "datetime",
    ];

    // Declare each $dbValues field as an explicit typed public property
    public int $id = 0;
    public string $title = "";
    public string $body = "";
    public int $author_id = 0;
    public bool $published = false;
    public int $views = 0;
    public string $created_at = "";
    public string $updated_at = "";

    // Optional: aliases (left = alias, right = real column)
    protected $dbAlias = [
        "content" => "body",
    ];
}
```

**Important:** Always declare each `$dbValues` field as an explicit typed public property. Do NOT use `#[\AllowDynamicProperties]`.

### Supported field types

| Type | PHP equivalent |
|------|---------------|
| `int` | integer |
| `boolean` | bool (stored as TINYINT 0/1) |
| `string` | string (VARCHAR etc.) |
| `text` | string (TEXT column) |
| `float` | float (DECIMAL/FLOAT) |
| `datetime` | string in `Y-m-d H:i:s` format |

### Using a model

```php
// Create and insert
$article = new Article();
$article->title     = "Hello World";
$article->body      = "My first article.";
$article->published = true;
$article->created_at = now();
$id = $article->Save(); // returns new ID

// Load by PK
$article = new Article(42);
echo $article->title;

// Update
$article->title = "Updated Title";
$article->Save(); // detects existing PK → UPDATE

// Delete
$article->Delete();

// Serialize for API response — use ToArray() for flat map, ToJson() for full envelope
return $article->ToArray();
```

---

## 4. Creating a Control

Every model gets a companion Control class for static data access.

```php
<?php
namespace App\Controls;

use Magrathea2\MagratheaModelControl;

class ArticleControl extends MagratheaModelControl {
    protected static $modelName      = "Article";
    protected static $modelNamespace = "App\\Models\\";
    protected static $dbTable        = "articles";
}
```

### Using a control

```php
use App\Controls\ArticleControl;

// All records
$all = ArticleControl::GetAll();

// Filtered
$published = ArticleControl::GetWhere(["published" => 1]);

// First match
$article = ArticleControl::GetRowWhere(["id" => 42]);

// Custom SQL
$recent = ArticleControl::RunQuery(
    "SELECT * FROM articles WHERE created_at > '2024-01-01' ORDER BY created_at DESC LIMIT 5"
);

// Count
$total = ArticleControl::QueryOne("SELECT COUNT(*) FROM articles WHERE published = 1");

// Paginated
$total = 0;
$query = \Magrathea2\DB\Query::Select()
    ->Obj(new \App\Models\Article())
    ->Where(["published" => 1])
    ->Order("created_at DESC");
$page = ArticleControl::RunPagination($query, $total, page: 0, limit: 10);
```

---

## 5. Building Queries

Use the Query Builder for anything beyond simple `GetWhere` calls.

```php
use Magrathea2\DB\Query;
use Magrathea2\DB\Database;

// Basic SELECT
$sql = Query::Select()
    ->Table("articles")
    ->Where(["published" => 1])
    ->Order("created_at DESC")
    ->Limit(10)
    ->Page(0)          // page * limit = OFFSET
    ->SQL();

// SELECT with model (auto-fills table and fields)
$sql = Query::Select()
    ->Obj(\App\Models\Article::class)
    ->Where(["published" => 1])
    ->SQL();

// JOIN
$sql = Query::Select()
    ->Obj(\App\Models\Article::class)
    ->SelectExtra("u.name AS author_name")
    ->Inner("users u", "u.id = articles.author_id")
    ->Where(["articles.published" => 1])
    ->SQL();

// INSERT
$sql = Query::Insert()
    ->Table("articles")
    ->Values(["title" => "New Post", "published" => 0, "created_at" => now()])
    ->SQL();

// UPDATE
$sql = Query::Update()
    ->Table("articles")
    ->SetArray(["title" => "New Title", "updated_at" => now()])
    ->Where("id = 42")
    ->SQL();

// DELETE
$sql = Query::Delete()
    ->Table("articles")
    ->Where("id = 42")
    ->SQL();

// Execute
$rows = Database::Instance()->QueryAll($sql);
$one  = Database::Instance()->QueryRow($sql);
$val  = Database::Instance()->QueryOne($sql);
```

**Always use `Query::Clean()` or `PrepareAndExecute()` for user input:**

```php
$safe = Query::Clean($_GET["search"]);
$sql  = "SELECT * FROM articles WHERE title LIKE '%$safe%'";

// Better: prepared statement
$result = Database::Instance()->PrepareAndExecute(
    "SELECT * FROM articles WHERE title LIKE ?",
    ["s"],
    ["%{$_GET['search']}%"]
);
```

---

## 6. Creating an API Class

The preferred pattern is to encapsulate all route definitions in a class that extends `MagratheaApi`. This makes the API reusable — both the HTTP entry point and the Admin ApiExplorer instantiate the same class.

```php
<?php
namespace App;

use Magrathea2\MagratheaApi;
use App\Api\AuthControl;
use App\Api\ArticleApiControl;

class MyAppApi extends MagratheaApi {

    public function __construct() {
        $this->Initialize();
    }

    public function Initialize() {
        $this->AllowAll();
        $this->SetAddress("/api/v1");
        $this->HealthCheck();
        $this->SetAuth();
        $this->Articles();
    }

    private function SetAuth() {
        $auth = new AuthControl();
        $this->BaseAuthorization($auth, "ValidateToken");
        $this->Add("POST", "auth/login", $auth, "Login");
        $this->Add("GET",  "me",         $auth, "Me",    true);
    }

    private function Articles() {
        $api = new ArticleApiControl();
        $this->Add("GET",    "articles",     $api, "List",   true);
        $this->Add("GET",    "articles/:id", $api, "Read",   true);
        $this->Add("POST",   "articles",     $api, "Create", true);
        $this->Add("PUT",    "articles/:id", $api, "Update", true);
        $this->Add("DELETE", "articles/:id", $api, "Delete", true);
    }
}
```

**HTTP entry point** (`public/index.php`) — thin wrapper that runs the API class:

```php
<?php
// shared bootstrap (bootstrap.php) sets up MagratheaPHP
$api = require __DIR__ . '/api.php';
$api->Run();
```

**Shared setup file** (`public/api.php`) — returns the configured instance, importable by both entry point and admin:

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';
// ... MagratheaPHP bootstrap ...
return new \App\MyAppApi();
```

**Key rules:**
- Group routes into private methods by domain (Auth, Articles, etc.)
- `Run()` is called only in the HTTP entry point, never inside the class
- `AllowAll()` is fine for development; use `Allow([...])` in production
- Route params use `:name` syntax — they arrive as `$params["name"]` in the controller

---

## 7. Creating an API Controller

```php
<?php
namespace App\Api;

use Magrathea2\MagratheaApiControl;
use Magrathea2\Exceptions\MagratheaApiException;
use App\Controls\ArticleControl;
use App\Models\Article;

class ArticleApiControl extends MagratheaApiControl {

    // GET /articles
    public function List(): array {
        $articles = ArticleControl::GetWhere(["published" => 1]);
        return array_map(fn($a) => $a->ToArray(), $articles);
    }

    // GET /articles/:id
    public function Read(array $params = []): array {
        $article = ArticleControl::GetRowWhere(["id" => $params["id"]]);
        if (!$article) {
            throw new MagratheaApiException("Article not found", 404);
        }
        return $article->ToArray();
    }

    // POST /articles
    public function Create(array $data = []): array {
        $post = $this->GetPost(); // reads JSON or form body

        $article = new Article();
        $article->title      = $post["title"]     ?? "";
        $article->body       = $post["body"]       ?? "";
        $article->author_id  = $this->GetUserId(); // from JWT
        $article->published  = false;
        $article->created_at = now();
        $article->Save();

        return $article->ToArray();
    }

    // PUT /articles/:id
    public function Update(array $params = []): array {
        $article = ArticleControl::GetRowWhere(["id" => $params["id"]]);
        if (!$article) {
            throw new MagratheaApiException("Article not found", 404);
        }

        $put = $this->GetPut();
        if (isset($put["title"])) $article->title = $put["title"];
        if (isset($put["body"]))  $article->body  = $put["body"];
        $article->updated_at = now();
        $article->Save();

        return $article->ToArray();
    }

    // DELETE /articles/:id
    public function Delete(array $params = []): bool {
        $article = ArticleControl::GetRowWhere(["id" => $params["id"]]);
        if (!$article) {
            throw new MagratheaApiException("Article not found", 404);
        }
        return $article->Delete();
    }
}
```

**Controller rules:**
- Always throw `MagratheaApiException` for expected errors (404, 400, 403) — the router converts it to JSON automatically
- Use `$this->GetPost()` for POST body, `$this->GetPut()` for PUT body
- Use `$this->GetUserId()` to read the user ID from the decoded JWT token
- Return `->ToArray()` (flat field map) for API responses — never `->ToJson()` (which wraps fields in an outer envelope)
- Never `echo` or `die()` inside controllers

---

## 8. JWT Authentication

### Step 1: Auth controller

```php
<?php
namespace App\Api;

use Magrathea2\MagratheaApiControl;
use Magrathea2\Exceptions\MagratheaApiException;
use Magrathea2\Config;
use App\Controls\UserControl;

class AuthControl extends MagratheaApiControl {

    // Override to use config-driven secret
    public function GetSecret(): string {
        return Config::Instance()->GetConfig("jwt/secret");
    }

    // POST /auth/login
    public function Login(): array {
        $post = $this->GetPost();
        $user = UserControl::GetRowWhere(["email" => $post["email"] ?? ""]);

        if (!$user || !password_verify($post["password"] ?? "", $user->password_hash)) {
            throw new MagratheaApiException("Invalid credentials", 401);
        }

        $token = $this->jwtEncode([
            "user_id" => $user->id,
            "email"   => $user->email,
            "role"    => $user->role,
            "exp"     => time() + 86400, // 24 hours
        ]);

        return ["token" => $token, "user" => $user->ToArray()];
    }

    // Used as base authorization for all protected routes
    public function ValidateToken(): bool {
        $token   = $this->GetAuthorizationToken(); // reads Bearer token
        $payload = $this->GetTokenInfo($token);    // decodes + verifies

        if (!$payload || !isset($payload->user_id)) {
            throw new MagratheaApiException("Unauthorized", 0, null, true);
        }

        $this->userInfo = $payload; // store for downstream use
        return true;
    }
}
```

### Step 2: Access user in other controllers

```php
public function Create(array $data = []): array {
    $userId = $this->GetUserId();       // int|null from token
    $info   = $this->GetUserInfo();     // full decoded payload object
    $role   = $info->role ?? "guest";
    // ...
}
```

**JWT rules:**
- Always set `"exp"` in the payload
- Never store passwords or sensitive PII in the token
- Always override `GetSecret()` to use a config value, not a hardcoded string
- `GetAuthorizationToken()` throws if the header is missing — call it inside `try/catch` or rely on the route's auth parameter

---

## 9. Caching

### In a controller (recommended pattern)

```php
public function List(): array {
    // 1. Serve from cache if available (outputs and exits on hit)
    $this->Cache("articles_published");

    // 2. Compute result
    $articles = ArticleControl::GetWhere(["published" => 1]);
    $result   = array_map(fn($a) => $a->ToArray(), $articles);

    // 3. Save to cache for next request
    $this->Cache("articles_published", json_encode($result));

    return $result;
}

public function Create(array $data = []): array {
    // ... create ...

    // Invalidate related cache
    $this->CacheClear("articles_published");

    return $article->ToArray();
}
```

### Invalidate by pattern (when many related keys exist)

```php
$this->CacheClearPattern("articles_*");
```

### Cache key naming convention

Always include parameters that affect the result:

```php
"articles_cat_{$categoryId}_page_{$page}"
"user_{$userId}_profile"
"products_active_sort_price"
```

---

## 10. Logging & Debugging

### Logging (production)

```php
use Magrathea2\Logger;

Logger::Instance()->Log("Order #$orderId processed by user #$userId");

try {
    // something risky
} catch (\Exception $e) {
    Logger::Instance()->LogError($e);
}

Logger::Instance()->SetLogFile("payments")->Log("Payment success: $amount");
```

### Debugging (development only)

```php
use Magrathea2\Debugger;

Debugger::Instance()->Info("Cache miss for: articles_published");
Debugger::Instance()->Add(["user_id" => $id, "role" => $role]);
Debugger::Instance()->Show();
```

---

## 11. Sending Email

### Native mail()

```php
use Magrathea2\MagratheaMail;

$mail = new MagratheaMail();
$mail->SetTo($user->email)
     ->SetFrom("noreply@myapp.com")
     ->SetSubject("Welcome!")
     ->SetHTMLMessage("<h1>Hi!</h1>")
     ->SetTXTMessage("Hi!")
     ->Send();
```

### SMTP

```php
use Magrathea2\MagratheaMailSMTP;

$mail = new MagratheaMailSMTP(); // uses [mail] section from config
$mail->SetTo($user->email)->SetSubject("Reset")->SetHTMLMessage($html)->Send();
```

---

## 12. Admin Panel — Entry Point

The admin entry point bootstraps Magrathea (including `StartSession()`), then hands off to `AdminManager`.

```php
<?php
// public/admin.php

use Magrathea2\Admin\AdminManager;

include("bootstrap.php");           // shared MagratheaPHP setup (no Run())
include("../admin/MyAppAdmin.php"); // the Admin class

try {
    AdminManager::Instance()->Start(new \App\MyAppAdmin());
} catch (Exception $ex) {
    \Magrathea2\p_r($ex);
}
```

**Rules:**
- `bootstrap.php` must call `->StartSession()` for admin auth to work
- `AdminManager::Instance()->Start()` handles auth, rendering, and routing — it does not return
- Never call `Run()` on the API inside an admin entry point

---

## 13. Admin Panel — Admin Class

Extend `\Magrathea2\Admin\Admin` and implement `\Magrathea2\Admin\iAdmin`.

```php
<?php
namespace App;

use Magrathea2\Admin\AdminMenu;
use Magrathea2\Admin\Features\ApiExplorer\ApiExplorer;
use Magrathea2\Admin\Features\AppConfig\AdminFeatureAppConfig;
use App\Admin\ArticleAdmin;

class MyAppAdmin extends \Magrathea2\Admin\Admin implements \Magrathea2\Admin\iAdmin {

    private $features = [];
    private $apiFeature;

    public function Initialize() {
        $this->SetTitle("My App Admin");
        $this->SetPrimaryColor("#5a2672");
        $this->SetAdminLogo(__DIR__ . "/logo.svg"); // absolute path
    }

    public function Auth($user): bool {
        return parent::Auth($user); // uses default Magrathea admin auth
    }

    public function SetFeatures() {
        parent::SetFeatures();
        $this->LoadConfig();
        $this->LoadFeatures();
        $this->LoadApi();
    }

    public function LoadApi() {
        $this->apiFeature = new ApiExplorer();
        $this->apiFeature->SetApi(new MyAppApi()); // instantiate the API class
        $this->AddFeature($this->apiFeature);
    }

    public function LoadConfig() {
        $this->features["app-config"] = new AdminFeatureAppConfig(true);
        $this->features["app-config"]->featureId   = "AppConfig";
        $this->features["app-config"]->featureName = "Settings";
        $this->AddFeature($this->features["app-config"]);
    }

    public function LoadFeatures() {
        $this->AddCrudFeature(new ArticleAdmin());
        // add more CRUD features here
    }

    public function BuildMenu(): AdminMenu {
        $menu = new AdminMenu();

        $menu->Add($this->features["app-config"]->GetMenuItem());

        $menu->Add($menu->CreateTitle("Api"))
             ->Add($this->apiFeature->GetMenuItem());

        $this->AddFeaturesMenu($menu); // auto-adds all CRUD features

        $menu->Add(["title" => "Magrathea", "type" => "main"]);
        $this->AddMagratheaMenu($menu);
        $menu->Add($menu->GetLogoutMenuItem());

        return $menu;
    }
}
```

**Key methods:**
- `Initialize()` — title, primary color, logo path (called once on load)
- `Auth($user)` — return `parent::Auth($user)` for default Magrathea auth
- `SetFeatures()` — orchestrates LoadConfig / LoadFeatures / LoadApi; always call `parent::SetFeatures()` first
- `LoadApi()` — creates `ApiExplorer`, calls `SetApi(new YourApiClass())`, adds feature
- `LoadFeatures()` — calls `AddCrudFeature()` for each CRUD admin class
- `BuildMenu()` — constructs the sidebar; use `CreateTitle()` for section headers, `AddFeaturesMenu()` to auto-include CRUD items, always end with `GetLogoutMenuItem()`
- `AddFeature()` vs `AddCrudFeature()` — use `AddCrudFeature` for `AdminCrudObject` subclasses, `AddFeature` for everything else

---

## 14. Admin Panel — CRUD Features

Each model gets a companion CRUD admin class extending `AdminCrudObject`.

```php
<?php
namespace App\Admin;

use Magrathea2\Admin\Features\CrudObject\AdminCrudObject;

class ArticleAdmin extends AdminCrudObject {
    protected $modelName        = "Article";
    protected $modelNamespace   = "App\\Models\\";
    protected $controlName      = "ArticleControl";
    protected $controlNamespace = "App\\Controls\\";
    protected $label            = "Articles";
    protected $icon             = "newspaper"; // Bootstrap Icons name
}
```

**The admin class folder must be registered with `AddCodeFolder()` in bootstrap.**

### Field visibility / permissions

By default `AdminCrudObject` allows full CRUD. Override to restrict:

```php
class ArticleAdmin extends AdminCrudObject {
    // ... properties ...

    public function CanCreate(): bool { return false; } // read-only list
    public function CanEdit(): bool   { return false; }
    public function CanDelete(): bool { return true;  }
}
```

### Bootstrap Icons reference

Common values for `$icon`: `person`, `file-earmark-text`, `card-text`, `pencil`, `trash`, `search`, `newspaper`, `gear`, `box-arrow-right`.

---

## 15. Error Handling

### In API controllers — always use MagratheaApiException

```php
use Magrathea2\Exceptions\MagratheaApiException;

throw new MagratheaApiException("Email is required", 400);
throw new MagratheaApiException("Unauthorized", 401);
throw new MagratheaApiException("You don't have permission", 403);
throw new MagratheaApiException("Resource not found", 404);

throw (new MagratheaApiException("Validation failed", 422))
    ->SetData(["errors" => $validationErrors]);

try {
    // some operation
} catch (\Exception $e) {
    throw MagratheaApiException::FromException($e, 500);
}
```

### Global exception handler (optional, add to entry point)

```php
set_exception_handler(function (\Throwable $e) {
    Logger::Instance()->LogError($e);
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode(["error" => "Internal Server Error"]);
    exit;
});
```

---

## 16. Testing

### PHPUnit bootstrap (phpunit.xml)

```xml
<phpunit bootstrap="vendor/autoload.php">
    <testsuites>
        <testsuite name="App">
            <directory>tests/</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

### Mocking for unit tests

```php
use PHPUnit\Framework\TestCase;
use Magrathea2\DB\Database;
use Magrathea2\Config;

class MyTest extends TestCase {
    protected function setUp(): void {
        Database::Instance()->Mock();
        Config::Instance()->SetConfig([
            "database" => ["host" => "localhost", "database" => "test"],
            "jwt"      => ["secret" => "test-secret"],
        ]);
    }
}
```

---

## 17. Complete Application Skeleton

```
my-app/
├── composer.json
├── config/
│   └── magrathea.conf
├── models/
│   └── Article.php
├── controls/
│   └── ArticleControl.php
├── api/
│   ├── AuthControl.php
│   └── ArticleApiControl.php
├── admin/
│   ├── MyAppAdmin.php        ← Admin class
│   ├── ArticleAdmin.php      ← CRUD feature
│   └── logo.svg
├── public/
│   ├── bootstrap.php         ← shared MagratheaPHP setup (returns nothing)
│   ├── api.php               ← returns new MyAppApi() (importable)
│   ├── index.php             ← require api.php → Run()
│   └── admin.php             ← AdminManager::Start(new MyAppAdmin())
├── MyAppApi.php              ← extends MagratheaApi
├── cache/
├── logs/
└── vendor/
```

**`public/bootstrap.php`** — shared setup, no output:

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Magrathea2\MagratheaPHP;

MagratheaPHP::LoadVendor();
MagratheaPHP::Instance()
    ->AppPath(__DIR__ . '/..')
    ->AddCodeFolder("models", "controls", "api", "admin")
    ->Prod()->Load()->Connect()->StartSession();
```

**`public/api.php`** — importable, returns the configured API:

```php
<?php
require_once __DIR__ . "/bootstrap.php";
return new \App\MyAppApi();
```

**`public/index.php`** — HTTP entry point:

```php
<?php
$api = require __DIR__ . "/api.php";
$api->Run();
```

**`public/admin.php`** — admin entry point:

```php
<?php
use Magrathea2\Admin\AdminManager;
require __DIR__ . "/bootstrap.php";

try {
    AdminManager::Instance()->Start(new \App\MyAppAdmin());
} catch (Exception $ex) {
    \Magrathea2\p_r($ex);
}
```

---

## 18. Checklist Before Delivering Code

### Bootstrap
- [ ] `MagratheaPHP::LoadVendor()` is called first
- [ ] `AppPath()` is set with `__DIR__` or an absolute path
- [ ] All class folders registered with `AddCodeFolder()` (including `admin/` for admin panels)
- [ ] `->Load()->Connect()` is called before any DB operation
- [ ] `->StartSession()` called when admin panel is used
- [ ] Mode is `->Dev()` for local, `->Prod()` for server

### Models
- [ ] `$dbTable` is set
- [ ] `$dbValues` includes all columns with correct types
- [ ] `$dbPk` is set if primary key is not `"id"`
- [ ] Each `$dbValues` field has an explicit typed public property declaration
- [ ] No `#[\AllowDynamicProperties]` attribute used

### Controls
- [ ] `$modelName`, `$modelNamespace`, `$dbTable` are all set
- [ ] Namespace matches the actual file location

### API Class
- [ ] Extends `MagratheaApi`
- [ ] Constructor calls `Initialize()`
- [ ] Routes grouped into domain methods
- [ ] `Run()` is NOT called inside the class — only in the HTTP entry point

### API Controllers
- [ ] Every controller extends `MagratheaApiControl`
- [ ] All error cases throw `MagratheaApiException`, not generic exceptions
- [ ] User input never directly interpolated into SQL
- [ ] `GetPost()` / `GetPut()` used (not `$_POST` directly)
- [ ] Returns `->ToArray()` not `->ToJson()` for API responses

### Admin Class
- [ ] Extends `\Magrathea2\Admin\Admin` and implements `\Magrathea2\Admin\iAdmin`
- [ ] `SetFeatures()` calls `parent::SetFeatures()` first
- [ ] `LoadApi()` uses `SetApi(new YourApiClass())` — not `require`
- [ ] `BuildMenu()` ends with `GetLogoutMenuItem()`
- [ ] `AddCrudFeature()` used for `AdminCrudObject` subclasses

### Admin CRUD Features
- [ ] Extends `AdminCrudObject`
- [ ] `$modelName`, `$modelNamespace`, `$controlName`, `$controlNamespace` all set
- [ ] `$label` and `$icon` set for UI

### Config
- [ ] Secrets use `$=ENV_VAR` — never hardcoded
- [ ] Config file at `config/magrathea.conf` relative to `AppPath`

### General
- [ ] `now()` used for datetime fields
- [ ] Cache invalidated when data is mutated
- [ ] Singletons accessed via `::Instance()`, never `new ClassName()`
