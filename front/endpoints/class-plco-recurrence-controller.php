<?php
/**
 * http://pluginscorner.com
 *
 * @package plco-membership
 */

namespace PLCOMembership\front\endpoints;

use PLCODashboard\classes\PLCO_Abstract_Payment_Method;
use PLCODashboard\classes\PLCO_REST_Controller;
use PLCOMembership\admin\classes\PLCOM_Const;
use PLCOMembership\admin\classes\PLCOM_Helpers;
use PLCOMembership\front\classes\PLCOM_Membership_Level;
use PLCOMembership\front\classes\PLCOM_Plan;
use PLCOMembership\front\classes\PLCOM_Recurrences;
use PLCODashboard\classes\repositories\PLCO_Connection_Repository;
use PLCOMembership\front\classes\repositories\PLCOM_Membership_Level_Repository;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class PLCO_Recurrence_Controller extends PLCO_REST_Controller {

	public $base = 'recurrence';

	/**
	 * Register Routes
	 */
	public function register_routes() {

		register_rest_route( self::$namespace . self::$version, '/' . $this->base, array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'edit_recurrence' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
				'args'                => array(),
			),
		) );

		register_rest_route( self::$namespace . self::$version, '/' . $this->base . '/(?P<ID>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_recurrence' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
				'args'                => array(),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'edit_recurrence' ),
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
	public function edit_recurrence( $request ) {
		/**
		 * @var WP_REST_Request $request
		 */
		$params = $request->get_params();
		$params['amount'] = (double) str_replace(',', '.', $params['amount']);
		$model  = new PLCOM_Recurrences( (object) $params );
		/** @var PLCOM_Recurrences $result */
		$result = $model->getRepository()->persist( $model );

		/**
		 * Try To create a plan
		 */
		$connections = PLCO_Connection_Repository::find_by(array('connection_type' => 'payment'));
		/** @var PLCOM_Membership_Level $membership_level */
		$membership_level = PLCOM_Membership_Level_Repository::find_one_by( array( 'ID' => $model->getMembershipId() ) );

		foreach ( $connections as $connection ) {
			$method = "PLCODashboard\\front\\classes\\PLCO_" . ucfirst( $connection->getConnectionName() ) . "_Payment_Method";
			/** @var PLCO_Abstract_Payment_Method $payment_method */
			$payment_method = new $method( $connection );
			$plan           = new PLCOM_Plan( (object)
			array(
				'recurrence_id' => $model->getID(),
				'connection_id' => $connection->getID(),
			)
			);

			$payment_method->add_plan( $plan, $result, $membership_level );
		}

		if ( $result ) {
			return new WP_REST_Response( $result, 200 );
		}

		return new \WP_Error( 'code', __( 'Failed to add or edit recurrence. Try again. If the error persists please contact our technical team.', PLCOM_Const::T ) );
	}

	/**
	 * @param $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_recurrence( $request ) {
		/**
		 * @var WP_REST_Request $request
		 */
		$params = $request->get_params();
		$model  = new PLCOM_Recurrences( (object) $params );

		/**
		 * Delete all plans associated with this recurrence
		 */
		foreach ( $model->getPlans() as $plan ) {
			$plan->getRepository()->destroy( $plan );
		}
		$result = $model->getRepository()->destroy( $model );

		if ( $result ) {
			return new WP_REST_Response( $result, 200 );
		}

		return new \WP_Error( 'code', __( 'Failed to delete recurrence. Try again. If the error persists please contact our technical team.', PLCOM_Const::T ) );
	}
}
