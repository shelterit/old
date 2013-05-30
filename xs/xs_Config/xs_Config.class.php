<?php

    class xs_Config extends xs_Properties implements ArrayAccess {

        function __construct ( $values = null ) {

            // Make sure we're basic (and have a global registry)
            parent::__construct ( $values ) ;
        }

        public function offsetExists ( $index ) {
            return isset ( $this->values[$index] ) ;
        }

        public function offsetGet ( $index ) {
            if ( $this->offsetExists ( $index ) ) {
                return $this->values[$index] ;
            }
            return false;
        }

        public function offsetSet ( $index, $value ) {
            if ( $index ) {
                $this->values[$index] = $value ;
            } else {
                $this->values[] = $value ;
            }
            return true;
        }

        public function offsetUnset ( $index ) {
            unset ( $this->values[$index] ) ;
            return true;
        }

        public function getContents () {
            return $this->values ;
        }

        // method for parsing sections

        public function parse_section ( $section, $filter = false ) {

            $ret = null ;

            if ( isset ( $this->glob->config[$section] ) ) {

                $what = $this->glob->config[$section] ;

                if ( $filter ) {
                    $what = array () ;
                    foreach ( $this->glob->config[$section] as $idx => $val ) {
                        $b = strstr ( $idx, $filter ) ;
                       if ( $b !== false )
                          $what[$idx] = $val ;
                    }
                }

                $ret = $paths = $dels = array () ;

                $weight = -10 ;

                foreach ( $what as $key => $value ) {

                    $is_special = explode ( ':', $key ) ;

                    $pull = explode ( '/', $is_special[0] ) ;

                    $resource = $pull[0] ;

                    $path = substr ( $is_special[0], strlen ( $pull[0] ) + 1 ) ;

                    // $paths[$path] = $path ;

                    // is there a special clause?
                    if ( isset ( $is_special[1] ) ) {

                        // yes, it's a special one
                        $special = $is_special[1] ;

                        // echo "($special) " ;

                        switch ( $special ) {

                            default:
                                $ret[$resource][$path]['@'.$special] = $value ;
                                break ;

                            case 'condition' :
                                $aa = explode ( '=', $value ) ;
                                if ( isset ( $aa[0] ) ) $aa[0] = trim ( $aa[0] ) ;
                                if ( isset ( $aa[1] ) ) $aa[1] = trim ( $aa[1] ) ;
                                if ( $aa[0] == 'group' ) {
                                    // If we're asking for group credentials, do
                                    // the authentication check
                                    // echo '['.$aa[1].']' ;
                                    $check = $this->glob->user->inGroup ( $aa[1] ) ;
                                    // if ( $check ) echo "User in group!" ;
                                    if ( $check != true ) {
                                        // $ret[$resource][$path]['del'] = true ;
                                        $dels[$resource][$path] = true ;
                                    }
                                } elseif ( $aa[0] == 'username' || $aa[0] == 'user' ) {
                                    if ( $this->glob->user->username != $aa[1] ) {
                                        $dels[$resource][$path] = true ;
                                        // $ret[$resource][$path]['del'] = true ;
                                    }
                                }
                                break ;

                        }

                    } else {

                        // else, just a resource item
                        $ret[$resource][$path]['@label'] = $value ;
                        $ret[$resource][$path]['@weight'] = $weight += 10 ;

                    }
                    
                    if ( ! isset ( $ret[$resource][$path]['@path'] ) )
                        $ret[$resource][$path]['@path'] = $path ;
                    
                    // support a few dynamic variables
                    $user = $this->glob->user->username ;
                    
                    $ret[$resource][$path]['@path'] = 
                       str_replace ( array ( '{$username}'), array ( $user ), $ret[$resource][$path]['@path'] ) ;
                    
                    if ( isset ($ret[$resource][$path]['@iframe']) ) 
                        $ret[$resource][$path]['@iframe'] = 
                            str_replace ( array ( '{$username}'), array ( $user ), $ret[$resource][$path]['@iframe'] ) ;
                    
                    if ( isset ($ret[$resource][$path]['@popup']) ) 
                        $ret[$resource][$path]['@popup'] = 
                            str_replace ( array ( '{$username}'), array ( $user ), $ret[$resource][$path]['@popup'] ) ;
                    
                    $ret[$resource][$path]['@uid'] = 'xs-'.strtolower(str_replace (array('/','\\','_','+'), '-', $path ) ) ;
                }
            }
            
            if ( is_array ( $ret ) && count ( $ret ) > 0 )
                
                foreach ( $ret as $resource => $paths ) {
                    foreach ( $paths as $path => $value ) {
                        if ( isset ( $dels[$resource] ) ) {
                            foreach ( $dels[$resource] as $p => $tmp ) {
                                if ( strpos ( $path, $p ) !== false ) {
                                    unset ( $ret[$resource][$path] ) ;
                                }
                            }
                        }

                    }
                }

           return $ret ;
        }

    }

