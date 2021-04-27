<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

add_action( 'load-post-new.php', 'mai_ctas_create_display_terms' );
add_action( 'load-post.php', 'mai_ctas_create_display_terms' );
/**
 * Creates default content type terms.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_ctas_create_display_terms() {
	$screen = get_current_screen();

	if ( 'mai_cta' !== $screen->post_type ) {
		return;
	}

	$create     = [];
	$post_types = get_post_types( [ 'public' => true ], 'objects' );
	unset( $post_types['attachment'] );

	foreach ( $post_types as $slug => $post_type ) {
		$create[ $slug ] = $post_type->label;
	}

	if ( ! $create ) {
		return;
	}

	foreach ( $create as $slug => $label ) {
		if ( term_exists( $slug, 'mai_cta_display' ) ) {
			continue;
		}

		$data = wp_insert_term( $label, 'mai_cta_display', [ 'slug' => $slug ] );
	}
}

add_filter( 'acf/load_field/key=mai_cta_display', 'mai_cta_load_display' );
/**
 * Loads display terms as choices.
 *
 * @since 0.1.0
 *
 * @param array $field The field data.
 *
 * @return array
 */
function mai_cta_load_display( $field ) {
	$field['choices'] = [];
	$terms            = get_terms(
		[
			'taxonomy'   => 'mai_cta_display',
			'hide_empty' => false,
		]
	);

	if ( $terms && ! is_wp_error( $tersm ) ) {
		$field['choices'] = wp_list_pluck( $terms, 'name', 'slug' );
	}

	return $field;
}

add_filter( 'acf/load_value/key=mai_cta_display', 'mai_cta_load_display_value', 10, 3 );
/**
 * Loads display terms as choices.
 *
 * @since 0.1.0
 *
 * @param $value   mixed      The field value.
 * @param $post_id int|string The post ID where the value is saved.
 * @param $field   array      The field array containing all settings.
 *
 * @return array
 */
function mai_cta_load_display_value( $value, $post_id, $field ) {
	$terms = get_the_terms( $post_id, 'mai_cta_display' );

	if ( $terms && ! is_wp_error( $terms ) ) {
		$value = wp_list_pluck( $terms, 'slug' );
	}

	return $value;
}

