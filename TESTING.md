# Testing Guide for Customs Fees for WooCommerce

This document provides comprehensive testing scenarios to ensure the plugin functions correctly in various use cases.

## Plugin Features Overview

### Supported Features ✅

- **Rule Types:** Percentage-based customs fees
- **Rule Matching:** By origin country, destination country, product category, HS codes
- **Stacking Modes:** Add, Override, Exclusive
- **Product Types:** Physical products only (virtual/downloadable excluded)
- **Display:** Fee breakdown in cart, checkout, orders, and emails
- **Presets:** Pre-configured rules for common trade routes
- **Logging:** Debug logging for troubleshooting

### Not Currently Supported ❌

- **Flat Fees:** Only percentage-based fees
- **Threshold Rules:** No minimum order amount settings
- **Bulk Actions:** No bulk origin setting for products
- **CSV Import/Export:** Manual rule entry only (except presets)
- **Per-Variation Origins:** Variations inherit parent product origin
- **Built-in Currency Conversion:** Relies on WooCommerce currency

### Recommended Test Data

1. Create at least 10 products with different origin countries
2. Set up shipping zones for US, UK, EU, and other regions
3. Configure tax settings (to test alongside customs fees)
4. Enable guest checkout for anonymous testing

## Core Testing Scenarios

### Test Case 1: Single Origin, Multiple Products

**Scenario:** Customer from the US orders multiple products, all from China

**Setup:**

1. Create 3 products, all with origin = China
   - Product A: $50 (Physical product)
   - Product B: $100 (Physical product)
   - Product C: $75 (Physical product)
2. Configure rule: China → US = 25% tariff (Stack: Exclusive)
3. Set shipping destination to United States

**Note:** China presets use "Exclusive" stacking mode by default, meaning ONLY the China tariff applies (not the general US tariff)

**Expected Results:**

```
Product A customs: $50 × 25% = $12.50
Product B customs: $100 × 25% = $25.00
Product C customs: $75 × 25% = $18.75
Total Customs Fees: $56.25
```

**Verification Points:**

- ✓ Fee appears in cart
- ✓ Fee appears in checkout
- ✓ Breakdown shows individual calculations
- ✓ Order email includes fee
- ✓ Admin order view shows fee details

---

### Test Case 2: Mixed Origins Cart

**Scenario:** Customer orders products from different countries

**Setup:**

1. Products in cart:
   - T-shirt from China: $30
   - Electronics from Japan: $200
   - Book from UK: $25
   - Local product (same country): $50
2. Rules configured:
   - China → US: 25%
   - Japan → US: 5%
   - UK → US: 8%
   - Local: No fee

**Expected Results:**

```
T-shirt (China): $30 × 25% = $7.50
Electronics (Japan): $200 × 5% = $10.00
Book (UK): $25 × 8% = $2.00
Local product: $0.00
Total Customs Fees: $19.50
```

**Verification Points:**

- ✓ Each origin calculates separately
- ✓ Local products excluded
- ✓ Breakdown shows origin countries
- ✓ Correct grouping in display

---

### Test Case 3: Free Trade Agreements

**Scenario:** Testing USMCA (formerly NAFTA) free trade

**Setup:**

1. Products from Canada: $500 total
2. Products from Mexico: $300 total
3. Shipping to United States
4. Rules: Canada/Mexico → US = 0%

**Expected Results:**

```
Canada products: $0.00 (USMCA free trade)
Mexico products: $0.00 (USMCA free trade)
Total Customs Fees: $0.00
```

**Verification Points:**

- ✓ No fees applied
- ✓ No fee line shown (or shows $0.00)
- ✓ Works for both countries

---

### Test Case 4: Category-Based Rules

**Scenario:** Different fees for different product categories

**Setup:**

1. Create category rules:
   - Electronics: 15%
   - Clothing: 12%
   - Books: 5%
2. Test products from each category
3. Shipping to United States

**Expected Results:**

```
Electronics ($200): $30.00 customs fee
Clothing ($100): $12.00 customs fee
Books ($50): $2.50 customs fee
```

**Verification Points:**

- ✓ Category rules apply correctly
- ✓ Multiple categories in cart handled
- ✓ Breakdown shows by category

---

### Test Case 5: HS Code-Based Rules

**Scenario:** Testing HS code pattern matching

**Setup:**

1. Rule 1: HS Code 61\* (Clothing) = 20%
2. Rule 2: HS Code 8471\* (Computers) = 5%
3. Products with matching HS codes

**Expected Results:**

```
T-shirt (HS 6109.10): $100 × 20% = $20.00
Laptop (HS 8471.30): $500 × 5% = $25.00
Product without HS code: No fee based on HS rule
```

