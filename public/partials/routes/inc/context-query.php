<?php
/**
 * Handle query posts and context for author and taxonomies.
 *
 * @package kiliframework
 */

$term = get_queried_object();
$args = array(
	'post_type' => get_post_type(),
	'post_status' => 'publish',
	'posts_per_page' => get_option( 'posts_per_page' ),
);
if ( isset( $term->taxonomy ) ) {
	$args['tax_query'] = array(
		array(
			'taxonomy' => $term->taxonomy,
			'field' => 'term_id',
			'terms' => $term->term_id,
		),
	);
}
$this->context['posts'] = Timber::get_posts( $args );
