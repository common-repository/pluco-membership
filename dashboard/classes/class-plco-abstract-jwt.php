<?php

/**
 * PluginsCorner - https://pluginscorner.com.com
 *
 * @package plco-dashboard
 */

namespace PLCODashboard\classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

abstract class PLCO_Abstract_JWT {

	/**
	 * Useful function to create a secure key.
	 * This key should preferably be kept in an .env file
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function generateSecretKey() {
		$randomBytes = function_exists( 'random_bytes' )
			? random_bytes( 32 )
			: openssl_random_pseudo_bytes( 32 );

		return bin2hex( $randomBytes );
	}

	/**
	 * Method used to create a base 64 URL encode
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public function base64UrlEncode( $text ) {
		return str_replace(
			[ '+', '/', '=' ],
			[ '-', '_', '' ],
			base64_encode( $text ) );
	}

	/**
	 * obtain the Secret Key from within the .env file
	 *
	 * @return string - Secret Key
	 */
	public function getEnvSecret() {
		return get_option('plco_environment_secret');
	}
}
