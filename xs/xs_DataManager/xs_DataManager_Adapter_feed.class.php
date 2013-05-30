<?php

    class xs_DataManager_Adapter_feed extends xs_DataManager_Adapter {

        private $debug = true ;

        function __construct ( $config = array () ) {
            parent::__construct ( $config ) ;
            // echo "session_creation " ;
        }

        function instantiate () {
            $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] plugin_feed->instantiate ()"  ) ;
        }

        function fetch_all ( $query, $token = null ) {
            
            $feed_URI = '' ;
            $context = array () ;
            
            $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] plugin_session->fetch_all ( $token )"  ) ;
            // echo "fetch_all " ;
            if ( is_array ( $query ) ) {
                $feed_URI = $query[0] ;
                $context = $query[1] ;
            } else {
                $feed_URI = $query ;
            }
            // print_r ( $feed_URI ) ;
            $cxContext = stream_context_create ( $context ) ;
            $file = file_get_contents ( $feed_URI, false, $cxContext ) ;

            if ( $file )
                return $file ;

            return null ;
        }

        function get ( $what ) {
        
            return null ;
        }

        function put ( $what, $data ) {

        }

    }
