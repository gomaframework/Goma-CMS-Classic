<?php defined("IN_GOMA") OR die();
/**
 * @package         goma framework
 * @link            http://goma-cms.org
 * @license:        LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 * @author          Goma-Team
 * @version         2.2.5
 *
 * last modified: 04.08.2015
 */
class SQL
{
    /**
     * this var contains the last query, for debug
     */
    public static $last_query;

    /**
     *
     * @var int
     * @use count queries
     **/
    static $queries = 0;

    /**
     * with this var you can freeze error-reporting.
     */
    static $track = true;

    /**
     * this var contains the current driver
     * @var SQLDriver
     */
    public static $driver;

    /**
     * factory - selects the sql-driver
     * @name factory
     * @param string - name of driver
     * @access public
     * @return gObject
     */
    static public function factory($name)
    {
        if (file_exists(dirname(__FILE__) . '/driver/' . $name . ".php")) {
            require_once(dirname(__FILE__) . '/driver/' . $name . ".php");
            $class_name = $name . "Driver";
            define("SQL_LOADUP", true);
            define("SQL_INIT", true);
            return new $class_name;
        } else {
            die('Could not load SQL-Driver');
        }
    }

    /**
     * inits the db with default settings
     */
    public static function Init()
    {
        new SQL(
            null,
            $GLOBALS["dbuser"],
            $GLOBALS["dbdb"],
            $GLOBALS["dbpass"],
            $GLOBALS["dbhost"]
        );
    }

    /**
     * connect to db
     *
     * @param null $driver
     * @param null $dbuser
     * @param null $dbdb
     * @param null $dbpass
     * @param null $dbhost
     */
    public function __construct($driver = null, $dbuser = null, $dbdb = null, $dbpass = null, $dbhost = null)
    {
        if (!isset($driver)) {
            if (defined("SQL_DRIVER_OVERRIDE")) {
                $driver = SQL_DRIVER_OVERRIDE;
            } else {
                $driver = SQL_DRIVER;
            }
        }

        if ($driver == "mysql")
            $driver = "mysqli";
        elseif ($driver == "postgresql")
            $driver = "pgsql";

        self::$driver = self::factory($driver);
        self::$driver->connect($dbuser, $dbdb, $dbpass, $dbhost);
    }

    /**
     * connect to db
     **/
    static function connect($dbuser, $dbdb, $dbpass, $dbhost)
    {
        $return = self::$driver->connect($dbuser, $dbdb, $dbpass, $dbhost);
        sql::setCharsetUTF8();
        return $return;
    }

    /**
     * tests the connection
     * @name test
     * @return bool|mixed
     */
    static function test($driver, $dbuser, $dbdb, $dbpass, $dbhost)
    {
        if ($driver == "mysql")
            $driver = "mysqli";
        elseif ($driver == "postgresql")
            $driver = "pgsql";

        if (file_exists(dirname(__FILE__) . '/driver/' . $driver . ".php")) {
            require_once(dirname(__FILE__) . '/driver/' . $driver . ".php");
            /** @var SQLDriver $instance */
            $driverClass = $driver . "Driver";
            $instance = new $driverClass(false);
            return $instance->test($dbuser, $dbdb, $dbpass, $dbhost);
        } else {
            return false;
        }
    }

    /**
     * run a query
     *
     * @param string $sql
     * @param bool $async
     * @param bool $track
     * @param bool $debug
     * @param bool $longQuery
     * @return
     */
    static function query($sql, $async = false, $track = true, $debug = true, $longQuery = false)
    {
        $start = microtime(true);

        $_sql = str_replace(array("\n", "\r\n", "\r", "\n\r", "\t"), ' ', $sql) . "\n\n\n\n";

        //echo $_sql . "\n";

        if ($track && self::$track)
            self::$last_query = str_replace(array("\n", "\r\n", "\r", "\n\r", "\t"), ' ', $sql);

        if (PROFILE) Profiler::mark("sql::query");
        self::$queries++; // count queries and make it 1 more
        $result = self::$driver->query($sql, $async, $debug);
        if (PROFILE) Profiler::unmark("sql::query");


        $time = (microtime(true) - $start) * 1000;
        //echo  $time . "\n\n";

        if (defined("SLOW_QUERY") && SLOW_QUERY != -1 && $time > SLOW_QUERY) {
            slow_query_log("Slow SQL-Query: " . $sql . " (" . $time . "ms)");
            if($time > 10000 && !$longQuery) {
                if(!isCommandLineInterface()) {
                    throw new LogicException("SQL-Queries takes way too long, cancelling.");
                } else {
                    echo "SQL-Queries taking very long: " . $time . "\n Query: " . $_sql . "\n";
                }
            }
        }

        return $result;
    }

