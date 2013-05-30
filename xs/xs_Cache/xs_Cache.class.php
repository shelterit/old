<?php

class xs_Cache {

    // Configuration, with defaults

    private $config = array(
        'id' => 'unknown',
        'time' => '+30 seconds',
        'cache_dir' => 'cache',
        'service' => 'unknown'
            );

    private $status = '';
    private $filename = null;
    private $start_timestamp = 0;
    private $now_timestamp = 0;
    private $file_timestamp = 0;
    private $cache_timestamp = 0;
    private $file_exist = false;

    function __construct($config) {

        foreach ($config as $idx => $val)
            $this->config[$idx] = $val;

        // date_default_timezone_set('Australia/ACT');

        // $this->config['id'] = $id ;

        $this->filename = $this->config['cache_dir'] . '/' . $this->config['id'] . '.tmp';

        $this->calculate();
    }

    function calculate() {

        $this->now_timestamp = time();
        $this->start_timestamp = $this->now_timestamp;
        $this->file_timestamp = 0;
        $this->cache_timestamp = strtotime($this->config['time'], $this->now_timestamp);

        $this->status .= '| service [' . $this->config['service'] . '] ';

        // Does the file currently exist?
        $this->file_exist = file_exists($this->filename);

        if ($this->file_exist) {

            $this->status .= '| file exists ';
            $this->file_timestamp = @filemtime($this->filename);
            $this->start_timestamp = $this->file_timestamp;

            $this->cache_timestamp = strtotime($this->config['time'], $this->start_timestamp);
        } else {

            $this->status .= '| no file ';
            $this->cache_timestamp = 0;
        }

        $this->status .= "{ init, \nfilename(" . $this->filename .
                ") filestamp=(" . $this->disp($this->file_timestamp) .
                ") \n timestamp=(" . $this->disp($this->now_timestamp) .
                ") cachestamp(" . $this->disp($this->cache_timestamp) . ")\n ";
    }

    function cached() {

        if ($this->now_timestamp > $this->cache_timestamp) {

            $this->status .= '| not cached ';

            return false;
        } else {

            $this->status .= '| cached! ';

            return true;
        }
    }

    function writeCache($stuff) {

        $this->status .= '| writing cached file (' . $this->filename . ') ';
        Filesystem::fileWrite($this->filename, $stuff);
    }

    function readCache() {

        $this->status .= '| reading cached file (' . $this->filename . ') ';
        return Filesystem::fileRead($this->filename);
    }

    function status() {
        return $this->status;
    }

    function getID() {
        return $this->config['id'];
    }

    function disp($timestamp) {
        $date_format = "d.m.Y-H:i:s";
        return date($date_format, $timestamp);
    }

}