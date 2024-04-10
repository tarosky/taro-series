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
		add_action( 'pre_get_posts', [ $this, 'query_in_series' ] );
		add_action( 'pre_get_posts', [ $this, 'query_series_top' ] );
		add_filter( 'posts_join', [ $this, 'posts_join' ], 10, 2 );
		add_filter( 'posts_orderby', [ $this, 'posts_orderby' ], 10, 2 );
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
		$vars[] = 'series_in'; // Posts in specific series.
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
	public function query_in_series( $wp_query ) {
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

	/**
	 * If this is series list, change query.
	 *
	 * @param \WP_Query $wp_query
	 * @return void
	 */
	public function query_series_top( $wp_query ) {
		if ( ! $this->is_series_update( $wp_query ) ) {
			return;
		}
		// Force post type to be series.
		$wp_query->set( 'post_type', taro_series_parent_post_type() );
		// Order should be asc or desc.
		if ( 'ASC' !== strtoupper( $wp_query->get( 'order' ) ) ) {
			$wp_query->set( 'order', 'DESC' );
		}
	}

	/**
	 * @param $join
	 * @param \WP_Query $wp_query
	 *
	 * @return mixed|string
	 */
	public function posts_join( $join, $wp_query ) {
		if ( $this->is_series_update( $wp_query ) ) {
			/* @var \wpdb $wpdb */
			global $wpdb;
			$post_types = implode( ', ', array_map( function( $post_type ) use ( $wpdb ) {
				return $wpdb->prepare( '%s', $post_type );
			}, taro_series_post_types() ) );
			$func = ( 'ASC' === strtoupper( $wp_query->get( 'order' ) ) ) ? 'MIN' : 'MAX';
			$sql  = <<<SQL
				INNER JOIN (
					SELECT CAST( pm.meta_value AS INT ) AS series_id , {$func}( p.post_date ) as last_updated
					FROM {$wpdb->posts} AS p
					LEFT JOIN {$wpdb->postmeta} AS pm
					ON pm.meta_key = %s AND pm.post_id = p.ID
					WHERE p.post_type IN ({$post_types})
					  AND p.post_status = 'publish'
					  AND pm.meta_value IS NOT NULL
					GROUP BY pm.meta_value
				) AS taro_series ON taro_series.series_id = {$wpdb->posts}.ID
SQL;
			$sql  = $wpdb->prepare( $sql, taro_series_meta_key() );
			$join .= $sql;
		}
		return $join;
	}

	/**
	 * Customize order by query.
	 *
	 * @param string $orderby
	 * @param \WP_Query $wp_query
	 *
	 * @return mixed
	 */
	public function posts_orderby( $orderby, $wp_query ) {
		if ( $this->is_series_update( $wp_query ) ) {
			/* @var \wpdb $wpdb */
			global $wpdb;
			$orderby = sprintf( 'taro_series.last_updated %s', ( 'ASC' === strtoupper( $wp_query->get( 'order' ) ) ? 'ASC' : 'DESC' ) );
		}
		return $orderby;
	}

	/**
	 * Detect if query is series update list.
	 *
	 * @param \WP_Query $wp_query
	 * @return bool
	 */
	public function is_series_update( $wp_query ) {
		return 'series-updated' === $wp_query->get( 'orderby' );
	}
}
