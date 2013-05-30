<?PHP

    class xs_DataManager_Adapter_xs_config extends xs_DataManager_Adapter {

        private $username = null ;
        private $password = null ;

        function __construct ( $config = array () ) {
            parent::__construct ( $config ) ;
        }

        function setup () {
        }


        function query () {
        }

        function fetch_all ( $query ) {

            // first, schema of incoming queries
            $schema = 'user/username/password' ;
            
            // pull from the config file our hardcoded users
            $hard = $this->glob->config->parse_section ( 'user_management' ) ;
            
            // any users found?
            if ( isset ( $hard['user'] ) && count ( $hard['user'] ) > 0 ) {
                
                $users = $hard['user'] ;
                
                $path = $this->_parse ( $query, $schema ) ;
                // debug_r($path,'xs_config');
                
                $username = $password = '' ;
                
                if ( isset ( $path['username'] ) ) 
                    $username = trim ( $path['username'] ) ;
                
                if ( isset ( $path['password'] ) ) 
                    $password = trim ( $path['password'] ) ;
                
                // can we find the given user?
                if ( isset ( $users[$username] ) ) {
                    
                    // echo "[yes:username] " ;
                    
                    if ( isset ( $users[$username]['@password'] ) ) {
                        
                        $int_password = trim ( $users[$username]['@password'] ) ;
                        
                        // debug_r ( $hard ) ;
                        if ( $int_password == $password ) {
                            
                            
                            // username and password correct; return success
                            return array (
                                'name' => $users[$username]['@name'],
                                'label' => $users[$username]['@name'],
                                'username' => $username,
                                'role' => isset ( $users[$username]['@role'] ) ? explode ( '|', $users[$username]['@role'] ) : array (),
                                'group' => isset ( $users[$username]['@group'] ) ? explode ( '|', $users[$username]['@group'] ) : array (),
                                'function' => isset ( $users[$username]['@function'] ) ? explode ( '|', $users[$username]['@function'] ) : array (),
                            ) ;
                            
                        } else {
                            return array () ; // echo "wrong credentials. " ;
                        }
                    }
                }
            }
            
            return null ;
            
/*
                        $ret = $this->transfer ( $userinfo, array ( 'name', 'displayname', 'mail', 'dn', 'memberof', 'samaccountname', 'primarygroupid' ) ) ;
                        if ( isset ( $ret['displayname'] ) )
                            $ret['name'] = $ret['displayname'] ;
                        if ( isset ( $ret['mail'] ) )
                            $ret['email'] = $ret['mail'] ;
                        

                        $ret['group'] = $this->driver->user_groups ( $username, true ) ;
*/
                
        }

}
