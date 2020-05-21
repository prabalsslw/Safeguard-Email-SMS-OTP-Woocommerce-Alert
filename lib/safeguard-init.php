<?php
    # Protect from alien invasion

    # Setup Database Table

    namespace Safeguard\Admin\Init;

    class Safeguard_Init
    {
        public static function safeg_install() {

            global $wpdb;
            global $safeg_db_version;

            $table_name = $wpdb->prefix . "safeguard_otp";
            $table_woo_name = $wpdb->prefix . "safeguard_wooalert";

            $charset_collate = '';

            if ( ! empty( $wpdb->charset ) ) {
              $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
            }

            if ( ! empty( $wpdb->collate ) ) {
                $charset_collate .= " COLLATE {$wpdb->collate}";
            }
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id mediumint(9) UNSIGNED NOT NULL,
                user_obj blob NOT NULL,
                login_time timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                auth_token varchar(32) NOT NULL,
                otp int(6) UNSIGNED,
                login_status int(1) UNSIGNED DEFAULT 0 NOT NULL,
                otp_destination varchar(55),
                user_ip varchar(45),
                sms_ref_id varchar(3000),
                otp_sent_limit int(3) DEFAULT '0',
                UNIQUE KEY id (id)
                ) $charset_collate;";
                dbDelta( $sql );
            }
            
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_woo_name'") != $table_woo_name) {
            $sql = "CREATE TABLE $table_woo_name (
                id mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id mediumint(9) UNSIGNED NOT NULL,
                user_name varchar(300) NULL,
                sending_time timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                user_ip varchar(45),
                sms_type varchar(100) NULL,
                phone_no varchar(20) NULL,
                sms_ref_id varchar(3000),
                UNIQUE KEY id (id)
                ) $charset_collate;";
                dbDelta( $sql );       
            }

            add_option( 'safeg_db_version', $safeg_db_version );

        }
    }