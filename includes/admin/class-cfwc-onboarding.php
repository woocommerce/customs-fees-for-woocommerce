<?php
/**
 * Onboarding and setup guidance for Customs Fees
 *
 * @package CustomsFeesWooCommerce
 * @since 1.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Onboarding handler class.
 *
 * @since 1.1.0
 */
class CFWC_Onboarding {

	/**
	 * Constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		// Admin notices - simple like AutomateWoo.
		add_action( 'admin_notices', array( $this, 'maybe_show_setup_notice' ), 10 );
		add_action( 'wp_ajax_cfwc_dismiss_setup_notice', array( $this, 'ajax_dismiss_notice' ) );
		
		// Quick edit.
		add_action( 'quick_edit_custom_box', array( $this, 'add_quick_edit_fields' ), 10, 2 );
		add_action( 'save_post_product', array( $this, 'save_quick_edit_data' ) );
		
		// Bulk edit.
		add_action( 'bulk_edit_custom_box', array( $this, 'add_bulk_edit_fields' ), 10, 2 );
		add_action( 'woocommerce_product_bulk_edit_save', array( $this, 'save_bulk_edit_data' ) );
		
		// AJAX for quick edit data.
		add_action( 'wp_ajax_cfwc_get_quick_edit_data', array( $this, 'ajax_get_quick_edit_data' ) );
		
		// Set activation time when activated.
		add_action( 'admin_init', array( $this, 'maybe_set_activation_time' ) );
	}

	/**
	 * Get product statistics.
	 *
	 * @since 1.1.0
	 * @return array Product counts.
	 */
	public function get_product_stats() {
		global $wpdb;
		
		// Get total published products.
		$total = (int) wp_count_posts( 'product' )->publish;
		
		// Check cache first.
		$cache_key = 'cfwc_products_with_origin_count';
		$with_origin = wp_cache_get( $cache_key, 'cfwc' );
		
		if ( false === $with_origin ) {
			// Get products with country of origin.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom query needed for performance.
			$with_origin = (int) $wpdb->get_var(
				"SELECT COUNT(DISTINCT p.ID) 
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				WHERE p.post_type = 'product' 
				AND p.post_status = 'publish'
				AND pm.meta_key = '_cfwc_country_of_origin'
				AND pm.meta_value != ''"
			);
			
			// Cache for 5 minutes.
			wp_cache_set( $cache_key, $with_origin, 'cfwc', 300 );
		}
		
		return array(
			'total'        => $total,
			'with_origin'  => $with_origin,
			'missing'      => $total - $with_origin,
		);
	}

	/**
	 * Maybe show setup notice.
	 *
	 * @since 1.1.0
	 */
	public function maybe_show_setup_notice() {
		// Simple check - admins only.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		// Check if dismissed.
		if ( get_option( 'cfwc_dismissed_setup_notice' ) ) {
			return;
		}
		
		// Don't show on the settings page.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['tab'] ) && 'tax' === $_GET['tab'] && isset( $_GET['section'] ) && 'customs' === $_GET['section'] ) {
			return;
		}
		
		// Check if plugin was just activated.
		$just_activated = get_option( 'cfwc_activated' ) ? true : false;
		
		// Check if activated within last 10 minutes (show on all pages).
		$activation_time = get_option( 'cfwc_activation_time' );
		$show_everywhere = false;
		
		if ( $activation_time && ( time() - $activation_time ) < 600 ) {
			// Within 10 minutes of activation - show everywhere.
			$show_everywhere = true;
		}
		
		if ( ! $show_everywhere ) {
			// After 10 minutes, only show on WooCommerce pages when needed.
			$screen = get_current_screen();
			if ( ! $screen || ( false === strpos( $screen->id, 'woocommerce' ) && 'product' !== $screen->post_type && 'shop_order' !== $screen->post_type ) ) {
				return;
			}
			
			// Check if products need origin data.
			$stats = $this->get_product_stats();
			if ( $stats['missing'] === 0 ) {
				return;
			}
		}
		
		$products_url = admin_url( 'edit.php?post_type=product' );
		$settings_url = admin_url( 'admin.php?page=wc-settings&tab=tax&section=customs' );
		
		// Determine current page.
		$on_products_page = false;
		$screen = get_current_screen();
		if ( $screen && 'product' === $screen->post_type && 'edit' === $screen->base ) {
			$on_products_page = true;
		}
		
		// Get stats for proper messaging.
		$stats = $this->get_product_stats();
		?>
		<div class="notice notice-warning is-dismissible cfwc-setup-notice">
			<p>
				<strong><?php esc_html_e( 'Customs Fees for WooCommerce:', 'customs-fees-for-woocommerce' ); ?></strong>
				<?php
				if ( $stats['missing'] > 0 ) {
					printf(
						/* translators: %d: number of products */
						esc_html( _n(
							'%d product needs Country of Origin data to calculate customs fees correctly.',
							'%d products need Country of Origin data to calculate customs fees correctly.',
							$stats['missing'],
							'customs-fees-for-woocommerce'
											) ),
					absint( $stats['missing'] )
				);
			} else {
					esc_html_e( 'All products have Country of Origin data. Configure your customs rules to start calculating fees.', 'customs-fees-for-woocommerce' );
				}
				?>
			</p>
			<p>
				<?php if ( ! $on_products_page && $stats['missing'] > 0 ) : ?>
					<a href="<?php echo esc_url( $products_url ); ?>" class="button button-primary">
						<?php esc_html_e( 'Go to Products', 'customs-fees-for-woocommerce' ); ?>
					</a>
				<?php endif; ?>
				
