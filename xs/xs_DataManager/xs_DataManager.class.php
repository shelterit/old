<?php

    class xs_DataManager extends xs_Core {

        // Should I override values?
        const OVERRIDE = true ;

        // Our registered queries
        // private $driver = null ;
        private $query  = null ;
        private $db     = null ;
        private $data   = null ;

        // a register over registrars, gets and status
        private $registry = array () ;

        // debugging, obviously
        private $debug = false ;

        function __construct () {
            parent::__construct () ;
            $this->adapter = array () ;
            $this->query = array () ;
            $this->db = array () ;
            $this->data = array () ;
        }

        // if the datasource has a adapter
        function get_adapter ( $adapter_id = 'tmp' ) {

            if ( ! isset ( $this->db[$adapter_id] ) ) {

                // Meaning, there is no info on such an adapter. Check if
                // there is a generic driver with that id

                try {

                    // try to initiate a blank / basic driver
                    $this->db[$adapter_id]['class'] = "xs_DataManager_Adapter_{$adapter_id}" ;

                    $this->db[$adapter_id]['instance'] = new $this->db[$adapter_id]['class'] ;
                    if ( $this->debug ) $this->glob->seclog->logInfo ( '['.$this->glob->user->username.'] dataManager->get_adapter ('.$adapter_id.' ) SUCCESS'  ) ;

                } catch ( exception $ex ) {
                    echo "Couldn't initiate " . $this->db[$adapter_id]['class'] ;
                    $this->glob->seclog->logInfo ( '['.$this->glob->user->username.'] dataManager->get_adapter ('.$adapter_id.' ) FAILED!'  ) ;
                    // var_dump ( $ex ) ;
                }

            } else {

               if ( $this->debug ) $this->glob->seclog->logInfo ( '['.$this->glob->user->username.'] dataManager->get_adapter ('.$adapter_id.' ) RESUSING existing adapter.'  ) ;

            }

            if ( isset ( $this->db[$adapter_id] ) ) {

                // Is there already an instance?
                if ( isset ( $this->db[$adapter_id]['instance'] ) )
                   return $this->db[$adapter_id]['instance'] ;

                // echo '('.$this->db[$adapter_id]['class'].') ' ;

                try {

                    // If not, invoke it
                    $class  = $this->db[$adapter_id]['class'] ;
                    $config = $this->db[$adapter_id]['config'] ;

                    $this->db[$adapter_id]['instance'] = new $class ( $config ) ;

                    if ( $this->debug ) $this->glob->seclog->logInfo ( '['.$this->glob->user->username.'] dataManager->get_adapter ('.$adapter_id.' ) Instance CREATED.'  ) ;

                    // return it
                    return $this->db[$adapter_id]['instance'] ;

                } catch ( exception $ex ) {
                    echo "Couldn't initiate " . $class ;
                    $this->glob->seclog->logInfo ( '['.$this->glob->user->username.'] dataManager->get_adapter ('.$adapter_id.' ) Instance FAILED! (class '.$class.')'  ) ;
                    // var_dump ( $ex ) ;
                }

            }

            if ( $this->debug ) $this->glob->seclog->logInfo ( '['.$this->glob->user->username.'] dataManager->get_adapter ('.$adapter_id.' ) NULL'  ) ;
            return null ;
            
        }

        // Get the native driver rather than the xSiteable adapter.
        // Naughty way to get, say, the PDO driver directly, when you
        // want to hack and do bad-practice stuff and muck it all up
        // and everything will go to hell, and it will be ALL YOUR FAULT!
        // (But I'll still let you do it. I'm pathetic that way.)

        function get_native_driver ( $adapter_id = 'file' ) {

            // first, let's get the xSiteable wrapper adapter
            $xs_adapter = $this->get_adapter ( $adapter_id ) ;

            if ( $this->debug ) $this->glob->seclog->logInfo ( '['.$this->glob->user->username.'] dataManager->get_native_adapter ('.$adapter_id.' )'  ) ;

            // try to pry and return the native driver from the wrapper
            return $xs_adapter->get_driver () ;

        }

        // register a datasource, expects a xs_DataManager_Plugin instance as $instance
        // TODO : if not an instance, maybe a token we can register and deal with
        //        on this end to improve performance

        public function register_datasource ( $datasource_name, $adapter_name = 'pdo', $config = array () ) {

            // register that our datasource uses a particular adapter
            if ( ! isset ( $this->db[$datasource_name] ) ) {
                $this->db[$datasource_name]['adapter'] = $adapter_name;
                $this->db[$datasource_name]['config'] = $config ;
                $this->db[$datasource_name]['instance'] = null ;
                $this->db[$datasource_name]['class'] = "xs_DataManager_Adapter_{$adapter_name}" ;
                if ( $this->debug ) $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] dataManager->register_datasource ($datasource_name, $adapter_name ) Ok."  ) ;
            } else {
                if ( $this->debug ) $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] dataManager->register_datasource ($datasource_name, $adapter_name ) already registered."  ) ;
            }

        }

        public function query_info ( $token ) {
            return $this->query[$token] ;
        }

        public function register_info () {
            return $this->registry ;
        }


        function register_action ( $what, $data = array (), $trace = null ) {

            $caller=array_shift($trace);

            $source = '(unknown)' ;
            if (isset($caller['file']))
                $source = $caller['file'];

            $db = 0 ;
            if ( is_array ( $data ) && isset ( $data['db'] ) )
                $db = $data['db'] ;

            $token = 0 ;
            if ( is_array ( $data ) && isset ( $data['token'] ) )
                $token = $data['token'] ;

            $msg = true ;
            if ( is_array ( $data ) && isset ( $data['msg'] ) )
                $msg = $data['msg'] ;

            // echo "<div style='border:dotted 2px #ccc;padding:10px;margin:10px;'>[$what][$db][$token][$source]</div> " ;
            
            $this->registry[$what][$db][$token][$source][uuidSecure()] = $msg ;

        }

        public function get_query_object ( $token ) {
            if ( isset ( $this->query[$token] ) )
                return $this->query[$token] ;
            return null ;
        }

        public function register_query ( $database, $token, $sql, $timer = XS_CACHE_DEFAULT, $params = array () ) {

            $this->register_action ( 'query', array ( 'db'=>$database, 'token'=>$token ), debug_backtrace() ) ;
            /*
            $trace=debug_backtrace();
            $caller=array_shift($trace);
            $source = '(unknown)' ;
            if (isset($caller['file']))
                $source = $caller['file'];
            $this->register_query[$database][$token][$source][uuidSecure()] = true ;
            */

            if ( ! isset ( $this->query[$token] ) ) {

                // Make sure we register and use the params in sorted order
                ksort ( $params ) ;

                // Ok, register the thing
                $this->query[$token] = array (
                   'db' => $database,
                   'sql' => $sql,
                   'params' => $params,
                   'timer' => $timer
                ) ;

                $this->query[$token]['id'] = $this->create_id ( $token, $params ) ;
                $this->query[$token]['hash'] = $this->create_hash ( $this->query[$token]['id'] ) ;

                $this->query[$token]['cache'] = new Cachette (
                    $this->query[$token]['id'],
                    array ( 'time' => $this->query[$token]['timer'], 'cache_dir' => $this->glob->config['framework']['cache_directory'] ),
                    $this->glob
                ) ;
                
                if ( $this->debug ) {
                    $s = print_r ( $sql, true ) ;

                    $this->glob->seclog->logInfo ( 
                        '['.(string)$this->glob->user->username.
                        "] dataManager->register_query (".
                        (string)$database.", ".
                        (string)$token.", ".
                        (string)$s.", ".
                        (string)$timer." ) Ok."  ) ;
                }
                
                // query is registered, but return the id we generated Because We Care [TM] !
                return $this->query[$token]['id'] ;

            }

            if ( $this->debug ) $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] dataManager->register_query ($database, $token, $sql, $timer ) RESUISNG old query."  ) ;

            // query is registered, but return the id we generated Because We Care [TM] !
            return $this->query[$token]['id'] ;
        }


        public function create_hash ( $id ) {
            return md5 ( $id ) ;
        }

        public function create_id ( $token, $params = array () ) {

            if ( !is_array ( $params ) )
                $params = array ( $params ) ;
            
            // Make sure we register and use the params in sorted order
            ksort ( $params ) ;

            // What to return? Start with the token
            $ret = $token ;
            
            $ripper   = array ('/','\\','?',':') ;
            $ripperit = array ('/','\\') ;

            // Add every param and value
            foreach ( $params as $param => $value )
                $ret .= '_'.str_replace ( $ripper, '_', $param).'='.str_replace ( $ripperit, '|', $value) ;

            return $ret ;
        }
        
        public function get_id ( $token ) {
            if ( isset ( $this->query[$token]['id'] ) )
               return $this->query[$token]['id'] ;
            return null ;
        }

        public function get_hash ( $token ) {
            if ( isset ( $this->query[$token]['hash'] ) )
               return $this->query[$token]['hash'] ;
            return null ;
        }

        public function get_query ( $token ) {
            if ( isset ( $this->query[$token] ) )
               return $this->query[$token] ;
            return null ;
        }

        // get the data result from a token (parameters optional)

        public function get ( $token, $params = null ) {

            // Try to get the item for this token
            $item = $this->get_query ( $token ) ;

            // var_dump ( $item ) ;

            // yup, got the item
            if ( $item ) {

                if ( $this->debug ) $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] dataManager->get ( $token ) found item."  ) ;


                // Check if it is cached
                if ( ! $cached = $item['cache']->get() ) {

                   if ( $this->debug ) $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] dataManager->get ( $token ) not cached."  ) ;


                   // Not cached? Go fetch it!
                   $cached = $item['cache']->put ( $this->query ( $token, $params ) ) ;
                   $this->register_action ( 'get', array ( 'db'=>$item['db'], 'token'=>$token, 'msg'=>'CREATED' ), debug_backtrace() ) ;

                } else {
                   if ( $this->debug ) $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] dataManager->get ( $token ) found CACHED item."  ) ;
                   $this->register_action ( 'get', array ( 'db'=>$item['db'], 'token'=>$token, 'msg'=>'CACHED' ), debug_backtrace() ) ;
                }

                return $cached ;

            } else {

                if ( $this->debug ) $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] dataManager->get ( $token ) NOT found item."  ) ;

                // no item. Could it be in temporary memory?
                if ( isset ( $this->data[$token] ) ) {
                    if ( $this->debug ) $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] dataManager->get ( $token ) ... but found in temporary memory."  ) ;
                    $this->register_action ( 'get', array ( 'db'=>$item['db'], 'token'=>$token, 'msg'=>'MEMORY CACHED' ), debug_backtrace() ) ;
                    return $this->data[$token] ;
                }
            }

            $this->register_action ( 'get', array ( 'db'=>$item['db'], 'token'=>$token, 'msg'=>'FAILED' ), debug_backtrace() ) ;
            return null ;

        }

        // just an alias
        public function set ( $token, $data ) {
            $this->put ( $token, $data ) ;
        }

        public function reset ( $token ) {
            
            // Try to get the item for this token
            $item = $this->get_query ( $token ) ;

            // yup, got the item
            if ( $item ) {

                // Is the cache set?
                if ( isset ( $item['cache'] ) ) {

                    // reset the cache
                    if ( $this->debug ) $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] dataManager->reset ( $token ) Item cache cleared, should force queries to re-query."  ) ;
                    $item['cache']->reset () ;
                    
                }

            }

        }

        // put the result of the query into the table
        public function put ( $token, $data ) {

            // Try to get the item for this token
            $item = $this->get_query ( $token ) ;

            
            // yup, got the item
            if ( $item ) {

                    if ( $item['timer'] ) {
                        if ( $this->debug ) $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] dataManager->put ( $token ) Item cached."  ) ;
                        $cached = $item['cache']->put ( $data ) ;
                    } else {
                        if ( $this->debug ) $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] dataManager->put ( $token ) Item NOT cached, no write."  ) ;

                        try {

                            // what datasource are we using?
                            $instance = $this->get_adapter ( $item['db'] ) ;

                            $instance->put ( $token, $data ) ;

                        } catch ( exception $ex ) {
                            echo "!!!!!!!!!!!!!!" ;
                        }
                    }

            } else {

                // didn't get an item, so we'll make it to memory (no cache)

                if ( $this->debug ) $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] dataManager->put ( $token ) Memory cached."  ) ;
                $this->data[$token] = $data ;
            }

            if ( $this->debug ) $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] dataManager->put ( $token ) Ok."  ) ;

        }

        function query ( $query_token, $params = array () ) {

            if ( $this->debug ) $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] dataManager->query ( $query_token ) Entry:"  ) ;

            // Try to get the query for this token
            $query = $this->get_query ( $query_token ) ;
            
            // $s = print_r ( $query, true ) ;
            // if ( $this->debug ) $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] dataManager->query ( $query_token ) query=[$s]"  ) ;

            // what datasource are we using?
            $instance = $this->get_adapter ( $query['db'] ) ;

            $i = @ (int) $instance ;

            if ( $this->debug ) $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] dataManager->query ( $query_token ) instance=[$i]"  ) ;
            // var_dump ( $query_token ) ;
            
            // initialize the adapter instance (if not already done)
            $instance->setup () ;

            // fetch all
            $result = $instance->fetch_all ( 
                $this->prepare_query ( $query['sql' ], $params, $query_token ), $query_token
            ) ;

            $qt = print_r ( $query_token, true ) ;
            $r = print_r ( $result, true ) ;
            
            if ( $this->debug ) $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] dataManager->query ( $qt ) fetch_all=[$r]"  ) ;

            // var_dump ( $this->prepare_query ( $query['sql' ], $params, $query_token ) ) ;


            // return result
            return $result ;

        }

        function prepare_query ( $query_template, $args = array (), $query_token ) {

            $debug = false ;

            if ( is_array ( $query_template ) ) {

                $qt = print_r ( $query_template, true ) ;
                
                if ( $this->debug ) $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] dataManager->prepare_query ( $qt ) Array!"  ) ;

                // $count = count ( $query_template ) ;

                return $query_template ;

                $str = "" ;

                foreach ( $query_template as $idx => $val ) {
                    $str .= '/' . $idx . ':' . $val ;
                }

                $query_template = $str ;

                // var_dump ( $query_template ) ;
                
            }

            // Break our path into little bits
            // $break = $query_template ;

            // echo "<hr />" ; var_dump ( $args ) ;
            
            // Find the tokens used from our schema template
            $tokens_first = $this->_getSubStrs ( "{","}", $query_template ) ;

            $tokens = array () ;

            if ( count ( $tokens_first ) < 1 ) {
                if ( $this->debug ) $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] dataManager->prepare_query ( $query_template ) tokens_first == 0, exiting"  ) ;
                return $query_template ;
            }

            if ( $debug ) { echo "<hr><pre style='background-color:#ccc;color:blue;border:dotted 2px #999;'>[data_manager] '$query_token': <br>" ; }
            if ( $debug ) { echo "  query_template = " ; var_dump ( $query_template ) ; }
            if ( $debug ) { echo "  args = " ; var_dump ( $args ) ; }

            // create new tokens with the brackets intact so we can replace them in a template
            foreach ( $tokens_first as $token )
                $tokens[$token] = '{'.$token.'}' ;

            if ( $debug ) { echo "  tokens = " ; var_dump ( $tokens ) ; }

            // yeah, replace tokens found with their argument equivalent
            $r = str_replace ( $tokens, $args, $query_template, $count ) ;

            if ( $debug ) { echo "  replaced = " ; var_dump ( $r ) ; echo "</pre><hr> " ; }

            if ( $this->debug ) $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] dataManager->prepare_query ( $query_template ) r=[$r]"  ) ;

            return $r ;
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
