( function( $ ) {

	if ( 'object' !== typeof acf ) {
		return;
	}

	/**
	 * Uses current post types for use in include/exclude post object query.
	 *
	 * @since 0.1.0
	 *
	 * @return object
	 */
	acf.addFilter( 'select2_ajax_data', function( data, args, $input, field, instance ) {

		var fieldKeys = [ 'mai_cta_include', 'mai_cta_exclude' ];

		if ( ! fieldKeys.includes( data.field_key ) ) {
			return data;
		}

		var fields = acf.getFields(
			{
				key: 'mai_cta_display',
				parent: $input.parents( '.acf-row' ),
			}
		);

		if ( ! fields ) {
			return data;
		}

		data.post_type = fields.shift().val();

		return data;
	} );

} )( jQuery );
