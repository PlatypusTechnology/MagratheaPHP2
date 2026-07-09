<!-- title: MagratheaPHP2 for AI-Assisted Coding — Detailed Report -->

# MagratheaPHP2 — Detailed Technical Report for AI-Native Development

Scope: `/mnt/Rincewind/MagratheaPHP2` (framework source, `src/`), read against the `magrathea-php2` skill cookbook (which encodes current conventions for downstream apps). Goal: identify what to change so an AI agent (no admin-panel code generator) can write correct, secure feature code by reading one or two files at a time.

---

## 1. Current architecture, in one paragraph

`MagratheaPHP` (`src/MagratheaPHP.php`) is a fluent bootstrap singleton that wires autoloading, config, DB connection, and session. Models (`MagratheaModel`, `src/MagratheaModel.php`) are active-record-style classes with magic `__get`/`__set`, a `$dbValues` type map, and a generated/hand-split `Base` + concrete class pair. `MagratheaModelControl` provides static query helpers per model. `Query` (`src/DB/Query.php` + `QueryInsert`/`QueryUpdate`/`QueryDelete`) is a fluent SQL builder. `Database` (`src/DB/Database.php`) wraps `mysqli` directly — no PDO, no DBAL. `MagratheaApi`/`MagratheaApiControl`/`MagratheaApiAuth` provide routing + JWT auth (backed by `firebase/php-jwt`, already a Composer dependency). The Admin panel (`src/Admin/*`) is a full server-rendered CRUD/config/code-generation GUI, including `CodeCreator.php` and `ObjectManager.php`, which write the generated `Base` model/control files from DB schema introspection — this is the piece the user says they no longer need, since Claude now writes that code directly.

---

## 2. Security findings

### 2.1 SQL injection — `Query::BuildWhere()` (src/DB/Query.php:563-575)
```php
public static function BuildWhere($arr, $condition){
    ...
    $whereSql .= " `".$field."` = '".$value."' ";
    ...
}
```
Every value passed through `->Where([...])`, `->WhereArray([...])`, `->W(...)`, and therefore every `ArticleControl::GetWhere([...])` call documented as the *standard, recommended* pattern in the skill cookbook (§4 "Using a control"), is interpolated into the SQL string with **no escaping whatsoever**. `$field` (the array key) is also interpolated unescaped. Contrast this with `MagratheaModel::Insert()`/`Update()`/`Delete()` (src/MagratheaModel.php:253-345), which correctly use `PrepareAndExecute()` with `?` placeholders and `mysqli::bind_param`. The framework is internally inconsistent: the low-level ORM write path is safe, the high-level read/query path is not. Any controller code an AI (or human) writes using `GetWhere(["email" => $_GET["email"]])` — which is exactly what the skill's own cookbook teaches — is a SQL injection vector.

**Fix:** rewrite `BuildWhere` to emit placeholders + a parallel values array, and thread that through to `Database::PrepareAndExecute`. This is a breaking change to the `Query` public API (return shape of `SQL()` today is a single string) — plan it as a v3 change or add a parallel `SQLWithParams()`.

### 2.2 Weak escaping helper — `Query::Clean()` (src/DB/Query.php:97-101)
```php
static public function Clean($query): string{
    $query = str_replace("'", "\'", $query);
    $query = str_replace('"', '\"', $query);
    return $query;
}
```
This is not `mysqli_real_escape_string`. It doesn't handle backslashes, NUL bytes, or multi-byte charset injection tricks (GBK-style). It's also documented in the skill cookbook (§5) as the go-to sanitizer for user input dropped into raw SQL strings ("Always use `Query::Clean()` ... for user input"). This function should either call `mysqli_real_escape_string()` against the live connection, or be deprecated entirely in favor of forcing `PrepareAndExecute()` everywhere.

### 2.3 `Database::PrepareAndExecute` swallows errors silently (src/DB/Database.php:513-516)
```php
} catch(MagratheaDBException $ex) {
    echo "got error! ";
    $ex->SetData($query, $args);
    return null;
}
```
Two problems: (a) `echo` inside a library method leaks raw error text into API/HTML responses regardless of caller context — a minor info-disclosure and a correctness bug (breaks JSON API responses by injecting stray text before headers/body); (b) the exception is caught and discarded (`return null`) rather than re-thrown, so calling code (including `MagratheaModel::Insert/Update/Delete`) cannot distinguish "0 rows affected" from "the query actually failed." An AI generating error-handling code around `->Save()` will not have a reliable signal to work with.

