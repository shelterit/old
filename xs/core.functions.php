<?php

    /* NOTICE : This file of various useful functions should be - little by
     * little - removed, folded into better objects where all this mish-
     * mash wouldn't be necessary. I'm sorry, I was in a hurry.
     */

function debug ( $data, $title = null ) {
    if ( $title ) 
        echo "<div style='float:right;margin-top:2px;margin-right:6px;margin-left:18px;padding:1px 3px;background-color:#deffde;font-size:0.7em;color:#333;'>$title</div>" ;
    echo '<pre style="clar:both;padding:4px;margin:4px;border:dotted 1px #999;">' ;
    var_dump ( $data ) ;
    echo '</pre>' ;
}

function debug_r ( $data, $title = null ) {
    if ( $title ) 
        echo "<div style='float:right;margin-top:2px;margin-right:6px;margin-left:18px;padding:1px 3px;background-color:#deffde;font-size:0.7em;color:#333;'>$title</div>" ;
    echo '<pre style="clar:both;padding:4px;margin:4px;border:dotted 1px #999;">' ;
    print_r ( $data ) ;
    echo '</pre>' ;
}

function human_filesize($bytes, $decimals = 2) {
  $sz = 'BKMGTP';
  $factor = floor((strlen($bytes) - 1) / 3);
  $extra = '' ;
  if ( $factor !== 0 )
      $extra = @$sz[$factor] ;
  return sprintf("%.{$decimals}f ", $bytes / pow(1024, $factor)) . $extra . 'b' ;
}

function create_image_wrapper ( $href, $filename ) {
    $data = "<html><head><title>Image</title></head><body><img src='{$href}' alt='' /></body></html>" ;
    file_put_contents ( $filename, $data ) ;
    echo "[[wrote: {$filename} ]]" ;
}

function stream_copy($src, $dest) 
{ 
    $fsrc = fopen($src,'r'); 
    $fdest = fopen($dest,'w+'); 
    $len = stream_copy_to_stream($fsrc,$fdest); 
    fclose($fsrc); 
    fclose($fdest); 
    return $len; 
} 

function debugPrintCallingFunction () { 
    $file = 'n/a'; 
    $func = 'n/a'; 
    $line = 'n/a'; 
    $debugTrace = debug_backtrace(); 
    if (isset($debugTrace[1])) { 
        $file = $debugTrace[1]['file'] ? $debugTrace[1]['file'] : 'n/a'; 
        $line = $debugTrace[1]['line'] ? $debugTrace[1]['line'] : 'n/a'; 
    } 
    if (isset($debugTrace[2])) $func = $debugTrace[2]['function'] ? $debugTrace[2]['function'] : 'n/a'; 
    echo "<pre>\n$file, $func, $line\n</pre>"; 
} 

    // This function is used for human dates through the XSLT framework
    function timed ( $date ) {
        global $xs_stack ;
        return $xs_stack->glob->human_dates->get ( $date ) ;
    }    


    if (!function_exists('getallheaders'))
    {
        function getallheaders()
        {
           foreach ($_SERVER as $name => $value)
           {
               if (substr($name, 0, 5) == 'HTTP_')
               {
                   $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
               }
           }
           return $headers;
        }
    }
    
    
    
    // TODO : derive all widget functions into wrappers and a class
    

    function fetch_widget_setup_xml ( $name ) {
        global $xs_stack ;

        if ( is_array ( $name ) )
            $name = $name[0] ;

        if (is_object($name))
            $name = $name->nodeValue ;
        
        if ( trim ( $name ) == '' )
            $name = $xs_stack->glob->request->_widget_name ;
        
        $widget = $xs_stack->get ( $name ) ;

        $src = $widget->gui_setup () ;

        $s = simplexml_load_string ( $src ) ;
        $z = dom_import_simplexml ( $s ) ;

        return $z ;

    }
    
    
function security_user_check_function ( $func ) {
    global $xs_stack ;
    if ( is_array ( $func ) )
        $func = $func[0] ;
    if (is_object($func))
        $func = $func->value ;
    return $xs_stack->glob->user->isAllowed ( $func ) ;
}

