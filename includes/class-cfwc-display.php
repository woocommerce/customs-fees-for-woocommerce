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
		// Hook into order totals display (for thankyou page, my account, and emails).
		add_filter( 'woocommerce_get_order_item_totals', array( $this, 'add_fees_to_order_totals' ), 10, 3 );

		// Add HS Codes to order item names on order pages (non-email).
		add_filter( 'woocommerce_order_item_name', array( $this, 'add_hs_code_to_order_item_display' ), 10, 3 );

		// Display info box on order received page.
		add_action( 'woocommerce_thankyou', array( $this, 'display_order_received_info' ), 15 );

		// Email info box display (fees are already in order totals).
		add_action( 'woocommerce_email_after_order_table', array( $this, 'display_customs_info_in_email' ), 10, 4 );

		// Save fee breakdown to order when order is created.
		add_action( 'woocommerce_checkout_create_order', array( $this, 'save_fee_breakdown_to_order' ), 10, 2 );

		// Enqueue frontend assets for tooltips.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_tooltip_assets' ) );
	}

	/**
	 * Customize fee display to show breakdown.
	 *
	 * @since 1.0.0
	 * @param WC_Cart_Fees $fee     Fee object.
	 * @param WC_Cart      $cart    Cart object.
	 * @return string Modified fee HTML.
	 */
	public function customize_fee_display( $fee, $cart ) {
		if ( __( 'Customs & Import Fees', 'customs-fees-for-woocommerce' ) !== $fee->name ) {
			return;
		}

		// Get breakdown from session.
		$breakdown = WC()->session->get( 'cfwc_fees_breakdown', array() );

		if ( empty( $breakdown ) ) {
			return;
		}

		$html  = '<div id="cfwc_fees_breakdown" class="cfwc-fees-breakdown">';
		$html .= '<ul>';
		foreach ( $breakdown as $fee_item ) {
			$html .= '<li>' . esc_html( $fee_item['label'] ) . ': ' . wc_price( $fee_item['amount'] ) . '</li>';
		}
		$html .= '</ul>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Enqueue frontend assets for tooltips.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_tooltip_assets() {
		if ( is_checkout() || is_cart() ) {
			wp_enqueue_script( 'cfwc-frontend', plugin_dir_url( __DIR__ ) . 'assets/js/frontend.js', array( 'jquery' ), '1.0.0', true );
			wp_enqueue_style( 'cfwc-frontend', plugin_dir_url( __DIR__ ) . 'assets/css/frontend.css', array(), '1.0.0' );
		}
	}

	/**
	 * Save fee breakdown to order.
	 *
	 * @since 1.0.0
	 * @param WC_Order $order Order object.
	 * @param array    $data   Posted data (unused but required by hook).
	 */
	public function save_fee_breakdown_to_order( $order, $data ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Required by hook signature.
		$breakdown = WC()->session->get( 'cfwc_fees_breakdown', array() );
		if ( ! empty( $breakdown ) ) {
			$order->update_meta_data( '_cfwc_fees_breakdown', $breakdown );
		}
	}

	/**
	 * Add fees to order totals display.
	 *
	 * @since 1.0.0
	 * @param array    $totals Order totals.
	 * @param WC_Order $order  Order object.
	 * @param bool     $tax_display Whether to display tax.
	 * @return array Modified totals.
	 */
	public function add_fees_to_order_totals( $totals, $order, $tax_display = false ) {
		$fees = $order->get_fees();

		foreach ( $fees as $fee ) {
			if ( strpos( $fee->get_name(), __( 'Customs & Import Fees', 'customs-fees-for-woocommerce' ) ) !== false ) {
				// Get the breakdown if available.
				$breakdown = $order->get_meta( '_cfwc_fees_breakdown', true );

				$value_html = '';
				if ( ! empty( $breakdown ) && is_array( $breakdown ) ) {
					// Show breakdown with proper formatting.
					$value_html  = '<div class="cfwc-fees-breakdown-wrapper">';
					$value_html .= '<ul class="cfwc-fees-breakdown woocommerce-order-overview" style="list-style: none !important; margin: 0 !important; padding: 0 !important;">';
					foreach ( $breakdown as $fee_item ) {
						$value_html .= '<li class="cfwc-fee-item" style="list-style: none !important; margin: 0 0 5px 0 !important; padding: 0 !important;">';
						$value_html .= '<span class="cfwc-fee-label">' . esc_html( $fee_item['label'] ) . ':</span> ';
						$value_html .= '<strong>' . wc_price( $fee_item['amount'], array( 'currency' => $order->get_currency() ) ) . '</strong>';
						$value_html .= '</li>';
					}
					$value_html .= '</ul>';
					$value_html .= '</div>';
				} else {
					// Fallback to simple total.
					$value_html = wc_price( $fee->get_total(), array( 'currency' => $order->get_currency() ) );
				}

				$totals['customs_fees'] = array(
					'label' => $fee->get_name() . ':',
					'value' => $value_html,
				);
			}
		}

		return $totals;
	}

	/**
	 * Display customs info box in order emails.
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
				// HTML info box.
				echo '<table cellspacing="0" cellpadding="0" style="width: 100%; margin: 30px 0;">';
				echo '<tr><td style="background-color: #f7f7f7; border-left: 4px solid #0073aa; padding: 20px;">';
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
		$origin  = get_post_meta( $parent_id ? $parent_id : $product_id, '_cfwc_origin_country', true );

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
	 * @param array    $args      Email arguments.
	 * @param WC_Order_Item $item Order item.
	 * @param WC_Order $order     Order object.
	 * @return array Modified arguments.
	 */
	public function add_hs_code_to_email_item( $args, $item, $order ) {
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
		$origin  = get_post_meta( $parent_id ? $parent_id : $product_id, '_cfwc_origin_country', true );

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
	 * @since 1.0.0
	 * @param int $order_id Order ID.
	 */
	public function display_order_received_info( $order_id ) {
		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$breakdown = $order->get_meta( '_cfwc_fees_breakdown', true );

		if ( ! empty( $breakdown ) && is_array( $breakdown ) ) {
			$total = 0;
			foreach ( $breakdown as $fee ) {
				$total += $fee['amount'];
			}

			if ( $total > 0 ) {
				?>
				<div class="cfwc-order-info-box">
					<h3><?php esc_html_e( 'Customs & Import Information', 'customs-fees-for-woocommerce' ); ?></h3>
					<p><?php esc_html_e( 'Estimated import duties and taxes based on destination country.', 'customs-fees-for-woocommerce' ); ?></p>
					<p><strong><?php esc_html_e( 'Total customs fees:', 'customs-fees-for-woocommerce' ); ?></strong> <?php echo wp_kses_post( wc_price( $total ) ); ?></p>
					<p class="cfwc-disclaimer"><?php esc_html_e( 'These are estimated fees based on current rates. Actual fees may vary depending on customs regulations and carrier handling charges.', 'customs-fees-for-woocommerce' ); ?></p>
				</div>
				<?php
			}
		}
	}
}