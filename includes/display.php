<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

add_action( 'get_header', 'mai_do_ctas' );
/**
 * Displays CTAs on a single entry.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_do_ctas() {
	if ( ! is_singular() ) {
		return;
	}

	$post_type = get_post_type();
	$ctas      = mai_cta_get_ctas( $post_type );

	if ( ! $ctas ) {
		return;
	}

	foreach ( $ctas as $cta ) {
		if ( apply_filters( 'mai_cta_hide_cta', false, $cta ) ) {
			continue;
		}

		mai_cta_do_cta( $cta );
	}
}

/**
 * Displays a CTA.
 *
 * @since 0.1.0
 *
 * @param array $args The CTA args.
 *
 * @return void
 */
function mai_cta_do_cta( $args ) {
	$args = wp_parse_args( $args,
		[
			'location'   => '',
			'include'    => '',
			'exclude'    => '',
			'content'    => '',
			'taxonomies' => '',
			'skip'       => 6,
		]
	);

	// Bail if no cta content.
	if ( ! $args['content'] ) {
		return;
	}

	$locations = mai_cta_get_locations();

	// Bail if no location and no content. Only check isset for location since 'content' has no hook.
	if ( ! isset( $locations[ $args['location'] ] ) ) {
		return;
	}

	// Sanitize.
	$args['exclude'] = is_array( $args['exclude'] ) ? array_map( 'absint', $args['exclude'] ) : $args['exclude'];
	$args['include'] = is_array( $args['include'] ) ? array_map( 'absint', $args['include'] ) : $args['include'];

	// Bail if excluding this entry.
	if ( $args['exclude'] && in_array( get_the_ID(), (array) $args['exclude'] ) ) {
		return;
	}

	// If including this entry.
	$include = $args['include'] && in_array( get_the_ID(), (array) $args['include'] );

	// If not already including, check taxonomies.
	if ( ! $include && $args['taxonomies'] ) {

		// Loop through all taxonomies to give a chance to bail if NOT IN.
		foreach ( $args['taxonomies'] as $taxonomy => $data ) {
			$term_ids = isset( $data['terms'] ) ? $data['terms'] : [];
			$operator = isset( $data['operator'] ) ? $data['operator'] : 'IN';

			// Skip this taxonomy if we don't have the data we need.
			if ( ! ( $term_ids && $operator ) ) {
				continue;
			}

			$has_term = has_term( $term_ids, $taxonomy );

			// Bail if we have a term and we aren't displaying here.
			if ( $has_term && 'NOT IN' === $operator ) {
				return;
			}

			// Bail if we have don't a term and we are dislaying here.
			if ( ! $has_term && 'IN' === $operator ) {
				return;
			}
		}
	}

	if ( 'content' === $args['location'] ) {

		add_filter( 'the_content', function( $content ) use ( $args ) {
			if ( ! is_main_query() ) {
				return $content;
			}

			return mai_cta_add_cta( $content, $args['content'], $args['skip'] );
		});

	} else {

		$priority = isset( $locations[ $args['location'] ]['priority'] ) && $locations[ $args['location'] ]['priority'] ? $locations[ $args['location'] ]['priority'] : 10;

		add_action( $locations[ $args['location'] ]['hook'], function() use ( $args, $priority ) {
			echo mai_cta_get_processed_content( $args['content'] );
		}, $priority );
	}
}

/**
 * Gets CTAs by type.
 *
 * @since 0.1.0
 *
 * @param string $type
 * @param bool   $use_cache
 *
 * @return array
 */
function mai_cta_get_ctas( $type, $use_cache = true ) {
	if ( ! function_exists( 'get_field' ) ) {
		return [];
	}

	static $ctas = null;

	if ( isset( $ctas[ $type ] ) && $use_cache ) {
		return $ctas[ $type ];
	}

	if ( ! is_array( $ctas ) ) {
		$ctas = [];
	}

	$transient = sprintf( 'mai_ctas_%s', $type );

	if ( ! $use_cache || ( false === ( $queried_ctas = get_transient( $transient ) ) ) ) {

		$queried_ctas = [];
		$query        = new WP_Query(
			[
				'post_type'              => 'mai_cta',
				'posts_per_page'         => 100,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'suppress_filters'       => false, // https: //github.com/10up/Engineering-Best-Practices/issues/116
				'orderby'                => 'menu_order',
				'order'                  => 'ASC',
				'tax_query'              => [
					[
						'taxonomy' => 'mai_cta_display',
						'field'    => 'slug',
						'terms'    => $type,
					],
				],
			]
		);

		if ( $query->have_posts() ) {
			$taxonomies = get_object_taxonomies( $type );

			while ( $query->have_posts() ) : $query->the_post();
				$mai_ctas = get_field( 'mai_ctas' );

				if ( ! $mai_ctas ) {
					continue;
				}

				foreach ( $mai_ctas as $mai_cta ) {
					if ( isset( $mai_cta['display'] ) && ! in_array( $type, (array) $mai_cta['display'] ) ) {
						continue;
					}

					$cta = [
						'id'         => get_the_ID(),
						'location'   => isset( $mai_cta['location'] ) ? $mai_cta['location'] : '',
						'skip'       => isset( $mai_cta['skip'] ) ? $mai_cta['skip'] : '',
						'include'    => isset( $mai_cta['include'] ) ? $mai_cta['include'] : '',
						'exclude'    => isset( $mai_cta['exclude'] ) ? $mai_cta['exclude'] : '',
						'content'    => get_post()->post_content,
						'taxonomies' => [],
					];

					if ( $taxonomies ) {
						foreach ( $taxonomies as $taxonomy ) {
							if ( ! ( isset( $mai_cta[ $taxonomy ] ) && $mai_cta[ $taxonomy ] ) ) {
								continue;
							}

							$cta['taxonomies'][ $taxonomy ]['terms']     = $mai_cta[ $taxonomy ];
							$cta['taxonomies'][ $taxonomy ]['operator' ] = isset( $mai_cta[ $taxonomy . '_operator' ] ) ? $mai_cta[ $taxonomy . '_operator' ] : 'IN';
						}
					}

					$queried_ctas[] = $cta;
				}

			endwhile;
		}

		wp_reset_postdata();

		// Set transient, and expire after 1 hour.
		set_transient( $transient, $queried_ctas, 1 * HOUR_IN_SECONDS );
	}

	$ctas[ $type ] = $queried_ctas;

	return $ctas[ $type ];
}

