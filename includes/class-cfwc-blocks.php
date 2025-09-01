<?php
/**
 * WooCommerce Blocks Compatibility
 *
 * Ensures customs fees work with WooCommerce Blocks checkout.
 *
 * @package CustomsFeesForWooCommerce
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce Blocks Compatibility Class
 *
 * This class ensures that customs fees (calculated via WC()->cart->add_fee())
 * work properly with WooCommerce Blocks. HS codes and tooltips are not
 * displayed in blocks due to platform limitations.
 *
 * @since 1.0.0
 */
class CFWC_Blocks {

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize blocks compatibility
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		// Save order metadata when using blocks checkout.
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'update_order_from_request' ), 10, 2 );
	}

	/**
	 * Update order from Store API request
	 *
	 * This ensures that customs fees and product metadata are properly saved
	 * when an order is placed through the blocks checkout.
	 *
	 * @since 1.0.0
	 * @param WC_Order        $order   The order object.
	 * @param WP_REST_Request $request The request object.
	 * @return void
	 */
	public function update_order_from_request( $order, $request ) {
		// Get customs fees from session.
		$customs_fees = WC()->session->get( 'cfwc_customs_fees', array() );

		if ( ! empty( $customs_fees ) ) {
			// Store customs fee metadata in order.
			$order->update_meta_data( '_cfwc_total_customs_fees', array_sum( $customs_fees ) );
			$order->update_meta_data( '_cfwc_customs_fees_breakdown', $customs_fees );

			// Store HS codes and origin for each item (for order details/emails).
			foreach ( $order->get_items() as $item ) {
				$product = $item->get_product();
				if ( $product ) {
					$hs_code = get_post_meta( $product->get_id(), '_cfwc_hs_code', true );
					$origin  = get_post_meta( $product->get_id(), '_cfwc_country_of_origin', true );

					if ( $hs_code ) {
						$item->update_meta_data( '_cfwc_hs_code', $hs_code );
					}
					if ( $origin ) {
						$item->update_meta_data( '_cfwc_country_of_origin', $origin );
					}
				}
			}

			$order->save();
		}
	}
}
