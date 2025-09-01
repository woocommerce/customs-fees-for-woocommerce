# Testing Guide for Customs Fees for WooCommerce

This document provides comprehensive testing scenarios to ensure the plugin functions correctly in various use cases.

## Test Environment Setup

### Prerequisites

- WordPress 6.0+
- WooCommerce 9.0+
- Sample products with different origins
- Test customer accounts
- Multiple shipping zones configured

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

### Test Case 4: Threshold-Based Rules

**Scenario:** Fees apply only above certain order values

**Setup:**

1. Create threshold rule:
   - Minimum order: $150
   - Rate: 10%
2. Test three orders:
   - Order A: $100
   - Order B: $200
   - Order C: $149.99

**Expected Results:**

```
Order A ($100): No customs fees
Order B ($200): $20.00 customs fee
Order C ($149.99): No customs fees
```

**Verification Points:**

- ✓ Threshold strictly enforced
- ✓ Applies to subtotal (before shipping)
- ✓ Clear messaging when no fees apply

---

### Test Case 5: Percentage vs Flat Fees

**Scenario:** Testing different fee calculation types

**Setup:**

1. Rule 1: China → US = 25% (percentage)
2. Rule 2: Processing fee = $10 (flat)
3. Cart with $100 product from China

**Expected Results:**

```
Percentage fee: $100 × 25% = $25.00
Flat fee: $10.00
Total Customs Fees: $35.00
```

**Verification Points:**

- ✓ Both fee types calculate correctly
- ✓ Flat fees don't scale with quantity
- ✓ Percentage fees scale with price

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

**Scenario:** Variable products with different origins

**Setup:**

1. T-shirt with variations:
   - Small (origin: China): $20
   - Medium (origin: Vietnam): $22
   - Large (origin: Bangladesh): $24
2. Rules:
   - China → US: 25%
   - Vietnam → US: 15%
   - Bangladesh → US: 12%

**Expected Results:**

```
Small: $20 × 25% = $5.00
Medium: $22 × 15% = $3.30
Large: $24 × 12% = $2.88
Total: $11.18
```

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

### Test Case 12: Multi-Currency

**Scenario:** Customer shopping in different currency

**Setup:**

1. Store base currency: USD
2. Customer currency: EUR
3. Product: $100 USD from China
4. Rule: 25% customs

**Expected Results:**

```
Product in EUR: €85 (example rate)
Customs in EUR: €21.25
(Fees convert to customer currency)
```

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

### Test Case 14: Bulk Actions

**Scenario:** Setting origin for multiple products

**Setup:**

1. Select 20 products in admin
2. Bulk action: Set origin to "China"
3. Verify all products updated

**Expected Results:**

- All 20 products show China as origin
- Fees calculate correctly for all

---

### Test Case 15: CSV Import/Export

**Scenario:** Bulk rule management

**Setup:**

1. Export current rules to CSV
2. Modify in spreadsheet
3. Import back
4. Verify rules updated

**Expected Results:**

- Export contains all rule fields
- Import validates data
- Invalid rows reported
- Valid rows imported successfully

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

1. Add 100+ products to cart
2. Configure 50+ rules
3. Measure page load time
4. Should complete in < 2 seconds

### Stress Testing

1. Concurrent users: 100+
2. Products per cart: 50+
3. Rules configured: 100+
4. Expected: No crashes, < 3s response

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
