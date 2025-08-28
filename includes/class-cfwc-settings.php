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
		$sections['customs'] = __( 'Customs Fees', 'customs-fees-for-woocommerce' );
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
		
		// Get the subsection.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading URL parameter for display purposes.
		$subsection = isset( $_GET['subsection'] ) ? sanitize_text_field( wp_unslash( $_GET['subsection'] ) ) : '';
		
		// Output subsection navigation.
		$this->output_subsection_nav( $subsection );
		
		// Output the appropriate settings based on subsection.
		if ( 'rules' === $subsection ) {
			$this->render_rules_section();
		} else {
			$this->render_general_section();
		}
	}
	
	/**
	 * Output subsection navigation.
	 *
	 * @since 1.0.0
	 * @param string $current_subsection Current subsection.
	 */
	private function output_subsection_nav( $current_subsection ) {
		$subsections = array(
			''          => __( 'Settings', 'customs-fees-for-woocommerce' ),
			'rules'     => __( 'Fee Rules', 'customs-fees-for-woocommerce' ),
		);
		
		echo '<ul class="subsubsub">';
		
		$array_keys = array_keys( $subsections );
		foreach ( $subsections as $id => $label ) {
			echo '<li>';
			
			$url = admin_url( 'admin.php?page=wc-settings&tab=tax&section=customs' );
			if ( $id ) {
				$url .= '&subsection=' . sanitize_title( $id );
			}
			
			$class = ( $current_subsection === $id ) ? 'current' : '';
			
			printf(
				'<a href="%s" class="%s">%s</a>',
				esc_url( $url ),
				esc_attr( $class ),
				esc_html( $label )
			);
			
			if ( end( $array_keys ) !== $id ) {
				echo ' | ';
			}
			
			echo '</li>';
		}
		
		echo '</ul><br class="clear" />';
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
			// Get the subsection.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading URL parameter for display purposes.
			$subsection = isset( $_GET['subsection'] ) ? sanitize_text_field( wp_unslash( $_GET['subsection'] ) ) : '';
			
			// Save the appropriate settings based on subsection.
			if ( 'rules' === $subsection ) {
				$this->save_rules();
			} else {
				$settings = $this->get_general_settings();
				if ( ! empty( $settings ) ) {
					WC_Admin_Settings::save_fields( $settings );
				}
			}
			
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
	 * Render general settings section.
	 *
	 * @since 1.0.0
	 */
	private function render_general_section() {
		$settings = $this->get_general_settings();
		WC_Admin_Settings::output_fields( $settings );
	}

	/**
	 * Get general settings fields.
	 *
	 * @since 1.0.0
	 * @return array Settings fields.
	 */
	private function get_general_settings() {
		$settings = array(
			array(
				'title' => __( 'Customs Fees Settings', 'customs-fees-for-woocommerce' ),
				'type'  => 'title',
				'desc'  => '',
				'id'    => 'cfwc_general_settings',
			),
			array(
				'title'   => __( 'Display Mode', 'customs-fees-for-woocommerce' ),
				'desc'    => __( 'Choose how fees appear at checkout', 'customs-fees-for-woocommerce' ),
				'id'      => 'cfwc_display_mode',
				'type'    => 'select',
				'default' => 'single',
				'options' => array(
					'single'    => __( 'Single line item', 'customs-fees-for-woocommerce' ),
					'breakdown' => __( 'Detailed breakdown', 'customs-fees-for-woocommerce' ),
				),
			),
			array(
				'title'       => __( 'Help Text', 'customs-fees-for-woocommerce' ),
				'desc'        => __( 'Optional tooltip shown next to customs fees', 'customs-fees-for-woocommerce' ),
				'id'          => 'cfwc_tooltip_text',
				'type'        => 'textarea',
				'default'     => 'Estimated import duties and taxes based on destination country.',
				'css'         => 'width: 400px; height: 50px; resize: vertical;',
				'custom_attributes' => array(
					'rows' => 2,
				),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'cfwc_general_settings',
			),
		);

		return apply_filters( 'cfwc_general_settings', $settings );
	}

	/**
	 * Render fee rules section.
	 *
	 * @since 1.0.0
	 */
	private function render_rules_section() {
		// Get saved rules.
		$rules = get_option( 'cfwc_rules', array() );
		
		// Get templates for quick loading.
		$templates_handler = new CFWC_Templates();
		$templates = $templates_handler->get_templates();
		
		// Include the rules template.
		include CFWC_PLUGIN_DIR . 'includes/admin/views/rules-section.php';
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
						'country'   => isset( $rule['country'] ) ? sanitize_text_field( $rule['country'] ) : '',
						'type'      => isset( $rule['type'] ) ? sanitize_text_field( $rule['type'] ) : 'percentage',
						'rate'      => isset( $rule['rate'] ) ? floatval( $rule['rate'] ) : 0,
						'amount'    => isset( $rule['amount'] ) ? floatval( $rule['amount'] ) : 0,
						'minimum'   => isset( $rule['minimum'] ) ? floatval( $rule['minimum'] ) : 0,
						'maximum'   => isset( $rule['maximum'] ) ? floatval( $rule['maximum'] ) : 0,
						'label'     => isset( $rule['label'] ) ? sanitize_text_field( $rule['label'] ) : '',
						'taxable'   => isset( $rule['taxable'] ) ? (bool) $rule['taxable'] : true,
						'tax_class' => isset( $rule['tax_class'] ) ? sanitize_text_field( $rule['tax_class'] ) : '',
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
}
