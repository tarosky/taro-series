<?php

namespace Tarosky\Series\Blocks;


use Tarosky\Series\Pattern\DynamicBlockPattern;

/**
 * Toc Block
 *
 * @
 */
class TocBlock extends DynamicBlockPattern {

	/**
	 * @inheritDoc
	 */
	protected function block_name() {
		return 'taro-series/toc';
	}

	/**
	 * @inheritDoc
	 */
	protected function editor_script() {
		return 'taro-series-toc';
	}

	/**
	 * @inheritDoc
	 */
	protected function style() {
		return 'taro-series-toc';
	}


	/**
	 * @inheritDoc
	 */
	protected function get_block_attributes() {
		return [
			'series_id' => [
				'type'    => 'integer',
				'default' => 0,
			],
			'title' => [
				'type'    => 'string',
				'default' => '',
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function render_callback( $arguments, $content = '' ) {
		$arguments = wp_parse_args( $arguments, [
			'series_id' => 0,
			'title'     => '',
		] );
		$series_id = $arguments['series_id'];
		$title     = $arguments['title'];
		try {
			if ( ! $series_id ) {
				$series = taro_series_get();
				if ( ! $series ) {
					throw new \Exception( __( 'This post is not a part of series. Please specify one.', 'taro-serires' ) );
				}
			} else {
				$series = get_post( $series_id );
				if ( ! $series || taro_series_parent_post_type() !== $series->post_type || 'publish' !== $series->post_status ) {
					throw new \Exception( __( 'Specified series does not exist or cannot be published.', 'taro-series' ) );
				}
			}
			ob_start();
			taro_series_the_index( $title, $series );
			$toc = ob_get_contents();
			ob_end_clean();
			return $toc;
		} catch ( \Exception $e ) {
			if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
				$html = <<<'HTML'
<div class="components-placeholder">
	<div class="components-placeholder__label">
		<span class="dashicon dashicons dashicons-info"></span>
		%s
	</div>
	<div class="components-placeholder__instructions">
		%s
	</div>
	<div class="components-placeholder__fieldset"></div>
</div>
HTML;
				return sprintf( $html, esc_html__( 'No TOC displayed.', 'taro-series' ), esc_html( $e->getMessage() ) );
			} else {
				return '';
			}
		}
	}

	/**
	 * Add a list of series for select tag.
	 *
	 * @param array $vars Variables to JS.
	 * @return array
	 */
	protected function block_variable_filter( $vars ) {
		$series = [ [
			'value' => 0,
			'label' => __( 'Current Series', 'taro-series' ),
		] ];
		$query  = new \WP_Query( [
			'post_type'      => taro_series_parent_post_type(),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'order'          => 'ASC',
			'orderby'        => 'name',
		] );
		foreach ( $query->posts as $post ) {
			$series[] = [
				'value' => $post->ID,
				'label' => get_the_title( $post ),
			];
		}
		$vars['series'] = $series;
		return $vars;
	}
}
