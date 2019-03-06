<?php
/**
 * Created by PhpStorm.
 * User: Jorge Rodriguez
 * Date: 26/07/2018
 * Time: 17:54
 */

namespace L;

use mysqli;

class Log
{

    protected static $_document_root;
    protected static $_file;
    protected static $_level = 0;
    protected static $_console = false;
    protected static $_errorlog = false;
    protected static $_filelog = false;
    protected static $_online = false;
    protected static $_debug = true;
    protected static $_mode = "a";
    protected static $_init_time;
    protected static $_log_filter = null;
    protected static $_filename = null;

    private static $host = 'localhost';
    private static $user = 'root';
    private static $pass = '';
    private static $dbname = 'joomla';

    public static function setLogFilter($filter)
    {
        self::initTime();
        if (is_array($filter)) self::$_log_filter = $filter;
        else self::$_log_filter = explode(",", $filter);
    }//function

    protected static function initTime()
    {
        if (empty($GLOBALS['_init_time'])) {
            $GLOBALS["_init_time"] = microtime(true);
        }//if
    }//function

    public static function enableErrors()
    {
        self::initTime();
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }//function

    public static function disableErrors()
    {
        self::initTime();
        error_reporting(0);
        ini_set('display_errors', 0);
    }//function

    public static function setDocumentRoot($_document_root)
    {
        self::initTime();
        self::$_document_root = $_document_root;
    }//function

    public static function setDebug($_debug)
    {
        self::initTime();
        self::$_debug = $_debug;
    }//function

    public static function setDebugFile($_file)
    {
        self::initTime();
        if (file_exists(__DIR__ . "Log.php/" . $_file)) {
            self::$_debug = true;
            include_once(__DIR__ . "Log.php/" . $_file);
        }//If
        if (file_exists($_file)) {
            self::$_debug = true;
            include_once($_file);
        }//if
    }//function

    public static function setLevel($level)
    {
        self::initTime();
        self::$_level = $level;
    }//function

    public static function setDump2Console($console)
    {
        self::initTime();
        self::$_console = $console;
    }//function

    public static function setDump2ErrorLog($error)
    {
        self::initTime();
        self::$_errorlog = $error;
    }//function

    public static function setDump2File($error)
    {
        self::initTime();
        self::$_filelog = $error;
    }//function

    public static function setDump2Online($online)
    {
        self::initTime();
        self::$_online = $online;
    }//function

    public static function setMode($mode)
    {
        self::initTime();
        self::$_mode = $mode;
    }//function

    public static function setFilename($filename)
    {
        self::initTime();
        self::$_filename = $filename;
    }//function

    public static function debug($level = 0, $logFilter = null)
    {
        self::initTime();
        if ($level > self::$_level || self::$_debug === false) {
            return;
        }

        $bt = debug_backtrace();
        $caller = array_shift($bt);

        $isMatch = preg_match("/\w+.php$/i", $caller["file"], $mt);
        $isMatch2 = preg_match("/(\w+)$/i", debug_backtrace()[1]["class"], $mt2);
        $filterPlus = [$mt[0], $mt2[1], debug_backtrace()[1]["function"]];

        if (!self::checkFilter($logFilter, $filterPlus)) return;

        if (empty($GLOBALS["_file"]) && self::$_filelog) {
            $GLOBALS["_file"] = \fopen((!empty(self::$_document_root) ? self::$_document_root : $_SERVER['DOCUMENT_ROOT']) . (self::$_filename ? "/" . self::$_filename : "/debug.log"), self::$_mode);
        }//if

        ob_start();
        debug_print_backtrace();
        $result = ob_get_clean();

        if (self::$_filelog) {
            \fwrite($GLOBALS["_file"], date("Y/m/d H:i:s ") . PHP_EOL . $result);
        }//if

        if (self::$_errorlog) {
            \error_log($result);
        }//if

        if (self::$_console) {
            echo "<script>console.log('" . addslashes($result) . "');</script>";
        }//if

        if (self::$_online) {
            echo "<pre style='background-color: black; color: white; border: 1px solid white; z-index: 999999;'>" . date("Y/m/d H:i:s ") . $result . "</pre>";
        }//if

    }//function


    protected static function checkFilter($logFilter, $file = null)
    {
        if (empty(self::$_log_filter)) return true;
        if (!is_array($logFilter)) $logFilter = explode(",", $logFilter);

        $logFilter = array_merge($logFilter, $file);

        foreach ($logFilter as $logTerm) {
            if (in_array($logTerm, self::$_log_filter)) return true;
        }//foreach

        return false;
    }//protected


