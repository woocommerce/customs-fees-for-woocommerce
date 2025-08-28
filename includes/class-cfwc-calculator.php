<?php
/**
 * Fee calculator class.
 *
 * Handles customs fee calculations based on configured rules.
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
	 * Fee rules cache.
	 *
	 * @since 1.0.0
	 * @var array|null
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
		$country = $this->get_customer_country();
		if ( empty( $country ) ) {
			return $fees;
		}

		// Get applicable rules for the country.
		$rules = $this->get_rules_for_country( $country );
		if ( empty( $rules ) ) {
			return $fees;
		}

		// Calculate cart totals.
		$cart_total = $this->get_cart_total( $cart );
		if ( $cart_total <= 0 ) {
			return $fees;
		}

		// Apply each rule.
		foreach ( $rules as $rule ) {
			$fee = $this->calculate_single_fee( $rule, $cart_total );
			if ( $fee !== false && $fee > 0 ) {
				$label = $this->get_fee_label( $rule, $country );
				
				// For breakdown mode, we want all fees even with same label.
				// For single mode, they'll be combined later anyway.
				$fees[] = array(
					'label'     => $label,
					'amount'    => $fee,
					'taxable'   => $this->is_fee_taxable( $rule ),
					'tax_class' => $this->get_fee_tax_class( $rule ),
				);
			}
		}

		// Allow filtering of calculated fees.
		$fees = apply_filters( 'cfwc_calculated_fees', $fees, $country, $cart );

		// Log the calculation if logging is enabled.
		$this->log_calculation( $country, $cart_total, $fees );

		return $fees;
	}

	/**
	 * Get customer shipping country.
	 *
	 * @since 1.0.0
	 * @return string Country code.
	 */
	private function get_customer_country() {
		if ( ! WC()->customer ) {
			return '';
		}

		// Use shipping country if available, otherwise billing country.
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
		$all_rules = $this->get_all_rules();
		$country_rules = array();

		foreach ( $all_rules as $rule ) {
			// Check if rule applies to this country.
			if ( $this->rule_applies_to_country( $rule, $country ) ) {
				$country_rules[] = $rule;
			}
		}

		// Allow filtering of country rules.
		return apply_filters( 'cfwc_country_rules', $country_rules, $country );
	}

	/**
	 * Get all configured rules.
	 *
	 * @since 1.0.0
	 * @return array All rules.
	 */
	private function get_all_rules() {
		// Use cached rules if available.
		if ( is_array( $this->rules_cache ) ) {
			return $this->rules_cache;
		}

		// Get rules from options.
		$rules = get_option( 'cfwc_rules', array() );

		// Ensure rules is an array.
		if ( ! is_array( $rules ) ) {
			$rules = array();
		}

		// Cache the rules.
		$this->rules_cache = $rules;

		return $rules;
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
		// Check if rule has country field.
		if ( ! isset( $rule['country'] ) ) {
			return false;
		}

		// Check for exact match.
		if ( $rule['country'] === $country ) {
			return true;
		}

		// Check for wildcard (all countries).
		if ( $rule['country'] === '*' || $rule['country'] === 'all' ) {
			return true;
		}

		// Check if country is in a list (for future use).
		if ( is_array( $rule['country'] ) && in_array( $country, $rule['country'], true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get cart total for fee calculation.
	 *
	 * @since 1.0.0
	 * @param WC_Cart $cart The cart object.
	 * @return float Cart total.
	 */
	private function get_cart_total( $cart ) {
		// Get cart contents total (excludes shipping and taxes).
		$total = $cart->get_cart_contents_total();

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

	/**
	 * Calculate a single fee based on rule.
	 *
	 * @since 1.0.0
	 * @param array $rule       Rule data.
	 * @param float $cart_total Cart total.
	 * @return float|false Calculated fee amount or false if not applicable.
	 */
	private function calculate_single_fee( $rule, $cart_total ) {
		$fee = 0;

		// Check rule type.
		$type = isset( $rule['type'] ) ? $rule['type'] : 'percentage';

		switch ( $type ) {
			case 'percentage':
				// Calculate percentage-based fee.
				$rate = isset( $rule['rate'] ) ? (float) $rule['rate'] : 0;
				if ( $rate > 0 ) {
					// Convert percentage to decimal if needed.
					if ( $rate > 1 ) {
						$rate = $rate / 100;
					}
					$fee = $cart_total * $rate;
				}
				break;

			case 'flat':
			case 'fixed':
				// Fixed fee amount.
				$fee = isset( $rule['amount'] ) ? (float) $rule['amount'] : 0;
				break;

			case 'tiered':
				// Tiered rates based on cart total (future feature).
				$fee = $this->calculate_tiered_fee( $rule, $cart_total );
				break;

			default:
				// Allow custom fee types via filter.
				$fee = apply_filters( 'cfwc_calculate_custom_fee_type', 0, $type, $rule, $cart_total );
				break;
		}

		// Apply minimum fee if set.
		if ( isset( $rule['minimum'] ) && $rule['minimum'] > 0 ) {
			$fee = max( $fee, (float) $rule['minimum'] );
		}

		// Apply maximum fee if set.
		if ( isset( $rule['maximum'] ) && $rule['maximum'] > 0 ) {
			$fee = min( $fee, (float) $rule['maximum'] );
		}

		// Round to 2 decimal places.
		$fee = round( $fee, 2 );

		// Allow filtering of calculated fee.
		$fee = apply_filters( 'cfwc_calculated_fee_amount', $fee, $rule, $cart_total );

		return $fee;
	}

	/**
	 * Calculate tiered fee.
	 *
	 * @since 1.0.0
	 * @param array $rule       Rule data.
	 * @param float $cart_total Cart total.
	 * @return float Calculated fee.
	 */
	private function calculate_tiered_fee( $rule, $cart_total ) {
		$fee = 0;

		// Check if tiers are defined.
		if ( ! isset( $rule['tiers'] ) || ! is_array( $rule['tiers'] ) ) {
			return $fee;
		}

		// Find applicable tier.
		foreach ( $rule['tiers'] as $tier ) {
			$min = isset( $tier['min'] ) ? (float) $tier['min'] : 0;
			$max = isset( $tier['max'] ) ? (float) $tier['max'] : PHP_FLOAT_MAX;

			if ( $cart_total >= $min && $cart_total <= $max ) {
				// Apply tier rate.
				if ( isset( $tier['rate'] ) ) {
					$rate = (float) $tier['rate'];
					if ( $rate > 1 ) {
						$rate = $rate / 100;
					}
					$fee = $cart_total * $rate;
				} elseif ( isset( $tier['amount'] ) ) {
					$fee = (float) $tier['amount'];
				}
				break;
			}
		}

		return $fee;
	}

	/**
	 * Get fee label.
	 *
	 * @since 1.0.0
	 * @param array  $rule    Rule data.
	 * @param string $country Country code.
	 * @return string Fee label.
	 */
	private function get_fee_label( $rule, $country ) {
		// Use custom label if provided.
		if ( ! empty( $rule['label'] ) ) {
			return $rule['label'];
		}

		// Get country name.
		$countries = WC()->countries->get_countries();
		$country_name = isset( $countries[ $country ] ) ? $countries[ $country ] : $country;

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
	 * Log fee calculation.
	 *
	 * @since 1.0.0
	 * @param string $country    Country code.
	 * @param float  $cart_total Cart total.
	 * @param array  $fees       Calculated fees.
	 */
	private function log_calculation( $country, $cart_total, $fees ) {
		// Check if logging is enabled.
		if ( ! apply_filters( 'cfwc_enable_calculation_logging', false ) ) {
			return;
		}

		// Calculate total fees.
		$total_fees = 0;
		foreach ( $fees as $fee ) {
			$total_fees += $fee['amount'];
		}

		// Log to database or file.
		if ( $total_fees > 0 ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'cfwc_logs';

			// Insert log entry.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table logging required for analytics
			$result = $wpdb->insert(
				$table_name,
				array(
					'country'     => $country,
					'cart_total'  => $cart_total,
					'fee_amount'  => $total_fees,
					'fee_type'    => 'calculated',
					'created_at'  => current_time( 'mysql' ),
				),
				array( '%s', '%f', '%f', '%s', '%s' )
			);

			// Clear any cached data if insert was successful.
			if ( $result ) {
				wp_cache_delete( 'cfwc_logs_' . $country, 'cfwc' );
			}
		}
	}

	/**
	 * Calculate fees for a specific country (used by AJAX/API).
	 *
	 * @since 1.0.0
	 * @param string $country    Country code.
	 * @param float  $cart_total Cart total amount.
	 * @return array Array of calculated fees.
	 */
	public function calculate_fees_for_country( $country, $cart_total ) {
		$fees = array();
		
		// Get rules for this country.
		$rule = $this->get_country_rule( $country );
		
		if ( ! $rule ) {
			return $fees;
		}

		// Calculate fee amount.
		$fee_amount = 0;
		if ( 'percentage' === $rule['type'] ) {
			$fee_amount = $this->calculate_percentage_fee( $cart_total, $rule['rate'] );
		} else {
			$fee_amount = $rule['amount'];
		}

		// Apply minimum/maximum.
		$fee_amount = $this->apply_min_max_fee( $fee_amount, $rule );

		if ( $fee_amount > 0 ) {
			$fees[] = array(
				'label'     => $rule['label'] ?: __( 'Customs & Import Fees', 'customs-fees-for-woocommerce' ),
				'amount'    => $fee_amount,
				'taxable'   => $rule['taxable'],
				'tax_class' => $rule['tax_class'],
			);
		}

		return $fees;
	}

	/**
	 * Clear the rules cache.
	 *
	 * @since 1.0.0
	 */
	public function clear_cache() {
		$this->rules_cache = null;
		
		// Also clear WordPress object cache for rules.
		wp_cache_delete( 'cfwc_rules', 'cfwc' );
		wp_cache_delete( 'cfwc_display_mode', 'cfwc' );
		
		// Clear transients if any.
		delete_transient( 'cfwc_rules_cache' );
	}
}
