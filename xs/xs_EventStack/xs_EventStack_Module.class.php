<?php

    /*
     * This class gives inherited classes the ability to work against all the
     * goodness of the xs_EventStack, like registering, looking up and define
     * themselves as different kinds of plugins.
     *
     */

    class xs_EventStack_Module extends xs_EventStack_Plugin {

        function __construct () {

            // Go to parents constructor first, making this a xs_Core class
            parent::__construct() ;

            // Every module gets an API resource matching the class name
            $this->_register_resource ( XS_MODULE, '_api/modules/' . $this->_meta->name ) ;
            
        }


    }
