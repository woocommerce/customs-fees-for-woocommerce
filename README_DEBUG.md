# Debug Logging Guide for Customs & Import Fees

## Overview

The plugin now includes comprehensive debug logging to help troubleshoot issues with customs fee calculations. When enabled, it logs detailed information about:

- Product types (physical, virtual, downloadable)
- Why products are skipped
- Country of origin configuration
- Rule matching logic
- Fee calculations

## How to Enable Debug Logging

### Method 1: WordPress Debug Log

Add these lines to your `wp-config.php`:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

Logs will be saved to: `/wp-content/debug.log`

### Method 2: WooCommerce Status Logs

When WP_DEBUG is enabled, the plugin automatically logs to WooCommerce:

- Navigate to: **WooCommerce > Status > Logs**
- Select log file: `customs-fees-debug-{date}-{hash}.log`

## What Gets Logged

### 1. Calculation Start

```
[CFWC] Starting customs fee calculation for destination: US with 5 rules configured
```

### 2. Skipped Products (Virtual/Downloadable)

```
[CFWC]   SKIPPED Product: "Digital Download" (ID: 123, SKU: DL-001) - Reason: Virtual product (no customs fees apply)
[CFWC]   SKIPPED Product: "Software License" (ID: 124, SKU: SW-001) - Reason: Downloadable product (no customs fees apply)
```

### 3. Skipped Products (No Origin)

```
[CFWC]   SKIPPED Product: "Basic T-Shirt" (ID: 125, SKU: TS-001) - Reason: No country of origin configured
```

### 4. Physical Products Being Processed

```
[CFWC]   PROCESSING Product: "Leather Bag" (ID: 126, SKU: BAG-001) - Type: Physical, Origin: IN, HS Code: 4202.11, Line Total: $99.00
[CFWC]     → Found 1 matching rule(s) for IN → US
[CFWC]       Applied: US Tariff (India) (25%) = $24.75
```

### 5. Calculation Summary

```
[CFWC] Calculation Summary:
[CFWC]   • US Tariff (Bangladesh) (20%): $138.24
[CFWC]   • US Tariff (Indonesia) (19%): $177.91
[CFWC] Total Customs & Import Fees: $316.15
```

## Sample Debug Output

Here's what you'll see in your debug.log for a typical cart:

```
[01-Sep-2025 19:30:00 UTC] [CFWC] Starting customs fee calculation for destination: US with 5 rules configured
[01-Sep-2025 19:30:00 UTC] [CFWC]   SKIPPED Product: "WordPress Plugin" (ID: 100, SKU: WP-PLG) - Reason: Virtual product (no customs fees apply)
[01-Sep-2025 19:30:00 UTC] [CFWC]   PROCESSING Product: "Copper Pants" (ID: 101, SKU: CP-001) - Type: Physical, Origin: TR, HS Code: 6204.44, Line Total: $830.03
[01-Sep-2025 19:30:00 UTC] [CFWC]     → Found 1 matching rule(s) for TR → US
[01-Sep-2025 19:30:00 UTC] [CFWC]       Applied: US Tariff (Turkey) (15%) = $124.50
[01-Sep-2025 19:30:00 UTC] [CFWC]   PROCESSING Product: "Concrete Bag" (ID: 102, SKU: CB-001) - Type: Physical, Origin: IN, HS Code: 8471.30, Line Total: $986.66
[01-Sep-2025 19:30:00 UTC] [CFWC]     → Found 1 matching rule(s) for IN → US
[01-Sep-2025 19:30:00 UTC] [CFWC]       Applied: US Tariff (India) (25%) = $246.67
[01-Sep-2025 19:30:00 UTC] [CFWC] Calculation Summary:
[01-Sep-2025 19:30:00 UTC] [CFWC]   • US Tariff (Turkey) (15%): $124.50
[01-Sep-2025 19:30:00 UTC] [CFWC]   • US Tariff (India) (25%): $246.67
[01-Sep-2025 19:30:00 UTC] [CFWC] Total Customs & Import Fees: $371.17
```

## Troubleshooting Common Issues

### Issue: Rules not applying to products

Check the debug log for:

1. **Virtual/Downloadable products**: These are correctly excluded
2. **Missing origin**: Product needs country of origin set
3. **No matching rules**: Check if origin→destination combination exists

### Issue: Wrong fees calculated

Look for:

1. **Line totals**: Verify the product prices are correct
2. **Rule percentages**: Check if the correct rate is being applied
3. **Multiple rules**: Check stacking mode (Add/Override/Exclusive)

### Issue: Some products missing fees

Debug log will show exactly why:

- `SKIPPED ... Reason: Virtual product`
- `SKIPPED ... Reason: No country of origin configured`
- `No matching rules found for CN → US`

## Disabling Debug Logging

To disable debug logging, set in `wp-config.php`:

```php
define( 'WP_DEBUG', false );
```

Or remove/comment out the debug constants entirely.

## Support

When reporting issues, please:

1. Enable debug logging
2. Reproduce the issue
3. Share relevant log entries
4. Include product configuration (type, origin, HS code)

This helps us quickly identify and resolve any problems!
