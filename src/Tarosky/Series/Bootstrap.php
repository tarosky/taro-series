<?php

namespace Tarosky\Series;


use Tarosky\Series\Controller\PostEditor;
use Tarosky\Series\Controller\SeriesEditor;
use Tarosky\Series\Controller\Setting;
use Tarosky\Series\Pattern\Singleton;
use Tarosky\Series\RestApis\SeriesArticles;
use Tarosky\Series\RestApis\SeriesList;

/**
 * Boostrap.
 *
 * @package taro-series
 */
class Bootstrap extends Singleton {

	/**
	 * @inheritDoc
	 */
	protected function init() {
		add_action( 'init', [ $this, 'register_series_type' ], 20 );
		add_action( 'init', [ $this, 'register_script' ], 21 );
		// Controllers.
		PostEditor::get_instance();
		SeriesEditor::get_instance();
		Setting::get_instance();
		// REST API.
		SeriesList::get_instance();
		SeriesArticles::get_instance();
	}

	/**
	 * Register post type for parent series.
	 */
	public function register_series_type() {
		$post_type               = taro_series_parent_post_type();
		$should_create_post_type = apply_filters( 'taro_series_should_create_post_type', true );
		if ( ! $post_type || ! $should_create_post_type ) {
			// If post type is omitted, do nothing.
			return;
		}
		$args = taro_series_parent_post_type_args();
		register_post_type( $post_type, $args );
	}

	/**
	 * Register asset.
	 */
	public function register_script() {
		$json = taro_series_dir() . '/wp-dependencies.json';
		if ( ! file_exists( $json ) ) {
			trigger_error( __( 'Dependency file wp-dependencies.json is missing. Did you run build script?', 'taro-series' ), E_USER_WARNING );
			return;
		}
		$json = json_decode( file_get_contents( $json ), true );
		if ( ! $json ) {
			return;
		}
		foreach ( $json as $asset ) {
			if ( ! $asset ) {
				continue;
			}
			switch ( $asset['ext'] ) {
				case 'css':
					wp_register_style( $asset['handle'], taro_series_url() . '/' . $asset['path'], $asset['deps'], $asset['hash'], $asset['media'] );
					break;
				case 'js':
					wp_register_script( $asset['handle'], taro_series_url() . '/' . $asset['path'], $asset['deps'], $asset['hash'], $asset['footer'] );
					break;
			}
		}
	}
}