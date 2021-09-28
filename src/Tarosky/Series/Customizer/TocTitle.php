<?php

namespace Tarosky\Series\Customizer;


use Tarosky\Series\Pattern\CustomizerPattern;

/**
 * Limit of index.
 *
 * @package taro-series
 */
class TocTitle extends CustomizerPattern {

	/**
	 * @inheritDoc
	 */
	protected function id() {
		return 'taro_series_toc_title';
	}

	/**
	 * Default TOC title.
	 *
	 * @return string
	 */
	public function default_title() {
		// translators: %s is series title.
		return __( 'TOC of "%s"', 'taro-series' );
	}

	/**
	 * @inheritDoc
	 */
	protected function controller_args() {
		return array_merge( parent::controller_args(), [
			'type'        => 'text',
			'label'       => __( 'Toc Title', 'taro-series' ),
			'description' => __( '%s will be replaced with series title. %0 means no TOC title.', 'taro-series' ),
			'input_attrs' => [
				'placeholder' => $this->default_title(),
			],
		] );
	}

	/**
	 * Get posts per page for index.
	 *
	 * @param string $series_title Title of series.
	 * @return string
	 */
	public static function get_title( $series_title = '' ) {
		$title = self::get() ?: self::get_instance()->default_title();
		if ( false !== strpos( $title, '%s' ) ) {
			$title = sprintf( $title, $series_title );
		}
		return $title;
	}
}
