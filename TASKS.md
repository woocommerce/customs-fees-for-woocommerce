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

### 2. Product Category-Based Tariff Rules 🎯 CRITICAL

**Problem:** Current country-only logic is **fundamentally flawed** for real tariffs  
**Reality:** China→US tariffs range from 0% to 100% depending on product category!

#### Current System Flaws:

- ❌ Single rate for all China→US products (completely wrong!)
- ❌ No way to handle category-specific tariffs
- ❌ Can't implement actual Section 301/232 tariffs correctly
- ❌ Forces merchants to create workarounds or incorrect calculations

#### Refined Implementation Plan:

##### Phase 1: Data Structure Enhancement

- [ ] Extend rule structure to support multiple matching criteria:
  ```php
  [
      'from_country' => 'CN',
      'to_country' => 'US',
      'match_type' => 'category|hs_code|all', // NEW
      'category_ids' => [15, 22],             // WooCommerce category IDs
      'hs_code_pattern' => '6109*',           // HS Code prefix matching
      'rate' => 69,                            // Actual apparel rate
      'label' => 'Section 301 - Apparel',
      'priority' => 10,                        // Higher priority = checked first
      'notes' => 'Apparel particularly high (~69%)'
  ]
  ```

##### Phase 2: Matching Logic Implementation

- [ ] Create rule matching priority system:
  1. **Exact HS Code match** (highest priority)
  2. **HS Code prefix match** (e.g., 6109\* for apparel)
  3. **Product category match**
  4. **Country-only match** (fallback)
- [ ] Implement `class-cfwc-rule-matcher.php` for complex matching
- [ ] Support AND/OR logic for multiple criteria
- [ ] Add rule testing/preview functionality

##### Phase 3: UI Enhancements

- [ ] Redesign rule creation form with tabs:
  - **Basic**: Country selection
  - **Products**: Category selector, HS code field
  - **Rate**: Percentage/flat, stacking options
  - **Advanced**: Priority, date ranges, notes
- [ ] Add visual rule builder with conditions
- [ ] Show which products match each rule (preview)
- [ ] Add "Import from Template" for common scenarios

##### Phase 4: Pre-configured Templates

- [ ] Create accurate China→US templates based on actual tariffs:
  ```php
  $china_us_templates = [
      ['name' => 'Apparel/Clothing', 'hs_prefix' => '61,62', 'rate' => 7.5, 'section_301_rate' => 61.5],
      ['name' => 'Electronics (Consumer)', 'hs_prefix' => '85', 'rate' => 0, 'max_rate' => 50],
      ['name' => 'Steel Products', 'rate' => 25, 'section_232' => true],
      ['name' => 'Aluminum Products', 'rate' => 10, 'section_232' => true],
      ['name' => 'Automobiles', 'rate' => 25, 'additional' => 75],
      ['name' => 'Auto Parts', 'rate' => 25],
      ['name' => 'Solar Equipment', 'rate' => 50],
      ['name' => 'Batteries (Non-EV)', 'rate' => 25],
      ['name' => 'Medical Devices', 'rate' => 25, 'specific_items' => 50],
      ['name' => 'Semiconductors', 'rate' => 50],
      ['name' => 'Footwear', 'rate' => 7.5, 'max_rate' => 15],
      ['name' => 'Toys', 'rate' => 7.5, 'max_rate' => 25],
  ];
  ```

##### Phase 5: Rule Management & Testing

- [ ] Add bulk rule import/export (CSV)
- [ ] Create rule conflict detection
- [ ] Add "Test Product" feature to see which rules apply
- [ ] Implement rule versioning/history
- [ ] Add effective date management (tariffs change!)

#### Technical Architecture:

```php
// New database structure (wp_cfwc_rules table)
CREATE TABLE wp_cfwc_rules (
    id INT PRIMARY KEY,
    from_country VARCHAR(2),
    to_country VARCHAR(2),
    match_type ENUM('all', 'category', 'hs_code', 'combined'),
    category_ids TEXT,  // JSON array
    hs_code_pattern VARCHAR(20),
    rate DECIMAL(5,2),
    rate_type ENUM('percentage', 'flat'),
    priority INT DEFAULT 0,
    stacking_allowed BOOLEAN DEFAULT true,
    effective_from DATE,
    effective_to DATE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### Success Criteria:

- ✅ Can accurately represent China→US tariff complexity
- ✅ Merchants can set different rates for different product types
- ✅ System matches most specific rule first
- ✅ Clear UI shows which rule applies to which product
- ✅ Calculation accuracy matches real-world tariffs

---

### 3. Rule Stacking Clarity & UI Improvements 📊 CRITICAL

**Problem:** Users selecting "China → US" preset don't realize it stacks with general US rules, resulting in unexpected total rates (e.g., 25% + 10% = 35%)

#### Immediate Fixes Needed:

##### Phase 1: Visual Clarity (Quick Win)

- [ ] Add stacking indicator in rules table:
  ```
  ⚠️ China → US (25%) + General → US (10%) = 35% TOTAL
  ```
- [ ] Color-code stacking rules (yellow background when multiple apply)
- [ ] Add "Effective Rates" summary box showing all combinations
- [ ] Show warning icon when creating overlapping rules

##### Phase 2: Stacking Control Options

- [ ] Add stacking mode selector per rule:
  - **Stack** (default): Adds to other rules
  - **Override**: Replaces lower priority rules
  - **Exclusive**: Only this rule applies if matched
- [ ] Implement priority system (1-100, higher = checked first)
- [ ] Add "Preview Impact" before saving rule

##### Phase 3: Testing & Preview Tools

- [ ] Create "Rate Calculator" tool:

  ```
  Test Product: [_____________________]
  Origin: [China ▼]  Destination: [United States ▼]
  Category: [Electronics ▼]  HS Code: [______]

  MATCHING RULES:
  ✓ China → US Electronics (Section 301): 25%
  ✓ General → US Import: 10%
  ─────────────────────────────
  TOTAL CUSTOMS FEE: 35%
  ```

- [ ] Add "Dry Run" mode for testing without affecting live site
- [ ] Show affected products count when editing rules

##### Phase 4: Smart Defaults & Guidance

- [ ] When selecting preset, ask: "Replace existing rules or add to them?"
- [ ] Add setup wizard with common scenarios:
  - "I only ship from one country" → Simple setup
  - "I ship from multiple countries" → Advanced setup
  - "I need category-specific rates" → Expert setup
- [ ] Include tooltips explaining stacking behavior
- [ ] Add "Common Mistakes" warning section

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
