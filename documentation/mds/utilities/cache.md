# MagratheaCache — Response Caching

**File:** `src/MagratheaCache.php`
**Namespace:** `Magrathea2`
**Extends:** `Singleton`

File-based response cache. Stores API responses and other data as flat files (JSON by default) to avoid redundant database queries or expensive computations.

---

## Configuration

Set the cache directory in your config file:

```ini
[cache]
path = /var/www/myapp/cache/
```

The cache directory must be writable by the web server.

---

## Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `$cacheName` | `string` | | Current cache key |
| `$saveCache` | `bool` | | Whether a cache hit was found |
| `$cachePath` | `string` | | Resolved cache directory |
| `$extension` | `string` | `"json"` | Cache file extension |

---

## Methods

### `LoadCachePath(): MagratheaCache`
Load the cache path from config. Called automatically on first use.

### `GetCachePath(): string|null`
Returns the current cache directory path.

### `Type(string $t): MagratheaCache`
Change the file extension (e.g., `"txt"`, `"html"`).

```php
MagratheaCache::Instance()->Type("html");
```

### `Cache(string $name, mixed $data = null): void`
Primary cache method. Dual-use:

1. **Check** (call with just `$name`): look for a cached file and output it if found.
2. **Save** (call with `$name` + `$data`): write the data to cache.

```php
// Pattern: check first, then save after computing
$cache = MagratheaCache::Instance();
$cache->Cache("products_list"); // outputs cached response if it exists and exits

$data = ProductControl::GetAll();

$cache->Cache("products_list", json_encode($data)); // saves for next request
```

### `LookForFile(): bool`
Check if a cache file exists for the current `$cacheName`.

### `GetCacheFile(): string`
Returns the full path to the current cache file.

### `SaveFile(string $data): mixed`
Write string data to the cache file.

### `Clear(string $name, mixed $data = null): bool`
Delete a specific cache entry. Returns `true` if the file was deleted.

```php
MagratheaCache::Instance()->Clear("products_list");
```

### `DeleteFile(string $file, bool $addExtension = true): bool`
Delete a cache file by filename (optionally appends the extension).

### `RemoveAllCache(): array`
Delete **all** cache files in the cache directory. Returns an array of deleted filenames.

```php
MagratheaCache::Instance()->RemoveAllCache();
```

### `RemovePattern(string $pattern): array`
Delete all cache files matching a pattern (uses `glob()`).

```php
MagratheaCache::Instance()->RemovePattern("products_*");
// Deletes: products_list.json, products_featured.json, etc.
```

### `HandleApiCache(array $data): bool`
Convenience method for API responses: check for cache, output if found, save if not. Returns `true` if cache was hit.

```php
if (MagratheaCache::Instance()->HandleApiCache($data)) {
    return; // cache served
}
```

### `ShowJson(array|string $data): void`
Output data as JSON and save it to the cache.

---

## Usage in API Controllers

The idiomatic way to use caching in a controller is via `MagratheaApiControl`'s built-in methods:

```php
class ProductApiControl extends MagratheaApiControl {

    public function List(): array {
        // Check cache first
        $this->Cache("products_all");

        // Compute result
        $products = ProductControl::GetAll();
        $result   = array_map(fn($p) => $p->ToJson(), $products);

        // Save to cache and return
        $this->Cache("products_all", json_encode($result));
        return $result;
    }

    public function Create(array $data = []): object {
        $product = new Product();
        $product->Assign($this->GetPost());
        $product->Save();

        // Invalidate cache
        $this->CacheClear("products_all");

        return $product->ToJson();
    }
}
```

---

## Direct Cache Usage Example

```php
use Magrathea2\MagratheaCache;

$cache = MagratheaCache::Instance();

// Try to serve from cache
$cache->Cache("expensive_report");

// Not cached — compute it
$data = compute_heavy_report();

// Cache and output
$cache->Cache("expensive_report", json_encode($data));
echo json_encode($data);
```

---

## Cache Key Naming Tips

Use descriptive, specific names. Avoid collisions by including relevant parameters:

```php
// Good
$this->Cache("products_category_{$categoryId}_page_{$page}");

// Avoid
$this->Cache("data");
```

---

## Notes

- Cache files are stored as `{name}.{extension}` in the configured cache path.
- There is no TTL (time-to-live) system — cache files persist until explicitly cleared.
- `RemovePattern` is the recommended way to invalidate related cache groups on data changes.
- The cache system is file-based, so it works without Redis, Memcached, or any external service.
