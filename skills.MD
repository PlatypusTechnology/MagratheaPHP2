---
name: magrathea-php2
description: How to correctly generate code using the MagratheaPHP2 framework. Read before writing any PHP code for a project that uses this framework.
---

# skills.MD — How to Use MagratheaPHP2 in a Project

This file teaches an AI assistant how to correctly generate code using the MagratheaPHP2 framework. It is a practical, task-oriented cookbook. Read it before writing any PHP code for a project that uses this framework.

**This cookbook targets MagratheaPHP2 `2.4.0`.** Several behaviors described here (HTTP error statuses, one DB connection per request, `Transaction()`, `decimal`, the Bootstrap lock, the File Editor opt-in) don't exist in earlier versions; check the project's installed version (`vendor/platypustechnology/magratheaphp2/src/version`) before relying on them.

Reference files:
- `instructions.MD` — project structure and rules
- `docs/mds/index.md` in the framework's repository (not shipped in `vendor/`), or the docs site (https://www.platypusweb.com.br/magratheaphp2/docs/) — full API reference

**Upgrading** a project from 2.3.5 to 2.4.0: follow the migration guide, `docs/mds/migrations/migrate-2.4.0.md` in the framework's repository (not shipped in `vendor/`), or https://www.platypusweb.com.br/magratheaphp2/docs/?p=migrations/migrate-2.4.0 . It's self-contained: each section has a `grep` to tell whether it applies and the exact change to make.

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

**Every entry point starts the same way**, and in real projects this bootstrap lives in one shared file — conventionally `app/_inc.php` — that every entry point (`public/index.php`, `public/admin.php`) requires. Never skip any step.

Real projects organize domain code as **features**, not flat `models/`/`controls`/ folders: one folder per table/domain under `app/features/<Name>/`, added via `->AddFeature(...)` (see §3–4 for what goes inside). Plain `->AddRootCodeFolder(...)` is still used for non-feature folders like `admin`, `api`, `shared`.

```php
<?php
// app/_inc.php
require __DIR__ . "/../vendor/autoload.php";

error_reporting(E_ALL);
ini_set("display_errors", "1");

try {
    Magrathea2\MagratheaPHP::Instance()
        ->MinVersion("2.4.0")                   // optional, pins a minimum framework version
        ->AppPath(realpath(dirname(__FILE__)))  // this folder (app/) becomes appRoot
        ->AddRootCodeFolder(                    // relative to AppPath
            "admin",
            "api",
            "api/Authentication",
            "api/Controls",
            "shared",
        )
        ->AddFeature(                           // adds features/<Name> + features/<Name>/Base for each
            "Article", "Author",
        )
        ->Dev()                                 // or ->Prod() in production
        ->Load()                                // reads ../configs/magrathea.conf (AppPath's parent)
        ->Connect();                            // DB credentials; the connection itself opens on the first query
} catch (Exception $ex) {
    \Magrathea2\p_r($ex);
}
```

Entry points then just require it:

```php
<?php
// public/index.php
require __DIR__ . "/../app/_inc.php";
// ... build and Run() the API class ...
```

**Rules:**
- `AppPath()` must be called before `Load()`; `configs/magrathea.conf` (plural `configs`) is expected one level **above** `AppPath()` (i.e. `configs/` is a sibling of `app/`, not inside it)
- `AddRootCodeFolder()` paths are relative to `AppPath`; `AddCodeFolder()` takes paths **as given** (absolute, or relative to the current working directory, i.e. the entry script's folder under a web server: `AddCodeFolder("admin")` from `public/index.php` means `public/admin`). Call both, and `AddFeature()`, after `AppPath()`
- `AddFeature("Article", ...)` adds `<appRoot>/features/Article` and `<appRoot>/features/Article/Base` to the autoloader — see §3 for what lives in each
- The autoloader is **not** PSR-4: it looks for `<ClassName>.php` (the basename, without namespace) in every code folder. Give every class a basename that's unique across the code folders. With duplicates, it loads the file whose `namespace` matches (since 2.4.0; the first folder's before), and in DEV it adds a warning on every request that loads one. A class it can't find fails with PHP's own `Error: Class "…" not found` (since 2.4.0; `MagratheaException` before), so `class_exists()` is safe; in DEV, classes under `->AppNamespace("App")` also add a "Could not find class … Are the code folders correct?" debug item
- Use `->Dev()` locally, `->Prod()` on servers. They only set the Debugger mode (DEV prints errors, LOG writes them to the log); they do **not** choose the config environment (see §2)
- Call `->StartSession()` before any session use (admin panel, etc.)
- `->Connect()` (after `Load()`) is required before any DB use: it hands the config's `db_*` keys to `Database`; without it the first query throws `MagratheaDBException("DB not initialized")`. It doesn't open a connection: that happens on the first query, so calling it on every request is free

---

## 2. Configuration

**Config file location:** `configs/magrathea.conf`, in `AppPath()`'s **parent** folder (`MagratheaPHP::Instance()->GetConfigRoot()`), e.g. `my-app/configs/magrathea.conf` when `AppPath()` is `my-app/app`.

Sections are named by **environment**, not by topic. `[general]` holds cross-environment settings, including `use_environment`: the name of the active section. It must name a section that exists, or every `Config::Instance()->Get()` throws "Could not find environment". The only other way to pick the environment is `Config::Instance()->SetEnvironment("production")` in code; `->Dev()`/`->Prod()` don't change it. Each environment section (`[dev]`, `[production]`, …) repeats the full flat set of keys it needs — there is no `[section:env]` override/inheritance syntax.

