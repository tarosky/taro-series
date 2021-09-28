<?php

namespace Tarosky\Series\Customizer;


use Tarosky\Series\Pattern\CustomizerPattern;

/**
 * Limit of index.
 *
 * @package taro-series
 */
class StyleLoading extends CustomizerPattern {

	/**
	 * @inheritDoc
	 */
	protected function id() {
		return 'taro_series_omit_style';
	}

	/**
	 * @inheritDoc
	 */
	protected function controller_args() {
		return array_merge( parent::controller_args(), [
			'type'  => 'checkbox',
			'label' => __( 'Do not load TOC stylesheet', 'taro-series' ),
		] );
	}

	/**
	 * Get posts per page for index.
	 *
	 * @return void
	 */
	public static function load_style() {
		$not_load = (bool) self::get();
		if ( $not_load ) {
			return;
		}
		wp_enqueue_style( 'taro-series-toc' );
	}
}
