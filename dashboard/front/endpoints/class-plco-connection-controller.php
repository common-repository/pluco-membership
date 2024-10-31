<?php
/**
 * http://pluginscorner.com
 *
 * @package plco-membership
 */

namespace PLCODashboard\front\endpoints;

use PLCODashboard\admin\classes\PLCO_Helpers;
use PLCODashboard\classes\PLCO_Abstract_Model;
use PLCODashboard\classes\PLCO_Connection;
use PLCODashboard\classes\PLCO_REST_Controller;
use PLCODashboard\classes\repositories\PLCO_Connection_Repository;
use PLCODashboard\front\classes\PLCO_Paypal_Payment_Method;
use PLCOMembership\admin\classes\PLCOM_Const;
use PLCOMembership\front\classes\PLCOM_Membership_Level;
use PLCOMembership\front\classes\PLCOM_Plan;
use PLCOMembership\front\classes\PLCOM_Recurrences;
use PLCOMembership\front\classes\repositories\PLCOM_Membership_Level_Repository;
use PLCOMembership\front\classes\repositories\PLCOM_Plan_Repository;
use PLCOMembership\front\classes\repositories\PLCOM_Recurrences_Repository;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class PLCO_Connection_Controller extends PLCO_REST_Controller {

	public $base = 'connection';

	/**
	 * Register Routes
	 */
	public function register_routes() {

		register_rest_route( self::$namespace . self::$version, '/' . $this->base, array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'add_connection' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
				'args'                => array(),
			),
		) );

		register_rest_route( self::$namespace . self::$version, '/' . $this->base . '/default', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'set_default_connection' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
				'args'                => array(),
			),
		) );

		register_rest_route( self::$namespace . self::$version, '/' . $this->base . '/(?P<ID>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_connection' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
				'args'                => array(),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'edit_connection' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
				'args'                => array(),
			),
		) );
	}

	/**
	 * Saves the connection to the DB
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function add_connection( $request ) {

		/**
		 * @var WP_REST_Request $request
		 */
		$params     = $request->get_params();
		$connection = new PLCO_Connection( (object) $params );

		if ( $params['connection_type'] === 'payment' ) {
			$connection_class = "PLCODashboard\\front\\classes\\PLCO_" . ucfirst( $connection->getConnectionName() ) . "_Payment_Method";

			/** @var $payment_method PLCO_Paypal_Payment_Method */
			if ( class_exists( $connection_class ) ) {
				$payment_method = new $connection_class( $connection );
				$model          = $payment_method->save();
			}

			if ( $model instanceof PLCO_Abstract_Model ) {
				$recurrences = PLCOM_Recurrences_Repository::get_all();


				/** @var PLCOM_Recurrences $recurrence */
				foreach ( $recurrences as $recurrence ) {
					if ( $recurrence->getID() === 1 ) {
						continue;
					}

					$has_plan = false;

					/** @var PLCOM_Plan $plan */
					foreach ( $recurrence->getPlans() as $plan ) {
						if ( (int) $plan->getConnectionId() === $model->getID() ) {
							$has_plan = true;
						}
					}

					if ( ! $has_plan ) {
						$plan = new PLCOM_Plan( (object)
						array(
							'recurrence_id' => $recurrence->getID(),
							'connection_id' => $model->getID(),
						)
						);



						/** @var PLCOM_Membership_Level $membership_level */
						$membership_level = PLCOM_Membership_Level_Repository::find_one_by( array( 'ID' => $recurrence->getMembershipId() ) );
						$payment_method->add_plan( $plan, $recurrence, $membership_level );
					}
				}


				return new WP_REST_Response( $model, 200 );
			} else {
				return new WP_Error( 'no-connection', __( $model, PLCOM_Const::T ) );
			}
		}

		if ( $params['connection_type'] === 'mail' ) {
			$connection_class = "PLCOMembership\\pro\\front\\classes\\PLCOM_Pro_" . ucfirst( $connection->getConnectionName() ) . "_Mail_Service";
			if ( class_exists( $connection_class ) ) {
				$mail_service = new $connection_class( $connection );
				$model        = $mail_service->save();
			}
		}

		if ( $model instanceof PLCO_Abstract_Model ) {
			return new WP_REST_Response( $model, 200 );
		} else {
			return new WP_Error( 'no-connection', __( $model, PLCOM_Const::T ) );
		}

	}

	/**
	 * Saves the membership to the DB
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function edit_connection( $request ) {
		/**
		 * @var WP_REST_Request $request
		 */
		$params     = $request->get_params();
		$connection = new PLCO_Connection( (object) $params );

		if ( $params['connection_type'] === 'payment' ) {
			$connection_class = "PLCODashboard\\front\\classes\\PLCO_" . ucfirst( $connection->getConnectionName() ) . "_Payment_Method";
			/** @var $payment_method PLCO_Paypal_Payment_Method */
			if ( class_exists( $connection_class ) ) {
				$payment_method = new $connection_class( $connection );
				$model          = $payment_method->save();
			}
		}

		if ( $params['connection_type'] === 'mail' ) {
			$connection_class = "PLCOMembership\\pro\\front\\classes\\PLCOM_Pro_" . ucfirst( $connection->getConnectionName() ) . "_Mail_Service";
			if ( class_exists( $connection_class ) ) {
				$mail_service = new $connection_class( $connection );
				$model        = $mail_service->save();
			}
		}


		if ( isset( $model ) && $model instanceof PLCO_Abstract_Model ) {
			return new WP_REST_Response( $model, 200 );
		} else {
			return new WP_Error( 'no-connection', __( $model, PLCOM_Const::T ) );
		}
	}

	/**
	 * Sets a connection as default (For mailing services)
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function set_default_connection( $request ) {
		/**
		 * @var WP_REST_Request $request
		 */
		$params = $request->get_params();

		update_option( 'plcom_default_mail_service', $params['handle'] );

		return new WP_REST_Response( true, 200 );
	}

	/**
	 * Deletes a connection
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_connection( $request ) {
		/**
		 * @var WP_REST_Request $request
		 */
		$params = $request->get_params();
		$plans  = PLCOM_Plan_Repository::find_by( array( 'connection_id' => $params["ID"] ) );
		/** @var PLCOM_Plan $plan */
		foreach ( $plans as $plan ) {
			$plan->getRepository()->destroy( $plan );
		}

		/** @var PLCO_Connection $connection */
		$connection = PLCO_Connection_Repository::find_one_by( array( 'ID' => $params["ID"] ) );
		$result     = $connection->getRepository()->destroy( $connection );

		if ( $result ) {
			return new WP_REST_Response( true, 200 );
		} else {
			return new WP_Error( 'cannot-delete', __( "The connection cannot be deleted because payments have been made using this payment method", PLCOM_Const::T ) );
		}
	}
}
