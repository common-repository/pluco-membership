<?php
/**
 * http://pluginscorner.com
 *
 * @package plco-membership
 */

namespace PLCOMembership\front\endpoints;

use PLCODashboard\classes\PLCO_REST_Controller;
use PLCOMembership\admin\classes\PLCOM_Helpers;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class PLCO_Payments_Controller extends PLCO_REST_Controller {

	public $base = 'payments';

	/**
	 * Register Routes
	 */
	public function register_routes() {

		register_rest_route( self::$namespace . self::$version, '/' . $this->base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_payments' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
				'args'                => array(),
			),
		) );
	}

	/**
	 * Fetches payments for the payment table
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_payments( $request ) {
		/**
		 * @var WP_REST_Request $request
		 */
		$params = $request->get_params();
		$result = PLCOM_Helpers::get_payments($params);
		unset($result["_"]);

		return new WP_REST_Response( $result, 200 );
	}
}
