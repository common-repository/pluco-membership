<?php
/**
 * http://pluginscorner.com
 *
 * @package plco-membership
 */

namespace PLCODashboard\front\endpoints;

use Exception;
use PLCODashboard\admin\classes\PLCO_Const;
use PLCODashboard\classes\PLCO_License_Manager;
use PLCODashboard\classes\PLCO_REST_Controller;
use PLCODashboard\front\classes\PLCO_JWT;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class PLCO_Topics_Controller extends PLCO_REST_Controller {

	public $base = 'topics';

	/**
	 * Register Routes
	 */
	public function register_routes() {

		register_rest_route( self::$namespace . self::$version, '/' . $this->base, array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'open_topic' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
				'args'                => array(),
			),
		) );

		register_rest_route( self::$namespace . self::$version, '/' . $this->base . '/(?P<id>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'open_topic' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
				'args'                => array(),
			),
		) );

		register_rest_route( self::$namespace . self::$version, '/' . $this->base . '/close_topic/', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'close_topic' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
				'args'                => array(),
			),
		) );

		register_rest_route( self::$namespace . self::$version, '/' . $this->base . '/reopen_topic/', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'reopen_topic' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
				'args'                => array(),
			),
		) );

		register_rest_route( self::$namespace . self::$version, '/' . $this->base . '/get_topics/', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_topics' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
				'args'                => array(),
			),
		) );

	}

	/**
	 * Get the products
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 * @throws Exception
	 */
	public function get_topics( $request ) {
		$params  = $request->get_params();
		$license = PLCO_JWT::checkJWTExpiration();

		if ( ! empty( $license['jwt_access'] ) ) {
			$response = PLCO_Const::send_request( 'plcos/v1/topics/get_topics?user_id=' . $license['user_id'], array(), 'GET' );
			$result   = PLCO_Const::process_api_response( $response );

		} else {
			return new WP_Error( 'no-license', __( 'You don\'t have access to this section, please re-validate your license.', PLCO_Const::T ) );
		}

		if ( isset($result->status) && $result->status == 401 ) {
			return new WP_Error( 'no-license', __( $result->msg, PLCO_Const::T ) );
		}

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function open_topic( $request ) {
		$params = $request->get_params();
		/**
		 * @var WP_REST_Request $request
		 */
		$license = PLCO_JWT::checkJWTExpiration();

		if ( ! empty( $license['jwt_access'] ) ) {
			$response = PLCO_Const::send_request( 'plcos/v1/topics', $params );
			$result   = PLCO_Const::process_api_response( $response );

		} else {
			return new WP_Error( 'no-license', __( 'You don\'t have access to this section, please re-validate your license.', PLCO_Const::T ) );
		}

		if ( isset( $result->status ) && $result->status == 401 ) {
			return new WP_Error( 'no-license', __( $result->msg, PLCO_Const::T ) );
		}

		return new WP_REST_Response( (array) $result, 200 );

	}

	/**
	 * Close the topic
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function close_topic( $request ) {
		/**
		 * @var WP_REST_Request $request
		 */
		$license = PLCO_JWT::checkJWTExpiration();

		if ( ! empty( $license['jwt_access'] ) ) {
			$response = PLCO_Const::send_request( 'plcos/v1/topics/close_topic', array( 'ID' => $request->get_param( 'ID' ) ) );
			$result   = PLCO_Const::process_api_response( $response );
		} else {
			return new WP_Error( 'no-license', __( 'You don\'t have access to this section, please re-validate your license.', PLCO_Const::T ) );
		}
		if ( isset( $result->status ) && $result->status == 401 ) {
			return new WP_Error( 'no-license', __( $result->msg, PLCO_Const::T ) );
		}

		return new WP_REST_Response( (array) $result, 200 );
	}

	/**
	 * Reopen the topic
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function reopen_topic( $request ) {
		/**
		 * @var  $request
		 */
		$license = PLCO_JWT::checkJWTExpiration();

		if ( ! empty( $license['jwt_access'] ) ) {
			$response = PLCO_Const::send_request( 'plcos/v1/topics/reopen_topic', array( 'ID' => $request->get_param( 'ID' ) ) );
			$result   = PLCO_Const::process_api_response( $response );
		} else {
			return new WP_Error( 'no-license', __( 'You don\'t have access to this section, please re-validate your license.', PLCO_Const::T ) );
		}

		if ( isset( $result->status ) && $result->status == 401 ) {
			return new WP_Error( 'no-license', __( $result->msg, PLCO_Const::T ) );
		}

		return new WP_REST_Response( (array) $result, 200 );
	}


}
