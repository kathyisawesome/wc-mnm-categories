jQuery( document ).ready( function( $ ) {

	$( '#mnm_product_data input.mnm_use_category' ).on( 'change', function() {

		if( $( this ).val() === 'yes' ) {
			$( '#mnm_allowed_contents_options' ).hide();
			$( '.mnm_product_cat_field' ).show();
		} else {
			$( '#mnm_allowed_contents_options' ).show();
			$( '.mnm_product_cat_field' ).hide();
		}

	} );

	$( '#mnm_product_data input.mnm_use_category:checked' ).trigger( 'change' );

	$( ':input.wc-mnm-category-search' ).filter( ':not(.enhanced)' ).each( function() {

		if ( $( this ).data( 'sortable' ) ) {
			var $select = $(this);
			var $list   = $( this ).next( '.select2-container' ).find( 'ul.select2-selection__rendered' );

			$list.sortable({
				placeholder : 'ui-state-highlight select2-selection__choice',
				forcePlaceholderSize: true,
				items       : 'li:not(.select2-search__field)',
				tolerance   : 'pointer',
				stop: function() {
					$( $list.find( '.select2-selection__choice' ).get().reverse() ).each( function() {
						var id     = $( this ).data( 'data' ).id;
						var option = $select.find( 'option[value="' + id + '"]' )[0];
						$select.prepend( option );
					} );
				}
			});
		}
	});

});