**Verification Points:**

- ✓ Wildcard patterns work
- ✓ Exact matches work
- ✓ Products without HS codes skip HS rules

---

### Test Case 6: Stacking Rules - Add Mode

**Scenario:** Multiple rules combine additively

**Setup:**

1. Rule 1: General US import: 10% (Stack: Add)
2. Rule 2: China specific: 25% (Stack: Add)
3. Product from China: $100

**Expected Results:**

```
General import: $100 × 10% = $10.00
China tariff: $100 × 25% = $25.00
Total: $35.00
```

**Important:** Most China presets use "Exclusive" mode to avoid double taxation. Use "Add" mode only when you want both general and specific tariffs to apply.

---

### Test Case 7: Stacking Rules - Override Mode

**Scenario:** Later rules replace earlier ones

**Setup:**

1. Rule 1: All imports: 10% (Stack: Add)
2. Rule 2: China imports: 25% (Stack: Override)
3. Products: One from China ($100), one from Japan ($100)

**Expected Results:**

```
China product: $25.00 (25% only, overrides 10%)
Japan product: $10.00 (10% applies)
Total: $35.00
```

---

### Test Case 8: Stacking Rules - Exclusive Mode

**Scenario:** First matching rule stops processing

**Setup:**

1. Rule 1: Electronics: 15% (Stack: Exclusive)
2. Rule 2: China origin: 25% (Stack: Add)
3. Electronic product from China: $200

**Expected Results:**

```
Only electronics rule applies: $200 × 15% = $30.00
(China rule ignored due to exclusive)
```

---

### Test Case 9: Virtual/Downloadable Products

**Scenario:** Digital products should have no customs fees

**Setup:**

1. Physical product: $50
2. Virtual product (software): $100
3. Downloadable product (ebook): $25
4. Rule: All imports 10%

**Expected Results:**

```
Physical product: $50 × 10% = $5.00
Virtual product: No fee
Downloadable product: No fee
Total Customs Fees: $5.00
```

**Verification Points:**

- ✓ Virtual products excluded
- ✓ Downloadable products excluded
- ✓ Mixed cart handles correctly

---

### Test Case 10: Product Variations

**Scenario:** Variable products with shared origin

**Setup:**

1. T-shirt with variations (all share parent product origin: China):
   - Small: $20
   - Medium: $22
   - Large: $24
2. Rule: China → US: 25%

**Expected Results:**

```
Small: $20 × 25% = $5.00
Medium: $22 × 25% = $5.50
Large: $24 × 25% = $6.00
Total: $16.50
```

**Note:** Variations inherit origin from parent product. Each variation cannot have a different origin.

---

### Test Case 11: Coupon Interactions

**Scenario:** How customs fees work with discounts

**Setup:**

1. Product: $100 from China
2. Coupon: 20% off
3. Rule: China → US = 25%

**Expected Results:**

```
Product after coupon: $80
Customs fee: $80 × 25% = $20.00
(Fee calculated on discounted price)
```

---

### Test Case 12: Multi-Currency (Requires Additional Plugin)

**Scenario:** Customer shopping in different currency

**Setup:**

1. Store base currency: USD
2. Multi-currency plugin active (e.g., WooCommerce Multi-Currency)
3. Customer currency: EUR
4. Product: $100 USD from China
5. Rule: 25% customs

**Expected Results:**

```
Product in EUR: €85 (example rate)
Customs in EUR: €21.25
(Fees automatically convert with WooCommerce currency)
```

**Note:** Currency conversion handled by WooCommerce, not by this plugin

---

### Test Case 13: Tax Interactions

**Scenario:** Customs fees and taxes together

**Setup:**

1. Product: $100
2. Customs fee: 25% = $25
3. Sales tax: 10%

**Expected Results:**

```
Subtotal: $100
Customs: $25
Tax: $10 (on product only, not on customs)
Total: $135
```

---

### Test Case 14: Manual Product Origin Setting

**Scenario:** Setting origin for products individually

**Setup:**

1. Edit product in WooCommerce admin
2. Find "Customs & Import Settings" metabox
3. Set Country of Origin and HS Code
4. Save product

**Expected Results:**

- Product shows selected origin
- HS code saved if provided
- Fees calculate based on origin

---

### Test Case 15: Preset Import

**Scenario:** Using preset rule templates

**Setup:**

1. Go to Customs & Import Fees settings
2. Select a preset (e.g., "United States Tariffs - China")
3. Click "Import" or "Add to Existing Rules"
4. Save settings

**Expected Results:**

