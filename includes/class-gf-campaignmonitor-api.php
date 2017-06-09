<?php

/**
 * Campaign Monitor API library.
 *
 * @since     1.0
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2017, Rocketgenius
 */
class GF_CampaignMonitor_API {

	/**
	 * Base Campaign Monitor API URL.
	 *
	 * @since  3.5
	 * @var    string
	 * @access protected
	 */
	public static $api_url = 'https://api.createsend.com/api/v3.1/';

	/**
	 * Initialize Campaign Monitor API library.
	 *
	 * @since  3.5
	 * @access public
	 *
	 * @param string $api_key   API key.
	 * @param string $client_id Client ID.
	 */
	public function __construct( $api_key = null, $client_id = null ) {

		$this->api_key   = $api_key;
		$this->client_id = $client_id;

	}





	// # AUTH METHODS --------------------------------------------------------------------------------------------------

	/**
	 * Test Campaign Monitor authentication credentials.
	 *
	 * @since  3.5
	 * @access public
	 *
	 * @uses GF_CampaignMonitor_API::make_request()
	 *
	 * @return array
	 */
	public function auth_test() {

		return $this->make_request( 'systemdate' );

	}





	// # CLIENT METHODS -------------------------------------------------------------------------------------------------

	/**
	 * Get Campaign Monitor client details.
	 *
	 * @since  3.5
	 * @access public
	 *
	 * @param string $client_id Client ID.
	 *
	 * @uses GF_CampaignMonitor_API::make_request()
	 *
	 * @return array
	 */
	public function get_client( $client_id = '' ) {

		return $this->make_request( 'clients/' . $client_id );

	}

	/**
	 * Get all Campaign Monitor clients.
	 *
	 * @since  3.5
	 * @access public
	 *
	 * @uses GF_CampaignMonitor_API::make_request()
	 *
	 * @return array
	 */
	public function get_clients() {

		return $this->make_request( 'clients' );

	}





	// # LIST METHODS --------------------------------------------------------------------------------------------------

	/**
	 * Get custom fields for Campaign Monitor list.
	 *
	 * @since  3.5
	 * @access public
	 *
	 * @uses GF_CampaignMonitor_API::make_request()
	 *
	 * @return array
	 */
	public function get_custom_fields( $list_id = '' ) {
		
		return $this->make_request( 'lists/' . $list_id . '/customfields' );
		
	}

	/**
	 * Get Campaign Monitor list details.
	 *
	 * @since  3.5
	 * @access public
	 *
	 * @param string $list_id List ID.
	 *
	 * @uses GF_CampaignMonitor_API::make_request()
	 *
	 * @return array
	 */
	public function get_list( $list_id = '' ) {

		return $this->make_request( 'lists/' . $list_id );

	}

	/**
	 * Get Campaign Monitor lists for client.
	 *
	 * @since  3.5
	 * @access public
	 *
	 * @param string $list_id List ID.
	 *
	 * @uses GF_CampaignMonitor_API::make_request()
	 *
	 * @return array
	 */
	public function get_lists() {

		return $this->make_request( 'clients/' . $this->client_id . '/lists' );

	}





	// # SUBSCRIBER METHODS --------------------------------------------------------------------------------------------

	/**
	 * Add subscriber to a Campaign Monitor list.
	 *
	 * @since  3.5
	 * @access public
	 *
	 * @param array  $subscriber Subscriber attributes.
	 * @param string $list_id    List ID.
	 *
	 * @uses GF_CampaignMonitor_API::make_request()
	 *
	 * @return array
	 */
	public function add_subscriber( $subscriber = array(), $list_id = '' ) {
		
		return $this->make_request( 'subscribers/' . $list_id, $subscriber, 'POST' );
		
	}





	// # HELPER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Define Campaign Monitor API key.
	 *
	 * @since  3.5
	 * @access public
	 *
	 * @param string $api_key API key.
	 */
	public function set_api_key( $api_key = '' ) {

		$this->api_key = $api_key;

	}

	/**
	 * Define Campaign Monitor client ID.
	 *
	 * @since  3.5
	 * @access public
	 *
	 * @param string $client_id Client ID.
	 */
	public function set_client_id( $client_id = '' ) {

		$this->client_id = $client_id;

	}





	// # API REQUESTS --------------------------------------------------------------------------------------------------

	/**
	 * Make API request.
	 *
	 * @since  3.5
	 * @access private
	 *
	 * @param string $path    Request path.
	 * @param array  $options Request option.
	 * @param string $method  Request method. Defaults to GET.
	 *
	 * @return array
	 */
	private function make_request( $path, $options = array(), $method = 'GET' ) {

		// Build request URL.
		$request_url = self::$api_url . $path . '.json';

		// Add options if this is a GET request.
		if ( 'GET' === $method ) {
			$request_url = add_query_arg( $options, $request_url );
		}

		// Build request arguments.
		$args = array(
			'body'    => 'GET' !== $method ? json_encode( $options ) : null,
			'method'  => $method,
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $this->api_key . ':x' ),
			),
		);

		// Execute request.
		$response = wp_remote_request( $request_url, $args );

		// If request attempt threw a WordPress error, throw exception.
		if ( is_wp_error( $response ) ) {
			throw new \Exception( $response->get_error_message() );
		}

		// Decode response.
		$response = json_decode( $response['body'], true );

		// If error response was received, throw exception.
		if ( isset( $response['Code'] ) ) {
			throw new \Exception( $response['Message'] );
		}

		return $response;

	}

}
