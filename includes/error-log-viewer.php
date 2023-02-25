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

$instance = new Error_Log_Viewer_WP();

$date_format = get_option( 'date_format' );

if ( isset( $_GET['date'] ) && ! empty( sanitize_text_field( wp_unslash( $_GET['date'] ) ) ) ) {
	$log_date = date( 'd-M-Y', strtotime( sanitize_text_field( wp_unslash( $_GET['date'] ) ) ) );
}

if ( isset( $_GET['date'] ) && ! empty( sanitize_text_field( wp_unslash( $_GET['date'] ) ) ) && isset( $_GET['type'] ) && ! empty( sanitize_text_field( wp_unslash( $_GET['type'] ) ) ) ) {
	$error_type = str_replace( ' ', '', sanitize_text_field( wp_unslash( $_GET['type'] ) ) );
}

$is_raw_log = false;

if ( isset( $_GET['is_raw_log'] ) && 'true' === sanitize_text_field( wp_unslash( $_GET['is_raw_log'] ) ) ) {
	$is_raw_log = true;
}

if ( isset( $_POST['date'] ) && isset( $_POST['elvwp_nonce'] ) && ! empty( sanitize_text_field( wp_unslash( $_POST['date'] ) ) ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['elvwp_nonce'] ) ), 'elvwp_date_filter_nonce' ) ) {

	if ( 'd/m/Y' === $date_format ) {
		$_POST['date'] = str_replace( '/', '-', sanitize_text_field( wp_unslash( $_POST['date'] ) ) );
	}

	$log_date = date( 'd-M-Y', strtotime( sanitize_text_field( wp_unslash( $_POST['date'] ) ) ) );

} elseif ( ( ! isset( $_GET['date'] ) || empty( sanitize_text_field( wp_unslash( $_GET['date'] ) ) ) ) && ( ! isset( $_GET['type'] ) || empty( sanitize_text_field( wp_unslash( $_GET['type'] ) ) ) ) ) {

	$last_log = elvwp_get_last_log();

	if ( $last_log ) {
		$log_date = date( 'd-M-Y', strtotime( $last_log->created_at ) );
	} else {
		$log_date = date( 'd-M-Y' );
	}
}

$log_details = $instance->elvwp_log_details( $log_date, $is_raw_log );
?>

