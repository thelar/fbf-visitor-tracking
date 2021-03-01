<?php

/**
 * Fired during plugin activation
 *
 * @link       https://4x4tyres.co.uk
 * @since      1.0.0
 *
 * @package    Fbf_Visitor_Tracking
 * @subpackage Fbf_Visitor_Tracking/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Fbf_Visitor_Tracking
 * @subpackage Fbf_Visitor_Tracking/includes
 * @author     Kevin Price-Ward <kevin.price-ward@4x4tyres.co.uk>
 */
class Fbf_Visitor_Tracking_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
        self::db_install();
	}

    private static function db_install()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fbf_visitor_tracking';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
          id mediumint(9) NOT NULL AUTO_INCREMENT,
          timestamp datetime DEFAULT CURRENT_TIMESTAMP,
          session_cookie varchar(255),
          user_id int(10),
          order_id int(10),
          customer_phone varchar(100),
          customer_email varchar(255),
          action varchar(20) NOT NULL,
          data longtext,
          PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        add_option('fbf_visitor_tracking_db_version', FBF_VISITOR_TRACKING_DB_VERSION);
    }

}
