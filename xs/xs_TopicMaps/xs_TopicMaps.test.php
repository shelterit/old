<?php

    require_once dirname(__FILE__) . '/xs_TopicMaps.class.php' ;
    require_once dirname(__FILE__) . '/xs_TopicMaps.TMAPI2.class.php' ;
    require_once dirname(__FILE__) . '/xs_TopicMaps.display.class.php' ;

    abstract class test_xs_TopicMaps extends UnitTestCase { }

    class testMain extends test_xs_TopicMaps {

        function testInstantiateBlank () {

            $tm = new xs_TopicMaps() ;

            $this->assertTrue ( $tm ) ;

            $tm = null ;

            $this->assertFalse ( $tm ) ;
        }

    }

    class testTopics extends test_xs_TopicMaps {

        public $tm = null ;

        function testEasy () {


            $tm = new xs_TopicMaps() ;

            // Create three users
            $tm->topic ( 'u02', 'Bob', 'user' ) ;
            $tm->topic ( 'u03', 'Phillip', 'user' ) ;
            $tm->topic ( 'u04', 'Jane', 'user' ) ;

            $total = $tm->get_topics() ;

            $this->assertEqual( count ( $total ), 3 ) ;
            $this->assertNotEqual( count ( $total ), 2 ) ;
            $this->assertNotEqual( count ( $total ), 4 ) ;

            // get a user
            $user = $tm->get_topic_by_id ( 'u03' ) ;
            $name = $user['u03']['name'][0] ;

            $this->assertEqual( $name, 'Phillip' ) ;

        }

        function testMap () {

            $this->tm = new xs_TopicMaps() ;

            $this->tm->topic ( 'user', 'User' ) ;
            $this->tm->topic ( 'person', 'Person' ) ;
            $this->tm->topic ( 'gender', 'Gender' ) ;
            $this->tm->topic ( 'male', 'Male', 'gender' ) ;
            $this->tm->topic ( 'female', 'Female', 'gender' ) ;

            $this->tm->topic ( 'u02', 'Bob', 'user' ) ;
            $this->tm->topic ( 'u03', 'Phillip', array ( 'user','person') ) ;
            $this->tm->topic ( 'u04', 'Jane', array ( 'user', 'female' ) ) ;

            // get a user
            $users = $this->tm->get_topic_by_type ( 'female' ) ;
            // $first = $users[0] ;

            // echo "<pre>" ; print_r ( $users ) ; echo "</pre>" ;
            
            // $this->assertEqual( count ( $total ), 3 ) ;

        }

        function testOperaMap () {

            $tm      = new xs_TopicMaps () ;

            $tm->import_xtm ( dirname(__FILE__) . '/maps/opera.xtm.xml' ) ;

            $display = new xs_TopicMaps_Display ( $tm ) ;


            /*
            $verdi = $tm->get_topic_by_id ( 'verdi' ) ;
            echo "<pre>" ; print_r ( $verdi ) ; echo "</pre>" ;

            $composer = $tm->get_topic_by_id ( 'composer' ) ;
            echo "<pre>" ; print_r ( $composer ) ; echo "</pre>" ;

            $composers = $tm->get_topic_by_type ( 'composer' ) ;
            echo "<pre>" ; print_r ( $composers ) ; echo "</pre>" ;
            */

            $find = $tm->get_topic_by_type ( 'composer' ) ;
            $expanded = $tm->expand ( $find, FULL ) ;

            $display->out ( $expanded ) ;

            // asort ( $composers ) ;
            
            // echo "<pre>" ; print_r ( $ont ) ; echo "</pre>" ;

            
            // echo "<pre>" ; print_r ( $composers ) ; echo "</pre>" ;


        }

        function testTMAPI2 () {

            $api = new xs_TopicMaps_TMAPI2 ( $this->tm ) ;

            $this->assertNotEqual( $api, null ) ;

        }

    }

