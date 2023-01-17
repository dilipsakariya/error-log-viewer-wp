<?php
/**
 * Error Log Viewer By WP Guru Plugin.
 *
 * @package      Error_Log_Viewer_WP
 * @copyright    Copyright (C) 2022-2023, WP Guru - support@wpguru.co
 * @link         https://wpguru.co
 * @since        1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       Error Log Viewer By WP Guru
 * Version:           1.0.0
 * Plugin URI:        https://wordpress.org/plugins/error-log-viewer-wp/
 * Description:       Error Log Viewer plugin offers a user-friendly way to view and analyze PHP error logs. Easy to monitor distinct error log entries which helps to solve all errors quickly.
 * Author:            WP Guru
 * Author URI:        https://wpguru.co
 * License:           GPL-3.0+
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       error-log-viewer-wp
 * Requires at least: 3.9
 * Tested up to:      6.1.1
 * Domain Path:       /languages
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ){
    exit;
}

if ( ! class_exists( 'Error_Log_Viewer_WP' ) ) {
    
    /**
     * Main Error_Log_Viewer_WP class
     *
     * @since       1.0.0
     */
    class Error_Log_Viewer_WP {
        
        /**
         * @var         Error_Log_Viewer_WP $instance The one true Error_Log_Viewer_WP
         * @since       1.0.0
         */
        private static $instance;

        /**
         * Holds `wp-config.php` file path.
         *
         * @var string
         */
        protected static $wp_config_path;

        /**
         * Holds Error Log Directory.
         *
         * @var string
         */
        protected $log_directory;

        /**
         * Holds Plugin Permalink.
         *
         * @var string
         */
        protected $elvwp_permalink;

        
        public function __construct() {

            self::$wp_config_path               = $this->get_wp_config_path();

            $this->elvwp_permalink              = 'error-log-viewer-wp';
            $this->log_directory                = WP_CONTENT_DIR . '/uploads/' . $elvwp_permalink;

            $elvwp_log_directory_htaccess       = $this->log_directory . '/.htaccess';
            $elvwp_log_directory_index          = $this->log_directory . '/index.php';

            if ( ! is_writable( self::$wp_config_path ) ) {
                echo '<div class="error notice is-dismissible"><p>';
                echo wp_kses_post( __( 'The <strong>Error Log Viewer</strong> plugin must have a <code>wp-config.php</code> file that is writable by the filesystem.', 'error-log-viewer-wp' ) );
                echo '</p></div>';

                return false;
            }
            
            if ( ! is_dir( $this->log_directory ) ) {
                mkdir( $this->log_directory );
            }
            
            if ( ! file_exists( $elvwp_log_directory_index ) ) {
                $elvwp_file_index = fopen( $elvwp_log_directory_index, 'w' ) or die( 'Unable to open file!' );
                
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
                    wp_die( __( 'You are not allowed to access this file.', 'error-log-viewer-wp' ) );
                    } else {
                    // insert here your awesome source code
                    // to serve the requested file
                    }";
                
                fwrite( $elvwp_file_index, $txt );
                fclose( $elvwp_file_index );
            }
            
            if ( ! file_exists( $elvwp_log_directory_htaccess ) ) {
                $elvwp_log_directory_htaccess = fopen( $elvwp_log_directory_htaccess, 'w' ) or die( 'Unable to open file!' );
                $rule = 'RewriteCond %{REQUEST_FILENAME} -s
                      RewriteRule ^(.*)$ /index.php?' . $this->elvwp_permalink . '=$1 [QSA,L]';
                
                fwrite( $elvwp_log_directory_htaccess, $rule );
                fclose( $elvwp_log_directory_htaccess );
            }
            
        }
        
        
        /**
         * Get active instance
         *
         * @access      public
         * @since       1.0.0
         * @return      object self::$instance The one true Error_Log_Viewer_WP
         */
        public static function instance() {
            
            if ( ! self::$instance ) {
                self::$instance = new Error_Log_Viewer_WP();
                self::$instance->setup_constants();
                self::$instance->load_table();
                self::$instance->load_textdomain();
                self::$instance->hooks();
                self::$instance->elvwp_error_log_details();
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
        private function setup_constants() {
            
            // Plugin version
            define( 'ELVWP_VER', '1.0.0' );
            
            // Plugin name
            define( 'ELVWP_NAME', 'Error Log Viewer By WP Guru' );
            
            // Plugin path
            define( 'ELVWP_DIR', plugin_dir_path( __FILE__ ) );
            
            // Plugin URL
            define( 'ELVWP_URL', plugin_dir_url( __FILE__ ) );
            
            define( 'ELVWP_SUPPORT_URL', 'https://wordpress.org/support/plugin/' . $this->elvwp_permalink );
            
            define( 'ELVWP_REVIEW_URL', 'https://wordpress.org/support/view/plugin-reviews/' . $this->elvwp_permalink . '?filter=5' );
            
            define( 'ELVWP_DEBUG_LOGFOLDER', $this->log_directory );

            if ( ! class_exists( 'WP_Config_Transformer' ) ) {
                require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-config-transformer.php';
            }
            
            $config_transformer     = new WP_Config_Transformer( self::$wp_config_path );
                        
            if ( ( defined( WP_DEBUG_LOG ) && WP_DEBUG_LOG == false ) || !defined( WP_DEBUG_LOG ) ) {
                
                if ( $config_transformer->exists( 'constant', 'WP_DEBUG_LOG' ) ) {
                    $config_transformer->update( 'constant', 'WP_DEBUG_LOG', true );
                } else {
                    $config_transformer->add( 'constant', 'WP_DEBUG_LOG', true );
                }
            }

            $error_log  = $this->log_directory . '/log-' . date( 'd-M-Y' ) . '.log';

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
        private function load_table() {
            global $wpdb;
            $elvwp_table = $wpdb->prefix . 'elvwp_error_logs';
            
            // create database table
            if ( $wpdb->get_var( $wpdb->prepare( "show tables like %s", $elvwp_table ) ) != $elvwp_table ) {
                $sql = 'CREATE TABLE ' . $elvwp_table . ' (
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
        }

        /**
         * Internationalization
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public function load_textdomain() {
            // Set filter for language directory
            $lang_dir       = ELVWP_DIR . '/languages/';
            $lang_dir       = apply_filters( 'error_log_viewer_wp_languages_directory', $lang_dir );
            
            // Traditional WordPress plugin locale filter
            $locale         = apply_filters( 'plugin_locale', get_locale(), 'error-log-viewer-wp' );
            $mofile         = sprintf( '%1$s-%2$s.mo', 'error-log-viewer-wp', $locale );
            
            // Setup paths to current locale file
            $mofile_local   = $lang_dir . $mofile;
            $mofile_global  = WP_LANG_DIR . '/elvwp/' . $mofile;
            
            if ( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/elvwp/ folder
                load_textdomain( 'error-log-viewer-wp', $mofile_global );
            } elseif ( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/elvwp/languages/ folder
                load_textdomain( 'error-log-viewer-wp', $mofile_local );
            } else {
                // Load the default language files
                load_plugin_textdomain( 'error-log-viewer-wp', false, $lang_dir );
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
        private function hooks() {

            // AJAX action hook to disable the 'review request' notice.
            add_action( 'wp_ajax_elvwp_review_notice', array(
                $this,
                'elvwp_dismiss_review_notice', 
            ) );
            
            if ( ! get_option( 'elvwp_review_time' ) ) {
                $review_time = time() + 7 * DAY_IN_SECONDS;
                add_option( 'elvwp_review_time', $review_time, '', false );
            }
            
            if ( is_admin() && get_option( 'elvwp_review_time' ) && get_option( 'elvwp_review_time' ) < time() && !get_option( 'elvwp_dismiss_review_notice' ) ) {
                add_action( 'admin_notices', array(
                    $this,
                    'elvwp_notice_review', 
                ) );
                add_action( 'admin_footer', array(
                    $this,
                    'elvwp_notice_review_script', 
                ) );

                add_filter( 'admin_footer_text', array(
                    $this,
                    'elvwp_admin_footer_text', 
                ) );
            }
            
            add_action( 'plugin_row_meta', array(
                $this,
                'elvwp_add_action_links', 
            ), 10, 2 );
            add_action( 'admin_footer', array(
                $this,
                'elvwp_add_deactive_modal', 
            ) );
            add_action( 'wp_ajax_elvwp_error_log_deactivation', array(
                $this,
                'elvwp_error_log_deactivation', 
            ) );
            add_action( 'plugin_action_links', array(
                $this,
                'elvwp_error_log_action_links', 
            ), 10, 2 );
            
            if ( is_admin() ) {
                
                add_action( 'admin_menu', array(
                    $this,
                    'elvwp_plugin_menu', 
                ) );
                add_action( 'init', array(
                    $this,
                    'elvwp_log_download', 
                ) );
                add_action( 'wp_ajax_nopriv_elvwp_log_download', array(
                    $this,
                    'elvwp_log_download', 
                ) );
                add_action( 'wp_before_admin_bar_render', array(
                    $this,
                    'elvwp_register_admin_bar', 
                ) );
                add_action( 'wp_ajax_elvwp_purge_log', array(
                    $this,
                    'elvwp_purge_log', 
                ) );
                add_action( 'wp_ajax_elvwp_datatable_loglist', array(
                    $this,
                    'elvwp_datatable_loglist', 
                ) );
                add_action( 'wp_ajax_elvwp_datatable_delete_data', array(
                    $this,
                    'elvwp_datatable_delete_data', 
                ) );
            }
            
        }

        /**
         * Get the `wp-config.php` file path.
         *
         * The config file may reside one level above ABSPATH but is not part of another installation.
         *
         * @see wp-load.php#L26-L42
         *
         * @return string $wp_config_path
         */

        public function get_wp_config_path() {
            $wp_config_path = ABSPATH . 'wp-config.php';

            if ( ! file_exists( $wp_config_path ) ) {
                if ( @file_exists( dirname( ABSPATH ) . '/wp-config.php' ) && ! @file_exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {
                    $wp_config_path = dirname( ABSPATH ) . '/wp-config.php';
                }
            }

            /**
             * Filter the config file path.
             *
             * @since 1.0.0
             *
             * @param string $wp_config_path
             */
            return apply_filters( 'elvwp_config_path', $wp_config_path );
        }

        /**
         * Error Log Details
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         *
         */
        public function elvwp_error_log_details( $log_date = '' ) {
            global $wpdb;
                                    
            $count                = 1;
            $wp_empty_folder      = ( count( glob( $this->log_directory . "/*" ) ) === 0 ) ? 'Empty' : 'Not empty';
            
            if ( 'Empty' === $wp_empty_folder ) {
                $elvwp_table = $wpdb->prefix . 'elvwp_error_logs';
                
                if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", '%'.$elvwp_table.'%' ) ) == $elvwp_table ) {
                    $wpdb->query( $wpdb->prepare( "TRUNCATE TABLE $elvwp_table" ) );
                }
            }
            
            if ( is_dir( $this->log_directory ) ) {
                $scanned_directory = array_diff( scandir( $this->log_directory, 1 ), array(
                        '..',
                        '.', 
                    ) );
                $elvwp_table             = $wpdb->prefix . 'elvwp_error_logs';
                
                if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", '%'.$elvwp_table.'%' ) ) == $elvwp_table ) {
                    
                    foreach ( $scanned_directory as $key => $value ) {
                        $count              = 1;
                        $file_name          = $value;
                        $elvwp_table              = $wpdb->prefix . 'elvwp_error_logs';
                        
                        $elvwp_table_data   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$elvwp_table}" ) );
                        
                        foreach ( $elvwp_table_data as $elvwp_tablekey => $elvwp_tablevalue ) {
                            $elvwp_database_file = $elvwp_tablevalue->file_name;
                            
                            if ( $elvwp_database_file == $file_name ) {
                                $count             = 0;
                            } else {
                                $elvwp_checkfile   = $this->log_directory . '/' . $elvwp_database_file;
                            }
                        }
                        
                        if ( 1 === $count ) {
                            $current_date           = date( 'Y-m-d', strtotime( substr( $file_name, 4, 11 ) ) );
                            $elvwp_coneverted_date  = date( 'd-M-Y', strtotime( $current_date ) );
                            $log_details            = $this->elvwp_log_details( $elvwp_coneverted_date );
                            
                            if ( $log_details ) {
                                $elvwp_serialize_data  = serialize( $log_details['typecount'] );
                                $log_path              = $log_details['error_log'];

                                if ( '.htaccess' !== $file_name && 'index.php' !== $file_name ) {
                                    $data   = array(
                                      'file_name'   => $file_name,
                                      'details'     => $elvwp_serialize_data,
                                      'created_at'  => $current_date,
                                      'log_path'    => $log_path,
                                    );
                                    $format = array(
                                      '%s',
                                      '%s',
                                      '%s',
                                      '%s', 
                                    );
                                    $wpdb->insert( $elvwp_table, $data, $format );
                                }
                            }
                        }
                        
                        if ( 0 === $count ) {
                            $current_date           = date( 'Y-m-d', strtotime( substr( $file_name, 4, 11 ) ) );
                            $elvwp_coneverted_date  = date( 'd-M-Y', strtotime( $current_date ) );
                            $log_details            = $this->elvwp_log_details( $elvwp_coneverted_date );
                            $elvwp_serialize_data   = serialize( $log_details['typecount'] );
                            $log_path               = $log_details['error_log'];
                            
                            if ( '.htaccess' !== $file_name && 'index.php' !== $file_name ) {
                                $data          = array(
                                    'details'       => $elvwp_serialize_data,
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

                                $wpdb->update( $elvwp_table, $data, $wherefilename, $format );
                            }
                        }
                    }
                }
            }
            
            $elvwp_table = $wpdb->prefix . 'elvwp_error_logs';
            
            if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", '%'.$elvwp_table.'%' ) ) == $elvwp_table ) {

                $elvwp_table_data = $wpdb->get_results( $wpdb->prepare( "SELECT file_name FROM {$elvwp_table}" ) );
                
                foreach ( $elvwp_table_data as $key => $value ) {
                    $elvwp_database_file = $value->file_name;
                    $elvwp_checkfile     = $this->log_directory . '/' . $elvwp_database_file;
                    
                    if ( ! file_exists( $elvwp_checkfile ) ) {
                        $wpdb->delete( $elvwp_table, array(
                            'file_name' => $elvwp_database_file, 
                        ) );
                    }
                }
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
        public function elvwp_log_download() {
            
            if ( is_admin() && isset( $_POST['elvwp_error_log_download'] ) && isset( $_POST['elvwp_error_log'] ) && !empty( sanitize_text_field( $_POST['elvwp_error_log'] ) ) ) {
                
                $elvwp_file = sanitize_text_field( $_POST['elvwp_error_log'] );
                
                try {
                    $filename = basename( $elvwp_file );
                    header( 'Pragma: public' );
                    header( 'Expires: 0' );
                    header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
                    header( 'Content-Type: application/octet-stream' );
                    header( 'Content-Disposition: attachment; filename=$filename' );
                    header( 'Content-Transfer-Encoding: binary' );
                    header( 'Content-Length: ' . filesize( $elvwp_file ) );
                    flush();
                    readfile( $elvwp_file );
                    exit;
                }
                catch ( Exception $e ) {
                    $data['error'] = $e->getMessage() . ' @ ' . $e->getFile() . ' - ' . $e->getLine();
                }
            }
            
            if ( is_admin() && isset( $_POST['elvwp_datatable_log_download'] ) && isset( $_POST['elvwp_datatable_downloadid'] ) && !empty( sanitize_text_field( $_POST['elvwp_datatable_downloadid'] ) ) ) {

                global $wpdb;
                $elvwp_table                   = $wpdb->prefix . 'elvwp_error_logs';
                $elvwp_datatable_downloadid    = sanitize_text_field( $_POST['elvwp_datatable_downloadid'] );

                $elvwp_download_table_data     = $wpdb->get_col( $wpdb->prepare( "SELECT file_name from {$elvwp_table} where id=%d", $elvwp_datatable_downloadid ) );
                
                foreach ( $elvwp_download_table_data as $key => $value ) {
                    $ps_download_filename = $value;
                }
                
                $elvwp_download_filepath       = $this->log_directory . '/' . $ps_download_filename;
                
                try {
                    $filename = basename( $elvwp_download_filepath );
                    header( 'Pragma: public' );
                    header( 'Expires: 0' );
                    header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
                    header( 'Content-Type: application/octet-stream' );
                    header( 'Content-Disposition: attachment; filename=$filename' );
                    header( 'Content-Transfer-Encoding: binary' );
                    header( 'Content-Length: ' . filesize( $elvwp_download_filepath ) );
                    flush();
                    readfile( $elvwp_download_filepath );
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
        public function elvwp_purge_log() {
            if ( ! isset( $_POST['elvwp_nonce'] ) ) {
                echo json_encode( array(
                    'success'   => '0',
                    'msg'       => __( 'Security Error.', 'error-log-viewer-wp' ), 
                ) );

                wp_die();
            }

            if ( ! wp_verify_nonce( sanitize_text_field( $_POST['elvwp_nonce'] ), 'elvwp_purge_log_nonce' ) ) {
                echo json_encode( array(
                    'success'   => '0',
                    'msg'       => __( 'Security Error.', 'error-log-viewer-wp' ), 
                ) );

                wp_die();
            }

            if ( is_admin() && isset( $_POST['elvwp_error_log'] ) && !empty( sanitize_text_field( $_POST['elvwp_error_log'] ) ) ) {
                $elvwp_error_log = sanitize_text_field( $_POST['elvwp_error_log'] );
                
                if ( file_exists( $elvwp_error_log ) ) {
                    
                    unlink( $elvwp_error_log );
                    
                    echo json_encode( array(
                        'success'   => '1',
                        'msg'       => __( 'Log file deleted successfully.', 'error-log-viewer-wp' ),
                    ) );

                    wp_die();
                } else {
                    echo json_encode( array(
                        'success'   => '0',
                        'msg'       => __( 'Log file deleted failed. Please try again after reloading page.', 'error-log-viewer-wp' ),
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
        public function elvwp_register_admin_bar() {
            
            if ( isset( $_POST['date'] ) && !empty( sanitize_text_field( $_POST['date'] ) ) ) {
                $log_date = date( 'd-M-Y', strtotime( sanitize_text_field( $_POST['date'] ) ) );
            } else {
                $log_date = date( 'd-M-Y' );
            }

            $log_details    = $this->elvwp_log_details( $log_date );
            $total          = 0;

            if ( $log_details ) {
                $count       = 1;
                $error_log   = $log_details['error_log'];
                $total       = $log_details['total'];
                
                if ( file_exists( $error_log ) ) {
                    $elvwp_serialize_data = serialize( $log_details['typecount'] );
                    $elvwp_primary_alert  = '<span class="elvwp-admin-bar-error-count"><strong>' .$total.' </strong></span>';
                }
            }
            
            // Only site admins can see the admin bar entry.
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            // Primary link text.
            $link_text = esc_html__( 'Error Log Viewer', 'error-log-viewer-wp' );
            
            if ( empty( $total ) ) {
                $elvwp_primary_alert = '<span class="elvwp-admin-bar-error-count"><strong>0</strong></span>';
            }
            
            // Bring the admin bar into scope.
            global $wp_admin_bar;
            
            // Add the link.
            $wp_admin_bar->add_menu( array(
                'parent'    => false,
                'id'        => 'elvwp_admin_bar',
                'title'     => $link_text . $elvwp_primary_alert,
                'href'      => admin_url( 'admin.php?page=' . $this->elvwp_permalink ),
                'meta'      => array(
                                    'title' => __( 'Error Log Viewer', 'error-log-viewer-wp' ),
                               ),
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
        public function elvwp_log_details( $log_date = '', $is_raw_log = false ) {
            
            $error_log  = $this->log_directory . '/log-' . $log_date . '.log';
            /**
             * @var string|null Path to log cache - must be writable - null for no cache
             */
            $cache      = null;
            /**
             * @var array Array of log lines
             */
            $logs       = array();
            /**
             * @var array Array of log types
             */
            $types      = array();
            
            /**
             * https://gist.github.com/amnuts/8633684
             */
            
            $total      = 0;
            
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
                    
                    while ( ! preg_match( '!^\[(?P<time>[^\]]*)\] ((PHP|ojs2: )(?P<typea>.*?):|(?P<typeb>(WordPress|Error|ojs2|\w has produced)\s{1,}\w+ \w+))\s+(?P<msg>.*)$!', $log->current() ) && !$log->eof() ) {
                        $more_str = $log->current();
                        array_push( $more, $more_str );
                        $log->next();
                    }
                    
                    if ( ! empty( $more ) ) {
                        $prev_error->more = join( '\n', $more );
                    }

                    $parts = array();
                    
                    if ( preg_match( '!^\[(?P<time>[^\]]*)\] ((PHP|ojs2: )(?P<typea>.*?):|(?P<typeb>(WordPress|Error|ojs2|\w has produced)\s{1,}\w+ \w+))\s+(?P<msg>.*)$!', $log->current(), $parts ) ) {
                        
                        $parts['type'] = ( $parts['typea'] ?: $parts['typeb'] );
                        
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
                                    $folders_arr        = explode( '\\', ltrim( $msg_arr[1], '\\' ) );
                                    $err_type_folder    =  isset( $folders_arr[0] ) ? $folders_arr[0] : '' ;
                                }
                            } elseif ( strpos( $msg, '/wp-content/plugins' ) !== false ) {
                                $err_type           = 'plugin';
                                $msg_arr            = explode( '/wp-content/plugins/', $msg );

                                if ( isset( $msg_arr[1] ) ) {
                                    $folders_arr        = explode( '/', ltrim( $msg_arr[1], '/' ) );
                                    $err_type_folder    =  isset( $folders_arr[0] ) ? $folders_arr[0] : '' ;
                                }
                            } elseif ( strpos( $msg, '\wp-content\themes' ) !== false ) {
                                $err_type           = 'theme';
                                $msg_arr            = explode( '\wp-content\themes\\', $msg );

                                if ( isset( $msg_arr[1] ) ) {
                                    $folders_arr        = explode( '\\', ltrim( $msg_arr[1], '\\' ) );
                                    $err_type_folder    =  isset( $folders_arr[0] ) ? $folders_arr[0] : '' ;
                                }
                            } elseif ( strpos( $msg, '/wp-content/themes' ) !== false ) {
                                $err_type           = 'theme';
                                $msg_arr            = explode( '/wp-content/themes/', $msg );

                                if ( isset( $msg_arr[1] ) ) {
                                    $folders_arr        = explode( '/', ltrim( $msg_arr[1], '/' ) );
                                    $err_type_folder    =  isset( $folders_arr[0] ) ? $folders_arr[0] : '' ;
                                }
                            } else {
                                $err_type       = 'other';
                                $base_path_er   =  trim( ABSPATH, '/' );
                                $msg_arr        = explode( $base_path_er, $msg );
                                
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

                $this->elvwp_error_log_osort( $logs, array(
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
        public function elvwp_plugin_menu() {
            
            $menu = add_menu_page( __( 'Error Log Viewer', 'error-log-viewer-wp' ), __( 'Error Log Viewer', 'error-log-viewer-wp' ), 'manage_options', $this->elvwp_permalink, array(
                $this,
                'elvwp_error_by_date', 
            ) );

            add_submenu_page( $this->elvwp_permalink, __( 'Error Log Overview', 'error-log-viewer-wp' ), __( 'Error Log Overview', 'error-log-viewer-wp' ), 'manage_options', $this->elvwp_permalink, array(
                $this,
                'elvwp_error_by_date', 
            ) );

            $list = add_submenu_page( $this->elvwp_permalink, __( 'Error Log List', 'error-log-viewer-wp' ), __( 'Error Log List', $this->elvwp_permalink ), 'manage_options', 'elvwp-list', array(
                $this,
                'elvwp_log_list_datatable', 
            ) );
            
            add_action( 'admin_enqueue_scripts', array(
                 $this,
                'elvwp_admin_enqueue',
            ) );
        }
        
        public function elvwp_log_list_datatable() {
            require_once( ELVWP_DIR . 'includes/error-log-list-template.php' );
        }
        
        public function elvwp_error_by_date() {
            require_once( ELVWP_DIR . 'includes/error-log-viewer.php' );
        }
        
        /**
         * Admin Enqueue style and scripts
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         *
         */
        public function elvwp_admin_enqueue( $hook ) {

            wp_enqueue_style( 'elvwp_error_log_admin_style', plugins_url( '/assets/css/admin.css', __FILE__ ), array(), ELVWP_VER );

            if ( 'toplevel_page_error-log-viewer-wp' === $hook || 'error-log-viewer_page_elvwp-list' === $hook ) {

                wp_enqueue_script( 'elvwp_datatable', plugins_url( '/assets/js/jquery.dataTables.min.js', __FILE__ ), array(
                     'jquery' 
                ), ELVWP_VER );
                
                wp_register_style( 'elvwp_datatables_style', plugins_url( '/assets/css/jquery.dataTables.min.css', __FILE__ ), array(), ELVWP_VER );
                wp_register_style( 'elvwp_ui_style', plugins_url( '/assets/css/datepicker.min.css', __FILE__ ), array(), ELVWP_VER );
                
                wp_enqueue_style( 'elvwp_datatables_style' );
                wp_enqueue_style( 'elvwp_ui_style' );                
            }

            if ( 'plugins.php' === $hook || 'toplevel_page_error-log-viewer-wp' === $hook || 'error-log-viewer_page_elvwp-list' === $hook ) {

                wp_localize_script( 'elvwp_admin_script', 'ajax_script_object', array(
                     'ajax_url'                 => admin_url( 'admin-ajax.php' ), 
                     'date_format'              => elvwp_date_formate(), 
                     'date_format_php'          => get_option( 'date_format' ), 
                     'months'                   => array( '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April', '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August', '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December' ), 
                     'delete_data_nonce'        => wp_create_nonce( 'elvwp_delete_data_nonce' ),
                     'purge_log_nonce'          => wp_create_nonce( 'elvwp_purge_log_nonce' ),
                ) );

                wp_localize_script( 'elvwp_admin_script', 'datatable', array(
                     'datatable_ajax_url' => admin_url( 'admin-ajax.php?action=elvwp_datatable_loglist' ) 
                ) );

                wp_localize_script( 'wp_elv_admin_script', 'script_object', array() );

                wp_enqueue_script( 'elvwp_admin_script', plugins_url( '/assets/js/admin.js', __FILE__ ), array(
                     'elvwp_admin_ui_script' 
                ), ELVWP_VER );

                wp_enqueue_script( 'elvwp_admin_ui_script', plugins_url( '/assets/js/datepicker.min.js', __FILE__ ), array(
                     'jquery' 
                ), ELVWP_VER );
            }

            
        }
        
        public function elvwp_datatable_loglist() {
            global $wpdb;
            $elvwp_table            = $wpdb->prefix . 'elvwp_error_logs';
            $column_sort_order      = sanitize_text_field( $_POST['order'][ 0 ]['dir'] );
            $draw                   = sanitize_text_field( $_POST['draw'] );
            $row                    = sanitize_text_field( $_POST['start'] );
            $row_per_page           = sanitize_text_field( $_POST['length'] ); // Rows display per page
            $column_index           = sanitize_text_field( $_POST['order'][ 0 ]['column'] ); // Column index
            $columnName             = sanitize_text_field( $_POST['columns'][ $column_index ]['data'] );

            $elvwp_table_data       = $wpdb->get_results( $wpdb->prepare( "SELECT * from {$elvwp_table} ORDER BY created_at {$column_sort_order} LIMIT %d,%d", $row, $row_per_page ) );

            $data                   = array();
            $date_format            = get_option( 'date_format' );
            
            if ( $elvwp_table_data ) {

                foreach ( $elvwp_table_data as $key => $value ) {
                    $created_at         = $value->created_at;
                    $elvwp_log_path     = $value->log_path;
                    $id                 = $value->id;
                    $filename           = $this->log_directory . '/' . $value->file_name;
                    $elvwp_url          = add_query_arg( 'date', $created_at, admin_url( 'admin.php?page=' . $this->elvwp_permalink ) );
                    $array_rewrite      = array();
                    // Array with the md5 hashes
                    $array              = array();

                    $array_hashes_main  = unserialize( $value->details );

                    $folder_wise        = array( 'plugin', 'theme', 'other' );

                    foreach ( $folder_wise as $ftype) {
                        // code...
                        $array_hashes = isset( $array_hashes_main[ $ftype ] ) ? $array_hashes_main[ $ftype ] : array() ;

                        if ( $array_hashes ) {

                            $elvwp_output[ $ftype ]    = implode( '', array_map( function( $v, $k ) use ($created_at) {
                                
                                    if ( is_array( $v ) ) {
                                        return '<b>'.$k.'</b><br>'.implode( '', array_map( function( $v1, $k1 ) use ($created_at) {
                                                    
                                                    if ( is_array( $v1 ) ) {
                                                        return '<div class="elvwp_datatable ' . $k1 . '">' . $k1 . "[]: " . implode( '&' . $k1 . "[]: ", $v1 ) . '</div>';
                                                    } else {
                                                        
                                                        $elvwp_date_url_array = array(
                                                            'date' => $created_at,
                                                            'type' => $k1, 
                                                        );
                                                        $elvwp_error_type_url   = add_query_arg( $elvwp_date_url_array, admin_url( 'admin.php?page=' . $this->elvwp_permalink ) );
                                                        return '<div class="elvwp_datatable ' . $k1 . '"><a href="' . $elvwp_error_type_url . '">' . ucwords( $k1 . ": " . $v1 ) . '</a></div>';
                                                    }
                                                }, $v, array_keys( $v ) ) );
                                    } else {
                                        
                                        $elvwp_date_url_array = array(
                                            'date' => $created_at,
                                            'type' => $k, 
                                        );
                                        $elvwp_error_type_url   = add_query_arg( $elvwp_date_url_array, admin_url( 'admin.php?page=' . $this->elvwp_permalink ) );
                                        return '<div class="elvwp_datatable ' . $k . '"><a href="' . $elvwp_error_type_url . '">' . ucwords( $k . ": " . $v ) . '</a></div>';
                                    }
                                }, $array_hashes, array_keys( $array_hashes ) ) );
                        } else {
                            $elvwp_output[ $ftype ]    = '';
                        }
                    }

                    $button       = '<div class="elvwp_datatable_ajaxbutton"><form method="post"><button type="button" onclick="location.href = \'' . $elvwp_url . '\';" id="elvwp_datatable_view" ><i class="dashicons dashicons-text-page view"></i></button><button class="elvwp_datatable_delete" id="' . $id . '"><i class="dashicons dashicons-trash"></i></button><input type="hidden" name="elvwp_datatable_downloadid" value="' . $id . '"><button type="submit" name="elvwp_datatable_log_download" class="elvwp_datatable_log_download"><i class="dashicons dashicons-download"></i></button></form></div>';
                    
                    $data_ar       = array(
                        'created_at'        => date( $date_format, strtotime( $created_at ) ),
                        'plugin'            => $elvwp_output['plugin'],
                        'theme'             => $elvwp_output['theme'],
                        'others'            => $elvwp_output['other'],
                        'elvwp_log_path'    => $elvwp_log_path,
                        'action'            => $button, 
                    );
                    array_push( $data, $data_ar );

                    $total_record = $wpdb->get_var( $wpdb->prepare( "SELECT count(file_name) as filecount from {$elvwp_table}" ) );
                    
                    $json_data = array(
                        'draw'                  => intval( $draw ),
                        'iTotalRecords'         => $total_record,
                        'iTotalDisplayRecords'  => $total_record,
                        'data'                  => $data,
                    );
                }
            } else {
                $data      = array(
                    'created_at'    => 'No log',
                    'plugin'        => 'No log',
                    'theme'         => 'No log',
                    'others'        => 'No log',
                    'action'        => 'No log',
                );

                $json_data = array(
                    'draw'                  => intval( $draw ),
                    'iTotalRecords'         => 0,
                    'iTotalDisplayRecords'  => 0,
                    'data'                  => $data,
                );
            }

            echo json_encode( $json_data );
            wp_die();
        }
        
        public function elvwp_datatable_delete_data() {
            if ( ! isset( $_POST['elvwp_nonce'] ) ) {
                echo json_encode( array(
                    'success'   => '0',
                    'msg'       => __( 'Security Error.', 'error-log-viewer-wp' ), 
                ) );

                wp_die();
            }

            if ( ! wp_verify_nonce( sanitize_text_field( $_POST['elvwp_nonce'] ), 'elvwp_delete_data_nonce' ) ) {
                echo json_encode( array(
                    'success'   => '0',
                    'msg'       => __( 'Security Error.', 'error-log-viewer-wp' ), 
                ) );

                wp_die();
            }

            if ( is_admin() && isset( $_POST['elvwp_datatable_deleteid'] ) && !empty( sanitize_text_field( $_POST['elvwp_datatable_deleteid'] ) ) ) {
                
                global $wpdb;
                $elvwp_table              = $wpdb->prefix . 'elvwp_error_logs';
                $elvwp_datatable_deleteid = sanitize_text_field( $_POST['elvwp_datatable_deleteid'] );
                
                $elvwp_table_data         = $wpdb->get_col( $wpdb->prepare( "SELECT file_name from $elvwp_table where id=%d", $elvwp_datatable_deleteid ) );
                
                if ( ! empty( $elvwp_table_data ) ) {
                    
                    foreach ( $elvwp_table_data as $value ) {
                        $elvwp_datatable_filename = $this->log_directory . '/' . $value;
                    }
                    
                    if ( file_exists( $elvwp_datatable_filename ) ) {
                        $elvwp_datatable_basename = basename( $value );
                        $elvwp_delete_data        = $wpdb->delete( $elvwp_table, array(
                            'file_name' => $elvwp_datatable_basename 
                        ) );
                        
                        if ( $elvwp_delete_data ) {
                            
                            unlink( $elvwp_datatable_filename );
                            
                            echo json_encode( array(
                                'success'   => '1',
                                'msg'       => __( 'Log file deleted successfully.', 'error-log-viewer-wp' ),
                            ) );

                            wp_die();
                        } else {
                            echo json_encode( array(
                                'success'   => '0',
                                'msg'       => __( 'Log file deleted Failed.', 'error-log-viewer-wp' ), 
                            ) );

                            wp_die();
                        }
                    } else {
                        echo json_encode( array(
                            'success'   => '0',
                            'msg'       => __( 'Log file deleted Failed.', 'error-log-viewer-wp' ),
                        ) );

                        wp_die();
                    }
                }
            }
        }
        
        public function elvwp_error_log_osort( &$array, $properties ) {
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

                    $collapse = function( $node, $props ) {
                        
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
         * Add deactivate modal layout.
         */
        public function elvwp_add_deactive_modal() {
            global $pagenow;
            
            if ( 'plugins.php' !== $pagenow ) {
                return;
            }
            include ELVWP_DIR . 'includes/deactivation-form.php';
        }
        
        /**
         * Called after the user has submitted his reason for deactivating the plugin.
         *
         * @since  1.0.0
         */
        
        public function elvwp_error_log_deactivation() {
        
            wp_verify_nonce( sanitize_text_field( $_REQUEST['elvwp_deactivation_nonce'] ), 'elvwp_deactivation_nonce' );
            
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die();
            }
            
            $reason_id      = sanitize_text_field( wp_unslash( $_POST['reason'] ) );
            
            
            if ( empty( $reason_id ) ) {
                wp_die();
            }
            
            $reason_info    = sanitize_text_field( wp_unslash( $_POST['reason_detail'] ) );
            
            if ( $reason_id == '1' ) {
                $reason_text = __( 'I only needed the plugin for a short period', 'error-log-viewer-wp' );
            } elseif ( $reason_id == '2' ) {
                $reason_text = __( 'I found a better plugin', 'error-log-viewer-wp' );
            } elseif ( $reason_id == '3' ) {
                $reason_text = __( 'The plugin broke my site', 'error-log-viewer-wp' );
            } elseif ( $reason_id == '4' ) {
                $reason_text = __( 'The plugin suddenly stopped working', 'error-log-viewer-wp' );
            } elseif ( $reason_id == '5' ) {
                $reason_text = __( 'I no longer need the plugin', 'error-log-viewer-wp' );
            } elseif ( $reason_id == '6' ) {
                $reason_text = __( 'It\'s a temporary deactivation. I\'m just debugging an issue.', 'error-log-viewer-wp' );
            } elseif ( $reason_id == '7' ) {
                $reason_text = __( 'Other', 'error-log-viewer-wp' );
            }
            
            $cuurent_user   = wp_get_current_user();
            
            $options = array(
                'plugin_name'           => ELVWP_NAME,
                'plugin_version'        => ELVWP_VER,
                'reason_id'             => $reason_id,
                'reason_text'           => $reason_text,
                'reason_info'           => $reason_info,
                'display_name'          => $cuurent_user->display_name,
                'email'                 => get_option( 'admin_email' ),
                'website'               => get_site_url(),
                'blog_language'         => get_bloginfo( 'language' ),
                'wordpress_version'     => get_bloginfo( 'version' ),
                'php_version'           => PHP_VERSION ,
            );
                        
            $to      = 'info@wpguru.co';
            $subject = 'Plugin Uninstallation';
            
            $body    = '<p>Plugin Name: ' . ELVWP_NAME . '</p>';
            $body   .= '<p>Plugin Version: ' . ELVWP_VER . '</p>';
            $body   .= '<p>Reason: ' . $reason_text . '</p>';
            $body   .= '<p>Reason Info: ' . $reason_info . '</p>';
            $body   .= '<p>Admin Name: ' . $cuurent_user->display_name . '</p>';
            $body   .= '<p>Admin Email: ' . get_option( 'admin_email' ) . '</p>';
            $body   .= '<p>Website: ' . get_site_url() . '</p>';
            $body   .= '<p>Website Language: ' . get_bloginfo( 'language' ) . '</p>';
            $body   .= '<p>Wordpress Version: ' . get_bloginfo( 'version' ) . '</p>';
            $body   .= '<p>PHP Version: ' . PHP_VERSION . '</p>';
            
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
        public function elvwp_error_log_action_links( $links, $file ) {
            
            static $this_plugin;
            
            if ( empty( $this_plugin ) ) {
                
                $this_plugin = $this->elvwp_permalink . '/error-log-viewer-wp.php';
            }
            
            if ( $file == $this_plugin ) {
                
                $settings_link = sprintf( esc_html__( '%1$s Log Viewer %2$s', 'error-log-viewer-wp' ), '<a href="' . admin_url( 'admin.php?page=' . $this->elvwp_permalink ) . '">', '</a>' );
                
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
        
        public function elvwp_add_action_links( $plugin_meta, $plugin_file ) {
            if ( $plugin_file == plugin_basename( __FILE__ ) ) {
                
                $plugin_meta_str = '<a href="' . ELVWP_SUPPORT_URL . '" target="_blank">' . __( 'Support', 'error-log-viewer-wp' ) . '</a>';
                array_push( $plugin_meta, $plugin_meta_str );

                $plugin_meta_str = '<a href="' . ELVWP_REVIEW_URL . '" target="_blank">' . __( 'Post Review', 'error-log-viewer-wp' ) . '</a>';
                array_push( $plugin_meta, $plugin_meta_str );
            }
            return $plugin_meta;
        }

        /**
         * Ask the user to leave a review for the plugin.
         */
        public function elvwp_notice_review() {
            global $current_user;
            wp_get_current_user();

            $user_n = '';
            
            if ( ! empty( $current_user->display_name ) ) {
                $user_n = ' ' . $current_user->display_name;
            }
            
            echo '<div id="elvwp-review" class="notice notice-info is-dismissible"><p>' . sprintf( __( 'Hi%s, Thank you for using <b>' . ELVWP_NAME . '</b>. Please don\'t forget to rate our plugin. We sincerely appreciate your feedback.', 'error-log-viewer-wp' ), esc_html( $user_n ) ) . '<br><a target="_blank" href="' . ELVWP_REVIEW_URL . '" class="button-secondary">' . esc_html__( 'Post Review', 'error-log-viewer-wp' ) . '</a>' . '</p></div>';
        }
        
        /**
         * Loads the inline script to dismiss the review notice.
         */
        public function elvwp_notice_review_script() {
            echo "<script>\n" . "jQuery(document).on('click', '#elvwp-review .notice-dismiss', function() {\n" . "\tvar elvwp_review_data = {\n" . "\t\taction: 'elvwp_review_notice',\n" . "\t};\n" . "\tjQuery.post(ajaxurl, elvwp_review_data, function(response) {\n" . "\t\tif (response) {\n" . "\t\t\tconsole.log(response);\n" . "\t\t}\n" . "\t});\n" . "});\n" . "</script>\n";
        }
        
        /**
         * Disables the notice about leaving a review.
         */
        public function elvwp_dismiss_review_notice() {
            update_option( 'elvwp_dismiss_review_notice', true, false );
            wp_die();
        }

        /**
         * Modify the footer text inside of the WordPress admin area.
         *
         * @since 1.0.0
         *
         * @param string $text  The default footer text.
         * @return string $text Amended footer text.
         */
        public function elvwp_admin_footer_text( $text ) {
            return __( 'If you like <strong><ins>' . ELVWP_NAME . '</ins></strong> please leave us a <a target="_blank" style="color:#f9b918" href="' . ELVWP_REVIEW_URL . '">★★★★★</a> rating. A huge thank you in advance!', 'error-log-viewer-wp' );
        }
        
    }
} // End if class_exists check

/**
 * The main function responsible for returning the one true Error_Log_Viewer_WP
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      \Error_Log_Viewer_WP The one true Error_Log_Viewer_WP
 *
 * @todo        Inclusion of the activation code below isn't mandatory, but
 *              can prevent any number of errors, including fatal errors, in
 *              situations where your extension is activated but EDD is not
 *              present.
 */
function elvwp_load() {
    
    if ( is_admin() ) {
        require_once plugin_dir_path( __FILE__ ) . 'includes/functions.php';
    }

    return Error_Log_Viewer_WP::instance();
}
add_action( 'plugins_loaded', 'elvwp_load' );

/**
 * The activation hook is called outside of the singleton because WordPress doesn't
 * register the call from within the class, since we are preferring the plugins_loaded
 * hook for compatibility, we also can't reference a function inside the plugin class
 * for the activation function. If you need an activation function, put it here.
 *
 * @since       1.0.0
 * @return      void
 */

function elvwp_activation() {
    /* Activation functions here */
}

register_activation_hook( __FILE__, 'elvwp_activation' );
