<?php
/**
 * Plugins Corner Membership - https://pluginscorner.com.com
 *
 * @package plco-membership
 */

namespace PLCOMembership\migrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

/**
 * Class PLCOM_Install_DB
 */
class PLCOM_Install_DB {
	/**
	 * global wpdb instance
	 *
	 * @var wpdb wpdb
	 */
	private $wpdb;

	/**
	 * @var string
	 */
	private $table_name;

	/**
	 * @var string
	 */
	private $plugin_prefix = 'plcom_';

	/**
	 * @var string
	 */
	private $prefix;

	/**
	 * CPT_PC_Install_DB constructor.
	 */
	public function __construct() {
		/**
		 * don't do squat if we don't have an upgrade set up
		 */
		if ( ! defined( 'PLCOM_DATABASE_UPGRADE' ) ) {
			return;
		}
		/**
		 * set the wpdb within the class
		 */
		global $wpdb;
		/** @var wpdb wpdb */
		$this->wpdb   = $wpdb;
		$this->prefix = $this->wpdb->prefix . $this->plugin_prefix;

		$this->create_tables();
	}

	/**
	 * Wrapper for all the tables needed on the install
	 */
	private function create_tables() {
		$this->create_membership_levels_table();
		$this->create_recurrences_table();
		$this->create_user_memberships_table();
		$this->create_payments_table();
		$this->create_plans_table();
		$this->create_cards_table();
		$this->create_logs_table();
	}

	/**
	 * Create the membership levels table
	 */
	private function create_membership_levels_table() {

		$this->table_name = $this->prefix . 'membership_levels';

		$sql = "CREATE TABLE  IF NOT EXISTS {$this->table_name} (
			ID INT( 11 ) NOT NULL AUTO_INCREMENT,
			membership_name VARCHAR( 255 ) NOT NULL,
			role VARCHAR( 255 ) NOT NULL DEFAULT 'subscriber',
			autorenew INT(11) DEFAULT '1',
    		status INT(11) DEFAULT '1',
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (ID)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";

		$this->wpdb->query( $sql );


		$sql = "INSERT IGNORE INTO {$this->table_name}
				SET `ID` = 1, `membership_name` = 'Non Member', `autorenew` = 1";

		$this->wpdb->query( $sql );
	}

	/**
	 * Create the membership levels table
	 */
	private function create_recurrences_table() {

		$this->table_name = $this->prefix . 'recurrences';

		$sql = "CREATE TABLE  IF NOT EXISTS {$this->table_name} (
			ID INT( 11 ) NOT NULL AUTO_INCREMENT,
			membership_id INT( 11 ) NOT NULL,
			recurrence INT(2) DEFAULT '0',
			recurrence_type INT(1) DEFAULT '0',
			cycles INT(11) DEFAULT '0',
			amount DOUBLE DEFAULT '0',
			currency INT(11) DEFAULT '0',
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (ID),
			FOREIGN KEY (membership_id) REFERENCES {$this->prefix}membership_levels(ID)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";

		$this->wpdb->query( $sql );

		$sql = "INSERT IGNORE INTO {$this->table_name}
				SET `ID` = 1, `membership_id` = 1, `recurrence` = 1, `recurrence_type` = 3, `currency` = 1";

		$this->wpdb->query( $sql );
	}


	/**
	 * Create the members table
	 */
	private function create_user_memberships_table() {
		$this->table_name = $this->prefix . 'user_memberships';
		$sql              = "CREATE TABLE  IF NOT EXISTS {$this->table_name} (
			ID INT( 11 ) NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			membership_id INT( 11 ) NOT NULL ,
			state VARCHAR( 255 ) NOT NULL,
			complementary INT(11) DEFAULT '0',
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			expires_at DATETIME,
			next_billing_date DATETIME,
			PRIMARY KEY (ID),
			FOREIGN KEY (user_id) REFERENCES {$this->wpdb->prefix}users(ID),
			FOREIGN KEY (membership_id) REFERENCES {$this->prefix}membership_levels(ID)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";

		$this->wpdb->query( $sql );
	}

	/**
	 * Create the payments table
	 */
	private function create_payments_table() {
		$this->table_name = $this->prefix . 'payments';
		$sql              = "CREATE TABLE  IF NOT EXISTS {$this->table_name} (
			ID INT( 11 ) NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			user_membership_id INT( 11 ) NOT NULL,
			membership_id INT( 11 ) NOT NULL ,
			recurrence_id INT( 11 ) NOT NULL ,
			subscription_id VARCHAR( 255 ) NOT NULL ,
			transaction_id VARCHAR( 255 ) NOT NULL ,
			payment_amount DOUBLE NOT NULL,
			status VARCHAR( 255 ) NOT NULL,
			payment_type_id INT( 11 ) NOT NULL,
			original_response TEXT( 255 ),
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (ID),
			FOREIGN KEY (user_id) REFERENCES {$this->wpdb->prefix}users(ID),
			FOREIGN KEY (payment_type_id) REFERENCES {$this->wpdb->prefix}plco_connections(ID),
			FOREIGN KEY (user_membership_id) REFERENCES {$this->prefix}user_memberships(ID),
			FOREIGN KEY (membership_id) REFERENCES {$this->prefix}membership_levels(ID),
			FOREIGN KEY (recurrence_id) REFERENCES {$this->prefix}recurrences(ID)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";

		$this->wpdb->query( $sql );
	}

	/**
	 * Create the payments table
	 */
	private function create_plans_table() {
		$this->table_name = $this->prefix . 'plans';
		$sql              = "CREATE TABLE  IF NOT EXISTS {$this->table_name} (
			ID INT( 11 ) NOT NULL AUTO_INCREMENT,
			recurrence_id INT( 11 ) NOT NULL,
			connection_id INT( 11 ) NOT NULL,
			plan_created  VARCHAR( 255 ) DEFAULT '0',
			plan_activated  VARCHAR( 255 ) DEFAULT '0',
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (ID),
			FOREIGN KEY (recurrence_id) REFERENCES {$this->prefix}recurrences(ID),
			FOREIGN KEY (connection_id) REFERENCES {$this->wpdb->prefix}plco_connections(ID)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";

		$this->wpdb->query( $sql );
	}


	/**
	 * Creates the logs table
	 */
	private function create_logs_table() {
		$this->table_name = $this->prefix . 'user_logs';
		$sql              = "CREATE TABLE  IF NOT EXISTS {$this->table_name} (
			ID INT( 11 ) NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			log_type INT( 1 ) NOT NULL ,
			message INT( 11 ) NOT NULL ,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (ID),
			FOREIGN KEY (user_id) REFERENCES {$this->wpdb->prefix}users(ID)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";

		$this->wpdb->query( $sql );
	}

	private function create_cards_table() {
		$this->table_name = $this->prefix . 'cards';

		$sql              = "CREATE TABLE  IF NOT EXISTS {$this->table_name} (
			ID INT( 11 ) NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			card_number INT( 4 ) NOT NULL ,
			full_name VARCHAR ( 256 ) NOT NULL ,
			customer_id VARCHAR ( 256 ) NOT NULL ,
			payment_method_id VARCHAR ( 256 ) NOT NULL ,
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (ID),
			FOREIGN KEY (user_id) REFERENCES {$this->wpdb->prefix}users(ID)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";

		$this->wpdb->query( $sql );
	}
}
