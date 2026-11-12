<?php
/**
 * Prepares the store of a WordPress Playground preview of a pull request build.
 *
 * The Blueprint in this folder runs this file once, after WooCommerce and the
 * Customs Fees build are active. The plugin never loads it, and it is not part
 * of the release zip.
 *
 * @package CustomsFeesForWooCommerce
 */

// A Blueprint step runs this file as a bare PHP script, so it loads WordPress itself.
require_once '/wordpress/wp-load.php';

// WooCommerce would otherwise replace the Blueprint's landing page with its setup wizard.
delete_transient( '_wc_activation_redirect' );
update_option( 'woocommerce_onboarding_profile', array( 'skipped' => true ) );

// A store in the United States, so a visitor without an address counts as shipping to the US.
update_option( 'woocommerce_default_country', 'US:CA' );
update_option( 'woocommerce_currency', 'USD' );
update_option( 'woocommerce_store_address', '60 29th Street' );
update_option( 'woocommerce_store_city', 'San Francisco' );
update_option( 'woocommerce_store_postcode', '94110' );
update_option( 'woocommerce_coming_soon', 'no' );
update_option( 'woocommerce_allow_tracking', 'no' );

// The Customs Fees settings are a section of the Tax tab, which WooCommerce only shows with taxes on.
// The store has no tax rates, so no tax is added to the totals.
update_option( 'woocommerce_calc_taxes', 'yes' );

// A WooCommerce installed by a Blueprint starts on legacy order storage, which is not what new stores use.
update_option( 'woocommerce_custom_orders_table_enabled', 'yes' );

WC_Install::create_pages();

// Free shipping everywhere and cash on delivery, so an order can be placed to any country.
$cfwc_preview_zone = new WC_Shipping_Zone( 0 );
if ( ! $cfwc_preview_zone->get_shipping_methods() ) {
	$cfwc_preview_zone->add_shipping_method( 'free_shipping' );
}

$cfwc_preview_gateways = WC()->payment_gateways()->payment_gateways();
if ( isset( $cfwc_preview_gateways['cod'] ) ) {
	$cfwc_preview_gateways['cod']->update_option( 'enabled', 'yes' );
}

// Two rules for goods made in China: 10% when they ship to the United States, 20% to the United Kingdom.
$cfwc_preview_rule = array(
	'country'          => '',
	'origin_country'   => '',
	'type'             => 'percentage',
	'rate'             => 0.0,
	'amount'           => 0.0,
	'label'            => '',
	'taxable'          => true,
	'tax_class'        => '',
	'from_country'     => 'CN',
	'to_country'       => '',
	'match_type'       => 'all',
	'category_ids'     => array(),
	'hs_code_pattern'  => '',
	'priority'         => 0,
	'stacking_mode'    => 'add',
	'rule_id'          => '',
	'valuation_method' => 'inherit',
	'base_includes'    => array(),
);

update_option(
	'cfwc_rules',
	CFWC_Settings::migrate_rules(
		array(
			array_merge(
				$cfwc_preview_rule,
				array(
					'label'      => 'China to US import duty',
					'country'    => 'US',
					'to_country' => 'US',
					'rate'       => 10.0,
				)
			),
			array_merge(
				$cfwc_preview_rule,
				array(
					'label'      => 'China to UK import duty',
					'country'    => 'GB',
					'to_country' => 'GB',
					'rate'       => 20.0,
				)
			),
		)
	)
);
delete_transient( 'cfwc_rules_cache' );

// A physical product made in China. Virtual products never get customs fees.
$cfwc_preview_product = new WC_Product_Simple();
$cfwc_preview_product->set_name( 'Sample imported T-shirt' );
$cfwc_preview_product->set_slug( 'sample-imported-t-shirt' );
$cfwc_preview_product->set_sku( 'cfwc-preview-t-shirt' );
$cfwc_preview_product->set_regular_price( '100' );
$cfwc_preview_product->set_weight( '1' );
$cfwc_preview_product->set_status( 'publish' );
$cfwc_preview_product->update_meta_data( '_cfwc_country_of_origin', 'CN' );
$cfwc_preview_product->update_meta_data( '_cfwc_hs_code', '6109.10' );
$cfwc_preview_product->save();

// The preview visitor is logged in as this user. A US address fills in the checkout form.
$cfwc_preview_customer = new WC_Customer( 1 );
$cfwc_preview_customer->set_props(
	array(
		'billing_first_name' => 'Playground',
		'billing_last_name'  => 'Tester',
		'billing_address_1'  => '60 29th Street',
		'billing_city'       => 'San Francisco',
		'billing_state'      => 'CA',
		'billing_postcode'   => '94110',
		'billing_country'    => 'US',
		'billing_email'      => 'admin@localhost.com',
		'billing_phone'      => '555-0100',
	)
);
$cfwc_preview_customer->save();

// Put the product in that user's cart, so the Cart and Checkout pages show the fee straight away.
wp_set_current_user( 1 );
wc_load_cart();
WC()->cart->add_to_cart( $cfwc_preview_product->get_id() );
