<?php
/**
 * Common functions.
 *
 * @package taro-series
 * @since 1.0.0
 */

/**
 * Get post types.
 *
 * @return string[]
 */
function taro_series_post_types() {
	$predefined = taro_series_post_types_predefined();
	if ( ! empty( $predefined ) ) {
		return $predefined;
	}
	return (array) get_option( 'taro_series_post_types', [] );
}

/**
 * Can post type be a part of series?
 *
 * @param string $post_type Post type name.
 * @return bool
 */
function taro_series_can_be( $post_type ) {
	return apply_filters( 'taro_series_can_be', in_array( $post_type, taro_series_post_types(), true ), $post_type );
}

/**
 * Get predefined post types.
 *
 * @return string[]
 */
function taro_series_post_types_predefined() {
	if ( ! defined( 'TARO_SERIES_POST_TYPES' ) ) {
		return apply_filters( 'taro_series_post_types', [] );
	}
	return array_values( array_filter( array_map( function( $post_type ) {
		$post_type = trim( $post_type );
		return post_type_exists( $post_type ) ? $post_type : '';
	}, explode( ',', TARO_SERIES_POST_TYPES ) ) ) );
}

/**
 * Get series post types.
 *
 * @return string
 */
function taro_series_parent_post_type() {
	return apply_filters( 'taro_series_parent_post_type', 'series' );
}

/**
 * Post type argument for parent post type.
 */
function taro_series_parent_post_type_args() {
	return apply_filters( 'taro_series_parent_post_type_args', [
		'label'           => __( 'Series', 'taro-series' ),
		'public'          => true,
		'hierarchical'    => false,
		'has_archive'     => false,
		'show_in_rest'    => true,
		'capability_type' => 'page',
		'supports'        => [ 'title', 'editor', 'author', 'thumbnail' ],
		'menu_icon'       => 'dashicons-book-alt',
		'menu_position'   => 20,
	] );
}

/**
 * Get meta key for series.
 *
 * @return string
 */
function taro_series_meta_key() {
	return '_taro_series_id';
}

/**
 * Get series post.
 *
 * @param null|int|WP_post $post
 * @return WP_Post|null
 */
function taro_series_get( $post = null ) {
	$parent_post_id = taro_series_parent_id( $post );
	if ( ! $parent_post_id ) {
		return null;
	}
	return get_post( $parent_post_id ) ?: null;
}

/**
 * Get series parent id.
 *
 * @param null|int|WP_Post $post
 * @return int
 */
function taro_series_parent_id( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return 0;
	}
	if ( taro_series_parent_post_type() === $post->post_type ) {
		return $post->ID;
	} elseif ( taro_series_can_be( $post->post_type ) ) {
		return (int) get_post_meta( $post->ID, taro_series_meta_key(), true );
	} else {
		return 0;
	}
}

/**
 * Get series meta from article or series.
 *
 * @param string           $key      Meta key.
 * @param null|int|WP_Post $post     Post object.
 * @param bool             $singular Is singular?
 * @return mixed
 */
function taro_series_meta( $key, $post = null, $singular = true ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return null;
	}
	if ( taro_series_parent_post_type() === $post->post_type ) {
		$series = $post;
	} elseif ( taro_series_can_be( $post->post_type ) ) {
		$series = taro_series_get( $post );
		if ( ! $series ) {
			return null;
		}
	} else {
		return null;
	}
	return get_post_meta( $series->ID, $key, $singular );
}

/**
 * Get total amount of articles in series.
 *
 * @param null|int|WP_Post $post Post object.
 * @return int|string
 */
function taro_series_total( $post = null ) {
	$total = taro_series_meta( '_series_total', $post, true );
	return is_null( $total ) ? '' : (int) $total;
}

/**
 * Is finished?
 *
 * @param null|int|WP_Post $post Post object.
 * @return bool
 */
function taro_series_is_finished( $post = null ) {
	return (bool) taro_series_meta( '_series_is_finished', $post, true );
}

/**
 * Get total amount of articles in series.
 *
 * @param null|int|WP_Post $post Post object.
 * @return string
 */
function taro_series_finish_at( $post = null ) {
	return (string) taro_series_meta( '_series_finish_at', $post, true );
}

/**
 * Get query arguments for series.
 *
 * @param int   $series_id Series ID.
 * @param array $args      Optional query arguments.
 * @return array
 */
