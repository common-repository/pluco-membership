<?php
/**
 * PluginsCorner - https://pluginscorner.com
 *
 * @package plco-membership
 */

namespace PLCODashboard\front\classes;

use Exception;
use HttpResponse;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PLCODashboard\classes\PLCO_Abstract_Model;
use PLCODashboard\classes\PLCO_Abstract_Payment_Method;
use PLCODashboard\classes\PLCO_Mail_Factory;
use PLCODashboard\front\classes\paypal\billing\BillingPlansCreateRequest;
use PLCODashboard\front\classes\paypal\billing\BillingPlansGetAllRequest;
use PLCODashboard\front\classes\paypal\billing\BillingSubscriptionsCancelRequest;
use PLCODashboard\front\classes\paypal\billing\BillingSubscriptionsCreateRequest;
use PLCODashboard\front\classes\paypal\product\ProductsCreateRequest;
use PLCOMembership\admin\classes\PLCOM_Const;
use PLCOMembership\front\classes\PLCOM_Membership_Level;
use PLCOMembership\front\classes\PLCOM_Payment;
use PLCOMembership\front\classes\PLCOM_Plan;
use PLCOMembership\front\classes\PLCOM_Recurrences;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PLCOMembership\front\classes\PLCOM_User_Membership;
use DateTime;
use PLCOMembership\front\classes\repositories\PLCOM_Payment_Repository;
use PLCOMembership\front\classes\repositories\PLCOM_Plan_Repository;
use ReflectionException;

class PLCO_Paypal_Payment_Method extends PLCO_Abstract_Payment_Method {

	/**
	 * Paypal Verification URL
	 */
	const VERIFY_URI = 'https://ipnpb.paypal.com/cgi-bin/webscr';

	/**
	 * Paypal Sandbox Verification url
	 */
	const SANDBOX_VERIFY_URI = 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';

	/**
	 * Set the connection
	 *
	 * @return PayPalHttpClient
	 */
	public function connection() {
		if ( $this->isSandbox() ) {
			$environment = new SandboxEnvironment( $this->connection->getApiKey(), $this->connection->getApiSecret() );

		} else {
			$environment = new ProductionEnvironment( $this->connection->getApiKey(), $this->connection->getApiSecret() );
		}

		return new PayPalHttpClient( $environment );
	}


	/**
	 * @return bool|PLCO_Abstract_Model
	 */
	public function save() {
		$result = $this->testConnection();

		if ( $result === true ) {
			return $this->connection->getRepository()->persist($this->connection);
		} else {
			return $result;
		}
	}

	/**
	 * Test the connection
	 *
	 * @return bool
	 */
	public function testConnection() {
		$apiContext    = $this->connection();
		$request       = new BillingPlansGetAllRequest();
		$request->body = array( "status" => "ALL", "total_required" => "yes" );

		try {
			$apiContext->execute( $request );
		} catch ( Exception $ex ) {

			$message = json_decode( $ex->getMessage() );

			return $message->error_description;
		}

		return true;
	}


	/**
	 * Creates a plan
	 *
	 * @param PLCOM_Plan $plan
	 * @param PLCOM_Recurrences $recurrence
	 * @param PLCOM_Membership_Level $membership_level
	 *
	 * @return PLCO_Abstract_Model|bool
	 */
	public function add_plan( PLCOM_Plan $plan, PLCOM_Recurrences $recurrence, PLCOM_Membership_Level $membership_level ) {
		$apiContext = $this->connection();

		// Create a new billing plan
		$recurrence_type_name = "";
		$currency_handle      = "";
		foreach ( PLCOM_Const::CURRENCIES as $currency ) {
			if ( (int) $currency["ID"] === (int) $recurrence->getCurrency() ) {
				$currency_handle = strtoupper( $currency["handle"] );
			}
		}

		foreach ( PLCOM_Const::RECURRENCE_TYPES as $recurrence_type ) {
			if ( (int) $recurrence_type["ID"] === (int) $recurrence->getRecurrenceType() ) {
				$recurrence_type_name = $recurrence_type["name"];
			}
		}

		$request = new ProductsCreateRequest();

		$request->body = array(
			"name"        => $membership_level->getMembershipName() . " " . $recurrence->getRecurrence() . " " . $recurrence_type_name,
			"description" => "NONE",
			"type"        => "SERVICE",
			"category"    => "SOFTWARE",
		);

		try {
			/** @var HttpResponse $createdPlan */
			$created_product = $apiContext->execute( $request );

			$request = new BillingPlansCreateRequest();

			$request->body = array(
				"product_id"          => $created_product->result->id,
				"name"                => $membership_level->getMembershipName() . " " . $recurrence->getRecurrence() . " " . $recurrence_type_name,
				"description"         => $membership_level->getMembershipName() . " " . $recurrence->getRecurrence() . " " . $recurrence_type_name,
				"type"                => $recurrence->getCycles() ? 'FIXED' : "INFINITE",
				"billing_cycles"      => array(
					array(
						"frequency"      => array(
							"interval_unit"  => strtoupper( substr( $recurrence_type_name, 0, - 1 ) ),
							"interval_count" => $recurrence->getRecurrence(),
						),
						"pricing_scheme" => array(
							"fixed_price" => array(
								'value'         => number_format( $recurrence->getAmount(), 2, '.', '' ),
								'currency_code' => $currency_handle
							),
						),
						"sequence"       => 1,
						"tenure_type"    => "REGULAR",
						"total_cycles"   => $recurrence->getCycles(),
					)
				),
				"payment_preferences" => array(
					"auto_bill_outstanding"    => $membership_level->getAutorenew(),
					"setup_fee"                => array(
						"value"         => number_format( $recurrence->getAmount(), 2, '.', '' ),
						"currency_code" => $currency_handle
					),
					"setup_fee_failure_action" => "CANCEL"
				)
			);

			try {
				$createdPlan = $apiContext->execute( $request );
				$plan->setPlanCreated( $createdPlan->result->id );
				$plan->setPlanActivated( $createdPlan->result->id );

				return PLCOM_Plan_Repository::persist( $plan );

			} catch ( Exception $ex ) {
				echo "<pre>";
				print_r( $ex );
				echo "</pre>";
				/**
				 * TODO: LOG PLAN CREATION ERROR
				 */
			}

		} catch ( Exception $ex ) {
			echo "<pre>";
			print_r( $ex );
			echo "</pre>";
			/**
			 * TODO: LOG PLAN CREATION ERROR
			 */
		}

		return true;
	}

