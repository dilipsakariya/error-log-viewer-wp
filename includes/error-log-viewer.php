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



$instance = new WP_Error_Log_Viewer;

$date_format = get_option( 'date_format' );

if ( isset( $_GET["date"] ) && !empty( $_GET["date"] ) ) {
    $log_date = date( "d-M-Y", strtotime( str_replace( array( ' ', ',' ), array( '-','' ), $_GET['date'] ) ) );
}

if ( isset( $_GET["date"] ) && !empty( $_GET["date"] ) && isset( $_GET["type"] ) && !empty( $_GET["type"] ) ) {
    $error_type = str_replace( ' ', '', $_GET["type"] );
}
$is_raw_log = false;

if( isset( $_GET['is_raw_log'] ) && 'true' == $_GET['is_raw_log'] ) {
    $is_raw_log = true;
}

if( isset( $_POST['date'] ) && !empty( $_POST['date'] ) ) {
    $log_date = date( "d-M-Y", strtotime( str_replace( array( ' ', ',' ), array( '-','' ), $_POST['date'] ) ) );
} elseif ( !isset( $_GET["date"] ) && empty( $_GET["date"] ) && !isset( $_GET["type"] ) && empty( $_GET["type"] ) ) {
    $last_log = wp_elv_get_last_log();

    if ( $last_log ) {
        $log_date = date( "d-M-Y", strtotime( $last_log->created_at ) );
    } else {
        $log_date = date( "d-M-Y" );
    }
}
$log_details = $instance->wp_elv_log_details( $log_date, $is_raw_log );
?>

