<?php
/**
 * Product HS Code functionality.
 *
 * @package CustomsFeesForWooCommerce
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product HS Code handler class.
 *
 * @since 1.0.0
 */
class CFWC_Products {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// REMOVED: Field additions - now handled by CFWC_Admin class in inventory tab.
		// Fields are saved by CFWC_Admin to avoid duplication.

		// Display in cart.
		add_filter( 'woocommerce_cart_item_name', array( $this, 'display_hs_code_in_cart' ), 10, 3 );

		// Display in checkout.
		add_filter( 'woocommerce_checkout_cart_item_quantity', array( $this, 'display_hs_code_in_checkout' ), 10, 3 );
	}



	/**
	 * Display HS Code in cart.
	 *
	 * @since 1.0.0
	 * @param string $product_name Product name HTML.
	 * @param array  $cart_item Cart item data.
	 * @param string $_cart_item_key Cart item key.
	 * @return string Modified product name.
	 */
	public function display_hs_code_in_cart( $product_name, $cart_item, $_cart_item_key ) {
		// Unused parameter is required by filter signature.
		unset( $_cart_item_key );
		// Only on cart page, NOT checkout (checkout handled separately by display_hs_code_in_checkout).
		if ( ! is_cart() ) {
			return $product_name;
		}

		// Don't add to blocks cart (handled by JavaScript).
		if ( has_block( 'woocommerce/cart' ) ) {
			return $product_name;
		}

		// Get the actual product (could be variation).
		$product = $cart_item['data'];
		
		// Get HS code and origin using centralized helper for proper variation support.
		if ( class_exists( 'CFWC_Products_Variation_Support' ) ) {
			$customs_data = CFWC_Products_Variation_Support::get_product_customs_data( $product );
			$hs_code = $customs_data['hs_code'];
			$origin = $customs_data['origin'];
		} else {
			// Fallback to direct meta lookup for backward compatibility.
			$product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
			$hs_code    = get_post_meta( $product_id, '_cfwc_hs_code', true );
			$origin     = get_post_meta( $product_id, '_cfwc_country_of_origin', true );
			
			// Check parent if variation doesn't have data.
			if ( empty( $hs_code ) && $cart_item['variation_id'] ) {
				$hs_code = get_post_meta( $cart_item['product_id'], '_cfwc_hs_code', true );
			}
			if ( empty( $origin ) && $cart_item['variation_id'] ) {
				$origin = get_post_meta( $cart_item['product_id'], '_cfwc_country_of_origin', true );
			}
		}

		if ( $hs_code || $origin ) {
			// Use a break tag to ensure it appears on a new line right after product name.
			$customs_info = '<br/><small class="cfwc-cart-customs">';

			if ( $hs_code ) {
				$customs_info .= sprintf(
					'%s: %s',
					__( 'HS Code', 'customs-fees-for-woocommerce' ),
					esc_html( $hs_code )
				);
			}

			if ( $origin ) {
				// Display country code in uppercase.
				$origin_display = strtoupper( substr( $origin, 0, 2 ) );

				if ( $hs_code ) {
					$customs_info .= ' | ';
				}

				$customs_info .= sprintf(
					'%s: %s',
					__( 'Origin', 'customs-fees-for-woocommerce' ),
					esc_html( $origin_display )
				);
			}

			$customs_info .= '</small>';

			$product_name .= $customs_info;
		}

		return $product_name;
	}

	/**
	 * Display HS Code in checkout.
	 *
	 * @since 1.0.0
	 * @param string $quantity_html Quantity HTML.
	 * @param array  $cart_item Cart item data.
	 * @param string $_cart_item_key Cart item key.
	 * @return string Modified quantity HTML.
	 */
	public function display_hs_code_in_checkout( $quantity_html, $cart_item, $_cart_item_key ) {
		// Unused parameter is required by filter signature.
		unset( $_cart_item_key );
		// Only on checkout page.
		if ( ! is_checkout() ) {
			return $quantity_html;
		}

		// Don't add to blocks checkout (handled by JavaScript).
		if ( has_block( 'woocommerce/checkout' ) ) {
			return $quantity_html;
		}

		// Get the actual product (could be variation).
		$product = $cart_item['data'];
		
		// Get HS code and origin using centralized helper for proper variation support.
		if ( class_exists( 'CFWC_Products_Variation_Support' ) ) {
			$customs_data = CFWC_Products_Variation_Support::get_product_customs_data( $product );
			$hs_code = $customs_data['hs_code'];
			$origin = $customs_data['origin'];
		} else {
			// Fallback to direct meta lookup for backward compatibility.
			$product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
			$hs_code    = get_post_meta( $product_id, '_cfwc_hs_code', true );
			$origin     = get_post_meta( $product_id, '_cfwc_country_of_origin', true );
			
			// Check parent if variation doesn't have data.
			if ( empty( $hs_code ) && $cart_item['variation_id'] ) {
				$hs_code = get_post_meta( $cart_item['product_id'], '_cfwc_hs_code', true );
			}
			if ( empty( $origin ) && $cart_item['variation_id'] ) {
				$origin = get_post_meta( $cart_item['product_id'], '_cfwc_country_of_origin', true );
			}
		}

		if ( $hs_code || $origin ) {
			$customs_info = '<div class="cfwc-checkout-customs">';

			if ( $hs_code ) {
				$customs_info .= sprintf( 'HS: %s', esc_html( $hs_code ) );
			}

			if ( $origin ) {
				if ( $hs_code ) {
					$customs_info .= ', ';
				}
				$customs_info .= sprintf( 'Origin: %s', esc_html( strtoupper( $origin ) ) );
			}

			$customs_info .= '</div>';

			$quantity_html = $customs_info . $quantity_html;
		}

		return $quantity_html;
	}
}