### 2.4 Config secrets: pattern is fine, storage is not enforced (src/ConfigFile.php:89-100)
The `$=ENV_VAR` convention (documented in skill §2) is a reasonable pattern, but nothing in `ConfigFile::GetConfig()` actually resolves `$=` prefixed values to environment variables — I did not find that resolution logic in `ConfigFile.php` itself (worth confirming in `Config.php`/`ConfigApp.php`, whichever wraps it, since `GetConfig()` here returns the raw ini value verbatim). If that resolution lives elsewhere, fine; if it doesn't exist, the documented pattern is a no-op and secrets are being committed as ini values from `[production]` sections directly, since there is no `.gitignore`-level distinction between `config/magrathea.conf` and other app files enforced by the framework itself.

### 2.5 Password hashing — not found in core
No `password_hash`/`password_verify` wrapper exists in `src/` outside of `Admin/Features/User/AdminUserControl.php` (not fully read in this pass, but referenced from `MagratheaApiAuth::AdminUserLogin`). The skill cookbook's JWT example (§8) shows `password_verify()` used ad hoc in application code, meaning every downstream app re-implements password handling instead of it being a one-line framework call. Low severity but a repeated-mistake surface.

### 2.6 JWT: reasonably solid, one footgun
`MagratheaApiControl` (not fully quoted above, see `jwtEncode`/`jwtDecode`, lines ~119-130) correctly delegates to `firebase/php-jwt`'s `JWT::encode`/`JWT::decode` rather than hand-rolling JWT — good. But `GetSecret()` is meant to be overridden per-app (skill §8: "Always override `GetSecret()` to use a config value"); the base class implementation's default return value should be audited to confirm it doesn't silently return an empty string or a hardcoded default that a careless AI-generated `AuthControl` could leave unoverridden in production.

