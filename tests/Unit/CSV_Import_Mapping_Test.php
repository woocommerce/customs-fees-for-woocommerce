<?php
/**
 * Unit tests for the product CSV import/export column mapping.
 *
 * Covers CUSFEES-48: WooCommerce 11.3 maps the "Country of origin" header to
 * its own customs_country_of_origin field. The importer lowercases headers,
 * so the plugin's "Country of Origin" alias replaced core's mapping: the value
 * went into _cfwc_country_of_origin and WooCommerce's field stayed empty.
 * Files exported by older plugin versions, with the exact header
 * "Country of Origin", must still fill the plugin's field.
 *
 * The test environment runs a WooCommerce version without the core customs
 * columns, so core's WC 11.3 mappings are added with a filter that runs before
 * the plugin's. That is the order in which they reach the plugin on WC 11.3,
 * where they are part of the default columns passed to the filter.
 *
 * @package Customs_Fees_For_WooCommerce
 */

declare( strict_types=1 );

namespace WooCommerce\CustomsFees\Tests\Unit;

/**
 * @covers \CFWC_Export_Import::add_import_column_mapping
 * @covers \CFWC_Export_Import::map_legacy_country_of_origin_header
 * @covers \CFWC_Export_Import::add_import_mapping_options
 * @covers \CFWC_Export_Import::add_export_column_names
 * @covers \CFWC_Export_Import::add_export_columns
 */
class CSV_Import_Mapping_Test extends \WC_Unit_Test_Case {

	/**
	 * Temporary CSV files to delete after each test.
	 *
	 * @var string[]
	 */
	private $csv_files = array();

