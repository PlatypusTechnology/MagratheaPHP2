# MagratheaPHP — Main Entry Point

**File:** `src/MagratheaPHP.php`
**Namespace:** `Magrathea2`
**Extends:** `Singleton`

The central bootstrap class for any MagratheaPHP2 application. Manages the app root path, autoloading, environment mode, configuration loading, and database connection. Uses the Singleton pattern — always access via `MagratheaPHP::Instance()`.

---

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `$appRoot` | `string` | Root directory of the application |
| `$magRoot` | `string` | Project root: `AppPath()`'s parent folder (holds `configs/`); not the framework's folder (that's `GetMagratheaRoot()`) |
| `$codeFolder` | `array<string>` | Folders the autoloader searches for `<ClassName>.php` (see Global Functions & Autoloader) |
| `$versionRequired` | `?string` | Minimum required Magrathea version |

---

## Static Methods

### `LoadVendor(): void`
**Deprecated since 2.4.0, removed in 3.0.** Require `vendor/autoload.php` yourself instead.

Requires Composer's `vendor/autoload.php` (`require_once`): the framework's own in a standalone
checkout, the project's when the framework is installed as a dependency; throws `MagratheaException`
if neither exists. Before 2.4.0 it only looked for the second one, so it failed with a fatal error in a
standalone checkout. Since `MagratheaPHP` itself is only loadable after `vendor/autoload.php` has been
required, calling it never does anything useful; it's not what registers Magrathea's autoloader
(Composer's `files` autoload of `src/_Functions.php` does that).

### `Version(): string`
Returns the current framework version string.

```php
echo MagratheaPHP::Version(); // "2.1.24"
```

### `GetDocumentationLink(): string`
Returns a link to the official online documentation.

### `Test(): void`
Runs internal self-test of the framework.

---

## Instance Methods (Fluent Interface)

All instance methods return `$this` for chaining unless otherwise stated.

### `AppPath(string $path): MagratheaPHP`
Sets the application root directory. **Must be called first.**

```php
MagratheaPHP::Instance()->AppPath(__DIR__);
```

### `GetAppRoot(): string`
Returns the currently set application root path.

### `GetMagratheaRoot(): string`
Returns the framework's own root path.

### `MinVersion(string $version, bool $throwEx = false): MagratheaPHP`
Asserts the running framework version is at least `$version`. If `$throwEx` is true, throws an exception on failure.

```php
MagratheaPHP::Instance()->MinVersion("2.1.0");
```

### `AddCodeFolder(...$folder): MagratheaPHP`
Registers folders for the autoloader, **as given**: absolute paths, or paths relative to the current working directory (under a web server, the entry script's folder), not to `$appRoot`. For folders inside the app, use `AddRootCodeFolder()`.

```php
$app->AddCodeFolder(__DIR__."/models", "/var/www/shared/lib");
```

### `AddRootCodeFolder(...$folder): MagratheaPHP`
Same as `AddCodeFolder()`, with each path prefixed by `$appRoot/`. Call it after `AppPath()`.

```php
$app->AddRootCodeFolder("models", "controls", "services"); // <appRoot>/models, ...
```

### `AddFeature(...$features): MagratheaPHP`
For each feature name, registers `<appRoot>/features/<Name>` and `<appRoot>/features/<Name>/Base` for the autoloader. Call it after `AppPath()`.

### `AppNamespace(string $namespace): MagratheaPHP`
Sets the project's root namespace (e.g. `"App"`). In DEV, a class in that namespace that the autoloader can't find adds a "Could not find class [...]. Are the code folders correct?" debug item. Since 2.4.0 it no longer throws: `class_exists()` returns `false` and `new` fails with PHP's `Error: Class "…" not found`.

### `Dev(): MagratheaPHP`
Enables **development mode**: `error_reporting(E_ALL)`, `display_errors` and `log_errors` on, and
`Debugger::DEV`. Like `Prod()` and `Debug()`, it doesn't choose the config environment: that's
`general/use_environment` or `Config::Instance()->SetEnvironment()`.

### `Debug(): MagratheaPHP`
Enables **debug mode**: `Debugger::DEBUG` (debug items are stored and printed at the end of the request; API responses carry them in `_debug`) + database query logging. It doesn't change PHP's error display, unlike `Dev()`.

### `Prod(): MagratheaPHP`
Enables **production mode**: `Debugger::LOG` (debug items and exceptions go to the log file). It
doesn't touch PHP's `display_errors`: turn that off in `php.ini` (or with `ini_set()`) on servers.

### `Load(): MagratheaPHP`
Loads the configuration file. Must be called after `AppPath()` and the desired mode. If the active
environment has a `timezone` key, it's set as PHP's timezone (`date_default_timezone_set()`); an
invalid name is ignored and PHP stays on php.ini's default. The key is `timezone`, not `time_zone`
(see the Config page).

### `GetConfigRoot(): string`
Returns the path to the configuration directory: `configs/` in `AppPath()`'s parent folder (`<magRoot>/configs`).

### `StartDB(): MagratheaPHP` / `Connect(): MagratheaPHP`
Sets the database connection from the active environment's config keys `db_host`, `db_name`, `db_user`,
`db_pass` and `db_port` (optional; without it, mysqli's default port is used. Before 2.4.0, `db_port`
was ignored). `StartDB` is an alias for `Connect`. Nothing is opened yet: the connection opens on the
first query and stays open for the rest of the request (see the Database page).

With a `timezone` in the config, it also turns on the MySQL timezone sync (`Database::SetTimezoneSync()`):
each new connection sets MySQL's session `time_zone` to PHP's. `db_sync_timezone = false` turns it off.

```php
$app->Connect();
// or
$app->StartDB();
```

### `GetDB(): Database`
Returns the active `Database` singleton instance.

```php
$db = MagratheaPHP::Instance()->GetDB();
```

### `StartSession(): MagratheaPHP`
Calls `session_start()` if no session is active.

### `AppVersion(): string`
Reads the app version from `<magRoot>/version` and removes trailing line breaks (`\n` / `\r\n`).

---

## Full Bootstrap Chain

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Magrathea2\MagratheaPHP;

$app = MagratheaPHP::Instance()
    ->AppPath(__DIR__)
    ->MinVersion("2.1.0")
    ->AddRootCodeFolder("models", "controls", "api")
    ->Dev()                 // swap for ->Prod() in production
    ->Load()
    ->StartSession()
    ->Connect();
```

---

## Debug Modes Comparison

These set the Debugger mode only; the config environment (`[dev]`, `[production]`, …) is chosen
by `general/use_environment` or `Config::SetEnvironment()`, never by these methods.

| Method | PHP error display | Query Logging | Debug items / exceptions |
|--------|-------------------|---------------|--------------------------|
| `Dev()` | Turned on (`display_errors`, `E_ALL`) | Off | Printed where they're added (`DEV`) |
| `Debug()` | Unchanged | On | Stored, printed at the end of the request (`DEBUG`) |
| `Prod()` | Unchanged (set it in `php.ini`) | Off | Written to the log (`LOG`) |

---

## Notes

- `MagratheaPHP` itself extends `Singleton`, so `MagratheaPHP::Instance()` always returns the same object.
- Config is expected at `<magRoot>/configs/magrathea.conf` (one level above `AppPath()`) by default.
- The autoloader scans every folder registered via `AddCodeFolder`/`AddRootCodeFolder`/`AddFeature` for `<ClassName>.php`. If several folders have one, it loads the file whose `namespace` matches the class (since 2.4.0; it used to load the first one), and warns in DEV.