### Writing a config file

```ini
[general]
	use_environment = "dev"

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

**Timezone:** the key is `timezone`, in each environment section (never `time_zone`, which is ignored, and never in `[general]`, which `Get()` doesn't read). `Load()` sets it as PHP's timezone, and since 2.4.0 `Connect()` also sets MySQL's session `time_zone` to PHP's on every connection, so `NOW()`/`CURRENT_TIMESTAMP` agree with `now()`. `db_sync_timezone = false` in the same section turns that off. Always set `timezone`; if MySQL lacks its timezone tables, the offset (`-03:00`) is used instead, which doesn't follow DST. The admin's Server page shows a timezone check (`Magrathea2\TimezoneCheck::Run()`).

**Bootstrap setup wizard** (`Magrathea2\Bootstrap\Start`, since 2.4.0): locked unless the **active** environment's section has `bootstrap_unlocked = true` (`true`/`1`/`on`/`yes`); locked, it answers 403. Put the flag in `[dev]` only, never in `[production]` (except temporarily, removed right after). In `[general]` it's ignored. Projects that don't use the wizard don't need it.

### Reading config in code

```php
use Magrathea2\Config;

// Read a single key from the active environment section (null if missing)
$host = Config::Instance()->Get("db_host");

// Read a key from a specific section: "section/key" (GetConfig() does NOT use the environment)
$prodHost = Config::Instance()->GetConfig("production/db_host");

// Read the full active-environment section (the section name is required)
$env = Config::Instance()->GetConfigSection(Config::Instance()->GetEnvironment());
echo $env["db_host"];
```

`Get()` and `GetConfig()` resolve `$=ENV_VAR` values; `GetConfigSection()` returns them raw.

**Never hardcode credentials.** Use `$=ENV_VAR_NAME` for secrets.

---

## 3. Creating a Model

A model maps to one database table, and lives in its own **feature** folder (added via `->AddFeature("Article", ...)` in `_inc.php`, see §1). This is the convention observed across real production Magrathea2 projects — prefer it over a flat `models/` folder.

Each feature is **split into a generated `Base` class and a concrete class**:

- `app/features/Article/Base/ArticleBase.php` — holds `$dbTable`, `$dbPk`, `$dbValues`, and FK **relations**. Marked as generated ("changes can be overwritten through the admin") because Magrathea's admin tooling can regenerate it from the DB schema — but hand-writing it in this exact shape is normal when that tooling isn't in play.
- `app/features/Article/Article.php` — the concrete class, `extends Base\ArticleBase`, empty unless you add real business-logic methods.

**Namespaces follow the feature, not the class kind** (the admin generator's default `feature` structure, with app namespace `App`): everything in `features/Article/` is `App\Article\…` (`App\Article\Article`, `App\Article\ArticleControl`, `App\Article\ArticleAdmin`), and everything in `features/Article/Base/` is `App\Article\Base\…`. Don't mix in `App\Models`/`App\Controls` namespaces unless the project already uses the generator's `mvc` structure: the CRUD admin (§14) finds the control as `<model class>Control` unless it's passed explicitly.

```php
<?php
// app/features/Article/Base/ArticleBase.php
## FILE GENERATED BY MAGRATHEA.
## This file was automatically generated and changes can be overwritten through the admin

namespace App\Article\Base;

use Magrathea2\iMagratheaModel;
use Magrathea2\MagratheaModel;

class ArticleBase extends MagratheaModel implements iMagratheaModel {

    public $id, $title, $body, $author_id, $published, $views;
    public $created_at, $updated_at;
    protected $autoload = null;
    protected $strictTypes = true; // new models: typed values on load (see "Supported field types")

    public function __construct($id = 0) {
        $this->MagratheaStart();
        if (!empty($id)) {
            $pk = $this->dbPk;
            $this->$pk = $id;
            $this->GetById($id);
        }
    }

    public function MagratheaStart() {
        $this->dbTable = "articles";
        $this->dbPk = "id";
        $this->dbValues["id"] = "int";
        $this->dbValues["title"] = "string";
        $this->dbValues["body"] = "text";
        $this->dbValues["author_id"] = "int";
        $this->dbValues["published"] = "boolean";
        $this->dbValues["views"] = "int";
        $this->dbValues["created_at"] = "datetime";
        $this->dbValues["updated_at"] = "datetime";

        // FK relation example — lazy-loaded getter/setter pair
        $this->relations["properties"]["Author"] = null;
        $this->relations["methods"]["Author"] = "GetAuthor";
        $this->relations["lazyload"]["Author"] = "true";
        $this->relations["external"]["author_id"] = "\App\Author\Author";
    }

    public function GetControl() {
        return new \App\Article\Base\ArticleControlBase();
    }

    // >>> relations:
    public function GetAuthor() {
        if ($this->relations["properties"]["Author"] != null) return $this->relations["properties"]["Author"];
        $this->relations["properties"]["Author"] = new \App\Author\Author($this->author_id);
        return $this->relations["properties"]["Author"];
    }
    public function SetAuthor($author) {
        $this->relations["properties"]["Author"] = $author;
        $this->author_id = $author->GetID();
        return $this;
    }
}
```

```php
<?php
// app/features/Article/Article.php
namespace App\Article;

class Article extends \App\Article\Base\ArticleBase {

    public function __construct($id = 0) {
        parent::__construct($id);
    }

