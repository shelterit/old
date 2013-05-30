<?php

    class xs_module_layout extends xs_EventStack_Plugin {

        public $meta = array (
            'name' => 'Layout module',
            'description' => 'The layout module control various parts of a web page',
            'version' => '1.0',
            'author' => 'Alexander Johannesen',
            'author_link' => 'http://shelter.nu/',
            'editable_options' => true,
        ) ;

        // Hold all layouts in one structure
        private $struct = array () ;

        // The base (and length of that string) for registering the API
        private $api_base = '_api/gui/layout/' ;
        private $api_base_length = 0 ;

        public $page_type = null ;


        function ___framework () {

            $this->api_base_length = strlen ( $this->api_base ) ;

            // Register resources for the main sections we deal with
            $this->_register_resource ( XS_MODULE, $this->api_base . 'header' ) ;
            $this->_register_resource ( XS_MODULE, $this->api_base . 'bar' ) ;
            $this->_register_resource ( XS_MODULE, $this->api_base . 'body' ) ;
            $this->_register_resource ( XS_MODULE, $this->api_base . 'section1' ) ;
            $this->_register_resource ( XS_MODULE, $this->api_base . 'section2' ) ;
            $this->_register_resource ( XS_MODULE, $this->api_base . 'section3' ) ;
            $this->_register_resource ( XS_MODULE, $this->api_base . 'section4' ) ;
            $this->_register_resource ( XS_MODULE, $this->api_base . 'section5' ) ;
            $this->_register_resource ( XS_MODULE, $this->api_base . 'section6' ) ;
            $this->_register_resource ( XS_MODULE, $this->api_base . 'section7' ) ;
            $this->_register_resource ( XS_MODULE, $this->api_base . 'section8' ) ;
            $this->_register_resource ( XS_MODULE, $this->api_base . 'section9' ) ;
            $this->_register_resource ( XS_MODULE, $this->api_base . 'footer' ) ;

        }

        function ___post_action () {

            $page = $this->glob->page ;

            $this->glob->data->register_query ( 'xs', 'xs_site_structure_pages', 
                'SELECT * FROM xs_topic WHERE type1 = 22 AND parent > 0', 
            '+1 hour' ) ;

            $struct = $this->glob->data->get ( 'xs_site_structure_pages' ) ;
            
            
            foreach ( $struct as $idx => $page ) {
                // echo '[' ; print_r ( $page['id'] ) ; echo '] ' ;
            }
            
            // print_r ( $this->glob->page ) ;
            
            $this->glob->data->register_query ( 'xs', 'xs_site_structure', 
                'SELECT * FROM xs_topic WHERE type1 = 22 AND parent > 0', 
            '+1 hour' ) ;

            // print_r ( $struct ) ;
        }

        function POST ( $args = null ) {
            // Transfer all POST traffic to a more generic _post function without
            // the URI baggage
            return $this->_post ( substr ( $this->resource, $this->api_base_length ), $args ) ;
        }

        function GET () {
            // echo "! GET" ;
        }

        function _get ( $menu ) {
            if ( trim ( $menu ) == '' ) { return ; }
            // Create a DOM tree from an array, and return that
            $ret = new array2xml ( $this->struct[$menu] ) ;
            return $ret->data ;
        }

        function _post ( $menu, $args = null ) {
            // POST to this plugin will register someone as part of the menu
            $label = '' ;
            $uri = '' ;
            if ( isset ( $args['label'] ) ) $label = $args['label'] ;
            if ( isset ( $args['uri'] ) ) $uri = $args['uri'] ;
            if ( $uri != '' )
                $this->struct[$menu][$uri] = $label ;
        }

    }