function security_user_check_lookup ( $group ) {
    global $xs_stack ;

    if ( is_array ( $group ) )
        $group = $group[0] ;

    if (is_object($group))
        $group = $group->value ;

    $items = explode ( '|', $group ) ;
    $func = 'group' ;

    foreach ( $items as $item ) {
        $newfunc = explode ( '=', $item ) ;
        $val = $item ;
        if ( isset ( $newfunc[1] ) ) {
            $func = $newfunc[0] ;
            $val = $newfunc[1] ;
        }
        $res = false ;
        switch ( $func ) {
            case 'group': $res = $xs_stack->glob->user->inGroup ( $val ) ; break ;
            case 'role' : $res = $xs_stack->glob->user->isRole ( $val ) ; break ;
            case 'user' : $res = $xs_stack->glob->user->isUser ( $val ) ; break ;
        }
        // debug ( array ( $func, $val, $res ), 'core:auth' ) ;
        if ( $res == true )
            return true ;
    }
    return false ;
    
    //xs_Core::$glob->log->add ( "User: group_lookup [$group]" ) ;
    //$w = $xs_stack->glob->user->inGroup ( $group ) ;

    //return $w ;
}

function security_group_lookup ( $group ) {
    global $xs_stack ;

    if ( is_array ( $group ) )
        $group = $group[0] ;

    if (is_object($group))
        $group = $group->value ;

    xs_Core::$glob->log->add ( "User: group_lookup [$group]" ) ;
    $w = $xs_stack->glob->user->inGroup ( $group ) ;

    return $w ;
}

function user_lookup ( $user ) {
    global $xs_stack ;

    if ( is_array ( $user ) )
        $user = $user[0] ;

    if (is_object($user))
        $user = $user->value ;

    xs_Core::$glob->log->add ( "User: user_lookup [$user]" ) ;
    $w = $xs_stack->glob->user->isUser ( $user ) ;

    return $w ;
}

function widget_security ( $widget, $func ) {
    global $xs_stack ;

    $widget = $xs_stack->get ( $widget ) ;
}

function widget ( $widget = 'not given', $what = 'no what', $name = null, $param = null ) {
    global $xs_stack ;

    // debug_r ( array ( $widget, $what, $name, $param ) ) ;
    
    xs_Core::$glob->log->add ( "core.func.widget: [$widget][$what][$name]" ) ;

    $widget = $xs_stack->get ( $widget ) ;

    $p = '' ;
    if ( $widget == null )
        $p = '<span />' ;
    else {
        return $widget->$what($name, $param) ;
    }
    $s = simplexml_load_string ( "<span>$p</span>" ) ;
    $z = dom_import_simplexml ( $s ) ;

    xs_Core::$glob->log->add ( "core.func.widget: done" ) ;

    return $z ;
}

function widget_properties ( $widget ) {
    global $xs_stack ;
    xs_Core::$glob->log->add ( "core.func.widget_properties: [$widget]" ) ;
    $instance = $xs_stack->get ( $widget ) ;
    return _dom_array ( $instance->_properties->__getArray () ) ;
}

function widget_settings ( $widget ) {
    global $xs_stack ;
    xs_Core::$glob->log->add ( "core.func.widget_settings: [$widget]" ) ;
    $instance = $xs_stack->get ( $widget ) ;
    return _dom_array ( $instance->_settings->__getArray () ) ;
}

function widget_property ( $widget, $property ) {
    global $xs_stack ;
    xs_Core::$glob->log->add ( "core.func.widget_property: [$widget][$property]" ) ;
    $instance = $xs_stack->get ( $widget ) ;
    return _dom_array_item ( $property, $instance->_properties->__getArray () ) ;
}

function widget_setting ( $widget, $setting ) {
    global $xs_stack ;
    xs_Core::$glob->log->add ( "core.func.widget_setting: [$widget][$setting]" ) ;
    $instance = $xs_stack->get ( $widget ) ;
    return _dom_array_item ( $setting, $instance->_settings->__getArray () ) ;
}