### 2.7 Multi-statement query splitting (`Database::SplitQueries`, src/DB/Database.php:233-268)
Custom hand-rolled SQL statement splitter that respects quoted strings. This exists to support `ImportFile`/migrations, which is reasonable, but it's exactly the kind of bespoke parsing logic that's easy to get subtly wrong (e.g., doesn't appear to handle SQL comments `-- ...` or `/* ... */` containing semicolons). Not directly an AI-friction issue, but worth a fuzz test since it's on the SQL-execution path.

---

## 3. AI-agent friction findings

### 3.1 The generated `Base` class split is now pure ceremony
Skill §3 documents the convention: `Article/Base/ArticleBase.php` (generated, holds `$dbTable`/`$dbPk`/`$dbValues`/relations) + `Article/Article.php` (concrete, business logic). The *only* reason for this split is to protect hand-written business logic from being clobbered when `CodeCreator`/`ObjectManager` (src/Admin/CodeCreator.php, src/Admin/ObjectManager.php) regenerate the Base file from a DB schema diff. If the AI is now the one authoring both files by hand, this split adds:
- 2x the files to read for full context on one table,
- a `## FILE GENERATED BY MAGRATHEA` comment that is now a lie and will bias an AI toward *not* editing the file, or worse, hunting for a "generator" that no longer needs to run,
- a naming/namespace duplication tax (`App\Models\Base\ArticleBase` vs `App\Models\Article`, `App\Controls\Base\ArticleControlBase` vs `App\Controls\ArticleControl`) with zero behavioral benefit once nothing auto-regenerates it.

**Recommendation:** collapse to one file per feature (`Article.php` with `$dbTable`/`$dbValues`/relations/business methods all in one class), and one file per control (`ArticleControl.php` with static helpers). Keep the Base-class split only in genuinely legacy apps that still rely on the admin generator; don't propagate it as the default pattern going forward.

### 3.2 Magic property access, `__get`/`__set` (src/MagratheaModel.php:355-430)
`Get()`/`Set()` branch on three different backing stores — `dbValues` real properties, `dbAlias` aliases, and `relations["properties"]` lazy-loaded related objects — with no static typing or IDE-visible property list beyond what's declared as `public $id, $title, ...`. An AI reading `Article.php` alone cannot know a property like `$author` (a relation) is valid without also reading the Base class's `MagratheaStart()` to see `$this->relations["properties"]["Author"]`. This is non-local reasoning by construction. It works fine for a human who wrote the framework and remembers the convention; it's exactly the kind of thing that causes an LLM to either invent a getter that doesn't exist or fail to use a lazy relation that does.

**Recommendation:** either (a) generate real typed getter/setter methods per relation (already partially done — `GetAuthor()`/`SetAuthor()` exist in the skill's example, src cookbook §3 — so lean into *always* emitting these and treat `$model->author` magic access as deprecated/internal only), or (b) move to PHP 8.1+ readonly/typed properties with explicit relation objects, dropping the array-keyed `relations["properties"|"methods"|"lazyload"|"external"]` structure (src/MagratheaModel.php:41) entirely in favor of method calls.

### 3.3 String-typed "type system" (`$dbValues["field"] = "int"|"string"|"text"|...`)
`GetDataTypeFromField()` (src/MagratheaModel.php:436-453) maps Magrathea's own type strings to a second, different set of type strings used by `PrepareAndExecute`'s bind-type switch (src/DB/Database.php:481-495: `"int"`, `"boolean"` → `i`; `"float"` → `d`; else → `s`). Three different type vocabularies exist across the codebase: PHP native types, the `$dbValues` Magrathea type strings (`int`, `boolean`, `string`, `text`, `float`, `datetime`, `uuid`), and the SQL-bind single-char types (`i`, `d`, `s`). An AI has to hold all three mappings in its head simultaneously and get the middle one exactly right per the skill's "Supported field types" table (§3) — a single typo (e.g. `"integer"` instead of `"int"`) silently falls through `GetDataTypeFromField`'s switch with no `default` case, returning `null`, and downstream breaks non-obviously.

**Recommendation:** add a `default: throw new MagratheaModelException(...)` to `GetDataTypeFromField` at minimum (cheap, high value — turns a silent bad-write into a loud, fixable error at dev time, exactly the kind of feedback an AI agent needs to self-correct). Longer term, use native PHP enums (`_EnumTrait.php` already exists in the codebase — src/_EnumTrait.php — so there's prior art) instead of bare strings for `dbValues` types.

### 3.4 Naming inconsistency: casing and verb style
Method names mix PascalCase-for-everything (`GetById`, `GetPkName`, `ToArray`) with the occasional lowercase-first outlier (`queryRow` used at src/MagratheaModel.php:161, vs `QueryRow` — capital Q — used at src/MagratheaModel.php:172, both calling the same `Database::Instance()` method). This is a real bug risk, not just a style nit: PHP method names are case-insensitive so both compile, but grepping for `QueryRow` to understand call sites will miss the `queryRow` call, and an AI pattern-matching off existing code has a 50/50 chance of picking the wrong casing for new call sites, propagating the inconsistency.

**Recommendation:** one method-naming convention (Pascal for public API, as almost everything already is), enforced by a lint rule (PHP_CodeSniffer custom sniff) that AI-authored diffs are checked against pre-commit.

### 3.5 Config format is bespoke and env-repeating
`config/magrathea.conf` uses ini sections keyed by *environment name* (`[dev]`, `[production]`), each repeating the full flat key set (skill §2 example shows this explicitly — no override/inheritance). This is copy-paste-prone (a key added to `[dev]` and forgotten in `[production]` fails silently — `ConfigFile::GetConfig()` just returns `null`/empty for a missing key, no validation). An AI editing config for a new feature has to remember to touch N environment sections, and nothing catches a miss.

**Recommendation:** either move to `.env` + a schema-validated config loader (fits AI workflows better — dotenv is universally known), or add a `Config::Validate(array $requiredKeys)` call to bootstrap that throws on missing keys across all declared environments.

### 3.6 `IncludeAllModels()` directory-scan autoloading (src/MagratheaModel.php:458-471)
Manually opens a `Models` folder and `include_once`s every `.php` file in it — parallel, redundant path to the `AddCodeFolder`/`AddFeature` PSR-4-ish autoload registration the current skill teaches (skill §1). Two different "how do I make my model visible" mechanisms exist in the same framework. An AI is likely to only discover one of them (whichever the skill/cookbook it was given documents — currently `AddFeature`), and may not realize `IncludeAllModels()` exists or is unused/legacy, leading to confusion if it's encountered in an older app.

**Recommendation:** confirm `IncludeAllModels()` is dead code in current-generation apps and delete it, or clearly deprecate with a docblock pointing at `AddFeature`.

### 3.7 The Admin panel's code generator is a large, separate mental model
`src/Admin/CodeCreator.php`, `src/Admin/ObjectManager.php`, plus ~15 view files under `src/Admin/views/pages/objects-*.php` and `src/Admin/views/actions/object-*.php` implement a full "point-and-click define your DB schema, generate PHP" tool. This is the functionality the user explicitly said they no longer need — Claude fills this role now by reading the DB schema (or being told the shape) and writing the Base+concrete files directly. Keeping it:
- adds a second source of truth for "what does a valid generated model look like" (the PHP templates baked into `CodeCreator`) that can drift from what the skill cookbook teaches the AI to write by hand,
- is a meaningful chunk of the codebase (generator logic + ~15 admin view files) with no test coverage found in `src/Tests/` for this specific path,
- still writes the "FILE GENERATED BY MAGRATHEA" header (see skill cookbook §3), which — if this tool is ever run again on a project an AI is also hand-maintaining — will silently clobber AI-authored business logic that happens to live in a file the generator thinks it owns.

**Recommendation:** see §5 below (dedicated section, since the user asked specifically about this).

---

## 4. Class-by-class notes (core, condensed)

| Class | File | Responsibility | Notes |
|---|---|---|---|
| `MagratheaPHP` | MagratheaPHP.php | Bootstrap singleton, fluent config of autoload/DB/session | Wide surface, acceptable — this is the intended single entry point |
| `MagratheaModel` | MagratheaModel.php | Active-record ORM base | Magic property access (§3.2), mixed-safety query paths (safe writes, unsafe reads via Query) |
| `MagratheaModelControl` | MagratheaModelControl.php | Static query helpers per model | Thin wrapper over `Query`/`Database`; inherits `BuildWhere` injection risk |
| `Query` / `QueryInsert` / `QueryUpdate` / `QueryDelete` | DB/Query.php, DB/Query{Insert,Update,Delete}.php | Fluent SQL builder | `SQL()` returns raw interpolated string; no parameter binding in the builder itself (§2.1) |
| `Database` | DB/Database.php | Thin `mysqli` wrapper, singleton | No PDO/DBAL; connection opened/closed per query call (`OpenConnectionPlease`/`CloseConnectionThanks` called every single `Query*` call — see 2.7/perf note below); error handling inconsistent (§2.3) |
| `DatabaseSimulate` | DB/DatabaseSimulate.php | Mock DB for tests | Good — enables `Database::Instance()->Mock()` per skill §16 |
| `Config` / `ConfigFile` / `ConfigApp` | Config.php / ConfigFile.php / ConfigApp.php | Config loading | Bespoke ini-with-repeated-sections format (§3.5) |
| `MagratheaApi` | MagratheaApi.php | Route table / dispatcher | Clean pattern already (class-based route grouping, per skill §6) |
| `MagratheaApiControl` | MagratheaApiControl.php | Base API controller, JWT helpers | Delegates to `firebase/php-jwt` correctly (§2.6) |
| `MagratheaApiAuth` | MagratheaApiAuth.php | Admin-user JWT login/refresh flows | Reasonable, tied specifically to `AdminUser` — not generic enough to reuse for app-level auth without subclassing |
| `MagratheaCache` | MagratheaCache.php | File-based cache | Not deeply reviewed this pass |
| `Logger` / `Debugger` | Logger.php / Debugger.php | Prod logging / dev debugging | Clear split, fine |
| `Uuid` | Uuid.php | UUIDv7 generation | New (2.1.30), clean addition — a good model for how new features should be shipped (opt-in type string, no ceremony) |
| `Singleton` | Singleton.php | Base singleton pattern | Used pervasively — see §6 below on testability implications |
| `Admin\*` (CodeCreator, ObjectManager, AdminCrudObject, etc.) | Admin/*.php | Full admin GUI: CRUD, config editor, code generator, API explorer, user management | Large surface; code-generation portion is the one the user wants to reconsider (§5) |

---

## 5. On the code generator specifically (what you asked about directly)

Yes — there is a real, dedicated generator: **`src/Admin/CodeCreator.php`** (builds the PHP source for Base model/control classes from a DB-schema-derived structure) plus **`src/Admin/ObjectManager.php`** (manages the "objects" — i.e. table/model definitions — that `CodeCreator` reads from), fed by admin UI at `src/Admin/views/pages/objects-create.php`, `objects-edit.php`, `objects-config.php`, and actions under `src/Admin/views/actions/object-*.php`. This is the "auto-write objects class code" feature you no longer need.

Given that:
- **Keep, unmodified, for old projects still running it** — don't break existing apps that depend on the wizard.
- **Stop teaching it as the default path going forward.** The skill cookbook I was given for this session already does this correctly — it teaches hand-authored feature folders, not the generator. Good; that's already aligned with your stated goal.
- **Consider extracting it into an optional Composer package** (`magrathea/admin-codegen` or similar) rather than deleting it outright — it's real, working code with no clear replacement need to be destroyed, just decoupled from the framework's critical path. This also shrinks the core framework's surface area, which directly helps AI-agent context budgets (less to read to understand "what is Magrathea").
- **If you do keep it in-core:** remove the "FILE GENERATED BY MAGRATHEA... changes can be overwritten" header from newly-scaffolded files, or make it conditional/config-driven, since in an AI-authored codebase that header is now false and actively harmful (§3.7's clobber risk).

---

## 6. Testability / global state note

`Singleton` (src/Singleton.php) underlies `Database`, `Config`, `Debugger`, `Logger`, `MagratheaPHP` itself. `DatabaseSimulate` exists specifically to work around this for tests (skill §16: `Database::Instance()->Mock()`). This is a reasonable pragmatic pattern already, but worth flagging: every one of these singletons is *implicit global mutable state* that an AI-generated unit test has to remember to reset/mock explicitly, and there's no single "reset everything" helper found in this pass — each test file presumably does it ad hoc. A `Magrathea2\Testing\ResetAllSingletons()` helper would reduce a whole class of "test passes alone, fails in the suite" flakiness that's expensive for an AI to debug (it looks like a logic bug, not a state-leak bug, from inside a single file).

---

## 7. Vendor dependencies already available (composer.json) — use these instead of hand-rolling

- `firebase/php-jwt` (^6.10||^7.0) — already used correctly for JWT (§2.6); no action needed, just don't let downstream apps re-implement JWT parsing.
- `phpmailer/phpmailer` (^6.9.1) — backs `MagratheaMailSMTP`; fine as-is.
- `scssphp/scssphp`, `twbs/bootstrap`, `components/jquery`, `tedivm/jshrink` — all admin-panel-asset-pipeline dependencies. If the admin code-generator UI is extracted per §5, these four dependencies move with it, shrinking core framework dependency footprint by more than half.

No PDO/Doctrine/DBAL dependency exists — the hand-rolled `mysqli` wrapper (`Database.php`) is 100% bespoke. This is the highest-leverage "replace with something the AI already knows" candidate in the whole codebase, per the simple report's "costly changes" section.

---

## 8. Priority ranking (my actual recommendation, if you want just one ordered list)

1. Fix `BuildWhere`/`Clean` SQL injection (§2.1, §2.2) — security, cheap, urgent.
2. Add `default: throw` to `GetDataTypeFromField` (§3.3) — one line, prevents silent data corruption.
3. Collapse Base+concrete split to single-file features going forward (§3.1) — biggest AI-ergonomics win, no framework code change required, just a convention change (already reflected in your skill file — good).
4. Write the "AI contract" doc (simple report) — cheap, compounding.
5. Decide the fate of the Admin code generator (§5) — not urgent, but worth a deliberate decision rather than letting it silently bit-rot.
6. Everything else (config format, DB abstraction swap, magic-property removal) — plan as a v3 milestone, not a sprint.