    // real business-logic methods go here, e.g.:
    public function IsPublished(): bool {
        return (bool) $this->published;
    }
}
```

**Important:** declare every `$dbValues` field as a public property on the Base class (untyped, comma-declared as above, matching the generator's style). Do NOT use `#[\AllowDynamicProperties]`.

For simple internal tools without the feature/Base convention, a flat single-class model (`$dbTable`/`$dbPk`/`$dbValues` directly in one class extending `MagratheaModel`) also works — but match whatever convention the rest of the project already uses.

### Supported field types

| Type | PHP equivalent |
|------|---------------|
| `int` | integer |
| `boolean` | bool (stored as TINYINT 0/1) |
| `string` | string (VARCHAR etc.) |
| `text` | string (TEXT column) |
| `float` | float (FLOAT/DOUBLE — approximate) |
| `decimal` | string (DECIMAL — exact, e.g. money; never cast to float, even with `$strictTypes`). Requires `2.4.0`+ |
| `datetime` | string in `Y-m-d H:i:s` format |
| `date` | string in `Y-m-d` format (SQL DATE column) |
| `uuid` | string (CHAR(36)), auto-generated UUIDv7 on insert if unset |

Declare `$this->dbValues["uuid"] = "uuid";` and the framework fills it in on `Insert()` (via `Uuid::V7()`) whenever the property is left empty. An explicitly pre-set value (e.g. from a fixture or migration) is respected and never overwritten. Nothing changes on `Update()` — UUIDs are immutable once assigned. Requires MagratheaPHP2 `2.1.30`+.

**Collision safety is not enforced in application code** — there is no pre-insert uniqueness check and no catch/retry on a duplicate-key error; `Uuid::V7()` relies purely on `random_bytes(10)` entropy. Always declare the `uuid` column `PRIMARY KEY`/`UNIQUE` in the DB schema so uniqueness is actually guaranteed, not just statistically likely.

`date` (requires MagratheaPHP2 `2.2.2`+) normalizes its value to `Y-m-d` on `Insert()`/`Update()`: it accepts `"YYYY-MM-DD"`, `"YYYY-MM-DD HH:MM:SS"`, and ISO-8601 (`"YYYY-MM-DDTHH:MM:SS.sssZ"`), and throws `MagratheaModelException` for anything else. The date part is taken as written — **no timezone conversion** — so prefer sending plain `"YYYY-MM-DD"` from clients to avoid off-by-one-day issues east of UTC. Existing models that declare a SQL `date` column as `"datetime"` keep working unchanged; the `"date"` type is picked up when objects are regenerated.

By default, values loaded from the DB (`GetById()`, `LoadObjectFromTableRow()`, list queries) are **not** type-coerced — mysqli/PDO return every column as a string, so `int`/`boolean`/`float` properties come back as strings unless cast by hand. `protected $strictTypes = true;` on a model (or its Base class) casts `int`/`boolean`/`float` values to their native PHP type on load (`string`/`text`/`uuid`/`date`/`datetime` stay untouched, `null` stays `null`; `decimal` must be numeric but stays the exact string). A value that can't be cast throws `MagratheaModelException`. Declare SQL `DECIMAL` columns as `"decimal"`, never `"float"`: a strict `float` field rounds a large DECIMAL on load, and `Update()` writes the rounded value back. Do decimal math with `bcmath`, not `+`/`*`. Requires MagratheaPHP2 `2.3.0`+.

**`$strictTypes = true` is recommended for new models**, generated or hand-written. The framework default stays `false`, so existing models are unaffected. Since 2.4.0 the admin's object generator emits the line for new objects (the object form's "Strict types" select, saved as `strict_types` in `magrathea_objects.conf`); objects created before 2.4.0 have no `strict_types` key, so regenerating their Base class doesn't add it. **Don't turn it on for an existing model without checking its API consumers**: its JSON changes from `"42"` to `42` and from `"1"` to `true`.

