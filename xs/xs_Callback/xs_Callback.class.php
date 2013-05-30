<?php

    class xs_Callback extends xs_Properties {

        private $origin = null ;
        private $data = null ;

        function __construct ( $data = null, $origin = null ) {
            parent::__construct () ;
            if ( $data )
                $this->data = $data ;
        }

        function set_origin ( $it ) {
            $this->origin = $it ;
        }

        function get_origin () {
            return $this->origin ;
        }

        function data () {
            return $this->data ;
        }

    }