<?php
/**
 * Product Variation Support for Customs Fees
 *
 * Adds HS code and origin country fields to product variations
 * following WooCommerce's inheritance pattern.
 *
 * @package CFWC
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CFWC_Products_Variation_Support
 *
 * Handles variation-level customs data with parent fallback.
 */
class CFWC_Products_Variation_Support {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Add fields to variation form.
		add_action( 'woocommerce_variation_options_pricing', array( $this, 'add_variation_fields' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_variation_fields' ), 10, 2 );

		// Add variation data to frontend.
		add_filter( 'woocommerce_available_variation', array( $this, 'add_variation_data' ), 10, 3 );
	}

	/**
	 * Add customs fields to variation edit form.
	 *
	 * @param int     $loop           Position in the loop.
	 * @param array   $variation_data Variation data.
	 * @param WP_Post $variation      Variation post object.
	 */
	public function add_variation_fields( $loop, $variation_data, $variation ) {
		$variation_id = $variation->ID;

		// Get current values (empty if not set, will inherit from parent).
		$hs_code = get_post_meta( $variation_id, '_cfwc_hs_code', true );
		$origin  = get_post_meta( $variation_id, '_cfwc_country_of_origin', true );

		// Get parent values for placeholder.
		$parent_id      = wp_get_post_parent_id( $variation_id );
		$parent_hs_code = get_post_meta( $parent_id, '_cfwc_hs_code', true );
		$parent_origin  = get_post_meta( $parent_id, '_cfwc_country_of_origin', true );

		// WooCommerce 11.3+ adds its own customs fields to each variation. Say which ones the fee uses.
		$wc_has_customs_fields = self::wc_has_product_customs_fields();
		?>
		<div class="cfwc-variation-customs-fields">
			<p class="form-row form-row-first">
				<label for="cfwc_hs_code_<?php echo esc_attr( $loop ); ?>">
					<?php esc_html_e( 'HS Code (Customs Fees)', 'customs-fees-for-woocommerce' ); ?>
					<?php
					echo wp_kses_post(
						wc_help_tip(
							sprintf(
							/* translators: %s: parent HS code */
								__( 'Leave empty to use parent product HS code: %s', 'customs-fees-for-woocommerce' ),
								! empty( $parent_hs_code ) ? $parent_hs_code : __( 'Not set', 'customs-fees-for-woocommerce' )
							) . ( $wc_has_customs_fields ? ' ' . __( 'Customs fees use this field, not the WooCommerce Commodity code field.', 'customs-fees-for-woocommerce' ) : '' )
						)
					);
					?>
				</label>
				<input type="text" 
						id="cfwc_hs_code_<?php echo esc_attr( $loop ); ?>" 
						name="cfwc_hs_code[<?php echo esc_attr( $loop ); ?>]" 
						value="<?php echo esc_attr( $hs_code ); ?>" 
						placeholder="<?php echo esc_attr( $parent_hs_code ); ?>" />
			</p>
			
			<p class="form-row form-row-last">
				<label for="cfwc_country_of_origin_<?php echo esc_attr( $loop ); ?>">
					<?php esc_html_e( 'Country of Origin (Customs Fees)', 'customs-fees-for-woocommerce' ); ?>
					<?php
					$countries = WC()->countries->get_countries();
					echo wp_kses_post(
						wc_help_tip(
							sprintf(
							/* translators: %s: parent country */
								__( 'Leave empty to use parent product origin: %s', 'customs-fees-for-woocommerce' ),
								$parent_origin && isset( $countries[ $parent_origin ] ) ? $countries[ $parent_origin ] : __( 'Not set', 'customs-fees-for-woocommerce' )
							) . ( $wc_has_customs_fields ? ' ' . __( 'Customs fees use this field, not the WooCommerce Country of origin field.', 'customs-fees-for-woocommerce' ) : '' )
						)
					);
					?>
				</label>
				<select id="cfwc_country_of_origin_<?php echo esc_attr( $loop ); ?>"
						name="cfwc_country_of_origin[<?php echo esc_attr( $loop ); ?>]"
						class="wc-enhanced-select cfwc-select2-field">
					<option value=""><?php esc_html_e( 'Use parent product', 'customs-fees-for-woocommerce' ); ?></option>
					<?php
					foreach ( WC()->countries->get_countries() as $code => $country ) {
						printf(
							'<option value="%s" %s>%s</option>',
							esc_attr( $code ),
							selected( $origin, $code, false ),
							esc_html( $country )
						);
					}
					?>
				</select>
			</p>
		</div>
		<?php
	}

	/**
	 * Whether WooCommerce has its own product customs fields (added in WooCommerce 11.3).
	 *
	 * The plugin does not read those fields, so the variation editor says that
	 * only the plugin's fields are used for fees.
	 *
	 * @since 1.3.8
	 * @return bool
	 */
	private static function wc_has_product_customs_fields() {
		return method_exists( 'WC_Product', 'get_customs_country_of_origin' );
	}

	/**
	 * Save variation customs fields.
	 *
	 * @param int $variation_id Variation ID.
	 * @param int $loop         Position in the loop.
	 */
	public function save_variation_fields( $variation_id, $loop ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by WooCommerce

		// Save HS Code.
		if ( isset( $_POST['cfwc_hs_code'][ $loop ] ) ) {
			$hs_code = sanitize_text_field( wp_unslash( $_POST['cfwc_hs_code'][ $loop ] ) );
			if ( ! empty( $hs_code ) ) {
				update_post_meta( $variation_id, '_cfwc_hs_code', $hs_code );
			} else {
				delete_post_meta( $variation_id, '_cfwc_hs_code' );
			}
		}

		// Save Country of Origin.
		if ( isset( $_POST['cfwc_country_of_origin'][ $loop ] ) ) {
			$origin = sanitize_text_field( wp_unslash( $_POST['cfwc_country_of_origin'][ $loop ] ) );
			if ( ! empty( $origin ) ) {
				update_post_meta( $variation_id, '_cfwc_country_of_origin', $origin );
			} else {
				delete_post_meta( $variation_id, '_cfwc_country_of_origin' );
			}
		}

		// phpcs:enable
	}

	/**
	 * Add customs data to variation data for frontend.
	 *
	 * @param array                $variation_data Variation data.
	 * @param WC_Product           $product        Parent product.
	 * @param WC_Product_Variation $variation      Variation product.
	 * @return array Modified variation data.
	 */
	public function add_variation_data( $variation_data, $product, $variation ) {
		// Get variation customs data with parent fallback.
		$hs_code = $this->get_variation_hs_code( $variation );
		$origin  = $this->get_variation_origin( $variation );

		if ( $hs_code ) {
			$variation_data['cfwc_hs_code'] = $hs_code;
		}

		if ( $origin ) {
			$variation_data['cfwc_country_of_origin'] = $origin;
		}

		return $variation_data;
	}

	/**
	 * Get HS code for variation with parent fallback.
	 *
	 * @param WC_Product_Variation $variation Variation product.
	 * @return string HS code or empty string.
	 */
	public function get_variation_hs_code( $variation ) {
		$hs_code = get_post_meta( $variation->get_id(), '_cfwc_hs_code', true );

		// Fallback to parent if not set.
		if ( empty( $hs_code ) && $variation->get_parent_id() ) {
			$hs_code = get_post_meta( $variation->get_parent_id(), '_cfwc_hs_code', true );
		}

		return $hs_code;
	}

	/**
	 * Get origin country for variation with parent fallback.
	 *
	 * @param WC_Product_Variation $variation Variation product.
	 * @return string Country code or empty string.
	 */
	public function get_variation_origin( $variation ) {
		$origin = get_post_meta( $variation->get_id(), '_cfwc_country_of_origin', true );

		// Fallback to parent if not set.
		if ( empty( $origin ) && $variation->get_parent_id() ) {
			$origin = get_post_meta( $variation->get_parent_id(), '_cfwc_country_of_origin', true );
		}

		return $origin;
	}

	/**
	 * Static helper to get customs data with proper fallback.
	 *
	 * @param int|WC_Product $product Product or ID.
	 * @return array Array with 'hs_code' and 'origin' keys.
	 */
	public static function get_product_customs_data( $product ) {
		if ( ! is_object( $product ) ) {
			$product = wc_get_product( $product );
		}

		if ( ! $product ) {
			return array(
				'hs_code' => '',
				'origin'  => '',
			);
		}

		$product_id = $product->get_id();
		$parent_id  = $product->get_parent_id();

		// Get HS code with fallback.
		$hs_code = get_post_meta( $product_id, '_cfwc_hs_code', true );
		if ( empty( $hs_code ) && $parent_id ) {
			$hs_code = get_post_meta( $parent_id, '_cfwc_hs_code', true );
		}

		// Get origin with fallback.
		$origin = get_post_meta( $product_id, '_cfwc_country_of_origin', true );
		if ( empty( $origin ) && $parent_id ) {
			$origin = get_post_meta( $parent_id, '_cfwc_country_of_origin', true );
		}

		return array(
			'hs_code' => $hs_code,
			'origin'  => $origin,
		);
	}
}

// Initialize the class.
new CFWC_Products_Variation_Support();
