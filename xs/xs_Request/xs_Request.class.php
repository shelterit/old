<?php

    /*
     * This class encapsulates not only all request variables as such, but also
     * the HTTP header, and adds a few methods to pull it apart and create a
     * better resource orientation for the resources requested.
     *
     */
	
    class xs_Request extends xs_Properties {

        private $_headers = null ;
        
        public $_method = null ;
        public $_uri = null ;

        function __construct ( $inp = null ) {

            parent::__construct();

            // Try to get all possible headers from the request
            $this->_headers = getallheaders () ;

            $regexp = '/[^a-z0-9 +\-\/!$*_=|.:]/i' ;

            $r = $_REQUEST ;

            if ( is_array ( $inp ) && count ( $inp > 0 ) )
                $r = $inp ;

            // echo "<pre>" ; print_r ( $this->_headers ) ; echo " (".print_r(get_class($this),true).")</pre>" ;

            if ( count ($r) > 0 ) {

                foreach ( $r as $idx => $value ) {
                    if ( is_array ( $value ) ) {
                        $this->values[$idx] = $value ;
                    } else {
                        // $value = trim ( preg_replace ( $regexp, '', urldecode ( $value ) ) ) ;
                        $value = trim ( ( $value ) ) ;
                        $this->values[$idx] = str_ireplace( '\\', '', $value ) ;
                    }
                }
            }

            $this->_method = strtoupper ( $_SERVER["REQUEST_METHOD"] ) ;

            if ( isset ( $this->values['_method'] ) )
                $this->_method =  strtoupper ( $this->values['_method'] ) ;
            
            $this->_uri = rtrim ( ltrim ( $this->__get ( 'q', '' ), '/' ), '/' ) ;
            

        }

        function __filter ( $what ) {
            $res = array() ;
            $whatlen = strlen ( $what.':' ) ;
            foreach ( $this->values as $idx=>$val )
                if ( substr ( $idx, 0, $whatlen ) == $what.':' )
                    $res[substr ( $idx, $whatlen )] = $val ;
            return $res ;
        }

        function __get_fields ( $prefix = 'f:' ) {
            $res = array() ;
            $len = strlen ( $prefix ) ;
            foreach ( $this->values as $idx=>$val )
                if ( substr ( $idx, 0, $len ) == $prefix )
                    $res[substr ( $idx, $len )] = $val ;
            return $res ;
        }

        function __get_props () {
            return $this->__get_fields ( 'p:' ) ;
        }

        // deprecated
        function method () {
            return $this->_method ;
        }

        function get_method () {
            return $this->method () ;
        }

        function get_headers () {
            return $this->_headers ;
        }

        function set_header ( $key, $value = null ) {
            if ( $value == null )
                unset ( $this->_headers[$key] ) ;
            else
                $this->_headers[$key] = $value ;
        }

    }

