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
- [x] Settings framework integrated into WooCommerce
- [x] Preset templates system (US, EU, China, UK, Canada, Australia)
- [x] Admin functionality with product HS code fields
- [x] Display handler for cart, checkout, orders, and emails
- [x] HPOS (High-Performance Order Storage) compatibility
- [x] Security implementation (nonces, sanitization, escaping)
- [x] Database table for fee calculation logs
- [x] Email integration with fee display
- [x] AJAX handlers for admin operations
- [x] Basic admin settings page

### In Progress (10%)

- [ ] WooCommerce Blocks checkout integration (framework ready)
- [ ] Admin UI JavaScript for rule management
- [ ] Testing with real scenarios

### TODO (5%)

- [ ] CSV import/export functionality
- [ ] Frontend tooltips and notices
- [ ] Agreement checkbox at checkout
- [ ] Create comprehensive testing suite
- [ ] Add more preset templates
- [ ] Performance optimization
- [ ] Multi-language support (POT file)

### Quick Start Example

```
// Add 10% customs fee for US orders
WooCommerce > Settings > Customs Fees
├── Enable customs fees: ✓
├── Display on cart: ✓
├── Display on checkout: ✓
└── Add Rule:
    ├── Country: United States (US)
    ├── Type: Percentage
    ├── Rate: 10%
    ├── Minimum: $5
    └── Label: US Import Duty
```

### Available Preset Templates

- [ ] **US General Import (10%)** - Standard US import duty
- [ ] **EU to US Import** - Common EU→US rates
- [ ] **China to US Import** - Chinese goods duties
- [ ] **UK VAT & Duty** - UK import VAT (20%) + duty
- [ ] **Canada GST & Duty** - Canadian import fees
- [ ] **Australia GST** - Australian GST (10%)

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

### Manual Testing Checklist

- [x] Plugin activation without errors
- [x] WooCommerce dependency check
- [x] Settings page loads correctly
- [x] HPOS compatibility verified
- [x] Security standards compliance (PHPCS)
- [ ] Rules can be added/edited/deleted
- [ ] Preset templates apply correctly
- [ ] Fees calculate on cart page
- [ ] Fees display on checkout (classic)
- [ ] Fees display on checkout (blocks)
- [ ] Fees appear in order emails
- [ ] Fees show in admin order view
- [ ] HS codes save on products
- [ ] AJAX operations work
- [ ] Cache clearing works

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
