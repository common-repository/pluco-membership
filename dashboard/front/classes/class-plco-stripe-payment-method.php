<?php
/**
 * PluginsCorner - https://pluginscorner.com
 *
 * @package plco-membership
 */

namespace PLCODashboard\front\classes;

use DateInterval;
use DateTime;
use Exception;
use PLCODashboard\admin\classes\PLCO_Helpers;
use PLCODashboard\classes\PLCO_Abstract_Model;
use PLCODashboard\classes\PLCO_Abstract_Payment_Method;
use PLCODashboard\classes\PLCO_Mail_Factory;
use PLCOMembership\admin\classes\PLCOM_Const;
use PLCOMembership\front\classes\PLCOM_Card;
use PLCOMembership\front\classes\PLCOM_Membership_Level;
use PLCOMembership\front\classes\PLCOM_Payment;
use PLCOMembership\front\classes\PLCOM_Plan;
use PLCOMembership\front\classes\PLCOM_Recurrences;
use PLCOMembership\front\classes\PLCOM_User_Membership;
use PLCOMembership\front\classes\repositories\PLCOM_Card_Repository;
use PLCOMembership\front\classes\repositories\PLCOM_Payment_Repository;
use PLCOMembership\front\classes\repositories\PLCOM_Plan_Repository;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use WP_User;

class PLCO_Stripe_Payment_Method extends PLCO_Abstract_Payment_Method {

	/**
	 * Set the connection
	 *
	 * @return StripeClient
	 */
	public function connection() {

		return new StripeClient( $this->connection->getApiSecret() );
	}

	/**
	 * @return bool|PLCO_Abstract_Model
	 * @throws \ReflectionException
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
	 * @param PLCOM_Plan $plan
	 * @param PLCOM_Recurrences $recurrence
	 * @param PLCOM_Membership_Level $membership_level
	 *
	 * @return PLCO_Abstract_Model|bool
	 */
	public function add_plan( PLCOM_Plan $plan, PLCOM_Recurrences $recurrence, PLCOM_Membership_Level $membership_level ) {
		$connection = $this->connection();

		$currency_handle = '';
		foreach ( PLCOM_Const::CURRENCIES as $currency ) {
			if ( (int) $currency["ID"] === (int) $recurrence->getCurrency() ) {
				$currency_handle = strtoupper( $currency["handle"] );
			}
		}

		$recurrence_type_name = '';
		foreach ( PLCOM_Const::RECURRENCE_TYPES as $recurrence_type ) {
			if ( (int) $recurrence_type["ID"] === (int) $recurrence->getRecurrenceType() ) {
				$recurrence_type_name = $recurrence_type["name"];
			}
		}

		try {
			$connection->products->create( array(
				'name' => $membership_level->getMembershipName() . " " . $recurrence->getRecurrence() . " " . $recurrence_type_name,
				'id'   => str_replace( " ", "", $membership_level->getMembershipName() ) . "_" . $recurrence->getRecurrence() . "_" . $recurrence_type_name,
			) );
		} catch ( Exception $ex ) {
			if ( $ex->getMessage() !== "Product already exists." ) {
				dump( $ex );
				/**
				 * TODO: LOG PLAN CREATION ERROR
				 */
			}
		}

		try {
			$result = $connection->prices->create( array(
				'unit_amount_decimal' => $recurrence->getAmount() * 100,
				'currency'            => $currency_handle,
				'recurring'           => array(
					'interval'       => strtolower( substr( $recurrence_type_name, 0, - 1 ) ),
					"interval_count" => $recurrence->getRecurrence()
				),
				'product'             => str_replace( " ", "", $membership_level->getMembershipName() ) . "_" . $recurrence->getRecurrence() . "_" . $recurrence_type_name,
			) );
			$plan->setPlanCreated( $result->id );
			$plan->setPlanActivated( $result->id );

			return PLCOM_Plan_Repository::persist( $plan );

		} catch ( Exception $ex ) {
			if ( $ex->getMessage() !== "Product already exists." ) {
				dump( $ex );
				/**
				 * TODO: LOG PLAN CREATION ERROR
				 */
			}
		}

		return false;
	}

