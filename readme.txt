=== Customs Fees for WooCommerce ===
Contributors: woocommerce
Tags: woocommerce, customs, import-fees, international-shipping, tariffs
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Requires Plugins: woocommerce
WC requires at least: 9.0
WC tested up to: 10.1.2
Stable tag: 1.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically calculate and display customs fees, import duties, and tariffs at checkout based on product origin and destination countries.

== Description ==

**Customs Fees for WooCommerce** provides transparent, automated customs and import fee calculations directly in your WooCommerce store. Perfect for international sellers who need to show customers the true landed cost at checkout.

= Why You Need This? =

With the U.S. ending its de minimis exemption on August 29, 2025, and increasing global trade regulations, showing accurate customs fees is essential for:

* **Reducing cart abandonment** from surprise fees at delivery
* **Building customer trust** with transparent total costs
* **Staying compliant** with international trade regulations
* **Improving conversion rates** for cross-border sales

= Key Features =

* **Smart Rule Engine** - Create unlimited rules based on origin/destination combinations.
* **Percentage-Based Calculations** - Apply customs fees as percentage of product value.
* **Product Origin Management** - Set country of origin and HS codes per product.
* **Variable Product Support** - Full support for variable products with parent-level defaults and variation-level overrides.
* **Preset Templates** - 30+ pre-configured rules for major trade routes.
* **Flexible Stacking Rules** - Control how multiple fees combine (add, override, or exclusive).
* **Detailed Breakdown** - Show customers exactly which fees apply and why.
* **Category & HS Code Matching** - Apply rules based on product categories or HS codes.
* **HPOS Compatible** - Full support for High-Performance Order Storage.
* **Debug Logging** - Comprehensive logging for troubleshooting.

= Supported Use Cases =

**Scenario 1: Mixed Origin Cart**
Customer orders 3 products:
- T-shirt from China → 12% duty applies
- Electronics from Japan → 5% duty applies  
- Local product → No duty
Plugin calculates each fee separately and shows total

**Scenario 2: Country-Specific Rules**
- Products from EU to US → 8% general duty
- Products from China to US → 25% tariff
- Products from Canada to US → 0% (USMCA agreement)

**Scenario 3: Category-Based Rules**
- Electronics category → 5% duty
- Textiles category → 12% duty
- Books category → 3% duty

**Scenario 4: HS Code Rules**
- Products with HS Code 61* (Clothing) → 20% duty
- Products with HS Code 8471* (Computers) → 5% duty
- Products without HS codes → Fall back to origin country rules

= Quick Start =

1. Install and activate the plugin.
2. Go to WooCommerce → Settings → Tax → Customs Fees.
3. Select a preset template (e.g., "US General Import").
4. Click "Add Preset Rules".
5. Set product origin countries in your product settings.
6. Fees automatically appear at checkout!

= Compatibility =

* WordPress 6.0 or higher.
* WooCommerce 9.0 or higher.
* PHP 7.4 or higher.
* Works with Classic and Block-based checkout.
* Compatible with major shipping plugins.
* Supports multi-currency stores.

== Installation ==

= Automatic Installation =

1. Go to Plugins → Add New in your WordPress admin.
2. Search for "Customs Fees for WooCommerce".
3. Click "Install Now" and then ."Activate".
4. Navigate to WooCommerce → Settings → Tax → Customs Fees.

= Manual Installation =

1. Download the plugin zip file.
2. Go to Plugins → Add New → Upload Plugin.
3. Choose the downloaded file and click "Install Now".
4. Activate the plugin.
5. Configure at WooCommerce → Settings → Tax → Customs Fees.

= First Time Setup =

After activation:
1. You'll see a setup notice prompting you to configure products.
2. Add country of origin to your products (bulk edit supported).
3. Configure fee rules using presets or custom rules.
4. Test with a sample order.

== Frequently Asked Questions ==

= How are customs fees calculated? =

The plugin matches products in the cart with configured rules based on:
- Shipping destination country
- Product origin country
- Product categories (if configured)
- Order value thresholds

= Can I use different rules for different product categories? =

Yes! You can create rules that apply to:
- All products from a specific country
- Specific product categories
- Individual products (using match conditions)
- Combinations of the above

= Do fees show in cart and checkout? =

Yes, fees are displayed in both cart and checkout pages. You can see:
- Total customs fees amount
- Detailed breakdown of individual fees
- Tooltips with additional information

