<?php

    class xs_TopicMaps_TMAPI2 {

        private $tm = null ;

	function __construct ( $tm = null ) {

            // Inject or prepare TM engine
            $this->tm = $tm ;

	}


        // Returns the topics which are an instance of the specified type.
        function getTopics ( $type, $matchAll = false ) {
        }

        // Returns the topics in topic map which are used as type in an "type-instance"-relationship.
        function getTopicTypes () {
        }

        // Returns the associations in the topic map whose type property equals type.
        function getAssociations ( $type ) {
        }

        // Returns the topics in the topic map used in the type property of Associations.
        function getAssociationTypes () {
        }

        // Returns the topic names in the topic map whose type property equals type.
        function getNames ( $type ) {
        }

        // Returns the topics in the topic map used in the type property of Names.
        function getNameTypes () {
        }

        // Returns the occurrences in the topic map whose type property equals type.
        function getOccurrences ( $type ) {
        }

        // Returns the topics in the topic map used in the type property of Occurrences.
        function getOccurrenceTypes () {
        }

        // Returns the roles in the topic map whose type property equals type.
        function getRoles ( $type ) {
        }

        // Returns the topics in the topic map used in the type property of Roles.
        function getRoleTypes () {
        }


    }