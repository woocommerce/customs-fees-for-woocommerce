<?php
/**
 * CSV Export/Import Handler for Customs Fees
 *
 * Handles WooCommerce CSV export and import integration for HS codes and country of origin.
 *
 * @package CustomsFeesWooCommerce
 * @since 1.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CSV Export/Import handler class.
 *
 * @since 1.1.0
 */
class CFWC_Export_Import {

	/**
	 * Constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.1.0
	 */
	private function init_hooks() {
		// Export hooks.
		add_filter( 'woocommerce_product_export_column_names', array( $this, 'add_export_column_names' ), 10 );
		add_filter( 'woocommerce_product_export_product_default_columns', array( $this, 'add_export_columns' ), 10 );
		add_filter( 'woocommerce_product_export_product_column_cfwc_hs_code', array( $this, 'export_hs_code_column' ), 10, 2 );
		add_filter( 'woocommerce_product_export_product_column_cfwc_country_of_origin', array( $this, 'export_country_column' ), 10, 2 );

		// Import hooks.
		add_filter( 'woocommerce_csv_product_import_mapping_options', array( $this, 'add_import_mapping_options' ), 10 );
		add_filter( 'woocommerce_csv_product_import_mapping_default_columns', array( $this, 'add_import_column_mapping' ), 10 );
		add_filter( 'woocommerce_csv_product_import_mapped_columns', array( $this, 'map_legacy_country_of_origin_header' ), 10, 2 );
		add_filter( 'woocommerce_product_import_pre_insert_product_object', array( $this, 'process_import' ), 10, 2 );
	}

	/**
	 * Get the labels of the plugin's CSV columns.
	 *
	 * Used as the export column headers and as the import mapping options.
	 * The "Customs Fees:" prefix keeps them distinct from WooCommerce's own
	 * "Commodity code (HS code)" and "Country of origin" columns (WC 11.3+).
	 * The importer matches headers case-insensitively, so a plain
	 * "Country of Origin" header would collide with WooCommerce's column.
	 *
	 * @since 1.3.8
	 * @return array<string, string> Labels keyed by column ID.
	 */
	private function get_column_labels() {
		return array(
			'cfwc_hs_code'           => __( 'Customs Fees: HS Code', 'customs-fees-for-woocommerce' ),
			'cfwc_country_of_origin' => __( 'Customs Fees: Country of Origin', 'customs-fees-for-woocommerce' ),
		);
	}

	/**
	 * Add column names to export dropdown.
	 *
	 * @since 1.1.0
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_export_column_names( $columns ) {
		return array_merge( (array) $columns, $this->get_column_labels() );
	}

	/**
	 * Add columns to default export columns.
	 *
	 * @since 1.1.0
	 * @param array $columns Default columns.
	 * @return array Modified columns.
	 */
	public function add_export_columns( $columns ) {
		return array_merge( (array) $columns, $this->get_column_labels() );
	}

	/**
	 * Export HS Code column data.
	 *
	 * @since 1.1.0
	 * @param mixed      $value   Column value.
	 * @param WC_Product $product Product object.
	 * @return string HS code value.
	 */
	public function export_hs_code_column( $value, $product ) {
		$hs_code = get_post_meta( $product->get_id(), '_cfwc_hs_code', true );
		return $hs_code ? $hs_code : '';
	}

	/**
	 * Export Country of Origin column data.
	 * Converts country code to full name for better readability.
	 *
	 * @since 1.1.0
	 * @param mixed      $value   Column value.
	 * @param WC_Product $product Product object.
	 * @return string Country name or code.
	 */
	public function export_country_column( $value, $product ) {
		$country_code = get_post_meta( $product->get_id(), '_cfwc_country_of_origin', true );

		if ( empty( $country_code ) ) {
			return '';
		}

		// Get country name from WooCommerce countries list.
		$countries = WC()->countries->get_countries();

		// Return full country name if available, otherwise return the code.
		return isset( $countries[ $country_code ] ) ? $countries[ $country_code ] : $country_code;
	}

	/**
	 * Add import mapping options.
	 *
	 * @since 1.1.0
	 * @param array $options Existing options.
	 * @return array Modified options.
	 */
	public function add_import_mapping_options( $options ) {
		return array_merge( (array) $options, $this->get_column_labels() );
	}

