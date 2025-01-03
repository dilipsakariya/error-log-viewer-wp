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
 * Version:           1.0.5
 * Plugin URI:        https://wordpress.org/plugins/error-log-viewer-wp/
 * Description:       Error Log Viewer plugin offers a user-friendly way to view and analyze PHP error logs. Easy to monitor distinct error log entries which helps to solve all errors quickly.
 * Author:            WP Guru
 * Author URI:        https://wpguru.co
 * License:           GPL-3.0+
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       error-log-viewer-wp
 * Requires at least: 5.4
 * Tested up to:      6.7.1
 * Domain Path:       /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
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

		/**
		 * Plugin Data table.
		 *
		 * @var string
		 */
		protected $elvwp_error_logs;


		public function __construct() {

			self::$wp_config_path = $this->get_wp_config_path();

			$this->elvwp_permalink  = 'error-log-viewer-wp';
			$this->log_directory    = WP_CONTENT_DIR . '/uploads/' . $this->elvwp_permalink;
			$this->elvwp_error_logs = 'elvwp_error_logs';

			$elvwp_log_directory_htaccess = $this->log_directory . '/.htaccess';
			$elvwp_log_directory_index    = $this->log_directory . '/index.php';

			$this->check_config_is_writable();

			if ( ! is_dir( $this->log_directory ) ) {
				mkdir( $this->log_directory );
			}

			if ( ! file_exists( $elvwp_log_directory_index ) ) {
				$elvwp_file_index = fopen( $elvwp_log_directory_index, 'w' );

				$txt = "<?php // Exit if accessed directly.
					if ( ! defined( 'ABSPATH' ) ) {
					exit;
					}
					";

				fwrite( $elvwp_file_index, $txt );
				fclose( $elvwp_file_index );
			}

			if ( ! file_exists( $elvwp_log_directory_htaccess ) ) {
				$elvwp_log_directory_htaccess = fopen( $elvwp_log_directory_htaccess, 'w' );
				$rule                         = 'RewriteCond %{REQUEST_FILENAME} -s
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
				self::$instance->includes();
				self::$instance->load_textdomain();
				self::$instance->hooks();

				$elvwp_version = get_option( 'elvwp_current_version', '0.0.0' );

				if ( version_compare( $elvwp_version, ELVWP_VER, '<' ) ) {
					self::$instance->elvwp_call_install();
				}

				self::$instance->elvwp_error_log_details();

				Elvwp_Dashboard_Widget::elvwp_get_instance();
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
			define( 'ELVWP_VER', '1.0.2' );

			// Plugin name
			define( 'ELVWP_NAME', 'Error Log Viewer By WP Guru' );

			// Plugin path
			define( 'ELVWP_DIR', plugin_dir_path( __FILE__ ) );

			// Plugin URL
			define( 'ELVWP_URL', plugin_dir_url( __FILE__ ) );

			// Plugin File
			define( 'ELVWP_FILE', __FILE__ );

			define( 'ELVWP_SUPPORT_URL', 'https://wordpress.org/support/plugin/' . $this->elvwp_permalink );
			
			define( 'ELVWP_SUPPORT_URL_GURU', 'https://wpguru.co/emergency-wordpress-support/' );

			define( 'ELVWP_REVIEW_URL', 'https://wordpress.org/support/view/plugin-reviews/' . $this->elvwp_permalink . '?filter=5' );

			define( 'ELVWP_DEBUG_LOGFOLDER', $this->log_directory );
		}

		/**
		 * Load plugin files
		 *
		 * @access      private
		 * @since       1.0.0
		 * @return      void
		 */
		private function includes() {
			require_once ELVWP_DIR . 'includes/class-elvwp-dashboard-widget.php';
		}

		/**
		 * Plugin check for update processes
		 * checks to see if there are any update procedures to be run, and if
		 * so runs them
		 *
		 * @access      private
		 * @since       1.0.0
		 * @return      void
		 */
		private function elvwp_call_install() {

			$version = get_option( 'elvwp_current_version', false );

			if ( ! $version ) {
				$this->elvwp_new_install();
			} elseif ( version_compare( $version, '1.0.1.4', '<' ) ) {
				$this->elvwp_v1014_upgrades();
			}

			update_option( 'elvwp_current_version', ELVWP_VER );
		}

		/**
		 * New Plugin Install routine
		 * This function installs all of the default
		 * so runs them
		 *
		 * @access      private
		 * @since       1.0.0
		 * @return      void
		 */
		private function elvwp_new_install() {
			$admin_email = get_option( 'admin_email' );
			$emails      = array( $admin_email );
			$emails      = array_map( 'trim', $emails );
			update_option( 'elvwp_notification_status', 1 );
			update_option( 'elvwp_notification_emails', $emails );
			update_option( 'elvwp_frequency', 'weekly' );
		}

		/**
		 * Plugin update routine
		 * This function installs all of the default
		 * so runs them
		 *
		 * @access      private
		 * @since       1.0.1.4
		 * @return      void
		 */
		private function elvwp_v1014_upgrades() {
			$admin_email = get_option( 'admin_email' );
			$emails      = array( $admin_email );
			$emails      = array_map( 'trim', $emails );
			update_option( 'elvwp_notification_status', 1 );
			update_option( 'elvwp_notification_emails', $emails );
			update_option( 'elvwp_frequency', 'weekly' );
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
			$elvwp_table = esc_sql( $wpdb->prefix . $this->elvwp_error_logs );

			// create database table
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( $wpdb->get_var( $wpdb->prepare( 'show tables like %s', $elvwp_table ) ) !== $elvwp_table ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
				$sql = 'CREATE TABLE ' . $elvwp_table . ' (
				  `id` INT(11) NOT NULL AUTO_INCREMENT,
				  `file_name` varchar(100) NOT NULL,
				  `details` text NOT NULL,
				  `log_path` text NOT NULL,
				  `created_at` date NOT NULL,
				   PRIMARY KEY (id)
				);';

				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
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
			$lang_dir = ELVWP_DIR . '/languages/';
			$lang_dir = apply_filters( 'error_log_viewer_wp_languages_directory', $lang_dir );

			// Traditional WordPress plugin locale filter
			$locale = apply_filters( 'plugin_locale', get_locale(), 'error-log-viewer-wp' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'error-log-viewer-wp', $locale );

			// Setup paths to current locale file
			$mofile_local  = $lang_dir . $mofile;
			$mofile_global = WP_LANG_DIR . '/elvwp/' . $mofile;

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
		 */
		private function hooks() {

			// AJAX action hook to disable the 'review request' notice.
			add_action(
				'wp_ajax_elvwp_review_notice',
				array(
					$this,
					'elvwp_dismiss_review_notice',
				)
			);

			if ( ! get_option( 'elvwp_review_time' ) ) {
				$review_time = time() + 7 * DAY_IN_SECONDS;
				add_option( 'elvwp_review_time', $review_time, '', false );
			}

			add_filter( 'cron_schedules', array( $this, 'elvwp_cron_schedules' ) );
			add_action(
				'elvwp_cron_task_hook_notification_time',
				array(
					$this,
					'elvwp_cron_function_notification_time',
				)
			);

			if ( is_admin() && get_option( 'elvwp_review_time' ) && get_option( 'elvwp_review_time' ) < time() && ! get_option( 'elvwp_dismiss_review_notice' ) ) {
				add_action(
					'admin_notices',
					array(
						$this,
						'elvwp_notice_review',
					)
				);
			}

			add_filter(
				'admin_footer_text',
				array(
					$this,
					'elvwp_admin_footer_text',
				)
			);

			add_action(
				'plugin_row_meta',
				array(
					$this,
					'elvwp_add_action_links',
				),
				10,
				2
			);
			add_action(
				'admin_footer',
				array(
					$this,
					'elvwp_add_deactive_modal',
				)
			);
			add_action(
				'wp_ajax_elvwp_error_log_deactivation',
				array(
					$this,
					'elvwp_error_log_deactivation',
				)
			);
			add_action(
				'plugin_action_links',
				array(
					$this,
					'elvwp_error_log_action_links',
				),
				10,
				2
			);

			if ( is_admin() ) {

				add_action(
					'admin_menu',
					array(
						$this,
						'elvwp_plugin_menu',
					)
				);
				add_action(
					'init',
					array(
						$this,
						'elvwp_log_init',
					)
				);
				add_action(
					'wp_ajax_elvwp_log_download',
					array(
						$this,
						'elvwp_log_download',
					)
				);
				add_action(
					'wp_before_admin_bar_render',
					array(
						$this,
						'elvwp_register_admin_bar',
					)
				);
				add_action(
					'wp_ajax_elvwp_purge_log',
					array(
						$this,
						'elvwp_purge_log',
					)
				);
				add_action(
					'wp_ajax_elvwp_datatable_loglist',
					array(
						$this,
						'elvwp_datatable_loglist',
					)
				);
				add_action(
					'wp_ajax_elvwp_datatable_delete_data',
					array(
						$this,
						'elvwp_datatable_delete_data',
					)
				);

				add_action(
					'wp_ajax_elvwp_datatable_delete_all_logs',
					array(
						$this,
						'elvwp_datatable_delete_all_logs',
					)
				);
			}
		}

		/**
		 * cronjob scheduler.
		 *
		 * @since 1.0.2
		 * @access public
		 *
		 * @return array
		 */
		public function elvwp_cron_schedules( $schedules ) {
			$elvwp_notification_status = get_option( 'elvwp_notification_status' );
			$elvwp_frequency           = get_option( 'elvwp_frequency' );

			if ( ! empty( $elvwp_frequency ) && ! empty( $elvwp_notification_status ) ) {
				$cron_time = 0;

				if ( 'daily' === $elvwp_frequency ) {
					$cron_time = ( 24 * 60 * 60 );
				} elseif ( 'weekly' === $elvwp_frequency ) {
					$cron_time = ( 7 * 24 * 60 * 60 );
				} elseif ( 'monthly' === $elvwp_frequency ) {
					$cron_time = ( 30 * 24 * 60 * 60 );
				}

				if ( ! empty( $cron_time ) ) {
					$schedules['elvwp_notification_time'] = array(
						'interval' => $cron_time,
						'display'  => __( 'Once every ' ) . $elvwp_frequency,
					);
				}
			}
			return $schedules;
		}

		/**
		 * cronjob scheduler run method.
		 *
		 * send reminder account
		 * coming in elvwp.
		 *
		 * @since 1.0.9
		 * @access public
		 *
		 * @return void
		 */
		public function elvwp_cron_function_notification_time() {
			global $wpdb;

			$elvwp_table               = esc_sql( $wpdb->prefix . $this->elvwp_error_logs );
			$elvwp_notification_status = get_option( 'elvwp_notification_status' );
			$elvwp_frequency           = get_option( 'elvwp_frequency' );
			$emails                    = get_option( 'elvwp_notification_emails' );

			if ( ! empty( $elvwp_frequency ) && ! empty( $elvwp_notification_status ) && ! empty( $emails ) ) {

				if ( 'daily' === $elvwp_frequency ) {
					$interval_day = 1;
				} elseif ( 'weekly' === $elvwp_frequency ) {
					$interval_day = 7;
				} elseif ( 'monthly' === $elvwp_frequency ) {
					$interval_day = 30;
				}

				$from_date = date( 'Y-m-d', strtotime( '-' . $interval_day . ' days' ) );
				$end_date  = date( 'Y-m-d' );

				$from_name        = get_option( 'from_name', get_bloginfo( 'name' ) );
				$from_email       = get_option( 'admin_email', get_bloginfo( 'admin_email' ) );
				$elvwp_table_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$elvwp_table} where created_at > %s AND created_at <= %s", $from_date, $end_date ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

				if ( $elvwp_table_data ) {
					$reports = array();

					foreach ( $elvwp_table_data as $elvwp_tablekey => $elvwp_tablevalue ) {
						$log_details = unserialize( $elvwp_tablevalue->details );

						if ( $log_details ) {

							foreach ( $log_details as $logkey => $logvalue ) {

								if ( is_array( $logvalue ) ) {

									foreach ( $logvalue as $folderkey => $foldervalue ) {

										if ( is_array( $foldervalue ) ) {

											foreach ( $foldervalue as $ekey => $evalue ) {

												if ( isset( $reports[ $logkey ][ $folderkey ][ $ekey ] ) ) {
													$reports[ $logkey ][ $folderkey ][ $ekey ] += $evalue;
												} else {
													$reports[ $logkey ][ $folderkey ][ $ekey ] = $evalue;
												}
											}
										} elseif ( isset( $reports[ $logkey ][ $folderkey ] ) ) {

												$reports[ $logkey ][ $folderkey ] += $foldervalue;
										} else {
											$reports[ $logkey ][ $folderkey ] = $foldervalue;
										}
									}
								} elseif ( isset( $reports[ $logkey ] ) ) {

										$reports[ $logkey ] += $logvalue;
								} else {
									$reports[ $logkey ] = $logvalue;
								}
							}
						}
					}

					if ( $reports ) {
						$report_list = '';

						foreach ( $reports as $rkey => $rvalue ) {

							if ( 'plugin' === $rkey || 'theme' === $rkey || 'other' === $rkey ) {

								if ( is_array( $rvalue ) ) {
									$error_count_str = '';

									foreach ( $rvalue as $subkey => $subvalue ) {

										if ( is_array( $subvalue ) ) {
											$error_count_log_str = '';

											foreach ( $subvalue as $sublogkey => $sublogvalue ) {
												$error_count_log_str .= '<tr>
													<td class="column column-1" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="66.66666666666667%">
														<table border="0" cellpadding="0" cellspacing="0" class="text_block block-2" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
															<tr>
																<td class="pad" style="padding-bottom:5px;padding-left:35px;padding-right:20px;padding-top:15px;">
																	<div style="font-family: Arial, sans-serif">
																		<div class="" style="font-size: 12px; font-family: \'Roboto Slab\', Arial, \'Helvetica Neue\', Helvetica, sans-serif; mso-line-height-alt: 18px; color: #3a4848; line-height: 1.5;">
																			<p style="margin: 0; font-size: 14px; text-align: left; mso-line-height-alt: 22.5px; margin-left: 10px">
																				<span style="font-size:15px;">
																					' . ucfirst( $sublogkey ) . '
																				</span>
																			</p>
																			<p style="margin: 0; font-size: 14px; text-align: left; mso-line-height-alt: 18px;">
																			</p>
																		</div>
																	</div>
																</td>
															</tr>
														</table>
													</td>
													<td class="column column-2" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="33.333333333333336%">
														<table border="0" cellpadding="0" cellspacing="0" class="text_block block-2" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
															<tr>
																<td class="pad" style="padding-bottom:5px;padding-left:35px;padding-right:20px;padding-top:15px;">
																	<div style="font-family: Arial, sans-serif">
																		<div class="" style="font-size: 12px; font-family: \'Oswald\', Arial, \'Helvetica Neue\', Helvetica, sans-serif; mso-line-height-alt: 18px; color: #3a4848; line-height: 1.5;">
																			<p style="margin: 0; font-size: 14px; text-align: left; mso-line-height-alt: 24px;">
																				<span style="font-size:16px;">
																					' . $sublogvalue . '
																				</span>
																			</p>
																			<p style="margin: 0; font-size: 14px; text-align: left; mso-line-height-alt: 18px;">
																			</p>
																		</div>
																	</div>
																</td>
															</tr>
														</table>
													</td>
												</tr>';
											}
										}

										$error_count_str .= '<table align="center" border="0" cellpadding="0" cellspacing="0" class="row-content stack" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #ffffff; color: #000000; width: 680px;" width="680">
											<tbody>
												<tr>
													<td class="pad" style="padding-bottom:5px;padding-left:35px;padding-right:20px;padding-top:15px;">
														<p style="margin: 0; font-size: 14px; text-align: left; mso-line-height-alt: 22.5px; line-height: 1.5;font-family: \'Roboto Slab\', Arial, \'Helvetica Neue\', Helvetica, sans-serif; margin-left: 5px;">
															<span style="font-size:15px;">
																' . $subkey . '
															</span>
														</p>
													</td>
												</tr>
												' . $error_count_log_str . '
											</tbody>
										</table>';
									}
								}
								$report_list .= '<tr>
									<td>
										<table align="center" border="0" cellpadding="0" cellspacing="0" class="row-content stack" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #ffffff; color: #000000; width: 680px;" width="680">
											<tr>
												<td class="pad" style="padding-bottom:5px;padding-left:35px;padding-right:20px;padding-top:15px;">
													<p style="margin: 0; font-size: 14px; text-align: left; mso-line-height-alt: 30px;">
														<span style="font-size:20px;color:#44b4b8;">
															' . ucfirst( $rkey ) . '
														</span>
													</p>
												</td>
											</tr>
										</table>
										' . $error_count_str . '
									</td>
								</tr>';
							}
						}
						$message_to_send = file_get_contents( ELVWP_DIR . 'templates/email.html' );
						$message_to_send = str_replace( '{{head_image}}', ELVWP_URL . 'templates/images/job.png', $message_to_send );
						$message_to_send = str_replace( '{{error_log_count_list}}', $report_list, $message_to_send );

						$subject = 'Error Log Report';

						$headers   = array();
						$headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
						$headers[] = 'Reply-To: {' . $from_email . "}\r\n";
						$headers[] = 'Content-Type: text/html; charset=UTF-8';
						$issend    = wp_mail( $emails, $subject, $message_to_send, $headers );
					}
				}
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

		private function check_config_is_writable() {

			if ( ! is_writable( self::$wp_config_path ) ) {
				echo '<div class="error notice is-dismissible"><p>';
				echo wp_kses_post( __( 'The <strong>Error Log Viewer</strong> plugin must have a <code>wp-config.php</code> file that is writable by the filesystem.', 'error-log-viewer-wp' ) );
				echo '</p></div>';

				return false;
			}
		}

		/**
		 * Error Log Details
		 *
		 * @access      public
		 * @since       1.0.0
		 * @return      void
		 */
		public function elvwp_error_log_details( $log_date = '' ) {
			global $wpdb;

			$count           = 1;
			$wp_empty_folder = ( count( glob( $this->log_directory . '/*' ) ) === 0 ) ? 'Empty' : 'Not empty';

			if ( 'Empty' === $wp_empty_folder ) {
				$elvwp_table = esc_sql( $wpdb->prefix . $this->elvwp_error_logs );

				if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', '%' . $elvwp_table . '%' ) ) === $elvwp_table ) {
					$wpdb->query( "TRUNCATE TABLE $elvwp_table" );
				}
			}

			if ( is_dir( $this->log_directory ) ) {
				$scanned_directory = array_diff(
					scandir( $this->log_directory, 1 ),
					array(
						'..',
						'.',
					)
				);
				$elvwp_table       = esc_sql( $wpdb->prefix . $this->elvwp_error_logs );

				if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', '%' . $elvwp_table . '%' ) ) === $elvwp_table ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

					foreach ( $scanned_directory as $key => $value ) {
						$count       = 1;
						$file_name   = $value;
						$elvwp_table = esc_sql( $wpdb->prefix . $this->elvwp_error_logs );

						$elvwp_table_data = $wpdb->get_results( "SELECT * FROM {$elvwp_table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

						foreach ( $elvwp_table_data as $elvwp_tablekey => $elvwp_tablevalue ) {
							$elvwp_database_file = $elvwp_tablevalue->file_name;

							if ( $elvwp_database_file === $file_name ) {
								$count = 0;
							} else {
								$elvwp_checkfile = $this->log_directory . '/' . $elvwp_database_file;
							}
						}

						if ( 1 === $count ) {
							$current_date          = date( 'Y-m-d', strtotime( substr( $file_name, 4, 11 ) ) );
							$elvwp_coneverted_date = date( 'd-M-Y', strtotime( $current_date ) );
							$log_details           = $this->elvwp_log_details( $elvwp_coneverted_date );

							if ( $log_details ) {
								$elvwp_serialize_data = serialize( $log_details['typecount'] );
								$log_path             = $log_details['error_log'];

								if ( '.htaccess' !== $file_name && 'index.php' !== $file_name ) {
									$data   = array(
										'file_name'  => $file_name,
										'details'    => $elvwp_serialize_data,
										'created_at' => $current_date,
										'log_path'   => $log_path,
									);
									$format = array(
										'%s',
										'%s',
										'%s',
										'%s',
									);
									$wpdb->insert( $elvwp_table, $data, $format ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
								}
							}
						}

						if ( 0 === $count ) {
							$current_date          = date( 'Y-m-d', strtotime( substr( $file_name, 4, 11 ) ) );
							$elvwp_coneverted_date = date( 'd-M-Y', strtotime( $current_date ) );
							$log_details           = $this->elvwp_log_details( $elvwp_coneverted_date );
							$elvwp_serialize_data  = serialize( $log_details['typecount'] );
							$log_path              = $log_details['error_log'];

							if ( '.htaccess' !== $file_name && 'index.php' !== $file_name ) {
								$data          = array(
									'details'    => $elvwp_serialize_data,
									'created_at' => $current_date,
									'log_path'   => $log_path,
								);
								$format        = array(
									'%s',
									'%s',
									'%s',
								);
								$wherefilename = array(
									'file_name' => $file_name,
								);

								$wpdb->update( $elvwp_table, $data, $wherefilename, $format ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							}
						}
					}
				}
			}

			$elvwp_table = esc_sql( $wpdb->prefix . $this->elvwp_error_logs );

			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', '%' . $elvwp_table . '%' ) ) === $elvwp_table ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

				$elvwp_table_data = $wpdb->get_results( "SELECT file_name FROM {$elvwp_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

				foreach ( $elvwp_table_data as $key => $value ) {
					$elvwp_database_file = $value->file_name;
					$elvwp_checkfile     = $this->log_directory . '/' . $elvwp_database_file;

					if ( ! file_exists( $elvwp_checkfile ) ) {
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->delete(
							$elvwp_table,
							array(
								'file_name' => $elvwp_database_file,
							)
						);
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
		 */
		public function elvwp_log_download() {

			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( isset( $_POST['elvwp_error_log_download'] ) && isset( $_POST['elvwp_error_log'] ) && ! empty( sanitize_text_field( wp_unslash( $_POST['elvwp_error_log'] ) ) ) ) {
				// Check if the user has the appropriate capability
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'error-log-viewer-wp' ) );
				}
			
				// Verify the nonce
				if ( ! isset( $_POST['elvwp_log_download_nonce'] ) || ! wp_verify_nonce( $_POST['elvwp_log_download_nonce'], 'elvwp_log_download_nonce' ) ) {
					wp_die( esc_html__( 'Nonce verification failed.', 'error-log-viewer-wp' ) );
				}

				$elvwp_file = sanitize_text_field( wp_unslash( $_POST['elvwp_error_log'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				
				try {
					$filename = basename( $elvwp_file );
					header( 'Pragma: public' );
					header( 'Expires: 0' );
					header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
					header( 'Content-Type: application/octet-stream' );
					header( 'Content-Disposition: attachment; filename=' . $filename );
					header( 'Content-Transfer-Encoding: binary' );
					header( 'Content-Length: ' . filesize( $elvwp_file ) );
					flush();
					readfile( $elvwp_file );
					exit;
				} catch ( Exception $e ) {
					throw $e->getMessage() . ' @ ' . $e->getFile() . ' - ' . $e->getLine();
				}
			}

			if ( isset( $_POST['elvwp_datatable_log_download'] ) && isset( $_POST['elvwp_datatable_downloadid'] ) && ! empty( sanitize_text_field( wp_unslash( $_POST['elvwp_datatable_downloadid'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				// Check if the user has the appropriate capability
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'error-log-viewer-wp' ) );
				}
			
				// Verify the nonce
				if ( ! isset( $_POST['elvwp_log_download_nonce'] ) || ! wp_verify_nonce( $_POST['elvwp_log_download_nonce'], 'elvwp_log_download_nonce' ) ) {
					wp_die( esc_html__( 'Nonce verification failed.', 'error-log-viewer-wp' ) );
				}
				
				global $wpdb;
				$elvwp_table                = esc_sql( $wpdb->prefix . $this->elvwp_error_logs );
				$elvwp_datatable_downloadid = sanitize_text_field( wp_unslash( $_POST['elvwp_datatable_downloadid'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

				$elvwp_download_table_data = $wpdb->get_col( $wpdb->prepare( "SELECT file_name from {$elvwp_table} where id=%d", $elvwp_datatable_downloadid ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

				foreach ( $elvwp_download_table_data as $key => $value ) {
					$ps_download_filename = $value;
				}

				$elvwp_download_filepath = $this->log_directory . '/' . $ps_download_filename;

				try {
					$filename = basename( $elvwp_download_filepath );
					header( 'Pragma: public' );
					header( 'Expires: 0' );
					header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
					header( 'Content-Type: application/octet-stream' );
					header( 'Content-Disposition: attachment; filename=' . $filename );
					header( 'Content-Transfer-Encoding: binary' );
					header( 'Content-Length: ' . filesize( $elvwp_download_filepath ) );
					flush();
					readfile( $elvwp_download_filepath );
					exit;
				} catch ( Exception $e ) {
					throw $e->getMessage() . ' @ ' . $e->getFile() . ' - ' . $e->getLine();
				}
			}
		}

		/**
		 * To set config var
		 *
		 * @access      public
		 * @since       1.0.0
		 * @return      void
		 */
		public function elvwp_set_config_variables() {

			if ( ! class_exists( 'WP_Config_Transformer' ) ) {
				require_once ELVWP_DIR . 'includes/class-wp-config-transformer.php';
			}

			$config_transformer = new WP_Config_Transformer( self::$wp_config_path );

			if ( ( defined( WP_DEBUG_LOG ) && false === WP_DEBUG_LOG ) || ! defined( WP_DEBUG_LOG ) ) {

				if ( $config_transformer->exists( 'constant', 'WP_DEBUG_LOG' ) ) {
					$config_transformer->update( 'constant', 'WP_DEBUG_LOG', true );
				} else {
					$config_transformer->add( 'constant', 'WP_DEBUG_LOG', true );
				}
			}

			$error_log = $this->log_directory . '/log-' . date( 'd-M-Y' ) . '.log';

			if ( $config_transformer->exists( 'inivariable', 'log_errors' ) ) {

				if ( 'On' !== $config_transformer->get_value( 'inivariable', 'log_errors' ) ) {
					$config_transformer->update( 'inivariable', 'log_errors', 'On' );
				}
			} else {
				$config_transformer->add( 'inivariable', 'log_errors', 'On' );
			}

			if ( $config_transformer->exists( 'inivariable', 'error_log' ) ) {

				if ( $error_log !== $config_transformer->get_value( 'inivariable', 'error_log' ) ) {
					$config_transformer->update( 'inivariable', 'error_log', $error_log );
				}
			} else {
				$config_transformer->add( 'inivariable', 'error_log', $error_log );
			}
		}

		/**
		 * To call innit
		 *
		 * @access      public
		 * @since       1.0.0
		 * @return      void
		 */
		public function elvwp_log_init() {
			$this->elvwp_set_config_variables();
			$this->elvwp_log_download();
		}

		/**
		 * To delete Log
		 *
		 * @access      public
		 * @since       1.0.0
		 * @return      void
		 */
		public function elvwp_purge_log() {

			if ( ! isset( $_POST['elvwp_nonce'] ) ) {
				echo wp_json_encode(
					array(
						'success' => '0',
						'msg'     => __( 'Nonce verification failed, Please try again.', 'error-log-viewer-wp' ),
					)
				);

				wp_die();
			}

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['elvwp_nonce'] ) ), 'elvwp_purge_log_nonce' ) ) {
				echo wp_json_encode(
					array(
						'success' => '0',
						'msg'     => __( 'Nonce verification failed, Please try again.', 'error-log-viewer-wp' ),
					)
				);

				wp_die();
			}

			if ( is_admin() && isset( $_POST['elvwp_error_log'] ) && ! empty( sanitize_text_field( wp_unslash( $_POST['elvwp_error_log'] ) ) ) ) {
				$elvwp_error_log = sanitize_text_field( wp_unslash( $_POST['elvwp_error_log'] ) );

				if ( file_exists( $elvwp_error_log ) ) {

					wp_delete_file( $elvwp_error_log );

					echo wp_json_encode(
						array(
							'success' => '1',
							'msg'     => __( 'Log file was purged successfully.', 'error-log-viewer-wp' ),
						)
					);

					wp_die();
				} else {
					echo wp_json_encode(
						array(
							'success' => '0',
							'msg'     => __( 'Failed to purge Log file. Please try again after reloading page.', 'error-log-viewer-wp' ),
						)
					);

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
		 */
		public function elvwp_register_admin_bar() {

			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( isset( $_POST['date'] ) && ! empty( sanitize_text_field( wp_unslash( $_POST['date'] ) ) ) ) {
				$log_date = date( 'd-M-Y', strtotime( sanitize_text_field( wp_unslash( $_POST['date'] ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			} else {
				$log_date = date( 'd-M-Y' );
			}

			$log_details = $this->elvwp_log_details( $log_date );
			$total       = 0;

			if ( $log_details ) {
				$count     = 1;
				$error_log = $log_details['error_log'];
				$total     = $log_details['total'];

				if ( file_exists( $error_log ) ) {
					$elvwp_serialize_data = serialize( $log_details['typecount'] );
					$elvwp_primary_alert  = '<span class="elvwp-admin-bar-error-count"><strong>' . $total . ' </strong></span>';
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
			$wp_admin_bar->add_menu(
				array(
					'parent' => false,
					'id'     => 'elvwp_admin_bar',
					'title'  => $link_text . $elvwp_primary_alert,
					'href'   => admin_url( 'admin.php?page=' . $this->elvwp_permalink ),
					'meta'   => array(
						'title' => __( 'Error Log Viewer', 'error-log-viewer-wp' ),
					),
				)
			);
		}

		/**
		 * Log details By Date
		 *
		 * @access      public
		 * @since       1.0.0
		 * @return      array
		 */
		public function elvwp_log_details( $log_date = '', $is_raw_log = false, $line_limit = false ) {

			$error_log = $this->log_directory . '/log-' . $log_date . '.log';
			/**
			 * @var string|null Path to log cache - must be writable - null for no cache
			 */
			$cache = null;
			/**
			 * @var array Array of log lines
			 */
			$logs = array();
			/**
			 * @var array Array of log types
			 */
			$types = array();
			/**
			 * @var array Array of log typecount
			 */
			$typecount = array();

			/**
			 * https://gist.github.com/amnuts/8633684
			 */

			$total = 0;

			if ( null === $error_log ) {
				$error_log = ini_get( 'error_log' );
			}

			if ( file_exists( $error_log ) ) {

				$log_details = array();

				try {
					$log = new SplFileObject( $error_log );
					$log->setFlags( SplFileObject::DROP_NEW_LINE );
				} catch ( RuntimeException $e ) {
					// Nothing to return.
				}

				if ( null !== $cache && file_exists( $cache ) ) {
					$cache_data = unserialize( file_get_contents( $cache ) );
					extract( $cache_data );
					$log->fseek( $seek );
				}

				$prev_error = new stdClass();

				while ( ! $log->eof() ) {

					if ( preg_match( '/stack trace:$/i', $log->current() ) ) {
						$stack_trace = array();
						$parts       = array();
						$log->next();

						while ( ( preg_match( '!^\[(?P<time>[^\]]*)\] PHP\s+(?P<msg>\d+\. .*)$!', $log->current(), $parts ) || preg_match( '!^(?P<msg>#\d+ .*)$!', $log->current(), $parts ) && ! $log->eof() ) ) {
							array_push( $stack_trace, $parts['msg'] );
							$log->next();
						}

						if ( '#0' === substr( $stack_trace[0], 0, 2 ) ) {
							$stack_trace_str = $log->current();
							array_push( $stack_trace, $stack_trace_str );
							$log->next();
						}

						$prev_error->trace = join( "\n", $stack_trace );
					}

					$more = array();

					while ( ! preg_match( '!^\[(?P<time>[^\]]*)\] ((PHP|ojs2: )(?P<typea>.*?):|(?P<typeb>(WordPress|Error|ojs2|\w has produced)\s{1,}\w+ \w+))\s+(?P<msg>.*)$!', $log->current() ) && ! $log->eof() ) {
						$more_str = $log->current();
						array_push( $more, $more_str );
						$log->next();
					}

					if ( ! empty( $more ) ) {
						$prev_error->more = join( '\n', $more );
					}

					$parts = array();

					if ( preg_match( '!^\[(?P<time>[^\]]*)\] ((PHP|ojs2: )(?P<typea>.*?):|(?P<typeb>(WordPress|Error|ojs2|\w has produced)\s{1,}\w+ \w+))\s+(?P<msg>.*)$!', $log->current(), $parts ) ) {

						$parts['type'] = ( $parts['typea'] ? $parts['typea'] : $parts['typeb'] );

						if ( 'ojs2: ' === $parts[3] || 'ojs2' === $parts[6] ) {
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
								$data['line'] = intval( $subparts['line'] );
								$data['core'] = str_replace( $subparts['core'], '', $data['msg'] );
								$data['code'] = '';

								try {

									if ( isset( $data['line'] ) && 4 <= $data['line'] ) {
										$file = new SplFileObject( str_replace( 'zend.view://', '', $subparts['path'] ) );
										$file->seek( $data['line'] - 4 );
										$i = 7;

										do {
											$data['code'] .= $file->current();
											$file->next();
										} while ( --$i && ! $file->eof() );
									}
								} catch ( Exception $e ) {
									// Nothing to return.
								}
							}

							$err_type_folder = 'other';

							if ( strpos( $msg, '\wp-content\plugins' ) !== false ) {
								$err_type = 'plugin';
								$msg_arr  = explode( '\wp-content\plugins\\', $msg );

								if ( isset( $msg_arr[1] ) ) {
									$folders_arr     = explode( '\\', ltrim( $msg_arr[1], '\\' ) );
									$err_type_folder = isset( $folders_arr[0] ) ? $folders_arr[0] : '';
								}
							} elseif ( strpos( $msg, '/wp-content/plugins' ) !== false ) {
								$err_type = 'plugin';
								$msg_arr  = explode( '/wp-content/plugins/', $msg );

								if ( isset( $msg_arr[1] ) ) {
									$folders_arr     = explode( '/', ltrim( $msg_arr[1], '/' ) );
									$err_type_folder = isset( $folders_arr[0] ) ? $folders_arr[0] : '';
								}
							} elseif ( strpos( $msg, '\wp-content\themes' ) !== false ) {
								$err_type = 'theme';
								$msg_arr  = explode( '\wp-content\themes\\', $msg );

								if ( isset( $msg_arr[1] ) ) {
									$folders_arr     = explode( '\\', ltrim( $msg_arr[1], '\\' ) );
									$err_type_folder = isset( $folders_arr[0] ) ? $folders_arr[0] : '';
								}
							} elseif ( strpos( $msg, '/wp-content/themes' ) !== false ) {
								$err_type = 'theme';
								$msg_arr  = explode( '/wp-content/themes/', $msg );

								if ( isset( $msg_arr[1] ) ) {
									$folders_arr     = explode( '/', ltrim( $msg_arr[1], '/' ) );
									$err_type_folder = isset( $folders_arr[0] ) ? $folders_arr[0] : '';
								}
							} else {
								$err_type     = 'other';
								$base_path_er = trim( ABSPATH, '/' );
								$msg_arr      = explode( $base_path_er, $msg );

								if ( isset( $msg_arr[1] ) ) {
									$folders_arr     = explode( '\\', ltrim( $msg_arr[1], '\\' ) );
									$err_type_folder = isset( $folders_arr[0] ) ? $folders_arr[0] : '';
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

					if ( $line_limit && count( $logs ) >= $line_limit ) {
						break;
					}
				}

				if ( null !== $cache ) {
					$cache_data = serialize(
						array(
							'seek'      => $log->getSize(),
							'logs'      => $logs,
							'types'     => $types,
							'typecount' => $typecount,
						)
					);

					file_put_contents( $cache, $cache_data );
				}

				$log = null;

				$this->elvwp_error_log_osort(
					$logs,
					array(
						'last' => SORT_DESC,
					)
				);

				$total = count( $logs );
				ksort( $types );

				$log_details['typecount'] = $typecount;
				$log_details['error_log'] = $error_log;
				$log_details['total']     = $total;
				$log_details['logs']      = $logs;
				$log_details['types']     = $types;

				if ( $is_raw_log ) {
					// Return raw log
					$file                = file( $error_log );
					$log_details['file'] = $file;
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
		 */
		public function elvwp_plugin_menu() {

			$menu = add_menu_page(
				__( 'Error Log Viewer', 'error-log-viewer-wp' ),
				__( 'Error Log Viewer', 'error-log-viewer-wp' ),
				'manage_options',
				$this->elvwp_permalink,
				array(
					$this,
					'elvwp_error_by_date',
				)
			);

			add_submenu_page(
				$this->elvwp_permalink,
				__( 'Error Log Overview', 'error-log-viewer-wp' ),
				__( 'Error Log Overview', 'error-log-viewer-wp' ),
				'manage_options',
				$this->elvwp_permalink,
				array(
					$this,
					'elvwp_error_by_date',
				)
			);

			$list = add_submenu_page(
				$this->elvwp_permalink,
				__( 'Error Log List', 'error-log-viewer-wp' ),
				__( 'Error Log List', 'error-log-viewer-wp' ),
				'manage_options',
				'elvwp-list',
				array(
					$this,
					'elvwp_log_list_datatable',
				)
			);

			$list = add_submenu_page(
				$this->elvwp_permalink,
				__( 'Error Notification', 'error-log-viewer-wp' ),
				__( 'Error Log Notification', 'error-log-viewer-wp' ),
				'manage_options',
				'elvwp-notification',
				array(
					$this,
					'elvwp_log_list_notification',
				)
			);

			add_action(
				'admin_enqueue_scripts',
				array(
					$this,
					'elvwp_admin_enqueue',
				)
			);
		}

		public function elvwp_log_list_datatable() {
			require_once ELVWP_DIR . 'includes/error-log-list-template.php';
		}

		public function elvwp_log_list_notification() {
			require_once ELVWP_DIR . 'includes/error-log-notification-template.php';
		}

		public function elvwp_error_by_date() {
			require_once ELVWP_DIR . 'includes/error-log-viewer.php';
		}

		/**
		 * Admin Enqueue style and scripts
		 *
		 * @access      public
		 * @since       1.0.0
		 * @return      void
		 */
		public function elvwp_admin_enqueue( $hook ) {

			wp_enqueue_style( 'elvwp_error_log_admin_style', plugins_url( '/assets/css/admin.css', ELVWP_FILE ), array(), ELVWP_VER );

			if ( 'toplevel_page_error-log-viewer-wp' === $hook || 'error-log-viewer_page_elvwp-list' === $hook || 'error-log-viewer_page_elvwp-notification' === $hook ) {

				wp_enqueue_script(
					'elvwp_datatable',
					plugins_url( '/assets/js/jquery.dataTables.min.js', ELVWP_FILE ),
					array(
						'jquery',
					),
					ELVWP_VER,
					true
				);

				wp_register_style( 'elvwp_datatables_style', plugins_url( '/assets/css/jquery.dataTables.min.css', ELVWP_FILE ), array(), ELVWP_VER );
				wp_register_style( 'elvwp_ui_style', plugins_url( '/assets/css/datepicker.min.css', ELVWP_FILE ), array(), ELVWP_VER );

				wp_enqueue_style( 'elvwp_datatables_style' );
				wp_enqueue_style( 'elvwp_ui_style' );
			}

			if ( 'plugins.php' === $hook || 'toplevel_page_error-log-viewer-wp' === $hook || 'error-log-viewer_page_elvwp-list' === $hook || 'error-log-viewer_page_elvwp-notification' === $hook ) {

				wp_enqueue_script(
					'elvwp_admin_ui_script',
					plugins_url( '/assets/js/datepicker.min.js', ELVWP_FILE ),
					array(
						'jquery',
					),
					ELVWP_VER,
					true
				);

				wp_enqueue_script(
					'elvwp_admin_script',
					plugins_url( '/assets/js/admin.js', ELVWP_FILE ),
					array(
						'elvwp_admin_ui_script',
					),
					ELVWP_VER,
					true
				);

				wp_localize_script(
					'elvwp_admin_script',
					'ajax_script_object',
					array(
						'ajax_url'             => admin_url( 'admin-ajax.php' ),
						'date_format'          => elvwp_date_formate(),
						'date_format_php'      => get_option( 'date_format' ),
						'months'               => array(
							'01' => 'January',
							'02' => 'February',
							'03' => 'March',
							'04' => 'April',
							'05' => 'May',
							'06' => 'June',
							'07' => 'July',
							'08' => 'August',
							'09' => 'September',
							'10' => 'October',
							'11' => 'November',
							'12' => 'December',
						),
						'delete_data_nonce'    => wp_create_nonce( 'elvwp_delete_data_nonce' ),
						'purge_log_nonce'      => wp_create_nonce( 'elvwp_purge_log_nonce' ),
						'delete_all_log_nonce' => wp_create_nonce( 'elvwp_delete_all_log_nonce' ),
					)
				);

				wp_localize_script(
					'elvwp_admin_script',
					'datatable',
					array(
						'datatable_ajax_url' => admin_url( 'admin-ajax.php?action=elvwp_datatable_loglist' ),
					)
				);

				wp_localize_script( 'elvwp_admin_script', 'script_object', array() );

			}
		}

		public function elvwp_datatable_loglist() {
			global $wpdb;
			// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.NonceVerification.Missing
			$elvwp_table       = esc_sql( $wpdb->prefix . $this->elvwp_error_logs );
			$column_sort_order = sanitize_text_field( wp_unslash( $_POST['order'][0]['dir'] ) );
			$draw              = sanitize_text_field( wp_unslash( $_POST['draw'] ) );
			$row               = sanitize_text_field( wp_unslash( $_POST['start'] ) );
			$row_per_page      = sanitize_text_field( wp_unslash( $_POST['length'] ) ); // Rows display per page
			$column_index      = sanitize_text_field( wp_unslash( $_POST['order'][0]['column'] ) ); // Column index
			$column_name       = sanitize_text_field( wp_unslash( $_POST['columns'][ $column_index ]['data'] ) );
			// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.NonceVerification.Missing

			$elvwp_table_data = $wpdb->get_results( $wpdb->prepare( "SELECT * from {$elvwp_table} ORDER BY created_at {$column_sort_order} LIMIT %d,%d", $row, $row_per_page ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			$data        = array();
			$date_format = get_option( 'date_format' );

			if ( $elvwp_table_data ) {

				foreach ( $elvwp_table_data as $key => $value ) {
					$created_at     = $value->created_at;
					$elvwp_log_path = $value->log_path;
					$id             = $value->id;
					$filename       = $this->log_directory . '/' . $value->file_name;
					$elvwp_url      = add_query_arg( 'date', $created_at, admin_url( 'admin.php?page=' . $this->elvwp_permalink ) );
					$array_rewrite  = array();
					// Array with the md5 hashes
					$array = array();

					$array_hashes_main = unserialize( $value->details );

					$folder_wise = array( 'plugin', 'theme', 'other' );

					foreach ( $folder_wise as $ftype ) {
						// code...
						$array_hashes = isset( $array_hashes_main[ $ftype ] ) ? $array_hashes_main[ $ftype ] : array();

						if ( $array_hashes ) {

							$elvwp_output[ $ftype ] = implode(
								'',
								array_map(
									function( $v, $k ) use ( $created_at ) {

										if ( is_array( $v ) ) {

											return '<b>' . $k . '</b><br>' . implode(
												'',
												array_map(
													function( $v1, $k1 ) use ( $created_at ) {

														if ( is_array( $v1 ) ) {
															return '<div class="elvwp_datatable ' . $k1 . '">' . $k1 . '[]: ' . implode( '&' . $k1 . '[]: ', $v1 ) . '</div>';
														} else {

															$elvwp_date_url_array = array(
																'date' => $created_at,
																'type' => $k1,
															);
															$elvwp_error_type_url = add_query_arg( $elvwp_date_url_array, admin_url( 'admin.php?page=' . $this->elvwp_permalink ) );
															return '<div class="elvwp_datatable ' . $k1 . '"><a href="' . $elvwp_error_type_url . '">' . ucwords( $k1 . ': ' . $v1 ) . '</a></div>';
														}
													},
													$v,
													array_keys( $v )
												)
											);
										} else {

											$elvwp_date_url_array = array(
												'date' => $created_at,
												'type' => $k,
											);
											$elvwp_error_type_url = add_query_arg( $elvwp_date_url_array, admin_url( 'admin.php?page=' . $this->elvwp_permalink ) );
											return '<div class="elvwp_datatable ' . $k . '"><a href="' . $elvwp_error_type_url . '">' . ucwords( $k . ': ' . $v ) . '</a></div>';
										}
									},
									$array_hashes,
									array_keys( $array_hashes )
								)
							);
						} else {
							$elvwp_output[ $ftype ] = '';
						}
					}

					$elvwp_log_download_nonce = wp_create_nonce( 'elvwp_log_download_nonce' );
					$button = '<div class="elvwp_datatable_ajaxbutton"><form method="post"><button type="button" onclick="location.href = \'' . $elvwp_url . '\';" id="elvwp_datatable_view" ><i class="dashicons dashicons-text-page view"></i></button><button class="elvwp_datatable_delete" id="' . $id . '"><i class="dashicons dashicons-trash"></i></button><input type="hidden" name="elvwp_datatable_downloadid" value="' . $id . '"><input type="hidden" name="elvwp_log_download_nonce" value="' . esc_attr( $elvwp_log_download_nonce ) . '"><button type="submit" name="elvwp_datatable_log_download" class="elvwp_datatable_log_download"><i class="dashicons dashicons-download"></i></button></form></div>';

					$data_ar = array(
						'created_at'     => date( $date_format, strtotime( $created_at ) ),
						'plugin'         => $elvwp_output['plugin'],
						'theme'          => $elvwp_output['theme'],
						'others'         => $elvwp_output['other'],
						'elvwp_log_path' => $elvwp_log_path,
						'action'         => $button,
					);
					array_push( $data, $data_ar );

					$total_record = $wpdb->get_var( "SELECT count(file_name) as filecount FROM {$elvwp_table}" );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

					$json_data = array(
						'draw'                 => intval( $draw ),
						'iTotalRecords'        => $total_record,
						'iTotalDisplayRecords' => $total_record,
						'data'                 => $data,
					);
				}
			} else {
				$data = array(
					'created_at' => 'No log',
					'plugin'     => 'No log',
					'theme'      => 'No log',
					'others'     => 'No log',
					'action'     => 'No log',
				);

				$json_data = array(
					'draw'                 => intval( $draw ),
					'iTotalRecords'        => 0,
					'iTotalDisplayRecords' => 0,
					'data'                 => $data,
				);
			}

			echo wp_json_encode( $json_data );
			wp_die();
		}

		public function elvwp_datatable_delete_data() {

			if ( ! isset( $_POST['elvwp_nonce'] ) ) {
				echo wp_json_encode(
					array(
						'success' => '0',
						'msg'     => __( 'Nonce verification failed, Please try again.', 'error-log-viewer-wp' ),
					)
				);

				wp_die();
			}

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['elvwp_nonce'] ) ), 'elvwp_delete_data_nonce' ) ) {
				echo wp_json_encode(
					array(
						'success' => '0',
						'msg'     => __( 'Nonce verification failed, Please try again.', 'error-log-viewer-wp' ),
					)
				);

				wp_die();
			}

			if ( is_admin() && isset( $_POST['elvwp_datatable_deleteid'] ) && ! empty( sanitize_text_field( wp_unslash( $_POST['elvwp_datatable_deleteid'] ) ) ) ) {

				global $wpdb;
				$elvwp_table              = esc_sql( $wpdb->prefix . $this->elvwp_error_logs );
				$elvwp_datatable_deleteid = sanitize_text_field( wp_unslash( $_POST['elvwp_datatable_deleteid'] ) );

				$elvwp_table_data = $wpdb->get_col( $wpdb->prepare( "SELECT file_name from $elvwp_table where id=%d", $elvwp_datatable_deleteid ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

				if ( ! empty( $elvwp_table_data ) ) {

					foreach ( $elvwp_table_data as $value ) {
						$elvwp_datatable_filename = $this->log_directory . '/' . $value;
					}

					if ( file_exists( $elvwp_datatable_filename ) ) {
						$elvwp_datatable_basename = basename( $value );
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
						$elvwp_delete_data = $wpdb->delete(
							$elvwp_table,
							array(
								'file_name' => $elvwp_datatable_basename,
							)
						);

						if ( $elvwp_delete_data ) {

							wp_delete_file( $elvwp_datatable_filename );

							echo wp_json_encode(
								array(
									'success' => '1',
									'msg'     => __( 'Log file was purged successfully.', 'error-log-viewer-wp' ),
								)
							);

							wp_die();
						} else {
							echo wp_json_encode(
								array(
									'success' => '0',
									'msg'     => __( 'Failed to purge Log file.', 'error-log-viewer-wp' ),
								)
							);

							wp_die();
						}
					} else {
						echo wp_json_encode(
							array(
								'success' => '0',
								'msg'     => __( 'Failed to purge Log file.', 'error-log-viewer-wp' ),
							)
						);

						wp_die();
					}
				}
			}
		}

		public function elvwp_datatable_delete_all_logs() {

			if ( ! isset( $_POST['elvwp_nonce'] ) ) {
				echo wp_json_encode(
					array(
						'success' => '0',
						'msg'     => __( 'Nonce verification failed, Please try again.', 'error-log-viewer-wp' ),
					)
				);

				wp_die();
			}

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['elvwp_nonce'] ) ), 'elvwp_delete_all_log_nonce' ) ) {
				echo wp_json_encode(
					array(
						'success' => '0',
						'msg'     => __( 'Nonce verification failed, Please try again.', 'error-log-viewer-wp' ),
					)
				);

				wp_die();
			}

			if ( is_admin() ) {

				global $wpdb;
				$elvwp_table      = esc_sql( $wpdb->prefix . $this->elvwp_error_logs );
				$is_success       = false;
				$elvwp_table_data = $wpdb->get_col( "SELECT file_name FROM {$elvwp_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery

				if ( ! empty( $elvwp_table_data ) ) {

					foreach ( $elvwp_table_data as $value ) {
						$elvwp_datatable_filename = $this->log_directory . '/' . $value;

						if ( file_exists( $elvwp_datatable_filename ) ) {
							$elvwp_datatable_basename = basename( $value );
							// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
							$elvwp_delete_data = $wpdb->delete(
								$elvwp_table,
								array(
									'file_name' => $elvwp_datatable_basename,
								)
							);

							if ( $elvwp_delete_data ) {

								wp_delete_file( $elvwp_datatable_filename );

								$is_success = true;
							}
						}
					}

					if ( $is_success ) {

						echo wp_json_encode(
							array(
								'success' => '1',
								'msg'     => __( 'All Logs were purged successfully.', 'error-log-viewer-wp' ),
							)
						);

						wp_die();
					} else {
						echo wp_json_encode(
							array(
								'success' => '0',
								'msg'     => __( 'Failed to purge Logs.', 'error-log-viewer-wp' ),
							)
						);

						wp_die();
					}
				}
			}
		}

		public function elvwp_error_log_osort( &$input_array, $properties ) {

			if ( is_string( $properties ) ) {
				$properties = array(
					$properties => SORT_ASC,
				);
			}

			uasort(
				$input_array,
				function( $a, $b ) use ( $properties ) {

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

						$a_prop = $collapse( $a, $k );
						$b_prop = $collapse( $b, $k );

						if ( $a_prop !== $b_prop ) {
							return ( SORT_ASC === $v ) ? strnatcasecmp( $a_prop, $b_prop ) : strnatcasecmp( $b_prop, $a_prop );
						}
					}

					return 0;
				}
			);
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

			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( empty( $_REQUEST['elvwp_deactivation_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_REQUEST['elvwp_deactivation_nonce'] ), 'elvwp_deactivation_nonce' ) ) {
				wp_die();
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die();
			}

			$reason_id = isset( $_POST['reason'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['reason'] ) ) ) : 7;

			if ( empty( $reason_id ) ) {
				wp_die();
			}

			$reason_info = isset( $_POST['reason_detail'] ) ? sanitize_text_field( wp_unslash( $_POST['reason_detail'] ) ) : '';

			if ( 1 === $reason_id ) {
				$reason_text = __( 'I only needed the plugin for a short period', 'error-log-viewer-wp' );
			} elseif ( 2 === $reason_id ) {
				$reason_text = __( 'I found a better plugin', 'error-log-viewer-wp' );
			} elseif ( 3 === $reason_id ) {
				$reason_text = __( 'The plugin broke my site', 'error-log-viewer-wp' );
			} elseif ( 4 === $reason_id ) {
				$reason_text = __( 'The plugin suddenly stopped working', 'error-log-viewer-wp' );
			} elseif ( 5 === $reason_id ) {
				$reason_text = __( 'I no longer need the plugin', 'error-log-viewer-wp' );
			} elseif ( 6 === $reason_id ) {
				$reason_text = __( 'It\'s a temporary deactivation. I\'m just debugging an issue.', 'error-log-viewer-wp' );
			} elseif ( 7 === $reason_id ) {
				$reason_text = __( 'Other', 'error-log-viewer-wp' );
			}

			$cuurent_user = wp_get_current_user();

			$to      = 'info@wpguru.co';
			$subject = 'Plugin Uninstallation';

			$body  = '<p>Plugin Name: ' . ELVWP_NAME . '</p>';
			$body .= '<p>Plugin Version: ' . ELVWP_VER . '</p>';
			$body .= '<p>Reason: ' . $reason_text . '</p>';
			$body .= '<p>Reason Info: ' . $reason_info . '</p>';
			$body .= '<p>Admin Name: ' . $cuurent_user->display_name . '</p>';
			$body .= '<p>Admin Email: ' . get_option( 'admin_email' ) . '</p>';
			$body .= '<p>Website: ' . get_site_url() . '</p>';
			$body .= '<p>Website Language: ' . get_bloginfo( 'language' ) . '</p>';
			$body .= '<p>WordPress Version: ' . get_bloginfo( 'version' ) . '</p>';
			$body .= '<p>PHP Version: ' . PHP_VERSION . '</p>';

			$headers = array(
				'Content-Type: text/html; charset=UTF-8',
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

			if ( $file === $this_plugin ) {
				/* translators: 1: settings link start, 2: settings link end. */
				$settings_link = sprintf( esc_html__( '%1$s Log Viewer %2$s', 'error-log-viewer-wp' ), '<a href="' . esc_url( admin_url( 'admin.php?page=' . $this->elvwp_permalink ) ) . '">', '</a>' );

				array_unshift( $links, $settings_link );

			}

			return $links;
		}

		/**
		 * Add support link
		 *
		 * @since 1.0.0
		 * @param array  $plugin_meta
		 * @param string $plugin_file
		 */
		public function elvwp_add_action_links( $plugin_meta, $plugin_file ) {

			if ( plugin_basename( ELVWP_FILE ) === $plugin_file ) {

				$plugin_meta_str = '<a href="' . esc_url( ELVWP_SUPPORT_URL ) . '" target="_blank">' . esc_html__( 'Support', 'error-log-viewer-wp' ) . '</a>';
				array_push( $plugin_meta, $plugin_meta_str );

				$plugin_meta_str = '<a href="' . esc_url( ELVWP_REVIEW_URL ) . '" target="_blank">' . esc_html__( 'Post Review', 'error-log-viewer-wp' ) . '</a>';
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
			$user_name = '';

			if ( ! empty( $current_user->display_name ) ) {
				$user_name = ' ' . $current_user->display_name;
			}

			echo "<div id='elvwp-review' class='notice notice-info is-dismissible'><p>" .

			sprintf( esc_html__( "Hi %1\$s, Thank you for using %2\$s. Please don't forget to rate our plugin. We sincerely appreciate your feedback.", 'psupsellmaster' ), esc_attr( $user_name ), '<b>' . esc_attr( ELVWP_NAME ) . '</b>' )
			.
			'<br><a target="_blank" href="' . esc_url( ELVWP_REVIEW_URL ) . '" class="button-secondary">' . esc_html__( 'Post Review', 'psupsellmaster' ) . '</a>' .
			'</p></div>';
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
		 * @return string Amended footer text.
		 */
		public function elvwp_admin_footer_text() {
			return sprintf(
				/* translators: 1: Plugin Name, 3: Plugin review URL */
				__( 'If you like <strong><ins>%1$s</ins></strong>. please leave us a <a target="_blank" style="color:#f9b918" href="%2$s">★★★★★</a> rating. A huge thank you in advance!', 'error-log-viewer-wp' ),
				esc_attr( ELVWP_NAME ),
				esc_url_raw( ELVWP_REVIEW_URL ),
			);
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
	if ( ! wp_next_scheduled( 'elvwp_cron_task_hook_notification_time' ) ) {
		wp_schedule_event( time(), 'elvwp_notification_time', 'elvwp_cron_task_hook_notification_time' );
	}
}

register_activation_hook( __FILE__, 'elvwp_activation' );

/**
 * The deactivation hook is called outside of the singleton because WordPress doesn't
 * register the call from within the class, since we are preferring the plugins_loaded
 * hook for compatibility, we also can't reference a function inside the plugin class
 * for the deactivation function. If you need an deactivation function, put it here.
 *
 * @since       1.0.0
 * @return      void
 */
function elvwp_deactivation() {

	if ( ! class_exists( 'WP_Config_Transformer' ) ) {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-config-transformer.php';
	}

	$object             = new Error_Log_Viewer_WP();
	$config_path        = $object->get_wp_config_path();
	$config_transformer = new WP_Config_Transformer( $config_path );

	if ( $config_transformer->exists( 'constant', 'WP_DEBUG_LOG' ) ) {
		$config_transformer->remove( 'constant', 'WP_DEBUG_LOG' );
	}

	if ( $config_transformer->exists( 'inivariable', 'log_errors' ) ) {
		$config_transformer->remove( 'inivariable', 'log_errors' );
	}

	if ( $config_transformer->exists( 'inivariable', 'error_log' ) ) {
		$config_transformer->remove( 'inivariable', 'error_log' );
	}

	wp_clear_scheduled_hook( 'elvwp_cron_task_hook_notification_time' );
}

register_deactivation_hook( __FILE__, 'elvwp_deactivation' );


function elvwp_submit_notification_setting() {
	$setting_page_url = admin_url( 'admin.php?page=elvwp-notification' );

	if ( isset( $_POST['elvwp_frequency'] ) ) {

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( empty( $_REQUEST['elvwp_notification_setting_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_REQUEST['elvwp_notification_setting_nonce'] ), 'elvwp_notification_setting_nonce' ) ) {
			wp_die();
		}

		if ( isset( $_POST['elvwp_notification_status'] ) ) {
			update_option( 'elvwp_notification_status', sanitize_text_field( wp_unslash( $_POST['elvwp_notification_status'] ) ) );
		} else {
			update_option( 'elvwp_notification_status', '' );
		}

		if ( isset( $_POST['elvwp_notification_emails'] ) && ! empty( $_POST['elvwp_notification_emails'] ) ) {
			$emails = explode( ',', sanitize_textarea_field( wp_unslash( $_POST['elvwp_notification_emails'] ) ) );
			$emails = array_map( 'trim', $emails );
			update_option( 'elvwp_notification_emails', $emails );
		} else {
			$admin_email = get_option( 'admin_email' );
			$emails      = array( $admin_email );
			$emails      = array_map( 'trim', $emails );
			update_option( 'elvwp_notification_emails', $emails );
		}

		if ( isset( $_POST['elvwp_frequency'] ) ) {
			update_option( 'elvwp_frequency', sanitize_text_field( wp_unslash( $_POST['elvwp_frequency'] ) ) );
		} else {
			update_option( 'elvwp_frequency', 'weekly' );
		}

		wp_safe_redirect( $setting_page_url );
		exit();
	}
}
add_action( 'admin_action_elvwp_submit_notification_setting', 'elvwp_submit_notification_setting' );
