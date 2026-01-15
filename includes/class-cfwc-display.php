<?php
/**
 * Frontend Display Class
 *
 * @package CustomsFeesWooCommerce
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Display class for frontend rendering.
 */
class CFWC_Display {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 */
	public function init_hooks() {
		// Override the default fee display on cart/checkout.
		add_filter( 'woocommerce_cart_totals_fee_html', array( $this, 'customize_fee_display' ), 10, 2 );

				// Hook into order totals display to show breakdown instead of plain total.
		add_filter( 'woocommerce_get_order_item_totals', array( $this, 'add_fees_to_order_totals' ), 20, 3 );

		// Add HS Codes to order item names on order pages (non-email).
		add_filter( 'woocommerce_order_item_name', array( $this, 'add_hs_code_to_order_item_display' ), 10, 3 );

		// Display info box on order received page (NOT the fee itself).
		// Use hook that fires AFTER customer details to avoid being inside styled containers.
		add_action( 'woocommerce_order_details_after_customer_details', array( $this, 'display_order_received_info' ), 10 );

		// Email info box display (NOT the fee itself - fee is in totals).
		add_action( 'woocommerce_email_after_order_table', array( $this, 'display_customs_info_in_email' ), 10, 4 );

		// Save fee breakdown to order when order is created.
		add_action( 'woocommerce_checkout_create_order', array( $this, 'save_fee_breakdown_to_order' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order_fee_item', array( $this, 'save_fee_breakdown_to_fee_item' ), 10, 4 );

		// Enqueue frontend assets for tooltips.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_tooltip_assets' ) );
	}

	/**
	 * Customize fee display on cart/checkout to show breakdown.
	 *
	 * @since 1.0.0
	 * @param string   $cart_totals_fee_html The HTML for the fee amount.
	 * @param stdClass $fee                  The fee object with properties: name, amount, taxable, tax_class.
	 * @return string Modified HTML.
	 */
	public function customize_fee_display( $cart_totals_fee_html, $fee ) {
		// Only customize our customs fees.
		// Check if fee has name property (it's a stdClass from WooCommerce).
		if ( ! isset( $fee->name ) || __( 'Customs & Import Fees', 'customs-fees-for-woocommerce' ) !== $fee->name ) {
			return $cart_totals_fee_html;
		}

		// Get the breakdown from session.
		$breakdown = WC()->session->get( 'cfwc_fees_breakdown', array() );
		if ( empty( $breakdown ) ) {
			return $cart_totals_fee_html;
		}

		// Build the breakdown display.
		$html = '<div id="cfwc_fees_breakdown" class="cfwc-fees-breakdown">';

		foreach ( $breakdown as $fee_item ) {
			$html .= '<div class="cfwc-fee-item">';
			$html .= '<span class="cfwc-fee-label">' . esc_html( $fee_item['label'] ) . '</span>';
			$html .= '<span class="cfwc-fee-amount"><strong>' . wc_price( $fee_item['amount'] ) . '</strong></span>';
			$html .= '</div>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Enqueue frontend assets for tooltips.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_tooltip_assets() {
		// Check if we're on a page with checkout shortcode.
		global $post;
		$has_checkout_shortcode = false;
		if ( is_object( $post ) && has_shortcode( $post->post_content, 'woocommerce_checkout' ) ) {
			$has_checkout_shortcode = true;
		}

		// Load on cart, checkout (including shortcode), order received, and my account pages.
		// Also load if we're on any WooCommerce page.
		if ( is_checkout() || is_cart() || is_order_received_page() || is_account_page() || is_woocommerce() || $has_checkout_shortcode ) {
			wp_enqueue_style( 'cfwc-frontend', plugin_dir_url( __DIR__ ) . 'assets/css/frontend.css', array(), '1.0.0' );
			// Only load JS on cart/checkout for tooltips.
			if ( is_checkout() || is_cart() || $has_checkout_shortcode ) {
				wp_enqueue_script( 'cfwc-frontend', plugin_dir_url( __DIR__ ) . 'assets/js/frontend.js', array( 'jquery' ), '1.0.0', true );
			}
		}
	}

	/**
	 * Save fee breakdown to order.
	 *
	 * @since 1.0.0
	 * @param WC_Order $order Order object.
	 * @param array    $data   Posted data (unused but required by hook).
	 */
	public function save_fee_breakdown_to_order( $order, $data ) {
		// $data is required by the hook signature but not used.
		unset( $data );
		$breakdown = WC()->session->get( 'cfwc_fees_breakdown', array() );
		if ( ! empty( $breakdown ) ) {
			$order->update_meta_data( '_cfwc_fees_breakdown', $breakdown );
		}
	}

	/**
	 * Save fee breakdown to fee item meta.
	 *
	 * @since 1.0.0
	 * @param WC_Order_Item_Fee $item    Fee item object.
	 * @param int               $fee_key Fee key.
	 * @param object            $fee     Fee object.
	 * @param WC_Order          $order   Order object.
	 */
	public function save_fee_breakdown_to_fee_item( $item, $fee_key, $fee, $order ) {
		// Only process our customs fees.
		if ( __( 'Customs & Import Fees', 'customs-fees-for-woocommerce' ) !== $fee->name ) {
			return;
		}

		$breakdown = WC()->session->get( 'cfwc_fees_breakdown', array() );
		if ( ! empty( $breakdown ) ) {
			// Save breakdown to the fee item itself as well.
			$item->add_meta_data( '_cfwc_breakdown', $breakdown );
			// Also ensure it's on the order.
			$order->update_meta_data( '_cfwc_fees_breakdown', $breakdown );
		}
	}

	/**
	 * Add fees to order totals display with breakdown.
	 *
	 * @since 1.0.0
	 * @param array    $totals Order totals.
	 * @param WC_Order $order  Order object.
	 * @param bool     $tax_display Whether to display tax.
	 * @return array Modified totals.
	 */
	public function add_fees_to_order_totals( $totals, $order, $tax_display = false ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Required by hook signature.
		// Look for our customs fee and replace its display with breakdown.
		foreach ( $totals as $key => $total ) {
			// Check if this is a fee row and if it's our customs fee.
			if ( isset( $total['label'] ) && strpos( $total['label'], __( 'Customs & Import Fees', 'customs-fees-for-woocommerce' ) ) !== false ) {
				// Get the breakdown if available.
				$breakdown = $order->get_meta( '_cfwc_fees_breakdown', true );

				if ( ! empty( $breakdown ) && is_array( $breakdown ) ) {
					// Check if we're in email context.
					$is_email = did_action( 'woocommerce_email_header' ) > 0;

					if ( $is_email ) {
						// Email-specific formatting without bullets.
						$value_html = '<table cellspacing="0" cellpadding="0" style="width: 100%; border: none;">';
						foreach ( $breakdown as $fee_item ) {
							$value_html .= '<tr>';
							$value_html .= '<td style="padding: 2px 0; border: none; text-align: left;">' . esc_html( $fee_item['label'] ) . ':</td>';
							$value_html .= '<td style="padding: 2px 0 2px 10px; border: none; text-align: right;"><strong>' . wc_price( $fee_item['amount'], array( 'currency' => $order->get_currency() ) ) . '</strong></td>';
							$value_html .= '</tr>';
						}
						$value_html .= '</table>';
					} else {
						// Regular display for web pages.
						$value_html = '<div class="cfwc-fees-breakdown-wrapper" style="display: block;">';
						foreach ( $breakdown as $fee_item ) {
							$value_html .= '<div class="cfwc-fee-item" style="display: block; margin: 0 0 5px 0; padding: 0;">';
							$value_html .= '<span class="cfwc-fee-label">' . esc_html( $fee_item['label'] ) . ':</span> ';
							$value_html .= '<strong>' . wc_price( $fee_item['amount'], array( 'currency' => $order->get_currency() ) ) . '</strong>';
							$value_html .= '</div>';
						}
						$value_html .= '</div>';
					}

					// Update the existing fee display with our breakdown.
					$totals[ $key ]['value'] = $value_html;
				}
			}
		}

		return $totals;
	}

	/**
	 * Display customs info box in order emails (NOT the fees - those are in totals).
	 *
	 * @since 1.0.0
	 * @param WC_Order $order         Order object.
	 * @param bool     $sent_to_admin Whether email is sent to admin.
	 * @param bool     $plain_text    Whether email is plain text.
	 * @param WC_Email $email         Email object.
	 */
	public function display_customs_info_in_email( $order, $sent_to_admin, $plain_text, $email = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Required by hook signature.
		$fees             = $order->get_fees();
		$has_customs_fees = false;
		$total_customs    = 0;

		foreach ( $fees as $fee ) {
			if ( strpos( $fee->get_name(), __( 'Customs & Import Fees', 'customs-fees-for-woocommerce' ) ) !== false ) {
				$has_customs_fees = true;
				$total_customs   += $fee->get_total();
			}
		}

		// Only show info box if there are customs fees.
		if ( $has_customs_fees && $total_customs > 0 ) {
			if ( $plain_text ) {
				// Plain text info.
				echo "\n";
				echo "========================================\n";
				echo esc_html__( 'Customs & Import Information', 'customs-fees-for-woocommerce' ) . "\n";
				echo "========================================\n";
				echo esc_html__( 'Estimated import duties and taxes based on destination country.', 'customs-fees-for-woocommerce' ) . "\n";
				echo "\n";
				echo esc_html__( 'Total customs fees:', 'customs-fees-for-woocommerce' ) . ' ' . esc_html( wp_strip_all_tags( wc_price( $total_customs, array( 'currency' => $order->get_currency() ) ) ) ) . "\n";
				echo "\n";
				echo esc_html__( 'These are estimated fees based on current rates. Actual fees may vary depending on customs regulations and carrier handling charges.', 'customs-fees-for-woocommerce' ) . "\n";
				echo "\n";
			} else {
				// HTML info box with blue background.
				echo '<table cellspacing="0" cellpadding="0" style="width: 100%; margin: 30px 0;">';
				echo '<tr><td style="background-color: #e8f4f8; border-left: 4px solid #0073aa; padding: 20px;">';
				echo '<h3 style="margin: 0 0 10px; color: #0073aa; font-size: 16px;">' . esc_html__( 'Customs & Import Information', 'customs-fees-for-woocommerce' ) . '</h3>';
				echo '<p style="margin: 0 0 10px; color: #666; font-size: 14px;">' . esc_html__( 'Estimated import duties and taxes based on destination country.', 'customs-fees-for-woocommerce' ) . '</p>';
				echo '<p style="margin: 0 0 10px; color: #333; font-size: 14px;"><strong>' . esc_html__( 'Total customs fees:', 'customs-fees-for-woocommerce' ) . '</strong> ' . wp_kses_post( wc_price( $total_customs, array( 'currency' => $order->get_currency() ) ) ) . '</p>';
				echo '<p style="margin: 0; color: #999; font-size: 12px; font-style: italic;">' . esc_html__( 'These are estimated fees based on current rates. Actual fees may vary depending on customs regulations and carrier handling charges.', 'customs-fees-for-woocommerce' ) . '</p>';
				echo '</td></tr>';
				echo '</table>';
			}
		}
	}

	/**
	 * Add HS Code and Origin to order item display on order pages.
	 *
	 * @since 1.0.0
	 * @param string        $item_name Item name HTML.
	 * @param WC_Order_Item $item      Order item object.
	 * @param bool          $is_visible Whether item is visible.
	 * @return string Modified item name.
	 */
	public function add_hs_code_to_order_item_display( $item_name, $item, $is_visible = true ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Required by hook signature.
		// Only add to order pages, not emails.
		if ( ! is_wc_endpoint_url( 'view-order' ) && ! is_order_received_page() && ! is_admin() ) {
			return $item_name;
		}

		// Only process product line items.
		if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
			return $item_name;
		}

		$product = $item->get_product();
		if ( ! $product ) {
			return $item_name;
		}

		$product_id = $product->get_id();
		$parent_id  = $product->get_parent_id();

		// Get data from product/parent.
		$hs_code = get_post_meta( $parent_id ? $parent_id : $product_id, '_cfwc_hs_code', true );
		$origin  = get_post_meta( $parent_id ? $parent_id : $product_id, '_cfwc_country_of_origin', true );

		// Add to display if available.
		$extra_info = '';

		if ( ! empty( $hs_code ) ) {
			$extra_info .= '<br><small class="cfwc-order-customs">' . esc_html__( 'HS Code:', 'customs-fees-for-woocommerce' ) . ' ' . esc_html( $hs_code ) . '</small>';
		}

		if ( ! empty( $origin ) ) {
			$countries   = WC()->countries->get_countries();
			$origin_name = isset( $countries[ $origin ] ) ? $countries[ $origin ] : $origin;
			$extra_info .= '<br><small class="cfwc-order-customs">' . esc_html__( 'Origin:', 'customs-fees-for-woocommerce' ) . ' ' . esc_html( $origin_name ) . '</small>';
		}

		if ( ! empty( $extra_info ) ) {
			$item_name .= $extra_info;
		}

		return $item_name;
	}

	/**
	 * Add HS Code and Origin to order item email display.
	 *
	 * @since 1.0.0
	 * @param array         $args      Email arguments.
	 * @param WC_Order_Item $item Order item.
	 * @param WC_Order      $order     Order object.
	 * @return array Modified arguments.
	 */
	public function add_hs_code_to_email_item( $args, $item, $order ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Required by hook signature.
		// Only process product line items.
		if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
			return $args;
		}

		$product = $item->get_product();
		if ( ! $product ) {
			return $args;
		}

		$product_id = $product->get_id();
		$parent_id  = $product->get_parent_id();

		// Get data from product/parent.
		$hs_code = get_post_meta( $parent_id ? $parent_id : $product_id, '_cfwc_hs_code', true );
		$origin  = get_post_meta( $parent_id ? $parent_id : $product_id, '_cfwc_country_of_origin', true );

		// Add to display if available.
		$extra_info = array();

		if ( ! empty( $hs_code ) ) {
			$extra_info[] = esc_html__( 'HS Code:', 'customs-fees-for-woocommerce' ) . ' ' . esc_html( $hs_code );
		}

		if ( ! empty( $origin ) ) {
			$countries    = WC()->countries->get_countries();
			$origin_name  = isset( $countries[ $origin ] ) ? $countries[ $origin ] : $origin;
			$extra_info[] = esc_html__( 'Origin:', 'customs-fees-for-woocommerce' ) . ' ' . esc_html( $origin_name );
		}

		if ( ! empty( $extra_info ) ) {
			// Add to product name.
			$args['product'] .= '<br><small style="color: #666; font-size: 0.9em;">' . implode( ' | ', $extra_info ) . '</small>';
		}

		return $args;
	}

	/**
	 * Display fee breakdown info box on order received page.
	 *
	 * Shows only the disclaimer since the fee total is already displayed
	 * in the order totals table above.
	 *
	 * @since 1.0.0
	 * @param WC_Order $order Order object.
	 */
	public function display_order_received_info( $order ) {
		// Handle both order object and order ID (for backward compatibility).
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( ! $order ) {
			return;
		}

		// Check if order has customs fees.
		$fees             = $order->get_fees();
		$has_customs_fees = false;

		foreach ( $fees as $fee ) {
			if ( strpos( $fee->get_name(), __( 'Customs & Import Fees', 'customs-fees-for-woocommerce' ) ) !== false ) {
				$has_customs_fees = true;
				break;
			}
		}

		// Only show info box if there are customs fees.
		if ( $has_customs_fees ) {
			?>
			<div class="cfwc-order-info-box">
				<h3><?php esc_html_e( 'Customs & Import Information', 'customs-fees-for-woocommerce' ); ?></h3>
				<p class="cfwc-disclaimer"><?php esc_html_e( 'The customs and import fees shown above are estimated based on current rates. Actual fees may vary depending on customs regulations and carrier handling charges.', 'customs-fees-for-woocommerce' ); ?></p>
			</div>
			<?php
		}
	}
}
