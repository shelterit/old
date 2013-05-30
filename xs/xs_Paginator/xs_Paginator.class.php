<?php

class xs_Paginator extends xs_Request {

    private $current_page = 0 ;
    private $max_pages = 0 ;
    private $items_count = 0 ;
    private $pages = array() ;

    public $start_item = 0 ;
    public $items_pr_page = 0 ;

    function __construct ( $items_count = 0, $items_per_page = 30 ) {

        parent::__construct() ;
        
        $this->items_count = (int) $items_count ;

        $this->start_item     = (int) $this->__fetch ( 'start', 0 ) ;
        $this->items_pr_page   = (int) $this->__fetch ( 'rows', $items_per_page ) ;
        if ( $this->items_pr_page < 1 ) $this->items_pr_page = $items_per_page ;

        if ( $this->items_count != 0 ) {

            $this->max_pages    = ceil ( $this->items_count / $this->items_pr_page ) ;
            $this->current_page = ceil ( ( $this->start_item ) / $this->items_pr_page ) + 1 ;

            $arr = array();

            try {

                for ($n = 0; $n < $this->max_pages; $n++)
                    $arr[$n + 1] = "rows=" . $this->items_pr_page . "&start=" . ( $n * $this->items_pr_page ) ;

                // Enforce first page
                $this->pages['1'] = $arr[1];

                // First, enforce second and second last '...' (which may be overwritten later)
                if ($this->max_pages > 1) {
                    $this->pages['2'] = '...';
                    $this->pages[$this->max_pages - 1] = '...';
                }

                foreach ($arr as $idx => $val) {

                    $min = $this->current_page - 5;
                    $max = $this->current_page + 5;

                    if ($idx > $min && $idx < $max) 
                        $this->pages[$idx] = $arr[$idx];

                }

                // Enforce last page
                $this->pages[$this->max_pages] = $arr[$this->max_pages];

                ksort($this->pages);

            } catch (exception $ex) {

                print_r($ex);
            }
        }
    }

    function getCurrentPage() {
        return $this->current_page;
    }

    function getPages() {
        return $this->pages;
    }

    function getitems_count() {
        return $this->items_count;
    }

}