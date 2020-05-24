<?php

namespace CS\Assets;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get paths for assets
 */
class Assets {

    /**
     * Manifest file object containing list of all hashed assets
     * @var Object
     */
	private $manifest;


    /**
     * Absolut path to theme 'dist' folder
     * @var string
     */
    private $dist_path;


    /**
     * URI to theme 'dist' folder
     * @var string
     */
    private $dist_uri;


    /**
     * Initiate
     */
	public function __construct() {

        $this->dist_path     = sprintf( '%s/dist', get_stylesheet_directory() );
        $this->dist_uri      = sprintf( '%s/dist', get_stylesheet_directory_uri() );
        $this->manifest_path = sprintf( '%s/assets.json', $this->dist_path );

        /**
         * Test for assets.json
         */
		if (file_exists($this->manifest_path)) {
			$this->manifest = json_decode(file_get_contents($this->manifest_path), true);
		} else {
			$this->manifest = [];
		}
	}


    /**
     * Get full URI to single asset
     * 
     * @param  string $filename File name
     * @return string           URI to resource
     */
	public function get( $filename ) {

        return $this->locate( $filename );
	}



    /**
     * Fix URL for requested files
     * 
     * @param  string $filename Requested asset
     * @return [type]           [description]
     */
    private function locate( $filename ) {

        // Return URL to requested file from manifest
        if ( array_key_exists($filename, $this->manifest) )
        {
            return sprintf( '%s/%s', $this->dist_uri, $this->manifest[ $filename ]);
        }

        /**
         * Fix URI to requested resource
         * Manifest is correctly generated only for images
         * For other types we need to append folder
         */
        $file = pathinfo($filename);
        $dir  = '/';

        switch ( $file['extension'] ) {
            case 'js':
                $dir = '/scripts/';
            break;

            case 'css':
                $dir = '/styles/';
            break;
            
            default:
                $dir = '/';
            break;
        }

        // Spritemap specific
        if ( 'spritemap.svg' == $filename )
        {
            $dir = '/sprite/';
        }

        return $this->dist_uri . $dir . $filename;
    }
}