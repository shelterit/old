<?php

    class xs_Autoloader {
        
        public function autoload ( $class_name ) {

            $inc = null ;
            $short = null ;

            // does the class start with 'xs_'?
            if ( substr ( $class_name, 0, 3 ) == 'xs_' ) {
                // yes?
                if ( $len = strpos ( $class_name, '_', 3 ) ) {

                    // does the class contain a sub-class, but located in the same directory as a main?
                    $short = substr ( $class_name, 0, $len ) ;
                    $inc = XS_DIR_XS . "/$short/$class_name.class.php" ;

                } else

                    // we're including the main class only
                    $inc = XS_DIR_XS . "/$class_name/$class_name.class.php" ;

            } else {
                // No? Look in /classes/$class_name.php
                $inc = XS_DIR_APP . "/classes/$class_name.php" ;
            }

            if (file_exists($inc)) {
                require_once ( $inc ) ;
                if ( 
                        class_exists ( 'xs_Core' ) &&
                        isset ( xs_Core::$glob ) &&
                        is_object ( xs_Core::$glob->log ) 
                )
                    xs_Core::$glob->log->add ( "__auto: [$inc]" ) ;
            } else {
                
                // used to be verbose, but now fails quietly in case there are
                // other autoloaders
                // 
                // xs_Core::$glob->log->add ( "__auto FAIL: [$inc]" ) ;
                // echo "xs_autoloader failed to find file [$inc] ($class_name) short=[$short]<br/> " ;
            }

        }
        
    }

    class xs_Core {

        public static $request_random_number = null ;
        public static $xs_version = '0.7.8' ;
        public static $app_version = null ; // will be set by the app

        public static $glob = null ;
        public static $glob_type = null ;

        public static $dir_xs = null ;
        public static $dir_lib = null ;
        public static $dir_app = null ;
        public static $dir_cache = null ;
        public static $dir_static = null ;

        function __construct () {
        }
        
        public function __get ( $idx ) {
            
            if ( $idx == 'glob' ) {
                return self::$glob ;
            } elseif ( $idx == 'glob_type' ) {
                return self::$glob_type ;
            } else {
                if ( isset ( $this->$idx ) )
                    return $this->$idx ;

                $trace = debug_backtrace(false);
                // print_r ( $trace ) ; die() ;
                $caller = $trace[2];
                $place = '';
                $parCaller = $trace[1];
                if (array_key_exists('class', $caller)) {
                    $place = $caller['class']."::".$caller['function'];
                } else {
                    $place = $caller['function'];
                }
                if ( isset ( $parCaller['line'] ) )
                    $place .= ' (line: '.$parCaller['line'].") ";

                echo "<div style='padding:4px;margin:4px;border:solid 1px #999;'>Oops! <span style='color:blue'>".  get_class( $this ) . "</span>-&gt;<span style='color:red'>$idx</span> not found. Called through ".$place." (xs_Core->__get)</div>" ;

                // throw new Exception('error', 2);

            }
        }

        public function __if_set ( $var, $default ) {
            if ( isset ( $var ) )
                return $var ;
            return $default ;
        }

        public static function static_setup () {

            // Create a random number for the request
            self::$request_random_number = rand() ;

            // Inject directory paths for various bits of our system
            self::$dir_xs  = dirname ( __FILE__ ) ;
            self::$dir_lib = self::$dir_xs . '/../lib' ;
            self::$dir_app = dirname ( $_SERVER['SCRIPT_FILENAME'] ) . '/application' ;
            self::$dir_cache = dirname ( $_SERVER['SCRIPT_FILENAME'] ) . '/cache' ;
            self::$dir_static = dirname ( $_SERVER['SCRIPT_FILENAME'] ) . '/static' ;

            // Make some handy constants out of them, and a few other
            self::static_make_constants() ;

        }

        public static function static_make_constants () {

            // First, a batch of constants we'll use for all sorts of things
            define ( 'NONE', NULL ) ;

            define ( 'XS_PAGE_AUTO', 'system' ) ;
            define ( 'XS_PAGE_STATIC', 'static' ) ;
            define ( 'XS_PAGE_DYNAMIC', 'dynamic' ) ;
            define ( 'XS_PAGE_RESOURCE', 'resource' ) ;

            define ( 'XS_NAMESPACE_NUT' , 'xmlns:nut="http://schema.shelter.nu/nut"' ) ;

            define ( 'XS_CONTEXT_USER', 'XS_CONTEXT_USER' ) ;
            define ( 'XS_CONTEXT_USER_GROUP', 'XS_CONTEXT_USER_GROUP' ) ;

            define ( 'XS_CONTEXT_CLASS', 'XS_CONTEXT_CLASS' ) ;
            define ( 'XS_CONTEXT_INSTANCE', 'XS_CONTEXT_INSTANCE' ) ;

            define ( 'XS_CONTEXT_PAGE', 'XS_CONTEXT_PAGE' ) ;
            define ( 'XS_CONTEXT_SECTION', 'XS_CONTEXT_SECTION' ) ;


            // Next, define some priorities
            define ( 'XS_SYSTEM',   -10 ) ;
            define ( 'XS_MODULE',    -8 ) ;
            define ( 'XS_PLUGIN',    -6 ) ;
            define ( 'XS_WIDGET',    -4 ) ;
            define ( 'XS_RESOURCE',  -2 ) ;

            // Finally, define some shortcuts for often-used paths
            define ( 'XS_DIR_XS', self::$dir_xs ) ;
            define ( 'XS_DIR_LIB', self::$dir_lib ) ;
            define ( 'XS_DIR_APP', self::$dir_app ) ;
            define ( 'XS_DIR_STATIC', self::$dir_static ) ;
        }
    }


 class array2xml {

        public $data;
        public $dom_tree;
        
        private $counter = 0 ;
        private $calls = 0 ;

        /**
         * basic constructor
         *
         * @param array $array
         */
        public  function __construct ( $array = array () ) {
            
            // echo '1. ' ;
            
            /*
            if(!is_array($array)){
                throw new Exception('array2xml requires an array', 1);
                unset($this);
            }
            if(!count($array)){
                throw new Exception('array is empty', 2);
                unset($this);
            }
             */
            // var_dump ( $array ) ;
        
            $x = (string) print_r ( $array, true ) ;
            
            // echo '2. ' ;
            $this->data = new DOMDocument('1.0');
    
            $this->dom_tree = $this->data->createElement('result');
  
            $this->recurse_node ( $array, $this->dom_tree ) ;

            $this->dom_tree->setAttribute ( 'iterations', $this->counter ) ;
            $this->dom_tree->setAttribute ( 'calls', $this->calls ) ;
            $this->dom_tree->setAttribute ( 'count_array', count ( $array ) ) ;
            
            $this->data->appendChild ( $this->dom_tree ) ;

        }
        
        /**
         * recurse a nested array and return dom back
         *
         * @param array $data
         * @param dom element $obj
         */
        private function recurse_node ( $data, $parent_obj, $level = 0 ) {
            
            $attrs = $nodes = $me = array () ;
            
            $this->counter++ ;
            
            if ( is_array ( $data ) && count ( $data ) > 0 )
                foreach ( $data as $key => $value )

                    if ( isset ( $key[0] ) && $key[0] == '@' ) {
                        $key = substr($key,1) ;
                        $attrs[$key] = $value ;
                    } else {
                        $nodes[$key] = $value ;
                    }
                
            if ( count ( $attrs ) > 0 ) 
                foreach ( $attrs as $key => $value )
                    $parent_obj->setAttribute ( $key, $value ) ;
            
            if ( count ( $nodes ) > 0 ) 
                foreach ( $nodes as $key => $value ) {
                
                    $this->calls++ ;

                    // create the element for the current node    
                    $me[$this->counter] = $this->data->createElement('item');

                    // set the default static attributes
                    $me[$this->counter]->setAttribute ( 'name', $key ) ;
                    $me[$this->counter]->setAttribute ( 'level', $level ) ;

                    // Attach new item to the main chain
                    $parent_obj->appendChild ( $me[$this->counter] ) ;

                    $this->recurse_node ( $value, $me[$this->counter], $level + 1 ) ;

                }
        }
        

        /**
         * get the finished xml as string
         *
         * @return string
         */
        public function saveXML(){
            return $this->data->saveXML();
        }
        
        /**
         * get the finished xml as DOM object
         *
         * @return DOMDocument
         */
        public function get () {
            return $this->data ;
        }

    }


