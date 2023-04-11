<?php

class Elvwp_Dashboard_Widget {
	protected $widget_id = 'elvwp_error_log';
	protected $required_capability = 'manage_options';
	protected $widget_css_path = 'assets/css/dashboard-widget.css';

	protected function __construct() {
		add_action('wp_dashboard_setup', array($this, 'elvwp_register_widget'));
		add_action('wp_network_dashboard_setup', array($this, 'elvwp_register_widget'));
	}

	public function elvwp_register_widget() {
		if ( $this->elvwp_user_can_see_widget() ) {
			wp_add_dashboard_widget(
				$this->widget_id,
				/* translators: Dashboard widget name */
				__('PHP Error Log', 'error-log-viewer-wp'),
				array($this, 'elvwp_display_widget_contents'),
				false
			);

			add_action( 'admin_enqueue_scripts', array( $this, 'elvwp_enqueue_widget_dependencies' ) );
		}
	}

	private function elvwp_user_can_see_widget() {
		return apply_filters('elvwp_show_dashboard_widget', current_user_can($this->required_capability));
	}

	private function elvwp_user_can_clear_log() {
		return $this->elvwp_user_can_see_widget() && current_user_can('install_plugins');
	}

	public function elvwp_enqueue_widget_dependencies( $hook ) {
		if ( $hook === 'index.php' ) {
			wp_enqueue_script(
				'elvwp-dashboard-widget-script',
				plugins_url('assets/js/dashboard-widget.js', ELVWP_FILE ),
				array('jquery'),
				ELVWP_VER
			);

			wp_enqueue_style(
				'elvwp-dashboard-widget-styles',
				plugins_url($this->widget_css_path, ELVWP_FILE ),
				array(),
				ELVWP_VER
			);
		}
	}

	public function elvwp_display_widget_contents() {
		
		if ( isset($_GET['elvwp-log-cleared']) && !empty($_GET['elvwp-log-cleared']) ) {
			printf('<p><strong>%s</strong></p>', __('Log cleared.', 'error-log-viewer-wp'));
		}

		$last_log = elvwp_get_last_log();

		if ( $last_log ) {
			$log_date = date( 'd-M-Y', strtotime( $last_log->created_at ) );
		} else {
			$log_date = date( 'd-M-Y' );
		}

		$is_raw_log = false;

		$log_details = elvwp_load()->elvwp_log_details( $log_date, $is_raw_log, 10 );
		echo '<div class="elvwp-dashboard-widget-main">
				<a href="'. esc_url( admin_url( 'admin.php?page=error-log-viewer-wp' ) ) .'" class="elvwp-view-log-link">' . __( 'View Full Log', 'error-log-viewer-wp' ) . '</a>';
		foreach ( $log_details['logs'] as $log ) : ?>
			<article class="<?php echo esc_attr( $log_details['types'][ $log->type ] ); ?>">
				<div class="<?php echo esc_attr( $log_details['types'][ $log->type ] ); ?>">
					<div class="elvwp-row">
						<div class="elvwp-auto-col elvwp-col elvwp-type-color"><i class="dashicons-before dashicons-info<?php echo esc_attr( ( 'warning' === $log->type ) ? '-outline' : '' ); ?>"></i><?php echo esc_html( ucwords( htmlentities( $log->type ) ) ); ?></div>
						<div class="elvwp-auto-col elvwp-col">
							<p>
								<?php if ( ! empty( $log->path ) ) : ?>
									<?php echo esc_html( htmlentities( $log->path ) ); ?>, <?php esc_html_e( 'line', 'error-log-viewer-wp' ); ?> <?php echo esc_html( $log->line ); ?><br />
								<?php endif; ?>
								<?php esc_html_e( 'Last seen:', 'error-log-viewer-wp' ); ?> <?php echo esc_html( date_format( date_create( "@{$log->last}" ), 'Y-m-d G:iA' ) ); ?>, <strong><?php echo esc_html( $log->hits ); ?></strong> 
									<?php
										$hit_str = ( 1 === (int) $log->hits ? '' : 's' );
										/* translators: %s: Number of hites */
										printf( esc_html__( 'Hit%s', 'error-log-viewer-wp' ), esc_attr( $hit_str ) );
									?>
								<br />
							</p>
						</div>
					</div>
					<div class="elvwp-row">
						<div class="elvwp-col">
							<b><?php echo esc_html( htmlentities( ( empty( $log->core ) ? $log->msg : $log->core ) ) ); ?></b>
							<?php if ( ! empty( $log->more ) ) : ?>
								<p><i><?php echo nl2br( esc_html( htmlentities( $log->more ) ) ); ?></i></p>
							<?php endif; ?>
							<div class="elvwp_err_trash">
								<?php if ( ! empty( $log->trace ) ) : ?>
									<?php $uid = uniqid( 'tbq' ); ?>
								<p><a href="#" class="traceblock" data-for="<?php echo esc_attr( $uid ); ?>"><?php esc_html_e( 'Show stack trace', 'error-log-viewer-wp' ); ?></a></p>
								<blockquote id="<?php echo esc_attr( $uid ); ?>"><?php echo highlight_string( $log->trace, true ); ?></blockquote>
							<?php endif; ?>

							<?php if ( ! empty( $log->code ) ) : ?>
								<?php $uid = uniqid( 'cbq' ); ?>
								<p><a href="#" class="codeblock" data-for="<?php echo esc_attr( $uid ); ?>"><?php esc_html_e( 'Show code snippet', 'error-log-viewer-wp' ); ?></a></p>
								<blockquote id="<?php echo esc_attr( $uid ); ?>"><?php echo highlight_string( $log->code, true ); ?></blockquote>
							<?php endif; ?>
							</div>
						</div>
					</div>
				</div>
			</article>
		<?php endforeach;

		echo '</div>';

		do_action('elvwp_after_widget_footer');
	}

	public static function elvwp_get_instance() {
		static $instance = null;
		if ( $instance === null ) {
			$instance = new self();
		}
		return $instance;
	}
}