`created_at`/`updated_at` (declared in every generated model's `$dbValues`) are **not** like `uuid`:
- `Insert()` skips them while they're `null`, so the **DB** fills them (column `DEFAULT CURRENT_TIMESTAMP` or a trigger). The table's timestamp columns must have a default: `NOT NULL` with no default fails every insert; nullable stays `NULL`.
- Assigning one before `Insert()` makes `Insert()` send it, but always as PHP's `now()`, never the assigned value. To insert rows with their original timestamps (imports), use `Query::Insert()`.
- After `Insert()`, the object holds PHP's `now()` for both, not the stored row values; `GetById()` again if you need the DB's.
- `Update()` writes `updated_at = now()` (PHP clock) and never `created_at` (exception: a never-loaded object updated after a `Set()` doesn't write `updated_at`). `Set()` ignores both.
- Inserts use MySQL's clock, updates PHP's: they agree when the config has `timezone` (MySQL's session timezone is synced to PHP's since 2.4.0, unless `db_sync_timezone = false`).

### Using a model

```php
// Create and insert
$article = new Article();
$article->title     = "Hello World";
$article->body      = "My first article.";
$article->published = true;
$id = $article->Save(); // returns new ID (or ->Insert() explicitly)

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

**What `Update()` writes** (since 2.4.0):
- **Loaded object** (`new Article(42)`, `GetById()`, any Control query, or after its own `Insert()`/`Update()`): only the fields changed since then, by plain assignment or `Set()`/`Assign()`, plus `updated_at`. Other columns are left alone. `5`/`"5"` and `true`/`"1"` count as the same value; `null` only equals `null`.
- **Never-loaded object** (`new Article(); $a->id = 42; … Update()`): with any `Set()`/`Assign()`, only those fields (plain assignments are dropped); otherwise every field, except `null` int/boolean fields (skipped). So for updates, load the object first (`new Article($id)`) instead of building one by hand.
- **Clearing an int/boolean (e.g. a nullable FK):** on a loaded object, `$a->category_id = null; $a->Save();` writes SQL `NULL`. `""` (an empty form field) in an int/boolean field is always written as `NULL`, never `0`: a `NOT NULL` int column needs a real value.
- `LoadObjectFromTableRow()` marks the object as loaded: use it for DB rows only, never for input (`$_POST`, `GetPut()`): use `Assign()`.

---

## 4. Creating a Control

Every model gets a companion Control class for static data access — same feature-folder Base split as the model.

```php
<?php
// app/features/Article/Base/ArticleControlBase.php
## FILE GENERATED BY MAGRATHEA.
## This file was automatically generated and changes can be overwritten through the admin

namespace App\Article\Base;

use Magrathea2\MagratheaModelControl;

class ArticleControlBase extends MagratheaModelControl {
    protected static $modelNamespace = "App\\Article";
    protected static $modelName = "Article";
    protected static $dbTable = "articles";
}
```

```php
<?php
// app/features/Article/ArticleControl.php
namespace App\Article;

use Magrathea2\DB\Query;

class ArticleControl extends \App\Article\Base\ArticleControlBase {

    // real query/business-logic methods go here; Control methods are static, e.g.:
    public static function GetPublished() {
        return static::GetWhere(["published" => 1]);
    }
}
```

### Using a control

```php
use App\Article\ArticleControl;

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

// Paginated (with total — costs an extra COUNT(*) query)
$total = 0;
$query = \Magrathea2\DB\Query::Select()
    ->Obj(new \App\Article\Article())
    ->Where(["published" => 1])
    ->Order("created_at DESC");
$page = ArticleControl::RunPagination($query, $total, page: 0, limit: 10);

// Paginated for an API response — no COUNT(*) query, has_more computed via limit+1 trick.
// Return the MagratheaPagination directly from a controller action; see §7.
$query = \Magrathea2\DB\Query::Select()
    ->Obj(new \App\Article\Article())
    ->Where(["published" => 1])
    ->Order("created_at DESC");
$pagination = ArticleControl::GetPagination($query, page: 0, limit: 10); // MagratheaPagination
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
    ->Obj(\App\Article\Article::class)
    ->Where(["published" => 1])
    ->SQL();

// JOIN
$sql = Query::Select()
    ->Obj(\App\Article\Article::class)
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

**Never interpolate user input into SQL unescaped.** The query builder's array forms (`Where([...])`, `SetArray()`, `Values()`) escape values for you. For a hand-built fragment, use `Database::Instance()->Escape()` (it wraps `mysqli::real_escape_string`). `Query::Clean()` is deprecated: since 2.4.0 it just calls `Escape()`, and before that it didn't escape backslashes, so it wasn't injection-safe.

```php
$safe = Database::Instance()->Escape($_GET["search"]);
$rows = Database::Instance()->QueryAll("SELECT * FROM articles WHERE title LIKE '%$safe%'");
```

`PrepareAndExecute()` is for **writes**: it returns the insert id (or `true`), never rows, so don't use it for a SELECT. Its types are Magrathea field types (`"int"`, `"boolean"`, `"float"`, `"string"`, `"text"`, …), not mysqli letters:

```php
$id = Database::Instance()->PrepareAndExecute(
    "INSERT INTO articles (title, author_id) VALUES (?, ?)",
    ["string", "int"],
    [$post["title"], $userId]
);
```

Every DB error throws `MagratheaDBException` (since 2.4.0, whatever mysqli's error reporting mode): `getCode()` is MySQL's errno (`1062` duplicate entry…), `query`/`values` hold the statement, and mysqli's own exception is `getPrevious()`. That includes the model's `Insert()`/`Update()`/`Save()`/`Delete()`. Catch `MagratheaDBException`, never `mysqli_sql_exception`, and never check `if (!$obj->Insert())`. In an API it's always a 500; turn expected errors into a `MagratheaApiException`:

```php
use Magrathea2\Exceptions\MagratheaDBException;

try {
    $user->Insert();
} catch (MagratheaDBException $ex) {
    if ($ex->getCode() !== 1062) throw $ex;
    throw new MagratheaApiException("Email already registered", 409);
}
```

### Connections and long-running workers

Since 2.4.0, each `Database` instance opens one connection on its first query and keeps it until the end of the request. Session state (`SET @var`, `GET_LOCK()`, temporary tables, `LAST_INSERT_ID()`) lasts the whole request. `CloseConnectionThanks()` does nothing anymore; `Database::Instance()->LastAffectedRows()` returns the rows affected by the last query.

**CLI scripts that loop (workers, daemons) must call `Disconnect()` at the start of each job**, or the first query after an idle longer than MySQL's `wait_timeout` fails:

```php
use Magrathea2\DB\Database;
use Magrathea2\Exceptions\MagratheaDBConnectionLostException;

while (true) {
    Database::Instance()->Disconnect(); // next query opens a fresh connection
    $job = NextJob();
    try {
        ProcessJob($job);
    } catch (MagratheaDBConnectionLostException $ex) {
        // code 2006: statement probably not executed; 4031 (idle timeout): not executed;
        // 2013: it may have executed.
        // The framework never retries. Retry only if ProcessJob() is idempotent.
        Requeue($job);
    }
}
```

`MagratheaDBConnectionLostException` extends `MagratheaDBException`; the dead connection is already dropped when it's thrown, so the next call reconnects. A second database is a subclass (`class ReportsDb extends Database {}` → `ReportsDb::Instance()`), with its own connection; models, controls and the query builder always use `Database::Instance()`.

### Transactions

Wrap related writes in `Database::Instance()->Transaction(fn)`: everything inside (model `Insert()`/`Update()`/`Delete()`, controls, raw queries, `SELECT … FOR UPDATE`) commits when the callback returns, or rolls back if it throws (the exception is rethrown unchanged). It returns the callback's result.

```php
$orderId = Database::Instance()->Transaction(function() use ($cart) {
    $stock = Database::Instance()->QueryOne("SELECT stock FROM products WHERE id = 5 FOR UPDATE");
    if ($stock < 1) throw new MagratheaApiException("Out of stock", 409);
    $order = new Order();
    $order->user_id = $cart->user_id;
    $order->Insert();
    Database::Instance()->Query("UPDATE products SET stock = stock - 1 WHERE id = 5");
    return $order->id;
});
```

**Rules:**
- Nested `Transaction()` calls run inline (no savepoints): an inner throw rolls back everything, even if outer code catches it.
- Never catch-and-continue DB exceptions inside the callback: after a lost connection, later queries would run on a new autocommit connection, outside the transaction.
- No DDL (`CREATE`/`ALTER`/`TRUNCATE`) inside: MySQL commits implicitly.
- Don't call `QueryTransaction()` inside it (it throws), and don't end the request (`die`, `Json()`) inside it.
- Prefer it over `QueryTransaction(array $sql)`, which only takes fixed SQL strings.

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
        $this->HealthCheck(true); // includes "database": "ok"|"fail" in response
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
$api = require __DIR__ . '/api.php';
$api->Run();
```

**Shared setup file** (`public/api.php`) — returns the configured instance, importable by both entry point and admin:

```php
<?php
require_once __DIR__ . '/../app/_inc.php'; // the shared bootstrap from §1
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
use Magrathea2\MagratheaPagination;
use Magrathea2\Exceptions\MagratheaApiException;
use Magrathea2\DB\Query;
use App\Article\ArticleControl;
use App\Article\Article;

class ArticleApiControl extends MagratheaApiControl {

    // GET /articles
    public function List(): array {
        $articles = ArticleControl::GetWhere(["published" => 1]);
        return array_map(fn($a) => $a->ToArray(), $articles);
    }

    // GET /articles/paginated?page=0 — returning a MagratheaPagination makes
    // MagratheaApi::ReturnSuccess() build {success, data, page, count, has_more} automatically.
    public function ListPaginated(array $params = []): MagratheaPagination {
        $query = Query::Select()
            ->Obj(new Article())
            ->Where(["published" => 1])
            ->Order("created_at DESC");
        return ArticleControl::GetPagination($query, page: (int)($params["page"] ?? 0), limit: 20);
    }

    // GET /articles/:id
    public function Read($params = []): array {
        $article = ArticleControl::GetRowWhere(["id" => $params["id"]]);
        if (!$article) {
            throw new MagratheaApiException("Article not found", 404);
        }
        return $article->ToArray();
    }

    // POST /articles
    public function Create($data = []): array {
        $post = $this->GetPost(); // reads JSON or form body

        $article = new Article();
        $article->title      = $post["title"]     ?? "";
        $article->body       = $post["body"]       ?? "";
        $article->author_id  = $this->GetUserId(); // from JWT
        $article->published  = false;
        $article->Save();

        return $article->ToArray();
    }

    // PUT /articles/:id
    public function Update($params = []): array {
        $article = ArticleControl::GetRowWhere(["id" => $params["id"]]);
        if (!$article) {
            throw new MagratheaApiException("Article not found", 404);
        }

        $put = $this->GetPut();
        if (isset($put["title"])) $article->title = $put["title"];
        if (isset($put["body"]))  $article->body  = $put["body"];
        $article->Save();

        return $article->ToArray();
    }

    // DELETE /articles/:id
    public function Delete($params = []): bool {
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
- Use `$this->GetPost()` for POST body, `$this->GetPut()` for PUT body — `GetPut()` also reads a `PATCH` body (or call its alias `$this->GetPatch()`); `Crud()` only ever registers `Update` under `PUT`, so a `PATCH` route on the same handler must be added separately with `Add("PATCH", ...)`
- Use `$this->GetUserId()` to read the user ID from the decoded JWT token
- Return `->ToArray()` (flat field map) for API responses — never `->ToJson()` (which wraps fields in an outer envelope)
- Never `echo` or `die()` inside controllers

---

## 8. JWT Authentication

### Step 1: Auth controller

```php
<?php
namespace App\Api;

use Magrathea2\MagratheaApiAuth;
use Magrathea2\Exceptions\MagratheaApiException;
use Magrathea2\Config;
use App\User\UserControl;

// The class passed to BaseAuthorization() must extend MagratheaApiAuth (a MagratheaApiControl
// subclass); a plain MagratheaApiControl throws a TypeError there.
class AuthControl extends MagratheaApiAuth {

    // GetSecret() already reads "jwt_key" from the active environment section
    // by default (configs/magrathea.conf) — only override it for a different key name.
    public function GetSecret(): string {
        return Config::Instance()->Get("jwt_key");
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
            throw new MagratheaApiException("Unauthorized", 401); // code 0 would be HTTP 500
        }

        $this->userInfo = $payload; // store for downstream use
        return true;
    }
}
```

### Step 2: Access user in other controllers

```php
public function Create($data = []): array {
    $userId = $this->GetUserId();       // int|null from token
    $info   = $this->GetUserInfo();     // full decoded payload object
    $role   = $info->role ?? "guest";
    // ...
}
```

**JWT rules:**
- Always set `"exp"` in the payload
- Never store passwords or sensitive PII in the token
- The secret comes from the config (`jwt_key`, read by the default `GetSecret()`), never a hardcoded string
- `GetAuthorizationToken()` throws if the header is missing — call it inside `try/catch` or rely on the route's auth parameter

### Step 3: Cookie-based auth (opt-in)

`GetTokenInfo()` also falls back to a cookie after `Bearer`/`Basic`, but only once a project overrides `$cookieName` — it is `null` by default, so this is fully opt-in and doesn't change behavior for projects that don't touch it. Useful for sharing a session across sibling subdomains via a cookie scoped to a parent domain.

```php
class AuthControl extends MagratheaApiAuth {

    // Enables the cookie fallback in GetTokenInfo(); override on a shared
    // base ApiControl class if more than one controller needs it.
    protected ?string $cookieName = "app_session";

    public function Login(): array {
        // ... validate credentials, build $token as above ...

        // Issues the cookie; expiry is derived from the token's own "exp" claim
        $this->SetAuthCookie($token, ".example.com"); // domain scoped to share across subdomains
        return ["token" => $token];
    }

    // HttpOnly cookies can't be cleared client-side — logout needs a real endpoint
    public function Logout(): array {
        $this->ClearAuthCookie(".example.com");
        return ["success" => true];
    }
}
```

**Cookie auth rules:**
- Header wins when both a header and a matching cookie are present
- `SetAuthCookie()`/`ClearAuthCookie()` are `Secure` by default (the `$forceSecureCookie` instance property, defaults `true`) — for local `http://localhost` development, override `$forceSecureCookie` to `false` on your `ApiControl` subclass, or pass `forceSecure: false` per call
- Two `ApiControl`s sharing a cookie need the *same* `$cookieName` **and** the same `$domain` passed to `SetAuthCookie()` — mismatched `Domain` scopes on the same cookie name is the one combination to avoid

---

## 9. Caching

`Cache($name, $data=null)` does **not** take the response payload as its second argument. `$data` is an optional key *suffix/discriminator* — it gets appended to `$name` (as `"{$name}-{$data}"`) to build the cache file's handle, e.g. to key the cache by an id or page number. Never pass a JSON blob or the computed result as `$data`.

Calling `$this->Cache($name, $data)` only *peeks*: if a cache file for that key already exists, it outputs it and kills execution (`die`) right there. It never writes anything itself. The actual write-to-disk happens automatically later, when the framework serializes the controller's return value to JSON — no second manual call is needed or correct.

Only successful responses are cached. `CacheClear()`/`CacheClearPattern()` don't touch the key set by `Cache()`, so an action may call both. API calls in raw mode (`SetRaw()`, `Run(true)`, e.g. from tests) bypass the cache entirely: the action always runs and nothing is saved.

### In a controller (recommended pattern)

```php
public function List(): array {
    // Serve from cache if available (outputs and exits on hit).
    // Saving happens automatically when the response is emitted.
    $this->Cache("articles_published");

    $articles = ArticleControl::GetWhere(["published" => 1]);
    return array_map(fn($a) => $a->ToArray(), $articles);
}

public function View(array $params = []): array {
    // $data suffix keys the cache per-id
    $this->Cache("article", $params["id"]);

    $article = new Article($params["id"]);
    return $article->ToArray();
}

public function Create($data = []): array {
    // ... create ...

    // Invalidate related cache (same $name/$data used to build the key)
    $this->CacheClear("articles_published");

    return $article->ToArray();
}
```

### Invalidate by pattern (when many related keys exist)

```php
$this->CacheClearPattern("articles_*");
```

### Cache key naming convention

Always include parameters that affect the result. Prefer the `$data` suffix param for a single discriminator (id, page); bake the rest into `$name`:

```php
$this->Cache("articles_cat_{$categoryId}", $page);
$this->Cache("user_profile", $userId);
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

In DEV, debug items (including every `MagratheaException`, even caught ones) print as `<pre>` where
they're added; `->Debug()` mode is `Debugger::DEBUG`, not DEV: it stores them and prints them all at the
end of the request (plus query logging). Inside API calls neither prints: the items go to the JSON
response as `_debug` (since 2.4.0), which only DEV/DEBUG responses have. Never `echo`/`var_dump` in an
endpoint to debug it: that breaks the JSON; use `Debugger::Instance()->Add(...)` and read `_debug`.

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

use Magrathea2\Config;

// SMTP settings are never read from the config automatically: pass them in
$config = Config::Instance();
$mail = new MagratheaMailSMTP();
$mail->SetSMTP($config->Get("smtp_host"), $config->Get("smtp_port"), $config->Get("smtp_user"), $config->Get("smtp_pass"));
$mail->SetFrom("noreply@myapp.com")
     ->SetTo($user->email)
     ->SetSubject("Reset")
     ->SetHTMLMessage($html);
if (!$mail->Send()) {
    Logger::Instance()->Log("mail failed: " . $mail->GetError());
}
```

The `smtp_*` key names above are the project's choice (any names work). Port `587` uses STARTTLS, other ports SMTPS, unless `SetSMTPArray()` passes `smtp_encryption`. `Send()` returns `false` when validation fails (missing message or SMTP setting; `GetError()` says which); a failed SMTP send throws PHPMailer's exception.

---

## 12. Admin Panel — Entry Point

The admin entry point bootstraps Magrathea (including `StartSession()`), then hands off to `AdminManager`.

```php
<?php
// public/admin.php

use Magrathea2\Admin\AdminManager;
use Magrathea2\MagratheaPHP;

require __DIR__ . "/../app/_inc.php"; // shared bootstrap from §1 (no Run())
MagratheaPHP::Instance()->StartSession();

try {
    AdminManager::Instance()->Start(new \App\MyAppAdmin()); // app/admin/MyAppAdmin.php, autoloaded
} catch (Exception $ex) {
    \Magrathea2\p_r($ex);
}
```

**Rules:**
- `->StartSession()` must run before `Start()` for admin auth to work (here, or in `_inc.php`)
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
use App\Article\ArticleAdmin;

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
- `SetFeatures()` — orchestrates LoadConfig / LoadFeatures / LoadApi; always call `parent::SetFeatures()` first (it loads App Config, Cache, Users and User Logs)
- File Editor — **not** loaded by default since 2.4.0 (any logged-in admin can edit server files with it). Only if the project really needs it, add `$this->LoadFileEditor();` after `parent::SetFeatures()`. Don't add it by default
- `LoadApi()` — creates `ApiExplorer`, calls `SetApi(new YourApiClass())`, adds feature
- `LoadFeatures()` — calls `AddCrudFeature()` for each CRUD admin class
- `BuildMenu()` — constructs the sidebar; use `CreateTitle()` for section headers, `AddFeaturesMenu()` to auto-include CRUD items, always end with `GetLogoutMenuItem()`
- `AddFeature()` vs `AddCrudFeature()` — use `AddCrudFeature` for `AdminCrudObject` subclasses, `AddFeature` for everything else

---

## 14. Admin Panel — CRUD Features

Each model gets a companion CRUD admin class extending `AdminCrudObject` (the admin generator writes it as `features/Article/ArticleAdmin.php`). Configure it in `Initialize()` by handing it a model instance; there are no `$modelName`/`$controlName` properties.

```php
<?php
// app/features/Article/ArticleAdmin.php
namespace App\Article;

class ArticleAdmin extends \Magrathea2\Admin\Features\CrudObject\AdminCrudObject {
    public string $featureName = "Articles"; // menu/page title (default: "Article CRUD")

    public function Initialize() {
        $this->SetObject(new Article());
    }
}
```

**How `SetObject()` finds the control:** `SetObject($model, $control = null)`. Without a control, it instantiates `<model's full class name>Control`, so `App\Article\Article` → `App\Article\ArticleControl` (works in the feature layout from §3). If the control lives in another namespace (e.g. `App\Models\Article` + `App\Controls\ArticleControl`), pass it: `$this->SetObject(new Article(), new ArticleControl())`; otherwise `SetObject()` throws a `MagratheaModelException` naming the missing class. The feature id defaults to `CRUD<Model>` (`CRUDArticle`) and becomes the menu link (`?magrathea_feature=CRUDArticle`). The menu has no icons.

### Permissions

By default `AdminCrudObject` allows everything to any logged-in admin. Override `HasPermission()`: it's called with the subpage about to run (`null` for the list page, then `"List"`, `"Form"`, `"Save"`, `"Delete"`); returning `false` shows the permission-denied page.

```php
class ArticleAdmin extends \Magrathea2\Admin\Features\CrudObject\AdminCrudObject {
    // ... Initialize() ...

    public function HasPermission($action = null): bool {
        return !in_array($action, ["Save", "Delete"]); // read-only
    }
}
```

### CSRF protection

The admin panel enforces a CSRF token on every authenticated POST request, automatically, with no opt-in/opt-out — this is not something you configure. Forms rendered through `AdminForm`/`AdminCrudObject` and AJAX calls made through the shared `ajax()`/`callAction()`/`callApi()`/`callFeature()` JS helpers already carry the token; nothing to do for standard CRUD features or forms.

Only relevant if you write a raw `<form>` or a raw AJAX call bypassing those helpers:

```php
use Magrathea2\Admin\AdminCsrf;

// inside a raw <form method="post">
echo '<input type="hidden" name="magrathea_csrf_token" value="'.AdminCsrf::Instance()->GetToken().'" />';
```

Or, for a raw AJAX call, send the same value as an `X-Magrathea-CSRF-Token` header instead of a form field. A POST with a missing or invalid token is rejected (`AdminManager::PermissionDenied()`) before it reaches your code. GET requests are never checked.

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

The code sets the HTTP status of the error response: `404` → 404, `4031` → 403 (4 digits: code / 10, for
app sub-codes), `40312` → 403 (5 digits: code / 100). Code `0`, or anything that doesn't map to 400-599
(e.g. MySQL's `1062`), gives **500**, so never throw code 0 for an expected error. To send a status that
differs from the code: `(new MagratheaApiException("Token missing", 400))->SetStatus(401)`. Exceptions that
aren't `MagratheaApiException` get the same mapping; code 0 → 500 with `{"message": ...}`. A DB error
(`MagratheaDBException`, whose code is MySQL's errno) is always 500: to answer a duplicate entry with a
409, catch it and throw a `MagratheaApiException` (see §5).

A PHP `\Error` in an action (`TypeError`, calling an undefined method...) becomes a JSON 500, logged with
`Logger::LogError()`; the client gets the message only in DEV ("Internal Server Error" otherwise). Don't
catch `\Error`s in controllers just to return JSON: the router already does.

Every error response is a real 4xx/5xx (since 2.4.0). Client code must read errors in the HTTP client's
error path (axios `catch`, jQuery `.fail`), not by checking `success === false` in the success handler.

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
        Database::Instance()->Mock(); // queries go to DatabaseSimulate, no MySQL needed
        // same shape as magrathea.conf: [general] + one flat environment section
        Config::Instance()->SetConfig([
            "general" => ["use_environment" => "test"],
            "test"    => ["db_host" => "localhost", "db_name" => "test", "jwt_key" => "test-secret"],
        ])->SetEnvironment("test");
    }
}
```

---

## 17. Complete Application Skeleton

```
my-app/
├── composer.json
├── configs/
│   └── magrathea.conf        ← AppPath's parent + /configs
├── app/                      ← AppPath
│   ├── _inc.php              ← shared bootstrap (§1), no output
│   ├── features/
│   │   └── Article/          ← namespace App\Article
│   │       ├── Article.php
│   │       ├── ArticleControl.php
│   │       ├── ArticleAdmin.php       ← CRUD feature
│   │       └── Base/         ← namespace App\Article\Base
│   │           ├── ArticleBase.php
│   │           └── ArticleControlBase.php
│   ├── api/                  ← namespace App / App\Api
│   │   ├── MyAppApi.php      ← extends MagratheaApi
│   │   ├── AuthControl.php
│   │   └── ArticleApiControl.php
│   └── admin/
│       ├── MyAppAdmin.php    ← Admin class
│       └── logo.svg
├── public/                   ← web root
│   ├── api.php               ← returns new MyAppApi() (importable)
│   ├── index.php             ← require api.php → Run()
│   └── admin.php             ← AdminManager::Start(new MyAppAdmin())
├── cache/
├── logs/
└── vendor/
```

**`app/_inc.php`** — shared setup, no output (full version in §1):

```php
<?php
require __DIR__ . "/../vendor/autoload.php";

Magrathea2\MagratheaPHP::Instance()
    ->AppPath(realpath(__DIR__))   // app/ → configs are read from ../configs/
    ->AddRootCodeFolder("admin", "api")
    ->AddFeature("Article")
    ->Prod()
    ->Load()
    ->Connect();
```

**`public/api.php`** — importable, returns the configured API:

```php
<?php
require_once __DIR__ . "/../app/_inc.php";
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
require __DIR__ . "/../app/_inc.php";
\Magrathea2\MagratheaPHP::Instance()->StartSession();

try {
    AdminManager::Instance()->Start(new \App\MyAppAdmin());
} catch (Exception $ex) {
    \Magrathea2\p_r($ex);
}
```

---

## 18. Checklist Before Delivering Code

### Bootstrap
- [ ] `vendor/autoload.php` is required first (`MagratheaPHP::LoadVendor()` can't do it: the class isn't loadable before the autoloader; it's deprecated, don't call it)
- [ ] `AppPath()` is set with `__DIR__` or an absolute path
- [ ] All class folders registered with `AddRootCodeFolder()` (or absolute paths in `AddCodeFolder()`), including `admin/` for admin panels
- [ ] `->Load()->Connect()` is called before any DB operation
- [ ] `->StartSession()` called when admin panel is used
- [ ] Mode is `->Dev()` for local, `->Prod()` for server

### Models
- [ ] `$dbTable` is set
- [ ] `$dbValues` includes all columns with correct types
- [ ] `$dbPk` is set if primary key is not `"id"`
- [ ] Each `$dbValues` field has a public property declaration (untyped, as the generator writes it)
- [ ] No `#[\AllowDynamicProperties]` attribute used
- [ ] If the project uses UUIDs as external ids, the field is declared as `"uuid"` type, not hand-rolled
- [ ] New models declare `protected $strictTypes = true;`; existing ones only after checking API consumers
- [ ] SQL `DECIMAL` columns are `"decimal"`, not `"float"`

### Controls
- [ ] `$modelName`, `$modelNamespace`, `$dbTable` are all set
- [ ] Same namespace as its model (`App\Article\ArticleControl` for `App\Article\Article`), matching the feature folder

### API Class
- [ ] Extends `MagratheaApi`
- [ ] Constructor calls `Initialize()`
- [ ] Routes grouped into domain methods
- [ ] `Run()` is NOT called inside the class — only in the HTTP entry point

### API Controllers
- [ ] Every controller extends `MagratheaApiControl`, except the class passed to `BaseAuthorization()`, which extends `MagratheaApiAuth`
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
- [ ] `Initialize()` calls `$this->SetObject(new Model())` with `<Model>Control` in the model's namespace, or `SetObject(new Model(), new ModelControl())` otherwise
- [ ] Optional: `public string $featureName` for the title, `HasPermission($action)` to restrict

### Admin Class (security)
- [ ] File Editor not loaded (`LoadFileEditor()`) unless the project really needs it

### Config
- [ ] Secrets use `$=ENV_VAR` — never hardcoded
- [ ] Config file at `configs/magrathea.conf` in `AppPath`'s **parent** folder
- [ ] `[general] use_environment` names a section that exists
- [ ] `bootstrap_unlocked = true`, if present, is only in `[dev]`

### General
- [ ] `now()` used for datetime fields
- [ ] Cache invalidated when data is mutated
- [ ] Singletons accessed via `::Instance()`, never `new ClassName()`