    public static function _($log, $level = 0, $logFilter = null)
    {
        self::add($log, $level, $logFilter);
    }//public

    public static function add($log, $level = 0, $logFilter = null)
    {
        self::initTime();

        $bt = debug_backtrace();
        $caller = array_shift($bt);
        $filterPlus = [];

        $isMatch = preg_match("/\w+.php$/i", $caller["file"], $mt);
        if (!empty(debug_backtrace()[1]["class"])) $isMatch2 = preg_match("/(\w+)$/i", debug_backtrace()[1]["class"], $mt2);
        if (!empty(debug_backtrace()[1]["function"])) $filterPlus = [$mt[0], $mt2[1], debug_backtrace()[1]["function"]];

        if (!self::checkFilter($logFilter, $filterPlus)) return;

        if (function_exists('xdebug_disable')) {
            xdebug_disable();
        }//if

        $time = round(microtime(true) - $GLOBALS['_init_time'], 2);
        $time = "({$time}s)";

        if ($level > self::$_level || self::$_debug === false) {
            return;
        }

        if (empty($GLOBALS["_file"])) {
            if (!empty(self::$_document_root)) {
                $_root = self::$_document_root;
            } else if (!empty($_SERVER['DOCUMENT_ROOT'])) {
                $_root = $_SERVER['DOCUMENT_ROOT'];
            } else {
                $_root = __DIR__;
            }

            $GLOBALS["_file"] = \fopen($_root . (self::$_filename ? "/" . self::$_filename : "/debug.log"), self::$_mode);
            if (!$GLOBALS["_file"]) {
                echo "ERROR: No puedo abrir " . $_root . (self::$_filename ? "/" . self::$_filename : "/debug.log");
                die();
            }//if

        }//if

        if (is_string($log) || is_int($log) || is_bool($log)) {
            $result = $log;
        }//If
        else if ($log === null) {
            $result = "null";
        }//elseif
        else {
            ob_start();
            print_r($log);
            $result = ob_get_clean();
        }//else

        if (!empty(self::$_log_filter)) {

        }//if

        $result = debug_backtrace()[1]["class"] . "::" . debug_backtrace()[1]["function"] . "(); {$mt[0]}({$caller['line']}); " . $result;

        if (self::$_filelog) {
            \fwrite($GLOBALS["_file"], date("Y/m/d H:i:s ") . $time . " :: " . memory_get_usage() . PHP_EOL . $result . PHP_EOL);
        }//if

        if (self::$_errorlog) {
            \error_log(date("Y/m/d H:i:s ") . $time . " :: " . $result);
        }//if
        if (self::$_console) {
            $resultConsole = preg_replace('/\\n/', ' ', $result);
            echo "<script style='display:none;'>console.log('" . addslashes(date("Y/m/d H:i:s ") . $time . " :: " . $resultConsole) . "');</script>";
        }//if

        if (self::$_online) {
            echo "<pre style='background-color: black; color: white; border: 1px solid white; z-index: 999999;'>" . date("Y/m/d H:i:s ") . $result . "</pre>";
        }//if

    }//function

    public static function sql($sql, $level = 0, $logFilter = null)
    {
        self::initTime();

        $bt = debug_backtrace();
        $caller = array_shift($bt);

        $isMatch = preg_match("/\w+.php$/i", $caller["file"], $mt);
        $isMatch2 = preg_match("/(\w+)$/i", debug_backtrace()[1]["class"], $mt2);
        $filterPlus = [$mt[0], $mt2[1], debug_backtrace()[1]["function"]];

        if ($level > self::$_level || self::$_debug === false) {
            return;
        }

        if (!self::checkFilter($logFilter, $filterPlus)) return;

        $conn = self::Connect();
        $resultado = self::Select($conn, $sql);

        if (empty($GLOBALS["_file"]) && self::$_filelog) {
            $GLOBALS["_file"] = \fopen((!empty(self::$_document_root) ? self::$_document_root : $_SERVER['DOCUMENT_ROOT']) . (self::$_filename ? "/" . self::$_filename : "/debug.log"), self::$_mode);
        }//if

        ob_start();
        echo "\$sql = $sql \n";
        print_r($resultado);
        $result = ob_get_clean();

        $result = debug_backtrace()[1]["class"] . "::" . debug_backtrace()[1]["function"] . "(); {$mt[0]}({$caller['line']}); " . $result;

        if (self::$_filelog) {
            \fwrite($GLOBALS["_file"], date("Y/m/d H:i:s ") . PHP_EOL . $result);
        }//if

        if (self::$_errorlog) {
            \error_log($result);
        }//if

        if (self::$_console) {
            echo "<script>console.log('" . addslashes($result) . "');</script>";
        }//if

        if (self::$_online) {
            echo "<pre style='background-color: black; color: white; border: 1px solid white; z-index: 999999;'>" . date("Y/m/d H:i:s ") . $result . "</pre>";
        }//if

    }//function

private static function Connect()
    {
        // Create connection

        $conn = new mysqli(self::$host, self::$user, self::$pass, self::$dbname);
        $conn->set_charset("utf8");

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }//if

