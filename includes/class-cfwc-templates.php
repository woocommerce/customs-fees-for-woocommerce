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
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Templates are loaded on demand to avoid issues with translation functions.
	}

	/**
	 * Initialize the templates handler.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		// Add AJAX handlers for template loading.
		add_action( 'wp_ajax_cfwc_load_template', array( $this, 'ajax_load_template' ) );
		add_action( 'wp_ajax_cfwc_get_templates', array( $this, 'ajax_get_templates' ) );
		add_action( 'wp_ajax_cfwc_apply_template', array( $this, 'ajax_apply_template' ) );
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
						'country'        => 'US',
						'origin_country' => '',  // Applies to all origins
						'type'           => 'percentage',
						'rate'           => 10,
						'amount'         => 0,
						'label'          => __( 'US Import Duty', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
				),
			),
			'eu_to_us' => array(
				'name'        => __( 'EU to US Import (15%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'Standard 15% rate for EU goods entering US', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'        => 'US',
						'origin_country' => 'EU',  // Applies to EU countries
						'type'           => 'percentage',
						'rate'           => 15,
						'amount'         => 0,
						'label'          => __( 'US Import Duty (from EU)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
				),
			),
			'china_to_us' => array(
				'name'        => __( 'China to US Import (25%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for Chinese goods - 25%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'        => 'US',
						'origin_country' => 'CN',  // China
						'type'           => 'percentage',
						'rate'           => 25,
						'amount'         => 0,
						'label'          => __( 'US Tariff (China)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
				),
			),
			
			// US Reciprocal Tariffs - Major Trading Partners
			'us_india_tariff' => array(
				'name'        => __( 'India to US (25%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from India - 25%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'        => 'US',
						'origin_country' => 'IN',
						'type'           => 'percentage',
						'rate'           => 25,
						'amount'         => 0,
						'label'          => __( 'US Tariff (India)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
				),
			),
			'us_uk_tariff' => array(
				'name'        => __( 'UK to US (10%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from United Kingdom - 10%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'        => 'US',
						'origin_country' => 'GB',
						'type'           => 'percentage',
						'rate'           => 10,
						'amount'         => 0,
						'label'          => __( 'US Tariff (UK)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
				),
			),
			'us_japan_tariff' => array(
				'name'        => __( 'Japan to US (15%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from Japan - 15%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'        => 'US',
						'origin_country' => 'JP',
						'type'           => 'percentage',
						'rate'           => 15,
						'amount'         => 0,
						'label'          => __( 'US Tariff (Japan)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
				),
			),
			'us_brazil_tariff' => array(
				'name'        => __( 'Brazil to US (10%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from Brazil - 10%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'        => 'US',
						'origin_country' => 'BR',
						'type'           => 'percentage',
						'rate'           => 10,
						'amount'         => 0,
						'label'          => __( 'US Tariff (Brazil)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
				),
			),
			'us_switzerland_tariff' => array(
				'name'        => __( 'Switzerland to US (39%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from Switzerland - 39%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'        => 'US',
						'origin_country' => 'CH',
						'type'           => 'percentage',
						'rate'           => 39,
						'amount'         => 0,
						'label'          => __( 'US Tariff (Switzerland)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
				),
			),
			
			// Southeast Asian Countries
			'us_vietnam_tariff' => array(
				'name'        => __( 'Vietnam to US (20%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from Vietnam - 20%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'        => 'US',
						'origin_country' => 'VN',
						'type'           => 'percentage',
						'rate'           => 20,
						'amount'         => 0,
						'label'          => __( 'US Tariff (Vietnam)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
				),
			),
			'us_thailand_tariff' => array(
				'name'        => __( 'Thailand to US (19%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from Thailand - 19%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'        => 'US',
						'origin_country' => 'TH',
						'type'           => 'percentage',
						'rate'           => 19,
						'amount'         => 0,
						'label'          => __( 'US Tariff (Thailand)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
				),
			),
			'us_indonesia_tariff' => array(
				'name'        => __( 'Indonesia to US (19%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from Indonesia - 19%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'        => 'US',
						'origin_country' => 'ID',
						'type'           => 'percentage',
						'rate'           => 19,
						'amount'         => 0,
						'label'          => __( 'US Tariff (Indonesia)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
				),
			),
			
			// South Asian Countries
			'us_bangladesh_tariff' => array(
				'name'        => __( 'Bangladesh to US (20%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from Bangladesh - 20%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'        => 'US',
						'origin_country' => 'BD',
						'type'           => 'percentage',
						'rate'           => 20,
						'amount'         => 0,
						'label'          => __( 'US Tariff (Bangladesh)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
				),
			),
			'us_pakistan_tariff' => array(
				'name'        => __( 'Pakistan to US (19%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from Pakistan - 19%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'        => 'US',
						'origin_country' => 'PK',
						'type'           => 'percentage',
						'rate'           => 19,
						'amount'         => 0,
						'label'          => __( 'US Tariff (Pakistan)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
				),
			),
			
			// East Asian Countries
			'us_south_korea_tariff' => array(
				'name'        => __( 'South Korea to US (15%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from South Korea - 15%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'        => 'US',
						'origin_country' => 'KR',
						'type'           => 'percentage',
						'rate'           => 15,
						'amount'         => 0,
						'label'          => __( 'US Tariff (South Korea)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
				),
			),
			'us_taiwan_tariff' => array(
				'name'        => __( 'Taiwan to US (20%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from Taiwan - 20%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'        => 'US',
						'origin_country' => 'TW',
						'type'           => 'percentage',
						'rate'           => 20,
						'amount'         => 0,
						'label'          => __( 'US Tariff (Taiwan)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
				),
			),
			
			// Middle Eastern Countries
			'us_turkey_tariff' => array(
				'name'        => __( 'Turkey to US (15%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from Turkey - 15%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'        => 'US',
						'origin_country' => 'TR',
						'type'           => 'percentage',
						'rate'           => 15,
						'amount'         => 0,
						'label'          => __( 'US Tariff (Turkey)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
				),
			),
			'us_israel_tariff' => array(
				'name'        => __( 'Israel to US (15%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from Israel - 15%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'        => 'US',
						'origin_country' => 'IL',
						'type'           => 'percentage',
						'rate'           => 15,
						'amount'         => 0,
						'label'          => __( 'US Tariff (Israel)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
				),
			),
			
			// African Countries
			'us_south_africa_tariff' => array(
				'name'        => __( 'South Africa to US (30%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from South Africa - 30%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'        => 'US',
						'origin_country' => 'ZA',
						'type'           => 'percentage',
						'rate'           => 30,
						'amount'         => 0,
						'label'          => __( 'US Tariff (South Africa)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
				),
			),
			'us_nigeria_tariff' => array(
				'name'        => __( 'Nigeria to US (15%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from Nigeria - 15%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'        => 'US',
						'origin_country' => 'NG',
						'type'           => 'percentage',
						'rate'           => 15,
						'amount'         => 0,
						'label'          => __( 'US Tariff (Nigeria)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
				),
			),
			
			// Pacific Countries
			'us_new_zealand_tariff' => array(
				'name'        => __( 'New Zealand to US (15%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from New Zealand - 15%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'        => 'US',
						'origin_country' => 'NZ',
						'type'           => 'percentage',
						'rate'           => 15,
						'amount'         => 0,
						'label'          => __( 'US Tariff (New Zealand)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
				),
			),
			
			'uk_vat' => array(
				'name'        => __( 'UK VAT & Duty', 'customs-fees-for-woocommerce' ),
				'description' => __( 'UK import VAT (20%) and duty for international shipments', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'        => 'GB',
						'origin_country' => '',  // Applies to all origins
						'type'           => 'percentage',
						'rate'           => 20,
						'amount'         => 0,
						'label'          => __( 'UK Import VAT', 'customs-fees-for-woocommerce' ),
						'taxable'        => false,
						'tax_class'      => '',
					),
					array(
						'country'        => 'GB',
						'origin_country' => '',  // Applies to all origins
						'type'           => 'percentage',
						'rate'           => 5,
						'amount'         => 0,
						'label'          => __( 'UK Import Duty', 'customs-fees-for-woocommerce' ),
						'taxable'        => false,
						'tax_class'      => '',
					),
				),
			),
			'canada_gst' => array(
				'name'        => __( 'Canada GST & Duty', 'customs-fees-for-woocommerce' ),
				'description' => __( 'Canadian GST and import duties', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'        => 'CA',
						'origin_country' => '',  // Applies to all origins
						'type'           => 'percentage',
						'rate'           => 5,
						'amount'         => 0,
						'label'          => __( 'Canadian GST', 'customs-fees-for-woocommerce' ),
						'taxable'        => false,
						'tax_class'      => '',
					),
					array(
						'country'        => 'CA',
						'origin_country' => '',  // Applies to all origins
						'type'           => 'percentage',
						'rate'           => 8,
						'amount'         => 0,
						'label'          => __( 'Canadian Import Duty', 'customs-fees-for-woocommerce' ),
						'taxable'        => false,
						'tax_class'      => '',
					),
				),
			),
			
			// Multi-Country Groups
			'us_high_tariff_countries' => array(
				'name'        => __( 'US High Tariff Countries (30%+)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'Countries with 30% or higher US reciprocal tariffs', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'        => 'US',
						'origin_country' => 'CH',  // Switzerland - 39%
						'type'           => 'percentage',
						'rate'           => 39,
						'amount'         => 0,
						'label'          => __( 'US Tariff (Switzerland)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
					array(
						'country'        => 'US',
						'origin_country' => 'SY',  // Syria - 41%
						'type'           => 'percentage',
						'rate'           => 41,
						'amount'         => 0,
						'label'          => __( 'US Tariff (Syria)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
					array(
						'country'        => 'US',
						'origin_country' => 'MM',  // Myanmar - 40%
						'type'           => 'percentage',
						'rate'           => 40,
						'amount'         => 0,
						'label'          => __( 'US Tariff (Myanmar)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
					array(
						'country'        => 'US',
						'origin_country' => 'LA',  // Laos - 40%
						'type'           => 'percentage',
						'rate'           => 40,
						'amount'         => 0,
						'label'          => __( 'US Tariff (Laos)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
					array(
						'country'        => 'US',
						'origin_country' => 'IQ',  // Iraq - 35%
						'type'           => 'percentage',
						'rate'           => 35,
						'amount'         => 0,
						'label'          => __( 'US Tariff (Iraq)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
					array(
						'country'        => 'US',
						'origin_country' => 'RS',  // Serbia - 35%
						'type'           => 'percentage',
						'rate'           => 35,
						'amount'         => 0,
						'label'          => __( 'US Tariff (Serbia)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
					array(
						'country'        => 'US',
						'origin_country' => 'DZ',  // Algeria - 30%
						'type'           => 'percentage',
						'rate'           => 30,
						'amount'         => 0,
						'label'          => __( 'US Tariff (Algeria)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
					array(
						'country'        => 'US',
						'origin_country' => 'BA',  // Bosnia and Herzegovina - 30%
						'type'           => 'percentage',
						'rate'           => 30,
						'amount'         => 0,
						'label'          => __( 'US Tariff (Bosnia)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
					array(
						'country'        => 'US',
						'origin_country' => 'LY',  // Libya - 30%
						'type'           => 'percentage',
						'rate'           => 30,
						'amount'         => 0,
						'label'          => __( 'US Tariff (Libya)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
				),
			),
			
			'us_southeast_asia_all' => array(
				'name'        => __( 'US-Southeast Asia All Countries', 'customs-fees-for-woocommerce' ),
				'description' => __( 'All Southeast Asian countries with US reciprocal tariffs', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'        => 'US',
						'origin_country' => 'VN',  // Vietnam - 20%
						'type'           => 'percentage',
						'rate'           => 20,
						'amount'         => 0,
						'label'          => __( 'US Tariff (Vietnam)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
					array(
						'country'        => 'US',
						'origin_country' => 'TH',  // Thailand - 19%
						'type'           => 'percentage',
						'rate'           => 19,
						'amount'         => 0,
						'label'          => __( 'US Tariff (Thailand)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
					array(
						'country'        => 'US',
						'origin_country' => 'MY',  // Malaysia - 19%
						'type'           => 'percentage',
						'rate'           => 19,
						'amount'         => 0,
						'label'          => __( 'US Tariff (Malaysia)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
					array(
						'country'        => 'US',
						'origin_country' => 'ID',  // Indonesia - 19%
						'type'           => 'percentage',
						'rate'           => 19,
						'amount'         => 0,
						'label'          => __( 'US Tariff (Indonesia)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
					array(
						'country'        => 'US',
						'origin_country' => 'PH',  // Philippines - 19%
						'type'           => 'percentage',
						'rate'           => 19,
						'amount'         => 0,
						'label'          => __( 'US Tariff (Philippines)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
					array(
						'country'        => 'US',
						'origin_country' => 'KH',  // Cambodia - 19%
						'type'           => 'percentage',
						'rate'           => 19,
						'amount'         => 0,
						'label'          => __( 'US Tariff (Cambodia)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
					array(
						'country'        => 'US',
						'origin_country' => 'BN',  // Brunei - 25%
						'type'           => 'percentage',
						'rate'           => 25,
						'amount'         => 0,
						'label'          => __( 'US Tariff (Brunei)', 'customs-fees-for-woocommerce' ),
						'taxable'        => true,
						'tax_class'      => '',
					),
				),
			),
			
			'australia_gst' => array(
				'name'        => __( 'Australia GST', 'customs-fees-for-woocommerce' ),
				'description' => __( 'Australian GST (10%) on imported goods', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'        => 'AU',
						'origin_country' => '',  // Applies to all origins
						'type'           => 'percentage',
						'rate'           => 10,
						'amount'         => 0,
						'label'          => __( 'Australian GST', 'customs-fees-for-woocommerce' ),
						'taxable'        => false,
						'tax_class'      => '',
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
		// Load templates on demand if not already loaded.
		if ( empty( $this->templates ) ) {
			$this->load_templates();
		}
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
		// Ensure templates are loaded.
		if ( empty( $this->templates ) ) {
			$this->load_templates();
		}
		return isset( $this->templates[ $template_id ] ) ? $this->templates[ $template_id ] : false;
	}

	/**
	 * Apply a template to current rules.
	 *
	 * @since 1.0.0
	 * @param string $template_id Template ID.
	 * @param bool   $append      Whether to append to existing rules or replace.
	 * @return array|false Array with status and counts, or false on error.
	 */
	public function apply_template( $template_id, $append = false ) {
		$template = $this->get_template( $template_id );
		
		if ( ! $template ) {
			return false;
		}

		$existing_rules = get_option( 'cfwc_rules', array() );
		$added_count = 0;
		$duplicate_count = 0;
		
		if ( $append ) {
			// Check for duplicates when appending
			$rules = $existing_rules;
			
			foreach ( $template['rules'] as $new_rule ) {
				// Check if rule already exists (same country + label combination)
				$is_duplicate = false;
				foreach ( $existing_rules as $existing_rule ) {
					if ( $existing_rule['country'] === $new_rule['country'] && 
					     $existing_rule['label'] === $new_rule['label'] ) {
						$is_duplicate = true;
						$duplicate_count++;
						break;
					}
				}
				
				if ( ! $is_duplicate ) {
					$rules[] = $new_rule;
					$added_count++;
				}
			}
		} else {
			// Replace all rules
			$rules = $template['rules'];
			$added_count = count( $template['rules'] );
		}

		update_option( 'cfwc_rules', $rules );
		
		// Clear cache.
		$calculator = new CFWC_Calculator();
		$calculator->clear_cache();
		
		return array(
			'success' => true,
			'added' => $added_count,
			'duplicates' => $duplicate_count,
			'total_rules' => count( $rules ),
		);
	}

	/**
	 * AJAX handler to apply a template.
	 *
	 * @since 1.0.0
	 */
	public function ajax_apply_template() {
		check_ajax_referer( 'cfwc_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( -1 );
		}

		$template_id = sanitize_text_field( wp_unslash( $_POST['template_id'] ?? '' ) );
		$append      = isset( $_POST['append'] ) && 'true' === $_POST['append'];
		
		$result = $this->apply_template( $template_id, $append );
		
		if ( $result && is_array( $result ) ) {
			// Build appropriate message based on results
			$message = '';
			
			if ( $append ) {
				if ( $result['duplicates'] > 0 && $result['added'] > 0 ) {
					$message = sprintf(
						/* translators: %1$d: number of rules added, %2$d: number of duplicates skipped */
						__( 'Added %1$d new rules. Skipped %2$d duplicates.', 'customs-fees-for-woocommerce' ),
						$result['added'],
						$result['duplicates']
					);
				} elseif ( $result['duplicates'] > 0 && $result['added'] === 0 ) {
					$message = __( 'All preset rules already exist. No rules added.', 'customs-fees-for-woocommerce' );
				} else {
					$message = sprintf(
						/* translators: %d: number of rules added */
						__( '%d preset rules added successfully.', 'customs-fees-for-woocommerce' ),
						$result['added']
					);
				}
			} else {
				$message = __( 'All rules replaced with preset.', 'customs-fees-for-woocommerce' );
			}
			
			wp_send_json_success( array(
				'message' => $message,
				'rules'   => get_option( 'cfwc_rules', array() ),
				'added'   => $result['added'],
				'duplicates' => $result['duplicates'],
			) );
		} else {
			wp_send_json_error( array(
				'message' => __( 'Failed to apply preset.', 'customs-fees-for-woocommerce' ),
			) );
		}
	}

	/**
	 * AJAX handler to load a template (legacy).
	 *
	 * @since 1.0.0
	 */
	public function ajax_load_template() {
		$this->ajax_apply_template();
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
