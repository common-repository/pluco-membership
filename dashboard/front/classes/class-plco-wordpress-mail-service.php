<?php
/**
 * PluginsCorner - https://pluginscorner.com
 *
 * @package plco-membership
 */

namespace PLCODashboard\front\classes;


use Dompdf\Exception;
use PLCODashboard\admin\classes\PLCO_Helpers;
use PLCODashboard\classes\PLCO_Abstract_Mail_Service;

class PLCO_Wordpress_Mail_Service extends PLCO_Abstract_Mail_Service {

	/**
	 * Saves the connection
	 *
	 * @return mixed
	 */
	public function save() {
		return $this->connection->getRepository()->persist($this->connection);
	}

	/**
	 * Tests the connection
	 *
	 * @return bool
	 */
	public function testConnection() {
		return true;
	}

	/**
	 * The connection
	 *
	 * @return mixed
	 */
	public function connection() {
		return '';
	}

	/**
	 * Sends the Email
	 *
	 * @param string $from
	 * @param string $to
	 * @param string $subject
	 * @param mixed $message
	 * @param array $headers
	 * @param array $attachments
	 *
	 * @return bool
	 */
	public function send_mail(string $from, string $to, string $subject, $message, array $headers = array(), array $attachments = array(), array $extra = array()) {
		return wp_mail( $to, $subject, $message, $headers, $attachments );
	}
}
