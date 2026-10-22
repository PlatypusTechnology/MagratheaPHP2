# Testing with MagratheaPHP2

**Directory:** `src/Tests/`
**Namespace:** `Magrathea2\Tests`

The framework ships with built-in test utilities to help you write unit tests for your models, APIs, and controllers.

---

## Test Infrastructure Files

| File | Description |
|------|-------------|
| `src/Tests/TestsManager.php` | Runs PHPUnit test folders/files from the admin's "Tests" page |
| `src/Tests/TestsHelper.php` | Two tiny helpers: `Debug()` (sets the Debugger to `NONE`) and `Print()` (a no-op) |
| `src/Tests/phpUnitBootstrap.php` | PHPUnit bootstrap: loads Composer's autoloader and sets the Debugger to `NONE` |

---

## PHPUnit Bootstrap

`phpUnitBootstrap.php` sets `error_reporting(E_ALL)`, requires `vendor/autoload.php` (it works both in a
framework checkout and installed under `vendor/`) and sets the Debugger to `NONE`, because DEV prints every
`MagratheaException` when it's created, caught ones included. It doesn't load any config or connect to a
database. Use it, or your own bootstrap, in `phpunit.xml`:

```xml
<!-- phpunit.xml -->
<phpunit bootstrap="vendor/platypustechnology/magratheaphp2/src/Tests/phpUnitBootstrap.php">
    <testsuites>
        <testsuite name="Application">
            <directory>tests/</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

---

## Mocking the Database

For unit tests that shouldn't hit a real database, call `Mock()` on the instance:

```php
use Magrathea2\DB\Database;

class ProductControlTest extends \PHPUnit\Framework\TestCase {

    protected function setUp(): void {
        Database::Instance()->Mock(); // Database::Instance() is now a DatabaseSimulate
    }
}
```

`Mock()` replaces the `Database` singleton with `DatabaseSimulate` for the rest of the PHP process (there's
no un-mock; code that kept the old instance in a variable keeps using it). The simulator never connects:
- every query is recorded with `Debugger::AddQuery()`;
- `QueryAll()`/`QueryRow()` return `[]`, `QueryOne()` and `Query()` return `null`;
- `PrepareAndExecute()` returns `42`, so a model's `Insert()` gets `42` as its id;
- `Escape()` escapes without a connection, so the query builder works;
- `Transaction()` follows the real rules and records `START TRANSACTION`/`COMMIT`/`ROLLBACK`.

It can't return rows: anything that needs data back needs a real test database.

For integration tests, point the connection at a test database. It opens on the first query and stays open
for the rest of the process (one connection per `Database` instance):

```php
protected function setUp(): void {
    Database::Instance()->SetConnection("localhost", "test_db", "root", "");
}
```

---

## Mocking Configuration

`SetConfig()` replaces the whole parsed config. Give it the same shape as `magrathea.conf`: a
`[general]` section plus one flat section per environment.

```php
use Magrathea2\Config;

Config::Instance()->SetConfig([
    "general" => ["use_environment" => "test"],
    "test"    => ["db_host" => "localhost", "db_name" => "test_db", "jwt_key" => "test-secret"],
])->SetEnvironment("test");

Config::Instance()->Get("db_name"); // "test_db"
```

---

## Mocking ConfigApp (Database-stored Config)

```php
use Magrathea2\ConfigApp;

ConfigApp::Instance()->Mock([
    "feature_flag_x" => "true",
    "max_retries"    => "3",
]);

// Use it
$flag = ConfigApp::Instance()->GetBool("feature_flag_x"); // true

// Restore
ConfigApp::Instance()->UnMock();
```

---

## Testing Models

```php
use App\Models\Product;
use PHPUnit\Framework\TestCase;

class ProductModelTest extends TestCase {

    public function testFieldAssignment(): void {
        $product = new Product();
        $product->name  = "Widget";
        $product->price = 9.99;

        $this->assertEquals("Widget", $product->name);
        $this->assertEquals(9.99, $product->price);
    }

    public function testToArray(): void {
        $product = new Product();
        $product->name  = "Widget";
        $product->price = 9.99;

        $arr = $product->ToArray();
        $this->assertArrayHasKey("name", $arr);
        $this->assertEquals("Widget", $arr["name"]);
    }

    public function testIsEmpty(): void {
        $product = new Product();
        $this->assertTrue($product->IsEmpty());

        $product->SetPK(1);
        $this->assertFalse($product->IsEmpty());
    }
}
```

---

## Testing API Controllers

```php
use App\Api\ProductApiControl;
use PHPUnit\Framework\TestCase;

class ProductApiControlTest extends TestCase {

    private ProductApiControl $ctrl;

    protected function setUp(): void {
        // Mock the database before tests
        \Magrathea2\DB\Database::Instance()->Mock();
        $this->ctrl = new ProductApiControl();
    }

