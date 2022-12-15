<?php
/**
 * WP_Error_Log_Viewer deactivation Content.
 * @package WP_Error_Log_Viewer
 * @version 1.0.0
 */

$wp_elv_error_log_deactivation_nonce = wp_create_nonce( 'wp_elv_error_log_deactivation_nonce' ); 
?>
<div class="wp_elv-popup-overlay">
    <div class="wp_elv-serveypanel">
        <form action="#" id="wp_elv-deactivate-form" method="post">
            <div class="wp_elv-popup-header">
                <h2>
                    <?php _e( 'Quick feedback about '.WP_ERROR_LOG_VIEWER_NAME, 'wp_elv' ); ?>
                </h2>
            </div>
            <div class="wp_elv-popup-body">
                <h3>
                    <?php _e( 'If you have a moment, please let us know why you are deactivating:', 'wp_elv' ); ?>
                </h3>
                <input class="wp_elv_error_log_deactivation_nonce" name="wp_elv_error_log_deactivation_nonce" type="hidden" value="<?php echo $wp_elv_error_log_deactivation_nonce; ?>">
                    <ul id="wp_elv-reason-list">
                        <li class="wp_elv-reason" data-input-placeholder="" data-input-type="">
                            <label>
                                <span>
                                    <input name="wp_elv-selected-reason" type="radio" value="1">
                                    </input>
                                </span>
                                <span class="reason_text">
                                    <?php _e( 'I only needed the plugin for a short period', 'wp_elv' ); ?>
                                </span>
                            </label>
                            <div class="wp_elv-internal-message">
                            </div>
                        </li>
                        <li class="wp_elv-reason has-input" data-input-type="textfield">
                            <label>
                                <span>
                                    <input name="wp_elv-selected-reason" type="radio" value="2">
                                    </input>
                                </span>
                                <span class="reason_text">
                                    <?php _e( 'I found a better plugin', 'wp_elv' ); ?>
                                </span>
                            </label>
                            <div class="wp_elv-internal-message">
                            </div>
                            <div class="wp_elv-reason-input">
                                <span class="message error-message ">
                                    <?php _e( 'Kindly tell us the Plugin name.', 'wp_elv' ); ?>
                                </span>
                                <input name="better_plugin" placeholder="What's the plugin's name?" type="text"/>
                            </div>
                        </li>
                        <li class="wp_elv-reason" data-input-placeholder="" data-input-type="">
                            <label>
                                <span>
                                    <input name="wp_elv-selected-reason" type="radio" value="3">
                                    </input>
                                </span>
                                <span class="reason_text">
                                    <?php _e( 'The plugin broke my site', 'wp_elv' ); ?>
                                </span>
                            </label>
                            <div class="wp_elv-internal-message">
                            </div>
                        </li>
                        <li class="wp_elv-reason" data-input-placeholder="" data-input-type="">
                            <label>
                                <span>
                                    <input name="wp_elv-selected-reason" type="radio" value="4">
                                    </input>
                                </span>
                                <span class="reason_text">
                                    <?php _e( 'The plugin suddenly stopped working', 'wp_elv' ); ?>
                                </span>
                            </label>
                            <div class="wp_elv-internal-message">
                            </div>
                        </li>
                        <li class="wp_elv-reason" data-input-placeholder="" data-input-type="">
                            <label>
                                <span>
                                    <input name="wp_elv-selected-reason" type="radio" value="5">
                                    </input>
                                </span>
                                <span class="reason_text">
                                    <?php _e( 'I no longer need the plugin', 'wp_elv' ); ?>
                                </span>
                            </label>
                            <div class="wp_elv-internal-message">
                            </div>
                        </li>
                        <li class="wp_elv-reason" data-input-placeholder="" data-input-type="">
                            <label>
                                <span>
                                    <input name="wp_elv-selected-reason" type="radio" value="6">
                                    </input>
                                </span>
                                <span class="reason_text">
                                    <?php _e( 'It\'s a temporary deactivation. I\'m just debugging an issue.', 'wp_elv' ); ?>
                                </span>
                            </label>
                            <div class="wp_elv-internal-message">
                            </div>
                        </li>
                        <li class="wp_elv-reason has-input" data-input-type="textfield">
                            <label>
                                <span>
                                    <input name="wp_elv-selected-reason" type="radio" value="7">
                                    </input>
                                </span>
                                <span class="reason_text">
                                    <?php _e( 'Other', 'wp_elv' ); ?>
                                </span>
                            </label>
                            <div class="wp_elv-internal-message">
                            </div>
                            <div class="wp_elv-reason-input">
                                <span class="message error-message ">
                                    <?php _e( 'Kindly tell us the reason so we can improve.', 'wp_elv' ); ?>
                                </span>
                                <input name="other_reason" placeholder="Kindly tell us the reason so we can improve." type="text"/>
                            </div>
                        </li>
                    </ul>
                </input>
            </div>
            <div class="wp_elv-popup-footer">
                <label class="wp_elv-anonymous">
                    <input type="checkbox"/>
                    <?php _e( 'Anonymous feedback', 'wp_elv' ); ?>
                </label>
                <input class="button button-secondary button-skip wp_elv-popup-skip-feedback" type="button" value="<?php _e( 'Skip & Deactivate', 'wp_elv'); ?>">
                    <div class="wp_elv_action-btns">
                        <span class="wp_elv-spinner">
                            <img alt="" src="<?php echo admin_url( '/images/spinner.gif' ); ?>"/>
                        </span>
                        <input class="button button-secondary button-deactivate wp_elv-popup-allow-deactivate" disabled="disabled" type="submit" value="<?php _e( 'Submit & Deactivate', 'wp_elv'); ?>">
                            <a class="button button-primary wp_elv-popup-button-close" href="#">
                                <?php _e( 'Cancel', 'wp_elv' ); ?>
                            </a>
                        </input>
                    </div>
                </input>
            </div>
        </form>
    </div>
</div>
