<?php
/**
 * http://pluginscorner.com
 *
 * @package plco-membership
 */

namespace PLCOMembership\front\endpoints;

use DateTime;
use PLCODashboard\classes\PLCO_REST_Controller;
use PLCODashboard\front\classes\PLCO_Paypal_Payment_Method;
use PLCODashboard\front\classes\PLCO_Stripe_Payment_Method;
use PLCOMembership\admin\classes\PLCOM_Const;
use PLCOMembership\admin\classes\PLCOM_Helpers;
use PLCOMembership\front\classes\PLCOM_Payment;
use PLCOMembership\front\classes\repositories\PLCOM_Payment_Repository;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class PLCO_Payment_Controller extends PLCO_REST_Controller {

	public $base = 'payment';

	/**
	 * Register Routes
	 */
	public function register_routes() {

		register_rest_route( self::$namespace . self::$version, '/' . $this->base, array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'process_payment' ),
				'permission_callback' => array( $this, 'no_permission_required' ),
				'args'                => array(),
			),
		) );

		register_rest_route( self::$namespace . self::$version, '/' . $this->base . '/validate/(?P<ID>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'validate_payment' ),
				'permission_callback' => array( $this, 'no_permission_required' ),
				'args'                => array(),
			),
		) );

		register_rest_route( self::$namespace . self::$version, '/' . $this->base . '/requires_action/(?P<ID>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'confirm_payment' ),
				'permission_callback' => array( $this, 'no_permission_required' ),
				'args'                => array(),
			),
		) );

		register_rest_route( self::$namespace . self::$version, '/' . $this->base . '/(?P<ID>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'cancel_subscription' ),
				'permission_callback' => array( $this, 'no_permission_required' ),
				'args'                => array(),
			),
		) );
	}

	/**
	 * Processes the payment
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function process_payment( $request ) {
		$params = $request->get_params();

		$validation = apply_filters( 'plcom_before_process_payment', true, $params );

		if ( ! $validation ) {
			return new WP_Error( 'payment-failed', __( 'Validation failed', PLCOM_Const::T ) );
		}
		/**
		 * @var WP_REST_Request $request
		 */

		$data = PLCOM_Helpers::create_user_with_memberhip( $params );

		if ( is_object( $data ) && isset( $data->error ) ) {
			return new WP_REST_Response( $data->message, 402 );
		}

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Validate the payment
	 *
	 * @param WP_REST_Request $request
	 *
	 * @throws \Exception
	 */
	public function validate_payment( $request ) {

		/**
		 * @var WP_REST_Request $request
		 */
		$params = $request->get_params();
		/** @var PLCOM_Payment $payment */
		$payment          = PLCOM_Payment_Repository::find_one_by( array( 'ID' => $params['ID'] ) );
		$connection       = $payment->getPaymentType();
		$connection_class = "PLCODashboard\\front\\classes\\PLCO_" . ucfirst( $connection->getConnectionName() ) . "_Payment_Method";
		/** @var PLCO_Paypal_Payment_Method|PLCO_Stripe_Payment_Method $payment_method */
		$payment_method = new $connection_class( $connection );

		/**
		 * Make sure the user is a non member
		 */
		$payment->getUserMembership()->setMembership( 1 );
		$payment = $payment_method->validate_payment( $payment );

		if ( $payment ) {
			return new WP_REST_Response( __( 'Success', PLCOM_Const::T ), 200 );
		}

		return new WP_Error( 'payment-failed', __( 'Payment Could not be processed', PLCOM_Const::T ) );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	/**
	 * @param WP_REST_Request $request
	 *
	 * @throws \Stripe\Exception\ApiErrorException
	 */
	public function confirm_payment( $request ) {

		$params = $request->get_params();
		/** @var PLCOM_Payment $payment */
		$payment          = PLCOM_Payment_Repository::find_one_by( array( 'ID' => $params['ID'] ) );
		$connection       = $payment->getPaymentType();
		$connection_class = "PLCODashboard\\front\\classes\\PLCO_" . ucfirst( $connection->getConnectionName() ) . "_Payment_Method";
		/** @var PLCO_Paypal_Payment_Method|PLCO_Stripe_Payment_Method $payment_method */
		$payment_method = new $connection_class( $connection );

		$result = $payment_method->confirm_payment( $payment );

		if ( $result ) {
			return new WP_REST_Response( __( $result, PLCOM_Const::T ), 200 );
		}

		return new WP_Error( 'payment-failed', __( 'Payment Could not be confirmed', PLCOM_Const::T ) );
	}


	/**
	 * Cancels a subscription
	 *
	 * @param $request
	 *
	 * @return WP_REST_Response
	 */
	public function cancel_subscription( $request ) {

		/**
		 * @var WP_REST_Request $request
		 */
		$params = $request->get_params();

		$result = PLCOM_Helpers::cancel_membership( (int) $params["ID"] );

		return new WP_REST_Response( $result, 200 );

	}
}
