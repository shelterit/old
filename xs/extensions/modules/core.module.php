<?php

    class xs_module_core extends xs_EventStack_Module {

        public $meta = array (
            'name' => 'Core module',
            'version' => '1.0',
            'author' => 'Alexander Johannesen',
            'author_link' => 'http://shelter.nu/',
            'editable_options' => true,
        ) ;

        // if types are to be defined and used
        protected $___register_types = array ( 

            // topic types
            'page' => 'A page',
            'widget' => "a widget"
            
        ) ;
        
        // Make a local copy of the events framework
        public $events = null ;

        // A list of possible paths for action classes
        private $ctrl_paths = null ;

        // A holder for the resource that represents the incoming request
        private $resource = null ;
        
        // if resource has internal errors, use this instead
        private $error_resource = null ;
        
        private $resource_source_id = null ;

        private $path = null ;

        // a generic identifier for the requested resource
        private $resource_id = null ;
        
        // looking up the resource in the database (shared between various methods)
        private $page_db_lookup = null ;
        
        private $_later_sync = null ;

        // Should we replicate this entry in the database? No if any kind of
        // error page, like HTTP response codes 404, 401, 303, etc.
        
        // private $replicate = false ;
        
        // this is our many and various resources in classes, files or plugins,
        // all fighting for the right to be The One True Action class to be run
        private $site = null ;
        
        // private $resource_functionality = null ;
        
        function __construct () { 
            
            // Make me beautiful!
            parent::__construct() ; 
            
            $this->debug = false ;
            
            // this class owns and deals with these events
            $this->_register_event ( XS_MODULE, 'on_core_process_request' ) ;
            
            // since we're in *one* module but want to simulate flexibility, register
            // event listeners to various methods as opposed to the normal ___* convention
            // that pretty much says 'one per module'
            
            $this->_register_event_listener ( XS_MODULE, 'on_core_process_request', 'on_request_api' ) ;
            $this->_register_event_listener ( XS_MODULE, 'on_core_process_request', 'on_request_file' ) ;
            $this->_register_event_listener ( XS_MODULE, 'on_core_process_request', 'on_request_db' ) ;
            
        }
        
        function register_identifier ( $uri = '' ) {
            
            if ( $this->debug ) debug ( $uri, 'register_identifier' ) ;
            
            if ( $uri == '' ) $uri = XS_ROOT_ID ;
                
            // Create a generic identifier for this resource
            $this->resource_id = $this->glob->data->create_id ( 
                XS_PAGE_DB_IDENTIFIER, 
                array ( 'uri' => $uri ) 
            ) ;
            
            $this->glob->page->identifier = $this->resource_id ;
            
        }
        
        function ___modules ( $uri = null ) {
            
            if ( $uri == null )
                $uri = $this->glob->request->_uri ;

            $this->register_identifier ( $uri ) ;
            
            // Define the structure query
            $this->glob->data->register_query (

                // identifier for what data connection to use (xs: default xSiteable)
                'xs',

                // identifier for our query
                $this->resource_id,

                // the query in question
                array ( 'name' => $this->resource_id ),

                // the timespan of caching the result
                '+1 week'
            ) ;            
            
        }

        function ___init () {
            $this->glob->config = new xs_Config ( parse_ini_file ( xs_Core::$dir_app . '/configuration.ini', true ) ) ;
        }

        function ___globals () {

            if ( isset ( $this->glob->config['website']['time-zone'] ) )
                date_default_timezone_set ( $this->glob->config['website']['time-zone'] ) ;
            
            $this->glob->human_dates = new HumanRelativeDate () ;
            $this->glob->logger      = new KLogger( XS_DIR_APP.'/log', KLogger::INFO, xs_Core::$request_random_number ) ;
            $this->glob->seclog      = new KLogger( XS_DIR_APP.'/log/security', KLogger::INFO, xs_Core::$request_random_number ) ;
            $this->glob->alerts      = array () ; // just an array for now
            $this->glob->website     = array () ;
            $this->glob->page        = new xs_WebPage () ;
            $this->glob->dir         = new xs_Properties () ;
            $this->glob->data        = new xs_DataManager () ;
            $this->glob->request     = new xs_Request () ;
            $this->glob->breakdown   = new xs_Breakdown () ;
            $this->glob->stack       = new xs_Stack () ;
            $this->glob->user        = new xs_User () ;
            $this->glob->session     = new xs_Session () ;
            $this->glob->html_helper = new html_helper () ;
            $this->glob->log->add ( 'Core : Created globals' ) ;
        }

       function ___env () {

            // Just add a generic 'now' date for various usage
            $this->glob->page->date = date ( XS_DATE ) ;
            define ( 'XS_CACHE_DEFAULT', $this->_config ( 'framework', 'cache_default' ) ) ;

            // Add them to the stack
            $this->glob->stack->add ( 'xs_config', $this->glob->config['website'] ) ;

        }

        // set up our main datastore
        function ___datastore () {

            // Main database

            $this->glob->data->register_datasource (

               // name of data source, and name of data source driver
               'xs', 'pdo',

               // populate an instance with config data needed
               array (
                'dsn'      => $this->_config ( 'database', 'dsn' ),
                'username' => $this->_config ( 'database', 'username' ),
                'password' => $this->_config ( 'database', 'password' )
               )

            ) ;

            // generic datasource for session data
            
            $this->glob->data->register_datasource (

               // name of data source, and name of data source driver
               'session', 'session', NULL

            ) ;

            // our main data source is the Topic Maps engine
            $this->glob->tm = new xs_TopicMaps () ;

            $this->glob->log->add ( 'Core : Datastore setup' ) ;
        }
        
        
        // instigate an event to kickstart our search for the right resource
        function ___control () {
            
            // at the event 'XS_CONTROL' we're firing the event 'on_core_process_request'
            // to ask any and all plugins to provide us with a class that should
            // be the main action class (and in control from now on)
            
            $this->_fire_event ( 'on_core_process_request' ) ;
            
        }
        
        function callback___on_core_process_request ( $return = null ) {
            
            // every time an event plugin returns a response, check it
            // tenatively, and if a proper resource is invoked, use
            // that and cancel the event (first proper resource wins).
            
            $id = 'Unknown' ;
            if ( isset ( $return[0] ) )
                $id = $return[0] ;
            
            $resource = null ;
            if ( isset ( $return[1] ) )
                $resource = $return[1] ;
            
            if ( $resource !== null ) {
                if ( $this->debug ) debug ( "[Accepted:$id]", '___controller' ) ;
                // debug ( $resource, '___controller' ) ;
                // print_r ( $return ) ;
                
                $this->resource = $resource ;
                $this->resource_source_id = $id ;
                
                // $this->resource->resource_id = $id ;
                
                $this->_end_event ( 'on_core_process_request' ) ;
                
            } else {
                if ( $this->debug ) debug ( "[Rejected:$id]", '___controller' ) ;
                // echo "[Rejected:$id]" ;
            }
        }
        
        // Event responder : is our URI hijacked by a plugin?
        function on_request_api () {
            
            global $xs_stack ;

            $resource = null ;
            
            if ( $this->resource == null ) {

                // Is the incoming URI hijacked by a plugin?
                $resources = $xs_stack->find_resource ( $this->glob->request->_uri ) ;
               
                if ( is_array ( $resources ) && isset ( $resources['instance'] ) )
                    $resource = $resources['instance'] ;
                
            }
            
            if ( $this->debug ) debug ( $this->glob->request->_uri, 'on_request_api' ) ;
            
            // So, was there a resource hogging our URI?
            if ( $resource !== null ) {
                
                // Set the type
                $this->glob->page->type = XS_PAGE_RESOURCE ;
                $this->glob->page->source = 'resource' ;

                // Add this event
                $this->_add_event ( 'XS_DISPATCHER_END', 'XS_RESOURCE' ) ;
                
                // debug($resource);
                return array ( 'api', $resource ) ;
                
            }
            
            return array ( 'api', null ) ;
        }
        
        // Event responder : is our URI associated with a file (with a class in it)?
        function on_request_file ( $file = null ) {
            
            // Do we have a specific file to try?
            if ( ( is_array ( $file ) && count ( $file ) == 0 ) || $file == null ) {
                
                // No; look for one using incoming URI
             
                // Create a list of potential action classes
                $this->create_action_class_paths ( $this->glob->request->_uri ) ;

                // What action class did we find?
                $file = $this->find_first_action_class () ;
                
            }
            
            $resource = null ;

            if ( $this->debug ) debug ( $file, 'on_request_file :: path' ) ;

            if ( $file !== null ) {


                // Try to include the class path most likely
                include ( xs_Core::$dir_app . '/' . $file )  ;

                // Instantiate it, and it will register its own methods
                // to the event stack in its constructor
                try {

                    if ( $file == 'website/403.php' || $file == 'website/404.php' ) 
                        
                        // creating the action instance (the class in charge of output)
                        $resource = new xs_action_instance_error () ;
                    
                    else {
                        
                        // creating the action instance (the class in charge of output)
                        $resource = new xs_action_instance () ;
                        
                        // has this URI got a 'page' topic in the db?
                        $this->page_db_lookup = $this->glob->data->get ( $this->resource_id ) ;
                        
                        if ( count ( $this->page_db_lookup ) < 1 ) {
                            
                            // sync page to database (for access control, at least)
                            $this->_later_sync = true ;
                        }
                    }
                    // inject its path into it
                    $resource->_meta->action_path = XS_DIR_APP . '/' . $file ;
                    
                    // add 'page', 'widgets' and 'menus' events just after the 'XS_DISPATCH' event
                    $this->_add_event ( 'XS_DISPATCHER_END', 'XS_PAGE' ) ;
                    $this->_add_event ( 'XS_DISPATCHER_END', 'XS_WIDGETS' ) ;
                    $this->_add_event ( 'XS_DISPATCHER_END', 'XS_MENUS' ) ;

                    // Set the page type
                    // $this->glob->page->type = XS_PAGE_DYNAMIC ;
                    // $this->glob->page->source = 'file' ;

                    // $this->glob->stack->add ( 'xs_framework', $resource->_meta ) ;


                    $this->glob->log->add ( 'Controller : dispatched action class '.$this->path ) ;

                    return array ( 'file', $resource ) ;

                } catch ( exception $ex ) {

                    // Should never really go here. All classes not found should be directed to the 404.php file above
                    echo "ACTION CLASS NOT FOUND <pre>[" ; print_r ( $this->path ) ; echo "]</pre>" ;
                    echo "<pre>" ; print_r ( $this->ctrl_paths ) ; echo "</pre>" ;

                }

            } 

            return array ( 'file', null ) ;

        }
        
        function ___action_end () {
            if ( $this->_later_sync ) {
                
                // if a URI haven't got a topic attached, it could be
                // any odd page, however we restrict it to at least pages
                // with valid (non-404, non-403) action controllers, and we
                // create these pseudo-pages so we can control access to
                // pretty much all URI's
                
                $topic = array (
                    'label' => $this->resource->_page->title,
                    'type' => $this->_type->page,
                    'name' => $this->resource_id
                ) ;
                $w = $this->glob->tm->create ( $topic ) ;
                // echo "[synced]" ;
            }
        }
        
        // Event responder : is our URI (also) found in our main database?
        function on_request_db ( $uri = null ) {
            
            $resource = null ;
            
            // get generic data for this URI (resource)
            $this->page_db_lookup = $this->glob->data->get ( $this->resource_id ) ;
            
            // debug_r ( $this->resource_id ) ;
            // debug_r ( $this->page_db_lookup ) ;
            
            // got something?
            if ( count ( $this->page_db_lookup ) > 0 ) {

                if ( count ( $this->page_db_lookup ) > 1 )
                    $this->glob->page->flag_multiple_identifiers = true ;
                
                
                // Yes? Ammend to the page object
                $p = $this->glob->page->__get_array() ;

                $pid = null ;
                foreach ( $this->page_db_lookup as $id => $item ) {
                    $pid = $id ;
                    break ;
                }
                
                $p = (array) $this->page_db_lookup[$pid] + (array) $p ;

                $this->glob->page->source = 'db' ;
                $this->glob->page->id = $this->page_db_lookup[$pid]['id'] ;
                $this->glob->page->type = $this->page_db_lookup[$pid]['type1'] ;
                $this->glob->page->name = $this->page_db_lookup[$pid]['name'] ;
                
                // create resource, and return it
                $page_type = isset ( $p['page-type'] ) ? $p['page-type'] : null ;
                
                switch ( $page_type ) {
                    case 'dynamic': 
                        $r = $this->on_request_file ( 'website/dynamic_content.php' ) ;
                        break ;
                    case 'generic':
                    default:
                        $r = $this->on_request_file ( 'website/generic_content.php' ) ;
                }
                // Let's fake a generic file
                $resource = $r[1] ;
                $resource->_topic = $p ;
                
                // if ( isset ( $p['label'] ) ) $resource->_page->title = $p['label'] ;
                
                // TODO : this is wrong; needs to happen later!!!!!!!!!!!!!!!!!!!!!!
                
                // if ( isset ( $p['template'] ) ) $resource->_page->template = $p['template'] ;
                
                // debug ( $p, 'db_before_check' ) ;
                // debug ( $resource, 'db_before_check' ) ;

                /*
                if ( isset ( $p['label'] ) ) $resource->_page->title = $p['label'] ;
                if ( isset ( $p['template'] ) ) $resource->_page->template = $p['template'] ;

                $this->glob->page->source = 'db' ;
                $resource->topic = $p ;

                // print_r ( $p ) ;
                $this->glob->log->add ( 'Controller : context : database action class '.$this->path ) ;
*/
                return array ( 'db', $resource ) ;

            }

            return array ( 'db', null ) ;
        }
        
        function check_uri_access ( $uri = null ) {
            
            // fetch hither the security module
            $security = $this->_get_module ( 'security' ) ;

            // Is the current URI allowed for the current default user?
            $res = $security->resolve_uri_structure ( $uri ) ;

            // parse security
            $security->parse_access_rules () ;

            return $security->has_access () ;
        }

        // Hook this function to the XS_DISPATCHER event; it runs the found resource
        // for our current URI
        
        function ___dispatcher () {

            // Are we a resource?
            if ( $this->resource !== null ) {

                // do a check on the URI of the resource
                $test = $this->check_uri_access () ;
                
                // get the labels, and put them on the stack
                $this->glob->stack->add ( 'xs_facets', $this->get_resource_label () ) ;

                // do we pass? Are we allowed to run normally?
                if ( $test ) {
                
                    $this->glob->log->add ( 'Controller : dispatching resource '.$this->glob->request->_uri ) ;
                    
                    // don't attach rendering methods if the resource is an API resource
                    if ( $this->resource_source_id == 'api' )
                        $this->resource->_set_as_action ( false ) ;
                    else
                        $this->resource->_set_as_action ( true ) ;
                    
                    // yes ; Action!
                    $this->resource->_http_action () ;

                    // Dispatched, and all done!
                    return ;
                    
                } else {
                    
                    // No ; throw a 403 error instead
                    $r = $this->on_request_file ( 'website/403.php' ) ;
                    if ( isset ( $r[1] ) )
                        $this->error_resource = $r[1] ;
                }
                
            }   
            
            // no error, but no file found? 404 it is!
            if ( ! $this->error_resource ) {

                // Let's use the class for the 404 responder
                $r = $this->on_request_file ( 'website/404.php' ) ;
                if ( isset ( $r[1] ) )
                    $this->error_resource = $r[1] ;

                // Let's make it clear that there is no source (hence, it's blank)
                $this->glob->page->source = '' ;

            }
            
            if ( $this->error_resource ) {

                $this->error_resource->_set_as_action ( true ) ;
                
                $this->error_resource->_http_action () ;
                
                // debug ( $error_resource ) ;
                
            } else {
                
                echo "ERROR : !!!!!!!!!!!!!!!!!!!" ;
            }

        }
        
        function ___output_pre () {
            
            // after all is said and done
            // transfer final 
            
            $page = $this->glob->page ;
            $res = $this->resource ;
            
            if ( $this->error_resource )
                $res = $this->error_resource ;
            
            
            // debug ( $this->error_resource ) ;
            // debug ( $this->resource ) ;
            
            $action_path = $res->_meta->action_path ;
            
            // $this->glob->page->title = $res->_page->title ;

            
            // debug ( $res->topic ) ;
            
            $c = $res->_page->__getArray() ;
            
            if ( $res->_topic ) {
                // debug ( 'Topic!' ) ;
                $c['id'] = $res->_topic['id'] ;
                $c['title'] = $res->_topic['label'] ;
                $c['type'] = $res->_topic['type1'] ;
                $c['name'] = $res->_topic['name'] ;
            }

            $template = XS_PAGE_AUTO ;
            $new_template = '' ;
            
            // debug ( $c ) ;

            foreach ( $c as $idx => $val ) {
                if ( $idx == 'template' )
                   $template = $val ;
                $page->$idx = $val ;
            }

            // debug ( $action_path ) ;

            $len = strlen ( XS_DIR_APP ) ;
            $action_path = substr ( $action_path, $len + 1 ) ;
            $dir = dirname($action_path);
            $base = substr ( $action_path, 0, strlen ( $action_path ) - 4 ) ;
            // $short = substr ( $base, 20 ) ;

            // debug ( $action_path ) ;
            // debug ( $dir ) ;
            
            if ( $template[0] == '/' || $template[0] == '\\' ) {
                $new_template = '..'.$template ;
            } else {
                switch ( $template ) {
                    case XS_PAGE_AUTO :      
                        $new_template = "../../$base" ; break ;
                    case XS_PAGE_DYNAMIC :
                        $new_template = '../layouts/col-3' ; break ;
                    default: 
                        $new_template = "../../$dir/$template" ; break ;
                }
            }
            
            // Set the new template (if different)
            $page->_set ( 'template', $new_template ) ;
            
            // also, transfer all potential alerts
            $page->_set ( 'alerts', $this->glob->alerts ) ;

            // Update our global page object with all of this, and move on
            $this->glob->page = $page ;
        
            // debug ( $page ) ;
            
            // Just pick out the configuration website name, and plonk it in
            $this->glob->page->sitename = $this->glob->config['website']['name'] ;

            // debug ( $this->glob->page ) ;
            // debug ( $res ) ;
        }
        
        function ___register_functionality () {
            $this->_register_functionality ( 'Page view', 'page', 'role:editor' ) ;
            $this->_register_functionality ( 'Page view', 'page:*', 'role:editor' ) ;
        }
        function ___register_functionality_end () {
            $this->glob->stack->add ( 'xs_functionality', $this->_get_functionality () ) ;
        }
        
        
        function get_resource_label () {
            
                // fetch hither the security module
                $security = $this->_get_module ( 'security' ) ;
                
                // get the topics from the structure
                $topics = $security->get_topics () ;
                
                $res = array () ;
                
                foreach ( $security->get_topic_names () as $page_id => $name ) {
                    if ( $page_id !== XS_ROOT_ID ) {
                        $label = '[no title set]' ;
                        foreach ( $topics as $id => $topic )
                            if ( isset ( $topic['name'] ) && $topic['name'] == $name ) {
                                $label = $topic['label'] ;
                                break ;
                            }
                        $res[$page_id] = $label ;
                    }
                }

                return $res ;
        }

        function ___request () {

            $uri_template = '{concept}/{section}/{id}/{selector}' ;

            if ( isset ( $this->glob->config['framework']['uri_template'] ) )
               $uri_template = $this->glob->config['framework']['uri_template'] ;

            $this->glob->breakdown->_parse ( $uri_template ) ;
            $this->glob->log->add ( 'Core : Request setup' ) ;
        }

        function ___directories () {
            
            $dirs = array (
                'root' => $this->glob->config['website']['uri'],
                'home' => $this->glob->config['website']['uri'],
                'static' => $this->glob->config['website']['uri'].'/static',
                'js' => $this->glob->config['website']['uri'].'/static/js',
                'css' => $this->glob->config['website']['uri'].'/static/css',
                'images' => $this->glob->config['website']['uri'].'/static/images',
                'api' => $this->glob->config['website']['uri'].'/_api',
                'q' => $this->glob->request->q,
                '_this' => $this->glob->config['website']['uri'].'/'.$this->glob->request->q,
                'xs_this' => XS_DIR_APP,
                'xs_self' => XS_DIR_APP,
                'xs_file' => XS_DIR_XS,
            ) ;

            if ( isset ( $this->glob->config['dms'] ) ) {
                $dirs = $dirs + array (
                    'docs_web'         => $this->glob->config['dms']['destination_uri'],
                    'docs_file_source' => $this->glob->config['dms']['source_folder'],
                    'docs_file_target' => $this->glob->config['dms']['destination_folder'],
                ) ;
            }

            // Replace all back-slashes with forward ones
            foreach ( $dirs as $key => $value )
                $dirs[$key] = str_replace ( '\\', '/', $value ) ;

            // Add them to the stack
            $this->glob->stack->add ( 'xs_dir', $dirs ) ;
            

            // Also, create a more accessible copy
            $this->glob->dir->__inject ( $dirs ) ;

            $this->glob->log->add ( 'Core : Created directories' ) ;
        }

        function ___framework () {
            // Make a reference to the global stack event manager
            global $xs_stack ;
            $this->events = $xs_stack ;
        }


        function create_action_class_paths ( $start_directory = '.' ) {

            global $debug ;

            $debug = false ;

            $tokens = array () ;
            
            if ( $this->ctrl_paths != null )
                return ;

            foreach ( explode ( '/', trim ( $start_directory ) ) as $idx => $value )
                if ( trim ( $value ) != '' )
                    $tokens[] = trim ( $value ) ;

            $max = count ( $tokens ) ;
            $re = $this->glob->request ;
            $this->ctrl_paths = array () ;
            
            if ($debug) { echo "<pre>[$start_directory][" ; print_r ( $tokens ) ; echo "]</pre>" ; }

            for ( $n=0; $n<$max; $n++ ) {
                $sofar = 'website' ;
                for ( $m=0; $m<$max-$n; $m++ )
                    $sofar .= '/'.$tokens[$m] ;
                $r = pathinfo ( xs_Core::$dir_app . '/' . $sofar ) ;
                // $debug=true; if ($debug) { echo "<pre>[" ; print_r ( $r ) ; echo "]</pre>" ; }

                if ( isset ( $r['extension'] ) && $r['extension'] == 'html' ) {
                    // $this->ctrl_paths[] = 'website/blog/index.php' ;
                } else {
                    $this->ctrl_paths[] = $sofar.'/index.php' ;
                    if ( isset ( $tokens[$m] ) && trim ( $tokens[$m] ) == '' )
                        $this->ctrl_paths[] = $sofar.'.php' ;
                }
            }

            // index action class, when directly asked for it
            if ( $re->q == '' || $re->q == '/' )
                $this->ctrl_paths[] = 'website/index.php' ;

            // Default action class, the 404 handler
            // $this->ctrl_paths[] = 'website/404.php' ;

            if ($debug) { echo "<pre>[" ; print_r ( $this->ctrl_paths ) ; echo "]</pre>" ; }

        }

        function find_first_action_class () {

            // Iterate through all suspected action class files, and bail out /
            // return the first one it finds (going from outer to inner)

            $pre = xs_Core::$dir_app ;
            
            foreach ( $this->ctrl_paths as $path ) {
                $dir = $pre . '/' . $path ;
                if ( is_file ( $dir ) )
                        return $path ;
            }
            return null ;
        }

        // the core module flips a few things into the bar at the top (left side)
        function ___gui_bar () {
            $ret = '' ;

            if ( isset ( $this->glob->config['website']['show-issues'] ) && $this->glob->config['website']['show-issues'] )
            $ret .= '
                <div style="float:left;" onclick="$(\'#issues\').show();$(\'#reporter\').hide();">
                    <a href="#" id="reporter" style="font-style:underline;">'.$this->glob->config['website']['show-issues'].'</a>
                </div> ' ;

            if ( isset ( $this->glob->config['website']['show-message'] ) && trim ( $this->glob->config['website']['show-message'] ) != '' )
            $ret .= '
                <div style="float:left;margin-left:20px;">
                   '.$this->glob->config['website']['show-message'].'
                </div> ' ;

            if ( isset ( $this->glob->config['website']['show-instance'] ) && $this->glob->config['website']['show-instance'] )
            $ret .= '
                <div style="float:left;margin-left:20px;">
                    instance='.xs_Core::$request_random_number.'
                </div> ' ;

            if ( isset ( $this->glob->config['website']['show-version'] ) && $this->glob->config['website']['show-version'] )
            $ret .= '
                <div style="float:left;margin-left:20px;">
                    versions: xs='.xs_Core::$xs_version.'
                    app='.xs_Core::$app_version.'
                </div> ' ;
            return $ret ;
        }

        // the core module flips a few things into the bar at the top (right side)
        function ___gui_bar_end () {
            $ret = '' ;
            return $ret ;
        }

        function xml_inject_path ( $path, $what ) {
            
            // fetch the root
            $root = $this->site ;
            
            // Break our path into little bits
            $break = explode ( '/', $path ) ;
            
            foreach ( $break as $item ) {
                
                $set = $root->xpath ( $path ) ;
                
            }
        }
        
        function str_find_array ( $needle, $heystack ) {
            $size = strlen ( $needle ) + 1 ;
            foreach ( $heystack as $key ) {
                // echo substr ( $key, 0, $size ) .'<br>' ;
                if ( substr ( $key, 0, $size ) == $needle )
                        return true ;
            }
            return false ;
        }
        
    }
