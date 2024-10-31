<?php

namespace PLCOMembership;

use Exception;
use PLCODashboard\classes\PLCO_Connection;
use PLCODashboard\classes\PLCO_Abstract_Payment_Method;
use PLCODashboard\classes\repositories\PLCO_Connection_Repository;

require_once( dirname( __FILE__, 4 ) . '/wp-load.php' );
require( 'vendor/autoload.php' );

class PLCOM_IPN {

	/**
	 * @var string
	 */
	private $payment_method;

	/**
	 * @var array
	 */
	private array $ipn_data;

	/**
	 * @var string
	 */
	private string $raw_data;


	/**
	 * @var bool
	 */
	private bool $result;


	public function __construct() {
		$this->ipn_data       = $_POST;
		$this->payment_method = $_GET['_pm'];
		$this->raw_data  = file_get_contents( 'php://input' );

		switch ($this->payment_method) {
			case 'paypal':
				$raw_post_array = explode( '&', $this->raw_data );
				foreach ( $raw_post_array as $keyval ) {
					$keyval = explode( '=', $keyval );
					if ( count( $keyval ) == 2 ) {
						// Since we do not want the plus in the datetime string to be encoded to a space, we manually encode it.
						if ( $keyval[0] === 'payment_date' ) {
							if ( substr_count( $keyval[1], '+' ) === 1 ) {
								$keyval[1] = str_replace( '+', '%2B', $keyval[1] );
							}
						}
						$this->ipn_data[ $keyval[0] ] = urldecode( $keyval[1] );
					}
				}
				break;
			case 'stripe':
				$this->ipn_data = (array) json_decode($this->raw_data);
				break;
		}

	}

	/**
	 * Processes the IPN
	 *
	 * @return bool
	 */
	public function get_result() {

		if ( ! empty( $this->ipn_data ) ) {
			$method     = "PLCODashboard\\front\\classes\\PLCO_" . ucfirst( $this->payment_method ) . "_Payment_Method";
			$connection = PLCO_Connection_Repository::find_one_by( array( 'connection_name' => $this->payment_method ) );
			/** @var PLCO_Abstract_Payment_Method $payment_method */
			$payment_method = new $method( $connection );

			/**
			 * Check the IPN to make sure it's a valid one
			 */
			$payment_method->set_ipn_data( $this->ipn_data );

			$validation_data = array();

			if($this->payment_method === 'stripe') {
				$validation_data['sig_header'] = $_SERVER['HTTP_STRIPE_SIGNATURE'];
				$validation_data['payload'] = $this->raw_data;
			}

			if ( $payment_method->is_valid_ipn($validation_data) ) {
				return $payment_method->process_ipn();
			}
		}

		return false;
	}
}


$data = new PLCOM_IPN();

return $data->get_result();
