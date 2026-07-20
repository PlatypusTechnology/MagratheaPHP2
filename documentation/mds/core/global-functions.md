# Global Functions & Autoloader

**Files:** `src/_Functions.php`, `src/_FunctionsDebug.php`
**Namespace:** Global (no namespace)

These files define procedural helper functions available globally (without `use` or namespace prefix) and register the framework's custom PSR-4 autoloader.

---

## Autoloader

MagratheaPHP2 uses a custom `spl_autoload_register` callback. After you call `MagratheaPHP::LoadVendor()`, the autoloader scans all registered code folders to resolve class names.

### How it works

1. `MagratheaPHP::LoadVendor()` registers the autoloader.
2. When PHP encounters an unknown class `App\Models\User`, the autoloader splits on `\\`, removes the root namespace if present, and looks for `User.php` inside each registered folder.
3. Folders are registered with `AddCodeFolder()` (relative to app root) or `AddRootCodeFolder()` (absolute paths).

```php
MagratheaPHP::Instance()
    ->AppPath(__DIR__)
    ->AddCodeFolder("models", "controls", "services", "api");
```

---

## Global Functions

### `now(): string`
Returns the current date/time formatted as `"Y-m-d H:i:s"`.

```php
echo now(); // "2024-03-15 14:30:00"
```

Useful for setting `created_at` / `updated_at` timestamps without importing a class.

---

### `arrToStr(array $array): string`
Converts an array to a human-readable formatted string (uses `print_r` internally, wrapped for string return).

```php
$arr = ["a" => 1, "b" => 2];
echo arrToStr($arr);
// Array
// (
//     [a] => 1
//     [b] => 2
// )
```

---

### `p_r(mixed $debugme): void`
Pretty-prints a value wrapped in `<pre>` tags. Equivalent to `echo "<pre>" . print_r($debugme, true) . "</pre>"`.

```php
$user = UserControl::GetWhere(["id" => 1])[0];
p_r($user);
```

---

### `isMagratheaModel(mixed $object): bool`
Returns `true` if the given value is an instance of `MagratheaModel`.

```php
if (isMagratheaModel($obj)) {
    echo $obj->ToJson();
}
```

---

### `getClassNameOfClass(string $fullClassName): string`
Strips the namespace from a fully-qualified class name and returns just the class name.

```php
echo getClassNameOfClass("App\\Models\\User"); // "User"
echo getClassNameOfClass("Magrathea2\\DB\\Database"); // "Database"
```

---

### `magrathea_getTypesArr(): array`
Returns the array of data types supported by the ORM field system:

```php
$types = magrathea_getTypesArr();
// ["int", "boolean", "string", "text", "float", "datetime"]
```

Used internally by `MagratheaModel` when validating `$dbValues` field type declarations.

---

## Debug Functions (`_FunctionsDebug.php`)

These are additional debug helpers loaded alongside the main functions file.

### `magrathea_debug(mixed $data): void`
Adds an item to the current `Debugger` instance for display.

### `magrathea_log(string $message): void`
Logs a message via the `Logger` singleton.

---

## Notes

- All global functions are unconditionally defined when `_Functions.php` is included. There are no conditional guards — avoid name collisions in your own code.
- `p_r()` outputs HTML, so it should only be used in web/CLI debug contexts, not in JSON API responses.