        return $conn;
    }//function


    //SELECCIONAR EN BBDD
    private static function Select($conn, $sql)
    {
        //select records
        $result = $conn->query($sql);

        if (!is_object($result)) {
            return;
        }
        if ($result->num_rows > 0) { // output data of each row return
            Log::add($result, 50);

            return $result->fetch_all(MYSQLI_ASSOC);
        } //if
        else {
            return null;
        }//else
    }//function

        public static function backtrace($level = 0, $logFilter = null)
    {
        self::initTime();

        $bt = debug_backtrace();
        $caller = array_shift($bt);

        $isMatch = preg_match("/\w+.php$/i", $caller["file"], $mt);
        $isMatch2 = preg_match("/(\w+)$/i", debug_backtrace()[1]["class"], $mt2);
        $filterPlus = [$mt[0], $mt2[1], debug_backtrace()[1]["function"]];

        if ($level > self::$_level || self::$_debug === false) {
            return;
        }

        if (!self::checkFilter($logFilter, $filterPlus)) return;

        if (empty($GLOBALS["_file"]) && self::$_filelog) {
            $GLOBALS["_file"] = \fopen((!empty(self::$_document_root) ? self::$_document_root : $_SERVER['DOCUMENT_ROOT']) . (self::$_filename ? "/" . self::$_filename : "/debug.log"), self::$_mode);
        }//if
        var_dump(debug_backtrace());
        $stack = '';
        $i = 1;
        $trace = debug_backtrace();

        print_r($trace);
        unset($trace[0]); //Remove call to this function from stack trace
        foreach ($trace as $node) {
            $stack .= "#$i " . $node['file'] . "(" . $node['line'] . "): ";
            if (isset($node['class'])) {
                $stack .= $node['class'] . "->";
            }
            $stack .= $node['function'] . "()" . PHP_EOL;
            $i++;
        }
        echo $stack;
        $result = $stack;

        if (self::$_filelog) {
            \fwrite($GLOBALS["_file"], date("Y/m/d H:i:s ") . PHP_EOL . $result);
        }//if

        if (self::$_errorlog) {
            \error_log($result);
        }//if

        if (self::$_console) {
            echo "<script>console.log('" . addslashes($result) . "');</script>";
        }//if

        if (self::$_online) {
            echo "<pre style='background-color: black; color: white; border: 1px solid white; z-index: 999999;'>" . date("Y/m/d H:i:s ") . $result . "</pre>";
        }//if
    }//function

public static function phpinfo()
    {
        echo phpinfo();
        die();
    }

    public static function config()
    {
        echo "<pre style='background-color: black; color: white; border: 1px solid white; z-index: 999999;'>";
        echo '$_document_root = ' . self::$_document_root . "<br>";
        echo '$_filename = ' . self::$_filename . "<br>";
        echo '$_level = ' . self::$_level . "<br>";
        echo '$_console = ' . self::$_console . "<br>";
        echo '$_errorlog = ' . self::$_errorlog . "<br>";
        echo '$_filelog = ' . self::$_filelog . "<br>";
        echo '$_online = ' . self::$_online . "<br>";
        echo '$_debug  = ' . self::$_debug . "<br>";
        echo '$_mode = ' . self::$_mode . "<br>";
        echo '$_init_time = ' . self::$_init_time . "<br>";
        echo '$_log_filter = ';
        print_r(self::$_log_filter);
        echo "<br>";
        echo "</pre>";
        die();
    }//function

    public static function echoLog()
    {
        echo "<pre style='background-color: black; color: white; border: 1px solid white; z-index: 999999;'>";
        echo file_get_contents(self::$_document_root . "/" . self::$_filename);
        echo "</pre>";
    }//function

    private static function varName($v)
    {
        self::initTime();
        $trace = debug_backtrace();
        $vLine = file(__FILE__);
        $fLine = $vLine[$trace[0]['line'] - 1];
        preg_match("#\\$(\w+)#", $fLine, $match);

        return $match;
    }//function

}//class