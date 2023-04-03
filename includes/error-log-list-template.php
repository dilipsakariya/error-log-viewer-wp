<div class="elvwp_log_overview">
	<h1><?php esc_html_e( 'Error Log List', 'error-log-viewer-wp' ); ?> <button type="button" id="elvwp_delete_all_logs" class="button button-delete-logs"><?php esc_html_e( 'Delete All', 'error-log-viewer-wp' );?></button> </h1>
	<hr>
	<div class="elvwp_log_table">
		<table class="elvwp_log_list_table row-border hover" id="elvwp_log_list_table">
			<thead>
				<tr>
					<th class="date"><?php esc_html_e( 'Date', 'error-log-viewer-wp' ); ?></th>
					<th><?php esc_html_e( 'Plugin', 'error-log-viewer-wp' ); ?></th>
					<th><?php esc_html_e( 'Theme', 'error-log-viewer-wp' ); ?></th>
					<th><?php esc_html_e( 'Others', 'error-log-viewer-wp' ); ?></th>
					<th class="log_path"><?php esc_html_e( 'Log Path', 'error-log-viewer-wp' ); ?></th>
					<th class="action"><?php esc_html_e( 'Action', 'error-log-viewer-wp' ); ?></th>
				</tr>
			</thead>
			<tbody>
			</tbody>
		</table>
	</div>
</div>
