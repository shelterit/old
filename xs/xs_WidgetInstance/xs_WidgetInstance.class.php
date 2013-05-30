<?php

    class  xs_WidgetInstance extends xs_Properties {

        public $id = null ;
        public $s = null ;
        public $_s = null ;
        public $p = null ;
        public $_p = null ;
        public $o = null ;
        public $_o = null ;

        public $view = null ;

        function __construct ( $id ) {
            parent::__construct() ;
            $this->id = $id ;
            $this->s  = new xs_Properties () ;
            $this->p  = new xs_Properties () ;
            $this->o  = new xs_Properties () ;
            $this->_s  = new xs_Properties () ;
            $this->_p  = new xs_Properties () ;
            $this->_o  = new xs_Properties () ;
        }

    }

