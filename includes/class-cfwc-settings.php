<?php
/**
 * Settings class.
 *
 * Handles plugin settings and WooCommerce integration.
 *
 * @package CustomsFeesForWooCommerce
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CFWC_Settings class.
 *
 * @since 1.0.0
 */
class CFWC_Settings {

	/**
	 * Settings tab ID.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const TAB_ID = 'cfwc';

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
	 * Initialize settings.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		// Add Customs Fees as a section under Tax tab.
		add_filter( 'woocommerce_get_sections_tax', array( $this, 'add_customs_section' ) );
		
		// Output settings when in our section.
		add_action( 'woocommerce_settings_tax', array( $this, 'output_customs_settings' ) );
		
		// Save settings when in our section.
		add_action( 'woocommerce_settings_save_tax', array( $this, 'save_customs_settings' ) );
	}

	/**
	 * Add Customs Fees section to Tax tab.
	 *
	 * @since 1.0.0
	 * @param array $sections Existing sections.
	 * @return array Modified sections.
	 */
	public function add_customs_section( $sections ) {
		$sections['customs'] = __( 'Customs & Import Fees', 'customs-fees-for-woocommerce' );
		return $sections;
	}

	/**
	 * Output customs settings when in Tax tab.
	 *
	 * @since 1.0.0
	 */
	public function output_customs_settings() {
		global $current_section;
		
		// Only output our settings when in the customs section.
		if ( 'customs' !== $current_section ) {
			return;
		}
		
		// Render the consolidated rules and settings section.
		$this->render_consolidated_section();
	}

	/**
	 * Save customs settings when in Tax tab.
	 *
	 * @since 1.0.0
	 */
	public function save_customs_settings() {
		global $current_section;
		
		// Only save our settings when in the customs section.
		if ( 'customs' !== $current_section ) {
			return;
		}
		
		try {
			// Save rules.
			$this->save_rules();
			
			// Clear any cached data.
			wp_cache_flush();
			
			// Clear WooCommerce cart session to force recalculation.
			if ( WC()->session ) {
				WC()->session->set( 'cart_totals', null );
				WC()->session->set( 'cfwc_tooltip_text', null );
			}
			
			// Clear calculator cache if class exists.
			if ( class_exists( 'CFWC_Calculator' ) ) {
				$calculator = new CFWC_Calculator();
				if ( method_exists( $calculator, 'clear_cache' ) ) {
					$calculator->clear_cache();
				}
			}
		} catch ( Exception $e ) {
			// Log error only when debugging is enabled.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Only used in debug mode
				error_log( 'CFWC Save Error: ' . $e->getMessage() );
			}
		}
	}

	/**
	 * Render consolidated settings and rules section.
	 *
	 * @since 1.0.0
	 */
	private function render_consolidated_section() {
		// Get saved rules.
		$rules = get_option( 'cfwc_rules', array() );
		
		// Get templates for quick loading.
		$templates_handler = new CFWC_Templates();
		$templates = $templates_handler->get_templates();
		
		?>
		<div class="cfwc-settings-wrapper">
			<h2><?php esc_html_e( 'Customs & Import Fees Settings', 'customs-fees-for-woocommerce' ); ?></h2>
			
			<!-- Include the rules template -->
			<?php include CFWC_PLUGIN_DIR . 'includes/admin/views/rules-section.php'; ?>
		</div>
		<?php
	}

	/**
	 * Save fee rules.
	 *
	 * @since 1.0.0
	 */
	private function save_rules() {
		// Check nonce.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		if ( ! isset( $_POST['cfwc_rules_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cfwc_rules_nonce'] ) ), 'cfwc_save_rules' ) ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// Get and sanitize rules.
		$rules = array();
		
		// Rules are sent as JSON string from the frontend.
		if ( isset( $_POST['cfwc_rules'] ) && ! empty( $_POST['cfwc_rules'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$rules_json = wp_unslash( $_POST['cfwc_rules'] );
			
			// Handle if it's already an array (shouldn't happen but safe check)
			if ( is_array( $rules_json ) ) {
				$posted_rules = $rules_json;
			} else {
				// Decode JSON string to array.
				$posted_rules = json_decode( $rules_json, true );
			}
			
			// Make sure we have a valid array.
			if ( is_array( $posted_rules ) ) {
				foreach ( $posted_rules as $rule ) {
					// Skip if not an array or empty.
					if ( ! is_array( $rule ) || empty( $rule ) ) {
						continue;
					}
					
					$sanitized_rule = array(
						'country'        => isset( $rule['country'] ) ? sanitize_text_field( $rule['country'] ) : '',
						'origin_country' => isset( $rule['origin_country'] ) ? sanitize_text_field( $rule['origin_country'] ) : '',
						'type'           => isset( $rule['type'] ) ? sanitize_text_field( $rule['type'] ) : 'percentage',
						'rate'           => isset( $rule['rate'] ) ? floatval( $rule['rate'] ) : 0,
						'amount'         => isset( $rule['amount'] ) ? floatval( $rule['amount'] ) : 0,
						'label'          => isset( $rule['label'] ) ? sanitize_text_field( $rule['label'] ) : '',
						'taxable'        => isset( $rule['taxable'] ) ? (bool) $rule['taxable'] : true,
						'tax_class'      => isset( $rule['tax_class'] ) ? sanitize_text_field( $rule['tax_class'] ) : '',
					);
					
					// Only add if country is set.
					if ( ! empty( $sanitized_rule['country'] ) ) {
						$rules[] = $sanitized_rule;
					}
				}
			}
		}

		// Save rules.
		update_option( 'cfwc_rules', $rules );
		
		// Clear WooCommerce cart session to force recalculation.
		if ( WC()->session ) {
			WC()->session->set( 'cart_totals', null );
			WC()->session->set( 'cfwc_tooltip_text', null );
		}
		
		// Clear all caches.
		wp_cache_flush();
		
		// Don't add settings error here - let WooCommerce handle the success message
		// to avoid conflicts with their redirect process.
	}

	/**
	 * Get available countries for rules.
	 *
	 * @since 1.0.0
	 * @return array Countries array.
	 */
	public static function get_countries_for_rules() {
		$countries = WC()->countries->get_countries();
		
		// Add "All Countries" option.
		$countries = array_merge(
			array( '*' => __( 'All Countries', 'customs-fees-for-woocommerce' ) ),
			$countries
		);

		return apply_filters( 'cfwc_countries_for_rules', $countries );
	}
	
	/**
	 * Get default help tooltip text.
	 *
	 * @since 1.0.0
	 * @return string Default help text.
	 */
	public static function get_default_help_text() {
		return __( 'Estimated import duties and taxes based on destination country.', 'customs-fees-for-woocommerce' );
	}
}