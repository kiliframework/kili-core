<?php
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
if ( file_exists(dirname(__FILE__) . '/composer/autoload.php') ) {
    require_once( dirname(__FILE__) . '/composer/autoload.php' );
}
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

	public function get_post_html() {
		$post_html = '';
		$fields = $this->get_post_fields();
		$fields_size = count( $fields );
		$i = 0;
		foreach ($fields as $field_key => $field_value) {
			$post_html .= $this->get_block_html( $field_key, $i );
			$i++;
		}
		return $post_html;
	}

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

	private function get_block_html( $field, $block_position = 0 ) {
		$html = '';
		if ( ! class_exists( '\Timber\Timber' ) ) {
			return $html;
		}
		$context = [];
		$layout_file = '{{layout}}';
		$find = array( $layout_file, '_' );
		$replace = array( $field, '-' );
		$new_layout_file = str_replace( $find, $replace, $layout_file );
		$layout_directory = '/blocks/pages/';
		$full_layout_directory = get_stylesheet_directory() . $layout_directory;
		if ( ! is_dir( $full_layout_directory ) ) {
			wp_mkdir_p( $full_layout_directory, 0755 );
		}
		$file_to_render = $this->get_file_name( $layout_directory, $new_layout_file, get_stylesheet_directory() . '/blocks/pages/' );
		$settings = array(
			'blocks_id' => 'kili_block_builder',
			'block_position' => $block_position,
			'block_unique_class' => $field . '_' . $block_position . '_' . $this->post_id,
		);
		$context['post'] = new \Timber\Post( $this->post_id );
		$context = array_merge( $context, $settings );
		$html = \Timber\Timber::compile( $file_to_render, $context );
		return $html;
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
