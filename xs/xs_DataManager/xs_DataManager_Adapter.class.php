<?php

    class xs_DataManager_Adapter extends xs_EventStack_Plugin {

        // debugging, obviously
        private $debug = false ;

        // internal configuration array
        public $config = null ;

        // internal breakdown array
        public $breakdown = null ;

        // link to the active driver
        protected $driver = null ;

        // null = uninitiated, true is ok, false is error
        public $status = null ;

        function __construct ( $config = array () ) {

            // Hello, papa!
            parent::__construct () ;

            // we need a configuration object
            $this->config = new xs_Config ( $config ) ;

            // we need a breakdown object (for query parsing)
            $this->breakdown = new xs_Breakdown () ;

        }

        function get_driver () {
            if ( $this->driver != null )
                return $this->driver ;

            $this->setup () ;
            return $this->driver ;
        }

        function instantiate () {
            // needs to be overwritten by actual code
        }

        function setup () {

            // setup if not already set up
            if ( ! $this->driver )
               $this->instantiate () ;

        }

        // how to create a resource with identifier
        function create ( $id, $resource ) {
            
        }

        // simple read an identified resource
        function read ( $id ) {

        }

        // update an identified resource
        function update ( $id, $resource ) {

        }

        // delete an identified resource
        function delete ( $id ) {

        }





        function _parse ( $input, $schema = '' ) {

            $ret = array () ;

            // Sanitize input data : Regular Expression
            $regexp = '/[^a-z0-9%() +\-\/!$*_=|.:]/i' ;

            // Break our path into little bits
            $break = explode ( '/', $input ) ;

            // Break our path into little bits
            $schema_break = explode ( '/', $schema ) ;

            $e = new xs_Breakdown ( $input ) ;

            // Loop through the path elements
            foreach ( $break as $count => $value ) {

                // Sanitize the value of the element
                $value = urldecode ( trim ( preg_replace ( $regexp, '', $value ) ) ) ;

                $key = null ;

                if ( isset ( $schema_break[$count] ) )
                    $key = $schema_break[$count] ;

                $ret[$key] = $value ;

            }

            return $ret ;
        }

        function __levels ( $input ) {
            $c = explode ( '/', $input ) ;
            $levels = 0 ;
            if ( trim ( $c[0] ) != '' )
                $levels = count ( $c ) ;
            return $levels ;
        }

        function _getSubStrs ( $from, $to, $str, &$result = array () ) {

            if ( strpos ( $str, $from ) !== false ) {

                $start = strpos ( $str, $from ) + 1 ;
                $end = strpos ( $str, $to ) - 1 ;

                $item = substr ( $str, $start, $end - $start + 1 ) ;
                $rest = substr ( $str, $end + 2 ) ;

                $result[$item] = $item ;

                $this->_getSubStrs ( $from, $to, $rest, $result ) ;

            }

            return $result ;
        }


        function alert ( $type, $headline, $message ) {

            $p = $this->glob->alerts ;

            // var_dump ( $p ) ;

            if ( !isset ( $p[$type] ) )
                $p[$type] = array () ;

            $p[$type][] = array ( $headline, $message ) ;

            $this->glob->alerts = $p ;

            // var_dump ( $this->glob->alerts ) ;
        }


        /*
        function

            // Find the tokens used from our schema template
            $tokens_first = $this->_getSubStrs ( "{","}", $query_template ) ;

            $tokens = array () ;

            if ( count ( $tokens_first ) < 1 )
                return $query_template ;

            // create new tokens with the brackets intact so we can replace them in a template
            foreach ( $tokens_first as $token )
                $tokens[$token] = '{'.$token.'}' ;

            // yeah, replace tokens found with their argument equivalent
            return str_replace ( $tokens, $args, $break ) ;

         *
         */

    }
