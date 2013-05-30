<?php

class xs_TopicMaps_Topic extends xs_Properties {

    // internal and external properties
    private $int = array () ;
    private $ext = array () ;

    // the Topic schema
    private $schema = array (

        // basic topic properties
        'id'=>true, 'label'=>true, 'type1'=>true, 'type2'=>true, 'type3'=>true, 'status'=>true, 'name'=>true, 'parent'=>true, 'scheme'=>true, 'value'=>true,

        // basic topic time markers
        'm_c_date'=>true, 'm_c_who'=>true, 'm_p_date'=>true, 'm_p_who'=>true,
        'm_u_date'=>true, 'm_u_who'=>true, 'm_d_date'=>true, 'm_d_who'=>true

    ) ;


    function __construct ( $incoming = false, $first = false ) {
        parent::__construct();
        if ( $incoming )
            $this->inject ( $incoming, $first ) ;
    }

    function get_as_array ( ) {
        return $this->int + $this->ext ;
    }

    function inject ( $arr = array (), $first = false ) {

        if ( $first ) 
            $arr = end ( $arr ) ;
        
        if ( is_array ( $arr ) ) {

            foreach ( $arr as $key => $value )
                if ( isset ( $this->schema[$key] ) )
                    $this->int[$key] = $value ;
                else
                    $this->ext[$key] = $value ;

            // some extra tweaking to deal with multiple types a bit more gracefully
            if ( isset ( $arr['type'] ) ) {
                if ( !isset ( $this->int['type1'] ) )
                   $this->int['type1'] = $arr['type'] ;
                else if ( !isset ( $this->int['type2'] ) )
                   $this->int['type2'] = $arr['type'] ;
                else if ( !isset ( $this->int['type3'] ) )
                   $this->int['type3'] = $arr['type'] ;
            }
        }
    }

    function is_set ( $what ) {
        if ( isset ( $this->int[$what] ) ) return true ;
        if ( isset ( $this->ext[$what] ) ) return true ;
        return false ;
    }

    function get ( $what ) {
        if ( isset ( $this->int[$what] ) ) return $this->int[$what] ;
        if ( isset ( $this->ext[$what] ) ) return $this->ext[$what] ;
        return false ;
    }

    function set ( $what, $data ) {
        $this->inject ( array ( $what => $data ) ) ;
    }

    function _quote ( $str ) {
        return "'$str'" ;
    }

    function _fieldNames ( $arr, $quoted = false ) {
            $sql = "" ;
            foreach ( $arr as $field => $value )
                if ( $quoted )
                    $sql .= "'$field', " ;
                else
                    $sql .= $field . ", " ;
            $sql = substr ( $sql, 0, strlen ($sql) - 2 ) ;
            return $sql ;
    }

    function _fieldValues ( $arr ) {
            $sql = "" ;
            foreach ( $arr as $field => $value )
                    $sql .= $this->_quote ( $value ) . ", " ;
            $sql = substr ( $sql, 0, strlen ($sql) - 2 ) ;
            return $sql ;
    }

    function _fieldNameValues ( $arr ) {
            $sql = "" ;
            foreach ( $arr as $field => $value )
                    $sql .= $field . "=".$this->_quote ( $value ).", " ;
            $sql = substr ( $sql, 0, strlen ($sql) - 2 ) ;
            return $sql ;
    }

    function _fieldNameValuesAnd ( $arr ) {
            $sql = "" ;
            foreach ( $arr as $field => $value )
                    $sql .= $field . "=".$this->_quote ( $value )." AND " ;
            $sql = substr ( $sql, 0, strlen ($sql) - 5 ) ;
            return $sql ;
    }

    function _fieldNameValuesOr ( $arr, $reverse = false ) {
            $sql = "" ;
            if ( $reverse ) {
                    foreach ( $arr as $field => $value )
                            $sql .= $field . "=".$this->_quote ( $value )." OR " ;
            } else {
                    foreach ( $arr as $value => $field )
                            $sql .= $field . "=".$this->_quote ( $value )." OR " ;
            }
            $sql = substr ( $sql, 0, strlen ($sql) - 4 ) ;
            return $sql ;
    }

    function _removeFields ( $arr, $who ) {
        $res = array () ;
        foreach ( $arr as $idx => $val ) {
            $keep = true ;
            foreach ( $who as $i => $v )
                if ( $idx == $i )
                    $keep = false ;
            if ( $keep )
                $res[$idx] = $val ;
        }
        return $res ;
    }

    function _getProperties ( $arr, $clear = false, $prepend = 'p:' ) {

        $res = array () ;
        $s = strlen ( $prepend ) ;

        foreach ( $arr as $idx=>$val )
            if ( substr ( $idx, 0, $s ) == $prepend ) {
                if ( $clear )
                    $res[substr ( $idx, $s )] = $val ;
                else
                    $res[$idx] = $val ;
            }
        return $res ;
    }



}

