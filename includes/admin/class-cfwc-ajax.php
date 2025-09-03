<?php
/**
 * AJAX handler for admin operations.
 *
 * @package CustomsFeesForWooCommerce
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CFWC_Ajax class.
 *
 * Handles AJAX requests for admin operations.
 *
 * @since 1.0.0
 */
class CFWC_Ajax {

	/**
	 * Initialize AJAX handlers.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		// Rule management.
		add_action( 'wp_ajax_cfwc_save_rule', array( $this, 'save_rule' ) );
		add_action( 'wp_ajax_cfwc_delete_rule', array( $this, 'delete_rule' ) );
		add_action( 'wp_ajax_cfwc_reorder_rules', array( $this, 'reorder_rules' ) );

		// Import/Export.
		add_action( 'wp_ajax_cfwc_export_rules', array( $this, 'export_rules' ) );
		add_action( 'wp_ajax_cfwc_import_rules', array( $this, 'import_rules' ) );

		// Testing.
		add_action( 'wp_ajax_cfwc_test_calculation', array( $this, 'test_calculation' ) );
	}

	/**
	 * Save a rule via AJAX.
	 *
	 * @since 1.0.0
	 */
	public function save_rule() {
		check_ajax_referer( 'cfwc_admin_nonce', 'nonce' );

		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- manage_woocommerce is a standard WooCommerce capability.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( -1 );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$rule_data = isset( $_POST['rule'] ) ? wp_unslash( $_POST['rule'] ) : array();
		$rule_id   = isset( $_POST['rule_id'] ) ? absint( $_POST['rule_id'] ) : null;

		// Sanitize rule data.
		$rule = array(
			'country'         => sanitize_text_field( $rule_data['country'] ?? '' ),
			'type'            => sanitize_text_field( $rule_data['type'] ?? 'percentage' ),
			'rate'            => floatval( $rule_data['rate'] ?? 0 ),
			'amount'          => floatval( $rule_data['amount'] ?? 0 ),
			'minimum'         => floatval( $rule_data['minimum'] ?? 0 ),
			'maximum'         => floatval( $rule_data['maximum'] ?? 0 ),
			'label'           => sanitize_text_field( $rule_data['label'] ?? '' ),
			'taxable'         => isset( $rule_data['taxable'] ) ? (bool) $rule_data['taxable'] : true,
			'tax_class'       => sanitize_text_field( $rule_data['tax_class'] ?? '' ),
			// New fields for advanced matching (v1.2.0).
			'from_country'    => sanitize_text_field( $rule_data['from_country'] ?? $rule_data['country'] ?? '' ),
			'to_country'      => sanitize_text_field( $rule_data['to_country'] ?? '' ),
			'match_type'      => sanitize_text_field( $rule_data['match_type'] ?? 'all' ),
			'category_ids'    => ! empty( $rule_data['category_ids'] ) ? array_map( 'absint', (array) $rule_data['category_ids'] ) : array(),
			'hs_code_pattern' => sanitize_text_field( $rule_data['hs_code_pattern'] ?? '' ),
			'priority'        => absint( $rule_data['priority'] ?? 0 ),
			'stacking_mode'   => sanitize_text_field( $rule_data['stacking_mode'] ?? 'add' ),
		);

		// Get existing rules.
		$rules = get_option( 'cfwc_rules', array() );

		// Update or add rule.
		if ( null !== $rule_id && isset( $rules[ $rule_id ] ) ) {
			$rules[ $rule_id ] = $rule;
		} else {
			$rules[] = $rule;
			$rule_id = count( $rules ) - 1;
		}

		// Save rules.
		update_option( 'cfwc_rules', $rules );

		// Clear cache.
		$calculator = new CFWC_Calculator();
		$calculator->clear_cache();

		wp_send_json_success(
			array(
				'message' => __( 'Rule saved successfully.', 'customs-fees-for-woocommerce' ),
				'rule_id' => $rule_id,
				'rule'    => $rule,
			)
		);
	}

	/**
	 * Delete a rule via AJAX.
	 *
	 * @since 1.0.0
	 */
	public function delete_rule() {
		check_ajax_referer( 'cfwc_admin_nonce', 'nonce' );

		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- manage_woocommerce is a standard WooCommerce capability.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( -1 );
		}

