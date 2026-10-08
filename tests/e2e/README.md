# E2E tests

Playwright suite for Customs Fees for WooCommerce, packaged as a QIT custom E2E test package (`customs-fees-for-woocommerce/e2e`).

## Coverage

- **Smoke**: wp-admin loads with no fatal PHP errors, the plugin is active, and the Tax → Customs Fees section renders.
- **Rules editor**: a rule added in the UI persists after Save changes.
- **Classic checkout**: a product made in CN shipped to GB gets the `Customs & Import Fees` fee (10%, $10.00 on $100.00), and the order records it. Shipping to the US (the store country) gets no fee.
- **Blocks checkout**: the same fee through the Store API and the Checkout block, recorded on the order.

## Run locally (wp-env)

```bash
pnpm install && composer install
pnpm exec wp-env start              # WordPress latest + WooCommerce latest stable
pnpm run test:e2e:install           # once: npm ci + Chromium
pnpm run test:e2e:local             # headless
pnpm run test:e2e:local:show        # headed
RESET_E2E_SESSION=1 pnpm run test:e2e:local   # force a fresh admin login
```

If another wp-env holds port 8888, add a gitignored `.wp-env.override.json` with `{ "port": 8890 }`.

`.wp-env.json` mounts WooCommerce at `wp-content/plugins/woocommerce` and activates it, then this plugin, from an `afterStart` script. The folder name matters: the plugin only treats WooCommerce as active when it is `woocommerce/woocommerce.php`.

## Run through QIT

```bash
pnpm run test:e2e   # builds the QIT zip, then qit run:e2e --test-package=./tests/e2e
```

Needs `qit connect` first. In CI the suite runs on the weekly Cron QIT and from **Actions → Manual Test Runner → `Custom Plugin E2E (tests/e2e)`**.

## How it works

- **Store state is provisioned on entry** (REST through `wp.apiFetch`, plus the Store API) and is idempotent, so a re-run or a run after an aborted one converges.
- **Rules are written by POSTing the plugin's own settings form**, so `CFWC_Settings::save_rules()` shapes the stored data.
- **Taxes must be enabled** (WooCommerce hides the Tax tab otherwise), and the store must have **no tax rates**. Provisioning fails loudly if it finds any.
- **The specs rewrite the store's own checkout page** between the shortcode and the Checkout block, so they run serially.
- **A fail-soft global teardown** empties the rules and resets the checkout page to the Checkout block.

**Run this suite only against a disposable store** (wp-env or QIT). It rewrites store settings, the checkout page, and the admin's session address.

## Shared files

These files are byte-identical copies of the sibling extensions' suites. Don't edit them here; fix them upstream and re-copy:

- From `woocommerce-shipping-canada-post@3cf00b6`:
  - `utils/{paths,wp-login,qit,admin-session,php-errors,wc-settings}.ts`
  - `bin/resolve-base-url.js`
  - `fixtures/auth.setup.ts`
  - `tsconfig.json`
- From `woocommerce-shipping-multiple-addresses@5774a41`:
  - `utils/rest.ts`
