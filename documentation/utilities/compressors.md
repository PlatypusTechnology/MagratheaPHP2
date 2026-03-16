# Compressors — CSS & JavaScript Compression

**Files:** `src/Compressors/MagratheaCompressor.php`, `src/Compressors/CssCompressor.php`, `src/Compressors/JavascriptCompressor.php`
**Namespace:** `Magrathea2`

File bundling and minification utilities for CSS and JavaScript assets. Used primarily by the Admin panel but available to application code as well.

---

## Base: MagratheaCompressor

Abstract base class providing the common file management interface.

### Methods

#### `AddFile(string $file): static`
Register a file to be bundled.

```php
$compressor->AddFile("/path/to/style.css");
```

#### `GetFiles(): array`
Returns all registered file paths.

#### `GetOutput(): string`
Combine and compress all registered files, returning the result as a string.

---

## CssCompressor

Bundles and minifies CSS files using `scssphp` (supports both SCSS and plain CSS).

### Usage

```php
use Magrathea2\Compressors\CssCompressor;

$css = new CssCompressor();
$css->AddFile("/assets/bootstrap.css")
    ->AddFile("/assets/app.scss");

echo "<style>" . $css->GetOutput() . "</style>";
```

### SCSS Support

Any `.scss` files are automatically compiled to CSS via `scssphp/scssphp` before minification.

---

## JavascriptCompressor

Bundles and minifies JavaScript files using `tedivm/jshrink`.

### Usage

```php
use Magrathea2\Compressors\JavascriptCompressor;

$js = new JavascriptCompressor();
$js->AddFile("/assets/jquery.min.js")
   ->AddFile("/assets/app.js");

echo "<script>" . $js->GetOutput() . "</script>";
```

---

## Admin Panel Usage

The Admin panel uses both compressors internally via `AdminManager`:

```php
use Magrathea2\Admin\AdminManager;

$manager = AdminManager::Instance();

// Add custom JS
$manager->AddJs("/my-feature/custom.js");

// Add custom CSS
$manager->AddCss("/my-feature/custom.css");

// Output in templates
echo $manager->GetJs();  // all JS bundled & minified
echo $manager->GetCss(); // all CSS bundled & minified
```

---

## Example: Asset Pipeline for a Custom Page

```php
use Magrathea2\Compressors\CssCompressor;
use Magrathea2\Compressors\JavascriptCompressor;

$appRoot = MagratheaPHP::Instance()->GetAppRoot();

$css = new CssCompressor();
$css->AddFile($appRoot . "assets/vendor/normalize.css")
    ->AddFile($appRoot . "assets/app/main.scss");

$js = new JavascriptCompressor();
$js->AddFile($appRoot . "assets/vendor/htmx.min.js")
   ->AddFile($appRoot . "assets/app/main.js");
?>
<!DOCTYPE html>
<html>
<head>
    <style><?= $css->GetOutput() ?></style>
</head>
<body>
    <!-- page content -->
    <script><?= $js->GetOutput() ?></script>
</body>
</html>
```

---

## Notes

- File paths should be absolute (use `$appRoot . "assets/..."` pattern).
- Compressors process files in the order they were added — respect dependency order.
- In production, consider caching the compressor output to avoid reprocessing on every request.
- SCSS compilation supports `@import`, variables, mixins, and other SCSS features.

### Caching Compressed Output

```php
$cacheFile = "/tmp/bundle.min.css";

if (!file_exists($cacheFile)) {
    $css = new CssCompressor();
    $css->AddFile("/assets/app.scss");
    file_put_contents($cacheFile, $css->GetOutput());
}

echo file_get_contents($cacheFile);
```
