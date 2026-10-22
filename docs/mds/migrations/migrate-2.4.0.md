# Migrating from 2.3.5 to 2.4.0

**From:** `2.3.5` · **To:** `2.4.0`

This file is self-contained: it's written to be handed to a person or an AI agent upgrading a
project, without the rest of the docs. Go through the sections in order. Each one says how to tell
whether it applies to your project (**Applies if**), what changed, and what to do.

## Summary

| # | Change | Breaking? | Action needed? |
|---|--------|-----------|----------------|
| 1 | Bootstrap setup wizard is locked unless `bootstrap_unlocked = true` | Yes, for projects using the wizard | Add the flag to `[dev]` |
| 2 | Admin first-run setup only with zero users; DB errors show an error page | No | None |
| 3 | Failed API authorization no longer runs the action in raw mode | Only for raw-mode callers relying on the bug | Authenticate in tests that call protected endpoints |
| 4 | DEV/ANALYSIS error `stacktrace` frames no longer include `args` | No | None, unless a tool parses `args` |
| 5 | Admin File Editor is no longer loaded by default | Yes, for admins that use it | Opt in with `$this->LoadFileEditor()` |
| 6 | `Query::Clean()` escapes backslashes (it was injection-unsafe); deprecated | Rarely | Prefer `Database::Instance()->Escape()` |
| 7 | Admin Cache feature only touches files inside the cache folder; "clear cache" is POST only; cached content is escaped | No | None, unless custom code calls its actions |
| 8 | Admin Tests page only runs/reads test files inside registered test folders; shell arguments are escaped | No | None, unless code calls `TestsManager` with paths outside `AddTest()` folders |
| 9 | Admin `?magrathea_page=`/`?magrathea_subpage=` only accept names of the admin's own page/action files; anything else is 404 | No | None, unless links use names that aren't files in `views/pages/` or `views/actions/` |
| 10 | New `Database::Transaction(callable $fn)` | No | None (optional: use it) |
| 11 | `QueryTransaction()` rolls back on failure; success returns `true` | No | None |
| 12 | Cache files are written atomically (temp file + rename) | No | None, unless the cache dir isn't writable |
| 13 | `Cache()` logs "adding cache for …" only in DEV | No | None, unless something reads those log lines |
| 14 | Raw-mode API calls bypass the response cache | Only for raw callers relying on cached data | None in practice |
| 15 | `CacheClear()` no longer changes the key set by `Cache()` | No | None |
| 16 | API errors are sent with real 4xx/5xx HTTP statuses (were 200) | **Yes**, for clients that read `success: false` in the success handler | Move error handling to the HTTP client's error path **before** upgrading |
| 17 | `MagratheaApiException::SetStatus()` is honored (it was ignored) | Only where it's called | Check `SetStatus()` calls; a missing token is now HTTP 401 (was 400) |
| 18 | PHP `\Error`s in actions become a logged JSON 500 (were a fatal error page) | No | None |
| 19 | Codes that map outside 400-599 give HTTP 500 | Only for code `2001`-style statuses | None in practice |
| 20 | `BaseAuthorization()` takes a `MagratheaApiAuth` (was typed `MagratheaApiControl`) | No (a plain control never worked) | Auth classes extend `MagratheaApiAuth` |
| 21 | DEV/DEBUG API responses carry debug items in `_debug` instead of printed HTML; DB query errors reach the Debugger once (LOG wrote them twice) | No (DEV/DEBUG/LOG only) | None |
| 22 | `created_at`/`updated_at` handling is unchanged, only documented (DB default on insert, PHP `now()` on update) | No | None |
| 23 | `Update()` on a loaded object writes only the fields that changed, plain assignments included | Yes, silently: input loaded with `LoadObjectFromTableRow()` isn't saved; display-only assignments are; other processes' changes aren't overwritten | Use `Assign()` for input; check assignments before `Save()` |
| 24 | A model object assigned to a field is saved as its id (it wrote `1` or the object's text) | No (it never worked) | None |
| 25 | `Update()` writes SQL `NULL` for a nulled int/boolean on a loaded object, and for `""` always | Yes, where code nulls an int it didn't mean to clear, or sends `""` to a `NOT NULL` int | Check int assignments and forms posting empty ints |
| 26 | `composer.json` requires PHP 8.1+ | No: the framework already needed 8.1 (it uses an `enum`) | None, unless Composer runs on PHP 8.0 |
| 27 | Failed writes (`PrepareAndExecute()`, model `Insert()`/`Update()`/`Delete()`) throw with mysqli reporting off (they printed `got error! ` and returned `null`); `QueryMulti($q, false)`/`ImportFile($f, false)` keep going after a failed statement with PHP 8.1+'s default reporting | Only with reporting off, or code checking `if (!$obj->Insert())` | Catch `MagratheaDBException` instead of checking the return value |
| 28 | DB errors are always `MagratheaDBException` (code = MySQL errno, mysqli's exception as `getPrevious()`), answered by the API with HTTP 500, carrying the statement's values | Yes, for code catching `mysqli_sql_exception` | Catch `MagratheaDBException` |
| 29 | Model writes bind int/boolean fields (and int PKs in `Update()`/`Delete()`) as integers; they were strings | Only for int PKs above `PHP_INT_MAX` | Declare those fields as `"string"` |
| 30 | One shared connection per request instead of one per query | Yes, for long-running CLI workers | Call `Database::Instance()->Disconnect()` at the start of each job |
| 31 | `QueryMulti()` returns SELECT rows (they were always empty) | No | None |
| 32 | `db_port` from the config is now used (it was ignored) | Only if `db_port` is wrong | Check `db_port` or remove it |
| 33 | The timezone key is `timezone`; `time_zone` (old samples) was always ignored, and still is | No, until you rename it | Rename `time_zone` → `timezone`: that turns on item 34 too |
| 34 | With `timezone` set, MySQL's session `time_zone` is synced to PHP's (`db_sync_timezone`, default on) | **Yes**, if MySQL runs in another zone: `TIMESTAMP` values read back shifted, `NOW()` changes | Run the timezone checks; `db_sync_timezone = false` to opt out |
| 35 | Autoloader: with two files of the same name in the code folders, the one whose `namespace` matches is loaded (was the first one); DEV warns | Only with duplicate class names, and then it loads different code | Check duplicates; rename them |
| 36 | A missing class no longer throws `MagratheaException` from the autoloader; PHP's `Error: Class not found` instead | Only for code catching `MagratheaException` around `new X()` / `class_exists()` | Catch `\Error`, or check `class_exists()` first |
| 37 | `MagratheaModelControl::ShowAll()` works with namespaced models (it failed with `Class not found`) and escapes values; deprecated (removed in 3.0) | No | None (stop calling it) |
| 38 | `MagratheaPHP::LoadVendor()` is deprecated (removed in 3.0); it also finds the autoloader in a standalone checkout; `TestsManager` no longer calls it | No | None (stop calling it) |
| 39 | New `decimal` field type for SQL `DECIMAL` (exact, kept as a string); the generator detects `DECIMAL` → `decimal`, `DOUBLE` → `float` | No, until a field is re-typed | Re-type `float` fields on `DECIMAL` columns, checking API consumers on strict models |
| 40 | The admin's object form has a "Strict types" select; new objects generate with `$strictTypes = true` | No (objects created before 2.4.0 are unchanged) | None |
| 41 | Code generator's `mvc` structure: CRUD admin, API and Base `GetControl()` fixed; `SetObject()` takes an optional control | No (they never worked under `mvc`) | Regenerate `mvc` Base models; fix hand-made `mvc` CRUD admins |
| 42 | Admin features run `Initialize()` before the default `featureId` is set; `AdminCrudObject` subclasses run it once (it ran twice) | Rarely: a feature reading an unset `featureId` in `Initialize()` | Set `featureId` before reading it |

## Composer constraint

2.4.0's `composer.json` requires **PHP 8.1+** (item 26). The framework already needed it, so this only
matters if Composer runs on an older PHP.

| Current constraint in `composer.json` | What happens |
|---------------------------------------|--------------|
| `~2.3.5`, `2.3.*`, or an exact `2.3.x` | Stays on 2.3.x. Change it to `^2.4` to receive 2.4.0. |
| `^2.3` (or `^2.0`, `^2.1`, …) | Receives 2.4.0 on the next `composer update`, with no edit: read this file **before** running it. |

```bash
grep -n "platypustechnology/magratheaphp2" composer.json
composer require "platypustechnology/magratheaphp2:^2.4"
```

New projects require `^2.4`.

## Changes with no impact

Nothing to check or do for these:
- **22** `created_at`/`updated_at` handling: unchanged, now documented (the DB default on insert, PHP's `now()` on update).
- **37** `MagratheaModelControl::ShowAll()`: works with namespaced models (it failed with `Class not found`) and escapes its output; deprecated, removed in 3.0, so stop calling it if you do.
- **38** `MagratheaPHP::LoadVendor()`: deprecated, removed in 3.0. It never did anything useful (the class can't
  be loaded before `vendor/autoload.php` is required), so delete the call from your entry files
  (`grep -rn "LoadVendor" --include='*.php' . | grep -v vendor/`). It now also works in a standalone
  checkout of the framework (it failed with a fatal error there), and `Tests\TestsManager` no longer calls it.
- The admin entry file the Bootstrap wizard generates (`magrathea.php`) requires `__DIR__."/../vendor/autoload.php"`
  instead of `"../vendor/autoload.php"` (relative to the current folder, so it failed when run from anywhere
  else). An existing file isn't rewritten; if yours has the old line and works, nothing to do, or edit it to the
  `__DIR__` form.
- Documentation only (`skills.MD`, the docs site, this file).
- Admin links that pointed to pages that don't exist now reach their features: the Structure page's "Cache"
  link, and `AdminMenu::GetItem("app-conf")` / `GetItem("tools-file-editor")` (new `AdminMenu::FeatureItem()`).

---

## 1. Bootstrap setup wizard is locked by default

**Applies if:** the project has an entry file that opens the setup wizard:

```bash
grep -rn "Bootstrap\\\\Start" --include='*.php' . | grep -v vendor/
```

**What changed:** `Magrathea2\Bootstrap\Start::Load()` now only renders the wizard when the
**active environment's** config section has `bootstrap_unlocked = true`. Otherwise it answers
`403` with a "Bootstrap is locked" page. The flag in `[general]` or in another environment's section
is ignored. Also, `?action=` only accepts the wizard's own actions; any other value gets a `404`
instead of a PHP include.

**What to do:**
- Add the flag to the environment section where you use the wizard, normally `[dev]`, in
  `configs/magrathea.conf`:

  ```ini
  [dev]
  	; ...existing keys...
  	bootstrap_unlocked = true
  ```

- Never add it to `[production]`, except temporarily (e.g. to reset a locked-out admin password),
  and remove it right after.
- Accepted values: `true`, `1`, `on`, `yes`, quoted or not. Anything else, or no flag, means locked.
- If you have custom code calling `?action=` with a name that isn't a file in
  `src/Bootstrap/views/actions/`, it now gets a 404. (It never worked: it was a failed include.)
- Projects that don't use the wizard: nothing to do.

---

## 2. Admin first-run setup can't be reached on an installed system

**Applies if:** the project uses the admin panel:

```bash
grep -rn "AdminManager::Instance()->Start" --include='*.php' . | grep -v vendor/
```

**What changed:** the setup form (which creates a super admin) now appears only when there are
zero admin users. A database error shows a 500 error page instead of the setup form, with the
exception message only in DEV. If users exist but the `admin_install_date` config row is missing,
the row is written and the login page is shown.

**What to do:** nothing. If the admin used to greet you with the setup form on an install that has
users, you'll see the login page now.

---

## 3. A failed API authorization stops the request in raw mode too

**Applies if:** the project calls the API in raw mode, usually from tests or CLI tools:

```bash
grep -rn "Run(true)\|SetRaw()\|ExecuteUrl(" --include='*.php' . | grep -v vendor/
```

**What changed:** when a protected endpoint's auth method returns `false`, `ExecuteUrl()` now returns
the `401` error array and never calls the action. In 2.3.5 it built the error and then ran the action
anyway; over HTTP this was hidden because the error response ends the script. HTTP behavior doesn't
change.

**What to do:**
- Over HTTP: nothing.
- Raw-mode callers (tests, tools) that hit a protected endpoint without valid auth and expected the
  action's data: authenticate first (send a valid token, or point `BaseAuthorization()` at an auth
  class that accepts the call). The response is now:

  ```php
  ["success" => false, "data" => ["code" => 401, "message" => "Authorization Failed! (<method> = false)", "error" => <authenticationErrorData>, ...]]
  ```

---

## 4. API error stack traces no longer include call arguments

**Applies if:** something reads the `stacktrace` from API error responses in DEV or ANALYSIS mode
and uses each frame's `args`:

```bash
grep -rn "stacktrace" --include='*.js' --include='*.ts' --include='*.php' . | grep -v vendor/ | grep -v node_modules/
```

**What changed:** `MagratheaApi::ReturnError()` and `ReturnApiException()` build `stacktrace` with
`debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)`. Frames keep `file`, `line`, `function`, `class` and
`type` and lose `args`, which used to leak config arrays, DB credentials, passwords and JWT keys into
the JSON response. Production modes never had a `stacktrace`.

**What to do:** nothing, unless a tool depends on `args`. If so, it has to get that data some other
way (logs, a debugger); it won't come back in the response.

---

## 5. The admin File Editor is opt-in

**Applies if:** the project uses the admin panel with the default feature set, i.e. its `Admin`
subclass calls `parent::SetFeatures()` or doesn't override `SetFeatures()` at all, or it uses
`AdminManager::Instance()->StartDefault()`:

```bash
grep -rnE 'parent::SetFeatures\(\)|StartDefault\(|Admin\\Admin\b' --include='*.php' . | grep -v vendor/
```

**What changed:** `Admin::SetFeatures()` no longer loads `AdminFeatureFileEditor`. Any logged-in admin
user could use it to edit files on the server, so a leaked admin password became code execution. The
"Tools → File Editor" menu entry and the code generator's "View File" link only appear when the
feature is loaded. A bookmarked `?magrathea_feature=AdminFeatureFileEditor` URL shows "Feature not
available".

**What to do:**
- If nobody uses the File Editor: nothing. That's the recommended state, especially in production.
- If you need it, opt in from your `Admin` subclass:

  ```php
  public function SetFeatures() {
      parent::SetFeatures();
      $this->LoadFileEditor();
  }
  ```

  `StartDefault()` users have no subclass, so they need to create one to get the editor back.
- Projects that override `SetFeatures()` without calling the parent never had the File Editor
  unless they loaded it themselves: nothing changes.

---

## 6. `Query::Clean()` is safe now, and deprecated

**Applies if:** the project calls `Query::Clean()`:

```bash
grep -rn "Query::Clean\|::Clean(" --include='*.php' . | grep -v vendor/
```

**What changed:** `Clean()` escaped quotes but not backslashes, so a value like `\'` could close the SQL
string: it didn't prevent SQL injection. It now calls `Database::Instance()->Escape()`
(`mysqli::real_escape_string`), so:
- it needs a configured database, and opens the connection on first use;
- it also escapes NUL, `\n`, `\r` and Ctrl-Z.

**What to do:**
- Nothing is required: existing calls are safe after upgrading.
- Replace `Query::Clean($x)` with `Database::Instance()->Escape($x)`; `Clean()` is deprecated.
- If you used `Clean()` for anything other than SQL (HTML, JSON, file names), stop: it was never meant
  for that, and its output changed.

---

## 7. The admin Cache feature stays inside the cache folder

**Applies if:** custom code or scripts call the Cache feature's actions directly
(`magrathea_feature=AdminFeatureCache`), e.g. to purge the cache after a deploy:

```bash
grep -rn "AdminFeatureCache" --include='*.php' --include='*.js' --include='*.sh' . | grep -v vendor/
```

**What changed:** `View`, `Edit`, `Save` and `Remove` took the posted `file` as a path relative to the
cache folder without checking it, so any logged-in admin could read, overwrite or delete any file the
web server can reach (`file=../...`), including writing a PHP file. They now accept only the name of an
existing file directly inside the cache folder; anything else throws "invalid file". `ClearCache`
answers 405 unless it's a POST (with the admin CSRF token). Cache contents and file names are
HTML-escaped when shown.

**What to do:** nothing, unless a script purges the cache with a GET to
`?magrathea_feature=AdminFeatureCache&magrathea_feature_action=ClearCache`: purge it in code with
`MagratheaCache::Instance()->RemoveAllCache()` instead.

---

## 8. The admin Tests page stays inside the registered test folders

**Applies if:** code calls `TestsManager`'s run or listing methods itself, or registers test folders:

```bash
grep -rnE "TestsManager|RunFolder\(|RunFile\(|RunFunction\(|GetTestsFromFolder\(|GetMethodsFromTest\(" --include='*.php' . | grep -v vendor/
```

**What changed:** the Tests page posted a folder or file path (and a function name) that
`Tests\TestsManager` put into a shell command unescaped, so any logged-in admin could run shell commands
(`folder=x; any command`) or have PHPUnit run any PHP file on the server. Now `RunFolder()`/`RunFile()`/
`RunFunction()` and the listing methods `GetTestsFromFolder()`/`GetMethodsFromTest()` only accept paths inside
a folder registered with `AddTest()` (or the framework's own, `AddMagrathaTests()`); files must be `*Test.php`;
function names must be letters, digits and `_`. Anything else throws `MagratheaException`, which the Tests page
shows as an error. Every shell argument is escaped. Also, `RunAllTests()` now runs every registered folder (it
was an empty method), and `AddTest()` without a name uses the first folder's name.

**What to do:** nothing for the admin page. Code calling these methods directly with a path outside the
registered folders: register the folder first with `TestsManager::Instance()->AddTest([...], "name")`. Don't
register `/` or the project root: everything under a registered folder can be run.

---

## 9. Admin pages and actions can't include other files

**Applies if:** the project links to admin URLs with `magrathea_page=` or `magrathea_subpage=` built by hand,
or added its own files to the framework's `views/pages/` or `views/actions/`:

```bash
grep -rnE "magrathea_page=|magrathea_subpage=|GetPageUrl\(" --include='*.php' --include='*.js' . | grep -v vendor/
```

**What changed:** the admin included `"pages/".$_GET["magrathea_page"].".php"` and
`"actions/".$_GET["magrathea_subpage"].".php"` as given, so a logged-in admin (or a link a logged-in admin
was tricked into opening: GET requests have no CSRF check) could include any `.php` file the web server can
read with `../`, including the Bootstrap wizard's actions past item 1's lock. Now only a plain name of a file
that exists in `src/Admin/views/pages/` or `src/Admin/views/actions/` is accepted. A bad action answers 404
"action not found"; a bad page answers 404 with a "Page not found" alert in the admin layout.

**What to do:** nothing, unless a link uses a page or action name that isn't one of those files: it already
showed an empty page with a PHP warning, and now shows the 404. Custom admin pages belong in an admin feature
(`?magrathea_feature=`).

---

## 10. New `Database::Transaction(callable $fn)`

**Applies if:** never required. Worth adopting where the project wraps several writes that must succeed
or fail together (look for `QueryTransaction(` or sequences of `Save()`/`Insert()` that should be atomic).

**What changed:** new method. Everything the callback runs on the connection (model writes, controls,
raw queries, `SELECT … FOR UPDATE`) commits when it returns and rolls back when it throws; the exception
is rethrown unchanged. Nested calls run inline. `QueryTransaction()` throws if it's called inside
`Transaction()` (no 2.3.5 code can do that, since `Transaction()` didn't exist).

**What to do:** nothing. If you adopt it: don't catch-and-continue DB exceptions inside the callback, and
keep DDL out of it (MySQL commits DDL implicitly).

---

## 11. `QueryTransaction()` rolls back on failure

**Applies if:** the project calls `QueryTransaction()`:

```bash
grep -rn "QueryTransaction" --include='*.php' . | grep -v vendor/
```

**What changed:**
- On a failing statement, it stops (the remaining statements aren't sent), rolls back everything it already
  ran, and turns autocommit back on. 2.3.5 never rolled back, only noticed an error if it was the
  *last* statement's, and left the connection with autocommit off.
- Failures: with mysqli reporting off, it returns `false`, as before; with PHP 8.1+'s default
  reporting, it throws after the rollback, now as `MagratheaDBException` instead of mysqli's
  `mysqli_sql_exception` (see item 28).
- Success returns `true` instead of `null`.

**What to do:** nothing. Only code that compared the result with `null` (`=== null`) notices the new
`true`; `if (QueryTransaction(...))` is now true on success, and `!== false` checks don't change.

---

## 12. Cache files are written atomically

**Applies if:** the project uses the response cache, or calls `SaveFile()` directly:

```bash
grep -rn "Cache(\|SaveFile(\|cache_path" --include='*.php' --include='*.conf' . | grep -v vendor/
```

**What changed:** `MagratheaCache::SaveFile()` writes to a `*.tmp` file in the cache directory and
renames it over the cache file. 2.3.5 truncated the cache file and then wrote it, so a concurrent request
could serve an empty or partial JSON response. If the file can't be written, `SaveFile()` now returns
`false`; 2.3.5 raised a warning and then a `TypeError`.

**What to do:** nothing, as long as the web server can write to the cache **directory** (a rename
needs directory write permission; 2.3.5 could get by with a writable file in a read-only directory).
Stray `*.tmp` files in the cache dir come from processes killed mid-write; "clear all cache" in the
admin (`RemoveAllCache()`) removes them.

---

## 13. `Cache()` logs only in DEV

**Applies if:** something (a script, a log alert, a person) reads "adding cache for" lines from the
Logger's files:

```bash
grep -rn "adding cache for" --include='*.php' --include='*.sh' . | grep -v vendor/
```

**What changed:** `MagratheaApiControl::Cache()` logged `adding cache for [name/data] from (...)` on
every call in every environment, cache hits included, which flooded the logs on polled routes. It now
logs only when `MagratheaPHP::Instance()->IsDev()` is true (`Dev()`; `Debug()` doesn't count).

**What to do:** nothing. To see the lines again, run the environment with `->Dev()`.

---

## 14. Raw-mode API calls bypass the response cache

**Applies if:** the project calls the API in raw mode (usually tests or CLI tools) on actions that
call `$this->Cache(...)`:

```bash
grep -rn "Run(true)\|SetRaw()\|ExecuteUrl(" --include='*.php' . | grep -v vendor/
grep -rn -- "->Cache(" --include='*.php' . | grep -v vendor/
```

**What changed:** `ExecuteUrl()` now calls `MagratheaCache::Instance()->Bypass($returnRaw)`. In raw mode
a cached action always runs and returns its data, and nothing is saved. In 2.3.5, a cache hit or a cache
save in raw mode printed the JSON and ended the script with `die`, so the caller never got its return
value (in PHPUnit, the run stopped silently with exit code 0).

**What to do:** nothing. Raw callers that worked around it (`no_cache = true` in the test environment,
clearing the cache before each call) can drop the workaround. Warming the cache through raw calls isn't
possible; make real HTTP requests for that.

---

## 15. `CacheClear()` doesn't change the current cache key

**Applies if:** an action calls `Cache()` and then `CacheClear()`:

```bash
grep -rn "CacheClear(" --include='*.php' . | grep -v vendor/
```

**What changed:** `MagratheaCache::Clear()` (behind `CacheClear()`) used to set the current cache key to
the one it cleared, so the action's response was saved under the cleared key instead of its own. It now
leaves the key alone.

**What to do:** nothing. If such an action exists, its cache file now gets its intended name; stray
files saved under the wrong name can be cleared from the admin's Cache feature.

---

## 16. API errors are sent with real HTTP statuses

**This is the most visible change in 2.4.0.** There is no switch to keep the old behavior.

**Applies if:** the project has API clients (front end, mobile app, other services) built on a client
that rejects 4xx/5xx responses (axios, jQuery `$.ajax`/`$.get`/`$.post`, Angular `HttpClient`,
Guzzle with `http_errors`) and checks `success` in its success handler:

```bash
grep -rnE "\.success\b|success\s*[!=]==?\s*false" --include='*.js' --include='*.ts' --include='*.jsx' --include='*.tsx' --include='*.vue' . | grep -v node_modules/ | grep -v vendor/
```

and, on the PHP side, if it calls `ReturnError()` or `ReturnFail()` itself:

```bash
grep -rn "ReturnError(\|ReturnFail(" --include='*.php' . | grep -v vendor/
```

**What changed:** in 2.3.5 most error responses were HTTP **200** with `{"success": false, ...}`: unknown
routes (404), exceptions that aren't `MagratheaApiException`, and `ReturnError()` without a `$status`.
Only `MagratheaApiException` and a failed authorization used real statuses. Now every error response
has a 4xx/5xx status, derived from the error code (`404` → 404, `4031` → 403, `40312` → 403, anything
else → 500):

| Case | 2.3.5 | 2.4.0 |
|------|-------|-------|
| unknown route | 200 | 404 |
| exception with code 0 (`new \Exception("x")`) | 200, `data: {}` | 500, `data: {"message": "x"}` |
| exception with a code (`new \Exception("x", 4091)`) | 200 | 409 |
| `ReturnError($code, ...)` without `$status` | 200 | from `$code` |
| `ReturnError(..., $status)` | `$status` | `$status` (unchanged) |
| `ReturnFail($exception)` | 200 | from the code (500 for 0) |
| `ReturnFail($array)` | 200 | 200 (unchanged) |
| `MagratheaApiException` | from the code | from the code (unchanged, except item 19) |

Response bodies are the same, except that an exception with code 0 now carries its message (it was
encoded as `{}`).

**What to do:**
- `fetch()` clients: nothing; `fetch` doesn't reject on 4xx/5xx and `success` is still `false`.
- axios / jQuery / Angular clients: move the `success: false` handling to the error path **before**
  upgrading the backend. Code that handles both paths works on 2.3.5 and 2.4.0:

  ```js
  // axios, before
  const rs = (await axios.get("/api/products/1")).data;
  if (!rs.success) return showError(rs.data.message);

  // axios, after (works on both versions)
  try {
      const rs = (await axios.get("/api/products/1")).data;
      if (!rs.success) return showError(rs.data.message); // 2.3.5 only
  } catch (err) {
      const body = err.response && err.response.data;      // {"success": false, "data": {...}}
      return showError(body && body.data ? body.data.message : err.message);
  }
  ```

  ```js
  // jQuery, before
  $.get("/api/products/1", rs => {
      if (!rs.success) return showError(rs.data.message);
      render(rs.data);
  });

  // jQuery, after (works on both versions)
  $.get("/api/products/1", rs => {
      if (!rs.success) return showError(rs.data.message); // 2.3.5 only
      render(rs.data);
  }).fail(jqXHR => {
      const body = jqXHR.responseJSON;                     // {"success": false, "data": {...}}
      showError(body && body.data ? body.data.message : jqXHR.statusText);
  });
  ```
- App classes extending `MagratheaApi` that call `ReturnError()` without `$status` now send a real
  status. Pass `$status` explicitly where HTTP 200 is really wanted.
- Monitoring/alerting on 5xx rates: expect more 5xx, since server errors used to be 200.
- The admin needs nothing: its `callApi()` helper still resolves API errors with the error body.

---

## 17. `MagratheaApiException::SetStatus()` is honored

**Applies if:**

```bash
grep -rn "SetStatus(\|->status\b" --include='*.php' . | grep -v vendor/
```

**What changed:** `SetStatus()` stored a status that nothing read; the HTTP status always came from the
code. Now a `SetStatus()` value between 400 and 599 is the response's status (others are ignored).
`$status` defaults to `null` (it was 500, or 200 for `$kill = false`), and `$kill` no longer affects any
status. The framework's own `GetAuthorizationToken()` uses it: a request without a token gets HTTP
**401** (code 400 in the body), where 2.3.5 sent HTTP 400.

**What to do:** check each `SetStatus()` call: the status it sets is what clients get now. Code that
read `$ex->status` expecting 500/200 gets `null` unless `SetStatus()` was called.

---

## 18. PHP `\Error`s in API actions become a JSON 500

**Applies if:** always (no action needed).

**What changed:** a `TypeError` or other PHP `\Error` thrown by an API action or auth method (in
`MagratheaApi` and the admin's `AdminApi`) used to escape as a PHP fatal error, with an HTML body. Now it
is logged with `Logger::LogError()` (class, message, file:line; PHP's `error_log()` if the Logger can't
write) and answered as `{"success": false, "data": {"message": "Internal Server Error"}}` with HTTP 500.
In DEV the response has the real message, the class and file:line. `Logger::LogError()` also accepts
PHP `\Error`s and non-exceptions now (they used to be logged without a message).

**What to do:** nothing. These errors now land in the Magrathea log (`logs_path`) instead of PHP's error
log, so check there.

---

## 19. Codes outside the status scheme give HTTP 500

**Applies if:** the project throws exceptions or calls `ReturnError()` with codes that don't map to a
4xx/5xx status (e.g. `2001`, `3000`, `7000`, or 3-digit codes below 400):

```bash
grep -rnE "MagratheaApiException\([^,]+,\s*[0-9]+|ReturnError\(\s*[0-9]+" --include='*.php' . | grep -v vendor/
```

**What changed:** the code → status mapping (3 digits as is, 4 digits / 10, 5 digits / 100) is kept,
but a result outside 400-599 now gives 500. In 2.3.5 MySQL's `1062` gave HTTP 106, `2001` gave HTTP 200
on an error, `7000` gave 700. Codes 400-599, 4000-5999 and 40000-59999 are unchanged.

**What to do:** nothing, unless a code was chosen to produce a non-error status; use `SetStatus()` or an
explicit `ReturnError()` `$status` instead.

---

## 20. `BaseAuthorization()` takes a `MagratheaApiAuth`

**Applies if:** the project calls `BaseAuthorization()`:

```bash
grep -rn "BaseAuthorization(" --include='*.php' . | grep -v vendor/
```

Then check what the class it receives extends:

```bash
grep -rnE "class \w+ extends \\\\?(Magrathea2\\\\)?MagratheaApi(Auth|Control)\b" --include='*.php' . | grep -v vendor/
```

**What changed:** the parameter was typed `MagratheaApiControl`, but the value is stored in a
`?MagratheaApiAuth` property, so passing a plain `MagratheaApiControl` already threw a `TypeError`
("Cannot assign … to property … $authClass"). The parameter is now typed `MagratheaApiAuth`, so the
`TypeError` names the call instead. The framework's own docs and `skills.MD` showed
`AuthControl extends MagratheaApiControl` passed to `BaseAuthorization()`: code copied from them never
worked.

**What to do:**
- If the class passed to `BaseAuthorization()` extends `MagratheaApiControl`, make it extend
  `MagratheaApiAuth` (and `use Magrathea2\MagratheaApiAuth;`). `MagratheaApiAuth` extends
  `MagratheaApiControl`, so its methods (`jwtEncode()`, `GetAuthorizationToken()`, `GetTokenInfo()`, …)
  stay available:

  ```php
  // before
  class AuthControl extends MagratheaApiControl { ... }

  // after
  class AuthControl extends MagratheaApiAuth { ... }
  ```

- If it already extends `MagratheaApiAuth`: nothing. Subclasses of `MagratheaApi` that override
  `BaseAuthorization()` with a `MagratheaApiControl` parameter stay compatible.

---

## 21. DEV and DEBUG API responses carry debug items in `_debug`

**Applies if:** the project runs its API with `->Dev()` or `->Debug()` (or `Debugger::DEV`/`Debugger::DEBUG`)
anywhere: local machines, staging. Production (`->Prod()`) is unaffected.

```bash
grep -rn -- "->Dev()\|->Debug()\|SetDev()\|SetDebug()\|Debugger::DEV\|Debugger::DEBUG" --include='*.php' . | grep -v vendor/
```

**What changed:** in DEV every debug item, including every `MagratheaException` when it's created (even one
that's caught), was printed as `<pre>…</pre>`. In DEBUG they were all printed as an HTML box at the end of
the request. In an API call that HTML landed before (DEV) or after (DEBUG) the JSON, so clients failed to
parse the response. Now, while `MagratheaApi::ExecuteUrl()` (and so `Run()`) runs, DEV and DEBUG collect
those items and the response gets them as an extra `_debug` key. In DEBUG it also carries the items stored
before the call, and the end-of-request box is no longer printed after a JSON response:

```json
{"success": true, "data": {...}, "_debug": [
  {"type": "Magrathea2\\Exceptions\\MagratheaException", "message": "...", "code": 42, "file": "/path/X.php:40", "data": {...}},
  "a string passed to Debugger::Add()"
]}
```

- Only DEV/DEBUG responses with something to send get `_debug`; other modes never do.
- Only associative-array responses get it: a list passed to `Json()` is sent unchanged.
- The cache file never contains `_debug`; a cache hit is sent without it. Raw mode returns no `_debug`.
- Pages and CLI scripts in DEV/DEBUG still print as before. In DEBUG, any response sent with a JSON
  `Content-Type` (including the app's own) no longer gets the end-of-request box.
- A failed query used to reach the Debugger twice (`Database` added the exception it had just created,
  which had already added itself): LOG wrote each DB error to the log twice, DEV printed it twice. Now once.

**What to do:** nothing.
- If you read exceptions from the HTML in the browser's Network tab, read `_debug` now.
- DEV/DEBUG clients that validate responses strictly (e.g. Zod `.strict()`, `additionalProperties: false`)
  must allow `_debug`.
- `_debug` can contain SQL and its values (`MagratheaDBException`; in DEBUG every query): if a staging
  server reachable from outside runs in DEV or DEBUG, they're in its responses. Run it with `->Prod()`.
- Log alerts that counted DB error lines: expect one line per failed query instead of two.

---

## 23. `Update()` writes the fields that changed

**Applies if:** the project updates models with `Save()`/`Update()`. It needs action only where
`LoadObjectFromTableRow()` is fed something that isn't a DB row:

```bash
grep -rn "LoadObjectFromTableRow" --include='*.php' . | grep -v vendor/
grep -rnE -- "->(Save|Update)\(\)" --include='*.php' . | grep -v vendor/
```

**What changed:** an object is now **loaded** after `new Model($id)`, `GetById()`, any Control query,
`LoadObjectFromTableRow()`, or its own `Insert()`/`InsertWithPk()`/`Update()`. A loaded object's
`Update()` writes the fields whose value changed since then (plain assignments like `$obj->name = …`
and `Set()`/`Assign()` alike), plus `updated_at`. In 2.3.5, one `Set()` made `Update()` write only the
`Set()` fields and silently drop the plain assignments; without any `Set()` it rewrote every column.
- `5`/`"5"`, `true`/`"1"`, `false`/`"0"` are the same value; `null` only equals `null`.
- Nothing changed: only `updated_at` is written.
- `$dirtyValues` is cleared after each successful `Insert()`/`InsertWithPk()`/`Update()`.
- Objects that were never loaded (`new Model(); $m->id = 5; … Update()`, e.g. `MagratheaApiControl::Update()`)
  keep the 2.3.5 rule.

**What to do:**
- `LoadObjectFromTableRow($_POST)` (or any input) followed by `Save()`/`Update()` now writes nothing but
  `updated_at`, because the object counts as loaded with those values. Replace it with `Assign()`:

  ```php
  $obj = new Article($id);
  $obj->Assign($this->GetPut());
  $obj->Save();
  ```

- Values another process changed between your load and your `Update()` are no longer overwritten,
  unless this object changed that field. If some code relied on `Update()` rewriting the whole row from
  a loaded object, assign those fields explicitly.
- Assignments that 2.3.5 dropped after a `Set()` are saved now. Look for code that assigns a field on a
  loaded object only for display (formatting a date, masking a value) and then calls `Save()`: that
  value now reaches the DB.

---

## 24. A model object in a field is saved as its id

**Applies if:** code assigns a model object to a field (`$article->author_id = $author;`) and saves.
There's no reliable grep; look at FK assignments near `Save()`/`Insert()`/`Update()`.

**What changed:** `Insert()`/`Update()` meant to save a related model's id, inserting the model first
if it had none, but the check never matched (`is_a()` with an unqualified class name). The object
itself was bound: an int field got `1` (with a PHP warning), a string field the object's `__toString()`
text. Now the id is written, and a related model without an id is inserted first.

**What to do:** nothing. If a row was saved this way in 2.3.5, its FK holds `1`: fix the data.

---

## 25. `Update()` can set int and boolean columns to NULL

**Applies if:** code sets an int/boolean field to `null` on a loaded object and saves, or saves
form/API input where an int field can be empty (`""`), e.g. the admin CRUD (int fields are text inputs):

```bash
grep -rnE -- "->[a-z_]+ *= *null;|Set\([^,]+, *null\)" --include='*.php' . | grep -v vendor/
grep -rnE -- "->Assign\(" --include='*.php' . | grep -v vendor/
```

**What changed:** `Update()` skipped every `null` int/boolean field, so a nullable FK could never be
cleared; `""` was bound as is (MySQL strict mode: `Incorrect integer value` error; otherwise `0`).
Now, for int/boolean fields:
- on a loaded object (see item 23), `null` is written as SQL `NULL`;
- `""` is written as SQL `NULL` on any object;
- other values go through `intval()` (they were sent raw for `int` fields): `"7"` → `7`, `"1.5"` → `1`
  (MySQL rounded it to `2`), `"abc"` → `0` (strict mode raised an error).
- On a never-loaded object, `null` ints/booleans are still skipped. `Insert()` is unchanged (`""` → `0`).

**What to do:**
- Code that nulls an int on a loaded object (e.g. to hide it before returning the object) and then
  saves: that `NULL` now reaches the DB. Unset it after saving, or work on a copy.
- A `NOT NULL` int/boolean column receiving `null` or `""` now raises a DB error instead of keeping its
  value (or, in non-strict mode, writing `0`). Validate the input, or send `0` explicitly.
- Admin CRUD: saving a record with an empty int field writes `NULL`; on a `NOT NULL` column that's a
  DB error shown in the form.

---

## 26. `composer.json` requires PHP 8.1+

**Applies if:** Composer runs on PHP 8.0 or older (`php -v`, on the machine that runs `composer`).

**What changed:** `composer.json` now has `"php": ">=8.1"`. The framework already needed PHP 8.1:
2.3.x declares an `enum` (`Magrathea2\DB\QueryType`), which is a fatal error on PHP 8.0 as soon as the
query builder loads. The constraint only makes Composer say so up front.

**What to do:** nothing if the project runs today. If Composer itself runs on an older PHP than the
server (a CI image, a deploy box), upgrade it there, or Composer won't install 2.4.0.

---

## 27. Failed writes always throw

**Applies if:** the project turns mysqli error reporting off, or code checks the return value of a write
instead of catching:

```bash
grep -rn "mysqli_report" --include='*.php' . | grep -v vendor/
grep -rnE "if *\( *!.*->(Insert|InsertWithPk|Update|Save|Delete|PrepareAndExecute)\(|(Insert|Update|Save|Delete|PrepareAndExecute)\(.*\) *[!=]==? *(null|false)" --include='*.php' . | grep -v vendor/
```

**What changed:**
- `Database::PrepareAndExecute()`, and so the model's `Insert()`, `InsertWithPk()`, `Update()`,
  `Save()` and `Delete()`, throws `MagratheaDBException` on any failed execute. With mysqli error
  reporting off, 2.3.5 printed `got error! ` (into the page, or before an API's JSON) and returned
  `null`: `Insert()` set the PK to `null`, `Update()` returned `null`, and the API answered success.
  With PHP 8.1+'s default reporting (exceptions on), nothing changes: it already threw.
- `QueryMulti($queries, false)` (and `ImportFile($file, false)`) records a failing statement and runs
  the next ones with PHP 8.1+'s default reporting too; before, the first failure threw. A lost
  connection still throws.

**What to do:**
- **Code that checks a write's return value** (`if (!$obj->Insert())`, `=== null`): it never sees a
  failure anymore, the exception goes up. Replace it with a `try`/`catch (MagratheaDBException $ex)`
  where the failure is expected (e.g. a duplicate key), or let it reach the API, which answers 500.
- **Projects with reporting off:** besides the above, expect failed writes to show up as exceptions
  (and API 500s) where they were silent.
- Everyone else: nothing.

---

## 28. DB errors are always `MagratheaDBException`, with MySQL's errno as code

**Applies if:** code catches `mysqli_sql_exception`, or reads a DB exception's code:

```bash
grep -rn "mysqli_sql_exception" --include='*.php' . | grep -v vendor/
grep -rn "catch *(.*MagratheaDBException" --include='*.php' . | grep -v vendor/
```

**What changed:**
- With PHP 8.1+'s default reporting, most query methods (`Query()`, `QueryAll()`, `QueryOne()`,
  `QueryMulti()`, `QueryTransaction()`, `PrepareAndExecute()`'s prepare, and `Transaction()`'s
  `COMMIT`) let mysqli's `mysqli_sql_exception` out as is. They now throw `MagratheaDBException`, with
  mysqli's exception as `getPrevious()`.
- `MagratheaDBException::getCode()` is MySQL's errno (`1062`, `1146`…) in both reporting modes. It was
  always `0`.
- An API answers any `MagratheaDBException` (or `mysqli_sql_exception`) with HTTP 500. With a real errno,
  the code-to-status mapping would have turned MySQL's `4031` into 403 and MariaDB's `4025` into 402.
- A DB exception's `values` now holds the prepared statement's values; it was always empty (the values
  were dropped). DEV/DEBUG output (`_debug`, the Debugger) shows them.

**What to do:**
- `catch (\mysqli_sql_exception $e)` around framework calls: change it to
  `catch (MagratheaDBException $e)`. `$e->getCode()` is the same errno, and the original exception
  (with `getSqlState()`) is `$e->getPrevious()`.
- Code that caught `MagratheaDBException` and checked `getCode() === 0`: it's the errno now.
- Nothing else.

---

## 29. Model writes bind int fields as integers

**Applies if:** the project has a `BIGINT UNSIGNED` int field (usually a PK) with values above
`9223372036854775807` (`PHP_INT_MAX`). Every other project: nothing to do.

**What changed:** `Insert()`, `Update()` and `Delete()` bound every value as a string (`"s"`), and MySQL
converted it: `PrepareAndExecute()` didn't recognize `"integer"`, the type the model passes for `int` and
`boolean` fields. They're bound as integers (`"i"`) now. `Update()` also binds the `WHERE` PK by the
PK field's type (it was always a string), as `Delete()` already did. `float` fields stay strings, so
DECIMAL values don't go through a PHP float. Direct `PrepareAndExecute()` calls with `"int"`,
`"boolean"` or `"float"` are unchanged; `"integer"` is now bound as an integer too.

**What to do:** nothing. With a `BIGINT UNSIGNED` value above `PHP_INT_MAX`, the `WHERE` PK of
`Update()` and `Delete()` is now clamped to `PHP_INT_MAX` (bound as a string, it worked), so they hit
the wrong row or none. `Insert()`/`Update()`'s values were already clamped by `intval()`. Declare such a
field as `"string"`, or write those rows with the query builder.

---

## 30. One database connection per request

**Applies if:** always, but it needs action only in long-running CLI scripts (workers, daemons, queue
consumers) and in code that relied on `CloseConnectionThanks()` actually closing:

```bash
grep -rnE "while *\(true\)|for *\(;;\)" --include='*.php' . | grep -v vendor/
grep -rn "CloseConnectionThanks\|pcntl_fork" --include='*.php' . | grep -v vendor/
```

**What changed:** each `Database` instance now opens one connection on its first query and reuses it
until the end of the request (a shutdown function closes it). 2.3.5 opened and closed a connection for
every query, including every escaped value in the query builder.
- Session state now lasts the whole request: `SET @var`, `GET_LOCK()`, temporary tables and
  `LAST_INSERT_ID()` survive between calls.
- `CloseConnectionThanks()` does nothing. `Database::Instance()->Disconnect()` closes the connection
  now; `Database::DisconnectAll()` closes every instance's.
- MySQL errors 2006 ("server has gone away"), 2013 ("lost connection during query") and 4031
  (MySQL 8.0.24+: disconnected for inactivity, past `wait_timeout`) throw the new
  `MagratheaDBConnectionLostException` (a `MagratheaDBException`), with the errno as its code. The
  dead connection is dropped first, so the next call reconnects. Nothing is retried automatically.
- New `Database::Instance()->LastAffectedRows()`.

**What to do:**
- **Workers that loop:** a worker idle longer than MySQL's `wait_timeout` (8 hours by default) now
  gets `MagratheaDBConnectionLostException` on its first query after the idle. Add one line at the
  start of each iteration, which restores 2.3.5's fresh-connection-per-job behavior:

  ```php
  while (true) {
      \Magrathea2\DB\Database::Instance()->Disconnect();
      // ... take and process one job ...
  }
  ```

  Catching `MagratheaDBConnectionLostException` and retrying is only safe for idempotent work: code
  2006 means the statement probably didn't run, 4031 that it didn't, 2013 that it may have.
- **Code that forks** (`pcntl_fork()`) or otherwise needed the connection closed mid-request: call
  `Database::Instance()->Disconnect()` where it used to call `CloseConnectionThanks()`.
- **Servers with a low `max_connections`:** a request now holds its connection while it waits on
  anything slow (external APIs, long computations), so peak connections get close to the number of
  PHP-FPM workers. Check that `max_connections` covers that.
- Web requests otherwise: nothing.

---

## 31. `QueryMulti()` returns SELECT rows

**Applies if:** the project calls `QueryMulti()`/`ImportFile()`, or `Query()` with more than one statement:

```bash
grep -rn "QueryMulti\|ImportFile" --include='*.php' . | grep -v vendor/
```

**What changed:** each SELECT's `result` used to be an empty array; it now holds the rows (assoc arrays).

**What to do:** nothing, unless code relied on `result` being empty for SELECTs.

---

## 32. `db_port` is no longer ignored

**Applies if:** the config has `db_port`:

```bash
grep -rn "db_port" configs/
```

**What changed:** `MagratheaPHP::Connect()` never read `db_port`, and `Database::SetConnection()` threw
away any port it was given (an inverted `if`). Every connection used mysqli's default port. Now
`Connect()` passes `db_port`, and `SetConnection()` uses it. Without `db_port` (or with it empty),
mysqli's default port is used, as before. With `db_host = "localhost"`, mysqli connects through the Unix
socket and ignores the port either way.

**What to do:** make sure `db_port` is the server's real port, or remove it. A wrong value that 2.3.5
silently ignored now makes the connection fail.

---

## 33. The timezone key is `timezone`

**Applies if:** the config has `time_zone` (the old sample's spelling), or no timezone key at all:

```bash
grep -n "time_zone\|timezone" configs/magrathea.conf
```

**What changed:** only `timezone`, in the **environment** section, was ever read by
`MagratheaPHP::Load()`. The sample config and the docs wrote `time_zone` (in `[general]` and `[dev]`),
which was silently ignored: those projects ran on php.ini's default timezone (often UTC). Nothing
changes on upgrade: `time_zone` is still ignored. The sample now uses `timezone` only, and the admin's
Server page and the Bootstrap wizard's config step show a timezone check that flags `time_zone`.

**What to do:**
- Rename `time_zone` → `timezone` in each environment section, and delete the `[general]` copy.
- **Renaming turns on two things at once:** PHP's `now()`/`date()` move to that zone (they were on
  php.ini's default), and the MySQL timezone sync of item 34 starts. Read item 34 before renaming
  in production.
- If you'd rather keep everything as it runs today, leave `time_zone` as is (or delete it): no change.

---

## 34. MySQL's session timezone is synced to PHP's

**Applies if:** the config has `timezone` (after item 33's rename, or already) **and** the MySQL
server runs in a different zone. Check it on the server, before upgrading:

```sql
SELECT @@global.time_zone, @@system_time_zone, NOW();
```

and compare with PHP (`php -r 'date_default_timezone_set("America/Sao_Paulo"); echo date_default_timezone_get(), " ", date("Y-m-d H:i:s"), "\n";'`,
with your `timezone`). Same time: nothing to do. Different: read on.

**What changed:** each new connection runs `SET time_zone = '<PHP's timezone>'`, so `NOW()`,
`CURRENT_TIMESTAMP`, column defaults, `ON UPDATE CURRENT_TIMESTAMP` and triggers use PHP's zone. It's
on by default when `timezone` is set; `db_sync_timezone = false` (or `0`, `off`, `no`, quoted or not) in
the same section turns it off. If
MySQL's timezone tables aren't loaded (error 1298), the current offset (e.g. `-03:00`) is used, which
doesn't follow DST. MySQL scheduled events and other clients (mysql CLI, BI tools) aren't affected.

When the zones differ, after the upgrade:
- `TIMESTAMP` columns read back converted to PHP's zone: existing values **appear shifted** by the
  difference (the stored instants don't change). Find them:

  ```sql
  SELECT table_name, column_name FROM information_schema.columns
  WHERE table_schema = DATABASE() AND data_type = 'timestamp';
  ```

- `DATETIME` columns filled by `NOW()`, defaults or triggers: old rows are on MySQL's clock, new rows on
  PHP's, so there's a jump at the moment of the upgrade.
- Raw SQL using `NOW()` returns PHP's local time.
- `created_at` (MySQL's clock) and `updated_at` (PHP's) now agree; before, `updated_at` could be
  earlier than `created_at`.

**What to do:**
- Decide whether the shift/jump matters for your data (reports grouping by day, comparisons with
  stored `DATETIME`s). If it does and you can't fix the data now, opt out:

  ```ini
  [production]
  	timezone = "America/Sao_Paulo"
  	db_sync_timezone = false
  ```

- Otherwise: nothing. Optionally load MySQL's timezone tables
  (`mysql_tzinfo_to_sql /usr/share/zoneinfo | mysql -u root mysql`) so the named zone is used
  instead of a fixed offset. The admin's Server page says which one is in use.

---

## 35. Autoloader picks the file whose namespace matches

**Applies if:** two code folders have a file with the same name. List the candidates (run from the
project root):

```sh
find . -path ./vendor -prune -o -name '*.php' -printf '%f\n' | sort | uniq -d
```

No output: nothing to do. A name listed there matters only if both files are in registered code folders
(`AddCodeFolder()`, `AddFeature()`).

**What changed:** the autoloader looks for `<ClassName>.php` (basename only) in every code folder.
2.3.5 loaded the first file found, in folder order, even when it declared another namespace (then
the class wasn't defined, or the wrong class with the same name was used). 2.4.0 loads the file whose
`namespace` declaration matches the requested class; if none matches, the first one, as before. In DEV,
each duplicate lookup adds a debug item (`<pre>` on pages, `_debug` in API responses) listing the files.
A folder registered twice (`AddFeature("X")` plus `AddRootCodeFolder("features/X")`) isn't a duplicate.

**What to do:** for each duplicate, check that the class now loaded is the one the code meant (2.3.5
may have run the other one without anybody noticing). Then rename one of the classes, so each basename is
unique: that removes the DEV warning and the extra file reads.

---

## 36. A missing class isn't a `MagratheaException` anymore

**Applies if:** the project calls `->AppNamespace(...)` (otherwise the autoloader never threw) **and**
catches `MagratheaException` around code that may use a missing class:

```sh
grep -rn "AppNamespace(" --include='*.php' . | grep -v vendor/
grep -rn "catch.*MagratheaException" --include='*.php' . | grep -v vendor/
```

**What changed:** for a class in the app namespace that isn't in any code folder, the autoloader threw
`MagratheaException("Could not find class [...]. Are the code folders correct?", 500)`. It returns
silently now: `class_exists("App\X")` returns `false` (it threw), autoloaders registered after
Magrathea's run (they never did), and `new X()` fails with PHP's `Error: Class "App\X" not found`. In
DEV, the "Could not find class" hint is still added as a debug item, with the code folders.

**What to do:** nothing, unless a `catch (MagratheaException ...)` was meant to handle a missing class:
catch `\Error` there, or test with `class_exists()` first. A catch-all `catch (\Exception ...)` doesn't
catch `\Error` either.

---

## 39. New `decimal` field type

**Applies if:** a model declares `"float"` for a column that is `DECIMAL` in the database. List the
`float` fields, then the database's `DECIMAL` columns, and compare:

```sh
grep -rn '"float"' --include='*.php' . | grep -v vendor/
```

```sql
SELECT table_name, column_name FROM information_schema.columns
WHERE table_schema = DATABASE() AND data_type = 'decimal';
```

**What changed:** 2.3.5 had no type for `DECIMAL`, so money columns were declared `"float"` (the docs
said so). With `protected $strictTypes = true;`, a `float` field is cast with `(float)` on load, so a
large `DECIMAL` (e.g. `12345678901234567.89`) loads rounded, and a later `Update()` writes the rounded
value back. 2.4.0 adds `"decimal"`: always the exact string the driver returns, strict or not (strict
mode only checks it's numeric and throws `MagratheaModelException` if not), and bound as a string on
writes. The admin's object generator now detects `DECIMAL` columns as `decimal` and `DOUBLE` as `float`
(both were `"unknown"`). Existing `float` fields behave exactly as before.

**What to do:**
- Nothing is required: no field changes type on its own.
- Recommended, for each `float` field on a `DECIMAL` column: change it to `"decimal"` in the model (or
  its Base class / the admin's object editor before regenerating).
  - On a model **without** `$strictTypes`: no visible change (the value was already a string).
  - On a model **with** `$strictTypes = true`: the field's JSON changes from a number (`12.5`) to a
    string (`"12.50"`). That's an API contract change: check the model's API consumers first.
  - PHP code doing arithmetic on the field gets a string: use `bcmath` (`bcadd()`, `bcmul()`, ...) to
    keep it exact; `+`/`*` silently convert it to float again.

---

## 40. The code generator makes new objects strict

**Applies if:** you create or regenerate objects in the admin ("Objects" → object form → generate code).

**What changed:** the object form has a "Strict types" select, saved as `strict_types` in the object's
section of `magrathea_objects.conf`. When it's `true`, the generated Base class gets
`protected $strictTypes = true;` (typed values on load: JSON numbers and booleans instead of strings).
It defaults to `true` for a new object. Objects created before 2.4.0 have no `strict_types` key: they
generate exactly as before, and the form shows `false` for them, so re-saving one doesn't change it.

**What to do:** nothing for existing objects. For a new object you want to keep string-typed (e.g. it
must match the JSON of older endpoints), pick `false` before generating.

---

## 41. The code generator's `mvc` structure works

**Applies if:** the admin's "Generate Code" page is set to "Model-View-Control" (`code_structure = mvc`
in the admin's app config), or a CRUD admin class's model and control live in different namespaces.

**What changed:**
- `AdminCrudObject::SetObject($object, $control = null)` takes an optional control. Without it, it
  still builds `<model class>Control`, but a missing class now throws a `MagratheaModelException`
  naming it (it was PHP's `Class not found`).
- Under `mvc`, the generated Base model's `GetControl()` returned `new \App\Models\Base\<Model>ControlBase()`,
  which doesn't exist; it's `\App\Controls\Base\<Model>ControlBase` now. `feature` code is unchanged.
- Generated CRUD admin and API classes use fully qualified names (`new \App\Models\Article()`,
  `new \App\Controls\ArticleControl()`) and the admin passes the control to `SetObject()`. Under `mvc`
  they're written to `Controls/` (they had no destination).

**What to do:** on an `mvc` project, regenerate each object's Base model (the CRUD form calls
`GetControl()` for relation fields). A hand-written CRUD admin whose control isn't `<model class>Control`
can now pass it: `$this->SetObject(new Article(), new \App\Controls\ArticleControl());`. Nothing for
`feature` projects.

---

## 42. Admin features: `Initialize()` runs first, and once

**Applies if:** an admin CRUD class's `Initialize()` does more than `SetObject()`, or any admin feature's
`Initialize()` reads `$this->featureId` (directly, or through `GetSubpageUrl()` and other URL helpers):

```sh
grep -rln "extends .*AdminCrudObject" --include='*.php' . | grep -v vendor/ | xargs -r grep -n -A8 "function Initialize"
grep -rlE "extends .*(AdminFeature|AdminCrudObject)\b" --include='*.php' . | grep -v vendor/ | xargs -r grep -n "featureId"
```

**What changed:**
- `AdminFeature`'s constructor ran `Initialize()` after setting the default `featureId` (the class's
  short name, used when the class declares `$featureId` empty or without a value). Now `Initialize()`
  runs first and the default is set after it, so a feature can set its own id in `Initialize()`
  (`AdminCrudObject::SetObject()` does).
- `AdminCrudObject`'s constructor called `Initialize()` and then `AdminFeature`'s constructor called it
  again. Now it runs once. Feature ids (`CRUD<Model>`) don't change.

**What to do:**
- A feature that declares `public string $featureId;` (no value) or `= ""` and reads it in
  `Initialize()`: it now gets `""`, or PHP's "must not be accessed before initialization" error. Give
  `$featureId` a value in the class, or set it in `Initialize()` before reading it. Features that declare
  `$featureId = "Something"`, or don't declare it, aren't affected.
- An `AdminCrudObject` subclass whose `Initialize()` counted on the second run (e.g. it toggled a flag
  or appended to an array and the result depended on both runs): make it work in one run.

---

## Verify after upgrading

- [ ] The server and the machine running Composer have PHP 8.1 or later (item 26).
- [ ] If the project uses the Bootstrap wizard: `bootstrap_unlocked = true` is in `[dev]` (item 1),
      and **not** in `[production]`.
- [ ] Admin menu: "Tools → File Editor" is gone unless you opted in with `LoadFileEditor()` (item 5).
- [ ] `db_port`, if present in the config, is the database's real port (it's used now).
- [ ] API clients handle errors in the HTTP client's error path (axios `catch`, jQuery `.fail`), and
      show the error message from the body (item 16). Try an unknown route: it must answer 404.
- [ ] CLI workers that loop call `Database::Instance()->Disconnect()` at the start of each job (item 30).
- [ ] No `catch (\mysqli_sql_exception ...)` is left around framework calls (item 28).
- [ ] The config uses `timezone`, not `time_zone`, in each environment section (item 33). If you just
      renamed it, PHP's timezone and the MySQL sync both switched on (item 34).
- [ ] The admin's Server page "Timezone check" shows PHP and MySQL **in sync**, and no warnings (items 33, 34).
- [ ] If MySQL was on a different zone: `TIMESTAMP` columns and `NOW()`-based queries show the times you
      expect, or `db_sync_timezone = false` is set (item 34).
- [ ] In DEV, no "has more than one file in the code folders" debug item appears (item 35).
- [ ] No model declares `"float"` for a `DECIMAL` column, or the ones that do were left on purpose
      (item 39).
