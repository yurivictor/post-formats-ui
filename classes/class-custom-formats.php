<?php

/**
 * Re-implement the WP custom post format functionality
 * for First and 17. What are custom post formats but another
 * taxonomy? So be lazy, and instead of having to write all
 * the check and balance functionality, rip it right from how
 * WP does custom post formats. This is really just code which
 * gets and sets a taxonomy (but without having to write all that pesky
 * code myself!).
 */

if( ! class_exists( 'custom_post_formats_fs' ) ):

final class custom_post_formats_fs {
	
	private static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new custom_post_formats_fs;
			self::$instance->setup_actions();
			self::$instance->setup_filters();
		}
		return self::$instance;
	}

	private function setup_actions() {
		add_action( 'init', array( __CLASS__, 'register_custom_post_formats_fs' ) );
		add_action( 'post_formats_enqueue', array( __CLASS__, 'enqueue_cpffs' ), 10, 3 );
		add_action( 'save_post', array( __CLASS__, 'save_custom_post_format_data' ) );
	}

	private function setup_filters() {
		add_filter( 'ui_post_formats', array( __CLASS__, 'add_custom_post_formats_fs' ) );
		add_filter( 'format_forms_name', array( __CLASS__, 'post_format_fs_template' ) );
		add_filter( 'post_formats_ui_template_vars', array( __CLASS__, 'add_post_ui_template_vars' ) );
	}


	public function add_post_ui_template_vars( $array_of_vars ) {
		global $post;

		$post_format_meta = get_post_meta( $post->ID, 'fs_info', true );

		$array_of_vars['format_type'] = isset( $post_format_meta['fs_type'] ) ? $post_format_meta['fs_type'] : 'standard';
		
		return $array_of_vars;
	}

	public function save_custom_post_format_data( $post_id ) {

		if ( wp_is_post_revision( $post_id ) )
			return;

		if ( !current_user_can( 'edit_post', $post_id ) )
			return;

		$post_format = isset( $_POST['fs_post_format_type'] ) ? $_POST['fs_post_format_type'] : 'standard';
		$post_format_url = isset( $_POST['fs_url'] ) ? $_POST['fs_url'] : null ;
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
			'fs_caption' => $post_format_caption,
			'fs_byline' => $post_format_byline,
			'fs_credit' => $post_format_credit,
			'fs_description' => $post_format_description,
			'fs_body' => $post_format_body
		);

		update_post_meta( $post_id, 'fs_info', $fs_info );

	}

	public function update_post_format_fs() {
		$post_id = $_POST['post_id'];

		self::set_post_format_fs( $post_id, $_POST['post_format'] );

		$response = array(
		   'what'=> 'format_update',
		   'action'=> 'formated_updated',
		   'id'=> $post_id,
		   'data'=> 'Post format updated successfully.'
		);
		
		$ajaxResponse = new WP_Ajax_Response($response);
		$ajaxResponse->send();
	}

	public function enqueue_cpffs( $js_to_enqueue, $all_post_formats ) {
		global $post; 

		wp_localize_script( $js_to_enqueue, 'cpffs', array( 
			'post_id' => $post->ID,
			'fs_info' => get_post_meta( $post->ID, 'fs_info' ),
			'all_post_formats' => $all_post_formats
		) );
	}

	public function post_format_fs_template( $template_name ) {
		return 'post-formats-backbone';
	}

	public function add_custom_post_formats_fs( $ui_post_formats_array ) {
		
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

	public function get_current_post_format_fs() {
		return array( 'currentPostFormat' => esc_html( self::get_post_format_fs() ) );
	}

	public function post_format_options_fs( $options, $post, $post_format) {
		return $options;
	}

	public function register_custom_post_formats_fs() {
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

	public function get_post_format_strings_fs() {
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

	/**
	 * Retrieves an array of post format slugs.
	 *
	 * @since 3.1.0
	 *
	 * @uses get_post_format_strings()
	 *
	 * @return array The array of post format slugs.
	 */
	public function get_post_format_slugs_fs() {
		$slugs = array_keys( self::get_post_format_strings_fs() );
		return array_combine( $slugs, $slugs );
	}

	/**
	 * Retrieve the format slug for a post
	 *
	 * @since 3.1.0
	 *
	 * @param int|object $post Post ID or post object. Optional, default is the current post from the loop.
	 * @return mixed The format if successful. False otherwise.
	 */
	public function get_post_format_fs( $post = null ) {
		if ( ! $post = get_post( $post ) )
			return false;


		$format = get_the_terms( $post->ID, 'post_format_cpft' );

		if ( empty( $format ) )
			return false;

		$format = array_shift( $format );
		$format_slug = str_replace( 'post-format-cpft-', '', $format->slug );
		$format_slugs_names = self::get_post_format_strings_fs();
		$format_info = array( 'slug' => $format_slug, 'name' => $format_slugs_names[$format_slug] );
		return $format_info;
	}

}

function Custom_Post_Formats_FS() {
	return custom_post_formats_fs::instance();
}
Custom_Post_Formats_FS();

endif;