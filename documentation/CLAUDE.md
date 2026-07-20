# CLAUDE.md — documentation

Guide for your future self working on this folder. This is a from-scratch documentation
site for MagratheaPHP2, replacing the old `docs/`, `docs-cache/`, and `.phpdoc/` folders
(deleted 2026-07-20). The old repo-root `documentation/` folder was renamed to
`mds/` and moved inside this folder on the same date — it is no longer a
sibling of `documentation/`, it now lives at `documentation/mds/`.

## General instructions from Paulo

- If any questions arise, ALWAYS ask before implementing.
- Be highly critical of any new ideas or changes or new implementations. Don't mind
  hurting his feelings.
- Any change to `src/` should be reflected here in the same session — see the repo-root
  `../CLAUDE.md` for what that means in practice (signatures are automatic via reflection,
  narrative prose is not and needs a manual check).

## What this is

A PHP-rendered (not static-build) documentation website. No framework, no Magrathea
dependency for the site itself, no composer packages added — deliberately dependency-free
so it stays trivial to serve (`php -S` or any web server pointed at this folder) and easy
to delete/replace later without touching the main framework's composer.json.

## Architecture decisions and why

- **Two data sources, kept deliberately separate:**
  - **Narrative prose** comes from `mds/*.md` (hand-curated, may go stale).
  - **Method/class signatures** come from live PHP Reflection over `src/` (`lib/Reflector.php`).
    This was the resolution to Paulo's concern that `mds/` might be out of date:
    rather than trust the prose for facts that can drift, signatures are always regenerated
    from the actual current code on every request. Only the narrative text can go stale, and
    when it does, `lib/DocMap::Freshness()` compares the doc file's last git-commit date
    against its mapped src file(s)' last-commit dates and shows an "may be outdated" badge —
    see the `freshness-note` block in `views/page.php`. If you rewrite this staleness check,
    keep the underlying signal (git commit dates, not filesystem mtimes — mtimes aren't
    reliable across clones/deploys).
  - `lib/DocMap.php` is the hand-maintained map tying `mds/*.md` files to their
    `src/*.php` counterparts (parsed from each doc's `**File:**` line where present, filled
    in manually for multi-file topics). If Paulo adds a new `mds/*.md` file or a
    new top-level src class, this map needs a manual entry — it is NOT auto-discovered
    (ask before changing that to be automatic; explicit was the deliberate choice so nothing
    silently appears in nav in a broken half-state).

- **`DocMap::HasClass()` guard is load-bearing, don't remove it.** Composer's PSR-4
  classloader does a bare `include()` (not `include_once`) when it resolves a class file
  that turns out to not actually contain a class. `src/_Functions.php` is procedural (global
  functions, no class) but its name would otherwise resolve as if it were class
  `Magrathea2\_Functions`. Calling `ReflectionClass("Magrathea2\_Functions")` triggers that
  bare `include`, and since `_Functions.php` is *also* loaded once already via composer's
  `files` autoload, PHP fatals with "Cannot redeclare function". `DocMap::HasClass()` regex-
  checks a src file actually declares `class|interface|trait` before anything calls
  `Reflector::ReflectClass()` on it. Any new code path that reflects an FQCN derived from a
  `src/` path must go through this guard first.

- **Markdown parser is hand-rolled** (`lib/MarkdownParser.php`), not a composer dependency.
  It only implements what `mds/*.md` actually uses (headers, fenced code, tables,
  lists, blockquotes, bold/italic/code/links, hr) — it is NOT a general CommonMark
  implementation. If you extend it, know that table-cell splitting must respect
  backslash-escaped pipes (`\|`) — several docs use `type\|null` union types inside table
  cells, and a naive `explode("|", ...)` silently truncates the row (this bit us once, see
  `TableRow()`).

- **PHP code highlighting uses `token_get_all()`**, not regex. An earlier regex-chain version
  broke because CSS class names it inserted (e.g. `tok-string`) contain keyword substrings
  (`string`) that a later regex pass would re-match and corrupt. Tokenizing first sidesteps
  that whole bug class. Don't go back to regex-chain highlighting.

- **Examples (`data/examples.php`) are curated by hand**, not extracted from skills.MD
  programmatically. Some are copied/adapted from `skills.MD`'s existing cookbook (tagged
  `"source" => "skills.MD"`), others are original to this site only (tagged `"original"`)
  and were deliberately NOT added back into skills.MD — skills.MD is the AI-facing coding
  cookbook, this site's examples are eye-candy/discoverability, keep those concerns separate
  unless Paulo asks otherwise.

- **`skill.md` download**: the actual file in the repo is `skills.MD` (capital MD, plural).
  `download.php` renames it to `skill.md` on download per Paulo's original phrasing — this
  is intentional, not a typo to "fix".

## Things to ask about, don't just decide

- Any change to `lib/DocMap.php`'s category structure/ordering (it mirrors
  `mds/index.md` by design — ask before diverging from that mental model).
- Adding any composer dependency to make this "nicer" (e.g. a real Markdown/CommonMark
  library, a JS framework, a highlighting library). The whole point so far has been zero
  new dependencies for a tool that's meant to be disposable/replaceable.

## Known rough edges (not fixed, flagged for a future pass — ask before fixing)

- No pagination/lazy-loading on `?p=all-classes` — it lists all ~200 classes on one page.
  Fine today, could get slow if the codebase grows a lot.
- `search-index.php` caches to `cache/search-index.json` for 5 minutes; there's no cache
  invalidation on deploy — a stale index could show links to renamed anchors until the
  cache file ages out or is deleted manually.
- No automated tests. Verification so far has been manual (`php -l` on every file + browser
  screenshots of representative pages).
