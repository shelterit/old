<?php

class xs_Output extends xs_Core {

    public $input_xml = '' ;
    public $output_xml = '' ;
    private $data = null ;
    private $template = '' ;

    private $config = array (
            'profiling' => 'false',
            'param' => array ( 'param.mode.content' => 'normal' ),
            ) ;

    function __construct ( $input_xml = '', $data = null, $template = '' ) {
        $this->input_xml = $input_xml ;
        $this->data = $data ;
        $this->template = $template ;
    }

    function inject_data ( $data ) {
        $this->data = $data ;
    }

    function get () {
        return $this->output_xml ;
    }

    function action ( $output_mode = 'xhtml' ) {

        // echo "!!!" ;
        
        $this->glob->log->add ( "Render: Action : output_mode = [$output_mode] " ) ;
// debug($output_mode);
        switch ( $output_mode ) {
            case 'xml' :
                header ( 'Content-type: application/xml' ) ;
                // echo '!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! [xml]' ;

                if ( $this->input_xml == null ) {

                    // Generate response XML from the application stack
                    $response = new xs_XmlResponse ( $this->data ) ;

                    // Get some XML goodness!
                    echo $response->get() ;

                } else

                    echo $this->input_xml ;

                break ;
            case 'json' :
                header('Cache-Control: no-cache, must-revalidate');
                header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                header('Content-type: application/json');
                echo json_encode ( $this->data ) ;
                break ;
                
            case 'csv' :
                header ( 'Content-type: text/plain' ) ;
                echo generate_csv_data ( $this->data ) ;
                break ;

            case 'excel' :
                // file name for download
                $filename = "export_" . date('Ymd') . ".xls";

                header('Cache-Control: no-cache, must-revalidate');
                header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

                header("Content-Disposition: attachment; filename=\"$filename\"");
                header("Content-Type: application/vnd.ms-excel");

                

                echo generate_csv_data ( $this->data, false, "\t" ) ;
                break ;

            case 'content-xml' :
                $this->config['param']['param.mode.content'] = 'find' ;
                $this->render () ;
                $this->convert ( 'clean_content.xsl' ) ;
                break ;
            case 'content-widget' :
                header('Content-type: text/html');
                $this->config['param']['param.mode.content'] = 'widget' ;
                // $this->config['param']['param.widget_id'] = $this->template ;
                $this->render () ;
                echo $this->output_xml ;
                // $this->convert ( 'clean_content.xsl' ) ;
                break ;
            case 'blank' :
            // header('Content-type: application/xml');
                $this->config['param']['param.mode.content'] = 'blank' ;
                $this->render () ;
                echo $this->output_xml ;
                break ;
            case 'txt' :
            case 'text' :
                header('Content-type: text/plain');
                $this->render () ;
                echo $this->output_xml ;
                break ;
            case 'css' :
                header('Content-type: text/css');
                $this->config['param']['param.mode.content'] = 'css' ;
                $this->render () ;
                echo $this->output_xml ;
                break ;
            case '301' :
                header('Location: '.$this->input_xml );
                exit ;
                break ;
            case '301-blank' :
                header('Location: '.$this->input_xml."?output=blank" );
                exit ;
                break ;
            case 'xhtml' :
            default :
                header('Content-type: text/html');
                $this->render () ;
                echo $this->output_xml ;
                break ;
        }
        $this->glob->log->add ( "Render: Action end" ) ;
    }

    function convert ( $convert_file ) {

        try {

            $doc = new DOMDocument() ;
            $xsl = new XSLTProcessor() ;

            $doc->load ( "application/templates/xslt/convert/".$convert_file ) ;
            $xsl->importStyleSheet ( $doc ) ;
            $doc->loadXML ( $this->output_xml ) ;

            $this->output_xml = $xsl->transformToXML ( $doc ) ;

        } catch ( Exception $ex ) {
            echo "Ouch! : " ;
            print_r ( $ex ) ;
        }

    }

    function render () {

        $this->glob->log->add ( "Render: begin" ) ;

        $start = microtime ( true ) ;

        // Our input XML stack as a DOM object
        try {
            $stack = new DOMDocument() ;
            $stack->loadXML ( $this->input_xml, LIBXML_COMPACT ) ;
            $this->glob->log->add ( "Render: XML OK" ) ;
        } catch ( exception $ex ) {
            $this->glob->log->add ( "Render: XML failed. Not well-formed?" ) ;
        }

        // The XSLT stylesheet as a DOM object
        try {
            $xsl = new DOMDocument() ;
            $xsl->load ( "./application/templates/framework.xsl", LIBXML_COMPACT ) ;
            $this->glob->log->add ( "Render: XSLT files load OK" ) ;
        } catch ( exception $ex ) {
            $this->glob->log->add ( "Render: XSLT files load failed. Not well-formed?" ) ;
        }

        // Create an xSLT processor
        try {
            $proc = new XSLTProcessor() ;
            $proc->importStyleSheet ( $xsl ) ;
            $this->glob->log->add ( "Render: XSLT import" ) ;
            $proc->setParameter ( '', $this->config['param'] ) ;
            $proc->registerPHPFunctions() ;
            $this->glob->log->add ( "Render: registration and parameters OK" ) ;
        } catch ( exception $ex ) {
            var_dump ( $ex ) ;
            $this->glob->log->add ( "Render: XSLT processor setup and parameters FAILED. Huh?" ) ;
        }

        $res = '' ;

        // Transform the XSLT
        try {
            $res = $proc->transformToXML ( $stack ) ;
            $this->glob->log->add ( "Render: Transform OK" ) ;
        } catch ( exception $ex ) {
            var_dump ( $ex ) ;
            $this->glob->log->add ( "Render: XSLT transform FAILED. Hoo-boy." ) ;
        }

        // Replace a few things
        $r = str_replace ( "<![CDATA[", "\n", $res ) ;
        $r = str_replace ( "]]>", "\n", $res ) ;

        $r = str_replace ( "tmt1:", "tmt:", $r ) ;
        $r = str_replace ( ":tmt1=", ":tmt=", $r ) ;

        $ti = round ( microtime ( true ) - $start, 4 );

        $this->glob->log->add ( "Render: Done (".sprintf("%01.3f", $ti).")" ) ;

        $debug = $this->glob->request->__fetch ( '_debug', 'false' ) ;
        if ( $debug == 'true' )
            $r = str_replace ( "</body>", "<div id='debuglog'><pre>".htmlentities($this->glob->log->reportXML())."</pre></div> <span id='debugx' class='xs-debugger-button' onclick=\"$('#debuglog').show();$('#debugx').hide();$('#debugi').show()\"> debug </span> <span id='debugi' onclick=\"$('#debuglog').hide();$('#debugx').show();$('#debugi').hide()\" class='xs-debugger-button'> hide debug </span> </body>", $r ) ;
 
        $this->output_xml = "<!doctype html>\n".$r ;

        $this->glob->log->add ( "Render: Replace OK" ) ;
        
    }

}


function cleanData(&$str)
  {
    if($str == 't') $str = 'TRUE';
    if($str == 'f') $str = 'FALSE';
    if(preg_match("/^0/", $str) || preg_match("/^\+?\d{8,}$/", $str) || preg_match("/^\d{4}.\d{1,2}.\d{1,2}/", $str)) {
      $str = "'$str";
    }
    if(strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"';
    $str = mb_convert_encoding($str, 'UTF-16LE', 'UTF-8');
  }
