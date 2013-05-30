<?php

class xs_File {

    var $attempts;

    // Constructor.
    function __constructor() {
        $this->Object();
        $this->attempts = 10;
    }

    // Read a file as an array
    // Note : multiple-user ready!!
    // Write file from array

    static function writeArray($file, $arr) {
        return ( $this->write($file, implode("\r\n", $arr)) );
    }

    // Write file

    static function write($file, $data) {

        // Try to write the file $attempts times (or a given number)
        // and give up with false if no access, or return true if success.

        $attempt = 0;

        while (true) {
            $handle = @fopen($file, "w");
            if ($handle)
                break;
            if ($attempt++ == $this->attempts)
                return false;
        }
        fwrite($handle, $data);
        fclose($handle);

        return true;
    }

    // Note : multiple-user ready!!

    static function read($filename) {

        // Try to write the file 10 times (or a given number)
        // and give up with false if no access, or return true if success.

        $attempt = 0;

        while (true) {
            $handle = @fopen($filename, "r");
            if ($handle)
                break;
            if ($attempt++ == $this->attempts)
                return false;
        }
        $contents = fread($handle, filesize($filename));
        fclose($handle);

        return $contents;
    }

    static function readArray($file) {

        // Try to read the file (returned as an array) 10 times (or a given number)
        // and give up, or return the file as an array of textual lines.

        $attempt = 0;
        $buffer = false;

        while (true) {
            $buffer = @file($file);
            if ($buffer || $attempt++ == $this->attempts)
                break;
        }

        return $buffer;
    }

    // Turn a properties file into an associative array

    static function propertiesRead($file, $options = false) {

        $in = $this->readArray($file);

        $res = array();
        $ci = false;
        $isIndex = false;

        foreach ($in as $value) {

            $value = trim($value);

            if ($value && $value[0] != '#') {

                // Check and process [SomeItem] elements

                $start = strpos($value, '[');

                if ($start !== false) {
                    $ci = trim($this->substring($value, $start + 1, strpos($value, ']')));
                    $isIndex = true;
                    if (!isset($res[$ci]))
                        $res[$ci] = array();
                }

                // Check and process single, paired and multiple properties

                if ($ci !== false && $isIndex == false) {
                    $s2 = strpos($value, '=');
                    $t2 = trim($this->substring($value, 0, $s2));
                    $r2 = trim(substr($value, $s2 + 1));
                    if ($s2 !== false) {
                        if ($options == MULTIPLE_INDEXS_RESULTS) {
                            // Explode any listed paired properties
                            $idxList = explode(',', $t2);
                            $valList = explode(',', $r2);
                            foreach ($idxList as $i) {
                                foreach ($valList as $v) {
                                    $res[$ci][$i][] = $v;
                                }
                            }
                        } else {
                            // Paired property
                            $res[$ci][$t2] = $r2;
                        }
                    } else {
                        // No equal sign found; single unnamed property
                        $res[$ci][] = $value;
                    }
                }

                $isIndex = false;
            }
        }

        return $res;
    }

    static function substring($str, $start, $end) {
        return substr($str, $start, ($end - $start));
    }

    static function copyFile($from, $to) {
        $this->makeDirectories($to);
        return @copy($from, $to);
    }

    static function makeDirectories($strPath, $nPermission='0777') {

        $strPathSeparator = "/";
        $strDirname = substr($strPath, 0, strrpos($strPath, $strPathSeparator));

        if (is_dir($strDirname)) {
            return true;
        }

        $arMake = array();
        array_unshift($arMake, $strDirname);

        do {

            $bStop = true;
            $nPos = strrpos($strDirname, $strPathSeparator);
            $strParent = substr($strDirname, 0, $nPos);

            if (!is_dir($strParent)) {
                $strDirname = $strParent;
                array_unshift($arMake, $strDirname);
                $bStop = false;
            }
        } while (!$bStop);

        if (count($arMake) > 0)
            foreach ($arMake as $strDir)
                mkdir($strDir, $nPermission);

        return true;
    }

    static function makeTree($path, $mama = 0, $disp = false) { //where $path is your source dir.
        $list = Array();
        $mama++;

        if ($mama > 50) {
            echo "Structure too big!";
            return "";
        }

        $l = "level_" . strval($mama);

        $handle = opendir($path);

        if ($handle) {
            while ($a = readdir($handle)) {

                echo " d     ";
                flush();

                if (!preg_match('/^\./', $a)) {
                    $full_path = "$path/$a";
                    $list[] = $full_path; // REPLACE WITH OPTION IF NEEDED.
                    // echo "[$full_path] " ;
                    if (is_dir($full_path)) {
                        $recursive = $this->makeTree($full_path, $mama, $disp);
                        for ($n = 0; $n < count($recursive); $n++) {
                            $list[] = $recursive[$n];
                            echo " f  ";
                            flush();
                        }
                    }
                }
            }
            closedir($handle);

            return $list;
        } else {
            echo "Failed to open directory '$path'.<br>";
            return false;
        }
    }

    static function delete($filename) {
        @unlink($filename);
    }

    static function dirDelete($dirname) {
        foreach (glob($dirname) as $fn)
            @unlink($fn);
    }

    static function serialisedRead() {
        $f = $this->fileRead('array.log');
        return unserialize($f);
    }

    static function serialisedWrite($a) {
        $s = serialize($a);
        $f = $this->write('array.log', $s);
    }

}
?>
