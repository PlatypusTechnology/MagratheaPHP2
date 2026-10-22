# Plan: MagratheaPHP2 2.4.0

Target version: **2.4.0**. Base: branch `version/2.4.0`, `src/version` = `2.3.5`.
Line numbers refer to the source as of commit `5746fa8`.

---

## Implementation instructions

1. Load the `magrathea-php2` skill before writing code. Follow repo conventions: tabs, existing
   naming, PHPDoc style, `**new:**` / `**fix:**` / `**note:**` changelog lines.
2. Work **item by item, in number order** (security group first). For each item:
   1. fix the code;
   2. add/adjust tests in `src/Tests/MagratheaTests/`;
   3. run the full suite — baseline before any change: **64 tests, 164 assertions, 3 deprecations, all passing**
      (`php phpunit.phar --bootstrap src/Tests/phpUnitBootstrap.php src/Tests`);
   4. update the narrative docs in `docs/mds/` that describe the touched class (signatures are automatic);
   5. update `skills.MD` if it has a cookbook section on the area, then copy it byte-for-byte to
      `/home/paulo/.claude/skills/magrathea-php2/SKILL.md`;
   6. add the item's entry to the migration file (format in item 31);
   7. add the item's line(s) to the `### 2.4.0` section at the top of `src/changelog.md` (tab-indented
      `- **fix:** …` lines, existing format): the code is changing for real, so is its changelog;
   8. tick the item's checkbox here.
3. Don't commit, push, or run `scripts/release.sh`.
4. Environment limits: the local PHP CLI (8.4) has **no `mysqli` extension** and the suite has no
   real database. DB behavior is tested through `DatabaseSimulate` (`Database::Mock()`) and through a
   fake connection object injected via a protected factory (see item 6). Whatever can't be covered
   that way gets listed in the final report.
5. If the code contradicts this plan, stop and report it instead of improvising.

---

## Group A — Security issues (fix first)

### 1. Bootstrap setup wizard is unauthenticated, and its `action` parameter can include any file
- [x] done

**Error.** `Bootstrap\Start::Load()` (`src/Bootstrap/Start.php:32`) renders the setup wizard with no
authentication at all. Its AJAX actions include:
- `views/actions/run-query.php`, which runs any SQL from `$_REQUEST["q"]`;
- `views/actions/user-reset.php`, which sets a new password for any admin `id`;
- `views/actions/save-config.php`, which rewrites the config file.

On top of that, `views/index.php:3-5` does `include("actions/".$_GET["action"].".php")`, so
`?action=../../…` includes any `.php` file the process can read. The wizard is only reachable if an app
has an entry file that calls `Bootstrap\Start::Load()`, but nothing stops that file from staying deployed.

**Solution.**
- Whitelist `action`: allow only the basenames that exist in `views/actions/` (a fixed array), and
  return 404 for anything else.
- Gate `Load()` (it includes `views/index.php`, which dispatches every step and action, so one check
  covers all of them) on a single rule: **allowed only when the active environment's section has
  `bootstrap_unlocked = true`** — read with `Config::Get("bootstrap_unlocked")`, truthy per
  `parse_ini_file()` (`"1"`). Write access to the config file proves ownership. This also keeps
  `user-reset` usable to recover a locked-out admin.
  - **Per environment, not `[general]`:** the same file often carries `[dev]` and `[production]`, so a
    `[general]` flag turned on for dev would stay on in production.
  - **No first-run exception and no DB check.** The DB can't tell "first run" from "installed but
    down": the wizard never creates admin users (`CreateFirstUser()`'s only caller is
    `Admin/views/setup.php`), and on first run DB errors are expected (step 4 is where people fix
    their credentials through `save-config`). Treating a DB error as "allowed" would unlock
    `run-query`/`save-config` during an outage; treating it as "locked" would trap a first-time user
    with a typo in their DB password. The flag alone decides; a missing flag means locked.
  - The wizard can't unlock itself: `save-config` writes into the env section, but the gate runs
    before any action.
  - otherwise, show a short "Bootstrap is locked" page explaining how to unlock it (the key, the
    active environment's section name, the config file path), and reminding the user to remove the
    flag outside development.
- `src/Bootstrap/docs/magrathea.conf.sample`: add `bootstrap_unlocked = true` to `[dev]` only, nothing
  in `[production]`. New projects work in dev out of the box; production is locked unless someone
  deliberately unlocks it.
- Known, accepted gap: with **no config file at all**, `PreLoad()` copies the sample, which comes up
  as `dev` + unlocked. In that state there are no real DB credentials, so `run-query` has nothing to
  reach. Document it.
- Tests: locked without the key, with `false`/`""`, and with the key only in a *different*
  environment's section or in `[general]`; unlocked with `true` in the active section; an unknown
  `action` is a 404 and never reaches `include`.

**Impact on a 2.3.5 project.**
- Projects that don't expose the wizard: none.
- **Every project that uses the wizard**, dev included: it's locked after upgrading (their config has
  no flag). Add `bootstrap_unlocked = true` to the environment section where they use it — normally
  `[dev]` — and never to production except temporarily.
- Custom code that links to a non-existent `action` name: 404 instead of a PHP include warning.

### 2. Admin first-run setup can be reached on an installed system
- [x] done

**Error.** `Admin\Start::Load()` (`src/Admin/Start.php:99-116`) shows `views/setup.php` whenever
`IsStarted()` is false. That form creates a **super admin** for any visitor. `IsStarted()`
(`:141-154`) returns false when:
- the `admin_install_date` config row is missing, even if admin users exist; or
- the DB throws (e.g. a temporary outage) — it catches the exception and returns false.

**Solution.**
- Setup is allowed **only when the user count is zero**.
- A DB error shows an error page and never the setup form.
- `setup.php`'s POST handler re-checks `HasUsers() === false` right before `CreateFirstUser()`, so a
  form loaded earlier can't be replayed.
- If users exist but `admin_install_date` is missing, write the missing row (that's what
  `SetBasicConfigs()` does) and go to login.

**Impact on a 2.3.5 project.** No code changes needed. An install with users but no
`admin_install_date` row now gets the login page instead of the setup form.

### 3. A failed API authorization still runs the endpoint in raw mode
- [x] done

**Error.** `MagratheaApi::ExecuteUrl()` (`src/MagratheaApi.php:466-469`) calls
`$this->ReturnError(..., 401)` without `return`.
- Over HTTP, `Json()` calls `die`, which hides the bug.
- With `returnRaw` (`Run(true)`, `SetRaw()`, `ExecuteUrl()` from tests or tools), `ReturnError()`
  returns and the protected action runs anyway.
- It's reachable with the stock auth: `MagratheaApiAuth::IsLogged()` returns `false` (it doesn't throw)
  when there's no token.

**Solution.** `return $this->ReturnError(...)`. Add a test: an endpoint protected by an auth method
that returns false, run in raw mode, must return the 401 payload and must not call the action.

**Impact on a 2.3.5 project.**
- HTTP behavior: none.
- Raw-mode callers that hit a protected endpoint without valid auth (usually tests) now get the 401
  error array instead of the action's data. Tests that relied on the bug need to authenticate.

### 4. DEV/ANALYSIS stack traces leak call arguments, including credentials
- [x] done

**Error.** `ReturnApiException()` (`:563-565`) and `ReturnError()` (`:600-602`) put `debug_backtrace()`
into the JSON response when the debugger is DEV or ANALYSIS. That includes every frame's `args`:
config arrays, DB connection details, passwords passed to login methods, JWT keys.

**Solution.** Use `debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)`. Frames keep
`file`/`line`/`function`/`class`/`type` and lose only `args`.

**Impact on a 2.3.5 project.** The `stacktrace` entries no longer have `args`. Only matters to a tool
that parses them, which is unlikely. DEV/ANALYSIS only.

### 5. The admin File Editor is enabled by default
- [x] done

**Error.** `Admin::SetFeatures()` (`src/Admin/Admin.php:138-143`) always loads `AdminFeatureFileEditor`,
which lets any logged-in admin user edit server files (`HasEditPermission()` only checks that someone
is logged in). A leaked admin password becomes code execution.

**Solution.** Remove `LoadFileEditor()` from the default `SetFeatures()`. `LoadFileEditor()` stays
`protected` and becomes the documented opt-in: an app's `Admin` subclass calls
`$this->LoadFileEditor()` (or `AddFeature(new AdminFeatureFileEditor())`) in its own `SetFeatures()`.
`AdminUrls::FileEditor`-style links (`AdminUrls.php:87`) and the menu code at `Admin.php:280` already
check whether the feature is loaded — verify each one handles its absence.

**Impact on a 2.3.5 project.**
- Projects that override `SetFeatures()` without calling the parent: none.
- Projects using the default feature set: the File Editor disappears from the admin menu. To keep it,
  add the opt-in call (documented in the migration file).

### 5b. `Query::Clean()` doesn't escape backslashes
- [x] done

**Error.** `Query::Clean()` (`src/DB/Query.php:97`) escapes `'` and `"` but not `\`. Input `\'` comes out as
`\\'`: the backslash escapes itself and the quote closes the string, so it doesn't prevent SQL injection.
Nothing in `src/` calls it, but `query-builder.md`, `orm-control.md`, `docs/data/examples.php` and
`skills.MD` told apps to use it on user input. (Found while working on item 20; not in the original plan.)

**Solution.** `Clean()` returns `Database::Instance()->Escape($query)` and is marked `@deprecated`
(PHPDoc only, no runtime notice). The docs point to `Escape()` instead.

**Impact on a 2.3.5 project.**
- Every caller becomes injection-safe with no code change.
- `Clean()` now needs a configured database (it opens the connection on first use) and also escapes NUL,
  `\n`, `\r` and Ctrl-Z, as `real_escape_string` does. Code misusing it outside SQL sees different output.

### 5c. Admin Cache feature: arbitrary file read/write/delete, GET purge, stored XSS
- [x] done

**Error** (found after item 28). `AdminFeatureCache` (a default feature) joined `$_POST["file"]` to the
cache path unchecked: `Save` writes any file (`file=../app/x.php` → code execution for any logged-in
admin, the risk item 5 removed for the File Editor), `View`/`Edit` read any file, `Remove`
(`MagratheaCache::DeleteFile($file, false)`) deletes any file. `ClearCache` ran on a GET, and the admin
CSRF check only covers POST. `view.php`/`edit.php`/`files.php` printed cache content and file names raw
(cached API JSON with user-submitted HTML → stored XSS in the admin; file names come from route params).