    /**
     * fetch_row
     */
    static function fetch_row($result)
    {
        return self::$driver->fetch_row($result);
    }

    /**
     * disconnect
     **/
    static function close()
    {
        return self::$driver->close();
    }

    /**
     * fetch object
     **/
    static function fetch_object($result)
    {
        return self::$driver->fetch_object($result);
    }

    /**
     * fetch array
     */
    static function fetch_array($result)
    {
        return self::$driver->fetch_array($result);
    }

    /**
     * fetch_assoc
     */
    static function fetch_assoc($result)
    {
        return self::$driver->fetch_assoc($result);
    }

    /**
     * num_rows
     */
    static function num_rows($result)
    {
        return self::$driver->num_rows($result);
    }

    /**
     * error of last query
     */
    static function error()
    {
        return self::$driver->error();
    }

    /**
     * errno of last query
     */
    static function errno()
    {
        return self::$driver->errno();
    }

    /**
     * insert_id of last query
     */
    static function insert_id()
    {
        return self::$driver->insert_id();
    }

    /**
     * free memory
     * @param $result
     * @return
     */
    static function free_result($result)
    {
        return self::$driver->free_result($result);
    }

    /**
     * protect string.
     */
    static function escape_string($result)
    {
        return self::$driver->escape_string($result);
    }

    /**
     * protect string.
     */
    static function real_escape_string($result)
    {
        return self::$driver->real_escape_string($result);
    }

    /**
     * protect.
     */
    static function protect($result)
    {
        return self::$driver->protect($result);
    }

    /**
     * returns affected rows after update or delete
     */
    static function affected_rows()
    {
        return self::$driver->affected_rows();
    }

