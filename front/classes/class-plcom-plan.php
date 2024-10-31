<?php
/**
 * Plugins Corner Membership - https://pluginscorner.com.com
 *
 * @package plco-membership
 */

namespace PLCOMembership\front\classes;

use PLCODashboard\classes\PLCO_Abstract_Model;

class PLCOM_Plan extends PLCO_Abstract_Model {

	/**
	 * @var
	 */
	public $recurrence_id;

	/**
	 * @var
	 */
	public $connection_id;

	/**
	 * @var
	 */
	public $plan_created;

	/**
	 * @var
	 */
	public $plan_activated;

	/**
	 * @var
	 */
	public $created_at;


	public function __construct( $data ) {
		parent::__construct( $data );
	}

	/**
	 * Initialize Plan
	 *
	 * @param $data
	 *
	 * @return void
	 */
	public function init( $data ) {
		if ( $data ) {
			foreach ( get_object_vars( $data ) as $key => $value ) {
				$exploded = explode( "_", $key );

				foreach ( $exploded as $ex => $item ) {
					$exploded[ $ex ] = ucwords( $item );
				}

				$method = "set" . implode( "", $exploded );

				if ( method_exists( $this, $method ) ) {
					$this->$method( $value );
				}
			}
		}
	}

	/**
	 * @return mixed
	 */
	public function getRecurrenceId() {
		return $this->recurrence_id;
	}

	/**
	 * @param mixed $recurrence_id
	 */
	public function setRecurrenceId( $recurrence_id ) {
		$this->recurrence_id = $recurrence_id;
	}

	/**
	 * @return mixed
	 */
	public function getConnectionId() {
		return $this->connection_id;
	}

	/**
	 * @param mixed $connection_id
	 */
	public function setConnectionId( $connection_id ) {
		$this->connection_id = $connection_id;
	}

	/**
	 * @return mixed
	 */
	public function getPlanCreated() {
		return $this->plan_created;
	}

	/**
	 * @param mixed $plan_created
	 */
	public function setPlanCreated( $plan_created ) {
		$this->plan_created = $plan_created;
	}

	/**
	 * @return mixed
	 */
	public function getPlanActivated() {
		return $this->plan_activated;
	}

	/**
	 * @param mixed $plan_activated
	 */
	public function setPlanActivated( $plan_activated ) {
		$this->plan_activated = $plan_activated;
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
