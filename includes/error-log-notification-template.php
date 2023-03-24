<?php

/**
 * PHP Error Log GUI
 *
 * A clean and effective single-file GUI for viewing entries in the PHP error
 * log, allowing for filtering by path and by type.
 *
 * @author Andrew Collington, andy@amnuts.com
 * @version 1.0.1
 * @link https://github.com/amnuts/phperror-gui
 * @license MIT, http://acollington.mit-license.org/
 */

/**
 * @var string|null Path to error log file or null to get from ini settings
 */

$elvwp_notification_status = get_option( 'elvwp_notification_status' );
$elvwp_notification_emails = get_option( 'elvwp_notification_emails' );
$elvwp_frequency           = get_option( 'elvwp_frequency' );
?>

<div id="elvwp_err_container">
	<h1><?php esc_html_e( 'Notification Setting', 'error-log-viewer-wp' ); ?></h1>
	<hr>
	<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="post">
		<input type="hidden" name="action" value="elvwp_submit_notification_setting" />
		<?php wp_nonce_field( 'elvwp_notification_setting_nonce', 'elvwp_notification_setting_nonce' ); ?>
		<table class="form-table">
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Enable Notification', 'error-log-viewer-wp' ); ?>
						</th>
						<td>
							<input class="" id="elvwp_notification_status" name="elvwp_notification_status" type="checkbox" value="1" <?php echo ( ! empty( $elvwp_notification_status ) ) ? 'checked' : ''; ?>>
							<label for="elvwp_notification_status">
								<?php esc_html_e( 'Turn ON/OFF Enable Email Notification', 'error-log-viewer-wp' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Notify Email', 'error-log-viewer-wp' ); ?>
						</th>
						<td>
							<textarea class="" id="elvwp_notification_emails" name="elvwp_notification_emails" ><?php echo esc_html( is_array( $elvwp_notification_emails ) ) ? implode( ', ', $elvwp_notification_emails ) : esc_attr( $elvwp_notification_emails ); ?></textarea>
							<br>
							<label for="elvwp_notification_status_toggle">
								<?php esc_html_e( 'Enter (,) sapreted email ids', 'error-log-viewer-wp' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Frequency', 'error-log-viewer-wp' ); ?>
						</th>
						<td>
							<select class="elvwp-select-chosen" data-placeholder="<?php esc_html_e( 'Select a Frequency', 'error-log-viewer-wp' ); ?>" id="elvwp_frequency" name="elvwp_frequency">
								<option value="daily" <?php echo ( 'daily' === $elvwp_frequency ) ? 'selected' : ''; ?>>
									<?php esc_html_e( 'Daily', 'error-log-viewer-wp' ); ?>
								</option>
								<option value="weekly" <?php echo ( 'weekly' === $elvwp_frequency ) ? 'selected' : ''; ?>>
									<?php esc_html_e( 'Weekly', 'error-log-viewer-wp' ); ?>
								</option>
								<option value="monthly" <?php echo ( 'monthly' === $elvwp_frequency ) ? 'selected' : ''; ?>>
									<?php esc_html_e( 'Monthly', 'error-log-viewer-wp' ); ?>
								</option>
							</select>
							<label for="elvwp_notification_status_toggle">
								<?php esc_html_e( 'This setting is used to notification frequency', 'error-log-viewer-wp' ); ?>
							</label>
						</td>
					</tr>
					<tr>
					</tr>
				</tbody>
			</table>
			<p class="submit">
				<input class="button button-primary" id="submit" name="submit" type="submit" value="<?php esc_html_e( 'Save Changes', 'error-log-viewer-wp' ); ?>"/>
			</p>
		</table>
	</form>    
</div>
