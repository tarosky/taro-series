<?php

namespace Tarosky\Series\Customizer;


use Tarosky\Series\Pattern\CustomizerPattern;

/**
 * Limit of index.
 *
 * @package taro-series
 */
class ArchiveLink extends CustomizerPattern {

	/**
	 * @inheritDoc
	 */
	protected function id() {
		return 'taro_series_archive_link';
	}

	/**
	 * Default TOC title.
	 *
	 * @return string
	 */
	public function default_title() {
		// translators: %s is series title.
		return __( 'See All Articles', 'taro-series' );
	}

	/**
	 * @inheritDoc
	 */
	protected function controller_args() {
		return array_merge( parent::controller_args(), [
			'type'        => 'text',
			'label'       => __( 'Archive Link Label', 'taro-series' ),
			// translators: %s is a placeholder.
			'description' => __( 'If you limit the amount of articles in TOC, a link to archive will be displayed. %s will be replaced with series title.', 'taro-series' ),
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
	public static function get_label( $series_title = '' ) {
		$label = self::get() ?: self::get_instance()->default_title();
		if ( false !== strpos( $label, '%s' ) ) {
			$label = sprintf( $label, $series_title );
		}
		return $label;
	}
}
