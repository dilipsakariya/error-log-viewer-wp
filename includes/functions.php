<?php
/**
 * Helper Functions
 *
 * @package     EDD\PluginTemplateWP\Functions
 * @since       1.0.0
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


if( !function_exists( 'wp_elv_file_size_convert' ) ) {
	
	function wp_elv_file_size_convert( $bytes ) {
	    $bytes = floatval( $bytes );
	        $arBytes = array(
	            0 => array(
	                "UNIT" => "TB",
	                "VALUE" => pow( 1024, 4 )
	            ),
	            1 => array(
	                "UNIT" => "GB",
	                "VALUE" => pow( 1024, 3 )
	            ),
	            2 => array(
	                "UNIT" => "MB",
	                "VALUE" => pow( 1024, 2 )
	            ),
	            3 => array(
	                "UNIT" => "KB",
	                "VALUE" => 1024
	            ),
	            4 => array(
	                "UNIT" => "B",
	                "VALUE" => 1
	            ),
	        );

	    foreach ( $arBytes as $arItem ) {
	        
	        if ( $bytes >= $arItem["VALUE"] ) {
	            $result = $bytes / $arItem["VALUE"];
	            $result = str_replace( ".", "," , strval( round( $result, 2 ) ) )." ".$arItem["UNIT"];
	            break;
	        }
	    }
	    return $result;
	}
}

if( !function_exists( 'wp_elv_date_formate' ) ) {
	
	function wp_elv_date_formate() {
	    $date_format =  get_option( 'date_format' );
	    
	    if( 'F j, Y' === $date_format ){
		    $res_date_format = str_replace( array( 'Y', 'F', 'j' ), array( 'yyyy', 'mm', 'dd' ), $date_format );
	    } elseif ( 'Y-m-d' === $date_format ){
		    $res_date_format = str_replace( array( 'Y', 'm', 'd' ), array( 'yyyy', 'mm', 'dd' ), $date_format );
	    } elseif ( 'm/d/Y' === $date_format ){
		    $res_date_format = str_replace( array( 'Y', 'm', 'd' ), array( 'yyyy', 'mm', 'dd' ), $date_format );
	    } elseif ( 'd/m/Y' === $date_format ){
		    $res_date_format = str_replace( array( 'Y', 'm', 'd' ), array( 'yyyy', 'mm', 'dd' ), $date_format );
	    } else {
	    	$date_format = "d-m-Y";
		    $res_date_format = str_replace( array( 'Y', 'm', 'd' ), array( 'yyyy', 'mm', 'dd' ), $date_format );
	    }
	    return $res_date_format;
	}
}

if( !function_exists( 'wp_elv_get_last_log' ) ) {
	
	function wp_elv_get_last_log() {
	    global $wpdb;
	    $table           = $wpdb->prefix . 'wp_error_logs';
	    $wp_elv_table_data   = $wpdb->get_results( $wpdb->prepare( "SELECT * from {$table} ORDER BY created_at DESC LIMIT 1" ) );

	    if ( isset( $wp_elv_table_data[0] ) ) {
	    	return $wp_elv_table_data[0];
	    }
	    return null;
	}
}




