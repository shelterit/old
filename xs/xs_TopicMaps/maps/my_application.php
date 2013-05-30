<?php

    $id_counter = 0 ;

    function topicTemplate () {
        return array (
            'id'         => '',
            'name'       => nameTemplate(),
            'psi'        => array (),
            'type'       => array (),
            'occurrence' => array (),
            'property'   => array (),
        ) ;
    }

    function nameTemplate () {
        return array ( 'default' => '' ) ;
    }

    function generateTopics ( $num = 300 ) {
        global $id_counter ;

        $topics = array() ;

        for ( $n=0; $n<$num; $n++) {
            $topics[] = topicTemplate () ;

            $t = &$topics[$n] ;

            $t['id'] = $id_counter++ ;
            $t['name']['default'] = 'namevalue:'.rand(1,400000) ;
            for ( $m=0; $m<rand(1,6); $m++) $t['name'][rand(1,400000)] = 'namevalue:'.rand(1,400000) ;
            for ( $m=0; $m<rand(1,3); $m++) $t['type'][$m] = rand(1,400000) ;
            for ( $m=0; $m<rand(1,3); $m++) $t['occurrence'][rand(1,400000)] = 'value:'.rand(1,400000) ;
            for ( $m=0; $m<rand(1,99); $m++) $t['property'][rand(1,400000)] = 'value:'.rand(1,400000) ;

        }

        return $topics ;
    }

    // Wide definitions
    tm_topic ( 'my_application', array ( 'My application', 'nickname' => 'bingo', 'description' => 'User Management Module' ), 'fstl:application' ) ;

    // My application's actions
    tm_topic ( 'action_email', 'Send email', 'fstl:action' ) ;

    // How my application use technology
    tm_assoc ( 'uses', NO_ID, array (
        array ( 'my_application',  'fstl:application' ),
        array ( 'technology_snmp', 'fstl:connector' ),
        array ( 'action_email',    'fstl:action' )
    ) ) ;

    tm_assoc ( 'tests', NO_ID, array (
        array ( 'my_application',  'tested' ),
        array ( 'testing_framework', 'tester' )
    ) ) ;

    // tm_topic ( 'testing_framework', 'My own testing framework' ) ;

    tm_type ( 'user', 'User' ) ;

    tm_topic ( 'u01', array ( '' => 'Alexander', 'shortname' => 'Alex', 'nickname' => 'shelterit' ), 'user',
        array ( 'website' => 'http://shelter.nu/' , 'birthdate' => '03061971' )
    ) ;
    tm_topic ( 'u02', 'Bob', 'user' ) ;
    tm_topic ( 'u03', 'Phillip', 'user' ) ;
    tm_topic ( 'u04', 'Jane', 'user' ) ;

    foreach ( generateTopics ( 0 ) as $id => $topic )
        tm_topic ( $topic['id'], $topic['name'], $topic['type'], $topic['occurrence'] ) ;