function _dom_array_item ( $id, $array = array () ) {
    if ( isset ( $array[$id] ) ) {
        $p = $array[$id] ;
        $s = simplexml_load_string ( "<span>$p</span>" ) ;
        return dom_import_simplexml ( $s ) ;
    }
    $s = simplexml_load_string ( "<span/>" ) ;
    return dom_import_simplexml ( $s ) ;
}

function _dom_array ( $array = array () ) {
    $ret = '<items>' ;
    if ( count ( $array ) > 0 ) {
        foreach ( $array as $key => $value ) {
            $ret .= "<item name='$key'>$value</item>" ;
        }
    } else
        $ret .= 'None.' ;
    $ret .= '</items>' ;
    $s = simplexml_load_string ( $ret ) ;
    return dom_import_simplexml ( $s ) ;
}




function plugin_get ( $plugin, $path ) {
    global $xs_stack ;
    
    xs_Core::$glob->log->add ( "core.func.plugin_get: [$plugin][$path]" ) ;
    $p = $xs_stack->get ( $plugin ) ;

    if ( is_object ( $p ) ) {

        $r = $p->_get_data ( $path ) ;
        
        $ret = new array2xml ( $r ) ;

        return $ret->get() ;

    } // else
        return "Plugin '$plugin' not found." ;
}

function gui_actions ( $event ) {
    global $xs_stack ;

    $l = '' ;

    if ( is_array ( $event ) ) {
        if ( isset ( $event[0] ) )
            $l = $event[0] ;
    }

    if ( is_object ( $l ) ) {
        if ( $l instanceof DOMAttr ) {
            $l = $l->value ;
        } else {
            $x = @simplexml_import_dom ( $l ) ;
            $l = (String) $x ;
        }
    }

    
    // echo "EVENT=($l) " ; // print_r ( $r ) ;

    // events->action returns an array with results from various
    // widgets/plugins that dumps content at the $event

    // echo " <hr/> !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! <hr/> $l = (" ;
    
    $r = $xs_stack->gui_action ( $l ) ;

    // var_dump ( $r ) ;
    
    $doc = new DOMDocument;
    // $doc->loadXml((string)$xml);
    // echo ") " ;

    $xml = "<span>" ;
    if ( is_array ( $r ) ) {
        foreach ( $r as $x ) {
            if ( is_array ( $x ) ) {
                foreach ( $x as $s ) {
                    if ( $s instanceof DOMElement ) {
                        $xml .= $s->normalize () ;
                        // var_dump ( $s->normalize() ) ;
                    } else
                        $xml .= (string) $s ;
                }
            } else
                $xml .= (string) $x ;
        }
    } else {
        $xml .= (string) $r ;
    }

    $xml .= "</span>" ;
    $xml = trim ( $xml ) ;

    //echo "<pre>$xml</pre>" ;
    $doc->loadXml((string)$xml);

    xs_Core::$glob->log->add ( "3. core.func.gui_actions: [$l] / END" ) ;
    return $doc;

}


function safe_set ( &$var, &$input ) {
    if ( isset ( $input ) ) {
        $var = $input ;
    } else {
        $var = '' ;
    }
}
function urldecode_wrap ( $inp ) {

    $l = '' ;

    if ( is_array ( $inp ) ) {

        if ( count ( $inp ) > 0 )
            $l = $inp[0] ;
        else
            return "" ;
    }

    if ( is_object ( $l ) ) {

        if ( $l instanceof DOMAttr ) {
            $l = $l->value ;
        } else {
            $x = @simplexml_import_dom ( $l ) ;
            $l = (String) $x ;
        }
    }

    return urldecode ( $l ) ;

}

function array_keyify ( $arr ) {
    $r = array () ;
    foreach ( $arr as $w ) $r[$w] = $w ;
    return $r ;
}

