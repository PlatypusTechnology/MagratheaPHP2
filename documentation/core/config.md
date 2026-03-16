# Config — Configuration Management

**File:** `src/Config.php`
**Namespace:** `Magrathea2`
**Extends:** `Singleton`

Manages application configuration from INI files. Supports multiple environments, sections, and environment-variable interpolation.

---

## Configuration File Format

Config files use PHP's standard INI format. Place your config at:

```
<appRoot>/config/magrathea.conf
```

### Basic example

```ini
[database]
host     = localhost
database = my_db
username = root
password = secret

[app]
name    = My Application
debug   = false

[mail]
from    = noreply@myapp.com
```

### Multi-environment sections

Append `:environment_name` to a section to make it environment-specific. Environment-specific values override the base section values.

```ini
[database]
host     = localhost
database = dev_db
username = root
password = dev_pass

[database:production]
host     = db.production.com
database = prod_db
username = prod_user
password = $=DB_PASSWORD
```

### Environment variable interpolation

Use `$=VAR_NAME` as a value to read from the system environment:

```ini
[database:production]
password = $=DB_PASSWORD
```

This reads `$_ENV["DB_PASSWORD"]` or `getenv("DB_PASSWORD")` at runtime.

---

## Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `$path` | `string` | | Config directory path |
| `$configFile` | `string` | `"magrathea.conf"` | Config file name |
| `$configs` | `array\|null` | `null` | Loaded config data |
| `$environment` | `null` | `null` | Active environment name |

---

## Methods

### `SetPath(string $p): Config`
Override the default config directory.

```php
Config::Instance()->SetPath("/etc/myapp/config");
```

### `GetPath(): string`
Returns the current config path.

### `SetConfigFile(string $f): Config`
Change the config filename (default: `magrathea.conf`).

```php
Config::Instance()->SetConfigFile("app.conf");
```

### `SetEnvironment(string $e): Config`
Activate a named environment. Section values for that environment will override base values.

```php
Config::Instance()->SetEnvironment("production");
```

### `GetEnvironment(): string`
Returns the currently active environment name.

### `GetAvailableEnvironments(): array`
Returns all environment names found in the config file.

### `GetConfig(string $config_name = ""): array|string`
Returns an entire section as array, or a specific key using `"section/key"` notation.

```php
$dbSection = Config::Instance()->GetConfig("database");
// Returns: ["host" => "localhost", "database" => "my_db", ...]

$host = Config::Instance()->GetConfig("database/host");
// Returns: "localhost"
```

### `Get(string $config_name): string|int|null`
Alias for `GetConfigFromDefault`. Reads from the `[default]` section or bare keys.

```php
$appName = Config::Instance()->Get("name");
```

### `GetConfigFromDefault(string $config_name, bool $throwable = false): string|int|null`
Read a key from the default section. If `$throwable` is true, throws `MagratheaConfigException` when the key is not found.

```php
$value = Config::Instance()->GetConfigFromDefault("app_key", true);
```

### `GetConfigSection(string $section_name): array`
Returns all key/value pairs for a given section, merging base and environment-specific values.

```php
$db = Config::Instance()->GetConfigSection("database");
// In production environment, returns production values merged over base
```

### `SetConfig(array $c): Config`
Manually set the config array (useful for testing).

```php
Config::Instance()->SetConfig([
    "database" => ["host" => "localhost", "database" => "test_db"]
]);
```

### `GetFilePath(): string`
Returns the full resolved path to the config file.

---

## Usage Examples

### Access database credentials

```php
use Magrathea2\Config;

$db = Config::Instance()->GetConfigSection("database");
echo $db["host"];    // "localhost"
echo $db["database"]; // "my_db"
```

### Read a single value

```php
$host = Config::Instance()->GetConfig("database/host");
```

### Switch environment programmatically

```php
Config::Instance()
    ->SetEnvironment("staging")
    ->LoadFile();
```

### Test with mock config

```php
Config::Instance()->SetConfig([
    "database" => [
        "host"     => "localhost",
        "database" => "test_db",
        "username" => "root",
        "password" => "",
    ]
]);
```

---

## Integration with MagratheaPHP

`MagratheaPHP::Load()` calls `Config::Instance()->SetPath(...)->LoadFile()` internally. You don't normally need to call `Config` directly during bootstrap.

---

## Notes

- Config files are only loaded once (cached in `$configs`).
- Sections without environments are considered the base/default.
- Environment-specific sections merge on top of the base, so you only need to override differing values.
