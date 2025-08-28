# Customs Fees for WooCommerce

A mini plugin for WooCommerce that adds transparent customs and import fee calculations to checkout. Built with simple fee tables similar to WooCommerce tax settings - no complex API integrations required.

## Purpose

With the U.S. ending its de minimis exemption on **August 29, 2025**, all international shipments will require customs duties regardless of value. This plugin helps merchants:

- **Add transparency** to international orders.
- **Prevent cart abandonment** from surprise fees.
- **Build customer trust** with upfront total costs.
- **Stay compliant** with new regulations.

## Progress

### Completed

- [x] Main plugin structure with WooCommerce dependency checking
- [x] Fee calculation engine with country-based rules
- [x] Settings framework integrated into WooCommerce (simplified UX)
- [x] Preset system integrated into fee rules (US, EU, China, UK, Canada, Australia)
- [x] Admin functionality with product HS code fields
- [x] Display handler for cart, checkout, orders, and emails
- [x] HPOS (High-Performance Order Storage) compatibility
- [x] Security implementation (nonces, sanitization, escaping)
- [x] Database table for fee calculation logs
- [x] Email integration with fee display
- [x] AJAX handlers for admin operations
- [x] Streamlined admin settings page (simplified UX)

### In Progress

- [ ] WooCommerce Blocks checkout integration (framework ready)
- [ ] Admin UI JavaScript for rule management
- [ ] Testing with real scenarios

### TODO

- [ ] CSV import/export functionality
- [ ] Frontend tooltips and notices
- [ ] Agreement checkbox at checkout
- [ ] Create comprehensive testing suite
- [ ] Add more preset templates
- [ ] Performance optimization
- [ ] Multi-language support (POT file)

### Quick Start

1. Go to **WooCommerce > Settings > Tax > Customs Fees**
2. Select a preset (e.g., "US General Import")
3. Click **"Add Preset Rules"**
4. Click **"Save changes"**
5. Fees will now appear at checkout for matching countries!

### Available Presets

- [x] **US General Import (10%)** - Standard US import duty
- [x] **EU to US Import** - Common EU→US rates
- [x] **China to US Import** - Chinese goods duties
- [x] **UK VAT & Duty** - UK import VAT (20%) + duty
- [x] **Canada GST & Duty** - Canadian import fees
- [x] **Australia GST** - Australian GST (10%)

### Hooks and Filters

```
// Modify calculated fees
add_filter( 'cfwc_calculated_fees', 'my_custom_fees', 10, 3 );

// Filter country-specific rules
add_filter( 'cfwc_country_rules', 'my_country_rules', 10, 2 );

// Customize fee labels
add_filter( 'cfwc_fee_label', 'my_fee_label', 10, 3 );

// Conditionally skip fees
add_filter( 'cfwc_apply_fee', 'my_fee_conditions', 10, 3 );

// Include/exclude shipping in calculation
add_filter( 'cfwc_include_shipping_in_calculation', '__return_false' );
```

## Testing

### Testing Status

- **Core Functionality**

  - Plugin activation and HPOS compatibility
  - WooCommerce dependency check
  - Security standards compliance (PHPCS)
  - Cache management for performance

- **Admin Interface**

  - Settings integrated under WooCommerce Tax tab
  - Dynamic rules management (add/edit/delete)
  - Inline editing without page reload
  - Save button auto-activation
  - Real-time validation

- **Preset System**

  - US, EU, and China import presets
  - Duplicate prevention when applying
  - Append or replace modes

- **Fee Calculation**

  - Country-based detection
  - Percentage and flat rate support
  - Minimum/maximum thresholds
  - Multiple rules per country
  - Tax-inclusive calculations

- **Frontend Display**
  - Cart and checkout integration (classic)
  - Display modes (single line vs breakdown)
  - Tooltip/help text with (?) icon
  - Responsive design
  - Auto-translation support

#### In Progress

- Display in order emails
- Display in My Account order details
- Admin order view integration

#### Next Up: HS Code Integration

- Add HS Code field to products.
- Country of Origin tracking.
- Display customs info throughout purchase flow.
- More accurate duty calculations.
- WooCommerce Blocks checkout support
- CSV import/export for rules
- Product category-based rules
- Weight-based calculations
- API integrations

### Test Scenarios

1. **US Import (10%):** $100 cart = $10 fee.
2. **With Minimum:** $30 cart with $5 minimum = $5 fee.
3. **Multiple Rules:** Test priority/ordering.
4. **Tax Application:** Verify taxable fees.

## License

GPL v2 or later - Same as WordPress,

## Privacy

This plugin:

- Does NOT collect personal data.
- Does NOT phone home.
- Does NOT use external APIs (by default).
- Stores logs locally only (optional).
- All calculations done server-side.