	/**
	 * Load the importer classes and register the plugin's hooks.
	 *
	 * The plugin loads CFWC_Export_Import only in admin requests, so it is not
	 * loaded in the test run. Hooks added here are removed by the base class
	 * tearDown().
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'CFWC_Export_Import' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/class-cfwc-export-import.php';
		} else {
			new \CFWC_Export_Import();
		}

		require_once WC_ABSPATH . 'includes/admin/importers/class-wc-product-csv-importer-controller.php';
		require_once WC_ABSPATH . 'includes/import/class-wc-product-csv-importer.php';
	}

	/**
	 * Delete the temporary CSV files.
	 */
	public function tearDown(): void {
		foreach ( $this->csv_files as $file ) {
			if ( file_exists( $file ) ) {
				unlink( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- test cleanup.
			}
		}
		$this->csv_files = array();

		parent::tearDown();
	}

	/**
	 * Add WooCommerce 11.3's core customs columns to the default mapping.
	 */
	private function simulate_wc_113_core_columns(): void {
		add_filter(
			'woocommerce_csv_product_import_mapping_default_columns',
			static function ( $columns ) {
				$columns['Commodity code (HS code)'] = 'customs_commodity_code';
				$columns['Country of origin']        = 'customs_country_of_origin';
				$columns['Customs description']      = 'customs_description';
				return $columns;
			},
			1
		);
	}

	/**
	 * Run WooCommerce's real column auto-mapping for a list of headers.
	 *
	 * @param string[] $headers CSV headers.
	 * @return array<string, string> Mapped field keyed by header.
	 */
	private function auto_map( array $headers ): array {
		$controller = new class() extends \WC_Product_CSV_Importer_Controller {
			/**
			 * Skip the admin page setup done by the parent constructor.
			 */
			public function __construct() {}

			/**
			 * Expose the protected auto-mapping.
			 *
			 * @param string[] $headers CSV headers.
			 * @return array Mapped fields.
			 */
			public function map( array $headers ): array {
				return $this->auto_map_columns( $headers, false );
			}
		};

		return $controller->map( $headers );
	}

	/**
	 * Write a CSV file and import it with WooCommerce's real importer.
	 *
	 * @param string[]   $headers CSV headers.
	 * @param string[][] $rows    CSV rows.
	 */
	private function import_csv( array $headers, array $rows ): void {
		$file = wp_tempnam( 'cfwc-import' ) . '.csv';
		$fh   = fopen( $file, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- test fixture.
		fputcsv( $fh, $headers, ',', '"', '\\' );
		foreach ( $rows as $row ) {
			fputcsv( $fh, $row, ',', '"', '\\' );
		}
		fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- test fixture.
		$this->csv_files[] = $file;

		$importer = new \WC_Product_CSV_Importer(
			$file,
			array(
				'mapping'         => array(
					'from' => $headers,
					'to'   => array_values( $this->auto_map( $headers ) ),
				),
				'parse'           => true,
				'update_existing' => true,
			)
		);
		$result   = $importer->import();

		$this->assertSame( array(), $result['failed'], 'The CSV row was not imported.' );
	}

	/**
	 * @testdox On WC 11.3, WooCommerce's "Country of origin" column keeps its core mapping.
	 */
	public function test_core_country_of_origin_header_is_not_claimed(): void {
		$this->simulate_wc_113_core_columns();

		$map = $this->auto_map( array( 'Country of origin', 'Commodity code (HS code)', 'Customs description' ) );

		$this->assertSame(
			array(
				'Country of origin'        => 'customs_country_of_origin',
				'Commodity code (HS code)' => 'customs_commodity_code',
				'Customs description'      => 'customs_description',
			),
			$map
		);
	}

	/**
	 * @testdox On WC 11.3, other spellings of core's header are not claimed, except the plugin's exact legacy export header.
	 */
	public function test_core_header_in_other_case_is_not_claimed(): void {
		$this->simulate_wc_113_core_columns();

		$map = $this->auto_map( array( 'country of origin', 'COUNTRY OF ORIGIN', 'Country Of Origin', 'Country of Origin' ) );

		$this->assertSame(
			array(
				'country of origin' => 'customs_country_of_origin',
				'COUNTRY OF ORIGIN' => 'customs_country_of_origin',
				'Country Of Origin' => 'customs_country_of_origin',
				'Country of Origin' => 'cfwc_country_of_origin',
			),
			$map
		);
	}

	/**
	 * @testdox On WC 11.3, a file with both core's header and the plugin's legacy header maps each to its own field.
	 */
	public function test_pre_fix_wc_113_export_headers_map_to_their_own_fields(): void {
		$this->simulate_wc_113_core_columns();

		// Headers of a WC 11.3 export made before this fix.
		$map = $this->auto_map( array( 'Commodity code (HS code)', 'Country of origin', 'HS Code', 'Country of Origin' ) );

		$this->assertSame(
			array(
				'Commodity code (HS code)' => 'customs_commodity_code',
				'Country of origin'        => 'customs_country_of_origin',
				'HS Code'                  => 'cfwc_hs_code',
				'Country of Origin'        => 'cfwc_country_of_origin',
			),
			$map
		);
	}

	/**
	 * @testdox The legacy header is mapped back with numeric column indexes too, as on the mapping screen.
	 */
	public function test_legacy_header_mapping_with_numeric_indexes(): void {
		$this->simulate_wc_113_core_columns();

		$headers = array( 'Country of origin', 'Country of Origin' );
		$mapped  = apply_filters(
			'woocommerce_csv_product_import_mapped_columns',
			array( 'customs_country_of_origin', 'customs_country_of_origin' ),
			$headers
		);

		$this->assertSame( array( 'customs_country_of_origin', 'cfwc_country_of_origin' ), $mapped );
	}

	/**
	 * @testdox On WC 11.3, a legacy plugin export with country names still updates the plugin's origin.
	 */
	public function test_importing_legacy_plugin_export_updates_plugin_origin(): void {
		$this->simulate_wc_113_core_columns();

		$product = \WC_Helper_Product::create_simple_product();
		$product->update_meta_data( '_cfwc_country_of_origin', 'CN' );
		$product->update_meta_data( '_cfwc_hs_code', '6201.40' );
		$product->save();

		$this->import_csv(
			array( 'ID', 'HS Code', 'Country of Origin' ),
			array( array( (string) $product->get_id(), '6109.10', 'Japan' ) )
		);

		$product = wc_get_product( $product->get_id() );
		$this->assertSame( 'JP', $product->get_meta( '_cfwc_country_of_origin' ) );
		$this->assertSame( '6109.10', $product->get_meta( '_cfwc_hs_code' ) );
	}

	/**
	 * @testdox On WC 11.3, the plugin's own export headers map to the plugin's fields.
	 */
	public function test_plugin_headers_map_to_plugin_fields_next_to_core_columns(): void {
		$this->simulate_wc_113_core_columns();

		$map = $this->auto_map(
			array(
				'Commodity code (HS code)',
				'Country of origin',
				'Customs Fees: HS Code',
				'Customs Fees: Country of Origin',
				'HS Code',
				'Made in',
			)
		);

		$this->assertSame(
			array(
				'Commodity code (HS code)'        => 'customs_commodity_code',
				'Country of origin'               => 'customs_country_of_origin',
				'Customs Fees: HS Code'           => 'cfwc_hs_code',
				'Customs Fees: Country of Origin' => 'cfwc_country_of_origin',
				'HS Code'                         => 'cfwc_hs_code',
				'Made in'                         => 'cfwc_country_of_origin',
			),
			$map
		);
	}

	/**
	 * @testdox Without core customs columns (WC 11.2 and earlier), legacy headers still map to the plugin's fields.
	 */
	public function test_legacy_headers_map_to_plugin_fields_without_core_columns(): void {
		$map = $this->auto_map( array( 'HS Code', 'hs code', 'Country of Origin', 'country of origin', 'Origin' ) );

		$this->assertSame(
			array(
				'HS Code'           => 'cfwc_hs_code',
				'hs code'           => 'cfwc_hs_code',
				'Country of Origin' => 'cfwc_country_of_origin',
				'country of origin' => 'cfwc_country_of_origin',
				'Origin'            => 'cfwc_country_of_origin',
			),
			$map
		);
	}

	/**
	 * @testdox A header that another extension already maps keeps that mapping.
	 */
	public function test_header_mapped_by_another_extension_is_not_claimed(): void {
		add_filter(
			'woocommerce_csv_product_import_mapping_default_columns',
			static function ( $columns ) {
				$columns['origin'] = 'other_extension_origin';
				return $columns;
			},
			5
		);

		$map = $this->auto_map( array( 'Origin', 'Made in' ) );

		$this->assertSame(
			array(
				'Origin'  => 'other_extension_origin',
				'Made in' => 'cfwc_country_of_origin',
			),
			$map
		);
	}

	/**
	 * @testdox The plugin's export headers and mapping options differ from WC 11.3's core headers, ignoring case.
	 */
	public function test_plugin_labels_do_not_collide_with_core_headers(): void {
		$core_headers = array( 'commodity code (hs code)', 'country of origin', 'customs description' );

		$export_names   = apply_filters( 'woocommerce_product_export_column_names', array() );
		$export_columns = apply_filters( 'woocommerce_product_export_product_default_columns', array() );
		$options        = apply_filters( 'woocommerce_csv_product_import_mapping_options', array(), array() );

		foreach ( array( $export_names, $export_columns, $options ) as $labels ) {
			$this->assertArrayHasKey( 'cfwc_hs_code', $labels );
			$this->assertArrayHasKey( 'cfwc_country_of_origin', $labels );

			foreach ( array( 'cfwc_hs_code', 'cfwc_country_of_origin' ) as $field ) {
				$this->assertNotContains( strtolower( $labels[ $field ] ), $core_headers, "Label for {$field} collides with a core header." );
			}
		}
	}

	/**
	 * @testdox On WC 11.3, importing WooCommerce's "Country of origin" column leaves the plugin's origin unchanged.
	 */
	public function test_importing_core_column_keeps_plugin_origin(): void {
		$this->simulate_wc_113_core_columns();

		$product = \WC_Helper_Product::create_simple_product();
		$product->update_meta_data( '_cfwc_country_of_origin', 'CN' );
		$product->update_meta_data( '_cfwc_hs_code', '6201.40' );
		$product->save();

		$this->import_csv(
			array( 'ID', 'Country of origin', 'Commodity code (HS code)' ),
			array( array( (string) $product->get_id(), 'DE', '9999.99' ) )
		);

		$product = wc_get_product( $product->get_id() );
		$this->assertSame( 'CN', $product->get_meta( '_cfwc_country_of_origin' ) );
		$this->assertSame( '6201.40', $product->get_meta( '_cfwc_hs_code' ) );
	}

	/**
	 * @testdox On WC 11.3, the plugin's own columns still update the plugin's origin and HS code.
	 */
	public function test_importing_plugin_columns_updates_plugin_meta(): void {
		$this->simulate_wc_113_core_columns();

		$product = \WC_Helper_Product::create_simple_product();
		$product->update_meta_data( '_cfwc_country_of_origin', 'CN' );
		$product->update_meta_data( '_cfwc_hs_code', '6201.40' );
		$product->save();

		$this->import_csv(
			array( 'ID', 'Country of origin', 'Customs Fees: Country of Origin', 'Customs Fees: HS Code' ),
			array( array( (string) $product->get_id(), 'FR', 'Germany', '6109.10' ) )
		);

		$product = wc_get_product( $product->get_id() );
		$this->assertSame( 'DE', $product->get_meta( '_cfwc_country_of_origin' ) );
		$this->assertSame( '6109.10', $product->get_meta( '_cfwc_hs_code' ) );
	}
}
