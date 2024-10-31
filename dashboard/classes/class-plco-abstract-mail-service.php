<?php
/**
 * Plugins Corner - https://pluginscorner.com
 *
 * @package dashboard
 */

namespace PLCODashboard\classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}


abstract class PLCO_Abstract_Mail_Service {

	/**
	 * @var PLCO_Connection
	 */
	public PLCO_Connection $connection;


	/**
	 * PLCO_Abstract_Payment_Method constructor.
	 *
	 * @param PLCO_Connection $connection
	 */
	public function __construct( PLCO_Connection $connection ) {
		$this->connection = $connection;
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
	 * Saves the mail service
	 *
	 * @return mixed
	 */
	abstract function save();

	/**
	 * Initiate the connection
	 *
	 * @return mixed
	 */
	abstract function connection();

	/**
	 * Tests if the connection is made
	 *
	 * @return bool
	 */
	abstract function testConnection();

	/**
	 * Sends the email
	 *
	 * @param string $from
	 * @param string $to
	 * @param string $subject
	 * @param mixed $message
	 * @param array $headers
	 * @param array $attachments
	 * @param array $extra
	 *
	 * @return mixed
	 */
	abstract function send_mail(string $from, string $to, string $subject, $message, array $headers = array(), array $attachments = array(), array $extra = array());
}
