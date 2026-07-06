<?php
/**
 * Plugin Name: Taro Series
 * Plugin URI: https://wordpress.org/plugins/taro-series/
 * Description: Add series feature to your WordPress site.
 * Author: Tarosky INC.
 * Version: nightly
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * Author URI: https://tarosky.co.jp/
 * License: GPL3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: taro-series
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) or die();

/**
 * Init plugins.
 */
function taro_series_init() {
	// Register translations.
	load_plugin_textdomain( 'taro-series', false, basename( __DIR__ ) . '/languages' );
	// Load functions.
	require_once __DIR__ . '/includes/functions.php';
	// Require Bootstrap.
	$autoload = __DIR__ . '/vendor/autoload.php';
	if ( ! file_exists( $autoload ) ) {
		trigger_error( __( 'Autoloader is missing. Did you ran composer install?', 'taro-series' ), E_USER_WARNING );
	} else {
		require $autoload;
		\Tarosky\Series\Bootstrap::get_instance();
	}
}

/**
 * Flush rewrite rules so that series permalinks work without a manual permalink resave.
 */
function taro_series_activate() {
	taro_series_init();
	\Tarosky\Series\Bootstrap::get_instance()->register_series_type();
	flush_rewrite_rules();
}

/**
 * Flush rewrite rules on deactivation to remove series-specific rules.
 */
function taro_series_deactivate() {
	flush_rewrite_rules();
}

/**
 * Get plugin base URL.
 *
 * @return string
 */
function taro_series_url() {
	return untrailingslashit( plugin_dir_url( __FILE__ ) );
}

/**
 * Get directory path.
 *
 * @return string
 */
function taro_series_dir() {
	return __DIR__;
}

/**
 * Get version.
 *
 * @return string
 */
function taro_series_version() {
	static $version = null;
	if ( is_null( $version ) ) {
		$data    = get_file_data( __FILE__, [
			'version' => 'Version',
		] );
		$version = $data['version'];
	}
	return $version;
}

// Register hooks.
add_action( 'plugins_loaded', 'taro_series_init' );
register_activation_hook( __FILE__, 'taro_series_activate' );
register_deactivation_hook( __FILE__, 'taro_series_deactivate' );
