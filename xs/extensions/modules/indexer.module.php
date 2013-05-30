<?php

    // A service that drives all manners of search and retreival

    class xs_module_indexer extends xs_EventStack_Module {

        public $meta = array (
            'name' => 'Indexer module',
            'description' => 'A generic indexer and search engine',
            'version' => '1.0',
            'author' => 'Alexander Johannesen',
            'author_link' => 'http://shelter.nu/',
            'editable_options' => true,
        ) ;

        function ___modules () {

            // Define the main events we control and can trigger
            $this->_register_event ( XS_MODULE, 'on_indexer_create' ) ;
            $this->_register_event ( XS_MODULE, 'on_indexer_read' ) ;
            $this->_register_event ( XS_MODULE, 'on_indexer_update' ) ;
            $this->_register_event ( XS_MODULE, 'on_indexer_delete' ) ;
        }

        function ___logic () {

            // $this->_fire_event ( 'on_indexer_create' ) ;
/*
            $res = $this->_get_resource ( '_api/modules/indexer' ) ;

            $msg = $res->_post ( array (
                'uri' => 'news/1003'
            ) ) ;

            echo "[$msg]" ;
*/
        }

        function ___on_indexer_create () {
            // echo "On indexer CREATE" ;
        }

        function callback___on_indexer_create ( $arr = array () ) {

            // echo "On indexer CREATE callback" ;

            // adds a document to the indexer

            // we need an identifier for the document (unique)
            if ( isset ( $arr['uri'] ) ) {

            }
        }


        function _post ( $input = array () ) {

            // we need an unique identifier for the item to be indexed
            $id = null ;

            if ( isset ( $input['uri'] ) )
                $id = $input['uri'] ;

            if ( ! $id )
                return 406 ;

            
        }

    }
