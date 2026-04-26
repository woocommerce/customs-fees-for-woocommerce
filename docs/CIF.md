# CIF Customs Valuation Documentation

This document covers the CIF (Cost, Insurance, Freight) customs valuation feature added in version 1.1.4, and the per-rule valuation & compound bases feature added in version 1.2.0.

---

## CIF Customs Valuation

Many countries calculate customs duties based on CIF (Cost, Insurance, Freight) value rather than just the product value. Version 1.1.4 introduces support for CIF-based customs calculations.

### What is CIF?

CIF stands for Cost, Insurance, and Freight. Under CIF valuation:

- **FOB (Free On Board)**: Customs value = Product price only (default)
- **CIF (Cost, Insurance, Freight)**: Customs value = Product price + Shipping costs

| Country/Region | Valuation Method |
| -------------- | ---------------- |
| United States  | FOB              |
| Canada         | FOB              |
| European Union | CIF              |
| United Kingdom | CIF              |
| Australia      | CIF              |
| Japan          | CIF              |
| New Zealand    | CIF              |

### Enable CIF Valuation

1. Go to **WooCommerce > Settings > Tax > Customs & Import Fees**
2. Find the **Customs Valuation Method** setting
3. Choose your valuation method:
   - **FOB - Product Value Only**: Calculate customs fees on product prices only (default, US/Canada)
   - **CIF - Include Shipping in Customs Value**: Include shipping costs in the customs value calculation

### How CIF Calculation Works

When CIF is enabled, shipping costs are distributed proportionally across products based on their value:

**Example:**

- Product A: $100 (50% of cart)
- Product B: $100 (50% of cart)
- Shipping: $20
- Customs duty rate: 25%

**FOB Calculation:**

```
Customs Value (A) = $100
Customs Value (B) = $100
Total Customs Fee = ($100 + $100) × 25% = $50.00
```

**CIF Calculation:**

```
Customs Value (A) = $100 + ($20 × 50%) = $110
Customs Value (B) = $100 + ($20 × 50%) = $110
Total Customs Fee = ($110 + $110) × 25% = $55.00
```

---

## Per-Rule Valuation & Compound Bases (v1.2.0)

Version 1.2.0 adds per-rule valuation method overrides and compound base support, allowing different valuation methods for duty vs. import tax within the same country.

### Problem: Single Global Setting is Insufficient

Some jurisdictions require different valuation bases for duty vs. import tax:

- **Canada**: Duty on FOB, GST on CIF + duty amount
- **Australia**: Duty on FOB, GST on customs value + duty
- **UK/EU**: Duty on CIF, VAT on CIF + duty

A single global FOB/CIF toggle cannot produce compliant results for these countries.

### Per-Rule Valuation Method

Each rule can now override the global valuation method:

| Setting | Behavior |
|---------|----------|
| `inherit` | Use the global setting (default) |
| `fob` | Product value only |
| `cif` | Product value + shipping |
| `cif_insurance` | Product value + shipping + insurance |

**Admin UI:** Each rule has a "Valuation" column showing the override (or "Global" if inheriting).

### Compound Bases (`base_includes`)

A rule can include other rules' computed fees in its own customs value base. This enables GST-on-CIF+duty calculations.

**Example - Canada:**

| Rule | Rate | Valuation | Depends on |
|------|------|-----------|------------|
| Duty | 8% | FOB | - |
| GST | 5% | CIF | Duty |

**Calculation:**
- Product: $100, Shipping: $20
- Duty base = $100 (FOB)
- Duty = $100 × 8% = **$8.00**
- GST base = $120 (CIF) + $8.00 (duty) = **$128.00**
- GST = $128 × 5% = **$6.40**

### Dependency Resolution

The calculator resolves rule dependencies in rounds:

1. Base rules (no dependencies) calculate first
2. Dependent rules calculate after their dependencies
3. Cycles are detected and logged; offending rules fall back to their own base

### Admin UI

