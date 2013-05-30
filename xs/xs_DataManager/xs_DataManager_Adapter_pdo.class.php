<?php

    class xs_DataManager_Adapter_pdo extends xs_DataManager_Adapter {

        function __construct ( $config = array () ) {
            parent::__construct ( $config ) ;
        }

        function instantiate () {
            
            // ok, let's try to initiate the driver for this plugin
            try {

                // we're trying to use a PDO object
                @$this->driver = new PDO (
                   $this->config['dsn'],
                   $this->config['username'],
                   $this->config['password'], 
                   array ( PDO::ATTR_PERSISTENT => true )
                ) ;
                
                $this->status = true ;

            } catch (Exception $exc) {
                
                // Ouch!
                // echo '<pre>'.$exc->getTraceAsString().'</pre>' ;
                // var_dump ( $this->driver ) ; echo "<br><br><hr><br><br>" ;

                $this->alert ( 'error', 'Cannot connect to PDO server', 'It seems we cannot connect to the PDO server ('.str_replace(array('[',']',"\n","\r"),'', print_r($this->config,true)).')' ) ;

                $this->status = false ;
            }

            // echo "<pre>" ; print_r ( $this->driver ) ; echo "</pre>" ;
        }

        function fetch_all ( $query ) {

            $sql = false ;

            // echo "<hr><pre style='color:red;border:solid 2px #999;background-color:yellow;'>" ;
            // echo "pdo_adaptor: <br>   query = " ; var_dump ( $query ) ;

            if ( $this->status ) {

                if ( ! is_array ( $query ) && strtoupper ( substr ( $query, 0, 6 ) ) == 'SELECT' )
                    $sql = true ;

                if ( ! $sql ) {

                    if ( is_array ( $query ) ) {

                        // echo "<b>TM</b>" ;
                        // it's an array, so pass it to the Topic Maps engine
                        return $this->glob->tm->query ( $query ) ;

                    } else {

                        $path = $this->_parse ( $query, 'user/{username}/{password}' ) ;

                        if ( isset ( $path['user'] ) && $path['user'] == 'user' ) {

                            $ret = array () ;

                            $username = $path['{username}'] ;
                            $password = $path['{password}'] ;

                            $res = $this->glob->tm->query ( array ( 
                                'name' => 'user:'.$username
                            ) ) ;
                            $res = end ( $res ) ;
                            
                            if ( count ( $res ) > 0 ) {
                                // debug('1');
                                if ( isset ( $res['password'] ) ) {
                                    // debug('2');
                                    if ( $res['password'] == $password ) {
                                        // debug('3');
                                        
                                        $ret = $this->transfer ( $res, array ( 'name', 'displayname', 'mail' ) ) ;
                                        
                                        if ( isset ( $ret['displayname'] ) )
                                            $ret['name'] = $ret['displayname'] ;
                                        if ( isset ( $ret['mail'] ) )
                                            $ret['email'] = $ret['mail'] ;

                                        if ( isset ( $res['group'] ) )
                                            $ret['group'] = @unserialize ( $res['group'] ) ;
                                        
                                        if ( isset ( $res['function'] ) )
                                            $ret['function'] = @unserialize ( $res['function'] ) ;
                                        
                                        if ( isset ( $res['role'] ) )
                                            $ret['role'] = @unserialize ( $res['role'] ) ;
                                        
                                        return $ret ;
                                    }
                                } else
                                    return array () ;
                            } else 
                                return null ;
                            
                            // debug ( $res, 'PDO' ) ;
                            // var_dump ( $username ) ;
                            // $userinfo = $this->driver->user_info ( $username ) ;

                            // TODO : Generic SQL / PDO support here

                        }
                    }

                } else {

                    try {

                        $inst = $this->driver->prepare ( $query ) ;
                        $inst->execute() ;

                        return $inst->fetchAll ( PDO::FETCH_ASSOC ) ;

                    } catch ( exception $ex ) {
                        print_r ( $ex ) ;
                    }
                }
            }

            // echo "</pre>" ;
        }

        function get ( $what ) {

            if ( $this->status ) {

                if ( $what[0] == '/' )
                    $what = substr ( $what, 1 ) ;

                $chunks = explode ( '/', $what ) ;

                if ( count ( $chunks > 1 ) ) {

                    $table = $chunks[0] ;
                    $id = $chunks[1] ;

                    $sql = "SELECT * FROM {$table} WHERE id={$id} OR {$table}_id={$id}" ;

                    return $this->fetch_all ( $sql ) ;
                }

                //    'user/ajohannesen'

                foreach ( $chunks as $chunk ) {


                }

            } else

                return null ;
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