function array_insert(&$array, $key, $data)
{
    $k = key($array);

    if (array_key_exists($key, $array) === true)
    {
        $key = array_search($key, array_keys($array)) + 1;
        $array = array_slice($array, null, $key, true) + $data + array_slice($array, $key, null, true);

        while ($k != key($array))
        {
            next($array);
        }
    }
}
function recurse_array ( $source, $dest ) {
    if(count($source) == 0)
        return false ;
    $t = 'null' ;
    if ( isset ($source[0]) )
        $t = urlsafe($source[0]) ;
    $dest[$t] = @recurse_array ( array_slice($source, 1), $dest[$t] ) ;
    if ( $dest[$t] === false )
        unset ( $dest[$t] ) ;
    uksort( $dest, 'strnatcmp');
    // natsort ( $dest ) ;
    return $dest ;
}

function recurse_array_key_value ( $source ) {
    if(count($source) == 1){
        return '' ;
    }
    $dest = recurse_array_key_value ( array_slice($source, 1), $dest[$source[0]] ) ;

    return '<item name="'.urlsafe($source[0]).'" id="'.$source[0].'">'.$dest.'</item>' ;
}

function natsort2d( &$arrIn, $index = null )
{
    
    $arrTemp = array();
    $arrOut = array();
    
    foreach ( $arrIn as $key=>$value ) {
        
        reset($value);
        $arrTemp[$key] = is_null($index)
                            ? current($value)
                            : $value[$index];
    }
    
    natcasesort($arrTemp);
    
    foreach ( $arrTemp as $key=>$value ) {
        $arrOut[$key] = $arrIn[$key];
    }
    
    $arrIn = $arrOut;
    
}


function urlsafe ( $in ) {
    
    $ret = 0 ;
    $fin = array () ;
    
    if ( ! is_array ( $in ) ) {
        $in = array ( $in ) ;
    } else {
        $ret = 1 ;
    }
    
    foreach ( $in as $item ) {
        $res = '' ;
        $w = explode ( '/', $item ) ;
        foreach ( $w as $t => $q )
            $res .= urlencode ( $q ) . '/' ;
        $res = substr ( $res, 0, -1 ) ;
        $fin[] = $res ;
    }
    if ( $ret == 1 )
        return $fin ;
    return $res ;
}

function getURI()
{
    $uri = "";

    if (isset($_SERVER['PATH_INFO']))
        $uri = getValueFrom($_SERVER, 'PATH_INFO', @getenv('PATH_INFO'));
    elseif (isset($_SERVER['ORIG_PATH_INFO']))
        $uri = getValueFrom($_SERVER, 'ORIG_PATH_INFO', @getenv('ORIG_PATH_INFO'));
    else
        $uri =  "";

    $scriptname = basename($_SERVER['SCRIPT_FILENAME']);
    if (strpos($uri, $scriptname) > -1)
        $uri = substr($uri, strpos($uri, $scriptname) + strlen($scriptname), strlen($uri));

    return $uri;
}


function my_flush (){
    echo(str_repeat(' ',256));
    // check that buffer is actually set before flushing
    if (ob_get_length()){
        @ob_flush();
        @flush();
        @ob_end_flush();
    }
    @ob_start();
}

function slug ( $title ) {
    return ( strtolower ( str_replace ( " ", "-", preg_replace ( "/[^a-zA-Z0-9 ]/", "", $title ) ) ) ) ;
}

function label ( $label, $lang=null ) {

    // global $glob, $language ;

    $language = 'en' ; // $glob->breakdown->__get ( 'language', 'en' ) ;

    if ( $lang != null )
        $language = $lang ;

    // return print_r ( $label, true ) ;
    // echo print_r ( $language, true  ) ;

    $deflang = 'en' ; // $glob->breakdown->__get ( 'language', 'en' ) ;

    $lang = $language ;

    if ( is_array ( $language ) ) {
        $xx = @simplexml_import_dom ( $language[0] ) ;
        $lang = (String) $xx ;
    }

    if ( trim ($lang) === '' )
        $lang = $deflang ;

    $l = '' ;

    // echo "($lang)" ;

    if ( is_array ( $label ) ) {
        // $l .= 'A/' ;
        if ( count ( $label ) > 0 )
            $l = $label[0] ;
        else
            return "" ;
    } else
        $l = (string) $label ;
        
    if ( is_object ( $l ) ) {

        // $l .= 'O/' ;
        if ( $l instanceof DOMAttr ) {
            $l = $l->value ;
            // $l .= print_r ( $l, true ) ;
            // $l = "!!!" ;

        } else {
            $x = @simplexml_import_dom ( $l ) ;
            $l = (String) $x ;
        }
    } 
        


    $res = array() ;
    foreach ( explode ( '|', $l ) as $item ) {
        $i = explode ( ':', $item ) ;
        if ( isset ( $i[1] ) )
            $res[$i[0]] = $i[1] ;
    }

    // print_r ( $res ) ; print_r ( $lang ) ;

    if ( isset ( $res[$lang] ) )
        return $res[$lang] ;
    else
        return $l ;
}


