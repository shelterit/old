<?php

    // The security module handles all things identification, authentication 
    // and access rules, with heaps of the base code borrowed from
    // https://github.com/GeoffYoung/PHP-ACL/blob/master/class.acl.php

    class xs_module_security extends xs_EventStack_Plugin {

        private $token_structure = 'security-website-pages' ;
        private $topics = null ;
        private $structure = null ;
        
        private $names = null ;
        private $result = null ;
        private $rules = null ;
        private $config = null ;
        
        private $uri = null ;
        
        // register that we want to include an associated JavaScript file
        public $_include_js = true ;

        function ___modules () {
            
            // we support a RESTful API at this URI
            $this->_register_resource ( XS_MODULE, '_api/module/security/access_table', $this ) ;

            // Define the structure query
            $this->structure = $this->glob->data->register_query (

                // identifier for what data connection to use (xs: default xSiteable)
                'xs',

                // identifier for our query
                $this->token_structure,

                // the query in question
                array (
                    'select'      => 'id,type1,parent,label',
                    'type'        => $this->_type->page,
                    'return'      => 'topics',
                ),

                // the timespan of caching the result
                '+1 second'
            ) ;
            
        }
        
        function resolve_uri_structure ( $path = null ) {
            
            // debug ( $path, '_resolve 1' ) ;
            
            // if ( $this->topics != null )
            //     return $this->names ;
            
            $res = array () ;
            
            if ( $path == null ) {
                $this->uri = $this->glob->request->_uri ;
            } else {
                $this->uri = $path ;
            }
            // if ( $this->uri == '' ) $this->uri = XS_ROOT_ID ;

            $path = explode ( '/', $this->uri ) ;
            
            $l = count ( $path ) ;

            // debug ( $path, $l ) ;
            
            if ( $l > 0 ) {
                
                $res[XS_ROOT_ID] = $this->glob->data->create_id ( 
                        XS_PAGE_DB_IDENTIFIER, array ( 'uri' => XS_ROOT_ID ) 
                    ) ;
                
                foreach ( $path as $idx => $item ) {
                    $e = '' ;
                    for ( $m=0; $m < $idx + 1; $m++)
                        $e .= $path[$m] . '/' ;
                    $id = substr ( $e, 0, -1 ) ;
                    if ( $id == '' ) $id = XS_ROOT_ID ;

                    $res[$id] = $this->glob->data->create_id ( 
                        XS_PAGE_DB_IDENTIFIER, array ( 'uri' => $id ) 
                    ) ;
                }

                $topics = $this->glob->tm->query ( array ( 
                    'select' => 'id,label,name,scheme', 
                    'name' => $res, 
                    'return' => 'topics' 
                ) ) ;

                $this->topics = $topics ;
                $this->names = $res ;
            }
            // debug ( $this->names, 'security___' ) ;
            return $res ;
        }
        
        function get_topics () {
            return $this->topics ;
        }
        
        function get_topic_names () {
            if ( is_array ( $this->names ) && count ( $this->names ) > 0 )
                return $this->names ;
            return array () ;
        }
        
        function parse_config () {
            $ret = array () ;
            // return $ret ;
            if ( isset ( $this->glob->config['access'] ) ) {
                foreach ( $this->glob->config['access'] as $page => $item ) {
                    foreach ( explode ( ',', $item ) as $ex ) {
                        // $what = 'deny' ;
                        // if ( substr ( $ex, 1, 4) == 'deny' ) 
                        // echo "<pre>" ; print_r ( $ex ) ; echo '</pre>' ;
                    }
                }
            }
            
            $retz = array (
                'tools' => array ( 
                    'page' => array ( 'usertype' => array ( '@anonymous' => 'd' ), 'role' => array ( 'Function - Intranet Editor' => 'd' ) ) ,
                ),
                'tools/bob' => array ( 
                    'page' => array ( 'usertype' => array ( '@anonymous' => 'd' ), 'role' => array ( 'Function - Intranet Editor' => 'd' ) ) ,
                    'page:delete' => array ( 'usertype' => array ( '@all' => 'd' ) ) ,
                ),
                'tools/alex' => array ( 
                    'page' => array ( 'usertype' => array ( '@anonymous' => 'd' ), 'role' => array ( 'Function - Intranet Editor' => 'd' ) ) ,
                    'page:delete' => array ( 'usertype' => array ( '@all' => 'd' ) ) ,
                ),
                'tools/alex/a' => array ( 
                    'page' => array ( 'role' => array ( 'Function - Intranet Editor' => 'a' ) ),
                    'page:edit' => array ( 'role' => array ( 'Function - Intranet Editor' => 'd' ) ),
                    'page:delete' => array ( 'role' => array ( 'admin' => 'd' ) ),
                )
            ) ;
            
            return $ret ;
        }
        
        function parse_access_rules ( $uri = null ) {

            if ( $uri == null )
                $uri = $this->glob->request->_uri ;
            
            $result = $this->result ;
            $rules = $this->rules ;
            
            if ( $this->config == null )
                $this->config = $this->parse_config () ;
            

            // if ($rules == null) {
                $rules = $this->config ;
                foreach ($this->topics as $topic) {
                    if (isset($topic['scheme'])) {
                        $l = @unserialize($topic['scheme']);
                        // debug ( $l ) ;
                        if (is_array($l) && count($l) > 0) {
                            $c = current ( $l ) ;
                            if ( isset ( $c['source'] ) )
                                $uri = $c['source'] ;
                            foreach ( $l as $idx => $rule ) {
                                $func = $rule['func'] ;
                                $type = $rule['type'] ;
                                $what = $rule['what'] ;
                                $rules[$uri][$func][$type][$what] = $rule['rule'] ;
                            }
                        }
                    }
                }
                // debug ( $rules ) ;
                // natsort2d ( $rules ) ;
                // debug_r ( $rules ) ;
                ksort ( $rules ) ;
                // debug_r ( $rules ) ;
                $this->rules = $rules ;
            // }
            
            // debug ( $this->rules ) ;

            // if ($result == null) {

                $result = array();

                // debug_r ( $rules ) ;
                foreach ( $this->rules as $pag => $functionalities ) {
                    
                    $page = $pag ;
                    if ( $page == '' ) $page = XS_ROOT_ID ;
                    
                    $pos = strpos ( $this->uri, $page ) ;
                    if ( $pos === false && $page != XS_ROOT_ID ) continue ;
                    
                    // if ( $pos !== false ) echo "[$page]=[$pos] " ;
                    
                    // echo "[$page][{$this->uri}]=" ; echo "[".strpos ( $this->uri, $page )."] " ;
                    
                    foreach ($functionalities as $functionality => $types ) {

                        foreach ($types as $type => $rule) {

                            foreach ($rule as $what => $ruling) {

                                $fetch = 0 ;

                                switch ($type) {
                                    case 'usertype' : $fetch = $this->glob->user->isUsertype ( $what ) ; break ;
                                    case 'group'    : $fetch = $this->glob->user->isGroup ( $what ) ; break ;
                                    case 'role'     : $fetch = $this->glob->user->isRole ( $what ) ; break ;
                                    case 'username' : $fetch = $this->glob->user->isUsername ( $what ) ; break ;
                                    default : break ;
                                }
                                // debug($fetch);
                                $allowed = 0 ;

                                if ($ruling == 'a' || $ruling == 'd') {

                                    if ( $fetch ) {
                                        // yes, the criteria fits
                                        if ( $ruling == 'a' ) $allowed = 1 ; 
                                        elseif ( $ruling == 'd' )  $allowed = 2 ;
                                    }

                                    $w = 'ignored' ;
                                    if ( $allowed == 2 ) $w = 'denied' ;
                                    if ( $allowed == 1 ) $w = 'allowed' ;

                                    $result[$page][] = array ( 
                                        'source' => $page, 
                                        'func' => $functionality, 
                                        'type' => $type, 
                                        'what' => $what, 
                                        'fetch' => $fetch, 
                                        'rule' => $ruling,
                                        'ruling' => $w,
                                        ) ;
                                    continue;
                                }

                                if ($ruling == 'aae') {
                                    if ($fetch == false) $allowed = 1; else $allowed = 2;
                                } elseif ($ruling == 'dae') {
                                    if ($fetch == true) $allowed = 1; else $allowed = 2;
                                }

                                $w = 'allowed' ;
                                if ( $allowed == 2 ) $w = 'denied' ;

                                $result[$page][] = array ( 
                                    'source' => $page, 
                                    'func' => $functionality, 
                                    'type' => $type, 
                                    'what' => $what, 
                                    'rule' => $ruling,
                                    'ruling' => $w,
                                ) ;
                            }
                        }
                    }
                }
                $this->result = $result ;
                // debug($result) ;
                $this->glob->stack->add ( 'xs_access', $result ) ;
                
            // }
        }
        
        function has_access ( $func = 'page' ) {
            
            $allowed = false ;
            
            if ( isset ( $this->glob->config['framework']['security_model'] )
                 && $this->glob->config['framework']['security_model'] == 'open' )
                $allowed = true ;
            // var_dump ( $allowed ) ;
            
            // debug ( $this->result ) ;
            
            foreach ( $this->result as $page => $rules ) {
                foreach ( $rules as $rule ) {
                    if ( $rule['func'] == $func && isset ( $rule['ruling'] ) ) {
                        if ( $rule['ruling'] == 'denied' ) {
                            $allowed = false ;
                        }
                        if ( $rule['ruling'] == 'allowed' ) {
                            $allowed = true ;
                        }
                    }
                }
            }
            // debug ( $this->result ) ;
            // var_dump ( $allowed ) ;
            return $allowed ;
        }
        
        function draw_radiolist ( $id, $list, $enabled = true, $select = null ) {
            foreach ( $list as $idx => $value ) {
                echo "<div><input type='radio' name='f:{$id}' value='{$idx}'" ;
                if ( $idx == $select )
                    echo " selected='selected'" ;
                echo ">{$value}</div>" ;
            }
        }
        
        function draw_list ( $id, $list, $enabled = true, $select = null ) {
            echo "<select id='{$id}' name='f:{$id}'" ;
            if ( ! $enabled )
                echo " disabled='disabled'" ;
            echo ">" ;
            foreach ( $list as $idx => $value ) {
                echo "<option value='{$idx}'" ;
                if ( $idx == $select )
                    echo " selected='selected'" ;
                echo ">{$value}</option>" ;
            }
            echo "</select>" ;
        }
        
        function draw_field ( $id, $value = '', $enabled = true ) {
            echo "<input type='text' style='width:inherit;' id='{$id}' name='f:{$id}' value='{$value}'" ;
            if ( ! $enabled )
                echo " disabled='disabled'" ;
            echo ">" ;
        }
        
        function _http_action ( $in = null ) {
            
            $method = $this->glob->request->get_method () ;
            $this->$method () ;
            
        }
        
        function POST () {
            
            $rules = array () ;
            $uri = $this->glob->request->uri ;
            if ( $uri == '' ) $uri = XS_ROOT_ID ;
            
            $fields = $this->glob->request->__get_fields () ;
            
            // Is the current URI allowed for the current default user?
            $res = $this->resolve_uri_structure ( $uri ) ;
            
            // parse security
            $this->parse_access_rules () ;
            
            foreach ( $fields as $key => $field ) {
                $t = explode ( '__', $key ) ;
                if ( count ( $t ) > 1 ) {
                    $rules[$t[0]][$t[1]] = $field ;
                }
            }
            
            $old = array () ;
            if ( isset ( $this->result[$uri] ) )
                $old = $this->result[$uri] ;

            // debug_r ( $old ) ;
            
            $new = array () ;
            foreach ( $rules as $idx => $rule ) {
                $new[$idx]['source'] = $uri ;
                $new[$idx]['func'] = $rule['source'] ;
                $new[$idx]['type'] = $rule['type'] ;
                $new[$idx]['what'] = $rule['what'] ;
                $new[$idx]['rule'] = $rule['rule'] ;
            }
            
            // identifier
            $id = $this->glob->data->create_id ( 
                XS_PAGE_DB_IDENTIFIER, array ( 'uri' => $uri ) 
            ) ;
            
            // debug ( $id ) ;

            $topics = $this->glob->tm->query ( array ( 
                'name' => $id, 
            ) ) ;
            $topic = end ( $topics ) ;
            
            $topic['scheme'] = serialize ( $new ) ;
            $this->glob->tm->update ( $topic ) ;
            
            // $topics = $this->glob->tm->query ( array ( 'name' => $id ) ) ;
            // $topic = end ( $topics ) ;
            
            $this->glob->request->_redirect = $uri ;
            // debug_r ( $topic ) ;

        }
        
        function GET () {
            
            $uri = $this->glob->request->uri ;
            // debug($uri, '_GET 1');
            if ( $uri == '' ) $uri = XS_ROOT_ID ;
            $f = $this->glob->request->func ;

            $z = str_replace ( array ( chr(10), ' ', chr(13) ), ' ', 
                   strip_tags ( $this->glob->request->func ) ) ;
            $func = explode ( ' ', $z ) ;
            
            // Is the current URI allowed for the current default user?
            $res = $this->resolve_uri_structure ( $uri ) ;

            // debug($res) ;
            // parse security
            $this->parse_access_rules () ;
            
            $list_what = array ( 
                'username' => 'Username',
                'usertype' => 'User is of Type',
                'group' => 'User belongs to Group',
                'role' => 'User has Role'
            ) ;
            
            $list_rules = array ( 'a' => 'Allow', 'd' => 'Deny' ) ;

            $list_source = array () ;
            foreach ( $func as $source ) {
                $list_source[$source] = $source ;
                if ( $source == 'page' )
                    $list_source[$source] = 'View page' ;
            }
            // debug($list_source);
            $counter = 0 ;
            
            $_uri = $this->glob->request->_uri ;
            if ( $_uri == '' ) $_uri = XS_ROOT_ID ;

            ?><form action="<?php echo $this->glob->dir->home . '/' . $_uri ; ?>" method="post">
                 <input type="hidden" name="uri" value="<?php echo $uri ; ?>" />
                 <table id="xs-access-rules" width="100%">
                    <thead><tr style="background-color:#999;">
                        <td style="background-color:#fca;font-weight:bold;width:30px;text-align:center;">rule</td>
                        <td style="background-color:#fca;font-weight:bold;">source</td>
                        <td style="background-color:#fca;font-weight:bold;">  </td>
                        <td style="background-color:#fca;font-weight:bold;">type</td>
                        <td style="background-color:#fca;font-weight:bold;">what</td>
                        <td style="background-color:#fca;font-weight:bold;">rule</td>
                        <td style="background-color:#fca;font-weight:bold;">ruling</td>
                        <td style="background-color:#fca;font-weight:bold;">action</td>
                    </tr></thead>
                    <tbody>
            <?php 
            // debug_r ( $this->result ) ;
            
             foreach ( $this->result as $source => $content ) {
                 
                 $enabled = true ;
                 if ( trim ( $source ) !== trim ( $uri ) )
                     $enabled = false ;
                 
                 $style = '' ;
                 if ( ! $enabled ) $style = 'background-color:#ddd;color:#555;' ;
                 
                foreach ( $content as $rule ) {
                    
                    $counter++ ;
                    $rnd = chr ( rand ( 65, 86 ) ) . rand ( 100, 999 ) ;
                    $func = 'page' ;
                    if ( isset ( $rule['id'] ) && trim ( $rule['id'] ) != '' )
                        $rnd = $rule['id'] ;
                    if ( isset ( $rule['func'] ) && trim ( $rule['func'] ) != '' )
                        $func = $rule['func'] ;
                    
            ?><tr id="<?php echo $rnd ; ?>__row" class="<?php if ( ! $enabled ) echo "state-disabled" ; ?>">
                  <td style="<?php echo $style; ?>width:30px;text-align:center;"><?php echo $counter; ?></td>
                  <td style="<?php echo $style; ?>"><?php 
                        
                    if ( ! $enabled ) 
                        echo $rule['source'] . ' &gt; '.$func.' <i style="font-size:0.7em;color:#333;">(inherited)</i>' ;
                    else {
                        $func = '' ;
                        if ( isset ( $rule['func'] ) )
                            $func = $rule['func'] ;
                        echo 'For ' ;
                        $this->draw_list ( $rnd.'__source', $list_source, $enabled, $func );
                    }
                        ?></td>
                        <td style="<?php echo $style; ?>">If</td>
                        <td style="<?php echo $style; ?>"><?php $this->draw_list ( $rnd.'__type', $list_what, $enabled, $rule['type'] ); ?></td>
                        <td style="<?php echo $style; ?>"><?php $this->draw_field ( $rnd.'__what', $rule['what'], $enabled ); ?></td>
                        <td style="<?php echo $style; ?>"> then <?php $this->draw_list ( $rnd.'__rule', $list_rules, $enabled, $rule['rule'] ); ?></td>
                        <td style="<?php echo $style; ?>"><?php
                           if ( $rule['ruling'] == 'denied' ) 
                               echo "<b style='background-color:#f88;'>{$rule['ruling']}</b>" ;
                           elseif  ( $rule['ruling'] == 'allowed' ) 
                               echo "<b style='background-color:#8f8;'>{$rule['ruling']}</b>" ;
                           elseif ( $rule['ruling'] == 'ignored' ) 
                               echo "<b style='background-color:#ddd;'>Didn't match</b>" ;
                        ?>
                        </td>
                        <td style="<?php echo $style; ?>">
                            <?php if ( $enabled ) { ?>
                                <button onclick="$('#<?php echo $rnd ; ?>__row').remove();">Delete</button>
                            <?php } ?>
                        </td>
                    </tr>
            <?php 
                }
             } ?>
                    <tr class="state-disabled last">
                        <td colspan="8" style="text-align:right;"><div style='text-align:right;'>
                            <button type="button" onclick='add_new_access_rule();return false;'>Add new rule</button>
                            <input type='submit' value='Save!' />
                        </div></td>
                    </tr>
            <?php 
            
            echo "</tbody></table></form>" ;

        }
    }