	/**
	 * Executes the payment
	 *
	 * @param PLCOM_User_Membership $user
	 * @param PLCOM_Recurrences $recurrence
	 * @param PLCOM_Membership_Level $membership_level
	 * @param array $data
	 *
	 * @return string|array|object
	 */
	public function make_payment( PLCOM_User_Membership $user, PLCOM_Recurrences $recurrence, PLCOM_Membership_Level $membership_level, array $data = array() ) {
		$apiContext           = $this->connection();
		$request              = new BillingSubscriptionsCreateRequest();
		$recurrence_type_name = '';

		foreach ( PLCOM_Const::RECURRENCE_TYPES as $recurrence_type ) {
			if ( (int) $recurrence_type["ID"] === (int) $recurrence->getRecurrenceType() ) {
				$recurrence_type_name = $recurrence_type["name"];
			}
		}

		$startDate       = date( 'c', strtotime( '+' . $recurrence->getRecurrence() . " " . $recurrence_type_name, strtotime( 'today midnight' ) ) );
		$connection_plan = "";

		foreach ( $recurrence->getPlans() as $plan ) {
			if ( (int) $plan->getConnectionId() === (int) $this->connection->getID() ) {
				$connection_plan = $plan;
				break;
			}
		}

		if ( ! $connection_plan ) {
			$connection_plan = $this->add_plan( new PLCOM_Plan( (object) array() ), $recurrence, $membership_level );
		}

		$success_page = get_option( 'plcom_success_page', array(
			'ID'   => '',
			'name' => '',
			'url'  => ''
		) );
		$cancel_page  = get_option( 'plcom_success_page', array(
			'ID'   => '',
			'name' => '',
			'url'  => ''
		) );

		$wp_user    = $user->getUser();
		$first_name = get_user_meta( $wp_user->ID, 'first_name', true );
		$last_name  = get_user_meta( $wp_user->ID, 'last_name', true );

		$request->body = array(
			"plan_id"             => $connection_plan->getPlanActivated(),
			"start_time"          => $startDate,
			"subscriber"          => array(
				"name"          => array(
					"given_name" => $first_name,
					"surname"    => $last_name
				),
				"email_address" => $wp_user->data->user_email
			),
			"application_context" => array(
				"brand_name"          => get_bloginfo(),
				"locale"              => "en-US",
				"shipping_preference" => "SET_PROVIDED_ADDRESS",
				"user_action"         => "SUBSCRIBE_NOW",
				"payment_method"      => array(
					"payer_selected"  => "PAYPAL",
					"payee_preferred" => "IMMEDIATE_PAYMENT_REQUIRED"
				),
				"return_url"          => $success_page["url"],
				"cancel_url"          => $cancel_page["url"]
			)
		);


		try {
			// Create agreement
			$result = $apiContext->execute( $request );

			return $result->result;

		} catch ( Exception $ex ) {
			return $ex->getMessage();
			// TODO: Log an error message
		}
	}

