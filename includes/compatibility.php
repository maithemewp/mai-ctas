<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

add_action( 'simple_page_ordering_ordered_posts', 'mai_cta_simple_page_ordering_delete_transients', 10, 2 );
/**
 * Delete all transients after simple page reordering.
 *
 * @param WP_Post $post    The current post being reordered.
 * @param array   $new_pos The post ID => page attributes values.
 *
 * @return void
 */
function mai_cta_simple_page_ordering_delete_transients( $post, $new_pos ) {
	if ( ! isset( $post->post_type ) || 'mai_cta' !== $post->post_type ) {
		return;
	}

	mai_cta_delete_transients( $post->ID );
}
