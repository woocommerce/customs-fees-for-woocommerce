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
		// Add fees to email order totals.
		add_filter( 'woocommerce_get_order_item_totals', array( $this, 'add_fees_to_email_totals' ), 10, 3 );
		
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
		
		foreach ( $fees as $fee ) {
			$fee_name = $fee->get_name();
			if ( strpos( $fee_name, __( 'Customs & Import Fees', 'customs-fees-for-woocommerce' ) ) !== false ) {
				// Insert customs fees before order total.
				$new_totals = array();
				foreach ( $totals as $key => $total ) {
					if ( 'order_total' === $key ) {
						$new_totals['customs_fees'] = array(
							'label' => $fee_name . ':',
							'value' => wc_price( $fee->get_total(), array( 'currency' => $order->get_currency() ) ),
						);
					}
					$new_totals[ $key ] = $total;
				}
				return $new_totals;
			}
		}
		
		return $totals;
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

		$fees       = $order->get_fees();
		$has_customs = false;
		
		foreach ( $fees as $fee ) {
			if ( strpos( $fee->get_name(), __( 'Customs & Import Fees', 'customs-fees-for-woocommerce' ) ) !== false ) {
				$has_customs = true;
				break;
			}
		}

		if ( ! $has_customs ) {
			return;
		}

		$disclaimer = cfwc_get_disclaimer_text();
		
		if ( $plain_text ) {
			echo "\n" . esc_html__( 'CUSTOMS & IMPORT INFORMATION', 'customs-fees-for-woocommerce' ) . "\n";
			echo "----------------------------------------\n";
			if ( $disclaimer ) {
				echo esc_html( $disclaimer ) . "\n";
			}
			echo esc_html__( 'The customs fees shown are estimates. Actual fees at delivery may vary depending on your country\'s regulations.', 'customs-fees-for-woocommerce' ) . "\n\n";
		} else {
			?>
			<div style="margin: 30px 0; padding: 20px; background-color: #f7f7f7; border-left: 4px solid #dc3232;">
				<h3 style="margin-top: 0; color: #dc3232;">
					<?php echo esc_html__( 'Important: Customs & Import Information', 'customs-fees-for-woocommerce' ); ?>
				</h3>
				<?php if ( $disclaimer ) : ?>
					<p style="margin: 10px 0;">
						<em><?php echo esc_html( $disclaimer ); ?></em>
					</p>
				<?php endif; ?>
				<p style="margin: 10px 0 0;">
					<?php echo esc_html__( 'The customs fees shown are estimates based on current rates. Actual fees at delivery may vary depending on your country\'s regulations and the carrier\'s handling charges.', 'customs-fees-for-woocommerce' ); ?>
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
		
		foreach ( $fees as $fee ) {
			if ( strpos( $fee->get_name(), __( 'Customs & Import Fees', 'customs-fees-for-woocommerce' ) ) !== false ) {
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
			echo "\n" . esc_html__( 'Customs Fees Applied:', 'customs-fees-for-woocommerce' ) . ' ' . esc_html( wp_strip_all_tags( wc_price( $total_fees ) ) ) . "\n";
			foreach ( $fee_details as $detail ) {
				echo '- ' . esc_html( $detail['name'] ) . ': ' . esc_html( wp_strip_all_tags( wc_price( $detail['amount'] ) ) ) . "\n";
			}
		} else {
			?>
			<p>
				<strong><?php echo esc_html__( 'Customs Fees Applied:', 'customs-fees-for-woocommerce' ); ?></strong>
				<?php echo wp_kses_post( wc_price( $total_fees, array( 'currency' => $order->get_currency() ) ) ); ?>
			</p>
			<?php if ( count( $fee_details ) > 1 ) : ?>
				<ul style="margin: 0; padding-left: 20px;">
					<?php foreach ( $fee_details as $detail ) : ?>
						<li>
							<?php echo esc_html( $detail['name'] ); ?>: 
							<?php echo wp_kses_post( wc_price( $detail['amount'], array( 'currency' => $order->get_currency() ) ) ); ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
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