	/**
	 * Executes the payment
	 *
	 * @param PLCOM_User_Membership $user
	 * @param PLCOM_Recurrences $recurrence
	 * @param PLCOM_Membership_Level $membership_level
	 * @param array $data
	 *
	 * @return object
	 */
	public function make_payment( PLCOM_User_Membership $user, PLCOM_Recurrences $recurrence, PLCOM_Membership_Level $membership_level, array $data = array() ) {
		$connection = $this->connection();
		$cards      = PLCOM_Card_Repository::find_by( array( 'user_id', $user->getUser()->data->ID ) );

		if ( isset( $data['existing_card'] ) ) {
			/** @var PLCOM_Card $card */
			foreach ( $cards as $existing_card ) {
				if ( (int) $existing_card->getID() === (int) $data['existing_card'] ) {
					$card = $existing_card;
				}
			}

		} else {
			if ( ! $this->check_cc( sanitize_text_field( $data['card_number'] ) ) ) {
				$message = array( 'error' => true, 'message' => 'Card Number is incorrect or invalid' );

				return (object) $message;
			}

			$date      = DateTime::createFromFormat( 'd/m/y', '01/' . $data['card_exp'] );
			$card_data = array(
				'user_id'         => $user->getUser()->data->ID,
				'full_name'       => sanitize_text_field( $data['card_name'] ),
				'card_number'     => substr( sanitize_text_field( $data['card_number'] ), - 4 ),
			);
			$card      = new PLCOM_Card( (object) $card_data );
		}


		/**
		 * create customer
		 */
		try {
			if ( ! $card->getCustomerId() ) {
				$result = $connection->customers->create( array(
					'email' => $user->getUser()->data->user_email,
					'name'  => $card->getFullName(),
				) );

				$card->setCustomerId( $result->id );
			}

			if ( ! $card->getPaymentMethodId() ) {
				$date   = DateTime::createFromFormat( 'd/m/y', '01/' . $data['card_exp'] );
				$result = $connection->paymentMethods->create( array(
					'type' => 'card',
					'card' => array(
						'number'    => sanitize_text_field( $data['card_number'] ),
						'exp_month' => $date->format( 'm' ),
						'exp_year'  => $date->format( 'y' ),
						'cvc'       => sanitize_text_field( $data['card_ccv'] ),
					),
				) );

				$card->setPaymentMethodId( $result->id );

				$connection->paymentMethods->attach(
					$card->getPaymentMethodId(),
					array( 'customer' => $card->getCustomerId() )
				);
			}

			$card = $card->getRepository()->persist( $card );

			$connection_plan = "";

			foreach ( $recurrence->getPlans() as $plan ) {
				if ( (int) $plan->getConnectionId() === (int) $this->connection->getID() ) {
					$connection_plan = $plan;
					break;
				}
			}

			if ( ! $connection_plan ) {
				$connection_plan = $this->add_plan(
					new PLCOM_Plan(
						(object) array(
							'recurrence_id' => $recurrence->getID(),
							'connection_id' => $this->connection->getID(),
						)
					),
					$recurrence,
					$membership_level
				);
			}

			$subscription_data = array(
				'customer'               => $card->getCustomerId(),
				'default_payment_method' => $card->getPaymentMethodId(),
				'off_session'            => true,
				'items'                  => array(
					array( 'price' => $connection_plan->getPlanActivated() ),
				),
			);

			if ( $recurrence->getCycles() > 0 ) {
				$date = new DateTime( 'NOW' );

				if ( (int) $recurrence->getRecurrenceType() === 1 ) {
					$type = 'D';
				} elseif ( (int) $recurrence->getRecurrenceType() === 2 ) {
					$type = 'M';
				} else {
					$type = 'Y';
				}
				$interval = new DateInterval( 'P' . $recurrence->getCycles() . $type );
				$date->add( $interval );
				$subscription_data['cancel_at'] = $date->getTimestamp();
			}

			$subscription_result = $connection->subscriptions->create( $subscription_data );


			if ( $subscription_result->status === 'incomplete' ) {

				$results = $connection->paymentIntents->all( [ 'limit' => 100 ] );

				/**
				 * Subtract 12h
				 */
				$date     = new DateTime( 'NOW' );
				$interval = new DateInterval( 'PT12H' );
				$date->sub( $interval );
				$created = $date->getTimestamp();

				foreach ( $results as $result ) {
					if ( $result->status === "requires_action" && $result->payment_method == $card->getPaymentMethodId() && $result->created > $created ) {

						$return = array(
							'id'             => $subscription_result->id,
							'transaction_id' => $result->id,
							'status'         => $result->status,
							'next_action'    => $result->next_action->type,
							'client_secret'  => $result->client_secret,
						);

						return (object) $return;
					}
				}
			}


			if ( $subscription_result->status === "active" ) {
				//TODO: TRY to get the invoice from the subscription instead of finding it
				$results = $connection->invoices->all( [
					'limit'        => 100,
					'subscription' => $subscription_result->id,
					'status'       => 'paid'
				] );
				/**
				 * Subtract 12h
				 */
				$date     = new DateTime( 'NOW' );
				$interval = new DateInterval( 'PT12H' );
				$date->sub( $interval );
				$created = $date->getTimestamp();

				foreach ( $results->data as $result ) {
					if ( $result->created > $created ) {
						$return = array(
							'id'             => $subscription_result->id,
							'transaction_id' => $result->payment_intent,
							'status'         => "PENDING",
							'next_action'    => 'validate',
							'client_secret'  => '',
						);

						return (object) $return;
					}
				}
			}

		} catch ( Exception $ex ) {
			$message = array( 'error' => true, 'message' => $ex->getMessage() );

			return (object) $message;
			/**
			 * TODO: LOG ERROR
			 */
		}

		$message = array( 'error' => true, 'message' => "Transaction Failed. Please Retry or use another Credit Card" );
		return (object ) $message;
	}

