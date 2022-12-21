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
 * @package     WP_Error_Log_Viewer
 * @subpackage  Uninstall
 * @copyright   All rights reserved Copyright (c) 2022, Jaitras - support@jaitras.com
 * @author      jaitras.com
 * @since       1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

function wp_elv_uninstall(){
	
}

wp_elv_uninstall();