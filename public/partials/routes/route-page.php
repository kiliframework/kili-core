<?php
/**
 * Array of templates and context for page view.
 *
 * @package Kili_Core
 */
$object = get_queried_object();
$id = get_queried_object_id();
$template = get_page_template_slug();
$pagename = get_query_var('pagename');
$parent_slug = get_post_field( 'post_name', $object->post_parent );

if ( ! $pagename && $id ) {
	// If a static page is set as the front page, $pagename will not be set. Retrieve it from the queried object
	if ( $object ) {
		$pagename = $object->post_name;
	}
}

if ( $parent_slug ) {
	$templates[] = "{$type}-{$parent_slug}-{$pagename}.twig";
}

if ( $pagename ) {
	$pagename_decoded = urldecode( $pagename );
	if ( $pagename_decoded !== $pagename ) {
		$templates[] = "{$type}-{$pagename_decoded}.twig";
	}
	$templates[] = "{$type}-{$pagename}.twig";
}

if ( $id ) {
	$templates[] = "{$type}-{$id}.twig";
}

$templates[] = $this->get_protected_view( $object, $type );
$templates[] = "{$type}.twig";
