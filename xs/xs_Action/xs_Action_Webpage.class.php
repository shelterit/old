<?php
	
    class xs_Action_Webpage extends xs_Action {

        private $props = array () ;
        private $access = null ;

        function __construct () {

            // Go to parents constructor first, making this a xs_Action class
            parent::__construct() ;

            // Set default style
            $this->set_style ( $this->__if_set ( $this->glob->config['website']['style'], 'smoothness' ) ) ;

        }
        
        function ___register_functionality () {
        }
        

    }
