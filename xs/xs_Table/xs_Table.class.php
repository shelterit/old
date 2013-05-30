<?php

    class xs_Table {
        
        // the original raw data
        private $data = array () ;
        
        // filtered data; picking out the bits we need
        private $filtered = array () ;
        
        // indeces of stuff we think are important
        private $index = array () ;
        
        // our legends (column names)
        private $legend = array () ;

        // our table configurations
        private $config = array () ;
        
        // tracking history
        public $history = array () ;
        
        private $start_time = 0 ;
        
        public function __construct ( $arr = array (), $config = array () ) {
            
            $this->start_time = microtime ( true ) ;
            
            $this->_log ( 'Construct' ) ;
            
            // default settings
            $this->_configure ( array (
                'first_line_legend' => false,
            ) ) ;
            
            $this->_log ( 'Default configuration' ) ;
            
            // check incoming configurations
            if ( count ( $config ) > 0 ) {
                $this->_configure ( $config ) ;
                $this->_log ( 'Additional ' . count ( $config ) . ' configurations' ) ;
            }
            
            // if we've got something incoming, inject it
            if ( count ( $arr ) > 0 )
                $this->_inject ( $arr ) ;
                $this->_log ( 'Injecting ' . count ( $arr ) . ' records' ) ;
        }
        
        public function _configure ( $config = array () ) {
            foreach ( $config as $idx => $value )
                $this->config[$idx] = $value ;
        }
        
        private function _log ( $what ) {
            $t = round ( microtime ( true ) - $this->start_time, 5 )   ;
            $this->history[] = '[' . sprintf("%01.3f", $t ) . '] ' . $what ;
        }
        
        public function _inject ( $arr = array (), $config = array () ) {
            
            if ( count ( $config ) > 0 )
                $this->_configure ( $config ) ;
            
            $include_first = true ;
            
            reset ( $arr ) ;
            $first = current ( $arr ) ;
            reset ( $arr ) ;
            
            if ( isset ( $this->config['first_line_legend'] ) && $this->config['first_line_legend'] == true ) {
                $this->legend = $first ;
                $include_first = false ;
            }
            
            foreach ( $first as $idx => $value )
                if ( isset ( $this->config['legend'] ) && isset ( $this->config['legend'][$idx] ) )
                    $this->legend[$idx] = $this->config['legend'][$idx] ;
                else 
                    $this->legend[$idx] = $idx ;
                
            if ( is_array ( $arr ) && count ( $arr ) > 0 ) {
                
                reset ( $arr ) ;
                
                $count = count ( $this->data ) - 1 ;
                
                foreach ( $arr as $idx => $item ) {
                    $count++ ;
                    if ( ! $include_first && $count == 0 ) {
                    } else {
                        
                        foreach ( $this->legend as $c => $idx )
                            $this->data[$count][$idx] = $item[$c] ;
                    }
                }
                
                $this->_log ( 'Injected ' . count ( $arr ) . ' records (total now: ' . ( $count + 1 ) . ' records)' ) ;
                
            }
        }
        
        function _inject_pdo ( $sql, $config = array () ) {
            
            $pdo = $ret = null ;
            
            if ( isset ( $this->config['pdo_driver'] ) )
                $pdo = $this->config['pdo_driver'] ;
            
            if ( $pdo ) {
            
                $statement = $pdo->query ( $sql ) ;
                
                $statement->setFetchMode ( PDO::FETCH_ASSOC ) ;
                
                $ret = $statement->fetchAll () ;

                $this->_inject ( $ret, $config ) ;
                
                return ;
            }
            
            return null ;
            
        }
        
        function _filter ( $in_schema = array (), $out_schema = array () ) {
            
           // array ( 2 => 'firstname', 1 => 'lastname' )
               
            $ret = array () ;
            
            // go through all records
            foreach ( $this->data as $idx => $item ) {
                foreach ( $in_schema as $what ) {
                    if ( isset ( $item[$what] ) ) {
                        $ret[$idx][$what] = $item[$what] ;
                    }
                }
                foreach ( $out_schema as $c => $what ) {
                    $ret[$idx][$c] = $what ;
                }
            }
            
            $this->filtered = $ret ;
            
            return $ret ;
        }

        function _make_username ( $str ) {
            $ret = '' ;
            $r = explode ( ' ', $str ) ;
            foreach ( $r as $t )
                $ret .= $t[0] ;
            return strtolower ( $ret . substr ( $r[count ( $r ) - 1], 1 ) ) ;
        }
        
        function _cleanup_name ( $str ) {
            if ( strtolower ( $str ) != 'null' )
                return ucwords ( strtolower ( $str ) ) ;
            return '' ;
        }

        function _cleanup ( $schema = array () ) {
            foreach ( $this->data as $idx => $val ) {
                foreach ( $schema as $field => $funky ) {
                    if ( isset ( $this->data[$idx][$field] ) ) {
                        $f = '_cleanup_' . $funky ;
                        $this->data[$idx][$field] = $this->$f ( $this->data[$idx][$field] ) ;
                    }
                }
            }
            // return $arr ;
        }

        function _create_usernames ( $in = array () ) {
            
            foreach ( $this->data as $idx => $val ) {
                
                $compound = $val['firstname'].' '. $val['lastname'] ;
                $w = explode ( ' ', $val['firstname'] ) ;
                $comp = $w[0].' '. $val['lastname'] ;

                $this->data[$idx]['username'] = $this->_make_username ( $compound ) ;
                $this->data[$idx]['username_alt'] = $this->_make_username ( $comp ) ;
            }
        }

        static function _get_csv ( $file ) {
            $row = 0;
            $ret = array () ;
            if (($handle = fopen($file, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 50000, ",")) !== FALSE) {
                    $num = count($data);
                    for ($c=0; $c < $num; $c++)
                        $ret[$row][$c] = $data[$c] ;
                    $row++;
                }
                fclose($handle);
            }
            return $ret ;
        }
        
        public function _get_keys () {
            if ( isset ( $this->data[0] ) && is_array ( $this->data[0] ) )
                return array_keys ( $this->data[0] ) ;
            return null ;
        }
        
        public function _get_data () {
            return $this->data ;
        }

        public function _get_filtered_data () {
            return $this->filtered ;
        }

    }