function xs_gui ( $header, $content, $footer ) {
    return '<html>' . $header . $content . $footer . '</html>' ;
}
function xs_gui_header ( $title ) {
    return '<head><title>' . $title . '</title></head><body>' ;
}
function xs_gui_footer () {
    return '</body>' ;
}
function xs_gui_body () {
    $str = '' ;
    foreach(func_get_args() as $s)
        $str .= $s ;
    return $str ;
}
function xs_gui_section ( $title ) {
    return '<div><h3>' . $title . '</h3>' ;
}
function xs_gui_section_end () {
    return '</div>' ;
}
function xs_gui_choose ( $env_id, $what ) {

    // echo '<pre>';print_r ( $_REQUEST ) ;echo '</pre>' ;

    $str = "<form action='' method='post'><ul>";

    if ( isset ( $what["$env_id"] ) )
        $str .= "<li>This environment ($env_id) FOUND as'".$what["$env_id"]."'</li>" ;
    else
        $str .= "<li>This environment needs a name: <input type='hidden' name='set_env' value='$env_id'> <input type='radio' name='$env_id' id='$env_id' value='dev'>DEV</input>   <input type='radio' name='$env_id' id='$env_id' value='test'>TEST</input>   <input type='radio' name='$env_id' id='$env_id' value='prod'>PROD</input></li>" ;

    foreach ( $what as $id => $env ) {

        if ( $env_id == $id )
            $str .= "<li>*** $env: <input type='text' id='$env' value='$id'></li>" ;
        else
            $str .= "<li>$env: <input type='text' id='$env' value='$id'></li>" ;
        // if ( )
    }
    $str .= '</ul><input type="submit"></form>';
    return $str ;
}


function simplexml_append(SimpleXMLElement $parent, SimpleXMLElement $new_child) {
    $node1 = dom_import_simplexml($parent);
    $dom_sxe = dom_import_simplexml($new_child);
    $node2 = $node1->ownerDocument->importNode($dom_sxe, true);
    $node1->appendChild($node2);
}

function utf8_encode_mix($input, $encode_keys=false) {
    $result = "" ;
    if(is_array($input)) {
        $result = array();
        foreach($input as $k => $v) {
            $key = ($encode_keys)? utf8_encode($k) : $k;
            $result[$key] = utf8_encode_mix( $v, $encode_keys);
        }
    }
    else {
        $result = utf8_encode($input);
    }

    return $result;
}


function utf8_decode_mix($input, $encode_keys=false) {
    $result = "" ;
    if(is_array($input)) {
        $result = array();
        foreach($input as $k => $v) {
            $key = ($encode_keys)? utf8_encode($k) : $k;
            $result[$key] = utf8_decode_mix( $v, $encode_keys);
        }
    }
    else {
        $result = utf8_decode($input);
    }

    return $result;
}


