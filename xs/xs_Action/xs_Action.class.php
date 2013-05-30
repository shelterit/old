<?php

    /*
     * This class makes inherited classes an Action class, meaning it's a class
     * now that not only reacts to incoming data, but performs actions to it,
     * so most plugins will be an action class (like Widgets, Resources and Webpages)
     * All Action classes also have access rules.
     */

    class xs_Action extends xs_EventStack_Plugin {

        public $layout = null ;

        function __construct ( $values = null ) {

            // Go to parents constructor first, making this a xs_EventStack_Plugin class
            parent::__construct() ;

            // All action classes gets an init and configure method setup
            $this->_register_plugin ( XS_PLUGIN, XS_ACTION_INIT, '_init' ) ;
            $this->_register_plugin ( XS_PLUGIN, XS_ACTION_CONFIG, '_configure' ) ;

            // If it's a resource (meaning; a direct URI hit), add some stuff to the event stack automatically
            // $this->_set_as_action () ;

            // What property objects do each action class have?
            $items = array ( 'meta', 'page', 'properties', 'settings' ) ;

            // Transfer whatever the class developer puts into local versions
            // of those properties into our proerty objects, so if a plugin
            // developer makes a local variable "public $settings['yalla'] = 'b';"
            // it gets put into $this->_settings->yalla and as an object can
            // be serialized and dealt with, including having global settings
            // that override, and so on. Good fun.

            foreach ( $items as $item ) {
                if ( isset ( $this->$item ) ) {
                    $_item = "_$item" ;
                    foreach ( $this->$item as $idx => $value ) {
                        $i = $this->$_item ;
                        $i->$idx = $value ;
                    }
                }
            }

            $this->glob->log->add ( 'xs_Action : construct : '.get_class ( $this ) ) ;
        }

        function log ( $action, $message = '' ) {
            $compund  = '{'.$this->glob->breakdown->concept.'} ' ;
            $compund .= '{'.$this->glob->breakdown->section.'}' ;
            // $compund .= '{'.$this->glob->breakdown->id.'}' ;
            $this->glob->logger->logInfo ( '['.$this->glob->user->username."] $compund ({$action}) \"$message\" " ) ;
        }
        
        /*
        function db_synch_me ( $id = null ) {
            
            // a function for synching objects into some database
            
            // 1. define query
            // 2. perform query
            // 3. if found, return
            // 4. if NOT found, create
            
            if ( $id == null )
                $id = 'obj:' . $this->_meta->uuid ;
            
            $this->glob->data->register_query (

                // use the default xs (xSiteable) datasource
                'xs',

                // identifier for our query
                $id,

                // the query in question (passing in an array sends the query to
                // the Topic Maps engine (that builds its own SQL) rather than
                // a generic SQL

                array (
                    'name'        => $id,
                    'select'      => 'id,type1,label,m_p_date,m_p_who,m_u_date,m_u_who,parent',
                    'return'      => 'topics'
                ),

                // the timespan of caching the result
                '+1 minute'
            ) ;
            
        }

*/

        function framework () {
            // $this->layout = $this->_get_resource ( $this->api_base . 'bar' ) ;
        }

        function _configure () {
            // Overwrite for your configuration!
        }

        function _init () {
            // Overwrite this one for all your init!
        }

        function _action () {
            // Overwrite for all your hot, steamy action!
        }

        // If we know we're dealing with a HTTP request, use this action! (Should
        // be set up by the xs_core controller)

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

            // debug ( $this->_meta->file, '_HTTP_ACTION :: '.$m ) ; 
            
            $this->glob->log->add ( 'xs_Action : ACTION : End' ) ;
        }

        function _end_action () {
        }

        // Overwrite at will
        
        public function GET () { 
            // debug ( $this->_meta->file, 'GET' ) ; 
        
        }
        public function POST ( $arg = null ) { 
            // debug ( $this->_meta->file, 'POST' ) ; 
        
        }
        public function DELETE () { 
            // debug ( $this->_meta->file, 'DELETE' ) ; 
        
        }
        public function PUT () { 
            // debug ( $this->_meta->file, 'PUT' ) ; 
        
        }
        public function HEADERS () { 
            // debug ( $this->_meta->file, 'HEADERS' ) ; 
        
        }

        function _get () {}
        function _put () {}
        function _post () {}
        function _delete () {}
        function _headers () {}

        function output_application_xml () {

        }

        function output_application_xhtml () {

        }

        function add_content_type ( $type ) {
            
        }





        // Dummy methods returning NULL until overridden with real stuff
        function _request_js () {
            return null ;
        }
        function _request_css () {
            return null ;
        }

        function _config_list () {
            $ret = "<div class='xsSection borderish'>" ;
            $ret .= $this->_gui_info () ;
            return $ret . "</div>" ;
        }

        function _config_edit () {
            $ret = "<div class='xsSection borderish'>" ;
            $ret .= $this->_gui_property ( 'Some test value', 'test', '200' ) ;
            return $ret . "</div>" ;
        }



        function _gui_property ( $label, $field, $default ) {
            $ret = "<div class='xs_plugin_property'>" ;
            $ret .= "<span>$label:</span> <input name='plugin:".$this->name.":".$field."' id='plugin:".$this->name.":".$field."' value='".$this->fields->get ( $field, $default )."' />" ;
            $ret .= '<input value="{$dir/static}" />' ;
            return $ret.'</div>' ;
        }

        function _gui_info () {
            $ret = "<div class='xs_plugin_property'>" ;
            $ret .= "<h1>".$this->metadata['name']."</h1>" ;
            $ret .= "<h2>Version ".$this->metadata['version']."</h2>" ;
            $ret .= "<h3>By " ;
            if ( isset ( $this->metadata['author_link'] ) )
                $ret .= "<a href='".$this->metadata['author_link']."'>".$this->metadata['author']."</a>" ;
            else
                $ret .= $this->metadata['author'] ;
            $ret .= "</h3>" ;

            if ( isset ( $this->metadata['editable_options'] ) && $this->metadata['editable_options'] == true )
                $ret .= "<div><a href='".$this->glob->dir->self."?mode=edit&amp;plugin=".$this->name."'>Edit options</a></div>" ;

            return $ret.'</div>' ;
        }

        function _find_persisted_vars () {
            $ret = array() ;
            // print_r ( get_object_vars($this) ) ; die() ;
            foreach ( get_object_vars($this) as $var => $value )
                if ( substr ( $var, 0, 1 ) == '_' && substr ( $var, 1, 1 ) != '_' )
                    $ret[] = substr ( $var, 1 ) ;
            return $ret ;
        }




        function _do_output () {
            // Shortcut for the three levels of outputs, created
            // for direct calls (like widgets) that don't attach these
            // themselves.
            // echo '[do]' ;

            $this->_prepare_output() ;
            $this->_init_output() ;
            $this->_render_output() ;
        }

        function _prepare_output () {

            // Put a few common things on the stack
            $re = $this->glob->request ;
            $br = $this->glob->breakdown ;

            $my_output   = $re->__fetch ( '_output', 'xhtml' ) ;

            $this->glob->log->add ( 'xs_Action : prepare_output : ' . $my_output ) ;

            $this->glob->stack->add ( 'xs_user', $this->glob->user->__getArray() ) ;
            $this->glob->stack->add ( 'xs_page', $this->glob->page->__getArray() ) ;
            $this->glob->stack->add ( 'xs_languages', explode ( '|', $this->glob->config['website']['language'] ) ) ;
            $this->glob->stack->add ( 'xs_request', $re->__getArray() ) ;
            $this->glob->stack->add ( 'xs_breakdown', $br->__getArray() ) ;

            $this->glob->log->add ( 'All stack items, ready to go!' ) ;

        }


        function _init_output () {

            $this->glob->log->add ( 'xs_Action : ACTION : do_output' ) ;

            // Put a few common things on the stack
            $re = $this->glob->request ;

            if ( $re == XS_ROOT_ID ) $re = '' ;
            
            // Are we redirecting?
            $my_redirect = $re->__fetch ( '_redirect', '' ) ;
            if ( $my_redirect != '' )
                header ( "Location: " . $my_redirect ) ;

            // Generate response XML from the application stack
            $response = new xs_XmlResponse ( $this->glob->stack ) ;

            // Get some XML goodness!
            $this->_output_xml = $response->get() ;

            // echo "<pre>".$this->_output_xml."</pre>" ;

        }

        function _render_output ( ) {

            $re = $this->glob->request ;

            $my_output   = $re->__fetch ( '_output', 'xhtml' ) ;
            $my_redirect = $re->__fetch ( '_redirect', '' ) ;

            // Prepare an output object with the response from the application
            $this->output = new xs_Output ( $this->_output_xml, $this->glob->stack, $this->_page->template ) ;

            $this->glob->log->add ( 'xs_Action : ACTION : do_output -> action () ' ) ;

            // Action: Output by default is XHTML, which kicks in the XSLT framework
            // but could also be 'text', 'xml' (great for debugging), and a few other
            $this->output->action ( $my_output ) ;

            $this->glob->log->add ( 'xs_Action : ACTION : do_output : Done' ) ;
        }

        function set_title ( $tmp ) {
            $this->_page->_set ( 'title', $tmp ) ;
        }

        function set_template ( $tmp ) {
            $this->_page->_set ( 'template', $tmp ) ;
        }

        function set_style ( $tmp ) {
            $this->_page->_set ( 'style', $tmp ) ;
        }

        // for preparing various results when called from the XSLT engine
        function prepare ( $txt ) {
            $s = @simplexml_load_string ( $txt ) ;
            if ( !$s ) 
                $s = @simplexml_load_string ( "<span>".$txt."</span>" ) ;
            return dom_import_simplexml ( $s ) ;
        }



        /*
         * Deal with access that this Action class instance has got
         *
         *
         *
         *
         *
         *
         */

        // function


    }
	