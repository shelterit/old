<?php

class xs_Stack extends xs_Properties {

    private $_stack = array() ;
    private $_type = array() ;

    function __construct () {
        parent::__construct() ;
    }

    function add ( $idx, $object, $type = null ) {

        if ( isset ( $this->_stack[$idx]['zzzzzzzzzzzzzzzzzzzz'] ) ) {

            if ( is_array ( $object ) ) {

                $new_keys = array_keys ( $object ) ;
                $old_keys = array_keys ( $this->_stack[$idx] ) ;

                foreach ( $object as $n_key => $n_obj )
                    $this->_stack[$idx][$n_key] = $n_obj ;

            } else {
                $this->_stack[$idx] = $object ;
                // echo "!!" ;
            }
        } else {
            $this->_stack[$idx] = $object ;
            $this->_type[$idx] = $type ;
        }
    }

    function add_merge ( $idx, $object, $type = null ) {

        if ( ! isset ( $this->_stack[$idx] ) )
            $this->_stack[$idx] = array () ;

        $this->_stack[$idx][] = $object ;
    }

    function get ( $idx = null ) {
        if ( $idx == null )
            return $this->_stack ;
        else {
            if ( isset ( $this->_stack[$idx] ) )
                return $this->_stack[$idx] ;
            else
                return null ;
        }

    }

    function get_structured () {

        $response = new xs_Xml_Response ( $this ) ;

        return $response->get() ;

    }

}