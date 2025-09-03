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
			'us_general'               => array(
				'name'        => __( 'US General Import (10%)', 'customs-fees-for-woocommerce' ),
				'description' => __( '⚠️ BASIC RULE: Only for general US imports. For China→US, use the China preset instead. This rule may stack with country-specific rules unless set to "Exclusive".', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'from_country'  => '',  // Any origin.
						'to_country'    => 'US',
						'match_type'    => 'all',
						'type'          => 'percentage',
						'rate'          => 10,
						'amount'        => 0,
						'priority'      => 5,  // Low priority - general fallback.
						'stacking_mode' => 'add',  // Will stack with other rules!
						'label'         => __( 'US Tariff (General)', 'customs-fees-for-woocommerce' ),
						'taxable'       => true,
						'tax_class'     => '',
					),
				),
			),

			'china_to_us'              => array(
				'name'        => __( 'China to US Import Tariffs (2025)', 'customs-fees-for-woocommerce' ),
				'description' => __( '⚠️ COMPLETE PRESET: Clear existing rules before applying! This preset includes ALL China→US tariffs (Section 301, 232, fentanyl). No additional US import rules needed.', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					// Apparel/Clothing - 7.5% to 70% (Section 301 + baseline, particularly high ~69%)
					array(
						'from_country'    => 'CN',
						'to_country'      => 'US',
						'match_type'      => 'hs_code',
						'hs_code_pattern' => '61*,62*',  // Apparel & Clothing.
						'type'            => 'percentage',
						'rate'            => 69,  // High rate for apparel.
						'priority'        => 30,
						'stacking_mode'   => 'exclusive',
						'label'           => __( 'China Apparel (Section 301 + Fentanyl)', 'customs-fees-for-woocommerce' ),
						'taxable'         => true,
						'tax_class'       => '',
					),
					// Electronics - 0% to 50% (some exemptions apply)
					array(
						'from_country'    => 'CN',
						'to_country'      => 'US',
						'match_type'      => 'hs_code',
						'hs_code_pattern' => '85*',  // Electronics & Electrical Equipment.
						'type'            => 'percentage',
						'rate'            => 25,  // Average electronics rate.
						'priority'        => 25,
						'stacking_mode'   => 'exclusive',
						'label'           => __( 'China Electronics (Section 301)', 'customs-fees-for-woocommerce' ),
						'taxable'         => true,
						'tax_class'       => '',
					),
					// Solar Cells/Equipment & Semiconductors - 50%
					array(
						'from_country'    => 'CN',
						'to_country'      => 'US',
						'match_type'      => 'hs_code',
						'hs_code_pattern' => '8541*,8542*',  // Solar/Semiconductors.
						'type'            => 'percentage',
						'rate'            => 50,  // Higher rate effective Sept 2024.
						'priority'        => 35,  // Higher priority for specific codes.
						'stacking_mode'   => 'exclusive',
						'label'           => __( 'China Solar/Semiconductors (50%)', 'customs-fees-for-woocommerce' ),
						'taxable'         => true,
						'tax_class'       => '',
					),
					// Electric Vehicles - 100%
					array(
						'from_country'    => 'CN',
						'to_country'      => 'US',
						'match_type'      => 'hs_code',
						'hs_code_pattern' => '8703.80*',  // Electric vehicles.
						'type'            => 'percentage',
						'rate'            => 100,  // 100% tariff on EVs.
						'priority'        => 40,  // Highest priority.
						'stacking_mode'   => 'exclusive',
						'label'           => __( 'China Electric Vehicles (100%)', 'customs-fees-for-woocommerce' ),
						'taxable'         => true,
						'tax_class'       => '',
					),
					// Auto Parts - 25%
					array(
						'from_country'    => 'CN',
						'to_country'      => 'US',
						'match_type'      => 'hs_code',
						'hs_code_pattern' => '8708*',  // Auto parts.
						'type'            => 'percentage',
						'rate'            => 25,  // Auto parts rate.
						'priority'        => 25,
						'stacking_mode'   => 'exclusive',
						'label'           => __( 'China Auto Parts (25%)', 'customs-fees-for-woocommerce' ),
						'taxable'         => true,
						'tax_class'       => '',
					),
					// Steel Products - 25% to 50% (Section 232)
					array(
						'from_country'    => 'CN',
						'to_country'      => 'US',
						'match_type'      => 'hs_code',
						'hs_code_pattern' => '72*,73*',  // Steel & Iron Products.
						'type'            => 'percentage',
						'rate'            => 25,  // Section 232 steel tariffs.
						'priority'        => 25,
						'stacking_mode'   => 'exclusive',
						'label'           => __( 'China Steel (Section 232)', 'customs-fees-for-woocommerce' ),
						'taxable'         => true,
						'tax_class'       => '',
					),
					// Aluminum Products - 25% to 50% (Section 232)
					array(
						'from_country'    => 'CN',
						'to_country'      => 'US',
						'match_type'      => 'hs_code',
						'hs_code_pattern' => '76*',  // Aluminum Products.
						'type'            => 'percentage',
						'rate'            => 25,  // Section 232 aluminum tariffs.
						'priority'        => 25,
						'stacking_mode'   => 'exclusive',
						'label'           => __( 'China Aluminum (Section 232)', 'customs-fees-for-woocommerce' ),
						'taxable'         => true,
						'tax_class'       => '',
					),
					// Batteries (Non-lithium and Lithium) - 25%
					array(
						'from_country'    => 'CN',
						'to_country'      => 'US',
						'match_type'      => 'hs_code',
						'hs_code_pattern' => '8506*,8507*',  // Batteries.
						'type'            => 'percentage',
						'rate'            => 25,  // Section 301 increase Sept 27, 2024.
						'priority'        => 25,
						'stacking_mode'   => 'exclusive',
						'label'           => __( 'China Batteries (25%)', 'customs-fees-for-woocommerce' ),
						'taxable'         => true,
						'tax_class'       => '',
					),
					// Medical Devices - Syringes/Needles 50%, Medical Gloves 25%
					array(
						'from_country'    => 'CN',
						'to_country'      => 'US',
						'match_type'      => 'hs_code',
						'hs_code_pattern' => '9018.31*,9018.32*',  // Syringes and needles.
						'type'            => 'percentage',
						'rate'            => 50,  // 50% on syringes/needles from 2026.
						'priority'        => 30,
						'stacking_mode'   => 'exclusive',
						'label'           => __( 'China Medical Syringes/Needles (50%)', 'customs-fees-for-woocommerce' ),
						'taxable'         => true,
						'tax_class'       => '',
					),
					// Consumer Appliances - 25% to 50%
					array(
						'from_country'    => 'CN',
						'to_country'      => 'US',
						'match_type'      => 'hs_code',
						'hs_code_pattern' => '8418*,8419*,8450*,8451*',  // Refrigerators, machinery, washers, dryers.
						'type'            => 'percentage',
						'rate'            => 25,  // Section 232 steel derivative items.
						'priority'        => 25,
						'stacking_mode'   => 'exclusive',
						'label'           => __( 'China Consumer Appliances', 'customs-fees-for-woocommerce' ),
						'taxable'         => true,
						'tax_class'       => '',
					),
					// Footwear - 7.5% to 15%
					array(
						'from_country'    => 'CN',
						'to_country'      => 'US',
						'match_type'      => 'hs_code',
						'hs_code_pattern' => '64*',  // Footwear.
						'type'            => 'percentage',
						'rate'            => 7.5,  // Section 301 List 4A tariffs.
						'priority'        => 20,
						'stacking_mode'   => 'exclusive',
						'label'           => __( 'China Footwear (Section 301)', 'customs-fees-for-woocommerce' ),
						'taxable'         => true,
						'tax_class'       => '',
					),
					// Leather goods and bags - 25%
					array(
						'from_country'    => 'CN',
						'to_country'      => 'US',
						'match_type'      => 'hs_code',
						'hs_code_pattern' => '4202*',  // Trunks, bags, cases, wallets, etc.
						'type'            => 'percentage',
						'rate'            => 25,
						'priority'        => 25,
						'stacking_mode'   => 'exclusive',
						'label'           => __( 'China Leather Goods & Bags', 'customs-fees-for-woocommerce' ),
						'taxable'         => true,
						'tax_class'       => '',
					),
					// Toys and Games - 7.5% to 25%
					array(
						'from_country'    => 'CN',
						'to_country'      => 'US',
						'match_type'      => 'hs_code',
						'hs_code_pattern' => '95*',  // Toys and games.
						'type'            => 'percentage',
						'rate'            => 7.5,  // Varies by specific product.
						'priority'        => 20,
						'stacking_mode'   => 'exclusive',
						'label'           => __( 'China Toys & Games', 'customs-fees-for-woocommerce' ),
						'taxable'         => true,
						'tax_class'       => '',
					),
					// Chemical Products - 25%
					array(
						'from_country'    => 'CN',
						'to_country'      => 'US',
						'match_type'      => 'hs_code',
						'hs_code_pattern' => '28*,29*,38*',  // Various chemicals.
						'type'            => 'percentage',
						'rate'            => 25,  // Section 301 chemicals.
						'priority'        => 25,
						'stacking_mode'   => 'exclusive',
						'label'           => __( 'China Chemical Products (25%)', 'customs-fees-for-woocommerce' ),
						'taxable'         => true,
						'tax_class'       => '',
					),
					// General baseline (MFN) - ~7% (fallback for unspecified products)
					array(
						'from_country'  => 'CN',
						'to_country'    => 'US',
						'match_type'    => 'all',  // Fallback for other products.
						'type'          => 'percentage',
						'rate'          => 7,  // WTO baseline tariff.
						'priority'      => 5,  // Lowest priority (fallback).
						'stacking_mode' => 'exclusive',  // Prevents stacking confusion.
						'label'         => __( 'China General Import (MFN)', 'customs-fees-for-woocommerce' ),
						'taxable'       => true,
						'tax_class'     => '',
					),
				),
			),

			// US Reciprocal Tariffs - Major Trading Partners
			'us_india_tariff'          => array(
				'name'        => __( 'India to US (25%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from India - 25%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'from_country' => 'IN',

						'to_country'   => 'US',
						'type'         => 'percentage',
						'rate'         => 25,
						'amount'       => 0,
						'label'        => __( 'US Tariff (India)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
				),
			),

			// EU-US Trade Agreement (August 2025) - All 27 EU Member States
			'eu_all_to_us'             => array(
				'name'        => __( 'EU to US Import Tariffs (2025 Agreement)', 'customs-fees-for-woocommerce' ),
				'description' => __( '⚠️ COMPLETE EU PRESET: 15% tariff on all 27 EU member states per Aug 2025 trade agreement. Special handling for cars, semiconductors, pharmaceuticals. Aircraft parts and generic pharma exempt.', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					// Germany
					array(
						'from_country'  => 'DE',
						'to_country'    => 'US',
						'match_type'    => 'all',
						'type'          => 'percentage',
						'rate'          => 15,
						'priority'      => 10,
						'stacking_mode' => 'exclusive',
						'label'         => __( 'US-EU Tariff (Germany)', 'customs-fees-for-woocommerce' ),
						'taxable'       => true,
					),
					// France
					array(
						'from_country'  => 'FR',
						'to_country'    => 'US',
						'match_type'    => 'all',
						'type'          => 'percentage',
						'rate'          => 15,
						'priority'      => 10,
						'stacking_mode' => 'exclusive',
						'label'         => __( 'US-EU Tariff (France)', 'customs-fees-for-woocommerce' ),
						'taxable'       => true,
					),
					// Italy
					array(
						'from_country'  => 'IT',
						'to_country'    => 'US',
						'match_type'    => 'all',
						'type'          => 'percentage',
						'rate'          => 15,
						'priority'      => 10,
						'stacking_mode' => 'exclusive',
						'label'         => __( 'US-EU Tariff (Italy)', 'customs-fees-for-woocommerce' ),
						'taxable'       => true,
					),
					// Spain
					array(
						'from_country'  => 'ES',
						'to_country'    => 'US',
						'match_type'    => 'all',
						'type'          => 'percentage',
						'rate'          => 15,
						'priority'      => 10,
						'stacking_mode' => 'exclusive',
						'label'         => __( 'US-EU Tariff (Spain)', 'customs-fees-for-woocommerce' ),
						'taxable'       => true,
					),
					// Netherlands
					array(
						'from_country'  => 'NL',
						'to_country'    => 'US',
						'match_type'    => 'all',
						'type'          => 'percentage',
						'rate'          => 15,
						'priority'      => 10,
						'stacking_mode' => 'exclusive',
						'label'         => __( 'US-EU Tariff (Netherlands)', 'customs-fees-for-woocommerce' ),
						'taxable'       => true,
					),
					// Belgium
					array(
						'from_country'  => 'BE',
						'to_country'    => 'US',
						'match_type'    => 'all',
						'type'          => 'percentage',
						'rate'          => 15,
						'priority'      => 10,
						'stacking_mode' => 'exclusive',
						'label'         => __( 'US-EU Tariff (Belgium)', 'customs-fees-for-woocommerce' ),
						'taxable'       => true,
					),
					// Poland
					array(
						'from_country'  => 'PL',
						'to_country'    => 'US',
						'match_type'    => 'all',
						'type'          => 'percentage',
						'rate'          => 15,
						'priority'      => 10,
						'stacking_mode' => 'exclusive',
						'label'         => __( 'US-EU Tariff (Poland)', 'customs-fees-for-woocommerce' ),
						'taxable'       => true,
					),
					// Austria
					array(
						'from_country'  => 'AT',
						'to_country'    => 'US',
						'match_type'    => 'all',
						'type'          => 'percentage',
						'rate'          => 15,
						'priority'      => 10,
						'stacking_mode' => 'exclusive',
						'label'         => __( 'US-EU Tariff (Austria)', 'customs-fees-for-woocommerce' ),
						'taxable'       => true,
					),
					// Sweden
					array(
						'from_country'  => 'SE',
						'to_country'    => 'US',
						'match_type'    => 'all',
						'type'          => 'percentage',
						'rate'          => 15,
						'priority'      => 10,
						'stacking_mode' => 'exclusive',
						'label'         => __( 'US-EU Tariff (Sweden)', 'customs-fees-for-woocommerce' ),
						'taxable'       => true,
					),
					// Denmark
					array(
						'from_country'  => 'DK',
						'to_country'    => 'US',
						'match_type'    => 'all',
						'type'          => 'percentage',
						'rate'          => 15,
						'priority'      => 10,
						'stacking_mode' => 'exclusive',
						'label'         => __( 'US-EU Tariff (Denmark)', 'customs-fees-for-woocommerce' ),
						'taxable'       => true,
					),
					// Finland
					array(
						'from_country'  => 'FI',
						'to_country'    => 'US',
						'match_type'    => 'all',
						'type'          => 'percentage',
						'rate'          => 15,
						'priority'      => 10,
						'stacking_mode' => 'exclusive',
						'label'         => __( 'US-EU Tariff (Finland)', 'customs-fees-for-woocommerce' ),
						'taxable'       => true,
					),
					// Ireland
					array(
						'from_country'  => 'IE',
						'to_country'    => 'US',
						'match_type'    => 'all',
						'type'          => 'percentage',
						'rate'          => 15,
						'priority'      => 10,
						'stacking_mode' => 'exclusive',
						'label'         => __( 'US-EU Tariff (Ireland)', 'customs-fees-for-woocommerce' ),
						'taxable'       => true,
					),
					// Portugal
					array(
						'from_country'  => 'PT',
						'to_country'    => 'US',
						'match_type'    => 'all',
						'type'          => 'percentage',
						'rate'          => 15,
						'priority'      => 10,
						'stacking_mode' => 'exclusive',
						'label'         => __( 'US-EU Tariff (Portugal)', 'customs-fees-for-woocommerce' ),
						'taxable'       => true,
					),
					// Greece
					array(
						'from_country'  => 'GR',
						'to_country'    => 'US',
						'match_type'    => 'all',
						'type'          => 'percentage',
						'rate'          => 15,
						'priority'      => 10,
						'stacking_mode' => 'exclusive',
						'label'         => __( 'US-EU Tariff (Greece)', 'customs-fees-for-woocommerce' ),
						'taxable'       => true,
					),
					// Czech Republic
					array(
						'from_country'  => 'CZ',
						'to_country'    => 'US',
						'match_type'    => 'all',
						'type'          => 'percentage',
						'rate'          => 15,
						'priority'      => 10,
						'stacking_mode' => 'exclusive',
						'label'         => __( 'US-EU Tariff (Czech Rep.)', 'customs-fees-for-woocommerce' ),
						'taxable'       => true,
					),
					// Hungary
					array(
						'from_country'  => 'HU',
						'to_country'    => 'US',
						'match_type'    => 'all',
						'type'          => 'percentage',
						'rate'          => 15,
						'priority'      => 10,
						'stacking_mode' => 'exclusive',
						'label'         => __( 'US-EU Tariff (Hungary)', 'customs-fees-for-woocommerce' ),
						'taxable'       => true,
					),
					// Romania
					array(
						'from_country'  => 'RO',
						'to_country'    => 'US',
						'match_type'    => 'all',
						'type'          => 'percentage',
						'rate'          => 15,
						'priority'      => 10,
						'stacking_mode' => 'exclusive',
						'label'         => __( 'US-EU Tariff (Romania)', 'customs-fees-for-woocommerce' ),
						'taxable'       => true,
					),
					// Bulgaria
					array(
						'from_country'  => 'BG',
						'to_country'    => 'US',
						'match_type'    => 'all',
						'type'          => 'percentage',
						'rate'          => 15,
						'priority'      => 10,
						'stacking_mode' => 'exclusive',
						'label'         => __( 'US-EU Tariff (Bulgaria)', 'customs-fees-for-woocommerce' ),
						'taxable'       => true,
					),
					// Slovakia
					array(
						'from_country'  => 'SK',
						'to_country'    => 'US',
						'match_type'    => 'all',
						'type'          => 'percentage',
						'rate'          => 15,
						'priority'      => 10,
						'stacking_mode' => 'exclusive',
						'label'         => __( 'US-EU Tariff (Slovakia)', 'customs-fees-for-woocommerce' ),
						'taxable'       => true,
					),
					// Croatia
					array(
						'from_country'  => 'HR',
						'to_country'    => 'US',
						'match_type'    => 'all',
						'type'          => 'percentage',
						'rate'          => 15,
						'priority'      => 10,
						'stacking_mode' => 'exclusive',
						'label'         => __( 'US-EU Tariff (Croatia)', 'customs-fees-for-woocommerce' ),
						'taxable'       => true,
					),
					// Slovenia
					array(
						'from_country'  => 'SI',
						'to_country'    => 'US',
						'match_type'    => 'all',
						'type'          => 'percentage',
						'rate'          => 15,
						'priority'      => 10,
						'stacking_mode' => 'exclusive',
						'label'         => __( 'US-EU Tariff (Slovenia)', 'customs-fees-for-woocommerce' ),
						'taxable'       => true,
					),
					// Lithuania
					array(
						'from_country'  => 'LT',
						'to_country'    => 'US',
						'match_type'    => 'all',
						'type'          => 'percentage',
						'rate'          => 15,
						'priority'      => 10,
						'stacking_mode' => 'exclusive',
						'label'         => __( 'US-EU Tariff (Lithuania)', 'customs-fees-for-woocommerce' ),
						'taxable'       => true,
					),
					// Latvia
					array(
						'from_country'  => 'LV',
						'to_country'    => 'US',
						'match_type'    => 'all',
						'type'          => 'percentage',
						'rate'          => 15,
						'priority'      => 10,
						'stacking_mode' => 'exclusive',
						'label'         => __( 'US-EU Tariff (Latvia)', 'customs-fees-for-woocommerce' ),
						'taxable'       => true,
					),
					// Estonia
					array(
						'from_country'  => 'EE',
						'to_country'    => 'US',
						'match_type'    => 'all',
						'type'          => 'percentage',
						'rate'          => 15,
						'priority'      => 10,
						'stacking_mode' => 'exclusive',
						'label'         => __( 'US-EU Tariff (Estonia)', 'customs-fees-for-woocommerce' ),
						'taxable'       => true,
					),
					// Luxembourg
					array(
						'from_country'  => 'LU',
						'to_country'    => 'US',
						'match_type'    => 'all',
						'type'          => 'percentage',
						'rate'          => 15,
						'priority'      => 10,
						'stacking_mode' => 'exclusive',
						'label'         => __( 'US-EU Tariff (Luxembourg)', 'customs-fees-for-woocommerce' ),
						'taxable'       => true,
					),
					// Cyprus
					array(
						'from_country'  => 'CY',
						'to_country'    => 'US',
						'match_type'    => 'all',
						'type'          => 'percentage',
						'rate'          => 15,
						'priority'      => 10,
						'stacking_mode' => 'exclusive',
						'label'         => __( 'US-EU Tariff (Cyprus)', 'customs-fees-for-woocommerce' ),
						'taxable'       => true,
					),
					// Malta
					array(
						'from_country'  => 'MT',
						'to_country'    => 'US',
						'match_type'    => 'all',
						'type'          => 'percentage',
						'rate'          => 15,
						'priority'      => 10,
						'stacking_mode' => 'exclusive',
						'label'         => __( 'US-EU Tariff (Malta)', 'customs-fees-for-woocommerce' ),
						'taxable'       => true,
					),
				),
			),

			'us_uk_tariff'             => array(
				'name'        => __( 'UK to US (10%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from United Kingdom - 10%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'from_country' => 'GB',

						'to_country'   => 'US',
						'type'         => 'percentage',
						'rate'         => 10,
						'amount'       => 0,
						'label'        => __( 'US Tariff (UK)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
				),
			),
			'us_japan_tariff'          => array(
				'name'        => __( 'Japan to US (15%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from Japan - 15%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'from_country' => 'JP',

						'to_country'   => 'US',
						'type'         => 'percentage',
						'rate'         => 15,
						'amount'       => 0,
						'label'        => __( 'US Tariff (Japan)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
				),
			),
			'us_brazil_tariff'         => array(
				'name'        => __( 'Brazil to US (10%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from Brazil - 10%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'from_country' => 'BR',

						'to_country'   => 'US',
						'type'         => 'percentage',
						'rate'         => 10,
						'amount'       => 0,
						'label'        => __( 'US Tariff (Brazil)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
				),
			),
			'us_switzerland_tariff'    => array(
				'name'        => __( 'Switzerland to US (39%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from Switzerland - 39%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'from_country' => 'CH',

						'to_country'   => 'US',
						'type'         => 'percentage',
						'rate'         => 39,
						'amount'       => 0,
						'label'        => __( 'US Tariff (Switzerland)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
				),
			),

			// Southeast Asian Countries
			'us_vietnam_tariff'        => array(
				'name'        => __( 'Vietnam to US (20%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from Vietnam - 20%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'from_country' => 'VN',

						'to_country'   => 'US',
						'type'         => 'percentage',
						'rate'         => 20,
						'amount'       => 0,
						'label'        => __( 'US Tariff (Vietnam)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
				),
			),
			'us_thailand_tariff'       => array(
				'name'        => __( 'Thailand to US (19%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from Thailand - 19%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'from_country' => 'TH',

						'to_country'   => 'US',
						'type'         => 'percentage',
						'rate'         => 19,
						'amount'       => 0,
						'label'        => __( 'US Tariff (Thailand)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
				),
			),
			'us_indonesia_tariff'      => array(
				'name'        => __( 'Indonesia to US (19%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from Indonesia - 19%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'from_country' => 'ID',

						'to_country'   => 'US',
						'type'         => 'percentage',
						'rate'         => 19,
						'amount'       => 0,
						'label'        => __( 'US Tariff (Indonesia)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
				),
			),

			// South Asian Countries
			'us_bangladesh_tariff'     => array(
				'name'        => __( 'Bangladesh to US (20%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from Bangladesh - 20%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'from_country' => 'BD',

						'to_country'   => 'US',
						'type'         => 'percentage',
						'rate'         => 20,
						'amount'       => 0,
						'label'        => __( 'US Tariff (Bangladesh)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
				),
			),
			'us_pakistan_tariff'       => array(
				'name'        => __( 'Pakistan to US (19%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from Pakistan - 19%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'from_country' => 'PK',

						'to_country'   => 'US',
						'type'         => 'percentage',
						'rate'         => 19,
						'amount'       => 0,
						'label'        => __( 'US Tariff (Pakistan)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
				),
			),

			// East Asian Countries
			'us_south_korea_tariff'    => array(
				'name'        => __( 'South Korea to US (15%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from South Korea - 15%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'from_country' => 'KR',

						'to_country'   => 'US',
						'type'         => 'percentage',
						'rate'         => 15,
						'amount'       => 0,
						'label'        => __( 'US Tariff (South Korea)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
				),
			),
			'us_taiwan_tariff'         => array(
				'name'        => __( 'Taiwan to US (20%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from Taiwan - 20%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'from_country' => 'TW',

						'to_country'   => 'US',
						'type'         => 'percentage',
						'rate'         => 20,
						'amount'       => 0,
						'label'        => __( 'US Tariff (Taiwan)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
				),
			),

			// Middle Eastern Countries
			'us_turkey_tariff'         => array(
				'name'        => __( 'Turkey to US (15%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from Turkey - 15%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'from_country' => 'TR',

						'to_country'   => 'US',
						'type'         => 'percentage',
						'rate'         => 15,
						'amount'       => 0,
						'label'        => __( 'US Tariff (Turkey)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
				),
			),
			'us_israel_tariff'         => array(
				'name'        => __( 'Israel to US (15%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from Israel - 15%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'from_country' => 'IL',

						'to_country'   => 'US',
						'type'         => 'percentage',
						'rate'         => 15,
						'amount'       => 0,
						'label'        => __( 'US Tariff (Israel)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
				),
			),

			// African Countries
			'us_south_africa_tariff'   => array(
				'name'        => __( 'South Africa to US (30%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from South Africa - 30%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'from_country' => 'ZA',

						'to_country'   => 'US',
						'type'         => 'percentage',
						'rate'         => 30,
						'amount'       => 0,
						'label'        => __( 'US Tariff (South Africa)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
				),
			),
			'us_nigeria_tariff'        => array(
				'name'        => __( 'Nigeria to US (15%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from Nigeria - 15%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'from_country' => 'NG',

						'to_country'   => 'US',
						'type'         => 'percentage',
						'rate'         => 15,
						'amount'       => 0,
						'label'        => __( 'US Tariff (Nigeria)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
				),
			),

			// Pacific Countries
			'us_new_zealand_tariff'    => array(
				'name'        => __( 'New Zealand to US (15%)', 'customs-fees-for-woocommerce' ),
				'description' => __( 'US reciprocal tariff for goods from New Zealand - 15%', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'from_country' => 'NZ',

						'to_country'   => 'US',
						'type'         => 'percentage',
						'rate'         => 15,
						'amount'       => 0,
						'label'        => __( 'US Tariff (New Zealand)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
				),
			),

			'uk_vat'                   => array(
				'name'        => __( 'UK VAT & Duty', 'customs-fees-for-woocommerce' ),
				'description' => __( 'UK import VAT (20%) and duty for international shipments', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'        => 'GB',
						'origin_country' => '',  // Applies to all origins
						'type'           => 'percentage',
						'rate'           => 20,
						'amount'         => 0,
						'label'          => __( 'UK VAT (Import)', 'customs-fees-for-woocommerce' ),
						'taxable'        => false,
						'tax_class'      => '',
					),
					array(
						'country'        => 'GB',
						'origin_country' => '',  // Applies to all origins
						'type'           => 'percentage',
						'rate'           => 5,
						'amount'         => 0,
						'label'          => __( 'UK Duty (Import)', 'customs-fees-for-woocommerce' ),
						'taxable'        => false,
						'tax_class'      => '',
					),
				),
			),
			'canada_gst'               => array(
				'name'        => __( 'Canada GST & Duty', 'customs-fees-for-woocommerce' ),
				'description' => __( 'Canadian GST and import duties', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'        => 'CA',
						'origin_country' => '',  // Applies to all origins
						'type'           => 'percentage',
						'rate'           => 5,
						'amount'         => 0,
						'label'          => __( 'Canada GST (Import)', 'customs-fees-for-woocommerce' ),
						'taxable'        => false,
						'tax_class'      => '',
					),
					array(
						'country'        => 'CA',
						'origin_country' => '',  // Applies to all origins
						'type'           => 'percentage',
						'rate'           => 8,
						'amount'         => 0,
						'label'          => __( 'Canada Duty (Import)', 'customs-fees-for-woocommerce' ),
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
						'from_country' => 'CH',

						'to_country'   => 'US',  // Switzerland - 39%
						'type'         => 'percentage',
						'rate'         => 39,
						'amount'       => 0,
						'label'        => __( 'US Tariff (Switzerland)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
					array(
						'from_country' => 'SY',

						'to_country'   => 'US',  // Syria - 41%
						'type'         => 'percentage',
						'rate'         => 41,
						'amount'       => 0,
						'label'        => __( 'US Tariff (Syria)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
					array(
						'from_country' => 'MM',

						'to_country'   => 'US',  // Myanmar - 40%
						'type'         => 'percentage',
						'rate'         => 40,
						'amount'       => 0,
						'label'        => __( 'US Tariff (Myanmar)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
					array(
						'from_country' => 'LA',

						'to_country'   => 'US',  // Laos - 40%
						'type'         => 'percentage',
						'rate'         => 40,
						'amount'       => 0,
						'label'        => __( 'US Tariff (Laos)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
					array(
						'from_country' => 'IQ',

						'to_country'   => 'US',  // Iraq - 35%
						'type'         => 'percentage',
						'rate'         => 35,
						'amount'       => 0,
						'label'        => __( 'US Tariff (Iraq)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
					array(
						'from_country' => 'RS',

						'to_country'   => 'US',  // Serbia - 35%
						'type'         => 'percentage',
						'rate'         => 35,
						'amount'       => 0,
						'label'        => __( 'US Tariff (Serbia)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
					array(
						'from_country' => 'DZ',

						'to_country'   => 'US',  // Algeria - 30%
						'type'         => 'percentage',
						'rate'         => 30,
						'amount'       => 0,
						'label'        => __( 'US Tariff (Algeria)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
					array(
						'from_country' => 'BA',

						'to_country'   => 'US',  // Bosnia and Herzegovina - 30%
						'type'         => 'percentage',
						'rate'         => 30,
						'amount'       => 0,
						'label'        => __( 'US Tariff (Bosnia)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
					array(
						'from_country' => 'LY',

						'to_country'   => 'US',  // Libya - 30%
						'type'         => 'percentage',
						'rate'         => 30,
						'amount'       => 0,
						'label'        => __( 'US Tariff (Libya)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
				),
			),

			'us_southeast_asia_all'    => array(
				'name'        => __( 'US-Southeast Asia All Countries', 'customs-fees-for-woocommerce' ),
				'description' => __( 'All Southeast Asian countries with US reciprocal tariffs', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'from_country' => 'VN',

						'to_country'   => 'US',  // Vietnam - 20%
						'type'         => 'percentage',
						'rate'         => 20,
						'amount'       => 0,
						'label'        => __( 'US Tariff (Vietnam)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
					array(
						'from_country' => 'TH',

						'to_country'   => 'US',  // Thailand - 19%
						'type'         => 'percentage',
						'rate'         => 19,
						'amount'       => 0,
						'label'        => __( 'US Tariff (Thailand)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
					array(
						'from_country' => 'MY',

						'to_country'   => 'US',  // Malaysia - 19%
						'type'         => 'percentage',
						'rate'         => 19,
						'amount'       => 0,
						'label'        => __( 'US Tariff (Malaysia)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
					array(
						'from_country' => 'ID',

						'to_country'   => 'US',  // Indonesia - 19%
						'type'         => 'percentage',
						'rate'         => 19,
						'amount'       => 0,
						'label'        => __( 'US Tariff (Indonesia)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
					array(
						'from_country' => 'PH',

						'to_country'   => 'US',  // Philippines - 19%
						'type'         => 'percentage',
						'rate'         => 19,
						'amount'       => 0,
						'label'        => __( 'US Tariff (Philippines)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
					array(
						'from_country' => 'KH',

						'to_country'   => 'US',  // Cambodia - 19%
						'type'         => 'percentage',
						'rate'         => 19,
						'amount'       => 0,
						'label'        => __( 'US Tariff (Cambodia)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
					array(
						'from_country' => 'BN',

						'to_country'   => 'US',  // Brunei - 25%
						'type'         => 'percentage',
						'rate'         => 25,
						'amount'       => 0,
						'label'        => __( 'US Tariff (Brunei)', 'customs-fees-for-woocommerce' ),
						'taxable'      => true,
						'tax_class'    => '',
					),
				),
			),

			'australia_gst'            => array(
				'name'        => __( 'Australia GST', 'customs-fees-for-woocommerce' ),
				'description' => __( 'Australian GST (10%) on imported goods', 'customs-fees-for-woocommerce' ),
				'rules'       => array(
					array(
						'country'        => 'AU',
						'origin_country' => '',  // Applies to all origins
						'type'           => 'percentage',
						'rate'           => 10,
						'amount'         => 0,
						'label'          => __( 'Australia GST (Import)', 'customs-fees-for-woocommerce' ),
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

		$existing_rules  = get_option( 'cfwc_rules', array() );
		$added_count     = 0;
		$duplicate_count = 0;

		if ( $append ) {
			// Check for duplicates when appending
			$rules = $existing_rules;

			foreach ( $template['rules'] as $new_rule ) {
				// Convert old format to new format if needed.
				if ( ! isset( $new_rule['from_country'] ) && isset( $new_rule['origin_country'] ) ) {
					$new_rule['from_country'] = $new_rule['origin_country'];
				}
				if ( ! isset( $new_rule['to_country'] ) && isset( $new_rule['country'] ) ) {
					$new_rule['to_country'] = $new_rule['country'];
				}

				// Check if rule already exists (same country pair + label combination).
				$is_duplicate = false;
				foreach ( $existing_rules as $existing_rule ) {
					$existing_from       = $existing_rule['from_country'] ?? $existing_rule['origin_country'] ?? $existing_rule['country'] ?? '';
					$existing_to         = $existing_rule['to_country'] ?? $existing_rule['country'] ?? '';
					$new_from            = $new_rule['from_country'] ?? $new_rule['origin_country'] ?? $new_rule['country'] ?? '';
					$new_to              = $new_rule['to_country'] ?? $new_rule['country'] ?? '';
					$existing_match_type = $existing_rule['match_type'] ?? 'all';
					$new_match_type      = $new_rule['match_type'] ?? 'all';

					// Check for exact duplicate
					if ( $existing_from === $new_from &&
						$existing_to === $new_to &&
						$existing_rule['label'] === $new_rule['label'] ) {
						$is_duplicate = true;
						++$duplicate_count;
						break;
					}

					// Check for conflicting general rules (both applying to all products from all origins)
					if ( empty( $existing_from ) && empty( $new_from ) &&
						$new_to === $existing_to &&
						'all' === $existing_match_type && 'all' === $new_match_type ) {
						// Two general rules for the same destination - skip the new one
						$is_duplicate = true;
						++$duplicate_count;
						break;
					}
				}

				if ( ! $is_duplicate ) {
					$rules[] = $new_rule;
					++$added_count;
				}
			}
		} else {
			// Replace all rules
			$rules       = $template['rules'];
			$added_count = count( $template['rules'] );
		}

		update_option( 'cfwc_rules', $rules );

		// Clear cache.
		$calculator = new CFWC_Calculator();
		$calculator->clear_cache();

		return array(
			'success'     => true,
			'added'       => $added_count,
			'duplicates'  => $duplicate_count,
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

		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- manage_woocommerce is a standard WooCommerce capability.
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
				} elseif ( $result['duplicates'] > 0 && 0 === $result['added'] ) {
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

			wp_send_json_success(
				array(
					'message'    => $message,
					'rules'      => get_option( 'cfwc_rules', array() ),
					'added'      => $result['added'],
					'duplicates' => $result['duplicates'],
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => __( 'Failed to apply preset.', 'customs-fees-for-woocommerce' ),
				)
			);
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

		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- manage_woocommerce is a standard WooCommerce capability.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( -1 );
		}

		wp_send_json_success( $this->templates );
	}
}
