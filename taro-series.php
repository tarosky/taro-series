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

/**
 * Register assets from wp-dependencies.json.
 */
function taro_series_register_assets() {
	$json = __DIR__ . '/wp-dependencies.json';
	if ( ! file_exists( $json ) ) {
		return;
	}
	$dependencies = json_decode( file_get_contents( $json ), true );
	if ( empty( $dependencies ) ) {
		return;
	}
	$base = trailingslashit( plugin_dir_url( __FILE__ ) );
	foreach ( $dependencies as $dep ) {
		if ( empty( $dep['path'] ) ) {
			continue;
		}
		$url = $base . $dep['path'];
		switch ( $dep['ext'] ) {
			case 'css':
				wp_register_style( $dep['handle'], $url, $dep['deps'], $dep['hash'], $dep['media'] );
				break;
			case 'js':
				$footer = [ 'in_footer' => $dep['footer'] ];
				if ( in_array( $dep['strategy'], [ 'defer', 'async' ], true ) ) {
					$footer['strategy'] = $dep['strategy'];
				}
				wp_register_script( $dep['handle'], $url, $dep['deps'], $dep['hash'], $footer );
				if ( in_array( 'wp-i18n', $dep['deps'], true ) ) {
					wp_set_script_translations( $dep['handle'], 'taro-series' );
				}
				break;
		}
	}
}

// Register hooks.
add_action( 'init', 'taro_series_register_assets' );
add_action( 'plugins_loaded', 'taro_series_init' );
register_activation_hook( __FILE__, 'taro_series_activate' );
register_deactivation_hook( __FILE__, 'taro_series_deactivate' );
