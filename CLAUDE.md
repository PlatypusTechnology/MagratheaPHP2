# CLAUDE.md — MagratheaPHP2

## Keep the documentation in sync with the code

This repo has an AI-built documentation site at `documentation/` (see
`documentation/CLAUDE.md` for its architecture). **Any change to `src/` should be
reflected there in the same session**, not left for later:

- **Method/class signatures never need manual updates** — `documentation/lib/Reflector.php`
  reads them live from `src/` via PHP Reflection on every page load. Adding, removing, or
  changing a method's signature just works automatically. Nothing to do here.
- **Narrative prose can go stale and needs a human/AI pass.** If you add, rename, move, or
  meaningfully change the behavior of a class in `src/`, check whether its narrative doc in
  `documentation/mds/*.md` (rendered on the corresponding `documentation` page)
  still describes reality — and update it if not. `documentation/lib/DocMap::Freshness()` will
  flag a page as "may be outdated" once the src file's git-commit date passes the doc's, but don't
  rely on that as a substitute for actually checking — it only catches "something changed," not
  "here's what's wrong" (see the `documentation/mds/core/config.md` rewrite from
  2026-07-20, where
  the whole file described a config system that never existed in the source — the freshness
  badge wouldn't have caught that on its own since nobody had touched `Config.php` recently).
- **New top-level classes/topics need a `lib/DocMap.php` entry** (`documentation/lib/DocMap.php`)
  or they simply won't appear in navigation/search. This is intentionally manual, not
  auto-discovered — ask before changing that.
- **`skills.MD`** (the AI-facing coding cookbook, downloadable from the site as `skill.md`) can
  also drift from real method signatures — e.g. its `Config` reading example used
  `GetConfig("db_host")` where the real API is `Get("db_host")` for environment-scoped reads.
  If you touch an area `skills.MD` has a cookbook section for, sanity-check that section
  against the actual current source too, not just against `documentation/mds/`.

When in doubt about whether a code change is "significant enough" to need a doc update, ask
Paulo rather than skipping it or over-editing — same standing rule as
`documentation/CLAUDE.md`: always ask before implementing, and be critical of any
new idea or change.
