<?PHP

    class xs_DataManager_Adapter_ad_ldap extends xs_DataManager_Adapter {

        private $ad = null ;
        private $ad_username = null ;
        private $ad_password = null ;
        private $ad_options = null ;


        function __construct ( $config = array () ) {
            parent::__construct ( $config ) ;
        }


        function setup () {

            // ok, let's try to initiate the driver for this plugin
            try {

                // we're trying to use a adLDAP object
                $this->driver = new adLDAP ( array (
                        'base_dn' => $this->config->base_dn,
                        'account_suffix' => $this->config->account_suffix,
                        'domain_controllers' => $this->config->domain_controllers
                ), $this->glob->logger ) ;

                $this->driver->set_ad_username ( $this->config['username'] ) ;
                $this->driver->set_ad_password ( $this->config['password'] ) ;

                $this->driver->connect () ;
                
                // var_dump ( $this->driver ) ;

                return true ;

            } catch (Exception $exc) {
                // Ouch!
                // echo '<pre>'.$exc->getTraceAsString().'</pre>' ;

                $this->alert ( 'error', 'Cannot connect to LDAP server', 'It seems we cannot connect to the LDAP server ('.str_replace(array('[',']',"\n","\r"),'', print_r($this->config->domain_controllers,true)).')' ) ;
                return null ;
            }

        }


        function query () {
            // echo "!!" ;
        }

        function fetch_all ( $query ) {

            $schema = 'user/{username}/{password}' ;

            // echo "<hr><pre style='color:blue;border:solid 2px #999;background-color:gray;'>" ;
            // echo "ad_ldap_adaptor: <br>   query = " ; var_dump ( $query ) ;

                try {

                    $path = $this->_parse ( $query, $schema ) ;
                    
                    if ( isset ( $path['user'] ) && $path['user'] == 'user' && $this->driver != null ) {

                        $ret = array () ;

                        $username = $path['{username}'] ;

                        // echo "   username = " ; var_dump ( $username ) ;

                        $userinfo = $this->driver->user_info ( $username ) ;
                        // debug($userinfo, 'tex');
                        if ( $userinfo == false )
                            return null ;
                        // echo "   userinfo = <pre style='margin:0;padding:0;'>" ; print_r ( $userinfo ) ; echo "</pre>" ;

                        // var_dump ( $userinfo ) ;
                        /*
                        if (isset($userinfo[0]['displayname'][0]))
                            $ret['name'] = $userinfo[0]['displayname'][0] ;

                        if (isset($userinfo[0]['mail'][0]))
                            $ret['email'] = $userinfo[0]['mail'][0] ;

                        primarygroupid
                        memberof
                        samaccountname
                        dn
                        */

                        
                        $ret = $this->transfer ( $userinfo, array ( 'name', 'displayname', 'mail', 'dn', 'memberof', 'samaccountname', 'primarygroupid' ) ) ;
                        if ( isset ( $ret['displayname'] ) )
                            $ret['name'] = $ret['displayname'] ;
                        if ( isset ( $ret['mail'] ) )
                            $ret['email'] = $ret['mail'] ;
                        

                        $ret['group'] = $this->driver->user_groups ( $username, true ) ;

                        // echo "   return = <pre style='margin:0;padding:0;'>" ; print_r ( $ret ) ; echo "</pre>" ;

                // echo "</pre>" ;
                        return $ret ;
                    }

                } catch ( exception $ex ) {
                    var_dump ( $ex ) ;
                }

                return null ;
                // echo "</pre>" ;
                
        }


        function transfer ( $userinfo = array (), $who = array () ) {
            $ret = array () ;
            foreach ( $who as $id )
                if (isset($userinfo[0][$id])) {
                    $c = $userinfo[0][$id][0] ;
                    // echo "[$id]=(".sizeof($userinfo[0][$id]).")(".sizeof($c).")  --- " ;
                    if ( sizeof ( $userinfo[0][$id] ) == 1 ) 
                        $ret[$id] = $userinfo[0][$id] ;
                    else
                        $ret[$id] = $c ;
                }
            return $ret ;
        }


}
