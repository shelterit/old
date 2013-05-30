<?php

    require_once dirname(__FILE__) . '/xs_Session.class.php' ;

    abstract class test_xs_Session extends UnitTestCase { }

    class testSessionMain extends test_xs_Session {

        function testInstantiateBlank () {

            $x = new xs_Session () ;

            $this->assertTrue ( $x ) ;

            $x = null ;

            $this->assertFalse ( $x ) ;
        }

    }

    class testSessionProperties extends test_xs_Session {

        function testEasy () {


            $x = new xs_Session() ;

            $_SESSION['test'] = 'Phillip' ;

            $name = $x->test ;
            
            $this->assertEqual( $name, 'Phillip' ) ;

        }

    }

