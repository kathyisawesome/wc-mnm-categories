jQuery( document ).ready(function() {

	$( "#mnm_product_data .mnm_use_category_field input" ).change( function() { 

		if( $( this ).val() == 'yes' ) {
			$( "#mnm_allowed_contents_options" ).hide();
			$( ".mnm_product_cat_field" ).show();
		} else {
			$( "#mnm_allowed_contents_options" ).show();
			$( ".mnm_product_cat_field" ).hide();
		}

	} );

	$( "#mnm_product_data input.mnm_use_category:checked" ).change();

} );


