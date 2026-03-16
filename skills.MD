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
6. [Creating an API](#6-creating-an-api)
7. [Creating an API Controller](#7-creating-an-api-controller)
8. [JWT Authentication](#8-jwt-authentication)
9. [Caching](#9-caching)
10. [Logging & Debugging](#10-logging--debugging)
11. [Sending Email](#11-sending-email)
12. [Admin Panel](#12-admin-panel)
13. [Error Handling](#13-error-handling)
14. [Testing](#14-testing)
15. [Complete Application Skeleton](#15-complete-application-skeleton)
16. [Checklist Before Delivering Code](#16-checklist-before-delivering-code)

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

### Writing a config file

```ini
[database]
host     = localhost
database = my_db
username = root
password = secret
port     = 3306

[app]
name    = My Application

[logs]
path = /var/log/my_app/

[cache]
path = /tmp/my_app_cache/

[jwt]
secret = a-very-long-random-string-here
```

### Multi-environment (override only what changes)

```ini
[database]
host     = localhost
database = dev_db
username = root
password = dev_pass

[database:production]
host     = db.prod.server
database = prod_db
username = app_user
password = $=DB_PASSWORD
```

### Reading config in code

```php
use Magrathea2\Config;

// Read a full section
$db = Config::Instance()->GetConfigSection("database");
echo $db["host"];

// Read a single key with section/key notation
$host = Config::Instance()->GetConfig("database/host");

// Read from default section
$appName = Config::Instance()->Get("name");
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

    // Optional: aliases (left = alias, right = real column)
    protected $dbAlias = [
        "content" => "body",
    ];
}
```

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

// Serialize for API response
return $article->ToJson();
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

## 6. Creating an API

```php
<?php
// public/index.php
require_once __DIR__ . '/../vendor/autoload.php';

use Magrathea2\MagratheaPHP;
use Magrathea2\MagratheaApi;

MagratheaPHP::LoadVendor();
MagratheaPHP::Instance()
    ->AppPath(__DIR__ . '/..')
    ->AddCodeFolder("models", "controls", "api")
    ->Prod()
    ->Load()
    ->Connect();

$api = new MagratheaApi();

// CORS
$api->AllowAll();                              // open (dev/public)
// $api->Allow(["https://myapp.com"]);        // restricted (production)

// Optional: base path prefix
$api->SetAddress("/api/v1");

// Optional: global auth on all protected routes
$api->BaseAuthorization(new \App\Api\AuthControl(), "ValidateToken");

// Register routes
$api->Add("GET",    "/articles",      new ArticleApiControl(), "List");
$api->Add("GET",    "/articles/{id}", new ArticleApiControl(), "Read");
$api->Add("POST",   "/articles",      new ArticleApiControl(), "Create", true); // auth
$api->Add("PUT",    "/articles/{id}", new ArticleApiControl(), "Update", true);
$api->Add("DELETE", "/articles/{id}", new ArticleApiControl(), "Delete", true);

// Or, all of the above in one line:
$api->Crud("/articles", new ArticleApiControl(), true);

// Health check endpoint: GET /health → {"status":"ok"}
$api->HealthCheck();

// Run — this outputs JSON and exits
$api->Run();
```

**Key API rules:**
- `Run()` must be the **last** call — it outputs and exits
- `AllowAll()` is fine for development; use `Allow([...])` in production
- Use `true` for the `$auth` parameter on any route that requires a token
- Route params use `{name}` syntax — they arrive as `$params["name"]` in the controller

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
        return array_map(fn($a) => $a->ToJson(), $articles);
    }

    // GET /articles/{id}
    public function Read(array $params = []): object|array {
        $article = ArticleControl::GetRowWhere(["id" => $params["id"]]);
        if (!$article) {
            throw new MagratheaApiException("Article not found", 404);
        }
        return $article->ToJson();
    }

    // POST /articles
    public function Create(array $data = []): object {
        $post = $this->GetPost(); // reads JSON or form body

        $article = new Article();
        $article->title      = $post["title"]     ?? "";
        $article->body       = $post["body"]       ?? "";
        $article->author_id  = $this->GetUserId(); // from JWT
        $article->published  = false;
        $article->created_at = now();
        $article->Save();

        return $article->ToJson();
    }

    // PUT /articles/{id}
    public function Update(array $params): object {
        $article = ArticleControl::GetRowWhere(["id" => $params["id"]]);
        if (!$article) {
            throw new MagratheaApiException("Article not found", 404);
        }

        $put = $this->GetPut();
        if (isset($put["title"])) $article->title = $put["title"];
        if (isset($put["body"]))  $article->body  = $put["body"];
        $article->updated_at = now();
        $article->Save();

        return $article->ToJson();
    }

    // DELETE /articles/{id}
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
- Return plain arrays or `->ToJson()` objects — the router handles JSON encoding
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

        return ["token" => $token, "user" => $user->ToJson()];
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

### Step 2: Register auth routes

```php
$api->Add("POST", "/auth/login",    new AuthControl(), "Login");
$api->Add("POST", "/auth/register", new AuthControl(), "Register");

// Set base auth for all protected routes
$api->BaseAuthorization(new AuthControl(), "ValidateToken");
```

### Step 3: Access user in other controllers

```php
public function Create(array $data = []): object {
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
- `GetAuthorizationToken()` throws if the header is missing — call it inside `try/catch` or rely on the route's `$auth` parameter

---

## 9. Caching

### In a controller (recommended pattern)

```php
public function List(): array {
    // 1. Serve from cache if available (outputs and exits on hit)
    $this->Cache("articles_published");

    // 2. Compute result
    $articles = ArticleControl::GetWhere(["published" => 1]);
    $result   = array_map(fn($a) => $a->ToJson(), $articles);

    // 3. Save to cache for next request
    $this->Cache("articles_published", json_encode($result));

    return $result;
}

public function Create(array $data = []): object {
    // ... create ...

    // Invalidate related cache
    $this->CacheClear("articles_published");

    return $article->ToJson();
}
```

### Invalidate by pattern (when many related keys exist)

```php
// Clears: articles_published.json, articles_draft.json, articles_page_1.json, etc.
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

// Simple log
Logger::Instance()->Log("Order #$orderId processed by user #$userId");

// Log an exception
try {
    // something risky
} catch (\Exception $e) {
    Logger::Instance()->LogError($e);
}

// Different log files per category
Logger::Instance()->SetLogFile("payments")->Log("Payment success: $amount");
Logger::Instance()->SetLogFile("auth")->Log("Login failed: $email");
```

### Debugging (development only)

```php
use Magrathea2\Debugger;

// Add data points during a request
Debugger::Instance()->Info("Cache miss for: articles_published");
Debugger::Instance()->Add(["user_id" => $id, "role" => $role]);

// Dump everything at end of request
Debugger::Instance()->Show();

// Enable query logging (via MagratheaPHP bootstrap)
MagratheaPHP::Instance()->Debug(); // -> Prod() disables this automatically
```

---

## 11. Sending Email

### Native mail()

```php
use Magrathea2\MagratheaMail;

$mail = new MagratheaMail();
$mail->SetTo($user->email)
     ->SetFrom("noreply@myapp.com")
     ->SetSubject("Welcome to MyApp!")
     ->SetHTMLMessage("<h1>Hi {$user->name}!</h1><p>Your account is ready.</p>")
     ->SetTXTMessage("Hi {$user->name}! Your account is ready.")
     ->Send();
```

### SMTP

```php
use Magrathea2\MagratheaMailSMTP;

$mail = new MagratheaMailSMTP(); // uses [mail] section from config
$mail->SetTo($user->email)
     ->SetSubject("Password Reset")
     ->SetHTMLMessage($htmlBody)
     ->Send();
```

### Simulate in non-production

```php
$mail = new MagratheaMail();

if (Config::Instance()->GetEnvironment() !== "production") {
    $mail->Simulate();
}

$mail->SetTo($email)->SetSubject("Test")->SetHTMLMessage("<p>Hi</p>")->Send();
```

---

## 12. Admin Panel

### Minimal admin entry point

```php
<?php
// admin/index.php
require_once __DIR__ . '/../vendor/autoload.php';

use Magrathea2\MagratheaPHP;
use Magrathea2\Admin\AdminManager;

MagratheaPHP::LoadVendor();
MagratheaPHP::Instance()
    ->AppPath(__DIR__ . '/..')
    ->AddCodeFolder("models", "controls", "admin")
    ->Prod()->Load()->Connect()->StartSession();

$manager = AdminManager::Instance()->StartDefault("My App Admin", "#2c3e50");

if (!$manager->Auth()) {
    header("Location: /admin/login.php");
    exit;
}
```

### Adding a CRUD feature for a model

```php
<?php
namespace App\Admin;

use Magrathea2\Admin\Features\CrudObject\AdminCrudObject;

class ArticleAdminFeature extends AdminCrudObject {
    protected $modelName        = "Article";
    protected $modelNamespace   = "App\\Models\\";
    protected $controlName      = "ArticleControl";
    protected $controlNamespace = "App\\Controls\\";
    protected $label            = "Articles";
    protected $icon             = "newspaper"; // Bootstrap icon name
}
```

```php
// Register it
$manager->GetAdmin()->AddFeaturesArray([new ArticleAdminFeature()]);
```

---

## 13. Error Handling

### In API controllers — always use MagratheaApiException

```php
use Magrathea2\Exceptions\MagratheaApiException;

// 400 Bad Request
if (empty($post["email"])) {
    throw new MagratheaApiException("Email is required", 400);
}

// 401 Unauthorized
throw new MagratheaApiException("Unauthorized", 401);

// 403 Forbidden
throw new MagratheaApiException("You don't have permission", 403);

// 404 Not Found
throw new MagratheaApiException("Resource not found", 404);

// 422 Validation failed
throw (new MagratheaApiException("Validation failed", 422))
    ->SetData(["errors" => $validationErrors]);

// Convert generic exception
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

## 14. Testing

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
        // Mock DB — no real queries
        Database::Instance()->Mock();

        // Mock config
        Config::Instance()->SetConfig([
            "database" => ["host" => "localhost", "database" => "test"],
            "jwt"      => ["secret" => "test-secret"],
        ]);
    }
}
```

### Testing query builder (no DB needed)

```php
public function testSelectQuery(): void {
    $sql = Query::Select()
        ->Table("articles")
        ->Where(["published" => 1])
        ->Order("created_at DESC")
        ->Limit(5)
        ->SQL();

    $this->assertStringContainsString("FROM articles", $sql);
    $this->assertStringContainsString("published = '1'", $sql);
    $this->assertStringContainsString("LIMIT 5", $sql);
}
```

### Testing a model

```php
public function testModelAssignment(): void {
    $article = new \App\Models\Article();
    $article->title     = "Test";
    $article->published = true;

    $this->assertEquals("Test", $article->title);
    $this->assertTrue($article->published);
    $this->assertTrue($article->IsEmpty()); // no PK set
}
```

---

## 15. Complete Application Skeleton

A minimal but complete structure for a new MagratheaPHP2 project:

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
├── public/
│   └── index.php         ← API entry point (web root)
├── admin/
│   ├── index.php         ← Admin entry point
│   └── features/
│       └── ArticleAdminFeature.php
├── cache/                ← writable by web server
├── logs/                 ← writable by web server
└── vendor/
```

**composer.json minimum:**

```json
{
    "require": {
        "magrathea/magrathea-php2": "^2.1"
    },
    "autoload": {
        "psr-4": {
            "App\\": "./"
        }
    }
}
```

**public/index.php:**

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Magrathea2\MagratheaPHP;
use Magrathea2\MagratheaApi;
use App\Api\AuthControl;
use App\Api\ArticleApiControl;

MagratheaPHP::LoadVendor();
MagratheaPHP::Instance()
    ->AppPath(__DIR__ . '/..')
    ->AddCodeFolder("models", "controls", "api")
    ->Prod()->Load()->Connect();

$api = new MagratheaApi();
$api->AllowAll()
    ->BaseAuthorization(new AuthControl(), "ValidateToken");

$api->Add("POST", "/auth/login", new AuthControl(), "Login");

$api->Add("GET",  "/articles",      new ArticleApiControl(), "List");
$api->Add("GET",  "/articles/{id}", new ArticleApiControl(), "Read");
$api->Add("POST", "/articles",      new ArticleApiControl(), "Create", true);
$api->Add("PUT",  "/articles/{id}", new ArticleApiControl(), "Update", true);
$api->Add("DELETE", "/articles/{id}", new ArticleApiControl(), "Delete", true);

$api->HealthCheck();
$api->Run();
```

---

## 16. Checklist Before Delivering Code

Use this before finalizing any generated code for a MagratheaPHP2 project.

### Bootstrap
- [ ] `MagratheaPHP::LoadVendor()` is called first
- [ ] `AppPath()` is set with `__DIR__` or an absolute path
- [ ] All class folders are registered with `AddCodeFolder()`
- [ ] `->Load()->Connect()` is called before any DB operation
- [ ] Mode is `->Dev()` for local, `->Prod()` for server

### Models
- [ ] `$dbTable` is set
- [ ] `$dbValues` includes all columns with correct types
- [ ] `$dbPk` is set if primary key is not `"id"`
- [ ] Model file is in a folder registered with `AddCodeFolder()`

### Controls
- [ ] `$modelName`, `$modelNamespace`, `$dbTable` are all set
- [ ] Namespace matches the actual file location

### API
- [ ] Every controller extends `MagratheaApiControl`
- [ ] All error cases throw `MagratheaApiException`, not generic exceptions
- [ ] User input is never directly interpolated into SQL (use `Query::Clean()` or `PrepareAndExecute()`)
- [ ] `$api->Run()` is the last line of the entry point
- [ ] Auth is set to `true` on routes that require a token
- [ ] `GetPost()` / `GetPut()` used (not `$_POST` directly)

### Config
- [ ] Secrets use `$=ENV_VAR` — never hardcoded in `.conf` files
- [ ] Config file is at `config/magrathea.conf` relative to `AppPath`

### General
- [ ] `now()` used for datetime fields (not `date()` directly)
- [ ] `->ToJson()` used in API responses, not `->ToArray()`
- [ ] Cache invalidated when data is mutated (`CacheClear` / `CacheClearPattern`)
- [ ] Singletons accessed via `::Instance()`, never `new ClassName()`