/**
 * Get CTA hook locations.
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_cta_get_locations() {
	static $hooks = null;

	if ( ! is_null( $hooks ) ) {
		return $hooks;
	}

	$hooks = [
		'before_entry'         => [
			'hook'     => 'genesis_before_entry',
			'priority' => 10,
		],
		'before_entry_content' => [
			'hook'     => 'genesis_before_entry_content',
			'priority' => 10,
		],
		'content'              => [
			'hook'     => '', // No hooks, counted in content.
			'priority' => null,
		],
		'after_entry_content'  => [
			'hook'     => 'genesis_after_entry_content',
			'priority' => 10,
		],
		'after_entry'          => [
			'hook'     => 'genesis_after_entry',
			'priority' => 8, // 10 was after comments.
		],
		'before_footer'        => [
			'hook'     => 'genesis_after_content_sidebar_wrap',
			'priority' => 10,
		],
	];

	return apply_filters( 'mai_cta_location_hooks', $hooks );
}

/**
 * Get processed content.
 * Take from mai_get_processed_content() in Mai Engine.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_cta_get_processed_content( $content ) {
	if ( function_exists( 'mai_get_processed_content' ) ) {
		return mai_get_processed_content( $content );
	}

	/**
	 * Embed.
	 *
	 * @var WP_Embed $wp_embed Embed object.
	 */
	global $wp_embed;

	$content = $wp_embed->autoembed( $content );     // WP runs priority 8.
	$content = $wp_embed->run_shortcode( $content ); // WP runs priority 8.
	$content = do_blocks( $content );                // WP runs priority 9.
	$content = wptexturize( $content );              // WP runs priority 10.
	$content = wpautop( $content );                  // WP runs priority 10.
	$content = shortcode_unautop( $content );        // WP runs priority 10.
	$content = function_exists( 'wp_filter_content_tags' ) ? wp_filter_content_tags( $content ) : wp_make_content_images_responsive( $content ); // WP runs priority 10. WP 5.5 with fallback.
	$content = do_shortcode( $content );             // WP runs priority 11.
	$content = convert_smilies( $content );          // WP runs priority 20.

	return $content;
}

/**
 * Adds CTA to existing content/HTML.
 *
 * @since 0.1.0
 *
 * @uses DOMDocument
 *
 * @param string $content The existing html.
 * @param string $cta     The CTA html.
 * @param int    $skip    The amount of elements to skip before showing the CTA.
 *
 * @return string.
 */
function mai_cta_add_cta( $content, $cta, $skip ) {
	$cta  = trim( $cta );
	$skip = absint( $skip );

	if ( ! ( trim( $content ) && $cta && $skip ) ) {
		return $content;
	}

	$dom      = mai_cta_get_dom_document( $content );
	$xpath    = new DOMXPath( $dom );
	$elements = [ 'div', 'p', 'ul', 'blockquote' ];
	$elements = apply_filters( 'mai_cta_content_elements', $elements );
	$query    = [];

	foreach ( $elements as $element ) {
		$query[] = $element;
	}

	// self::p | self::div | self::ul | self::blockquote
	$query = 'self::' . implode( ' | self::', $query );

	$elements = $xpath->query( sprintf( '/html/body/*[%s][string-length() > 0]', $query ) );

	if ( ! $elements->length ) {
		return $content;
	}

	// Build the HTML node.
	$fragment = $dom->createDocumentFragment();
	$fragment->appendXml( $cta );

	$item = 0;

	foreach ( $elements as $element ) {
		$item++;

		if ( $skip !== $item ) {
			continue;
		}

		/**
		 * Add cta after this element. There is no insertAfter() in PHP ??\_(???)_/??.
		 * @link https://gist.github.com/deathlyfrantic/cd8d7ef8ba91544cdf06
		 */
		if ( null === $element->nextSibling ) {
			$element->parentNode->appendChild( $fragment );
		} else {
			$element->parentNode->insertBefore( $fragment, $element->nextSibling );
		}

		// No need to keep looping.
		break;
	}

	$content = $dom->saveHTML();

	return mai_cta_get_processed_content( $content );
}

/**
 * Gets DOMDocument object.
 * Copies mai_get_dom_document() in Mai Engine, but without dom->replaceChild().
 *
 * @since 0.1.0
 *
 * @param string $html Any given HTML string.
 *
 * @return DOMDocument
 */
function mai_cta_get_dom_document( $html ) {

	// Create the new document.
	$dom = new DOMDocument();

	// Modify state.
	$libxml_previous_state = libxml_use_internal_errors( true );

	// Load the content in the document HTML.
	$dom->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ) );

	// Remove <!DOCTYPE.
	$dom->removeChild( $dom->doctype );

	// Remove <html><body></body></html>.
	// $dom->replaceChild( $dom->firstChild->firstChild->firstChild, $dom->firstChild ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

	// Handle errors.
	libxml_clear_errors();

	// Restore.
	libxml_use_internal_errors( $libxml_previous_state );

	return $dom;
}
