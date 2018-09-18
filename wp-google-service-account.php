<?php
/**
 * Library for accessing a Google Service Account on WordPress
 *
 * @package WP-API-Libraries\WP-Google-Service-Account
 */

/*
 * Plugin Name: WP Google Service Account
 * Plugin URI: https://wp-api-libraries.com/
 * Description: Library that facilitates the authentication of service accounts on wordpress.
 * Author: WP API Libraries
 * Version: 1.0.0
 * Author URI: https://wp-api-libraries.com
 * GitHub Plugin URI: https://github.com/imforza
 * GitHub Branch: master
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'WPGoogleServiceAccount' ) ) {

	/**
	 * A WordPress API library for accessing a Google Service Account.
	 *
	 * @version 1.1.0
	 * @link https://developers.google.com/identity/protocols/OAuth2ServiceAccount Documentation
	 * @package WP-API-Libraries\WP-Google-Service-Account
	 * @author Santiago Garza <https://github.com/sfgarza>
	 * @author imFORZA <https://github.com/imforza>
	 */
	class WPGoogleServiceAccount {

		/**
		 * Service Account key
		 *
		 * JSON decoded array of the service account credentials provided by google.
		 *
		 * @var string
		 */
		protected $service_account_key;
		
		/**
		 * Auth Scope.
		 *
		 * @var string
		 */
		protected $scope;
		
		/**
		 * The GCP Service token.
		 * 
		 * @var array
		 */
		protected $gcp_service_token;

		/**
		 * Authentication API Endpoint
		 *
		 * @var string
		 * @access protected
		 */
		protected $base_uri = 'https://www.googleapis.com/oauth2/v4/token';

		/**
		 * Class constructor.
		 *
		 * @param string $api_key  Auth token.
		 */
		public function __construct( array $service_account_key, $scope) {
			$this->service_account_key = $service_account_key;
			$this->scope = $scope;
		} 
		
		public function get_token(){
			// Retrieve and return cached token or auth a new one.
			if ( ( false === ( $this->gcp_service_token = get_transient( 'gcp_service_token' ) ) ) ||  $this->gcp_service_token['expiration'] < time()  ) {
				$this->gcp_service_token['access_token'] = $this->authenticate();
				$this->gcp_service_token['expiration'] = $this->expiration;
				set_transient( 'gcp_service_token', $this->gcp_service_token, HOUR_IN_SECONDS );
			}
			
			return $this->gcp_service_token['access_token'];
		}
    
		/**
		 * Build JWT assertion used for service account auth.
		 * 
		 * @return string A Base64 URL encoded string used to auth account.
		 */
    protected function build_assertion( ){
			
      //{Base64url encoded JSON header}
      $jwtHeader = $this->encode(array(
        "alg" => "RS256",
        "typ" => "JWT"
      ), true );
      
      //{Base64url encoded JSON claim set}
      $now = time();
			$this->expiration = $now + 3600;
      $jwtClaim = $this->encode(array(
        "iss" => $this->service_account_key['client_email'],
        "scope" => $this->scope,
        "aud" => $this->base_uri,
        "exp" => $this->expiration,
        "iat" => $now
      ), true);
      
			//The base string for the signature: {Base64url encoded JSON header}.{Base64url encoded JSON claim set}
      openssl_sign(
        $jwtHeader.".".$jwtClaim,
        $jwtSig,
        $this->service_account_key['private_key'],
        "sha256WithRSAEncryption"
      );
      
      $jwtSign = $this->encode($jwtSig);
      
      return $jwtHeader.".".$jwtClaim.".".$jwtSign;
    }
    
		/**
		 * Encode base64 URL encode data.
		 *  
		 * @param  array   $data        Data to be encoded.
		 * @param  boolean $json_encode Whether to encode data to JSON as well.
		 * @return string               Base64 url encoded string.
		 */
    protected function encode( $data, bool $json_encode = false ) { 
      $data = ( $json_encode ) ? json_encode( $data ) : $data;
      return rtrim( strtr( base64_encode( $data ), '+/', '-_'), '='); 
    }
		
		/**
		 * Fetch the request from the API.
		 *
		 * @access private
		 * @return array|WP_Error Request results or WP_Error on request failure.
		 */
		protected function authenticate() {
			// Start building query.
			$args = array(
				'method' => 'POST',
				'timeout' => 20,
				'headers' => array(
						'Content-Type' => 'application/x-www-form-urlencoded',
				),
				'body' => array( 
					'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 
					'assertion' => $this->build_assertion()
				)
			);

			// Make the request.
			$response = wp_remote_request( $this->base_uri, $args );

			// Retrieve Status code & body.
			$code = wp_remote_retrieve_response_code( $response );
			$body = json_decode( wp_remote_retrieve_body( $response ) );
			
			// Return WP_Error if request is not successful.
			if ( ! $this->is_status_ok( $code ) ) {
				return new WP_Error( 'response-error', sprintf( __( 'Status: %d', 'wp-google-service-account' ), $code ), $body );
			}

			return $body->access_token;
		}

		/**
		 * Check if HTTP status code is a success.
		 *
		 * @param  int $code HTTP status code.
		 * @return boolean       True if status is within valid range.
		 */
		protected function is_status_ok( $code ) {
			return ( 200 <= $code && 300 > $code );
		}
	}
}
