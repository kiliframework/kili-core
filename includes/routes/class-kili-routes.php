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

	/**
	 * Add filters to the site
	 *
	 * @return void
	 */
	public function add_filters() {
		$context = null;
		if ( class_exists( 'Timber' ) ) {
			$this->context = Timber::get_context();
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
		$template = $this->get_template( $type );
		if ( strcasecmp( $template, '' ) === 0 ) {
			return;
		}
		$this->do_render = false;
		if ( class_exists( 'Timber' ) && false === stripos( $template, '.php' ) ) {
			$this->context['post'] = new TimberPost();
			echo Timber::compile( $template, $this->context );
			return ;
		}
		include_once( $template );
	}


	/**
	 * Get the current view page template
	 *
	 * @param  mixed $type     Page type
	 * @param  mixed $fallback Template file to use if there is no template for the type
	 *
	 * @return string Template file route
	 */
	private function get_template( $type = 'home', $fallback = '' ) {
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
		return $template;
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
		$settings = array(
			'default' => '404.twig',
			'is_preview' => get_query_var( 'preview' ),
			'is_user_logged_in' => is_user_logged_in(),
			'object' => $object,
			'show' => $this->is_user_allowed_to_see( wp_get_current_user(), $object ),
			'type' => $type,
		);
		if ( $this->post_is_not_published( $object ) ) {
			$view = $this->get_protected_post_view( $settings );
		} elseif ( post_password_required( $object->ID ) ) {
			$settings['default'] = $type . '-password.twig';
			$view = $this->get_protected_post_view( $settings );
		} elseif ( ! get_query_var( 'pagename' ) && $object->ID ) {
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
	 * Check if a user can see a post
	 *
	 * @param  mixed $user Current user object.
	 * @param  mixed $post Post object.
	 * @return boolean Whether the user can see the post or not
	 */
	public function is_user_allowed_to_see( $user, $post ) {
		$is_allowed = false;
		if ( strcasecmp( '' . $user->ID, $post->post_author ) === 0 ) {
			$is_allowed = true;
		} elseif ( current_user_can('editor') ) {
			$is_allowed = true;
		} elseif ( current_user_can('administrator') ) {
			$is_allowed = true;
		}
		return $is_allowed;
	}

	/**
	 * Check if the post object is unpublished
	 *
	 * @param  mixed $post Post object.
	 * @return boolean Whether the post is unpublished or not
	 */
	private function post_is_not_published( $post ) {
		switch ($post->post_status) {
			case 'private':
			case 'draft':
			case 'future':
			case 'pending':
				return true;
				break;

			default:
				return false;
				break;
		}
		return false;
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
			$response = $this->get_protected_post_view_name( $options );
		} elseif ( $options['object'] ) {
			$response = "{$options['type']}-{$options['object']->post_type}.twig";
		}
		return $response;
	}

	/**
	 * Return the protected post template file name
	 *
	 * @param  mixed $options Current page data
	 *
	 * @return string The template file name
	 */
	private function get_protected_post_view_name( $options ) {
		$post_type = $options['object']->post_type;
		if ( strcasecmp( 'post', $post_type ) !== 0 || strcasecmp( 'page', $post_type ) !== 0 ) {
			return "single-{$post_type}.twig";
		} else if ( strcasecmp( 'page', $post_type ) !== 0 ) {
			return "{$options['type']}.twig";
		}
		return "{$options['type']}.twig";
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
