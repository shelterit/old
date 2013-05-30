<?php
    /*
     * xSiteable RESTful Topic Maps PHP framework for happy application
     * development, sporting a funky xSLT templating framework, and using
     * the spiffy HTML5 Boilerplate (http://html5boilerplate.com/) templates.
     *
     */

     define ( 'XS_ROOT_ID', '---' ) ;

     define ( 'XS_PAGE_DB_IDENTIFIER', 'core-content-page' ) ;
    
     define ( 'XS_DATE', "Y-m-d H:i:s" ) ;

     // Start timing of, well, everything!
     $xs_profiling_start = microtime ( true ) ;

     // Include the core class
     require_once ( __DIR__.'/xs/core.class.php' ) ;

     // Include other basic functions not done better in classes yet
     require_once ( __DIR__.'/xs/core.functions.php' ) ;

     // register our autoloader
     spl_autoload_register ( array ( new xs_Autoloader(), 'autoload' ) ) ;
     
     // Initialize file paths and the like
     xs_Core::static_setup () ;

     // Create a global registry / property object
     xs_Core::$glob = new xs_Properties() ;
     xs_Core::$glob_type = new xs_Properties() ;

     // Create a logger for performance and stuff
     xs_Core::$glob->log = new xs_Profiler ( $xs_profiling_start ) ;

     xs_Core::$glob->log->add ( 'Start including all framework files' ) ;

     xs_Core::$glob->log->add ( 'Includes done' ) ;


     // Yes, we use sessions. So sue me.
     session_start() ;

