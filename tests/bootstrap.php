<?php
/**
 * PHPUnit bootstrap for Customs Fees for WooCommerce unit tests.
 *
 * Mirrors the ShipStation extension setup: it loads WooCommerce's legacy test
 * bootstrap (which boots WordPress + WooCommerce against the throwaway test DB),
 * then the plugin under test. Unlike ShipStation, the plugin has no file-scope
 * side effects, so the whole plugin is loaded normally — its loader is gated
 * behind is_woocommerce_active(), which reads the active_plugins option, so we
 * mark WooCommerce active for the run via the active_plugins filter.
 *
 * Run with:  composer test   (which calls tests/bin/run-tests.sh)
 *
 * @package Customs_Fees_For_WooCommerce
 */

$wc_tmp_root = rtrim( sys_get_temp_dir(), '/\\' ) . '/woocommerce/plugins/woocommerce';

if ( false !== getenv( 'WC_DEVELOP_DIR' ) ) {
	$wc_root = getenv( 'WC_DEVELOP_DIR' );
} elseif ( file_exists( $wc_tmp_root . '/tests/legacy/bootstrap.php' ) ) {
	$wc_root = $wc_tmp_root;
} else {
	// exit() with a string argument prints it and exits 0 — a broken bootstrap
	// must not report green, so write to STDERR and exit non-zero.
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI bootstrap error before WP loads; WP_Filesystem unavailable.
	fwrite( STDERR, "Could not find WC test root at {$wc_tmp_root}. Run tests/bin/install-wc-tests.sh, or set WC_DEVELOP_DIR to a WooCommerce checkout (plugins/woocommerce)." . PHP_EOL );
	exit( 1 );
}

$env_wp_tests_dir = getenv( 'WP_TESTS_DIR' );
$wp_tests_dir     = $env_wp_tests_dir ? $env_wp_tests_dir : rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';

if ( ! file_exists( $wp_tests_dir . '/includes/functions.php' ) ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI bootstrap error before WP loads; WP_Filesystem unavailable.
	fwrite( STDERR, "Could not find {$wp_tests_dir}/includes/functions.php. Run tests/bin/install-wc-tests.sh first." . PHP_EOL );
	exit( 1 );
}

// Use this plugin's own wp-tests-config.php, written by tests/bin/run-tests.sh
// with this plugin's DB_HOST. The copy in the shared WP test library is
// rewritten by other plugins' setups, so a run that read it could connect to,
// and wipe, another plugin's test database.
if ( ! defined( 'WP_TESTS_CONFIG_FILE_PATH' ) ) {
	$plugin_wp_tests_config = getenv( 'WP_TESTS_CONFIG_FILE_PATH' );
	if ( $plugin_wp_tests_config && ! is_readable( $plugin_wp_tests_config ) ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI bootstrap error before WP loads; WP_Filesystem unavailable.
		fwrite( STDERR, "WP_TESTS_CONFIG_FILE_PATH points to {$plugin_wp_tests_config}, which cannot be read. Run composer test." . PHP_EOL );
		exit( 1 );
	}
	if ( ! $plugin_wp_tests_config ) {
		// vendor/bin/phpunit run by hand: use the file the last `composer test` wrote.
		$plugin_wp_tests_config = __DIR__ . '/.wp-tests-config.php';
	}
	if ( is_readable( $plugin_wp_tests_config ) ) {
		define( 'WP_TESTS_CONFIG_FILE_PATH', $plugin_wp_tests_config );
	}
	unset( $plugin_wp_tests_config );
}

// Give access to tests_add_filter().
require_once $wp_tests_dir . '/includes/functions.php';

if ( ! defined( 'WC_UNIT_TESTING' ) ) {
	define( 'WC_UNIT_TESTING', true );
}

/**
 * Load the plugin under test once WordPress reaches muplugins_loaded.
 *
 * WooCommerce itself is loaded by WC's legacy bootstrap; this only needs to
 * pull in the plugin's main file, which registers the plugins_loaded init hook.
 */
function _cfwc_manually_load_plugin() {
	require dirname( __DIR__ ) . '/customs-fees-for-woocommerce.php';
}
tests_add_filter( 'muplugins_loaded', '_cfwc_manually_load_plugin' );

/**
 * Mark WooCommerce active so the plugin's is_woocommerce_active() gate passes.
 *
 * The test database has an empty active_plugins option, so without this the
 * plugin's loader never runs and the CFWC_* classes never load.
 *
 * @param mixed $plugins Active plugins list.
 * @return array Active plugins including WooCommerce.
 */
function _cfwc_force_woocommerce_active( $plugins ) {
	$plugins = (array) $plugins;
	if ( ! in_array( 'woocommerce/woocommerce.php', $plugins, true ) ) {
		$plugins[] = 'woocommerce/woocommerce.php';
	}
	return $plugins;
}
tests_add_filter( 'active_plugins', '_cfwc_force_woocommerce_active' );

// Load WC's legacy bootstrap (boots WordPress + WooCommerce).
require $wc_root . '/tests/legacy/bootstrap.php';

// Composer autoloader for the PSR-4 test namespace (WooCommerce\CustomsFees\Tests\).
require_once dirname( __DIR__ ) . '/vendor/autoload.php';
