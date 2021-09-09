<?php

namespace Tarosky\Series\Controller;


use Tarosky\Series\Pattern\Singleton;

/**
 * Editor related functions.
 *
 * @package taro-series
 */
class PostEditor extends Singleton {

	/**
	 * @inheritDoc
	 */
	protected function init() {
		add_action( 'add_meta_boxes', [ $this, 'register_meta_boxes' ] );
		add_action( 'save_post', [ $this, 'save_post' ], 10, 2 );
		add_filter( 'display_post_states', [ $this, 'post_states' ], 10, 2 );
	}

	/**
	 * Register meta box.
	 *
	 * @param string $post_type Post type to be a part of series.
	 */
	public function register_meta_boxes( $post_type ) {
		if ( ! taro_series_can_be( $post_type ) ) {
			// This post type is not for series.
			return;
		}
		add_meta_box( 'taro-series-setting', __( 'Series Setting', 'taro-series' ), [ $this, 'meta_box_callback' ], $post_type, 'side' );
	}

	/**
	 * Meta box callback.
	 *
	 * @param \WP_Post $post post object.
	 */
	public function meta_box_callback( \WP_Post $post) {
		// Select series.
		$can    = apply_filters( 'taro_series_change_parent_post', current_user_can( 'edit_post', $post->ID ), $post );
		$series = taro_series_get( $post );
		if ( ! $can ) {
			// No capability.
			if ( $series ) {
				printf(
					'<p class="description">%s: <a href="%s" target="_blank" rel="noopener noreferrer">%s</a></p>',
					esc_html__( 'This post is a part of series. You have no capability to change', 'taro-series' ),
					get_edit_post_link( $series->ID ),
					esc_html( get_the_title( $series ) )
				);
			} else {
				printf(
					'<p class="description">%s</p>',
					esc_html__( 'This post is not a part of any series. You have no capability to change.', 'taro-series' )
				);
			}
		} else {
			// Has capability.
			wp_nonce_field( 'taro_series_change', '_taroseriesnonce' );
			// Enqueue script.
			wp_enqueue_script( 'taro-series-post-editor' );
			?>
			<div id="taro-series-selector" data-post-id="<?php echo $series ? esc_attr( $series->ID ) : '0' ?>" data-post-type="<?php echo esc_attr( $post->post_type ); ?>"></div>
			<?php
		}
	}

	/**
	 * Save parent ID.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public function save_post( $post_id, $post ) {
		if ( ! taro_series_can_be( $post->post_type ) ) {
			// This post type is not post.
			return;
		}
		if ( ! wp_verify_nonce( filter_input( INPUT_POST, '_taroseriesnonce' ), 'taro_series_change' ) ) {
			return;
		}
		update_post_meta( $post_id, taro_series_meta_key(), (int) filter_input( INPUT_POST, 'taro-series-parent' ) );
	}

	/**
	 * Add series name to post.
	 *
	 * @param string[] $states States.
	 * @param \WP_Post $post   Post object.
	 * @return string[]
	 */
	public function post_states( $states, $post ) {
		if ( ! taro_series_can_be( $post->post_type ) ) {
			return $states;
		}
		$series = taro_series_get( $post );
		if ( ! $series ) {
			return $states;
		}
		$states[ 'series' ] = sprintf( _x( 'Series "%s"', 'series-state', 'taro-series' ), get_the_title( $series ) );
		return $states;
	}
}
