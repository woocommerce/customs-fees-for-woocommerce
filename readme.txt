=== Customs Fees for WooCommerce ===
Contributors: woocommerce
Tags: woocommerce, tarrifs, fees, customs, duties
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Requires Plugins: woocommerce
WC requires at least: 9.0
WC tested up to: 10.1.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add transparent customs and import fee calculations to WooCommerce checkout using simple rule tables. No external APIs required.

== Description ==

Customs Fees for WooCommerce lets you add clear, predictable customs, duties, and import fee calculations directly to the cart and checkout. Rules are configured similarly to WooCommerce tax settings (percent or fixed amounts, thresholds, and country-based logic) — without relying on third‑party APIs.

Key use cases:
- Provide fee transparency to reduce cart abandonment.
- Show customers true landed costs at checkout.
- Stay compliant with changing international import requirements.

= Features =
- Rule-based fee engine (country-based, percentage/flat amounts, min/max thresholds).
- Display modes: single line total or itemized breakdown.
- Optional tooltip/help text next to fee labels on cart and checkout.
- Product metadata support (HS Code and Country of Origin display on order screens and emails).
- Emails and order totals include customs/import fees.
- HPOS (High-Performance Order Storage) compatible.
- Lightweight, privacy-friendly (no tracking, no external API calls by default).
- Optional local logs table for fee calculation analytics.

= Compatibility =
- WordPress: 6.0+ (tested up to 6.8)
- PHP: 7.4+
- WooCommerce: requires 9.0+, tested up to 10.1.2
- Blocks: Cart & Checkout Blocks compatibility declared; deeper integration is in progress.

= Quick Start =
1. Go to WooCommerce → Settings → Tax → Customs Fees.
2. Choose a preset (e.g., “US General Import”).
3. Click “Add Preset Rules”.
4. Save changes.
5. Matching orders will display customs/import fees at checkout.

= Privacy =
- No personal data is collected or sent to external services.
- No “phone home” requests.
- All calculations are performed server-side.
- Optional logs are stored locally in your WordPress database.

== Installation ==

= From your WordPress dashboard =
1. Upload the plugin ZIP via Plugins → Add New → Upload Plugin, or place the plugin folder in wp-content/plugins/.
2. Activate “Customs Fees for WooCommerce”.
3. Ensure WooCommerce is installed and active (required).
4. Configure rules in WooCommerce → Settings → Tax → Customs Fees.

= From FTP/SFTP =
1. Extract the plugin ZIP.
2. Upload the extracted folder to wp-content/plugins/.
3. Activate the plugin from the WordPress “Plugins” screen.
4. Configure rules in WooCommerce → Settings → Tax → Customs Fees.

== Frequently Asked Questions ==

= Does this plugin calculate official customs duty rates automatically? =
No. It uses rule-based tables you define (percentage or fixed amounts, thresholds, and country rules). There are no external API integrations by default.

= Where are the settings? =
WooCommerce → Settings → Tax → Customs Fees.

= Can I show a breakdown of multiple fee types? =
Yes. Choose the “Breakdown” display mode to itemize fees or use “Single” to show one combined line.

= Is it compatible with HPOS? =
Yes. HPOS compatibility is declared.

= Does it support WooCommerce Blocks checkout? =
Cart & Checkout Blocks compatibility is declared. Deeper block integration is in progress.

= Will the fee be taxable? =
You can configure whether a fee is taxable and pick the tax class, similar to WooCommerce’s built-in fee behavior.

= Does it add an information tooltip? =
Yes. You can set a tooltip text in settings; it appears near fee labels on cart/checkout.

== Screenshots ==

1. Customs Fees settings under WooCommerce → Tax → Customs
2. Example fee rules table with country and thresholds
3. Checkout with single-line “Customs & Import Fees”
4. Checkout with itemized breakdown and tooltip

== Changelog ==

= 1.0.0 - 2025-08-29 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release with rule-based customs/import fee calculation, display modes, tooltips, HS code display, email integration, and HPOS compatibility.
