<?php
/**
 * Plugin Name: Post Formats UI
 * Plugin URI: 
 * Description: The post formats UI pulled from 3.6
 * Version:     0.0.1
 * Author:      Yuri Victor
 * Author URI:  http://yurivictor.com
 * Credits:     Just ripped all the code from 3.6-beta1-24067
 * License:     GPL ( attached )
 */

require( 'custom-post-formats-fs.php' );

if ( ! class_exists( 'Post_Formats_UI' ) ) :

final class Post_Formats_UI {

	/** Constants *************************************************************/

	const version          = '0.0.1';
	const min_wp_version   = '3.7';
	const key              = 'post-formats-ui';
	const url_endpoint     = 'post-formats-ui';
	const nonce_key        = 'post_formats_ui_nonce';

	/** Variables *************************************************************/

	private static $post             = null;
	private static $post_type        = null;
	private static $post_type_object = null;

	private static $format_class          = '';
	private static $post_format_set_class = '';
	private static $post_format_options   = '';

	private static $legacy_wp = false;

	/** Load Methods **********************************************************/

	/**
	 * Load necessary functions
	 */
	public static function load() {

		if ( self::is_wp_too_old() ) {
			self::add_old_wp_notice();
			self::$legacy_wp = true;
		}

		self::add_actions();
	}

