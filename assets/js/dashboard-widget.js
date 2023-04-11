(function($) {
	$( document ).on(
		'click',
		'a.codeblock, a.traceblock',
		function(e) {
			$( '#' + $( this ).data( 'for' ) ).toggle();
			return false;
		}
	);
})( jQuery );