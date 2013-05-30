<?php

    class xs_Session extends xs_Properties {

        public function __set ( $name, $value ) {
            $_SESSION[$name] = $value ;
        }

        public function __get ( $name ) {
            if ( isset ( $_SESSION[$name] ) )
                return $_SESSION[$name] ;
            return null ;
        }

        public function __delete ( $name ) {

            // var_dump ( $name ) ;
            // var_dump ( $_SESSION ) ;
            
            if ( isset ( $_SESSION[$name] ) ) {
                $_SESSION[$name] = null ;
                unset ( $_SESSION[$name] ) ;
                // echo "!" ;
            } else {
                // echo "@" ;
            }
            return null ;
        }

        public function get ( $name, $default = null ) {
            if ( isset ( $_SESSION[$name] ) )
                return $_SESSION[$name] ;
            return $default ;
        }

    }
