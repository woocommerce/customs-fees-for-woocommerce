<?php
/**
 * Email integration handler.
 *
 * @package CustomsFeesForWooCommerce
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CFWC_Emails class.
 *
 * Handles email customizations for customs fees display.
 *
 * @since 1.0.0
 */
class CFWC_Emails {

	/**
	 * Initialize email handler.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		// REMOVED: add_filter for woocommerce_get_order_item_totals - handled by class-cfwc-display.php to avoid duplication.
		
		// Add HS Codes to order item names in emails.
		add_filter( 'woocommerce_order_item_name', array( $this, 'add_hs_code_to_order_item' ), 10, 3 );
		
		// Add custom content to emails.
		add_action( 'woocommerce_email_after_order_table', array( $this, 'add_customs_info_to_email' ), 15, 4 );
		
		// Admin email notifications.
		add_action( 'woocommerce_email_order_meta', array( $this, 'add_admin_email_meta' ), 10, 3 );
		
		// Customize email styles.
		add_filter( 'woocommerce_email_styles', array( $this, 'add_email_styles' ), 10, 2 );
	}

	/**
	 * Add fees to email order totals.
	 *
	 * @since 1.0.0
	 * @param array    $totals Order totals.
	 * @param WC_Order $order  Order object.
	 * @param bool     $tax_display Tax display setting.
	 * @return array Modified totals.
	 */
	public function add_fees_to_email_totals( $totals, $order, $tax_display = false ) {
		$fees = $order->get_fees();
		
		if ( empty( $fees ) ) {
			return $totals;
		}
		
		// Collect all customs fees.
		$customs_fees = array();
		$total_customs = 0;
		
		foreach ( $fees as $fee ) {
			$fee_name = $fee->get_name();
			// Check if this is a customs fee by looking for common patterns.
			if ( strpos( strtolower( $fee_name ), 'customs' ) !== false || 
			     strpos( strtolower( $fee_name ), 'import' ) !== false ||
			     strpos( strtolower( $fee_name ), 'duty' ) !== false ) {
				$customs_fees[] = array(
					'name' => $fee_name,
					'amount' => $fee->get_total(),
				);
				$total_customs += $fee->get_total();
			}
		}
		
		// If we have customs fees, add them before the order total.
		if ( ! empty( $customs_fees ) ) {
			$new_totals = array();
			
			foreach ( $totals as $key => $total ) {
				// Add customs fees before order total.
				if ( 'order_total' === $key && $total_customs > 0 ) {
					// Add each customs fee separately or as combined based on display mode.
					$display_mode = get_option( 'cfwc_display_mode', 'single' );
					
					if ( 'breakdown' === $display_mode && count( $customs_fees ) > 1 ) {
						// Show breakdown of fees.
						foreach ( $customs_fees as $customs_fee ) {
							$new_totals[ 'customs_fee_' . sanitize_key( $customs_fee['name'] ) ] = array(
								'label' => $customs_fee['name'] . ':',
								'value' => wc_price( $customs_fee['amount'], array( 'currency' => $order->get_currency() ) ),
							);
						}
					} else {
						// Single line display.
						$label = count( $customs_fees ) > 1 ? 
							__( 'Customs & Import Fees', 'customs-fees-for-woocommerce' ) : 
							$customs_fees[0]['name'];
						
						$new_totals['customs_fees'] = array(
							'label' => $label . ':',
							'value' => wc_price( $total_customs, array( 'currency' => $order->get_currency() ) ),
						);
					}
				}
				
				$new_totals[ $key ] = $total;
			}
			
			return $new_totals;
		}
		
		return $totals;
	}
	
	/**
	 * Add HS Code to order item display in emails.
	 *
	 * @since 1.0.0
	 * @param string        $item_name Item name HTML.
	 * @param WC_Order_Item $item      Order item object.
	 * @param bool          $is_visible Whether item is visible.
	 * @return string Modified item name.
	 */
	public function add_hs_code_to_order_item( $item_name, $item, $is_visible = true ) {
		// Only modify for product line items.
		if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
			return $item_name;
		}
		
