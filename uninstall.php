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

if ( ! class_exists( 'WP_Config_Transformer' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-config-transformer.php';
}

if ( ! class_exists( 'Error_Log_Viewer_WP' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'error-log-viewer-wp.php';
}

if ( ! function_exists( 'elvwp_remove_data_from_config' ) ) {
    function elvwp_remove_data_from_config() {

        $object                 = new Error_Log_Viewer_WP();
        $config_path            = $object->get_wp_config_path();
        $config_transformer     = new WP_Config_Transformer( $config_path );

        if ( $config_transformer->exists( 'constant', 'WP_DEBUG_LOG' ) ) {
            $config_transformer->remove( 'constant', 'WP_DEBUG_LOG' );
        }

        if ( $config_transformer->exists( 'inivariable', 'log_errors' ) ) {
            $config_transformer->remove( 'inivariable', 'log_errors' );
        }

        if ( $config_transformer->exists( 'inivariable', 'error_log' ) ) {
            $config_transformer->remove( 'inivariable', 'error_log' );
        }

    }
}

function elvwp_uninstall(){

    global $wpdb;

	delete_option( 'elvwp_error_log_details' );
    delete_option( 'elvwp_dismiss_review_notice' );
    delete_option( 'elvwp_review_time' );

    $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'elvwp_error_logs' ) );

    elvwp_remove_data_from_config();
}

elvwp_uninstall();