<?php

class xs_XmlResponse {

    private $_header = "<?xml version='1.0' encoding='UTF-8'?>\n<response version='1.0' schema='http://schema.shelter.nu/NUTS'>" ;
    private $_footer = "</response>" ;
    private $_xml = '' ;
    private $wi = 1 ;

    function __construct ( $obj = null ) {

        if ($obj != null)
            $this->add ( $obj ) ;
    }

    function get () {
        return 	$this->_header .
                $this->_xml .
                $this->_footer ;
    }

    function add ( $obj = null ) {
        if ( is_array ( $obj ) ) {
            foreach ( $obj as $i=>$v )
                $this->add_item ( $i, $v ) ;
        } else {
            foreach ( $obj->get() as $i=>$v )
                $this->add_item ( $i, $v ) ;
        }
    }

    function add_item ( $idx, $inp, $type = '' ) {

        // print_r ( $inp ) ; echo "<hr>" ;

        if ( is_array ( $inp ) ) {
// echo "A" ;
            $this->open ( $idx ) ;
            foreach ( $inp as $i=>$v )
                $this->add_item ( $i, $v ) ;
            $this->close ( $idx ) ;

        } else if ( $inp instanceOf xs_Stack ) {

// echo "S" ;
            $this->open ( $idx, get_class($inp) ) ;
            foreach ( $inp->get() as $i=>$v )
                $this->add_item ( $i, $v ) ;
            $this->close ( $idx ) ;

        } else if ( $inp instanceOf xs_TopicMaps_Collection ) {

            $this->open ( $idx ) ;
            $this->add ( $inp->get_as_array () ) ;
            $this->close ( $idx ) ;

        } else if ( is_object ( $inp) ) {

// echo "O" ;
            $this->open ( $idx, 'object' ) ;
            $this->add_item ( get_class($inp), get_object_vars( $inp ) ) ;
            $this->close ( $idx ) ;

        } else {
// echo "E" ;

            if ( $idx === 'xs_tm' ) {
                $this->_xml .= "<topicMap>" ;
                $this->_xml .= (String) utf8_encode ( $inp ) ;
                $this->_xml .= "</topicMap>" ;

            } else {
                $this->open ( $idx, $type, $prop = true ) ;
                $t = str_replace('&nbsp;', ' ', $inp ) ;
                $t = str_replace('&', '&amp;', $t ) ;
                $this->_xml .= (String) utf8_encode ( $t ) ;
                // $this->_xml .= (String) utf8_encode ( str_replace(array('&','&nbsp;'), array('&amp;',' '), $inp ) ) ;
                $this->close ( $idx, $prop = true ) ;
            }
        }

    }

    function open ( $idx = 'nil', $type = '', $prop = false ) {
        $this->_xml .= "\n".str_repeat ( "   ", $this->wi ) ;
        $this->_xml .= "<item" ;
        if ( trim ( $idx ) !== '' )
            $this->_xml .= " name='".$idx."'" ;
        if ( trim ( $type ) !== '' )
            $this->_xml .= " type='".$type."'" ;
        $this->_xml .= ">" ;
        $this->wi++ ;
    }

    function close ( $idx = 'nil', $prop = false ) {
        $this->wi-- ;
        if ( !$prop )
            $this->_xml .= "\n" . str_repeat ( "   ", $this->wi ) ;
        $this->_xml .= "</item>" ;
    }

}
