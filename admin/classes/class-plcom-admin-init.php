<?php
/**
 * Plugins Corner Membership - https://pluginscorner.com.com
 *
 * @package plco-membership
 */

namespace PLCOMembership\admin\classes;

use PLCODashboard\admin\classes\PLCO_Const;
use PLCODashboard\admin\classes\PLCO_Helpers;
use PLCODashboard\classes\PLCO_Abstract_Init;
use PLCODashboard\classes\PLCO_License_Manager;
use PLCODashboard\front\classes\PLCO_Crontab;
use PLCOMembership\front\classes\repositories\PLCOM_Membership_Level_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

/**
 * Class PLCOM_Admin_Init
 *
 * @package PLCOMembership\admin\classes
 */
class PLCOM_Admin_Init extends PLCO_Abstract_Init {


	/**
	 * @var PLCOM_Const
	 */
	protected $const;

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
	 * Add the actions
	 */
	public function add_actions() {
		$this->loader->add_action( 'admin_footer', $this, 'add_footer_data' );
		$this->loader->add_filter( 'PLCO_dashboard_items', $this, 'add_to_admin_menu', 10 );
		$this->loader->add_action( 'admin_enqueue_scripts', $this, 'admin_enqueue_scripts', 20 );
		$this->loader->add_action( 'add_meta_boxes', $this, 'set_permissions_metabox', 0, 2 );

		$args       = array(
			'public'   => true,
			'_builtin' => true,
		);
		$post_types = get_post_types( $args );
		foreach ( $post_types as $post_type ) {
			$this->loader->add_filter( 'manage_' . $post_type . '_posts_columns', $this, 'set_membership_column', 20 );
			$this->loader->add_action( 'manage_' . $post_type . '_posts_custom_column', $this, 'set_membership_column_data', 20, 2 );
		}
		$this->loader->add_filter( 'display_post_states', $this, 'add_post_state', 10, 2 );

		if ( file_exists( PLCOM_Const::plugin_path() . 'pro/admin/classes/class-plcom-pro-admin-init.php' ) ) {
			$this->loader->add_filter( 'plco_dashboard_localization', $this, 'mark_dash_as_pro' );
		}

	}

	/**
	 * @param $data
	 *
	 * @return mixed
	 */
	public function mark_dash_as_pro( $data ) {
		$data['is_pro'] = $this->is_pro;

		return $data;
	}


	/**
	 * @param $post_states
	 * @param $post
	 *
	 * @return array
	 */
	public function add_post_state( $post_states, $post ) {

		$registration = get_option( 'plcom_registration_page', array(
			'ID'   => '',
			'name' => '',
			'url'  => ''
		) );

		$cancel = get_option( 'plcom_cancel_page', array(
			'ID'   => '',
			'name' => '',
			'url'  => ''
		) );

		$account = get_option( 'plcom_account_page', array(
			'ID'   => '',
			'name' => '',
			'url'  => ''
		) );
		$success = get_option( 'plcom_success_page', array(
			'ID'   => '',
			'name' => '',
			'url'  => ''
		) );

		if ( $success["ID"] && $post->ID === (int) $success["ID"] && ! in_array( 'Membership Success Page', $post_states ) ) {
			$post_states[] = 'Membership Success Page';
		}

		if ( $account["ID"] && $post->ID === (int) $account["ID"] && ! in_array( 'Membership Account Page', $post_states ) ) {
			$post_states[] = 'Membership Account Page';
		}

		if ( $cancel["ID"] && $post->ID === (int) $cancel["ID"] && ! in_array( 'Membership Cancelation Page', $post_states ) ) {
			$post_states[] = 'Membership Cancelation Page';
		}

		if ( $registration["ID"] && $post->ID === (int) $registration["ID"] && ! in_array( 'Membership Registration Page', $post_states ) ) {
			$post_states[] = 'Membership Registration Page';
		}

		return $post_states;

	}


	/**
	 *
	 */
	public function set_permissions_metabox() {
		$args       = array(
			'public'   => true,
			'_builtin' => true,
		);
		$post_types = get_post_types( $args );

		foreach ( $post_types as $post_type ) {
			if ( $post_type === "attachment" ) {
				return;
			}
			add_meta_box(
				'permissions_meta_box', // $id
				'Protection', // $title
				array( $this, 'set_permissions_metabox_content' ), // $callback
				$post_type,
				'side', // $context
				'high' // $priority
			);
		}
	}

