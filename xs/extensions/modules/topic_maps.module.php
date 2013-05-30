<?php

    class xs_module_topic_maps extends xs_Action {

        public $meta = array (
            'name' => 'Pages module',
            'version' => '1.0',
            'author' => 'Alexander Johannesen',
            'author_link' => 'http://shelter.nu/',
            'editable_options' => true,
        ) ;
        
        private $registry = array () ;
        
        private $cache = null ;

        function _register_type ( $idx, $value ) {
            // echo "[$idx] " ;
            if ( ! isset ( $this->registry[$idx] ) )
                $this->registry[$idx] = $value ;
        }
        
        function ___topicmaps_cache () {
            
            // echo "[topicmaps_cache] " ;
            // debug ( $this->registry ) ;
            
            $keys = $therest = array_keyify ( array_keys ( $this->registry ) ) ;
            $string = '' ;
            
            // pick out the first and third character of the name, and concatenate
            // a whole bunch of them to create a hash we can test against
            
            foreach ( $keys as $idx )
                $string .= 'c'.count($keys).'-'.$idx[0].$idx[2] ;
            
            $hash = md5 ( $string ) ;
            
            // create a cache
            $this->cache = new Cachette ( $hash, array ( 'time' => '1 week', 
                'cache_dir' => $this->glob->config['framework']['cache_directory'] ), $this->glob ) ;
            
            // what is the filename?
            $f = $this->cache->getFilename () ;
            
            // the temporary data array
            $data = array () ;
            
            if ( ! file_exists ( $f ) ) {
                
                // cache doesn't exist; go through the list, and compare it against the database
                // creating new or updating old as we go along, and put the data
                
                // 1. check topicmap for existing types
                $res = $this->glob->tm->query ( array ( 'name' => $keys ) ) ;
                
                // echo '[put]' ;
                if ( count ( $res ) > 0 ) {
                    
                    // yes, found some topics; update properties with this value
                    foreach ( $res as $topic_id => $topic ) {
                        $data[$topic['name']] = $topic_id ;
                        unset ( $therest[$topic['name']] ) ;
                    }
                    
                    // these were not found; create them, and update properties with its new value
                    foreach ( $therest as $idx ) {

                        $desc = null ;
                        if ( isset ( $this->registry[$idx] ) )
                            $desc = $this->registry[$idx] ;
                        
                        if ( $desc !== null ) {

                            $new_id = $this->glob->tm->create ( array (
                                'label' => $desc,
                                'name' => $idx,
                            ) ) ;

                            $data[$idx] = $new_id ;
                        
                        } else {
                            // echo "[fail:$idx] " ;
                        }
                    }
                }
                // echo "<pre>" ; print_r ( $res ) ; echo "</pre>" ;
            
                
                $this->cache->put ( $data ) ;
                
            } else {
                
                // echo '[get]' ;
                
                // file exist; cached data should be fine, so get the data
                
                $data = $this->cache->get () ;
            }
            
            if ( is_array ( $data ) )
                foreach ( $data as $idx => $value )
                    $this->_type->$idx = $value ;
            
        }
        
    }
