<?php

namespace Tarosky\Series\Customizer;


use Tarosky\Series\Pattern\CustomizerPattern;

/**
 * Settings for display index in series pages.
 *
 * @package taro-series
 */
class SeriesIndex extends CustomizerPattern {

	/**
	 * @inheritDoc
	 */
	protected function id() {
		return 'taro_series_index_in_series';
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
			'label'           => __( 'Display TOC in Series Page', 'taro-series' ),
			'type'            => 'checkbox',
			'description'     => __( 'If checked, TOC of the series will be displayed in series single page.', 'taro-series' ),
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
		if ( ! is_singular( taro_series_parent_post_type() ) ) {
			return $content;
		}
		ob_start();
		taro_series_the_index();
		$content .= ob_get_contents();
		ob_end_clean();
		return $content;
	}
}
