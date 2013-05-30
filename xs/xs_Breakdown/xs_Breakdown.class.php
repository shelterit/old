<?php
	
    class xs_Breakdown extends xs_Request {

        private $_tokens = null ;
        private $_path = '' ;
        private $_request_variable = 'q' ;

        function __construct ( $incoming = null ) {

            //; First, pretend to be a $REQUEST object
            parent::__construct() ;

            // Pick out the path we need from $REQUEST
            if ( isset ( $this->values[$this->_request_variable] ) )
                $this->_path = $this->values[$this->_request_variable] ;

            // Got a specific request variable to pick the path from?
            if ( $incoming != null )
                $this->_path = $incoming ;
                
            $this->_path = str_replace ( '&', '%26', $this->_path ) ;

        }

        function _init ( $path ) {
            $this->_path = $path ;
        }

        function _parse ( $schema = '' ) {

            // Sanitize input data : Regular Expression
            $regexp = '/[^a-z0-9%() +\-\/!$*_=|.:]/i' ;

            // Break our path into little bits
            $break = explode ( '/', $this->_path ) ;

            // Find the tokens used from our schema template
            $this->_tokens = $this->_getSubStrs ( "{","}", $schema ) ;

            // var_dump ( $this->_path ) ;
            // var_dump ( $this->_tokens ) ;
            
            // Loop through the path elements
            foreach ( $break as $key => $value ) {

                // Sanitize the value of the element
                $value = urldecode ( trim ( preg_replace ( $regexp, '', $value ) ) ) ;

                // Element not blank? (Meaning, real text)
                if ( $value != '' )

                    // Index it!
                    @$this->values[$this->_tokens[$key]] = $value ;

            }

        }

        function __levels () {
            $c = explode ( '/', $this->_path ) ;
            $levels = 0 ;
            if ( trim ( $c[0] ) != '' )
                $levels = count ( $c ) ;
            return $levels ;
        }

        function _ammendRequest ( $request ) {
            foreach ( $request as $idx=>$val )
                foreach ( $this->_tokens as $n=>$t )
                    if ( $t == strtolower($idx) )
                        $this->values[strtolower($idx)] = $val ;
        }


        function _getSubStrs ( $from, $to, $str, &$result = array () ) {

            if ( strpos ( $str, $from ) !== false ) {

                $start = strpos ( $str, $from ) + 1 ;
                $end = strpos ( $str, $to ) - 1 ;

                $item = substr ( $str, $start, $end - $start + 1 ) ;
                $rest = substr ( $str, $end + 2 ) ;

                $result[] = $item ;

                $this->_getSubStrs ( $from, $to, $rest, $result ) ;

            }

            return $result ;
        }

    }