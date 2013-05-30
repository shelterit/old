<?php

	class xs_Xslt {

            public $input_xml = '' ;
            public $output_xml = '' ;
            private $config = array (
                    'profiling' => 'false',
                    'param' => array ( 'param.mode.content' => 'normal' ),
            ) ;

            // Constructor.
            function __construct ( $config = array () ) {


            }

            function action () {
                try {

                        $doc = new DOMDocument() ;
                        $xsl = new XSLTProcessor() ;

                        $doc->load ( "application/view/xslt/convert/".$convert_file ) ;
                        $xsl->importStyleSheet ( $doc ) ;
                        $doc->loadXML ( $this->output_xml ) ;

                        $this->output_xml = $xsl->transformToXML ( $doc ) ;

                } catch ( Exception $ex ) {
                        echo "Ouch! : " ; print_r ( $ex ) ;
                }
            }
        }
?>