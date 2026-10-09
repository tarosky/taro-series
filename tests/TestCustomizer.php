<?php
/**
 * Tests for customizer settings.
 *
 * @package taro-series
 */

use Tarosky\Series\Customizer\ArchiveLink;
use Tarosky\Series\Customizer\IndexLimit;
use Tarosky\Series\Customizer\TocTitle;

/**
 * Test customizer helpers.
 */
class TestCustomizer extends SeriesTestCase {

	/**
	 * TOC title.
	 */
	public function test_toc_title() {
		$this->assertSame( 'TOC of "My Series"', TocTitle::get_title( 'My Series' ) );
		update_option( 'taro_series_toc_title', 'Index: %s' );
		$this->assertSame( 'Index: My Series', TocTitle::get_title( 'My Series' ) );
		update_option( 'taro_series_toc_title', 'Static Title' );
		$this->assertSame( 'Static Title', TocTitle::get_title( 'My Series' ) );
	}

	/**
	 * Archive link label.
	 */
	public function test_archive_link_label() {
		$this->assertSame( 'See All Articles', ArchiveLink::get_label( 'My Series' ) );
		update_option( 'taro_series_archive_link', 'All of %s' );
		$this->assertSame( 'All of My Series', ArchiveLink::get_label( 'My Series' ) );
	}

	/**
	 * Index limit.
	 */
	public function test_index_limit() {
		$this->assertSame( -1, IndexLimit::posts_per_page() );
		update_option( 'taro_series_index_limit', '0' );
		$this->assertSame( -1, IndexLimit::posts_per_page() );
		update_option( 'taro_series_index_limit', 'abc' );
		$this->assertSame( -1, IndexLimit::posts_per_page() );
		update_option( 'taro_series_index_limit', '3' );
		$this->assertSame( 3, IndexLimit::posts_per_page() );
	}
}
