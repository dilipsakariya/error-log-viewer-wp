var elvwp_debounce = function(func, wait, immediate) {
	var timeout;
	wait = wait || 250;
	return function() {
		var context = this,
			args    = arguments;
		var later   = function() {
			timeout = null;
			if ( ! immediate) {
				func.apply( context, args );
			}
		};
		var callNow = immediate && ! timeout;
		clearTimeout( timeout );
		timeout = setTimeout( later, wait );
		if (callNow) {
			func.apply( context, args );
		}
	};
};

function elvwp_parseQueryString(qs) {
	var query = (qs || '?').substr( 1 ),
		map   = {};
	query.replace(
		/([^&=]+)=?([^&]*)(?:&+|$)/g,
		function(match, key, value) {
			(map[key] = map[key] || value);
		}
	);
	return map;
}

function elvwp_stripe() {
	var errors = jQuery( '#elvwp_error_list' ).find( 'article' );
	errors.removeClass( 'alternate' );
	errors.filter( ':not(.hide):odd' ).addClass( 'alternate' );
}

function elvwp_filterSet() {
	var typeCount = {};
	var checked   = jQuery( '#elvwp_type_filter' ).find( 'input:checkbox:checked' ).map(
		function() {
			return jQuery( this ).val();
		}
	).get();
	var input     = jQuery( '#elvwp_path_filter' ).find( 'input' ).val();
	jQuery( '#elvwp_error_list article' ).each(
		function() {
			var a     = jQuery( this );
			var found = a.data( 'path' ).toLowerCase().indexOf( input.toLowerCase() );
			if ((input.length && found == -1) || (jQuery.inArray( a.data( 'type' ), checked ) == -1)) {
				a.addClass( 'hide' );
			} else {
				a.removeClass( 'hide' );
			}
			if (found != -1) {
				if (typeCount.hasOwnProperty( a.data( 'type' ) )) {
					++typeCount[a.data( 'type' )];
				} else {
					typeCount[a.data( 'type' )] = 1;
				}
			}
		}
	);
	jQuery( '#elvwp_type_filter' ).find( 'label' ).each(
		function() {
			var type = jQuery( this ).attr( 'class' );
			if (typeCount.hasOwnProperty( type )) {
				jQuery( 'span', jQuery( this ) ).data( 'current', typeCount[type] );
			} else {
				jQuery( 'span', jQuery( this ) ).data( 'current', 0 );
			}
		}
	);
}

