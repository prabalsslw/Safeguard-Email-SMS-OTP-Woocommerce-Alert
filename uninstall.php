<?php 
/*
 * Removes options from database when plugin is deleted.
 *  
 *
 */

# if uninstall not called from WordPress exit

	if (!defined('WP_UNINSTALL_PLUGIN' ))
	    exit();

	global $wpdb, $wp_version;

	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}safeguard_otp" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}safeguard_wooalert" );

	delete_option("safeg_db_version");
	delete_option('safeg_setting');

	wp_cache_flush();

?>