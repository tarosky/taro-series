<?php
/**
 * Tests for template functions.
 *
 * @package taro-series
 */

/**
 * Test includes/functions.php.
 */
class TestFunctions extends SeriesTestCase {

	/**
	 * Series post type is registered by default.
	 */
	public function test_parent_post_type_registered() {
		$this->assertSame( 'series', taro_series_parent_post_type() );
		$this->assertTrue( post_type_exists( 'series' ) );
		$this->assertTrue( get_post_type_object( 'series' )->show_in_rest );
	}

	/**
	 * Post types are taken from option and can be filtered.
	 */
	public function test_post_types() {
		$this->assertSame( [ 'post' ], taro_series_post_types() );
		$this->assertTrue( taro_series_can_be( 'post' ) );
		$this->assertFalse( taro_series_can_be( 'page' ) );

		$filter = function () {
			return [ 'page' ];
		};
		add_filter( 'taro_series_post_types', $filter );
		$this->assertSame( [ 'page' ], taro_series_post_types(), 'Predefined post types should take precedence over option.' );
		$this->assertTrue( taro_series_can_be( 'page' ) );
		remove_filter( 'taro_series_post_types', $filter );

		$can_be = function ( $can, $post_type ) {
			return 'attachment' === $post_type ? true : $can;
		};
		add_filter( 'taro_series_can_be', $can_be, 10, 2 );
		$this->assertTrue( taro_series_can_be( 'attachment' ) );
		remove_filter( 'taro_series_can_be', $can_be );
	}

	/**
	 * Parent ID and series object.
	 */
	public function test_parent_id_and_get() {
		$series  = $this->create_series();
		$article = $this->create_article( $series->ID );
		$orphan  = $this->create_article();
		$page    = self::factory()->post->create_and_get( [ 'post_type' => 'page' ] );
		update_post_meta( $page->ID, taro_series_meta_key(), $series->ID );

		$this->assertSame( $series->ID, taro_series_parent_id( $series ), 'Series itself returns its ID.' );
		$this->assertSame( $series->ID, taro_series_parent_id( $article ) );
		$this->assertSame( 0, taro_series_parent_id( $orphan ) );
		$this->assertSame( 0, taro_series_parent_id( $page ), 'Post type out of series should be ignored.' );
		$this->assertSame( 0, taro_series_parent_id( 0 ) );

		$this->assertSame( $series->ID, taro_series_get( $article )->ID );
		$this->assertNull( taro_series_get( $orphan ) );

		// Deleted series.
		update_post_meta( $orphan->ID, taro_series_meta_key(), 999999 );
		$this->assertNull( taro_series_get( $orphan ), 'Non-existing series should return null.' );
	}

	/**
	 * Series meta is accessible from articles.
	 */
	public function test_series_meta() {
		$series  = $this->create_series();
		$article = $this->create_article( $series->ID );
		$orphan  = $this->create_article();
		update_post_meta( $series->ID, '_series_total', '12' );
		update_post_meta( $series->ID, '_series_is_finished', '1' );
		update_post_meta( $series->ID, '_series_finish_at', '2024-12-31' );

		$this->assertSame( 12, taro_series_total( $article ) );
		$this->assertSame( 12, taro_series_total( $series ) );
		$this->assertTrue( taro_series_is_finished( $article ) );
		$this->assertSame( '2024-12-31', taro_series_finish_at( $article ) );

		$this->assertSame( '', taro_series_total( $orphan ) );
		$this->assertFalse( taro_series_is_finished( $orphan ) );
		$this->assertSame( '', taro_series_finish_at( $orphan ) );
	}

	/**
	 * Default query arguments reflect customizer settings.
	 */
	public function test_query_args_default() {
		$args = taro_series_query_args( 10 );
		$this->assertSame( [ 'post' ], $args['post_type'] );
		$this->assertSame( [ 'publish' ], $args['post_status'] );
		$this->assertSame( 'date', $args['orderby'] );
		$this->assertSame( 'DESC', $args['order'] );
		$this->assertSame( -1, $args['posts_per_page'] );
		$this->assertTrue( $args['no_found_rows'] );
		$this->assertSame( taro_series_meta_key(), $args['meta_query'][0]['key'] );
		$this->assertSame( 10, $args['meta_query'][0]['value'] );
	}

	/**
	 * Query arguments with customizer settings.
	 */
	public function test_query_args_customized() {
		update_option( 'taro_series_index_limit', '5' );
		update_option( 'taro_series_include_scheduled_posts', '1' );
		update_option( 'taro_series_orderby', 'menu_order' );
		update_option( 'taro_series_order', 'ASC' );
		$args = taro_series_query_args( 10, [ 'post_status' => 'draft' ] );
		$this->assertSame( 'draft', $args['post_status'], 'Arguments should be overridable.' );
		$this->assertSame( 5, $args['posts_per_page'] );
		$this->assertFalse( $args['no_found_rows'] );
		$this->assertSame( 'menu_order', $args['orderby'] );
		$this->assertSame( 'ASC', $args['order'] );

		$args = taro_series_query_args( 10 );
		$this->assertSame( [ 'publish', 'future' ], $args['post_status'] );

		// Invalid order falls back to DESC.
		update_option( 'taro_series_order', 'invalid' );
		$this->assertSame( 'DESC', taro_series_query_args( 10 )['order'] );
	}

	/**
	 * Count and index query.
	 */
	public function test_count_and_index_query() {
		$series = $this->create_series();
		$first  = $this->create_article( $series->ID, [ 'post_date' => '2024-01-01 00:00:00' ] );
		$second = $this->create_article( $series->ID, [ 'post_date' => '2024-02-01 00:00:00' ] );
		$this->create_article( $series->ID, [ 'post_status' => 'draft' ] );
		$this->create_article();

		$this->assertSame( 2, taro_series_count( $series->ID ) );
		$this->assertSame( 1, taro_series_count( $series->ID, 'draft' ) );

		$query = taro_series_index_query( $first );
		$this->assertInstanceOf( WP_Query::class, $query );
		$this->assertSame( [ $second->ID, $first->ID ], wp_list_pluck( $query->posts, 'ID' ) );

		$this->assertNull( taro_series_index_query( $this->create_article() ) );
	}

	/**
	 * Series link depends on rewrite rules.
	 */
	public function test_series_link() {
		$series  = $this->create_series( [ 'post_name' => 'my-series' ] );
		$article = $this->create_article( $series->ID );

		$this->set_permalink_structure( '' );
		$this->assertSame( add_query_arg( [ 'series_in' => 'my-series' ], home_url() ), taro_series_link( $article ) );

		$this->set_permalink_structure( '/%postname%/' );
		$this->assertSame( home_url( 'series/archive/my-series' ), taro_series_link( $article ) );

		$this->assertSame( '', taro_series_link( $this->create_article() ) );
	}

	/**
	 * List of series.
	 */
	public function test_series_list() {
		$b = $this->create_series( [ 'post_name' => 'b-series' ] );
		$a = $this->create_series( [ 'post_name' => 'a-series' ] );
		$this->create_series( [ 'post_status' => 'draft' ] );
		$this->assertSame( [ $a->ID, $b->ID ], wp_list_pluck( taro_series_list(), 'ID' ) );
	}
}
