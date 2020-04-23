<?php

defined( 'ABSPATH' ) or die();

/**
 * Gravity Forms Zammad API Library.
 *
 * @since     1.0
 * @package   GravityForms
 * @author    Johan VIVIEN (Hotfirenet)
 * @copyright Copyright (c) 2020, Hotfirenet
 */
class GF_Zammad_API {

	private $api_path = '/api/v1';

	/**
	 * Initialize Zammad API library.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string $zammad_url   URL of the zammad instance.
	 * @param string $zammad_token Zammad Baerer token.
	 */
	public function __construct( $zammad_url, $zammad_token ) {
		
		$this->zammad_url    = $zammad_url;
		$this->zammad_token  = $zammad_token;
		

		gf_zammad()->log_debug( __METHOD__ . '(): Response: ' . $zammad_url );
	}

	public function create_user( $user ) {
		return $this->make_request( 'users', $user, 'POST' );
	}

	public function get_groups() {
		return $this->make_request( 'groups' );
	}

	public function get_ticket_states() {
		return $this->make_request( 'ticket_states' );
	}

	public function get_ticket_priorities() {
		return $this->make_request( 'ticket_priorities' );
	}

	public function create_ticket( $ticket ) {
		return $this->make_request( 'tickets', $ticket, 'POST' );
	}

	// # REQUEST METHODS -----------------------------------------------------------------------------------------------

	/**
	 * Make API request.
	 *
	 * @since  1.0
	 * @access private
	 *
	 * @param string $action     Request action.
	 * @param array  $options    Request options.
	 * @param string $auth_type  Authentication token to use. Defaults to server.
	 * @param string $method     HTTP method. Defaults to GET.
	 * @param string $return_key Array key from response to return. Defaults to null (return full response).
	 *
	 * @return array|string|bool|WP_Error
	 */
	private function make_request( $action, $options = array(), $method = 'GET', $return_key = null ) {

		// Build request options string.
		$request_options = 'GET' === $method ? '?' . http_build_query( $options ) : null;

		// Build request URL.
		$request_url = $this->zammad_url . $this->api_path . '/' . $action . $request_options;
		gf_zammad()->log_debug( __METHOD__ . '(): URL: ' . $request_url );

		// Build request arguments.
		$request_args = array(
			'body'      => 'GET' !== $method ? json_encode( $options ) : '',
			'method'    => $method,
			'sslverify' => false,
			'headers'   => array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json',
			),
		);

		$request_args['headers']['Authorization'] = 'Bearer ' . $this->zammad_token;

		// Execute API request.
		$response = wp_remote_request( $request_url, $request_args );

		if ( is_wp_error( $response ) ) {

			return $response;
		}

		switch ( wp_remote_retrieve_response_code( $response ) ) {
			case 200:
			// Created resource.
			case 201:
				// Convert JSON response to array.
				$response = json_decode( $response['body'], true );
				break;
			// Updated resource.
			case 204:
				return true;
			default:
				return new WP_Error( wp_remote_retrieve_response_code( $response ), wp_remote_retrieve_response_message( $response ), wp_remote_retrieve_body( $response ) );
		}

		// If a return key is defined and array item exists, return it.
		if ( ! empty( $return_key ) && rgars( $response, $return_key ) ) {
			return rgars( $response, $return_key );
		}

		return $response;
	}

}