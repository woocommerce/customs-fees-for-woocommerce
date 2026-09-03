## CRITICAL Rules

- Never modify `vendor/`, `node_modules/`, or minified assets (`*.min.js`, `*.min.css`).
- No autoloading or namespaces -- the plugin uses `CFWC_` prefixed classes with manual `require_once`. Do not introduce PSR-4 or Composer autoloading.
- All PHP classes must have the `ABSPATH` guard at the top: `if ( ! defined( 'ABSPATH' ) ) { exit; }`.
- Minified assets are built from source files -- edit only the non-minified versions in `assets/`.
- No external API calls. All customs fee calculations happen server-side with zero network dependencies.
- WooCommerce is a hard dependency declared via `Requires Plugins: woocommerce`.
- Never manually bump the plugin version.
- State the backward-compatibility impact in the PR description when changing any public or externally exposed surface -- see [Backward Compatibility](#backward-compatibility).

## Project Knowledge

**Plugin**: Customs Fees for WooCommerce
**Purpose**: Calculate and display customs/import fees at WooCommerce checkout based on product origin, destination country, HS codes, and category rules.
**Version**: see the `Version` header in `customs-fees-for-woocommerce.php` (bumped by release tooling)

### Stack

| Layer | Tool |
|-------|------|
| PHP | >= 7.4 (no namespaces, `CFWC_` prefix convention) |
| WordPress | see `Requires at least` / `Tested up to` headers in `customs-fees-for-woocommerce.php` |
| WooCommerce | see `WC requires at least` / `WC tested up to` headers in `customs-fees-for-woocommerce.php` |
| Node | 22.14.0 (pinned in `.nvmrc`) |
| Package manager | pnpm >= 10.4.1 |
| JS minification | UglifyJS (`uglify-js`) |
| CSS minification | clean-css-cli |
| i18n | node-wp-i18n |
| Static analysis | PHPStan level 2 with `phpstan-wordpress` + `woocommerce-stubs` |
| Release packaging | `composer archive` |

### Key Directories

```
includes/               # All PHP classes
includes/admin/         # Admin-only classes (loaded conditionally via is_admin())
includes/admin/views/   # Admin view templates (rules-section.php)
assets/css/             # Source CSS (admin.css, admin-improvements.css, frontend.css)
assets/js/              # Source JS (admin.js, frontend.js)
languages/              # POT file only; translations are not auto-loaded (see Empty load_textdomain() note)
docs/                   # Developer docs: CIF.md, HOWTO_DEBUG.md, QUICK_START.md, TESTING.md
.github/workflows/      # CI: QIT tests, build, release, merge-to-trunk
```

### Architecture Notes

- **Singleton pattern**: `Customs_Fees_WooCommerce` (main) and `CFWC_Loader` use `::instance()`.
- **Loader class** (`CFWC_Loader`) handles all dependency loading, class init, and hook registration.
- **Calculator** (`CFWC_Calculator`) is the fee engine -- called via `woocommerce_cart_calculate_fees`.
- **Rule Matcher** (`CFWC_Rule_Matcher`) matches cart items to configured rules.
- **Settings** live under WooCommerce > Settings > Tax > Customs Fees.
- **Fee breakdown** stored in `WC()->session` for display in cart/checkout.
- **Product meta**: `_cfwc_hs_code` and `_cfwc_country_of_origin` on postmeta.
- HPOS and Cart/Checkout blocks compatibility declared.

## Commands

```bash
# Install PHP dependencies (dev)
composer install

# Install PHP dependencies (production)
composer install --no-dev -o

# Install JS dependencies
pnpm install

# Build for production (install, minify, i18n, archive)
pnpm build

# Build for development (install, minify, i18n -- no archive)
pnpm build:dev

# Minify JS only
pnpm uglify

# Minify CSS only
pnpm cleancss

# Generate POT file
pnpm makepot

# Run PHPStan
vendor/bin/phpstan analyse

# Lint PHP (PHPCS)
# Not yet configured -- no .phpcs.xml in repo

# Run PHP unit tests (PHPUnit, config in phpunit.xml.dist, suites in tests/Unit/)
composer test

# Start wp-env
npx wp-env start

# Stop wp-env
npx wp-env stop

# Clean wp-env (removes containers + data)
npx wp-env clean all

# Run WP-CLI inside wp-env
npx wp-env run cli wp plugin list

# View wp-env logs
npx wp-env logs
```

## Conventions

### Branches

- `trunk` -- stable, release-ready code.
- Feature/fix branches: `add/CUSFEES-*`, `tweak/CUSFEES-*`, `fix/CUSFEES-*`.

### Pull Requests

- Reference the issue: `Closes #`.
- PR template at `.github/PULL_REQUEST_TEMPLATE.md` requires: description, changes list, test instructions, and a checklist (i18n, caching, hooks, logs).
- Every PR must include test instructions reproducible by a reviewer.

### Changelog

- Three changelog locations: `readme.txt`, `changelog.txt`, and `README.md`.
- Format in `changelog.txt`: `YYYY-MM-DD - version X.Y.Z` followed by `* Type - Description.`
- Types: `Add`, `Fix`, `Tweak`, `Update`, `Dev`.

## E2E Testing

QIT (Quality Insights Toolkit) E2E tests run remotely via `.github/workflows/qit.yml` but there are no local E2E tests or Playwright config yet.

## Architectural Decisions

- **No namespaces by design**: The plugin predates namespace adoption and uses the `CFWC_` prefix convention consistently. Do not refactor to namespaces without explicit approval.
- **Manual require_once loading**: `CFWC_Loader::load_dependencies()` handles all includes. Admin classes are conditionally loaded behind `is_admin()`.
- **Empty `load_textdomain()`**: The main plugin class does not call `load_plugin_textdomain()`. This plugin is not hosted on WordPress.org, so the WordPress 4.6+ just-in-time auto-loading that covers .org-hosted plugins does not apply here. Without a `load_plugin_textdomain()` call, only translations installed globally in `WP_LANG_DIR/plugins/` are found; bundled `.mo` files in `languages/` are not loaded. This is currently harmless because `languages/` ships only a `.pot` and no `.mo` files, but the first shipped translation would be silently ignored. Whether to implement `load_plugin_textdomain()` is a separate decision (own ticket).
- **Stub AJAX handlers in Loader**: `ajax_save_rules`, `ajax_delete_rule`, `ajax_load_template` in `CFWC_Loader` are stubs. Actual AJAX handling is in `CFWC_Ajax`.
- **composer archive for packaging**: Release ZIPs are built via `composer archive` with exclusion rules in `composer.json`. The `.gitattributes` file also controls `export-ignore`.
- **Rules dual storage**: Rules are stored via `cfwc_rules` option and cached via `cfwc_rules_cache` transient.

## Backward Compatibility

Any change to a **public or externally exposed** surface is **high-risk** and **must state its backward-compatibility impact in the PR description**. This plugin has no namespaces (by design -- see Architectural Decisions): every `CFWC_*` class is global, and only `Customs_Fees_WooCommerce` is `final`. A `private` member is internal; a `public` or `protected` method on a loaded class is reachable -- and subclassable -- by anything else on the site. When in doubt, assume the surface is consumed and state the impact.

**Deprecate, don't rename.** Never rename or remove an existing public symbol (class, method, hook, option key, meta key, AJAX action, script handle) in place. Mark the old one `@deprecated`, introduce the replacement alongside it, and keep both working through a deprecation window so external consumers and already-stored data can migrate.

> This rule exists because WooCommerce 10.9.0 was reverted on WP Cloud: a required method added to a published contract fataled every older extension that implemented it. [Core's AGENTS.md Backward Compatibility section](https://github.com/woocommerce/woocommerce/blob/trunk/AGENTS.md#backward-compatibility) carries the same guardrails.

### The compatibility surface is wider than PHP signatures

- **Hooks and filters are public contracts.** The `cfwc_*` filters (`cfwc_calculated_fees`, `cfwc_calculated_single_fee`, `cfwc_product_origin`, `cfwc_customs_value`, `cfwc_fee_label`, `cfwc_rules_for_country`, `cfwc_include_shipping_in_calculation`, and the rest) plus the `cfwc_cache_cleared` action are this plugin's entire customization surface. Removing one, renaming it, or removing/reordering its arguments breaks every attached callback, and changing *when* or *whether* one fires breaks consumers that depend on its timing. Additive is the safe path: append new arguments at the end, never remove or reorder existing ones. Retire a hook via `apply_filters_deprecated()` / `do_action_deprecated()` for a deprecation window instead of deleting it.
- **Never trust data that flows through hooks.** Keep hook callback parameters untyped and validate or coerce received values before they reach strictly typed code, since any callback can receive a value another one produced. And validate a filter's final return before using it: `cfwc_calculated_fees` returns whatever the last callback produced, and `CFWC_Loader::add_customs_fees()` feeds each entry's `label`/`amount`/`taxable`/`tax_class` straight into `WC()->cart->add_fee()` -- a malformed entry corrupts checkout totals rather than failing visibly.
- **Overridable classes are contracts too, including which internal methods get called.** Every `CFWC_*` class except the main singleton can be subclassed and its `public` or `protected` methods overridden. Adding a fast path that skips an overridable method silently disables a subclass's override even though no signature changed: the override simply stops running. When optimizing such a class, keep overridable methods invoked on every code path, or treat the change as breaking and state it in the PR.
- **Registered script and style handles are public contracts.** Third-party code can enqueue `cfwc-admin` and `cfwc-frontend`, list them as dependencies, or dequeue them, and admin JS can read the objects localized onto `cfwc-admin`. Renaming a handle breaks those consumers. To rename with a compatibility window, register the legacy handle as an alias that depends on the new handle (the pattern WordPress core uses for `jquery` -> `jquery-core`); do not register the same file under both handles, or pages with mixed consumers load it twice.
- **AJAX action names are a public surface.** The `wp_ajax_cfwc_*` actions (`save_rules`, `delete_rule`, `import_rules`, `test_calculation`, and the rest) can be called directly by third-party code, not only by our own admin JS. Renaming one, or changing a request or response payload shape, breaks those callers with no compile-time signal.
- **Persisted data is a contract with past versions of ourselves.** The `cfwc_rules` option's row shape, the `_cfwc_hs_code` / `_cfwc_country_of_origin` product meta, the `_cfwc_fees_breakdown` order meta (written via `$order->update_meta_data()`, so HPOS-safe -- keep it that way), the `cfwc_version` and migration-flag options, and the `cfwc_rules_cache` transient all sit on live sites. Renaming a key or changing a stored shape orphans that data; a rename needs a migration or a read-time fallback, never a bare rename.
- **Do not assume global state.** Fee calculation runs from classic checkout, Store API block checkout (`CFWC_Blocks`), and admin AJAX, and admin classes load only behind `is_admin()`. `WC()->session`, `WC()->cart`, and admin-only classes are not available in every context a new code path can reach (cron, CLI, REST). Guard the exact dependency explicitly (`class_exists`, `isset`, `did_action`) and verify `WC()` components are initialized before dereferencing them.
- **Do not assume single-site or a standard install layout.** Options here are site-scoped (`get_option`), so each network site holds its own rules -- keep it that way or state the migration. Never build paths or URLs by concatenation from the domain root; derive them (`plugin_dir_url( __DIR__ )`, `plugins_url()`), as the existing enqueues do.

### Database migrations

`Customs_Fees_WooCommerce::maybe_run_migrations()` is the migration runner and has two invariants:

- **One-shot and keyed.** Each migration is guarded by its own flag option written with an atomic `add_option()` (`cfwc_rules_migrated_perrule_valuation` is the pattern), so it runs at most once per site even under concurrent requests. A new migration gets a new flag -- never reuse one, since sites that updated past a flag never re-run it. `cfwc_version` is kept aligned with the running release so future migrations can also gate on `version_compare()`.
- **Reversible one version back.** A rollback to the previous release must not fatal or corrupt: the old code will read the already-migrated `cfwc_rules` rows. Prefer additive shape changes that leave the old keys readable for one release over in-place deletions, and remember to `delete_transient( 'cfwc_rules_cache' )` after rewriting rules.

### Before changing any public or externally exposed surface (agent checklist)

1. Identify the contract you are touching: signature, overridable method (and whether it still gets called), hook, a filtered value's shape, script/style handle, AJAX action, option/meta/transient key, migration flag, global/scope expectation, site topology, or install layout.
2. Assume unseen consumers. You cannot enumerate third-party code or the data already sitting in live databases; if the surface is reachable from outside this plugin, someone consumes it.
3. Prefer the additive path (new optional argument, appended hook argument, new symbol plus deprecation, read-time fallback for a renamed key) over changing what exists.
4. State the impact in the PR description: what changed, who could consume it, and why it is safe or what the deprecation and migration path is.
5. If you cannot establish the impact, stop and flag it to the user as needing review.

## Common Pitfalls

- **Missing escaping**: All user-facing output must use `esc_html()`, `esc_attr()`, `wp_kses()`, etc. The plugin renders rule names and labels in admin and frontend.
- **Nonce verification**: All AJAX handlers and form submissions must verify nonces with `check_ajax_referer()` or `wp_verify_nonce()`.
- **Capability checks**: Admin operations must check `manage_woocommerce` capability.
- **Transient cache**: After modifying rules, the `cfwc_rules_cache` transient must be invalidated. Forgetting this causes stale fee calculations.
- **Version sync**: The version number exists in four files (see CRITICAL Rules). Missing one causes release inconsistencies.
- **Min file generation**: Never commit minified assets directly. They are generated by `pnpm uglify` and `pnpm cleancss`, and gitignored.
- **PHPStan bootstrap**: `phpstan-bootstrap.php` defines constants for analysis. If you add new `define()` calls to the main plugin file, mirror them here.

## Skills and Additional Guidance

Developer docs in `docs/`: CIF.md (valuation feature), HOWTO_DEBUG.md (logging), QUICK_START.md (setup), TESTING.md (manual test scenarios; automated unit tests live in `tests/Unit/`).
