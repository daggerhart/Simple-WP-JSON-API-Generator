<?php

class AJAX {

	/**
	 * Templating engine
	 */
	public $twig;

	/**
	 * Submitted values
	 * 
	 * @var array
	 */
	public $fields = array();

	/**
	 * Processed values
	 * 
	 * @var array
	 */
	public $data = array();

	/**
	 * Request action
	 * 
	 * @var string
	 */
	public $action = '';

	/**
	 * Generated zip archive file name
	 * 
	 * @var string
	 */
	public $zipFileName = '[className].zip';

	/**
	 * Generated zip archive contents
	 * 
	 * @var array
	 */
	public $zipFiles = array(
		'[className]/' => '/',
		'[className]/[className].php' => 'json-api-template.twig', 
	);

	/**
	 * @param $get
	 * @param $post
	 *
	 * @return bool
	 */
	static public function validateRequest( $get, $post ){
		$valid = false;

		if ( defined( 'API_GEN_PATH' )
		     && isset( $get['ajax'] )
		     && ( $get['ajax'] == 'download' )
		     && !empty( $post )
		)
		{
			$valid = true;
		}

		return $valid;
	}

	/**
	 * Setup the AJAX object 
	 * 
	 * @param $twig
	 * @param $fields
	 * @param $action
	 */
	function __construct( $twig, $fields, $action ){
		$this->twig = $twig;
		$this->fields = $fields;
		$this->action = $action;
		
		$this->extendTwig();
	}

	/**
	 * Extend Twig as needed
	 */
	function extendTwig(){
		$filter = new Twig_SimpleFilter('commaStringToArray', array( $this, 'commaStringToArray' ) );
		$this->twig->addFilter( $filter );
	}

	/**
	 * Twig filter to convert a comman separated list into array values of those
	 * strings.
	 * 
	 * ex: 'one,two,three' -> 'one', 'two', 'three'
	 * 
	 * @param $string
	 * @return string
	 */
	function commaStringToArray( $string ){
		$array = explode( ',', $string );
		array_walk( $array, 'trim' );
		return "'" . implode("', '", $array ) . "'";
	}

	/**
	 * Process fields, and execute the given action
	 */
	function execute(){
		$this->processFields();
		
		switch( $this->action ){
			case 'download':
				$this->createZip();
				$this->sendZipDownloadResponse();
				break;
		}
	}

	/**
	 * Extract data from fields
	 *
	 * @return array
	 */
	function processFields(){
		$this->preprocessFields();
		$this->data = array();

		foreach ( $this->fields as $name => $value ){
			$this->data[ $name ] = $value;
		}

		return $this->data;
	}

	/**
	 * Setup field default state
	 */
	function preprocessFields(){
		if ( ! isset( $this->fields['pluginSlug'] ) || empty( $this->fields['pluginSlug'] ) ) {
			$this->fields['pluginSlug'] = 'simple-json-api';
		}

		$this->fields['className'] = strtolower( strtr( $this->fields['pluginSlug'], array( '-' => '_' ) ) );
	}
	
	/**
	 * Create a zip archive of the rendered twig template files
	 */
	function createZip(){
		// replace zip file name tokens
		$this->zipFileName = str_replace('[className]', $this->data['className'], $this->zipFileName );
		
		$zip = new ZipArchive();
		$zip->open( $this->zipFileName, ZipArchive::CREATE );
		
		foreach( $this->zipFiles as $filename => $template ){
			// replace file name tokens
			$filename = str_replace('[className]', $this->data['className'], $filename );
			
			// create directory
			if ( $template === '/' ){
				$zip->addEmptyDir( $filename );
			}
			// otherwise attempt to template
			else {
				$content = $this->twig->render( $template, $this->data );
				$zip->addFromString( $filename, $content );
			}
		}
		$zip->close();
	}

	/**
	 * Send the generated zip archive to the user's browser
	 */
	function sendZipDownloadResponse(){
		header( 'Content-Type: application/zip' );
		header( 'Content-disposition: attachment; filename=' . $this->zipFileName );
		header( 'Content-Length: ' . filesize( $this->zipFileName ) );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		readfile( $this->zipFileName );
		unlink( $this->zipFileName );
	}
}