**Solution (Paulo, 2026-10-21: validate in the admin feature, `MagratheaCache` unchanged).** Private
`GetPostedCacheFile()`: a plain name (`basename($file) === $file`, no `\`, NUL, `.`, `..`) of an existing
regular file whose `realpath()` is directly in the cache folder (symlinks out are rejected), else
`MagratheaException("invalid file")`. `ClearCache` → 405 unless POST; `scripts.js` posts it. Views
escape content and names (`htmlspecialchars`, `json_encode` for the `onclick` arguments). Tests:
`adminCacheFeatureTest.php`.
- Not covered by tests: the buttons' `onclick` escaping (those blocks use `<?` short tags, which the
  CLI doesn't run).

**Impact on a 2.3.5 project.** None for the admin UI. A script purging the cache with a GET to the
feature action gets 405.

### 5d. Admin Tests page: shell command injection and arbitrary PHP execution
- [x] done

**Error** (found during item 29). The admin's Tests page (`views/actions/test-run.php`, reached by any
logged-in admin) passed `$_POST["folder"]`, `["file"]` and `["fn"]` to `Tests\TestsManager::RunFolder()`/
`RunFile()`/`RunFunction()`, which concatenated them into an `exec()`'d command unescaped: `folder=x; any command`
ran shell commands. Even escaped, any PHP file on the server could be handed to PHPUnit, which runs it.
`test-view-folder.php`/`test-view-methods.php` listed any directory and read any file (`GetTestsFromFolder()`,
`GetMethodsFromTest()`). Same risk class as items 5 and 5c.

**Solution (Paulo, 2026-10-21: escape and restrict to the registered folders).** Private
`GetPathInTestFolders()`: `realpath()` of the path must be a registered test folder (`AddTest()`,
`AddMagrathaTests()`) or inside one (so `../` and symlinks out are rejected), else
`MagratheaException("invalid test path")`. `RunFolder()`/`GetTestsFromFolder()` need a directory;
`RunFile()`/`RunFunction()`/`GetMethodsFromTest()` a `*Test.php` file; `RunFunction()`'s name must match `\w+`
(`"invalid test function"`). Every shell argument (paths, function, phar, bootstrap, cache file) goes through
`escapeshellarg()`. New protected `Execute()` runs the command (the tests override it). The three action views
catch the exception and show it, escape PHPUnit's output and file names, and build `onclick` arguments with
`htmlspecialchars(json_encode())` as 5c does. Tests: `testsManagerTest.php`.
- Also fixed here: `RunAllTests()` was empty (runs every registered folder now); `AddTest()` without `$name`
  read an undefined `$test` (uses the first folder's name now).
- Not covered by tests: the views' `onclick` escaping inside `form-button.php` (`<?` short tags, as in 5c);
  checked by rendering the views by hand with a `X'"><img src=x>Test.php` file and a hostile `container`.
- Found here, fixed afterwards (Paulo, 2026-10-22: fix and deprecate): `MagratheaPHP::LoadVendor()` (called by
  `TestsManager::Initialize()`) required `src/../../../autoload.php`, which only exists when the framework is
  installed under `vendor/` (a standalone checkout got a fatal `require`). It can never do anything useful (the
  class can't load before the autoloader), so: it looks in `src/../vendor/` then `src/../../../` (as
  `phpUnitBootstrap.php`), `require_once`, `MagratheaException` if neither exists, `@deprecated` (PHPDoc only;
  removal in `magrathea-3.0.md`). `TestsManager::Initialize()` is gone, and so is the test's empty override.
  Docs bootstrap examples no longer call it. Test: `autoloaderTest::testLoadVendorFindsAutoload` (in a
  subprocess: the old fatal would end the suite).
- Also fixed then (Paulo, 2026-10-22): `Admin\Install::GetAdminCode()` generated `require "../vendor/autoload.php";`
  (relative to the current folder); now `require __DIR__."/../vendor/autoload.php";`. Existing generated files
  aren't rewritten. Test: `adminInstallTest.php` (runs the generated file from another folder against a stub
  `vendor/autoload.php`).

**Impact on a 2.3.5 project.** None for the admin UI. Code calling the run/listing methods with paths
outside the registered folders gets `MagratheaException`.

### 5e. Admin page/action routing includes any PHP file
- [x] done

**Error** (found during 5d). `src/Admin/views/index.php` did `include("actions/".$_GET["magrathea_subpage"].".php")`
and `include("pages/".$_GET["magrathea_page"].".php")`: with `../`, any `.php` file the web server can read was
included, for any logged-in admin, or through a link opened by one (the CSRF check covers POST only). Reachable
targets include the Bootstrap wizard's actions (`../../../Bootstrap/views/actions/save-config`), past item 1's
lock.