function taro_series_query_args( $series_id, $args = [] ) {
	$posts_per_page = \Tarosky\Series\Customizer\IndexLimit::posts_per_page();
	$args           = array_merge( [
		'post_type'           => taro_series_post_types(),
		'post_status'         => \Tarosky\Series\Customizer\ScheduledPosts::post_statuses(),
		'orderby'             => \Tarosky\Series\Customizer\OrderBy::order_by(),
		'order'               => \Tarosky\Series\Customizer\Order::order(),
		'posts_per_page'      => $posts_per_page,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => ( 1 > $posts_per_page ),
		'meta_query'          => [
			[
				'key'   => taro_series_meta_key(),
				'value' => $series_id,
			],
		],
	], $args );
	return apply_filters( 'taro_series_query_args', $args, $series_id, $args );
}

/**
 * Get wp_query.
 *
 * @param int   $series_id Series ID.
 * @param array $args      Additional query arguments.
 *
 * @return WP_Query
 */
function taro_series_query( $series_id, $args = [] ) {
	return new WP_Query( taro_series_query_args( $series_id, $args ) );
}

/**
 * @param $series_id
 * @param string $status
 *
 * @return int
 */
function taro_series_count( $series_id, $status = 'publish' ) {
	$query = taro_series_query( $series_id, [
		'post_status' => $status,
		'fields'      => 'ids',
	] );
	return count( $query->posts );
}

/**
 * Get posts in series.
 *
 * @param null $post
 * @param array $args
 *
 * @return WP_Query
 */
function taro_series_index_query( $post = null, $args = [] ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return null;
	}
	$series = taro_series_get( $post );
	if ( ! $series ) {
		return null;
	}
	return taro_series_query( $series->ID, $args );
}

/**
 * Get template part alternatives.
 *
 * @param string $name   File name.
 * @param string $suffix Optional suffix.
 * @param array  $args   Arguments passed to template.
 */
function taro_series_template_part( $name, $suffix = '', $args = [] ) {
	$dirs         = [ get_stylesheet_directory() ];
	$template_dir = get_template_directory();
	if ( $dirs[0] !== $template_dir ) {
		$dirs[] = $template_dir;
	}
	array_push( $dirs, taro_series_dir() );
	$files = [ $name . '.php' ];
	if ( $suffix ) {
		array_unshift( $files, $name . '-' . $suffix . '.php' );
	}
	$found = '';
	foreach ( $files as $file ) {
		foreach ( $dirs as $dir ) {
			$path = $dir . '/' . $file;
			if ( file_exists( $path ) ) {
				$found = $path;
				break 2;
			}
		}
	}
	$found = apply_filters( 'taro_series_template', $found, $name, $suffix, $args );
	if ( ! $found ) {
		return;
	}
	load_template( $found, false, $args );
}

/**
 * Display index.
 *
 * @param null|int|WP_Post $post
 */
function taro_series_the_index( $title = '', $post = null ) {
	$series       = taro_series_get( $post );
	$post_name    = isset( $series->post_name ) ? $series->post_name : '';
	$series_title = get_the_title( $series );
	if ( ! $title ) {
		$title = \Tarosky\Series\Customizer\TocTitle::get_title( $series_title );
	} elseif ( '%0' === $title ) {
		$title = '';
	} else {
		$title = str_replace( '%s', get_the_title( $series ), $title );
	}
	taro_series_template_part( 'template-parts/series/list', $post_name, [
		'title'      => $title,
		'series'     => $series,
		'link'       => taro_series_link( $series ),
		'link_label' => \Tarosky\Series\Customizer\ArchiveLink::get_label( $series_title ),
	] );
}

/**
 * Prefix of series archive.
 *
 * @return string
 */
function taro_series_prefix() {
	return apply_filters( 'taro_series_archive_prefix', 'series/archive' );
}

/**
 * Get series link.
 *
 * @param null|int|\WP_Post $post Post object.
 * @return string
 */
function taro_series_link( $post = null ) {
	$series = taro_series_get( $post );
	if ( ! $series ) {
		return '';
	}
	if ( get_option( 'rewrite_rules' ) ) {
		// Rewrite rules on.
		return home_url( trailingslashit( taro_series_prefix() ) . $series->post_name );
	} else {
		// Rewrite rules off.
		return add_query_arg( [
			'series_in' => $series->post_name,
		], home_url() );
	}
}

/**
 * Detect if this is series archive.
 *
 * @return bool
 */
function taro_is_series_archive() {
	return (bool) get_query_var( 'series_in' );
}

/**
 * Get series posts.
 *
 * @param array $args Optional arguments.
 * @return WP_Post[]
 */
function taro_series_list( $args = [] ) {
	$query = new \WP_Query( array_merge( [
		'post_type'      => taro_series_parent_post_type(),
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'order'          => 'ASC',
		'orderby'        => 'name',
	], $args ) );
	return $query->posts;
}