<div id="wp_elv_err_container">
    
    <?php if ( !empty(  $log_details['logs'] ) ): ?>
        <h1><?php _e( 'WP Error Log Viewer', 'wp_elv' ); ?></h1>
        <div class="wp_elv_ps_error_log_filter">
            <div class="">
                <h3 class="wp_elv_filter_heading"><?php _e( 'Filters', 'wp_elv' ); ?></h3>
                <form action="" method="POST">
                    <fieldset id="dateFilter">
                        <div><label class="wp_elv-lbl-filter"><?php _e( 'Filter by Date: ', 'wp_elv' ); ?></label> <input type="text" name="date" id="ps_datepicker" class="hasDatepicker" value="<?php echo date( $date_format,strtotime( $log_date ) );?>" />&nbsp;&nbsp;
                        <button type="submit" class="button button-primary" name="wp_elv_ps_error_log_filter_by_date" id="wp_elv_ps_error_log_filter_by_date" value=""><?php _e( 'Apply', 'wp_elv' ); ?></button></div>
                    </fieldset>
                </form>

                <fieldset id="wp_elv_path_filter">
                    <input type="hidden" value="">
                </fieldset>
                <?php if ( !$is_raw_log ) { ?>
                <fieldset id="wp_elv_type_filter">
                    <p> <label class="wp_elv-lbl-filter"><?php _e( 'Filter by Type: ', 'wp_elv' ); ?></label>
                        <?php foreach ($log_details['types'] as $title => $class ): ?>
                        
                        <label class=" wp_elv_type_lbl <?php if( !empty( $class ) ) { echo $class; }else{ echo $type; } ?>">
                            <input type="checkbox"   value="<?php echo $class; ?>" checked="checked" /> 
                            <?php
                                echo ucwords( $title ); 
                            ?> 
                            (<span data-total="<?php echo $log_details['typecount'][ $title ]; ?>">
                            <?php
                                echo $log_details['typecount'][ $title ]; 
                            ?>
                            </span>)
                        </label>
                        <?php endforeach; ?>
                    </p>
                </fieldset>

                <fieldset id="wp_elv_sort_options">
                    <p><label class="wp_elv-lbl-filter"><?php _e( 'Sort By: ', 'wp_elv' ); ?></label>
                        <a href="?type=last&amp;order=asc"><?php _e( 'Last Seen', 'wp_elv' ); ?> (<span><?php _e( 'asc', 'wp_elv' ); ?></span>)</a>, 
                        <a href="?type=hits&amp;order=desc"><?php _e( 'Hits', 'wp_elv' ); ?> (<span><?php _e( 'desc', 'wp_elv' ); ?></span>)</a>, 
                        <a href="?type=type&amp;order=asc"><?php _e( 'Type', 'wp_elv' ); ?> (<span><?php _e( 'a-z', 'wp_elv' ); ?></span>)</a>
                    </p>
                </fieldset>
                <?php } ?>
                <div class="clear"></div>

                <?php if ( !$is_raw_log ) { ?>
                    <a href="<?php 
                        $view_raw_log  = array('date'=>date( 'Y-m-d', strtotime( $log_date) ),'is_raw_log'=>'true');
                        echo add_query_arg($view_raw_log,admin_url('admin.php?page=wp-error-log-viewer') ); ?>" class="button primary" name="wp_elv_error_raw_log" id="wp_elv_error_raw_log" value=""><?php _e( 'View Raw Log', 'wp_elv' ); ?></a>
                <?php }else{ ?>
                    <a href="<?php 
                        $view_raw_log  =array('date'=>date( 'Y-m-d', strtotime( $log_date) ),'is_raw_log'=>'false');
                        echo add_query_arg($view_raw_log,admin_url('admin.php?page=wp-error-log-viewer') ); ?>" class="button primary" name="wp_elv_error_raw_log" id="wp_elv_error_raw_log" value=""><?php _e( 'View Log', 'wp_elv' ); ?></a>
                <?php } ?>

                    <a href="<?php echo add_query_arg('date',date( 'Y-m-d', strtotime( $log_date ) ), admin_url( 'admin.php?page=wp-error-log-viewer' ) );?>" class="button primary" value=""><?php _e( 'Refresh Log', 'wp_elv' ); ?></a>
            </div>
            <div class="clear"></div>
        </div>
        <p id="entryCount">
            <div class="ps_error_log_buttons">
                <?php if( isset( $log_details['error_log'] ) && !empty( $log_details['error_log'] ) ) {   ?>
                    <form method="post">
                        
                        <button type="submit" class="button primary" name="wp_elv_error_log_download" id="wp_elv_error_log_download" value=""><?php _e( 'Download Log', 'wp_elv' ); ?></button>
                    
                        <input type="hidden" name="ps_error_log" id="ps_error_log"  value="<?php echo $log_details['error_log'];?>">
                        <button type="button" class="button primary" name="wp_elv_error_log_purge" id="wp_elv_error_log_purge" value=""><?php _e( 'Purge Log', 'wp_elv' ); ?></button>

                    </form>
                <?php } ?>
            </div>
            <div class="wp_elv_skip_bottom_wrap">
                <a href="javascript:void(0);" name="ps_skip_to_bottom" id="ps_skip_to_bottom" value=""><?php _e( 'Skip To Bottom', 'wp_elv' ); ?></a>
            </div>
            <div class="clear"></div>
            <div class="wp_elv-log-path-main-holder">
                <div class="wp_elv-log-path-holder">
                    <p><strong><?php _e( 'Log Path: ', 'wp_elv' ); ?></strong><?php echo $log_details['error_log'];?></p>
                    <div class="wp_elv_log_data_wrap">
                        <span class="log_entries">
                            <strong><?php $total_str = ( 1 == $log_details['total'] ? 'y' : 'ies' );printf( __( 'Distinct Entr%s: ', 'wp_elv' ), $total_str ); ?> </strong> <?php echo $log_details['total']; ?>
                        </span>
                        <span id="wp_elv_ps_file_size">
                            <strong><?php _e( 'File Size: ', 'wp_elv' ); ?></strong><?php echo wp_elv_file_size_convert( filesize( $log_details['error_log'] ) ); ?>
                        </span>
                    </div>
                </div>

            </div>
        </p>
        <p id="logfilesize">
        </p>
        <div class="wp_elv_type_error">
            <?php foreach( $log_details['types'] as $type=>$class ){?>
                <div class="wp_elv_logoverview_static <?php if( !empty( $class ) ) { echo $class; }else{ echo $type; } ?>">
                    <div><strong><i class="dashicons-before dashicons-info<?php echo ( 'warning' === $type ) ? '-outline' : '' ;?>"></i><?php echo ucwords( $type );?>: </strong><?php echo $log_details['typecount'][ $type ];?> <?php _e( 'Entries - ', 'wp_elv' ); ?><span><?php  echo number_format( 100*$log_details['typecount'][ $type ]/$log_details['total'], 2 );  ?>%</span></div>
                </div>
            <?php } ?>
        </div>
        <section id="wp_elv_error_list">

            <?php if ( !$is_raw_log ) { ?>
                <?php foreach ( $log_details['logs'] as $log ): ?>
                    <article class="<?php echo $log_details['types'][ $log->type ]; ?>"
                            data-path="<?php if ( !empty( $log->path ) ) echo htmlentities( $log->path ); ?>"
                            data-line="<?php if ( !empty( $log->line ) ) echo $log->line; ?>"
                            data-type="<?php echo $log_details['types'][ $log->type ]; ?>"
                            data-hits="<?php echo $log->hits; ?>"
                            data-last="<?php echo $log->last; ?>">
                        <div class="<?php echo $log_details['types'][$log->type]; ?>">
                            <div class="wp_elv_er_type"><i class="dashicons-before dashicons-info<?php echo ( 'warning' === $log->type ) ? '-outline' : '' ;?>"></i><?php echo ucwords( htmlentities( $log->type ) ); ?></div> 
                            <div class="wp_elv_er_path">
                                <b><?php echo htmlentities( ( empty( $log->core) ? $log->msg : $log->core ) ); ?></b>
                                <?php if ( !empty(  $log->more ) ): ?>
                                    <p><i><?php echo nl2br( htmlentities( $log->more ) ); ?></i></p>
                                <?php endif; ?>
                                <div class="wp_elv_err_trash">
                                    <?php if ( !empty( $log->trace ) ): ?>
                                    <?php $uid = uniqid( 'tbq' ); ?>
                                    <p><a href="#" class="traceblock" data-for="<?php echo $uid; ?>"><?php _e( 'Show stack trace', 'wp_elv' );?></a></p>
                                    <blockquote id="<?php echo $uid; ?>"><?php echo highlight_string( $log->trace, true ); ?></blockquote>
                                <?php endif; ?>
                            
                            
                                <?php if ( !empty( $log->code ) ): ?>
                                    <?php $uid = uniqid( 'cbq' ); ?>
                                    <p><a href="#" class="codeblock" data-for="<?php echo $uid; ?>">Show code snippet</a></p>
                                    <blockquote id="<?php echo $uid; ?>"><?php echo highlight_string( $log->code, true ); ?></blockquote>
                                <?php endif; ?>
                                </div>
                            </div>
                            <div class="wp_elv_er_time">
                                <p>
                                    <?php if ( !empty( $log->path ) ): ?>
                                        <?php echo htmlentities( $log->path  ); ?>, line <?php echo $log->line; ?><br />
                                    <?php endif; ?>
                                    Last seen <?php echo date_format( date_create( "@{$log->last}" ), 'Y-m-d G:ia' ); ?>, <?php echo $log->hits; ?> hit<?php echo( 1 == $log->hits ? '' : 's' ); ?><br />
                                </p>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
                <a href="javascript:void(0);" name="wp_elv_skip_to_top" id="wp_elv_skip_to_top" value=""><?php _e( 'Skip To Top', 'wp_elv' ); ?></a>
            <?php }else{  ?>
                <textarea class="widefat" rows="25" name="raw_log_textarea"><?php $raw_log_details = implode( '', $log_details['file'] ); echo $raw_log_details; ?></textarea>
            <?php }  ?>
        </section>
        <p id="nothingToShow" class="hide"><?php _e( 'Nothing to show with selected filters.', 'wp_elv' ); ?></p>
    <?php else: ?>
        <div class="wp_elv_ps_error_log_filter">
            <div class="left">
                <h3 class="wp_elv_filter_heading"><?php _e( 'Filters', 'wp_elv' ); ?></h3>
                <form action="" method="POST">
                    <fieldset id="dateFilter">
                        <div><label><?php _e( 'Filter by Date: ', 'wp_elv' ); ?><input type="text" name="date" id="ps_datepicker" class="hasDatepicker" value="<?php echo date( $date_format,strtotime( $log_date ) );?>" ></label>&nbsp;&nbsp;
                        <button type="submit" class="button primary" name="wp_elv_ps_error_log_filter_by_date" id="wp_elv_ps_error_log_filter_by_date" value=""><?php _e( 'Apply', 'wp_elv' ); ?></button></div>
                    </fieldset>
                </form>

                <fieldset id="wp_elv_path_filter">
                    <input type="hidden" value="">
                </fieldset>

                <fieldset id="wp_elv_type_filter">
                    <p><?php _e( 'Filter by Type:', 'wp_elv' ); ?>
                        <label class="wp_elv_fatal_error">
                            <input type="checkbox" value="wp_elv_fatal_error" checked="checked"> <?php _e( 'Fatal Error', 'wp_elv' ); ?>
                        </label>
                    </p>
                </fieldset>

                <fieldset id="wp_elv_sort_options">
                    <p><?php _e( 'Sort By: ', 'wp_elv' ); ?><a href="?type=last&amp;order=asc"><?php _e( 'Last Seen ', 'wp_elv' ); ?>(<span><?php _e( 'asc', 'wp_elv' ); ?></span>)</a>, <a href="?type=hits&amp;order=desc"><?php _e( 'Hits ', 'wp_elv' ); ?>(<span><?php _e( 'desc', 'wp_elv' ); ?></span>)</a>, <a href="?type=type&amp;order=asc"><?php _e( 'Type ', 'wp_elv' ); ?>(<span><?php _e( 'a-z', 'wp_elv' ); ?></span>)</a></p>
                </fieldset>
                
                <div class="clear"></div>
            </div>
            
            <div class="clear"></div>
        </div>
        </br>
        <p id="wp_elv_error_nolog_entries"><?php _e( 'There are currently no PHP error log entries available for this date.', 'wp_elv' ); ?></p>
    <?php endif; 

    $script_object = array(
        'error_type' => ( isset($error_type) ? true : false ),
        'total' => ( isset( $log_details['total'] ) ? $log_details['total'] : 0 ),
    );
    wp_localize_script( 'wp_elv_admin_script', 'script_object', $script_object );
    ?>

</div>
