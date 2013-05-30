<?php

    class xs_DataManager_Adapter_session extends xs_DataManager_Adapter {

        private $debug = false ;

        function __construct ( $config = array () ) {
            parent::__construct ( $config ) ;
            // echo "session_creation " ;
        }

        function instantiate () {
            $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] plugin_session->instantiate ()"  ) ;
        }

        function fetch_all ( $query, $token = null ) {
            $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] plugin_session->fetch_all ( $token )"  ) ;
            // echo "fetch_all " ;
            if ( $this->debug ) echo "session:fetch_all($query) " ;
            if ( $token )
                return $this->get ( $token ) ;
            if ( $query != null )
                echo "[$query] " ;
        }

        function get ( $what ) {
            if ( $this->debug ) echo "session:get($what) " ;
            if ( isset ( $_SESSION[$what] ) ) {
                $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] plugin_session->get ( $what ), return SUCCESS data"  ) ;
                return $_SESSION[$what] ;
            }
            $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] plugin_session->get ( $what ), return NULL"  ) ;
            if ( $this->debug ) echo "session:get found NOTHING. " ;
            if ( $this->debug ) print_r ( $_SESSION ) ;
            return null ;
        }

        function put ( $what, $data ) {
            // if ( $this->debug )
                    // echo "session:put($what) " ;
            $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] plugin_session->put ( $what ) : ''".str_replace(array("\n",'[',']'),'',print_r($data,true))."''"  ) ;
            $_SESSION[$what] = $data ;
        }

    }