	/**
	 * Hook actions into WordPress API
	 *
	 * @uses add_action()
	 */
	private static function add_actions() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
		add_action( 'admin_menu', array( __CLASS__, 'remove_meta_boxes' ) );
		add_action( 'edit_form_top', array( __CLASS__, 'add_buttons' ) );
		add_action( 'edit_form_after_title', array( __CLASS__, 'add_format_forms' ) );
	}
 
	/**
	 * Enqueue the necessary CSS and JS
	 *
	 * @uses wp_enqueue_style()
	 * @uses wp_enqueue_script()
	 * @uses wp_localize_script()
	 */
	public static function admin_enqueue_scripts() {
		// css
		wp_enqueue_style( self::key, plugins_url( 'css/post-formats.css', __FILE__ ), null, self::version );
		// js
		wp_enqueue_script( self::key, plugins_url( 'js/post-formats.js', __FILE__ ), array( 'underscore', 'backbone' ), self::version, true );
		wp_localize_script( 'post-formats-ui', 'postFormats', self::get_current_post_format() );
		// COME BACK TO THIS
		do_action( 'post_formats_enqueue', self::key, self::get_all_post_formats() );
	}

	/**
	 * Hooks into admin notices to show error
	 * @uses add_action()
	 */
	private static function add_old_wp_notice() {
		add_action( 'admin_notices', array( __CLASS__, 'show_old_wp_notice' ) );
	}

	/**
	 * Shows error if user is using an unsupported version of WordPress
	 */
	public static function show_old_wp_notice() {
		global $wp_version;
		$min_version = self::min_wp_version;
		return self::template( 'old-wp-notice', compact( 'wp_version', 'min_version' ) );
	}

	/** Private Methods *****************************************************/

	/**
	 * All the post formats available
	 * @return array $all_post_formats all the post formats with description
	 */
	private static function get_all_post_formats() {	
		$all_post_formats = array(
			'standard' => array (
				'description' => __( 'Use the editor below to compose your post.' )
			),
			'image' => array (
				'description' => __( 'Select or upload an image for your post.' )
			),
			'gallery' => array (
				'description' => __( 'Use the Add Media button to select or upload images for your gallery.' )
			),
			'link' => array (
				'description' => __( 'Add a link URL below.' )
			),
			'video' => array (
				'description' => __( 'Select or upload a video, or paste a video embed code into the box.' )
			),
			'audio' => array (
				'description' => __( 'Select or upload an audio file, or paste an audio embed code into the box.' )
			),
			'chat' => array (
				'description' => __( 'Copy a chat or Q&A transcript into the editor.' )
			),
			'status' => array (
				'description' => __( 'Use the editor to compose a status update. What&#8217;s new?' )
			),
			'quote' => array (
				'description' => __( 'Add a source and URL if you have them. Use the editor to compose the quote.' )
			),
			'aside' => array (
				'description' => __( 'Use the editor to share a quick thought or side topic.' )
			)
		);
		// COME BACK TO THIS
		return apply_filters( 'ui_post_formats', $all_post_formats );
	}

	/**
	 * Gets the post id
	 * @return int $post_id the id of the post
	 */
	private static function get_post_id() {
		if ( isset( $_GET['post'] ) )
		 	$post_id = $post_ID = (int) $_GET['post'];
		elseif ( isset( $_POST['post_ID'] ) )
		 	$post_id = $post_ID = (int) $_POST['post_ID'];
		else
		 	$post_id = $post_ID = 0;
		return $post_id;
	}	

	/**
	 * Gets post based on id
	 *
	 * @uses get_post()
	 * @return array $post the post being edited
	 */
	private static function get_post_by_id() {
		$post_id = self::get_post_id();
		$post = get_post( $post_id );
		return $post;
	}

	/**
	 * Get the post type
	 *
	 * @uses get_post_type_object()
	 * @return string $post_type_object the current post type
	 */
	private static function get_post_objects() {
		$post = self::get_post_by_id();
		$post_type = $post->post_type;
		$post_type_object = get_post_type_object( $post_type );
		return $post_type_object;
	}

	/**
	 * Gets current post format
	 *
	 * @uses get_post_format
	 * @return array $current_post_format an array of the current post's format
	 */
	private static function get_current_post_format() {
		$all_post_formats = self::get_all_post_formats();
		$post_format = get_post_format();
		foreach ( $all_post_formats as $slug => $attr ) {
			$active_post_type_slug = $slug;
		}

		$current_post_format = apply_filters( 'current_post_format', array( 'currentPostFormat' => esc_html( $active_post_type_slug ) ) );

		return $current_post_format;
	}

	/**
	 * Gets the post format options
	 *
	 * @uses get_post_format();
	 * @return string $post_format_options the html for the post formats
	 */
	private static function get_post_format_options() {

		$post = self::get_post_by_id();
		$post_format = get_post_format();
		$post_format_set_class = 'post-format-set';


		if ( ! $post_format ) {
			$post_format = 'standard';
			if ( 'auto-draft' == $post->post_status )
				$post_format_set_class = '';
		}

		$format_class = " class='wp-format-{$post_format}'";

		$all_post_formats = self::get_all_post_formats();
		foreach ( $all_post_formats as $slug => $attr ) {
			$class = '';
			if ( $post_format == $slug ) {
				$class = 'class="active"';
				$active_post_type_slug = $slug;
			}
			self::$post_format_options .= '<a ' . $class . ' href="?format=' . $slug . '" data-description="' . $attr['description'] . '" data-wp-format="' . $slug . '" title="' . ucfirst( $slug ) . '"><div class="' . $slug . '"></div><span class="post-format-title">' . ucfirst( $slug ) . '</span></a>';
			// self::$post_format_options .= '<a ' . $class . ' href="?format=' . $slug . '" data-description="' . $attr['description'] . '" data-wp-format="' . $slug . '" title="' . ucfirst( $slug ) . '">';
			// if( isset( $attr['icon'] ) ) {
			// 	self::$post_format_options .= '<i class="' . $attr['icon'] .'"></i>';
			// } else {
			// 	self::$post_format_options .= '<div class="' . $slug . '"></div>';
			// }
			// self::$post_format_options .= '<span class="post-format-title">' . ucfirst( $slug ) . '</span></a>';
		}

		return apply_filters( 'post_format_options', self::$post_format_options, $post, $post_format  );
	}

	/** Post Formats UI Methods ***********************************************/

	/**
	 * Removes the *new* post formats meta box
	 *
	 * @uses remove_meta_box()
	 */
	public static function remove_meta_boxes() {
		remove_meta_box( 'formatdiv', 'post', 'side' );
	}

	/** 
	 * Add post format buttons above post title
	 */
	public static function add_buttons() {
		$post_format_options = self::get_post_format_options();
		$post_format_descriptions = self::get_all_post_formats();

		$add_buttons_template = apply_filters( 'post_formats_ui_template', 'post-formats-ui' );
		$add_butons_template_variables = apply_filters( 'post_formats_ui_template_vars', compact( 'post_format_options', 'post_format_descriptions' ) );
		self::template( $add_buttons_template, $add_butons_template_variables );
	}

	/**
	 * Legacy functionality
	 */
	public static function post_format_buttons_box() {
		add_meta_box( 
			'post_formats_metabox', 
			'Post Formats',
			array( __CLASS__, 'add_buttons' ),
			'post'
		);
	}

	/**
	 * Add hidden post format forms after post title
	 */
	public static function add_format_forms() {
		if( !self::is_valid_post_type() )
			return;
		
		global $wp_embed, $post_format;
		$all_post_formats = self::get_all_post_formats();
		$post_ID          = self::get_post_id();
		$format_meta      = self::get_post_format_meta( $post_ID );
		$template_name = apply_filters( 'format_forms_name', 'post-formats');
		$template_args = apply_filters( 'format_forms_args', compact( 'wp_embed', 'post_ID', 'post_format', 'all_post_formats', 'format_meta' ) );
		self::template( $template_name, $template_args );
		self::template( 'post-format-info' );
	}

	/** Public Methods **********************************************************/

	/** 
	 * Load a template. MVC FTW!
	 * @param string $template the template to load, without extension (assumes .php). File should be in templates/ folder
	 * @param args array of args to be run through extract and passed to template
	 */
	public static function template( $template, $args = array() ) {

	    extract( $args );

	    if ( ! $template )
	        return false;
	        
	    $path = dirname( __FILE__ ) . "/templates/{$template}.php";
	    $path = apply_filters( 'liveblog', $path, $template );

	    include $path;
	    
	}

	/**
	 * Retrieve post format metadata for a post
	 *
	 * @param int $post_id (optional) The post ID.
	 * @return array The array of post format metadata.
	 */
	public static function get_post_format_meta( $post_id = 0 ) {
		$meta = get_post_meta( $post_id );
		$keys = array( 'quote', 'quote_source_name', 'quote_source_url', 'link_url', 'gallery', 'audio_embed', 'video_embed', 'url', 'image' );

		if ( empty( $meta ) )
			return array_fill_keys( $keys, '' );

		$upgrade = array(
			'_wp_format_quote_source' => 'quote_source_name',
			'_wp_format_audio' => 'audio_embed',
			'_wp_format_video' => 'video_embed'
		);

		$format = get_post_format( $post_id );
		if ( ! empty( $format ) ) {
			switch ( $format ) {
			case 'link':
				$upgrade['_wp_format_url'] = 'link_url';
				break;
			case 'quote':
				$upgrade['_wp_format_url'] = 'quote_source_url';
				break;
			}
		}

		$upgrade_keys = array_keys( $upgrade );
		foreach ( $meta as $key => $values ) {
			if ( ! in_array( $key, $upgrade_keys ) )
				continue;
			update_post_meta( $post_id, '_format_' . $upgrade[$key], reset( $values ) );
			delete_post_meta( $post_id, $key );
		}

		$values = array();

		foreach ( $keys as $key ) {
			$value = get_post_meta( $post_id, '_format_' . $key, true );
			$values[$key] = empty( $value ) ? '' : $value;
		}

		return $values;
	}

	/** Plupload Helpers ******************************************************/

	private static function is_wp_too_old() {
		global $wp_version;
		// if WordPress is loaded in a function the version variables aren't globalized
		// see: http://core.trac.wordpress.org/ticket/17749#comment:40
		if ( !isset( $wp_version ) || !$wp_version ) {
			return false;
		}
		return version_compare( $wp_version, self::min_wp_version, '<' );
	}

	private static function is_valid_post_type() {
		global $post;

		if( get_post_type( $post ) != 'post' )
			return false;
		else
			return true;
	}

}

global $pagenow;
if ( is_admin() && in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) )
	Post_Formats_UI::load();

endif;