	/**
	 *
	 */
	public function set_permissions_metabox_content() {
		global $post;
		echo '<span data-id="' . $post->ID . '" class="plco-material plco-protection-wrapper"></span>';
	}

	/**
	 * @param $actions
	 * @param $post
	 *
	 * @return mixed
	 */
	public function search_google( $actions, $post ) {
		$actions['google_link'] = '<a href="http://google.com/search?q=' . $post->post_title . '" class="google_link">' . __( 'Search Google for Page Title' ) . '</a>';

		return $actions;
	}

	/**
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function set_membership_column( $columns ) {
		$columns['plco_protection'] = __( 'Protection', $this->const::T );

		return $columns;
	}

	/**
	 * @param $column
	 * @param $post_id
	 */
	public function set_membership_column_data( $column, $post_id ) {
		if ( $column === "plco_protection" ) {
			echo '<span data-id="' . $post_id . '" class="plco-material plco-protection-wrapper"></span>';
		}
	}

	/**
	 *
	 */
	public function add_footer_data() {
		if ( ! get_option( 'plcom_first_run_finished' ) ) {
			echo '<div class="plco-dummy-editor-wrapper"> ' . wp_editor( "", "plco-dummy-editor" ) . '</div>';
		}
	}

	/**
	 *
	 */
	private function check_plans() {
		$this->helper::check_plans();
	}

	/**
	 * Add the plugin to the dashboard admin menu
	 *
	 * @param $items
	 *
	 * @return array
	 */
	public function add_to_admin_menu( $items ) {
		$exists = false;

		foreach ( $items as $item ) {
			if ( in_array( 'plco_membership', $item ) ) {
				$exists = true;
			}
		}

		if ( ! $exists ) {
			$items[] = array(
				'ID'          => 'plco_membership',
				'name'        => 'Membership',
				'description' => "<p>Membership plugin. Create membership levels easily directly on your pages while giving access to them instead of navigating to setting pages and getting lost within the admin area. </p><p>Avalable payment integrations include PayPal, Stripe, Authorize.net and much more ...</p>",
				'state'       => '',
				'btn_txt'     => 'Add Your Memberships',
				'capability'  => 'manage_options',
				'img'         => PLCOM_Const::plugin_uri() . 'images/membership-logo.png',
				'installed'   => true,
				'function'    => array( $this, 'display' ),
				'url'         => '',
			);
		}


		return $items;

	}

	/**
	 * Enqueue all the needed admin scripts for the plugin
	 */
	public function admin_enqueue_scripts( $handle ) {
		$this->enqueue_style( 'plcom-styles-css', PLCOM_Const::url( 'css/styles.css', $this->include_dir ) );

		/**
		 * Enqueue the admin js which will hold all the backbone stuff
		 */

		$this->enqueue_script( 'plcom-main-js', PLCOM_Const::url( '/js/dist/membership.min.js', $this->include_dir ), array(
			'jquery',
			'backbone',
			'wp-color-picker',
			'wp-hooks',
		), false, true );

		wp_localize_script( 'plcom-main-js', 'PLCO_Membership', $this->get_localization( $handle ) );

		$this->load_backbone_templates();
	}

	/**
	 * Return the template path
	 *
	 * @return string
	 */
	public function bb_template_path() {
		return PLCOM_Const::path( '/templates/views', $this->include_dir );
	}