**Solution (Paulo, 2026-10-21: plain names of existing files, else 404).** New public
`Admin\Start::GetViewFile(string $folder, $name): ?string`: `$folder` must be `"pages"` or `"actions"`; `$name` a
plain name (a string, `basename($name) === $name`, no `\`, NUL, `.`, `..`) of an existing file in
`views/<folder>/`, else `null`. `index.php` resolves both before any output: a bad action dies with 404
"action not found" (as Bootstrap's `GetActionFile()`); a bad page sets 404 and shows a "Page not found" alert in
the layout. Tests: `adminStartTest.php` (every existing page/action resolves; traversal, sub-folders, other
folders, non-strings are rejected; `index.php` has no concatenated `include`). Driven by hand in a subprocess:
`../../Start`, `../login`, `nope` → 404; `test-view-methods` still runs.
- Not covered by tests: rendering a bad `magrathea_page` in the full layout (needs a logged-in admin and a DB).
- Also fixed (Paulo, 2026-10-21; broken in 2.3.5 too): links to pages that don't exist. `pages/structure.php`'s
  "Cache" link used `GetPageUrl("cache")`: it now links to the loaded `AdminFeatureCache` (found by class, since an
  app can change its id), or shows no link without it. `AdminMenu`'s `app-conf` (`config-data`) and
  `tools-file-editor` (`file-editor`) items (`GetItem()`, not used by the default menu) point to
  `AdminFeatureAppConfig`/`AdminFeatureFileEditor` through the new `AdminMenu::FeatureItem($title, $featureId)`.
  Tests: `adminMenuTest.php` (every menu page and every `GetPageUrl("…")` literal in `src/Admin` resolves with
  `GetViewFile()`; it fails on the old code).

**Impact on a 2.3.5 project.** None for the admin UI. A link to a page/action name that isn't a framework file
gets 404 (it was an empty page with a PHP warning).

---

## Group B — Improvements

### 6. `Database::Transaction(callable $fn)`
- [x] done

**Error.** The only transaction API is `QueryTransaction(array $sql)`, which takes a fixed list of SQL
strings. You can't wrap model writes (`Save`/`Insert`/`Update`/`Delete`), `GetWhere`, or
`QueryRow("SELECT … FOR UPDATE")` in one transaction. With today's per-query connections (item 20),
a transaction couldn't even span two calls: each call opens and closes its own `\mysqli`.

**Solution.** Implemented **after item 20** (shared connection), which it depends on.
- Add `public function Transaction(callable $fn): mixed` to `Database`:
  - depth 0: open the connection if needed, `begin_transaction()`, run `$fn()`, `commit()`, return
    `$fn`'s result;
  - any `\Throwable` → rollback, then rethrow the **original** exception unchanged. The rollback
    itself can fail (e.g. the connection died: item 20's `MagratheaDBConnectionLostException`, after
    which there's nothing to roll back on — MySQL already discarded the transaction with the session).
    Swallow any rollback error and still rethrow the original; never let a rollback error replace it;
  - **nested calls run inline** (depth counter, no savepoints). Only the outermost call begins,
    commits or rolls back. An inner throw marks the transaction as failed;
  - if outer code catches an inner exception and returns normally, the outermost call still rolls
    back and throws `MagratheaDBException("transaction marked as failed by an inner call…")`;
  - failed `commit()` (false return or `mysqli_sql_exception`) → rollback and throw;
  - depth and failed flag are always reset in `finally`.
- **`QueryTransaction()` guard:** if it's called while `Transaction()` is open, throw
  `MagratheaDBException` with a clear message. Otherwise its behavior is unchanged (but see item 7).
- **`DatabaseSimulate::Transaction()`:** same nesting, rethrow and failed-flag semantics, no real
  transaction. It logs `begin`/`commit`/`rollback` through `Simulate()` so tests can assert the sequence.
- **Testability:** add `protected function CreateConnection(): \mysqli`, which `OpenConnectionPlease()`
  uses. Tests subclass `Database` and return a fake connection object that records
  `begin_transaction`/`commit`/`rollback`/`query`/`prepare` calls. That proves every call runs on the
  same object. The tests must not touch `\mysqli` directly, since the CLI here has no mysqli extension.
- **Verify and report:** every `Query*`, `PrepareAndExecute`, `Escape` and model method goes through the
  shared connection.
  - **Verified (2026-10-19):** every DB path in `src/` (models, controls, query builder, admin, bootstrap,
    API health check) uses `Database::Instance()`; `CreateConnection()` is the only place a `\mysqli` is
    created. Covered by `databaseConnectionTest::testTransactionCommitsAndReturnsResult` (model `Insert()`,
    control `GetWhere()`, `QueryRow(… FOR UPDATE)` on one connection).
  - **Decided not to guard (Paulo, 2026-10-19):** a callback that catches a lost-connection exception
    and carries on makes the next query reconnect in autocommit mode, outside the transaction, and
    `Transaction()` doesn't notice. Rare (needs a drop mid-callback *and* a swallowing catch; a stale
    connection fails on `START TRANSACTION`, before the callback), so it's documented instead of
    refusing to reconnect while a transaction is open.

**Impact on a 2.3.5 project.** Additive: a new method. The only behavior change is that
`QueryTransaction()` throws when called inside `Transaction()`, which no 2.3.5 code can do.

Things to document (not code changes):
- DDL (`CREATE`/`ALTER`/`TRUNCATE`) commits implicitly in MySQL, even inside `Transaction()`.
- Ending the request with `die` inside the callback (`Json()`, `ReturnError()`, a cache hit's
  `ShowJson()`) leaves the transaction uncommitted, and MySQL rolls it back when the connection closes.
- Writes made by framework logging into the DB (`AdminManager::Log()`) inside the callback are part of
  the transaction and roll back with it.

### 7. `QueryTransaction()` doesn't roll back and leaves the connection in a bad state
- [x] done

**Error.** `Database::QueryTransaction()` (`src/DB/Database.php:380-401`):
- only checks `mysqli->error` after the loop, so it misses every error except the last query's;
- on error it calls `ErrorHandle()` without throwing, returns `false`, and never rolls back;
- it leaves `autocommit(false)` on and the connection open.

With PHP 8.1+ default error reporting, a failing query throws mid-loop and skips the cleanup too.

**Solution.**
- Check each query's result.
- On the first failure: rollback, restore autocommit, and return `false`. **Keep returning `false`,
  not throwing**, to preserve the 2.3.5 contract.
- Wrap the loop in try/catch for `mysqli_sql_exception`: rollback, restore autocommit, return `false`.
  - **Changed during implementation (Paulo, 2026-10-19): rethrow instead of `false`.** The "2.3.5
    contract" only held with mysqli reporting off: with PHP 8.1+'s default, 2.3.5's
    `catch(\Exception) { throw $ex; }` passed `mysqli_sql_exception` up, so default installs saw an
    exception. Turning it into `false` would silently swallow errors on every default install. So:
    rollback, restore autocommit, rethrow the original exception. `false` only for errors mysqli
    *returns*. `MagratheaDBConnectionLostException` (item 20) passes through with no rollback attempt
    (the connection is already gone). A failed `commit()` is handled the same way. If the rollback or
    the autocommit restore fails, that error is swallowed and the connection is dropped.
- Return `true` on success. 2.3.5 returned `null`, which is falsy, so callers checking `!== false` are
  unaffected — but `if(QueryTransaction(...))` starts being true on success. Note this in the migration file.

**Impact on a 2.3.5 project.**
- Partial writes from a failed `QueryTransaction()` are rolled back instead of left uncommitted.
- Success returns `true` instead of `null`. Only code that tested for `null` explicitly would notice.

### 8. Atomic cache file writes
- [x] done

**Error.** `MagratheaCache::SaveFile()` (`src/MagratheaCache.php:124-130`) does `fopen($path,'w')`,
which truncates, then `fwrite()`. A concurrent reader (`LookForFile()` → `file_get_contents`) can see an
empty or partial file and serve broken JSON. If `fopen()` fails, `fwrite(false, …)` is a `TypeError`
on PHP 8.

**Solution.**
- Write to `<target>.<pid>.<random>.tmp` in the same directory, opened with `fopen(..., 'x')`. Don't
  use `tempnam()`: it creates files as 0600, which would break setups where the web server and CLI run
  as different users.
- Then `fflush` + `fclose` + `rename()` over the target (atomic on the same filesystem).
  - **Changed during implementation (Paulo, 2026-10-19):** no `fflush` (`fclose` flushes; its result is
    checked). Orphan `.tmp` files from killed processes are removed by `RemoveAllCache()` (its `*` glob
    already matches them; now tested and documented). No return type added to `SaveFile()`, so subclasses
    overriding it without one don't fatal.
  - **Added during implementation (Paulo, 2026-10-19):** two cache bugs found while rewriting
    `docs/mds/utilities/cache.md` (which described a config key and a `Cache()` API that don't exist; rewritten
    from the source). (a) Raw mode died on a cache hit or save (`LookForFile()`/`HandleApiCache()` call `die`
    whatever `returnRaw` is): new `MagratheaCache::Bypass(bool)`, set by `ExecuteUrl()` from `returnRaw`, so
    raw calls bypass the cache (chosen over "return hits as data", which needs exception control flow that a
    controller's `catch(\Exception)` would swallow). (b) `Clear()` overwrote the current cache key, so
    `Cache("a")` + `CacheClear("b")` saved the response as `b.json`; it now uses a local key.
- On any failure, unlink the temp file and return `false`. On success, return the byte count, as 2.3.5 does.
- `SaveFile()` is the only truncate-then-write path for cache files. Logger appends, config
  saves aren't cache files, and `HandleApiCache()` goes through `SaveFile()`.
- Test: fork a reader with `pcntl_fork` (available here) that reads the target in a loop while the
  parent rewrites a large payload many times. The reader must never see a length other than the full
  payload's. Skip the test if `pcntl` isn't available. Also test: no `.tmp` files left behind, and an
  unwritable directory returns `false` with the target unchanged.

**Impact on a 2.3.5 project.** None, except that `SaveFile()` on an unwritable cache dir now returns
`false` instead of a PHP warning followed by a `TypeError`.

### 9. Quiet cache logging
- [x] done

**Error.** `MagratheaApiControl::Cache()` (`src/MagratheaApiControl.php:429-435`) writes
"adding cache for …" to the Logger on every call. That includes cache hits, because the log line runs
before `LookForFile()` serves the hit and dies. It floods logs on frequently polled routes.

**Solution.** Log only when `MagratheaPHP::Instance()->IsDev()`. No other cache logging changes: none of it runs
per request. `DeleteFile()` logs, but it's only called from the admin App Configuration save.
`Clear()`, `RemovePattern()` and `HandleApiCache()` don't log.

**Impact on a 2.3.5 project.** These lines disappear from logs outside DEV, including `->Debug()` mode
(`IsDev()` is DEV only). Nothing to change in code.

### 10. API errors are returned as HTTP 200
- [x] done

**Error.**
- `ReturnError()` defaults `$status=200` (`src/MagratheaApi.php:590`).
- `Return404()` (`:546`) doesn't pass a status.
- Exceptions with a code (`:474`, `:489`) call `ReturnError($code, …)` without a status.
- Code-0 exceptions go to `ReturnFail()` (`:641-659`), which sends 200 and JSON-encodes a raw
  `\Exception` as `{}`.
- `Admin\Api\AdminApi::Call()` has the same paths.

So clients get `200 {"success":false}` for 404s and server errors, and 500s lose their message.

**Solution.**
- `ReturnError($code=500, $message="", $data=null, ?int $status=null)`. When `$status` is null, derive
  it from `$code` with the same mapping as `ReturnApiException()` (item 12), defaulting to 500.
  Explicit `$status` arguments are unchanged.
- `Return404()` → 404.
- `ReturnFail()` → status derived from the exception's code (500 for code 0). The body uses
  `{"message": …, "code"?: …}` for any `\Throwable`, not just `MagratheaApiException`, so the message
  isn't lost as `{}`.
- Extract the mapping into one private method used by all three paths.
- No opt-out: real statuses are the only behavior.
- **Admin:** audit `src/Admin/views/**` and the admin JS for callers of `ajax()`/`ajaxApi()` that
  only handle failures in `.then(rs => !rs.success …)`. Non-2xx responses go to `.fail`/`reject`, so
  each of those callers must get a `.catch` that shows the same error, so the admin keeps showing
  its error messages.
- **Changed during implementation (Paulo, 2026-10-19):**
  - Admin: no per-caller `.catch`. Only `callApi()` reaches `AdminApi` (10 callers, none with a
    `.catch`, several never check `success`), so `callApi()` resolves a rejected response whose body is
    `{success: false}` and rethrows anything else: callers behave as in 2.3.5. `ApiExplorer` already
    handles rejections.
  - `ReturnFail()` with non-exception data keeps HTTP 200; only `\Throwable`s get a derived status.
  - `MagratheaApiException::SetStatus()` was dead code (stored, never read; `GetAuthorizationToken()`'s
    `SetStatus(401)` went out as 400). It's honored now when 400-599, for `ReturnApiException()` and
    `ReturnFail()`. `$status` defaults to `null`: the constructor's 500/200 defaults (from `$kill`)
    would have overridden every code mapping.
  - Item 12 done here: the extracted mapping (`HttpStatusFromCode()`) already clamps to 400-599, so
    `ReturnError()`'s new derived status never sends 1062 → 106. Numeric-string codes (PDO's
    `"23000"`) are mapped too; non-numeric → 500.
  - Docs examples throwing `MagratheaApiException("Unauthorized", 0, null, true)` (HTTP 500) now use
    code 401 (`api-controller.md`, `authentication.md`, `skills.MD`).

**Impact on a 2.3.5 project.** **The most visible change in this release.**
- Response bodies are unchanged except that `ReturnFail()` now carries the message.
- `fetch()`-based clients: no change (fetch doesn't reject on 4xx/5xx).
- axios, jQuery, Angular `HttpClient` and similar clients send these responses to their error path.
  Code that read `success:false` in the success handler must move to the error handler **before**
  upgrading. There is no switch to keep the old behavior. The migration file must give a concrete grep
  and a before/after example for axios and jQuery.
- App classes extending `MagratheaApi` that call `ReturnError()` without `$status` now send a real status.
- Responses from `MagratheaApiException` already used real statuses in 2.3.5 and don't change.

### 11. `\Throwable` isn't caught in the API dispatcher
- [x] done

**Error.** `ExecuteUrl()` (`:473`, `:485`) and `AdminApi::Call()` only catch `\Exception`. A `TypeError`
or `Error` thrown in a control escapes as a PHP fatal with an HTML body.

**Solution.** Catch `\Throwable` and route it through the same code as item 10 (500, message included).

- **Changed during implementation (Paulo, 2026-10-19):** the `\Exception` paths stay as they are; a new
  `catch(\Error)` goes to the new protected `ReturnInternalError()`. The catch logs the error with
  `Logger::LogError()` (once caught it no longer reaches PHP's error log), falling back to
  `error_log()` if the Logger throws. `LogError()` was fixed to take any `\Throwable` (an `\Error` used to
  log with an undefined `$line`); non-exceptions are `print_r`'d, which also fixes `LogLastError()`. The
  message goes to the client only in DEV (`IsDev()`, not ANALYSIS): `\Error` messages show paths and
  signatures. Otherwise: "Internal Server Error". `AdminApi` runs in LOG mode, so it always sends the generic one.

**Impact on a 2.3.5 project.** Endpoints that used to fatal now return a JSON 500. Nothing to change.

### 12. Exception code → HTTP status mapping can produce non-error statuses
- [x] done (with item 10)

**Error.** `ReturnApiException()` (`:566-575`) maps 4-digit codes `/10` and 5-digit codes `/100`. That's
deliberate (sub-codes such as 4031 → 403, guia.lol depends on it), but codes outside the scheme give
nonsense: MySQL 1062 → 106, 2001 → 200 on an error, 7000 → 700.

**Solution.** Keep the mapping. Accept the derived status only if it's between 400 and 599; otherwise
use 500. 3-digit codes outside 400–599 also become 500. This is shared with item 10.

**Impact on a 2.3.5 project.** Codes 4000–5999, 40000–59999 and 400–599 are unchanged. Codes that
mapped to 1xx/2xx/3xx/6xx+ now give 500. Only a project that deliberately threw e.g. `2001` to get
HTTP 200 would notice.

### 13. `BaseAuthorization()` parameter type doesn't match the property
- [x] done

**Error.** `BaseAuthorization(MagratheaApiControl $authClass, …)` (`:171`) stores into
`public ?MagratheaApiAuth $authClass` (`:42`). Passing a plain control, as the parameter type allows,
throws a `TypeError` at the assignment.

**Solution.** Type the parameter `MagratheaApiAuth` and fix the PHPDoc.

- **Added during implementation (Paulo, 2026-10-19):** `authentication.md` and `skills.MD` (§8) declared
  `AuthControl extends MagratheaApiControl` and passed it to `BaseAuthorization()`, which never worked.
  All their `AuthControl` examples now extend `MagratheaApiAuth`; `magrathea-api.md`'s hand-written
  heading shows the new signature.

**Impact on a 2.3.5 project.** None: passing a non-`MagratheaApiAuth` already failed. Subclass
overrides that declare `MagratheaApiControl` stay compatible (a wider parameter type is allowed).

### 14. Creating any `MagratheaException` prints HTML in DEV
- [x] done

**Error.** `MagratheaException::__construct` (`src/Exceptions/MagratheaException.php:30`) calls
`Debugger::Add($this)`. In DEV that echoes `<pre>…</pre>` (`src/Debugger.php:169-170`) and falls through
to also store it. This happens for every exception, including ones that are caught and handled. It
corrupts JSON responses in DEV, and it's the source of the `<pre>[MAGRATHEA … EXCEPTION]` noise in the
test output.

~~**Solution.** In `Debugger::Add()`'s DEV case, don't echo `\Throwable` items: store them (same as DEBUG)
and leave rendering to the uncaught-exception handler and `Debugger::Show()`.~~ Dropped: there is no
uncaught-exception handler in `src/` (nothing calls `set_exception_handler`), it would also silence explicit
`AddError()`/`DebugError()` calls, and printing exceptions in DEV is wanted.

- **Changed during implementation (Paulo, 2026-10-19):** DEV keeps printing, except inside API calls.
  - `ExecuteUrl()` wraps the call in `Debugger::StartCapture()`/`StopCapture()` (nested calls count; always
    on, ~1 µs). While capturing, DEV's `Add()`/`Info()` collect items instead of echoing them; other modes are
    unchanged and collect nothing.
  - `Json()` and `MagratheaCache::HandleApiCache()` (after saving the file) add them as `_debug` through
    `Debugger::WithCaptured()`: only to associative arrays, only if something was collected. Exceptions are
    `{type, message, code, file, data?, query?, values?}`, formatted when read (the exception adds itself
    before its code is set); the same object is collected once (`Database::ErrorHandle()` adds its exception
    twice). If `_debug` can't be JSON-encoded it becomes an error message. Cache hits and raw mode: no `_debug`.
  - "DEV never caches" was considered and rejected: it would undo item 9 and hide cache bugs until production.
  - Tests: the bootstrap uses `Debugger::NONE` (not DEBUG: tests leave the type set to whatever they used last,
    and DEBUG prints everything at shutdown). `Json()`/cache tests run in a separate PHP process (they `die`).
  - Docs: `debugger.md`'s level table and its "`Debug()` → DEV" line were wrong (`Debug()` sets
    `Debugger::DEBUG`), same in `magrathea-php.md`; fixed.
- **Added during implementation (Paulo, 2026-10-20):**
  - DEBUG (`->Debug()`) broke API JSON too: the shutdown function printed `Show()`'s HTML after the response.
    While capturing, DEBUG collects items instead of storing them; `WithCaptured()` in DEBUG puts the items
    stored before the call first. The shutdown function calls the new `Debugger::ShowAtShutdown()`, which skips
    `Show()` when `headers_list()` has a JSON `Content-Type` (CLI has no headers: it still prints there; the
    decision is tested with a headers argument).
  - `Database::ErrorHandle()` no longer adds its `MagratheaDBException` to the Debugger: it already adds
    itself, so LOG logged every query error twice (and DEV printed it twice). The mysqli exception added in
    `OpenConnectionPlease()` before its `MagratheaDBException` is a different object and stays.

**Impact on a 2.3.5 project.** DEV and DEBUG API responses get an extra `_debug` key instead of HTML before
(DEV) or after (DEBUG) the JSON. Pages and CLI print as before. DB query errors are logged once. Other modes:
nothing. No code changes needed.

### 15. `Insert()` never writes `created_at` / `updated_at`
- [x] done — **decision: keep the behavior, document it** (option 1a)

**Error (as first reported).** In `CreateInsertQuery()` (`src/MagratheaModel.php:276-293`),
`if(!isset($this->$field)) continue;` runs before the timestamp branch. On a new object both fields
are null, so they're skipped. `Set()` refuses them too (`:439`). Tables with `NOT NULL` timestamp
columns and no default fail the insert, and tables with a default use the DB clock instead of the app's.

**Why the original solution (always send `now()`) was dropped.**
- The broken case is hypothetical: a 2.3.5 project with a `NOT NULL`, no-default timestamp column
  can't insert at all. Working projects have a default, a trigger or a nullable column, and the
  admin's table generator (`ObjectManager::GetSQLTypeFromType()`) always creates
  `DEFAULT CURRENT_TIMESTAMP`.
- Always sending a value overrides the column default **and** bypasses conditional `BEFORE INSERT`
  triggers (`IF NEW.created_at IS NULL …`), silently. An unconditional trigger would still overwrite
  the value, leaving the object's in-memory timestamps wrong anyway.
- Making the DB own both clocks (`Update()` stops writing `updated_at`) is the right design but
  breaks tables without `ON UPDATE CURRENT_TIMESTAMP`: moved to `future-plans/magrathea-3.0.md`.

**What was done.**
- No code change. `Insert()` keeps leaving unset timestamps to the database; assigned ones are still
  sent as `now()`; the object still gets PHP's `now()` after the insert (it matches the row when PHP
  and MySQL agree on the timezone, item 22); `Update()` still writes `updated_at = now()`.
- New `modelTimestampsTest.php` pins that behavior, so a later "fix" has to be deliberate.
- `docs/mds/database/orm-model.md` gets a "Timestamps" section; `skills.MD` drops the false claim
  that `uuid` "behaves the same way `created_at`/`updated_at` already do" (an assigned timestamp
  is replaced with `now()`, an assigned uuid is kept) and documents the rules.

**Impact on a 2.3.5 project.** None.

### 16. Partial updates silently drop plain property assignments
- [x] done — plus (decided while implementing): `$dirtyValues` is cleared after a successful save,
  `InsertWithPk()` refreshes the snapshot too, `Update()` reads the current property instead of the
  dirty value (`:358`), `LoadObjectFromTableRow()` on input data is documented as unsupported

**Error.**
- `Update()` (`:346-350`) writes only `dirtyValues` once any `Set()` was called.
- Model fields are declared `public` (e.g. `AdminUser`, and generated models), so `$obj->name = …`
  never goes through `__set` and is never marked dirty.
- So one `Set()` followed by plain assignments writes only the `Set()` field, and loses the rest
  silently.

**Solution.**
- Take a snapshot of the field values in `LoadObjectFromTableRow()` (`:131`), the single load path
  used by the constructor, `GetById()` and the Control's builders.
  - The method has **two branches** (`$strictTypes` off / on, 2.3.0) with an early `return` in the
    first one. Take the snapshot in **both**, after `CoerceValue()` on the strict path, so it holds the
    values the object actually got. A snapshot taken only on one path silently drops strict (or
    non-strict) models back to the 2.3.5 rule.
- `Update()` writes fields whose value differs from the snapshot, plus anything set through `Set()`.
  - **"Differs" is null-aware, then string-compared:** if exactly one side is `null`, it differs;
    if both are `null`, it doesn't; otherwise compare `(string)` casts, with `bool` normalized to
    `"1"`/`"0"` first (`(string)false` is `""`, but the DB hands back `"0"`). Not `!==`: a strict model snapshotting `5` and receiving `"5"`
    from `$_POST` would write for nothing (and a non-strict one the reverse). Not `==`:
    `null == 0` and `null == ""` are true, which would hide item 17's "set back to NULL".
- **Objects that were never loaded** (no snapshot, e.g. `new Model(); $m->id = 5; …; Update()`) keep
  the exact 2.3.5 rule.
- Refresh the snapshot after a successful `Insert()`/`Update()`.
- Keep `$dirtyValues` (it's `protected`, subclasses may read it).

**Impact on a 2.3.5 project.**
- Loaded objects: assignments that 2.3.5 silently dropped are now saved. Correct, but it may surface
  as "new" writes in code that set a field without meaning to save it.
- Columns changed by another process between load and save are no longer overwritten unless this
  object changed them.
- If nothing changed, `Update()` writes only `updated_at`.

### 17. `Update()` can't clear a nullable int column
- [x] done — plus (decided while implementing): `""` in an int/boolean field is written as `NULL` on any
  object (admin CRUD posts empty int inputs as `""`; `intval("")` would have silently written `0`);
  booleans share the path (`GetDataTypeFromField()` maps both to `"integer"`); `Insert()` unchanged

**Error.** `Update()` (`:356`) skips any integer field that is `null`, so a nullable FK can never be
set back to NULL. Removing the skip alone isn't enough: `:367` turns null into `0` with `intval()`.
`$value` (`:358`) is computed and then ignored.

**Solution.** Build on item 16.
- For loaded objects, a null int is written as SQL `NULL` when it differs from the snapshot.
- For objects without a snapshot, the 2.3.5 skip stays, so sparse updates don't wipe int columns.
- Bind real `null` instead of `intval(null)`.
- Use `$value` consistently.

**Impact on a 2.3.5 project.** Code that sets an int field to null on a loaded object and saves now
writes NULL. 2.3.5 silently kept the old value. A `NOT NULL` column now raises a DB error instead of
silently not changing.

### 18. `PrepareAndExecute()` swallows execute errors when mysqli reporting is off
- [x] done — attached with `SetQueryData()`, not `SetData()`: `MagratheaException::SetData()` takes one
  argument, so `SetData($query, $args)` would have stored the query and dropped the args. Plus (decided
  with Paulo): `ErrorHandle()`/`makeValuesReferenced()` had the same `SetData()` bug, now `SetQueryData()`
  (Debugger's query-equals-data workaround removed); `DatabaseSimulate::PrepareAndExecute()`'s dead
  try/catch removed; `database.md`'s `PrepareAndExecute()` examples rewritten as writes with Magrathea types.
  Then (decided with Paulo, "18b"): every query error is a `MagratheaDBException` with MySQL's errno
  as code and `mysqli_sql_exception` as previous (`FromMysqliException()`/`MysqliCall()`), which
  reopens items 6/7's "rethrows the original `mysqli_sql_exception`" (docs and tests updated); the API
  answers DB errors with 500 whatever the errno (`HttpStatusFromException()`, and `ReturnError()` uses it
  when `$data` is an exception); 4031 (MySQL 8.0.24+ idle disconnect) added to `CONNECTION_LOST_ERRORS`
  (item 20). Not changed: `Query()`/`QueryTransaction()` return `false` on a returned error (reporting off)

**Error.** In `src/DB/Database.php:520-531` (`:737-742` after item 20), an execute error is thrown
inside the `try` and caught by `catch(MagratheaDBException)`, which does `echo "got error! "` and
returns `null`, leaving the connection open.
- The `echo` is leftover debug output: it prints into pages and API responses (before the JSON).
  Removing the branch removes it; make sure no `echo` survives in `PrepareAndExecute()` (add a test
  that the failing call prints nothing).
- With PHP 8.1+'s default `mysqli_report` (exceptions on), errors land in `catch(Exception)` instead
  and are rethrown, so default setups aren't affected.
- Apps that turn reporting off (or run PHP < 8.1) lose write errors silently. `Insert()` then sets the
  PK to `null`, the API reports success, and the response body gets a stray `got error! `.

**Solution.** Remove the swallowing branch: attach query and args to the exception with `SetData()`
and rethrow.
- **Affects item 6:** with reporting off, the swallowing branch hides a failed write inside
  `Transaction()` (it returns `null` instead of throwing), so the transaction commits whatever ran. After
  this item it throws and `Transaction()` rolls back; add a test for that.
- **Also `QueryMulti($queries, $killable = false)`** (`src/DB/Database.php`, found during item 20): with
  PHP 8.1+'s default mysqli reporting, a failing statement throws `mysqli_sql_exception` from `query()`
  before `$killable` is checked, so `false` never records the failure and keeps going. That breaks
  `Admin/Install.php`'s `ImportFile($file, false)`. Catch `mysqli_sql_exception` per statement: with
  `$killable = false`, record `success => false` with the error and continue (but let
  `MagratheaDBConnectionLostException` through: on a dead connection, the next statements can't run);
  with `true`, keep throwing. Remove the caveat this adds to `database.md`. Also add `"php": ">=8.1"` to `composer.json`'s `require`. The code already needs PHP 8.0
syntax, and 8.1 makes mysqli throw on errors by default.

**Impact on a 2.3.5 project.**
- Default PHP 8.1+ setups: none.
- Projects with mysqli reporting off: failed writes now throw `MagratheaDBException` instead of
  returning `null`. Code that checked `if(!$obj->Insert())` needs a try/catch.
- Projects on PHP 8.0: Composer won't install 2.4.0 until they upgrade PHP.

### 19. Prepared-statement types: integers are bound as strings
- [x] done — plus: `Delete()`'s PK was also bound as `"s"` (right type passed, but `"integer"` matched no
  case), so it binds `i` now too; int PKs above `PHP_INT_MAX` (`BIGINT UNSIGNED`) get clamped in
  `Update()`/`Delete()` (documented in the migration file). Real `Database` switch tested with the fake
  connection (`testPrepareAndExecuteBindTypes`), model writes through `DatabaseSimulate` (`modelBindTypesTest.php`)

**Error.** `GetDataTypeFromField()` returns `"integer"`, `"decimal"`, `"text"` and `"date"`, but
`PrepareAndExecute()`'s switch (`:492-509`) matches `"int"`/`"boolean"`/`"float"`. Everything falls
to `"s"`. MySQL coerces the values, so it works, but ints travel as strings.

**Solution.** Map `"integer"` → `"i"` in both `Database` and `DatabaseSimulate`. **Keep `decimal` as
`"s"`**: binding it as `"d"` would pass DECIMAL values through a PHP float and could change stored
values (e.g. `0.1`).
- **Also fix `Update()`'s PK binding** (`src/MagratheaModel.php:376`): it calls
  `GetDataTypeFromField($pkField)` with the field **name** (`"id"`), which matches no case, returns
  `null`, and binds the `WHERE` PK as `"s"`. Use `GetDataTypeFromField($this->dbValues[$pkField])`
  (the field's type, not its name), exactly as `Delete()` (`:385`) already does. Without this, after this item every int in an `UPDATE` binds as `i` except
  the PK.
- Test through `DatabaseSimulate`: `Update()`'s last bind type is `i` for an `int` PK and `s` for a
  `uuid` PK.
- Not here: `:367`'s dead `$type == "integer"` comparison belongs to item 17, which rewrites that line
  (done: `Update()` now `intval()`s int/boolean values, binds `null` as `null`).

**Impact on a 2.3.5 project.** None expected. Int columns receive ints instead of numeric strings.

### 20. A new connection for every query
- [x] done

**Error.** Every `Query`, `QueryAll`, `QueryRow`, `QueryOne`, `QueryMulti`, `PrepareAndExecute`,
`QueryTransaction` and `Escape` calls `OpenConnectionPlease()` (a new `\mysqli`) and
`CloseConnectionThanks()`.
- Session state (`SET`, `GET_LOCK`, temporary tables, `LAST_INSERT_ID()`) is lost between calls.
- Transactions can't span calls (needed by item 6).
- Since 2.3.4, every escaped value in the query builder opens and closes a whole connection.
- `QueryRow()` returns early on 0 rows without closing (`:343`).
- There's no way to get affected rows: `PrepareAndExecute()` returns `insert_id` or `true`.

**Solution.**
- `OpenConnectionPlease()` opens lazily and reuses an open connection, through
  `CreateConnection()` (item 6). `CloseConnectionThanks()` keeps its name and public signature, but
  only the request end actually closes: a `register_shutdown_function` plus an explicit
  `Database::Disconnect()` for CLI scripts.
- **Lost connections: no automatic retry, anywhere.** Whether a statement is safe to repeat depends
  on idempotency, which only the app knows: on errno 2013 ("lost connection *during* query") the
  server may already have run it, so a retried `INSERT`/`balance = balance - 10` would apply twice;
  and any reconnect is a new MySQL session, silently dropping `GET_LOCK`, `@vars`, temporary tables
  and `LAST_INSERT_ID()`. The framework reports, the app decides:
  - new `MagratheaDBConnectionLostException extends MagratheaDBException`
    (`src/Exceptions/MagratheaDBConnectionLostException.php`), so existing `catch(MagratheaDBException)`
    blocks keep catching it. Thrown on errno **2006** or **2013**, whether mysqli reports it as a
    `mysqli_sql_exception` or as an error return. Its code is the errno (2006 = "probably not
    executed", 2013 = "may have executed" — the app's input for deciding whether to retry), the
    original exception is `$previous`, and query/args are attached with `SetQueryData()`. Via item 12
    these codes map to HTTP 500;
  - **before throwing, drop the dead connection** (close it, ignoring errors, and null the instance's
    handle), so the next call on that instance opens a fresh one lazily. Without this, a worker that
    catches the exception and loops would hit the same dead `\mysqli` forever;
  - inside `Transaction()` the same exception propagates; item 6's rollback must survive the dead
    connection (see there);
  - `mysqli::ping()` is deprecated in PHP 8.4, so don't use it for any pre-check either.
  - Tests (fake connection via `CreateConnection()`): a 2006 and a 2013 each throw
    `MagratheaDBConnectionLostException` with the right code and query data; the statement is sent
    exactly once (no retry); the next call goes through `CreateConnection()` again and gets a new
    object; a non-connection error (e.g. 1062) stays a plain `MagratheaDBException` and keeps the
    connection.
- **Documented worker pattern** (`database.md` + `skills.MD`): call `Database::Disconnect()` at the
  start of each job iteration. One line, and it restores 2.3.5's fresh-connection-per-job behavior, so
  idle time never matters. Catching `MagratheaDBConnectionLostException` and retrying is for workers
  that know their statements are idempotent; the docs show both, with the 2006/2013 meaning.
- New `Database::LastAffectedRows(): int`, set by `PrepareAndExecute()` and `Query*`.
  `PrepareAndExecute()`'s return value is unchanged, because `Insert()` depends on it.
- **The connection belongs to each `Database` instance**, not to a global. A second database works
  through a subclass (`class ReportsDb extends Database {}` → `ReportsDb::Instance()`), since
  `Singleton` keeps one instance per class name. Each instance opens its own connection on first use.
  The end-of-request close must close **every** open instance, not just `Database::Instance()`.
  Add a test with two instances proving they never share a connection. Document the limits:
  models/controls/query builder always use `Database::Instance()`, and `Transaction()` never spans
  two connections.
- Every result must be fully read (`QueryMulti()` / `store_result()` loops), or the shared connection
  hits "Commands out of sync" on the next query.

**Impact on a 2.3.5 project.**
- Fewer connections per request.
- Session state now persists across queries in the same request (e.g. a `SET @var` stays set).
  Correct, but different.
- **Long-running CLI workers: a regression unless they adapt.** 2.3.5 opened a connection per query,
  so a worker idle past MySQL's `wait_timeout` (8h by default) never noticed. In 2.4.0 its first query
  after that idle throws `MagratheaDBConnectionLostException`. Fix: `Database::Disconnect()` at the
  start of each job iteration (or catch and retry where idempotent). The migration file gets this
  with **Applies if:** CLI scripts/daemons that loop (`grep -rn "while *(true)\|for *(;;)" app/ cli/`).
  Web requests are unaffected in practice (they don't outlive `wait_timeout`).
- Subclasses that override `OpenConnectionPlease()` (the test suite does) keep working.
- Code that relied on `CloseConnectionThanks()` actually closing mid-request (e.g. before `fork`) must
  call `Disconnect()`.
- A request that waits on something slow (an external API, a long computation) now holds its MySQL
  connection the whole time. Peak connections become roughly the number of PHP-FPM workers. That's
  fine with typical settings, but setups with a low `max_connections` should check.

### 21. The `timezone` config key: code and samples disagree
- [x] done (together with item 22)

> Note (2026-10-21): the list below was partly wrong. The sample's `[production]` already had
> `timezone`; `skills.MD:104`, `docs/data/examples.php:289` and `config.md:29` were the dead `[general]`
> copy (deleted, not renamed), and their environment sections already used `timezone` (`config.md`'s
> had none: added). Troubleshooter: `Magrathea2\TimezoneCheck::Run()` (+ DocMap page `core/timezone`),
> invalid-name check uses `timezone_identifiers_list(DateTimeZone::ALL_WITH_BC)` (PHP accepts BC names).
> All wizard checks are in the config step; the DB part reports "can't query MySQL" when unreachable.

**Error.** `MagratheaPHP::Load()` (`src/MagratheaPHP.php:184-185`) reads `Config::Get("timezone")`,
which only looks in the active environment section. But every sample and doc writes **`time_zone`**:
- `src/Bootstrap/docs/magrathea.conf.sample:3,13`
- `skills.MD:104`
- `docs/mds/core/config.md:29`
- `docs/data/examples.php:293`
- `src/Tests/MagratheaTests/test-dump/magrathea-test.conf:10,22` and `configTest.php:46`

So projects created from the sample never call `date_default_timezone_set()`, and PHP runs on
php.ini's default timezone (often UTC). The `[general]` copy in the sample is never read at all:
`Get()` doesn't look in `[general]`.

**Solution.**
- `timezone` is the only key. Rename `time_zone` → `timezone` in every file
  listed above.
- Keep it in the environment sections. Remove the `[general]` copy from the sample: `Get()` never
  reads it, so it only misleads.
- Document the key in `core/config.md`, `core/magrathea-php.md` and the `skills.MD` config section:
  where it goes, that it sets PHP's timezone, and that it drives item 22.
- **Timezone troubleshooter, in setup/admin only — never at runtime.**
  `MagratheaPHP::Load()` and the connection code don't validate anything new on every request. The
  checks live in:
  - the Admin panel's server info page (`src/Admin/views/pages/server.php`, which already prints the
    timezone);
  - the Bootstrap wizard's config step.

  The troubleshooter reports:
  - **config has `time_zone` but no `timezone`** → warning: the key is ignored, rename it (and what
    that switches on, item 22);
  - **invalid `timezone` name** (not in `timezone_identifiers_list()`) → error. It's shown, not
    thrown: at runtime, `date_default_timezone_set()` keeps today's behavior;
  - PHP's effective zone and `now()` next to MySQL's `@@global.time_zone`, `@@session.time_zone` and
    `NOW()`, with a clear "in sync / out of sync" result;
  - `db_sync_timezone` state (on, off, or off because there's no `timezone`), and whether MySQL accepted
    the named zone or the offset fallback was used (i.e. whether MySQL's timezone tables are loaded).

**Impact on a 2.3.5 project.**
- Config with `time_zone` only: nothing changes on upgrade. The key stays ignored, as it always was.
  Renaming it to `timezone` turns on **two** things at once: PHP's `now()`/`date()` move to that zone,
  and the DB timezone sync starts (item 22). The migration file must say this explicitly.
- Config already with `timezone`: the PHP side doesn't change, but item 22's sync turns on by default.

### 22. Sync MySQL's session timezone with PHP's
- [x] done (together with item 21)

**Error.** PHP's `now()`/`date()` use PHP's timezone. MySQL's `NOW()`, `CURRENT_TIMESTAMP`,
`DEFAULT CURRENT_TIMESTAMP`, `ON UPDATE CURRENT_TIMESTAMP` and triggers use the MySQL server's.
When the two differ, timestamps written by the framework (`Update()`'s `updated_at`, the object's
in-memory timestamps after `Insert()`, see item 15) and timestamps written by MySQL (insert defaults)
disagree.

**Solution.**
- New config key `db_sync_timezone`, in the environment section:
  - **default true**: absent means on;
  - explicit `false` turns it off. `parse_ini_file()` turns `false`/`off`/`no` into `""`, so treat
    `""`, `"0"` and `false` as off. Only a missing key counts as "default";
  - **if no `timezone` is set in the config, the sync is off**, whatever `db_sync_timezone` says.
    "Set" means the config's `timezone` key, not php.ini's default.
- When it's on, `OpenConnectionPlease()` runs `SET time_zone = '<timezone>'` right after
  `set_charset()`, once per connection (= once per request with item 20).
- The value sent to MySQL is `date_default_timezone_get()`, not the raw config string. PHP only ever
  returns a valid identifier, so the SQL can't be injected and no runtime validation is needed (checks
  belong in the troubleshooter, item 21). It's also what `now()` actually uses: with an
  invalid `timezone` value, PHP stays on its default zone, and MySQL is synced to that same zone.
- If MySQL rejects the name (error 1298: its timezone tables aren't loaded, which is common in plain
  Docker images), fall back to the current offset, `date('P')` (e.g. `-03:00`), and log which one was
  used in DEV. An offset doesn't follow DST, so a CLI worker running across a DST switch would be an
  hour off until it reconnects. Document this.
- `DatabaseSimulate` records the `SET time_zone` through `Simulate()` so tests can assert it.
  Tests: on by default with `timezone`; off with `db_sync_timezone = false`; off without `timezone`;
  offset fallback. The troubleshooter checks (item 21) get their own tests, as a plain function
  returning the list of findings so the admin page and the wizard both render it.
- Covers `NOW()`, `CURRENT_TIMESTAMP`, column defaults, `ON UPDATE`, and triggers fired by this
  connection. **Not covered:** MySQL scheduled events (they keep the time zone they were created with)
  and other clients (mysql CLI, BI tools, cron jobs connecting directly).

**Impact on a 2.3.5 project.** Only projects that have `timezone` set and a MySQL server in a
different zone:
- `TIMESTAMP` columns read back converted to PHP's zone. Existing values **appear shifted** by the
  difference. The stored instants don't change, only how they're displayed.
- `DATETIME` columns filled by `NOW()`, defaults or triggers: old rows were written on MySQL's clock and
  new rows on PHP's, so there's a jump at the moment of the upgrade.
- Raw SQL using `NOW()` returns PHP's local time.
- The migration file gives:
  - the check: `SELECT @@global.time_zone, @@system_time_zone, NOW();` compared with
    `date_default_timezone_get()` and `now()`;
  - the list of affected columns:
    `SELECT table_name, column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND data_type = 'timestamp';`;
  - the way out: `db_sync_timezone = false`.

### 23. Autoloader resolves classes by basename only
- [x] done

**Error.** The autoloader (`src/_Functions.php:90-99`) looks for `<basename>.php` in each code folder
and loads the first match. Two classes with the same short name in different namespaces/folders
collide silently, and the wrong file may load.

**Solution.**
- Collect all matches across code folders. If there's exactly one, load it (same as today).
- If there are several, load the one whose `namespace` declaration matches the requested class (read
  with a small regex). If none match, keep 2.3.5's first-match behavior.
- In DEV, log a warning about the duplicate.
- PSR-4 is out of scope (it would be breaking).
- **Added during implementation (Paulo, 2026-10-21):**
  - Matches are deduped by `realpath()`: a folder registered twice (`AddFeature("X")` + `AddRootCodeFolder("features/X")`)
    isn't a duplicate and doesn't warn.
  - The DEV warning goes through `Debugger::Add()` (same channel as item 24): `<pre>` on pages, `_debug` in API calls.
  - New global `Magrathea2\fileDeclaresNamespace($file, $namespace)`: case-insensitive, any of the file's
    `namespace` declarations, `""` = no declaration.
  - `global-functions.md` called the autoloader PSR-4 and said it strips the root namespace (neither is
    true); fixed, along with its wrong `arrToStr()` description.

**Impact on a 2.3.5 project.**
- No duplicates: none.
- Duplicates: the right class now loads where 2.3.5 loaded the first one found. That fixes latent
  bugs, but the loaded code changes.
- Tiny cost: all folders are checked instead of stopping at the first hit.

### 24. Autoloader throws for missing app classes
- [x] done

**Error.** For classes in the app namespace that aren't found, the autoloader throws
`MagratheaException` (`src/_Functions.php:100-105`). That breaks `class_exists("App\X")` (it throws
instead of returning false) and any autoloader registered after it.

**Solution.** Return silently. In DEV, log the "Could not find class … are the code folders correct?"
hint via `Debugger`, so the useful message isn't lost.
- **Added during implementation (Paulo, 2026-10-21):** the hint is a string with the full class name and
  the code folders, not a `MagratheaException`: an exception adds itself to the Debugger in every mode
  (item 14), so in LOG it would write the production log on every `class_exists()` miss. `AddCodeFolder()`
  turned out to keep paths as given (CWD-relative), not relative to `appRoot` as `magrathea-php.md` and
  `skills.MD` said. Docs-only fix (Paulo chose it over changing the code): descriptions corrected and every
  bootstrap example (`magrathea-php.md`, `admin.md`, `admin-manager.md`, `magrathea-api.md`, `examples.php`,
  `skills.MD`) switched to `AddRootCodeFolder()`.

**Impact on a 2.3.5 project.** A missing class now fails with PHP's own `Error: Class "…" not found`
instead of `MagratheaException`. Code that caught `MagratheaException` around `new X()` stops catching
it (unlikely). `class_exists()` works.

### 25. `MagratheaModelControl::ShowAll()` ignores `$modelNamespace`
- [x] done

**Error.** `ShowAll()` (`src/MagratheaModelControl.php:314`) does `new static::$modelName()`. That
resolves as a global class name, unlike every other method, which uses `GetModelName()`.

**Solution.** Use `static::GetModelName()`.
- **Added during implementation (Paulo, 2026-10-21):** values are printed through `htmlspecialchars()`
  (they were raw HTML), the extra `</tr>` after the rows is gone, and the method is `@deprecated`
  (PHPDoc only): it's a debug helper with no callers in `src/`, dropped in 3.0 (`magrathea-3.0.md`).

**Impact on a 2.3.5 project.** None: `ShowAll()` only worked for global-namespace models before.
Values containing HTML now show as text.

### 26. New `decimal` field type (exact numbers aren't safe under `$strictTypes`)
- [x] done

**Error.**
- There's no Magrathea type for SQL `DECIMAL`. `ObjectManager::GetType()` (`src/Admin/ObjectManager.php:498-530`)
  maps only `flo…` → `float`; `dec…` and `dou…` fall to `"unknown"`, so people pick `float` by hand from
  `GetTypesArr()` (`:536`) for money columns. The docs encourage it: `orm-model.md`'s example declares
  `"price" => "float"`, and both type tables say `float` = "DECIMAL, FLOAT, DOUBLE".
- With `$strictTypes = true` (2.3.0), `CoerceValue()` (`src/MagratheaModel.php:169-172`) casts `float`
  with `(float)`, so a `DECIMAL(20,2)` value like `12345678901234567.89` loads already rounded, and with
  item 16 any write-back saves the rounded value. It's the same hazard item 19 avoids on the binding
  side, applied on load. Item 27 makes strict models the generator's default, so this must land first.

**Solution.**
- Add `decimal` to both type lists: `magrathea_getTypesArr()` (`src/_Functions.php:72`) and
  `ObjectManager::GetTypesArr()` (`:537`).
- `ObjectManager::GetType()`: `dec…` → `decimal`. Also `dou…` → `float` (today it's `"unknown"`; DOUBLE is
  as lossy as FLOAT in MySQL, so `float` is correct for it).
- `ObjectManager::GetSQLTypeFromType()` (`:564`): `decimal` → `decimal(10,2) DEFAULT NULL`. Only used by
  the "generate CREATE TABLE" helper (`GenerateQueryForObject()`). `(10,2)` is the common money shape
  and deliberately just a starting point: emit an inline `/* adjust precision */` next to it in the
  generated SQL (a block comment, so it survives being on the same line), and document it. No precision
  field in the object editor: the model never reads it, only this helper would.
- `MagratheaModel::GetDataTypeFromField()` (`:481`): `decimal` → `"text"`, so it binds as `"s"` and
  `CreateInsertQuery()`/`Update()` never `intval()` it. Deliberately **not** `"decimal"`: that return
  value already means "float-ish" for `float`/`double`, and reusing the name for exact numbers would be
  a trap for item 19's switch.
- `CoerceValue()`: explicit `case "decimal":` — throw `MagratheaModelException` on non-numeric (same
  schema-drift rule as `int`/`float`), otherwise **return the string unchanged**. Never cast.
- `float` behavior doesn't change at all (non-strict: string; strict: `(float)`).
- Admin CRUD needs nothing: `AdminCrudObject` (`:162-173`) renders any unlisted type as a text input.
- Tests: `decimal` round-trips `"12345678901234567.89"` unchanged with `$strictTypes` on and off;
  non-numeric throws with strict on; binds as `"s"` through `DatabaseSimulate`; `GetType("decimal(10,2)")`
  → `decimal`, `GetType("double")` → `float`.
- **Added during implementation (2026-10-21):** a reflection test for `GetSQLTypeFromType("decimal")`.
  Docs: both type tables (`orm-model.md`, `skills.MD`) now say `float` = FLOAT/DOUBLE and list
  `decimal`; `orm-model.md` gained a "`decimal` vs `float`" note (bcmath for arithmetic). Left for
  item 27: the `orm-model.md` example's `"price" => "float"` and the "recommended" `$strictTypes` wording.

**Impact on a 2.3.5 project.** None until a model is changed: existing `float` fields keep their exact
behavior. Two things go in the migration file:
- Re-typing a `float` field to `decimal` on a **strict** model changes that field's JSON from a number
  (`12.5`) to a string (`"12.50"`). That's the point (exactness), but it's an API contract change for
  that model.
- **Applies if:** `grep -rn '"float"' app/` on models whose SQL column is `DECIMAL` — check with
  `SELECT table_name, column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND data_type = 'decimal';`.

### 27. Code generator: new objects get `$strictTypes = true`
- [x] done

**Error.** `$strictTypes` (2.3.0) is the correct behavior for new code, but it's opt-in by hand and
`CodeCreator::GenerateBaseCodeForObject()` (`src/Admin/CodeCreator.php:198-249`) never emits it, so every
generated model starts with string-typed numbers. Flipping the framework default is rejected for 2.4.0
(it would change every consumer's JSON at once, on top of item 10).

**The catch.** Base classes are regenerated over existing files ("changes can be overwritten through the
admin", `GetCommentAlert()`). If the generator just always emitted `true`, regenerating an **existing**
model's Base in 2.4.0 would silently flip its JSON contract. So "new code" has to be recorded per object,
not guessed by the generator.

**Solution.**
- New per-object key `strict_types` in the objects config (`magrathea_objects.conf`, same section as
  `table_name`/`db_pk`).
- `src/Admin/views/actions/object-create.php` (one form for both create and edit, `$alreadyExists`): add a
  "Strict types" select with explicit `true`/`false` values. **Not a checkbox**: unchecked checkboxes
  don't post, `object-creation-save.php` saves the whole `$_POST` as the section, and "absent" must keep
  meaning "legacy object".
  - new object (`!$alreadyExists`): default `true`;
  - existing object with no `strict_types` key: default `false`, so re-saving it from the admin keeps
    2.3.5 behavior unless someone deliberately changes it;
  - existing object with the key: show its value.
- `CodeCreator`: when `!empty($data["strict_types"])` (`parse_ini_file` turns `true` into `"1"` and
  `false` into `""`), emit `\tprotected $strictTypes = true;` after the `$autoload` line. Otherwise emit
  nothing (inherits `false`). Never emit `= false` explicitly: it would shadow a future framework default.
- Tests: generated code for an object with `strict_types = true` contains the line; with the key absent
  or `false`, it doesn't. Use the existing `test-dump/magrathea-object-test.conf` fixture (add one
  strict object; leave the existing ones without the key, as the "legacy" case).
- **Recommend it for new code** in `skills.MD` and the docs (item 28/29): new models — generated or
  hand-written — should declare `protected $strictTypes = true;` and use `decimal`, not `float`, for
  exact numbers (item 26). Existing models: only opt in after checking their API consumers. Replace the
  `orm-model.md` example's `"price" => "float"` with `"decimal"` and add `$strictTypes = true` to it, and
  update both type tables (`float` = FLOAT/DOUBLE, `decimal` = DECIMAL).
- **Added during implementation (2026-10-21):** the fixture's strict object is `[review]`, so
  `objectManagerTest::testGetObjects()` now lists four objects. Tests live in `codeCreatorTest.php`
  (sets `CodeCreator`'s `namespace`/`structure` directly: `Load()` needs the DB-backed app config), plus
  a round trip of the select's `"true"`/`"false"` through `ConfigFile::Save()` + `parse_ini_file`.
  Docs: `admin.md` gained an "Objects and the code generator" subsection (the admin docs never
  described the object editor); `skills.MD`'s generated Base example now has the `$strictTypes` line.
  Type tables were already done in item 26.

**Impact on a 2.3.5 project.** None for existing objects: no key → no line → same Base class as before.
Objects created in the 2.4.0 admin are strict by default; a developer who doesn't want that picks
`false` before generating. Migration file: one line explaining the new select and that regenerating an
existing object doesn't change it.

### 27b. `mvc` code structure: the generated Admin and API classes can't work
- [x] done

**Error** (found during item 28).
- `AdminCrudObject::SetObject()` (`src/Admin/Features/CrudObject/AdminCrudObject.php:50-61`) builds the
  control as `get_class($object)."Control"`. In the `mvc` structure the model is `App\Models\Article`
  and the control `App\Controls\ArticleControl`, so it does `new App\Models\ArticleControl()` → PHP
  `Class not found`, before `SetControl()` can be called. (The `feature` structure, the install default
  `Install.php:98`, puts both in `App\Article` and works.)
- `CodeCreator::GenerateCodeForAdmin()` / `GenerateCodeForApi()` emit `namespace App\Controls;` and then
  `new Article()` (→ `App\Controls\Article`, doesn't exist) under `mvc`.
- `CodeManager::GetFileDestination()` (`src/Admin/CodeManager.php:108-121`) has no `mvc` case for the
  `admin` and `api` file types: they fall to `$destinationPath."/".$fileName."/default"`.

- **Found while deciding (2026-10-21):** the generated Base model's `GetControl()` (`CodeCreator.php:247`)
  emitted `new \App\Models\Base\<Model>ControlBase()` under `mvc` (the ControlBase is in
  `App\Controls\Base`). `AdminCrudObject::GetField()` calls it for every `belongs_to` field, so the `mvc`
  CRUD form broke on relations even with `SetObject()` fixed.

**Solution** (Paulo, 2026-10-21: fix it, don't drop `mvc`).
- `SetObject(MagratheaModel $object, ?MagratheaModelControl $control = null)`: a passed control is used
  as is; without one, `<model FQCN>Control` as before, but a missing class throws a
  `MagratheaModelException` naming it. Rejected: resolving through `GetControl()` (it returns the
  **ControlBase**, so the CRUD would stop using the app's `<Model>Control` overrides) and a
  `$controlName` property (same effect, more state).
- `CodeCreator`: new `GetFullControlName()`; Admin/API code uses fully qualified model/control names in
  both structures, and the Admin passes the control to `SetObject()`. Base `GetControl()` uses
  `GetFullControlBaseName()` (same output as before under `feature`).
- `CodeManager::GetFileDestination()`: `mvc` admin/api files go to `Controls/` (matching the
  `App\Controls` namespace already emitted; no new folders). An unknown structure/type throws instead of
  returning a `…/default` path.
- Tests: `codeCreatorTest.php` (Base `GetControl()`, Admin/API code and destinations in both structures,
  unknown structure throws), `adminCrudObjectTest.php` (explicit control used; missing conventional
  control throws).
- Docs: `admin-features.md`, `admin.md` (new "Code structure" table: the docs never described
  `feature`/`mvc`), `skills.MD` (§3 namespace note, §14 `SetObject()` rule and checklist), migration item.

**Impact on a 2.3.5 project.** None for `feature`. `mvc` projects can't have used the generated
Admin/API files; they should regenerate Base models to get the fixed `GetControl()`.

### 27c. `AdminCrudObject` runs `Initialize()` twice
- [x] done

**Error** (found during item 28). `AdminCrudObject::__construct()` called `$this->Initialize()` and then
`parent::__construct()`, which calls it again: `SetObject()` built the control twice, and JS added in
`Initialize()` was registered twice. The first call was there so `SetObject()` could set `featureId`
(`CRUD<Model>`) before `AdminFeature`'s class-basename fallback.

**Solution.** `AdminFeature::__construct()` calls `Initialize()` first and applies the `featureId` fallback
after it; `AdminCrudObject::__construct()` only calls `parent::__construct()`. Tests:
`adminCrudObjectTest.php` (one call; `CRUD<Model>`/`"<Model> CRUD"` defaults; an own `featureName` kept;
basename fallback without `SetObject()`).

**Impact on a 2.3.5 project.** None, unless an `Initialize()` depended on running twice. A custom feature
with an empty `featureId` that reads it inside `Initialize()` now sees it empty (the fallback comes after);
no built-in feature does.

### 28. `skills.MD` doesn't match the code
- [x] done

**Error.**
- It says `config/` where the code uses `configs/` (`Config.php:20`, `MagratheaPHP.php:172`).
- `Config::Instance()->GetConfig("db_host")` (`skills.MD:141`) should be `Get("db_host")`.
- The `AdminCrudObject` example (`:943-950`) uses properties that don't exist; the real API is
  `SetObject()`.
- It says `use_environment` is "usually overridden by `->Dev()`/`->Prod()`" (`:97`). Those only set the
  Debugger; the environment comes from `general/use_environment` or `Config::SetEnvironment()`.
- Don't add the claim "a Control must share its model's namespace": it's false (`GetModelName()`
  uses `$modelNamespace`; only `ShowAll()` ignored it, item 25).

**Solution.** Fix each against the source. Add the sections that items 1, 5, 6, 10, 20, 21, 22, 26 and
27 need (Transactions, error statuses, File Editor opt-in, Bootstrap lock, `decimal` in the field types
table, and the `$strictTypes` paragraph at `skills.MD:265` rewritten from "opt in" to "**recommended for
new models**; check API consumers before turning it on for existing ones"), and add a note at the top
that the cookbook targets 2.4.0. Check `docs/mds/` and `docs/data/examples.php` for the same errors. Copy to
`SKILL.md` afterwards.
- **Added during implementation (Paulo, 2026-10-21):**
  - The plan's "a Control doesn't have to share its model's namespace" holds for `MagratheaModelControl`
    but not for `AdminCrudObject::SetObject()` (control = `<model FQCN>Control`). Paulo chose the generator's
    `feature` namespaces for every `skills.MD` example (`App\Article\Article`, `App\Article\ArticleControl`,
    `App\Article\Base\…`, `App\Article\ArticleAdmin`), and the cookbook now states the CRUD's rule. The `mvc`
    breakage went to item 27b.
  - Sections from items 6, 10, 20, 21, 22, 26, 27 and the File Editor (5) were already in `skills.MD` from
    their own items; only the Bootstrap lock (1) was added here.
  - Extra `skills.MD` errors fixed: `use_environment = "default"` with no `[default]` section (every `Get()`
    throws), `GetConfigSection()` without its required argument, `Connect()` called "optional" (without it
    queries throw "DB not initialized"), fake `CanCreate()/CanEdit()/CanDelete()` (real hook:
    `HasPermission($action)`), fake icon list (`AdminMenu.php:25`: "not in use"), SMTP "reads [mail] from
    config" (it never reads config), `new ArticleControl($id)` used as a model, a Testing `SetConfig()` with
    an invented shape, a §17 skeleton whose `AppPath()` put `configs/` outside the project, checklist
    contradictions ("typed" properties, `LoadVendor()` first), `GetPublished()` as an instance method,
    stale `documentation/` reference.
  - Same errors fixed in docs: `core/config.md` (path, `use_environment`), `core/magrathea-php.md` (path,
    `$magRoot` described as the framework's folder, `Dev()`/`Prod()` claims and the modes table: `Prod()`
    never touches `display_errors`), `docs/data/examples.php` (title, `use_environment`, a note pointing
    at the old `skills.MD` snippet), `admin/admin.md` and `admin/admin-features.md` (fake CRUD properties
    and a table of CRUD routes that don't exist), `getting-started.md` (an invented config format with
    `[database]` sections and `[section:environment]` inheritance; `AppPath(__DIR__)` putting `configs/`
    outside the project), `core/global-functions.md` and `core/magrathea-php.md` (`LoadVendor()` described
    as registering the autoloader: Composer's `files` autoload of `_Functions.php` does; `LoadVendor()`
    only re-requires `vendor/autoload.php`).
  - **Left for item 29:** `advanced/testing.md`'s `TestsManager` example is fictional (`new TestsManager()`,
    `Add()`, `Run()`, `GetConfig("database/host")`); the real class is a singleton with
    `AddTest()`/`RunAllTests()`.

**Impact on a 2.3.5 project.** Docs only.

### 29. Narrative docs pass
- [x] done — most pages were already updated item by item; this pass fixed what was left:
  `database/database.md` (default fetch mode is assoc; examples read arrays; `SetFetchMode()` only takes
  `"object"`/`"assoc"`), `api/api-controller.md` (item 9's DEV-only log; the `Cache()` example passed the
  response as the key), `advanced/testing.md` (rewritten: `TestsManager` is the admin Tests page's runner,
  `Mock()` semantics, `SetConfig()` shape, package path, raw-mode API test), `index.md` (PHP 8.1+, File
  Editor opt-in). Also found: every `Read/Create/Update/Delete` override in the docs and `skills.MD` typed
  its parameter `array`, a fatal against `MagratheaApiControl`'s untyped methods; fixed. DocMap's testing
  entry pointed at a nonexistent `Tests/MagratheaTests.php`.
  - **Code issues found, then fixed (Paulo, 2026-10-21):** `TestsManager::RunAllTests()` was empty and
    `AddTest()` without `$name` read an undefined `$test`; fixed together with the Tests page's shell injection
    as item 5d.

**Error / Solution.** Update every page whose class changed:
- `database/database.md` (items 6, 7, 18, 19, 20, 22; plus: it says `FETCH_OBJECT` is the default fetch mode, but the code defaults to assoc, and every example reads rows as objects, e.g. `$user->email` — fix the claim and the examples);
- `core/config.md` and `core/magrathea-php.md` (21, 22);
- `database/orm-model.md` (15, 16, 17, 26, 27 — the `$strictTypes` section becomes a recommendation
  for new models, the example uses `decimal` + `$strictTypes = true`, the type table gains `decimal`);
- `database/orm-control.md` (25);
- `api/magrathea-api.md` (3, 4, 10, 11, 12, 13);
- `api/api-controller.md` (9);
- `utilities/cache.md` (8, 9);
- `utilities/debugger.md` and `exceptions/exceptions.md` (14; plus `MagratheaDBConnectionLostException`
  from 20, and add its file to the `exceptions` entry's `src` list in `docs/lib/DocMap.php:81`);
- `core/global-functions.md` (23, 24);
- `admin/admin.md` and `admin/admin-features.md` (2, 5, 27 — the object editor's "Strict types" select);
- `getting-started.md` (1, Bootstrap lock);
- `advanced/testing.md` (found in item 28: the `TestsManager` example doesn't match the class).

**Impact on a 2.3.5 project.** Docs only.

### 30. Migrations docs page
- [x] done — `skills.MD`'s docs-site URL (`platypusweb.com.br/site/magratheaphp2/docs/`) was a 404; it's
  `https://www.platypusweb.com.br/magratheaphp2/docs/` (the README's).

**Solution.**
- The file lives only at **`docs/mds/migrations/migrate-2.4.0.md`**. It's a normal docs page: no
  DocMap path change, no copy in `src/`.
- New DocMap category `migrations` ("Migrations"), with one page per release, starting with
  `2.4.0` (`"md" => "migrations/migrate-2.4.0.md"`, `"src" => []`).
- Mirror it in `docs/mds/index.md` (docs/CLAUDE.md: the DocMap mirrors the index).
- `/docs` is `export-ignore` in `.gitattributes`, so the file **doesn't ship in
  `vendor/`**. An agent migrating a consuming project has to read it from the repo or the docs site.
  So:
  - the `### 2.4.0` changelog entry (which does ship, in `src/changelog.md`) gives the file's repo path
    and the docs-site URL;
  - `skills.MD` gets an "Upgrading" line with the same two locations.

### 31. `docs/mds/migrations/migrate-2.4.0.md`
- [x] done

Written for both humans and AI agents, as a self-contained file: an agent may be handed just this
file, without the rest of the docs. Structure:
- a header with the from/to versions, a summary table (item → breaking? → action needed?), and the
  composer constraint to use;
- **one section per item that has any impact**, each with:
  - **Applies if:** a concrete `grep` the agent can run in the project (e.g.
    `grep -rn "QueryTransaction\|->Update()" app/`, or client code checking `success` in `.then`);
  - **What changed:** one or two sentences;
  - **What to do:** exact code, or "nothing";
- a "verify after upgrading" checklist: the `time_zone` → `timezone` rename and what it switches on
  (items 21, 22), the timestamp/timezone checks (item 22), admin menu (item 5), API client error
  handling (item 10), `float` fields on `DECIMAL` columns (item 26), `bootstrap_unlocked = true` in
  `[dev]` if the project uses the Bootstrap wizard (item 1).

Items with no impact are listed in one line each, so an agent doesn't waste time on them.

---

## Release (after all items are done)

- [x] `src/version` → `2.4.0` (done by Paulo; the trailing newline is harmless, `Version()` trims it since 2.3.3).
- [x] Review the `### 2.4.0` section of `src/changelog.md` (filled item by item, instruction 2.7): set
      its date line to the release month, and check it has the `QueryTransaction()` guard note and a
      `**note:**` pointing to the migration file. (2026-10-22: date line `2026-10`; the guard is in the
      `Transaction()` entry; the migration `**note:**` is the first line.)
- [x] Full test run; record the final counts here. (2026-10-22: **363 tests, 1337 assertions, 3 deprecations,
      all passing**; baseline was 64/164/3. The 3 deprecations are the same as the baseline's: `firebase/php-jwt`
      v6.10.1's implicitly nullable parameters on PHP 8.4, from `apiControlAuthTest`; not framework code.)
- [x] Copy `skills.MD` → `SKILL.md` one last time. (2026-10-22: byte-identical, checked with `cmp`.)
- [x] Final report: files changed, test results, and what isn't covered by tests (real MySQL:
      `FOR UPDATE` locking, implicit commits, real 2006/2013 errors raising `MagratheaDBConnectionLostException`
      after `wait_timeout`). (2026-10-22: below.)

### Final report (2026-10-22)

**Files changed** (uncommitted on `version/2.4.0`): 80 modified (4022+/990−), 25 new.
- `src/` (framework): 12 top-level files, `Admin/` (25), `Bootstrap/` (5), `DB/` (3), `Exceptions/` (2).
  New: `TimezoneCheck.php`, `Exceptions/MagratheaDBConnectionLostException.php`, `Bootstrap/views/locked.php`.
- `src/Tests/` (29): 22 new test files, e.g. `databaseConnectionTest.php`, `modelPartialUpdateTest.php`,
  `timezoneTest.php`, `adminStartTest.php`, `adminInstallTest.php`.
- Docs: 22 pages in `docs/mds/` (new: `core/timezone.md`, `migrations/migrate-2.4.0.md`), `docs/lib/DocMap.php`,
  `docs/data/examples.php`, `skills.MD` (+ `SKILL.md` copy).
- Other: `composer.json` (`"php": ">=8.1"`), `src/version`, `src/changelog.md`, `future-plans/` (this file,
  new `magrathea-3.0.md`). `CLAUDE.md` is modified too (not by the plan's items).

**Tests:** 363 tests, 1337 assertions, all passing; 3 deprecations, all from `firebase/php-jwt` (see above).

**Not covered by tests** (no `mysqli` in this CLI, no database; DB paths were tested through `DatabaseSimulate`
and the fake connection from `CreateConnection()`):
- Real MySQL behavior: `SELECT … FOR UPDATE` locking inside `Transaction()`; DDL's implicit commit; real
  2006/2013/4031 errors (e.g. after `wait_timeout`) raising `MagratheaDBConnectionLostException`; a real
  rollback after a dropped connection.
- Timezone sync against a real server (items 21, 22): `SET time_zone` accepted by MySQL, the 1298 → offset
  fallback with timezone tables missing, and `TimezoneCheck::Run()`'s MySQL comparison (only its "can't
  query MySQL" path and the config checks are tested).
- Pages that need a logged-in admin and a DB: rendering a bad `magrathea_page` in the full admin layout (5e),
  the admin's setup/DB-error pages themselves (2: the decision, `GetInstallState()`/`CanCreateFirstUser()`, is
  tested; the rendered pages aren't).
- `onclick` escaping inside `<?` short-tag views (5c, 5d): checked by rendering by hand, not by tests.
- The pcntl-based concurrent read test for atomic cache writes (8) is skipped where `pcntl` is missing (it ran here).

**Open, not part of 2.4.0:** `firebase/php-jwt` is locked at v6.10.1 while `composer.json` allows `^6.10||^7.0`;
whether a newer release drops the PHP 8.4 deprecations wasn't checked (consumers resolve their own version
anyway: `composer.lock` doesn't ship).

## Composer constraints (for the migration file)

- Projects on `~2.3.5` / `2.3.*` must change their constraint to receive 2.4.0.
- Projects on `^2.3` receive 2.4.0 on their next `composer update`.
- New projects require `^2.4`.

## Changelog notes

Moved to the `### 2.4.0` section of `src/changelog.md` (instruction 2.7).