/* Wiky.php - A tiny PHP "library" to convert Wiki Markup language to HTML
 * Author: Toni Lähdekorpi <toni@lygon.net>
 *
 * Code usage under any of these licenses:
 * Apache License 2.0, http://www.apache.org/licenses/LICENSE-2.0
 * Mozilla Public License 1.1, http://www.mozilla.org/MPL/1.1/
 * GNU Lesser General Public License 3.0, http://www.gnu.org/licenses/lgpl-3.0.html
 * GNU General Public License 2.0, http://www.gnu.org/licenses/gpl-2.0.html
 * Creative Commons Attribution 3.0 Unported License, http://creativecommons.org/licenses/by/3.0/
 */

class wiky {
	private $patterns, $replacements;

	public function __construct($analyze=false) {
		$this->patterns=array(
			// Headings
			"/^==== (.+?) ====$/m",						// Subsubheading
			"/^=== (.+?) ===$/m",						// Subheading
			"/^== (.+?) ==$/m",						// Heading

			// Formatting
			"/\'\'\'\'\'(.+?)\'\'\'\'\'/s",					// Bold-italic
			"/\'\'\'(.+?)\'\'\'/s",						// Bold
			"/\'\'(.+?)\'\'/s",						// Italic

			// Special
			"/^----+(\s*)$/m",						// Horizontal line
			"/\[\[(file|img):((ht|f)tp(s?):\/\/(.+?))( (.+))*\]\]/i",	// (File|img):(http|https|ftp) aka image
			"/\[((news|(ht|f)tp(s?)|irc):\/\/(.+?))( (.+))\]/i",		// Other urls with text
			"/\[((news|(ht|f)tp(s?)|irc):\/\/(.+?))\]/i",			// Other urls without text

			// Indentations
			"/[\n\r]: *.+([\n\r]:+.+)*/",					// Indentation first pass
			"/^:(?!:) *(.+)$/m",						// Indentation second pass
			"/([\n\r]:: *.+)+/",						// Subindentation first pass
			"/^:: *(.+)$/m",						// Subindentation second pass

			// Ordered list
			"/[\n\r]?#.+([\n|\r]#.+)+/",					// First pass, finding all blocks
			"/[\n\r]#(?!#) *(.+)(([\n\r]#{2,}.+)+)/",			// List item with sub items of 2 or more
			"/[\n\r]#{2}(?!#) *(.+)(([\n\r]#{3,}.+)+)/",			// List item with sub items of 3 or more
			"/[\n\r]#{3}(?!#) *(.+)(([\n\r]#{4,}.+)+)/",			// List item with sub items of 4 or more

			// Unordered list
			"/[\n\r]?\*.+([\n|\r]\*.+)+/",					// First pass, finding all blocks
			"/[\n\r]\*(?!\*) *(.+)(([\n\r]\*{2,}.+)+)/",			// List item with sub items of 2 or more
			"/[\n\r]\*{2}(?!\*) *(.+)(([\n\r]\*{3,}.+)+)/",			// List item with sub items of 3 or more
			"/[\n\r]\*{3}(?!\*) *(.+)(([\n\r]\*{4,}.+)+)/",			// List item with sub items of 4 or more

			// List items
			"/^[#\*]+ *(.+)$/m",						// Wraps all list items to <li/>

			// Newlines (TODO: make it smarter and so that it groupd paragraphs)
			"/^(?!<li|dd).+(?=(<a|strong|em|img)).+$/mi",			// Ones with breakable elements (TODO: Fix this crap, the li|dd comparison here is just stupid)
			"/^[^><\n\r]+$/m",						// Ones with no elements
		);
		$this->replacements=array(
			// Headings
			"<h3>$1</h3>",
			"<h2>$1</h2>",
			"<h1>$1</h1>",

			//Formatting
			"<strong><em>$1</em></strong>",
			"<strong>$1</strong>",
			"<em>$1</em>",

			// Special
			"<hr/>",
			"<img src=\"$2\" alt=\"$6\"/>",
			"<a href=\"$1\">$7</a>",
			"<a href=\"$1\">$1</a>",

			// Indentations
			"\n<dl>$0\n</dl>", // Newline is here to make the second pass easier
			"<dd>$1</dd>",
			"\n<dd><dl>$0\n</dl></dd>",
			"<dd>$1</dd>",

			// Ordered list
			"\n<ol>\n$0\n</ol>",
			"\n<li>$1\n<ol>$2\n</ol>\n</li>",
			"\n<li>$1\n<ol>$2\n</ol>\n</li>",
			"\n<li>$1\n<ol>$2\n</ol>\n</li>",

			// Unordered list
			"\n<ul>\n$0\n</ul>",
			"\n<li>$1\n<ul>$2\n</ul>\n</li>",
			"\n<li>$1\n<ul>$2\n</ul>\n</li>",
			"\n<li>$1\n<ul>$2\n</ul>\n</li>",

			// List items
			"<li>$1</li>",

			// Newlines
			"$0<br/>",
			"$0<br/>",
		);
		if($analyze) {
			foreach($this->patterns as $k=>$v) {
				$this->patterns[$k].="S";
			}
		}
	}
	public function parse($input) {
		if(!empty($input))
			$output=preg_replace($this->patterns,$this->replacements,$input);
		else
			$output=false;
		return $output;
	}
}