- **Add/Edit Rule Modal**: New "Valuation" select and "Depends on" multi-select
- **Rules Table**: Shows valuation badge and dependency count
- **Cycle Warning**: Dismissible admin notice appears if a cycle is detected

### Migration

Existing rules from versions < 1.2.0 are automatically migrated on upgrade:
- `rule_id`: Assigned a stable UUID
- `valuation_method`: Set to `inherit`
- `base_includes`: Set to empty array

### Important Notes

- CIF only applies to **physical products** that require shipping.
- **Virtual/downloadable products** are automatically excluded.
- The same **rules and percentages** apply - only the calculation base changes.
- Existing orders are **not affected** - CIF only applies to new orders.
- If shipping is **free or $0**, CIF behaves the same as FOB.

---

## Insurance Integration for CIF

For countries that include insurance in CIF calculations (CIF = Cost + Insurance + Freight), the extension provides a filter hook for integration with third-party insurance plugins.

### Using the Insurance Filter Hook

If you use a shipping insurance plugin (like Shipping Insurance Manager), you can integrate it with customs calculations:

```php
/**
 * Integrate shipping insurance with CIF customs calculations.
 *
 * @param float  $insurance_value   Default insurance value (0).
 * @param float  $product_value     Product line total.
 * @param string $insurance_method  Current setting (percentage, flat, disabled).
 */
add_filter( 'cfwc_insurance_value', function( $insurance_value, $product_value, $method ) {
    // Example: Get insurance from another plugin
    if ( function_exists( 'get_cart_insurance_amount' ) ) {
        $total_insurance = get_cart_insurance_amount();
        $cart_subtotal   = WC()->cart->get_cart_contents_total();

        // Return proportional share for this product
        if ( $cart_subtotal > 0 ) {
            return ( $product_value / $cart_subtotal ) * $total_insurance;
        }
    }

    return $insurance_value;
}, 10, 3 );
```

### Simple Insurance Example

Add a fixed percentage for insurance:

```php
add_filter( 'cfwc_insurance_value', function( $value, $product_value, $method ) {
    // Calculate 2% insurance for all products
    return $product_value * 0.02;
}, 10, 3 );
```

---

## Developer Reference

### Filters

#### Fee Calculation Filters

```php
// Modify all calculated fees before they're applied
apply_filters( 'cfwc_calculated_fees', $fees, $destination_country, $cart );

// Modify a single fee calculation
apply_filters( 'cfwc_calculated_single_fee', $fee, $rule, $total );

// Customize fee label displayed at checkout
apply_filters( 'cfwc_fee_label', $label, $rule, $country, $origin );

// Filter if fee should be taxable
apply_filters( 'cfwc_fee_taxable_default', true, $rule );

// Filter fee tax class
apply_filters( 'cfwc_fee_tax_class_default', '', $rule );
```

#### Product & Origin Filters

```php
// Override product origin country
apply_filters( 'cfwc_product_origin', $origin, $product_id, $product );

// Modify the customs value (for CIF calculations)
apply_filters( 'cfwc_customs_value', $customs_value, $line_total, $cart_item, $method, $rule );

// Provide insurance value for CIF calculations
apply_filters( 'cfwc_insurance_value', $insurance_value, $product_value, $method );
```

#### Rules Filters

```php
// Filter rules for a specific country
apply_filters( 'cfwc_rules_for_country', $country_rules, $country );

// Filter all available rules
apply_filters( 'cfwc_all_rules', $rules );

// Filter countries available for rules
apply_filters( 'cfwc_countries_for_rules', $countries );

// Filter preset templates
apply_filters( 'cfwc_preset_templates', $templates );
```

#### Configuration Filters

```php
// Include shipping in cart total calculations
apply_filters( 'cfwc_include_shipping_in_calculation', true );

// Include taxes in cart total calculations
apply_filters( 'cfwc_include_taxes_in_calculation', false );

// Enable detailed calculation logging
apply_filters( 'cfwc_enable_calculation_logging', false );

// Control data removal on uninstall
apply_filters( 'cfwc_uninstall_remove_data', true );
```

