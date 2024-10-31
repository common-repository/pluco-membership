<?php
/**
 * http://pluginscorner.com
 *
 * @package plco-membership
 */

namespace PLCODashboard\front\endpoints;

use PLCODashboard\admin\classes\PLCO_Const;
use PLCODashboard\admin\classes\PLCO_Helpers;
use PLCODashboard\classes\PLCO_Connection;
use PLCODashboard\classes\PLCO_License_Manager;
use PLCODashboard\classes\PLCO_REST_Controller;
use PLCODashboard\front\classes\PLCO_JWT;
use PLCODashboard\front\classes\PLCO_Paypal_Payment_Method;
use PLCOMembership\admin\classes\PLCOM_Const;
use PLCOMembership\admin\classes\PLCOM_Helpers;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class PLCO_License_Controller extends PLCO_REST_Controller {

	public $base = 'license';

	/**
	 * Register Routes
	 */
	public function register_routes() {

		register_rest_route( self::$namespace . self::$version, '/' . $this->base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'add_license' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
				'args'                => array(),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'remove_license' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
				'args'                => array(),
			),
		) );

		register_rest_route( self::$namespace . self::$version, '/' . $this->base . '/fetch', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'fetch_licenses' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
				'args'                => array(),
			)
		) );
	}

	/**
	 * Fetches the licenses from the server
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_REST_Response
	 * @throws \Exception
	 */
	public function fetch_licenses( $request ) {
		/**
		 * @var WP_REST_Request $request
		 */
		$params  = $request->get_params();
		$license = PLCO_JWT::checkJWTExpiration();

		$args = array(
			'sslverify' => false,
			'timeout'   => 120,
			'headers'   => array(
				'Accept'        => 'application/json',
				'Authorization' => 'Bearer ' . $license['jwt_access'],
			)
		);

		$api_url = PLCO_Const::get_api_url();

		$response = wp_remote_get( $api_url . PLCO_Const::REST_NAMESPACE . '/license/get_available_user_licenses', $args );
		$result   = PLCO_Const::process_api_response( $response );

		if ( $result->data->status > 400 ) {
			return new WP_Error( 'call-failed', __( $result->message, PLCO_Const::T ) );
		} else {
			return new WP_REST_Response( $result, 200 );
		}
	}


	/**
	 * Adds the license
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_REST_Response
	 * @throws \Exception
	 */
	public function add_license( $request ) {

		$params = $request->get_params();
		/** @var PLCO_License_Manager $license */
		$license = PLCO_JWT::checkJWTExpiration();

		if(!empty($license['jwt_access'])) {
			$args = array(
				'sslverify' => false,
				'timeout'   => 120,
				'headers'   => array(
					'Accept'        => 'application/json',
					'Authorization' => 'Bearer ' . $license['jwt_access'],
				),
				'body'      => array( 'license_key' => $params['license_key'], 'domain' => get_site_url() )
			);

			$api_url  = PLCO_Const::get_api_url();
			$response = wp_remote_get( $api_url . PLCO_Const::REST_NAMESPACE . '/license/check', $args );
			$result   = PLCO_Const::process_api_response( $response );


			$control = $result->control;
			unset( $result->control );

			update_option( 'plco_license', (array) $result );
			set_transient( 'plco_control', $control, 1209600 );

			$result->control  = $control;
			$result->products = maybe_unserialize( $result->products );

			return new WP_REST_Response( $result, 200 );
		}

		return new WP_REST_Response( '', 200 );

	}


	/**
	 * Delete the options
	 */
	public function remove_license() {
		$license = PLCO_JWT::checkJWTExpiration();


		$args = array(
			'sslverify' => false,
			'timeout'   => 120,
			'method' => 'DELETE',
			'headers'   => array(
				'Accept'        => 'application/json',
				'Authorization' => 'Bearer ' . $license['jwt_access'],
			),
			'body'      => array( 'license_key' => $license['license_key'], 'domain' => get_site_url() )
		);


		$api_url  = PLCO_Const::get_api_url();
		$response = wp_remote_post( $api_url . PLCO_Const::REST_NAMESPACE . '/license/remove_domain', $args );
		$result   = PLCO_Const::process_api_response( $response );


		if($result) {
			delete_option( 'plco_license' );
			delete_transient( 'plco_control' );

			$license = PLCO_License_Manager::reset_license_instance();
		}


		return new WP_REST_Response( $license, 200 );
	}



}
