<?php
/**
 * Plugins Corner - https://pluginscorner.com
 *
 * @package dashboard
 */

namespace PLCODashboard\classes;

use PLCODashboard\admin\classes\PLCO_Helpers;
use PLCODashboard\front\classes\PLCO_Wordpress_Mail_Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class PLCO_Mail_Factory {

	/**
	 * @var string|mixed|void
	 */
	protected string $service = PLCO_Wordpress_Mail_Service::class;

	/**
	 * @var PLCO_Connection
	 */
	protected PLCO_Connection $connection;

	public function __construct() {

		$data = apply_filters( 'plcom_mail_service', array() );

		if ( isset( $data['connection'] ) ) {
			$this->connection = PLCO_Helpers::get_connection_by_name( $data['connection'] );
		} else {
			$this->connection = PLCO_Helpers::get_connection_by_name( "wordpress" );
		}

		if ( isset( $data['service'] ) ) {
			$this->service = $data['service'];
		}

	}

	/**
	 * @return mixed
	 */
	public function get_mail_service() {
		return new $this->service( $this->connection );
	}
}