function dircopy($srcdir, $dstdir, $offset=0, $verbose = false) {
    if(!isset($offset)) $offset=0;
    $num = 0;
    $fail = 0;
    $sizetotal = 0;
    $fifail = '';

    $ret = false ;

    if(!is_dir($dstdir)) mkdir($dstdir);
    if($curdir = opendir($srcdir)) {
        while($file = readdir($curdir)) {
            if($file != '.' && $file != '..') {
                $srcfile = $srcdir . '\\' . $file;
                $dstfile = $dstdir . '\\' . $file;
                if(is_file($srcfile)) {
                    if(is_file($dstfile)) $ow = filemtime($srcfile) - filemtime($dstfile); else $ow = 1;
                    if($ow > 0) {
                        if($verbose) echo "Copying '$srcfile' to '$dstfile'...";
                        if(copy($srcfile, $dstfile)) {
                            touch($dstfile, filemtime($srcfile));
                            $num++;
                            $sizetotal = ($sizetotal + filesize($dstfile));
                            if($verbose) echo "OK\n";
                        }
                        else {
                            echo "Error: File '$srcfile' could not be copied!\n";
                            $fail++;
                            $fifail = $fifail.$srcfile."|";
                        }
                    }
                }
                else if(is_dir($srcfile)) {
                    $res = explode(",",$ret);
                    $ret = dircopy($srcfile, $dstfile, $verbose);
                    $mod = explode(",",$ret);
                    $imp = array($res[0] + $mod[0],$mod[1] + $res[1],$mod[2] + $res[2],$mod[3].$res[3]);
                    $ret = implode(",",$imp);
                }
            }
        }
        closedir($curdir);
    }
    $red = explode(",",$ret);
    // $ret = ($num + $red[0]).",".(($fail-$offset) + $red[1]).",".($sizetotal + $red[2]).",".$fifail.$red[3];
    return $ret;
}


function min_mod () {
    $args = func_get_args();
    $min = false;

    if (!count($args[0]))
        return false;
    else {
        foreach ($args[0] AS $value) {
            if (is_numeric($value)) {
                $curval = floatval($value);
                if ($curval < $min || $min === false) $min = $curval;
            }
        }
    }

    return $min;
}

function is_numeric_regex($str) {
    $str    = "{$str}";

    if (in_array($str[0], array('-', '+')))    $str = "{$str[0]}0" . substr($str, 1);
    else $str = "0{$str}";

    $eng    = preg_match ("/^[+,-]{0,1}([0-9]+)(,[0-9][0-9][0-9])*([.][0-9]){0,1}([0-9]*)$/" , $str) == 1;
    $world    = preg_match ("/^[+,-]{0,1}([0-9]+)(.[0-9][0-9][0-9])*([,][0-9]){0,1}([0-9]*)$/" , $str) == 1;

    return ($eng or $world);
}


function generate_csv_data($data,$use_key=false,$delm=',') {
  $output = NULL;
  if(is_array($data)) {
    if($use_key == false) {
      if(isset($data[0]) && is_array($data[0])) {
        foreach($data as $key) {
          $output .= implode($delm,$key) . "\n";
        }
      } else {
          if ( is_array ( $data ) ) {
            foreach($data as $key)
                $output .= implode($delm,$key) . "\n";
          } else
            $output .= implode("$delm", $data)."\n";
      }
    } else {
      foreach($data as $key => $value) {
          if ( is_array ( $value ) ) {
            foreach($value as $k => $v)
                $output .= "$k{$delm}$v\n";
          } else
            $output .= "$key{$delm}$value\n";
      }
    }
  } else {
    $output = $data;
  }
  if(empty($output)) {
    trigger_error('OUTPUT WAS EMPTY!', E_USER_ERROR);
    return false;
  }
  return $output;
}



/**
 * @brief Generates a Universally Unique IDentifier, version 4.
 *
 * This function generates a truly random UUID. The built in CakePHP String::uuid() function
 * is not cryptographically secure. You should uses this function instead.
 *
 * @see http://tools.ietf.org/html/rfc4122#section-4.4
 * @see http://en.wikipedia.org/wiki/UUID
 * @return string A UUID, made up of 32 hex digits and 4 hyphens.
 */
