<?php
/**
 * Fee calculator class.
 *
 * Handles calculation of customs fees based on rules.
 *
 * @package CustomsFeesForWooCommerce
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CFWC_Calculator class.
 *
 * @since 1.0.0
 */
class CFWC_Calculator {

	/**
	 * Rules cache.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $rules_cache = null;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Constructor intentionally left empty.
		// Use init() method for initialization.
	}

	/**
	 * Get all rules from settings.
	 *
	 * @since 1.2.0
	 * @return array Array of rules.
	 */
	private function get_rules() {
		if ( null === $this->rules_cache ) {
			$this->rules_cache = get_option( 'cfwc_rules', array() );
		}
		return $this->rules_cache;
	}

	/**
	 * Initialize the calculator.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		// No initialization needed at this time.
	}

	/**
	 * Calculate fees for the cart.
	 * 
	 * IMPORTANT: How Customs & Import Fees Work
	 * ==========================================
	 * In real-world customs, when a shipment contains multiple products:
	 * 
	 * 1. Each product type (HS code) is assessed individually
	 * 2. The duty rate for each product is based on its specific HS code
	 * 3. The fee for each product = (product value) × (duty rate)
	 * 4. Total customs fees = sum of all individual product fees
	 * 
	 * Example: If you ship 2 electronics (25% duty) and 1 battery (25% duty):
	 * - Electronics: $100 × 25% = $25
	 * - Electronics: $100 × 25% = $25  
	 * - Battery: $50 × 25% = $12.50
	 * - Total customs fees = $62.50
	 * 
	 * Rule Matching Priority:
	 * - More specific HS codes have priority over broader patterns
	 * - Example: "8506*" (batteries) beats "85*" (electronics) for HS code 8506
	 * - The specificity score considers pattern length and complexity
	 *
	 * @since 1.0.0
	 * @param WC_Cart $cart The WooCommerce cart object.
	 * @return array Array of fees to add.
	 */
	public function calculate_fees( $cart ) {
		$fees = array();

		// Get customer shipping country.
		$destination_country = $this->get_customer_country();
		if ( empty( $destination_country ) ) {
			return $fees;
		}

		// Get all rules (not filtered by country for new matching system).
		$rules = $this->get_rules();
		if ( empty( $rules ) ) {
			return $fees;
		}

		// Initialize rule matcher for advanced matching (v1.2.0).
		$rule_matcher = new CFWC_Rule_Matcher();

		// Log calculation start.
		$this->log_debug(
			sprintf(
				'Starting customs fee calculation for destination: %s with %d rules configured',
				$destination_country,
				count( $rules )
			)
		);

		// Calculate fees per product for accurate category/HS code matching.
		$fees_by_label = array(); // Group fees by label for consolidation.

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			$product    = $cart_item['data'];
			$product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];

			// Build product info for logging.
			$product_info = sprintf(
				'Product: "%s" (ID: %d, SKU: %s)',
				$product->get_name(),
				$product_id,
				$product->get_sku() ? $product->get_sku() : 'N/A'
			);

			// Check and log if virtual (purely digital products).
			if ( $product->is_virtual() ) {
				$this->log_debug(
					sprintf(
						'  SKIPPED %s - Reason: Virtual product (no customs fees apply)',
						$product_info
					)
				);
				continue;
			}

			// Check and log if doesn't need shipping.
			if ( ! $product->needs_shipping() ) {
				$this->log_debug(
					sprintf(
						'  SKIPPED %s - Reason: Product does not require shipping',
						$product_info
					)
				);
				continue;
			}

			// Get product origin using centralized helper.
			if ( class_exists( 'CFWC_Products_Variation_Support' ) ) {
				$customs_data = CFWC_Products_Variation_Support::get_product_customs_data( $product );
				$origin = $customs_data['origin'];
			} else {
				// Fallback to direct meta lookup.
				$origin = get_post_meta( $product_id, '_cfwc_country_of_origin', true );
				
				// For variations, check parent product if no origin found on variation.
				if ( empty( $origin ) && $product->get_parent_id() ) {
					$origin = get_post_meta( $product->get_parent_id(), '_cfwc_country_of_origin', true );
				}
			}

