<?php
/**
 * Plugin Name:     WP Error Log Viewer
 * Plugin URI:      https://wordpress.org/plugins/wp-error-log-viewer/
 * Description:     WP Error Log Viewer plugin offers a user-friendly way to view and analyze PHP error logs. Easy to monitor distinct error log entries which helps to solve all errors quickly.
 * Version:         1.0.2
 * Author:          Jaitras
 * Author URI:      https://jaitras.com/
 * Text Domain:     wp_elv
 * Requires at least:   3.9
 * Tested up to:        6.1.1
 *
 * @package         EDD\WP_Error_Log_Viewer
 * @author          jaitras.com
 * @copyright       All rights reserved Copyright (c) 2022, jaitras.com
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ){
    exit;
}

if ( ! class_exists( 'WP_Error_Log_Viewer' ) ) {
    
    /**
     * Main WP_Error_Log_Viewer class
     *
     * @since       1.0.0
     */
    class WP_Error_Log_Viewer
    {
        
        /**
         * @var         WP_Error_Log_Viewer $instance The one true WP_Error_Log_Viewer
         * @since       1.0.0
         */
        private static $instance;
        private $message = '';
        private $messageError = FALSE;
        
        
        public function __construct()
        {
            
            $log_directory_folder   = WP_CONTENT_DIR . '/uploads/wp-error-log-viewer';
            $wp_elv_htaccess        = WP_CONTENT_DIR . '/uploads/wp-error-log-viewer/.htaccess';
            $wp_elv_index           = WP_CONTENT_DIR . '/uploads/wp-error-log-viewer/index.php';
            
            if ( ! is_dir( $log_directory_folder ) ) {
                mkdir( $log_directory_folder );
            }
            
            if ( ! file_exists( $wp_elv_index ) ) {
                $wp_elv_file_index = fopen( $wp_elv_index, 'w' ) or die( 'Unable to open file!' );
                $txt = "<?php // Exit if accessed directly.
                    if ( ! defined( 'ABSPATH' ) ) {
                    exit;
                    }
                    require_once( 'wp-load.php' );
                    is_user_logged_in() || auth_redirect();
                    if ( ! user_can( wp_get_current_user(), 'administrator' ) ) {
                    global $wp_query;
                    $wp_query->set_403();
                    status_header( 403 );
                    get_template_part( 403 );
                    wp_die( __( 'You are not allowed to access this file.', 'wp_elv' ) );
                    } else {
                    // insert here your awesome source code
                    // to serve the requested file
                    }";
                
                fwrite( $wp_elv_file_index, $txt );
                fclose( $wp_elv_file_index );
            }
            
            if ( ! file_exists( $wp_elv_htaccess ) ) {
                $wp_elv_htaccess = fopen( $wp_elv_htaccess, 'w' ) or die( 'Unable to open file!' );
                $txt = 'RewriteCond %{REQUEST_FILENAME} -s
                      RewriteRule ^(.*)$ /index.php?wp-error-log-viewer=$1 [QSA,L]';
                fwrite( $wp_elv_htaccess, $txt );
                fclose( $wp_elv_htaccess );
            }
            
        }
        
        
        /**
         * Get active instance
         *
         * @access      public
         * @since       1.0.0
         * @return      object self::$instance The one true WP_Error_Log_Viewer
         */
        public static function instance()
        {
            
            if ( ! self::$instance ) {
                self::$instance = new WP_Error_Log_Viewer();
                self::$instance->setup_constants();
                self::$instance->load_table();
                self::$instance->load_textdomain();
                self::$instance->hooks();
                self::$instance->wp_elv_error_log_details();
            }
            return self::$instance;
        }
        
        /**
         * Setup plugin constants
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function setup_constants()
        {
            
            // Plugin version
            define( 'WP_ERROR_LOG_VIEWER_VER', '1.0.2' );
            
            // Plugin name
            define( 'WP_ERROR_LOG_VIEWER_NAME', 'WP Error Log Viewer' );
            
            // Plugin path
            define( 'WP_ERROR_LOG_VIEWER_DIR', plugin_dir_path( __FILE__ ) );
            
            // Plugin URL
            define( 'WP_ERROR_LOG_VIEWER_URL', plugin_dir_url( __FILE__ ) );
            
            define( 'WP_ERROR_LOG_VIEWER_SUPPORT_URL', 'https://wordpress.org/support/plugin/wp-error-log-viewer/' );
            
            define( 'WP_ERROR_LOG_VIEWER_REVIEW_URL', 'https://wordpress.org/support/view/plugin-reviews/wp-error-log-viewer?filter=5' );
            
            define( 'WP_ERROR_LOG_VIEWER_DEBUG_LOGFOLDER', WP_CONTENT_DIR . '/uploads/wp-error-log-viewer' );

            if ( ! class_exists( 'WP_Config_Transformer' ) ) {
                require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-config-transformer.php';
            }
            
            $config_path            = ABSPATH . '\wp-config.php';
            $config_transformer     = new WP_Config_Transformer( $config_path );
                        
            if ( ( defined( WP_DEBUG_LOG ) && WP_DEBUG_LOG == false ) || !defined( WP_DEBUG_LOG ) ) {
                
                if ( $config_transformer->exists( 'constant', 'WP_DEBUG_LOG' ) ) {
                    $config_transformer->update( 'constant', 'WP_DEBUG_LOG', true );
                } else {
                    $config_transformer->add( 'constant', 'WP_DEBUG_LOG', true );
                }
            }

            $error_log  = WP_CONTENT_DIR . '/uploads/wp-error-log-viewer/log-' . date( 'd-M-Y' ) . '.log';

            if ( $config_transformer->exists( 'inivariable', 'log_errors' ) ) {
                $config_transformer->update( 'inivariable', 'log_errors', 'On' );
            } else {
                $config_transformer->add( 'inivariable', 'log_errors', 'On' );
            }

            if ( $config_transformer->exists( 'inivariable', 'error_log' ) ) {
                $config_transformer->update( 'inivariable', 'error_log', $error_log );
            } else {
                $config_transformer->add( 'inivariable', 'error_log', $error_log );
            }
        }
        
        /**
         * Load necessary files
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function load_table()
        {
            global $wpdb;
            $table = $wpdb->prefix . 'wp_error_logs';
            
            // create database table
            if ( $wpdb->get_var( $wpdb->prepare( "show tables like %s", $table ) ) != $table ) {
                $sql = 'CREATE TABLE ' . $table . ' (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `file_name` varchar(100) NOT NULL,
                  `details` text NOT NULL,
                  `log_path` text NOT NULL,
                  `created_at` date NOT NULL,
                   PRIMARY KEY (id)
                );';
                
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                dbDelta( $sql );
            }
            
            $version = get_option( 'wp_elv_error_log_details' ) ? get_option( 'wp_elv_error_log_details' ) : '1.0.0';
            
            if ( version_compare( $version, '1.0.1', '<' ) ) {
                
                $row = $wpdb->get_results( $wpdb->prepare( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE table_name = %s AND column_name = 'log_path'", $table ) );
                if ( empty( $row ) ) {
                    $wpdb->query( $wpdb->prepare( "ALTER TABLE {$table} ADD log_path text NOT NULL after details" ) );
                }
                
                update_option( 'wp_elv_error_log_details', WP_ERROR_LOG_VIEWER_VER );
            }
        }
        
        /**
         * Error Log Details
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         *
         */
        public function wp_elv_error_log_details( $log_date = '' )
        {
            global $wpdb;
                                    
            $count                = 1;
            $log_directory_folder = WP_CONTENT_DIR . '/uploads/wp-error-log-viewer';
            $wp_empty_folder       = ( count( glob( "$log_directory_folder/*" ) ) === 0 ) ? 'Empty' : 'Not empty';
            
            if ( 'Empty' === $wp_empty_folder ) {
                $table = $wpdb->prefix . 'wp_error_logs';
                
                if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", '%'.$table.'%' ) ) == $table ) {
                    $wpdb->query( $wpdb->prepare( "TRUNCATE TABLE $table" ) );
                }
            }
            
            if ( is_dir( $log_directory_folder ) ) {
                $scanned_directory = array_diff( scandir( $log_directory_folder, 1 ), array(
                        '..',
                        '.', 
                    ) );
                $table             = $wpdb->prefix . 'wp_error_logs';
                
                if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", '%'.$table.'%' ) ) == $table ) {
                    
                    foreach ( $scanned_directory as $key => $value ) {
                        $count         = 1;
                        $file_name     = $value;
                        $table         = $wpdb->prefix . 'wp_error_logs';
                        $wp_elv_table_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table}" ) );
                        
                        foreach ( $wp_elv_table_data as $wp_elv_tablekey => $wp_elv_tablevalue ) {
                            $wp_elv_database_file = $wp_elv_tablevalue->file_name;
                            
                            if ( $wp_elv_database_file == $file_name ) {
                                $count = 0;
                            } else {
                                $wp_elv_checkfile = WP_CONTENT_DIR . '/uploads/wp-error-log-viewer/' . $wp_elv_database_file;
                            }
                        }
                        
                        if ( 1 === $count ) {
                            $current_date       = date( 'Y-m-d', strtotime( substr( $file_name, 4, 11 ) ) );
                            $wp_elv_coneverted_date = date( 'd-M-Y', strtotime( $current_date ) );
                            $log_details        = $this->wp_elv_log_details( $wp_elv_coneverted_date );
                            
                            if ( $log_details ) {
                              $wp_elv_serialize_data  = serialize( $log_details['typecount'] );
                              $log_path           = $log_details['error_log'];
                              
                              if ( '.htaccess' !== $file_name && 'index.php' !== $file_name ) {
                                  $data   = array(
                                      'file_name'   => $file_name,
                                      'details'     => $wp_elv_serialize_data,
                                      'created_at'  => $current_date,
                                      'log_path'    => $log_path,
                                  );
                                  $format = array(
                                      '%s',
                                      '%s',
                                      '%s',
                                      '%s', 
                                  );
                                  $wpdb->insert( $table, $data, $format );
                              }
                            }
                        }
                        
                        if ( 0 === $count ) {
                            $current_date       = date( 'Y-m-d', strtotime( substr( $file_name, 4, 11 ) ) );
                            $wp_elv_coneverted_date = date( 'd-M-Y', strtotime( $current_date ) );
                            $log_details        = $this->wp_elv_log_details( $wp_elv_coneverted_date );
                            $wp_elv_serialize_data  = serialize( $log_details['typecount'] );
                            $log_path           = $log_details['error_log'];
                            
                            if ( '.htaccess' !== $file_name && 'index.php' !== $file_name ) {
                                $data          = array(
                                    'details'       => $wp_elv_serialize_data,
                                    'created_at'    => $current_date,
                                    'log_path'      => $log_path,
                                );
                                $format        = array(
                                    '%s',
                                    '%s',
                                    '%s',
                                );
                                $wherefilename = array(
                                    'file_name' => $file_name,
                                );

                                $wpdb->update( $table, $data, $wherefilename, $format );
                            }
                        }
                    }
                }
            }
            
            $table = $wpdb->prefix . 'wp_error_logs';
            
            if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", '%'.$table.'%' ) ) == $table ) {
                $wp_elv_table_data = $wpdb->get_results( $wpdb->prepare( "SELECT file_name FROM {$table}" ) );
                
                foreach ( $wp_elv_table_data as $key => $value ) {
                    $wp_elv_database_file = $value->file_name;
                    $wp_elv_checkfile     = WP_CONTENT_DIR . '/uploads/wp-error-log-viewer/' . $wp_elv_database_file;
                    
                    if ( ! file_exists( $wp_elv_checkfile ) ) {
                        $wpdb->delete( $table, array(
                            'file_name' => $wp_elv_database_file, 
                        ) );
                    }
                }
            }
        }
        
        /**
         * Run action and filter hooks
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         *
         */
        private function hooks()
        {
            add_action( 'admin_enqueue_scripts', array(
                $this,
                'wp_elv_admin_dash_enqueue', 
            ) );
            // AJAX action hook to disable the 'review request' notice.
            add_action( 'wp_ajax_wp_elv_error_log_review_notice', array(
                $this,
                'wp_elv_dismiss_review_notice', 
            ) );
            
            if ( ! get_option( 'wp_elv_error_log_review_time' ) ) {
                $review_time = time() + 7 * DAY_IN_SECONDS;
                add_option( 'wp_elv_error_log_review_time', $review_time, '', false );
            }
            
            if ( is_admin() && get_option( 'wp_elv_error_log_review_time' ) && get_option( 'wp_elv_error_log_review_time' ) < time() && !get_option( 'wp_elv_dismiss_review_notice' ) ) {
                add_action( 'admin_notices', array(
                    $this,
                    'wp_elv_notice_review', 
                ) );
                add_action( 'admin_footer', array(
                    $this,
                    'wp_elv_notice_review_script', 
                ) );
            }
            
            add_action( 'plugin_row_meta', array(
                $this,
                'wp_elv_add_action_links', 
            ), 10, 2 );
            add_action( 'admin_footer', array(
                $this,
                'wp_elv_add_deactive_modal', 
            ) );
            add_action( 'wp_ajax_wp_elv_error_log_deactivation', array(
                $this,
                'wp_elv_error_log_deactivation', 
            ) );
            add_action( 'plugin_action_links', array(
                $this,
                'wp_elv_error_log_action_links', 
            ), 10, 2 );
            
            if ( is_admin() ) {
                
                add_action( 'admin_menu', array(
                    $this,
                    'wp_elv_plugin_menu', 
                ) );
                // add_action( 'wp_dashboard_setup', array($this,'error_widget_new' ));
                add_action( 'init', array(
                    $this,
                    'wp_elv_log_download', 
                ) );
                add_action( 'wp_ajax_nopriv_wp_elv_log_download', array(
                    $this,
                    'wp_elv_log_download', 
                ) );
                add_action( 'wp_before_admin_bar_render', array(
                    $this,
                    'wp_elv_register_admin_bar', 
                ) );
                add_action( 'wp_ajax_wp_elv_purge_log', array(
                    $this,
                    'wp_elv_purge_log', 
                ) );
                add_action( 'wp_ajax_wp_elv_datatable_loglist', array(
                    $this,
                    'wp_elv_datatable_loglist', 
                ) );
                add_action( 'wp_ajax_wp_elv_datatable_delete_data', array(
                    $this,
                    'wp_elv_datatable_delete_data', 
                ) );
            }
            
            
        }
        
        /**
         * To Download Log
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         *
         */
        public function wp_elv_log_download()
        {
            
            if ( is_admin() && isset( $_POST['wp_elv_error_log_download'] ) && isset( $_POST['wp_elv_error_log'] ) && !empty( $_POST['wp_elv_error_log'] ) ) {
                
                $wp_elv_file = sanitize_text_field( $_POST['wp_elv_error_log'] );
                
                try {
                    $filename = basename( $wp_elv_file );
                    header( 'Pragma: public' );
                    header( 'Expires: 0' );
                    header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
                    header( 'Content-Type: application/octet-stream' );
                    header( 'Content-Disposition: attachment; filename=$filename' );
                    header( 'Content-Transfer-Encoding: binary' );
                    header( 'Content-Length: ' . filesize( $wp_elv_file ) );
                    flush();
                    readfile( $wp_elv_file );
                    exit;
                }
                catch ( Exception $e ) {
                    $data['error'] = $e->getMessage() . ' @ ' . $e->getFile() . ' - ' . $e->getLine();
                }
            }
            
            if ( is_admin() && isset( $_POST['wp_elv_datatable_log_download'] ) && isset( $_POST['wp_elv_datatable_downloadid'] ) && !empty( $_POST['wp_elv_datatable_downloadid'] ) ) {

                global $wpdb;
                $table                          = $wpdb->prefix . 'wp_error_logs';
                $wp_elv_datatable_downloadid    = sanitize_text_field( $_POST['wp_elv_datatable_downloadid'] );

                $wp_elv_download_table_data     = $wpdb->get_col( $wpdb->prepare( "SELECT file_name from {$table} where id=%d", $wp_elv_datatable_downloadid ) );
                
                foreach ( $wp_elv_download_table_data as $key => $value ) {
                    $ps_download_filename = $value;
                }
                
                $wp_elv_download_filepath = WP_CONTENT_DIR . '/uploads/wp-error-log-viewer/' . $ps_download_filename;
                
                try {
                    $filename = basename( $wp_elv_download_filepath );
                    header( 'Pragma: public' );
                    header( 'Expires: 0' );
                    header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
                    header( 'Content-Type: application/octet-stream' );
                    header( 'Content-Disposition: attachment; filename=$filename' );
                    header( 'Content-Transfer-Encoding: binary' );
                    header( 'Content-Length: ' . filesize( $wp_elv_download_filepath ) );
                    flush();
                    readfile( $wp_elv_download_filepath );
                    exit;
                }
                catch ( Exception $e ) {
                    $data['error'] = $e->getMessage() . ' @ ' . $e->getFile() . ' - ' . $e->getLine();
                }
            }
        }
        /**
         * To delete Log
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         *
         */
        public function wp_elv_purge_log()
        {
            if ( ! isset( $_POST['wp_elv_nonce'] ) ) {
                echo json_encode( array(
                    'success'   => '0',
                    'msg'       => __( 'Security Error.', 'wp_elv' ), 
                ) );

                wp_die();
            }

            if ( ! wp_verify_nonce( $_POST['wp_elv_nonce'], 'wp_elv_purge_log_nonce' ) ) {
                echo json_encode( array(
                    'success'   => '0',
                    'msg'       => __( 'Security Error.', 'wp_elv' ), 
                ) );

                wp_die();
            }

            if ( is_admin() && isset( $_POST['wp_elv_error_log'] ) && !empty( $_POST['wp_elv_error_log'] ) ) {
                $wp_elv_error_log = sanitize_text_field( $_POST['wp_elv_error_log'] );
                
                if ( file_exists( $wp_elv_error_log ) ) {
                    
                    unlink( $wp_elv_error_log );
                    
                    echo json_encode( array(
                        'success'   => '1',
                        'msg'       => __( 'Log file deleted successfully.', 'wp_elv' ),
                    ) );

                    wp_die();
                } else {
                    echo json_encode( array(
                        'success'   => '0',
                        'msg'       => __( 'Log file deleted failed. Please try again after reloading page.', 'wp_elv' ),
                    ) );

                    wp_die();
                }
            }
        }

        /**
         * To Register admin status bar in dashboard
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         *
         */
        public function wp_elv_register_admin_bar()
        {
            
            if ( isset( $_POST['date'] ) && !empty( $_POST['date'] ) ) {
                $log_date = date( 'd-M-Y', strtotime( sanitize_text_field( $_POST['date'] ) ) );
            } else {
                $log_date = date( 'd-M-Y' );
            }

            $log_details = $this->wp_elv_log_details( $log_date );
            $total = 0;

            if ( $log_details ) {
                $count       = 1;
                $error_log   = $log_details['error_log'];
                $total       = $log_details['total'];
                
                if ( file_exists( $error_log ) ) {
                    $wp_elv_serialize_data = serialize( $log_details['typecount'] );
                    $wp_elv_primary_alert  = '<span class="wp_elv-admin-bar-error-count"><strong>' .$total.' </strong></span>';
                }
            }
            
            // Only site admins can see the admin bar entry.
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            // Primary link text.
            $link_text = esc_html__( 'WP Error Log Viewer', 'wp_elv' );
            
            if ( empty( $total ) ) {
                $wp_elv_primary_alert = '<span class="wp_elv-admin-bar-error-count"><strong>0</strong></span>';
            }
            
            // Bring the admin bar into scope.
            global $wp_admin_bar;
            
            // Add the link.
            $wp_admin_bar->add_menu( array(
                'parent'    => false,
                'id'        => 'wp_elv_admin_bar',
                'title'     => $link_text . $wp_elv_primary_alert,
                'href'      => admin_url( 'admin.php?page=wp-error-log-viewer' ),
                'meta'      => array(
                                    'title' => __( 'WP Error Log Viewer', 'wp_elv' ),
                               ) 
            ) );
        }
        
        /**
         * Log details By Date
         *
         * @access      public
         * @since       1.0.0
         * @return      array
         *
         */
        public function wp_elv_log_details( $log_date = '', $is_raw_log = false )
        {
            $error_log = WP_CONTENT_DIR . '/uploads/wp-error-log-viewer/log-' . $log_date . '.log';
            /**
             * @var string|null Path to log cache - must be writable - null for no cache
             */
            $cache     = null;
            /**
             * @var array Array of log lines
             */
            $logs      = array();
            /**
             * @var array Array of log types
             */
            $types     = array();
            
            /**
             * https://gist.github.com/amnuts/8633684
             */
            
            $total = 0;
            
            if ( $error_log === null ) {
                $error_log = ini_get( 'error_log' );
            }
            
            if ( file_exists( $error_log ) ) {
                
                $log_details = array();

                try {
                    $log = new SplFileObject( $error_log );
                    $log->setFlags( SplFileObject::DROP_NEW_LINE );
                }
                catch ( RuntimeException $e ) {
                }
                
                if ( $cache !== null && file_exists( $cache ) ) {
                    $cache_data = unserialize( file_get_contents( $cache ) );
                    extract( $cache_data );
                    $log->fseek( $seek );
                }
                $prev_error = new stdClass;
               
                while ( ! $log->eof() ) {
                    
                    if ( preg_match( '/stack trace:$/i', $log->current() ) ) {
                        $stack_trace = $parts = array();
                        $log->next();
                        
                        while ( ( preg_match( '!^\[(?P<time>[^\]]*)\] PHP\s+(?P<msg>\d+\. .*)$!', $log->current(), $parts ) || preg_match( '!^(?P<msg>#\d+ .*)$!', $log->current(), $parts ) && !$log->eof() ) ) {
                            array_push( $stack_trace, $parts['msg'] );
                            $log->next();
                        }
                        
                        if ( substr( $stack_trace[ 0 ], 0, 2 ) == '#0' ) {
                            $stack_trace_str = $log->current();
                            array_push( $stack_trace, $stack_trace_str );
                            $log->next();
                        }
                        $prev_error->trace = join( "\n", $stack_trace );
                    }
                    $more = array();
                    
                    while ( ! preg_match( '!^\[(?P<time>[^\]]*)\] ((PHP|ojs2: )(?P<typea>.*?):|(?P<typeb>(WordPress|ojs2|\w has produced)\s{1,}\w+ \w+))\s+(?P<msg>.*)$!', $log->current() ) && !$log->eof() ) {
                        $more_str = $log->current();
                        array_push( $more, $more_str );
                        $log->next();
                    }
                    
                    if ( ! empty( $more ) ) {
                        $prev_error->more = join( '\n', $more );
                    }

                    $parts = array();
                    
                    if ( preg_match( '!^\[(?P<time>[^\]]*)\] ((PHP|ojs2: )(?P<typea>.*?):|(?P<typeb>(WordPress|ojs2|\w has produced)\s{1,}\w+ \w+))\s+(?P<msg>.*)$!', $log->current(), $parts ) ) {
                        $parts['type'] = ( @$parts['typea'] ?: $parts['typeb'] );
                        
                        if ( $parts[ 3 ] == 'ojs2: ' || $parts[ 6 ] == 'ojs2' ) {
                            $parts['type'] = 'ojs2 application';
                        }

                        $msg            = trim( $parts['msg'] );
                        $type           = strtolower( trim( $parts['type'] ) );
                        $types[ $type ] = strtolower( preg_replace( '/[^a-z]/i', '', $type ) );
                        
                        if ( ! isset( $logs[ $msg ] ) ) {
                            $data     = array(
                                'type'  => $type,
                                'first' => date_timestamp_get( date_create( $parts['time'] ) ),
                                'last'  => date_timestamp_get( date_create( $parts['time'] ) ),
                                'msg'   => $msg,
                                'hits'  => 1,
                                'trace' => null,
                                'more'  => null,
                            );
                            $subparts = array();
                            
                            if ( preg_match( '!(?<core> in (?P<path>(/|zend)[^ :]*)(?: on line |:)(?P<line>\d+))$!', $msg, $subparts ) ) {
                                $data['path'] = $subparts['path'];
                                $data['line'] = $subparts['line'];
                                $data['core'] = str_replace( $subparts['core'], '', $data['msg'] );
                                $data['code'] = '';
                                
                                try {
                                    $file = new SplFileObject( str_replace( 'zend.view://', '', $subparts['path'] ) );
                                    $file->seek( $subparts['line'] - 4 );
                                    $i = 7;
                                    
                                    do {
                                        $data['code'] .= $file->current();
                                        $file->next();
                                    } while ( --$i && !$file->eof() );
                                }
                                catch ( Exception $e ) {
                                }
                            }
                            $err_type_folder = 'other';
                            if ( strpos( $msg, '\wp-content\plugins' ) !== false ) {
                                $err_type           = 'plugin';
                                $msg_arr            = explode( '\wp-content\plugins\\', $msg );

                                if ( isset( $msg_arr[1] ) ) {
                                    $folders_arr    = explode( '\\', ltrim( $msg_arr[1], '\\' ) );
                                    $err_type_folder =  isset( $folders_arr[0] ) ? $folders_arr[0] : '' ;
                                }
                            } elseif ( strpos( $msg, '\wp-content\themes' ) !== false ) {
                                $err_type           = 'theme';
                                $msg_arr            = explode( '\wp-content\themes\\', $msg );

                                if ( isset( $msg_arr[1] ) ) {
                                    $folders_arr        = explode( '\\', ltrim( $msg_arr[1], '\\' ) );
                                    $err_type_folder    =  isset( $folders_arr[0] ) ? $folders_arr[0] : '' ;
                                }
                            } else {
                                $err_type = 'other';
                                $base_path_er =  trim( ABSPATH, '/' );
                                $msg_arr  = explode( $base_path_er, $msg );
                                
                                if ( isset( $msg_arr[1] ) ) {
                                    $folders_arr        = explode( '\\', ltrim( $msg_arr[1], '\\' ) );
                                    $err_type_folder    =  isset( $folders_arr[0] ) ? $folders_arr[0] : '' ;
                                }
                            }
                            $logs[ $msg ] = (object) $data;

                            
                            if ( ! isset( $typecount[ $err_type ][ $err_type_folder ][ $type ] ) ) {
                                $typecount[ $err_type ][ $err_type_folder ][ $type ] = 1;
                            } else {
                                ++$typecount[ $err_type ][ $err_type_folder ][ $type ];
                            }

                            if ( ! isset( $typecount[ $type ] ) ) {
                                $typecount[ $type ] = 1;
                            } else {
                                ++$typecount[ $type ];
                            }
                        } else {
                            ++$logs[ $msg ]->hits;
                            $time = date_timestamp_get( date_create( $parts['time'] ) );
                            
                            if ( $time < $logs[ $msg ]->first ) {
                                $logs[ $msg ]->first = $time;
                            }
                            
                            if ( $time > $logs[ $msg ]->last ) {
                                $logs[ $msg ]->last = $time;
                            }
                        }
                        $prev_error =& $logs[ $msg ];
                    }
                    $log->next();
                }
                
                if ( $cache !== null ) {
                    $cache_data = serialize( array(
                        'seek'      => $log->getSize(),
                        'logs'      => $logs,
                        'types'     => $types,
                        'typecount' => $typecount, 
                    ) );
                    
                    file_put_contents( $cache, $cache_data );
                }
                
                $log = null;
                $this->wp_elv_error_log_osort( $logs, array(
                    'last' => SORT_DESC 
                ) );
                $total = count( $logs );
                ksort( $types );
                $log_details['typecount'] = $typecount;
                $log_details['error_log'] = $error_log;
                $log_details['total']     = $total;
                $log_details['logs']      = $logs;
                $log_details['types']     = $types;
                
                if ( $is_raw_log ) {
                    // Return raw log
                    $file                   = file( $error_log );
                    $log_details['file']    = $file;
                }
                
                return $log_details;
                
            }
        }
        /**
         * Ps Error Log plugin menu
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         *
         */
        public function wp_elv_plugin_menu()
        {
            
            $menu = add_menu_page( __( 'WP Error Log Viewer', 'wp_elv' ), __( 'WP Error Log Viewer', 'wp_elv' ), 'manage_options', 'wp-error-log-viewer', array(
                $this,
                'wp_elv_error_by_date', 
            ) );

            add_submenu_page( 'wp_elv', __( 'WP Error Log Overview', 'wp_elv' ), __( 'Error Log Overview', 'wp_elv' ), 'manage_options', 'wp-error-log-viewer', array(
                $this,
                'wp_elv_error_by_date', 
            ) );

            $list = add_submenu_page( 'wp-error-log-viewer', __( 'WP Error Log List', 'wp_elv' ), __( 'Error Log List', 'wp_elv' ), 'manage_options', 'wp_elv-list', array(
                $this,
                'wp_elv_log_list_datatable', 
            ) );
            
            
            add_action( 'load-' . $menu, array(
                $this,
                'wp_elv_load_admin_scripts', 
            ) );

            add_action( 'load-' . $list, array(
                $this,
                'wp_elv_load_admin_scripts', 
            ) );
        }
        
        public function wp_elv_log_list_datatable()
        {
            require_once( WP_ERROR_LOG_VIEWER_DIR . 'includes/ps-log-list-template.php' );
        }
        
        public function wp_elv_error_by_date()
        {
            require_once( WP_ERROR_LOG_VIEWER_DIR . 'includes/error-log-viewer.php' );
        }
        
        // This function is only called when plugin's page loads!
        public function wp_elv_load_admin_scripts()
        {
            // Unfortunately can't just enqueue scripts here - it's too early. So register against the proper action hook to do it
            add_action( 'admin_enqueue_scripts', array(
                 $this,
                'admin_enqueue',
            ) );
        }
        
        /**
         * Admin Enqueue style and scripts
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         *
         */
        public function wp_elv_admin_dash_enqueue()
        {
            wp_enqueue_style( 'wp_elv_error_log_admin_style', plugins_url( '/assets/css/admin.css', __FILE__ ) );
            wp_enqueue_style( 'dashicons' );
            global $pagenow;
            
            if ( 'plugins.php' === $pagenow ) {
                $this->admin_enqueue();
            }
        }
        
        /**
         * Admin Enqueue style and scripts
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         *
         */
        public function admin_enqueue()
        {
            
            wp_enqueue_script( 'wp_elv_admin_ui_script', plugins_url( '/assets/js/datepicker.min.js', __FILE__ ), array(
                 'jquery' 
            ), time(), true );
            wp_enqueue_script( 'wp_elv_admin_script', plugins_url( '/assets/js/admin.js', __FILE__ ), array(
                 'wp_elv_admin_ui_script' 
            ), time(), true );
            wp_localize_script( 'wp_elv_admin_script', 'ajax_script_object', array(
                 'ajax_url'                 => admin_url( 'admin-ajax.php' ), 
                 'date_format'              => wp_elv_date_formate(), 
                 'date_format_php'          => get_option( 'date_format' ), 
                 'months'                   => array( '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April', '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August', '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December' ), 
                 'delete_data_nonce'        => wp_create_nonce( 'wp_elv_delete_data_nonce' ),
                 'purge_log_nonce'          => wp_create_nonce( 'wp_elv_purge_log_nonce' ),
            ) );
            wp_localize_script( 'wp_elv_admin_script', 'datatable', array(
                 'datatable_ajax_url' => admin_url( 'admin-ajax.php?action=wp_elv_datatable_loglist' ) 
            ) );
            wp_localize_script( 'wp_elv_admin_script', 'script_object', array() );
            
            wp_enqueue_script( 'wp_elv_datatable', plugins_url( '/assets/js/jquery.dataTables.min.js', __FILE__ ), array(
                 'jquery' 
            ), time(), true );
            wp_register_style( 'wp_elv_datatables_style', plugins_url( '/assets/css/jquery.dataTables.min.css', __FILE__ ) );
            wp_register_style( 'wp_elv_ui_style', 'https://cdnjs.cloudflare.com/ajax/libs/datepicker/1.0.10/datepicker.min.css' );
            wp_enqueue_style( 'wp_elv_datatables_style' );
            wp_enqueue_style( 'wp_elv_ui_style' );
        }
        
        public function wp_elv_datatable_loglist()
        {
            global $wpdb;
            $table                  = $wpdb->prefix . 'wp_error_logs';
            $column_sort_order      = sanitize_text_field( $_POST['order'][ 0 ]['dir'] );
            $draw                   = sanitize_text_field( $_POST['draw'] );
            $row                    = sanitize_text_field( $_POST['start'] );
            $row_per_page           = sanitize_text_field( $_POST['length'] ); // Rows display per page
            $column_index           = sanitize_text_field( $_POST['order'][ 0 ]['column'] ); // Column index
            $columnName             = sanitize_text_field( $_POST['columns'][ $column_index ]['data'] );
            $wp_elv_table_data      = $wpdb->get_results( $wpdb->prepare( "SELECT * from {$table} ORDER BY created_at {$column_sort_order} LIMIT %d,%d", $row, $row_per_page ) );
            $data                   = array();

            foreach ( $wp_elv_table_data as $key => $value ) {
                $created_at         = $value->created_at;
                $wp_elv_log_path    = $value->log_path;
                $id                 = $value->id;
                $filename           = WP_CONTENT_DIR . '/uploads/wp-error-log-viewer/' . $value->file_name;
                $wp_elv_url         = add_query_arg( 'date', $created_at, admin_url( 'admin.php?page=wp-error-log-viewer' ) );
                $array_rewrite      = array();
                // Array with the md5 hashes
                $array              = array();

                $array_hashes_main   = unserialize( $value->details );

                $folder_wise        = array( 'plugin', 'theme', 'other' );

                foreach ( $folder_wise as $ftype) {
                    // code...
                    $array_hashes = isset( $array_hashes_main[ $ftype ] ) ? $array_hashes_main[ $ftype ] : array() ;

                    if ( $array_hashes ) {

                        $wp_elv_output[ $ftype ]    = implode( '', array_map( function( $v, $k ) use ($created_at)
                        {
                            
                            if ( is_array( $v ) ) {
                                return '<b>'.$k.'</b><br>'.implode( '', array_map( function( $v1, $k1 ) use ($created_at)
                                        {
                                            
                                            if ( is_array( $v1 ) ) {
                                                return '<div class="wp_elv_datatable ' . $k1 . '">' . $k1 . "[]: " . implode( '&' . $k1 . "[]: ", $v1 ) . '</div>';
                                            } else {
                                                
                                                $wp_elv_date_url_array = array(
                                                    'date' => $created_at,
                                                    'type' => $k1, 
                                                );
                                                $wp_elv_error_type_url   = add_query_arg( $wp_elv_date_url_array, admin_url( 'admin.php?page=wp-error-log-viewer' ) );
                                                return '<div class="wp_elv_datatable ' . $k1 . '"><a href="' . $wp_elv_error_type_url . '">' . ucwords( $k1 . ": " . $v1 ) . '</a></div>';
                                            }
                                        }, $v, array_keys( $v ) ) );
                            } else {
                                
                                $wp_elv_date_url_array = array(
                                    'date' => $created_at,
                                    'type' => $k, 
                                );
                                $wp_elv_error_type_url   = add_query_arg( $wp_elv_date_url_array, admin_url( 'admin.php?page=wp-error-log-viewer' ) );
                                return '<div class="wp_elv_datatable ' . $k . '"><a href="' . $wp_elv_error_type_url . '">' . ucwords( $k . ": " . $v ) . '</a></div>';
                            }
                        }, $array_hashes, array_keys( $array_hashes ) ) );
                    } else {
                        $wp_elv_output[ $ftype ]    = '';
                    }
                }

                $button       = '<div class="wp_elv_datatable_ajaxbutton"><form method="post"><button type="button" onclick="location.href = \'' . $wp_elv_url . '\';" id="wp_elv_datatable_view" ><i class="dashicons dashicons-text-page view"></i></button><button class="wp_elv_datatable_delete" id="' . $id . '"><i class="dashicons dashicons-trash"></i></button><input type="hidden" name="wp_elv_datatable_downloadid" value="' . $id . '"><button type="submit" name="wp_elv_datatable_log_download" class="wp_elv_datatable_log_download"><i class="dashicons dashicons-download"></i></button></form></div>';
                $data_ar       = array(
                    'created_at'        => $created_at,
                    'plugin'            => $wp_elv_output['plugin'],
                    'theme'             => $wp_elv_output['theme'],
                    'others'            => $wp_elv_output['other'],
                    'wp_elv_log_path'   => $wp_elv_log_path,
                    'action'            => $button, 
                );
                array_push( $data, $data_ar );

                $total_record = $wpdb->get_results( $wpdb->prepare( "SELECT count(file_name) as filecount from {$table}" ) );
                
                foreach ( $total_record as $key => $value ) {
                    $records_total = $value->filecount;
                }

                $json_data = array(
                    'draw'                  => intval( $draw ),
                    'iTotalRecords'         => $records_total,
                    'iTotalDisplayRecords'  => $records_total,
                    'data'                  => $data,
                );
            }
            
            if ( empty( $json_data ) ) {
                $data      = array(
                    'created_at'    => 'No log',
                    'plugin'        => 'No log',
                    'theme'         => 'No log',
                    'others'        => 'No log',
                    'action'        => 'No log',
                );

                $json_data = array(
                     'data' => $data 
                );
            }
            echo json_encode( $json_data );
            wp_die();
        }
        
        public function wp_elv_datatable_delete_data()
        {
            if ( ! isset( $_POST['wp_elv_nonce'] ) ) {
                echo json_encode( array(
                    'success'   => '0',
                    'msg'       => __( 'Security Error.', 'wp_elv' ), 
                ) );

                wp_die();
            }

            if ( ! wp_verify_nonce( $_POST['wp_elv_nonce'], 'wp_elv_delete_data_nonce' ) ) {
                echo json_encode( array(
                    'success'   => '0',
                    'msg'       => __( 'Security Error.', 'wp_elv' ), 
                ) );

                wp_die();
            }

            if ( is_admin() && isset( $_POST['wp_elv_datatable_deleteid'] ) && !empty( $_POST['wp_elv_datatable_deleteid'] ) ) {
                
                global $wpdb;
                $table                     = $wpdb->prefix . 'wp_error_logs';
                $wp_elv_datatable_deleteid = sanitize_text_field( $_POST['wp_elv_datatable_deleteid'] );
                
                $wp_elv_table_data         = $wpdb->get_col( $wpdb->prepare( "SELECT file_name from $table where id=%d", $wp_elv_datatable_deleteid ) );
                
                if ( ! empty( $wp_elv_table_data ) ) {
                    
                    foreach ( $wp_elv_table_data as $value ) {
                        $wp_elv_datatable_filename = WP_CONTENT_DIR . '/uploads/wp-error-log-viewer/' . $value;
                    }
                    
                    if ( file_exists( $wp_elv_datatable_filename ) ) {
                        $wp_elv_datatable_basename = basename( $value );
                        $wp_elv_delete_data        = $wpdb->delete( $table, array(
                            'file_name' => $wp_elv_datatable_basename 
                        ) );
                        
                        if ( $wp_elv_delete_data ) {
                            
                            unlink( $wp_elv_datatable_filename );
                            
                            echo json_encode( array(
                                'success'   => '1',
                                'msg'       => __( 'Log file deleted successfully.', 'wp_elv' ),
                            ) );

                            wp_die();
                        } else {
                            echo json_encode( array(
                                'success'   => '0',
                                'msg'       => __( 'Log file deleted Failed.', 'wp_elv' ), 
                            ) );

                            wp_die();
                        }
                    } else {
                        echo json_encode( array(
                            'success'   => '0',
                            'msg'       => __( 'Log file deleted Failed.', 'wp_elv' ),
                        ) );

                        wp_die();
                    }
                }
            }
        }
        
        public function wp_elv_error_log_osort( &$array, $properties )
        {
            if ( is_string( $properties ) ) {
                $properties = array(
                     $properties => SORT_ASC, 
                );
            }
            uasort( $array, function( $a, $b ) use ( $properties )
            {
                foreach ( $properties as $k => $v ) {
                    
                    if ( is_int( $k ) ) {
                        $k = $v;
                        $v = SORT_ASC;
                    }
                    $collapse = function( $node, $props )
                    {
                        
                        if ( is_array( $props ) ) {
                            foreach ( $props as $prop ) {
                                $node = ( ! isset( $node->$prop ) ) ? null : $node->$prop;
                            }
                            return $node;
                        } else {
                            return ( ! isset( $node->$props ) ) ? null : $node->$props;
                        }
                    };
                    $aProp    = $collapse( $a, $k );
                    $bProp    = $collapse( $b, $k );
                    
                    if ( $aProp != $bProp ) {
                        return ( $v == SORT_ASC ) ? strnatcasecmp( $aProp, $bProp ) : strnatcasecmp( $bProp, $aProp );
                    }
                }
                return 0;
            } );
        }
        
        /**
         * Internationalization
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public function load_textdomain()
        {
            // Set filter for language directory
            $lang_dir = WP_ERROR_LOG_VIEWER_DIR . '/languages/';
            $lang_dir = apply_filters( 'wp_error_log_viewer_languages_directory', $lang_dir );
            
            // Traditional WordPress plugin locale filter
            $locale = apply_filters( 'plugin_locale', get_locale(), 'wp_elv' );
            $mofile = sprintf( '%1$s-%2$s.mo', 'wp_elv', $locale );
            
            // Setup paths to current locale file
            $mofile_local  = $lang_dir . $mofile;
            $mofile_global = WP_LANG_DIR . '/wp_elv/' . $mofile;
            
            if ( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/wp_elv/ folder
                load_textdomain( 'wp_elv', $mofile_global );
            } elseif ( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/wp_elv/languages/ folder
                load_textdomain( 'wp_elv', $mofile_local );
            } else {
                // Load the default language files
                load_plugin_textdomain( 'wp_elv', false, $lang_dir );
            }
        }
        
        /**
         * Add deactivate modal layout.
         */
        public function wp_elv_add_deactive_modal()
        {
            global $pagenow;
            
            if ( 'plugins.php' !== $pagenow ) {
                return;
            }
            include WP_ERROR_LOG_VIEWER_DIR . 'includes/deactivation-form.php';
        }
        
        /**
         * Called after the user has submitted his reason for deactivating the plugin.
         *
         * @since  1.0.0
         */
        
        public function wp_elv_error_log_deactivation()
        {
        
            wp_verify_nonce( $_REQUEST['wp_elv_error_log_deactivation_nonce'], 'wp_elv_error_log_deactivation_nonce' );
            
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die();
            }
            
            $reason_id = sanitize_text_field( wp_unslash( $_POST['reason'] ) );
            
            
            if ( empty( $reason_id ) ) {
                wp_die();
            }
            
            $reason_info = sanitize_text_field( wp_unslash( $_POST['reason_detail'] ) );
            
            if ( $reason_id == '1' ) {
                $reason_text = __( 'I only needed the plugin for a short period', 'wp_elv' );
            } elseif ( $reason_id == '2' ) {
                $reason_text = __( 'I found a better plugin', 'wp_elv' );
            } elseif ( $reason_id == '3' ) {
                $reason_text = __( 'The plugin broke my site', 'wp_elv' );
            } elseif ( $reason_id == '4' ) {
                $reason_text = __( 'The plugin suddenly stopped working', 'wp_elv' );
            } elseif ( $reason_id == '5' ) {
                $reason_text = __( 'I no longer need the plugin', 'wp_elv' );
            } elseif ( $reason_id == '6' ) {
                $reason_text = __( 'It\'s a temporary deactivation. I\'m just debugging an issue.', 'wp_elv' );
            } elseif ( $reason_id == '7' ) {
                $reason_text = __( 'Other', 'wp_elv' );
            }
            
            $cuurent_user = wp_get_current_user();
            
            $options = array(
                'plugin_name' => WP_ERROR_LOG_VIEWER_NAME,
                'plugin_version' => WP_ERROR_LOG_VIEWER_VER,
                'reason_id' => $reason_id,
                'reason_text' => $reason_text,
                'reason_info' => $reason_info,
                'display_name' => $cuurent_user->display_name,
                'email' => get_option( 'admin_email' ),
                'website' => get_site_url(),
                'blog_language' => get_bloginfo( 'language' ),
                'wordpress_version' => get_bloginfo( 'version' ),
                'php_version' => PHP_VERSION 
            );
                        
            $to      = 'info@jaitras.com';
            $subject = 'Plugin Uninstallation';
            $body    = '<p>Plugin Name: ' . WP_ERROR_LOG_VIEWER_NAME . '</p>';
            $body .= '<p>Plugin Version: ' . WP_ERROR_LOG_VIEWER_VER . '</p>';
            $body .= '<p>Reason: ' . $reason_text . '</p>';
            $body .= '<p>Reason Info: ' . $reason_info . '</p>';
            $body .= '<p>Admin Name: ' . $cuurent_user->display_name . '</p>';
            $body .= '<p>Admin Email: ' . get_option( 'admin_email' ) . '</p>';
            $body .= '<p>Website: ' . get_site_url() . '</p>';
            $body .= '<p>Website Language: ' . get_bloginfo( 'language' ) . '</p>';
            $body .= '<p>Wordpress Version: ' . get_bloginfo( 'version' ) . '</p>';
            $body .= '<p>PHP Version: ' . PHP_VERSION . '</p>';
            $headers = array(
                 'Content-Type: text/html; charset=UTF-8' 
            );
            
            wp_mail( $to, $subject, $body, $headers );
            
            wp_die();
        }
        
        
        /**
         * Add a link to the settings page to the plugins list
         *
         * @since  1.0.0
         */
        public function wp_elv_error_log_action_links( $links, $file )
        {
            
            static $this_plugin;
            
            if ( empty( $this_plugin ) ) {
                
                $this_plugin = 'wp-error-log-viewer/wp-error-log-viewer.php';
            }
            
            if ( $file == $this_plugin ) {
                
                $settings_link = sprintf( esc_html__( '%1$s Log Viewer %2$s', 'wp_elv' ), '<a href="' . admin_url( 'admin.php?page=wp-error-log-viewer' ) . '">', '</a>' );
                
                array_unshift( $links, $settings_link );
                
            }
            
            return $links;
        }
        
        /**
         * Add support link
         *
         * @since 1.0.0
         * @param array $plugin_meta
         * @param string $plugin_file
         */
        
        public function wp_elv_add_action_links( $plugin_meta, $plugin_file )
        {
            if ( $plugin_file == plugin_basename( __FILE__ ) ) {
                
                $plugin_meta_str = '<a href="' . WP_ERROR_LOG_VIEWER_SUPPORT_URL . '" target="_blank">' . __( 'Support', 'wp_elv' ) . '</a>';
                array_push( $plugin_meta, $plugin_meta_str );

                $plugin_meta_str = '<a href="' . WP_ERROR_LOG_VIEWER_REVIEW_URL . '" target="_blank">' . __( 'Post Review', 'wp_elv' ) . '</a>';
                array_push( $plugin_meta, $plugin_meta_str );
            }
            return $plugin_meta;
        }

        /**
         * Ask the user to leave a review for the plugin.
         */
        public function wp_elv_notice_review()
        {
            global $current_user;
            wp_get_current_user();
            $user_n = '';
            
            if ( ! empty( $current_user->display_name ) ) {
                $user_n = ' ' . $current_user->display_name;
            }
            
            echo '<div id="wp_elv-review" class="notice notice-info is-dismissible"><p>' . sprintf( __( 'Hi%s, Thank you for using <b>' . WP_ERROR_LOG_VIEWER_NAME . '</b>. Please don\'t forget to rate our plugin. We sincerely appreciate your feedback.', 'wp_elv' ), $user_n ) . '<br><a target="_blank" href="' . WP_ERROR_LOG_VIEWER_REVIEW_URL . '" class="button-secondary">' . esc_html__( 'Post Review', 'wp_elv' ) . '</a>' . '</p></div>';
        }
        
        /**
         * Loads the inline script to dismiss the review notice.
         */
        public function wp_elv_notice_review_script()
        {
            echo "<script>\n" . "jQuery(document).on('click', '#wp_elv-review .notice-dismiss', function() {\n" . "\tvar wp_elv_error_log_review_data = {\n" . "\t\taction: 'wp_elv_error_log_review_notice',\n" . "\t};\n" . "\tjQuery.post(ajaxurl, wp_elv_error_log_review_data, function(response) {\n" . "\t\tif (response) {\n" . "\t\t\tconsole.log(response);\n" . "\t\t}\n" . "\t});\n" . "});\n" . "</script>\n";
        }
        
        /**
         * Disables the notice about leaving a review.
         */
        public function wp_elv_dismiss_review_notice()
        {
            update_option( 'wp_elv_dismiss_review_notice', true, false );
            wp_die();
        }
        
        /*public function osort( &$array, $properties )
        {
            
            if ( is_string( $properties ) ) {
                $properties = array(
                     $properties => SORT_ASC 
                );
            }

            uasort( $array, function( $a, $b ) use ($properties)
            {
                
                foreach ( $properties as $k => $v ) {
                    
                    if ( is_int( $k ) ) {
                        $k = $v;
                        $v = SORT_ASC;
                    }

                    $collapse = function( $node, $props )
                    {
                        
                        if ( is_array( $props ) ) {
                            foreach ( $props as $prop ) {
                                $node = ( ! isset( $node->$prop ) ) ? null : $node->$prop;
                            }
                            return $node;
                        } else {
                            return ( ! isset( $node->$props ) ) ? null : $node->$props;
                        }
                    };
                    $aProp    = $collapse( $a, $k );
                    $bProp    = $collapse( $b, $k );
                    
                    if ( $aProp != $bProp ) {
                        return ( $v == SORT_ASC ) ? strnatcasecmp( $aProp, $bProp ) : strnatcasecmp( $bProp, $aProp );
                    }
                }
                return 0;
            } );
        }*/
        
    }
} // End if class_exists check

/**
 * The main function responsible for returning the one true WP_Error_Log_Viewer
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      \WP_Error_Log_Viewer The one true WP_Error_Log_Viewer
 *
 * @todo        Inclusion of the activation code below isn't mandatory, but
 *              can prevent any number of errors, including fatal errors, in
 *              situations where your extension is activated but EDD is not
 *              present.
 */
function WP_Error_Log_Viewer_load()
{
    
    if ( is_admin() ) {
        require_once plugin_dir_path( __FILE__ ) . 'includes/functions.php';
    }
    return WP_Error_Log_Viewer::instance();
}
add_action( 'plugins_loaded', 'WP_Error_Log_Viewer_load' );

/**
 * The activation hook is called outside of the singleton because WordPress doesn't
 * register the call from within the class, since we are preferring the plugins_loaded
 * hook for compatibility, we also can't reference a function inside the plugin class
 * for the activation function. If you need an activation function, put it here.
 *
 * @since       1.0.0
 * @return      void
 */

function wp_error_log_viewer_activation()
{
    /* Activation functions here */
}
register_activation_hook( __FILE__, 'wp_error_log_viewer_activation' );