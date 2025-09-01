# Customs Fees for WooCommerce - Development Tasks

## Priority 1: Critical Features (Product Manager Feedback)

### 1. CSV Import/Export for Bulk Updates ✅ COMPLETED

**Problem:** No way to bulk update Country of Origin for products  
**Solution:** Add WooCommerce CSV import/export integration

#### Tasks:

- [x] Create `class-cfwc-export-import.php` handler class
- [x] Add export columns for HS Code and Country of Origin
- [x] Convert country codes to names on export for readability
- [x] Add import mapping for both country codes and names
- [x] Support multiple column name variations (HS Code, Tariff Code, etc.)
- [x] Handle variations (inherit from parent or override)
- [x] Add documentation for bulk update process

**Acceptance Criteria:**

- Merchants can export products with customs data
- Country names are human-readable in CSV (not just codes)
- Import accepts both "United States" and "US" formats
- Columns auto-map on import screen

---

### 2. Product Category-Based Tariff Rules 🎯 NEW

**Problem:** Current country-only logic is too broad for complex tariff structures  
**Example:** China→US tariffs vary widely by product category (7.5%-100%)

#### Tasks:

- [ ] Research WooCommerce product category structure
- [ ] Extend rule structure to support category-based rules
- [ ] Add category selector to rule creation UI
- [ ] Support both product categories and tags
- [ ] Allow HS Code range matching (e.g., 6109.\* for all apparel)
- [ ] Create category mapping presets for common scenarios
- [ ] Add "Product Category" column in rules table

**Implementation Ideas:**

```php
// New rule structure
[
    'from_country' => 'CN',
    'to_country' => 'US',
    'product_category' => 'electronics', // NEW
    'hs_code_range' => '8471.*',        // NEW
    'rate' => 50,
    'label' => 'Section 301 - Electronics'
]
```

**Category Examples (China→US):**

- Apparel/Clothing: 7.5% - 70%
- Electronics: 0% - 50%
- Steel/Aluminum: 25% - 50%
- Automobiles: 25% - 100%
- Solar Equipment: 50%
- Batteries: 25%
- Medical Devices: 25% - 50%

---

### 3. Rule Stacking Clarity & UI Improvements 📊

**Problem:** Users don't understand that multiple rules can stack

#### Tasks:

- [ ] Add visual indicator when multiple rules apply
- [ ] Show rule stacking preview in admin
- [ ] Add "Preview Calculation" feature
- [ ] Create rule priority/order system
- [ ] Add option to make rules exclusive (non-stacking)
- [ ] Show warning when preset + general rules overlap
- [ ] Add "Effective Rate" calculator tool

**UI Improvements:**

- Show "⚠️ This rule stacks with X other rules" warning
- Add toggle: "[ ] Allow stacking with other rules"
- Preview panel showing: Base + Additional = Total
- Rule testing tool: "Test with sample cart"

---

### 4. User Guidance & Onboarding ✅ COMPLETED

**Problem:** Users don't know they need to set Country of Origin on products

#### Tasks:

- [x] ~~Add setup wizard/onboarding flow~~ (Simplified to status notice)
- [x] Create admin notice for products missing origin
- [x] Add "Quick Start Guide" to settings page (Setup status)
- [x] Show product count with/without origin data
- [x] Add bulk action to set origin for multiple products (Quick/Bulk Edit)
- [x] Context-aware admin notice (hides buttons when on relevant pages)
- [x] Permanent dismissal with reactivation reset
- [x] Optimized plugin activation/deactivation performance
- [ ] Create video tutorial links (Future)
- [ ] Add contextual help tabs (Future)

**Completed Features:**

1. Admin notice shows on all pages for 10 minutes after activation
2. Context-aware buttons (no "Go to Products" when on products page)
3. Quick/Bulk edit for HS Code and Country of Origin
4. CSV Import/Export for bulk updates
5. Setup status on settings page with helpful guidance

---

