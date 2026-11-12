# WordPress Playground preview

The "Test the build" workflow (`.github/workflows/test_the_build.yml`) comments on every pull request with links that open the pull request's build in [WordPress Playground](https://wordpress.org/playground/). This folder holds the files behind those links. They are not part of the release zip (`tests` is in `archive.exclude` in `composer.json`).

- `blueprint.json` installs WooCommerce and the build, then runs `setup.php`. It expects both in `/tmp/pr`. The workflow adds a first step that downloads them from the run's artifact, and makes a second link that lands on the cart.
- `setup.php` prepares the store. The plugin never loads it.

## What the preview store has

- A US store in USD, with the WooCommerce setup wizard skipped and HPOS on.
- Taxes enabled, with no tax rates. The Customs Fees settings are a section of the Tax tab, and WooCommerce hides that tab while taxes are off.
- Free shipping everywhere and cash on delivery.
- Two rules for goods made in China: 10% to the United States and 20% to the United Kingdom.
- A $100 "Sample imported T-shirt" made in China, already in the admin's cart.
- A US billing and shipping address on the admin user, which fills in the checkout form.

So the cart shows a $10 customs fee, a UK address makes it $20, and any other country removes it.

To preview a feature that sits behind an option, set the option in `setup.php`.

## Run it locally

```bash
pnpm build
mkdir -p /tmp/cfwc-preview
cp customs-fees-for-woocommerce.zip tests/playground/setup.php /tmp/cfwc-preview/
npx @wp-playground/cli@latest server --blueprint=tests/playground/blueprint.json --mount-before-install=/tmp/cfwc-preview:/tmp/pr
```

Wait for the "Ready!" line, then open `/wp-admin/admin.php?page=wc-settings&tab=tax&section=customs` or `/cart/` on the address it prints. The local server does not go to the Blueprint's landing page by itself.

## Limits

- The links download the build through Playground's artifact proxy, which finds it by branch name. A fork's pull request with the same branch name can end up behind a link. Never put credentials in these files, and never ask the visitor for any.
- Emails are not delivered. In the browser, the Email panel of the Playground toolbar shows them. A local run has no such panel, and `wp_mail()` fails there.
- The links stop working when the artifact expires, 14 days after the run.
