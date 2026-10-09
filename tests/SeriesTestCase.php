<?php
/**
 * Base test case for taro-series.
 *
 * @package taro-series
 */

/**
 * Provides helpers to create series and articles.
 */
abstract class SeriesTestCase extends WP_UnitTestCase {

	/**
	 * Enable "post" as a series article post type.
	 */
	public function set_up() {
		parent::set_up();
		update_option( 'taro_series_post_types', [ 'post' ] );
	}

	/**
	 * Create a series post.
	 *
	 * @param array $args Post arguments.
	 * @return WP_Post
	 */
	protected function create_series( $args = [] ) {
		return self::factory()->post->create_and_get( array_merge( [
			'post_type'   => taro_series_parent_post_type(),
			'post_status' => 'publish',
		], $args ) );
	}

	/**
	 * Create an article that belongs to the series.
	 *
	 * @param int   $series_id Series ID. 0 means no series.
	 * @param array $args      Post arguments.
	 * @return WP_Post
	 */
	protected function create_article( $series_id = 0, $args = [] ) {
		$post = self::factory()->post->create_and_get( array_merge( [
			'post_type'   => 'post',
			'post_status' => 'publish',
		], $args ) );
		if ( $series_id ) {
			update_post_meta( $post->ID, taro_series_meta_key(), $series_id );
		}
		return $post;
	}
}
