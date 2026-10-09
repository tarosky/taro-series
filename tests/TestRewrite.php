<?php
/**
 * Tests for rewrite and query customization.
 *
 * @package taro-series
 */

use Tarosky\Series\Controller\Rewrite;

/**
 * Test series archive.
 */
class TestRewrite extends SeriesTestCase {

	/**
	 * Query var and rewrite rules are registered.
	 */
	public function test_rewrite_rules() {
		$this->assertContains( 'series_in', Rewrite::get_instance()->query_vars( [] ) );
		$this->set_permalink_structure( '/%postname%/' );
		$rules = get_option( 'rewrite_rules' );
		$this->assertArrayHasKey( '^series/archive/([^/]+)/?', $rules );
		$this->assertSame( 'index.php?series_in=$matches[1]&paged=$matches[2]', $rules['^series/archive/([^/]+)/page/(\d+)/?'] );
	}

	/**
	 * Series archive lists articles in the series.
	 */
	public function test_series_archive() {
		$series   = $this->create_series( [
			'post_name'  => 'my-series',
			'post_title' => 'My Series',
		] );
		$article  = $this->create_article( $series->ID );
		$this->create_article();
		$this->go_to( add_query_arg( [ 'series_in' => 'my-series' ], home_url( '/' ) ) );
		$this->assertTrue( taro_is_series_archive() );
		$this->assertTrue( is_archive() );
		$this->assertFalse( is_home() );
		$this->assertSame( [ $article->ID ], wp_list_pluck( $GLOBALS['wp_query']->posts, 'ID' ) );
		$this->assertSame( 'Articles in My Series', get_the_archive_title() );
		$this->assertSame( 'archive-in-series-my-series.php', Rewrite::get_instance()->template_hierarchy( [ 'index.php' ] )[0] );
	}

	/**
	 * Unknown series shows nothing.
	 */
	public function test_unknown_series_archive() {
		$this->create_article( $this->create_series()->ID );
		$this->go_to( add_query_arg( [ 'series_in' => 'not-exists' ], home_url( '/' ) ) );
		$this->assertEmpty( $GLOBALS['wp_query']->posts );
		$this->assertSame( 'Series Archive', get_the_archive_title() );
	}

	/**
	 * Series can be ordered by the last updated article.
	 */
	public function test_series_updated_order() {
		$a = $this->create_series();
		$b = $this->create_series();
		$this->create_series(); // No article.
		$this->create_article( $a->ID, [ 'post_date' => '2024-01-01 00:00:00' ] );
		$this->create_article( $b->ID, [ 'post_date' => '2023-01-01 00:00:00' ] );
		$this->create_article( $b->ID, [ 'post_date' => '2025-01-01 00:00:00' ] );

		$query = new WP_Query( [
			'post_type' => 'post',
			'orderby'   => 'series-updated',
		] );
		$this->assertSame( [ $b->ID, $a->ID ], wp_list_pluck( $query->posts, 'ID' ), 'Series with latest article comes first and series without articles is excluded.' );

		$query = new WP_Query( [
			'orderby' => 'series-updated',
			'order'   => 'ASC',
		] );
		$this->assertSame( [ $b->ID, $a->ID ], wp_list_pluck( $query->posts, 'ID' ), 'ASC uses the oldest article.' );
	}

	/**
	 * Shortcode lists series.
	 */
	public function test_shortcode() {
		$series = $this->create_series( [ 'post_title' => 'Shortcode Series' ] );
		$this->create_article( $series->ID );
		$html = do_shortcode( '[taro_series]' );
		$this->assertStringContainsString( 'Shortcode Series', $html );
		$this->assertStringContainsString( get_permalink( $series ), $html );
	}
}
