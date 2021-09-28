<?php

namespace Tarosky\Series\Customizer;


use Tarosky\Series\Pattern\CustomizerPattern;

/**
 * Limit of index.
 *
 * @package taro-series
 */
class ScheduledPosts extends CustomizerPattern {

	/**
	 * @inheritDoc
	 */
	protected function id() {
		return 'taro_series_include_scheduled_posts';
	}

	/**
	 * @inheritDoc
	 */
	protected function controller_args() {
		return array_merge( parent::controller_args(), [
			'type'        => 'checkbox',
			'label'       => __( 'Include scheduled posts', 'taro-series' ),
		] );
	}

	/**
	 * Get posts per page for index.
	 *
	 * @return string[]
	 */
	public static function post_statuses() {
		$scheduled = (bool) self::get();
		$post_status = [ 'publish' ];
		if ( $scheduled ) {
			$post_status[] = 'future';
		}
		return $post_status;
	}
}
