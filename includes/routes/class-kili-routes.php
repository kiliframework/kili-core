<?php

/**
 * This class handles the WordPress template routes
 *
 * @link       https://github.com/kiliframework/kili-core
 * @since      1.0.0
 *
 * @package    Kili_Core
 * @subpackage Kili_Core/admin
 */

/**
 * This class handles the WordPress template routes
 *
 * @link       https://github.com/kiliframework/kili-core
 * @since      1.0.0
 *
 * @package    Kili_Core
 * @subpackage Kili_Core/admin
 * @author     Kili Team <hello@kiliframework.org>
 */

 class Kili_Routes {
	/**
	 * Template type names to be used for dynamic hooks.
	 *
	 * @var array
	 */
	private $template_types = array(
		'404', 'search', 'taxonomy', 'frontpage', 'home', 'attachment',
		'single', 'page', 'singular', 'category', 'tag', 'author', 'date',
		'archive', 'commentspopup', 'paged', 'index',
	);

	/**
	 * Validate if the page template has been rendered
	 *
	 * @var boolean
	 */
	private $do_render = true;

	/**
	 * Object for keeping the context
	 *
	 * @var mixed
	 */
	public $context;

	 /**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// #code
	}

	/**
	 * Get current Twig view based on WordPress page hierarchy.
	 * Aditional add to context required views data.
	 *
	 * @param array $context Page context data.
	 * @return void
	 */
	public function process_view( $context = null) {
		$this->context = $context;
		foreach ( $this->template_types as $type ) {
			add_filter( $type . '_template', array( $this, 'query_template' ) );
		}
	}

	public function add_filters() {
		$context = null;
		if ( class_exists( 'Timber' ) ) {
			$context = Timber::get_context();
		}
		$this->process_view( $context );
	}

	/**
	 * Filter for current page template
	 * and includes the correct view twig file, if exists
	 *
	 * @param string $fallback Provide file fallback if the .twig file is missing.
	 * @return void
	 */
	public function query_template( $fallback ) {
		if ( ! $this->do_render ) {
			return;
		}
		// trim '_template' from end.
		$type      = substr( current_filter(), 0, - 9 );
		$templates = array();
		switch ( $type ) {
			case '404':
			case 'search':
			case 'attachment':
			case 'taxonomy':
			case 'frontpage':
			case 'home':
			case 'single':
			case 'page':
			case 'singular':
			case 'category':
			case 'tag':
			case 'author':
			case 'archive':
				include_once( plugin_dir_path( __DIR__ ) . '../public/partials/routes/route-' . $type . '.php' );
				break;
			default:
				$templates = array( $type . '.twig' );
		}
		$template = $this->locate_template( $templates, $fallback );
		if ( strcasecmp( $template, '' ) === 0 ) {
			return;
		}
		$this->do_render = false;
		if ( strpos( $template, 'index.php' ) === false && class_exists( 'Timber' ) ) {
			echo Timber::compile( $template, $this->context );
			return ;
		}
		include_once( $template );
	}

	/**
	 * Used to quickly retrieve the path of a template without including the file
	 * extension. It will also check the parent theme, if the file exists, with
	 * the use of locate_template().
	 *
	 * @param array  $template_names array with the required view names.
	 * @param string $fallback Name to assign if no template is found.
	 * @return string View path
	 */
	private function locate_template( $template_names, $fallback ) {
		$located = $fallback;
		foreach ( (array) $template_names as $template_name ) {
			$name = $this->get_template_filename( $template_name );
			if ( strcasecmp( $name, '' ) !== 0 ) {
				return $name;
			}
		}
		return $located;
	}

	/**
	 * Check if the template exists and return its path
	 *
	 * @param string $template_name The template name.
	 * @return string The path to the template file
	 */
	private function get_template_filename( $template_name ) {
		$filename = '';
		if ( ! $template_name ) {
			$filename = '';
		} elseif ( file_exists( STYLESHEETPATH . '/views/' . $template_name ) ) {
			$filename = STYLESHEETPATH . '/views/' . $template_name;
		} elseif ( file_exists( TEMPLATEPATH . '/views/' . $template_name ) ) {
			$filename = TEMPLATEPATH . '/views/' . $template_name;
		} elseif ( file_exists( ABSPATH . WPINC . '/views/theme-compat/' . $template_name ) ) {
			$filename = ABSPATH . WPINC . '/views/theme-compat/' . $template_name;
		}
		return $filename;
	}

	/**
	 * Extend support for post status states
	 *
	 * @param object $object get the queried object.
	 * @param string $type view type.
	 * @return string View file name
	 */
	public function get_protected_view( $object, $type ) {
		$view = '';
		$pagename = get_query_var( 'pagename' );
		$is_user_logged_in = is_user_logged_in();
		$is_preview = get_query_var( 'preview' );
		$this->context['post'] = new TimberPost();
		$current_user = wp_get_current_user();
		if ( is_page_template( 'page-templates/layout-builder.php' ) ) {
			$this->context['is_kili'] = true;
		}
		if ( strcasecmp( $object->post_status, 'private' ) === 0 || strcasecmp( $object->post_status, 'draft' ) === 0 || strcasecmp( $object->post_status, 'future' ) === 0 || strcasecmp( $object->post_status, 'pending' ) === 0 ) {
			$view = $this->get_protected_post_view( array(
				'default' => '404.twig',
				'is_preview' => $is_preview,
				'is_user_logged_in' => $is_user_logged_in,
				'object' => $object,
				'show' => strcasecmp( '' . $current_user->ID, $object->post_author ) === 0 || current_user_can('editor') || current_user_can('administrator'),
				'type' => $type,
			) );
		} elseif ( post_password_required( $object->ID ) ) {
			$view = $this->get_protected_post_view( array(
				'default' => "{$type}-password.twig",
				'is_preview' => $is_preview,
				'is_user_logged_in' => $is_user_logged_in,
				'object' => $object,
				'show' => strcasecmp( '' . $current_user->ID, $object->post_author ) === 0,
				'type' => $type,
			) );
		} elseif ( ! $pagename && $object->ID ) {
			$view = $this->get_page_view_name( array(
				'object' => $object,
				'type' => $type,
			) );
		} elseif ( is_page_template( get_page_template_slug( $object->id ) ) ) {
			$view = str_ireplace( 'php', 'twig', basename( get_page_template_slug( $object->id ) ) );
		}
		return $view;
	}

	/**
	 * Return the view file name for protected pages and posts
	 *
	 * @param array $options View options.
	 * @return string View name
	 */
	private function get_protected_post_view( $options = array() ) {
		$response = $options['default'] ? $options['default'] : '';
		if ( ! $options['show'] ) {
			return $response;
		}
		if ( $options['is_preview'] && $options['is_user_logged_in'] ) {
			if ( strcasecmp( 'post', $options['object']->post_type ) !== 0 || strcasecmp( 'page', $options['object']->post_type ) !== 0 ) {
				return "single-{$options['object']->post_type}.twig";
			} else if ( strcasecmp( 'page', $options['object']->post_type ) !== 0 ) {
				return "{$options['type']}.twig";
			}
			return "{$options['type']}.twig";
		} elseif ( $options['object'] ) {
			return "{$options['type']}-{$options['object']->post_type}.twig";
		}
		return $response;
	}
	/**
	 * Return the view file name
	 *
	 * @param array $options View options.
	 * @return string View file name
	 */
	private function get_page_view_name( $options ) {
		$response = '';
		$object = $options['object'];
		$type = $options['type'];
		$pagename = $object->post_name;
		$is_custom_post = $this->is_custom_post_type( $object );
		$file_extension = 'php';
		if ( class_exists( 'Timber' ) ) {
			$file_extension = 'twig';
		}
		if ( $is_custom_post ) {
			$response = "{$type}-{$object->post_type}." . $file_extension;
		} elseif ( $pagename ) {
			$response = "{$type}-{$pagename}." . $file_extension;
		} elseif ( $object->ID ) {
			$response = "{$type}-{$object->ID}." . $file_extension;
		} elseif ( $object ) {
			$response = "{$type}-{$object->post_type}." . $file_extension;
		}
		return $response;
	}

	/**
	 * Determine if a post object is a custom post type
	 *
	 * @param object $post The post object.
	 * @return boolean If is a custom post type
	 */
	public static function is_custom_post_type( $post = null ) {
		$all_custom_post_types = get_post_types( array(
			'_builtin' => false,
		) );
		// there are no custom post types.
		if ( empty( $all_custom_post_types ) ) {
			return false;
		}
		$custom_types = array_keys( $all_custom_post_types );
		$current_post_type = get_post_type( $post );
		// could not detect current type.
		if ( ! $current_post_type ) {
			return false;
		}
		return in_array( $current_post_type, $custom_types, true );
	}
 }