	/**
	 * Add automatic column mapping for import.
	 * Maps various column name variations to our fields.
	 *
	 * A header that is already mapped to another field is left alone. Since
	 * WC 11.3, WooCommerce maps "Country of origin" to its own
	 * customs_country_of_origin field. The importer lowercases headers, so
	 * the plugin's "Country of Origin" alias used to replace that mapping and
	 * send WooCommerce's column into the plugin's field.
	 *
	 * @since 1.1.0
	 * @param array $columns Existing mappings.
	 * @return array Modified mappings.
	 */
	public function add_import_column_mapping( $columns ) {
		$columns = (array) $columns;
		$labels  = $this->get_column_labels();

		$aliases = array(
			// Current export headers, translated and English.
			$labels['cfwc_hs_code']            => 'cfwc_hs_code',
			'Customs Fees: HS Code'            => 'cfwc_hs_code',
			$labels['cfwc_country_of_origin']  => 'cfwc_country_of_origin',
			'Customs Fees: Country of Origin'  => 'cfwc_country_of_origin',

			// Export headers before 1.3.8, and other HS code variations.
			__( 'HS Code', 'customs-fees-for-woocommerce' ) => 'cfwc_hs_code',
			'HS Code'                          => 'cfwc_hs_code',
			'HS/Tariff Code'                   => 'cfwc_hs_code',
			'Tariff Code'                      => 'cfwc_hs_code',
			'HTS Code'                         => 'cfwc_hs_code',
			'Harmonized Code'                  => 'cfwc_hs_code',

			// Export headers before 1.3.8, and other country of origin variations.
			__( 'Country of Origin', 'customs-fees-for-woocommerce' ) => 'cfwc_country_of_origin',
			'Country of Origin'                => 'cfwc_country_of_origin',
			'Origin Country'                   => 'cfwc_country_of_origin',
			'Origin'                           => 'cfwc_country_of_origin',
			'Made in'                          => 'cfwc_country_of_origin',
			'Country of Manufacture'           => 'cfwc_country_of_origin',
			'Manufacturing Country'            => 'cfwc_country_of_origin',
		);

		// Headers that are already mapped, keyed the way the importer matches them.
		$mapped = array();
		foreach ( $columns as $header => $field ) {
			$mapped[ strtolower( (string) $header ) ] = $field;
		}

		foreach ( $aliases as $header => $field ) {
			$key = strtolower( (string) $header );

			if ( isset( $mapped[ $key ] ) && $mapped[ $key ] !== $field ) {
				continue;
			}

			$columns[ $header ] = $field;
			$mapped[ $key ]     = $field;
		}

		return $columns;
	}

	/**
	 * Map the plugin's legacy "Country of Origin" export header to the plugin's field.
	 *
	 * Before 1.3.8 the plugin exported its origin as "Country of Origin", with
	 * country names as values. On WC 11.3+ the importer matches that header
	 * case-insensitively to WooCommerce's "Country of origin" column, so files
	 * exported by older versions would fill WooCommerce's field instead, and
	 * WooCommerce rejects rows that hold a country name there.
	 *
	 * WooCommerce writes "Country of origin" (lowercase "o"), so only an exact,
	 * case-sensitive match of the plugin's legacy header is mapped back. Any
	 * other spelling keeps WooCommerce's mapping.
	 *
	 * @since 1.3.8
	 * @param array $headers     Mapped fields, keyed by column index or raw header.
	 * @param array $raw_headers Raw CSV headers.
	 * @return array Mapped fields.
	 */
	public function map_legacy_country_of_origin_header( $headers, $raw_headers ) {
		if ( ! is_array( $headers ) || ! is_array( $raw_headers ) ) {
			return $headers;
		}

		$legacy_headers = array(
			'Country of Origin',
			__( 'Country of Origin', 'customs-fees-for-woocommerce' ),
		);

		foreach ( $raw_headers as $key => $raw_header ) {
			if ( ! in_array( $raw_header, $legacy_headers, true ) ) {
				continue;
			}

			// auto_map_columns() keys the result by column index or by raw header.
			$index = array_key_exists( $key, $headers ) ? $key : $raw_header;

			if ( isset( $headers[ $index ] ) && 'customs_country_of_origin' === $headers[ $index ] ) {
				$headers[ $index ] = 'cfwc_country_of_origin';
			}
		}

		return $headers;
	}

