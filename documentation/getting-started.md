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

Create a `magrathea.conf` file (INI format) in your config directory:

```ini
[database]
host     = localhost
database = my_app_db
username = root
password = secret
port     = 3306

[app]
name    = My App
version = 1.0.0

[logs]
path = /var/log/my_app/
```

### Multi-environment config

Append environment-specific sections using the format `[section:environment]`:

```ini
[database]
host     = localhost
database = my_app_db
username = root
password = secret

[database:production]
host     = db.myserver.com
database = my_app_prod
username = app_user
password = $=DB_PASSWORD
```

The `$=VAR_NAME` syntax reads from environment variables.

---

## Bootstrapping

Every entry point (e.g. `index.php` or your API entry file) starts with:

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Magrathea2\MagratheaPHP;

MagratheaPHP::LoadVendor(); // load Magrathea's own autoloader

$app = MagratheaPHP::Instance()
    ->AppPath(__DIR__)          // set app root
    ->Dev()                     // enable dev mode (can be Prod() for production)
    ->Load()                    // load configuration
    ->Connect();                // connect to the database
```

---

## Minimal API Example

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Magrathea2\MagratheaPHP;
use Magrathea2\MagratheaApi;

MagratheaPHP::LoadVendor();

$app = MagratheaPHP::Instance()
    ->AppPath(__DIR__)
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
    protected static $modelNamespace = "App\\Models\\";
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

## Next Steps

- [Configuration reference →](core/config.md)
- [Database & Query Builder →](database/database.md)
- [ORM Model →](database/orm-model.md)
- [API Framework →](api/magrathea-api.md)