<div id="elvwp_err_container">

	<?php if ( ! empty( $log_details['logs'] ) ) : ?>
		<h1><?php esc_html_e( 'Error Log Viewer', 'error-log-viewer-wp' ); ?></h1>
		<div class="elvwp_error_log_filter">
			<div class="">
				<h3 class="elvwp_filter_heading"><?php esc_html_e( 'Filters', 'error-log-viewer-wp' ); ?></h3>
				<form action="" method="POST">
					<fieldset id="dateFilter">
						<div><label class="elvwp-lbl-filter"><?php esc_html_e( 'Filter by Date: ', 'error-log-viewer-wp' ); ?></label> <input type="text" name="date" id="elvwp_datepicker" class="hasDatepicker" value="<?php echo esc_attr( date( $date_format, strtotime( $log_date ) ) ); ?>" />&nbsp;&nbsp;
						<button type="submit" class="button button-primary" name="elvwp_error_log_filter_by_date" id="elvwp_error_log_filter_by_date" value=""><?php esc_html_e( 'Apply', 'error-log-viewer-wp' ); ?></button></div>
					</fieldset>
					<?php wp_nonce_field( 'elvwp_date_filter_nonce', 'elvwp_nonce' ); ?>
				</form>

				<fieldset id="elvwp_path_filter">
					<input type="hidden" value="">
				</fieldset>
				<?php if ( ! $is_raw_log ) { ?>
				<fieldset id="elvwp_type_filter">
					<p> 
						<label class="elvwp-lbl-filter"><?php esc_html_e( 'Filter by Type: ', 'error-log-viewer-wp' ); ?></label>
						<?php foreach ( $log_details['types'] as $log_title => $class ) : ?>

						<label class=" elvwp_type_lbl 
							<?php
							if ( ! empty( $class ) ) {
								echo esc_attr( $class );
							} else {
								echo esc_attr( $type ); }
							?>
						">
							<input type="checkbox" value="<?php echo esc_attr( $class ); ?>" checked="checked" /> 
							<?php
								echo esc_html( ucwords( $log_title ) );
							?>

							(<span data-total="<?php echo esc_attr( $log_details['typecount'][ $log_title ] ); ?>">
							<?php
								echo esc_html( $log_details['typecount'][ $log_title ] );
							?>
							</span>)
						</label>
						<?php endforeach; ?>
					</p>
				</fieldset>

				<fieldset id="elvwp_sort_options">
					<p>
						<label class="elvwp-lbl-filter"><?php esc_html_e( 'Sort By: ', 'error-log-viewer-wp' ); ?></label>
						<a href="?type=last&amp;order=asc"><?php esc_html_e( 'Last Seen', 'error-log-viewer-wp' ); ?> (<span><?php esc_html_e( 'asc', 'error-log-viewer-wp' ); ?></span>)</a>, 
						<a href="?type=hits&amp;order=desc"><?php esc_html_e( 'Hits', 'error-log-viewer-wp' ); ?> (<span><?php esc_html_e( 'desc', 'error-log-viewer-wp' ); ?></span>)</a>, 
						<a href="?type=type&amp;order=asc"><?php esc_html_e( 'Type', 'error-log-viewer-wp' ); ?> (<span><?php esc_html_e( 'a-z', 'error-log-viewer-wp' ); ?></span>)</a>
					</p>
				</fieldset>
				<?php } ?>
				<div class="clear"></div>

				<?php if ( ! $is_raw_log ) { ?>
					<a href="
					<?php
						$view_raw_log = array(
							'date'       => date( 'Y-m-d', strtotime( $log_date ) ),
							'is_raw_log' => 'true',
						);
						echo esc_url( add_query_arg( $view_raw_log, admin_url( 'admin.php?page=error-log-viewer-wp' ) ) );
						?>
						" class="button primary" name="elvwp_error_raw_log" id="elvwp_error_raw_log" value=""><?php esc_html_e( 'View Raw Log', 'error-log-viewer-wp' ); ?></a>
				<?php } else { ?>
					<a href="
					<?php
						$view_raw_log = array(
							'date'       => date( 'Y-m-d', strtotime( $log_date ) ),
							'is_raw_log' => 'false',
						);
						echo esc_url( add_query_arg( $view_raw_log, admin_url( 'admin.php?page=error-log-viewer-wp' ) ) );
						?>
						" class="button primary" name="elvwp_error_raw_log" id="elvwp_error_raw_log" value=""><?php esc_html_e( 'View Log', 'error-log-viewer-wp' ); ?></a>
				<?php } ?>

					<a href="<?php echo esc_url( add_query_arg( 'date', date( 'Y-m-d', strtotime( $log_date ) ), admin_url( 'admin.php?page=error-log-viewer-wp' ) ) ); ?>" class="button primary" value=""><?php esc_html_e( 'Refresh Log', 'error-log-viewer-wp' ); ?></a>
			</div>
			<div class="clear"></div>
		</div>
		<p id="entryCount">
			<div class="elvwp_error_log_buttons">
				<?php if ( isset( $log_details['error_log'] ) && ! empty( $log_details['error_log'] ) ) { ?>
					<form method="post">

						<button type="submit" class="button primary" name="elvwp_error_log_download" id="elvwp_error_log_download" value=""><?php esc_html_e( 'Download Log', 'error-log-viewer-wp' ); ?></button>

						<input type="hidden" name="elvwp_error_log" id="elvwp_error_log" value="<?php echo esc_attr( $log_details['error_log'] ); ?>">
						<button type="button" class="button primary" name="elvwp_error_log_purge" id="elvwp_error_log_purge" value=""><?php esc_html_e( 'Purge Log', 'error-log-viewer-wp' ); ?></button>
					</form>
				<?php } ?>
			</div>
			<div class="elvwp_skip_bottom_wrap">
				<a href="javascript:void(0);" name="elvwp_skip_to_bottom" id="elvwp_skip_to_bottom" value=""><?php esc_html_e( 'Skip To Bottom', 'error-log-viewer-wp' ); ?></a>
			</div>
			<div class="clear"></div>
			<div class="elvwp-log-path-main-holder">
				<div class="elvwp-log-path-holder">
					<p><strong><?php esc_html_e( 'Log Path: ', 'error-log-viewer-wp' ); ?></strong><?php echo esc_html( $log_details['error_log'] ); ?></p>
				</div>
				<div class="elvwp_log_data_wrap">
					<span class="log_entries">
						<strong><?php echo esc_html( $log_details['total'] ); ?></strong>
						<?php
							$total_str = ( 1 == $log_details['total'] ? 'y' : 'ies' );
							esc_html( printf( __( 'Distinct Entr%s', 'error-log-viewer-wp' ), esc_html( $total_str ) ) );
						?>
					</span>
					<span id="elvwp_file_size"> <?php esc_html_e( 'File Size: ', 'error-log-viewer-wp' ); ?><strong><?php echo esc_html( elvwp_file_size_convert( filesize( $log_details['error_log'] ) ) ); ?> </strong></span>
				</div>
			</div>
		</p>
		<p id="logfilesize">
		</p>
		<div class="elvwp_type_error">
			<?php foreach ( $log_details['types'] as $type => $class ) { ?>
				<div class="elvwp_logoverview_static 
				<?php
				if ( ! empty( $class ) ) {
					echo esc_attr( $class );
				} else {
					echo esc_attr( $type ); }
				?>
				">
					<div><strong><i class="dashicons-before dashicons-info<?php echo ( esc_attr( 'warning' === $type ) ? '-outline' : '' ); ?>"></i><?php echo esc_html( ucwords( $type ) ); ?>: </strong><?php echo esc_html( $log_details['typecount'][ $type ] ); ?> <?php esc_html_e( 'Entries - ', 'error-log-viewer-wp' ); ?><span><?php echo esc_html( number_format( 100 * $log_details['typecount'][ $type ] / $log_details['total'], 2 ) ); ?>%</span></div>
				</div>
			<?php } ?>
		</div>
		<section id="elvwp_error_list">

			<?php if ( ! $is_raw_log ) { ?>
				<?php foreach ( $log_details['logs'] as $log ) : ?>
					<article class="<?php echo esc_attr( $log_details['types'][ $log->type ] ); ?>"
							data-path="
							<?php
							if ( ! empty( $log->path ) ) {
								echo esc_attr( htmlentities( $log->path ) );}
							?>
							"
							data-line="
							<?php
							if ( ! empty( $log->line ) ) {
								echo esc_attr( $log->line );}
							?>
							"
							data-type="<?php echo esc_attr( $log_details['types'][ $log->type ] ); ?>"
							data-hits="<?php echo esc_attr( $log->hits ); ?>"
							data-last="<?php echo esc_attr( $log->last ); ?>">
						<div class="<?php echo esc_attr( $log_details['types'][ $log->type ] ); ?>">
							<div class="elvwp_er_type"><i class="dashicons-before dashicons-info<?php echo esc_attr( ( 'warning' === $log->type ) ? '-outline' : '' ); ?>"></i><?php echo esc_html( ucwords( htmlentities( $log->type ) ) ); ?></div> 
							<div class="elvwp_er_path">
								<b><?php echo esc_html( htmlentities( ( empty( $log->core ) ? $log->msg : $log->core ) ) ); ?></b>
								<?php if ( ! empty( $log->more ) ) : ?>
									<p><i><?php echo nl2br( esc_html( htmlentities( $log->more ) ) ); ?></i></p>
								<?php endif; ?>
								<div class="elvwp_err_trash">
									<?php if ( ! empty( $log->trace ) ) : ?>
										<?php $uid = uniqid( 'tbq' ); ?>
									<p><a href="#" class="traceblock" data-for="<?php echo esc_attr( $uid ); ?>"><?php esc_html_e( 'Show stack trace', 'error-log-viewer-wp' ); ?></a></p>
									<blockquote id="<?php echo esc_attr( $uid ); ?>"><?php echo esc_html( highlight_string( $log->trace, true ) ); ?></blockquote>
								<?php endif; ?>

								<?php if ( ! empty( $log->code ) ) : ?>
									<?php $uid = uniqid( 'cbq' ); ?>
									<p><a href="#" class="codeblock" data-for="<?php echo esc_attr( $uid ); ?>"><?php esc_html_e( 'Show code snippet', 'error-log-viewer-wp' ); ?></a></p>
									<blockquote id="<?php echo esc_attr( $uid ); ?>"><?php echo esc_html( highlight_string( $log->code, true ) ); ?></blockquote>
								<?php endif; ?>
								</div>
							</div>
							<div class="elvwp_er_time">
								<p>
									<?php if ( ! empty( $log->path ) ) : ?>
										<?php echo esc_html( htmlentities( $log->path ) ); ?>, <?php esc_html_e( 'line', 'error-log-viewer-wp' ); ?> <?php echo esc_html( $log->line ); ?><br />
									<?php endif; ?>
									<?php esc_html_e( 'Last seen:', 'error-log-viewer-wp' ); ?> <?php echo esc_html( date_format( date_create( "@{$log->last}" ), 'Y-m-d G:iA' ) ); ?>, <strong><?php echo esc_html( $log->hits ); ?></strong> 
										<?php
											$hit_str = ( 1 == $log->hits ? '' : 's' );
											printf( esc_html__( 'Hit%s', 'error-log-viewer-wp' ), esc_attr( $hit_str ) );
										?>
									<br />
								</p>
							</div>
						</div>
					</article>
				<?php endforeach; ?>
				<a href="javascript:void(0);" name="elvwp_skip_to_top" id="elvwp_skip_to_top" value=""><?php esc_html_e( 'Skip To Top', 'error-log-viewer-wp' ); ?></a>
			<?php } else { ?>
				<textarea class="widefat" rows="25" name="raw_log_textarea">
				<?php
				$raw_log_details = implode( '', $log_details['file'] );
				echo esc_html( $raw_log_details );
				?>
				</textarea>
			<?php } ?>
		</section>
		<p id="nothingToShow" class="hide"><?php esc_html_e( 'Nothing to show with selected filters.', 'error-log-viewer-wp' ); ?></p>
	<?php else : ?>
		<div class="elvwp_error_log_filter">
			<div class="left">
				<h3 class="elvwp_filter_heading"><?php esc_html_e( 'Filters', 'error-log-viewer-wp' ); ?></h3>
				<form action="" method="POST">
					<fieldset id="dateFilter">
						<div><label><?php esc_html_e( 'Filter by Date: ', 'error-log-viewer-wp' ); ?><input type="text" name="date" id="elvwp_datepicker" class="hasDatepicker" value="<?php echo esc_attr( date( $date_format, strtotime( $log_date ) ) ); ?>" ></label>&nbsp;&nbsp;
						<button type="submit" class="button primary" name="elvwp_error_log_filter_by_date" id="elvwp_error_log_filter_by_date" value=""><?php esc_html_e( 'Apply', 'error-log-viewer-wp' ); ?></button></div>
						<?php wp_nonce_field( 'elvwp_date_filter_nonce', 'elvwp_nonce' ); ?>
					</fieldset>
				</form>

				<fieldset id="elvwp_path_filter">
					<input type="hidden" value="">
				</fieldset>

				<fieldset id="elvwp_type_filter">
					<p><?php esc_html_e( 'Filter by Type:', 'error-log-viewer-wp' ); ?>
						<label class="elvwp_fatal_error">
							<input type="checkbox" value="elvwp_fatal_error" checked="checked"> <?php esc_html_e( 'Fatal Error', 'error-log-viewer-wp' ); ?>
						</label>
					</p>
				</fieldset>

				<fieldset id="elvwp_sort_options">
					<p><?php esc_html_e( 'Sort By: ', 'error-log-viewer-wp' ); ?><a href="?type=last&amp;order=asc"><?php esc_html_e( 'Last Seen ', 'error-log-viewer-wp' ); ?>(<span><?php esc_html_e( 'asc', 'error-log-viewer-wp' ); ?></span>)</a>, <a href="?type=hits&amp;order=desc"><?php esc_html_e( 'Hits ', 'error-log-viewer-wp' ); ?>(<span><?php esc_html_e( 'desc', 'error-log-viewer-wp' ); ?></span>)</a>, <a href="?type=type&amp;order=asc"><?php esc_html_e( 'Type ', 'error-log-viewer-wp' ); ?>(<span><?php esc_html_e( 'a-z', 'error-log-viewer-wp' ); ?></span>)</a></p>
				</fieldset>

				<div class="clear"></div>
			</div>

			<div class="clear"></div>
		</div>
		</br>
		<p id="elvwp_error_nolog_entries"><?php esc_html_e( 'There are currently no PHP error log entries available for this date.', 'error-log-viewer-wp' ); ?></p>
		<?php
	endif;

	$script_object = array(
		'error_type' => ( isset( $error_type ) ? $error_type : false ),
		'total'      => ( isset( $log_details['total'] ) ? $log_details['total'] : 0 ),
	);

	wp_localize_script( 'elvwp_admin_script', 'script_object', $script_object );
	?>

</div>
