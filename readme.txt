=== Customs Fees for WooCommerce ===
Contributors: shameemreza
Tags: woocommerce, checkout, fees, customs, duties, import, taxes, international
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add transparent customs and import fee calculations to WooCommerce checkout. Simple fee tables — no external APIs required.

== Description ==

Customs Fees for WooCommerce lets you add clear, upfront import duties and taxes to orders at cart/checkout so customers aren’t surprised on delivery. Define simple country-based rules (similar to WooCommerce tax settings) and show either a single combined fee or a detailed breakdown.

Why now? With the U.S. ending its de minimis exemption on August 29, 2025, many international shipments will incur customs duties regardless of value. This plugin helps merchants:
- Add transparency to international orders
- Prevent cart abandonment from surprise fees
- Build customer trust with upfront total costs
- Stay compliant with new regulations

Key features
- Country-based fee rules: percentage and/or fixed fees, with optional minimum amounts and custom labels
- Display modes: single total or line-by-line breakdown
- Cart, Checkout, Order details, and Emails display
- Optional disclaimer/tooltip text for customer clarity
- Preset templates to get started quickly (US, EU, China, UK, Canada, Australia)
- Admin settings integrated under WooCommerce settings
- HPOS compatible
- WooCommerce Blocks framework in place (Checkout Blocks integration in progress)
- No external API dependencies; all calculations are server-side

Compatibility
- WooCommerce minimum: 9.0
- WooCommerce tested up to: 10.1.2
- WordPress: 6.0+ (tested up to 6.8)
- PHP: 7.4+

Documentation and Support
- Documentation: https://github.com/shameemreza/customs-fees-for-woocommerce/wiki
- Support/Issues: https://github.com/shameemreza/customs-fees-for-woocommerce/issues

Privacy
This plugin:
- Does NOT collect personal data
- Does NOT send data to external services
- Stores optional calculation logs locally only
- Performs all calculations on your server

== Installation ==

From your WordPress dashboard
1. Ensure WooCommerce is installed and active.
2. Go to Plugins > Add New and upload the plugin ZIP, or place the plugin folder in wp-content/plugins/.
3. Activate “Customs Fees for WooCommerce”.
4. Go to WooCommerce > Settings > Customs Fees to configure.

Quick start example
- Enable customs fees
- Display on cart and checkout: enabled
- Add Rule:
  - Country: United States (US)
  - Type: Percentage
  - Rate: 10%
  - Minimum: $5
  - Label: US Import Duty

== Frequently Asked Questions ==

Does this plugin use courier/customs APIs to calculate “real” duties and taxes?
- No. It uses your admin-defined fee rules (percentage/fixed/minimum) per country. It is designed for transparency and predictability rather than live-rate complexity.

Does it support WooCommerce Checkout Blocks?
- The framework is in place and compatibility is declared; full Checkout Blocks UI/flow integration is in progress. Classic checkout is supported today.

Can fees be taxable?
- Yes. You can mark fees as taxable and select a tax class if required.

Can I show a breakdown instead of a single line?
- Yes. Use the “Display mode” setting to switch between a single combined fee or a detailed breakdown with custom labels.

Does it work with multi-currency plugins?
- The plugin uses WooCommerce core totals and filters. Most currency switchers that integrate via standard WooCommerce APIs should work, but coverage may vary by plugin.

Does the plugin store personal data?
- No. Optional logs (for fee calculations) store order IDs and high-level details; no personal data is transmitted offsite.

== Screenshots ==

1. Settings page with rule table and presets
2. Cart page showing estimated customs fees
3. Checkout page with fee breakdown and tooltip
4. Admin order screen including customs fee lines
5. Order email including customs fee lines

== Changelog ==

= 1.0.0 - 2025-08-28 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release.

== Filters and Hooks ==

You can customize behavior with the following filters:

```php
// Modify calculated fees array before they are added to the cart.
add_filter( 'cfwc_calculated_fees', 'my_custom_fees', 10, 3 );

// Filter country-specific rules before calculation.
add_filter( 'cfwc_country_rules', 'my_country_rules', 10, 2 );

// Customize fee labels.
add_filter( 'cfwc_fee_label', 'my_fee_label', 10, 3 );

// Conditionally skip fees.
add_filter( 'cfwc_apply_fee', 'my_fee_conditions', 10, 3 );

// Include or exclude shipping in the fee calculation.
add_filter( 'cfwc_include_shipping_in_calculation', '__return_false' );
```

== Notes ==

- Requires WooCommerce to be installed and activated.
- Translations: For WordPress.org-hosted plugins, translations are loaded automatically since WordPress 4.6. Text domain: customs-fees-for-woocommerce (Domain Path: /languages).
