<?php

namespace Tarosky\Series\Customizer;


use Tarosky\Series\Pattern\CustomizerPattern;

/**
 * Limit of index.
 *
 * @package taro-series
 */
class Order extends CustomizerPattern {

	/**
	 * @inheritDoc
	 */
	protected function id() {
		return 'taro_series_order';
	}

	/**
	 * @inheritDoc
	 */
	protected function controller_args() {
		return array_merge( parent::controller_args(), [
			'type'        => 'select',
			'label'       => __( 'Order', 'taro-series' ),
			'choices'     => [
				''   => __( 'DESC', 'taro-series' ),
				'ASC' => __( 'ASC', 'taro-series' ),
			],
			'default'     => '',
		] );
	}

	/**
	 * Get posts per page for index.
	 *
	 * @return string
	 */
	public static function order() {
		$order = self::get();
		switch ( $order ) {
			case 'ASC':
				return $order;
			default:
				return 'DESC';
		}
	}
}
