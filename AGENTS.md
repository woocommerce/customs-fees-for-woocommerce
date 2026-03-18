## CRITICAL Rules

- Never modify `vendor/`, `node_modules/`, or minified assets (`*.min.js`, `*.min.css`).
- No autoloading or namespaces -- the plugin uses `CFWC_` prefixed classes with manual `require_once`. Do not introduce PSR-4 or Composer autoloading.
- All PHP classes must have the `ABSPATH` guard at the top: `if ( ! defined( 'ABSPATH' ) ) { exit; }`.
- Minified assets are built from source files -- edit only the non-minified versions in `assets/`.
- No external API calls. All customs fee calculations happen server-side with zero network dependencies.
- WooCommerce is a hard dependency declared via `Requires Plugins: woocommerce`.
- Never manually bump the plugin version.

## Project Knowledge

**Plugin**: Customs Fees for WooCommerce
**Purpose**: Calculate and display customs/import fees at WooCommerce checkout based on product origin, destination country, HS codes, and category rules.
**Version**: 1.1.5

### Stack

| Layer | Tool |
|-------|------|
| PHP | >= 7.4 (no namespaces, `CFWC_` prefix convention) |
| WordPress | >= 6.8, tested up to 6.9 |
| WooCommerce | >= 10.4, tested up to 10.6 |
| Node | 22.14.0 (pinned in `.nvmrc`) |
| Package manager | pnpm >= 10.4.1 |
| JS minification | UglifyJS (`uglify-js`) |
| CSS minification | clean-css-cli |
| i18n | node-wp-i18n |
| Static analysis | PHPStan level 0 with `phpstan-wordpress` + `woocommerce-stubs` |
| Release packaging | `composer archive` |

### Key Directories

```
includes/               # All PHP classes
includes/admin/         # Admin-only classes (loaded conditionally via is_admin())
includes/admin/views/   # Admin view templates (rules-section.php)
assets/css/             # Source CSS (admin.css, admin-improvements.css, frontend.css)
assets/js/              # Source JS (admin.js, frontend.js)
languages/              # POT file only -- translations loaded by WordPress automatically
docs/                   # Developer docs: CIF.md, HOWTO_DEBUG.md, QUICK_START.md, TESTING.md
.github/workflows/      # CI: QIT tests, build, release, merge-to-main
```

### Architecture Notes

- **Singleton pattern**: `Customs_Fees_WooCommerce` (main) and `CFWC_Loader` use `::instance()`.
- **Loader class** (`CFWC_Loader`) handles all dependency loading, class init, and hook registration.
- **Calculator** (`CFWC_Calculator`) is the fee engine -- called via `woocommerce_cart_calculate_fees`.
- **Rule Matcher** (`CFWC_Rule_Matcher`) matches cart items to configured rules.
- **Settings** live under WooCommerce > Settings > Tax > Customs Fees.
- **Fee breakdown** stored in `WC()->session` for display in cart/checkout.
- **Product meta**: `_cfwc_hs_code` and `_cfwc_country_of_origin` on postmeta.
- HPOS, Cart/Checkout blocks, and product block editor compatibility declared.

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

# Run PHP unit tests
# Not yet implemented -- no tests/ directory or phpunit.xml

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

- `main` -- stable, release-ready code.
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
- **Empty `load_textdomain()`**: Intentionally empty -- WordPress 4.6+ auto-loads translations for WordPress.org-hosted plugins. Do not add `load_plugin_textdomain()` calls.
- **Stub AJAX handlers in Loader**: `ajax_save_rules`, `ajax_delete_rule`, `ajax_load_template` in `CFWC_Loader` are stubs. Actual AJAX handling is in `CFWC_Ajax`.
- **composer archive for packaging**: Release ZIPs are built via `composer archive` with exclusion rules in `composer.json`. The `.gitattributes` file also controls `export-ignore`.
- **Rules dual storage**: Rules are stored via `cfwc_rules` option and cached via `cfwc_rules_cache` transient.

## Common Pitfalls

- **Missing escaping**: All user-facing output must use `esc_html()`, `esc_attr()`, `wp_kses()`, etc. The plugin renders rule names and labels in admin and frontend.
- **Nonce verification**: All AJAX handlers and form submissions must verify nonces with `check_ajax_referer()` or `wp_verify_nonce()`.
- **Capability checks**: Admin operations must check `manage_woocommerce` capability.
- **Transient cache**: After modifying rules, the `cfwc_rules_cache` transient must be invalidated. Forgetting this causes stale fee calculations.
- **Version sync**: The version number exists in four files (see CRITICAL Rules). Missing one causes release inconsistencies.
- **Min file generation**: Never commit minified assets directly. They are generated by `pnpm uglify` and `pnpm cleancss`, and gitignored.
- **PHPStan bootstrap**: `phpstan-bootstrap.php` defines constants for analysis. If you add new `define()` calls to the main plugin file, mirror them here.

## Skills and Additional Guidance

Developer docs in `docs/`: CIF.md (valuation feature), HOWTO_DEBUG.md (logging), QUICK_START.md (setup), TESTING.md (manual test scenarios - no automated tests exist yet).