	/**
	 * @param PLCOM_Payment $payment
	 *
	 * @return array|bool
	 * @throws ApiErrorException
	 */
	public function confirm_payment( PLCOM_Payment $payment ) {
		$connection = $this->connection();

		try {
			$payment_intent = $connection->paymentIntents->retrieve( $payment->getTransactionId() );

			return array(
				'confirmation_required' => true,
				'sdk'                   => $payment_intent->next_action->type === "use_stripe_sdk",
				'client_secret'         => $payment_intent->client_secret,
				'payment_id'            => $payment->getID()
			);

		} catch ( ApiErrorException $ex ) {
			echo $ex->getCode();
			echo $ex->getData();
			// TODO: Log an error message
		} catch ( Exception $ex ) {
			// TODO: Log an error message
		}

		return false;


	}

	/**
	 * Validates a card
	 *
	 * @param $card_number
	 *
	 * @return bool
	 */
	public function validate_card( $card_number ) {
		$card_number = preg_replace( "/\D|\s/", "", $card_number );  # strip any non-digits
		$card_length = strlen( $card_number );
		$parity      = $card_length % 2;
		$sum         = 0;
		for ( $i = 0; $i < $card_length; $i ++ ) {
			$digit = $card_number[ $i ];
			if ( $i % 2 == $parity ) {
				$digit = $digit * 2;
			}
			if ( $digit > 9 ) {
				$digit = $digit - 9;
			}
			$sum = $sum + $digit;
		}

		return ( $sum % 10 == 0 );
	}

	/**
	 * Checks if credit card is real
	 *
	 * @param $cc
	 *
	 * @return bool|string
	 */
	function check_cc( $cc ) {

		$cards   = array(
			"visa"       => "(4\d{12}(?:\d{3})?)",
			"amex"       => "(3[47]\d{13})",
			"jcb"        => "(35[2-8][89]\d\d\d{10})",
			"maestro"    => "((?:5020|5038|6304|6579|6761)\d{12}(?:\d\d)?)",
			"solo"       => "((?:6334|6767)\d{12}(?:\d\d)?\d?)",
			"mastercard" => "(5[1-5]\d{14})",
			"discover" => "(^6(?:011\d{12}|5\d{14}|4[4-9]\d{13}|22(?:1(?:2[6-9]|[3-9]\d)|[2-8]\d{2}|9(?:[01]\d|2[0-5]))\d{10})$)",
			"diners" => "(^3(?:0[0-5]|[68][0-9])[0-9]{11}$)",
			"union" => "(^(62[0-9]{14,17})$)",
			"switch"     => "(?:(?:(?:4903|4905|4911|4936|6333|6759)\d{12})|(?:(?:564182|633110)\d{10})(\d\d)?\d?)",
		);
		$names   = array( "Visa", "American Express", "JCB", "Maestro", "Solo", "Mastercard", "Discover", "Diners", "Union", "Switch" );
		$matches = array();
		$pattern = "#^(?:" . implode( "|", $cards ) . ")$#";
		$cc_nr = preg_replace( "/\D+/", "", $cc );
		$result  = preg_match( $pattern, $cc_nr, $matches );

		return ( $result > 0 ) ? $names[ sizeof( $matches ) - 2 ] : false;
	}

