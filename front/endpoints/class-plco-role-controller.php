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

class PLCO_Role_Controller extends PLCO_REST_Controller {

	public $base = 'roles';

	/**
	 * Register Routes
	 */
	public function register_routes() {

		register_rest_route( self::$namespace . self::$version, '/' . $this->base . '/(?P<ID>[a-zA-Z0-9-]+)', array(
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_role' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
				'args'                => array(),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'add_or_edit_role' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
				'args'                => array(),
			),
		) );
	}

	/**
	 * Saves the membership to the DB
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function add_or_edit_role( $request ) {
		/**
		 * @var WP_REST_Request $request
		 */
		$params = $request->get_params();
		$roles  = new \WP_Roles();
		$add    = true;

		foreach ( $roles->roles as $key => $role ) {
			if ( $key === $params['ID'] ) {
				$add = false;
			}
		}

		if($add) {
			add_role($params["ID"], $params["name"],$params["capabilities"] );
		} else {
			$role = $roles->get_role($params["ID"]);
			foreach ($params['capabilities'] as $key => $capability) {
				if(!array_key_exists($key, $role->capabilities)) {
					$role->add_cap($key);
				}
			}

			foreach ($role->capabilities as $key => $capability) {
				if(!array_key_exists($key, $params['capabilities'])) {
					$role->remove_cap($key);
				}
			}
		}

		return new WP_REST_Response( $params, 200 );
	}


	/**
	 * Deletes a role
	 *
	 * @param $request
	 *
	 * @return WP_REST_Response
	 */
	public function delete_role( $request ) {
		/**
		 * @var WP_REST_Request $request
		 */
		$params = $request->get_params();
		$roles  = new \WP_Roles();

		$roles->remove_role($params["ID"]);

		return new WP_REST_Response( true, 200 );
	}
}
