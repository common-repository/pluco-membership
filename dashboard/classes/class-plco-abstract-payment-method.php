<?php
/**
 * PluginsCorner - https://pluginscorner.com
 *
 * @package plco-membership
 */

namespace PLCODashboard\classes;


use PLCOMembership\front\classes\PLCOM_Membership_Level;
use PLCOMembership\front\classes\PLCOM_Plan;
use PLCOMembership\front\classes\PLCOM_Recurrences;
use PLCOMembership\front\classes\PLCOM_User_Membership;

/**
 * Class PLCO_Abstract_Payment_Method
 *
 * @package PLCODashboard\classes
 */
abstract class PLCO_Abstract_Payment_Method {

	/**
	 * @var PLCO_Connection
	 */
	public PLCO_Connection $connection;

	/**
	 * @var array
	 */
	protected array $ipn;

	/**
	 * PLCO_Abstract_Payment_Method constructor.
	 *
	 * @param PLCO_Connection $connection
	 */
	public function __construct( PLCO_Connection $connection ) {
		$this->connection = $connection;

	}

	/**
	 * Check for Sandbox Connection
	 *
	 * @return mixed
	 */
	public function isSandbox() {
		return $this->connection->getSandbox();
	}

	/**
	 * Gets the connection
	 *
	 * @return PLCO_Connection
	 */
	public function getConnection() {
		return $this->connection;
	}

	/**
	 * Sets the connection
	 *
	 * @param $connection
	 *
	 * @return void
	 */
	public function setConnection( $connection ) {
		$this->connection = $connection;
	}

	/**
	 * Initiate the connection
	 *
	 * @return mixed
	 */
	abstract function connection();

	abstract function save();

	/**
	 * Adds the plan
	 *
	 * @param PLCOM_Plan $plan
	 * @param PLCOM_Recurrences $recurrence
	 * @param PLCOM_Membership_Level $membership_level
	 *
	 * @return mixed
	 */
	abstract function add_plan( PLCOM_Plan $plan, PLCOM_Recurrences $recurrence, PLCOM_Membership_Level $membership_level );

	/**
	 * Activates the plan
	 *
	 * @param PLCOM_Plan $plan
	 *
	 * @return mixed
	 */
	abstract function activate_plan( PLCOM_Plan $plan );

	/**
	 * Makes the payment
	 *
	 * @param PLCOM_User_Membership $user
	 * @param PLCOM_Recurrences $recurrence
	 * @param PLCOM_Membership_Level $membership_level
	 * @param array $data
	 *
	 * @return mixed
	 */
	abstract function make_payment( PLCOM_User_Membership $user, PLCOM_Recurrences $recurrence, PLCOM_Membership_Level $membership_level, array $data = array() );

	/**
	 * Validates that the IPN comes from the gateway
	 * (Set  as false by default so it's overwritten if needed)
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	abstract function is_valid_ipn( array $data = array());

	/**
	 * Processes the IPN
	 *
	 * @return bool
	 */
	abstract function process_ipn();

	/**
	 * Tests if the connection is made
	 *
	 * @return bool
	 */
	abstract function testConnection();

	/**
	 * Sets the IPN Data
	 *
	 * @param array $ipn
	 *
	 * @return void
	 */
	public function set_ipn_data( array $ipn ) {
		$this->ipn = $ipn;
	}
}