	/**
	 * Validate the payment
	 *
	 * @param PLCOM_Payment $payment
	 *
	 * @return false|PLCOM_Payment
	 */
	public function validate_payment( PLCOM_Payment $payment ) {
		$connection = $this->connection();

		try {
			$subscription_result = $connection->subscriptions->retrieve( $payment->getSubscriptionId() );
			$invoice             = $connection->invoices->retrieve( $subscription_result->latest_invoice, array() );

			if ( $subscription_result->status === 'active' && $invoice->status === "paid" ) {
				$encoded = json_encode( $this->ipn );

				$next_billing_date = new DateTime();
				$next_billing_date->setTimestamp( $subscription_result->current_period_end );
				$payment->setOriginalResponse( $encoded );
				$payment->setStatus( "ACTIVE" );
				$payment->setTransactionID( $invoice->payment_intent );

				$recurrence = $payment->getRecurrence();

				$recurrence_type_name = "";
				foreach ( PLCOM_Const::RECURRENCE_TYPES as $recurrence_type ) {
					if ( (int) $recurrence_type["ID"] === (int) $recurrence->getRecurrenceType() ) {
						$recurrence_type_name = $recurrence_type["name"];
					}
				}

				$currency_handle = '';
				foreach ( PLCOM_Const::CURRENCIES as $currency ) {
					if ( (int) $currency["ID"] === (int) $recurrence->getCurrency() ) {
						$currency_handle = strtoupper( $currency["handle"] );
					}
				}

				$payment->getUserMembership()->setNextBillingDate( $next_billing_date->format( 'Y-m-d H:i:s' ) );

				/**
				 * Set expiration date if the membership expires
				 */
				if ( $subscription_result->cancel_at ) {
					/** @var PLCOM_Recurrences $recurrence */
					$end_time = new DateTime();
					$end_time->setTimestamp( $subscription_result->cancel_at );
					$payment->getUserMembership()->setExpiresAt( $end_time->format( 'Y-m-d H:i:s' ) );
				}

				$payment->getUserMembership()->setMembership( $payment->getMembership()->getID() );

				/** @var WP_User $user */
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
		} catch ( Exception $ex ) {
			// TODO: Log an error message
		}

		return false;

	}

	/**
	 *
	 * @param PLCOM_Payment $payment
	 *
	 * @return PLCOM_Payment|bool
	 */
	public function requires_action( PLCOM_Payment $payment ) {
		$requires_action_subject = get_option( 'plcom_requires_action_template_subject', PLCOM_Const::REQUIRES_ACTION_TEMPLATE_SUBJECT );
		$requires_action_message = get_option( 'plcom_requires_action_template_message', PLCOM_Const::REQUIRES_ACTION_TEMPLATE_MESSAGE );

		$factory = new PLCO_Mail_Factory();
		/** @var PLCO_Wordpress_Mail_Service $service */
		$service = $factory->get_mail_service();
		$service->send_mail('', $payment->getUser()->data->user_email, $requires_action_subject, $requires_action_message, array( "content-type" => "text/html" ));

		$payment->getUserMembership()->setState( PLCOM_Const::STATUSES['active'] );
		$payment->getUserMembership()->getRepository()->persist( $payment->getUserMembership() );
		$payment->setStatus( PLCOM_Const::STATUSES['requires_action'] );

		$payment->getRepository()->persist( $payment );

		return true;
	}

	/**
	 * Cancel the subscription
	 *
	 * @param $subscription_id
	 *
	 * @return bool
	 * @throws ApiErrorException
	 */
	public function cancel_subscription( $subscription_id ) {
		$connection = $this->connection();

		try {
			$result = $connection->subscriptions->cancel(
				$subscription_id,
				array()
			);

			if ( $result->status === 'canceled' ) {
				return true;
			}
		} catch ( ApiErrorException $ex ) {
			echo $ex->getCode();
			echo $ex->getData();
			// TODO: Log an error message
		} catch ( Exception $ex ) {
			// TODO: Log an error message
		}

		return false;

	}

	/**
	 * Test the connection
	 *
	 * @return bool
	 */
	public function testConnection() {
		$stripe = $this->connection();

		try {
			$params = array( 'limit' => '3' );
			$stripe->prices->all( $params );
		} catch ( Exception $ex ) {

			return $ex->getMessage();
		}

		return true;
	}

	/**
	 * Makes sure the IPN is valid
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public function is_valid_ipn( array $data = array() ) {

		/**
		 * Check for test mode header
		 */
		$signatures = [];
		$items      = \explode( ',', $data['sig_header'] );

		foreach ( $items as $item ) {
			$itemParts = \explode( '=', $item, 2 );
			if ( \trim( $itemParts[0] ) === 'v0' ) {
				$signatures[] = $itemParts[1];
			}
		}

		if ( count( $signatures ) ) {
			return true;
		}

		try {
			$event = Webhook::constructEvent(
				$data['payload'], $data['sig_header'], $this->connection->getApiSecret(), 5000
			);

			return ! ! $event;
		} catch ( \UnexpectedValueException|SignatureVerificationException $e ) {
			return false;
		}
	}

