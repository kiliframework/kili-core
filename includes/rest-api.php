<?php
/**
 * REST API functions
 *
 * @package Kili_Core
 */

function register_kili_rest() {
	register_rest_route(
		'api/v1', '/post-has-kili/(?P<id>\d+)',
		array(
			'methods'  => 'GET',
			'callback' => 'post_has_kili',
		)
	);
	register_rest_route(
		'api/v1', '/set-post-kili/',
		array(
			'methods'  => 'PUT',
			'callback' => 'update_post_kili',
		)
	);
}

function update_post_kili( $request ) {
	$post_id = sanitize_text_field( $request->get_param( 'id' ) );
	if ( ! isset( $post_id ) || $post_id == '' ) {
		return new WP_Error( 'invalid_id', __('Please provide a valid post id', 'kili-core'), array( 'status' => 400 ) );
	}
	$value = sanitize_text_field( $request->get_param( 'value' ) );
	if ( ! isset( $value ) || $value == '' ) {
		return new WP_Error( 'invalid_value', __('Please provide a valid value for the field', 'kili-core'), array( 'status' => 400 ) );
	}
	$result = update_post_meta( $post_id , 'enable_kili', $value );
	return rest_ensure_response( $result != false );
}

function post_has_kili( $request ) {
	$post_id = sanitize_text_field( $request->get_param( 'id' ) );
	if ( ! isset( $post_id ) || $post_id == '' ) {
		return new WP_Error( 'invalid_id', __('Please provide a valid post id', 'kili-core'), array( 'status' => 400 ) );
	}
	$is_active = get_post_meta( $post_id, 'enable_kili', true);
	return rest_ensure_response( $is_active );
}

add_action( 'rest_api_init', function () {
	register_kili_rest();
} );