	/**
	 * Return data for localizing
	 *
	 * @return array
	 */
	private function get_localization( $handle ) {

		/**
		 * get_membership_levels
		 */
		$wp_roles              = new \WP_Roles();
		$roles                 = array();
		$caps                  = array();
		$capabilities          = array();
		$extra_membership_data = array();

		foreach ( $wp_roles->roles as $key => $role ) {
			$roles[] = array( 'ID' => $key, 'name' => $role['name'], 'capabilities' => $role['capabilities'] );
			$caps    = array_merge( $caps, $role['capabilities'] );
		}

		foreach ( $caps as $key => $capability ) {
			$capabilities[]['capability'] = $key;
		}

		if ( ! $this->is_pro ) {
			$membership_levels = PLCOM_Membership_Level_Repository::get_all();
		}

		$extra_membership_data = apply_filters( "plco_extra_membership_data", $extra_membership_data );

		$data = array(
			'first_run'                => array(
				'first_run_finished' => get_option( 'plcom_first_run_finished' ),
			),
			'is_membership_admin_page' => $handle === "plugins-corner_page_plco_membership",
			'general_data'             => array(
				'registration_page'              => get_option( 'plcom_registration_page', array(
					'ID'   => '',
					'name' => '',
					'url'  => ''
				) ),
				'success_page'                   => get_option( 'plcom_success_page', array(
					'ID'   => '',
					'name' => '',
					'url'  => ''
				) ),
				'cancel_page'                    => get_option( 'plcom_cancel_page', array(
					'ID'   => '',
					'name' => '',
					'url'  => ''
				) ),
				'account_page'                   => get_option( 'plcom_account_page', array(
					'ID'   => '',
					'name' => '',
					'url'  => ''
				) ),
				'grace_period'                   => get_option( 'plcom_grace_period', 15 ),
				'cancel_period'                  => get_option( 'plcom_cancel_period', 30 ),
				'allow_non_members_registration' => get_option( 'plcom_allow_non_members_registration' ),
				'multiple_memberships'           => get_option( 'plcom_multiple_memberships' ),
				'allow_users_to_admin'           => get_option( 'plcom_allow_users_to_admin' ),
				'hide_admin_bar'                 => get_option( 'plcom_hide_admin_bar' ),
				'allow_account_delete'           => get_option( 'plcom_allow_account_delete' ),
				'content_restriction_message'    => get_option( 'plcom_content_restriction_message', $this->const::DEFAULT_RESTRICTION_MESSAGE ),
				'expired_restriction_message'    => get_option( 'plcom_expired_restriction_message', $this->const::DEFAULT_EXPIRED_RESTRICTION_MESSAGE ),
			),
			'email_templates'          => array(
				'success_template_subject'         => get_option( 'plcom_success_template_subject', $this->const::SUCCESS_TEMPLATE_SUBJECT ),
				'success_template_message'         => get_option( 'plcom_success_template_message', $this->const::SUCCESS_TEMPLATE_MESSAGE ),
				'cancel_template_subject'          => get_option( 'plcom_cancel_template_subject', $this->const::CANCEL_TEMPLATE_SUBJECT ),
				'cancel_template_message'          => get_option( 'plcom_cancel_template_message', $this->const::CANCEL_TEMPLATE_MESSAGE ),
				'requires_action_template_subject' => get_option( 'plcom_requires_action_template_subject', $this->const::REQUIRES_ACTION_TEMPLATE_SUBJECT ),
				'requires_action_template_message' => get_option( 'plcom_requires_action_template_message', $this->const::REQUIRES_ACTION_TEMPLATE_MESSAGE ),
			),
			'membership_levels'        => isset( $membership_levels ) ? (array) $membership_levels : [],
			'extra_mebership_data'     => $extra_membership_data,
			'connections'              => (array) PLCO_Helpers::get_connections(),
			'users_custom_meta_fields' => array(), //List of custom user meta fields line age country etc
			'license'                  => PLCO_License_Manager::get_license(),
			'routes'                   => array(
				'first_run'        => $this->get_route_url( 'first_run' ),
				'general_data'     => $this->get_route_url( 'general_data' ),
				'autocomplete'     => $this->get_route_url( 'autocomplete' ),
				'membership_level' => $this->get_route_url( 'membership_level' ),
				'recurrence'       => $this->get_route_url( 'recurrence' ),
				'connection'       => $this->get_route_url( 'connection' ),
				'protection'       => $this->get_route_url( 'protection' ),
				'roles'            => $this->get_route_url( 'roles' ),
				'email_templates'  => $this->get_route_url( 'email_templates' ),
				'members'          => $this->get_route_url( 'members' ),
				'payments'         => $this->get_route_url( 'payments' ),
			),
			'roles'                    => $roles,
			'capabilities'             => $capabilities,
			'recurrence_types'         => $this->const::RECURRENCE_TYPES,
			'currencies'               => $this->const::CURRENCIES,
			'available_connections'    => PLCO_Const::AVAILABLE_CONNECTION,
			'tabs'                     => PLCOM_Const::TABS,
			'protected_posts'          => $this->helper::get_all_protections(),
			'post'                     => $handle === "post.php",
			'is_pro'                   => $this->is_pro,
			'plugin_uri'               => $this->const::plugin_uri(),
			't'                        => require $this->const::plugin_path() . '/i18n.php',
		);

		$data = apply_filters( "plco_membership_localization_data", $data );

		return $data;
	}

	/**
	 *
	 */
	public function display() {
		$this->template( "membership.php" );
	}
}

