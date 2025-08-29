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
		
		// Order pages display - adds to order totals table.
		add_filter( 'woocommerce_get_order_item_totals', array( $this, 'add_fees_to_order_totals' ), 10, 3 );
		
		// REMOVED: Separate thank you page display - fees already shown in order totals table via add_fees_to_order_totals.
		
		// Add HS Codes to order item names on order pages (non-email).
		add_filter( 'woocommerce_order_item_name', array( $this, 'add_hs_code_to_order_item_display' ), 10, 3 );
		
		// Email display.
		add_action( 'woocommerce_email_after_order_table', array( $this, 'display_fees_in_email' ), 10, 4 );
		
		// REMOVED: My Account order display - fees already shown in order totals table via add_fees_to_order_totals.
		
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
	 * Display fees on thank you page (order received).
	 *
	 * @since 1.0.0
	 * @param int $order_id Order ID.
	 */
	public function display_fees_on_thankyou( $order_id ) {
		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$fees = $order->get_fees();
		$customs_fees = array();
		$total_customs = 0;

		// Collect all customs fees.
		foreach ( $fees as $fee ) {
			if ( strpos( $fee->get_name(), __( 'Customs & Import Fees', 'customs-fees-for-woocommerce' ) ) !== false ) {
				$customs_fees[] = $fee;
				$total_customs += $fee->get_total();
			}
		}

		// Display if we have customs fees.
		if ( ! empty( $customs_fees ) ) {
			?>
			<section class="woocommerce-customs-details">
				<h2 class="woocommerce-column__title"><?php esc_html_e( 'Customs & Import Information', 'customs-fees-for-woocommerce' ); ?></h2>
				
				<table class="woocommerce-table woocommerce-table--customs-details shop_table customs_details">
					<tbody>
						<?php foreach ( $customs_fees as $fee ) : ?>
							<tr>
								<th scope="row"><?php echo esc_html( $fee->get_name() ); ?>:</th>
								<td><?php echo wp_kses_post( wc_price( $fee->get_total(), array( 'currency' => $order->get_currency() ) ) ); ?></td>
							</tr>
						<?php endforeach; ?>
						<?php if ( count( $customs_fees ) > 1 ) : ?>
							<tr>
								<th scope="row"><strong><?php esc_html_e( 'Total Customs Fees', 'customs-fees-for-woocommerce' ); ?>:</strong></th>
								<td><strong><?php echo wp_kses_post( wc_price( $total_customs, array( 'currency' => $order->get_currency() ) ) ); ?></strong></td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
				
				<?php if ( get_option( 'cfwc_disclaimer_text' ) ) : ?>
					<p class="cfwc-disclaimer" style="margin-top: 10px; font-size: 0.9em; color: #666;">
						<?php echo esc_html( get_option( 'cfwc_disclaimer_text' ) ); ?>
					</p>
				<?php endif; ?>
			</section>
			<?php
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
	public function add_hs_code_to_order_item_display( $item_name, $item, $is_visible = true ) {
		// Only modify for product line items.
		if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
			return $item_name;
		}
		
		// Skip if we're in an email context (emails handled separately).
		if ( did_action( 'woocommerce_email_header' ) ) {
			return $item_name;
		}
		
		// Only on frontend order pages (thank you, my account), NOT admin.
		if ( ! is_order_received_page() && ! is_account_page() ) {
			return $item_name;
		}
		
		// Skip in admin area - handled separately as order item meta.
		if ( is_admin() ) {
			return $item_name;
		}
		
		$product = $item->get_product();
		if ( ! $product ) {
			return $item_name;
		}
		
		$product_id = $product->get_id();
		$hs_code = get_post_meta( $product_id, '_cfwc_hs_code', true );
		$origin = get_post_meta( $product_id, '_cfwc_country_of_origin', true );
		
		if ( $hs_code || $origin ) {
			$customs_info = '<br><small style="color: #666; font-size: 0.9em;">';
			
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
			
			$item_name .= $customs_info;
		}
		
		return $item_name;
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
