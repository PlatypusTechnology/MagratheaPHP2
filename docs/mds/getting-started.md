# Getting Started with MagratheaPHP2

## Installation

```bash
composer require magrathea/magrathea-php2
```

Or clone and install dependencies:

```bash
git clone https://github.com/your-org/MagratheaPHP2.git
cd MagratheaPHP2
composer install
```

---

## Configuration File

Create `configs/magrathea.conf` (INI format) one level **above** the folder you pass to
`AppPath()`: with `AppPath(__DIR__ . "/app")`, that's `configs/` next to `app/`.

Sections are **environments**, not topics. `[general]` says which one is active; each environment
section has the full, flat set of keys (nothing is inherited between sections, and there's no
`[section:environment]` syntax):

```ini
[general]
	use_environment = "dev"

[dev]
	db_host = "localhost"
	db_name = "my_app_db"
	db_user = "root"
	db_pass = "secret"
	db_port = "3306"
	timezone = "America/Sao_Paulo"

[production]
	db_host = "db.myserver.com"
	db_name = "my_app_prod"
	db_user = "app_user"
	db_pass = "$=DB_PASSWORD"
	timezone = "America/Sao_Paulo"
```

The `$=VAR_NAME` syntax reads from environment variables. `use_environment` must name a section
that exists. See the [Configuration reference](core/config.md) for every key.

---

## Bootstrapping

Every entry point (e.g. `index.php` or your API entry file) starts with:

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Magrathea2\MagratheaPHP;

$app = MagratheaPHP::Instance()
    ->AppPath(__DIR__ . "/app") // app root; the config is read from ./configs/
    ->Dev()                     // debug mode only (Prod() on servers); the environment comes from the config
    ->Load()                    // load configuration
    ->Connect();                // DB credentials (the connection opens on the first query)
```

---

## Minimal API Example

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Magrathea2\MagratheaPHP;
use Magrathea2\MagratheaApi;

$app = MagratheaPHP::Instance()
    ->AppPath(__DIR__ . "/app")
    ->Prod()
    ->Load()
    ->Connect();

$api = new MagratheaApi();
$api->AllowAll(); // enable CORS for all origins

// Simple GET endpoint
$api->Add("GET", "/hello", null, function() {
    return ["message" => "Hello, World!"];
});

// Run the API
$api->Run();
```

---

## Minimal Model + API Example

```php
<?php
// models/User.php
namespace App\Models;

use Magrathea2\MagratheaModel;

class User extends MagratheaModel {
    protected $dbTable = "users";
    protected $dbPk = "id";
    protected $dbValues = [
        "id"         => "int",
        "name"       => "string",
        "email"      => "string",
        "created_at" => "datetime",
    ];
}
```

```php
<?php
// controls/UserControl.php
namespace App\Controls;

use Magrathea2\MagratheaModelControl;
use App\Models\User;

class UserControl extends MagratheaModelControl {
    protected static $modelName = "User";
    protected static $modelNamespace = "App\\Models";
    protected static $dbTable = "users";
}
```

```php
<?php
// api/UserApiControl.php
namespace App\Api;

use Magrathea2\MagratheaApiControl;
use App\Controls\UserControl;

class UserApiControl extends MagratheaApiControl {
    public function List(): array {
        return UserControl::GetAll();
    }

    public function Read($params = false): object|array {
        $id = $params["id"] ?? null;
        return UserControl::GetWhere(["id" => $id], "AND")[0] ?? [];
    }
}
```

```php
<?php
// index.php
use Magrathea2\MagratheaApi;
use App\Api\UserApiControl;

$api = new MagratheaApi();
$api->AllowAll();

// Automatically registers GET /users, GET /users/{id},
// POST /users, PUT /users/{id}, DELETE /users/{id}
$api->Crud("/users", new UserApiControl());

$api->Run();
```

---

## Running in Different Environments

```php
// Development — verbose errors, query logging
$app->Dev();

// Debug — same as dev but with extra stack traces
$app->Debug();

// Production — suppresses errors, logs to file
$app->Prod();
```

---

## Setup Wizard (Bootstrap)

`Magrathea2\Bootstrap\Start::Instance()->Load()` renders a setup wizard: folder structure, config
editing, DB connection test, a raw SQL runner, admin install and admin password reset. That's a
remote control for your server, so it's **locked by default** (since 2.4.0).

It only opens when the **active environment's section** has the flag:

```ini
[dev]
	db_host = "localhost"
	; ...
	bootstrap_unlocked = true
```

- Only the active environment counts. The flag in `[general]` or in another environment's section
  does nothing, so a `[dev]` flag never unlocks `[production]` in the same file.
- `true`, `1`, `on` and `yes` (quoted or not) unlock it. Anything else, or no flag at all, means
  locked.
- Locked, `Load()` answers **403** with a page that says "Bootstrap is locked" and the active
  environment's name, nothing else.
- The wizard can't unlock itself: the check runs before any step or action.
- Only known actions can be called (`?action=run-query`, `save-config`, …). Any other `action`
  value gets a **404**.

The sample config (`src/Bootstrap/docs/magrathea.conf.sample`) has `bootstrap_unlocked = true` in
`[dev]` only. Keep it out of production. If you really need the wizard there (e.g. to reset a
locked-out admin password with `user-reset`), add the flag, do what you need, and remove it.

**Known gap:** `Start::PreLoad()` copies the sample when there's no config file at all, and the
copy comes up as `dev` + unlocked. It has no real DB credentials at that point, so the SQL runner
has nothing to reach. Still, don't leave a wizard entry file deployed.

---

## Next Steps

- [Configuration reference →](core/config.md)
- [Database & Query Builder →](database/database.md)
- [ORM Model →](database/orm-model.md)
- [API Framework →](api/magrathea-api.md)
