<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/kiliframework/kili-core
 * @since      1.0.0
 *
 * @package    Kili_Core
 * @subpackage Kili_Core/admin
 */
/**
 * Load dependencies
 */
use Timber\Twig;
use Timber\ImageHelper;
use Timber\Admin;
use Timber\Integrations;
use Timber\PostGetter;
use Timber\TermGetter;
use Timber\Site;
use Timber\URLHelper;
use Timber\Helper;
use Timber\Pagination;
use Timber\Request;
use Timber\User;
use Timber\Loader;
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Kili_Core
 * @subpackage Kili_Core/admin
 * @author     Kili Team <hello@kiliframework.org>
 */
class Kili_Blocks {
	private $post_id;
	/**
	 * Class constructor
	 *
	 * @param  mixed $post_id The post id
	 *
	 * @return void
	 */
	public function __construct( $post_id ) {
		if ( isset( $post_id ) && strcasecmp( $post_id . '', '' ) !== 0 ) {
			$this->post_id = $post_id;
		}
	}
	/**
	 * Get the post blocks html
	 *
	 * @return string The processed code
	 */
	public function get_post_html() {
		$post_html = '';
		$fields = $this->get_post_fields();
		$fields_size = count( $fields );
		$i = 0;
		foreach ($fields as $field_key => $field_value) {
			$post_html .= $this->get_block_html( $field_key, $field_value, $i );
			$i++;
		}
		$post_html = $this->clean_code( $post_html );
		return $post_html;
	}
	/**
	 * Get current post custom fields
	 *
	 * @return array The post custom fields
	 */
	private function get_post_fields() {
		if ( ! function_exists( 'get_fields' ) ) {
			return [];
		}
		$acf_fields = get_fields( $this->post_id );
		if ( ! $acf_fields ) {
			return [];
		}
		return $acf_fields;
	}
	/**
	 * Obtain the html content for a custom block
	 *
	 * @param  mixed $field           Block name.
	 * @param  mixed $field_content   Block fields.
	 * @param  mixed $block_position Block position in the page/post.
	 *
	 * @return string Processed block html
	 */
	private function get_block_html( $field, $field_content, $block_position = 0 ) {
		if ( ! class_exists( '\Timber\Timber' ) ) {
			return '';
		}
		$context = \Timber\Timber::get_context();
		$layout_file = '{{layout}}';
		$find = array( $layout_file, '_' );
		$replace = array( $field, '-' );
		$new_layout_file = str_replace( $find, $replace, $layout_file );
		$layout_directory = '/blocks/';
		$full_layout_directory = get_stylesheet_directory() . $layout_directory;
		if ( ! is_dir( $full_layout_directory ) ) {
			wp_mkdir_p( $full_layout_directory, 0755 );
		}
		$file_to_render = $this->get_file_name( $layout_directory, $new_layout_file, get_stylesheet_directory() . $layout_directory );
		if ( 0 == strcasecmp( $file_to_render, '' ) ) {
			return '';
		}
		$settings = $this->get_block_settings( $field, $field_content, $block_position );
		$context = array_merge( $context, $settings );
		$html = \Timber\Timber::compile( $file_to_render, $context );
		return $html;
	}
	/**
	 * Get custom block data
	 *
	 * @param  mixed $field           Block name.
	 * @param  mixed $field_content   Block fields.
	 * @param  mixed $block_position Block position in the page/post.
	 *
	 * @return array Block custom settings
	 */
	private function get_block_settings( $field, $field_content, $block_position ) {
		return array(
			'blocks_id' => 'kili_block_builder',
			'block_position' => $block_position,
			'block_unique_class' => $field . '_' . $block_position . '_' . $this->post_id,
			'post' => new \Timber\Post( $this->post_id ),
			'page_block' => $field_content[0],
		);
	}
	/**
	 * Remove unnecessary whitespaces, line breaks and tabulations
	 *
	 * @param  mixed $html The string to be cleaned.
	 *
	 * @return string The string without unnecessary whitespaces, line breaks and tabulations
	 */
	public function clean_code( $html = '' ) {
		if ( 0 === strcasecmp( $html, '' ) ) {
			return '';
		}
		$clean_html = $html;
		// Remove whitespaces.
		$clean_html = str_replace( array( "\r\n", "\r", "\n", "\t", '  ' ), '', $clean_html );
		// Collapse adjacent spaces into a single space.
		$clean_html = preg_replace( ' {2,}', ' ', $clean_html );
		return $clean_html;
	}
	/**
	 * Returns the full file path of the file to be rendered, if exists
	 *
	 * @param string $layout_directory Where should be the file.
	 * @param string $layout_file The file name.
	 * @param string $default_directory Directory where are located the default files.
	 * @return string The full file path of the file to be rendered, if exists; else, an empty string
	 */
	private function get_file_name( $layout_directory, $layout_file, $default_directory ) {
		$file_name = '';
		if ( file_exists( get_stylesheet_directory() . $layout_directory . $layout_file . '.twig' ) ) {
			$file_name = get_stylesheet_directory() . $layout_directory . $layout_file . '.twig';
		} elseif ( file_exists( $default_directory . $layout_file . '.twig' ) ) {
			$file_name = $default_directory . $layout_file . '.twig';
		} elseif ( file_exists( get_template_directory() . $layout_directory . $layout_file . '.twig' ) ) {
			$file_name = get_template_directory() . $layout_directory . $layout_file . '.twig';
		}
		return $file_name;
	}
}
