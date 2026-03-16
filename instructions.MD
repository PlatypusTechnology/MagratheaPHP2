# Instructions for AI Assistants — MagratheaPHP2

This file is a guide for any AI assistant (Claude, GPT, Gemini, etc.) working on this codebase. Read it before doing any task. It explains the project structure, conventions, key design decisions, and how to approach common tasks.

---

## What This Project Is

**MagratheaPHP2** is a full PHP framework primarily designed for building RESTful APIs. It also ships with an ORM, a query builder, a file-based cache, a logging system, an email system, CSS/JS compression, and an admin panel.

- **Namespace root:** `Magrathea2`
- **Source code:** `src/`
- **Documentation:** `documentation/`
- **Config format:** INI files (`config/magrathea.conf`)
- **PHP version:** 8.0+
- **Database:** MySQL / MariaDB via MySQLi

---

## Source Code Map

```
src/
├── MagratheaPHP.php           ← Main bootstrap/entry point (start here)
├── Config.php                 ← INI-based config
├── ConfigApp.php              ← DB-stored key-value config
├── ConfigFile.php             ← Config file I/O
├── Singleton.php              ← Base singleton pattern
├── MagratheaHelper.php        ← Static utility functions
├── MagratheaModel.php         ← ORM base model (abstract)
├── MagratheaModelControl.php  ← ORM static query interface (abstract)
├── MagratheaApi.php           ← RESTful API router
├── MagratheaApiControl.php    ← API controller base
├── MagratheaCache.php         ← File-based response cache
├── MagratheaMail.php          ← Email (native mail())
├── MagratheaMailSMTP.php      ← Email (SMTP via PHPMailer)
├── Authentication.php         ← JWT token generation
├── Logger.php                 ← File-based logging
├── Debugger.php               ← Debug mode manager
├── _Functions.php             ← Global procedural helpers + autoloader
├── _FunctionsDebug.php        ← Debug helpers
├── _EnumTrait.php             ← Enum trait
├── DB/
│   ├── Database.php           ← MySQLi wrapper (Singleton)
│   ├── Query.php              ← Fluent SELECT query builder
│   ├── QueryInsert.php        ← INSERT query builder
│   ├── QueryUpdate.php        ← UPDATE query builder
│   ├── QueryDelete.php        ← DELETE query builder
│   ├── QueryHelper.php        ← Query utilities
│   └── DatabaseSimulate.php   ← Mock database for tests
├── Exceptions/
│   ├── MagratheaException.php     ← Base exception
│   ├── MagratheaApiException.php  ← API exceptions (carries HTTP status)
│   ├── MagratheaDBException.php   ← DB exceptions
│   ├── MagratheaConfigException.php
│   └── MagratheaModelException.php
├── Compressors/
│   ├── MagratheaCompressor.php    ← Abstract base
│   ├── CssCompressor.php          ← CSS + SCSS → minified CSS
│   └── JavascriptCompressor.php   ← JS → minified JS
├── Errors/
│   └── ErrorManager.php
├── Tests/
│   ├── TestsManager.php
│   ├── TestsHelper.php
│   └── phpUnitBootstrap.php
├── Bootstrap/                 ← Setup wizard
└── Admin/                     ← Admin panel system
    ├── Admin.php              ← Admin config object
    ├── AdminManager.php       ← Admin runtime (Singleton)
    ├── AdminFeature.php       ← Feature base class
    ├── AdminMenu.php          ← Menu builder
    ├── AdminForm.php          ← Form helpers
    ├── AdminElements.php      ← UI element helpers
    ├── AdminDatabase.php
    ├── AdminUrls.php
    ├── AdminUsers.php
    ├── ObjectManager.php
    ├── CodeCreator.php
    ├── CodeManager.php
    ├── iAdmin.php
    ├── Install.php
    ├── Start.php
    └── Features/
        ├── AppConfig/         ← Manage DB-stored config in admin
        ├── Cache/             ← Cache management
        ├── CrudObject/        ← Auto-generated CRUD UI
        ├── FileEditor/        ← Server-side file editor
        ├── User/              ← Admin user management
        ├── UserLogs/          ← Admin action log
        └── ApiExplorer/       ← API endpoint browser
```

---

## Key Concepts

### 1. Singleton Services
All manager/service classes are singletons. Always access them via `ClassName::Instance()`, never `new ClassName()`:
```php
Database::Instance()
Config::Instance()
Logger::Instance()
Debugger::Instance()
MagratheaCache::Instance()
AdminManager::Instance()
```

### 2. Model + Control Pair
Every database entity has two classes:
- **Model** (`extends MagratheaModel`): instance-level, handles a single row, provides CRUD operations
- **Control** (`extends MagratheaModelControl`): static-only, handles queries returning collections

### 3. Query Builder Doesn't Execute
`Query` only builds SQL strings. Execution is done by `Database::Instance()->QueryAll($query->SQL())` or through the Control methods.

