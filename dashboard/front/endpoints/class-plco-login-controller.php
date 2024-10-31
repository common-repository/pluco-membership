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

class PLCO_Login_Controller extends PLCO_REST_Controller {

	public $base = 'login';

	/**
	 * Register Routes
	 */
	public function register_routes() {

		register_rest_route( self::$namespace . self::$version, '/' . $this->base, array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'login' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
				'args'                => array(),
			)
		) );
	}

	/**
	 * Saves the connection to the DB
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function login( $request ) {
		/**
		 * @var WP_REST_Request $request
		 */
		$params =$request->get_params();

		if(isset($params['disconnect'])) {
			delete_option('plco_jwt_access');
			delete_option('plco_jwt_refresh');
			return new WP_REST_Response( true, 200 );
		}

		$args = array(
			'sslverify' => false,
			'timeout'   => 120,
			'headers'   => array(
				'Accept' => 'application/json',
			),
			'body'      => array(
				'data' => base64_encode( json_encode($params ) )
			),
		);


		$api_url = PLCO_Const::get_api_url();
		$response = wp_remote_post( $api_url . PLCO_Const::REST_NAMESPACE . '/license/login', $args );
		$result = PLCO_Const::process_api_response( $response );


		if ( isset($result->data->status) && $result->data->status > 400) {
			return new WP_Error( 'login-failed', __( $result->message, PLCO_Const::T ) );
		} else {
			update_option('plco_jwt_access', $result->access);
			update_option('plco_jwt_refresh', $result->refresh);
			return new WP_REST_Response( $result, 200 );
		}
	}

}
