<?php

/**
 * Plan
 *  - TWIG for templating in PHP
 *  - lodash for templating in JS
 *
 * http://twig.sensiolabs.org/doc/templates.html
 * https://lodash.com/docs#template
 * 
 */

define( 'API_GEN_PATH', __DIR__ );
define( 'APP_PATH', API_GEN_PATH . '/app' );
define( 'VENDOR_PATH', API_GEN_PATH . '/vendor' );

require_once APP_PATH . '/includes/ajax.php';

require_once VENDOR_PATH . '/twig/lib/Twig/Autoloader.php';
Twig_Autoloader::register();

$loader = new Twig_Loader_Filesystem( APP_PATH . '/templates' );
$twig = new Twig_Environment( $loader, array());

/**
 * 2 routes:
 *      /index.php?ajax=download
 *      /index.php
 */
// see if this is a valid ajax request
if ( AJAX::validateRequest( $_GET, $_POST ) ) {
	$ajax = new AJAX( $twig, $_POST, $_GET['ajax'] );
	$ajax->execute();
}
// otherwise, output the homepage
else {
	// @todo: $twig->setCache( APP_PATH . '/cache' );
	print $twig->render('index.twig', array(
		'page_title' => 'My Awesome Generator',
		'page_subtitle' => 'cooler than your generator',
	));
}

exit;

/**
 * Notes:
 *
 * Fields
 *  - make_plugin
 *  - plugin_header
 *      - name (string)
 *      - desc (string)
 *      - author (string)
 *      - version (string)
 *      - license - GPL
 *
 *  - endpoint_base (string)
 *  - allowed_post_types ( [] )
 *      - ... (string)
 *
 * - default_query_arguments
 *      - posts_per_page (int)
 *      - orderby (string)
 *      - order (string)
 *      - performance_arguments (bool)
 *
 *  - post_data
 *      - title || raw
 *      - content || raw
 *      - date || raw
 *      - date_gmt || raw
 *      - excerpt || raw
 *      - permalink
 *      - featured_image_url
 *          - sizes ( [] )
 *      - meta_values ( [] )
 *      - taxonomies ( [] )
 *          - ... (string)
 */