			// Use default origin if no origin set on product.
			if ( empty( $origin ) ) {
				$origin = $this->get_default_origin();
				
				// Skip if still no origin after checking default.
				if ( empty( $origin ) ) {
					$this->log_debug(
						sprintf(
							'  SKIPPED %s - Reason: No country of origin configured and no default origin set',
							$product_info
						)
					);
					continue;
				} else {
					$this->log_debug(
						sprintf(
							'  Using default origin (%s) for %s',
							$origin,
							$product_info
						)
					);
				}
			}

			// Get matching rules for this product.
			$matching_rules = $rule_matcher->find_matching_rules(
				$product,
				$origin,
				$destination_country,
				$rules
			);

			// Calculate fees for this product.
			$line_total = $cart_item['line_total'];

			// Get HS code using centralized helper.
			if ( class_exists( 'CFWC_Products_Variation_Support' ) ) {
				$customs_data = CFWC_Products_Variation_Support::get_product_customs_data( $product );
				$hs_code = $customs_data['hs_code'];
			} else {
				// Fallback to direct meta lookup.
				$hs_code = get_post_meta( $product_id, '_cfwc_hs_code', true );
				
				// For variations, check parent product if no HS code found on variation.
				if ( empty( $hs_code ) && $product->get_parent_id() ) {
					$hs_code = get_post_meta( $product->get_parent_id(), '_cfwc_hs_code', true );
				}
			}
			$this->log_debug(
				sprintf(
					'  PROCESSING %s - Type: Physical, Origin: %s, HS Code: %s, Line Total: $%.2f',
					$product_info,
					$origin,
					$hs_code ? $hs_code : 'Not set',
					$line_total
				)
			);

			if ( count( $matching_rules ) > 0 ) {
				$this->log_debug(
					sprintf(
						'    → Found %d matching rule(s) for %s → %s',
						count( $matching_rules ),
						$origin,
						$destination_country
					)
				);
			} else {
				$this->log_debug(
					sprintf(
						'    → No specific rules found for %s → %s, checking for general import rule',
						$origin,
						$destination_country
					)
				);
				
				// Look for a general import rule (match_type = 'all') for this country pair
				foreach ( $rules as $rule_id => $rule ) {
					$match_type = $rule['match_type'] ?? 'all';
					$rule_from = $rule['from_country'] ?? $rule['origin_country'] ?? $rule['country'] ?? '';
					$rule_to   = $rule['to_country'] ?? $rule['country'] ?? '';
					
					// Check if this is a general rule for the same country pair
					if ( 'all' === $match_type && 
						 $rule_from === $origin && 
						 $rule_to === $destination_country ) {
						$rule['rule_id'] = $rule_id;
						$matching_rules[] = $rule;
						$this->log_debug(
							sprintf(
								'      → Found general import rule for %s → %s',
								$origin,
								$destination_country
							)
						);
						break; // Use the first matching general rule
					}
				}
				
				if ( empty( $matching_rules ) ) {
					$this->log_debug( '      → No general import rule found either' );
				}
			}