    /**
     * split queries at semicolon.
     */
    static function split($sql)
    {
        return preg_split('/;\s*\n/', $sql, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * @access public
     * @use to view tables
     */
    static function list_tables($db)
    {
        return self::$driver->list_tables($db);
    }

    /**
     * @access public
     * @use to view tablename
     */
    static function tablename($res, $i)
    {
        return self::$driver->tablename($res, $i);
    }


    /**
     * INDEX-functions
     */
    //!Index
    static function addIndex($table, $field, $type, $name = null, $db_prefix = null)
    {
        return self::$driver->addIndex($table, $field, $type, $name, $db_prefix);
    }

    static function dropIndex($table, $name, $db_prefix = null)
    {
        return self::$driver->dropIndex($table, $name, $db_prefix);
    }

    static function getIndexes($table, $db_prefix = null)
    {
        return self::$driver->getIndexes($table, $db_prefix);
    }

    /**
     * writes the manipulation-array in the database
     * e.g. array(
     *        "pages"    => array(
     *            "id"        => "1",
     *            "command"    => "UPDATE",
     *            "fields"    => array(
     *                "path"    => "neu"
     *            )
     *        )
     * )
     * @name writeManipulation
     * @access public
     * @param array - manipulation
     */
    static function writeManipulation($ma)
    {
        return self::$driver->writeManipulation($ma);
    }

    /**
     * the same like writeManipulation
     * @name manipulate
     * @access public
     */
    static function manipulate($ma)
    {
        return self::writeManipulation($ma);
    }

    /**
     * table-functions V2
     */
    //!Table

    /**
     * lists all fields of the table
     */
    static function getFieldsOfTable($table, $prefix = null, $track = true)
    {
        if (self::$driver)
            return self::$driver->getFieldsOfTable($table, $prefix, $track);
        else
            return array();
    }

    /**
     * gets much information about a table, e.g. field-names, default-values, field-types
     *
     * @name showTableDetails
     * @access public
     * @param string - table
     * @param bool - if track query
     * @param string - prefix
     */
    static function showTableDetails($table, $track = true, $prefix = null)
    {
        return self::$driver->showTableDetails($table, $track, $prefix);
    }

    /**
     * requires, that a table is exactly in this form
     *
     * @name requireTable
     * @access public
     * @param string - table
     * @param array - fields
     * @param array - indexes
     * @param array - defaults
     * @param string - prefix
     */
    static function requireTable($table, $fields, $indexes, $defaults, $prefix = null)
    {
        return self::$driver->requireTable($table, $fields, $indexes, $defaults, $prefix);
    }

    /**
     * deletes a table
     *
     * @param string - table
     * @param string - prefix
     */
    static function dontRequireTable($table, $prefix = null)
    {
        return self::$driver->dontRequireTable($table, $prefix);
    }

    /**
     * sets the charset UTF-8
     *
     * @name setCharsetUTF8
     */
    static function setCharsetUTF8()
    {
        return self::$driver->setCharsetUTF8();
    }

    static function listStorageEngines()
    {
        return self::$driver->listStorageEngines();
    }

    static function setStorageEngine($table, $engine)
    {
        return self::$driver->setStorageEngine($table, $engine);
    }

    /**
     * extract to where
     *
     * @name extractToWhere
     * @access public
     * @param array $where
     * @param bool $includeWhere
     * @param array $DBFields to set field tables if you have various multi-table-fields
     * @param array $collidingFields coliding fields
     * @return string
     */
    public static function extractToWhere($where, $includeWhere = true, $DBFields = array(), $collidingFields = array())
    {
        // WHERE
        $sql = "";
        if (is_array($where) && count($where) > 0) {
            $conjunction = "";
            $firstQuery = true;
            $whereAdded = !$includeWhere;
            $currentQuery = "";
            $skipConjunctionCheck = false;
            foreach ($where as $field => $value) {
                $sql = self::addQueryPart($sql, $currentQuery, $conjunction, $firstQuery, $whereAdded);
                $currentQuery = "";

                if(!$skipConjunctionCheck) {
                    if (RegexpUtil::isNumber($field) && ($value == "OR" || $value == "||")) {
                        $conjunction = " OR ";
                        $skipConjunctionCheck = true;
                        continue;
                    } else {
                        $conjunction = " AND ";
                    }
                } else {
                    $skipConjunctionCheck = false;
                }

                // support for subqueries
                $field = trim($field);
                if (RegexpUtil::isNumber($field)) {
                    if (is_array($value)) {
                        $subQuery = self::extractToWhere($value, false, $DBFields, $collidingFields);
                        if($subQuery) {
                            $currentQuery = "(".$subQuery.")";
                        }
                    } else if (is_string($value)) {
                        $currentQuery = " ( ".$value." ) ";
                    }
                    continue;
                }

                // patch for colliding fields
                if (!isset($DBFields[$field]) && isset($collidingFields[$field]) && count(
                        $collidingFields[$field]
                    ) > 0) {
                    $currentQuery .= " ( ";

                    $b = 0;

                    foreach ($collidingFields[$field] as $alias) {
                        if ($b == 0) {
                            $b++;
                        } else {
                            $currentQuery .= " OR ";
                        }
                        $currentQuery .= "(".$alias.".".$field." IS NOT NULL AND ";
                        $currentQuery .= self::parseValue($alias.".".$field, $value);
                        $currentQuery .= ")";
                    }
                    $currentQuery .= " ) ";
                    continue;
                }

                if (isset($DBFields[$field])) {
                    $field = $DBFields[$field][0].".".$DBFields[$field][1];
                }

                $currentQuery = self::parseValue($field, $value);
            }
            $sql = self::addQueryPart($sql, $currentQuery, $conjunction, $firstQuery, $whereAdded);
        } else if (is_string($where)) {
            if ($includeWhere) {
                $sql .= " WHERE ";
            }

            $sql .= $where;
        }

        return $sql;
    }

    /**
     * @param string $sql
     * @param string $currentQuery
     * @param string $conjunction
     * @param bool $firstQuery
     * @param bool $whereAdded
     * @return string
     */
    protected static function addQueryPart($sql, $currentQuery, $conjunction, &$firstQuery, &$whereAdded) {
        if(trim($currentQuery)) {
            if(!$whereAdded) {
                $sql .= " WHERE ";
                $whereAdded = true;
            }

            if($firstQuery) {
                $firstQuery = false;
            } else {
                $sql .= $conjunction;
            }

            $sql .= $currentQuery;
        }

        return $sql;
    }

    /**
     * returns the part of parsing value attribute
     *
     * @param string $field
     * @param string $value
     * @return string
     */
    static function parseValue($field, $value)
    {
        if($value === null) {
            return ' ' . convert::raw2sql($field) . ' IS NULL ';
        } else
        if (is_array($value) && count($value) == 2 && isset($value[1], $value[0]) && ($value[0] == "LIKE" || $value[0] == ">" || $value[0] == "!=" || $value[0] == "<" || $value[0] == ">=" || $value[0] == "<=" || $value[0] == "<>")) {
            if ($value[0] == "LIKE") {
                return ' ' . convert::raw2sql($field) . ' ' . (defined("SQL_LIKE") ? SQL_LIKE : "LIKE") . ' "' . convert::raw2sql($value[1]) . '"';
            } else {
                return ' ' . convert::raw2sql($field) . ' ' . $value[0] . ' "' . convert::raw2sql($value[1]) . '"';
            }

        } else if (is_array($value)) {
            return ' ' . convert::raw2sql($field) . ' IN ("' . implode('","', array_map(array("convert", "raw2sql"), $value)) . '")';
        } else {
            return ' ' . convert::raw2sql($field) . ' = "' . convert::raw2sql($value) . '"';
        }
    }
}

/**
 * interface for all SQL-Drivers
 *
 * @name SQLDriver
 */
interface SQLDriver
{
    /**
     * @name __construct
     * @access public
     */
    public function __construct();

    /**
     * connects to db
     * @name connect
     * @param username
     * @param databasename
     * @param password
     * @param hostname
     */
    public function connect($dbuser, $dbdb, $dbpass, $host);

    /**
     * tests db.
     * @name connect
     * @param username
     * @param databasename
     * @param password
     * @param hostname
     * @return bool
     */
    public function test($dbuser, $dbdb, $dbpass, $host);

    /**
     * runs a query
     * @name query
     * @access public
     * @param string - query
     * @param bool - if unbuffered
     */
    public function query($sql, $unbuffered = false, $debug = true);

    /**
     * the following functions are simly sql-functions
     */
    public function fetch_row($result);

    public function close();

    public function fetch_object($result);

    public function fetch_array($result);

    public function fetch_assoc($result);

    public function num_rows($result);

    public function error();

    public function errno();

    public function insert_id();

    public function free_result($result);

    public function escape_string($str);

    public function real_escape_string($str);

    public function affected_rows();

    public function protect($str);

    /**
     * splits more than one query at the ;
     * @name split
     * @access public
     * @param string - queries
     */
    public function split($sql);

    public function list_tables($database);

    /**
     * table-functions
     */
    public function getFieldsOfTable($table, $prefix = null, $track = true);

    /**
     * table-functions V2
     */
    public function showTableDetails($table, $track = true, $prefix = null);

    public function requireTable($table, $fields, $indexes, $defaults, $prefix = null);

    public function dontRequireTable($table, $prefix = null);

    /**
     * INDEX-functions
     */
    public function addIndex($table, $field, $type, $name = null, $db_prefix = null);

    public function dropIndex($table, $name, $db_prefix = null);

    public function getIndexes($table, $db_prefix = null);

    public function writeManipulation($manipulation);

    /**
     * storage engines.
     */
    public function listStorageEngines();

    public function setStorageEngine($table, $engine);

    public function setCharsetUTF8();

}

/**
 * logs slow queries
 *
 * this information may uploaded to the goma-server for debug-use
 *
 * @name slow_query_log
 * @access public
 * @param string - debug-string
 */
function slow_query_log($data)
{
    FileSystem::requireFolder(ROOT . CURRENT_PROJECT . "/" . LOG_FOLDER . "/slow_queries/");
    $date_format = (defined("DATE_FORMAT")) ? DATE_FORMAT : "Y-m-d H:i:s";
    FileSystem::requireFolder(ROOT . CURRENT_PROJECT . "/" . LOG_FOLDER . "/slow_queries/" . date("m-d-y"));
    $folder = ROOT . CURRENT_PROJECT . "/" . LOG_FOLDER . "/slow_queries/" . date("m-d-y") . "/" . date("H_i_s");
    $file = $folder . "-1.log";
    $i = 1;
    while (file_exists($folder . "-" . $i . ".log")) {
        $i++;
        $file = $folder . "-" . $i . ".log";
    }

    FileSystem::write($file, $data, null, 0777);
}
