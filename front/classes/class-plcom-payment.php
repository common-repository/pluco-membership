<?php
/**
 * Plugins Corner Membership - https://pluginscorner.com.com
 *
 * @package plco-membership
 */

namespace PLCOMembership\front\classes;

use PLCODashboard\classes\PLCO_Abstract_Model;
use PLCODashboard\classes\PLCO_Connection;
use PLCOMembership\admin\classes\PLCOM_Helpers;
use PLCODashboard\admin\classes\PLCO_Helpers;
use PLCOMembership\front\classes\repositories\PLCOM_Membership_Level_Repository;
use PLCOMembership\front\classes\repositories\PLCOM_Recurrences_Repository;
use PLCOMembership\front\classes\repositories\PLCOM_User_Membership_Repository;
use WP_User;

/**
 * Class PLCOM_Payment
 *
 * @package PLCOMembership\front\classes
 */
class PLCOM_Payment extends PLCO_Abstract_Model {

	/**
	 * @var
	 */
	public $user;

	/**
	 * @var
	 */
	public $user_membership;

	/**
	 * @var
	 */
	public $membership;

	/**
	 * @var
	 */
	public $recurrence;

	/**
	 * @var
	 */
	public $transaction_id;

	/**
	 * @var
	 */
	public $subscription_id;

	/**
	 * @var
	 */
	public $payment_amount;

	/**
	 * @var
	 */
	public $status;

	/**
	 * @var
	 */
	public $payment_type;

	/**
	 * @var
	 */
	public $original_response;

	/**
	 * @var
	 */
	public $created_at;

	/**
	 * PLCOM_Payment constructor.
	 *
	 * @param $data
	 */
	public function init( $data ) {
		foreach ( get_object_vars( $data ) as $key => $value ) {
			$exploded = explode( "_", $key );

			foreach ( $exploded as $ex => $item ) {
				$exploded[ $ex ] = ucwords( $item );
			}

			$method = "set" . implode( "", $exploded );

			if ( method_exists( $this, $method )  && ! is_array( $value ) ) {
				$this->$method( $value );
			}
		}

		$this->setUser( $data->user_id );
		$this->setMembership( $data->membership_id );
		$this->setUserMembership( $data->user_membership_id );
		$this->setRecurrence( $data->recurrence_id );
		$this->setPaymentType( $data->payment_type_id );
	}

	/**
	 * @return WP_User
	 */
	public function getUser() {
		return $this->user;
	}

	/**
	 * @param mixed $user
	 */
	public function setUser( $user ) {
		$this->user = new WP_User( $user );
	}

	/**
	 * @return PLCOM_User_Membership
	 */
	public function getUserMembership() {
		return $this->user_membership;
	}

	/**
	 * @param int|string $user_membership
	 */
	public function setUserMembership( $user_membership ) {
		$this->user_membership = PLCOM_User_Membership_Repository::find_one_by( array( 'ID' => $user_membership ) );
	}

	/**
	 * @return PLCOM_Membership_Level
	 */
	public function getMembership() {
		return $this->membership;
	}

	/**
	 * @return PLCOM_Recurrences
	 */
	public function getRecurrence() {
		return $this->recurrence;
	}

	/**
	 * @param mixed $recurrence
	 */
	public function setRecurrence( $recurrence ) {
		$this->recurrence = PLCOM_Recurrences_Repository::find_one_by( array( 'ID' => $recurrence ) );
	}


	/**
	 * @param mixed $membership
	 */
	public function setMembership( $membership ) {
		$this->membership = PLCOM_Membership_Level_Repository::find_one_by( array( 'ID' => $membership ) );
	}

	/**
	 * @return mixed
	 */
	public function getSubscriptionId() {
		return $this->subscription_id;
	}

	/**
	 * @param mixed $subscription_id
	 */
	public function setSubscriptionId( $subscription_id ) {
		$this->subscription_id = $subscription_id;
	}

	/**
	 * @return mixed
	 */
	public function getTransactionId() {
		return $this->transaction_id;
	}

	/**
	 * @param mixed $transaction_id
	 */
	public function setTransactionId( $transaction_id ) {
		$this->transaction_id = $transaction_id;
	}

	/**
	 * @return mixed
	 */
	public function getPaymentAmount() {
		return $this->payment_amount;
	}

	/**
	 * @param mixed $payment_amount
	 */
	public function setPaymentAmount( $payment_amount ) {
		$this->payment_amount = (double) $payment_amount;
	}

	/**
	 * @return mixed
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * @param mixed $status
	 */
	public function setStatus( $status ) {
		$this->status = $status;
	}

	/**
	 * @return PLCO_Connection
	 */
	public function getPaymentType() {
		return $this->payment_type;
	}

	/**
	 * @param PLCO_Connection $payment_type
	 *
	 * @return void
	 */
	public function setPaymentType( $payment_type ) {
		$helper             = new PLCO_Helpers();
		$this->payment_type = $helper::get_connection_by_id( $payment_type );
	}

	/**
	 * @return mixed
	 */
	public function getCreatedAt() {
		return $this->created_at;
	}

	/**
	 * @param mixed $created_at
	 */
	public function setCreatedAt( $created_at ) {
		$this->created_at = $created_at;
	}

	/**
	 * @return mixed
	 */
	public function getResponse() {
		return $this->original_response;
	}

	/**
	 * @param mixed $original_response
	 */
	public function setOriginalResponse( $original_response ) {
		$this->original_response = $original_response;
	}


}
