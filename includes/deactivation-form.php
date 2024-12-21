<?php
/**
 * Error_Log_Viewer_WP deactivation Content.
 *
 * @package Error_Log_Viewer_WP
 * @version 1.0.0
 */

$elvwp_deactivation_nonce = wp_create_nonce( 'elvwp_deactivation_nonce' );
?>
<div class="elvwp-popup-overlay">
	<div class="elvwp-serveypanel">
		<form action="#" id="elvwp-deactivate-form" method="post">
			<div class="elvwp-popup-header">
				<h2>
					<?php
						/* translators: %s: Plugin Name */
						echo sprintf( esc_html__( 'Quick feedback about %s', 'error-log-viewer-wp' ), esc_html( ELVWP_NAME ) );
					?>
				</h2>
			</div>
			<div class="elvwp-popup-body">
				<h3>
					<?php esc_html_e( 'If you have a moment, please let us know why you are deactivating:', 'error-log-viewer-wp' ); ?>
				</h3>
				<input class="elvwp_deactivation_nonce" name="elvwp_deactivation_nonce" type="hidden" value="<?php echo esc_attr( $elvwp_deactivation_nonce ); ?>">
					<ul id="elvwp-reason-list">
						<li class="elvwp-reason" data-input-placeholder="" data-input-type="">
							<label>
								<span>
									<input name="elvwp-selected-reason" type="radio" value="1">
									</input>
								</span>
								<span class="reason_text">
									<?php esc_html_e( 'I only needed the plugin for a short period', 'error-log-viewer-wp' ); ?>
								</span>
							</label>
							<div class="elvwp-internal-message">
							</div>
						</li>
						<li class="elvwp-reason has-input" data-input-type="textfield">
							<label>
								<span>
									<input name="elvwp-selected-reason" type="radio" value="2">
									</input>
								</span>
								<span class="reason_text">
									<?php esc_html_e( 'I found a better plugin', 'error-log-viewer-wp' ); ?>
								</span>
							</label>
							<div class="elvwp-internal-message">
							</div>
							<div class="elvwp-reason-input">
								<span class="message error-message ">
									<?php esc_html_e( 'Kindly tell us the Plugin name.', 'error-log-viewer-wp' ); ?>
								</span>
								<input name="better_plugin" placeholder="What's the plugin's name?" type="text"/>
							</div>
						</li>
						<li class="elvwp-reason" data-input-placeholder="" data-input-type="">
							<label>
								<span>
									<input name="elvwp-selected-reason" type="radio" value="3">
									</input>
								</span>
								<span class="reason_text">
									<?php esc_html_e( 'The plugin broke my site', 'error-log-viewer-wp' ); ?>
								</span>
							</label>
							<div class="elvwp-internal-message">
							</div>
						</li>
						<li class="elvwp-reason" data-input-placeholder="" data-input-type="">
							<label>
								<span>
									<input name="elvwp-selected-reason" type="radio" value="4">
									</input>
								</span>
								<span class="reason_text">
									<?php esc_html_e( 'The plugin suddenly stopped working', 'error-log-viewer-wp' ); ?>
								</span>
							</label>
							<div class="elvwp-internal-message">
							</div>
						</li>
						<li class="elvwp-reason" data-input-placeholder="" data-input-type="">
							<label>
								<span>
									<input name="elvwp-selected-reason" type="radio" value="5">
									</input>
								</span>
								<span class="reason_text">
									<?php esc_html_e( 'I no longer need the plugin', 'error-log-viewer-wp' ); ?>
								</span>
							</label>
							<div class="elvwp-internal-message">
							</div>
						</li>
						<li class="elvwp-reason" data-input-placeholder="" data-input-type="">
							<label>
								<span>
									<input name="elvwp-selected-reason" type="radio" value="6">
									</input>
								</span>
								<span class="reason_text">
									<?php esc_html_e( 'It\'s a temporary deactivation. I\'m just debugging an issue.', 'error-log-viewer-wp' ); ?>
								</span>
							</label>
							<div class="elvwp-internal-message">
							</div>
						</li>
						<li class="elvwp-reason has-input" data-input-type="textfield">
							<label>
								<span>
									<input name="elvwp-selected-reason" type="radio" value="7">
									</input>
								</span>
								<span class="reason_text">
									<?php esc_html_e( 'Other', 'error-log-viewer-wp' ); ?>
								</span>
							</label>
							<div class="elvwp-internal-message">
							</div>
							<div class="elvwp-reason-input">
								<span class="message error-message ">
									<?php esc_html_e( 'Kindly tell us the reason so we can improve.', 'error-log-viewer-wp' ); ?>
								</span>
								<input name="other_reason" placeholder="Kindly tell us the reason so we can improve." type="text"/>
							</div>
						</li>
					</ul>
				</input>
			</div>
			<div class="elvwp-popup-footer">
				<label class="elvwp-anonymous">
					<input type="checkbox"/>
					<?php esc_html_e( 'Anonymous feedback', 'error-log-viewer-wp' ); ?>
				</label>
				<input class="button button-secondary button-skip elvwp-popup-skip-feedback" type="button" value="<?php esc_html_e( 'Skip & Deactivate', 'error-log-viewer-wp' ); ?>">
					<div class="elvwp_action-btns">
						<span class="elvwp-spinner">
							<img alt="" src="<?php echo esc_url( admin_url( '/images/spinner.gif' ) ); ?>"/>
						</span>
						<input class="button button-secondary button-deactivate elvwp-popup-allow-deactivate" disabled="disabled" type="submit" value="<?php esc_html_e( 'Submit & Deactivate', 'error-log-viewer-wp' ); ?>">
							<a class="button button-primary elvwp-popup-button-close" href="#">
								<?php esc_html_e( 'Cancel', 'error-log-viewer-wp' ); ?>
							</a>
						</input>
					</div>
				</input>
			</div>
		</form>
	</div>
</div>
