# MagratheaHelper — Utility Functions

**File:** `src/MagratheaHelper.php`
**Namespace:** `Magrathea2`
**Type:** Static utility class (all methods are `static`)

A collection of general-purpose helper functions used throughout the framework and available to application code.

---

## Methods

### `RandomString(int $length = 10): string`
Generates a cryptographically random alphanumeric string of the given length.

```php
use Magrathea2\MagratheaHelper;

$token = MagratheaHelper::RandomString(32);
// e.g. "aB3xK9mQp2..."
```

**Use cases:** API keys, temporary passwords, CSRF tokens, cache keys.

---

### `HexToRgb(string $hex): array`
Converts a hex color code to an RGB decimal array.

```php
$rgb = MagratheaHelper::HexToRgb("#FF8C00");
// Returns: [255, 140, 0]

$rgb = MagratheaHelper::HexToRgb("FF8C00"); // hash is optional
```

**Returns:** `[int $r, int $g, int $b]`

---

### `EnsureTrailingSlash(string $str): string|null`
Ensures the given path string ends with a `/`. Returns `null` if input is empty.

```php
$path = MagratheaHelper::EnsureTrailingSlash("/var/www/app");
// Returns: "/var/www/app/"

$path = MagratheaHelper::EnsureTrailingSlash("/var/www/app/");
// Returns: "/var/www/app/" (unchanged)
```

---

### `FormatSize(int $bytes, int $decimals = 2): string`
Converts a raw byte count into a human-readable file size string.

```php
echo MagratheaHelper::FormatSize(1024);
// "1.00 KB"

echo MagratheaHelper::FormatSize(1_048_576);
// "1.00 MB"

echo MagratheaHelper::FormatSize(1_073_741_824, 1);
// "1.0 GB"
```

**Supported units:** B, KB, MB, GB, TB, PB, EB, ZB, YB

---

## Practical Examples

### Generating an API key for a new user

```php
use Magrathea2\MagratheaHelper;

$apiKey = MagratheaHelper::RandomString(40);
$user->Set("api_key", $apiKey);
$user->Save();
```

### Using a hex color from config in CSS

```php
$hexColor = Config::Instance()->Get("primary_color"); // "#3A7BD5"
[$r, $g, $b] = MagratheaHelper::HexToRgb($hexColor);

echo "rgba($r, $g, $b, 0.8)"; // "rgba(58, 123, 213, 0.8)"
```

### Displaying file sizes in an admin UI

```php
$fileSize = filesize("/uploads/report.pdf");
echo MagratheaHelper::FormatSize($fileSize); // "2.34 MB"
```