function uuidSecure() {

    $pr_bits = null;
    $fp = @fopen('/dev/urandom','rb');
    if ($fp !== false) {
        $pr_bits .= @fread($fp, 16);
        @fclose($fp);
    } else {
        // If /dev/urandom isn't available (eg: in non-unix systems), use mt_rand().
        $pr_bits = "";
        for($cnt=0; $cnt < 16; $cnt++) {
            $pr_bits .= chr(mt_rand(0, 255));
        }
    }

    $time_low = bin2hex(substr($pr_bits,0, 4));
    $time_mid = bin2hex(substr($pr_bits,4, 2));
    $time_hi_and_version = bin2hex(substr($pr_bits,6, 2));
    $clock_seq_hi_and_reserved = bin2hex(substr($pr_bits,8, 2));
    $node = bin2hex(substr($pr_bits,10, 6));

    /**
     * Set the four most significant bits (bits 12 through 15) of the
     * time_hi_and_version field to the 4-bit version number from
     * Section 4.1.3.
     * @see http://tools.ietf.org/html/rfc4122#section-4.1.3
     */
    $time_hi_and_version = hexdec($time_hi_and_version);
    $time_hi_and_version = $time_hi_and_version >> 4;
    $time_hi_and_version = $time_hi_and_version | 0x4000;

    /**
     * Set the two most significant bits (bits 6 and 7) of the
     * clock_seq_hi_and_reserved to zero and one, respectively.
     */
    $clock_seq_hi_and_reserved = hexdec($clock_seq_hi_and_reserved);
    $clock_seq_hi_and_reserved = $clock_seq_hi_and_reserved >> 2;
    $clock_seq_hi_and_reserved = $clock_seq_hi_and_reserved | 0x8000;

    return sprintf('%08s-%04s-%04x-%04x-%012s',
            $time_low, $time_mid, $time_hi_and_version, $clock_seq_hi_and_reserved, $node);
}





  $a = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ');
  $b = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o');

function remove_accent($str)
{
    global $a, $b ;
  return str_replace($a, $b, $str);
}

function create_slug ( $str ) {
  // return strtolower(preg_replace(array('/[^a-zA-Z0-9 -]/', '/[ -]+/', '/^-|-$/'), array('', '-', ''), remove_accent($str))) ;
  return strtolower(preg_replace(array('/[^a-zA-Z0-9 -]/', '/[ -]+/', '/^-|-$/'), array('', '-', ''), $str)) ;
}

function sanitize ( $str ) {
    return preg_replace('/[^0-9a-zA-Z\-\.\!]/i', ' ', $str ) ;
}


/**
 * @return array
 * @param array $src
 * @param array $in
 * @param int|string $pos
*/
function array_push_before($src,$in,$pos){
    if(is_int($pos)) $R=array_merge(array_slice($src,0,$pos), $in, array_slice($src,$pos));
    else{
        foreach($src as $k=>$v){
            if($k==$pos)$R=array_merge($R,$in);
            $R[$k]=$v;
        }
    }return $R;
}

/**
 * @return array
 * @param array $src
 * @param array $in
 * @param int|string $pos
*/
function array_push_after($src,$in,$pos){
    if(is_int($pos)) $R=array_merge(array_slice($src,0,$pos+1), $in, array_slice($src,$pos+1));
    else{
        foreach($src as $k=>$v){
            $R[$k]=$v;
            if($k==$pos) { $R=array_merge($R,$in); }
        }
    }return $R;
}




