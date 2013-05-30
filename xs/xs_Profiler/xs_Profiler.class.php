<?php

class xs_Profiler {

    private $ticks = array() ;
    private $count = 0 ;
    private $start = null ;
    public  $rand = null ;

    // Constructor.

    function __construct ( $start = null ) {
        // parent::__construct();
        $this->start = microtime ( true ) ;
        if ( $start != null )
            $this->start = $start ;
        // Generate a random number
        $this->rand = rand() ;
    }

    function add ( $tick ) {
        $this->ticks[$this->count]['time'] = microtime ( true  )  ;
        $this->ticks[$this->count]['label'] = $tick  ;
        $this->count++ ;
        // echo "[$tick ".microtime ( true  )."]" ;
    }

    public function reportXML() {
        $xml = "" ;
        $levels = array (
                '0.25'  => '*PANIC!*',
                '0.10' => '**OUCH!*',
                '0.08' => '********',
                '0.06' => '*******-',
                '0.04' => '******--',
                '0.02' => '*****---',
                '0.01' => '****----',
                '0.007' => '***-----',
                '0.003' => '**------',
                '0.001' => '*-------',
                '0.00' => '--------',
                ) ;
        $totals = array () ;

        // $totbad = $totslow = $totmedium = 0 ;

        foreach ( $this->ticks as $idx=>$val ) {
            $l = round ( $val['time'] - $this->start, 4 )  ;
            $t = (0+$idx) ;
            if ( $t != 0 ) {
                $ti = round ( $val['time'] - $this->ticks[$idx-1]['time'], 4 )  ;
            } else {
                $ti = round ( $val['time'] - $this->start, 4 )  ;
            }
            $xml .= "<item" ;
            $level = 0 ;
            foreach ( $levels as $limit => $msg ) {
                if ( $ti >= (float) $limit ) {
                    $xml .= " meter='[$msg]'" ;
                    if ( !isset($totals[$msg])) $totals[$msg] = 0 ;
                    $totals[$msg] += $ti ;
                    break ;
                }
                $level++ ;
            }
            $xml .= " timed='".sprintf("%01.3f", $ti)."' lapsed='".sprintf("%01.3f", $l)."' info='".htmlentities($val['label'])."' />\n" ;
        }
        $xml .= "\n\n" ;
        asort($totals) ;
        foreach ( $totals as $level => $value )
            $xml .= "<item-sum total-time='".sprintf("%01.3f", $value)."' for-speed='$level' /> \n" ;
        // $xml .= "<item outright-BAD-items='".sprintf("%01.3f", $totbad)."' needs-optimizing-items='".sprintf("%01.3f", $totslow)."' slow-items='".sprintf("%01.3f", $totmedium)."' time-lapse='".sprintf("%01.3f", $l)."' />" ;
        return $xml ;
    }

    public function report() {
        $res = "Report:<br>---------<br>" ;
        foreach ( $this->ticks as $idx=>$val ) {
            $res .="<p>" ;
            $l = round ( $val['time'] - $this->start, 4 )  ;
            $t = (0+$idx) ;
            if ( $t != 0 ) {
                $ti = round ( $val['time'] - $this->ticks[$idx-1]['time'], 4 )  ;
            } else {
                $ti = round ( $val['time'] - $this->start, 4 )  ;
            }
            $res .= $val['label']." : clocked-at='$ti' time-lapse='".$l."'" ;
        }
        $res .= "</p> \n" ;
        return $res ;
    }

}
