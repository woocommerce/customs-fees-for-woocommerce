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

		// Get applicable rules for the destination country.
		$rules = $this->get_rules_for_country( $destination_country );
		if ( empty( $rules ) ) {
			return $fees;
		}

		// Group cart items by origin country.
		$items_by_origin = $this->group_cart_items_by_origin( $cart );
		
		// Debug log the grouped items
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug only
			error_log( 'CFWC Items by Origin: ' . wp_json_encode( array_keys( $items_by_origin ) ) );
		}
		
		// Apply fees based on origin countries.
		foreach ( $items_by_origin as $origin => $items ) {
			// Skip if no shippable items in this origin group.
			if ( empty( $items['shippable_items'] ) ) {
				continue;
			}
			
			// Debug log
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug only
				error_log( sprintf( 'CFWC Processing origin %s with total %s', $origin, $items['total'] ) );
			}
			
			// Collect ALL matching rules - both specific and general
			$matching_rules = array();
			
			// First, get rules specific to this origin
			$specific_rules = $this->get_rules_for_origin( $rules, $origin );
			if ( ! empty( $specific_rules ) ) {
				$matching_rules = array_merge( $matching_rules, $specific_rules );
			}
			
			// Also get general rules (empty origin) that apply to all origins
			$general_rules = $this->get_rules_for_origin( $rules, '' );
			if ( ! empty( $general_rules ) ) {
				// For general rules, we need to check they haven't already been added for this specific origin
				foreach ( $general_rules as $general_rule ) {
					// Only add if we don't have a specific rule with the same label for this origin
					$already_added = false;
					foreach ( $specific_rules as $specific_rule ) {
						if ( $specific_rule['label'] === $general_rule['label'] ) {
							$already_added = true;
							break;
						}
					}
					if ( ! $already_added ) {
						$matching_rules[] = $general_rule;
					}
				}
			}
			
			// Debug log matching rules
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug only
				error_log( sprintf( 'CFWC Matching rules for origin %s: %d rules', $origin, count( $matching_rules ) ) );
			}
			
			// Calculate fees for this origin group - apply ALL matching rules
			foreach ( $matching_rules as $rule ) {
				$fee = $this->calculate_single_fee( $rule, $items['total'] );
				if ( $fee !== false && $fee > 0 ) {
					$base_label = $this->get_fee_label( $rule, $destination_country, $origin );
					
					// Add percentage rate in brackets only for percentage rules
					if ( isset( $rule['type'] ) && 'percentage' === $rule['type'] && ! empty( $rule['rate'] ) ) {
						$label = sprintf( '%s (%s%%)', $base_label, $rule['rate'] );
					} else {
						// For flat rate, just use the base label
						$label = $base_label;
					}
					
					$fees[] = array(
						'label'     => $label,
						'amount'    => $fee,
						'taxable'   => $this->is_fee_taxable( $rule ),
						'tax_class' => $this->get_fee_tax_class( $rule ),
					);
					
					// Debug log each fee
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
						// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug only
						error_log( sprintf( 'CFWC Added fee: %s = %s for origin %s', $label, $fee, $origin ) );
					}
				}
			}
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
			
			// Skip virtual and downloadable products.
			if ( $product->is_virtual() || $product->is_downloadable() ) {
				continue;
			}
			
			// Get product origin.
			$product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
			$origin = get_post_meta( $product_id, '_cfwc_country_of_origin', true );
			
			// Default to 'unknown' if no origin set.
			if ( empty( $origin ) ) {
				$origin = 'unknown';
			}
			
			// Initialize group if not exists.
			if ( ! isset( $groups[ $origin ] ) ) {
				$groups[ $origin ] = array(
					'items' => array(),
					'shippable_items' => array(),
					'total' => 0,
				);
			}
			
			// Add item to group.
			$groups[ $origin ]['items'][] = $cart_item;
			
			// Check if item needs shipping.
			if ( $product->needs_shipping() ) {
				$groups[ $origin ]['shippable_items'][] = $cart_item;
				
				// Add to total (including quantity).
				$item_total = $cart_item['line_total'];
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
						$proportion = $data['total'] / $total_shippable;
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
		// If the rule has a custom label, use it as-is
		if ( ! empty( $rule['label'] ) ) {
			return $rule['label'];
		}
		
		// Otherwise, build a default label
		$countries = WC()->countries->get_countries();
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

		$logger = wc_get_logger();
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
			
			// Skip virtual and downloadable products.
			if ( ! $product || $product->is_virtual() || $product->is_downloadable() ) {
				continue;
			}
			
			// If an origin_country is specified, only include products matching that origin.
			if ( ! empty( $origin_country ) ) {
				$product_origin = get_post_meta( $product->get_id(), '_cfwc_country_of_origin', true );
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