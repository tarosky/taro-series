<?php
/**
 * Tests for REST API endpoints.
 *
 * @package taro-series
 */

/**
 * Test taro-series/v1 endpoints.
 */
class TestRestApi extends SeriesTestCase {

	/**
	 * @var int Editor user ID.
	 */
	protected static $editor;

	/**
	 * @var int Subscriber user ID.
	 */
	protected static $subscriber;

	/**
	 * Create users.
	 *
	 * @param WP_UnitTest_Factory $factory Factory.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$editor     = $factory->user->create( [ 'role' => 'editor' ] );
		self::$subscriber = $factory->user->create( [ 'role' => 'subscriber' ] );
	}

	/**
	 * Initialize REST server.
	 */
	public function set_up() {
		parent::set_up();
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init', $wp_rest_server );
	}

	/**
	 * Reset REST server.
	 */
	public function tear_down() {
		global $wp_rest_server;
		$wp_rest_server = null;
		parent::tear_down();
	}

	/**
	 * Dispatch request.
	 *
	 * @param string $method Method.
	 * @param string $route  Route.
	 * @param array  $params Parameters.
	 * @return WP_REST_Response
	 */
	protected function request( $method, $route, $params = [] ) {
		$request = new WP_REST_Request( $method, '/taro-series/v1/' . $route );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}
		return rest_get_server()->dispatch( $request );
	}

	/**
	 * Routes are registered.
	 */
	public function test_routes() {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/taro-series/v1/series/(?P<series_id>\d+)', $routes );
		$this->assertArrayHasKey( '/taro-series/v1/available/(?P<post_type>[^/]+)', $routes );
	}

	/**
	 * Anonymous and subscriber can not access.
	 */
	public function test_permission() {
		$series = $this->create_series();
		$this->assertSame( 401, $this->request( 'GET', 'series/' . $series->ID )->get_status() );
		$this->assertSame( 401, $this->request( 'GET', 'available/post' )->get_status() );
		wp_set_current_user( self::$subscriber );
		$this->assertSame( 403, $this->request( 'GET', 'series/' . $series->ID )->get_status() );
		$this->assertSame( 403, $this->request( 'GET', 'available/post' )->get_status() );
	}

	/**
	 * Add, list and remove articles.
	 */
	public function test_series_articles_lifecycle() {
		wp_set_current_user( self::$editor );
		$series  = $this->create_series();
		$article = $this->create_article( 0, [ 'post_title' => 'Lonely Article' ] );

		// Initially empty.
		$response = $this->request( 'GET', 'series/' . $series->ID );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 0, $response->get_data()['total'] );

		// Add.
		$response = $this->request( 'POST', 'series/' . $series->ID, [ 'post_id' => $article->ID ] );
		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['success'] );
		$this->assertSame( $series->ID, taro_series_parent_id( $article ) );

		// Adding twice fails.
		$response = $this->request( 'POST', 'series/' . $series->ID, [ 'post_id' => $article->ID ] );
		$this->assertSame( 400, $response->get_status() );

		// Listed.
		$data = $this->request( 'GET', 'series/' . $series->ID )->get_data();
		$this->assertSame( 1, $data['total'] );
		$this->assertSame( $article->ID, $data['posts'][0]['id'] );
		$this->assertSame( 'Lonely Article', $data['posts'][0]['title'] );
		$this->assertSame( 'post', $data['posts'][0]['postType'] );
		$this->assertSame( 'publish', $data['posts'][0]['status'] );

		// Removing from another series fails.
		$other    = $this->create_series();
		$response = $this->request( 'DELETE', 'series/' . $other->ID, [ 'post_id' => $article->ID ] );
		$this->assertSame( 400, $response->get_status() );

		// Remove.
		$response = $this->request( 'DELETE', 'series/' . $series->ID, [ 'post_id' => $article->ID ] );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 0, taro_series_parent_id( $article ) );
	}

	/**
	 * Search articles to add.
	 */
	public function test_series_articles_search() {
		wp_set_current_user( self::$editor );
		$series = $this->create_series();
		$this->create_article( 0, [ 'post_title' => 'Unique Banana Story' ] );
		$this->create_article( 0, [ 'post_title' => 'Something else' ] );
		$data = $this->request( 'GET', 'series/' . $series->ID, [ 's' => 'Banana' ] )->get_data();
		$this->assertSame( 1, $data['total'] );
		$this->assertSame( 'Unique Banana Story', $data['posts'][0]['title'] );
	}

	/**
	 * Series ID must be a series.
	 */
	public function test_invalid_series_id() {
		wp_set_current_user( self::$editor );
		$article = $this->create_article();
		$this->assertSame( 400, $this->request( 'GET', 'series/' . $article->ID )->get_status() );
	}

	/**
	 * Available series for post type.
	 */
	public function test_available_series() {
		wp_set_current_user( self::$editor );
		$old = $this->create_series( [ 'post_title' => 'Old Series', 'post_date' => '2020-01-01 00:00:00' ] );
		$new = $this->create_series( [ 'post_title' => 'New Series', 'post_date' => '2024-01-01 00:00:00' ] );

		$response = $this->request( 'GET', 'available/post' );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( [ $new->ID, $old->ID ], wp_list_pluck( $response->get_data(), 'id' ) );

		$data = $this->request( 'GET', 'available/post', [ 'p' => $old->ID ] )->get_data();
		$this->assertCount( 1, $data );
		$this->assertSame( 'Old Series', $data[0]['title'] );
		$this->assertNotEmpty( $data[0]['edit_link'] );

		$data = $this->request( 'GET', 'available/post', [ 's' => 'New' ] )->get_data();
		$this->assertSame( [ $new->ID ], wp_list_pluck( $data, 'id' ) );

		// Post type out of series is invalid.
		$this->assertSame( 400, $this->request( 'GET', 'available/page' )->get_status() );
	}
}
