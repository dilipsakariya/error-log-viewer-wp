<?php
/**
 * Uninstall PS Plugin Template
 *
 * Deletes all the plugin data i.e.
 *         1. Plugin options.
 *         2. Integration.
 *         3. Database tables.
 *         4. Cron events.
 *
 * @package     Error_Log_Viewer_WP
 * @subpackage  Uninstall
 * @copyright   All rights reserved Copyright (c) 2022, Jaitras - support@wpguru.co
 * @author      wpguru.co
 * @since       1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! class_exists( 'Error_Log_Viewer_WP' ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'error-log-viewer-wp.php';
}

function elvwp_uninstall() {

	global $wpdb;

	delete_option( 'elvwp_error_log_details' );
	delete_option( 'elvwp_dismiss_review_notice' );
	delete_option( 'elvwp_review_time' );
	delete_option( 'elvwp_frequency' );
	delete_option( 'elvwp_notification_status' );
	delete_option( 'elvwp_notification_emails' );

	$table_name = $wpdb->prefix . 'elvwp_error_logs';
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %s', $table_name ) ); // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
}

elvwp_uninstall();