## Priority 2: Enhanced Features

### 5. Advanced HS Code Features

- [ ] HS Code validation (check format)
- [ ] HS Code lookup/suggestion tool
- [ ] Support HS Code hierarchies (2, 4, 6, 8, 10 digits)
- [ ] Import HS Code database for auto-complete
- [ ] Map product categories to common HS codes

### 6. Reporting & Analytics

- [ ] Add customs fee reports to WooCommerce Analytics
- [ ] Show fee breakdown by country/category
- [ ] Export customs data for accounting
- [ ] Track which rules are most used
- [ ] Identify products missing customs data

### 7. Integration Improvements

- [ ] Support for multi-vendor (each vendor sets origin) for now we cna work on our own product vendor plugins.
- [ ] Integration with shipping plugins
- [ ] Support for dropshipping scenarios
- [ ] API for external systems
- [ ] Webhook for fee calculations

---

## Priority 3: Future Enhancements

### 8. Block Editor Support (Started)

- [ ] Create checkout block for fee breakdown
- [ ] Add product block for HS code display
- [ ] Support block-based cart display
- [ ] Create admin block for rule management

### 9. Advanced Calculations

- [ ] Support tiered rates (e.g., first $800 free)
- [ ] Minimum/maximum fee limits
- [ ] Compound calculations
- [ ] Currency-specific rules
- [ ] Date-based rules (tariff changes)

### 10. Compliance & Documentation

- [ ] Add compliance mode (strict vs. estimate)
- [ ] Generate customs documentation
- [ ] Support for customs forms
- [ ] Integration with customs APIs
- [ ] Audit trail for fee calculations

---

## Technical Debt & Optimization

### Code Quality

- [x] Remove inline CSS/JS ✅ COMPLETED
- [x] Implement proper asset enqueueing ✅ COMPLETED
- [x] Add proper nonce verification ✅ COMPLETED
- [x] Improve notification system ✅ COMPLETED
- [ ] Add unit tests
- [ ] Add integration tests
- [ ] Performance optimization for large catalogs
- [ ] Add caching for fee calculations

### Documentation

- [ ] Complete inline documentation
- [ ] Create developer documentation
- [ ] Add code examples
- [ ] Create API documentation
- [ ] Add troubleshooting guide

---

## Implementation Timeline

### Phase 1 (Immediate - Week 1)

1. CSV Import/Export ← **Currently Planned**
2. Basic user guidance/onboarding
3. Rule stacking clarity

### Phase 2 (Week 2-3)

4. Product category-based rules
5. HS Code improvements
6. Advanced rule options

### Phase 3 (Week 4+)

7. Reporting features
8. Block editor support
9. Advanced integrations

---

## Success Metrics

- **User Adoption**: Products with origin data > 80%
- **Accuracy**: Category-based rules reduce calculation errors
- **Efficiency**: Bulk update reduces setup time by 90%
- **Clarity**: Support tickets about stacking decrease by 50%
- **Engagement**: Users create average of 5+ rules

---

## Notes from Product Manager

> "The China→US scenario is our most complex use case. If we can handle the varying tariff rates by product category (electronics at 0-50%, apparel at 7.5-70%, etc.), we'll cover most merchant needs. The current country-only approach works for simple scenarios but falls short for real-world compliance."

> "Merchants are used to working with CSV files for bulk updates. This is table stakes for any serious e-commerce plugin. They should be able to export, update in Excel, and reimport without technical knowledge."

> "The stacking confusion is real. One merchant thought they were charging 10% but actually had three rules stacking to 30%. We need better visualization of how rules combine."

---

## Related Files

- `/includes/class-cfwc-calculator.php` - Core calculation logic
- `/includes/admin/views/rules-section.php` - Rules UI
- `/includes/class-cfwc-products.php` - Product meta handling
- `/includes/admin/class-cfwc-admin.php` - Admin functionality

---

_Last Updated: January 2025_
_Version: 1.1.0-dev_
