<?php

namespace Tarosky\Series\Customizer;


use Tarosky\Series\Pattern\CustomizerPattern;

/**
 * Limit of index.
 *
 * @package taro-series
 */
class IndexLimit extends CustomizerPattern {

	/**
	 * @inheritDoc
	 */
	protected function id() {
		return 'taro_series_index_limit';
	}

	/**
	 * @inheritDoc
	 */
	protected function controller_args() {
		return array_merge( parent::controller_args(), [
			'type' => 'number',
			'label'       => __( 'Max Articles in Index', 'taro-series' ),
			'description' => __( '0 means all articles. If more than 0, the amount of articles in index will be limited.', 'taro-series' ),
			'input_attrs' => [
				'min' => 0,
			],
		] );
	}

	/**
	 * Get posts per page for index.
	 */
	public static function posts_per_page() {
		$index = self::get();
		return ( is_numeric( $index ) && 0 < $index ) ? (int) $index : -1;
	}
}
