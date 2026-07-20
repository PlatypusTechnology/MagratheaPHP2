<?php

/**
 * Curated "example of use" snippets shown in modals on class pages and on
 * the Examples page. `topics` links a snippet to one or more page slugs
 * (category/slug from DocMap) so it surfaces in the right place.
 * Sourced from skills.MD where noted; others are original, written for this
 * site only (not duplicated back into skills.MD) - see CLAUDE.md.
 */

return [

	[
		"id" => "setup-php",
		"title" => "Bootstrap a project — setup.php",
		"source" => "original",
		"topics" => ["core/magrathea-php"],
		"code" => <<<'PHP'
<?php

die; // remove this line once configuration below is correct for your environment

require "../vendor/autoload.php";

Magrathea2\MagratheaPHP::Instance()
    ->AppPath(realpath(dirname(__FILE__)))
    ->Dev()
    ->Load();
Magrathea2\Bootstrap\Start::Instance()->Load();
PHP,
		"note" => "The `die;` is intentional: it stops the script from running until you've reviewed the config for the target environment, then you delete the line.",
	],

	[
		"id" => "app-bootstrap",
		"title" => "Real project entry point (app/_inc.php)",
		"source" => "skills.MD",
		"topics" => ["core/magrathea-php"],
		"code" => <<<'PHP'
<?php
require __DIR__ . "/../vendor/autoload.php";

error_reporting(E_ALL);
ini_set("display_errors", "1");

Magrathea2\MagratheaPHP::Instance()
    ->MinVersion("2.1.19")
    ->AppPath(realpath(dirname(__FILE__)))
    ->AddCodeFolder("admin", "api", "api/Authentication")
    ->AddFeature("Article")
    ->Load();
PHP,
	],

	[
		"id" => "model-usage",
		"title" => "Create, load, update, delete a Model",
		"source" => "skills.MD",
		"topics" => ["database/orm-model"],
		"code" => <<<'PHP'
// Create and insert
$article = new Article();
$article->title      = "Hello World";
$article->body       = "My first article.";
$article->published  = true;
$article->created_at = now();
$id = $article->Save(); // returns new ID

// Load by primary key
$article = new Article(42);
echo $article->title;

// Update
$article->title = "Updated Title";
$article->Save(); // existing PK -> UPDATE

// Delete
$article->Delete();

// Serialize for an API response
return $article->ToArray();
PHP,
	],

	[
		"id" => "control-usage",
		"title" => "Query data through a Control",
		"source" => "skills.MD",
		"topics" => ["database/orm-control"],
		"code" => <<<'PHP'
use App\Controls\ArticleControl;

$all       = ArticleControl::GetAll();
$published = ArticleControl::GetWhere(["published" => 1]);
$article   = ArticleControl::GetRowWhere(["id" => 42]);

$recent = ArticleControl::RunQuery(
    "SELECT * FROM articles WHERE created_at > '2024-01-01' ORDER BY created_at DESC LIMIT 5"
);

// Paginated for an API response (no COUNT(*), has_more via limit+1 trick)
$query = \Magrathea2\DB\Query::Select()
    ->Obj(new \App\Models\Article())
    ->Where(["published" => 1])
    ->Order("created_at DESC");
$pagination = ArticleControl::GetPagination($query, page: 0, limit: 10);
PHP,
	],

	[
		"id" => "query-builder",
		"title" => "Fluent Query Builder",
		"source" => "skills.MD",
		"topics" => ["database/query-builder"],
		"code" => <<<'PHP'
use Magrathea2\DB\Query;
use Magrathea2\DB\Database;

// SELECT with model + JOIN
$sql = Query::Select()
    ->Obj(\App\Models\Article::class)
    ->SelectExtra("u.name AS author_name")
    ->Inner("users u", "u.id = articles.author_id")
    ->Where(["articles.published" => 1])
    ->Order("created_at DESC")
    ->Limit(10)
    ->SQL();

$rows = Database::Instance()->QueryAll($sql);

// User input must always go through a prepared statement
$result = Database::Instance()->PrepareAndExecute(
    "SELECT * FROM articles WHERE title LIKE ?",
    ["s"],
    ["%{$_GET['search']}%"]
);
PHP,
		"note" => 'Never interpolate raw $_GET/$_POST into SQL — use PrepareAndExecute() or Query::Clean().',
	],

	[
		"id" => "api-controller",
		"title" => "API Controller action",
		"source" => "original",
		"topics" => ["api/api-controller", "api/magrathea-api"],
		"code" => <<<'PHP'
namespace App\Api;

use Magrathea2\MagratheaApiControl;
use Magrathea2\Exceptions\MagratheaApiException;
use App\Controls\ArticleControl;

class ArticleApi extends MagratheaApiControl {

    public function GetList() {
        $page  = (int)($this->request["page"] ?? 0);
        $query = \Magrathea2\DB\Query::Select()
            ->Obj(new \App\Models\Article())
            ->Where(["published" => 1]);

        return ArticleControl::GetPagination($query, page: $page, limit: 20);
    }

    public function Get($id) {
        $article = new \App\Models\Article((int)$id);
        if (!$article->id) {
            throw new MagratheaApiException("Article not found", 404);
        }
        return $article->ToArray();
    }
}
PHP,
	],

	[
		"id" => "jwt-auth",
		"title" => "JWT Authentication",
		"source" => "skills.MD",
		"topics" => ["api/authentication"],
		"code" => <<<'PHP'
use Magrathea2\Authentication;

// Issuing a token (login controller)
$token = Authentication::Instance()->GenerateToken([
    "user_id" => $user->id,
    "role"    => $user->role,
]);
return ["token" => $token];

// Reading the current user in any other controller
$payload = Authentication::Instance()->GetTokenData();
$userId  = $payload->user_id ?? null;
PHP,
	],

	[
		"id" => "caching",
		"title" => "Caching a controller response",
		"source" => "skills.MD",
		"topics" => ["utilities/cache"],
		"code" => <<<'PHP'
use Magrathea2\MagratheaCache;

$key = "articles:published:page:{$page}";

$cached = MagratheaCache::Instance()->Get($key);
if ($cached !== null) {
    return $cached;
}

$data = ArticleControl::GetWhere(["published" => 1]);
MagratheaCache::Instance()->Set($key, $data, 300); // seconds

return $data;
PHP,
	],

	[
		"id" => "logging-debug",
		"title" => "Logging & Debugging",
		"source" => "skills.MD",
		"topics" => ["utilities/logger", "utilities/debugger"],
		"code" => <<<'PHP'
use Magrathea2\Logger;
use Magrathea2\Debugger;

// Production-safe logging
Logger::Instance()->Log("Order #{$order->id} processed", "orders");

// Development-only debug output (silenced unless Dev mode is on)
Debugger::Instance()->Debug($order, "order-debug");
PHP,
	],

	[
		"id" => "mail",
		"title" => "Sending Email",
		"source" => "skills.MD",
		"topics" => ["utilities/mail"],
		"code" => <<<'PHP'
use Magrathea2\MagratheaMailSMTP;

MagratheaMailSMTP::Instance()
    ->To("someone@example.com", "Someone")
    ->Subject("Welcome!")
    ->Body("<p>Thanks for signing up.</p>")
    ->Send();
PHP,
	],

	[
		"id" => "admin-entry",
		"title" => "Admin panel entry point",
		"source" => "skills.MD",
		"topics" => ["admin/admin", "admin/admin-manager"],
		"code" => <<<'PHP'
<?php
// public/admin.php
require __DIR__ . "/../app/_inc.php";

Magrathea2\Admin\AdminManager::Instance()
    ->SetAdminClass(\App\Admin\MyAdmin::class)
    ->Run();
PHP,
	],

	[
		"id" => "admin-crud-feature",
		"title" => "Registering a CRUD admin feature",
		"source" => "original",
		"topics" => ["admin/admin-features"],
		"code" => <<<'PHP'
use Magrathea2\Admin\Features\AdminFeatureCrud;

$this->AddFeature(
    new AdminFeatureCrud("Articles", \App\Models\Article::class, [
        "icon"   => "bi-file-text",
        "fields" => ["title", "published", "created_at"],
    ])
);
PHP,
	],

	[
		"id" => "config-file",
		"title" => "Writing config/magrathea.conf",
		"source" => "skills.MD",
		"topics" => ["core/config"],
		"lang" => "ini",
		"code" => <<<'PHP'
[general]
	use_environment = "default"
	time_zone = "America/Sao_Paulo"

[dev]
	db_host = "localhost"
	db_name = "my_db"
	db_user = "root"
	db_pass = "secret"
	db_port = "3306"
	site_path = "/var/www/html"
	logs_path = "../logs"
	cache_path = "../cache"
	timezone = "America/Sao_Paulo"
	server_url = "http://localhost:8080"
	jwt_key = "a-very-long-random-string-here"

[production]
	db_host = "db.prod.server"
	db_name = "my_db"
	db_user = "app_user"
	db_pass = "$=DB_PASSWORD"
	db_port = "3306"
	site_path = "/var/www/html"
	logs_path = "../logs"
	cache_path = "../cache"
	timezone = "America/Sao_Paulo"
	server_url = "https://myapp.example.com"
	jwt_key = "$=JWT_SECRET"
PHP,
		"note" => "Sections are named by environment (not by topic) and each repeats its own full flat key set — there is no [section:env] inheritance. Use \$=ENV_VAR for secrets, never hardcode credentials.",
	],

	[
		"id" => "config",
		"title" => "Reading configuration",
		"source" => "original",
		"topics" => ["core/config"],
		"code" => <<<'PHP'
use Magrathea2\Config;

// Get() reads a bare key from the ACTIVE environment section
// (general/use_environment, or whatever ->SetEnvironment() set)
$host = Config::Instance()->Get("db_host");

// GetConfig() reads from the config root, independent of environment -
// use "section/key" slash notation to reach into a named section
$prodHost = Config::Instance()->GetConfig("production/db_host");

// GetConfigSection() returns any named section as an array
// (the section name is required - there is no "active section" default)
$prodSection = Config::Instance()->GetConfigSection("production");
echo $prodSection["db_host"];
PHP,
		"note" => "Get() and GetConfig() are not interchangeable: Get() is environment-scoped, GetConfig() is not. This differs from skills.MD's simplified snippet - verified directly against src/Config.php.",
	],

];
