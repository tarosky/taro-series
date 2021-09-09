<?php

namespace Tarosky\Series\RestApis;


use Tarosky\Series\Pattern\RestApi;

/**
 * Articles of series.
 *
 * @package taro-series
 */
class SeriesArticles extends RestApi {

	/**
	 * @inheritDoc
	 */
	protected function route() {
		return 'series/(?P<series_id>\d+)';
	}

	/**
	 * @inheritDoc
	 */
	protected function get_methods() {
		return [ 'GET', 'POST', 'DELETE' ];
	}

	/**
	 * @inheritDoc
	 */
	protected function get_args( $method ) {
		$args = [
			'series_id' => [
				'required' => true,
				'type'     => 'int',
				'validate_callback' => function( $post_id ) {
					$post = get_post( $post_id );
					return $post && ( taro_series_parent_post_type() === $post->post_type );
				},
			],
		];
		switch ( $method ) {
			case 'GET':
				$args = array_merge( $args, [
					's'              => [
						'type'    => 'string',
						'default' => '',
					],
					'posts_per_page' => [
						'type'    => 'int',
						'default' => 10,
					],
					'paged'          => [
						'type'    => 'int',
						'default' => 1,
						'sanitize_callback' => function( $var ) {
							return max( 1, $var );
						},
					],
				] );
				break;
			case 'POST':
			case 'DELETE':
				$args['post_id'] = [
					'type'     => 'int',
					'required' => true,
				];
				break;
		}
		return $args;
	}

	/**
	 * @inheritDoc
	 */
	public function callback( \WP_REST_Request $request ) {
		switch ( strtoupper( $request->get_method() ) ) {
			case 'GET':
				return $this->callback_get( $request );
			case 'POST':
				return $this->callback_post( $request );
			case 'DELETE':
				return $this->callback_delete( $request );
			default:
				return new \WP_Error( 'rest_api_error', __( 'Method not allowed.', 'taro-series' ) );
		}
	}

	/**
	 * Search or get results.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	protected function callback_get( \WP_REST_Request $request ) {
		$s      = $request->get_param( 's' );
		$series = $request->get_param( 'series_id' );
		if ( $s ) {
			$args = taro_series_query_args( $series, [
				'post_status'    => 'any',
				's'              => $s,
				'posts_per_page' => $request->get_param( 'posts_per_page' ),
				'paged'          => $request->get_param( 'paged' ),
				'order'          => 'DESC',
			] );
			unset( $args['meta_query'] );
			unset( $args['no_found_rows'] );
		} else {
			$args = taro_series_query_args( $series, [
				'post_status' => 'any',
			] );
		}
		$query = new \WP_Query( $args );
		return new \WP_REST_Response( [
			'total' => $s ? $query->found_posts : count( $query->posts ),
			'posts' => array_map( [ $this, 'convert' ], $query->posts ),
		] );
	}

	/**
	 * Add post to series.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	protected function callback_post( \WP_REST_Request $request ) {
		$series_id = (int) $request->get_param( 'series_id' );
		$post_id   = (int) $request->get_param( 'post_id' );
		$series    = taro_series_get( $post_id );
		if ( $series ) {
			// translators: %1$d is post id, %2$d is series id.
			return new \WP_Error( 'rest_api_error', sprintf( __( '#%1$d is already a part of series #%2$d', 'taro-series' ), $post_id, $series_id ), [
				'status' => 400,
			] );
		}
		update_post_meta( $post_id, taro_series_meta_key(), $series_id );
		return new \WP_REST_Response( [
			'success' => true,
			// translators: %d is post ID.
			'message' => sprintf( __( '#%d is added as a part of this series.', 'taro-series' ), $post_id ),
			'post'    => $this->convert( get_post( $post_id ) ),
		] );
	}

	/**
	 * Remove post from series.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	protected function callback_delete( \WP_REST_Request $request ) {
		$series_id = (int) $request->get_param( 'series_id' );
		$post_id   = (int) $request->get_param( 'post_id' );
		$series    = taro_series_get( $post_id );
		if ( ! $series || ( $series_id !== $series->ID ) ) {
			// translators: %1$d is post id, %2$d is series id.
			return new \WP_Error( 'rest_api_error', sprintf( __( '#%1$d is not a part of series #%2$d', 'taro-series' ), $post_id, $series_id ), [
				'status' => 400,
				'series' => $series,
			] );
		}
		delete_post_meta( $post_id, taro_series_meta_key(), $series_id );
		return new \WP_REST_Response( [
			'success' => true,
			// translators: %d is post ID.
			'message' => sprintf( __( '#%d is removed from the articles in this series.', 'taro-series' ), $post_id ),
		] );
	}

	/**
	 * Convert post object for REST API.
	 *
	 * @param \WP_Post $post Post object.
	 * @return array
	 */
	protected function convert( $post ) {
		return [
			'id'            => $post->ID,
			'title'         => get_the_title( $post ),
			'postType'      => get_post_type( $post ),
			'postTypeLabel' => get_post_type_object( get_post_type( $post ) )->label,
			'link'          => ( 'publish' === $post->post_status ) ? get_permalink( $post ) : get_preview_post_link( $post ),
			'editLink'     => get_edit_post_link( $post, 'api' ),
			'date'          => $post->post_date,
			'dateFormatted' => mysql2date( get_option( 'date_format', 'Y-m-d' ) . ' ' . get_option( 'time_format', 'H:i:s' ), $post->post_date ),
			'status'        => $post->post_status,
			'statusLabel'   => get_post_status_object( $post->post_status )->label
		];
	}

	/**
	 * @inheritDoc
	 */
	public function permission_callback( $request ) {
		return current_user_can( 'edit_post', $request->get_param( 'series_id' ) );
	}
}
