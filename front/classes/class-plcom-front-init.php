<?php
/**
 * Plugins Corner Membership - https://pluginscorner.com.com
 *
 * @package plco-membership
 */

namespace PLCOMembership\front\classes;

use PLCODashboard\admin\classes\PLCO_Const;
use PLCODashboard\admin\classes\PLCO_Helpers;
use PLCODashboard\classes\PLCO_Connection;
use PLCODashboard\classes\PLCO_Abstract_Init;
use PLCODashboard\classes\repositories\PLCO_Connection_Repository;
use PLCODashboard\front\classes\PLCO_Paypal_Payment_Method;
use PLCOMembership\admin\classes\PLCOM_Const;
use PLCOMembership\admin\classes\PLCOM_Helpers;
use PLCOMembership\front\classes\repositories\PLCOM_Card_Repository;
use PLCOMembership\front\classes\repositories\PLCOM_Membership_Level_Repository;
use PLCOMembership\front\classes\repositories\PLCOM_Payment_Repository;
use PLCOMembership\front\classes\repositories\PLCOM_Recurrences_Repository;
use PLCOMembership\front\classes\repositories\PLCOM_User_Membership_Repository;
use PLCOMembership\PLCOM_Check_Payments;
use PLCOMembership\pro\classes\PLCOM_Pro_Initialize;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class PLCOM_Front_Init extends PLCO_Abstract_Init {

	/**
	 * @var PLCOM_Helpers
	 */
	protected $helper;

	/**
	 * Default Constants File Name
	 *
	 * @var string
	 */
	protected $constants_filename = "class-plcom-const.php";

	/**
	 * Default Helper File Name
	 *
	 * @var string
	 */
	protected $helper_filename = "class-plcom-helpers.php";

	/**
	 * Initialize
	 */
	public function init() {
		if ( $this->is_pro ) {
			new PLCOM_Pro_Initialize();
		}
	}

	/**
	 * Add the actions
	 */
	public function add_actions() {
		$this->loader->add_action( 'the_content', $this, 'check_permissions', 10, 1 );
		$this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_scripts', 20 );
		$this->loader->add_action( 'delete_user', $this, 'before_user_delete', 20, 3 );
		$this->loader->add_action( 'init', $this, 'check_payments' );
		$this->loader->add_filter( 'cron_schedules', $this, 'add_cron_schedule', 10, 1 );
		$this->loader->add_action( 'wp_login_failed', $this, 'login_fail' );
		$this->loader->add_action( 'wp_mail_content_type', $this, 'set_content_type' );
		$admin_bar = get_option( 'plcom_hide_admin_bar' );
		if ( (int) $admin_bar ) {
			add_filter( 'show_admin_bar', '__return_false', PHP_INT_MAX );
		}

		add_shortcode( 'plco_registration_form', array( $this, 'registration_form' ) );
		add_shortcode( 'plco_account_form', array( $this, 'account_form' ) );
		add_shortcode( 'plcom_login_form', array( $this, 'login_form' ) );
	}

	/**
	 * Sets wp_mail's content type
	 *
	 * @return string
	 */
	public function set_content_type() {
		return "text/html";
	}

	/**
	 * Checks if any payments were done
	 */
	public function check_payments() {
		new PLCOM_Check_Payments();
	}

	/**
	 * Delete data before deleting the user
	 *
	 * @param $user_id
	 */
	public function before_user_delete( $user_id ) {

		$cards       = PLCOM_Card_Repository::find_by( array( 'user_id' => $user_id ) );
		$payments    = PLCOM_Payment_Repository::find_by( array( 'user_id' => $user_id ) );
		$memberships = PLCOM_User_Membership_Repository::find_by( array( 'user_id' => $user_id ) );

		if ( ! empty( $cards ) ) {
			foreach ( $cards as $card ) {
				$card->getRepository()->destroy( $card );
			}
		}

		if ( ! empty( $payments ) ) {
			foreach ( $payments as $payment ) {
				$payment->getRepository()->destroy( $payment );
			}
		}

		if ( ! empty( $memberships ) ) {
			foreach ( $memberships as $membership ) {
				$membership->getRepository()->destroy( $membership );
			}
		}
	}

	/**
	 * Add 5 min cron schedule
	 *
	 * @param $schedules
	 *
	 * @return mixed
	 */
	public function add_cron_schedule( $schedules ) {
		if ( ! isset( $schedules["5min"] ) ) {
			$schedules["5min"] = array(
				'interval' => 300,
				'display'  => __( 'Once every 5 minutes' )
			);
		}

		return $schedules;
	}


	/**
	 * Checks if the user has permissions
	 *
	 * @param $content
	 *
	 * @return string
	 */
	public function check_permissions( $content ) {
		global $post;
		if ( ! is_admin() ) {
			$protection = maybe_unserialize( get_post_meta( $post->ID, "plco_protected", true ) );

			if ( $protection ) {
				if ( ! $this->helper::has_access( $protection ) ) {
					return wpautop( get_option( "plcom_content_restriction_message" ) );
				}
			}
		}

		return $content;

	}


	/**
	 * @param $atts
	 *
	 * @return false|string
	 */
	public function registration_form( $atts = array() ) {
		ob_start();
		if ( ! $atts ) {
			$atts = array();
		}
		$content     = "";
		$connections = PLCO_Connection_Repository::find_by( array( 'connection_type' => 'payment' ) );

		$levels = array();
		if ( isset( $_GET['m_id'] ) ) {
			$atts['membership'] = sanitize_text_field( $_GET['m_id'] );
		}

		if ( isset( $_GET['r_id'] ) ) {
			$atts['recurrence'] = sanitize_text_field( $_GET['r_id'] );
		}

		if ( isset( $atts['recurrence'] ) ) {
			/** @var PLCOM_Recurrences $recurrence */
			$recurrence = PLCOM_Recurrences_Repository::find_one_by( array( 'ID' => (int) $atts['recurrence'] ) );
			if ( $recurrence ) {
				$levels = PLCOM_Membership_Level_Repository::find_by(
					array(
						'ID'     => (int) $recurrence->getMembershipId(),
						'status' => 1
					) );
			}
		} else {
			if ( isset( $atts['membership'] ) ) {
				$levels = PLCOM_Membership_Level_Repository::find_by( array(
					'ID'     => (int) $atts['membership'],
					'status' => 1
				) );
			} else {
				$levels = PLCOM_Membership_Level_Repository::find_by( array( 'status' => 1 ) );
			}
		}


		$logged_in            = is_user_logged_in();
		$multiple_memberships = get_option( 'plcom_multiple_memberships' );
		$allow_non_members    = get_option( 'plcom_allow_non_members_registration' );

		if ( ! $allow_non_members ) {
			foreach ( $levels as $key => $level ) {
				if ( $level->getID() === 1 ) {
					unset( $levels[ $key ] );
				}
			}
		}


		$data = array(
			'logged_in'            => is_user_logged_in(),
			'multiple_memberships' => get_option( 'plcom_multiple_memberships' ),
			'allow_non_member'     => $allow_non_members,
			'levels'               => $levels,
			'connections'          => $connections,
			'atts'                 => $atts,
		);

		$data = apply_filters( 'plcom_registration_form_data', $data );

		if ( $logged_in ) {
			$paid_memberships = PLCOM_User_Membership_Repository::find_by( array(
				'user_id'       => get_current_user_id(),
				'state'         => 'ACTIVE',
				'membership_id' => array(
					'condition' => '>',
					'value'     => 1,
				),
			) );

			if ( count( $paid_memberships ) > 0 && ! $multiple_memberships ) {
				$this->template( 'no_registration.php' );
				return ob_get_clean();
			}
		}

		$this->template( 'registration_form.php', $data );

		echo apply_filters( "plcom_after_registration_form", $content );

		return ob_get_clean();
	}

	/**
	 * Checks a Payment
	 *
	 * @throws \Exception
	 */
	public function check_payment() {
		$susccess = get_option( 'plcom_success_page', array(
			'ID'   => '',
			'name' => '',
			'url'  => ''
		) );

		$post_id = url_to_postid( ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? "https" : "http" ) . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" );

		/**
		 * Check if Paypal payment has been made
		 */
		do_action( "plcom_before_check_payment" );

		if ( ! is_admin() && $susccess["ID"] === $post_id && isset( $_GET["ba_token"] ) && isset( $_GET["token"] ) && isset( $_GET["subscription_id"] ) ) {
			$connection          = PLCO_Helpers::get_connection_by_name( "paypal" );
			$payment_method      = new PLCO_Paypal_Payment_Method( $connection );
			$payments            = PLCOM_Payment_Repository::find_by( array( 'subscription_id' => sanitize_text_field( $_GET["subscription_id"] ) ) );
			$unprocessed_payment = "";

			if ( ! empty( $payments ) ) {
				/** @var PLCOM_Payment $payment */
				foreach ( $payments as $payment ) {
					if ( $payment->getStatus() === "APPROVAL_PENDING" && (int) $payment->getUser()->data->ID === get_current_user_id() ) {
						$unprocessed_payment = $payment;
					}
				}
			}

			if ( $unprocessed_payment ) {
				$payment_method->validate_payment( $unprocessed_payment );
			}

			/**
			 * After Payment Hook
			 */
			do_action( "plcom_after_check_payment" );
		}
	}

	/**
	 * Account form shortcode
	 *
	 * @return false|string
	 */
	public function account_form() {
		ob_start();
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			wp_redirect( get_home_url() );
		}

		$data = array(
			'wp_user'              => wp_get_current_user(),
			'user_meta'            => get_user_meta( $user_id ),
			'memberships'          => PLCOM_User_Membership_Repository::find_by( [ 'user_id' => $user_id ] ),
			'due_payments'         => $this->helper::get_user_requires_action_payments( $user_id ),
			'home_url'             => get_home_url(),
			'logout_url'           => wp_logout_url( get_home_url() ),
			'allow_account_delete' => get_option( 'plcom_allow_account_delete' ),
		);

		$this->template( 'account_form.php', apply_filters( 'plcom_account_form_data', $data ) );

		return ob_get_clean();
	}


	/**
	 * Set the login form
	 *
	 * @return string|void
	 */
	public function login_form() {
		$user_id = get_current_user_id();

		$account_page = get_option( 'plcom_account_page', array(
			'ID'   => '',
			'name' => '',
			'url'  => ''
		) );

		if ( $user_id ) {
			if ( ! is_admin() && ! is_home() && ! $account_page ) {
				wp_redirect( get_home_url() );
			}
		}

		$args = array(
			'echo'           => false,
			'redirect'       => ! empty( $account_page['url'] ) ? $account_page['url'] : get_home_url(),
			'value_remember' => true
		);

		return wp_login_form( $args );
	}

	/**
	 * Redirect to the same page at failed login
	 *
	 * @param $username
	 *
	 * @return void
	 */
	public function login_fail( $username ) {
		$referrer = $_SERVER['HTTP_REFERER'];  // where did the post submission come from?
		// if there's a valid referrer, and it's not the default log-in screen
		if ( ! empty( $referrer ) && ! strstr( $referrer, 'wp-login' ) && ! strstr( $referrer, 'wp-admin' ) ) {
			wp_redirect( $referrer . '?login=failed' );  // let's append some information (login=failed) to the URL for the theme to use
			exit;
		}
	}

	/**
	 * Enqueue the front end scripts
	 */
	public function enqueue_scripts() {
		$this->enqueue_style( 'plcom-styles-css', $this->const::url( 'css/styles.css', $this->include_dir ) );
		$connection = PLCO_Helpers::get_connection_by_name( "stripe" );

		if ( $connection ) {
			$this->enqueue_script( 'plcom-stripe-js', 'https://js.stripe.com/v3/', array(
				'jquery'
			), false, true );
		}
		$this->enqueue_script( 'plcom-main-front-js', $this->const::url( '/js/dist/main.min.js', $this->include_dir ), array(
			'jquery'
		), false, true );

		$this->enqueue_script( 'plcom-imask-front-js', 'https://cdnjs.cloudflare.com/ajax/libs/imask/3.4.0/imask.min.js', array(
			'jquery'
		), false, true );


		wp_localize_script( 'plcom-main-front-js', 'PLCOM_Main', $this->get_localization() );
	}

	/**
	 * Localizes Data
	 *
	 * @return array
	 */
	public function get_localization() {

		$connection = PLCO_Helpers::get_connection_by_name( "stripe" );

		return array(
			'nonce'        => wp_create_nonce( 'wp_rest' ),
			'stripe_pub'   => $connection ? $connection->getApiKey() : '',
			'pro'          => get_option( 'plcom_convex_info', 0 ),
			'success_page' => get_option( 'plcom_success_page', array(
				'ID'   => '',
				'name' => '',
				'url'  => ''
			) ),
		);
	}
}
