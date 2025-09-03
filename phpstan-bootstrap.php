<?php
/**
 * PHPStan Bootstrap File for Customs Fees for WooCommerce
 *
 * This file defines plugin constants for PHPStan analysis.
 * The WordPress stubs are automatically loaded by szepeviktor/phpstan-wordpress.
 *
 * @package CustomsFeesForWooCommerce
 */

// Prevent direct access.
if ( ! defined( 'PHPSTAN_RUNNING' ) ) {
	define( 'PHPSTAN_RUNNING', true );
}

// Define WordPress constants if not already defined.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( dirname( dirname( __DIR__ ) ) ) . '/' );
}

// Define plugin constants used throughout the plugin.
if ( ! defined( 'CFWC_VERSION' ) ) {
	define( 'CFWC_VERSION', '1.0.0' );
}

if ( ! defined( 'CFWC_PLUGIN_FILE' ) ) {
	define( 'CFWC_PLUGIN_FILE', __DIR__ . '/customs-fees-for-woocommerce.php' );
}

if ( ! defined( 'CFWC_PLUGIN_DIR' ) ) {
	define( 'CFWC_PLUGIN_DIR', __DIR__ . '/' );
}

if ( ! defined( 'CFWC_PLUGIN_URL' ) ) {
	// Provide a default URL for PHPStan analysis.
	define( 'CFWC_PLUGIN_URL', 'https://example.com/wp-content/plugins/customs-fees-for-woocommerce/' );
}

// WooCommerce constants are defined by the WooCommerce stubs file.
