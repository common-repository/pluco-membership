<?php
/**
 * http://pluginscorner.com
 *
 * @package plco-membership
 */

namespace PLCODashboard\front\endpoints;

use PLCODashboard\admin\classes\PLCO_Const;
use PLCODashboard\classes\PLCO_REST_Controller;
use PLCODashboard\front\classes\PLCO_JWT;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class PLCO_Replies_Controller extends PLCO_REST_Controller {

	public $base = 'replies';

	/**
	 * Register Routes
	 */
	public function register_routes() {

		register_rest_route( self::$namespace . self::$version, '/' . $this->base, array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'add_reply' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
				'args'                => array(),
			),
		) );

		register_rest_route( self::$namespace . self::$version, '/' . $this->base . '/(?P<ID>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'add_reply' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
				'args'                => array(),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_reply' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
				'args'                => array(),
			),
		) );

	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function add_reply( $request ) {
		$params = $request->get_params();
		$license = PLCO_JWT::checkJWTExpiration();

		if ( ! empty( $license['jwt_access'] ) ) {
			$response = PLCO_Const::send_request( 'plcos/v1/replies', $params );
			$result   = PLCO_Const::process_api_response( $response );
		} else {
			return new WP_Error( 'no-license', __( 'You don\'t have access to this section, please re-validate your license.', PLCO_Const::T ) );
		}
		if ( $result->status == 401 ) {
			return new WP_Error( 'no-license', __( $result->msg, PLCO_Const::T ) );
		}

		return new WP_REST_Response( (array) $result->msg, 200 );

	}

	/**
	 * Delete a reply
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_reply( $request ) {
		/**
		 * @var WP_REST_Request $request
		 */

		$response = PLCO_Const::send_request( 'plcos/v1/replies/' . $request->get_param( 'ID' ), array(), 'DELETE' );
		$result = PLCO_Const::process_api_response( $response );

		if($result) {
			return new WP_REST_Response( $result, 200 );
		}

		return new WP_Error( 'no-license', __( $result->msg, PLCO_Const::T ) );
	}

}