function elvwp_sortEntries(type, order) {
	var aList = jQuery( '#elvwp_error_list' ).find( 'article' );
	aList.sort(
		function(a, b) {
			if ( ! isNaN( jQuery( a ).data( type ) )) {
				var entryA = parseInt( jQuery( a ).data( type ) );
				var entryB = parseInt( jQuery( b ).data( type ) );
			} else {
				var entryA = jQuery( a ).data( type );
				var entryB = jQuery( b ).data( type );
			}
			if (order == 'asc') {
				return (entryA < entryB) ? -1 : (entryA > entryB) ? 1 : 0;
			}
			return (entryB < entryA) ? -1 : (entryB > entryA) ? 1 : 0;
		}
	);
	jQuery( 'section' ).html( aList );
}
jQuery( document ).ready(
	function($) {
		$( "#elvwp_datepicker,#elvwp_select_date" ).datepicker(
			{
				format: ajax_script_object.date_format
			}
		);
		$( document ).on(
			'change',
			'#elvwp_datepicker,#elvwp_select_date',
			function() {
				var date_format_php = ajax_script_object.date_format_php;
				var date_val        = $( this ).val();
				if (date_format_php == 'F j, Y') {
					var date_val_arr = date_val.split( ' ' );
					date_val_arr[0]  = ajax_script_object.months[date_val_arr[0]]
					date_val         = date_val_arr.join( ' ' );
					$( this ).val( date_val );
				}
			}
		);
		$( '#elvwp_skip_to_bottom' ).on(
			'click',
			function() {
				$( document ).scrollTop( $( document ).height() );
			}
		);
		$( '#elvwp_skip_to_top' ).on(
			'click',
			function() {
				document.body.scrollTop            = 0;
				document.documentElement.scrollTop = 0;
			}
		);
		$( '#elvwp_error_log_purge' ).on(
			'click',
			function() {
				var r = confirm( "Are you sure want to delete this log?" );
				if (r == true) {
					var elvwp_error_log = $( '#elvwp_error_log' ).val();
					jQuery.ajax(
						{
							type: 'POST',
							url: ajax_script_object.ajax_url,
							dataType: "json",
							data: {
								'action': 'elvwp_purge_log',
								'elvwp_nonce': ajax_script_object.purge_log_nonce,
								'elvwp_error_log': elvwp_error_log
							},
							success: function(data) {
								if (data.success == 1) {
									window.location.reload();
								} else {
									alert( data.msg );
								}
							}
						}
					);
				}
			}
		);
		$( '#elvwp_type_filter' ).find( 'input:checkbox' ).on(
			'change',
			function() {
				elvwp_filterSet();
				elvwp_visible();
			}
		);
		$( '#elvwp_path_filter' ).find( 'input' ).on(
			'keyup',
			elvwp_debounce(
				function() {
					elvwp_filterSet();
					elvwp_visible();
				}
			)
		);
		$( '#elvwp_sort_options' ).find( 'a' ).on(
			'click',
			function() {
				var qs = elvwp_parseQueryString( $( this ).attr( 'href' ) );
				elvwp_sortEntries( qs.type, qs.order );
				$( this ).attr( 'href', '?type=' + qs.type + '&order=' + (qs.order == 'asc' ? 'desc' : 'asc') );
				if (qs.type == 'type') {
					$( 'span', $( this ) ).text( (qs.order == 'asc' ? 'z-a' : 'a-z') );
				} else {
					$( 'span', $( this ) ).text( (qs.order == 'asc' ? 'desc' : 'asc') );
				}
				return false;
			}
		);
		$( document ).on(
			'click',
			'a.codeblock, a.traceblock',
			function(e) {
				$( '#' + $( this ).data( 'for' ) ).toggle();
				return false;
			}
		);
		elvwp_stripe();
	}
);
$ = jQuery;
$( document ).ready(
	function() {
		if ($( '#elvwp_log_list_table' ).length > 0) {
			var elvwp_log_list_table = $( '#elvwp_log_list_table' ).dataTable(
				{
					"processing": true,
					"serverSide": true,
					'serverMethod': 'post',
					"searching": false,
					"dataType": "json",
					"dom": 'Bfrtip',
					"paging": true,
					"elvwp_visible": false,
					"lengthChange": true,
					"pageLength": 10,
					"order": [
					[0, "desc"]
					],
					"bSort": true,
					"fnDrawCallback": function(oSettings) {
						if ($( '#elvwp_log_list_table tr' ).length > 5) {
							$( '.dataTables_paginate' ).show();
						}
					},
					"ajax": datatable.datatable_ajax_url,
					columns: [{
						data: 'created_at'
					}, {
						data: 'plugin'
					}, {
						data: 'theme'
					}, {
						data: 'others'
					}, {
						data: 'elvwp_log_path'
					}, {
						data: 'action'
					}],
				}
			);
			$( document ).on(
				'click',
				'.elvwp_datatable_delete',
				function(e) {
					e.preventDefault();
					var r = confirm( "Are you sure want to delete this log?" );
					if (r == true) {
						var elvwp_datatable_deleteid = $( this )[0].id;
						jQuery.ajax(
							{
								type: 'POST',
								url: ajax_script_object.ajax_url,
								dataType: "json",
								data: {
									'action': 'elvwp_datatable_delete_data',
									'elvwp_nonce': ajax_script_object.delete_data_nonce,
									'elvwp_datatable_deleteid': elvwp_datatable_deleteid
								},
								success: function(data) {
									if (data.success == 1) {
										window.location.reload();
									} else {
										alert( data.msg );
									}
								}
							}
						);
					}
				}
			);
		}
	}
);
/*deactivation*/
(function($) {
	$(
		function() {
			var pluginSlug = 'error-log-viewer-by-wp-guru';
			// Code to fire when the DOM is ready.
			$( document ).on(
				'click',
				'tr[data-slug="' + pluginSlug + '"] .deactivate',
				function(e) {
					e.preventDefault();
					$( '.elvwp-popup-overlay' ).addClass( 'elvwp-active' );
					$( 'body' ).addClass( 'elvwp-hidden' );
				}
			);
			$( document ).on(
				'click',
				'.elvwp-popup-button-close',
				function() {
					elvwp_close_popup();
				}
			);
			$( document ).on(
				'click',
				".elvwp-serveypanel,tr[data-slug='" + pluginSlug + "'] .deactivate",
				function(e) {
					e.stopPropagation();
				}
			);
			$( document ).click(
				function() {
					elvwp_close_popup();
				}
			);
			$( '.elvwp-reason label' ).on(
				'click',
				function() {
					if ($( this ).find( 'input[type="radio"]' ).is( ':checked' )) {
						$( this ).next().next( '.elvwp-reason-input' ).show().end().end().parent().siblings().find( '.elvwp-reason-input' ).hide();
					}
				}
			);
			$( 'input[type="radio"][name="elvwp-selected-reason"]' ).on(
				'click',
				function(event) {
					$( ".elvwp-popup-allow-deactivate" ).removeAttr( 'disabled' );
					$( ".elvwp-popup-skip-feedback" ).removeAttr( 'disabled' );
					$( '.message.error-message' ).hide();
					$( '.elvwp-pro-message' ).hide();
				}
			);
			$( '.elvwp-reason-pro label' ).on(
				'click',
				function() {
					if ($( this ).find( 'input[type="radio"]' ).is( ':checked' )) {
						$( this ).next( '.elvwp-pro-message' ).show().end().end().parent().siblings().find( '.elvwp-reason-input' ).hide();
						$( this ).next( '.elvwp-pro-message' ).show()
						$( '.elvwp-popup-allow-deactivate' ).attr( 'disabled', 'disabled' );
						$( '.elvwp-popup-skip-feedback' ).attr( 'disabled', 'disabled' );
					}
				}
			);
			$( document ).on(
				'submit',
				'#elvwp-deactivate-form',
				function(event) {
					event.preventDefault();
					var _reason          = $( 'input[type="radio"][name="elvwp-selected-reason"]:checked' ).val();
					var _reason_details  = '';
					var deactivate_nonce = $( '.elvwp_deactivation_nonce' ).val();
					if (_reason == 2) {
						_reason_details = $( this ).find( "input[type='text'][name='better_plugin']" ).val();
					} else if (_reason == 7) {
						_reason_details = $( this ).find( "input[type='text'][name='other_reason']" ).val();
					}
					if ((_reason == 7 || _reason == 2) && _reason_details == '') {
						$( '.message.error-message' ).show();
						return;
					}
					$.ajax(
						{
							url: ajax_script_object.ajax_url,
							type: 'POST',
							data: {
								action: 'elvwp_error_log_deactivation',
								reason: _reason,
								reason_detail: _reason_details,
								elvwp_deactivation_nonce: deactivate_nonce
							},
							beforeSend: function() {
								$( ".elvwp-spinner" ).show();
								$( ".elvwp-popup-allow-deactivate" ).attr( "disabled", "disabled" );
							}
						}
					).done(
						function() {
							$( ".elvwp-spinner" ).hide();
							$( ".elvwp-popup-allow-deactivate" ).removeAttr( "disabled" );
							window.location.href = $( "tr[data-slug='" + pluginSlug + "'] .deactivate a" ).attr( 'href' );
						}
					);
				}
			);
			$( '.elvwp-popup-skip-feedback' ).on(
				'click',
				function(e) {
					window.location.href = $( "tr[data-slug='" + pluginSlug + "'] .deactivate a" ).attr( 'href' );
				}
			)

			function elvwp_close_popup() {
				$( '.elvwp-popup-overlay' ).removeClass( 'elvwp-active' );
				$( '#elvwp-deactivate-form' ).trigger( "reset" );
				$( ".elvwp-popup-allow-deactivate" ).attr( 'disabled', 'disabled' );
				$( ".elvwp-reason-input" ).hide();
				$( 'body' ).removeClass( 'elvwp-hidden' );
				$( '.message.error-message' ).hide();
				$( '.elvwp-pro-message' ).hide();
			}
		}
	);
})( jQuery );

