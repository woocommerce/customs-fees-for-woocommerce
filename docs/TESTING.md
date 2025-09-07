# Testing Guide - Customs Fees for WooCommerce

## Table of contents

1. [Testing overview](#testing-overview)
2. [Test environment setup](#test-environment-setup)
3. [Functional testing](#functional-testing)
4. [Integration testing](#integration-testing)
5. [Performance testing](#performance-testing)
6. [Security testing](#security-testing)
7. [Compatibility testing](#compatibility-testing)
8. [Test scenarios](#test-scenarios)
9. [Automated testing](#automated-testing)
10. [Troubleshooting tests](#troubleshooting-tests)

---

## Testing overview

This guide provides comprehensive testing procedures to ensure Customs Fees for WooCommerce functions correctly in various scenarios and configurations.

### Testing priorities

1. **Critical** - Core fee calculation and display
2. **High** - Rule matching and priority logic
3. **Medium** - Import/export functionality
4. **Low** - UI/UX elements and edge cases

---

## Test environment setup

### Recommended test environment

- **Local development**: Local by Flywheel, DevKinsta, or XAMPP
- **WordPress**: Latest stable version and minimum supported (6.0)
- **WooCommerce**: Latest stable version and minimum supported (8.0)
- **PHP**: Test on 7.4, 8.0, 8.1, and 8.2
- **Database**: MySQL 5.6+ or MariaDB 10.0+

### Essential test data

#### Products

Create test products with:

- Different categories (Electronics, Clothing, Books)
- Various price points (USD $10, $100, $1000)
- Multiple origins (China, Germany, United States)
- Different HS codes (6109, 8517, 9503)

#### Rules

Configure test rules covering:

- General import fees (all products)
- Category-specific fees
- HS code-based fees
- Combined criteria rules

#### Test users

- Admin user for configuration
- Shop manager for order management
- Customer accounts for checkout testing

---

## Functional testing

### 1. Plugin activation and deactivation

#### Test: Clean activation

1. Install plugin on fresh WordPress site
2. Activate plugin
3. **Expected**: No errors, settings page accessible
4. **Verify**: Database table created (`wp_cfwc_rules`)

#### Test: Deactivation preserves data

1. Configure rules and settings
2. Deactivate plugin
3. Reactivate plugin
4. **Expected**: All settings and rules preserved

### 2. Settings configuration

#### Test: Default product origin

1. Navigate to WooCommerce > Settings > Tax > Customs Fees
2. Set "Default Product Origin" to each option:
   - Use store location
   - Custom country (select China)
   - None
3. **Expected**: Setting saves correctly
4. **Verify**: New products inherit default origin

### 3. Rule management

#### Test: Create basic rule

1. Click "Add New Rule"
2. Fill in:
   - Name: "Test Import Fee"
   - From: China
   - To: United States
   - Fee Type: Percentage
   - Amount: 10
   - Match Type: All Products
3. Save rule
4. **Expected**: Rule appears in list as Active

#### Test: Rule priority

1. Create three rules with same criteria but different priorities
2. Set priorities: 1, 5, 10
3. Add product to cart
4. **Expected**: Rule with priority 1 applies first

#### Test: Stacking modes

Create three USD $10 fees and test each mode:

- **Add**: Total fee = USD $30
- **Override**: Total fee = USD $10 (highest priority)
- **Exclusive**: Total fee = USD $10 (first match only)

### 4. Product configuration

#### Test: Individual product settings

1. Edit product
2. Set Country of Origin: Germany
3. Set HS Code: 8517.12
4. Save product
5. **Expected**: Values saved and displayed correctly

#### Test: Bulk edit

1. Select five products
2. Bulk edit > Country of Origin > China
3. Apply
4. **Expected**: All five products updated

---

## Integration testing

### 1. Cart calculations

#### Test: Single product fee

1. Add product from China to cart
2. Set shipping to United States
3. **Expected**: Customs fee displays in cart totals

#### Test: Multiple products, same fee

1. Add three identical products
2. **Expected**: Fee shows as "Import Fee (10%) x 3"

#### Test: Multiple products, different fees

1. Add products with different applicable rules
2. **Expected**: Each fee listed separately with counts

### 2. Checkout process

#### Test: Classic checkout display

1. Proceed to classic checkout
2. **Expected**:
   - Fees shown in order review
   - Individual fee breakdown visible
   - Total includes customs fees

#### Test: Block checkout display

1. Use block-based checkout
2. **Expected**:
   - Total customs fees shown
   - Fees included in order total

### 3. Order management

#### Test: Order creation

1. Complete checkout with customs fees
2. View order in admin
3. **Expected**:
   - Fees listed in order items
   - Fee details in order notes

#### Test: Order emails

1. Place order with customs fees
2. Check admin and customer emails
3. **Expected**: Fees displayed in both emails

#### Test: Customer account

1. Customer views order in My Account
2. **Expected**: Customs fees visible in order details

### 4. Import/Export

#### Test: Export rules

1. Create five diverse rules
2. Click "Export Rules"
3. **Expected**: CSV contains all rule data

#### Test: Import rules

1. Modify exported CSV
2. Import modified file
3. **Expected**: Rules imported without duplicates

---

## Performance testing

### 1. Load testing

#### Test: Large catalog

1. Create 1000 products
2. Configure 50 rules
3. Add 10 products to cart
4. **Expected**: Checkout loads within three seconds

#### Test: Complex rules

1. Create rules with multiple HS codes (comma-separated)
2. Use wildcards in HS codes (85\*)
3. **Expected**: Calculation time under one second

### 2. Database queries

#### Test: Query optimization

1. Enable Query Monitor plugin
2. Load cart with customs fees
3. **Expected**:
   - No duplicate queries
   - Queries use indexes
   - Total queries reasonable (under 100)

---

## Security testing

### 1. Input validation

#### Test: SQL injection prevention

1. Try SQL in rule name: `'; DROP TABLE wp_users; --`
2. **Expected**: Input sanitized, no database damage

#### Test: XSS prevention

1. Enter JavaScript in HS Code field: `<script>alert('XSS')</script>`
2. **Expected**: Script escaped, not executed

### 2. Permission checks

#### Test: User capabilities

1. Login as Shop Manager
2. Try to modify customs rules
3. **Expected**: Appropriate permissions required

#### Test: Direct file access

1. Try accessing plugin files directly via URL
2. **Expected**: "No direct access" message or blank page

---

## Compatibility testing

### 1. WordPress compatibility

#### Test: Minimum version (6.0)

1. Install on WordPress 6.0
2. Test all features
3. **Expected**: Full functionality

#### Test: Latest version

1. Update to latest WordPress
2. Test all features
3. **Expected**: No deprecation warnings

### 2. WooCommerce compatibility

#### Test: HPOS enabled

1. Enable High-Performance Order Storage
2. Place orders with customs fees
3. **Expected**: Orders stored correctly

#### Test: HPOS disabled

1. Disable HPOS
2. Place orders with customs fees
3. **Expected**: Legacy storage works

### 3. Theme compatibility

#### Test: Default themes

Test with:

- Twenty Twenty-Four
- Twenty Twenty-Three
- Storefront

**Expected**: Fees display correctly in all themes

#### Test: Popular themes

Test with common WooCommerce themes:

- Flatsome
- Astra
- OceanWP

**Expected**: No styling conflicts

### 4. Plugin compatibility

#### Test: Tax plugins

1. Install WooCommerce Tax or TaxJar
2. Configure tax rates
3. **Expected**: Customs fees separate from taxes

#### Test: Currency plugins

1. Install multi-currency plugin
2. Switch currencies
3. **Expected**: Fees convert correctly

#### Test: Shipping plugins

1. Install Table Rate Shipping
2. Configure shipping zones
3. **Expected**: No conflicts, fees calculate correctly

---

## Test scenarios

### Scenario 1: International B2C store

**Setup:**

- Store in United States
- Products from China, Vietnam, India
- Customers worldwide

**Test steps:**

1. Configure general import rules for major countries
2. Add category-specific fees for electronics
3. Process orders to European Union, Canada, Australia
4. **Verify**: Appropriate fees apply based on destination

### Scenario 2: European dropshipper

**Setup:**

- Store in Germany
- Products from multiple suppliers globally
- EU customers only

**Test steps:**

1. Set product origins per supplier
2. Configure EU import rules
3. Test with mixed-origin cart
4. **Verify**: Fees calculated per product origin

### Scenario 3: B2B wholesale

**Setup:**

- Bulk orders only
- Fixed customs fees
- Business customers

**Test steps:**

1. Create fixed-fee rules
2. Add 100+ quantity to cart
3. Apply wholesale pricing
4. **Verify**: Fixed fees apply regardless of quantity

---

## Automated testing

### PHPUnit tests

Run unit tests:

```bash
composer test
```

Test coverage:

```bash
composer test-coverage
```

### JavaScript tests

Run Jest tests:

```bash
npm test
```

Watch mode:

```bash
npm test -- --watch
```

### End-to-end tests

Using Playwright:

```bash
npm run test:e2e
```

Specific test:

```bash
npm run test:e2e -- --grep "checkout"
```

### Continuous integration

GitHub Actions workflow runs on:

- Pull requests
- Main branch commits
- Release tags

Tests include:

- PHPUnit (PHP 7.4, 8.0, 8.1, 8.2)
- JavaScript tests
- PHPCS coding standards
- Plugin check validation

---

## Troubleshooting tests

### Common test failures

#### Issue: Fees not calculating

**Debug steps:**

1. Enable WP_DEBUG and WP_DEBUG_LOG
2. Check `/wp-content/debug.log`
3. Verify rule configuration
4. Check product origin settings

#### Issue: Database errors

**Debug steps:**

1. Check table exists: `SHOW TABLES LIKE '%cfwc_rules%'`
2. Verify table structure
3. Check charset compatibility
4. Review error logs

#### Issue: JavaScript errors

**Debug steps:**

1. Open browser console
2. Check for script conflicts
3. Verify jQuery loaded
4. Test with default theme

### Debug mode testing

Enable debug mode:

```php
// wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'SCRIPT_DEBUG', true );
```

Check debug output:

```bash
tail -f wp-content/debug.log
```

### Performance profiling

Using Query Monitor:

1. Install Query Monitor plugin
2. Add products to cart
3. Check Queries tab
4. Review slow queries

Using Debug Bar:

1. Install Debug Bar plugin
2. Enable WP_DEBUG
3. Check console for warnings
4. Review deprecated notices

---

## Test checklist

### Pre-release checklist

#### Functionality

- [ ] Plugin activates without errors
- [ ] Settings save correctly
- [ ] Rules create, edit, delete properly
- [ ] Fees calculate accurately
- [ ] Fees display in cart
- [ ] Fees display in checkout
- [ ] Fees appear in order emails
- [ ] Import/export works
- [ ] Bulk operations function

#### Compatibility

- [ ] WordPress 6.0+
- [ ] WooCommerce 8.0+
- [ ] PHP 7.4, 8.0, 8.1, 8.2
- [ ] HPOS enabled
- [ ] HPOS disabled
- [ ] Block checkout
- [ ] Classic checkout
- [ ] Default themes
- [ ] RTL languages

#### Performance

- [ ] Page load under 3 seconds
- [ ] No memory leaks
- [ ] Efficient database queries
- [ ] No JavaScript errors
- [ ] No PHP warnings

#### Security

- [ ] Input sanitization
- [ ] Output escaping
- [ ] Nonce verification
- [ ] Capability checks
- [ ] SQL injection prevention

#### Code quality

- [ ] PHPCS passing
- [ ] PHPUnit tests passing
- [ ] JavaScript tests passing
- [ ] No deprecated functions
- [ ] Documentation complete

---

## Reporting issues

When reporting test failures:

1. **Environment details**

   - WordPress version
   - WooCommerce version
   - PHP version
   - Theme name
   - Active plugins

2. **Steps to reproduce**

   - Exact sequence of actions
   - Test data used
   - Expected result
   - Actual result

3. **Error information**

   - Error messages
   - Debug log entries
   - Screenshots
   - Browser console errors

4. **Additional context**
   - When issue started
   - Recent changes
   - Workarounds tried

---

_Last updated: January 2025 | Version 1.0.0_