### Actions

```php
// After cache is cleared
do_action( 'cfwc_cache_cleared' );
```

### Code Examples

#### Apply discount for large orders

```php
add_filter( 'cfwc_calculated_single_fee', function( $fee, $rule, $total ) {
    // Apply 50% discount for orders over USD $1000
    if ( WC()->cart->subtotal > 1000 ) {
        $fee = $fee * 0.5;
    }
    return $fee;
}, 10, 3 );
```

#### Customize fee labels

```php
add_filter( 'cfwc_fee_label', function( $label, $rule, $country, $origin ) {
    // Add emoji prefix to all customs fees
    return '🌍 ' . $label;
}, 10, 4 );
```

#### Override product origin for specific products

```php
add_filter( 'cfwc_product_origin', function( $origin, $product_id, $product ) {
    // Force all products in category 'electronics' to use 'CN' origin
    if ( has_term( 'electronics', 'product_cat', $product_id ) ) {
        return 'CN';
    }
    return $origin;
}, 10, 3 );
```

#### Integrate third-party insurance

```php
add_filter( 'cfwc_insurance_value', function( $value, $product_value, $method ) {
    // Calculate 2% insurance for all products
    return $product_value * 0.02;
}, 10, 3 );
```

#### Enable calculation logging for debugging

```php
add_filter( 'cfwc_enable_calculation_logging', '__return_true' );
```

#### Country-specific valuation override

```php
add_filter( 'cfwc_customs_value', function( $customs_value, $line_total, $cart_item, $method, $rule ) {
    $destination = WC()->customer->get_shipping_country();

    // Force FOB for US/Canada even if CIF is enabled globally.
    if ( in_array( $destination, array( 'US', 'CA' ), true ) ) {
        return $line_total; // Return product value only.
    }

    // Example: Add an arbitrary surcharge to the customs value for a specific rule.
    if ( ! empty( $rule['label'] ) && false !== strpos( $rule['label'], 'Special' ) ) {
        $customs_value += 10;
    }

    return $customs_value;
}, 10, 5 );
```

---

## FAQs

### What is the difference between FOB and CIF?

**FOB (Free On Board)** calculates customs duties on the product value only. This is used by the United States and Canada.

**CIF (Cost, Insurance, Freight)** calculates customs duties on the product value plus shipping costs (and sometimes insurance). This is used by most other countries including the EU, UK, Australia, and Japan.

### When should I enable CIF?

Enable CIF if you primarily ship to countries that use CIF-based customs valuation:

- European Union countries
- United Kingdom
- Australia
- New Zealand
- Japan
- Most Asian countries

Keep FOB (default) if you primarily ship to:

- United States
- Canada

### Will enabling CIF affect my existing orders?

No. CIF only affects new orders placed after you enable it. Existing order data remains unchanged.

### Can I use different valuation methods for different countries?

Currently, the valuation method is a global setting. However, you can use the `cfwc_customs_value` filter to implement country-specific logic. See the "Country-specific valuation override" code example above.

### How do I add insurance to CIF calculations?

Use the `cfwc_insurance_value` filter to integrate with your insurance solution. See the "Insurance Integration for CIF" section above.

### Does the fee breakdown show shipping separately in CIF mode?

No. When CIF is enabled, the shipping cost is included in the customs value calculation, but customers see the same fee display as before. The fee label and amount reflect the total calculated duty - the breakdown of how it was calculated (product + shipping) is handled internally.

This follows industry standards: services like Shopify, Zonos, and DHL show the total duty amount without labeling it as "CIF-based."

### What happens if shipping is free?

If shipping costs are $0 (free shipping), CIF behaves exactly the same as FOB since there's no shipping cost to add to the customs value.

### Does CIF work with all shipping methods?

Yes. CIF works with any shipping method that provides a shipping total to WooCommerce, including:

- Flat rate shipping
- Table rate shipping
- Live carrier rates (UPS, FedEx, etc.)
- Free shipping (behaves same as FOB)