		// Only for emails (check if we're in an email context).
		if ( ! did_action( 'woocommerce_email_header' ) ) {
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
	 * Add customs information to order emails.
	 *
	 * @since 1.0.0
	 * @param WC_Order $order         Order object.
	 * @param bool     $sent_to_admin Whether email is for admin.
	 * @param bool     $plain_text    Whether email is plain text.
	 * @param WC_Email $email         Email object.
	 */
	public function add_customs_info_to_email( $order, $sent_to_admin, $plain_text, $email ) {
		// Only show for customer emails.
		if ( $sent_to_admin ) {
			return;
		}

		$fees = $order->get_fees();
		$has_customs = false;
		$customs_total = 0;
		
		// Check for any customs-related fees.
		foreach ( $fees as $fee ) {
			$fee_name = strtolower( $fee->get_name() );
			if ( strpos( $fee_name, 'customs' ) !== false || 
			     strpos( $fee_name, 'import' ) !== false ||
			     strpos( $fee_name, 'duty' ) !== false ) {
				$has_customs = true;
				$customs_total += $fee->get_total();
			}
		}

		if ( ! $has_customs ) {
			return;
		}

		// Get tooltip/help text.
		$tooltip_text = class_exists( 'CFWC_Settings' ) ? CFWC_Settings::get_default_help_text() : '';
		
		if ( $plain_text ) {
			echo "\n" . esc_html__( 'CUSTOMS & IMPORT INFORMATION', 'customs-fees-for-woocommerce' ) . "\n";
			echo "----------------------------------------\n";
			if ( $tooltip_text ) {
				echo esc_html( $tooltip_text ) . "\n";
			}
			echo sprintf( 
				/* translators: %s: Total customs fees amount */
				esc_html__( 'Total customs fees applied: %s', 'customs-fees-for-woocommerce' ),
				esc_html( wp_strip_all_tags( wc_price( $customs_total, array( 'currency' => $order->get_currency() ) ) ) )
			) . "\n";
			echo esc_html__( 'Note: These are estimated fees. Actual fees at delivery may vary.', 'customs-fees-for-woocommerce' ) . "\n\n";
		} else {
			?>
			<div style="margin: 30px 0; padding: 20px; background-color: #f0f8ff; border-left: 4px solid #007cba; border-radius: 4px;">
				<h3 style="margin-top: 0; color: #007cba; font-size: 16px;">
					<?php echo esc_html__( 'Customs & Import Information', 'customs-fees-for-woocommerce' ); ?>
				</h3>
				
				<?php if ( $tooltip_text ) : ?>
					<p style="margin: 10px 0; color: #555;">
						<?php echo wp_kses_post( $tooltip_text ); ?>
					</p>
				<?php endif; ?>
				
				<p style="margin: 10px 0; font-weight: bold; color: #333;">
					<?php 
					echo sprintf( 
						/* translators: %s: Total customs fees amount */
						esc_html__( 'Total customs fees: %s', 'customs-fees-for-woocommerce' ),
						wp_kses_post( wc_price( $customs_total, array( 'currency' => $order->get_currency() ) ) )
					); 
					?>
				</p>
				
				<p style="margin: 10px 0 0; font-size: 13px; color: #666; font-style: italic;">
					<?php echo esc_html__( 'These are estimated fees based on current rates. Actual fees may vary depending on customs regulations and carrier handling charges.', 'customs-fees-for-woocommerce' ); ?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Add metadata to admin emails.
	 *
	 * @since 1.0.0
	 * @param WC_Order $order         Order object.
	 * @param bool     $sent_to_admin Whether email is for admin.
	 * @param bool     $plain_text    Whether email is plain text.
	 */
	public function add_admin_email_meta( $order, $sent_to_admin, $plain_text ) {
		if ( ! $sent_to_admin ) {
			return;
		}

		$fees        = $order->get_fees();
		$total_fees  = 0;
		$fee_details = array();
		
		// Check for any customs-related fees.
		foreach ( $fees as $fee ) {
			$fee_name = strtolower( $fee->get_name() );
			if ( strpos( $fee_name, 'customs' ) !== false || 
			     strpos( $fee_name, 'import' ) !== false ||
			     strpos( $fee_name, 'duty' ) !== false ) {
				$total_fees += $fee->get_total();
				$fee_details[] = array(
					'name'   => $fee->get_name(),
					'amount' => $fee->get_total(),
				);
			}
		}

		if ( $total_fees <= 0 ) {
			return;
		}

		if ( $plain_text ) {
			echo "\n" . esc_html__( 'Customs Fees Applied:', 'customs-fees-for-woocommerce' ) . ' ' . esc_html( wp_strip_all_tags( wc_price( $total_fees, array( 'currency' => $order->get_currency() ) ) ) ) . "\n";
			foreach ( $fee_details as $detail ) {
				echo '- ' . esc_html( $detail['name'] ) . ': ' . esc_html( wp_strip_all_tags( wc_price( $detail['amount'], array( 'currency' => $order->get_currency() ) ) ) ) . "\n";
			}
		} else {
			?>
			<div style="margin: 15px 0; padding: 10px; background: #f9f9f9; border-left: 3px solid #007cba;">
				<p style="margin: 0 0 10px;">
					<strong><?php echo esc_html__( 'Customs Fees Applied:', 'customs-fees-for-woocommerce' ); ?></strong>
					<?php echo wp_kses_post( wc_price( $total_fees, array( 'currency' => $order->get_currency() ) ) ); ?>
				</p>
				<?php if ( count( $fee_details ) > 1 ) : ?>
					<ul style="margin: 0; padding-left: 20px; font-size: 14px;">
						<?php foreach ( $fee_details as $detail ) : ?>
							<li>
								<?php echo esc_html( $detail['name'] ); ?>: 
								<?php echo wp_kses_post( wc_price( $detail['amount'], array( 'currency' => $order->get_currency() ) ) ); ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
			<?php
		}
	}

	/**
	 * Add custom styles to emails.
	 *
	 * @since 1.0.0
	 * @param string   $css   Email CSS.
	 * @param WC_Email $email Email object.
	 * @return string Modified CSS.
	 */
	public function add_email_styles( $css, $email ) {
		$additional_css = '
			.cfwc-customs-notice {
				margin: 20px 0;
				padding: 15px;
				background-color: #fff3cd;
				border: 1px solid #ffeaa7;
				border-radius: 4px;
			}
			.cfwc-customs-notice h4 {
				margin-top: 0;
				color: #856404;
			}
			.cfwc-customs-notice p {
				margin-bottom: 0;
				color: #856404;
			}
			.cfwc-fee-breakdown {
				margin: 10px 0;
				padding: 10px;
				background: #f8f9fa;
				border-radius: 3px;
			}
			.cfwc-fee-breakdown ul {
				margin: 0;
				padding-left: 20px;
			}
		';
		
		return $css . $additional_css;
	}
}