### 4. API Structure
- `MagratheaApi` — the router/runner. Register routes with `->Add()` or `->Crud()`, then call `->Run()`.
- `MagratheaApiControl` — base for controllers. Override `List()`, `Read()`, `Create()`, `Update()`, `Delete()`.
- JWT auth is built-in: use `GetAuthorizationToken()`, `GetTokenInfo()`, `jwtEncode()`, `jwtDecode()`.

### 5. Configuration
Config files use INI format. Multi-environment: `[section:environment]` overrides `[section]` when that environment is active. Environment variable interpolation: `password = $=MY_ENV_VAR`.

---

## Common Tasks

### Adding a new database entity

1. Create `models/MyThing.php` extending `MagratheaModel`
2. Define `$dbTable`, `$dbPk`, `$dbValues`
3. Create `controls/MyThingControl.php` extending `MagratheaModelControl`
4. Set `$modelName`, `$modelNamespace`, `$dbTable`

### Adding a new API endpoint

1. Create/extend `api/MyThingApiControl.php` extending `MagratheaApiControl`
2. Override methods: `List()`, `Read()`, `Create()`, `Update()`, `Delete()`
3. Register in your entry point with `$api->Add(...)` or `$api->Crud(...)`

### Adding admin CRUD

1. Create a class extending `AdminCrudObject` in `admin/`
2. Set `$modelName`, `$modelNamespace`, `$controlName`, `$controlNamespace`, `$label`
3. Register via `$admin->AddFeaturesArray([new MyThingAdminFeature()])`

### Debugging queries

```php
MagratheaPHP::Instance()->Debug(); // enables query logging
Debugger::Instance()->Show();      // dump collected debug info
```

### Caching an API response

In the controller method:
```php
public function List(): array {
    $this->Cache("my_cache_key");              // serve cached if exists
    $data = MyControl::GetAll();
    $this->Cache("my_cache_key", json_encode($data)); // save
    return $data;
}
```

---

## Conventions & Rules

| Rule | Reason |
|------|--------|
| Never instantiate Singletons with `new` | Framework guarantees one instance |
| Always call `Query::Clean()` or use `PrepareAndExecute()` for user input | SQL injection prevention |
| Config secrets use `$=ENV_VAR` in the conf file | Keep secrets out of version control |
| Models return `ToJson()` in API responses (not raw `ToArray()`) | `ToJson()` handles relations recursively |
| Call `MagratheaPHP::LoadVendor()` before anything else | Registers the autoloader |
| Use `->Prod()` in production, `->Dev()` in development | Controls error display and debug verbosity |
| API controllers throw `MagratheaApiException` | Automatically converted to structured JSON error |

---

## Documentation

Full documentation lives in `documentation/`:

| File | Topic |
|------|-------|
| `documentation/index.md` | Master index |
| `documentation/getting-started.md` | Quickstart |
| `documentation/core/` | MagratheaPHP, Config, Singleton, Helper, Functions |
| `documentation/database/` | Database, Query Builder, Model, Control |
| `documentation/api/` | MagratheaApi, ApiControl, Authentication |
| `documentation/admin/` | Admin panel and features |
| `documentation/utilities/` | Cache, Mail, Logger, Debugger, Compressors |
| `documentation/exceptions/` | Exception hierarchy |
| `documentation/advanced/` | Design patterns, Testing |

---

## External Dependencies

| Package | Used for |
|---------|---------|
| `firebase/php-jwt` | JWT token encode/decode |
| `phpmailer/phpmailer` | SMTP email |
| `scssphp/scssphp` | SCSS compilation |
| `tedivm/jshrink` | JS minification |
| `components/jquery` | Admin panel jQuery |
| `twbs/bootstrap` | Admin panel Bootstrap |

---

## Things AI Assistants Should Watch Out For

1. **Don't assume `new Database()` is valid** — it's a singleton.
2. **Query Builder methods return `$this`** — they don't execute SQL.
3. **`MagratheaApiException`** is caught by the router and turned into a JSON error — throw it freely in controllers.
4. **`Config::Get()` reads from the `[default]` section** — use `GetConfig("section/key")` for named sections.
5. **`Save()` on a model auto-detects insert vs. update** — based on whether PK is set.
6. **The admin panel requires `session_start()`** — ensure `StartSession()` is called in bootstrap.
7. **Route parameters use `{param}` syntax** in `MagratheaApi::Add()` — they arrive as array in the controller method.
8. **`MagratheaModelControl` methods are all `static`** — don't call them on instances.
9. **`$dbValues` defines the column schema** — fields not listed here are ignored on insert/update.
10. **`AllowAll()` opens CORS to everyone** — use `Allow([...])` in production for specific origins.

---

## Author & Contact

- **Author:** Paulo Henrique Martins — Platypus Technology
- **Official docs:** https://www.platypusweb.com.br/magratheaphp2
- **License:** MIT
