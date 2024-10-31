<?php
/**
 * Plugins Corner - https://pluginscorner.com
 *
 * @package dashboard
 */

namespace PLCODashboard\classes;

use WP_REST_Request;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class PLCO_License_Manager {

	/**
	 * @var array|mixed|void
	 */
	private static $license;

	/**
	 * @var null
	 */
	private static $instance = null;

	private function __construct() {
		self::set_details();
	}

	private static function set_details() {
		$defaults = array(
			'license_key'        => '',
			'uses'               => 0,
			'user_membership_id' => 0,
			'user_id'            => 0,
			'uses_limit'         => 0,
			'domains_used'       => array(),
			'products'           => array(),
			'created_at'         => '0000-00-00 00:00:00',
			'date_expires'       => '0000-00-00 00:00:00',
			'control'            => '',
		);

		$license = get_option( 'plco_license' );

		if ( ! $license ) {
			self::$license = $defaults;
		} else {
			$control             = get_transient( 'plco_control' );
			$license['control']  = $control ? $control : '';
			$license['products'] = maybe_unserialize( $license['products'] );

			self::$license = $license;
		}

		self::$license['jwt_access']  = get_option( 'plco_jwt_access', "" );
		if(self::$license['jwt_access']) {
			$tokenParts   = explode( '.', self::$license['jwt_access'] );
			$tokenPayload = json_decode(base64_decode( $tokenParts[1] ));
			self::$license['user_id'] = (int) $tokenPayload->user_id;
		}

		self::$license['jwt_refresh'] = get_option( 'plco_jwt_refresh', "" );
	}

	public static function reset_license_instance() {
		self::$instance = new self();

		return (array) self::$license;
	}

	/**
	 * @return PLCO_License_Manager|array
	 */
	public static function get_license() {
		// Check if instance is already exists
		if ( self::$instance == null ) {
			self::$instance = new self();
		}

		return (array) self::$license;

	}
}