function phpinfo_array () {
    
    ob_start();
    phpinfo();
    $info_arr = array();
    $info_lines = explode("\n", strip_tags(ob_get_clean(), "<tr><td><h2>"));
    $cat = "General";

    foreach($info_lines as $line)
    {
        // new cat?
        preg_match("~<h2>(.*)</h2>~", $line, $title) ? $cat = $title[1] : null;
        if(preg_match("~<tr><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td></tr>~", $line, $val))
        {
            $info_arr[$cat][$val[1]] = $val[2];
        }
        elseif(preg_match("~<tr><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td></tr>~", $line, $val))
        {
            $info_arr[$cat][$val[1]] = array("local" => $val[2], "master" => $val[3]);
        }
    }

    @ob_end_clean() ;

    return $info_arr;
}





    
    /**
 * Explode any single-dimensional array into a full blown tree structure,
 * based on the delimiters found in it's keys.
 *
 * @author  Kevin van Zonneveld <kevin@vanzonneveld.net>
 * @author  Lachlan Donald
 * @author  Takkie
 * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
 * @version   SVN: Release: $Id: explodeTree.inc.php 89 2008-09-05 20:52:48Z kevin $
 * @link    http://kevin.vanzonneveld.net/
 *
 * @param array   $array
 * @param string  $delimiter
 * @param boolean $baseval
 *
 * @return array
 */

    function explodeTree($array, $delimiter = '/', $baseval = true ) {
        
    if (!is_array($array))
        return false;
    
    $splitRE = '/' . preg_quote($delimiter, '/') . '/';
    $returnArr = array();
    
    foreach ($array as $key => $val) {
        
                // echo '*' ;
        // Get parent parts and the current leaf
        $parts = preg_split($splitRE, $key, -1, PREG_SPLIT_NO_EMPTY);
        $leafPart = array_pop($parts);
        
        // Build parent structure
        // Might be slow for really deep and large structures
        $parentArr = &$returnArr;
        foreach ($parts as $part) {
                // echo '-' ;
            if (!isset($parentArr[$part])) {
                $parentArr[$part] = array();
                // echo ',' ;
            } elseif (!is_array($parentArr[$part])) {
                // echo '.' ;
                    $parentArr[$part] = array('@path' => $parentArr[$part]);
            }
            $parentArr = &$parentArr[$part];
        }

        // Add the final part to the structure
        if (empty($parentArr[$leafPart])) {
            $parentArr[$leafPart] = $val;
        } elseif ($baseval && is_array($parentArr[$leafPart])) {
            $parentArr[$leafPart]['@path'] = $val;
        }
    }
  return $returnArr;
}




/*
* File: SimpleImage.php
* Author: Simon Jarvis
* Copyright: 2006 Simon Jarvis
* Date: 08/11/06
* Link: http://www.white-hat-web-design.co.uk/articles/php-image-resizing.php
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; either version 2
* of the License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details:
* http://www.gnu.org/licenses/gpl.html
*
*/
 
class SimpleImage {
 
   var $image;
   var $image_type;
 
   function load($filename) {
 
      $image_info = getimagesize($filename);
      $this->image_type = $image_info[2];
      if( $this->image_type == IMAGETYPE_JPEG ) {
 
         $this->image = imagecreatefromjpeg($filename);
      } elseif( $this->image_type == IMAGETYPE_GIF ) {
 
         $this->image = imagecreatefromgif($filename);
      } elseif( $this->image_type == IMAGETYPE_PNG ) {
 
         $this->image = imagecreatefrompng($filename);
      }
   }
   function save($filename, $image_type=IMAGETYPE_JPEG, $compression=75, $permissions=null) {
 
      if( $image_type == IMAGETYPE_JPEG ) {
         imagejpeg($this->image,$filename,$compression);
      } elseif( $image_type == IMAGETYPE_GIF ) {
 
         imagegif($this->image,$filename);
      } elseif( $image_type == IMAGETYPE_PNG ) {
 
         imagepng($this->image,$filename);
      }
      if( $permissions != null) {
 
         chmod($filename,$permissions);
      }
   }
   function output($image_type=IMAGETYPE_JPEG) {
 
      if( $image_type == IMAGETYPE_JPEG ) {
         imagejpeg($this->image);
      } elseif( $image_type == IMAGETYPE_GIF ) {
 
         imagegif($this->image);
      } elseif( $image_type == IMAGETYPE_PNG ) {
 
         imagepng($this->image);
      }
   }
   function getWidth() {
 
      return imagesx($this->image);
   }
   function getHeight() {
 
      return imagesy($this->image);
   }
   function resizeToHeight($height) {
 
      $ratio = $height / $this->getHeight();
      $width = $this->getWidth() * $ratio;
      $this->resize($width,$height);
   }
 
   function resizeToWidth($width) {
      $ratio = $width / $this->getWidth();
      $height = $this->getheight() * $ratio;
      $this->resize($width,$height);
   }
 
   function scale($scale) {
      $width = $this->getWidth() * $scale/100;
      $height = $this->getheight() * $scale/100;
      $this->resize($width,$height);
   }
 
   function resize($width,$height) {
      $new_image = imagecreatetruecolor($width, $height);
      imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
      $this->image = $new_image;
   }      
 
}