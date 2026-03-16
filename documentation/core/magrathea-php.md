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
| `$magRoot` | `string` | Root directory of the Magrathea framework itself |
| `$codeFolder` | `array<string>` | Folders registered for PSR-4 autoloading |
| `$versionRequired` | `?string` | Minimum required Magrathea version |

---

## Static Methods

### `LoadVendor(): void`
Loads Magrathea's internal autoloader. Call this **before** anything else.

```php
MagratheaPHP::LoadVendor();
```

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
Registers additional folders (relative to `$appRoot`) for the autoloader.

```php
$app->AddCodeFolder("models", "controls", "services");
```

### `AddRootCodeFolder(...$folder): MagratheaPHP`
Same as `AddCodeFolder` but paths are relative to the filesystem root.

### `AddFeature(...$features): MagratheaPHP`
Registers Admin panel feature classes.

### `Dev(): MagratheaPHP`
Enables **development mode**: verbose PHP errors and warnings.

### `Debug(): MagratheaPHP`
Enables **debug mode**: same as Dev + database query logging via `Debugger`.

### `Prod(): MagratheaPHP`
Enables **production mode**: suppresses error display, uses file logging.

### `Load(): MagratheaPHP`
Loads the configuration file. Must be called after `AppPath()` and the desired mode.

### `GetConfigRoot(): string`
Returns the path to the configuration directory (typically `<appRoot>/config/`).

### `StartDB(): MagratheaPHP` / `Connect(): MagratheaPHP`
Connects to the database using credentials from the loaded config. `StartDB` is an alias for `Connect`.

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
Returns the version value from the app's own config.

---

## Full Bootstrap Chain

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Magrathea2\MagratheaPHP;

MagratheaPHP::LoadVendor();

$app = MagratheaPHP::Instance()
    ->AppPath(__DIR__)
    ->MinVersion("2.1.0")
    ->AddCodeFolder("models", "controls", "api")
    ->Dev()                 // swap for ->Prod() in production
    ->Load()
    ->StartSession()
    ->Connect();
```

---

## Environment Modes Comparison

| Method | PHP Errors | Query Logging | Exception Display |
|--------|-----------|---------------|-------------------|
| `Dev()` | On | Off | Verbose |
| `Debug()` | On | On | Verbose |
| `Prod()` | Off | Off | File log only |

---

## Notes

- `MagratheaPHP` itself extends `Singleton`, so `MagratheaPHP::Instance()` always returns the same object.
- Config is expected at `<appRoot>/config/magrathea.conf` by default.
- The autoloader scans every folder registered via `AddCodeFolder` to resolve class names.
