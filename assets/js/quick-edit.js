( function( $ ) {
	'use strict';

	const wp_inline_edit_function = inlineEditPost.edit;

	// we overwrite the it with our own.
	inlineEditPost.edit = function( post_id ) {

		// let's merge arguments of the original function.
		wp_inline_edit_function.apply( this, arguments );

		// get the post ID from the argument.
		if ( typeof( post_id ) == 'object' ) { // if it is object, get the ID number.
			post_id = parseInt( this.getId( post_id ) );
		}

		// add rows to variables.
		const edit_row = $( '#edit-' + post_id );
		const post_row = $( '#post-' + post_id );

		const author = $( '.column-wpex_author_staff_id', post_row ).find( 'input[type="hidden"]' ).val();

		if ( '0' === author ) {
			author = '';
		}

		$( 'select[name="wpex_author_staff_id"]', edit_row ).val( author );
	}

} ) ( jQuery );
