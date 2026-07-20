# Config — Configuration Management

**File:** `src/Config.php`
**Namespace:** `Magrathea2`
**Extends:** `Singleton`

Manages application configuration from an INI file. Sections are named by **environment**
(not by topic) and each environment section repeats its own full flat set of keys —
there is no section-inheritance or merging syntax.

---

## Configuration File Format

Place your config at:

```
<appRoot>/config/magrathea.conf
```

`[general]` holds cross-environment settings, including `use_environment` (which section
is active by default when `SetEnvironment()` hasn't been called explicitly). Every other
section is a flat, self-contained environment — nothing is inherited or merged between
sections.

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
	jwt_key = "a-very-long-random-string-here"

[production]
	db_host = "db.prod.server"
	db_name = "my_db"
	db_user = "app_user"
	db_pass = "$=DB_PASSWORD"
	db_port = "3306"
	jwt_key = "$=JWT_SECRET"
```

### Environment variable interpolation

Any value starting with `$=` is resolved via `getenv()` at read time, by both `GetConfig()`
and `GetConfigFromDefault()`:

```ini
db_pass = "$=DB_PASSWORD"
```

---

## Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `$path` | `string` | `"configs"` | Config directory path |
| `$configFile` | `string` | `"magrathea.conf"` | Config file name |
| `$configs` | `array\|null` | `null` | Parsed config data (loaded once, then cached) |
| `$environment` | `string\|null` | `null` | Active environment name |

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
Sets the active environment for `Get()` / `GetConfigFromDefault()`. Does not itself read
or validate anything — the environment section is only looked up (and can throw) the next
time a default-scoped read happens.

```php
Config::Instance()->SetEnvironment("production");
```

### `GetEnvironment(): string`
Returns the active environment name. If none was set explicitly via `SetEnvironment()`,
it's resolved from `general/use_environment` in the config file.

### `GetAvailableEnvironments(): array`
Returns every top-level section name except `general`.

### `GetConfig(string $config_name = ""): array|string`
Reads from the **root** of the parsed config — not environment-scoped.
- Empty string (default): returns the entire parsed config.
- `"section"`: returns that whole section as an array.
- `"section/key"`: returns a single key from that section.

```php
$prodSection = Config::Instance()->GetConfig("production");
// ["db_host" => "db.prod.server", "db_name" => "my_db", ...]

$prodHost = Config::Instance()->GetConfig("production/db_host");
// "db.prod.server"
```

### `Get(string $config_name): string|int|null`
Alias for `GetConfigFromDefault($config_name)`. Reads a bare key from the **active
environment** section (see `GetEnvironment()`).

```php
$host = Config::Instance()->Get("db_host");
```

### `GetConfigFromDefault(string $config_name, bool $throwable = false): string|int|null`
Reads a bare key from the active environment section. Throws `MagratheaConfigException`
if the environment section itself is missing from the file. If the key is missing within
that section, returns `null` unless `$throwable` is `true`, in which case it throws
`MagratheaConfigException` (code `704`).

```php
$value = Config::Instance()->GetConfigFromDefault("jwt_key", true);
```

### `GetConfigSection(string $section_name): array`
Returns a named section as an array (any section — not necessarily the active
environment). Throws `MagratheaConfigException` if the section is empty/missing.
There is **no** merging with any other section.

```php
$db = Config::Instance()->GetConfigSection("production");
echo $db["db_host"];
```

### `SetConfig(array $c): Config`
Manually overrides the parsed config array (useful for testing — bypasses the file
entirely).

```php
Config::Instance()->SetConfig([
    "dev" => ["db_host" => "localhost", "db_name" => "test_db"],
]);
```

### `GetFilePath(): string`
Returns the full resolved path to the config file (`$path . "/" . $configFile`). Throws
`MagratheaConfigException` if the path is empty or the file doesn't exist.

---

## Usage Examples

### Read the active environment's config

```php
use Magrathea2\Config;

$host = Config::Instance()->Get("db_host");
$user = Config::Instance()->Get("db_user");
```

### Read a specific environment regardless of which is active

```php
$prodHost = Config::Instance()->GetConfig("production/db_host");
// or the whole section:
$prod = Config::Instance()->GetConfigSection("production");
```

### Switch environment programmatically

```php
Config::Instance()->SetEnvironment("staging");
$host = Config::Instance()->Get("db_host"); // now reads [staging]
```

### Test with mock config

```php
Config::Instance()->SetConfig([
    "dev" => ["db_host" => "localhost", "db_name" => "test_db"],
]);
```

---

## Integration with MagratheaPHP

`MagratheaPHP::Load()` calls `Config::Instance()->SetPath(...)->LoadFile()` internally.
You don't normally need to call `Config` directly during bootstrap.

---

## Notes

- The config file is only parsed once per request (cached in `$configs`).
- `GetConfig()` is **not** environment-scoped; `Get()` / `GetConfigFromDefault()` are.
  These are not interchangeable — pick based on whether you want the active environment
  or a specific named section.
- There is no section-inheritance syntax (`[section:env]` or similar) and no automatic
  merging between sections — each environment section repeats its full flat key set.
- Never hardcode credentials. Use `$=ENV_VAR_NAME` for secrets.
