<?php

    // A service that drives all manners of search and retreival

    class xs_module_generic_content extends xs_EventStack_Module {

        public $meta = array (
            'name' => 'Generic Content module',
            'description' => 'A generic content controller',
            'version' => '1.0',
            'author' => 'Alexander Johannesen',
            'author_link' => 'http://shelter.nu/',
            'editable_options' => true,
        ) ;

        private $debug = false ;
        
        function ___modules () {

            // Define the main events we control and can trigger
            $this->_register_event ( XS_MODULE, 'on_content_create' ) ;
            $this->_register_event ( XS_MODULE, 'on_content_read' ) ;
            $this->_register_event ( XS_MODULE, 'on_content_update' ) ;
            $this->_register_event ( XS_MODULE, 'on_content_delete' ) ;
            
            // Gain control over a specific resource
            $this->_register_resource ( XS_MODULE, '_api/resources/content' ) ;

        }
        
        function _http_action () {
            
            // Overwrite this one for all your action, otherwise it will try
            // to use the method functions instead

            $this->glob->log->add ( 'xs_Action : ACTION : Start' ) ;

            // echo "(".$this->_meta->class.") " ;

            // Get the HTTP method
            $m = $this->glob->request->method() ;

            // Create an indexed array of all this class' methods
            $mm = array_keyify ( $this->_meta->methods ) ;

            // If any of the match the HTTP method, just call it, so for example
            // if the HTTP method is 'DELETE', and it exists, we call $this->DELETE()

            if ( isset ( $mm[$m] ) )
                $this->$m() ;
            
            $this->glob->log->add ( 'xs_Action : ACTION : End' ) ;
        }
        
        function GET () {
            echo "GET!" ;
        }

        function callback___on_indexer_create ( $arr = array () ) {

        }
        
        // before we output, make sure no one is requesting a redirect
        
        function ___output_pre () {
            
            $redirect = urldecode ( $this->glob->request->__fetch ( '_redirect', '' ) ) ;
            
            if ( $redirect != '' ) {

                if ( $redirect == XS_ROOT_ID ) $redirect = '' ;
                
                $domain = $_SERVER['SERVER_NAME'] . $this->glob->dir->home ;

                $l = $redirect ;

                if ( sizeof ( $l ) > 0 && $l[0] !== '/' && $l[0] !== '\\' ) {
                    $domain .= '/' ;
                }

                if ( ! strstr ( $domain, 'http:' ) and ! strstr ( $domain, 'https:' ))
                    $domain = 'http://' . $domain ;

                if ( strstr ( $redirect, 'http:' ) or strstr ( $redirect, 'https:' ))
                    $domain = '' ;

                $redir = "Location: {$domain}{$redirect}" ;
                
                if ( $this->debug ) { print_r ( $redir ) ; die () ; }
                // die() ;
                header ( $redir ) ;
            }
        }


        function POST ( $input = array () ) {

            /*
             * 1. get identifier
             * 2. if identifier is given, query for it
             * 3. create TM_Topic
             * 4. stuff it with type xs::_page type
             * 5. stuff it with other POST data
             * 
             */ 
            
            // $item = new xs_TopicMaps_Topic () ;

            $redirect = $this->glob->request->__get ( '_redirect', '' ) ;

            // Create a generic identifier for this resource
            $resource_id = $this->glob->data->create_id ( 
                XS_PAGE_DB_IDENTIFIER, 
                array ( 'uri' => $redirect ) 
            ) ;

            // echo " 0:" ; print_r ( $resource_id ) ;
            
            // Define the structure query
            $this->glob->data->register_query (

                // identifier for what data connection to use (xs: default xSiteable)
                'xs',

                // identifier for our query
                $resource_id,

                // the query in question
                array ( 'name' => $resource_id ),

                // the timespan of caching the result
                '+5 seconds'
            ) ;
            
            // get generic data for this URI (resource)
            $page_db_lookup = $this->glob->data->get ( $resource_id ) ;
            
            if ( $this->debug ) { echo "<div style='margin:10px;padding:10px;border:dotted 1px #999;'>[fetch_all, from ".debugPrintCallingFunction()."]" ; print_r ( $page_db_lookup ) ; echo "</div>" ; }

            $fields = $this->glob->request->__get_fields () ;
            if ( $this->debug ) { echo "<div style='margin:10px;padding:10px;border:dotted 1px #999;'>[fetch_all, from ".debugPrintCallingFunction()."]" ; print_r ( $fields ) ; echo "</div>" ; }
            
            // got something?
            if ( count ( $page_db_lookup ) > 0 ) {
                reset ( $page_db_lookup ) ;
                // echo " 2:[" ; print_r ( key ( $page_db_lookup ) ) ; echo "]" ;skumring
                
                $fields = $fields + current ( $page_db_lookup ) ;
            }
            
            if ( $this->debug ) { echo "<div style='margin:10px;padding:10px;border:dotted 1px #999;'>[fetch_all, from ".debugPrintCallingFunction()."]" ; print_r ( $fields ) ; echo "</div>" ; }
            
            $who = isset ( $this->glob->user->values['id'] ) ? $this->glob->user->values['id'] : 0 ;
            
            // print_r ( $who ) ;
            
            // echo "<pre>" ;
            // print_r ( $fields ) ;
            // echo '['.strstr ( $fields['type'], 'xs::_' ).']' ;
            
            if ( isset ( $fields['type'] ) && strstr ( $fields['type'], 'xs::_' ) ) {
                
                // there's a type coming in, and they specify a known xSiteable content type
                
                $e = 0 ;
                // echo "[$e] " ;
                eval ( '$e = ' . $fields['type'] . ";" ) ;
                // echo "[$e] " ;
                $fields['type1'] = $e ;
                
                // echo "[$resource_id]" ;

                switch ( $fields['type'] ) {
                    case 'xs::_page' : 
                        $fields['name'] = $resource_id ; 
                        break ;
                    case 'xs::_news' : break ;
                    default : break ;
                }
                unset ( $fields['type'] ) ;
            }
            
            // print_r ( $fields ) ;
            // die() ;
            if ( ! isset ( $fields['pub_full'] ) ) {
                $fields['pub_full'] = '<b>This</b> is fresh new content. Hit the edit button to make it yours!' ;
                $fields['pub_full_type'] = 0 ;
            }
            
            $try = simplexml_load_string ( trim ( '<span>'.$fields['pub_full'] ).'</span>' ) ;
            
            if ( $try === false ) {
                
                // $fields['pub_full'] = str_replace ( '&', ' ', $fields['pub_full'] ) ;
                
                // echo "<pre style='background-color:orange;margin:20px;padding:20px;'>Bad content, no save!</pre>" ;
                // echo "<pre style='background-color:yellow;margin:20px;padding:20px;'>".htmlentities($fields['pub_full'])."</pre>" ;
                // print_r ( $try ) ;
                // return ;
            }
            
            // $item->inject ( $fields ) ;
            
            // is the ID there, and is it over 0? (meaning; this is probably an update)
            if ( isset ( $fields['id'] ) && $fields['id'] > 0 ) {
                
                if ( $this->debug ) echo "old" ;
                // $fields['m_u_who'] = $who ;
                // $fields['m_u_date']
                $w = $this->glob->tm->update ( $fields ) ;
                $this->alert ( 'notice', 'Okay', 'You have successfully updated this content.' ) ;

            } else {
                
                if ( $this->debug ) echo "new" ;
                $w = $this->glob->tm->create ( $fields ) ;
                $this->alert ( 'notice', 'Good news!', 'You have successfully created a new page. Now edit it!' ) ;
                
            }
            
            $this->glob->data->reset ( $resource_id ) ;
            
            // die() ;
        }

    }