	/**
	 * Processes the IPN
	 * @return bool
	 */
	public function process_ipn() {
		$result = false;

		if ( isset( $this->ipn['type'] ) ) {

			switch ( $this->ipn['type'] ) {

				case 'invoice.paid':
				case 'invoice.payment_action_required':

					/** Invoice was paid  */
					$payment = PLCOM_Payment_Repository::find_one_by( array( 'transaction_id' => sanitize_text_field( $this->ipn['data']->object->payment_intent ) ) );
					/**
					 * If the payment was found we're at our first payment otherwise we need to create one
					 */
					if ( ! $payment ) {
						$payments     = PLCOM_Payment_Repository::find_by( array( 'subscription_id' => sanitize_text_field( $this->ipn['data']->object->subscription ) ) );

						if(count($payments) > 0) {
							$last_payment = end( $payments );
							$payment      = new PLCOM_Payment( (object)
							array(
								'user_id'            => $last_payment->getUser()->data->ID,
								'user_membership_id' => $last_payment->getUserMembership()->getID(),
								'membership_id'      => $last_payment->getMembership()->getID(),
								'recurrence_id'      => $last_payment->getRecurrence()->getID(),
								'subscription_id'    => $last_payment->getSubscriptionId(),
								'transaction_id'     => $this->ipn['data']->object->payment_intent,
								'status'             => 'APPROVAL_PENDING',
								'payment_amount'     => $last_payment->getPaymentAmount(),
								'payment_type_id'    => $last_payment->getPaymentType()->getID(),
							)
							);
						}
					}

					if ( $payment ) {
						if ( $this->ipn['type'] === 'invoice.payment_action_required' ) {
							$result = $this->requires_action( $payment );
						} else {
							$result = $this->validate_payment( $payment );



							if($result && $this->ipn['data']->object->billing_reason === 'subscription_create') {
								$data = array(
									'user_membership_id' => $payment->getUserMembership()->getID(),
								);

								do_action('plcom_initial_membership_created', $data);
							}

						}
					}

					break;

				case 'invoice.payment_failed':
					/** @var PLCOM_Payment $payment */
					$payment = PLCOM_Payment_Repository::find_one_by( array( 'transaction_id' => sanitize_text_field( $this->ipn['data']->object->payment_intent ) ) );

					if ( $payment ) {
						$payment->setStatus( PLCOM_Const::STATUSES['canceled'] );
					}

					if ( ! $payment ) {
						$payment = PLCOM_Payment_Repository::find_one_by( array( 'subscription_id' => sanitize_text_field( $this->ipn['data']->object->subscription ) ) );
					}

					if ( $payment ) {
						$payment->getUserMembership()->setState( PLCOM_Const::STATUSES['canceled'] );
						$payment->getUserMembership()->getRepository()->persist( $payment->getUserMembership() );
						$payment->getRepository()->persist( $payment );
					}


					$result = true;

					break;
				case 'customer.subscription.deleted':
					$payments = PLCOM_Payment_Repository::find_by( array( 'subscription_id' => sanitize_text_field( $this->ipn['data']->object->id ) ) );
					/** @var PLCOM_Payment $last_payment */
					$last_payment = end( $payments );

					if ( $last_payment ) {
						$last_payment->getUserMembership()->setState( PLCOM_Const::STATUSES['canceled'] );
						$last_payment->getUserMembership()->getRepository()->persist( $last_payment->getUserMembership() );
					}

					$result = true;
					break;
				case 'charge.refunded':
					/** @var PLCOM_Payment $payment */
					$payment = PLCOM_Payment_Repository::find_one_by( array( 'transaction_id' => sanitize_text_field( $this->ipn['data']->object->payment_intent ) ) );

					if($payment) {
						$payment->getUserMembership()->setState( PLCOM_Const::STATUSES['canceled'] );
						$payment->setStatus(PLCOM_Const::STATUSES['refunded']);

						$payment->getUserMembership()->getRepository()->persist( $payment->getUserMembership() );
						$payment->getRepository()->persist( $payment );
					}

					break;
			}
		}

		return $result;
	}

	/**
	 * @param PLCOM_Plan $plan
	 *
	 * @return mixed|void
	 */
	function activate_plan( PLCOM_Plan $plan ) {
		// TODO: Plans do not need to be activated
	}
}
