<?php

/* module to control and deal with;
 *  - menu structures and creation
 *  - initiation and instantiation of pages from configuration.ini
 * 
 *  NOTE : This module was attempted to rename to 'pages', but it 
 *         failed, not sure why.
 */

    class xs_module_menu extends xs_Action_Resource {

        public $meta = array (
            'name' => 'Pages module',
            'version' => '1.0',
            'author' => 'Alexander Johannesen',
            'author_link' => 'http://shelter.nu/',
            'editable_options' => true,
        ) ;

        // Hold all menus in one structure
        private $struct = array () ;

        // internal raw data structure
        private $data = array () ;

        // The base (and length of that string) for registering the API
        private $api_base = '_api/gui/menu' ;
        private $api_base_length = 0 ;

        function ___modules_pre () {

            // Register the four basic menu resources that people can post items to
            
            // The main resource
            $this->_register_resource ( XS_MODULE, $this->api_base ) ;
            
        }
        
        function ___precondition () {
            $this->data = $this->glob->config->parse_section ( 'pages' ) ;
            $this->glob->website = $this->data ;
        }
        
        function ___modules () {
            
            $this->glob->data->register_query (

                // use the default xs (xSiteable) datasource
                'xs',

                // identifier for our query
                'site-structure',

                // the query in question (passing in an array sends the query to
                // the Topic Maps engine (that builds its own SQL) rather than
                // a generic SQL

                array (
                    'select'      => 'id,label,parent',
                    'type'        => array ( xs::_page ),
                    'return'      => 'topics'
                ),

                // the timespan of caching the result
                '+1 second'
            ) ;
            
        }

        public function GET () {
            $this->_get_data () ;
        }

        public function POST ( $arg = null ) {
            $this->_post_data ( $arg ) ;
        }

        function _get_data ( $path = null ) {
            // Transfer all GET traffic to a more generic _get function without
            // the URI baggage
            if ( $path != '' )
                return $this->this_get ( $path ) ;

            return $this->this_get ( substr ( $this->api_base, $this->api_base_length ) ) ;
        }

        function _post_data ( $args = null ) {
            if ( is_array ( $args ) )
                foreach ( $args as $menu => $arg )
                    $this->this_post ( $menu, $arg ) ;
        }

        function this_get ( $menu ) {
            
            if ( trim ( $menu ) == '' ) { return ; }

            if ( ! isset ( $this->data[$menu] ) )
                return ;
            
            $data = $this->data[$menu] ;
            $weight = array () ;
            
            
            foreach ( $data as $idx => $val )
                if ( isset ( $val['@weight'] ) )
                    $weight[$idx] = $val['@weight'] ;
                else
                    $weight[$idx] = 100 ;

            array_multisort ( $weight, SORT_ASC, $data ) ;

            return explodeTree ( $data ) ;
        }
        
        // function ___output () { print_r ( $this->data ) ; }

        function this_post ( $menu, $args = array () ) {
            
            // turn all params without an '@' in front into ones with
            foreach ( $args as $idx => $arg )
                if ( $idx[0] !== '@' ) {
                    $args["@{$idx}"] = $arg ;
                    unset ( $args[$idx] ) ;
                }

            // default values if none set
            if ( ! isset ( $args['@uri'] ) ) $args['@uri'] = '' ;
            if ( ! isset ( $args['@label'] ) ) $args['@label'] = '...' ;
            if ( ! isset ( $args['@weight'] ) ) $args['@weight'] = 100 ;
            
            // transfer from array to global structure
            foreach ( $args as $idx => $arg )
                if ( $idx !== '@uri' )
                    $this->data[$menu][$args['@uri']][$idx] = $arg ;

        }
    }
