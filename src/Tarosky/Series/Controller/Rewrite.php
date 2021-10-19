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
		add_filter( 'get_the_archive_title', [ $this, 'archive_title' ] );
		add_filter( 'index_template_hierarchy', [ $this, 'template_hierarchy' ] );
		add_filter( 'archive_template_hierarchy', [ $this, 'template_hierarchy' ] );
		add_filter( 'document_title_parts', [ $this, 'title_in_head' ] );
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
		$preg = '^' . ltrim( taro_series_prefix(), '/' ) . '/([^/]+)';
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
		$series = $this->get_current_series( $series_in );
		if ( ! $series ) {
			$series_id = 0;
		} else {
			$series_id = $series->ID;
		}
		// Set meta query.
		$meta_query   = $wp_query->get( 'meta_query' ) ?: [];
		$meta_query[] = [
			'key'   => taro_series_meta_key(),
			'value' => $series_id,
		];
		// Change conditions.
		$wp_query->is_archive = true;
		$wp_query->is_home    = false;
		$wp_query->set( 'meta_query', $meta_query );
		// Limit post type.
		$wp_query->set( 'post_type', taro_series_post_types() );
		// Ignore sticky.
		$wp_query->set( 'ignore_sticky_posts', true );
	}

	/**
	 * Add series link template.
	 */
	public function template_hierarchy( $templates ) {
		if ( taro_is_series_archive() ) {
			$templates = [
				sprintf( 'archive-in-series-%s.php', get_query_var( 'series_in' ) ),
				'archive-in-series.php',
				'archive.php',
				'index.php',
			];
		}
		return $templates;
	}

	/**
	 * Get the title.
	 *
	 * @param string $title Original title.
	 * @return string
	 */
	public function archive_title( $title ) {
		if ( taro_is_series_archive() ) {
			// Change title.
			$title = $this->title( $this->get_current_series() );
		}
		return $title;
	}

	/**
	 * Change title tag in
	 *
	 * @param string[] $title_parts Title parts.
	 * @return string[]
	 */
	public function title_in_head( $title_parts ) {
		if ( taro_is_series_archive() ) {
			$title_parts['title'] = $this->title( $this->get_current_series() );
		}
		return $title_parts;
	}

	/**
	 * Get series title for archive.
	 *
	 * @param \WP_Post|null|int $series Post object.
	 * @return string
	 */
	protected function title( $series ) {
		$series = get_post( $series );
		if ( $series ) {
			// translators: %s is series title.
			$title = apply_filters( 'taro_series_archive_title', sprintf( __( 'Articles in %s', 'taro-series' ), get_the_title( $series ) ), $series );
		} else {
			$title = __( 'Series Archive', 'taro-series' );
		}
		return apply_filters( 'taro_series_archive_title', $title, $series );
	}

	/**
	 * Get series post object from current query.
	 *
	 * @param string $slug Series slug. Default, main queyr's series_in
	 * @return \WP_Post|null
	 */
	public function get_current_series( $slug = '' ) {
		if ( ! $slug ) {
			$slug = get_query_var( 'series_in' );
		}
		return get_page_by_path( $slug, OBJECT, taro_series_parent_post_type() );
	}
}
