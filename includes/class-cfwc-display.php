<?php
/**
 * Display handler for frontend fee presentation.
 *
 * @package CustomsFeesForWooCommerce
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CFWC_Display class.
 *
 * Handles the display of customs fees on cart, checkout, and order pages.
 *
 * @since 1.0.0
 */
class CFWC_Display {

	/**
	 * Initialize the display handler.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		// Cart page display.
		add_action( 'woocommerce_cart_totals_after_shipping', array( $this, 'display_cart_fees' ), 20 );
		
		// Classic checkout display.
		add_action( 'woocommerce_review_order_after_shipping', array( $this, 'display_checkout_fees' ), 20 );
		
		// Order pages display.
		add_filter( 'woocommerce_get_order_item_totals', array( $this, 'add_fees_to_order_totals' ), 10, 3 );
		
		// Email display.
		add_action( 'woocommerce_email_after_order_table', array( $this, 'display_fees_in_email' ), 10, 4 );
		
		// My Account order display.
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'display_fees_in_account' ) );
		
		// Enqueue frontend scripts.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Display fees on cart page.
	 *
	 * @since 1.0.0
	 */
	public function display_cart_fees() {
		if ( ! get_option( 'cfwc_show_on_cart', true ) ) {
			return;
		}

		// Get calculated fees from cart.
		$fees = WC()->cart->get_fees();
		
		foreach ( $fees as $fee ) {
			if ( strpos( $fee->id, 'customs-fee' ) === 0 ) {
				$this->render_fee_row( $fee->name, $fee->amount, 'cart' );
			}
		}
	}

	/**
	 * Display fees on checkout page.
	 *
	 * @since 1.0.0
	 */
	public function display_checkout_fees() {
		if ( ! get_option( 'cfwc_show_on_checkout', true ) ) {
			return;
		}

		// Get calculated fees from cart.
		$fees = WC()->cart->get_fees();
		
		foreach ( $fees as $fee ) {
			if ( strpos( $fee->id, 'customs-fee' ) === 0 ) {
				$this->render_fee_row( $fee->name, $fee->amount, 'checkout' );
			}
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
				$totals['customs_fees'] = array(
					'label' => $fee->get_name() . ':',
					'value' => wc_price( $fee->get_total() ),
				);
			}
		}
		
		return $totals;
	}

	/**
	 * Display fees in order emails.
	 *
	 * @since 1.0.0
	 * @param WC_Order $order         Order object.
	 * @param bool     $sent_to_admin Whether email is sent to admin.
	 * @param bool     $plain_text    Whether email is plain text.
	 * @param WC_Email $email         Email object.
	 */
	public function display_fees_in_email( $order, $sent_to_admin, $plain_text, $email = null ) {
		$fees = $order->get_fees();
		
		foreach ( $fees as $fee ) {
			if ( strpos( $fee->get_name(), __( 'Customs & Import Fees', 'customs-fees-for-woocommerce' ) ) !== false ) {
				if ( $plain_text ) {
					echo esc_html( $fee->get_name() ) . ': ' . esc_html( wp_strip_all_tags( wc_price( $fee->get_total() ) ) ) . "\n";
				} else {
					echo '<p><strong>' . esc_html( $fee->get_name() ) . ':</strong> ' . wp_kses_post( wc_price( $fee->get_total() ) ) . '</p>';
				}
			}
		}
	}

	/**
	 * Display fees in My Account order view.
	 *
	 * @since 1.0.0
	 * @param WC_Order $order Order object.
	 */
	public function display_fees_in_account( $order ) {
		$fees = $order->get_fees();
		
		foreach ( $fees as $fee ) {
			if ( strpos( $fee->get_name(), __( 'Customs & Import Fees', 'customs-fees-for-woocommerce' ) ) !== false ) {
				echo '<div class="cfwc-order-fees">';
				echo '<h3>' . esc_html__( 'Customs & Import Fees', 'customs-fees-for-woocommerce' ) . '</h3>';
				echo '<p>' . wp_kses_post( wc_price( $fee->get_total() ) ) . '</p>';
				if ( get_option( 'cfwc_disclaimer_text' ) ) {
					echo '<p class="cfwc-disclaimer">' . esc_html( get_option( 'cfwc_disclaimer_text' ) ) . '</p>';
				}
				echo '</div>';
			}
		}
	}

	/**
	 * Render a fee row.
	 *
	 * @since 1.0.0
	 * @param string $label   Fee label.
	 * @param float  $amount  Fee amount.
	 * @param string $context Display context (cart/checkout).
	 */
	private function render_fee_row( $label, $amount, $context = 'cart' ) {
		$show_tooltip = get_option( 'cfwc_show_tooltip', true );
		$tooltip_text = get_option( 'cfwc_tooltip_text', '' );
		
		?>
		<tr class="cfwc-fee-row fee">
			<th>
				<?php echo esc_html( $label ); ?>
				<?php if ( $show_tooltip && $tooltip_text ) : ?>
					<span class="cfwc-tooltip" title="<?php echo esc_attr( $tooltip_text ); ?>">
						<span class="dashicons dashicons-info-outline"></span>
					</span>
				<?php endif; ?>
			</th>
			<td data-title="<?php echo esc_attr( $label ); ?>">
				<?php echo wp_kses_post( wc_price( $amount ) ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Enqueue frontend scripts and styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		if ( is_cart() || is_checkout() || is_account_page() ) {
			// Inline styles for now, will move to separate file later.
			wp_add_inline_style( 'woocommerce-general', '
				.cfwc-fee-row th { font-weight: normal; }
				.cfwc-tooltip { cursor: help; margin-left: 5px; }
				.cfwc-tooltip .dashicons { font-size: 16px; width: 16px; height: 16px; }
				.cfwc-disclaimer { font-size: 0.9em; color: #666; font-style: italic; }
				.cfwc-order-fees { margin-top: 20px; padding: 15px; background: #f7f7f7; border-radius: 4px; }
			' );
		}
	}
}
