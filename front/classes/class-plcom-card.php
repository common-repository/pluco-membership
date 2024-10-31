<?php
/**
 * Plugins Corner Membership - https://pluginscorner.com.com
 *
 * @package plco-membership
 */

namespace PLCOMembership\front\classes;

use PLCODashboard\classes\PLCO_Abstract_Model;
use WP_User;

class PLCOM_Card extends PLCO_Abstract_Model {

	/**
	 * @var
	 */
	public $user;

	/**
	 * @var
	 */
	public $card_number;

	/**
	 * @var
	 */
	public $full_name;

	/**
	 * @var
	 */
	public $customer_id;

	/**
	 * @var
	 */
	public $payment_method_id;

	/**
	 * @var
	 */
	public $created_at;


	/**
	 * PLCOM_Recurrences constructor.
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

			if ( method_exists( $this, $method ) ) {
				$this->$method( $value );
			}

			$this->setUser( $data->user_id );
		}
	}

	/**
	 * @return mixed
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
	 * @return mixed
	 */
	public function getCardNumber() {
		return $this->card_number;
	}

	/**
	 * @param mixed $card_number
	 */
	public function setCardNumber( $card_number ) {
		$this->card_number = $card_number;
	}

	/**
	 * @return mixed
	 */
	public function getFullName() {
		return $this->full_name;
	}

	/**
	 * @param mixed $full_name
	 */
	public function setFullName( $full_name ) {
		$this->full_name = $full_name;
	}

	/**
	 * @return mixed
	 */
	public function getCustomerId() {
		return $this->customer_id;
	}

	/**
	 * @param mixed $customer_id
	 */
	public function setCustomerId( $customer_id ) {
		$this->customer_id = $customer_id;
	}

	/**
	 * @return mixed
	 */
	public function getPaymentMethodId() {
		return $this->payment_method_id;
	}

	/**
	 * @param mixed $payment_method_id
	 */
	public function setPaymentMethodId( $payment_method_id ) {
		$this->payment_method_id = $payment_method_id;
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

}