		$rule_id = isset( $_POST['rule_id'] ) ? absint( $_POST['rule_id'] ) : null;

		if ( null === $rule_id ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid rule ID.', 'customs-fees-for-woocommerce' ),
				)
			);
		}

		// Get existing rules.
		$rules = get_option( 'cfwc_rules', array() );

		// Remove rule.
		if ( isset( $rules[ $rule_id ] ) ) {
			unset( $rules[ $rule_id ] );
			// Re-index array.
			$rules = array_values( $rules );

			// Save rules.
			update_option( 'cfwc_rules', $rules );

			// Clear cache.
			$calculator = new CFWC_Calculator();
			$calculator->clear_cache();

			wp_send_json_success(
				array(
					'message' => __( 'Rule deleted successfully.', 'customs-fees-for-woocommerce' ),
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => __( 'Rule not found.', 'customs-fees-for-woocommerce' ),
				)
			);
		}
	}

	/**
	 * Reorder rules via AJAX.
	 *
	 * @since 1.0.0
	 */
	public function reorder_rules() {
		check_ajax_referer( 'cfwc_admin_nonce', 'nonce' );

		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- manage_woocommerce is a standard WooCommerce capability.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( -1 );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$order = isset( $_POST['order'] ) ? array_map( 'absint', wp_unslash( $_POST['order'] ) ) : array();

		if ( empty( $order ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid order data.', 'customs-fees-for-woocommerce' ),
				)
			);
		}

		// Get existing rules.
		$rules     = get_option( 'cfwc_rules', array() );
		$reordered = array();

		// Reorder based on provided order.
		foreach ( $order as $index ) {
			if ( isset( $rules[ $index ] ) ) {
				$reordered[] = $rules[ $index ];
			}
		}

		// Save reordered rules.
		update_option( 'cfwc_rules', $reordered );

		// Clear cache.
		$calculator = new CFWC_Calculator();
		$calculator->clear_cache();

		wp_send_json_success(
			array(
				'message' => __( 'Rules reordered successfully.', 'customs-fees-for-woocommerce' ),
			)
		);
	}

	/**
	 * Export rules as CSV.
	 *
	 * @since 1.0.0
	 */
	public function export_rules() {
		check_ajax_referer( 'cfwc_admin_nonce', 'nonce' );

		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- manage_woocommerce is a standard WooCommerce capability.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( -1 );
		}

		$rules = get_option( 'cfwc_rules', array() );

		// Create CSV content.
		$csv_content = "Country,Type,Rate,Amount,Minimum,Maximum,Label,Taxable,Tax Class\n";

		foreach ( $rules as $rule ) {
			$csv_content .= sprintf(
				'"%s","%s",%.2f,%.2f,%.2f,%.2f,"%s","%s","%s"' . "\n",
				$rule['country'],
				$rule['type'],
				$rule['rate'],
				$rule['amount'],
				$rule['minimum'],
				$rule['maximum'],
				$rule['label'],
				$rule['taxable'] ? 'yes' : 'no',
				$rule['tax_class']
			);
		}

		wp_send_json_success(
			array(
				'filename' => 'customs-fees-rules-' . gmdate( 'Y-m-d' ) . '.csv',
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Used for CSV download, not obfuscation.
				'content'  => base64_encode( $csv_content ),
				'message'  => __( 'Export ready for download.', 'customs-fees-for-woocommerce' ),
			)
		);
	}

	/**
	 * Import rules from CSV.
	 *
	 * @since 1.0.0
	 */
	public function import_rules() {
		check_ajax_referer( 'cfwc_admin_nonce', 'nonce' );

		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- manage_woocommerce is a standard WooCommerce capability.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( -1 );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$csv_content = isset( $_POST['csv_content'] ) ? wp_unslash( $_POST['csv_content'] ) : '';
		$append      = isset( $_POST['append'] ) && 'true' === $_POST['append'];

		if ( empty( $csv_content ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'No CSV content provided.', 'customs-fees-for-woocommerce' ),
				)
			);
		}

		// Parse CSV.
		$lines = explode( "\n", $csv_content );
		// Add escape parameter (backslash) for PHP 8.4+ compatibility.
		$headers   = str_getcsv( array_shift( $lines ), ',', '"', '\\' );
		$new_rules = array();

		foreach ( $lines as $line ) {
			if ( empty( trim( $line ) ) ) {
				continue;
			}

			// Add escape parameter (backslash) for PHP 8.4+ compatibility.
			$data = str_getcsv( $line, ',', '"', '\\' );
			if ( count( $data ) === count( $headers ) ) {
				$new_rules[] = array(
					'country'   => sanitize_text_field( $data[0] ),
					'type'      => sanitize_text_field( $data[1] ),
					'rate'      => floatval( $data[2] ),
					'amount'    => floatval( $data[3] ),
					'minimum'   => floatval( $data[4] ),
					'maximum'   => floatval( $data[5] ),
					'label'     => sanitize_text_field( $data[6] ),
					'taxable'   => 'yes' === strtolower( $data[7] ),
					'tax_class' => sanitize_text_field( $data[8] ),
				);
			}
		}

		if ( empty( $new_rules ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'No valid rules found in CSV.', 'customs-fees-for-woocommerce' ),
				)
			);
		}

		// Get existing rules if appending.
		if ( $append ) {
			$existing_rules = get_option( 'cfwc_rules', array() );
			$new_rules      = array_merge( $existing_rules, $new_rules );
		}

		// Save rules.
		update_option( 'cfwc_rules', $new_rules );

		// Clear cache.
		$calculator = new CFWC_Calculator();
		$calculator->clear_cache();

		wp_send_json_success(
			array(
				'message' => sprintf(
				/* translators: %d: Number of rules imported */
					__( '%d rules imported successfully.', 'customs-fees-for-woocommerce' ),
					count( $new_rules )
				),
				'rules'   => $new_rules,
			)
		);
	}

	/**
	 * Test calculation for given parameters.
	 *
	 * @since 1.0.0
	 */
	public function test_calculation() {
		check_ajax_referer( 'cfwc_admin_nonce', 'nonce' );

		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- manage_woocommerce is a standard WooCommerce capability.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( -1 );
		}

		$country    = sanitize_text_field( wp_unslash( $_POST['country'] ?? '' ) );
		$cart_total = floatval( $_POST['cart_total'] ?? 0 );

		if ( empty( $country ) || $cart_total <= 0 ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid test parameters.', 'customs-fees-for-woocommerce' ),
				)
			);
		}

		// Calculate fees by simulating a cart.
		// Since calculate_fees_for_country doesn't exist, we need to simulate the calculation.
		$calculator = new CFWC_Calculator();

		// Get rules and filter by country to simulate the calculation.
		$all_rules  = get_option( 'cfwc_rules', array() );
		$fees       = array();
		$total_fees = 0;

		// Filter rules for the specified country.
		foreach ( $all_rules as $rule ) {
			if ( isset( $rule['country'] ) && $rule['country'] === $country ) {
				$fee_amount = 0;

				// Calculate based on rule type.
				if ( 'percentage' === $rule['type'] ) {
					$fee_amount = ( $cart_total * $rule['rate'] ) / 100;
				} elseif ( 'flat' === $rule['type'] ) {
					$fee_amount = $rule['amount'];
				}

				// Apply minimum/maximum limits if set.
				if ( isset( $rule['minimum'] ) && $rule['minimum'] > 0 && $fee_amount < $rule['minimum'] ) {
					$fee_amount = $rule['minimum'];
				}
				if ( isset( $rule['maximum'] ) && $rule['maximum'] > 0 && $fee_amount > $rule['maximum'] ) {
					$fee_amount = $rule['maximum'];
				}

				if ( $fee_amount > 0 ) {
					$fees[]      = array(
						'label'  => isset( $rule['label'] ) ? $rule['label'] : __( 'Customs Fee', 'customs-fees-for-woocommerce' ),
						'amount' => $fee_amount,
						'type'   => $rule['type'],
						'rate'   => isset( $rule['rate'] ) ? $rule['rate'] : 0,
					);
					$total_fees += $fee_amount;
				}
			}
		}

		wp_send_json_success(
			array(
				'message' => __( 'Calculation complete.', 'customs-fees-for-woocommerce' ),
				'fees'    => $fees,
				'total'   => $total_fees,
			)
		);
	}
}
