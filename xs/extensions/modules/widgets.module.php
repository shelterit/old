<?php

/*
 *
 * Rules for widget handling, overriding data along the way ;
 *
 *  - load widget controller properties data from data source
 *  - load widget properties from data source
 *  - load widget instance properties from data source
 *
 *
 */

    class xs_module_widgets extends xs_Action {

        public $metadata = array (
            'name' => 'Widgets module',
            'description' => 'Module to handle widgets',
            'version' => '1.0',
            'author' => 'Alexander Johannesen',
            'author_link' => 'http://shelter.nu/',
            'editable_options' => true,
        ) ;

        // Local reference to the global stack event manager
        public $events = null ;


        // The base (and length of that string) for registering the API
        private $api_base = '_api/widgets/control' ;

        // The variable to store our various widget controller classes
        private $widgets_index = null ;

        // the variable used to store all active instances found
        // format: $widget_instances[$instance_id] = (string) $widget_controller_id ;

        // private $widget_instances = array () ;


        function ___application () {

            global $xs_stack ;

            // Register the resource of the basic API (meaning, getting all
            // HTTP requests, like GET and POST and so on)
            $this->_register_resource ( XS_MODULE, $this->api_base ) ;

            // Register the resource for rendering a single widget
            $this->_register_resource ( XS_MODULE, $this->api_base.'/render' ) ;

            // Make a reference to the global stack event manager
            $this->events = $xs_stack ;

        }

        function ___widgets () {

            // quick, make a handy reference to the current URI
            $path = $this->glob->request->_uri ;

            // load to see if there's a database entry for this URI and its widgets
            $this->load_widgets ( $path ) ;

            // then, make a list of all the widgets that we are to display
             $all_widgets = array () ;

            // First, loop through the mess and pick out just the widgets
            if ( is_array ( $this->widgets_index ) && count ( $this->widgets_index ) > 0 ) {
                foreach ( $this->widgets_index as $section ) {
                    if ( is_array ( $section ) ) {
                        foreach ( $section as $widget ) {
                            $all_widgets[$widget] = $widget ;
                        }
                    }
                }
            }

            // if no widgets are found, just exit
            if ( count ( $all_widgets ) < 1 ) 
                return ;
            
            // fetch all widgets that are to be displayed from the Topic Map
            $result = $this->glob->tm->query ( array ( 'name' => $all_widgets ) ) ;
            
            // alter the structure so that 'name' field becomes the index for the array instead of field 'id'
            if ( is_array ( $result ) && count ( $result ) > 0 ) {
                foreach ( $result as $id => $widget ) {
                    if ( isset ( $widget['name'] ) ) {
                        $result[$widget['name']] = $widget ;
                        unset ( $result[$id] ) ;
                    }
                }
            }
            

            // var_dump ( $result ) ;
            if ( $this->glob->request->__get( '_debug', 'false' ) == 'true' ) {
                $this->widgets_index['_DEBUG'][] = 'widget-data_manager-' . rand ( 1000, 9999 ) ;
            }


            // if it's an array, then yes!
            if ( is_array ( $this->widgets_index ) ) {


                foreach ( $this->widgets_index as $section => $widgets ) {

                    foreach ( $widgets as $position => $widget ) {

                                    $p = explode ( '-', $widget ) ;

                                    if ( isset ( $p[1] ) ) {

                                        $widget_controller = $p[1] ;
                                        $widget_id = $p[2] ;

                                        $w = $this->events->get_widgets ( $widget_controller ) ;

                                        if ( is_array ( $w ) ) {
                                            foreach ( $w as $wc ) {

                                                $pp = $ss = null ;

                                                // has the widget a stored version of itself in the database?
                                                if ( isset ( $result[$widget] ) ) {

                                                    $properties = $settings = array () ;

                                                    foreach ( $result[$widget] as $idx => $value ) {
                                                        $x = explode ( '__', $idx ) ;
                                                        if ( isset ( $x[1] ) ) {
                                                            $what = $x[0] ;
                                                            $key = $x[1] ;
                                                            if ( $what == 'p' ) {
                                                                $properties[$key] = $value ;
                                                            } else if ( $what == 's' ) {
                                                                $settings[$key] = $value ;
                                                            }
                                                        }
                                                    }

                                                    $pp = new xs_Properties ( $properties ) ;
                                                    $ss = new xs_Properties ( $settings ) ;

                                                    // echo "<pre style='background-color:yellow'> " ; print_r ( $settings ) ; echo "</pre>" ;
                                                    // echo "<pre style='background-color:yellow'> " ; print_r ( $properties ) ; echo "</pre>" ;

                                                }

                                                // add the instance id to the widget controller (for its own reference)
                                                $wc->_add_instance ( $widget, $pp, $ss ) ;

                                                // echo '{' . $widget . '} ' ;

                                                // Setup the widgets to run in their GUI events (sections)
                                                $c = 'XS_GUI_SECTION'.$section ;
                                                $wc->_register_plugin ( XS_WIDGET, constant($c), 'gui_setup', array ( 'id' => $widget ) ) ;
                                                $this->glob->log->add ( 'WidgetController : setup section '.$c ) ;
                                                // echo '[' . $c . '] ' ;

                                                // create an event the plugins can use to hook themselves onto because they are to be active
                                                $d = 'XS_WIDGET_' . strtoupper ( $widget_controller ) . '_ACTIVE' ;
                                                $this->events->add_event ( constant('XS_WIDGETS_ACTION'), $d ) ;
                                                $this->glob->log->add ( 'WidgetController : add event '.$d ) ;
                                                // echo '(' . $d . ') ' ;
                                            }
                                        }
                                    }

                    }
                }







                /*



                $all_widgets = array () ;

                // First, loop through the mess and pick out just the widgets
                foreach ( $this->widgets_index as $uri => $index ) {
                    if ( is_array ( $index ) && $path == $uri ) {
                        foreach ( $index as $section => $widgets ) {
                            foreach ( $widgets as $id => $widget ) {
                                $all_widgets[$id] = $id ;
                            }
                        }
                    }
                }

                // then, loop through it again, this time set up events; first for when widgets are to be displayed,
                // and second for those widgets in particular

                foreach ( $this->widgets_index as $uri => $index ) {
                    if ( is_array ( $index ) && $path == $uri ) {

                        foreach ( $index as $section => $widgets ) {
                            if ( is_array ( $widgets ) ) {
                                foreach ( $widgets as $id => $widget ) {
                                    // echo "[$id] " ;
                                    $p = explode ( '-', $id ) ;
                                    if ( isset ( $p[1] ) ) {
                                        $w = $this->events->get_widgets ( $p[1] ) ;
                                        // print_r ( $w ) ;
                                        if ( is_array ( $w ) ) {
                                            foreach ( $w as $wc ) {

                                                // add the instance id to the widget controller (for its own reference)
                                                $wc->_add_instance ( $id ) ;

                                                // Setup the widgets to run in their GUI events (sections)
                                                $c = 'XS_GUI_SECTION'.$section ;
                                                $wc->_register_plugin ( XS_WIDGET, constant($c), 'gui_setup', $widget ) ;
                                                $this->glob->log->add ( 'WidgetController : setup section '.$c ) ;

                                                // create an event the plugins can use to hook themselves onto because they are to be active
                                                $d = 'XS_WIDGET_' . strtoupper ( $p[1] ) . '_ACTIVE' ;
                                                $this->events->add_event ( constant('XS_WIDGETS_ACTION'), $d ) ;
                                                $this->glob->log->add ( 'WidgetController : add event '.$d ) ;
                                                // echo '(' . $d . ') ' ;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                // widget controllers sorted, now load instance data
                $result = $this->glob->tm->query ( array ( 'name' => $all_widgets ) ) ;
                // print_r ( $result ) ;

                foreach ( $result as $id => $widget ) {

                    $p = explode ( '-', $id ) ;
                    if ( isset ( $p[1] ) ) {
                        // $w = $this->events->get_widgets ( $p[1] ) ;
                        // $w->instances[$id]->s->__inject ( $result ) ;
                        // $w->instances[$id]->p->__inject ( $wc->_settings->__get_array () ) ;
                        //var_dump ( $w ) ;
                    }
                }

                 *
                 */
                // echo "WIDGETS: " ; var_dump ( $result ) ;
            }
        }

        //  4239.35

        function ___widgets_end () {

            // At the end of dealing with all the widgets (so, active widget
            // controllers, *not* widget instances), compile a list of
            // them, and place them on the stack

            // Get all widgets
            $widgets = $this->events->get_widgets () ;

            $ret = array () ;

            foreach ( $widgets as $count => $widget ) {
                
                $name = $widget->widget_name ;

                if ( trim ( $name ) != '' ) {
                   $ret[$name] = array (
                       'name'         => $widget->_meta->name,
                       'description'  => $widget->_meta->description,
                       'version'      => $widget->_meta->version,
                       'type'         => $widget->_meta->type
                   ) ;
                }
            }
            $this->glob->stack->add ( 'xs_widgets', $ret ) ;


            // are there any incoming widget settings or properties
            // that the user wants to save?

            // TODO : DO this.

            /*
            $fields = $this->glob->request->__get_fields () ;

            $topic = array () ;

            foreach ( $fields as $idx => $value ) {

                $p = explode ( '__', $idx ) ;
                
                if ( isset ( $p[1] ) ) {
                    
                    $id = $p[0] ;
                    $what = $p[1] ;
                    $field = $p[2] ;
                    
                    $topic['name'] = $id ;
                    $topic['type1'] = XS_WIDGET ;
                    
                    switch ( $field ) {
                        case 'title': $topic['label'] = $value ; break ;
                        default     : $topic[$field] = $value ; break ;
                    }
                    
                }


            }

            $w = $this->glob->tm->update ( $topic, true ) ;

            */

            // var_dump ( $topic ) ;

            // echo "PAGE: " ; var_dump ( $w ) ;


            // echo "<pre>" ; print_r ( $ret ) ; echo "</pre>" ;

        }

        function clean_id ( $id ) {

            $id = rtrim ( ltrim ( $id, '/' ), '/' ) ;

            // if identifier is blank, we assume it is index (as we're basing it off the incoming URIs)
            if ( trim ( $id ) == '' ) $id = 'index' ;

            // use the global data manager to create a good identifier for us
            return $this->glob->data->create_id ( 'widgetsmngr-' . str_replace ( '/', '-', $id ) ) ;

        }

        function save_widgets ( $id = '' ) {

            // filename?
            $file = xs_Core::$dir_app . '/datastore/'.$this->clean_id ( $id ).'.arr' ;

            file_put_contents ( $file, serialize ( $this->widgets_index ) ) ;
            // echo "<br><br>saved [$file] <br><br>" ;
            // print_r ( $this->widgets_index ) ;
            
            $this->glob->log->add ( 'widgets.module : save ' . $file ) ;
        }

        function load_widgets ( $id = '' ) {

            // filename?
            $file = xs_Core::$dir_app . '/datastore/'.$this->clean_id ( $id ).'.arr' ;

            if ( file_exists ( $file ) ) {

                $res = file_get_contents ( $file ) ;
            // echo "L ($file):" ; var_dump ( serialize ( $this->widgets_index ) ) ;
            //echo var_dump ( $res ) ;

                $this->widgets_index = unserialize ( $res ) ;
              // echo "L ($file):" ; var_dump ( $this->widgets_index ) ;
                // echo "<br><br>[application/datastore/{$id}.arr] <br><br>" ;
            }

            // }
            // echo "loaded ($file)" ;
            
            $this->glob->log->add ( 'widgets.module : load ' . $file ) ;
        }

        public function GET () {

            $q    = $this->glob->request->q ;
            $name = $this->glob->request->name ;
            
            if ( $q == '_api/widgets/control/render' ) {

                // Render a widget
                $r = $this->events->get_widgets ( $name ) ;

                if ( is_array ( $r ) && isset ( $r[$name] ) ) {

                    $widget = $r[$name] ;
                    // debug ( $widget ) ;

                    $this->glob->request->_set ( '_output', 'content-widget' ) ;
                    $this->glob->request->_set ( '_widget_name', $name ) ;

                    // debug ( $this ) ;
                    
                    $this->glob->widget_output = new xs_Action_Webpage () ;
                    // $this->glob->widget_output->_set_as_action ( true ) ;
                    
                    $d = 'XS_' . strtoupper ( $name ) . '_ACTIVE' ;
                    $c = 'XS_WIDGETS_ACTION' ;
                    if ( ! defined ( $c ) )
                        define ( $c, $c ) ;

                    $this->events->add_event ( constant ( $c ), $d ) ;

                    // $w = $widget->gui_setup() ;
                    
                    $this->glob->widget_output->_do_output () ;
                    
                    // return $widget->GET() ;
                    
                    // $this->glob->widget_output->_register_event ( XS_MODULE, XS_OUTPUT, '_do_output' ) ;
                    
                    // echo "<pre style='padding:5px;margin:5px;border:dotted 2px gray;'>" ; print_r ( $items ) ; echo "</pre>" ;
                    // $w->_do_output () ;
                    // die() ;

                } else {

                    echo "[$name] not found." ;

                }
            } else {
                // Control
            }
        }
        
        public function POST ( $arg = null ) {

            $layout  = $this->glob->request->layout ;
            $uri     = $this->glob->request->uri ;
            $id      = $this->glob->request->widget_id ;

            $res = array () ;

            if ( $id != '' ) {

                echo "WIDGET" ;

                $settings = array () ;
                $properties = array () ;

                $fields = $this->glob->request->__get_fields () ;

                $fields['name']  = $id ;
                $fields['type1'] = XS_WIDGET ;

                if ( isset ( $fields['s__title'] ) )
                    $fields['label'] = $fields['s__title'] ;

                $z = $this->glob->tm->update ( $fields, true ) ;

                // var_dump ( $fields ) ;
                // var_dump ( $z ) ;

                $z = $this->glob->tm->query ( array ( 'name' => $id ) ) ;
                var_dump ( $z ) ;

                /*
                foreach ( $fields as $idx => $value ) {
                    $x = explode ( '__', $idx ) ;
                    if ( isset ( $x[1] ) ) {
                        $what = $x[0] ;
                        $key = $x[1] ;
                        if ( $what == 'p' ) {
                            $properties[$key] = $value ;
                        } else if ( $what == 's' ) {
                            $settings[$key] = $value ;
                        }
                    }
                }

                echo "<pre style='background-color:yellow'> " ; print_r ( $this->glob->request->__get_fields () ) ; echo "</pre>" ;
                echo "<pre style='background-color:yellow'> " ; print_r ( $settings ) ; echo "</pre>" ;
                echo "<pre style='background-color:yellow'> " ; print_r ( $properties ) ; echo "</pre>" ;
                */



            } else {
                
                echo "POSITIONS" ;

                $columns = explode ( '|', $layout ) ;

                foreach ( $columns as $column ) {

                    $part = explode ( ':', $column ) ;

                    if ( isset ( $part[1] ) ) {
                        $column_id = $part[0] ;
                        $widgets = explode ( ',', $part[1] ) ;

                        foreach ( $widgets as $position => $widget )
                            $res[$column_id][$position] = $widget ;
                    }
                }

                $this->widgets_index = $res ;

                echo "<pre style='background-color:yellow'> " ; print_r ( $res ) ; echo "</pre>" ;

                $this->save_widgets ( $uri ) ;

            }

        }

    }
