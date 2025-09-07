# Quick Start Guide - Customs Fees for WooCommerce

Welcome! This guide will help you set up customs fee calculations in under five minutes.

## Step 1: Access settings (30 seconds)

After activating the plugin, navigate to:
**WooCommerce > Settings > Tax > Customs Fees**

You'll see the main configuration page with two sections:

- Global settings
- Fee rules management

---

## Step 2: Set default origin (30 seconds)

Choose how to determine product origin for items without individual settings:

1. **Use store location** - Products ship from your store address
2. **Custom country** - Select a specific default country
3. **None** - Each product must have origin set individually

**Tip:** Most stores choose "Custom country" and select their primary supplier location.

---

## Step 3: Create your first rule (2 minutes)

Click **Add New Rule** and configure:

### Example: 10 percent import duty from China to United States

**Basic Information:**

- **Rule Name:** China to US Import Duty
- **Status:** Active

**Countries:**

- **From Country:** China (CN)
- **To Country:** United States (US)

**Fee Configuration:**

- **Fee Type:** Percentage
- **Fee Amount:** 10
- **Priority:** 10 (default)

**Matching Criteria:**

- **Match Type:** All Products
- **Stacking Mode:** Add (default)

Click **Save Rule** to activate.

---

## Step 4: Configure a product (1 minute)

Edit any product and scroll to **Product data > Inventory**:

1. **Country of Origin:** Select "China"
2. **HS Code:** Enter code (e.g., "6109" for T-shirts)
   - Optional but recommended for precise calculations

Click **Update** to save.

---

## Step 5: Test your setup (1 minute)

1. Add the configured product to cart
2. View cart - initial fee displays
3. Go to checkout
4. Enter a United States shipping address
5. See customs fee in order total

**Expected result:** 10 percent customs fee appears

---

## Common rule examples

### Electronics from anywhere to EU

```
Name: EU Electronics Import
From: All Countries
To: Any EU Country
Categories: Electronics
Fee Type: Percentage
Amount: 20
```

### Fixed handling fee for batteries

```
Name: Battery Handling Fee
HS Codes: 8506,8507
Fee Type: Fixed
Amount: 15.00
Match Type: HS Code
```

### Duty-free books

```
Name: Books Duty Free
Categories: Books
Fee Type: Percentage
Amount: 0
Priority: 1 (highest)
Stacking: Exclusive
```

---

## Pro tips for success

### Start simple

Begin with one or two general rules, then add specific ones as needed.

### Use priorities wisely

- **1-10:** Exemptions and special cases
- **11-50:** Specific product rules
- **51-100:** General category rules
- **100+:** Fallback rules

### Test incrementally

After each rule, test with sample products before adding more.

### Document your rules

Use clear, descriptive names like "China Electronics 25% Section 301" instead of "Rule 1".

---

## Next steps

### Essential tasks

- [ ] Add rules for your main product categories
- [ ] Set origin for your top 20 products
- [ ] Test complete checkout flow
- [ ] Review order emails

### Advanced configuration

- [ ] Add HS codes for precise calculations
- [ ] Configure category-specific rules
- [ ] Set up rule priorities
- [ ] Import bulk rules via CSV

### Going live

- [ ] Test with real product data
- [ ] Verify calculations match regulations
- [ ] Update checkout page text if needed
- [ ] Train customer service team
---

## Checklist for going live

Before enabling for customers:

✅ **Rules configured**

- [ ] General import rules created
- [ ] Special product rules added
- [ ] Exemptions configured

✅ **Products ready**

- [ ] Origins set for main products
- [ ] HS codes added where known
- [ ] Test products verified

✅ **Testing complete**

- [ ] Cart calculations correct
- [ ] Checkout display works
- [ ] Order emails show fees
- [ ] Customer account displays fees

✅ **Customer communication**

- [ ] Checkout text explains fees
- [ ] FAQ page updated
- [ ] Support team briefed

---

**Congratulations!** 🎉

You're ready to provide transparent customs fee calculations for your international customers.

---

_Quick Start Guide v1.0 | Updated January 2025_