add_action( 'init', 'mai_cta_add_settings_metabox', 99 );
/**
 * Add CTA Settings metabox.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_cta_add_settings_metabox() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		[
			'key'      => 'mai_cta_field_group',
			'title'    => __( 'CTA Display Settings', 'mai-ctas' ),
			'fields'   => [
				[
					'key'          => 'mai_ctas',
					'label'        => __( 'Display Conditions', 'mai-ctas' ),
					'name'         => 'mai_ctas',
					'type'         => 'repeater',
					'collapsed'    => 'display',
					'min'          => 1,
					'max'          => 0,
					'layout'       => 'row',
					'button_label' => __( 'Add Display Condition', 'mai-ctas' ),
					'sub_fields'   => mai_cta_get_settings_metabox_sub_fields(),
				],
			],
			'location' => [
				[
					[
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'mai_cta',
					],
				],
			],
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
		]
	);
}

/**
 * Gets CTA settings fields.
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_cta_get_settings_metabox_sub_fields() {
	$fields = [
		[
			'label'        => __( 'Display location', 'mai-ctas' ),
			'instructions' => __( 'Location of CTA.', 'mai-ctas' ),
			'key'          => 'mai_cta_location',
			'name'         => 'location',
			'type'         => 'radio',
			'required'     => 1,
			'choices'      => [
				''                     => __( 'None (inactive)', 'mai-ctas' ),
				'before_entry'         => __( 'Before entry', 'mai-ctas' ),
				'before_entry_content' => __( 'Before entry content', 'mai-ctas' ),
				'content'              => __( 'In content', 'mai-ctas' ),
				'after_entry_content'  => __( 'After entry content', 'mai-ctas' ),
				'after_entry'          => __( 'After entry', 'mai-ctas' ),
				'before_footer'        => __( 'Before footer', 'mai-ctas' ),
			],
		],
		[
			'label'             => __( 'Elements', 'mai-ctas' ),
			'instructions'      => __( 'Display after this many elements.', 'mai-ctas' ),
			'key'               => 'mai_cta_skip',
			'name'              => 'skip',
			'type'              => 'number',
			'append'            => __( 'elements', 'mai-ctas' ),
			'required'          => 1,
			'default_value'     => 6,
			'min'               => 1,
			'max'               => '',
			'step'              => 1,
			'conditional_logic' => [
				[
					[
						'field'    => 'mai_cta_location',
						'operator' => '==',
						'value'    => 'content',
					],
				],
			],
		],
		[
			'label'             => __( 'Content types', 'mai-ctas' ),
			'instructions'      => __( 'Display on these content types.', 'mai-ctas' ),
			'key'               => 'mai_cta_display',
			'name'              => 'display',
			'type'              => 'select',
			'required'          => 1,
			'ui'                => 1,
			'multiple'          => 1,
			'choices'           => [],
			'conditional_logic' => [
				[
					[
						'field'    => 'mai_cta_location',
						'operator' => '!=empty',
					],
				],
			],
		],
	];

	$taxonomies = get_taxonomies( [ 'public' => 'true' ], 'objects' );
	unset( $taxonomies['post_format'] );

	if ( $taxonomies ) {
		foreach ( $taxonomies as $taxonomy ) {
			$conditions = [];

			foreach ( $taxonomy->object_type as $type ) {
				$term = get_term_by( 'slug', $type, 'mai_cta_display' );

				if ( ! $term ) {
					continue;
				}

				$conditions[] = [
					[
						'field'    => 'mai_cta_location',
						'operator' => '!=empty',
					],
					[
						'field'    => 'mai_cta_display',
						'operator' => '==',
						'value'    => $term->slug,
					],
				];
			}

			if ( ! $conditions ) {
				continue;
			}

			$fields = array_merge( $fields, [
				[
					'label'             => $taxonomy->label,
					'key'               => sprintf( 'mai_cta_terms_%s', $taxonomy->name ),
					'name'              => $taxonomy->name,
					'type'              => 'taxonomy',
					'instructions'      => sprintf( __( 'Limit to entries with these %s.', 'mai-ctas' ), strtolower( $taxonomy->label ) ),
					'required'          => 0,
					'taxonomy'          => $taxonomy->name,
					'field_type'        => 'multi_select',
					'allow_null'        => 0,
					'add_term'          => 0,
					'save_terms'        => 0,
					'load_terms'        => 0,
					'return_format'     => 'id',
					'multiple'          => 1,
					'conditional_logic' => $conditions,
				]
			] );
		}
	}

	$fields = array_merge( $fields, [
		// [
		// 	'label'             => __( 'Include', 'mai-ctas' ),
		// 	'key'               => 'mai_cta_include',
		// 	'name'              => 'include',
		// 	'type'              => 'post_object',
		// 	'instructions'      => __( 'Show on specific entries.', 'mai-ctas' ),
		// 	'required'          => 0,
		// 	'post_type'         => '',
		// 	'taxonomy'          => '',
		// 	'allow_null'        => 0,
		// 	'multiple'          => 1,
		// 	'return_format'     => 'id',
		// 	'ui'                => 1,
		// 	'conditional_logic' => [
		// 		[
		// 			[
		// 				'field'    => 'mai_cta_location',
		// 				'operator' => '!=empty',
		// 			],
		// 			[
		// 				'field'    => 'mai_cta_display',
		// 				'operator' => '!=empty',
		// 			],
		// 		],
		// 	],
		// ],
		[
			'label'             => __( 'Exclude entries', 'mai-ctas' ),
			'key'               => 'mai_cta_exclude',
			'name'              => 'exclude',
			'type'              => 'post_object',
			'instructions'      => __( 'Hide on specific entries.', 'mai-ctas' ),
			'required'          => 0,
			'post_type'         => '',
			'taxonomy'          => '',
			'allow_null'        => 0,
			'multiple'          => 1,
			'return_format'     => 'id',
			'ui'                => 1,
			'conditional_logic' => [
				[
					[
						'field'    => 'mai_cta_location',
						'operator' => '!=empty',
					],
					[
						'field'    => 'mai_cta_display',
						'operator' => '!=empty',
					],
				],
			],
		],
	] );

	return $fields;
}