				<a href="<?php echo esc_url( $settings_url ); ?>" class="button <?php echo ( $on_products_page || $stats['missing'] === 0 ) ? 'button-primary' : ''; ?>">
					<?php esc_html_e( 'Configure Rules', 'customs-fees-for-woocommerce' ); ?>
				</a>
				
				<a href="#" class="cfwc-dismiss-notice" style="margin-left: 15px;">
					<?php esc_html_e( 'Close and don\'t show', 'customs-fees-for-woocommerce' ); ?>
				</a>
			</p>
		</div>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('.cfwc-dismiss-notice').on('click', function(e) {
				e.preventDefault();
				$.post(ajaxurl, {
									action: 'cfwc_dismiss_setup_notice',
				nonce: '<?php echo esc_js( wp_create_nonce( 'cfwc_dismiss_notice' ) ); ?>'
			});
				$(this).closest('.notice').fadeOut();
			});
		});
		</script>
		<?php
		
		// Clear activation transient AFTER displaying the notice.
		if ( $just_activated ) {
			delete_transient( 'cfwc_activated' );
		}
	}

	/**
	 * AJAX handler to dismiss notice.
	 *
	 * @since 1.1.0
	 */
	public function ajax_dismiss_notice() {
		check_ajax_referer( 'cfwc_dismiss_notice', 'nonce' );
		
		if ( current_user_can( 'manage_options' ) ) {
			update_option( 'cfwc_dismissed_setup_notice', true );
		}
		
		wp_die();
	}
	
	/**
	 * Maybe set activation time.
	 *
	 * @since 1.1.0
	 */
	public function maybe_set_activation_time() {
		// When plugin is first activated, set the activation time.
		if ( get_option( 'cfwc_activated' ) ) {
			update_option( 'cfwc_activation_time', time() );
			// This is handled by the main plugin class now.
		}
	}

	/**
	 * Add quick edit fields.
	 *
	 * @since 1.1.0
	 * @param string $column_name Column name.
	 * @param string $post_type Post type.
	 */
	public function add_quick_edit_fields( $column_name, $post_type ) {
		// Show on any column for products since we don't have our own column anymore.
		if ( 'product' !== $post_type || 'name' !== $column_name ) {
			return;
		}
		?>
		<fieldset class="inline-edit-col-left">
			<div class="inline-edit-col">
				<label>
					<span class="title"><?php esc_html_e( 'HS Code', 'customs-fees-for-woocommerce' ); ?></span>
					<span class="input-text-wrap">
						<input type="text" name="_cfwc_hs_code" class="text cfwc_hs_code" value="" />
					</span>
				</label>
				<br class="clear" />
				<label>
					<span class="title"><?php esc_html_e( 'Country of Origin', 'customs-fees-for-woocommerce' ); ?></span>
					<span class="input-text-wrap">
						<select class="cfwc_country_of_origin" name="_cfwc_country_of_origin">
							<option value=""><?php esc_html_e( '— Select —', 'customs-fees-for-woocommerce' ); ?></option>
							<?php
							$countries = WC()->countries->get_countries();
							foreach ( $countries as $code => $name ) {
								echo '<option value="' . esc_attr( $code ) . '">' . esc_html( $name ) . '</option>';
							}
							?>
						</select>
					</span>
				</label>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Save quick edit data.
	 *
	 * @since 1.1.0
	 * @param int $post_id Post ID.
	 */
	public function save_quick_edit_data( $post_id ) {
		// Security checks.
		if ( ! current_user_can( 'edit_product', $post_id ) ) {
			return;
		}
		
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce handles nonce for quick edit.
		if ( isset( $_POST['_cfwc_hs_code'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput
			$hs_code = sanitize_text_field( wp_unslash( $_POST['_cfwc_hs_code'] ) );
			
			if ( '' === $hs_code ) {
				delete_post_meta( $post_id, '_cfwc_hs_code' );
			} else {
				update_post_meta( $post_id, '_cfwc_hs_code', $hs_code );
			}
		}
		
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce handles nonce for quick edit.
		if ( isset( $_POST['_cfwc_country_of_origin'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput
			$value = sanitize_text_field( wp_unslash( $_POST['_cfwc_country_of_origin'] ) );
			
			if ( '' === $value ) {
				delete_post_meta( $post_id, '_cfwc_country_of_origin' );
			} else {
				update_post_meta( $post_id, '_cfwc_country_of_origin', $value );
			}
		}
	}

	/**
	 * Add bulk edit fields.
	 *
	 * @since 1.1.0
	 * @param string $column_name Column name.
	 * @param string $post_type Post type.
	 */
	public function add_bulk_edit_fields( $column_name, $post_type ) {
		// Show on any column for products since we don't have our own column anymore.
		if ( 'product' !== $post_type || 'name' !== $column_name ) {
			return;
		}
		?>
		<div class="inline-edit-group">
			<label class="inline-edit-cfwc-hs-code">
				<span class="title"><?php esc_html_e( 'HS Code', 'customs-fees-for-woocommerce' ); ?></span>
				<input type="text" name="_cfwc_hs_code" class="text" placeholder="<?php esc_attr_e( '— No Change —', 'customs-fees-for-woocommerce' ); ?>" />
			</label>
		</div>
		<div class="inline-edit-group">
			<label class="inline-edit-cfwc-origin">
				<span class="title"><?php esc_html_e( 'Country of Origin', 'customs-fees-for-woocommerce' ); ?></span>
				<select class="cfwc_country_of_origin" name="_cfwc_country_of_origin">
					<option value=""><?php esc_html_e( '— No Change —', 'customs-fees-for-woocommerce' ); ?></option>
					<option value="_clear"><?php esc_html_e( '— Clear —', 'customs-fees-for-woocommerce' ); ?></option>
					<?php
					$countries = WC()->countries->get_countries();
					foreach ( $countries as $code => $name ) {
						echo '<option value="' . esc_attr( $code ) . '">' . esc_html( $name ) . '</option>';
					}
					?>
				</select>
			</label>
		</div>
		<?php
	}

	/**
	 * Save bulk edit data.
	 *
	 * @since 1.1.0
	 * @param WC_Product $product Product object.
	 */
	public function save_bulk_edit_data( $product ) {
		$save_needed = false;
		
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WooCommerce handles nonce for bulk edit.
		if ( isset( $_REQUEST['_cfwc_hs_code'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput
			$hs_code = sanitize_text_field( wp_unslash( $_REQUEST['_cfwc_hs_code'] ) );
			
			if ( '_clear' === $hs_code ) {
				$product->delete_meta_data( '_cfwc_hs_code' );
				$save_needed = true;
			} elseif ( '' !== $hs_code && '— No Change —' !== $hs_code ) {
				$product->update_meta_data( '_cfwc_hs_code', $hs_code );
				$save_needed = true;
			}
		}
		
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WooCommerce handles nonce for bulk edit.
		if ( isset( $_REQUEST['_cfwc_country_of_origin'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput
			$value = sanitize_text_field( wp_unslash( $_REQUEST['_cfwc_country_of_origin'] ) );
			
			if ( '_clear' === $value ) {
				$product->delete_meta_data( '_cfwc_country_of_origin' );
				$save_needed = true;
			} elseif ( '' !== $value ) {
				$product->update_meta_data( '_cfwc_country_of_origin', $value );
				$save_needed = true;
			}
		}
		
		if ( $save_needed ) {
			$product->save();
		}
	}

	/**
	 * AJAX handler to get quick edit data.
	 *
	 * @since 1.1.0
	 */
	public function ajax_get_quick_edit_data() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Read-only AJAX operation for Quick Edit data retrieval.
		if ( ! isset( $_POST['product_id'] ) ) {
			wp_send_json_error();
		}
		
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput -- Read-only AJAX operation.
		$product_id = absint( $_POST['product_id'] );
		
		if ( ! current_user_can( 'edit_product', $product_id ) ) {
			wp_send_json_error();
		}
		
		$hs_code = get_post_meta( $product_id, '_cfwc_hs_code', true );
		$country_of_origin = get_post_meta( $product_id, '_cfwc_country_of_origin', true );
		
		wp_send_json_success( array(
			'hs_code' => $hs_code,
			'country_of_origin' => $country_of_origin,
		) );
	}
	
	/**
	 * Render setup status for settings page.
	 *
	 * @since 1.1.0
	 */
	public function render_setup_status() {
		$stats = $this->get_product_stats();
		
		// Only show if products are missing origin.
		if ( $stats['missing'] === 0 ) {
			return;
		}
		
		$products_url = admin_url( 'edit.php?post_type=product' );
		?>
		<div class="cfwc-setup-status notice notice-warning inline" style="margin: 0 0 20px 0;">
			<p>
				<strong><?php esc_html_e( 'Setup Status:', 'customs-fees-for-woocommerce' ); ?></strong>
				<?php
				printf(
					/* translators: %1$d: number of products missing origin, %2$d: total products */
									esc_html__( '%1$d of %2$d products need Country of Origin data.', 'customs-fees-for-woocommerce' ),
				absint( $stats['missing'] ),
				absint( $stats['total'] )
			);
				?>
				<a href="<?php echo esc_url( $products_url ); ?>" style="margin-left: 10px;">
					<?php esc_html_e( 'View Products', 'customs-fees-for-woocommerce' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
}

