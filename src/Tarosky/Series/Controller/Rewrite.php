<?php

namespace Tarosky\Series\Controller;


use Tarosky\Series\Pattern\Singleton;

/**
 * Add rewrite rule.
 *
 * @package taro-series
 */
class Rewrite extends Singleton {

	/**
	 * @inheritDoc
	 */
	protected function init() {
		add_filter( 'query_vars', [ $this, 'query_vars' ] );
		add_filter( 'rewrite_rules_array', [ $this, 'rewrite_rules' ] );
		add_action( 'pre_get_posts', [ $this, 'pre_get_posts' ] );
	}

	/**
	 * Add query vars.
	 *
	 * @param string[] $vars Query vars.
	 * @return string[]
	 */
	public function query_vars( $vars ) {
		$vars[] = 'series_in';
		return $vars;
	}

	/**
	 * Add rewrite rules.
	 *
	 * @param string[] $rules
	 */
	public function rewrite_rules( $rules ) {
		$preg = '^' . ltrim( taro_series_prefix(),'/' ) . '/([^/]+)';
		return array_merge( [
			$preg . '/page/(\d+)/?' => 'index.php?series_in=$matches[1]&paged=$matches[2]',
			$preg . '/?'            => 'index.php?series_in=$matches[1]',
		], $rules );
	}

	/**
	 * Customize query.
	 *
	 * @param \WP_Query $wp_query Query object.
	 */
	public function pre_get_posts( $wp_query ) {
		$series_in = $wp_query->get( 'series_in' );
		if ( ! $series_in ) {
			return;
		}
		// Get series by slug.
		$series = get_page_by_path( $series_in, OBJECT, taro_series_parent_post_type() );
		if ( ! $series ) {
			$series_id = 0;
		} else {
			$series_id = $series->ID;
		}
		// Set meta query.
		$meta_query = $wp_query->get( 'meta_query' ) ?: [];
		$meta_query[] = [
			'key'   => taro_series_meta_key(),
			'value' => $series_id,
		];
		$wp_query->set( 'meta_query', $meta_query );
		// Limit post type.
		$wp_query->set( 'post_type', taro_series_post_types() );
		// Ignore sticky.
		$wp_query->set( 'ignore_sticky_posts', true );
	}
}
