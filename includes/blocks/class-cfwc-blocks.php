<?php
/**
 * WooCommerce Blocks integration.
 *
 * @package CustomsFeesForWooCommerce
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CFWC_Blocks class.
 *
 * Handles integration with WooCommerce Blocks (Store API and checkout blocks).
 *
 * @since 1.0.0
 */
class CFWC_Blocks {

	/**
	 * Initialize blocks integration.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		// Register Store API endpoints.
		add_action( 'rest_api_init', array( $this, 'register_store_api_endpoints' ) );

		// Extend Store API cart/checkout schemas.
		add_filter( 'woocommerce_store_api_cart_schema', array( $this, 'extend_cart_schema' ) );
		add_filter( 'woocommerce_store_api_checkout_schema', array( $this, 'extend_checkout_schema' ) );

		// Add data to Store API responses.
		add_filter( 'woocommerce_store_api_cart_data', array( $this, 'add_cart_data' ) );
		add_filter( 'woocommerce_store_api_checkout_data', array( $this, 'add_checkout_data' ) );

		// Register block scripts.
		add_action( 'wp_enqueue_scripts', array( $this, 'register_block_scripts' ) );

		// Add block support flags.
		add_action( 'woocommerce_blocks_loaded', array( $this, 'register_block_support' ) );
	}

	/**
	 * Register Store API endpoints.
	 *
	 * @since 1.0.0
	 */
	public function register_store_api_endpoints() {
		register_rest_route(
			'cfwc/v1',
			'/calculate-fees',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'calculate_fees_endpoint' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'country' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'total'   => array(
						'required'          => true,
						'type'              => 'number',
						'sanitize_callback' => 'floatval',
					),
				),
			)
		);
	}

	/**
	 * Calculate fees endpoint callback.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function calculate_fees_endpoint( $request ) {
		$country = $request->get_param( 'country' );
		$total   = $request->get_param( 'total' );

		$calculator = new CFWC_Calculator();
		$fees       = $calculator->calculate_fees_for_country( $country, $total );

		return new WP_REST_Response(
			array(
				'fees'  => $fees,
				'total' => array_sum( array_column( $fees, 'amount' ) ),
			),
			200
		);
	}

	/**
	 * Extend cart schema for Store API.
	 *
	 * @since 1.0.0
	 * @param array $schema Cart schema.
	 * @return array Modified schema.
	 */
	public function extend_cart_schema( $schema ) {
		$schema['properties']['customs_fees'] = array(
			'description' => __( 'Customs and import fees', 'customs-fees-for-woocommerce' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'readonly'    => true,
			'properties'  => array(
				'enabled'    => array(
					'description' => __( 'Whether customs fees are enabled', 'customs-fees-for-woocommerce' ),
					'type'        => 'boolean',
				),
				'fees'       => array(
					'description' => __( 'Calculated customs fees', 'customs-fees-for-woocommerce' ),
					'type'        => 'array',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'label'  => array( 'type' => 'string' ),
							'amount' => array( 'type' => 'number' ),
						),
					),
				),
				'total'      => array(
					'description' => __( 'Total customs fees amount', 'customs-fees-for-woocommerce' ),
					'type'        => 'number',
				),
				'disclaimer' => array(
					'description' => __( 'Disclaimer text', 'customs-fees-for-woocommerce' ),
					'type'        => 'string',
				),
			),
		);

		return $schema;
	}

	/**
	 * Extend checkout schema for Store API.
	 *
	 * @since 1.0.0
	 * @param array $schema Checkout schema.
	 * @return array Modified schema.
	 */
	public function extend_checkout_schema( $schema ) {
		// Use same extension as cart.
		return $this->extend_cart_schema( $schema );
	}

	/**
	 * Add data to cart Store API response.
	 *
	 * @since 1.0.0
	 * @param array $cart_data Cart data.
	 * @return array Modified cart data.
	 */
	public function add_cart_data( $cart_data ) {
		if ( ! get_option( 'cfwc_enabled', false ) ) {
			$cart_data['customs_fees'] = array(
				'enabled' => false,
				'fees'    => array(),
				'total'   => 0,
			);
			return $cart_data;
		}

		// Get fees from cart.
		$fees       = WC()->cart->get_fees();
		$fee_data   = array();
		$total_fees = 0;

		foreach ( $fees as $fee ) {
			if ( strpos( $fee->id, 'customs-fee' ) === 0 ) {
				$fee_data[]  = array(
					'label'  => $fee->name,
					'amount' => $fee->amount,
				);
				$total_fees += $fee->amount;
			}
		}

		$cart_data['customs_fees'] = array(
			'enabled'    => true,
			'fees'       => $fee_data,
			'total'      => $total_fees,
			'disclaimer' => cfwc_get_disclaimer_text(),
		);

		return $cart_data;
	}

	/**
	 * Add data to checkout Store API response.
	 *
	 * @since 1.0.0
	 * @param array $checkout_data Checkout data.
	 * @return array Modified checkout data.
	 */
	public function add_checkout_data( $checkout_data ) {
		// Use same data as cart.
		return $this->add_cart_data( $checkout_data );
	}

	/**
	 * Register block scripts.
	 *
	 * @since 1.0.0
	 */
	public function register_block_scripts() {
		if ( ! is_checkout() ) {
			return;
		}

		// For now, add inline script. Will move to separate file later.
		wp_add_inline_script(
			'wc-checkout',
			'
			// Listen for WooCommerce Blocks checkout updates
			if (window.wp && window.wp.data) {
				const { select, subscribe } = wp.data;
				const checkoutStore = select("wc/store/checkout");
				
				if (checkoutStore) {
					subscribe(() => {
						const customsFees = checkoutStore.getCustomsFees ? checkoutStore.getCustomsFees() : null;
						if (customsFees && customsFees.enabled) {
							console.log("Customs fees applied:", customsFees);
						}
					});
				}
			}
		'
		);
	}

	/**
	 * Register block support.
	 *
	 * @since 1.0.0
	 */
	public function register_block_support() {
		// Register support for cart and checkout blocks.
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Package' ) ) {
			add_filter( 'woocommerce_blocks_cart_compatible', '__return_true' );
			add_filter( 'woocommerce_blocks_checkout_compatible', '__return_true' );
		}
	}
}
