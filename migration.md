<!-- title: MagratheaPHP2 — Migration Plan for AI-REPORT changes -->

# Migration Plan

## Priority 0 — must-do before anything else

### Bug: `MagratheaApiControl::GetAuthorizationToken()` unguarded array access (src/MagratheaApiControl.php:62-63)

```php
public function GetAuthorizationToken() {
      $token = $this->GetAllHeaders()["Authorization"];
      $gotToken = false;
      if (substr($token, 0, 6) == 'Basic ') {
```

`$this->GetAllHeaders()["Authorization"]` is accessed directly with no `isset()`/`??` guard. When a request has no `Authorization` header at all (e.g. an anonymous hit on a route protected by `BaseAuthorization`), this throws `PHP Warning: Undefined array key "Authorization"`.

**Why it matters:** with `display_errors=1` (normal in dev bootstraps per this project's `_inc.php`), the warning is emitted as inline HTML/Xdebug output before the exception handler reaches `http_response_code()` in `MagratheaApi::ReturnApiException()`. PHP then throws a second warning — "Cannot modify header information - headers already sent" — and the intended status (e.g. 401 for a missing/invalid token) never actually gets sent; the response defaults to HTTP 200 even though the JSON body correctly says `"code":401`. In production (`display_errors=0`) this is silent/harmless, but in dev it corrupts both the status code and the response body (HTML warning dump prepended to the JSON).

**Reproduction:** hit any endpoint whose route auth is `true` (protected) with no `Authorization` header — e.g. `GET /api/v1/me` with no bearer token.

**Fix:**
```php
public function GetAuthorizationToken() {
      $token = $this->GetAllHeaders()["Authorization"] ?? "";
      $gotToken = false;
      if (substr($token, 0, 6) == 'Basic ') {
```
Same pattern already used correctly elsewhere in this file — `getTokenByType()`/`getAuthorizationHeader()` (lines ~154, 165) guard with `isset()` against `$_SERVER`. `GetAuthorizationToken()` is the one outlier that skips that guard.

**Migration impact:** none. This is a pure bugfix — the corrected code path only changes behavior for the previously-broken case (missing header), which today either warns-and-corrupts-the-response (dev) or silently misbehaves (prod, since `$token` was already effectively `null`→`""` in string operations, just via a suppressed warning instead of a clean default). No caller passes or depends on the undefined-key warning as a signal. **Ship this immediately as a SemVer-safe patch, ahead of every other item in this report** — every other change here assumes requests correctly receive the status code the framework intends to send, and this bug undermines that in dev for every protected-but-unauthenticated request, which is also the exact scenario an AI agent will hit constantly while iterating on auth-protected endpoints locally.

---

Companion to `AI-REPORT-SIMPLE.md` / `AI-REPORT-DETAILED.md`. For every proposed change: what breaks in existing (human-written, generator-based) projects, and how old and new code can run side by side during the transition. MagratheaPHP2 is a Composer library consumed by multiple downstream apps you don't control the deploy schedule of — so "just change it" is not an option for anything that touches the public API. Each item below is tagged:

- **SemVer-safe** — can ship in a patch/minor, no downstream code changes required
- **Opt-in** — new behavior available, old behavior still default; downstream migrates on its own schedule
- **Breaking** — requires a major version bump (v3) and a compatibility shim if you want old and new to coexist

---

## 1. Fix `Query::BuildWhere()` SQL injection (src/DB/Query.php:563-575)

**Impact on existing code:** Every `->Where([...])` call, and every `ArticleControl::GetWhere([...])`/`GetRowWhere([...])` built on top of it, currently produces a plain SQL string via `Query::SQL()`. Old projects call `->SQL()` and either pass it straight to `Database::Instance()->QueryAll($sql)` or let `RunQuery()` do it. If `BuildWhere` starts emitting placeholders (`?`) instead of inlined values, `SQL()` alone becomes unusable — you also need the bound values, which today aren't tracked or returned anywhere in the `Query` object.

**Why it's breaking:** `SQL(): string` is a documented, widely-called public method. Changing its output format silently (still returning a string, but now with literal `?` in it and no values) breaks every caller at runtime, not at compile time — the query will execute with `?` as a literal character or fail with a bind-count mismatch. This is the highest-blast-radius change in the whole list because `GetWhere()` is the #1 most-used method in downstream code.

**Migration path — coexistence:**
1. Add parameter tracking to `Query` internally (`protected array $boundParams = []`), populated by `BuildWhere` regardless of which SQL method is used.
2. Keep `SQL(): string` returning the **old, inlined, escaped-via-`mysqli_real_escape_string`** format by default — so it fixes bug 2.2 (weak `Clean()`) immediately as a **SemVer-safe patch** without changing the method's contract. This alone closes the injection hole for the common case with zero downstream changes.
3. Add a new method, `SQLWithParams(): array` (returns `["sql" => "...WHERE field = ?", "params" => [...], "types" => "s"]`), as an **opt-in** addition. New/AI-authored code calls this; old code keeps calling `SQL()` unchanged.
4. Update `Database::PrepareAndExecute()` (already exists, already used by `Insert`/`Update`/`Delete`) to accept the array `SQLWithParams()` returns directly, so callers don't have to hand-split sql/types/params themselves.
5. Update the skill/cookbook and `MagratheaModelControl::GetWhere/RunQuery` to prefer `SQLWithParams()` internally — this fixes the *framework's own* call sites (which is where most downstream exposure actually comes from) without forcing every app to change a single line, since `GetWhere()`'s signature doesn't change, only its internals.
6. Reserve full placeholder-only `SQL()` (removing the escaped-inline fallback) for v3, flagged loudly in the changelog, with the escaped-inline behavior kept as `SQLLegacy()` for one major version so hand-written raw-SQL call sites (`RunQuery($someQuery->SQL())`) have a mechanical find-replace path.

**Net effect:** step 2 alone is safe to ship today and removes the actual injection risk for 90%+ of real call sites. Steps 3-5 are opt-in and additive. Only step 6 is breaking, and it's a v3-only decision you can defer indefinitely.

---

## 2. Fix `Query::Clean()` (src/DB/Query.php:97-101)

**Impact on existing code:** Any app calling `Query::Clean($_GET['x'])` today gets a backslash-escaped string back and concatenates it into raw SQL by hand. If `Clean()` starts calling `mysqli_real_escape_string()` against the live connection, behavior for the common ASCII case is identical — the risk is purely that `Clean()` currently has no dependency on an open DB connection (it's a static string function), and `mysqli_real_escape_string` requires one.

**Why it's mostly safe:** `Database::Instance()` is a singleton already required for any app doing DB work, so requiring an active connection inside `Clean()` is a non-issue in practice — except in test contexts using `DatabaseSimulate`/`Mock()` (skill §16), where `Clean()` would need a mock-aware branch or it throws in unit tests that don't hit a real DB.

**Migration path:**
1. **SemVer-safe patch:** change `Clean()`'s implementation to call `Database::Instance()->EscapeString($value)` (add this thin wrapper around `mysqli_real_escape_string` to `Database.php` if it doesn't already exist), falling back to the current backslash-escape behavior only when `Database::Instance()` has no live connection (e.g., under `Mock()`), with a `Debugger`/`Logger` warning so misuse surfaces in dev.
2. No downstream code changes needed — signature and semantics (returns an escaped string) are unchanged, just more correct.
3. Update the skill cookbook's §5 wording from "always use `Query::Clean()`" to "prefer `PrepareAndExecute()`; `Clean()` is a fallback for legacy raw-SQL code," so AI-authored new code stops reaching for it by default — this is a docs change, not a framework change, so it has zero migration cost.

---

## 3. `default: throw` in `GetDataTypeFromField()` (src/MagratheaModel.php:436-453)

**Impact on existing code:** Today, an unrecognized `$dbValues` type string (e.g. a typo like `"interger"`) silently returns `null` from `GetDataTypeFromField()`, and downstream this presumably degrades to treating the field as a string bind (`s`) or gets skipped. Any **existing** project that has a latent typo like this is, today, running in production without visibly failing — adding a `throw` turns that dormant bug into a hard crash on next deploy.

**Why this needs care despite being "one line":** you cannot know how many downstream apps have an undetected typo in a `$dbValues` type until you turn on the throw. This is a classic "tightening validation is technically a breaking change" situation.

**Migration path:**
1. **Opt-in first:** add the `default` branch, but make it `Logger::Instance()->LogError(...)` (or `Debugger::Instance()->Info(...)` in dev mode) instead of `throw`, for one minor version. Ship this as a SemVer-safe patch — it changes no return values, just adds visibility.
2. Grep your own known downstream projects (or ask affected teams) to run this logged-but-not-thrown version in staging for a sprint, watching logs for hits.
3. Flip `Logger` call to `throw new MagratheaModelException(...)` in the next **minor** version bump (not major — this is a bugfix-shaped change, but call it out prominently in the changelog under "breaking behavior change" since technically a previously-silent path now throws), after confirming zero hits in step 2. If any project does hit it, fix that project's typo first, then ship the throw.

---

## 4. Collapse Base+concrete class split into one file per feature

**Impact on existing code:** This is the single biggest AI-ergonomics win and, encouragingly, the **least framework-breaking** item on the whole list — because the split is purely a *convention* enforced by the generator (`CodeCreator.php`) and followed by hand-written code, not something `MagratheaModel`/`MagratheaModelControl` require structurally. `MagratheaModel` doesn't care whether `MagratheaStart()` is defined in a `Base` class or the concrete class — it just calls `$this->MagratheaStart()` via the constructor chain. A single-file `Article extends MagratheaModel` with `MagratheaStart()` defined directly works today, unmodified, on the current framework version.

**Why old and new can coexist immediately, with zero framework change:**
- Old projects: keep `Article/Base/ArticleBase.php` + `Article/Article.php`, generator keeps working, nothing changes.
- New (AI-authored) projects/features: write `Article.php` with everything in one class. `->AddFeature("Article")` in `_inc.php` already adds both `features/Article` and `features/Article/Base` to the autoloader (skill §1) — if `features/Article/Base/` simply doesn't exist for a given feature, autoloading that (empty/missing) path is a no-op, not an error. **Verify this specific behavior in `AddFeature()`'s implementation before relying on it** — if `AddFeature` currently `require`s or asserts the `Base` folder exists, that's a one-line relaxation (make the Base-folder registration conditional on the path existing) to ship as a SemVer-safe patch, and it's the only framework change this item actually needs.
- Both patterns can live in the *same* project simultaneously, feature-by-feature, since each feature folder is self-contained. Migrating `Article` to single-file doesn't require touching `Author` at all.

**Migration path for an individual existing feature (manual, per-table, whenever convenient):**
1. Copy `MagratheaStart()`, the `$dbValues`, relations, and property declarations from `ArticleBase.php` into `Article.php`, above/alongside the existing business-logic methods.
2. Change `class Article extends Base\ArticleBase` to `class Article extends MagratheaModel implements iMagratheaModel`.
3. Delete `Article/Base/ArticleBase.php` (or leave it — dead code, harmless, but remove to avoid confusion).
4. Same steps for `ArticleControlBase` → `ArticleControl`.
5. No DB schema change, no data migration, no API-contract change — this is a pure code-organization refactor, testable by running existing tests/requests against the collapsed class.

**Documentation change (do this regardless of whether any project migrates):** update the skill cookbook to teach single-file as the default for anything the generator doesn't touch, and explicitly mark the Base-split pattern as "legacy / generator-managed only." This is the highest-leverage, lowest-risk item in the entire report.

---

## 5. Remove/conditionalize the "FILE GENERATED BY MAGRATHEA" header

**Impact on existing code:** None functionally — it's a comment. The only "impact" is on the generator itself: `CodeCreator.php` presumably checks for this header (or file existence generally) before deciding whether it's safe to overwrite a file on regeneration.

**Migration path:**
1. **Check first:** grep `CodeCreator.php`/`ObjectManager.php` for whether the overwrite-safety check actually parses this header string, or just checks "does this exact file path already exist." If it's the latter, the header is purely cosmetic today and can be reworded/removed with zero risk — **SemVer-safe patch**.
2. If the generator *does* parse the header to decide overwrite safety, don't remove it from generator-managed files — instead, stop *emitting* it for anything hand-authored going forward, and add a second, distinct marker (e.g. `## HAND-AUTHORED — DO NOT USE MAGRATHEA GENERATOR ON THIS FILE`) that the generator is taught to respect as a "never touch this" signal. This protects existing generator-managed projects from a behavior change while giving AI-authored files an explicit opt-out.

---

## 6. Decouple the Admin code generator into an optional package

**Impact on existing code:** This is the most organizationally disruptive item, because `src/Admin/*` (CodeCreator, ObjectManager, ~15 view files, plus the CSS/JS asset pipeline dependencies — scssphp, bootstrap, jquery, jshrink) currently ships as part of the core `platypustechnology/magratheaphp2` Composer package. Any project doing `composer require platypustechnology/magratheaphp2` today gets the admin panel bundled whether it uses it or not — moving it out is a **structural package change**, not a code behavior change, so no downstream *runtime* code breaks, but every downstream `composer.json` needs a decision made about whether to also pull the new package.

**Migration path (this is the standard "extract a package" playbook, nothing exotic):**
1. Create `platypustechnology/magrathea-admin` as a new Composer package containing everything under current `src/Admin/*`, depending on `platypustechnology/magratheaphp2` (core) as its own dependency, not the other way around.
2. In the **next minor** of core `magratheaphp2`, keep `src/Admin/*` in place but mark it deprecated in the changelog/docstrings, pointing at the new package — **SemVer-safe, no break**.
3. In the **major** version (v3) of core, remove `src/Admin/*` from the core package entirely. Any project still using the admin panel adds `composer require platypustechnology/magrathea-admin` alongside their now-v3 core dependency; namespaces (`Magrathea2\Admin\...`) stay identical between the old bundled location and the new package, so downstream code (`use Magrathea2\Admin\AdminManager;` etc.) requires **zero changes** — only `composer.json` changes.
4. Because namespaces don't move, both configurations — "admin bundled in core" (pre-v3 projects) and "admin as separate package" (v3+ projects) — can exist across your whole install base simultaneously with no code-level incompatibility; the only thing that has to match is which package version resolves `Magrathea2\Admin\AdminManager`, which Composer already guarantees can't happen twice in one project.
5. Practical rollout order: cut the new package first (step 1), let it sit released-but-unused for a bit so it's proven, deprecate in a minor (step 2), remove in the next planned major (step 3). No project is forced to move until they choose to adopt v3 core.

---

## 7. Replace bespoke `mysqli` `Database` layer with PDO/DBAL (or wrap it)

**Impact on existing code:** Large. `Database.php` is a singleton wrapping `mysqli` directly, referenced throughout `MagratheaModel`, `MagratheaModelControl`, `Query`, and any hand-written app code calling `Database::Instance()->QueryAll/QueryRow/QueryOne/PrepareAndExecute` directly (common in Control classes with custom SQL, per skill §4 "Custom SQL" example). Swapping the underlying driver changes: error types thrown (`mysqli_sql_exception` vs `PDOException`), connection-open semantics, and the exact bind-type-char API (`PrepareAndExecute($sql, ["s","i"], [...])` is a `mysqli`-flavored calling convention that PDO doesn't share natively).

**Why this must be additive, not a rewrite-in-place:** any direct behavior change to `Database::Instance()` breaks every downstream app simultaneously, including ones with no other reason to upgrade.

**Migration path:**
1. Introduce a `DatabaseDriverInterface` (SemVer-safe addition) with the current `mysqli`-backed implementation as `MysqliDriver` (default, zero behavior change) and a new `PdoDriver` as an alternative implementation of the *same* interface.
2. `Database::Instance()` keeps its exact current public method signatures (`QueryAll`, `QueryRow`, `QueryOne`, `PrepareAndExecute`, etc.) as a thin facade delegating to whichever driver is configured — **opt-in via config** (`db_driver = "mysqli"` default vs `"pdo"`), not a code change for existing apps.
3. Existing apps: change nothing, stay on `MysqliDriver` indefinitely — it's not deprecated by this change, just no longer the *only* option.
4. New apps (or apps that want it): set `db_driver = "pdo"` in config, get the same `Database::Instance()` API but PDO underneath. Any app relying on `mysqli`-specific behavior (e.g. inspecting a raw `mysqli_result`) would need to check for that — grep downstream projects for direct `mysqli_*` calls (not `Database::` methods) before flipping the flag; that's the actual audit cost per project, not a framework-side cost.
5. This is explicitly a "costly / plan for it" item, not something to rush — the interface-extraction step (1-2) is the only part worth scheduling soon; the PDO driver itself (and any project opting into it) can happen on its own timeline.

---

## 8. Config format change (`.env` / schema validation)

**Impact on existing code:** Every downstream project has a `config/magrathea.conf` in the current ini-with-repeated-environment-sections format, and `Config::Instance()->GetConfig("key")` / `GetConfigSection()` calls throughout app code. A hard format swap breaks every single deployed project's config file simultaneously — this is infrastructure, not code, so it can't be "tested by running the test suite," it fails at boot on whichever server has the old file format.

**Migration path:**
1. **Opt-in, coexisting parsers:** teach `ConfigFile` to detect format at load time — if `config/magrathea.conf` exists, use the current ini parser (unchanged, zero risk to existing deployments); if a `.env` file exists instead (or additionally), layer it on top via a new `ConfigEnv` loader, with `.env` values taking precedence for any key present in both (lets a project migrate key-by-key rather than all-at-once).
2. `Config::Instance()->GetConfig("key")`'s signature and return value don't change regardless of which backing format resolved it — call sites throughout app code need zero changes.
3. Add `Config::Validate(array $requiredKeys)` (SemVer-safe addition, opt-in call in `_inc.php`) as a separate, independent improvement — this doesn't require the `.env` migration at all and is useful immediately for catching missing-key-in-one-environment mistakes on the *current* ini format.
4. Full retirement of the ini format is a v3+ decision, and given how low the actual pain is (it works, it's just verbose), this is fairly low priority relative to the security fixes above — no urgency to force it.

---

## 9. Real typed relation getters / drop magic `__get`/`__set` array-keyed relations

**Impact on existing code:** High coupling to internals. `relations["properties"|"methods"|"lazyload"|"external"]` (src/MagratheaModel.php:41, 362-404) is read by `MagratheaModel::Get()`/`Set()` magic methods and populated by every existing `MagratheaStart()` across every existing feature in every existing project. This is the most deeply load-bearing pattern in the ORM — more so than the Base-class split, because it affects runtime property access (`$article->author`), not just file organization.

**Migration path:**
1. **Do not remove the array-keyed relations mechanism.** Instead, treat "always emit real typed getters" (`GetAuthor()`/`SetAuthor()`, already shown in the skill cookbook §3 as the expected pattern) as the actual fix — this requires **no framework change at all**, since typed getters already coexist with the magic-property mechanism today (the cookbook example has both `GetAuthor()` and the underlying magic array). The friction isn't that the mechanism exists; it's that magic-property access (`$article->author`) is documented/used as if it were guaranteed discoverable, when it isn't without reading the Base class.
2. **Documentation-only fix, ship immediately:** update the skill cookbook to state relation access should always go through the explicit `Get<Name>()`/`Set<Name>()` methods in AI-authored code, treating `$model->propertyName` magic relation access as legacy/internal. Zero migration cost, because it changes guidance, not code.
3. If you eventually want to *remove* the array-keyed magic entirely (full typed-properties rewrite), that's a v3+, feature-folder-by-feature-folder rewrite with no shortcut — every `MagratheaStart()` in every project needs its relations block translated to constructor-injected typed properties. Don't schedule this until items 1-8 are done; it's the most expensive item in the whole report for the least incremental benefit once step 1-2 here are in place.

---

## Suggested rollout order (sequencing across all 9 items)

| Phase | Items | Type | Downstream action required |
|---|---|---|---|
| Now (patch) | 2 (Clean fix), 3 (log-not-throw), 5.1 (header check) | SemVer-safe | None |
| Now (minor, additive) | 1 steps 1-5 (SQLWithParams), 7 steps 1-2 (driver interface), 8 steps 1-3 (.env coexistence + Validate) | Opt-in | None required; available immediately |
| Soon (docs-only) | 4 (single-file convention), 5.2 (dual header), 9.1-9.2 (typed getters guidance) | Docs/convention | None — guidance change only, per-feature adoption whenever a project touches that file |
| Planned (minor→major) | 3 flip to throw, 6 (admin package extraction) | Deprecation window then breaking | Fix any latent type typos; add `composer require magrathea-admin` if using the panel |
| v3 milestone | 1 step 6 (drop SQLLegacy), 7 full PDO cutover, 8 full .env cutover, 9.3 (drop magic relations) | Breaking | Per-project migration effort, scheduled deliberately, not forced |

The throughline: almost everything here can be shipped as additive/opt-in without forcing any of your 10 years of existing downstream projects to change on your timeline. The only genuinely unavoidable breaking change is #1 step 6, and even that has a two-version deprecation window (`SQLLegacy()`) built in.
