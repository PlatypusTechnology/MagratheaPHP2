### 2.4.0
2026-10
	- **note:** upgrading from 2.3.5? Read the migration guide first: `docs/mds/migrations/migrate-2.4.0.md` in the repository (https://github.com/PlatypusTechnology/MagratheaPHP2; `docs/` isn't shipped in `vendor/`), or https://www.platypusweb.com.br/magratheaphp2/docs/?p=migrations/migrate-2.4.0 . It lists, per change, how to tell if it affects a project and what to do. Composer: `~2.3.5`/`2.3.*` constraints must be changed (to `^2.4`) to receive 2.4.0; `^2.3` receives it on the next `composer update`. Requires PHP 8.1+.
	- **fix:** Bootstrap setup wizard (`Bootstrap\Start::Load()`) is locked unless the active environment's section has `bootstrap_unlocked = true` (read with `FILTER_VALIDATE_BOOLEAN`); locked answers 403 with a page showing only the environment name. `?action=` is whitelisted (`Start::ACTIONS`), unknown actions get 404 instead of an arbitrary `include`. The sample config enables the flag in `[dev]` only.
	- **fix:** Admin first-run setup (super admin creation) is shown only when there are zero admin users (`Admin\Start::GetInstallState()`). A DB error now shows a 500 error page (exception message only in DEV, logged) instead of the setup form; a missing `admin_install_date` row is written and the login page is shown. The setup POST re-checks `CanCreateFirstUser()` before creating the user.
	- **fix:** `MagratheaApi::ExecuteUrl()` returns the 401 error when a protected route's auth method returns `false`; in raw mode (`Run(true)`, `SetRaw()`, `ExecuteUrl()`) the action used to run anyway.
	- **fix:** API error responses in DEV/ANALYSIS (`ReturnError()`, `ReturnApiException()`) build `stacktrace` with `DEBUG_BACKTRACE_IGNORE_ARGS`: frames no longer carry call arguments (credentials, config arrays, JWT keys).
	- **fix:** the admin File Editor is no longer a default feature (`Admin::SetFeatures()`); opt in with `$this->LoadFileEditor()` in the app's `Admin` subclass. The code generator's "View File" link only shows when the File Editor is loaded.
	- **fix:** `MagratheaPHP::Connect()` passes the config's `db_port` (it was never read), and `Database::SetConnection()` / `DatabaseSimulate::SetConnection()` keep the port they're given (the condition was inverted, so a real port was always dropped). No `db_port` → mysqli's default port.
	- **new:** `Database` keeps one connection per instance for the whole request (opened lazily through the new protected, untyped `CreateConnection()`, closed by a shutdown function). `CloseConnectionThanks()` is a no-op; new `Disconnect()`, static `DisconnectAll()` and `LastAffectedRows()` (also on `DatabaseSimulate`). MySQL errors 2006/2013/4031 (4031: MySQL 8.0.24+ disconnected an idle client, past `wait_timeout`) throw the new `MagratheaDBConnectionLostException` (code = errno, query data attached, dead connection dropped, never retried). `QueryRow()` frees its result on 0 rows.
	- **note:** long-running CLI workers must call `Database::Instance()->Disconnect()` at the start of each job (see the migration file).
	- **fix:** `Database::QueryMulti()` (and so a multi-statement `Query()`) returns SELECT rows: it read `store_result()` after `query()`, which has nothing left to store, so `result` was always empty. It now reads and frees the result `query()` returns.
	- **fix:** `Query::Clean()` didn't escape backslashes, so it didn't prevent SQL injection (`\'` → `\\'`). It now calls `Database::Instance()->Escape()` (so it needs a configured database) and is deprecated: use `Escape()`.
	- **fix:** `Database::QueryTransaction()` stops at the first failing statement and rolls the whole transaction back, then restores autocommit (2.3.5 only checked the last statement's error, never rolled back, and left autocommit off). Errors thrown by mysqli (PHP 8.1+ default) are thrown as `MagratheaDBException` after the rollback; returned errors make it return `false` (reporting off). A failed `commit()` is handled the same way. If the rollback itself fails, the connection is dropped and the original error is still the one reported. Success returns `true` (it returned `null`).
	- **new:** `Database::Transaction(callable $fn)` runs `$fn` in a transaction and returns its result: commit when it returns, rollback and rethrow the original exception when it throws (a failed rollback never replaces it). Nested calls run inline (no savepoints); an inner failure caught by outer code still makes the outermost call roll back and throw `MagratheaDBException`. Failed `START TRANSACTION`/`COMMIT` throw `MagratheaDBException` (2006/2013/4031 as `MagratheaDBConnectionLostException`). `QueryTransaction()` throws when called inside `Transaction()`. `DatabaseSimulate::Transaction()` has the same rules and logs `START TRANSACTION`/`COMMIT`/`ROLLBACK`.
	- **fix:** `MagratheaCache::SaveFile()` writes atomically: a temp file (`<cache file>.<pid>.<random>.tmp`, opened with `'x'`) in the cache dir is renamed over the cache file, so concurrent requests no longer serve an empty or partial cached response. On failure the temp file is removed, the old cache file is kept, and it returns `false` (2.3.5: warning + `TypeError` on an unwritable dir). `RemoveAllCache()` also clears `.tmp` files left by killed processes.
	- **fix:** API calls in raw mode (`SetRaw()`, `Run(true)`) bypass the response cache: a cache hit or save used to print the JSON and `die`, so the caller (e.g. a PHPUnit test) never got the return value. **new:** `MagratheaCache::Bypass(bool $bypass = true)`, set by `ExecuteUrl()` on every call.
	- **fix:** `MagratheaCache::Clear()` (and so `MagratheaApiControl::CacheClear()`) no longer replaces the current cache key: an action calling `Cache("a")` then `CacheClear("b")` saved its response as `b.json`.
	- **fix:** `MagratheaApiControl::Cache()` writes its "adding cache for …" line to the Logger only in DEV (`IsDev()`); it logged on every call, cache hits included, flooding the logs on polled routes. The line's URI was always empty (it read `$_REQUEST["REQUEST_URI"]`); it reads `$_SERVER` now.
	- **fix:** API error responses use real HTTP statuses (2.3.5 sent most of them as 200): unknown routes 404; `ReturnError()`'s `$status` defaults to `null` and is derived from `$code`; `ReturnFail()` derives it from an exception's code (500 for code 0) and sends any `\Throwable` as `{message, code?}` (a plain `\Exception` was encoded as `{}`). Non-exception data in `ReturnFail()` keeps 200. Same in `Admin\Api\AdminApi`; the admin's `callApi()` JS helper resolves error bodies so its callers keep working.
	- **fix:** `MagratheaApiException::SetStatus()` is honored (400-599) by `ReturnApiException()`/`ReturnFail()`; it was stored and never read. `$status` defaults to `null` (was 500/200 by `$kill`). A missing `Authorization` token (`GetAuthorizationToken()`) now answers HTTP 401 instead of 400.
	- **fix:** code → HTTP status mapping (3 digits as is, 4 digits / 10, 5 digits / 100) gives 500 when the result is outside 400-599 (1062 → 106, 2001 → 200, 7000 → 700 before). One private `MagratheaApi::HttpStatusFromCode()` for all error paths.
	- **fix:** `MagratheaApi::ExecuteUrl()` and `Admin\Api\AdminApi::Call()` catch PHP `\Error`s (`TypeError`, ...) thrown by actions/auth methods: new protected `ReturnInternalError()` logs them (`Logger::LogError()`, `error_log()` fallback) and sends a JSON 500, with the message, class and file:line in DEV only ("Internal Server Error" otherwise). They used to escape as a PHP fatal with an HTML body.
	- **fix:** `Logger::LogError()` logs PHP `\Error`s (class, message, file:line) and non-exceptions (`print_r`, e.g. `LogLastError()`); both used to log a line with no message.
	- **fix:** `MagratheaApi::BaseAuthorization()` takes a `MagratheaApiAuth` (it was typed `MagratheaApiControl` but stored into a `?MagratheaApiAuth` property, so a plain control failed with a `TypeError` on assignment). Docs and `skills.MD` examples passed an `AuthControl extends MagratheaApiControl`; they now extend `MagratheaApiAuth`.
	- **fix:** in DEV and DEBUG, debug items added during an API call (`ExecuteUrl()`/`Run()`), including every `MagratheaException` when it's created, no longer print HTML before (DEV `<pre>`) or after (DEBUG's end-of-request `Show()`) the JSON: they're sent in the response as `_debug` (associative-array responses only; in DEBUG with the items stored before the call; never written to the cache file; not in raw mode). DEBUG's shutdown `Show()` is skipped after any response with a JSON `Content-Type`. Pages and CLI still print them. **new:** `Debugger::StartCapture()`, `StopCapture()`, `IsCapturing()`, `GetCaptured()`, `WithCaptured()`, `ShowAtShutdown()`.
	- **fix:** `Database` query errors reach the Debugger once: `ErrorHandle()` added the `MagratheaDBException` that had already added itself, so LOG logged each one twice.
	- **note:** `created_at`/`updated_at` handling is unchanged and now documented (ORM model docs, `skills.MD`): `Insert()` leaves unset timestamps to the database's default or trigger (columns need `DEFAULT CURRENT_TIMESTAMP`), sends assigned ones as PHP's `now()`, and sets the object's timestamps to `now()` without reading the row back; `Update()` writes `updated_at = now()`, except on a never-loaded object updated after a `Set()` (unchanged from 2.3.5), and doesn't change the object's own `updated_at`.
	- **fix:** `MagratheaModel::Update()` on a loaded object (`LoadObjectFromTableRow()`, so the constructor, `GetById()` and every Control builder, or after its own `Insert()`/`InsertWithPk()`/`Update()`) writes the fields changed since then, plain assignments included, plus `updated_at`. One `Set()` used to make it write only the `Set()` fields and drop plain assignments silently. "Changed" is null-aware, then compared as strings (bools as `"1"`/`"0"`). Never-loaded objects keep the 2.3.5 rule. `$dirtyValues` is cleared after a successful save.
	- **fix:** `MagratheaModel::Insert()`/`Update()` save a related model assigned to a field as its id (inserting it first when it has none, through the private `GetRelationId()`). The check was `is_a($value, "MagratheaModel")`, an unqualified name that never matched, so the object itself was bound (`1` with a warning for int fields, its `__toString()` for string fields). On a loaded object it's compared by id.
	- **fix:** `MagratheaModel::Update()` writes SQL `NULL` for an int/boolean field that is `null` on a loaded object (it skipped every `null` int, so a nullable FK could never be cleared), and for `""` on any object (it was bound raw: a strict-mode error or `0`). Other int/boolean values go through `intval()` (the `$type == "integer"` check never matched an `int` field) and `null` is bound as `null` (it was `intval(null)` = `0`). Never-loaded objects still skip `null` ints. `Insert()` is unchanged.
	- **fix:** `Database::PrepareAndExecute()` throws `MagratheaDBException` (query and values attached with `SetQueryData()`) when an execute fails with mysqli error reporting off. It printed `got error! ` (into pages and before API JSON) and returned `null`, so `Insert()` set the PK to `null`, `Update()` returned `null` and the API answered success. PHP 8.1+'s default reporting already threw.
	- **fix:** `Database::QueryMulti($queries, false)` (and `ImportFile($file, false)`, used by `Admin\Install::InstallDatabase()`) records a failing statement and runs the next ones with PHP 8.1+'s default reporting too: `mysqli_sql_exception` is caught per statement (a lost connection still throws). The first failure used to throw whatever `$killable` said.
	- **fix:** `MagratheaDBException`s built by `Database` carry the values of the failed statement: `ErrorHandle()` and `makeValuesReferenced()` called `SetData($sql, $values)`, which takes one argument, so the values were dropped and the SQL stored as the exception's data. They call `SetQueryData()` now. `Debugger` no longer needs to hide a DB exception's `data` when it equals the query.
	- **fix:** every `Database` query error throws `MagratheaDBException`, whatever mysqli's error reporting mode: mysqli's `mysqli_sql_exception` (PHP 8.1+ default) is wrapped (new private `FromMysqliException()`/`MysqliCall()`), with MySQL's errno as the code and mysqli's exception as `getPrevious()`; returned errors get the errno too (the code was always 0). Before, `Query()`, `QueryAll()`, `QueryOne()`, `QueryMulti()`, `QueryTransaction()`, `PrepareAndExecute()`'s prepare and `Transaction()`'s `COMMIT` let `mysqli_sql_exception` out as is. `QueryTransaction()` also throws `MagratheaDBConnectionLostException` without a rollback attempt on a lost `COMMIT`. `Query()` and `QueryTransaction()` still return `false` on a returned error (reporting off).
	- **fix:** `MagratheaApi` (and `AdminApi`) answers a `MagratheaDBException` or `mysqli_sql_exception` with HTTP 500, whatever its code (`HttpStatusFromException()`; `ReturnError()` derives the status from `$data` when it's an exception). A MySQL errno would have mapped to a 4xx (4031 → 403, MariaDB's 4025 → 402).
	- **fix:** `DatabaseSimulate::PrepareAndExecute()` loses its dead `try`/`catch` (it caught the nonexistent `Magrathea2\DB\Exception`, around code that can't throw); it always returns `42`, as before.
	- **note:** `composer.json` requires `"php": ">=8.1"`. The framework already needed it (`enum QueryType`, since 2023): this only makes Composer check it.
	- **fix:** model writes bind `int`/`boolean` fields as integers: `PrepareAndExecute()` (and `DatabaseSimulate`'s) didn't recognize `"integer"`, the category `GetDataTypeFromField()` returns for them, so every value was bound as a string (MySQL converted it). `float` fields (`"decimal"`) stay strings, so exact values don't go through a PHP float. `Update()` binds the `WHERE` PK by the PK field's type, as `Delete()` does; it passed the field name to `GetDataTypeFromField()`, which matched nothing. An int PK above `PHP_INT_MAX` (`BIGINT UNSIGNED`) is now clamped in `Update()`/`Delete()`: declare it as `"string"`.
	- **fix:** the timezone config key is `timezone` (environment section), the only one `MagratheaPHP::Load()` ever read. The sample config (`[general]` and `[dev]`), `skills.MD`, the Config docs, the docs examples and the test config used `time_zone`, which was ignored; they use `timezone` now, and the dead `[general]` copy is gone. `time_zone` is still ignored.
	- **new:** MySQL's session timezone follows PHP's: with a `timezone` in the config, each new connection runs `SET time_zone = '<date_default_timezone_get()>'` after `set_charset()`. New config key `db_sync_timezone` (default on; `false`/`0`/`off`/`no`/empty, quoted or not, turn it off; off without `timezone`). If MySQL doesn't know the name (1298, timezone tables not loaded), the offset `date('P')` is used, logged in DEV/DEBUG. **new:** `Database::SetTimezoneSync()` (called by `MagratheaPHP::Connect()`), `GetSyncedTimezone()`; `DatabaseSimulate` logs the `SET time_zone`.
	- **fix:** the autoloader looks for `<ClassName>.php` in every code folder: if several have it, it loads the one whose `namespace` matches the requested class (new `fileDeclaresNamespace()`; case-insensitive; no match → the first one, as before), and in DEV adds a debug item listing the files. It used to load the first file found, so two classes with the same basename collided silently. The same file reached through two folder entries counts once.
	- **fix:** the autoloader returns silently when it can't find a class in the app namespace (`AppNamespace()`); it threw `MagratheaException`, which broke `class_exists()` and the autoloaders registered after it. A missing class now fails with PHP's `Error: Class "…" not found`. In DEV, the "Could not find class [...]. Are the code folders correct?" hint is added to the Debugger (a string, with the code folders).
	- **new:** `Magrathea2\TimezoneCheck`: `SyncState()` and the troubleshooter `Run()` (findings: `time_zone` without `timezone`, invalid name, PHP vs MySQL zones and clocks in/out of sync, sync state and offset fallback). Shown on the admin's Server page and the Bootstrap wizard's config step; never run on normal requests.
	- **note:** projects with `timezone` set and MySQL in another zone see `TIMESTAMP` values and `NOW()` in PHP's zone after upgrading; renaming `time_zone` → `timezone` also switches PHP's timezone. See the migration file (items 33, 34); `db_sync_timezone = false` opts out.
	- **fix:** `MagratheaModelControl::ShowAll()` builds its model with `GetModelName()`, like every other Control method; it did `new static::$modelName()`, ignoring `$modelNamespace`, so it failed with `Class not found` for any namespaced model. It also escapes the values it prints (`htmlspecialchars()`) and no longer closes the last row twice. Deprecated: a debug helper, to be removed in 3.0.
	- **new:** `decimal` field type for SQL `DECIMAL` columns (exact numbers, e.g. money): the value is always the string the driver returns, never cast (with `$strictTypes`, a non-numeric value throws `MagratheaModelException`, like `int`/`float`), and it's bound as a string (`GetDataTypeFromField()` → `"text"`). Added to `magrathea_getTypesArr()` and `ObjectManager::GetTypesArr()`. `ObjectManager::GetType()` detects `DECIMAL` → `decimal` and `DOUBLE` → `float` (both were `"unknown"`); the CREATE TABLE helper emits `decimal(10,2) DEFAULT NULL /* adjust precision */`. `float` is unchanged: with `$strictTypes` it's cast with `(float)`, which rounds large DECIMALs, so DECIMAL columns should be declared `decimal`.
	- **security fix:** the admin Cache feature (`AdminFeatureCache`, loaded by default) joined the posted `file` to the cache path unchecked, so any logged-in admin could write (`Save`, i.e. drop a PHP file: code execution), read (`View`/`Edit`) or delete (`Remove`) any file the web server can reach with `file=../...`. They now accept only a plain name of an existing file directly in the cache folder (symlinks pointing outside are rejected); anything else throws `MagratheaException("invalid file")`. `ClearCache` answers 405 unless it's a POST, so it goes through the admin CSRF check (a GET link could purge the cache). Cached content and file names are HTML-escaped in the views (cached API responses with user-submitted HTML ran as script in the admin).
	- **security fix:** the admin Tests page passed the posted folder/file/function into `Tests\TestsManager`'s shell command unescaped, so any logged-in admin could run shell commands or have PHPUnit run any PHP file. `RunFolder()`, `RunFile()`, `RunFunction()`, `GetTestsFromFolder()` and `GetMethodsFromTest()` now only accept a path whose `realpath()` is inside a folder registered with `AddTest()`/`AddMagrathaTests()` (files: `*Test.php`; function names: `\w+`), else `MagratheaException("invalid test path")` / `("invalid test function")`; every shell argument goes through `escapeshellarg()`. The Tests page's actions show those errors and escape output, file names and `onclick` arguments. New protected `TestsManager::Execute()` runs the command. Also: `RunAllTests()` was an empty method, it runs every registered folder now (`RunFolder()`, in registration order); `AddTest()` without a `$name` read an undefined `$test` (warning, empty name), it's named after its first folder now.
	- **fix:** `MagratheaPHP::LoadVendor()` only looked for the project's `vendor/autoload.php` (framework installed as a dependency), so in a standalone checkout it failed with a fatal `require`; it now also looks in the framework's own `vendor/`, uses `require_once`, and throws `MagratheaException` if neither exists. Deprecated, to be removed in 3.0: the class can only be loaded after `vendor/autoload.php` is required, so calling it never does anything. `Tests\TestsManager` no longer calls it (its `Initialize()` is gone).
	- **fix:** the admin entry file generated by `Admin\Install` (`magrathea.php`, Bootstrap wizard's "install admin" step) requires `__DIR__."/../vendor/autoload.php"`; it required `"../vendor/autoload.php"`, relative to the current folder, so it failed with a fatal error when run from anywhere but its own folder (CLI, some server setups). Files generated before are not rewritten.
	- **security fix:** the admin included `"pages/".$_GET["magrathea_page"].".php"` and `"actions/".$_GET["magrathea_subpage"].".php"` unchecked, so `../` included any `.php` file the web server can read (logged-in admins, or a link opened by one: GET has no CSRF check), the Bootstrap wizard's actions included. **new:** `Admin\Start::GetViewFile($folder, $name)`: only a plain name of an existing file in `views/pages/`/`views/actions/`; anything else answers 404 ("action not found" for an action, a "Page not found" alert for a page).
	- **security fix:** the admin's Query Tester ("Magrathea Query", `?magrathea_page=query-creator`, in the default Database menu) appended the posted text to PHP code and ran it with `eval()`: any logged-in admin ran PHP on the server. Removed (page, action, `query.js`, the `mag-query` menu item); `AdminMenu::GetItem("mag-query")` is `null`, and `AdminMenu::Add()` ignores `null` items. Migration item 43.
	- **security fix:** admin backups put the posted file name into a `mysqldump … > <file>` command run with `system()` (shell injection), with the DB password on the command line and sent back to the browser, and `backup-file` read any file with `../`. **new:** `AdminDatabase::ValidateBackupFileName()` (`.sql` appended; `^[A-Za-z0-9][A-Za-z0-9._-]*\.sql$` without `..`, else `MagratheaException("invalid file name")`). `DoBackup()` refuses an existing file, runs `mysqldump` through `proc_open()` with an argument array (new protected `RunProcess()`), passes the credentials in a temporary `0600` `[client]` defaults file deleted afterwards, deletes the partial file and throws on failure, and returns `["file", "size"]`. `AdminDatabase::GetCommand()` is removed; the `CreateBackupFile` API answers `file`/`size`, without `command`. `backup-file` accepts only a `.sql` file in the backups folder. Migration item 44.
	- **security fix:** the admin's `file-view` action read any file whose path was posted in `file` (the logs page posted absolute paths), and its tail printed log lines unescaped. It now renders only a `$viewFile` set by the including view (404 "action not found" when called directly), escapes the tail, and can be included twice (a closure instead of a global function; `return` instead of `die`, which also cut short the pages including it). **new:** `log-view` action (a file name inside the logs folder, `lines` clamped to 1–10000), used by the logs page; **new:** `Admin\AdminFiles::ResolveInFolder($folder, $name)`. Migration item 45.
	- **security fix:** stored XSS in the admin: `AdminElements::Table()` and the form elements printed values as HTML, so app data ran as script in the admin (every CRUD list and form, App Config, users, logs, query results). `Table()` escapes scalar values (`null` empty, arrays/objects as escaped JSON); callable columns and callable row values stay raw HTML. `Input()`/`Textarea()`/`Select()` (option values and labels, `Ref()` included)/`Checkbox()` escape their values, and `AdminForm` its `hidden` and non-callable `empty` values. Also escaped: the Queries result card (which no longer passes a failed SELECT's exception to `Table()`, a TypeError), user log details, user/App Config edit headers, the CRUD form header, App Config's key/value columns, the Bootstrap wizard's `run-query`/`show-tables` (which also backtick-quotes table names; `run-query` called the nonexistent `getMsg()`). Admin views that passed HTML as values use callables now (Structure, Cache details, relation forms); the Cache views no longer pre-escape. The element templates and the touched views use `<?php` instead of short open tags. Migration item 46.
	- **security fix:** reflected XSS in admin actions through GET links (no CSRF check on GET): `object`, `table` and `onclose` in `object-detail`, `object-create`, `object-edit` (and its relation views), `code-create` and `code-gen-view`, the CRUD feature's `id`, the Config page's `env` and the "Feature not available" name were printed unescaped (`onclose` straight into an `onclick`). They're escaped now; **new:** `AdminElements::JsCallback($fn, $args)` accepts only a plain JS function name. `AdminDatabase::GetTableColumns()` backtick-quotes the table. `object-detail` answers "object not found" for an unknown object; `code-create` read an undefined `$data` for its errors (TypeError). Migration item 47.
	- **security fix:** admin feature URLs (`magrathea_feature_subpage`, `magrathea_feature_action`) called any public method of the feature over GET, with no CSRF check: a GET link made `AdminCrudObject::Save()` save an empty object on any CRUD feature. **new:** `AdminFeature::IsDispatchable($method)` (a public, non-static method of the feature with no required parameters, named `^[A-Za-z]\w*$`, not one of `AdminFeature`'s own methods except `GetPage`/`Index`), checked by `views/index.php`, `Start::CheckFeature()` and `AdminFeature::GetPage()`; anything else answers 404. `AdminCrudObject::Save()`/`Delete()` and `AdminFeatureAppConfig::Import()` answer 405 unless it's a POST; `ImportData()`'s progress output is escaped, and so is `CheckFeature()`'s "Feature not available" name. Also fixed: `magrathea_feature_subpage=GetPage` made `GetPage()` call itself until PHP ran out of memory. Migration item 48.
	- **fix:** admin links to pages that don't exist: the Structure page's "Cache" link (`GetPageUrl("cache")`) goes to the loaded Cache feature, and `AdminMenu::GetItem("app-conf")`/`GetItem("tools-file-editor")` point to `AdminFeatureAppConfig`/`AdminFeatureFileEditor` (they pointed to the nonexistent `config-data`/`file-editor` pages). **new:** `AdminMenu::FeatureItem($title, $featureId)`.
	- **fix:** `AdminCrudObject` subclasses ran `Initialize()` twice per request (its constructor called it, then `AdminFeature::__construct()` called it again), so `SetObject()` built the control twice and JS files added there were registered twice. `AdminFeature::__construct()` now calls `Initialize()` once and applies the `$featureId` class-name fallback after it; feature ids don't change, but a feature that declares `$featureId` without a value (or `""`) and reads it in `Initialize()` no longer sees the fallback there.
	- **fix:** the framework's views (52 files: the admin's layout, login, setup, menu, pages, actions and features, the Bootstrap wizard's steps, `Errors/views/view-exception.php`) opened PHP with the short `<?` tag, so with `short_open_tag = Off` (PHP's recommended `php.ini` files) they printed their code as text and the admin didn't render. They use `<?php` now; a test fails if a short open tag comes back.
	- **new:** the admin's object form (create/edit) has a "Strict types" select, saved as the per-object `strict_types` key in `magrathea_objects.conf`. `CodeCreator::GenerateBaseCodeForObject()` emits `protected $strictTypes = true;` when it's `true`, and nothing otherwise (never `= false`). New objects default to `true`; objects without the key (created before 2.4.0) default to `false` in the form and generate the same Base class as before. `$strictTypes = true` is now the documented recommendation for new models; the framework default is still `false`.

### 2.3.5
2026-09
	- **fix:** `ConfigFile::GetConfig()` now throws `MagratheaConfigException` instead of silently returning `false` when `parse_ini_file()` fails to parse an existing-but-malformed config file. Previously the raw `false` propagated to callers typed to return `array` — e.g. `Admin\ObjectManager::GetFullObjectData(): array`, which crashed with an uncaught `TypeError` (`Return value must be of type array, false returned`) instead of the intended `catch(Exception)` in the "Loading Magrathea Objects" bootstrap step showing a friendly error. A genuinely empty config file is unaffected (`parse_ini_file()` returns `array()`, not `false`, for an empty file) and still loads as an empty config
	- **fix:** `MagratheaApiControl::jwtDecode()` now catches `firebase/php-jwt`'s decode exceptions (`ExpiredException`, `SignatureInvalidException`, `BeforeValidException`, `\UnexpectedValueException`, `\DomainException`, `\InvalidArgumentException`) and translates them into `MagratheaApiException` — `ExpiredException` becomes code `4010` (unified with `MagratheaApiAuth::CheckExpire()`'s own expiry code), with the token's `exp` claim attached as `expiredAt` via `SetData()`; everything else becomes code `401`. Previously these library exceptions propagated uncaught out of `GetTokenInfo()`, were only caught by `MagratheaApi::ExecuteUrl()`'s generic `catch(\Exception)` fallback, and — since that fallback calls `ReturnError()` with no explicit `$status` and `getCode()` is always `0` on these exceptions — came back as **HTTP 200** with `{"success": false, ...}` instead of a real 401
	- **fix:** `MagratheaApiAuth::IsLogged()` no longer stomps the code of any `MagratheaApiException` it catches (including `CheckExpire()`'s own `4010` and the `jwtDecode()` codes above) down to a hardcoded `401` — it now lets the exception propagate with its original code, so callers can actually distinguish an expired token (`4010`) from any other invalid-token case (`401`)
	- **fix:** Admin "App Configuration" feature no longer crashes the whole admin page after a successful save when `cache_path` is empty or points to a non-existent directory. The post-save `MagratheaCache::DeleteFile("settings")` call (clearing cached settings) let `LoadCachePath()`'s `MagratheaException` ("cache path is invalid: ...") propagate uncaught, even though the config value had already been saved. The exception is now caught and shown as a warning alert ("Config saved, but cache could not be cleared: ..."). `MagratheaCache` itself is unchanged — `Cache()`/`SaveFile()`/`DeleteFile()` still throw on an invalid path

### 2.3.4
2026-09
	- **security fix:** SQL injection via unescaped values in `Query::BuildWhere()` and its siblings `QueryUpdate::SQL()` (`Set()`/`SetArray()`) and `QueryInsert::SQL()` (`Values()`) — all three interpolated raw string values directly into the SQL string with no escaping anywhere in the chain, so any string value passed through `Where(array)`/`WhereArray()`/`W()`, `Set()`/`SetArray()`, or `Values()` landed in raw SQL unescaped. `MagratheaModel::GetById()`'s non-`"int"`-typed-PK branch (e.g. `uuid` PKs) had the same root cause at its own call site. `Query::Clean()` existed as a sanitizer but was never actually called anywhere in the framework
	- **new:** `Database::Escape(mixed $value): string` — wraps `mysqli::real_escape_string()` (opening a connection if needed), and is now the escaping used internally by all the call sites above. `DatabaseSimulate::Escape()` mirrors the same escaping rules without needing a live connection, for the `Database::Mock()` test path
	- **note:** behavior-preserving for legitimate values (escaping a value with no special characters is a no-op) — regression tests added in `src/Tests/MagratheaTests/sqlInjectionTest.php`. Raw SQL strings (`Where(string $whereSql)`, `SetRaw()`, `Table()`, `Order()`, `Group()`, `Join()`/`Inner()`/`Left()`) are still never escaped and remain the caller's responsibility, same as before
	- **note:** projects pinning `platypustechnology/magratheaphp2` to an exact version need their `composer.json` constraint bumped and `composer update` run separately — this fix does not propagate automatically

### 2.3.3
2026-09
	- **fix:** `MagratheaPHP::Version()` no longer returns the `version` file's trailing newline. The untrimmed `"2.3.2\n"` made `MinVersion()`'s `version_compare($magVersion, $version, ">=")` return `false` even when both versions were identical, since PHP compares the embedded `\n` as part of the string — every app hit "Magrathea version outdated" against its own exact version, with the `\n` invisible in the error message. Now strips trailing `\r\n` the same way `AppVersion()` already does

### 2.3.2
2026-09
	- **new:** `MagratheaApi::ReturnApiException()` gained a third exception-code→HTTP-status bucket for 5-digit codes (`10000`-`59999`), mapped as `intval($exCode / 100)` — giving `XXXYY` where `XXX` is the 3-digit HTTP status and `YY` is a 00-99 sub-code, i.e. 100 sub-codes per status instead of the existing 4-digit scheme's 10 (`4030`-`4039` etc). Purely additive: the new range sits above the existing `1000`-`9999` bucket with no overlap, so no 3-digit or 4-digit code changes meaning. Added because a consumer (guia.lol's API) exhausted its 403 sub-code bucket (4031-4038 in use, only 4039 free) and a 5-digit code previously fell through to the `else` branch, silently returning a misleading HTTP 500 instead of the intended status
	- **note:** this logic is not duplicated anywhere else in the package — `ReturnApiException()` is the only place exception codes are mapped to HTTP status codes

### 2.3.1
2026-08
	- **fix:** two admin dispatch paths could call a feature's action method while skipping `AdminFeature::HasPermission()` entirely — `Start::CheckFeature()` (the `?magrathea_feature=X&magrathea_feature_action=Y` route) called `$feature->$action()` directly with no permission check, and `views/index.php`'s subpage dispatch called `$f->$subpage()` directly for any explicit `magrathea_feature_subpage` value, also unchecked. Only the default subpage path (`AdminFeature::GetPage()`) enforced `HasPermission()`. Both bypasses are now closed: `CheckFeature()` and the explicit-subpage branch of `views/index.php` call `HasPermission($action)`/`HasPermission($subpage)` before dispatching, rejecting via the same `AdminManager::PermissionDenied()` used everywhere else. The default (`GetPage()`) path is unchanged — it already enforced this itself
	- **note:** this is a behavior change only for a feature that has overridden `HasPermission()` with logic that assumes it is only ever called from the default subpage path. `AdminFeature::HasPermission()`'s base implementation returns `true` unconditionally, and the only in-repo override (`AdminFeatureUserLog`) also always returns `true`, so this is a no-op for any feature that hasn't written a custom override. As with the 2.3.0 CSRF enforcement, this ships immediately with no opt-out or staged rollout

### 2.3.0
2026-08
	- **new:** `AdminCsrf` class (`src/Admin/AdminCsrf.php`) — CSRF token management for the admin panel: `GetToken()` (get-or-create), `Regenerate()`, `Validate()`. The token is seeded on every admin request (`Start::Initialize()`) and rotated on login (`AdminUsers::Login()`)
	- **new:** the admin panel now enforces CSRF protection on every authenticated POST request, from this release, with no opt-out. `Start::Load()` validates a `magrathea_csrf_token` POST field (falling back to an `X-Magrathea-CSRF-Token` header) before dispatching to `CheckApi()`/`CheckFeature()`/`views/index.php`; a missing or invalid token is rejected via `AdminManager::PermissionDenied()` and logged to the `csrf` log file. The token is emitted automatically by the shared `views/elements/form.php` template (used by `AdminForm::Print()`) and attached automatically to every POST made through the shared `ajax()` function (`callAction()`/`callApi()`/`callFeature()`) — no per-call-site changes needed for admin code already using those helpers. GET requests are deliberately exempt
	- **fix:** `views/actions/object-remove.php` (object deletion, triggered by `deleteObject()` in `object-creation.js`) moved from a GET-based delete to POST — a GET-based mutation can never be safely protected by an embedded token, since URLs leak via browser history, server/proxy logs, and `Referer` headers
	- **note:** this changes behavior for any consumer with raw admin forms or custom AJAX calls outside the two centralized paths above (`AdminForm`/`ajax()`) — those specific actions will start failing on upgrade with "Permission denied!" until updated to send the token. There is no `admin_csrf_mode` config and no staged rollout; enforcement is immediate
	- **new:** `MagratheaModel::LoadObjectFromTableRow()` gained opt-in typed hydration — set `protected $strictTypes = true;` on a model (or its Base class) and DB row values are cast to their declared `$dbValues` PHP type (`int`/`boolean`/`float`) instead of staying the raw string every DB driver returns. `null` is always left as `null`; `string`/`text`/`uuid`/`date`/`datetime` fields are untouched. A value that can't be cast (e.g. schema drift — a non-numeric string in an `int` column) throws `MagratheaModelException` naming the field and value. Opt-in via `$strictTypes`, off by default — zero behavior change for existing models
	- **change:** `MagratheaApiControl::GetCookieName()` converted from a method to a plain property, `protected ?string $cookieName = null;`, matching the shape of its siblings `$jwtEncodeType`/`$forceSecureCookie` — it never had any logic beyond returning a static value. Projects opting into cookie auth now override `$cookieName` instead of overriding the method; no project on record overrides the old method, so this ships as a straight rename with no compatibility shim

### 2.2.4
2026-08
	- **new:** `MagratheaApiControl::GetTokenInfo()` gained a cookie-based fallback, tried after `Bearer`/`Basic`, so a session can be recognized across subdomains via a cookie instead of a header. Opt-in only: the new `GetCookieName()` hook returns `null` by default (no behavior change for any existing project), and must be overridden in a project's own `ApiControl` subclass to enable it — same convention as `GetSecret()`
	- **new:** `MagratheaApiControl::SetAuthCookie()` / `ClearAuthCookie()` — issues/clears the session cookie, deriving its expiry from the JWT's own `exp` claim. `SetAuthCookie()` accepts a `$domain` (e.g. `.example.com` to share a session across subdomains), and defaults to `HttpOnly` + `SameSite=Lax`. Because `HttpOnly` cookies can't be cleared client-side, logout now needs a real server round-trip through `ClearAuthCookie()`
	- **new:** `MagratheaPHP::IsDev()` — read-accessor for whether the project was started with `->Dev()`. Used by `SetAuthCookie()`/`ClearAuthCookie()` to only mark the cookie `Secure` outside of dev, since a `Secure` cookie is never sent by the browser over plain `http://localhost`

### 2.2.3
2026-08
	- **fix:** `AdminManager::PrintLogo()` rendered SVG logos with literal `<?=$logoSize?>` text in the `width`/`height` attributes instead of the actual size — `file_get_contents()` reads the default `views/logo.svg` template as raw bytes without evaluating its PHP placeholder. Now does a plain string substitution on the SVG content instead of executing it, fixing the default template while staying inert for custom SVG logos

### 2.2.2
2026-07
	- **new:** `"date"` field type for models — SQL `date` columns are now detected as `"date"` (instead of collapsing into `"datetime"`) when generating objects; on `Insert()`/`Update()` the value is normalized to `Y-m-d` before binding, accepting `"YYYY-MM-DD"`, `"YYYY-MM-DD HH:MM:SS"` and ISO-8601 (`"YYYY-MM-DDTHH:MM:SS.sssZ"`), and throwing a `MagratheaModelException` if the value is not a parseable date. Note: the ISO-8601 date part is taken as written — no timezone conversion is applied
	- **backward compatibility:** existing generated Base files and `magrathea_objects.conf` entries that say `"datetime"` for SQL `date` columns keep working exactly as before; the new type only applies once a project regenerates its objects. Admin auto-CRUD renders `"date"` fields as `<input type="date">`
	- **fix:** `MagratheaApi::Json()` sent a malformed `Status: Unauthorized` header for 401 responses (missing the leading `401`), which Caddy/PHP-FPM couldn't parse — causing a bare 502 instead of the real 401 response
	- **fix:** `MagratheaApi::Json()` threw an undefined-array-key warning for any HTTP status code outside `200/400/401/422/500` (e.g. `403`, `404`, `409`) — under `display_errors=On` this leaked an HTML warning into the JSON response body and broke the subsequent `header()` call. Replaced the partial status map with a full reason-phrase table plus an `'Unknown Status'` fallback for unlisted codes

### 2.2.1
2026-07
	- **new:** `MagratheaApi::HealthCheck()` gained a `$checkDatabase` param — when `true`, the `GET /health-check` response also includes a `database` field (`"ok"`/`"fail"`) reflecting DB connectivity
	- **fix:** `MagratheaPHP::AppVersion()` now strips trailing line breaks from the `version` file, and returns `"???"` instead of `false` if the file can't be read
	- **new:** `OpenApiAdmin` admin feature — renders a Swagger UI page for a given OpenAPI/Swagger file URL, add via `AddFeature(new OpenApiAdmin("swagger.yaml"))`
	- **new:** `PATCH` for APIs

### 2.2.0
2026-07
	- **new:** `MagratheaPagination` object — return it from an API controller and `MagratheaApi::ReturnSuccess()` automatically builds a paginated JSON envelope (`{success, data, page, count, has_more, total?}`)
	- **new:** `MagratheaModelControl::GetPagination()` — builds a `MagratheaPagination` from a `Query`; by default fetches `limit + 1` rows to compute `has_more` without an extra `COUNT(*)` query
	- **improvement:** `MagratheaModelControl::RunPagination()` gained a `$withTotal` param (default `true`, backward compatible) to skip the `COUNT(*)` query when the total isn't needed

### 2.1.31
2026-07
	- **fix:** `MagratheaApiControl::GetAuthorizationToken()` no longer throws a PHP warning when the `Authorization` header is missing, which in dev could corrupt the response status/body
	- **fix:** __URGENT FIX__: setting charset to `utf8mb4` to accept emojis in the database (`Guia.LOL` urgent change)

### 2.1.30
2026-07
	- **new:** native `"uuid"` field type for models — auto-generates a UUIDv7 on `Insert()` when the field is declared in `$dbValues` and left unset (`Uuid::V7()` helper added)

### 2.1.27
2026-06
	- **fix:** fix error on ApiExplorer Admin

### 2.1.26
2026-06
	- **fix:** fix bug from 2.1.25 on autoloader only for internal classes
	- **improvement:** cleaning code comments
	- **improvement:** Adding Magrathea Version to bootstrap
	- **improvement:** fixing admin logo

### 2.1.25
2026-04
	- **improvement:** API admin now show status and codes
	- **fix:** returning correct status codes on Magrathea API
	- **new:** Debugger mode: ANALYSIS
	- **new:** App Namespaces for `MagratheaPHP`
	- **fix:** fix autoloader only for internal classes

### 2.1.24
2026-02
	- **improvement:** Authentication failing response improved
	- **fix:** SMTP Mail fix
	- **fix:** Database query can run multiplei queries wonderfully

### 2.1.23
2025-12
	- **improvement:** function `Count(Query $q)` in `MagratheaModelControl` for counting rows in a query.
	- **new:** sample for caddy files
	- **fix:** updated all the enums of the code for a better handling

### 2.1.21
2025-10
	- **fix:** fixing `AppConfigFeatureAdmin`

### 2.1.20
2025-10
	- **improvement:** improving MagratheaApi function and documentation
	- **improvement:** improving debugging for unknown errors

### 2.1.19
2025-10
	- **improvement:** improving functions for Magrathea Admin
	- **improvement:** improving CORS
	- **improvement:** improving MagratheaApi function and documentation

### 2.1.18
2025-09-30
	- **new:** config now getting environment variables when starting with `$=`
	- **improvement:** PHP 8.4: dealing with some deprecations...

### 2.1.17
2025-06-18
	- **fix:** Magrathea API debugger fixing closure functions

### 2.1.16
2025-05-19
	- **fix:** Create new Admin User with md5 password
	- **fix:** Update Admin User with md5 password

### 2.1.13
2025-02-11
	- **fix:** ConfigApp saving

### 2.1.11
2025-01-01
	- **improvement:** admin improvements
	- **improvement:** improving insert query generator

### 2.1.9
2024-12-11
	- **improvement:** `GetAppRoot` function on `MagratheaPHP`

### 2.1.8
2024-12-03
	- **fix:** deleting filed cache fixed

### 2.1.7
2024-12-02
post PNC Update
	- **fix:** deleting cache of settings when updating settings
	- **fix:** changing `MagratheaCache->DeleteFile` with new parameter `$addExtension`, default to `true`
	- **fix:** Calling Admin `Initialize` on load
	- **change:** changelog moved to inside `src` for easier deploy
	- **improvement:** log cache delete 
	- **new:** `LogLastError` function on `Logger`
	- **new:** reloading cache button
	- **new:** function `MinVersion` on `MagratheaPHP`
	- **new:** `CacheClearPattern` function on `MagratheaApiControl`

### 2.1.5
by Paulo Martins
dromedario.etc update
	- fix Admin Logo
	- getting put data on ApiControl
	- fixing update from basic api crud
	- fix Join Object 
	- improved `ToArray` model function
	- `CacheClear` function on `MagratheaApiControl`
	- automatic cache on CRUD requests
	- cache fix
	- delete log file from admin

### 2.1.3
by Paulo Martins
	- delete objects from magrathea_objects.conf
	- API fallback
	- move authentication endpoints to parent ApiAuthentication class