	/**
	 * Process imported data.
	 * Saves HS code and country of origin to product meta.
	 *
	 * @since 1.1.0
	 * @param WC_Product $product Product being imported.
	 * @param array      $data    CSV data for the product.
	 * @return WC_Product Modified product object.
	 */
	public function process_import( $product, $data ) {
		// Process HS Code.
		if ( isset( $data['cfwc_hs_code'] ) ) {
			$hs_code = sanitize_text_field( $data['cfwc_hs_code'] );
			if ( ! empty( $hs_code ) ) {
				$product->update_meta_data( '_cfwc_hs_code', $hs_code );
			} else {
				// Clear the meta if empty value provided.
				$product->delete_meta_data( '_cfwc_hs_code' );
			}
		}

		// Process Country of Origin.
		if ( isset( $data['cfwc_country_of_origin'] ) ) {
			$country_value = trim( $data['cfwc_country_of_origin'] );

			if ( ! empty( $country_value ) ) {
				// Convert country name to code if necessary.
				$country_code = $this->convert_country_to_code( $country_value );

				if ( $country_code ) {
					$product->update_meta_data( '_cfwc_country_of_origin', $country_code );
				}
			} else {
				// Clear the meta if empty value provided.
				$product->delete_meta_data( '_cfwc_country_of_origin' );
			}
		}

		return $product;
	}

	/**
	 * Convert country name or variation to country code.
	 * Handles various formats: full names, codes, common variations.
	 *
	 * @since 1.1.0
	 * @param string $country_value Country name or code from CSV.
	 * @return string|false Country code or false if not found.
	 */
	private function convert_country_to_code( $country_value ) {
		// Clean up the input.
		$country_value = trim( $country_value );

		if ( empty( $country_value ) ) {
			return false;
		}

		// Get WooCommerce countries list.
		$countries = WC()->countries->get_countries();

		// First, check if it's already a valid country code.
		$upper_value = strtoupper( $country_value );
		if ( strlen( $upper_value ) === 2 && isset( $countries[ $upper_value ] ) ) {
			return $upper_value;
		}

		// Try to find by country name (case-insensitive).
		foreach ( $countries as $code => $name ) {
			if ( strcasecmp( $name, $country_value ) === 0 ) {
				return $code;
			}
		}

		// Common country name variations and abbreviations.
		$country_aliases = array(
			// United States variations.
			'USA'                         => 'US',
			'U.S.A.'                      => 'US',
			'U.S.'                        => 'US',
			'United States of America'    => 'US',
			'America'                     => 'US',

			// United Kingdom variations.
			'UK'                          => 'GB',
			'U.K.'                        => 'GB',
			'United Kingdom'              => 'GB',
			'Great Britain'               => 'GB',
			'England'                     => 'GB',
			'Britain'                     => 'GB',

			// China variations.
			'PRC'                         => 'CN',
			'P.R.C.'                      => 'CN',
			'People\'s Republic of China' => 'CN',
			'Mainland China'              => 'CN',

			// Other common variations.
			'Nederland'                   => 'NL',
			'Holland'                     => 'NL',
			'Deutschland'                 => 'DE',
			'Nippon'                      => 'JP',
			'ROK'                         => 'KR',
			'Republic of Korea'           => 'KR',
			'South Korea'                 => 'KR',
			'Czech Republic'              => 'CZ',
			'Czechia'                     => 'CZ',
			'UAE'                         => 'AE',
			'U.A.E.'                      => 'AE',
			'United Arab Emirates'        => 'AE',
		);

		// Check aliases (case-insensitive).
		foreach ( $country_aliases as $alias => $code ) {
			if ( strcasecmp( $alias, $country_value ) === 0 ) {
				// Verify the code exists in WooCommerce countries.
				if ( isset( $countries[ $code ] ) ) {
					return $code;
				}
			}
		}

		// Try partial matching as last resort (be careful with this).
		foreach ( $countries as $code => $name ) {
			// Check if the input contains the country name or vice versa.
			if ( stripos( $name, $country_value ) !== false || stripos( $country_value, $name ) !== false ) {
				// Only match if it's a significant portion (more than 70% of the string).
				$similarity = similar_text( strtolower( $name ), strtolower( $country_value ), $percent );
				if ( $percent > 70 ) {
					return $code;
				}
			}
		}

		// If we can't match, return the original value if it's 2 characters.
		// This allows for codes that might not be in WooCommerce's list.
		if ( strlen( $country_value ) === 2 ) {
			return strtoupper( $country_value );
		}

		// Log unmatched country for debugging.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug only
			error_log( sprintf( 'CFWC Import: Could not match country "%s" to a country code', $country_value ) );
		}

		return false;
	}
}

// Initialize the class.
new CFWC_Export_Import();
