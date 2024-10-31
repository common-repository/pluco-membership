<?php
/**
 * Plugins Corner Membership - https://pluginscorner.com.com
 *
 * @package plco-membership
 */

namespace PLCODashboard\front\classes;

use Exception;
use PLCODashboard\admin\classes\PLCO_Const;
use PLCODashboard\classes\PLCO_Abstract_JWT;
use DateTime;
use PLCODashboard\classes\PLCO_License_Manager;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class PLCO_JWT extends PLCO_Abstract_JWT {
	/**
	 * Time expired
	 */
	const TOKEN_EXPIRED = 0;

	/**
	 * Token is Valid
	 */
	const TOKEN_VALID = 1;

	/**
	 * Invalid Signature
	 */
	const TOKEN_INVALID_SIGNATURE = 2;

	/**
	 * @var string secret key
	 */
	protected $secret = '';

	/**
	 * @var null token
	 */
	protected $token = null;

	/**
	 * @var null token
	 */
	protected $refresh = null;

	/**
	 * @var array payload
	 */
	protected $payload = array();

	/**
	 * PLCO_JWT constructor.
	 *
	 * @throws \Exception
	 */
	public function __construct() {
		$secret = $this->getEnvSecret();
		if ( ! $this->getEnvSecret() ) {
			$secret = $this->generateSecretKey();
			update_option( "plco_environment_secret", $secret );
		}

		$this->setSecret( $secret );
	}

	/**
	 * Set secret key
	 *
	 * @param $secret
	 *
	 * @return $this
	 */
	public function setSecret( $secret ) {
		$this->secret = $secret;

		return $this;
	}

	/**
	 * get secret key
	 *
	 * @return string
	 */
	protected function getSecret() {
		return $this->secret;
	}

	/**
	 * Set data to be encoded
	 *
	 * @param array $payload
	 *
	 * @return $this
	 */
	public function setPayload( $payload = array() ) {
		$this->payload = $payload;

		return $this;
	}

	/**
	 * return payload Data
	 *
	 * @return false|string
	 */
	protected function getPayload() {
		return json_encode( $this->payload );
	}

	protected function getSignature( $header, $payload ) {
		return hash_hmac(
			'sha256',
			"{$header}.{$payload}",
			$this->getSecret(),
			true
		);
	}

	protected function getHeader( $refresh = false ) {
		return json_encode( [
			'typ' => $refresh ? 'REFRESH' : 'ACCESS',
			'alg' => 'HS256'
		] );
	}

	/**
	 * Generate a new token
	 *
	 * @param bool $refresh
	 *
	 * @return |null
	 */
	public function generateToken( $refresh = false ) {
		$base64UrlHeader    = $this->base64UrlEncode( $this->getHeader( $refresh ) );
		$base64UrlPayload   = $this->base64UrlEncode( $this->getPayload() );
		$base64UrlSignature = $this->base64UrlEncode( $this->getSignature( $base64UrlHeader, $base64UrlPayload ) );

		$this->setToken( "{$base64UrlHeader}.{$base64UrlPayload}.{$base64UrlSignature}" );

		return $this->getToken();
	}

	public function setToken( $token ) {
		$this->token = $token;

		return $this;
	}

	public function getToken() {
		return $this->token;
	}

	/**
	 * Check if current token in the this class is valid
	 *
	 * @param bool $refresh
	 *
	 * @return int
	 * @throws \Exception
	 */
	public function validate( $refresh = false ) {
		$tokenParts = explode( '.', $this->getToken() );

		$tokenHeader       = base64_decode( $tokenParts[0] );
		$tokenPayload      = base64_decode( $tokenParts[1] );
		$signatureProvided = $tokenParts[2];

		// Token expired?
		if ( $this->isTokenExpired( json_decode( $tokenPayload )->exp ) ) {
			return self::TOKEN_EXPIRED;
		}

		if(!$refresh && json_decode($tokenHeader)->typ !== "ACCESS") {
			return self::TOKEN_INVALID_SIGNATURE;
		}

		if($refresh && json_decode($tokenHeader)->typ !== "REFRESH") {
			return self::TOKEN_INVALID_SIGNATURE;
		}

		// build signature from header and payload
		$base64UrlHeader    = $this->base64UrlEncode( $tokenHeader );
		$base64UrlPayload   = $this->base64UrlEncode( $tokenPayload );
		$signature          = $this->getSignature( $base64UrlHeader, $base64UrlPayload );
		$base64UrlSignature = $this->base64UrlEncode( $signature );

		if ( ( $base64UrlSignature === $signatureProvided ) === false ) {
			return self::TOKEN_INVALID_SIGNATURE;
		}

		return self::TOKEN_VALID;
	}

	/**
	 * Check if token has been expired
	 *
	 * @param int $expireTime
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function isTokenExpired( $expireTime = 0 ) {
		$now = ( new DateTime( 'now' ) )->getTimestamp();

		return ( $expireTime - $now < 0 );
	}

	/**
	 * Checks if the tokens are valid
	 *
	 * @return array|PLCO_License_Manager|WP_Error
	 * @throws Exception
	 */
	public static function checkJWTExpiration() {
		$license = PLCO_License_Manager::get_license();

		$jwt = new PLCO_JWT();

		$tokenParts   = explode( '.', $license['jwt_access'] );
		$tokenPayload = base64_decode( $tokenParts[1] );

		$is_expired = $jwt->isTokenExpired( json_decode( $tokenPayload )->exp );

		if ( $is_expired ) {
			$args = array(
				'sslverify' => false,
				'timeout'   => 120,
				'headers'   => array(
					'Accept'        => 'application/json',
					'Authorization' => 'Bearer ' . $license['jwt_refresh'],
				)
			);

			$api_url  = PLCO_Const::get_api_url();
			$response = wp_remote_get( $api_url . PLCO_Const::REST_NAMESPACE . '/license/refresh', $args );
			$result   = PLCO_Const::process_api_response( $response );

			if ( $result->data->status > 400 ) {
				update_option( 'plco_jwt_access', '' );
				update_option( 'plco_jwt_refresh', '' );
				delete_option('plco_license');
			} else {
				update_option( 'plco_jwt_access', $result->access );
				update_option( 'plco_jwt_refresh', $result->refresh );
			}

			$license = PLCO_License_Manager::reset_license_instance();
		}

		return $license;
	}
}
