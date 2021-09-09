<?php

namespace Tarosky\Series\Pattern;


/**
 * Rest API pattern.
 *
 * @package taro-series
 */
abstract class RestApi extends Singleton {

	/**
	 * @var string Name space.
	 */
	protected $namespace = 'taro-series/v1';

	/**
	 * Route name.
	 *
	 * @return string
	 */
	abstract protected function route();

	/**
	 * @inheritDoc
	 */
	protected function init() {
		add_action( 'rest_api_init', [ $this, 'register_api' ] );
	}

	/**
	 * Get methods.
	 *
	 * @return string|string[]
	 */
	protected function get_methods() {
		return 'GET';
	}

	/**
	 * Get arguments.
	 *
	 * @param string $method Method name.
	 * @return array
	 */
	abstract protected function get_args( $method );

	/**
	 * Callback for endpoint.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	abstract public function callback( \WP_REST_Request  $request );

	/**
	 * Register API.
	 */
	public function register_api() {
		register_rest_route( $this->namespace, $this->route(), array_map( function( $method ) {
			return [
				'methods'             => $method,
				'args'                => $this->get_args( $method ),
				'callback'            => [ $this, 'callback' ],
				'permission_callback' => [ $this, 'permission_callback' ],
			];
		}, (array) $this->get_methods() ) );
	}

	/**
	 * Permission for REST.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool|\WP_Error
	 */
	public function permission_callback( $request ) {
		return current_user_can( 'edit_posts' );
	}
}
