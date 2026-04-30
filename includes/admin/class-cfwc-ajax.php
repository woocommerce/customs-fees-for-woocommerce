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
			// New fields for advanced matching (v1.1.4).
			'from_country'    => sanitize_text_field( $rule_data['from_country'] ?? $rule_data['country'] ?? '' ),
			'to_country'      => sanitize_text_field( $rule_data['to_country'] ?? '' ),
			'match_type'      => sanitize_text_field( $rule_data['match_type'] ?? 'all' ),
			'category_ids'    => ! empty( $rule_data['category_ids'] ) ? array_map( 'absint', (array) $rule_data['category_ids'] ) : array(),
			'hs_code_pattern' => sanitize_text_field( $rule_data['hs_code_pattern'] ?? '' ),
			'priority'        => absint( $rule_data['priority'] ?? 0 ),
			'stacking_mode'   => sanitize_text_field( $rule_data['stacking_mode'] ?? 'add' ),
			// New fields for per-rule valuation and compound bases (v1.2.0).
			'rule_id'         => ! empty( $rule_data['rule_id'] )
				? sanitize_text_field( $rule_data['rule_id'] )
				: 'rule_' . wp_generate_uuid4(),
			'valuation_method' => in_array( $rule_data['valuation_method'] ?? '', array( 'inherit', 'fob', 'cif', 'cif_insurance' ), true )
				? $rule_data['valuation_method']
				: 'inherit',
			'base_includes'   => isset( $rule_data['base_includes'] ) && is_array( $rule_data['base_includes'] )
				? array_values( array_unique( array_map( 'sanitize_text_field', $rule_data['base_includes'] ) ) )
				: array(),
		);

		// Get existing rules.
		$rules = get_option( 'cfwc_rules', array() );

		// Normalize before save.
		if ( class_exists( 'CFWC_Settings' ) && method_exists( 'CFWC_Settings', 'migrate_rules' ) ) {
			$rules = CFWC_Settings::migrate_rules( $rules );
		}

		// Update or add rule.
		$updated = false;
		if ( null !== $rule_id && isset( $rules[ $rule_id ] ) ) {
			$rules[ $rule_id ] = $rule;
			$updated = true;
		} else {
			// Try matching by rule_id string for existing rules.
			foreach ( $rules as $index => $existing_rule ) {
				if ( isset( $existing_rule['rule_id'] ) && $existing_rule['rule_id'] === $rule['rule_id'] ) {
					$rules[ $index ] = $rule;
					$rule_id         = $index;
					$updated         = true;
					break;
				}
			}
		}

		if ( ! $updated ) {
			$rules[] = $rule;
			$rule_id = count( $rules ) - 1;
		}

		// Save rules.
		update_option( 'cfwc_rules', $rules );

		// Clear cache.
		CFWC_Calculator::clear_cache();

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

		// Support both string rule_id (new) and numeric index (legacy).
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw_rule_id = isset( $_POST['rule_id'] ) ? wp_unslash( $_POST['rule_id'] ) : null;

		if ( null === $raw_rule_id || '' === $raw_rule_id ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid rule ID.', 'customs-fees-for-woocommerce' ),
				)
			);
		}

		// Get existing rules.
		$rules = get_option( 'cfwc_rules', array() );

		$rule_index = null;

		// Try matching by string rule_id first.
		if ( is_string( $raw_rule_id ) ) {
			foreach ( $rules as $index => $existing_rule ) {
				if ( isset( $existing_rule['rule_id'] ) && $existing_rule['rule_id'] === $raw_rule_id ) {
					$rule_index = $index;
					break;
				}
			}
		}

		// Fallback to numeric index for legacy clients.
		if ( null === $rule_index ) {
			$numeric_id = absint( $raw_rule_id );
			if ( isset( $rules[ $numeric_id ] ) ) {
				$rule_index = $numeric_id;
			}
		}

		// Remove rule.
		if ( null !== $rule_index && isset( $rules[ $rule_index ] ) ) {
			unset( $rules[ $rule_index ] );
			// Re-index array.
			$rules = array_values( $rules );

			// Save rules.
			update_option( 'cfwc_rules', $rules );

			// Clear cache.
			CFWC_Calculator::clear_cache();

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
		CFWC_Calculator::clear_cache();

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

		// Create CSV content with all rule fields.
		$csv_content = "Country,Type,Rate,Amount,Minimum,Maximum,Label,Taxable,Tax Class,Rule ID,From Country,To Country,Match Type,HS Code,Priority,Stacking Mode,Valuation Method,Depends On\n";

		foreach ( $rules as $rule ) {
			$base_includes = isset( $rule['base_includes'] ) && is_array( $rule['base_includes'] ) ? implode( '|', $rule['base_includes'] ) : '';
			$csv_content  .= sprintf(
				'"%s","%s",%.2f,%.2f,%.2f,%.2f,"%s","%s","%s","%s","%s","%s","%s","%s",%d,"%s","%s","%s"' . "\n",
				$rule['country'] ?? '',
				$rule['type'] ?? 'percentage',
				$rule['rate'] ?? 0,
				$rule['amount'] ?? 0,
				$rule['minimum'] ?? 0,
				$rule['maximum'] ?? 0,
				$rule['label'] ?? '',
				! empty( $rule['taxable'] ) ? 'yes' : 'no',
				$rule['tax_class'] ?? '',
				$rule['rule_id'] ?? '',
				$rule['from_country'] ?? '',
				$rule['to_country'] ?? '',
				$rule['match_type'] ?? 'all',
				$rule['hs_code_pattern'] ?? '',
				$rule['priority'] ?? 0,
				$rule['stacking_mode'] ?? 'add',
				$rule['valuation_method'] ?? 'inherit',
				$base_includes
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

		// Build header index map for flexible column mapping.
		$header_map = array();
		foreach ( $headers as $index => $header ) {
			$header_map[ strtolower( trim( $header ) ) ] = $index;
		}

		$header_count = count( $headers );

		foreach ( $lines as $line ) {
			if ( empty( trim( $line ) ) ) {
				continue;
			}

			// Add escape parameter (backslash) for PHP 8.4+ compatibility.
			$data = str_getcsv( $line, ',', '"', '\\' );

			// Skip rows that have no data at all.
			if ( empty( $data ) ) {
				continue;
			}

			// Tolerate row width drift: pad short rows (Excel/Sheets often
			// strips trailing empty columns on save) and truncate long ones
			// so the header_map lookups stay in bounds.
			if ( count( $data ) < $header_count ) {
				$data = array_pad( $data, $header_count, '' );
			} elseif ( count( $data ) > $header_count ) {
				$data = array_slice( $data, 0, $header_count );
			}

			// Helper to safely read a column by header name.
			$get = function ( $name, $default = '' ) use ( $data, $header_map ) {
				$index = $header_map[ strtolower( $name ) ] ?? null;
				return ( null !== $index && isset( $data[ $index ] ) && '' !== $data[ $index ] ) ? $data[ $index ] : $default;
			};

			$base_includes_raw = $get( 'depends on', '' );
			$base_includes     = array();
			if ( ! empty( $base_includes_raw ) ) {
				$base_includes = array_values( array_unique( array_filter( array_map( 'trim', explode( '|', $base_includes_raw ) ) ) ) );
			}

			$country_value    = sanitize_text_field( $get( 'country', '' ) );
			$to_country_value = sanitize_text_field( $get( 'to country', '' ) );

			// Mirror the settings save validation: a rule must declare at least
			// one destination (legacy `country` or new `to_country`). Otherwise
			// the row is dropped so importers don't end up with inert rules.
			if ( '' === $country_value && '' === $to_country_value ) {
				continue;
			}

			$new_rules[] = array(
				'country'          => $country_value,
				'type'             => sanitize_text_field( $get( 'type', 'percentage' ) ),
				'rate'             => floatval( $get( 'rate', 0 ) ),
				'amount'           => floatval( $get( 'amount', 0 ) ),
				'minimum'          => floatval( $get( 'minimum', 0 ) ),
				'maximum'          => floatval( $get( 'maximum', 0 ) ),
				'label'            => sanitize_text_field( $get( 'label', '' ) ),
				'taxable'          => 'yes' === strtolower( $get( 'taxable', 'yes' ) ),
				'tax_class'        => sanitize_text_field( $get( 'tax class', '' ) ),
				'rule_id'          => ! empty( $get( 'rule id', '' ) )
					? sanitize_text_field( $get( 'rule id' ) )
					: 'rule_' . wp_generate_uuid4(),
				'from_country'     => sanitize_text_field( $get( 'from country', '' ) ),
				'to_country'       => $to_country_value,
				'match_type'       => sanitize_text_field( $get( 'match type', 'all' ) ),
				'hs_code_pattern'  => sanitize_text_field( $get( 'hs code', '' ) ),
				'priority'         => absint( $get( 'priority', 0 ) ),
				'stacking_mode'    => sanitize_text_field( $get( 'stacking mode', 'add' ) ),
				'valuation_method' => in_array( $get( 'valuation method', 'inherit' ), array( 'inherit', 'fob', 'cif', 'cif_insurance' ), true )
					? $get( 'valuation method', 'inherit' )
					: 'inherit',
				'base_includes'    => $base_includes,
			);
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
		CFWC_Calculator::clear_cache();

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

		// Test tool uses cart_total as the FOB base and resolves base_includes;
		// per-product shipping share is unavailable here, so cif/cif_insurance
		// fall back to cart_total (with global insurance percentage added when
		// method is cif_insurance and a percentage is configured).
		$all_rules     = get_option( 'cfwc_rules', array() );
		$global_method = get_option( 'cfwc_valuation_method', 'fob' );

		$matching = array();
		foreach ( $all_rules as $key => $rule ) {
			$rule_country = $rule['to_country'] ?? $rule['country'] ?? '';
			if ( $rule_country !== $country ) {
				continue;
			}
			if ( empty( $rule['rule_id'] ) ) {
				$rule['rule_id'] = (string) $key;
			}
			$matching[] = $rule;
		}

		$id_to_rule = array();
		foreach ( $matching as $mr ) {
			$rid = isset( $mr['rule_id'] ) ? $mr['rule_id'] : '';
			if ( '' !== $rid ) {
				$id_to_rule[ $rid ] = $mr;
			}
		}

		// One-shot cycle detection over the matching set.
		$cycle_rule_ids = array();
		foreach ( $matching as $cr ) {
			$cr_id = $cr['rule_id'] ?? '';
			if ( '' !== $cr_id && CFWC_Calculator::has_cycle( $matching, $cr_id ) ) {
				$cycle_rule_ids[ $cr_id ] = true;
			}
		}

		$fees       = array();
		$total_fees = 0;
		$calculated = array();
		$pending    = $matching;
		$rounds     = 0;
		$max_rounds = count( $matching ) + 1;

		while ( ! empty( $pending ) && $rounds < $max_rounds ) {
			++$rounds;
			$made_progress = false;
			$still_pending = array();

			foreach ( $pending as $rule ) {
				$rule_id = $rule['rule_id'] ?? '';
				$deps    = isset( $rule['base_includes'] ) && is_array( $rule['base_includes'] )
					? $rule['base_includes']
					: array();
				$deps    = array_filter(
					$deps,
					function ( $dep_id ) use ( $id_to_rule ) {
						return isset( $id_to_rule[ $dep_id ] );
					}
				);

				$deps_ready = true;
				foreach ( $deps as $dep_id ) {
					if ( ! isset( $calculated[ $dep_id ] ) ) {
						$deps_ready = false;
						break;
					}
				}

				if ( ! $deps_ready ) {
					$still_pending[] = $rule;
					continue;
				}

				$fee_amount = self::compute_test_fee( $rule, $cart_total, $deps, $calculated, $cycle_rule_ids, $global_method );

				if ( $fee_amount > 0 ) {
					$calculated[ $rule_id ] = $fee_amount;
					$fees[]                 = array(
						'label'  => isset( $rule['label'] ) ? $rule['label'] : __( 'Customs Fee', 'customs-fees-for-woocommerce' ),
						'amount' => $fee_amount,
						'type'   => $rule['type'],
						'rate'   => isset( $rule['rate'] ) ? $rule['rate'] : 0,
					);
					$total_fees += $fee_amount;
				}

				$made_progress = true;
			}

			$pending = $still_pending;

			if ( ! $made_progress ) {
				// Fallback: compute remaining rules on their own base so the
				// preview still shows something useful when deps cannot resolve.
				foreach ( $pending as $rule ) {
					$rule_id    = $rule['rule_id'] ?? '';
					$fee_amount = self::compute_test_fee( $rule, $cart_total, array(), $calculated, $cycle_rule_ids, $global_method );

					if ( $fee_amount > 0 ) {
						if ( '' !== $rule_id ) {
							$calculated[ $rule_id ] = $fee_amount;
						}
						$fees[]      = array(
							'label'  => isset( $rule['label'] ) ? $rule['label'] : __( 'Customs Fee', 'customs-fees-for-woocommerce' ),
							'amount' => $fee_amount,
							'type'   => $rule['type'],
							'rate'   => isset( $rule['rate'] ) ? $rule['rate'] : 0,
						);
						$total_fees += $fee_amount;
					}
				}
				break;
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

	/**
	 * Compute a single test fee with per-rule valuation and dependency includes.
	 *
	 * @since 1.2.0
	 * @param array $rule           Rule data.
	 * @param float $cart_total     Test cart total (treated as the FOB base).
	 * @param array $deps           Filtered list of dependency rule IDs.
	 * @param array $calculated     Map of already-computed fees by rule_id.
	 * @param array $cycle_rule_ids Map of rule IDs that participate in a cycle.
	 * @param string $global_method Global valuation method.
	 * @return float Computed fee amount (after min/max limits).
	 */
	private static function compute_test_fee( $rule, $cart_total, $deps, $calculated, $cycle_rule_ids, $global_method ) {
		$valuation = $rule['valuation_method'] ?? 'inherit';
		$method    = ( 'inherit' !== $valuation ) ? $valuation : $global_method;

		$base = (float) $cart_total;

		if ( 'cif_insurance' === $method ) {
			$insurance_method = get_option( 'cfwc_insurance_method', 'disabled' );
			if ( 'percentage' === $insurance_method ) {
				$pct   = floatval( get_option( 'cfwc_insurance_percentage', 2 ) );
				$base += ( $cart_total * $pct ) / 100;
			}
		}

		$rule_id = $rule['rule_id'] ?? '';
		if ( '' === $rule_id || ! isset( $cycle_rule_ids[ $rule_id ] ) ) {
			foreach ( $deps as $dep_id ) {
				if ( $dep_id === $rule_id ) {
					continue;
				}
				if ( isset( $calculated[ $dep_id ] ) ) {
					$base += $calculated[ $dep_id ];
				}
			}
		}

		$fee_amount = 0;
		if ( 'percentage' === $rule['type'] ) {
			$fee_amount = ( $base * floatval( $rule['rate'] ?? 0 ) ) / 100;
		} elseif ( 'flat' === $rule['type'] ) {
			$fee_amount = floatval( $rule['amount'] ?? 0 );
		}

		if ( isset( $rule['minimum'] ) && $rule['minimum'] > 0 && $fee_amount < $rule['minimum'] ) {
			$fee_amount = floatval( $rule['minimum'] );
		}
		if ( isset( $rule['maximum'] ) && $rule['maximum'] > 0 && $fee_amount > $rule['maximum'] ) {
			$fee_amount = floatval( $rule['maximum'] );
		}

		return $fee_amount;
	}

}
