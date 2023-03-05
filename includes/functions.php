<?php
/**
 * Helper Functions
 *
 * @package     Error_Log_Viewer_WP\Functions
 * @since       1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! function_exists( 'elvwp_file_size_convert' ) ) {

	function elvwp_file_size_convert( $bytes ) {
		$bytes        = floatval( $bytes );
			$ar_bytes = array(
				0 => array(
					'UNIT'  => 'TB',
					'VALUE' => pow( 1024, 4 ),
				),
				1 => array(
					'UNIT'  => 'GB',
					'VALUE' => pow( 1024, 3 ),
				),
				2 => array(
					'UNIT'  => 'MB',
					'VALUE' => pow( 1024, 2 ),
				),
				3 => array(
					'UNIT'  => 'KB',
					'VALUE' => 1024,
				),
				4 => array(
					'UNIT'  => 'B',
					'VALUE' => 1,
				),
			);

			foreach ( $ar_bytes as $ar_item ) {

				if ( $bytes >= $ar_item['VALUE'] ) {
					$result = $bytes / $ar_item['VALUE'];
					$result = str_replace( '.', ',', strval( round( $result, 2 ) ) ) . ' ' . $ar_item['UNIT'];

					break;
				}
			}

			return $result;
	}
}

if ( ! function_exists( 'elvwp_date_formate' ) ) {

	function elvwp_date_formate() {
		$date_format = get_option( 'date_format' );

		if ( 'F j, Y' === $date_format ) {
			$res_date_format = str_replace( array( 'Y', 'F', 'j' ), array( 'yyyy', 'mm', 'dd' ), $date_format );
		} elseif ( 'Y-m-d' === $date_format ) {
			$res_date_format = str_replace( array( 'Y', 'm', 'd' ), array( 'yyyy', 'mm', 'dd' ), $date_format );
		} elseif ( 'm/d/Y' === $date_format ) {
			$res_date_format = str_replace( array( 'Y', 'm', 'd' ), array( 'yyyy', 'mm', 'dd' ), $date_format );
		} elseif ( 'd/m/Y' === $date_format ) {
			$res_date_format = str_replace( array( 'Y', 'm', 'd' ), array( 'yyyy', 'mm', 'dd' ), $date_format );
		} else {
			$date_format     = 'd-m-Y';
			$res_date_format = str_replace( array( 'Y', 'm', 'd' ), array( 'yyyy', 'mm', 'dd' ), $date_format );
		}

		return $res_date_format;
	}
}

if ( ! function_exists( 'elvwp_get_last_log' ) ) {

	function elvwp_get_last_log() {
		global $wpdb;
		$table            = $wpdb->prefix . 'elvwp_error_logs';
		$elvwp_table_data = $wpdb->get_results( $wpdb->prepare( "SELECT * from {$table} ORDER BY created_at DESC LIMIT 1" ) );

		if ( isset( $elvwp_table_data[0] ) ) {
			return $elvwp_table_data[0];
		}

		return null;
	}
}




