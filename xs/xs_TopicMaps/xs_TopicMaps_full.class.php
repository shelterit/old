<?php

    define ('FAST',          xs_TopicMaps::FAST ) ;

    define ('EXPAND',        xs_TopicMaps::EXPAND ) ;
    define ('TOPIC',         xs_TopicMaps::TOPIC ) ;
    define ('ASSOC',         xs_TopicMaps::ASSOC ) ;
    define ('MEMBER',        xs_TopicMaps::MEMBER ) ;
    define ('TOPIC_TYPE',    xs_TopicMaps::TOPIC_TYPE ) ;
    define ('ASSOC_TYPE',    xs_TopicMaps::ASSOC_TYPE ) ;
    define ('ROLE_TYPE',     xs_TopicMaps::ROLE_TYPE ) ;
    define ('NAME_TYPE',     xs_TopicMaps::NAME_TYPE ) ;
    define ('OCC_TYPE',      xs_TopicMaps::OCC_TYPE ) ;
    define ('ID',            xs_TopicMaps::ID ) ;
    
    class xs_TopicMaps {
    	
        // Our main map and index into the map
        public   $tm     = array () ;
        private  $tm_idx = array () ;
        private  $name   = 'topicmap' ;

        // Define some constants we'll be using a lot

        const NO_ID = null ;
        const ID = 'id' ;
        const FAST = 'fast' ;
        const EXPAND = 'expand' ;

        const TOPIC = 't' ;
        const ASSOC = 'a' ;
        const MEMBER = 'm' ;

        const TOPIC_TYPE = 'tt' ;
        const OCC_TYPE = 'ot' ;
        const NAME_TYPE = 'nt' ;
        const ASSOC_TYPE = 'at' ;
        const ROLE_TYPE = 'rt' ;

	function __construct () {
            
            // Prepare topics
            $this->tm[TOPIC] = array() ;

            // Prepare associations
            $this->tm_idx[TOPIC_TYPE] = array() ;
            $this->tm_idx[NAME_TYPE] = array() ;
            $this->tm_idx[OCC_TYPE] = array() ;

	}

        /******************************** CORE FUNCTIONS ********************/

        function topic ( $id = null, $name = null, $type = null, $occ = null, $ids = null ) {
            
            if ( $id == null )
                $id = $this->create_new_id () ;

            try {
                
                if ( ! isset ( $this->tm[TOPIC]["$id"] ) ) {
                    $this->tm[TOPIC]["$id"]['verifying_existence'] = 'verified' ;
                    $this->tm[TOPIC]["$id"][TOPIC_TYPE] = array() ;
                    $this->tm[TOPIC]["$id"][NAME_TYPE] = array() ;
                    $this->tm[TOPIC]["$id"][OCC_TYPE] = array() ;
                    $this->tm[TOPIC]["$id"][ID] = array(
                        'identifier' => array(), 'indicator' => array()
                    ) ;
                }
                $this->topic_sub ( NAME_TYPE, $id, $name ) ;
                $this->topic_sub ( TOPIC_TYPE, $id, $type ) ;
                $this->topic_sub ( OCC_TYPE,  $id, $occ ) ;
                $this->topic_sub ( ID,  $id, $ids ) ;
                
            } catch ( exception $ex ) {
                print_r ( $ex ) ;
            }


            if ( isset ( $this->tm[TOPIC]["$id"] ) )
                return true ;
            else
                return false ;
        }
        

        function type ( $id, $name = null, $type = null, $occ = null ) {
            $i = array ( 'tm:topic_type' ) ;
            if ( $type != null ) {
                if ( is_array ( $type ) ) {
                    foreach ( $type as $c => $v )
                        $i[$c] = $v ;
                } else {
                    $i[0] = $type ;
                }
            }
            $o = array () ;
            if ( $occ != null ) {
                if ( is_array ( $occ ) ) {
                    foreach ( $occ as $c => $v )
                        $o[$c] = $v ;
                } else {
                    $o[0] = $type ;
                }
            }
            $this->topic ( $id, $name, $i, $o ) ;
        }

        function topic_sub ( $idx, $id, $input = null ) {

            if ( $input != null ) {

                $items = array () ;

                if ( is_array ( $input ) ) {
                    foreach ( $input as $i => $v )
                        $items[$i] = $v ;
                } else
                  $items[] = $input ;

                foreach ( $items as $i => $v ) {
                    $this->tm[TOPIC]["$id"][$idx][$i] = $v ;
                    if     ( $idx == TOPIC_TYPE )  $this->tm_idx[TOPIC_TYPE][$v]["$id"] = $v ;
                    elseif ( $idx == NAME_TYPE )   $this->tm_idx[NAME_TYPE][$i]["$id"] = $v ;
                    elseif ( $idx == OCC_TYPE )    $this->tm_idx[OCC_TYPE][$i]["$id"] = $v ;
                    elseif ( $idx == ID )          $this->tm_idx[ID][$i]["$id"] = $v ;
                }
            }
        }

        function topic_delete ( $id ) {
            if ( isset ( $this->tm[TOPIC]["$id"] ) )
               unset ( $this->tm[TOPIC]["$id"] ) ;
        }

        function assoc_delete ( $id ) {
            if ( isset ( $this->tm[ASSOC]["$id"] ) )
               unset ( $this->tm[ASSOC]["$id"] ) ;
        }

        function assoc ( $id = null, $type = null, $members = null ) {

            if ( $type == null || trim ( $type ) == '' )
                return false ;

            if ( $id == null )
                $id = $this->create_new_id () ;

            $this->tm[ASSOC]["$id"][TOPIC_TYPE] = $type ;
            $this->tm[ASSOC]["$id"][MEMBER] = array() ;

            $this->tm_idx[ASSOC_TYPE][$type]["$id"] = true ;

            foreach ( $members as $idx => $pick ) {

                if ( is_array ( $pick ) ) {

                    $ref = 'null' ;
                    $role = 'null' ;

                    if ( isset ( $pick[0] ) )
                        $topic = $pick[0] ;
                    elseif ( isset ( $pick['ref'] ) ) {
                        $topic = $pick['ref'] ;
                    }
                    if ( isset ( $pick[1] ) )
                        $role = $pick[1] ;
                    elseif ( isset ( $pick['role'] ) ) {
                        $role = $pick['role'] ;
                    }

                   $this->tm[ASSOC]["$id"][MEMBER][$topic] = $role ;
                   $this->tm_idx[ROLE_TYPE][$role][$topic] = $idx ;
                   $this->tm_idx[ASSOC][$topic]["$id"] = $role ;
                   // $tm_idx['assoc_by_topic'][$id][$topic] = $role ;

                }
            }
        }

        function create_new_id () {
            return md5 ( uniqid() ) ;
        }

        function expand ( $topics, $method = FAST ) {

            // For every item in the array, find and inject (expand)
            // all related topics and associations into one larger
            // context

            if ( $method == FAST )
                return $topics ;

            $list = array () ;
            foreach ( $topics as $idx => $item )
                 $list[] = $idx ;
            return $this->get_topic_by_id_out ( $list ) ;
        }

        /******************************** QUERY / API ************************/

        function get_topics ( $method = FAST ) {
            $result = array() ;
            if ( isset ( $this->tm[TOPIC] ) )
                foreach ( $this->tm[TOPIC] as $idx => $topic )
                    $result[$idx] = $idx ;
            return $this->expand ( $result, $method ) ;
        }

        function get_this ( $idx, $method = FAST ) {
            $result = array() ;
            foreach ( $this->tm_idx["$idx"] as $idx => $topic )
                $result[$idx] = count ( $topic ) ;
            return $this->expand ( $result, $method ) ;
        }

        function get_topic_types ( $method = FAST ) {
            return $this->get_this ( TOPIC_TYPE, $method ) ;
        }
        function get_role_types ( $method = FAST ) {
            return $this->get_this ( ROLE_TYPE, $method ) ;
        }
        function get_name_types ( $method = FAST ) {
            return $this->get_this ( NAME_TYPE, $method ) ;
        }
        function get_occurrence_types ( $method = FAST ) {
            return $this->get_this ( OCC_TYPE, $method ) ;
        }
        function get_association_types ( $method = FAST ) {
            return $this->get_this ( ASSOC_TYPE, $method ) ;
        }

        function get_assocs ( $method = FAST ) {
            $result = array() ;
            if ( isset ( $this->tm[ASSOC] ) ) {
                foreach ( $this->tm[ASSOC] as $idx => $assoc ) {
                    $result[$idx] = $assoc ;
                }
            }
            return $result ;
        }

        // Get topics that use $type as type

        function get_topic_by_type ( $type, $method = FAST  ) {

            if ( !is_array ( $type ) )
                $type = array ( $type ) ;

            $result = array() ;
            foreach ( $type as $idx ) {
                if ( isset ( $this->tm_idx[TOPIC_TYPE][$idx] ) ) {
                    $t = $this->tm_idx[TOPIC_TYPE][$idx] ;
                    if ( $method == FAST ) {
                        foreach ( $t as $found => $val)
                            if ( isset ( $this->tm[TOPIC][$found] ) )
                                $result[$found] = count ( $t ) ;
                    } else {
                        foreach ( $t as $found => $val)
                            if ( isset ( $this->tm[TOPIC][$found] ) )
                                $result[$found] = $this->tm[TOPIC][$found] ;
                    }
                }
            }
// print_r ( $result ) ; die() ;
            return $this->expand ( $result, $method ) ;
        }

        // Get all occurrence of $type

        function get_occurrence_by_type ( $type, $method = FAST  ) {

            if ( !is_array ( $type ) )
                $type = array ( $type ) ;

            $result = array() ;

            foreach ( $type as $idx ) {
                if ( isset ( $this->tm_idx[OCC_TYPE][$idx] ) ) {
                    foreach ( $this->tm_idx[OCC_TYPE][$idx] as $found => $val)
                        if ( isset ( $this->tm[TOPIC][$found] ) )
                            $result[$found] = $this->tm[TOPIC][$found] ;
                }
            }
            return $this->expand ( $result, $method ) ;
        }


        function get_topic_by_id ( $id, $method = FAST  ) {

            if ( !is_array ( $id ) )
                $id = array ( $id ) ;

            $result = array() ;

            foreach ( $id as $idx ) {
                if ( isset ( $this->tm[TOPIC][$idx] ) ) {
                    $result[$idx] = $this->tm[TOPIC][$idx] ;
                }
            }
            return $this->expand ( $result, $method ) ;
        }

        function get_topic_by_id_out ( $id ) {

            if ( !is_array ( $id ) )
                $id = array ( $id ) ;

            $result = array() ;

            foreach ( $id as $idx ) {
                if ( isset ( $this->tm[TOPIC][$idx] ) ) {
                    $result[$idx] = $this->tm[TOPIC][$idx] ;
                }
            }
            return $result ;
        }

        function get_topic_by_role ( $type, $method = FAST  ) {
            $result = array() ;
            if ( isset ( $this->tm_idx[ROLE_TYPE][$type] ) ) {
                foreach ( $this->tm_idx[ROLE_TYPE][$type] as $idx => $found )
                    if ( isset ( $this->tm[TOPIC][$found] ) )
                        $result[$found] = $this->tm[TOPIC][$found] ;
                    // else
                        // $result[$found] = $this->tm[TOPIC]['404_referenced'] ;
            }
            return $this->expand ( $result, $method ) ;
        }


        function get_assoc_by_id ( $id, $method = FAST  ) {

            if ( !is_array ( $id ) )
                $id = array ( $id ) ;

            $result = array() ;

            foreach ( $id as $idx ) {
                if ( isset ( $this->tm[ASSOC][$idx] ) ) {
                    $result[$idx] = $this->tm[ASSOC][$idx] ;
                }
            }
            return $result ;
        }

        function get_assoc_by_type ( $type, $topic_id = null, $method = FAST  ) {

            if ( !is_array ( $type ) )
                $type = array ( $type ) ;

            $result = array() ;

            foreach ($type as $this_type ) {
                $result = array() ;
                if ( isset ( $this->tm_idx[ASSOC_TYPE][$this_type] ) ) {
                    foreach ( $this->tm_idx[ASSOC_TYPE][$this_type] as $found => $val ) {
                        if ( $topic_id == null )
                            $result[$found] = $this->tm[ASSOC][$found] ;
                        else {
                            if ( isset ( $this->tm[ASSOC][$found][MEMBER][$topic_id] ) )
                            $result[$found] = $this->tm[ASSOC][$found] ;
                        }
                    }
                }
            }
            return $result ;
        }

        function get_assoc_by_role_type ( $type, $method = FAST  ) {
            $result = array() ;
            if ( isset ( $this->tm_idx[ROLE_TYPE][$type] ) ) {
                foreach ( $this->tm_idx[ROLE_TYPE][$type] as $found => $val )
                    $result[$found] = $val ;
            }
            return $result ;
        }

        function get_assoc_by_topic ( $topic, $method = FAST  ) {
            $result = array() ;
            if ( isset ( $this->tm_idx[ASSOC][$topic] ) ) {
                foreach ( $this->tm_idx[ASSOC][$topic] as $idx => $top )
                    // $result[$topic][$idx] = $top ;
                    $result += $this->get_assoc_by_id ( $idx ) ;
            }
            return $result ;
        }

        function ontologize () {

            // This method creates a set of indexes out of the ontological
            // expressions in our map that are sorted to make life and
            // implementors happier, and is used primarly through the
            // queries and template languages supporting it

            // TODO : Make the darn thing

        }
       

        /******************************** IMPORT / EXPORT ********************/

        function load ( $name ) {
            $this->tm = unserialize ( file_get_contents ( $name.'.tm' ) ) ;
            $this->tm_idx = unserialize ( file_get_contents ( $name.'.tmx' ) ) ;
            $this->name = $name ;
        }

        function save ( $name = null ) {
            if ( $name == null )
                $name = $this->name ;
            file_put_contents ( $name.'.tm', serialize ( $this->tm ) ) ;
            file_put_contents ( $name.'.tmx', serialize ( $this->tm_idx) ) ;
        }

        function clean ( $string ) {
            return str_replace ( "#", "", $string ) ;
        }

        function import_xtm_string ( $string ) {

            try {

                $version = '1.0' ;
                $str = str_replace ( "xlink:", "", $string ) ;
                $xml = simplexml_load_string ( $str ) ;

                if ( (string) $xml['version'] == '2.0' )
                    $version = '2.0' ;
                elseif ( (string) $xml['version'] == '3.0' )
                    $version = '3.0' ;

                switch ( $version ) {

                    case '1.0' :

                        foreach ( $xml->topic as $item ) {

                            $types = array() ;
                            $sid = $sin = array() ;
                            $names = array() ;
                            $occs  = array() ;

                            $id = $item['id'] ;

                            foreach ( $item->instanceOf as $instanceOf )
                                foreach ( $instanceOf->topicRef as $ref )
                                    $types[] = $this->clean ( (string) $ref['href'] ) ;

                            foreach ( $item->subjectIdentity as $subjectIdentity ) {
                                foreach ( $subjectIdentity->resourceRef as $ref )
                                    $sid[] = $this->clean ( (string) $ref['href'] ) ;
                                foreach ( $subjectIdentity->subjectIndicatorRef as $ref )
                                    $sin[] = $this->clean ( (string) $ref['href'] ) ;
                            }

                            foreach ( $item->baseName as $baseName ) {
                                $scope = 0 ;
                                foreach ( $baseName->scope as $sc )
                                    $scope = $this->clean ( $sc->topicRef['href'] ) ;
                                foreach ( $baseName->baseNameString as $ref ) {
                                    $names[$scope] = $this->clean ( (string) $ref ) ;
                                }
                            }

                            foreach ( $item->occurrence as $occurrence ) {
                                $t = $this->clean ( $occurrence->instanceOf->topicRef['href'] ) ;
                                if ( isset ( $occurrence->resourceRef['href'] ) )
                                    $occs[$t] = (string) $occurrence->resourceRef['href'] ;
                                elseif ( isset ( $occurrence->resourceData ) )
                                    $occs[$t] = (string) $occurrence->resourceData ;
                            }

                            $this->topic ( $id, $names, $types, $occs, array ( 'identifier' => $sid, 'indicator' => $sin ) ) ;
                        }

                        foreach ( $xml->association as $item ) {

                            $members = array() ;

                            $type = $this->clean ( (string) $item->instanceOf->topicRef['href'] ) ;

                            foreach ( $item->member as $member ) {

                                $members[] = array (
                                    'role' => $this->clean ( (string) $member->roleSpec->topicRef['href'] ),
                                    'ref' => $this->clean ( (string) $member->topicRef['href'] )
                                ) ;

                            }

                            $this->assoc ( xs_TopicMaps::NO_ID, $type, $members ) ;
                        }


                        break ;

                    // TODO
                    case '2.0' :
                        break ;

                    // TODO
                    case '3.0' :
                        break ;

                    default: break ;

                }

                // Ontologize the new Topic Map (meaning, create an index of
                // ontological expressions

                $this->ontologize () ;

            } catch ( exception $ex ) {
                echo "\n\nOops! \n\n" ;
                print_r ( $ex ) ;
            }

        // echo "<pre>" ;
        }

        function import_xtm ( $filename ) {
            $this->import_xtm_string ( file_get_contents ( $filename ) ) ;
        }


        /******************************** HELPERS ****************************/

        function helper_tm2xml ( $tm_fragment = null ) {

            // if ( $tm_fragment == null ) $tm_fragment = $this->tm[TOPIC] + $this->tm[ASSOC] ;

            // print_r ( $tm_fragment ) ;

            $xml = '' ;
            if ( is_array ( $tm_fragment ) && count ( $tm_fragment ) > 0 ) {

                foreach ( $tm_fragment as $id => $item ) {

                    $item_type = xs_TopicMaps::TOPIC ;
                    $item_xml = '' ;

                    if ( isset ( $item[NAME_TYPE] ) && is_array ( $item[NAME_TYPE] ) )
                        foreach ( $item[NAME_TYPE] as $name_type => $name )
                            $item_xml .= " <name type='$name_type'>$name</name>\n" ;

                    if ( isset ( $item[OCC_TYPE] ) && is_array ( $item[OCC_TYPE] ) )
                        foreach ( $item[OCC_TYPE] as $name_type => $name )
                            $item_xml .= " <occurrence type='$name_type'>$name</occurrence>\n" ;

                    if ( isset ( $item[TOPIC_TYPE] ) && is_array ( $item[TOPIC_TYPE] ) ) {
                        foreach ( $item[TOPIC_TYPE] as $idx => $type )
                            $item_xml .= " <type>$type</type>\n" ;
                    } else {
                        if ( isset ( $item[TOPIC_TYPE] ) )
                            $item_xml .= " <type>".$item[TOPIC_TYPE]."</type>\n" ;
                    }

                    if ( isset ( $item[MEMBER] ) && is_array ( $item[MEMBER] ) ) {
                        $item_type = xs_TopicMaps::ASSOC ;

                        if ( isset ( $item[TOPIC_TYPE] ) ) {
                            $t = $item[TOPIC_TYPE] ;
                            if ( isset ( $this->tm[TOPIC][$t] ) && is_array ( $this->tm[TOPIC][$t] ) ) {
                                foreach ( $this->tm[TOPIC][$t] as $name_type => $name )
                                    $item_xml .= " <name type='$name_type'>$name</name>\n" ;
                            }
                        }

                        foreach ( $item[MEMBER] as $ref => $role ) {
                            /*
                            $refname = $rolename = 'not found' ;
                            if ( isset ( $this->tm[TOPIC][$ref] ) )
                                $refname = $this->tm[TOPIC][$ref][NAME_TYPE][''] ;
                            if ( isset ( $this->tm[$role] ) )
                                $rolename = $tm[$role][NAME_TYPE][''] ;
                             *
                             */
                            $refname = htmlentities ( $this->lookup_name ( $ref ), ENT_QUOTES ) ;
                            $rolename = $this->lookup_name ( $role ) ;
                            $item_xml .= "   <member ref='$ref' refname='$refname' role='$role' rolename='$rolename' />\n" ;
                        }
                    }

                    if ( $item_type == xs_TopicMaps::TOPIC )
                        $xml .= "<topic id='$id'>\n".$item_xml."</topic>\n" ;
                    else
                        $xml .= "<assoc id='$id'>\n".$item_xml."</assoc>\n" ;
                }
            }
            return $xml ;
        }

        function lookup_name ( $ref ) {
            $name = 'Not found' ;
            if ( isset ( $this->tm[TOPIC][$ref] ) ) {
                $name = $this->tm[TOPIC][$ref][NAME_TYPE] ;
                if ( is_array ( $name ) ) {
                    foreach ( $name as $n ) {
                        $name = $n ;
                        break ;
                    }
                }
            }
            return $name ;
        }

        function helper_tm2js_topics ( $tm_fragment ) {

            $js = '' ;

            if ( is_array ( $tm_fragment ) && count ( $tm_fragment ) > 0 ) {

                foreach ( $tm_fragment as $id => $topic ) {
                    $js .= "addId('$id');" ;

                    if ( isset ( $topic['name'] ) )
                        foreach ( $topic['name'] as $name_type => $name )
                            $js .= "addName('$name_type','$name');" ;

                    if ( isset ( $topic['occ'] ) )
                        foreach ( $topic['occ'] as $name_type => $name )
                            $js .= "addOcc('$name_type','$name');" ;

                    if ( isset ( $topic[TOPIC_TYPE] ) )
                        foreach ( $topic[TOPIC_TYPE] as $idx => $type )
                            $js .= "addType('$type');" ;
                }
            }
            return $js ;
        }

        function helper_tm2js_assocs ( $tm_fragment ) {

            $js = '' ;

            if ( is_array ( $tm_fragment ) && count ( $tm_fragment ) > 0 ) {

                foreach ( $tm_fragment as $id => $assoc ) {
                    $js .= "addId('$id');" ;

                    if ( isset ( $assoc['member'] ) )
                        foreach ( $assoc['member'] as $ref => $role )
                            $js .= "addMem('$ref','$role');" ;

                    if ( isset ( $assoc[TOPIC_TYPE] ) )
                        $js .= "addType('".$assoc[TOPIC_TYPE]."');" ;

                }
            }
            return $js ;
        }

        /******************************** QUERY PATM *************************/

        function fetch ( $query ) {

            $res = array() ;
            $ex = explode ( ' ', $query ) ;
            $cmd = array() ;

            $num = 0 ;
            $c = -1 ;

            foreach ( $ex as $idx => $t ) {

                $t = trim ( $t ) ;

                if ( ! isset ( $cmd[$num] ) )
                    $cmd[$num] = array() ;

                switch ( $t ) {

                    case '<' :
                    case '>' :
                    case '=' :
                    case '!=' :

                    case 'get' :
                    case 'put' :
                    case 'post' :
                    case 'delete' :
                        $res[++$c] = $t ;
                        break ;

                    case 'with' :
                    case 'where' :
                        $res[++$c] = 'filter' ;
                        $num++ ;
                        break ;

                    case 'sort' : $res[++$c] = 'sort_ascending' ; $num++ ; break ;
                    case 'ascending' : $res[++$c] = 'sort_ascending' ; break ;
                    case 'descending' : $res[++$c] = 'sort_descending' ; break ;

                    case 'topic' : case 'topics' : $res[++$c] = 'topics' ; $num++ ; break ;
                    case 'identities' : case 'identity' : case 'id' : $res[++$c] = 'identities' ; break ;
                    case 'associations' : case 'association' : case ASSOC : $res[++$c] = 'associations' ; break ;
                    case 'occurrences' : case 'occurrence' : case 'occ' : $res[++$c] = 'occurrences' ; break ;


                    default:
                        $first = substr ( $t, 0, 1 ) ;
                        $rest = substr ( $t, 1 ) ;

                        if ( is_numeric ( trim ( $t ) ) ) {
                            $res[++$c] = 'int' ;
                            $res[++$c] = $t ;
                        } else

                        if ( $first == '#' ) {
                            $res[++$c] = TOPIC_TYPE ;
                            $res[++$c] = $rest ;
                        } else

                        if ( $first == '!' ) {
                            $res[++$c] = 'occurrence_type' ;
                            $res[++$c] = $rest ;
                        }
                        break ;
                }

            }

            $sql = '' ;


            $i=0;
            $prev = '' ;
            $current_axis = null ;

            while($i < count($res)) {

                switch ( $res[$i] ) {
                    case "get"    : $sql .= 'SELECT ' ; break ;
                    case "topics" : $sql .= '* FROM xs_topics ' ; break ;
                    case "type"   : $sql .= 'WHERE ID IN ( SELECT ref_topic_id FROM xs_topic_type WHERE someCondition ) ' ; break ;
                }

                $prev = $res[$i] ;
                $i++;
            }

            return array ( 'tokenize' => $res, 'sql' => $sql ) ;
        }

        function query ( $query ) {

            $t = explode ( ' ', $this->query_tokenize ( $query ) ) ;


        }

        function query_tokenize ( $s )  {

            $rg = array();

            // remove whitespace
            $s = preg_replace("/\s+/", '', $s);

            // split at numbers, identifiers, function names and operators
            // $rg = preg_split('/([*\/^+\(\)-])|(#\d+)|([\d.]+)|(\w+)/', $s, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
            $rg = preg_split('/\[[*\/^+\(\)-]\]|(#\d+)|([\d.]+)|(\w+)/', $s, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

            // find right-associative '-' and put it as a sign onto the following number
            for ($ix = 0, $ixMax = count($rg); $ix < $ixMax; $ix++) {
                if ('-' == $rg[$ix]) {
                    if (isset($rg[$ix - 1]) && self::fIsOperand($rg[$ix - 1])) {
                        continue;
                    } else if (isset($rg[$ix + 1]) && self::fIsOperand($rg[$ix + 1])) {
                        $rg[$ix + 1] = $rg[$ix].$rg[$ix + 1];
                        unset($rg[$ix]);
                    } else {
                        throw new Exception("Syntax error: Found right-associative '-' without operand");
                    }
                }
            }
            $rg = array_values($rg);

            // echo join(" ", $rg)."\n";

            return $rg;
        }



        function query_context ( $context, $span = 1 ) {



        }



    }
