<?php

namespace Tarosky\Series\Customizer;


use Tarosky\Series\Pattern\CustomizerPattern;

/**
 * Limit of index.
 *
 * @package taro-series
 */
class OrderBy extends CustomizerPattern {

	/**
	 * @inheritDoc
	 */
	protected function id() {
		return 'taro_series_orderby';
	}

	/**
	 * @inheritDoc
	 */
	protected function controller_args() {
		return array_merge( parent::controller_args(), [
			'type'    => 'select',
			'label'   => __( 'Order By', 'taro-series' ),
			'choices' => [
				''           => __( 'Published Date', 'taro-series' ),
				'menu_order' => __( 'Page Order', 'taro-series' ),
				'name'       => __( 'Post Slug', 'taro-series' ),
				'rand'       => __( 'Random', 'taro-series' ),
			],
			'default' => '',
		] );
	}

	/**
	 * Get posts per page for index.
	 *
	 * @return string
	 */
	public static function order_by() {
		$order_by = self::get();
		return $order_by ? $order_by : 'date';
	}
}
