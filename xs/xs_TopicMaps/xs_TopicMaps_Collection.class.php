<?php

class xs_TopicMaps_Collection extends xs_EventStack_Plugin {

    public $topics = array () ;

    function __construct ( $incoming = false ) {
        parent::__construct();
        if ( $incoming )
            $this->inject ( $incoming ) ;
    }

    function inject ( $arr = array () ) {

        if ( is_array ( $arr ) )

            foreach ( $arr as $key => $value )

                if ( !isset ( $this->topics[$key] ) )
                    $this->topics[$key] = new xs_TopicMaps_Topic ( $value ) ;
                else
                    $this->topics[] = new xs_TopicMaps_Topic ( $value ) ;
    }

    function resolve_topics ( $inp = array () ) {

        if ( ! is_array ( $inp ) )
           return false ;

        $found = array () ;

        // go through the input
        foreach ( $inp as $find => $config ) {

            // find all properties in our topics
            foreach ( $this->topics as $topic_idx => $topic )
               if ( $topic->is_set ( $find ) )
                   $found[$topic->get ( $find )] = $topic->get ( $find ) ;

            // Found a few wotsits?
            if ( count ( $found > 0 ) ) {

                // Find those things
                $lookup = $this->glob->tm->query ( array (
                    'id' => $found
                ) ) ;

        // var_dump ( $found ) ;
        // var_dump ( $lookup ) ;


                // if  found, merge them into the result
                foreach ( $lookup as $lut_idx => $lut ) {

                    $id = $lut['id'] ;

                    foreach ( $this->topics as $topic_idx => $topic ) {

                        if ( $topic->get ( $find ) == $id ) {

                            foreach ( $config as $field => $setup ) {

                                if ( $topic->is_set ( $find ) ) {

                                    $new_field = "{$find}_{$field}" ;
                                    $data = '(not found)' ;
                                    if ( $setup != false ) {
                                        $t = explode ( '=', $setup ) ;
                                        $in = $lut[$field] ;
                                        $func = '$out = ' . $t[1].';' ;
                                        $new_field = "{$find}_{$t[0]}" ;
                                        eval ( $func ) ;
                                        // echo "[$func] " ;
                                        $data = $out ;
                                    } else {
                                        $data = $lut[$field] ;
                                    }
                                    $topic->set ( $new_field, $data ) ;

                                }

                            }

                        }
                    }





                    /*

                    if ( isset ( $whats[$val] ) ) {
                        if ( is_array ( $pick ) ) {
                            $v = $whats[$val][$pick[0]] ;
                            $s = substr ( $v, $pick[1] ) ;
                           $this->topics[$topic_idx]->set (
                               $what, $s
                           ) ;
                        } else {
                           $this->topics[$topic_idx]->set (
                               $what, $whats[$val][$pick]
                           ) ;
                        }
                                       // echo "[".print_r ($whats[$val], true)."] " ;

                    } */
                    
                }



            }

        }
    }

    function resolve ( $what, $on, $pick ) {

        // for all of the found comments, pick out the author identifiers
        $find = array () ;
        foreach ( $this->topics as $which => $item )
           if ( $item->is_set ( $on ) )
               $find[] =  $item->get ( $on ) ;

        // var_dump ( $find ) ;

        // Found a few wotsits?
        if ( count ( $find > 0 ) ) {

            // Find those things
            $whats = $this->glob->tm->query ( array ( 
                'id' => $find
            ), false ) ;

            // var_dump ( $whats ) ;
            // var_dump ( $this->topics ) ;
            
            // if authors found, merge them into the result
            foreach ( $this->topics as $which => $topic ) {
                $val = $topic->get ( $on ) ;
                // echo "[$val]:" ;
                if ( isset ( $whats[$val] ) ) {
                    if ( is_array ( $pick ) ) {
                        $v = $whats[$val][$pick[0]] ;
                        $s = substr ( $v, $pick[1] ) ;
                       $this->topics[$which]->set (
                           $what, $s
                       ) ;
                    } else {
                       $this->topics[$which]->set (
                           $what, $whats[$val][$pick]
                       ) ;
                    }
                                   // echo "[".print_r ($whats[$val], true)."] " ;

                }
            }
            // var_dump ( $this->topics ) ;
        }

    }

    function get_as_array ( ) {
        $ret = array () ;
        foreach ( $this->topics as $idx => $topic )
           $ret[$idx] = $topic->get_as_array () ;
        return $ret ;
    }

}

