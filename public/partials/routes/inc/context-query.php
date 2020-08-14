<?php
/**
 * Handle query posts and context for author and taxonomies.
 *
 * @package kiliframework
 */

$term = get_queried_object();
$args = array(
	'post_type'      => get_post_type(),
	'post_status'    => 'publish',
	'orderby'        => 'date',
	'order'          => 'DESC',
	'posts_per_page' => get_option( 'posts_per_page' ),
);
if ( isset( $term->taxonomy ) ) {
	$args['tax_query'] = array(
		array(
			'taxonomy' => $term->taxonomy,
			'field' => 'slug',
			'terms'    => $term->slug,
			'include_children' => false,
		),
	);
}
$this->context['posts'] = class_exists('Timber') ? Timber::get_posts( $args ) : get_posts( $args );
