<?php

namespace Tarosky\Series\Customizer;


use Tarosky\Series\Pattern\CustomizerPattern;

/**
 * Settings for display index in post page.
 *
 * @package taro-series
 */
class PostIndex extends CustomizerPattern {

	/**
	 * @inheritDoc
	 */
	protected function id() {
		return 'taro_series_index_in_post';
	}

	/**
	 * @inheritDoc
	 */
	protected function register_hooks() {
		add_filter( 'the_content', [ $this, 'the_content' ] );
	}

	/**
	 * @inheritDoc
	 */
	protected function controller_args() {
		return array_merge( parent::controller_args(), [
			'label'           => __( 'Display TOC in Article Pages', 'taro-series' ),
			'type'            => 'checkbox',
			'description'     => __( 'If checked, TOC of the series will be displayed in each articles in series.', 'taro-series' ),
		] );
	}


	/**
	 * If set, add index.
	 *
	 * @param string $content Post body.
	 * @return string
	 */
	public function the_content( $content ) {
		if ( ! static::get() ) {
			return $content;
		}
		$post_types = taro_series_post_types();
		if ( ! $post_types || ! is_singular( $post_types ) ) {
			return $content;
		}
		// Find parent series.
		$series = taro_series_get();
		if ( ! $series ) {
			return $content;
		}
		ob_start();
		taro_series_the_index();
		$content .= ob_get_contents();
		ob_end_clean();
		return $content;
	}
}
