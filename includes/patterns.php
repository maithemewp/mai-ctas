<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

add_action( 'init', 'mai_ctas_register_block_pattern_categories' );
/**
 * Registers block pattern categories.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_ctas_register_block_pattern_categories() {
	if ( ! function_exists( 'register_block_pattern_category' ) ) {
		return;
	}

	register_block_pattern_category(
		'ctas',
		[
			'label' => _x( 'CTAs', 'Block pattern category', 'mai-ctas' ),
		]
	);
}

add_action( 'init', 'mai_ctas_register_block_patterns' );
/**
 * Registers block patterns.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_ctas_register_block_patterns() {
	if ( ! function_exists( 'register_block_pattern' ) ) {
		return;
	}

	$dir      = MAI_CTAS_PLUGIN_DIR . 'patterns/';
	$cats     = [ 'ctas' ];
	$keywords = [ 'mai', 'cta', 'ctas', 'call', 'action' ];
	$patterns = [
		[
			'title'       => __( 'CTA: Solid Color Background', 'mai-ctas' ),
			'description' => _x( 'Basic call to action with a solid color background.', 'Block pattern description', 'mai-ctas' ),
			'slug'        => 'solid-background',
		],
		[
			'title'       => __( 'CTA: Image Background', 'mai-ctas' ),
			'description' => _x( 'Basic call to action with an image background.', 'Block pattern description', 'mai-ctas' ),
			'slug'        => 'image-background',
		],
		[
			'title'       => __( 'CTA: Image/Text Columns', 'mai-ctas' ),
			'description' => _x( 'Image and text columns call to action.', 'Block pattern description', 'mai-ctas' ),
			'slug'        => 'image-text',
		]
	];

	foreach ( $patterns as $pattern ) {
		$file = $dir . $pattern['slug'] . '.php';

		if ( ! file_exists( $file ) ) {
			continue;
		}

		register_block_pattern(
			sprintf( 'mai-ctas/%s', $pattern['slug'] ),
			[
				'title'       => $pattern['title'],
				'description' => $pattern['description'],
				'categories'  => $cats,
				'keywords'    => $keywords,
				'content'     => file_get_contents( $file ),
			]
		);
	}
}