= Is this compatible with WooCommerce Blocks? =

Yes, the plugin is compatible with WooCommerce Cart and Checkout blocks. Fees display correctly in block-based checkout flows.

= Can I import preset rules? =

Yes! The plugin includes 30+ preset templates for common trade routes:
- US tariffs from various countries
- UK/EU Brexit-related rules
- Free trade agreements
You can import presets with one click and add to existing rules.

= How do I set product origin countries? =

Edit each product individually:
1. Go to product edit page
2. Find "Customs & Import Settings" metabox
3. Set Country of Origin and optional HS Code
4. Save the product

For variable products:
- Set default values at the parent product level (applies to all variations)
- Optionally override specific variations in the variation settings
- Variations inherit parent values when not explicitly set

= Do virtual/downloadable products have customs fees? =

No, the plugin automatically excludes virtual and downloadable products from customs fee calculations.

= Can I test without affecting live orders? =

Yes! You can:
- Enable WordPress debug logging to see detailed calculations
- Create test rules for specific countries
- Use staging environments for testing
- Test with different shipping destinations

= Is customer data sent to external services? =

No, all calculations happen locally on your server. No customer data is sent to external APIs.

= Can I customize fee labels and descriptions? =

Yes, through:
- Admin settings for default labels
- Individual rule labels
- Filter hooks for developers

== Screenshots ==

1. Main settings page with rule configuration
2. Quick preset templates for fast setup
3. Product edit screen with origin country and HS code fields
4. Cart page showing customs fee breakdown
5. Checkout page with detailed fee information
6. Order confirmation with customs fees included
7. Admin order view with fee details
8. Email with customs fee information

== Changelog ==

= 1.1.1 - 2025-10-07 =
* Fixed issue where downloadable products with physical shipping were incorrectly excluded from customs fee calculations
* Downloadable products that require shipping now properly have customs fees applied
* Virtual products continue to be excluded as expected

= 1.1.0 - 2025-09-16 =
* Added full support for variable products with parent-level defaults and variation-level overrides
* Enhanced inheritance pattern for HS codes and origin countries in product variations
* Improved UX with clear parent value display in variation settings
* Fixed issue where variable products weren't applying customs fees

= 1.0.0 - 2025-09-15 =
* Initial release
* Core customs fee calculation engine
* Product origin country and HS code management
* 30+ preset templates for major trade routes
* Percentage-based fee calculations
* HPOS compatibility
* Cart and checkout block support
* Detailed fee breakdown display
* Stacking rules (add, override, exclusive)
* Category and HS code rule matching
* Comprehensive admin interface
* Improved UX with clear parent value display in variation settings
* Virtual/downloadable product exclusion
* Email integration with fee breakdown
* Debug logging system

== Upgrade Notice ==

= 1.0.0 =
Initial release. Install to start adding transparent customs fees to your international orders.

== Advanced Features ==

= Rule Stacking Options =

Control how multiple rules interact:
* **Add** - Combine all matching fees
* **Override** - Later rules replace earlier ones
* **Exclusive** - Only the first matching rule applies

= Developer Hooks =

`cfwc_calculated_fee` - Modify calculated fees
`cfwc_fee_rules` - Add/modify rules programmatically
`cfwc_fee_label` - Customize fee labels
`cfwc_product_origin` - Override product origin
`cfwc_should_calculate` - Control when fees apply

== Testing Scenarios ==

= Test Case 1: Single Origin, Single Destination =
1. Add products all from China
2. Ship to United States
3. Verify 25% tariff applies to all products

= Test Case 2: Mixed Origins =
1. Add product from China (25% tariff)
2. Add product from EU (8% duty)
3. Add product from Canada (0% - USMCA)
4. Verify each fee calculates separately

= Test Case 3: Virtual Products =
1. Add physical products to cart - fees apply
2. Add virtual/downloadable products - no fees
3. Mixed cart - fees only on physical products

= Test Case 4: Stacking Modes =
1. Set rules with different stacking modes
2. Test Add mode - multiple fees combine
3. Test Override mode - later rules replace earlier
4. Test Exclusive mode - first match only

== Privacy Policy ==

This plugin:
* Does not collect personal data.
* Does not send data to external services.
* Does not use cookies for tracking.
* Stores fee calculations locally.
* Respects WordPress privacy settings.

For more information, see our [Privacy Policy](https://woocommerce.com/privacy).