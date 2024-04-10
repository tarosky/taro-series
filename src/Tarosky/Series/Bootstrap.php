<?php

namespace Tarosky\Series;


use Tarosky\Series\Blocks\TocBlock;
use Tarosky\Series\Controller\PostEditor;
use Tarosky\Series\Controller\Rewrite;
use Tarosky\Series\Controller\SeriesEditor;
use Tarosky\Series\Controller\Setting;
use Tarosky\Series\Customizer\ArchiveLink;
use Tarosky\Series\Customizer\IndexLimit;
use Tarosky\Series\Customizer\Order;
use Tarosky\Series\Customizer\OrderBy;
use Tarosky\Series\Customizer\PostIndex;
use Tarosky\Series\Customizer\ScheduledPosts;
use Tarosky\Series\Customizer\SeriesIndex;
use Tarosky\Series\Customizer\StyleLoading;
use Tarosky\Series\Customizer\TocTitle;
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
		// Post type.
		add_action( 'init', [ $this, 'register_series_type' ], 20 );
		// Assets.
		add_action( 'init', [ $this, 'register_script' ], 21 );
		// Controllers.
		PostEditor::get_instance();
		SeriesEditor::get_instance();
		Setting::get_instance();
		Rewrite::get_instance();
		// REST API.
		SeriesList::get_instance();
		SeriesArticles::get_instance();
		// Customizer
		SeriesIndex::get_instance();
		PostIndex::get_instance();
		ScheduledPosts::get_instance();
		StyleLoading::get_instance();
		IndexLimit::get_instance();
		OrderBy::get_instance();
		Order::get_instance();
		TocTitle::get_instance();
		ArchiveLink::get_instance();
		// Block
		TocBlock::get_instance();
		// Shortcode
		add_shortcode( 'taro_series', [ $this, 'do_shortcode' ] );
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
					// If requires translations, register.
					if ( in_array( 'wp-i18n', $asset['deps'], true ) ) {
						wp_set_script_translations( $asset['handle'], 'taro-series' );
					}
					break;
			}
		}
	}

	/**
	 * Render shortcode for debugging.
	 *
	 * @param array  $attrs    Shortcode attributes.
	 * @param string $contents Shortcode contents.
	 *
	 * @return string
	 */
	public function do_shortcode( $attrs = [], $contents = '' ) {
		$attrs      = shortcode_atts( [
			'order'          => 'DESC',
			'posts_per_page' => 10,
		], $attrs, 'taro_series' );
		$query_args = array_merge( [
			'post_type'   => taro_series_parent_post_type(),
			'post_status' => 'publish',
			'orderby'     => 'series-updated',
		], $attrs );
		$query      = new \WP_Query( $query_args );
		if ( ! $query->have_posts() ) {
			return '';
		}
		ob_start();
		echo '<ul>';
		foreach ( $query->posts as $post ) {
			printf(
				'<li><a href="%s">%s</a></li>',
				get_permalink( $post ),
				get_the_title( $post )
			);
		}
		echo '</ul>';
		return ob_get_clean();
	}
}
