# Testing with MagratheaPHP2

**Directory:** `src/Tests/`
**Namespace:** `Magrathea2\Tests`

The framework ships with built-in test utilities to help you write unit tests for your models, APIs, and controllers.

---

## Test Infrastructure Files

| File | Description |
|------|-------------|
| `src/Tests/TestsManager.php` | Test runner and assertion manager |
| `src/Tests/TestsHelper.php` | Assertion helpers |
| `src/Tests/phpUnitBootstrap.php` | PHPUnit bootstrap for database and framework setup |

---

## PHPUnit Bootstrap

The `phpUnitBootstrap.php` file initializes the framework for test runs. Reference it in your `phpunit.xml`:

```xml
<!-- phpunit.xml -->
<phpunit bootstrap="vendor/magrathea/magrathea-php2/src/Tests/phpUnitBootstrap.php">
    <testsuites>
        <testsuite name="Application">
            <directory>tests/</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

---

## Mocking the Database

For unit tests that shouldn't hit a real database, use `Database::Mock()`:

```php
use Magrathea2\DB\Database;

class ProductControlTest extends \PHPUnit\Framework\TestCase {

    protected function setUp(): void {
        Database::Instance()->Mock(); // disable real queries
    }
}
```

For integration tests, use `Database::Instance()->SetConfig(...)` to point to a test database:

```php
protected function setUp(): void {
    Database::Instance()->SetConnectionArray([
        "host"     => "localhost",
        "database" => "test_db",
        "username" => "root",
        "password" => "",
    ])->OpenConnectionPlease();
}
```

---

## Mocking Configuration

```php
use Magrathea2\Config;

Config::Instance()->SetConfig([
    "database" => [
        "host"     => "localhost",
        "database" => "test_db",
        "username" => "root",
        "password" => "",
    ],
    "app" => [
        "name" => "Test App",
    ],
]);
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

---

## Testing the Query Builder

The query builder doesn't execute SQL — it just builds strings. This makes it very easy to test:

```php
use Magrathea2\DB\Query;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase {

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

## TestsManager (Built-in Runner)

The `TestsManager` provides a lightweight assertion runner without PHPUnit, useful for integration health checks:

```php
use Magrathea2\Tests\TestsManager;

$tests = new TestsManager();

$tests->Add("Database connects", function() {
    Database::Instance()->OpenConnectionPlease();
    return true;
});

$tests->Add("Config loads", function() {
    $host = Config::Instance()->GetConfig("database/host");
    return !empty($host);
});

$tests->Run();
// Outputs pass/fail for each registered test
```

---

## Notes

- The framework's mock system (`MockClass`, `Mock()`, `Simulate()`) is designed to make unit testing practical without a running database.
- Prefer integration tests (real database, test schema) over mock-heavy unit tests for critical data paths.
- Always run tests against a dedicated test database — never the production database.
