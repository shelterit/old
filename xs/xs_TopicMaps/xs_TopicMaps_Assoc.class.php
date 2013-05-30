<?php

class xs_TopicMaps_Assoc extends xs_Properties {

    public $id = null ;
    public $type = null ;
    public $type_label = null ;
    
    public $members = array () ;
 

    function __construct ( $incoming = false ) {
        parent::__construct();
        if ( $incoming)
            $this->inject ( $incoming) ;
    }
    
    function has_other_members_than_id ( $id ) {
        foreach ( $this->members as $idx => $member )
            if ( $member['topic'] != $id )
                return true ;
         return false ;
    }
    
    function get_members ( $type = null ) {
        
        if ( $type == null )
            return $this->members ;
        
        $ret = array () ;
        
        foreach ( $this->members as $idx => $member )
            if ( $member['role'] == $type )
                $ret[$member['role']] = $member['topic'] ;
        
        return $ret ;
    }
    
    function member_resolve ( ) {
        
        $check = $this->members ;
        
        if ( isset ( $this->type ) ) $check[$this->type] = $this->type ;
        
        foreach ( $this->members as $idx => $member )
            $check[$member['role']] = $member['role'] ;
        
        $lut = $this->glob->tm->lookup_topics ( $check ) ;

        foreach ( $this->members as $idx => $member ) {
            
            if ( isset ( $lut[$member['role']] ) )
                $this->members[$idx]['role_label'] = $lut[$member['role']]['label'] ;
            
            if ( isset ( $lut[$idx] ) )
                $this->members[$idx]['label'] = $lut[$idx]['label'] ;
        }
        
        if ( isset ( $this->type ) && isset ( $lut[$this->type] ) ) {
            $this->type_label = $lut[$this->type]['label'] ;
        }
    }
    
    function piece_inject ( $arr ) {
        if ( isset ( $arr['type'] ) ) {
            $this->type = $arr['type'] ;
        }

        if ( $this->type == false && isset ( $arr['type1'] ) )
            $this->type = $arr['type1'] ;

        if ( $this->id == false && isset ( $arr['id'] ) )
            $this->id = $arr['id'] ;

        if ( isset ( $arr['topic'] ) && isset ( $arr['role'] )) {
            if ( ! isset ( $arr['label'] ) )
                $arr['label'] = '' ;
            $this->members[$arr['topic']] = array (
                'role' => $arr['role'],
                'topic' => $arr['topic'],
                'label' => $arr['label']
            ) ;
        }
        
        if ( isset ( $arr['members'] ) && is_array ( $arr['members'] ) ) {
            foreach ( $arr['members'] as $member ) {
                if ( isset ( $member['role'] ) && isset ( $member['topic'] ) ) {
                    $t = trim ( $member['topic'] ) ;
                    $this->members[$t]['role'] = $member['role'] ;
                    $this->members[$t]['label'] = $member['label'] ;
                }
            }
        }
    }

    function inject ( $arr = array () ) {
        
        if ( isset ( $arr[0]['id'] ) ) {
// echo "*" ;
            foreach ( $arr as $idx => $item ) {
    // echo "[$idx]" ;
                $this->piece_inject ( $item ) ;
            }
            
        } else
            
            $this->piece_inject ( $arr ) ;
    }

    function __get_array () {
        return array (
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'members' => $this->members
        ) ;
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