			// Process matching rules for this product.
			foreach ( $matching_rules as $rule ) {
				$fee = $this->calculate_single_fee( $rule, $line_total );
				if ( false !== $fee && $fee > 0 ) {
					$base_label = $this->get_fee_label( $rule, $destination_country, $origin );

					// Check if the label already contains a percentage (e.g., "(50%)")
					$has_percentage = preg_match( '/\(\d+(?:\.\d+)?%\)/', $base_label );

					// Add percentage rate in brackets only if not already present and it's a percentage rule.
					if ( ! $has_percentage && isset( $rule['type'] ) && 'percentage' === $rule['type'] && ! empty( $rule['rate'] ) ) {
						$label = sprintf( '%s (%s%%)', $base_label, $rule['rate'] );
					} else {
						// For flat rate or if percentage already in label, just use the base label.
						$label = $base_label;
					}

					// Create a unique key for grouping that includes the base label and rate
					$group_key = $base_label . '|' . ( $rule['type'] ?? 'flat' ) . '|' . ( $rule['rate'] ?? '0' );

					// Group fees by rule for consolidation.
					if ( ! isset( $fees_by_label[ $group_key ] ) ) {
						$fees_by_label[ $group_key ] = array(
							'base_label' => $base_label,
							'label'      => $label,
							'amount'     => 0,
							'count'      => 0,
							'rate'       => $rule['rate'] ?? '',
							'type'       => $rule['type'] ?? 'flat',
							'taxable'    => $this->is_fee_taxable( $rule ),
							'tax_class'  => $this->get_fee_tax_class( $rule ),
						);
					}

					$fees_by_label[ $group_key ]['amount'] += $fee;
					$fees_by_label[ $group_key ]['count']++;

					// Log fee application.
					$this->log_debug(
						sprintf(
							'      Applied: %s = $%.2f',
							$label,
							$fee
						)
					);
				}
			}
		}

		// Convert grouped fees to array and update labels with count.
		$fees = array();
		foreach ( $fees_by_label as $fee_data ) {
			// Update label to show count if more than one product matches
			if ( $fee_data['count'] > 1 ) {
				// Check if the label already has percentage
				$has_percentage = preg_match( '/\(\d+(?:\.\d+)?%\)/', $fee_data['label'] );
				
				if ( $has_percentage ) {
					// Label already has percentage, just add count
					$fee_data['label'] = sprintf( '%s x %d', 
						$fee_data['label'],
						$fee_data['count']
					);
				} elseif ( 'percentage' === $fee_data['type'] && ! empty( $fee_data['rate'] ) ) {
					// Add both percentage and count
					$fee_data['label'] = sprintf( '%s (%s%%) x %d', 
						$fee_data['base_label'], 
						$fee_data['rate'],
						$fee_data['count']
					);
				} else {
					// Just add count
					$fee_data['label'] = sprintf( '%s x %d', 
						$fee_data['base_label'],
						$fee_data['count']
					);
				}
			}
			// Remove temporary fields
			unset( $fee_data['base_label'], $fee_data['count'], $fee_data['rate'], $fee_data['type'] );
			$fees[] = $fee_data;
		}

		// Log summary.
		if ( ! empty( $fees ) ) {
			$this->log_debug( 'Calculation Summary:' );
			foreach ( $fees as $fee ) {
				$this->log_debug(
					sprintf(
						'  • %s: $%.2f',
						$fee['label'],
						$fee['amount']
					)
				);
			}
			$total_fees = array_sum( wp_list_pluck( $fees, 'amount' ) );
			$this->log_debug( sprintf( 'Total Customs & Import Fees: $%.2f', $total_fees ) );
		} else {
			$this->log_debug( 'No customs fees applied to this cart.' );
		}

		// Allow filtering of calculated fees.
		$fees = apply_filters( 'cfwc_calculated_fees', $fees, $destination_country, $cart );

		// Log the calculation if logging is enabled.
		$this->log_calculation( $destination_country, $cart->get_cart_contents_total(), $fees );

		return $fees;
	}

	/**
	 * Group cart items by origin country.
	 *
	 * @since 1.0.0
	 * @param WC_Cart $cart The cart object.
	 * @return array Items grouped by origin country.
	 */
	private function group_cart_items_by_origin( $cart ) {
		$groups = array();

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			$product = $cart_item['data'];

			// Skip virtual products (purely digital, no shipping required).
			if ( $product->is_virtual() ) {
				continue;
			}

			// Get product origin.
			$product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
			// Get origin using centralized helper.
			if ( class_exists( 'CFWC_Products_Variation_Support' ) ) {
				$customs_data = CFWC_Products_Variation_Support::get_product_customs_data( $product );
				$origin = $customs_data['origin'];
			} else {
				// Fallback to direct meta lookup.
				$origin = get_post_meta( $product_id, '_cfwc_country_of_origin', true );
				
				// For variations, check parent product if no origin found on variation.
				if ( empty( $origin ) && $product->get_parent_id() ) {
					$origin = get_post_meta( $product->get_parent_id(), '_cfwc_country_of_origin', true );
				}
			}

			// Use default origin if no origin set on product.
			if ( empty( $origin ) ) {
				$origin = $this->get_default_origin();
				// If still no origin, default to 'unknown'.
				if ( empty( $origin ) ) {
					$origin = 'unknown';
				}
			}

			// Initialize group if not exists.
			if ( ! isset( $groups[ $origin ] ) ) {
				$groups[ $origin ] = array(
					'items'           => array(),
					'shippable_items' => array(),
					'total'           => 0,
				);
			}

			// Add item to group.
			$groups[ $origin ]['items'][] = $cart_item;

			// Check if item needs shipping.
			if ( $product->needs_shipping() ) {
				$groups[ $origin ]['shippable_items'][] = $cart_item;

				// Add to total (including quantity).
				$item_total                  = $cart_item['line_total'];
				$groups[ $origin ]['total'] += $item_total;
			}
		}

		// Include shipping in totals if applicable.
		$include_shipping = apply_filters( 'cfwc_include_shipping_in_calculation', true );
		if ( $include_shipping && $cart->get_shipping_total() > 0 ) {
			// Distribute shipping proportionally across origin groups.
			$total_shippable = 0;
			foreach ( $groups as $origin => $data ) {
				$total_shippable += $data['total'];
			}

			if ( $total_shippable > 0 ) {
				$shipping_total = $cart->get_shipping_total();
				foreach ( $groups as $origin => &$data ) {
					if ( $data['total'] > 0 ) {
						$proportion     = $data['total'] / $total_shippable;
						$data['total'] += ( $shipping_total * $proportion );
					}
				}
			}
		}

		return $groups;
	}

	/**
	 * Get rules that match a specific origin.
	 *
	 * @since 1.0.0
	 * @param array  $rules  All rules for the destination country.
	 * @param string $origin Origin country code.
	 * @return array Matching rules.
	 */
	private function get_rules_for_origin( $rules, $origin ) {
		$matching = array();

		foreach ( $rules as $rule ) {
			$rule_origin = isset( $rule['origin_country'] ) ? $rule['origin_country'] : '';

			// Empty origin_country means applies to all origins.
			if ( empty( $rule_origin ) ) {
				$matching[] = $rule;
				continue;
			}

			// Exact match.
			if ( $rule_origin === $origin ) {
				$matching[] = $rule;
				continue;
			}

			// Check for EU countries.
			if ( 'EU' === $rule_origin && $this->is_eu_country( $origin ) ) {
				$matching[] = $rule;
				continue;
			}
		}

		return $matching;
	}

	/**
	 * Check if a country is in the EU.
	 *
	 * @since 1.0.0
	 * @param string $country Country code.
	 * @return bool True if EU country.
	 */
	private function is_eu_country( $country ) {
		$eu_countries = array(
			'AT', // Austria.
			'BE', // Belgium.
			'BG', // Bulgaria.
			'HR', // Croatia.
			'CY', // Cyprus.
			'CZ', // Czech Republic.
			'DK', // Denmark.
			'EE', // Estonia.
			'FI', // Finland.
			'FR', // France.
			'DE', // Germany.
			'GR', // Greece.
			'HU', // Hungary.
			'IE', // Ireland.
			'IT', // Italy.
			'LV', // Latvia.
			'LT', // Lithuania.
			'LU', // Luxembourg.
			'MT', // Malta.
			'NL', // Netherlands.
			'PL', // Poland.
			'PT', // Portugal.
			'RO', // Romania.
			'SK', // Slovakia.
			'SI', // Slovenia.
			'ES', // Spain.
			'SE', // Sweden.
		);

		return in_array( $country, $eu_countries, true );
	}

	/**
	 * Get customer shipping/billing country.
	 *
	 * @since 1.0.0
	 * @return string Country code.
	 */
	private function get_customer_country() {
		// Check if customer object is available.
		if ( ! WC()->customer ) {
			return '';
		}

		// Get shipping country first, fallback to billing.
		$country = WC()->customer->get_shipping_country();
		if ( empty( $country ) ) {
			$country = WC()->customer->get_billing_country();
		}

		return $country;
	}

	/**
	 * Get rules for a specific country.
	 *
	 * @since 1.0.0
	 * @param string $country Country code.
	 * @return array Applicable rules.
	 */
	private function get_rules_for_country( $country ) {
		// Get all rules.
		$all_rules = $this->get_all_rules();

		// Filter rules for this country.
		$country_rules = array();
		foreach ( $all_rules as $rule ) {
			if ( $this->rule_applies_to_country( $rule, $country ) ) {
				$country_rules[] = $rule;
			}
		}

		return apply_filters( 'cfwc_rules_for_country', $country_rules, $country );
	}

	/**
	 * Check if a rule applies to a country.
	 *
	 * @since 1.0.0
	 * @param array  $rule    Rule data.
	 * @param string $country Country code.
	 * @return bool True if rule applies.
	 */
	private function rule_applies_to_country( $rule, $country ) {
		// Check if rule has country restriction.
		if ( empty( $rule['country'] ) ) {
			return false;
		}

		// Check for exact match.
		if ( $rule['country'] === $country ) {
			return true;
		}

		// Check for wildcard (all countries).
		if ( '*' === $rule['country'] ) {
			return true;
		}

		// Check for EU countries.
		if ( 'EU' === $rule['country'] && $this->is_eu_country( $country ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get all configured rules.
	 *
	 * @since 1.0.0
	 * @return array All rules.
	 */
	private function get_all_rules() {
		// Use cache if available.
		if ( null !== $this->rules_cache ) {
			return $this->rules_cache;
		}

		// Get rules from options.
		$rules = get_option( 'cfwc_rules', array() );

		// Ensure rules is an array.
		if ( ! is_array( $rules ) ) {
			$rules = array();
		}

		// Cache rules.
		$this->rules_cache = $rules;

		return apply_filters( 'cfwc_all_rules', $rules );
	}

	/**
	 * Calculate a single fee.
	 *
	 * @since 1.0.0
	 * @param array $rule  Rule data.
	 * @param float $total Cart total for calculation.
	 * @return float|false Fee amount or false if not applicable.
	 */
	private function calculate_single_fee( $rule, $total ) {
		// Check if rule is valid.
		if ( empty( $rule['type'] ) ) {
			return false;
		}

		$fee = 0;

		// Calculate based on type.
		if ( 'percentage' === $rule['type'] ) {
			$rate = isset( $rule['rate'] ) ? floatval( $rule['rate'] ) : 0;
			if ( $rate > 0 ) {
				$fee = ( $total * $rate ) / 100;
			}
		} elseif ( 'flat' === $rule['type'] ) {
			$fee = isset( $rule['amount'] ) ? floatval( $rule['amount'] ) : 0;
		}

		// Allow filtering of calculated fee.
		$fee = apply_filters( 'cfwc_calculated_single_fee', $fee, $rule, $total );

		return $fee;
	}

	/**
	 * Get fee label.
	 *
	 * @since 1.0.0
	 * @param array  $rule    Rule data.
	 * @param string $country Destination country code.
	 * @param string $origin  Origin country code.
	 * @return string Fee label.
	 */
	private function get_fee_label( $rule, $country, $origin = '' ) {
		// If the rule has a custom label, use it as-is.
		if ( ! empty( $rule['label'] ) ) {
			return $rule['label'];
		}

		// Otherwise, build a default label.
		$countries    = WC()->countries->get_countries();
		$country_name = isset( $countries[ $country ] ) ? $countries[ $country ] : $country;

		// Add origin to label if available.
		if ( ! empty( $origin ) && 'unknown' !== $origin ) {
			$origin_name = isset( $countries[ $origin ] ) ? $countries[ $origin ] : $origin;
			return sprintf(
				/* translators: %1$s: destination country, %2$s: origin country */
				__( 'Import Fees (%1$s from %2$s)', 'customs-fees-for-woocommerce' ),
				$country_name,
				$origin_name
			);
		}

		// Default label.
		return sprintf(
			/* translators: %s: country name */
			__( 'Customs & Import Fees (%s)', 'customs-fees-for-woocommerce' ),
			$country_name
		);
	}

	/**
	 * Check if fee should be taxable.
	 *
	 * @since 1.0.0
	 * @param array $rule Rule data.
	 * @return bool True if taxable.
	 */
	private function is_fee_taxable( $rule ) {
		// Check rule setting.
		if ( isset( $rule['taxable'] ) ) {
			return (bool) $rule['taxable'];
		}

		// Default to taxable.
		return apply_filters( 'cfwc_fee_taxable_default', true, $rule );
	}

	/**
	 * Get fee tax class.
	 *
	 * @since 1.0.0
	 * @param array $rule Rule data.
	 * @return string Tax class.
	 */
	private function get_fee_tax_class( $rule ) {
		// Check rule setting.
		if ( ! empty( $rule['tax_class'] ) ) {
			return $rule['tax_class'];
		}

		// Default to standard tax class.
		return apply_filters( 'cfwc_fee_tax_class_default', '', $rule );
	}

	/**
	 * Enhanced debug logging for troubleshooting.
	 *
	 * @since 1.3.0
	 * @param string $message Log message.
	 * @param string $level   Log level (debug, info, notice, warning, error).
	 */
	private function log_debug( $message, $level = 'debug' ) {
		// Check if debug mode is enabled.
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		// Prefix all messages.
		$prefixed_message = '[CFWC] ' . $message;

		// Log to WordPress debug.log if enabled.
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug only
			error_log( $prefixed_message );
		}

		// Also log to WooCommerce Status > Logs if available.
		if ( function_exists( 'wc_get_logger' ) ) {
			$logger  = wc_get_logger();
			$context = array( 'source' => 'customs-fees-debug' );

			switch ( $level ) {
				case 'error':
					$logger->error( $message, $context );
					break;
				case 'warning':
					$logger->warning( $message, $context );
					break;
				case 'notice':
					$logger->notice( $message, $context );
					break;
				case 'info':
					$logger->info( $message, $context );
					break;
				default:
					$logger->debug( $message, $context );
					break;
			}
		}
	}

	/**
	 * Log fee calculation for debugging.
	 *
	 * @since 1.0.0
	 * @param string $country    Destination country.
	 * @param float  $cart_total Cart total.
	 * @param array  $fees       Calculated fees.
	 */
	private function log_calculation( $country, $cart_total, $fees ) {
		// Only log if WooCommerce logging is enabled.
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		// Check if logging is enabled for this plugin.
		$logging_enabled = apply_filters( 'cfwc_enable_calculation_logging', false );
		if ( ! $logging_enabled ) {
			return;
		}

		$logger  = wc_get_logger();
		$context = array( 'source' => 'customs-fees' );

		// Log calculation details.
		$message = sprintf(
			'Fee calculation for country: %s, Cart total: %s',
			$country,
			wc_price( $cart_total )
		);

		if ( ! empty( $fees ) ) {
			$message .= ' | Fees: ';
			foreach ( $fees as $fee ) {
				$message .= sprintf(
					'[%s: %s] ',
					$fee['label'],
					wc_price( $fee['amount'] )
				);
			}
		} else {
			$message .= ' | No fees applied';
		}

		$logger->info( $message, $context );
	}

	/**
	 * Get default origin country from settings.
	 *
	 * @since 1.2.1
	 * @return string|null Country code or null if not set.
	 */
	private function get_default_origin() {
		$default_origin = get_option( 'cfwc_default_origin', 'store' );
		
		if ( 'store' === $default_origin ) {
			// Use store's base country.
			$base_country = get_option( 'woocommerce_default_country' );
			if ( $base_country ) {
				// Extract country code from base country (format: US:CA).
				$parts = explode( ':', $base_country );
				return $parts[0];
			}
		} elseif ( 'custom' === $default_origin ) {
			// Use custom default origin.
			return get_option( 'cfwc_custom_default_origin', '' );
		}
		
		// Return null if 'none' or not set.
		return null;
	}

	/**
	 * Clear cache.
	 *
	 * @since 1.0.0
	 */
	public function clear_cache() {
		$this->rules_cache = null;

		// Clear any transients.
		delete_transient( 'cfwc_rules_cache' );

		// Trigger action for other caching mechanisms.
		do_action( 'cfwc_cache_cleared' );
	}

	/**
	 * Get cart total for calculation.
	 *
	 * @since 1.0.0
	 * @param WC_Cart $cart           The cart object.
	 * @param string  $origin_country Optional origin country to filter by.
	 * @return float Cart total.
	 */
	private function get_cart_total( $cart, $origin_country = '' ) {
		$total = 0;

		foreach ( $cart->get_cart_contents() as $cart_item_key => $cart_item ) {
			$product = $cart_item['data'];

			// Skip virtual products (purely digital, no shipping required).
			if ( ! $product || $product->is_virtual() ) {
				continue;
			}

			// If an origin_country is specified, only include products matching that origin.
			if ( ! empty( $origin_country ) ) {
				$product_origin = get_post_meta( $product->get_id(), '_cfwc_country_of_origin', true );
				
				// Use default origin if no origin set on product.
				if ( empty( $product_origin ) ) {
					$product_origin = $this->get_default_origin();
				}
				
				if ( $product_origin !== $origin_country ) {
					continue;
				}
			}

			$total += $cart_item['line_total'];
		}

		// Option to include shipping in calculation.
		$include_shipping = apply_filters( 'cfwc_include_shipping_in_calculation', true );
		if ( $include_shipping ) {
			$total += $cart->get_shipping_total();
		}

		// Option to include taxes in calculation.
		$include_taxes = apply_filters( 'cfwc_include_taxes_in_calculation', false );
		if ( $include_taxes ) {
			$total += $cart->get_taxes_total();
		}

		return (float) $total;
	}
}
