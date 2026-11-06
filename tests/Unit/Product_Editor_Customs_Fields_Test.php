<?php
/**
 * Unit tests for the plugin's customs fields in the product and variation editors.
 *
 * WooCommerce 11.3 adds its own Commodity code and Country of origin fields to
 * the product Shipping tab and to each variation. The fee uses only the
 * plugin's fields, so the plugin labels them "(Customs Fees)" and, when core's
 * fields exist, says that core's fields are not used for fees.
 *
 * @package Customs_Fees_For_WooCommerce
 */

declare( strict_types=1 );

namespace WooCommerce\CustomsFees\Tests\Unit;

/**
 * @covers \CFWC_Admin::add_product_fields
 * @covers \CFWC_Products_Variation_Support::add_variation_fields
 */
class Product_Editor_Customs_Fields_Test extends \WC_Unit_Test_Case {

	/**
	 * Note shown above the product fields when WooCommerce has its own customs fields.
	 */
	private const PRODUCT_NOTE = 'The Commodity code and Country of origin fields in the Shipping tab are not used for fees.';

	/**
	 * Load the admin class and WooCommerce's admin field helpers
	 * (woocommerce_wp_text_input() and friends). Both load only in wp-admin.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		require_once WC_ABSPATH . 'includes/admin/wc-meta-box-functions.php';
		require_once dirname( __DIR__, 2 ) . '/includes/admin/class-cfwc-admin.php';
	}

	/**
	 * Restore the global post after each test.
	 */
	public function tearDown(): void {
		unset( $GLOBALS['post'] );
		parent::tearDown();
	}

	/**
	 * Whether the running WooCommerce version has core product customs fields.
	 *
	 * The test is independent of the code under test: it checks the getter
	 * WooCommerce 11.3 introduced, not the plugin's own helper.
	 *
	 * @return bool
	 */
	private function wc_has_customs_fields(): bool {
		return method_exists( \WC_Product::class, 'get_customs_commodity_code' );
	}

	/**
	 * Render the product editor fields for a product.
	 *
	 * @param int $product_id Product ID.
	 * @return string HTML.
	 */
	private function render_product_fields( int $product_id ): string {
		$GLOBALS['post'] = get_post( $product_id );

		$admin = new \CFWC_Admin();
		ob_start();
		$admin->add_product_fields();
		return (string) ob_get_clean();
	}

	/**
	 * Render the variation editor fields for a variation.
	 *
	 * @param int $variation_id Variation ID.
	 * @return string HTML.
	 */
	private function render_variation_fields( int $variation_id ): string {
		$support = new \CFWC_Products_Variation_Support();
		ob_start();
		$support->add_variation_fields( 0, array(), get_post( $variation_id ) );
		return (string) ob_get_clean();
	}

	/**
	 * The product editor labels both fields as the plugin's.
	 */
	public function test_product_fields_are_labelled_customs_fees(): void {
		$product = \WC_Helper_Product::create_simple_product();

		$html = $this->render_product_fields( $product->get_id() );

		$this->assertStringContainsString( '<label for="_cfwc_hs_code">HS/Tariff Code (Customs Fees)</label>', $html );
		$this->assertStringContainsString( '<label for="_cfwc_country_of_origin">Country of Origin (Customs Fees)</label>', $html );
	}

	/**
	 * The product editor says which fields the fee uses only when WooCommerce has its own.
	 */
	public function test_product_note_matches_core_customs_fields(): void {
		$product = \WC_Helper_Product::create_simple_product();

		$html = $this->render_product_fields( $product->get_id() );

		if ( $this->wc_has_customs_fields() ) {
			$this->assertStringContainsString( self::PRODUCT_NOTE, $html );
		} else {
			$this->assertStringNotContainsString( self::PRODUCT_NOTE, $html );
			$this->assertStringNotContainsString( 'cfwc-product-fields-note', $html );
		}
	}

	/**
	 * The product field names, and so the saved meta keys, do not change.
	 */
	public function test_product_field_names_are_unchanged(): void {
		$product = \WC_Helper_Product::create_simple_product();

		$html = $this->render_product_fields( $product->get_id() );

		$this->assertStringContainsString( 'name="_cfwc_hs_code"', $html );
		$this->assertStringContainsString( 'name="_cfwc_country_of_origin"', $html );
	}

	/**
	 * The variation editor labels both fields as the plugin's, keeps the field
	 * names, and adds the core-field sentence to the help tips only when
	 * WooCommerce has its own fields.
	 */
	public function test_variation_fields_labels_and_help_tips(): void {
		$variable   = \WC_Helper_Product::create_variation_product();
		$variations = $variable->get_children();
		update_post_meta( $variable->get_id(), '_cfwc_hs_code', '6201.40' );
		update_post_meta( $variable->get_id(), '_cfwc_country_of_origin', 'CN' );

		$html = $this->render_variation_fields( (int) $variations[0] );

		$this->assertStringContainsString( 'HS Code (Customs Fees)', $html );
		$this->assertStringContainsString( 'Country of Origin (Customs Fees)', $html );
		$this->assertStringContainsString( 'name="cfwc_hs_code[0]"', $html );
		$this->assertStringContainsString( 'name="cfwc_country_of_origin[0]"', $html );

		// The parent-inheritance help text is kept.
		$this->assertStringContainsString( 'Leave empty to use parent product HS code: 6201.40', $html );
		$this->assertStringContainsString( 'Leave empty to use parent product origin: China', $html );

		$hs_sentence     = 'Customs fees use this field, not the WooCommerce Commodity code field.';
		$origin_sentence = 'Customs fees use this field, not the WooCommerce Country of origin field.';
		if ( $this->wc_has_customs_fields() ) {
			$this->assertStringContainsString( $hs_sentence, $html );
			$this->assertStringContainsString( $origin_sentence, $html );
		} else {
			$this->assertStringNotContainsString( $hs_sentence, $html );
			$this->assertStringNotContainsString( $origin_sentence, $html );
		}
	}
}
