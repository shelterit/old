<?php

class xs_DocumentManager_Document extends xs_Properties {

    public $uid = '' ;
    
    public $label = '' ;
    public $filename = '' ;
    public $extension = '' ;
    public $timestamp = 0 ;
    public $spidered_timestamp = 0 ;
    public $timestamp_db_property = 0 ;
    
    public $controlled = 'false' ;
    public $deleted = 'false' ;
    public $harvest = 'false' ;
    
    public $absolute_path = '' ;
    public $absolute_path_md5 = '' ;
    
    public $relative_path = '' ;
    public $relative_path_md5 = '' ;
    
    public $final_path = '' ;
    public $final_file = '' ;
    public $home_directory = '' ;
    
    public $uri = '' ;
    public $type = 0 ;
    
    public $db_name = '' ;
    public $db_id = '' ;
    
    public $create_preview = false ;
    
    private $fstat = array () ;
    
    private $topic = null ;
    
    function __construct ( $path = '', $fstat = null ) {
        parent::__construct () ;
        $this->init ( $path, $fstat ) ;
    }
    
    function attach_topic ( $topic ) {
        $this->topic = $topic ;
    }
    
    function get_topic () {
        return $this->topic ;
    }
    
    function init ( $path = '', $fstat = null ) {
        
        $this->absolute_path = $path ;
        $this->absolute_path_md5 = $this->uid = md5 ( $this->absolute_path ) ;
        
        $this->relative_path = $this->relative_path ( $path ) ;
        $this->relative_path_md5 = md5 ( $this->relative_path ) ;
        
        $this->home_directory = $this->get_dir_structure ( $this->absolute_path_md5 ) ;
        
        $info = pathinfo ( $path ) ;
        $this->filename = $info['filename'] ;
        $this->extension = $info['extension'] ;
        
                
        $this->label = $this->create_label ( $this->relative_path ) ;

        if ( $fstat !== null ) {
            
            if ( isset ( $fstat['mtime'] ) )
                $this->timestamp = $this->spidered_timestamp = $fstat['mtime'] ;

            $this->fstat = $fstat ;
        }
    }
    
    function inject ( $arr ) {
        foreach ( $arr as $idx => $value )
            if ( isset ( $this->$idx ) )
                $this->$idx = $value ;
    }
    
    function create_label ( $str ) {
        $e = explode ( '/', $str ) ;
        return trim ( end ( $e ) ) ;
    }
    
    function relative_path ( $file ) {
        $f = trim ( substr ( $file, strlen ( $this->glob->config['dms']['source_folder'] ) + 1 ) ) ;
        $f = trim ( substr ( $f, 0, -4) ) ;
        return $f ;
    }

    function get_dir_structure ( $filename, $base_folder = '' ) {
        
        $size = strlen ( $filename ) ;
        $dest = '' ;
        if ( $size > 3 ) {
            $q = $w = '/' ;
            $q .= $filename[0] ;
            $q .= $filename[1] ;
            $w .= $filename[2] ;
            $w .= $filename[3] ;
            $dest = $q . $w ;
        }
        return $base_folder . $dest ;
    }
    
}
