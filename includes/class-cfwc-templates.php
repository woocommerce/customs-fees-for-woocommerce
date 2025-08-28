<?php
/**
 * Templates handler for preset rule configurations.
 *
 * @package CustomsFeesForWooCommerce
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CFWC_Templates class.
 *
 * Provides preset templates for common customs fee scenarios.
 *
 * @since 1.0.0
 */
class CFWC_Templates {

	/**
	 * Available preset templates.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $templates = array();

	/**
	 * Initialize the templates handler.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		$this->load_templates();
		
		// Add AJAX handlers for template loading.
		add_action( 'wp_ajax_cfwc_load_template', array( $this, 'ajax_load_template' ) );
		add_action( 'wp_ajax_cfwc_get_templates', array( $this, 'ajax_get_templates' ) );
	}

	/**
	 * Load available templates.
	 *
	 * @since 1.0.0
	 */
	private function load_templates() {
		$this->templates = array(
			'us_general' => array(
				'name'        => __( 'US General Import (10%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'Standard 10% import duty for general merchandise entering the US', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'   => 'US',
						'type'      => 'percentage',
						'rate'      => 10,
						'amount'    => 0,
						'minimum'   => 5,
						'maximum'   => 0,
						'label'     => __( 'US Import Duty', 'customs-fees-for-woocommerce' ),
						'taxable'   => true,
						'tax_class' => '',
					),
				),
			),
			'eu_to_us' => array(
				'name'        => __( 'EU to US Import', 'customs-fees-for-woocommerce' ),
				'description' => __( 'Common rates for EU goods entering the US market', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'   => 'US',
						'type'      => 'percentage',
						'rate'      => 12,
						'amount'    => 0,
						'minimum'   => 10,
						'maximum'   => 0,
						'label'     => __( 'US Import Duty (from EU)', 'customs-fees-for-woocommerce' ),
						'taxable'   => true,
						'tax_class' => '',
					),
				),
			),
			'china_to_us' => array(
				'name'        => __( 'China to US Import', 'customs-fees-for-woocommerce' ),
				'description' => __( 'Import duties for Chinese goods entering the US', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'   => 'US',
						'type'      => 'percentage',
						'rate'      => 25,
						'amount'    => 0,
						'minimum'   => 15,
						'maximum'   => 0,
						'label'     => __( 'US Import Duty (from China)', 'customs-fees-for-woocommerce' ),
						'taxable'   => true,
						'tax_class' => '',
					),
				),
			),
			'uk_vat' => array(
				'name'        => __( 'UK VAT & Duty', 'customs-fees-for-woocommerce' ),
				'description' => __( 'UK import VAT (20%) and duty for international shipments', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'   => 'GB',
						'type'      => 'percentage',
						'rate'      => 20,
						'amount'    => 0,
						'minimum'   => 0,
						'maximum'   => 0,
						'label'     => __( 'UK Import VAT', 'customs-fees-for-woocommerce' ),
						'taxable'   => false,
						'tax_class' => '',
					),
					array(
						'country'   => 'GB',
						'type'      => 'percentage',
						'rate'      => 5,
						'amount'    => 0,
						'minimum'   => 0,
						'maximum'   => 0,
						'label'     => __( 'UK Import Duty', 'customs-fees-for-woocommerce' ),
						'taxable'   => false,
						'tax_class' => '',
					),
				),
			),
			'canada_gst' => array(
				'name'        => __( 'Canada GST & Duty', 'customs-fees-for-woocommerce' ),
				'description' => __( 'Canadian GST and import duties', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'   => 'CA',
						'type'      => 'percentage',
						'rate'      => 5,
						'amount'    => 0,
						'minimum'   => 0,
						'maximum'   => 0,
						'label'     => __( 'Canadian GST', 'customs-fees-for-woocommerce' ),
						'taxable'   => false,
						'tax_class' => '',
					),
					array(
						'country'   => 'CA',
						'type'      => 'percentage',
						'rate'      => 8,
						'amount'    => 0,
						'minimum'   => 20,
						'maximum'   => 0,
						'label'     => __( 'Canadian Import Duty', 'customs-fees-for-woocommerce' ),
						'taxable'   => false,
						'tax_class' => '',
					),
				),
			),
			'australia_gst' => array(
				'name'        => __( 'Australia GST', 'customs-fees-for-woocommerce' ),
				'description' => __( 'Australian GST (10%) on imported goods', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'   => 'AU',
						'type'      => 'percentage',
						'rate'      => 10,
						'amount'    => 0,
						'minimum'   => 0,
						'maximum'   => 0,
						'label'     => __( 'Australian GST', 'customs-fees-for-woocommerce' ),
						'taxable'   => false,
						'tax_class' => '',
					),
				),
			),
		);

		/**
		 * Filter available templates.
		 *
		 * @since 1.0.0
		 * @param array $templates Available templates.
		 */
		$this->templates = apply_filters( 'cfwc_preset_templates', $this->templates );
	}

	/**
	 * Get all templates.
	 *
	 * @since 1.0.0
	 * @return array Templates.
	 */
	public function get_templates() {
		return $this->templates;
	}

	/**
	 * Get a specific template.
	 *
	 * @since 1.0.0
	 * @param string $template_id Template ID.
	 * @return array|false Template data or false if not found.
	 */
	public function get_template( $template_id ) {
		return isset( $this->templates[ $template_id ] ) ? $this->templates[ $template_id ] : false;
	}

	/**
	 * Apply a template to current rules.
	 *
	 * @since 1.0.0
	 * @param string $template_id Template ID.
	 * @param bool   $append      Whether to append to existing rules or replace.
	 * @return bool Success status.
	 */
	public function apply_template( $template_id, $append = false ) {
		$template = $this->get_template( $template_id );
		
		if ( ! $template ) {
			return false;
		}

		$existing_rules = get_option( 'cfwc_rules', array() );
		
		if ( $append ) {
			$rules = array_merge( $existing_rules, $template['rules'] );
		} else {
			$rules = $template['rules'];
		}

		update_option( 'cfwc_rules', $rules );
		
		// Clear cache.
		$calculator = new CFWC_Calculator();
		$calculator->clear_cache();
		
		return true;
	}

	/**
	 * AJAX handler to load a template.
	 *
	 * @since 1.0.0
	 */
	public function ajax_load_template() {
		check_ajax_referer( 'cfwc_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( -1 );
		}

		$template_id = sanitize_text_field( wp_unslash( $_POST['template_id'] ?? '' ) );
		$append      = isset( $_POST['append'] ) && 'true' === $_POST['append'];
		
		if ( $this->apply_template( $template_id, $append ) ) {
			wp_send_json_success( array(
				'message' => __( 'Template applied successfully.', 'customs-fees-for-woocommerce' ),
				'rules'   => get_option( 'cfwc_rules', array() ),
			) );
		} else {
			wp_send_json_error( array(
				'message' => __( 'Failed to apply template.', 'customs-fees-for-woocommerce' ),
			) );
		}
	}

	/**
	 * AJAX handler to get all templates.
	 *
	 * @since 1.0.0
	 */
	public function ajax_get_templates() {
		check_ajax_referer( 'cfwc_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( -1 );
		}

		wp_send_json_success( $this->templates );
	}
}