- Preset rules imported successfully
- Rules appear in rules table
- Can combine multiple presets
- Stacking modes work as configured

---

## Edge Cases & Error Handling

### Edge Case 1: No Origin Set

**Test:** Add product without origin country to cart
**Expected:** No customs fee applied, or default rule if configured

### Edge Case 2: Invalid Rule Data

**Test:** Create rule with negative percentage
**Expected:** Validation prevents saving, error message shown

### Edge Case 3: Circular Rules

**Test:** Create conflicting override rules
**Expected:** First rule in priority order applies

### Edge Case 4: Maximum Fees

**Test:** Very expensive order ($10,000+)
**Expected:** Fees calculate correctly without overflow

### Edge Case 5: Zero-Value Orders

**Test:** Free product (after 100% coupon)
**Expected:** No customs fees on $0 value

### Edge Case 6: Partial Refunds

**Test:** Refund one product from order with customs
**Expected:** Customs fee remains on order (manual adjustment needed)

### Edge Case 7: Same Origin as Destination

**Test:** Product origin same as shipping country
**Expected:** No customs fees applied (domestic shipping)

---

## User Role Testing

### Administrator

- ✓ Can configure all settings
- ✓ Can add/edit/delete rules
- ✓ Can bulk edit product origins
- ✓ Can view fee analytics

### Shop Manager

- ✓ Can configure settings
- ✓ Can manage rules
- ✓ Can edit product origins
- ✓ Cannot modify plugin files

### Customer

- ✓ Sees fees in cart/checkout
- ✓ Sees breakdown on hover/click
- ✓ Receives email with fees
- ✓ Cannot modify calculations

### Guest

- ✓ Same as customer
- ✓ Fees calculate without login

---

## Localization Testing

### Language Testing

1. Switch WordPress to different language
2. Verify all strings translate
3. Check number formatting (1,000 vs 1.000)
4. Verify currency symbols position

### Country-Specific

1. Test with local addressing formats
2. Verify country names display correctly
3. Check state/province handling
4. Test postal code validation

---

## Performance Testing

### Load Testing

1. Add 20-30 products to cart
2. Configure 10-20 rules
3. Measure page load time
4. Should complete in < 2 seconds

### Reasonable Limits

1. Products per cart: Up to 50
2. Rules configured: Up to 30
3. Expected: Smooth operation without delays

---

## Security Testing

### Input Validation

1. Try SQL injection in rule fields
2. Try XSS in product origins
3. Try CSRF on settings save
4. All should be blocked

### Permission Testing

1. Try accessing settings as subscriber
2. Try editing rules without permission
3. Try bulk actions as customer
4. All should be denied

---

## Compatibility Testing

### Theme Compatibility

- ✓ Storefront
- ✓ Twenty Twenty-Four
- ✓ Divi
- ✓ Elementor
- ✓ Flatsome

### Plugin Compatibility

- ✓ WooCommerce Subscriptions
- ✓ WooCommerce Bookings
- ✓ WPML/Polylang
- ✓ Multi-currency plugins
- ✓ Shipping plugins

### Browser Testing

- ✓ Chrome (latest)
- ✓ Firefox (latest)
- ✓ Safari (latest)
- ✓ Edge (latest)
- ✓ Mobile browsers

---

## Regression Testing

After each update, verify:

1. Existing rules still work
2. Product origins preserved
3. Fee calculations unchanged
4. Display formatting consistent
5. No breaking changes

---

## Testing Checklist

Before release, ensure:

- [ ] All test cases pass
- [ ] Edge cases handled
- [ ] Performance acceptable
- [ ] Security validated
- [ ] Compatibility confirmed
- [ ] Documentation updated
- [ ] Translations complete
- [ ] Accessibility checked
- [ ] Mobile responsive
- [ ] PHPCS compliant

---

## Test Reporting Template

```
Test Case: [Name]
Date: [Date]
Tester: [Name]
Version: [Plugin version]
Environment: [WP version, WC version, PHP version]

Steps:
1. [Step 1]
2. [Step 2]
3. [Step 3]

Expected Result:
[What should happen]

Actual Result:
[What actually happened]

Status: [Pass/Fail]

Notes:
[Any additional observations]
```

---

## Automated Testing

### PHPUnit Tests

```
composer test
```

### JavaScript Tests

```
npm test
```

### E2E Tests

```
npm run test:e2e
```

### PHPCS

```
composer run phpcs
```

---

## Test Coverage Goals

- Unit Tests: 80%+ coverage
- Integration Tests: Core workflows
- E2E Tests: Critical user paths
- Manual Tests: UI/UX validation

---

**Remember:** Thorough testing ensures a quality product. When in doubt, test it out!
