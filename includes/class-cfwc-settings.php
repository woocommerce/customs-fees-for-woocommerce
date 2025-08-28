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
		// Add settings tab to WooCommerce.
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );
		add_action( 'woocommerce_settings_tabs_' . self::TAB_ID, array( $this, 'render_settings_tab' ) );
		add_action( 'woocommerce_update_options_' . self::TAB_ID, array( $this, 'save_settings' ) );
		
		// Add settings sections.
		add_action( 'woocommerce_settings_' . self::TAB_ID, array( $this, 'output_sections' ) );
	}

	/**
	 * Add settings tab to WooCommerce.
	 *
	 * @since 1.0.0
	 * @param array $tabs Existing tabs.
	 * @return array Modified tabs.
	 */
	public function add_settings_tab( $tabs ) {
		$tabs[ self::TAB_ID ] = __( 'Customs Fees', 'customs-fees-for-woocommerce' );
		return $tabs;
	}

	/**
	 * Output settings sections.
	 *
	 * @since 1.0.0
	 */
	public function output_sections() {
		global $current_section;

		$sections = array(
			''          => __( 'General', 'customs-fees-for-woocommerce' ),
			'rules'     => __( 'Fee Rules', 'customs-fees-for-woocommerce' ),
			'display'   => __( 'Display Settings', 'customs-fees-for-woocommerce' ),
			'templates' => __( 'Templates', 'customs-fees-for-woocommerce' ),
		);

		echo '<ul class="subsubsub">';

		$array_keys = array_keys( $sections );
		foreach ( $sections as $id => $label ) {
			echo '<li>';
			
			$url = admin_url( 'admin.php?page=wc-settings&tab=' . self::TAB_ID );
			if ( $id ) {
				$url .= '&section=' . sanitize_title( $id );
			}
			
			$class = ( $current_section === $id ) ? 'current' : '';
			
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
	 * Render settings tab content.
	 *
	 * @since 1.0.0
	 */
	public function render_settings_tab() {
		global $current_section;

		switch ( $current_section ) {
			case 'rules':
				$this->render_rules_section();
				break;
				
			case 'display':
				$this->render_display_section();
				break;
				
			case 'templates':
				$this->render_templates_section();
				break;
				
			default:
				$this->render_general_section();
				break;
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
				'title' => __( 'General Settings', 'customs-fees-for-woocommerce' ),
				'type'  => 'title',
				'desc'  => __( 'Configure general customs fees settings.', 'customs-fees-for-woocommerce' ),
				'id'    => 'cfwc_general_settings',
			),
			array(
				'title'   => __( 'Enable Customs Fees', 'customs-fees-for-woocommerce' ),
				'desc'    => __( 'Enable customs fee calculation at checkout', 'customs-fees-for-woocommerce' ),
				'id'      => 'cfwc_enabled',
				'type'    => 'checkbox',
				'default' => 'no',
			),
			array(
				'title'   => __( 'Display Mode', 'customs-fees-for-woocommerce' ),
				'desc'    => __( 'How to display customs fees at checkout', 'customs-fees-for-woocommerce' ),
				'id'      => 'cfwc_display_mode',
				'type'    => 'select',
				'default' => 'single',
				'options' => array(
					'single'    => __( 'Single line item', 'customs-fees-for-woocommerce' ),
					'breakdown' => __( 'Detailed breakdown', 'customs-fees-for-woocommerce' ),
				),
			),
			array(
				'title'   => __( 'Require Customer Agreement', 'customs-fees-for-woocommerce' ),
				'desc'    => __( 'Require customers to acknowledge customs fees before checkout', 'customs-fees-for-woocommerce' ),
				'id'      => 'cfwc_require_agreement',
				'type'    => 'checkbox',
				'default' => 'yes',
			),
			array(
				'title'       => __( 'Disclaimer Text', 'customs-fees-for-woocommerce' ),
				'desc'        => __( 'Text shown to customers about customs fees', 'customs-fees-for-woocommerce' ),
				'id'          => 'cfwc_disclaimer_text',
				'type'        => 'textarea',
				'default'     => __( 'Customs fees are estimates and actual fees at delivery may vary.', 'customs-fees-for-woocommerce' ),
				'css'         => 'width: 100%; height: 100px;',
				'placeholder' => __( 'Enter disclaimer text...', 'customs-fees-for-woocommerce' ),
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
		
		// Include the rules template.
		include CFWC_PLUGIN_DIR . 'includes/admin/views/rules-section.php';
	}

	/**
	 * Render display settings section.
	 *
	 * @since 1.0.0
	 */
	private function render_display_section() {
		$settings = $this->get_display_settings();
		WC_Admin_Settings::output_fields( $settings );
	}

	/**
	 * Get display settings fields.
	 *
	 * @since 1.0.0
	 * @return array Settings fields.
	 */
	private function get_display_settings() {
		$settings = array(
			array(
				'title' => __( 'Display Settings', 'customs-fees-for-woocommerce' ),
				'type'  => 'title',
				'desc'  => __( 'Configure how customs fees are displayed to customers.', 'customs-fees-for-woocommerce' ),
				'id'    => 'cfwc_display_settings',
			),
			array(
				'title'   => __( 'Show on Cart Page', 'customs-fees-for-woocommerce' ),
				'desc'    => __( 'Display customs fees on the cart page', 'customs-fees-for-woocommerce' ),
				'id'      => 'cfwc_show_on_cart',
				'type'    => 'checkbox',
				'default' => 'yes',
			),
			array(
				'title'   => __( 'Show on Checkout Page', 'customs-fees-for-woocommerce' ),
				'desc'    => __( 'Display customs fees on the checkout page', 'customs-fees-for-woocommerce' ),
				'id'      => 'cfwc_show_on_checkout',
				'type'    => 'checkbox',
				'default' => 'yes',
			),
			array(
				'title'   => __( 'Show Tooltip', 'customs-fees-for-woocommerce' ),
				'desc'    => __( 'Display an information tooltip next to customs fees', 'customs-fees-for-woocommerce' ),
				'id'      => 'cfwc_show_tooltip',
				'type'    => 'checkbox',
				'default' => 'yes',
			),
			array(
				'title'       => __( 'Tooltip Text', 'customs-fees-for-woocommerce' ),
				'desc'        => __( 'Text shown in the tooltip', 'customs-fees-for-woocommerce' ),
				'id'          => 'cfwc_tooltip_text',
				'type'        => 'text',
				'default'     => __( 'Estimated import duties and taxes based on destination country.', 'customs-fees-for-woocommerce' ),
				'css'         => 'width: 100%;',
				'placeholder' => __( 'Enter tooltip text...', 'customs-fees-for-woocommerce' ),
			),
			array(
				'title'   => __( 'Show in Order Emails', 'customs-fees-for-woocommerce' ),
				'desc'    => __( 'Include customs fees in order confirmation emails', 'customs-fees-for-woocommerce' ),
				'id'      => 'cfwc_show_in_emails',
				'type'    => 'checkbox',
				'default' => 'yes',
			),
			array(
				'title'   => __( 'Show in My Account', 'customs-fees-for-woocommerce' ),
				'desc'    => __( 'Display customs fees in customer order history', 'customs-fees-for-woocommerce' ),
				'id'      => 'cfwc_show_in_account',
				'type'    => 'checkbox',
				'default' => 'yes',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'cfwc_display_settings',
			),
		);

		return apply_filters( 'cfwc_display_settings', $settings );
	}

	/**
	 * Render templates section.
	 *
	 * @since 1.0.0
	 */
	private function render_templates_section() {
		// Include the templates view.
		include CFWC_PLUGIN_DIR . 'includes/admin/views/templates-section.php';
	}

	/**
	 * Save settings.
	 *
	 * @since 1.0.0
	 */
	public function save_settings() {
		global $current_section;

		switch ( $current_section ) {
			case 'rules':
				$this->save_rules();
				break;
				
			case 'display':
				$settings = $this->get_display_settings();
				WC_Admin_Settings::save_fields( $settings );
				break;
				
			case 'templates':
				// Templates are loaded via AJAX, no save action needed here.
				break;
				
			default:
				$settings = $this->get_general_settings();
				WC_Admin_Settings::save_fields( $settings );
				break;
		}

		// Clear any cached data.
		wp_cache_flush();
		
		// Clear calculator cache.
		$calculator = new CFWC_Calculator();
		$calculator->clear_cache();
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
		
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $_POST['cfwc_rules'] ) && is_array( $_POST['cfwc_rules'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$posted_rules = wp_unslash( $_POST['cfwc_rules'] );
			foreach ( $posted_rules as $rule ) {
				$sanitized_rule = array(
					'country'   => sanitize_text_field( $rule['country'] ?? '' ),
					'type'      => sanitize_text_field( $rule['type'] ?? 'percentage' ),
					'rate'      => floatval( $rule['rate'] ?? 0 ),
					'amount'    => floatval( $rule['amount'] ?? 0 ),
					'minimum'   => floatval( $rule['minimum'] ?? 0 ),
					'maximum'   => floatval( $rule['maximum'] ?? 0 ),
					'label'     => sanitize_text_field( $rule['label'] ?? '' ),
					'taxable'   => isset( $rule['taxable'] ) ? (bool) $rule['taxable'] : true,
					'tax_class' => sanitize_text_field( $rule['tax_class'] ?? '' ),
				);
				
				// Only add if country is set.
				if ( ! empty( $sanitized_rule['country'] ) ) {
					$rules[] = $sanitized_rule;
				}
			}
		}

		// Save rules.
		update_option( 'cfwc_rules', $rules );

		// Add admin notice.
		add_settings_error(
			'cfwc_rules',
			'cfwc_rules_saved',
			__( 'Fee rules saved successfully.', 'customs-fees-for-woocommerce' ),
			'success'
		);
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