    public function testListReturnsArray(): void {
        $result = $this->ctrl->List();
        $this->assertIsArray($result);
    }
}
```

### Calling an endpoint in raw mode

To test routing, authorization and error handling too, register the routes on a `MagratheaApi` in raw mode
and call `ExecuteUrl()`. It returns the response array instead of printing JSON:

```php
public function testListRoute(): void {
    \Magrathea2\DB\Database::Instance()->Mock();
    $api = (new MagratheaApi())->SetRaw();
    $api->Add("GET", "products", new ProductApiControl(), "List");

    $rs = $api->ExecuteUrl("products", "GET");
    $this->assertTrue($rs["success"]);
    $this->assertEquals([], $rs["data"]); // the simulator returns no rows
}
```

An unknown route reads `$_SERVER["REQUEST_METHOD"]`, so set it in the test before checking for the 404.

- Raw mode bypasses the response cache: a cached action always runs and returns its data (2.4.0+).
- A protected route whose auth method returns `false` returns the 401 error array and never runs the
  action (2.4.0+), so authenticate first in tests of protected endpoints.

---

## Testing the Query Builder

The query builder doesn't execute SQL, it builds strings. String values are escaped through
`Database::Instance()->Escape()`, which opens a connection, so mock the database first (the simulator
escapes without one):

```php
use Magrathea2\DB\Query;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase {

    protected function setUp(): void {
        \Magrathea2\DB\Database::Instance()->Mock();
    }

    public function testBasicSelect(): void {
        $sql = Query::Select()
            ->Table("products")
            ->Where("active = 1")
            ->Order("name ASC")
            ->Limit(10)
            ->SQL();

        $this->assertStringContainsString("FROM products", $sql);
        $this->assertStringContainsString("WHERE active = 1", $sql);
        $this->assertStringContainsString("ORDER BY name ASC", $sql);
        $this->assertStringContainsString("LIMIT 10", $sql);
    }

    public function testInsert(): void {
        $sql = Query::Insert()
            ->Table("products")
            ->Values(["name" => "Widget", "price" => 9.99])
            ->SQL();

        $this->assertStringContainsString("INSERT INTO products", $sql);
        $this->assertStringContainsString("Widget", $sql);
    }
}
```

---

## Simulating Email

```php
use Magrathea2\MagratheaMail;
use PHPUnit\Framework\TestCase;

class MailTest extends TestCase {

    public function testEmailNotSentInTest(): void {
        $mail = new MagratheaMail();
        $mail->Simulate(); // don't actually send

        $mail->SetTo("test@example.com")
             ->SetFrom("noreply@app.com")
             ->SetSubject("Test")
             ->SetHTMLMessage("<p>Hello</p>");

        $result = $mail->Send();
        $this->assertTrue($result); // simulate always returns true
    }
}
```

---

## TestsManager (the admin's Tests page)

`TestsManager` is a singleton behind the admin's "Tests" page: it lists test folders, their `*Test.php` files
and their `test*` methods, and runs them by shelling out to the framework's `phpunit.phar` with
`phpUnitBootstrap.php`. It's not an assertion library, and it doesn't run anything by itself.

```php
use Magrathea2\Tests\TestsManager;

// e.g. in your Admin subclass, so the folder shows up on the Tests page
TestsManager::Instance()->AddTest([__DIR__ . "/../tests"], "App tests");

// the same calls the Tests page makes
$output = TestsManager::Instance()->RunAllTests();                                  // every registered folder
$output = TestsManager::Instance()->RunFolder(__DIR__ . "/../tests");             // array of output lines
$output = TestsManager::Instance()->RunFile(__DIR__ . "/../tests/OrderTest.php");
$output = TestsManager::Instance()->RunFunction(__DIR__ . "/../tests/OrderTest.php", "testTotal");
```

- `AddTest(array $folders, $name = null)`: without `$name`, the test is named after its first folder
  (`"/app/tests/models"` → `"models"`). Before 2.4.0 it read an undefined variable and got an empty name.
- `RunAllTests()` runs every folder of every registered test, in the order they were added, and returns all
  their output lines (`RunFolder()`'s, one folder after the other). Before 2.4.0 it did nothing.
- `AddMagrathaTests()` (sic) registers the framework's own suites; `Admin` calls it on start.
- The commands need `exec()` and the `phpunit.phar` at the framework's root.
- **Only registered folders (2.4.0+).** `RunFolder()` and `GetTestsFromFolder()` take a registered test folder
  or a folder inside one; `RunFile()`, `RunFunction()` and `GetMethodsFromTest()` take a `*Test.php` file inside
  one. Paths are resolved with `realpath()` first, so `../` and symlinks pointing out are rejected. Anything else
  throws `MagratheaException("invalid test path")`; a `RunFunction()` name that isn't letters, digits and `_`
  throws `"invalid test function"`. Every argument is passed to the shell with `escapeshellarg()`. Folders you
  pass to `AddTest()` are trusted: don't register `/` or your app's root.

---

## Notes

- The framework's mocks are `Database::Instance()->Mock()`, `Singleton::MockClass()` (replace any singleton's instance), `ConfigApp::Mock()` and `MagratheaMail::Simulate()`.
- Prefer integration tests (real database, test schema) over mock-heavy unit tests for critical data paths.
- Always run tests against a dedicated test database — never the production database.
