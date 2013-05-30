<?php

    /*
     * This is the action class for widgets. Note, however, that it functions as a
     * widget *controller*, and needs to support dealing with multiple instances
     * of that type of widget, possibly with different views and so forth. There's
     * an array in the foyer of the instances associated with this controller.
     */

    class xs_Action_Widget extends xs_Action_Webpage {

        // Just a quick shortcut for the name of the widget (based on its class name)
        public $widget_name = array() ;

        // the default view of the widget. If only one view, ignore it
        public $view = null ;

        // If this widget controller pops out many widgets, this is how many
        private $widget_counter = -1 ;

        // every widget instance (each widget class is effectively a controller for
        // a number of possible widget instances)
        public $instances = array () ;

        function __construct () {

            // Go to parents constructor first, making this a xs_Action class
            parent::__construct() ;

            $this->widget_name = substr ( $this->_meta->class, 10 ) ;

            // Set the widgets name (not unique id) in the _meta
            $this->_meta->widget_name = $this->widget_name ;

            // Every widget gets an API resource matching the class name
            $this->_register_resource ( XS_WIDGET, '_api/widgets/' . $this->widget_name ) ;

            // They also get a handy data feed
            $this->_register_resource ( XS_WIDGET, '_api/widgets/' . $this->widget_name . '/feed' ) ;

        }

        function ___this_pre () {
            // just wrap a possible 'widget_view' request variable into a local one
            $this->view = $this->glob->request->__fetch ( 'widget_view', 'index' ) ;
        }


        // a way for the widget controller (primarily) to inject the instances
        // for this controller to deal with
        function _add_instance ( $id = null ) {

            if ( $id != null ) {

                // add the instance with its controller reference (our reference)
                $this->instances[$id] = new xs_WidgetInstance ( $id, $p = null, $s = null ) ;

                // initiate all properties and settings
                $this->instances[$id]->_p->__inject ( $this->_properties->__get_array () ) ;
                $this->instances[$id]->_s->__inject ( $this->_settings->__get_array () ) ;

                if ( $p ) $this->instances[$id]->_p->__inject ( $p->__get_array () ) ;

                if ( $s ) $this->instances[$id]->_s->__inject ( $s->__get_array () ) ;
            }
        }

        function get_instance ( $id ) {
            if ( isset ( $this->instances[$id] ) )
                return $this->instances[$id] ;
            return null ;
        }

        // shorthand/cheat/hack for accessing properties
        function prop ( $idx ) {
            if ( isset ( $this->properties[$idx] ) )
               return $this->properties[$idx] ;
            return '' ;
        }
        
        function GET_title () { return $this->prepare ( $this->_settings->title ) ; }
        function GET_menu () { return $this->prepare ('') ; }
        function GET_content () { return $this->prepare ('') ; }
        function GET_footer () { return $this->prepare ('') ; }

        function gui_setup ( $param = array ( 'nothing' ) ) {

            $id = null ;
            if ( isset ( $param['id'] ) )
                $id = $param['id'] ;

            $inst = $this->get_instance ( $id ) ;

            // var_dump ( $param ) ; var_dump ( $id ) ; var_dump ( $inst ) ;
            
            // just keeping track
            $this->widget_counter++ ;

            // the widgets visual and behavioral options. Everything is disabled
            // by default (and upped based on users credentials)

            $default = false ;

            $option = array (
                'collapse' => $default,
                'close' => $default,
                'config' => $default,
                'edit' => $default,
                'move' => $default
            ) ;

            // TODO : Move this hardcoded rubbish to a place it makes sense
            if ( $this->glob->user->inGroup ( 'Function - Intranet Editors' ) ) {
                $option['move'] = true ;
                $option['close'] = true ;
                $option['collapse'] = true ;
            }

            // var_dump ( $id ) ;
            // var_dump ( $this->instances ) ;

            // get XML for our settings
            $settings = $this->get_xml_object ( $inst, 's' ) ;
            if ( $settings == '' ) $option['edit'] = false ;

            // get XML for our properties
            $properties = $this->get_xml_object ( $inst, 'p' ) ;
            if ( $properties == '' ) $option['config'] = false ;

            // create XML representation for our options
            $options = '' ;
            foreach ( $option as $item => $state )
                if ( $state )
                    $options .= "<$item />" ;

            // echo "[$id :: ".$this->_meta->widget_name." :: ".$this->_meta->class."] " ;
            
            // a chunk of XML that will render the widget (through another call to the XSLT layer)
            $ret = "<nut:widget xmlns:nut='http://schema.shelter.nu/nut' name='".$this->_meta->widget_name."' id='$id'><options>$options</options><settings>$settings</settings><properties>$properties</properties></nut:widget> " ;

            // debug_r($ret);
            
            return $ret ;
        }

        function get_xml_object ( $inst, $what ) {

            // get the widget's sub-object
            $farce = "_$what" ;
            $arr = array () ;
            

            if ( is_object ( $inst ) ) {

                $obj = $inst->$farce ;



                // These objects are all xs_Properties, so have a '__get_array() method
                $arr = $obj->__get_array() ;

            }

            // create XML from these arrays
            $ret = '' ;
            foreach ( $arr as $idx => $value )
                $ret .= "<item name='$idx'>$value</item>" ;
            return $ret ;
        }


    }
	