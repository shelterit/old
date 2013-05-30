<?php

    /* This xs_User class is really a plugin, that also is accessible through
     * the global stack ($this->glob->user) because it's so important
     */

    class xs_User extends xs_EventStack_Plugin {

        public $authenticated = false ;

        // List of authenticators
        private $authenticators = array () ;

        // Values injected into this object as properties
        public $values = array() ;
        
        private $_blanks = array () ;

        // A simple array for configuring user credentials
        private $credentials = array () ;

        private $users = array () ;
        
        public $this_schema = array ( 'name', 'label', 'username', 'role', 'function', 'group' ) ;
        
        private $group = array () ;
        private $function = array () ;
        private $role = array () ;

        
        public $debug = false ;

        function __construct ( $values = null ) {

            // Make sure we're basic (and have a global registry)
            parent::__construct() ;

            // If there's an array coming in with values, fill them in
            if ( is_array ( $values ) )
                $this->__inject ( $values ) ;

            // fill us with default values
            $this->__inject ( array (
                'id' => null,
                'username' => null,
                'name' => null,
                'email' => null,
                'role' => null,
                'ip' => (string) $_SERVER['REMOTE_ADDR'],
                'checked' => false,
                'authenticated' => false,
            ) ) ;

            // this class owns and deals with these three basic events
            $this->_register_event ( XS_MODULE, 'on_user_blank' ) ;
            $this->_register_event ( XS_MODULE, 'on_user_config' ) ;
            $this->_register_event ( XS_MODULE, 'on_user_check' ) ;
            $this->_register_event ( XS_MODULE, 'on_user_auth' ) ;

        }

        function zane ( $arr ) {
            return str_replace(array("\n",'[',']'),'',print_r($arr,true)) ;
        }
        
        // Deal with user credentials and session persistence
        function ___users () {

            /* basically, here's what happens; this method attaches itself to
             * the XS_USERS event (the stack event that purports to deal with
             * users), firing off this on_user_blank event. Plugins
             * will listen out for this event, and feed data back if they feel
             * it's the right thing to do. Our callback functions will fetch
             * the data (credentials, mostly) and deal with it in order.   */

            if ( $this->debug ) debug('___users()','IN') ;
            
            // First, have we switched on DEV? (development mode)
            if ( isset ( $this->glob->config['dev_user_management']['DEV'] ) &&
                         $this->glob->config['dev_user_management']['DEV'] ) {

                $r = $this->glob->config->parse_section ( 'dev_user_management', 'user/' ) ;

                if ( isset ( $r['user'] ) )
                    
                    foreach ( $r['user'] as $user => $property )

                        $this->users[$user] = array (
                            'name' => $property['@name'],
                            'password' => $property['@password'] ,
                            'roles' => isset ( $property['@roles'] ) ? $property['@roles'] : null ,
                            'group' => isset ( $property['@group'] ) ? $property['@group'] : null ,
                        ) ;

                $this->glob->stack->add ( 'xs_users', $this->users ) ;

            }

            // Log it
            // $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] xs_User->___users () BEGIN :"  ) ;

            // Second, set up a generic query for the session
            $this->glob->data->register_query (

                // we're using the 'session' data source
                'session',

                // Let's call it something recognizable
                'session_user_credentials',
                
                // we won't be using a pre-defined template
                null,

                // and no cache
                null
            ) ;
            
                // debug($_SESSION);
                // debug($this);
                
            // fire a blanking user credential event to start the
            // workflow for the user authentication
            $this->_fire_event ( 'on_user_blank' ) ;

            if ( $this->debug ) debug('___users()','OUT') ;
        }

        // just a forced event listener, looking to see if anything is in the session
        function ___on_user_blank () {
            
            // $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] xs_User->___on_user_blank () BEGIN :"  ) ;

            if ( $this->debug ) debug('on_user_blank()', 'IN') ;
            if ( $this->glob->request->__get ( '_auth' ) !== null ) {

                $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] xs_User->___on_user_blank ('".$this->glob->request->__get('_auth', 'true')."') session: ".print_r ( $_SESSION, true )   ) ;
                $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] xs_User->___on_user_blank () FORCED END"  ) ;

                $this->glob->session->__delete ( 'session_user_credentials' ) ;
                $this->glob->session->__delete ( '_auth' ) ;

                return null ;
            }

            // $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] xs_User->___on_user_blank () should get session data. END"  ) ;

            if ( $this->debug ) debug('on_user_blank()', 'OUT') ;
            return $this->glob->data->get ( 'session_user_credentials' ) ;
        }
        
        function callback___on_user_blank ( $param = null ) {
            $this->_blanks[] = $param ;
            // debug($param,'real param');
        }
        

        // when blanking this user object, is something coming in? (session)
        function callback_final___on_user_blank () {

            $param = null ;
            foreach ( $this->_blanks as $blank )
                $param = $blank ;
            
            if ( $this->debug ) debug('callback_final___on_user_blank()', 'IN') ;
            // $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] xs_User->___on_user_blank_callback ( ".print_r ( $param, true ).")"  ) ;

            // if whomever tried to blank the user object returns NULL, it
            // basically means we should fire off a few events that would
            // fetch proper credentials and possibly authenticate them
            
            if ( $this->debug ) debug ( $this->_blanks, '$param' ) ;

            // debug ($param, "param");
            
            if ( $param == null ) {

                // $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] xs_User->___on_user_blank_callback : no params, firing events"  ) ;

                if ( $this->debug ) debug('callback_final___on_user_blank() :: FIRE on_user_config') ;
                $this->_fire_event ( 'on_user_config' ) ;
                
                // debug('callback_final___on_user_blank() :: FIRE on_user_check') ;
                // $this->_fire_event ( 'on_user_check' ) ;
                /*
                if ( $this->authenticated != 1 ) {
                    debug('error');
                    $this->alert ( 'notice', 'user check', "Username or password wrong or missing." ) ;
                }
                 * 
                 */

            } else {

                // or, we got a result of sorts, meaning someone wants us to
                // use a set of specific credentials instead of full blanking
                // (which probably means we found used details in the session)

                $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] xs_User->___on_user_blank_callback : injecting found param (".$this->zane($param).")"  ) ;

                if ( is_array ( $param ) )
                    $this->__inject ( $param ) ;

            }
                // debug($_SESSION);
                // debug($this);
            if ( $this->debug ) debug('callback_final___on_user_blank()', 'OUT') ;
        }

        function ___on_user_config () {
            // $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] xs_User->___on_user_config () BEGIN :"  ) ;

            if ( $this->debug ) debug('on_user_config()','IN') ;
            // check if there's a request for login coming in
            $r = $this->glob->request->__get_fields () ;

            // Well, is it?
            if ( isset ( $r['xs-login-username'] ) ) {

                if ( $this->debug ) debug('on_user_config()','OUT u/p') ;
                
                // yes, let's return those credentials
                return array (
                    'username' => strtolower ( $r['xs-login-username'] ),
                    'password' => $r['xs-login-password']
                ) ;
            }

            // if we're in DEV mode, also check to see if we're just switching user

            if ( isset ( $this->glob->config['dev_user_management']['DEV'] ) &&
                         $this->glob->config['dev_user_management']['DEV'] ) {

                if ( $this->debug ) debug('on_user_config()','OUT dev') ;
                return $this->dev_users () ;
                
            }

            // $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] xs_User->___on_user_config () END"  ) ;
            
            if ( $this->debug ) debug('on_user_config()','OUT') ;
            
            // else just return null for no action
            return null ;
        }
        
        function callback___on_user_config ( $param = null ) {
            // $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] xs_User->___on_user_config_callback (".$this->zane($param).")"  ) ;
            if ( $this->debug ) debug('callback___on_user_config()','IN') ;
            
            if ( $param != null ) {
                $this->credentials = $param ;
                
                // if username was set, just make sure it's lowercase
                if ( isset ( $this->credentials['username'] ) )
                    $this->glob->user->username = strtolower ( $this->credentials['username'] ) ;
                
                if ( $this->debug ) debug('callback___on_user_config()','Credentials set.') ;
                
            }
            
            if ( $this->debug ) debug('callback___on_user_config()','OUT') ;
        }
        
        function callback_final___on_user_config () {
            if ( $this->debug ) debug('callback_final___on_user_config()','IN') ;
            if ( $this->credentials != null ) {
                if ( $this->debug ) debug('callback_final___on_user_config()','Credentials found : firing on_user_check') ;
                $this->_fire_event ( 'on_user_check' ) ;
            }
            if ( $this->debug ) debug('callback_final___on_user_config()','OUT') ;
        }
        
        function ___on_user_check () {
            if ( $this->debug ) debug('on_user_check()') ;
            // $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] xs_User->___on_user_check."  ) ;
        }

        function callback_final___on_user_check () {

            if ( $this->debug ) debug('callback_final___on_user_check()','IN') ;
            // $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] xs_User->___on_user_check_callback (".$this->zane($param).")"  ) ;
            
            // if no one has returned or set any credentials, exit
            /*
            if ( ! isset ( $this->credentials ) || count ( $this->credentials ) < 1 ) {
                if ( $this->debug ) debug('callback_final___on_user_check()','OUT premature') ;
                return null ;
            }
             * 
             */
            // echo "on_user_check:" ; var_dump ( $this->credentials ) ;
            
            // $this->alert ( 'note', 'checking services', "(".print_r ( $this->glob->config['user_management']['service'], true).")" ) ;

            
            // first, check to see if we've configured a few good authenticators
            if ( isset ( $this->glob->config['user_management']['service'] )
              && is_array ( $this->glob->config['user_management']['service'] ) ) {

                // $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] xs_User->___on_user_check_callback (".$this->zane($param).")"  ) ;
                
                // do we have user credentials?
                // if ( isset ( $this->credentials['username'] ) ) {
                // if ( true ) {

                    $username = strtolower ( $this->glob->user->username ) ;

                    foreach ( $this->glob->config['user_management']['service'] as $service => $config ) {

                        $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] xs_User->___on_user_check_callback service='".$service."'"  ) ;

                        $this->authenticators[] = $service ;

                        // echo "<br/><hr/>[$service] :: " ;

                        $this->glob->data->register_datasource (

                            // What is the token identifier for this datasource?
                            $service,

                            // name of the adapter
                            $service,

                            // push in what token name, have the drivers find their own damn config options
                            // make sure your configuration.ini has an [$auth] with the config info needed

                            $this->glob->config[$service]

                        ) ;

                        $service_id = "user_fetch_{$service}_{$username}" ;

                        // register a query
                        $this->glob->data->register_query (
                            $service, $service_id, 'user/{username}/{password}', '+10 second'
                        ) ;

                        $un = ( isset ( $this->credentials['username'] ) ) ? strtolower ( $this->credentials['username'] ) : null ;
                        $pn = ( isset ( $this->credentials['password'] ) ) ? $this->credentials['password'] : null ;

                        // fetch the data
                        $result = $this->glob->data->get (
                            $service_id, array (
                                'username' => $un, 'password' => $pn
                            )
                        ) ;
                        
                        // debug ( array ( 'username' => $un, 'password' => $pn ), $service ) ;
                        // debug ( $result, $service ) ;

                        
                        if ( $result !== null && is_array ( $result ) && count ( $result ) > 0  && trim ( $un ) != '' ) {

                            $result['username'] = $un ;
                            $result['authenticated'] = true ;
                            // $result['groups'] = $this->groups ;

                            // debug_r($result,'something');
                            
                            foreach ( $result as $idx => $value )
                                $this->_set ( $idx, $value ) ;

                            $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] callback_user_check ( $service_id ) "  ) ;

                            // var_dump ( $this->values ) ;

                            // Yes, we would proclaim to be authenticated at this stage
                            $this->authenticated = 1 ;
                            $_SESSION['xs_auth'] = true;
                            $_SESSION['xs_auth_group'] = $this->group ;
                            $_SESSION['xs_auth_role'] = $this->role ;
                            $_SESSION['xs_auth_function'] = $this->function ;

                            // Persist $user to session and database
                            $this->database_sync () ;

                            $this->alert ( 'notice', 'user check', "[$service] authenticated ($un)" ) ;

                            // if authenticated, break out of authenticating
                            break ;

                        } else if ( $result != null ) {
                            $this->alert ( 'notice', 'user check', "[$service] didn't find ($un)" ) ;
                        } else {
                            // $this->alert ( 'error', 'error user check', "[$service] check returned error for (".$this->credentials['username'].")" ) ;
                        }

                    }

                // }
            }
            if ( $this->debug ) debug('callback___on_user_check()','OUT end') ;
        }


        function database_sync () {

            $id = null ;

            // Lookup username
            $find = $this->glob->tm->lookup_names (
               array ( 'user:' . $this->username => true )
            ) ;

            // If not found, create a user in the database
            if ( ! $find ) {

                // Create a new topic for this user
                $id = $this->glob->tm->create ( array (
                    'name' => 'user:' . $this->username,
                    'label' => isset ( $this->label ) ? $this->label : $this->name,
                    'type1' => xs::_user,
                    'email' => $this->email,
                    'group' => @serialize($this->group),
                    'function' => @serialize($this->function),
                    'role' => @serialize($this->role),
                ) ) ;

                // echo "(( $id )) " ;

                // force the id into the user object
                $this->_set ( 'id', $id ) ;

                $this->alert ( 'comment', 'Just a note ...', '... that your user profile has been first-time synhronized with the Intranet.' ) ;

            } else {

                // if already found, just make sure our user object has the proper id
                $id = $find[0]['id'] ;

                // force the id into the user object
                $this->_set ( 'id', $id ) ;

                // $this->alert ( 'comment', 'Just a note ...', '... that we\'re reusing a local profile we found in our local database. All good.' ) ;
            }

            // And persist $user to session, one more time, for good measure
            $this->persist();

        }

        function persist () {

            // Persist to session
            // $this->glob->session->xs_user_object = (string) serialize ( $this->__getArray() ) ;

            // $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] xs_User->persist ()"  ) ;
            
            $res = array () ;
            foreach ( $this->__get_array() as $idx => $v )
                if ( is_array ( $v ) || !is_object ( $v ) ) 
                    $res[$idx] = $v ;
            
            // debug ( $res, 'persist' ) ;

            $this->glob->data->put ( 
                'session_user_credentials', 
                $res 
            ) ;
            
            if ( ! isset ( $this->group ) || count ( $this->group ) < 1 )
               if ( isset ( $_SESSION['xs_auth_group'] ) )
                   $this->group = $_SESSION['xs_auth_group'] ;

            if ( ! isset ( $this->role ) || count ( $this->role ) < 1 )
               if ( isset ( $_SESSION['xs_auth_role'] ) )
                   $this->role = $_SESSION['xs_auth_role'] ;

            if ( ! isset ( $this->function ) || count ( $this->function ) < 1 )
               if ( isset ( $_SESSION['xs_auth_function'] ) )
                   $this->function = $_SESSION['xs_auth_function'] ;

            // var_dump ( $this->glob->user->__get_array () ) ;
        }

        function isAllowed ( $func ) {
            // echo "3";
            $sec = $this->_get_module ( 'security' ) ;
            return $sec->is_user_allowed_functionality ( $func ) ;
        }
        
        function isAuthenticated () {
            return $this->authenticated ;
        }

        function isUsername ( $username ) {
            return $this->isUser ( $username ) ;
        }
        
        function isUser ( $find ) {

            if ( ! is_array ( $find ) )
                $find = explode ( '|', $find ) ;

            $this->glob->seclog->logInfo ( '['.$this->glob->user->username."] xs_User->is_user ( ".str_replace(array("\n",'[',']'),'',print_r($find,true))." )"  ) ;

            foreach ( $find as $user ) {
                if ( $user == '*' )
                    return true ;

                if ( trim(strtoupper($user)) == trim(strtoupper($this->values['username'])) )
                    return true ;
            }
            
            return false ;
        }
        
        function isUsertype ( $type ) {
            // echo "2";
            $role = '' ;
            // var_dump ( $this->authenticated ) ;
            if ( isset ( $_SESSION['xs_auth'] ) && $_SESSION['xs_auth'] )
                $role = '@user' ;
            else
                $role = '@anonymous' ;
            if ( $type == '@all' ) 
                return true ;
            if ( $type == '@none' ) 
                return false ;
            // echo "[$role]/[$type] " ;
            if ( $role == $type ) 
                return true ;
            return false ;
        }
        
        function isRole () {
            return false ;
        }
        
        function isGroup ( $find ) {
            return $this->inGroup ( $find ) ;
        }

        function inGroup ( $find ) {

            // debug ( $find, 'User::inGroup' ) ;
            // debug ( $this->values['group'], 'User::inGroup' ) ;
            
            if ( ! is_array ( $find ) )
                $find = explode ( '|', $find ) ;

            if ( isset ( $this->values['group'] ) && $this->values['group'] ) {

                $groups = $this->values['group'] ;

                if ( ! is_array ( $groups ) )
                    $groups = explode ( '|', $this->values['group'] ) ;

                foreach ( $groups as $group ) {
                    if ( trim($group) == '*' ) {
                        // echo "!" ;
                        return true ;
                    }
                    foreach ( $find as $item ) {
                        // echo "<hr>".trim(strtoupper($group)).' == '.trim(strtoupper($item)).' <br/>' ;
                        if ( trim(strtoupper($group)) == trim(strtoupper($item)) ) {
                            // echo "TRUE " ;
                            return true ;
                        }
                    }
                }

            }
            return false ;
        }


        function dev_users () {

            $user = trim ( $this->glob->request->_user ) ;

            if ( $user != '' ) {

                // echo "user coming in" ;

                // print_r ( $this->users ) ;
                

                $u = array (
                    'username' => $user,
                    'name' => $this->users[$user]['name'],
                    'label' => $this->users[$user]['name'],
                    'password' => $this->users[$user]['password'],
                    'role' => isset ( $this->users[$user]['role'] ) ? $this->users[$user]['role'] : null,
                    'group' => isset ( $this->users[$user]['group'] ) ? $this->users[$user]['group'] : null,
                    'user' => isset ( $this->users[$user]['user'] ) ? $this->users[$user]['user'] : null,
                ) ;

                return $u ;

            }

            return null ;

        }

        /***************************************/


        function  __call ( $name, $arguments = null ) {

            // Yes, there's a property of that name
            if ( isset ( $this->values[$name] ) )
                return $this->values[$name] ;

            if ( isset ( $arguments[0] ) )
                return $arguments[0] ;

            return $arguments ;
        }

        public function __get ( $idx ) {
            if ( $idx == 'glob' ) {
                // echo "!" ;
                return parent::$glob ;
            }
            if ( isset ( $this->values[$idx] ) )
                return $this->values[$idx] ;
        }

        public function __fetch ( $idx, $default = '' ) {
            if ( isset ( $this->values[$idx] ) )
                return $this->values[$idx] ;
            else
                return $default ;
        }

        public function __set ( $idx, $value ) {
            $this->values[$idx] = $value ;
        }

        public function _set ( $idx, $value ) {
            $this->values[$idx] = $value ;
        }

        function __getArray () {
            return $this->values ;
        }

        function __get_array () {
            return $this->values ;
        }

        function __inject ( $values = array () ) {
            foreach ( $values as $idx => $value )
                $this->values[$idx] = $value ;
        }


    }
