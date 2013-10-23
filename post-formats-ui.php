<?php
/**
 * Plugin Name:  Post Formats UI
 * Description:  The post formats UI pulled from 3.6
 * Version:      0.0.2
 * Authors:      Yuri Victor, Connor Jennings
 * Credits:      Ripped a lot of the code from 3.6-beta1-24067
 * License:      GPL ( attached )
 */

if ( ! class_exists( 'Post_Formats_UI' ) ) :

/**
 * Re-implement the WP custom post format functionality
 * What are custom post formats but another taxonomy? 
 * So be lazy, and instead of having to write all the check 
 * and balance functionality, rip it right from how WP does 
 * custom post formats. This is really just code which gets
 * and sets a taxonomy, but without having to write all that pesky code
 */

final class Post_Formats_UI {

	/** Constants *************************************************************/

	const version          = '0.0.2';
	const min_wp           = '3.7';
	const key              = 'post-formats-ui';
	const key_             = 'post_formats_ui_';
	const nonce_key        = 'post_formats_ui_nonce';

	/** Variables *************************************************************/

	private static $instance;
	private static $post_format_options;

	/** Load Methods **********************************************************/

	/**
	 * Init necessary functions
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Post_Formats_UI;
			self::$instance->add_actions();
			self::$instance->add_filters();
		}
		return self::$instance;
	}

	/**
	 * Hook actions into WordPress API
	 * @uses add_action()
	 */
	private static function add_actions() {
		add_action( 'admin_menu', array( __CLASS__, 'remove_meta_boxes' ) );
		add_action( 'edit_form_after_title', array( __CLASS__, 'add_format_forms' ) );
		add_action( 'edit_form_top', array( __CLASS__, 'add_buttons' ) );


		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
		add_action( 'init', array( __CLASS__, 'register_custom_post_formats_fs' ) );
		add_action( 'post_formats_enqueue', array( __CLASS__, 'enqueue_cpffs' ), 10, 3 );
		add_action( 'save_post', array( __CLASS__, 'save_custom_post_format_data' ) );		
	}

	/**
	 * Hook filters into WordPress API
	 * @uses add_filter()
	 */	
	private static function add_filters() {
		add_filter( 'ui_post_formats', array( __CLASS__, 'add_custom_post_formats_fs' ) );
		add_filter( 'format_forms_name', array( __CLASS__, 'post_format_fs_template' ) );
		add_filter( 'post_formats_ui_template_vars', array( __CLASS__, 'add_post_ui_template_vars' ) );
	}
 
 	/** Public Methods ********************************************************/

	/**
	 * Enqueue the necessary CSS and JS
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
	 * Shows error if user is using an unsupported version of WordPress
	 */
	public static function show_old_wp_notice() {
		global $wp_version;
		$min_version = self::min_wp;
		return self::template( 'old-wp-notice', compact( 'wp_version', 'min_version' ) );
	}

	/** Post Formats UI Methods ***********************************************/

	/**
	 * Removes the *new* post formats meta box
	 * @uses remove_meta_box()
	 */
	public static function remove_meta_boxes() {
		remove_meta_box( 'formatdiv', 'post', 'side' );
	}

	/** Plupload Helpers ******************************************************/

	/** 
	 * Load a template. MVC FTW!
	 * @param string $template the template to load, without extension (assumes .php). File should be in templates/ folder
	 * @param args array of args to be run through extract and passed to template
	 */
	public static function template( $template, $args = array() ) {
		extract( $args );

		if ( ! $template ) return false;

		$path = dirname( __FILE__ ) . "/templates/{$template}.php";
		$path = apply_filters( 'liveblog', $path, $template );

		include $path;
	}

	/**
	 * Verifies WordPress version meets the necessary minimum
	 * @return unknown
	 */
	public static function is_wp_too_old() {
		global $wp_version;
		// if WordPress is loaded in a function the version variables aren't globalized
		// see: http://core.trac.wordpress.org/ticket/17749#comment:40
		if ( ! isset( $wp_version ) || ! $wp_version )
			return false;

		return version_compare( $wp_version, self::min_wp, '<' );
	}

