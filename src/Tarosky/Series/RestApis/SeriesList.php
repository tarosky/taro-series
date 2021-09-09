<?php

namespace Tarosky\Series\RestApis;

use Tarosky\Series\Pattern\RestApi;

/**
 * Rest API for series set.
 *
 * @package taro-series
 */
class SeriesList extends RestApi {

	/**
	 * @inheritDoc
	 */
	protected function route() {
		return 'available/(?P<post_type>[^/]+)';
	}

	/**
	 * @inheritDoc
	 */
	protected function get_args( $method ) {
		return [
			'post_type'      => [
				'type'              => 'string',
				'required'          => true,
				'validate_callback' => function( $var ) {
					return taro_series_can_be( $var );
				},
			],
			's'              => [
				'type'    => 'string',
				'default' => '',
			],
			'p'              => [
				'type'    => 'int',
				'default' => 0,
			],
			'posts_per_page' => [
				'type'              => 'int',
				'default'           => 10,
				'validate_callback' => function( $var ) {
					return ( -1 === $var ) || ( 0 < $var );
				},
			],
			'order'          => [
				'type'              => 'string',
				'default'           => 'DESC',
				'validate_callback' => function( $var ) {
					return in_array( $var, [ 'DESC', 'ASC' ], true );
				},
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function callback( \WP_REST_Request $request ) {
		$p         = $request->get_param( 'p' );
		$post_args = [
			'post_type'     => taro_series_parent_post_type(),
			'no_found_rows' => true,
		];
		if ( 0 < $p ) {
			$post_args = array_merge( $post_args, [
				'posts_per_page' => 1,
				'p'              => $p,
			] );
		} else {
			$post_args = array_merge( $post_args, [
				'posts_per_page' => $request->get_param( 'posts_per_page' ),
				'order'          => $request->get_param( 'order' ),
				'orderby'        => 'date',
			] );
			$s         = $request->get_param( 's' );
			if ( $s ) {
				$post_args['s'] = $s;
			}
		}
		$post_args = apply_filters( 'taro_series_selectable_args', $post_args, $request->get_param( 'post_type' ) );
		$query     = new \WP_Query( $post_args );
		if ( ! $query->have_posts() ) {
			return new \WP_REST_Response( [] );
		}
		return new \WP_REST_Response( array_map( function( \WP_Post $post ) {
			return [
				'id'        => $post->ID,
				'title'     => get_the_title( $post ),
				'url'       => ( 'publish' === $post->post_status ) ? get_permalink( $post ) : get_preview_post_link( $post ),
				'edit_link' => current_user_can( 'edit_post', $post->ID, ) ? get_edit_post_link( $post ) : '',
			];
		}, $query->posts) );
	}
}
