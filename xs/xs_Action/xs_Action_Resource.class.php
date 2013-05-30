<?php

    /*
     * This class makes inherited classes an Action class, meaning it's a class
     * now that not only reacts to incoming data, but performs actions to it
     *
     */

    class xs_Action_Resource extends xs_Action {

        function __construct () {

            // Go to parents constructor first, making this a xs_EventStack_Plugin class
            parent::__construct() ;

            // Every resource gets an API resource matching them
            // $this->_register_resource ( XS_WIDGET, '_api/resources/' . $this->widget_name ) ;
        }

    }
	