	/**
	 * Saves the payment and user's membership
	 *
	 * @param PLCOM_Payment $payment
	 *
	 * @return bool
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public function validate_payment( PLCOM_Payment $payment ) {
		$payment_status = $this->ipn['initial_payment_status'] ?? $this->ipn['payment_status'];
		$txn_id         = $this->ipn['initial_payment_txn_id'] ?? $this->ipn['txn_id'];

		$encoded = json_encode( $this->ipn );
		if ( $payment_status === "Completed" || $payment_status === "Created" || $payment_status === "Processed" ) {
			$next_billing_date = new DateTime( $this->ipn['next_payment_date'] );

			$payment->setStatus( 'ACTIVE' );
			$payment->setOriginalResponse( $encoded );
			$payment->setTransactionId( $txn_id );
			$payment->getUserMembership()->setNextBillingDate( $next_billing_date->format( 'Y-m-d H:i:s' ) );

			if ( $payment->getUserMembership()->getMembership()->getID() !== $payment->getMembership()->getID() && $payment->getTransactionId() ) {
				$payment->getUserMembership()->setMembership( $payment->getMembership()->getID() );
			}

			/** @var PLCOM_Recurrences $recurrence */
			$recurrence = $payment->getRecurrence();

			$recurrence_type_name = "";
			foreach ( PLCOM_Const::RECURRENCE_TYPES as $recurrence_type ) {
				if ( (int) $recurrence_type["ID"] === (int) $recurrence->getRecurrenceType() ) {
					$recurrence_type_name = $recurrence_type["name"];
				}
			}

			foreach ( PLCOM_Const::CURRENCIES as $currency ) {
				if ( (int) $currency["ID"] === (int) $recurrence->getCurrency() ) {
					$currency_handle = strtoupper( $currency["handle"] );
				}
			}
			/**
			 * Set expiration date if the membership expires
			 */
			if ( isset( $this->ipn['initial_payment_status'] ) && $recurrence->getCycles() > 0 ) {
				/** @var PLCOM_Recurrences $recurrence */
				$created_time = new DateTime( $this->ipn['time_created'] );
				/** @var PLCOM_Plan $plan */
				$created_time->modify( "+" . (string) ( (int) $recurrence->getRecurrence() * (int) $recurrence->getCycles() ) . " " . $recurrence_type_name );
				$payment->getUserMembership()->setExpiresAt( $created_time->format( 'Y-m-d H:i:s' ) );
			}

			/** @var \WP_User $user */
			$user     = $payment->getUser();
			$is_admin = false;
			foreach ( $user->roles as $role ) {
				/**
				 * DO NOT REMOVE ADMINS
				 */
				if ( $role === "administrator" ) {
					$is_admin = true;
					continue;
				}
				$user->remove_role( $role );
			}

			if ( ! $is_admin ) {
				$user->add_role( $payment->getMembership()->getRole() );
			}

			$payment->getUserMembership()->getRepository()->persist( $payment->getUserMembership() );
			$payment->getRepository()->persist( $payment );

			$success_subject = get_option( 'plcom_success_template_subject', PLCOM_Const::SUCCESS_TEMPLATE_SUBJECT );
			$success_message = get_option( 'plcom_success_template_message', PLCOM_Const::SUCCESS_TEMPLATE_MESSAGE );

			$success_message = str_replace( "{{user_name}}", $user->data->display_name, $success_message );
			$invoice_data    = '<strong>' . $payment->getMembership()->getMembershipName() . '</strong>' . "<br/>" . $recurrence->getAmount() . $currency_handle . " per " . $recurrence->getRecurrence() . $recurrence_type_name;
			$success_message = str_replace( "{{invoice_data}}", $invoice_data, $success_message );

			$attachments = apply_filters( 'plcom_success_mail_attachments', array(), $payment );

			$factory = new PLCO_Mail_Factory();
			/** @var PLCO_Wordpress_Mail_Service $service */
			$service = $factory->get_mail_service();
			$service->send_mail('', $user->data->user_email, $success_subject, $success_message, array( "content-type" => "text/html"), $attachments);

			do_action( 'plcom_after_payment' );

