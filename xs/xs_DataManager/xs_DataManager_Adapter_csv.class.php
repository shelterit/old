<?php

    class xs_DataManager_Adapter_csv extends xs_DataManager_Adapter {

        function __construct ( $config = array () ) {
            parent::__construct ( $config ) ;
        }

        function query ( $query ) {
            // echo "!!!!" ;
		return array ( array (
                    array ( 'test' => 'My test' ),
                    array ( 'test' => 'My other test' ) ),
                ) ;
        }

        function fetch_all ( $query ) {
            // echo "@@@@@@" ;
		return array ( array (
                    array ( 'test' => 'My test' ),
                    array ( 'test' => 'My other test' ) ),
                ) ;
        }

    }