function elvwp_visible() {
	var vis = jQuery( '#elvwp_error_list' ).find( 'article' ).filter( ':not(.hide)' );
	var len = vis.length;
	if (len == 0) {
		jQuery( '#nothingToShow' ).removeClass( 'hide' );
		jQuery( '.log_entries' ).text( '0 entries showing (' + script_object.total + ' filtered out)' );
	} else {
		jQuery( '#nothingToShow' ).addClass( 'hide' );
		if (len == script_object.total) {
			jQuery( '.log_entries' ).text( script_object.total + ' distinct entr' + ((script_object.total) == 1 ? 'y' : 'ies') );
		} else {
			jQuery( '.log_entries' ).text( len + ' distinct entr' + (len == 1 ? 'y' : 'ies') + ' showing (' + (script_object.total - len) + ' filtered out)' );
		}
	}
	jQuery( '#elvwp_type_filter' ).find( 'label span' ).each(
		function() {
			var count = (jQuery( '#elvwp_path_filter' ).find( 'input' ).val() == '' ? jQuery( this ).data( 'total' ) : jQuery( this ).data( 'current' ) + '/' + jQuery( this ).data( 'total' ));
			jQuery( this ).text( count );
		}
	);
	elvwp_stripe();
}
if (typeof script_object.error_type !== "undefined" && script_object.error_type) {
	jQuery( 'input:checkbox' ).removeAttr( 'checked' );
	jQuery( 'input[type=checkbox]' ).each(
		function() {
			var a            = jQuery( this );
			var checkedvalue = jQuery( this ).val();
			a.addClass( checkedvalue );
			jQuery( '.' + script_object.error_type ).prop( "checked", true );
			var typeCount = {};
			var checked   = jQuery( '#elvwp_type_filter' ).find( 'input:checkbox:checked' ).map(
				function() {
					return jQuery( this ).val();
				}
			).get();
			var input     = jQuery( '#elvwp_path_filter' ).find( 'input' ).val();
			jQuery( '#elvwp_error_list article' ).each(
				function() {
					var a     = jQuery( this );
					var found = a.data( 'path' ).toLowerCase().indexOf( input.toLowerCase() );
					if ((input.length && found == -1) || (jQuery.inArray( a.data( 'type' ), checked ) == -1)) {
						a.addClass( 'hide' );
					} else {
						a.removeClass( 'hide' );
					}
					if (found != -1) {
						if (typeCount.hasOwnProperty( a.data( 'type' ) )) {
							++typeCount[a.data( 'type' )];
						} else {
							typeCount[a.data( 'type' )] = 1;
						}
					}
					jQuery( "input[type=checkbox]" ).change(
						function() {
							if (jQuery( this ).is( ":checked" )) {
								jQuery( '#elvwp_skip_to_top' ).show();
							} else if (jQuery( this ).is( ":not(:checked)" )) {
								jQuery( '#elvwp_skip_to_top' ).hide();
							}
						}
					);
				}
			);
			jQuery( '#elvwp_type_filter' ).find( 'label' ).each(
				function() {
					var type = jQuery( this ).attr( 'class' );
					if (typeCount.hasOwnProperty( type )) {
						jQuery( 'span', jQuery( this ) ).data( 'current', typeCount[type] );
					} else {
						jQuery( 'span', jQuery( this ) ).data( 'current', 0 );
					}
				}
			);
		}
	);
	elvwp_visible();
}