			return true;
		}

		return false;
	}

	/**
	 * Cancel the subscription
	 *
	 * @param $subscription_id
	 *
	 * @return bool
	 */
	public function cancel_subscription( $subscription_id ) {
		$apiContext = $this->connection();
		$request    = new BillingSubscriptionsCancelRequest( $subscription_id );

		$request->body = array(
			"reason" => "User Request"
		);

		try {
			$result = $apiContext->execute( $request );

			if ( $result->statusCode === 204 ) {
				return true;
			}
		} catch ( Exception $ex ) {
			// TODO: Log an error message
		}

		return true;
	}

	/**
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public function is_valid_ipn(array $data = array()) {

		$validate_ipn = array( 'cmd' => '_notify-validate' );
		$validate_ipn = array_merge( $validate_ipn, $this->ipn );

		$params = array(
			'body'        => $validate_ipn,
			'sslverify'   => false,
			'timeout'     => 60,
			'httpversion' => '1.1',
			'compress'    => false,
			'decompress'  => false,
			'user-agent'  => 'paypal-ipn/'
		);

		$response = wp_safe_remote_post( $this->getPaypalUri(), $params );

		return $response['body'] === "VERIFIED";
	}

	/**
	 * Process the IPN Based on txn_type or
	 *
	 * @return bool
	 * @throws ReflectionException
	 */
	public function process_ipn() {

		$result = false;
		if ( isset( $this->ipn['txn_type'] ) ) {
			$payments            = PLCOM_Payment_Repository::find_by( array( 'subscription_id' => $this->ipn["recurring_payment_id"] ) );
			$unprocessed_payment = "";

			switch ( $this->ipn['txn_type'] ) {
				case 'recurring_payment_profile_created':
				case 'recurring_payment':
				case 'subscr_payment':
				case 'subscr_signup':
					if ( ! empty( $payments ) ) {
						if ( $this->ipn['txn_type'] === 'recurring_payment_profile_created' ) {
							/** @var PLCOM_Payment $payment */
							foreach ( $payments as $payment ) {
								if ( $payment->getStatus() === "APPROVAL_PENDING" || $payment->getStatus() === "PENDING" ) {
									$unprocessed_payment = $payment;
								}
							}

						} else {
							/** @var PLCOM_Payment $last_payment */
							$last_payment        = end( $payments );
							$unprocessed_payment = new PLCOM_Payment( (object)
							array(
								'user_id'            => $last_payment->getUser()->data->ID,
								'user_membership_id' => $last_payment->getUserMembership()->getID(),
								'membership_id'      => $last_payment->getMembership()->getID(),
								'recurrence_id'      => $last_payment->getRecurrence()->getID(),
								'subscription_id'    => $last_payment->getSubscriptionId(),
								'status'             => 'APPROVAL_PENDING',
								'payment_amount'     => $last_payment->getPaymentAmount(),
								'payment_type_id'    => $last_payment->getPaymentType()->getID(),
							)
							);
						}
					}

					if ( $unprocessed_payment ) {
						$result = $this->validate_payment( $unprocessed_payment );

						/**
						 * The first membership was created
						 */
						if($result && $this->ipn['txn_type'] === 'recurring_payment_profile_created') {
							$data = array(
								'user_membership_id' => $unprocessed_payment->getUserMembership()->getID(),
							);

							do_action('plcom_initial_membership_created', $data);
						}
					}
					break;
				case 'recurring_payment_failed':
				case 'recurring_payment_suspended_due_to_max_failed_payment':
				case 'subscr_failed':
					/** @var PLCOM_Payment $last_payment */
					$last_payment = end( $payments );

					if ( $last_payment ) {
						$last_payment->getUserMembership()->setState( PLCOM_Const::STATUSES['suspended'] );
						$last_payment->getUserMembership()->getRepository()->persist( $last_payment->getUserMembership() );

						if ( $last_payment->getStatus() === PLCOM_Const::STATUSES['approval_pending'] || $last_payment->getStatus() === PLCOM_Const::STATUSES['pending'] ) {
							$last_payment->setStatus( PLCOM_Const::STATUSES['canceled'] );
						}
					}

					break;
				case 'recurring_payment_expired':
				case 'subscr_cancel':
				case 'subscr_eot':
					/** @var PLCOM_Payment $last_payment */
					$last_payment = end( $payments );

					if ( $last_payment ) {
						$last_payment->getUserMembership()->setState( PLCOM_Const::STATUSES['canceled'] );
						$last_payment->getUserMembership()->getRepository()->persist( $last_payment->getUserMembership() );
					}

					$result = true;
					break;
				case 'recurring_payment_profile_cancel':
					/** @var PLCOM_Payment $last_payment */
					$last_payment = end( $payments );

					if ( $last_payment ) {
						$last_payment->getUserMembership()->setState( PLCOM_Const::STATUSES['pending_cancelation'] );
						$last_payment->getUserMembership()->getRepository()->persist( $last_payment->getUserMembership() );
					}

					$result = true;
					break;
			}
		}

		return $result;
	}

	/**
	 * Returns the PayPal URI based on sandbox mode
	 *
	 * @return string
	 */
	private function getPaypalUri() {
		if ( $this->isSandbox() ) {
			return self::SANDBOX_VERIFY_URI;
		} else {
			return self::VERIFY_URI;
		}
	}

	/**
	 * @param PLCOM_Plan $plan
	 *
	 * @return mixed|void
	 */
	function activate_plan( PLCOM_Plan $plan ) {
		// do nothing, as we don't need to activate plans for paypal
	}
}
