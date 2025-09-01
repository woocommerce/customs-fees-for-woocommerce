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
		add_filter( 'woocommerce_product_import_pre_insert_product_object', array( $this, 'process_import' ), 10, 2 );
	}

	/**
	 * Add column names to export dropdown.
	 *
	 * @since 1.1.0
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_export_column_names( $columns ) {
		$columns['cfwc_hs_code']            = __( 'HS Code', 'customs-fees-for-woocommerce' );
		$columns['cfwc_country_of_origin']  = __( 'Country of Origin', 'customs-fees-for-woocommerce' );
		return $columns;
	}

	/**
	 * Add columns to default export columns.
	 *
	 * @since 1.1.0
	 * @param array $columns Default columns.
	 * @return array Modified columns.
	 */
	public function add_export_columns( $columns ) {
		$columns['cfwc_hs_code']            = __( 'HS Code', 'customs-fees-for-woocommerce' );
		$columns['cfwc_country_of_origin']  = __( 'Country of Origin', 'customs-fees-for-woocommerce' );
		return $columns;
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
		$options['cfwc_hs_code']            = __( 'HS Code', 'customs-fees-for-woocommerce' );
		$options['cfwc_country_of_origin']  = __( 'Country of Origin', 'customs-fees-for-woocommerce' );
		return $options;
	}

	/**
	 * Add automatic column mapping for import.
	 * Maps various column name variations to our fields.
	 *
	 * @since 1.1.0
	 * @param array $columns Existing mappings.
	 * @return array Modified mappings.
	 */
	public function add_import_column_mapping( $columns ) {
		// HS Code variations.
		$columns[ __( 'HS Code', 'customs-fees-for-woocommerce' ) ]        = 'cfwc_hs_code';
		$columns['HS Code']                                                 = 'cfwc_hs_code';
		$columns['hs code']                                                 = 'cfwc_hs_code';
		$columns['HS/Tariff Code']                                         = 'cfwc_hs_code';
		$columns['Tariff Code']                                            = 'cfwc_hs_code';
		$columns['HTS Code']                                               = 'cfwc_hs_code';
		$columns['Harmonized Code']                                        = 'cfwc_hs_code';
		
		// Country of Origin variations.
		$columns[ __( 'Country of Origin', 'customs-fees-for-woocommerce' ) ] = 'cfwc_country_of_origin';
		$columns['Country of Origin']                                         = 'cfwc_country_of_origin';
		$columns['country of origin']                                         = 'cfwc_country_of_origin';
		$columns['Origin Country']                                            = 'cfwc_country_of_origin';
		$columns['Origin']                                                   = 'cfwc_country_of_origin';
		$columns['Made in']                                                  = 'cfwc_country_of_origin';
		$columns['Country of Manufacture']                                   = 'cfwc_country_of_origin';
		$columns['Manufacturing Country']                                    = 'cfwc_country_of_origin';
		
		return $columns;
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
			'USA'                        => 'US',
			'U.S.A.'                    => 'US',
			'U.S.'                      => 'US',
			'United States of America'  => 'US',
			'America'                   => 'US',
			
			// United Kingdom variations.
			'UK'                        => 'GB',
			'U.K.'                      => 'GB',
			'United Kingdom'            => 'GB',
			'Great Britain'             => 'GB',
			'England'                   => 'GB',
			'Britain'                   => 'GB',
			
			// China variations.
			'PRC'                       => 'CN',
			'P.R.C.'                   => 'CN',
			'People\'s Republic of China' => 'CN',
			'Mainland China'            => 'CN',
			
			// Other common variations.
			'Nederland'                 => 'NL',
			'Holland'                   => 'NL',
			'Deutschland'               => 'DE',
			'Nippon'                    => 'JP',
			'ROK'                       => 'KR',
			'Republic of Korea'         => 'KR',
			'South Korea'               => 'KR',
			'Czech Republic'            => 'CZ',
			'Czechia'                   => 'CZ',
			'UAE'                       => 'AE',
			'U.A.E.'                   => 'AE',
			'United Arab Emirates'      => 'AE',
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
