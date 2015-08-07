<?php
/*
Plugin Name: My simple json api plugin name
Description: Describe this thing
Author: Author goes here
Version: 1.0.0
License: GPL2
*/

/**
 * Class Simple_Json_Api
 */
class Simple_Json_Api {

	/**
	 * The top level argument for the endpoint.
	 * ex http://example.com/myjson/post/1
	 *
	 * @var string
	 */
	public $endpoint_base = 'myjson';

	/**
	 * Only provide json data for the post_types in this array.
	 *
	 * @var array
	 */
	public $allowed_post_types = array( 'post' );

	/**
	 * Default WP_Query arguments for retrieving posts.
	 * Here you can limit the number of items returned, or change the order
	 * of items returned, etc.
	 *
	 * @var array
	 */
	public $default_query_arguments = array(
		'posts_per_page' => 10,
		'post_status' => array( 'publish' ),
		'orderby' => 'date',
		'order' => 'ASC',
		'ignore_sticky_posts' => true
	);

	/**
	 * Create an array of data for a single post that will be part
	 * of the json response.
	 *
	 * @param $post
	 */
	static public function make_json_data( $post ){
		// featured image urls
		$image_id = get_post_thumbnail_id( $post->ID );
		$image_full  = wp_get_attachment_image_src( $img_id, 'full' );
		$image_thumb = wp_get_attachment_image_src( $img_id, 'thumbnail' );

		$item = array(
			// The global $post object is set, so we can use template tags.
			// Additionally, we have the object,m so we could get raw data.
			'title' => get_the_title(),
			'title_raw' => $post->post_title,
			'content' => get_the_content(),
			'content_raw' => $post->post_content,
			'date' => get_the_date(),
			'date_raw' => $post->post_date,

			// meta values
			'my_meta_value' => get_post_meta( $post->ID, 'my_meta_value', TRUE ),

			// all meta values ( not recommended unless you're sure you want to do this
			//'meta' => get_post_meta( $post->ID ),

			// default image values
			'image_id' => !empty( $image_id ) ? $image_id : false,
			'image_full' => !empty( $image_full[0] ) ? $image_full[0] : false,
			'image_thumb' => !empty( $image_thumb[0] ) ? $image_thumb[0] : false,

			// taxonomy data
			'categories' => get_the_terms( $post->ID, 'category' ),
			'tags' => get_the_terms( $post->ID, 'post_tag' ),
		);

		// OPTIONAL:
		// Depending on your plugin organizational structure, you may want each
		// post_type to be able to control its own json data.  In that case, you 
		// could use a dynamic hook like this to provide that flexibility.
		// return apply_filters( "myjson_api_{$post->post_type}_data", $item, $post );

		return $item;
	}

	/**
	 * Hook the plugin into WordPress
	 */
	static public function register(){
		$plugin = new self();

		add_action( 'init', array( $plugin, 'add_endpoint' ) );
		add_action( 'template_redirect', array( $plugin, 'handle_endpoint' ) );
	}

	/**
	 * Create our json endpoint by adding new rewrite rules to WordPress
	 */
	function add_endpoint(){
		$post_type_tag = $this->endpoint_base . '_type';
		$post_id_tag   = $this->endpoint_base . '_id';

		// Add new rewrite tags to WP for our endpoint's post_type
		// and post_id arguments
		add_rewrite_tag( "%{$post_type_tag}%", '([^&]+)' );
		add_rewrite_tag( "%{$post_id_tag}%", '([0-9]+)' );

		// Add the rules that look for our rewrite tags in the route query.
		// Most specific rule first, then fallback to the general rule

		// specific rule finds a single post
		// http://example.com/myjson/post/1
		add_rewrite_rule(
			$this->endpoint_base . '/([^&]+)/([0-9]+)/?',
			'index.php?'.$post_type_tag.'=$matches[1]&'.$post_id_tag.'=$matches[2]',
			'top' );

		// general rule finds "all" (post_per_page) of a given post_type
		// http://example.com/myjson/post
		add_rewrite_rule(
			$this->endpoint_base . '/([^&]+)/?',
			'index.php?'.$post_type_tag.'=$matches[1]',
			'top' );
	}

	/**
	 * Handle the request of an endpoint
	 */
	function handle_endpoint(){
		global $wp_query;

		// get the query args and sanitize them for confidence
		$type = sanitize_text_field( $wp_query->get( $this->endpoint_base . '_type' ) );
		$id   = intval( $wp_query->get( $this->endpoint_base . '_id' ) );
		
		// only allowed post_types
		if ( ! in_array( $type, $this->allowed_post_types ) ) {
			return;
		}

		// the post_type of the given id must match the requested post_type
		if ( $id && get_post_type( $id ) != $type ) {
			return;
		}
		
		// start with our default query arguments
		$args = $this->default_query_arguments;

		// add the post_type
		$args['post_type'] = array( $type );

		// add the post ID if specified
		if ( $id ) {
			$args['post__in'] = array( $id );
		}

		$query = new WP_Query( $args );
		$data = array();

		// loop through the posts and build our endpoint data arrays
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();

				global $post;
				$data[] = self::make_json_data( $post );
			}
			wp_reset_query();
		}

		// data is built. print as json and stop
		wp_send_json( $data ); exit;
	}
}

// huzzah!
Simple_Json_Api::register();