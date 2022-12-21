<div class="wp_elv_new_error_form_style">
    <form id="form1" method="post" action="">
        <?php
            $first_date  =  date( 'Y-m-d' ); 
            if( isset( $_POST['view'] ) ) {
                $first_date  = date( 'Y-m-d', strtotime( $_POST['select_date'] ) );
            }
        ?>
        <label for="select_date" class="new_lbl_selected"><?php _e( 'Log From: ', 'wp_elv' ); ?></label>
        <input type="text" value="<?php echo $first_date;?>" name="select_date" id="wp_elv_select_date" class="new_date_field" /></br>
        <input type="submit" class="button button-primary view" name="view" value="View"></label>
        <div>
            <?php
                get_option( 'timezone_string' );
                
                if ( ! isset( $_POST['view'] ) ) {
                    $first_date                 =    date( 'd-M-Y', strtotime( $first_date ) );
                    $file                       =  WP_CONTENT_DIR .'/uploads/wp-error-log-viewer/log-'.$first_date.'.log';
                    $pattern                    = '/\[(.*?)\s/';
                    $pattern_fatal_error        = '/PHP Fatal error:/';
                    $pattern_stack_trace        = '/Stack trace:/';
                    $pattern_php_notice         = '/PHP Notice:/';
                    $pattern_php_warning        = '/PHP Warning:/';
                    $count_line                 = 0;
                    $handle                     = fopen( $file, 'r' );
                    $line_date                  = 0;
                    
                    if ( ! empty( $handle ) ) {
                        $c = 1;
                        
                        while ( ! feof( $handle ) ) {
                            $line = fgets( $handle );
                            
                            if ( preg_match( $pattern, $line, $matches ) ) {
                                $line_date  = $matches[1];
                            }
                            echo $c;
                            $c++;
                            
                            if ( preg_match( $pattern_fatal_error, $line ) ) {
                                echo '<div class="fatal_error_body">';
                                echo '<span id="myspan">' .$line. '</span>';
                                echo '</div>';
                            } elseif ( preg_match( $pattern_stack_trace, $line ) ) {
                                echo '<div class="normal_error">';
                                echo '<span id="linespan">' .$line. '</span>';
                                echo '</div>';
                            } elseif ( preg_match( $pattern_php_notice, $line ) ) {
                                echo '<div class="normal_error">';
                                echo '<span id="linespan">' .$line. '</span>';
                                echo '</div>';
                            } elseif ( preg_match( $pattern_php_warning, $line ) ) {
                                echo '<div class="normal_error">';
                                echo '<span id="linespan">' .$line. '</span>';
                                echo '</div>';
                            } else {
                                echo '<div class="normal_error">';
                                echo '<span id="linespan">' .$line. '</span>';
                                echo '</div>';
                            }
                            $count_line ++;
                        }

                        if ( 0 == $count_line ) {
                            printf( __( 'No log in search date from %s', 'wp_elv' ), $first_date );
                        }
                        fclose( $handle );
                    } else {
                        return printf(  __( 'No log in search date from %s', 'wp_elv' ), $first_date );
                    }
                }

                if ( isset( $_POST['view'] ) ) {
                    $first_date     = date( 'd-M-Y', strtotime( $first_date ) );
                    $file           = WP_CONTENT_DIR.'/uploads/wp-error-log-viewer/log-'.$first_date.'.log';
                    $pattern        = '/\[(.*?)\s/';
                    $pattern2       = '/PHP Fatal error/';
                    $pattern3       = '/Stack trace/';
                    $count_line     = 0;
                    $handle         = fopen( $file, 'r' );
                    $line_date      = 0;
                    $match          = 0;
                    
                    if ( ! empty( $handle ) ) {
                        
                        while ( ! feof( $handle ) ) {
                            $line = fgets( $handle );
                            
                            if( preg_match( $pattern, $line, $matches ) ) {
                                 $line_date  = $matches[1];
                            }

                            if (  $line_date  == $first_date  ) {
                                
                                if ( preg_match( $pattern2, $line ) ) {
                                    echo '<div class="fatal_error_body">';
                                    echo '<span id="myspan">' .$line. '</span>';
                                    echo '</div>';
                                } else {
                                    echo '<div class="normal_error">';
                                    echo '<span id="linespan">' .$line. '</span>';
                                    echo '</div>';
                                }
                                $count_line ++;
                            } else {
                                continue;
                            }
                        }
                        
                        if ( 0 == $count_line ) {
                            printf( __( 'No log in search date from %s', 'wp_elv' ), $first_date );
                        }
                        fclose( $handle );
                    } else {
                        return printf( __( 'No log in search date from %s', 'wp_elv' ), $first_date );
                    }
                } 
                
            ?>
        </div>
    </form>
</div>