	/**
	 * Hooks into admin notices to show error
	 * @uses add_action()
	 */
	public static function add_old_wp_notice() {
		add_action( 'admin_notices', array( __CLASS__, 'show_old_wp_notice' ) );
	}		

	/** Need to clean up ******************************************************/

	/**
	 * All the post formats available
	 * @return array $all_post_formats all the post formats with description
	 */
	public static function get_all_post_formats() {	
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
	public static function get_post_id() {
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
	public static function get_post_by_id() {
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
		}

		return apply_filters( 'post_format_options', self::$post_format_options, $post, $post_format  );
	}
	/**
	 * Add hidden post format forms after post title
	 * @global $wp_embed, $post_format
	 * @uses apply_filters()
	 */
	public static function add_format_forms() {
		global $wp_embed, $post_format;
		$all_post_formats = self::get_all_post_formats();
		$post_ID          = self::get_post_id();
		$template_name    = apply_filters( 'format_forms_name', 'post-formats');
		$template_args    = apply_filters( 'format_forms_args', compact( 'wp_embed', 'post_ID', 'post_format', 'all_post_formats', 'format_meta' ) );
		self::template( $template_name, $template_args );
		self::template( 'post-format-info' );
	}

	/** 
	 * Add post format buttons above post title
	 * @uses apply_filters()
	 */
	public static function add_buttons() {
		$post_format_options      = self::get_post_format_options();
		$post_format_descriptions = self::get_all_post_formats();
		$add_buttons_template     = apply_filters( 'post_formats_ui_template', 'post-formats-ui' );
		$add_butons_template_args = apply_filters( 'post_formats_ui_template_vars', compact( 'post_format_options', 'post_format_descriptions' ) );
		self::template( $add_buttons_template, $add_butons_template_args );
	}
	public static function post_format_fs_template( $template_name ) {
		return 'post-formats-backbone';
	}

	public static function add_custom_post_formats_fs( $ui_post_formats_array ) {
		
		$ui_new_post_formats_array['standard'] = array(
			'description' => __( 'Use the editor below to compose your post' ),
		);

		$ui_new_post_formats_array['image'] = array(
			'description' => __( 'Select or upload an image for your post' ),
		);

		$ui_new_post_formats_array['gallery'] = array(
			'description' => __( 'Use the Add Media button to select or upload images for your gallery' ),
		);

		$ui_new_post_formats_array['link'] = array(
			'description' => __( 'Add a link URL below' ),
		);

		$ui_new_post_formats_array['video'] = array(
			'description' => __( 'Select or upload a video, or paste a video embed code into the box' ),
		);

		$ui_new_post_formats_array['audio'] = array(
			'description' => __( 'Select or upload an audio file, or paste an audio embed code into the box' ),
		);

		$ui_new_post_formats_array['chat'] = array(
			'description' => __( 'Copy a chat or Q&A transcript into the editor' ),
		);

		$ui_new_post_formats_array['status'] = array(
			'description' => __( 'Use the editor to compose a status update. What’s new?' ),
		);		

		$ui_new_post_formats_array['quote'] = array(
			'description' => __( 'Add a source and URL if you have them. Use the editor to compose the quote' ),
		);

		$ui_new_post_formats_array['aside'] = array(
			'description' => __( 'Use the editor to share a quick thought or side topic' ),
		);

		return $ui_new_post_formats_array;
	}	

	public static function add_post_ui_template_vars( $array_of_vars ) {
		global $post;

		$post_format_meta = get_post_meta( $post->ID, 'fs_info', true );

		$array_of_vars['format_type'] = isset( $post_format_meta['fs_type'] ) ? $post_format_meta['fs_type'] : 'standard';
		
		return $array_of_vars;
	}	
	public static function register_custom_post_formats_fs() {
		register_taxonomy( 'post_format_cpft', 'post', array(
		'public' => false,
		'hierarchical' => false,
		'labels' => array(
			'name' => 'Post Formats',
			'singular_name' => 'Post Format'
		),
		'query_var' => true,
		'show_ui' => false,
		) );
	}

	public static function enqueue_cpffs( $js_to_enqueue, $all_post_formats ) {
		global $post; 

		wp_localize_script( $js_to_enqueue, 'cpffs', array( 
			'post_id' => $post->ID,
			'fs_info' => get_post_meta( $post->ID, 'fs_info' ),
			'all_post_formats' => $all_post_formats
		) );
	}	

	public static function save_custom_post_format_data( $post_id ) {

		if ( wp_is_post_revision( $post_id ) )
			return;

		if ( !current_user_can( 'edit_post', $post_id ) )
			return;

		$post_format = isset( $_POST['fs_post_format_type'] ) ? $_POST['fs_post_format_type'] : 'standard';
		$post_format_url = isset( $_POST['fs_url'] ) ? $_POST['fs_url'] : null ;
		$post_format_url_headline = isset( $_POST['fs_url_headline'] ) ? $_POST['fs_url_headline'] : null ;
		$post_format_caption = isset( $_POST['fs_caption'] ) ? $_POST['fs_caption'] : null;
		$post_format_credit = isset( $_POST['fs_credit'] ) ? $_POST['fs_credit'] : null;
		$post_format_byline = isset( $_POST['fs_byline'] ) ? $_POST['fs_byline'] : null;
		$post_format_description = isset( $_POST['fs_description'] ) ? $_POST['fs_description'] : null;
		$post_format_body = isset( $_POST['fs_body'] ) ? $_POST['fs_body'] : null;
		$uuid = null;
		//First set the post format
		self::set_post_format_fs( $post_id, $post_format );

		$fs_info = array(
			'fs_type' => $post_format,
			'fs_uuid' => $uuid,
			'fs_url' => $post_format_url,
			'fs_url_headline' => $post_format_url_headline,
			'fs_caption' => $post_format_caption,
			'fs_byline' => $post_format_byline,
			'fs_credit' => $post_format_credit,
			'fs_description' => $post_format_description,
			'fs_body' => $post_format_body
		);

		update_post_meta( $post_id, 'fs_info', $fs_info );

	}

	/**
	 * Retrieves an array of post format slugs.
	 *
	 * @since 3.1.0
	 *
	 * @uses get_post_format_strings()
	 *
	 * @return array The array of post format slugs.
	 */
	public static function get_post_format_slugs_fs() {
		$slugs = array_keys( self::get_post_format_strings_fs() );
		return array_combine( $slugs, $slugs );
	}

	public static function get_post_format_strings_fs() {
		$strings = array(
			'standard' => 'Standard',
			'image'    => 'Images',
			'gallery'  => 'Galleries',
			'video'    => 'Videos',
			'audio'    => 'Audio',
			'chat'     => 'Chats',
			'status'   => 'Statuses',
			'quote'    => 'Quotes',
			'aside'    => 'Asides',
		);

		return apply_filters( 'add_post_format_cpffs', $strings );
	}

	public function get_post_format_strings_singular_fs() {
		$strings = array(
			'standard' => 'Standard',
			'image'    => 'Image',
			'gallery'  => 'Gallery',
			'video'    => 'Video',
			'audio'    => 'Audio',
			'chat'     => 'Chat',
			'status'   => 'Status',
			'quote'    => 'Quote',
			'aside'    => 'Aside',
		);

		return $strings;
	}	

	public static function set_post_format_fs( $post, $format ) {
		$post = get_post( $post );

		if ( empty( $post ) )
			return new WP_Error( 'invalid_post', __( 'Invalid post' ) );

		if ( ! empty( $format ) ) {
			$format = sanitize_key( $format );
			if ( in_array( $format, self::get_post_format_slugs_fs() ) )
				$format = 'post-format-cpft-' . $format;
		}

		return wp_set_post_terms( $post->ID, $format, 'post_format_cpft' );
	}

}

global $pagenow;
if ( is_admin() && in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) ) {
	function post_formats_ui() {
		return Post_Formats_UI::instance();
	}
	post_formats_ui();
}

endif;