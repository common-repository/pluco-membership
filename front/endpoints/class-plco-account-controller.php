<?php
/**
 * http://pluginscorner.com
 *
 * @package plco-membership
 */

namespace PLCOMembership\front\endpoints;

use PLCODashboard\classes\PLCO_REST_Controller;
use PLCOMembership\admin\classes\PLCOM_Const;
use PLCOMembership\admin\classes\PLCOM_Helpers;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

require_once( ABSPATH . 'wp-admin/includes/user.php' );

class PLCO_Account_Controller extends PLCO_REST_Controller {

	public $base = 'account';

	/**
	 * Register Routes
	 */
	public function register_routes() {

		register_rest_route( self::$namespace . self::$version, '/' . $this->base, array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'edit_account' ),
				'permission_callback' => array( $this, 'login_check' ),
				'args'                => array(),
			),
		) );

		register_rest_route( self::$namespace . self::$version, '/' . $this->base . '/(?P<ID>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_account' ),
				'permission_callback' => array( $this, 'login_check' ),
				'args'                => array(),
			),
		) );
	}

	/**
	 * Edits the user account
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function edit_account( WP_REST_Request $request ) {

		$params = $request->get_params();
		/** @var \WP_User $wp_user */
		$wp_user                   = wp_get_current_user();
		$wp_user->data->first_name = $params['first_name'];
		$wp_user->data->last_name  = $params['last_name'];
		$wp_user->data->user_email = $params['email'];
		wp_update_user( $wp_user );

		do_action('plcom_after_user_insert', $params, $wp_user->data->ID);

		return new WP_REST_Response( true, 200 );

	}

	/**
	 * Edits the user account
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_account( WP_REST_Request $request ) {
		$result = wp_delete_user( get_current_user_id() );

		if ( $result ) {
			wp_destroy_current_session();

			return new WP_REST_Response( true, 200 );
		}

		return new WP_Error( 404, __( 'User cannot be deleted ! Please Contact our administrators.', PLCOM_Const::T ) );
	}
}
