=== Customs Fees for WooCommerce ===
Contributors: woocommerce
Tags: woocommerce, customs, import-fees, international-shipping, tariffs
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Requires Plugins: woocommerce
WC requires at least: 9.0
WC tested up to: 10.1.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically calculate and display customs fees, import duties, and tariffs at checkout based on product origin and destination countries.

== Description ==

**Customs Fees for WooCommerce** provides transparent, automated customs and import fee calculations directly in your WooCommerce store. Perfect for international sellers who need to show customers the true landed cost at checkout.

= Why You Need This Plugin =

With the U.S. ending its de minimis exemption on August 29, 2025, and increasing global trade regulations, showing accurate customs fees is essential for:

* **Reducing cart abandonment** from surprise fees at delivery
* **Building customer trust** with transparent total costs
* **Staying compliant** with international trade regulations
* **Improving conversion rates** for cross-border sales

= Key Features =

* **Smart Rule Engine** - Create unlimited rules based on origin/destination combinations
* **Multiple Calculation Methods** - Percentage-based or flat fee calculations
* **Product Origin Management** - Set country of origin and HS codes per product
* **Preset Templates** - Quick setup with pre-configured rules for US, UK, EU, Canada, and Australia
* **Flexible Stacking Rules** - Control how multiple fees combine (add, override, or exclusive)
* **Detailed Breakdown** - Show customers exactly which fees apply and why
* **CSV Import/Export** - Bulk manage rules and product data
* **HPOS Compatible** - Full support for High-Performance Order Storage
* **Developer Friendly** - Extensive hooks and filters for customization

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

**Scenario 3: Threshold-Based Fees**
- Orders under $150 → No fees
- Orders $150-800 → 10% duty
- Orders over $800 → 15% duty + $50 flat fee

**Scenario 4: Product Category Rules**
- Electronics → 5% duty
- Textiles → 12% duty
- Food products → 8% duty + health inspection fee

= Quick Start =

1. Install and activate the plugin
2. Go to WooCommerce → Settings → Tax → Customs Fees
3. Select a preset template (e.g., "US General Import")
4. Click "Add Preset Rules"
5. Set product origin countries in your product settings
6. Fees automatically appear at checkout!

= Compatibility =

* WordPress 6.0 or higher
* WooCommerce 9.0 or higher
* PHP 7.4 or higher
* Works with Classic and Block-based checkout
* Compatible with major shipping plugins
* Supports multi-currency stores

== Installation ==

= Automatic Installation =

1. Go to Plugins → Add New in your WordPress admin
2. Search for "Customs Fees for WooCommerce"
3. Click "Install Now" and then "Activate"
4. Navigate to WooCommerce → Settings → Tax → Customs Fees

= Manual Installation =

1. Download the plugin zip file
2. Go to Plugins → Add New → Upload Plugin
3. Choose the downloaded file and click "Install Now"
4. Activate the plugin
5. Configure at WooCommerce → Settings → Tax → Customs Fees

= First Time Setup =

After activation:
1. You'll see a setup notice prompting you to configure products
2. Add country of origin to your products (bulk edit supported)
3. Configure fee rules using presets or custom rules
4. Test with a sample order

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

= Can I import/export rules? =

Yes! The plugin includes CSV import/export functionality for:
- Bulk rule management
- Backup and migration
- Team collaboration

= How do I set product origin countries in bulk? =

Three methods:
1. Use WordPress bulk edit for selected products
2. Import via WooCommerce CSV with our custom columns
3. Use the provided bulk actions in the products list

= Do virtual/downloadable products have customs fees? =

No, the plugin automatically excludes virtual and downloadable products from customs fee calculations.

= Can I test without affecting live orders? =

Yes! You can:
- Use test mode to see calculations without charging
- Create test rules for specific countries
- Use staging environments for testing

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
8. CSV import/export interface
9. Bulk edit for product origins
10. Email with customs fee information

== Changelog ==

= 1.0.0 - 2025-01-20 =
* Initial release
* Core customs fee calculation engine
* Product origin country and HS code management
* 25+ preset templates for major countries
* CSV import/export functionality
* HPOS compatibility
* Cart and checkout block support
* Detailed fee breakdown display
* Stacking rules (add, override, exclusive)
* Developer hooks and filters
* Comprehensive admin interface
* Bulk product management tools
* Email integration
* Multi-language ready

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

= Shortcodes =

`[cfwc_fee_calculator]` - Display fee calculator widget
`[cfwc_origin_selector]` - Product origin selector
`[cfwc_fee_breakdown]` - Show current fee breakdown

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

= Test Case 3: Threshold Rules =
1. Create rule with minimum threshold $150
2. Test order under $150 - no fees
3. Test order over $150 - fees apply

= Test Case 4: Category-Specific =
1. Set different rates for electronics vs textiles
2. Add mixed products to cart
3. Verify correct rates apply per category

== Support ==

* Documentation: [GitHub Wiki](https://github.com/woocommerce/customs-fees)
* Issues: [GitHub Issues](https://github.com/woocommerce/customs-fees/issues)
* Community: [WordPress.org Support Forum](https://wordpress.org/support/plugin/customs-fees-for-woocommerce/)

== Credits ==

Developed by WooCommerce
Special thanks to the WordPress and WooCommerce communities

== Privacy Policy ==

This plugin:
* Does not collect personal data
* Does not send data to external services
* Does not use cookies for tracking
* Stores fee calculations locally
* Respects WordPress privacy settings

For more information, see our [Privacy Policy](https://woocommerce.com/privacy).