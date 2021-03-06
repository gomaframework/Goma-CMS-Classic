<?php defined('IN_GOMA') OR die();
/**
 * @package goma framework
 * @link http://goma-cms.org
 * @license: LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 * @author Goma-Team
 * last modified: 04.08.2015
 */

// REGEXP for your SQL
define('SQL_REGEXP', 'RLIKE');
// LIKE for your SQL without a differnet between A and a
define('SQL_LIKE', 'LIKE');

class mysqliDriver implements SQLDriver
{

    /**
     * @access public
     * @var MySQLi
     * @use for the mysql connetion
     **/
    public $_db;

    public $version;
    public $engines;
    public $tableStatuses;

    protected $error;
    protected $errno;

    /**
     * @access public
     * @use: connect to db
     * @param bool $autoConnect
     * @throws DBConnectError
     */
    public function __construct($autoConnect = false)
    {
        /* --- */
        if (!defined("NO_AUTO_CONNECT") && $autoConnect) {
            global $dbuser;

            global $dbdb;
            global $dbpass;
            global $dbhost;
            if (!isset($this->_db)) {
                self::connect($dbuser, $dbdb, $dbpass, $dbhost);
            }
        }

    }

    /**
     * tries to connect to db. in case of not existing database, tries to create it.
     **/
    public function connect($dbuser, $dbdb, $dbpass, $dbhost)
    {
        $this->_db = new MySQLi($dbhost, $dbuser, $dbpass, $dbdb);
        if (mysqli_connect_errno()) {
            if ($test = new MySQLi($dbhost, $dbuser, $dbpass)) {
                logging("Create DataBase " . $dbdb);
                if ($test->query("CREATE DATABASE " . $dbdb . " DEFAULT COLLATE = utf8_unicode_ci")) {
                    $test->close();
                    return $this->connect($dbuser, $dbdb, $dbpass, $dbhost);
                }
            }

            throw new DBConnectError();
        }

        self::setCharsetUTF8();
        $this->query("SET sql_mode = '';");

        return true;
    }

    /**
     *
     */
    public function test($dbuser, $dbdb, $dbpass, $dbhost)
    {
        $test = @new MySQLi($dbhost, $dbuser, $dbpass, $dbdb);
        if (!mysqli_connect_errno()) {
            $test->close();
            return true;
        } else {
            if ($test = new MySQLi($dbhost, $dbuser, $dbpass)) {
                logging("Create DataBase " . $dbdb);
                if ($test->query("CREATE DATABASE " . $dbdb . " DEFAULT COLLATE = utf8_unicode_ci")) {
                    $test->close();
                    return true;
                } else {
                    logging($test->error);
                }
            }
            return false;
        }
    }

    /**
     * @param string $sql
     * @param bool $unbuffered
     * @param bool $debug
     * @return bool|mysqli_result
     * @throws SQLException
     */
    public function query($sql, $unbuffered = false, $debug = true)
    {
        if (!$this->_db->ping()) {
            $this->__construct();
        }

        if ($result = $this->_db->query($sql, $unbuffered ? MYSQLI_ASYNC : MYSQLI_STORE_RESULT)) {
            return $result;
        } else {
            $this->error = $this->_db->error;
            $this->errno = $this->_db->errno;

            if ($debug) {
                if(isPHPUnit()) {
                    throw new SQLException($sql);
                }

                $trace = debug_backtrace();
                log_error('SQL-Error in Statement: ' . $sql . ' in ' . $trace[1]["file"] . ' on line ' . $trace[1]["line"] . '. ' .
                    $this->errno . ": " . $this->error);
                $this->runDebug($sql);
                unset($trace);
            }

            return false;
        }
    }

    /**
     * some debug-operations
     *
     * @param string $sql
     * @throws MySQLException
     */
    public function runDebug($sql)
    {
        SQL::$track = false;
        if ($this->errno() == 1054) {
            // match out table
            if (preg_match('/from\s+([a-zA-Z0-9_\-]+)/i', $sql, $matches)) {
                $table = $matches[1];
                if (substr($table, 0, strlen(DB_PREFIX))) {
                    $table = substr($table, strlen(DB_PREFIX));
                }

                if (isset(ClassInfo::$tables[$table])) {
                    $class = ClassInfo::$tables[$table];

                    /** @var DataObject $dataObject */
                    $dataObject = new $class();
                    $dataObject->buildDB(DB_PREFIX);
                }
            }
        }
        SQL::$track = true;
    }

    /**
     * @param MySQLi_Result $result
     * @return StdClass
     */
    public function fetch_row($result)
    {
        return $result->fetch_row();
    }

    /**
     * @access public
     * @use to diconnect
     **/
    public function close()
    {
        $this->_db->close();
    }

    /**
     * @param MySQLi_Result $result
     * @return StdClass
     **/
    public function fetch_object($result)
    {
        if(!isset($result)) {
            throw new LogicException("Result can't be null.");
        }
        return $result->fetch_object();
    }

    /**
     * @param MySQLi_Result $result
     * @return array
     */
    public function fetch_array($result)
    {
        if(!isset($result)) {
            throw new LogicException("Result can't be null.");
        }
        return $result->fetch_array();
    }

    /**
     * @param MySQLi_Result $result
     * @return array
     */
    public function fetch_assoc($result)
    {
        if(!isset($result)) {
            throw new LogicException("Result can't be null.");
        }

        return $result->fetch_assoc();
    }

    /**
     * @param MySQLi_Result $result
     * @return int
     */
    public function num_rows($result)
    {
        if(!isset($result)) {
            throw new LogicException("Result can't be null.");
        }

        return $result->num_rows;
    }

    /**
     *
     */
    public function error()
    {
        return $this->error;
    }

    /**
     *
     */
    public function errno()
    {
        return $this->errno;
    }

    /**
     *
     */
    public function insert_id()
    {
        return $this->_db->insert_id;
    }

    /**
     * @param MySQLi_Result $result
     */
    public function free_result($result)
    {
        $result->free();
    }

    /**
     * @access public
     * @use to protect
     */
    public function escape_string($str)
    {
        if (is_array($str)) {
            throw new InvalidArgumentException("Array is not allowed as given value for escape_string. Expected string.");
        }
        if (is_object($str)) {
            throw new InvalidArgumentException("Object is not allowed as given value for escape_string. Expected string.");
        }

        return $this->_db->real_escape_string((string)$str);
    }

    /**
     * @access public
     * @use to protect
     */
    public function real_escape_string($str)
    {
        if (is_array($str)) {
            throw new InvalidArgumentException("Array is not allowed as given value for escape_string. Expected string.");
        }
        if (is_object($str)) {
            throw new InvalidArgumentException("Object is not allowed as given value for escape_string. Expected string.");
        }

        return $this->_db->real_escape_string((string)$str);
    }

    /**
     * @access public
     * @use to protect
     */
    public function protect($str)
    {
        return self::real_escape_string($str);
    }

    /**
     * @access public
     * @use to split queries
     */
    public function split($sql)
    {
        $queries = preg_split('/;\s*\n/', $sql, -1, PREG_SPLIT_NO_EMPTY);
        return $queries;
    }

    /**
     * affected rows of last operation.
     *
     * @name affected_rows
     * @access public
     * @return int
     */
    public function affected_rows()
    {
        return $this->_db->affected_rows;
    }

    /**
     * @access public
     * @use to view tables
     * @param  string $database
     * @return array
     */
    public function list_tables($database)
    {
        $list = array();
        if ($result = sql::query("SHOW TABLES FROM " . $database . "")) {
            while ($row = $this->fetch_array($result)) {
                $list[] = $row[0];
            }
        }
        return $list;
    }

    /**
     * this function checks, if the table exists and get all fields
     * it returns false when table doesn't exist
     *
     * @param string $table table-name without prefix
     * @param string|null $prefix
     * @param bool $track
     * @return array|false
     */
    public function getFieldsOfTable($table, $prefix = null, $track = true)
    {
        if (!isset($prefix))
            $prefix = DB_PREFIX;

        $sql = "SHOW COLUMNS FROM " . $prefix . $table . "";
        if ($result = sql::query($sql, false, $track, false)) {
            $fields = array();
            while ($row = $this->fetch_object($result)) {
                $fields[$row->Field] = $row->Type;
            }
            return $fields;
        } else {
            return false;
        }
    }

    //!Index-Methods

    /**
     * @param string $table
     * @param string $field
     * @param string $type
     * @param string $name
     * @param string|null $db_prefix
     * @return bool
     * @throws MySQLException
     */
    public function addIndex($table, $field, $type, $name = null, $db_prefix = null)
    {
        if ($db_prefix === null)
            $db_prefix = DB_PREFIX;

        switch (strtolower($type)) {
            case "unique":
                $type = "UNIQUE";
                break;
            CASE "fulltext":
                $type = "FULLTEXT";
                break;
            default:
                $type = "INDEX";
                break;
        }

        if (is_array($field)) {
            $field = implode(',', $field);
        }

        $name = ($name === null) ? "" : $name;

        $sql = "ALTER TABLE " . $db_prefix . $table . " ADD " . $type . " " . $name . " (" . $field . ")";
        if (sql::query($sql)) {
            return true;
        } else {
            throw new MySQLException();
        }
    }

    /**
     * @param string $table
     * @param string $name
     * @param string|null $db_prefix
     * @return bool
     * @throws MySQLException
     */
    public function dropIndex($table, $name, $db_prefix = null)
    {
        if ($db_prefix === null)
            $db_prefix = DB_PREFIX;

        $sql = "ALTER TABLE " . $db_prefix . $table . " DROP INDEX " . $name;
        if (sql::query($sql)) {
            return true;
        } else {
            throw new MySQLException();
        }
    }

    /**
     * gets the indexes of a table
     *
     * @name getIndexes
     * @param string $table
     * @param string|null $db_prefix
     * @return array|bool
     */
    public function getIndexes($table, $db_prefix = null)
    {
        if ($db_prefix === null)
            $db_prefix = DB_PREFIX;

        $indexes = array();
        $sql = "SHOW INDEXES FROM " . $db_prefix . $table . "";
        if ($result = sql::query($sql)) {
            while ($row = sql::fetch_object($result)) {
                if (!isset($indexes[$row->Key_name])) {
                    if($row->Key_name == "PRIMARY") {
                        $type = "PRIMARY";
                    } else if(in_array($row->Index_type, array("FULLTEXT", "SPATIAL"))) {
                        $type = $row->Index_type;
                    } else if ($row->Non_unique == 0) {
                        $type = "UNIQUE";
                    } else {
                        $type = "INDEX";
                    }


                    $indexes[$row->Key_name] = array("fields" => array(), "type" => $type);
                }
                $indexes[$row->Key_name]["fields"][] = $row->Column_name;
            }
            return $indexes;
        } else {
            return false;
        }
    }


    /**
     * table-functions V2
     */
    //!Table-API

    /**
     * gets much information about a table, e.g. field-names, default-values, field-types
     *
     * @name showTableDetails
     * @access public
     * @param string $table
     * @param bool $track if to track query
     * @param string|null $prefix
     * @return array|bool
     */
    public function showTableDetails($table, $track = true, $prefix = null)
    {
        if (!isset($prefix))
            $prefix = DB_PREFIX;


        $sql = "SHOW COLUMNS FROM " . $prefix . $table;
        if ($result = sql::query($sql, false, $track, false)) {
            $fields = array();
            while ($row = $this->fetch_object($result)) {
                $fields[strtolower($row->Field)] = array(
                    "type" => $row->Type,
                    "key" => $row->Key,
                    "default" => $row->Default,
                    "extra" => $row->Extra,
                    "null" => $row->Null == "YES" ? true : false
                );
            }
            return $fields;
        } else {
            return false;
        }
    }

    /**
     * requires, that a table is exactly in this form
     *
     * @param string $table
     * @param array $fields
     * @param array $indexes
     * @param array $defaults
     * @param string|null $prefix
     * @return string
     * @throws MySQLException
     */
    public function requireTable($table, $fields, $indexes, $defaults, $prefix = null)
    {
        if (!isset($prefix))
            $prefix = DB_PREFIX;

        $this->tableStatuses = null;

        if ($tableInfo = $this->showTableDetails($table, true, $prefix)) {
            $this->updateTable($tableInfo, $table, $fields, $indexes, $defaults, $prefix);
        } else {
            return $this->createTable($table, $fields, $indexes, $defaults, $prefix);
        }
    }

    /**
     * updates a table with given constraints.
     *
     * @param array $tableInfo
     * @param string $table
     * @param array $fields
     * @param array $indexes
     * @param array $defaults
     * @param string $prefix
     * @return string
     * @throws MySQLException
     */
    protected function updateTable($tableInfo, $table, $fields, $indexes, $defaults, $prefix) {
        $editsql = 'ALTER TABLE ' . $prefix . $table . ' ';

        // get fields missing

        $updates = "";
        $log = "";

        foreach ($fields as $name => $type) {
            if ($name == "id")
                continue;

            if (!isset($tableInfo[$name])) {
                $editsql .= ' ADD ' . $name . ' ' . $type . ' ';
                if (isset($defaults[$name])) {
                    $editsql .= ' DEFAULT "' . addslashes($defaults[$name]) . '"';
                    $updates .= ' ' . $name . ' = "' . addslashes($defaults[$name]) . '",';
                }
                if(!DBField::definesNullInfo($type)) {
                    $editsql .= " NOT NULL";
                }
                $editsql .= ",";

                $log .= "ADD Field " . $name . " " . $type . "\n";
            } else {
                // type value
                $type = str_replace(", ", ",", $type);
                if (self::fieldNeedsTypeUpdate($tableInfo[$name], $type) ||
                    self::fieldNeedsDefaultUpdate($name, $tableInfo[$name], $defaults) ||
                    self::fieldNeedsDropDefault($name, $tableInfo[$name], $defaults)) {
                    $editsql .= " MODIFY " . $name . " " . $type . "";
                    if(!DBField::definesNullInfo($type)) {
                        $editsql .= " NOT NULL";
                    }

                    if(self::fieldNeedsDropDefault($name, $tableInfo[$name], $defaults)) {
                        if(DBField::isNullType($type)) {
                            $editsql .= " DEFAULT NULL";
                        }
                    } else if(self::fieldNeedsDefaultUpdate($name, $tableInfo[$name], $defaults)) {
                        if($this->isFieldInt($type)) {
                            if($defaults[$name] != 0) {
                                $editsql .= " DEFAULT " . ((int)$defaults[$name]) . "";
                            }
                        } else {
                            $editsql .= " DEFAULT \"" . addslashes($defaults[$name]) . "\"";
                        }
                    }
                    $editsql .= ",";
                    $log .= "Modify Field " . $name . " from " . $tableInfo[$name]["type"] . " to " . $type . "\n";
                }
            }
        }

        // get fields too much
        foreach ($tableInfo as $name => $_data) {
            if ($name != "id" && !isset($fields[$name])) {
                // patch
                if ($name == "default") $name = '`default`';
                if ($name == "read") $name = '`read`';
                $editsql .= ' DROP COLUMN ' . $name . ',';
                $log .= "Drop Field " . $name . "\n";
            }
        }

        $currentindexes = $this->getIndexes($table, $prefix);
        $allowed_indexes = array(); // for later delete

        // sort sql, so first drop and then add
        $removeindexsql = "";
        $addindexsql = "";

        $forceUserMyISAM = false;
        // check indexes
        foreach ($indexes as $key => $tableInfo) {
            if (!$tableInfo)
                continue;

            if (is_array($tableInfo)) {
                $name = $tableInfo["name"];
                $ifields = $tableInfo["fields"];
                $type = $tableInfo["type"];
            } else if (preg_match("/\(/", $tableInfo)) {
                $name = $key;
                $allowed_indexes[$name] = true;
                if (isset($currentindexes[$key])) {
                    $removeindexsql .= " DROP INDEX " . $key . ",";
                }
                $addindexsql .= " ADD " . $tableInfo . ",";
                continue;
            } else {
                $name = $key;
                $ifields = array($key);
                $type = $tableInfo;
            }
            $allowed_indexes[$name] = true;

            switch (strtolower($type)) {
                case "unique":
                    $type = "UNIQUE";
                    break;
                case "fulltext":
                    $type = "FULLTEXT";
                    break;
                case "spatial":
                    $type = "SPATIAL";
                    if(!$forceUserMyISAM) {
                        $forceUserMyISAM = true;
                        $this->setStorageEngine($prefix . $table, "MyISAM");
                    }
                    break;
                case "index":
                    $type = "INDEX";
                    break;
            }

            if (!isset($currentindexes[$name])) { // we have to create the index
                $addindexsql .= " ADD " . $type . " " . $name . " (" . implode(",", $ifields) . "),";
                $log .= "Add Index " . $name . "\n";
            } else {
                // create matchable fields
                $mfields = array();
                foreach ($ifields as $key => $value) {
                    $mfields[$key] = preg_replace('/\((.*)\)/', "", $value);
                }

                if ($currentindexes[$name]["type"] != $type || count(array_diff($currentindexes[$name]["fields"], $mfields)) > 0) {
                    $removeindexsql .= " DROP INDEX " . $name . ",";
                    $addindexsql .= " ADD " . $type . " " . $name . "  (" . implode(",", $ifields) . "),";
                    $log .= "Change Index " . $name . "\n";
                }
                unset($mfields, $ifields);
            }
        }

        // check not longer needed indexes
        foreach ($currentindexes as $name => $tableInfo) {
            if ($tableInfo["type"] != "PRIMARY" && !isset($allowed_indexes[$name])) {
                // sry, it's a hack for older versions
                if ($name == "show") $name = '`' . $name . '`';
                $removeindexsql .= " DROP INDEX " . $name . ", ";
                $log .= "Drop Index " . $name . "\n";
            }
        }

        // add sql
        $editsql .= $removeindexsql;
        $editsql .= $addindexsql;
        unset($removeindexsql, $addindexsql);

        // run query
        $editsql = trim($editsql);

        if (substr($editsql, -1) == ",") {
            $editsql = substr($editsql, 0, -1);
        }

        if (sql::query($editsql)) {
            if ($updates) {
                $updates = "UPDATE " . $prefix . $table . " SET " . $updates;
                if (substr($updates, -1) == ",") {
                    $updates = substr($updates, 0, -1);
                }
                if (!SQL::Query($updates)) {
                    throw new MySQLException();
                }
            }

            if ($version = $this->getServerVersion()) {
                $engines = $this->listStorageEngines();
                $tableStatuses = $this->listStorageEnginesByTable();

                if(isset($tableStatuses[strtolower($prefix . $table)])) {
                    if (!$forceUserMyISAM && version_compare($version, "5.6", ">=") && isset($engines["innodb"])) {
                        if ($tableStatuses[strtolower($prefix . $table)]["Engine"] != "InnoDB") {
                            $this->setStorageEngine($prefix . $table, "InnoDB");
                        }
                    } else if (isset($engines["myisam"])) {
                        if ($tableStatuses[strtolower($prefix . $table)]["Engine"] != "MyISAM") {
                            $this->setStorageEngine($prefix . $table, "MyISAM");
                        }
                    }
                } else {
                    log_error("Could not find Table {$prefix}{$table} in " . print_r($tableStatuses, true));
                    throw new LogicException("Trying to update Table-Status of non-existing table {$prefix}{$table}.");
                }
            }

            ClassInfo::$database[$table] = $fields;
            return $log;
        } else
            throw new MySQLException();
    }

    /**
     * returns true if field needs type update.
     *
     * @param array $info
     * @param string $type
     * @return bool
     */
    protected static function fieldNeedsTypeUpdate($info, $type) {
        if (str_replace('"', "'", $info["type"]) != $type &&
            str_replace("'", '"', $info["type"]) != $type &&
            $info["type"] != $type) {
            return true;
        }

        // if nullable collumn shouldn't be nullable anymore
        if($info["null"] && !DBField::isNullType($type)) {
            return true;
        }

        // if not nullable column should be nullable
        if(!$info["null"] && DBField::isNullType($type)) {
            return true;
        }

        return false;
    }

    /**
     * returns true if field needs "default" update.
     *
     * @param string $name
     * @param array $info
     * @param string $defaults
     * @return bool
     */
    protected static function fieldNeedsDefaultUpdate($name, $info, $defaults) {
        return isset($defaults[$name]) &&
        $info["default"] != $defaults[$name] &&
        strtolower($info["type"]) != "text" &&
        strtolower($info["type"]) != "blob";
    }

    /**
     * @param string $name
     * @param array $info
     * @param array $defaults
     * @return bool
     */
    protected static function fieldNeedsDropDefault($name, $info, $defaults) {
        return !isset($defaults[$name]) && isset($info["default"]);
    }

    protected function isFieldInt($field) {
        return strtolower(substr(trim($field), 0, 3)) == "int";
    }

    /**
     * creates a table with given constraints.
     *
     * @param string $table
     * @param array $fields
     * @param array $indexes
     * @param array $defaults
     * @param string $prefix
     * @return string
     * @throws MySQLException
     */
    protected function createTable($table, $fields, $indexes, $defaults, $prefix) {
        $sql = "CREATE TABLE " . $prefix . $table . " ( ";
        $i = 0;
        foreach ($fields as $name => $value) {
            if ($i == 0) {
                $i++;
            } else {
                $sql .= ",";
            }

            $sql .= ' ' . $name . ' ' . $value . ' ';
            if (isset($defaults[$name]) &&
                trim(strtolower($value)) != "text" && trim(strtolower($value)) != "blob") {
                if($this->isFieldInt($value)) {
                    if($defaults[$name] != 0) {
                        $sql .= " DEFAULT " . ((int)$defaults[$name]) . "";
                    }
                } else {
                    $sql .= " DEFAULT '" . addslashes($defaults[$name]) . "'";
                }
            } else if(!DBField::definesNullInfo($value)) {
                $sql .= " NOT NULL";
            }
        }

        foreach ($indexes as $key => $data) {
            if ($i == 0) {
                $i++;
            } else {
                $sql .= ",";
            }
            if (is_array($data)) {
                $name = $data["name"];
                $type = $data["type"];
                $ifields = $data["fields"];
            } else if (preg_match("/\(/", $data)) {
                $sql .= $data;
                continue;
            } else {
                $name = $field = $key;
                $ifields = array($field);
                $type = $data;
            }

            switch (strtolower($type)) {
                case "fulltext":
                    $type = "FULLTEXT";
                    break;
                case "unique":
                    $type = "UNIQUE";
                    break;
                case "spatial":
                    $type = "SPATIAL";
                    $forceUserMyISAM = true;
                    break;
                case "index":
                default:
                    $type = "INDEX";
                    break;
            }

            $sql .= '' . $type . ' ' . $name . ' (' . implode(',', $ifields) . ')';
        }
        $sql .= ") DEFAULT CHARACTER SET 'utf8' COLLATE utf8_unicode_ci";

        if (sql::query($sql)) {
            ClassInfo::$database[$table] = $fields;

            if ($version = $this->getServerVersion()) {
                $engines = $this->listStorageEngines();

                if (version_compare($version, "5.6", ">=") && isset($engines["innodb"])) {
                    $this->setStorageEngine($prefix . $table, "InnoDB");
                } else if (isset($engines["myisam"])) {
                    $this->setStorageEngine($prefix . $table, "MyISAM");
                }
            }

            return $sql;
        } else {
            throw new MySQLException();
        }
    }

    /**
     * deletes a table
     *
     * @name dontRequireTable
     * @access public
     * @param string $table
     * @param string|null $prefix
     * @return bool
     */
    public function dontRequireTable($table, $prefix = null)
    {
        if (!isset($prefix))
            $prefix = DB_PREFIX;

        if ($this->showTableDetails($table, true, $prefix)) {
            return sql::query('DROP TABLE ' . $prefix . $table);
        }
        return true;
    }

    /**
     * writes the manipulation-array in the database
     * there are three types of manipulation:
     * - insert
     * - update
     * - delete
     *
     * @param array $manipulation
     * @return bool
     * @throws MySQLException
     */
    public function writeManipulation($manipulation)
    {
        if (PROFILE) Profiler::mark("MySQLi::writeManipulation");
        try {
            $manipulation = $this->extractManipulationSQL($manipulation);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage() . print_r($manipulation, true), $e->getCode(), $e);
        }

        foreach($manipulation as $info) {
            if(isset($info["sql"])) {
                // TODO: Error Handling
                SQL::query($info["sql"]);
            }
        }

        if (PROFILE) Profiler::unmark("MySQLi::writeManipulation");
        return true;
    }

    /**
     * @param array $manipulation
     * @return mixed
     */
    protected function extractManipulationSQL($manipulation) {
        foreach ($manipulation as $class => $data) {
            switch (strtolower($data["command"])) {
                case "update":
                    if (isset($data["id"]) || isset($data["where"])) {
                        if (isset($data["fields"]) && count($data["fields"]) > 0) {
                            if (
                                (isset($data["table_name"]) && $table_name = $data["table_name"]) ||
                                (ModelInfoGenerator::classTable($class) && $table_name = ModelInfoGenerator::classTable($class))
                            ) {
                                if (isset($data["ignore"]) && $data["ignore"])
                                    $sql = "UPDATE IGNORE " . DB_PREFIX . $table_name . " SET ";
                                else
                                    $sql = "UPDATE " . DB_PREFIX . $table_name . " SET ";

                                $i = 0;
                                foreach ($data["fields"] as $field => $value) {
                                    if ($i == 0) {
                                        $i++;
                                    } else {
                                        $sql .= " , ";
                                    }

                                    $casting = DBField::getObjectByCasting(ClassInfo::$database[$table_name][$field], $field, $value);
                                    $sql .= " " . $field . " = " . $casting->forDBQuery() . " ";
                                }
                                unset($i);

                                if (isset($data["id"])) {
                                    $id = $data["id"];

                                    $sql .= " WHERE id = '" . convert::raw2sql($id) . "'";
                                    unset($id);
                                } else if (isset($data["where"])) {
                                    $where = $data["where"];
                                    $where = SQL::extractToWhere($where);
                                    $sql .= $where;
                                    unset($where);
                                } else {
                                    throw new InvalidArgumentException("Update requires id or WHERE-Clause." . print_r($data, true));
                                }

                                $manipulation[$class]["sql"] = $sql;
                            } else {
                                throw new InvalidArgumentException("Table for Update does not exist. The key is table_name. " . print_r($data, true));
                            }
                        }
                    } else {
                        throw new InvalidArgumentException("Update requires specification of id or where." . print_r($data, true));
                    }
                    break;
                case "insert":
                    $manipulation[$class]["sql"] = $this->manipulateInsert($data, $class);
                    break;
                case "delete":
                    if (!isset($data["where"]) && isset($data["id"]))
                        $data["where"]["id"] = $data["id"];

                    if (isset($data["where"])) {
                        if (
                            (isset($data["table"]) && $table_name = $data["table"]) ||
                            (isset($data["table_name"]) && $table_name = $data["table_name"]) ||
                            (ModelInfoGenerator::classTable($class) && $table_name = ModelInfoGenerator::classTable($class))
                        ) {
                            $where = $data["where"];
                            $where = SQL::extractToWhere($where);

                            $sql = "DELETE FROM " . DB_PREFIX . $table_name . $where;

                            $manipulation[$class]["sql"] = $sql;
                        } else {
                            throw new InvalidArgumentException("Table for Delete does not defined." . print_r($data, true));
                        }
                    } else {
                        throw new InvalidArgumentException("Delete requires specification of id or where." . print_r($data, true));
                    }

                    break;
                case "rawupdate":
                    if(!isset($data["sql"])) {
                        throw new InvalidArgumentException("rawupdate requires defined SQL.");
                    }
                    break;
                default:
                    if (PROFILE) Profiler::unmark("MySQLi::writeManipulation");
                    throw new InvalidArgumentException("Manipulation requires valid command: INSERT, DELETE, RAWUPDATE or UPDATE." . print_r($data, true));
            }
        }

        return $manipulation;
    }

    /**
     * creates insert operation out of manipulation.
     *
     * @param array $data
     * @param string $class
     * @return string
     */
    private function manipulateInsert($data, $class)
    {
        if(isset($data["fields"]) && count($data["fields"]) > 0) {
            if (
                (isset($data["table_name"]) && $table_name = $data["table_name"]) ||
                (ModelInfoGenerator::classTable($class) && $table_name = ModelInfoGenerator::classTable($class))
            ) {
                if (isset($data["ignore"]) && $data["ignore"]) {
                    $sql = 'INSERT IGNORE INTO ' . DB_PREFIX . $table_name . ' ';
                } else {
                    $sql = 'INSERT INTO ' . DB_PREFIX . $table_name . ' ';
                }

                $fields = $this->getFieldsFromInsertManipulation($data);
                $sql .= "(".implode(",", array_map(array("convert", "raw2sql"), $fields)).")";

                $sql .= $this->getValuesSQL($data, $fields, $table_name);

                return $sql;
            } else {
                throw new InvalidArgumentException("Table for Insert does not exist." . print_r($data, true));
            }
        }
    }

    /**
     * returns values sql with the VALUES part of the query.
     *
     * @param array $data
     * @param array $fields
     * @param string $table
     * @return string
     */
    private function getValuesSQL($data, $fields, $table) {
        $records = $this->getRecords($data);

        $sql = " VALUES ( ";

        $recordCount = count($records);
        for($i = 0; $i < $recordCount; $i++) {

            if ($i != 0) {
                $sql .= " ) , ( ";
            }

            $record = $records[$i];

            if(count($record) != count($fields)) {
                throw new InvalidArgumentException("Every dictionary must have the same size of entries. \n" .
                    print_r($record, true) .
                    " fields: " . print_r($fields, true));
            }

            foreach($fields as $field) {
                if($field != $fields[0]) {
                    $sql .= ", ";
                }

                $casting = DBField::getObjectByCasting(
                    isset(ClassInfo::$database[$table][$field]) ? ClassInfo::$database[$table][$field] : null,
                    $field,
                    $record[$field]
                );
                $sql .= $casting->forDBQuery();
            }
        }

        $sql .= " ) ";

        return $sql;
    }

    /**
     * formats the records in a correct way.
     *
     * @param array $data
     * @return array
     */
    private function getRecords($data) {
        $records = $data["fields"];
        if(!is_array($records)) {
            throw new InvalidArgumentException("Fields must be an array.");
        }

        $values = array_values($records);
        if(isset($values[0]) && !is_array($values[0])) {
            $records = array($records);
        }

        return array_values($records);
    }

    /**
     * returns field out of data array.
     *
     * @param $data
     * @return array
     */
    private function getFieldsFromInsertManipulation($data) {
        if(!isset($data) || !$data) {
            throw new InvalidArgumentException("Fields must be an array with at least one field.");
        }

        $fields = $this->getRecords($data);

        if(!isset($fields[0])) {
            return array();
        }

        return array_keys($fields[0]);
    }

    /**
     * storage engines.
     */
    public function listStorageEngines()
    {

        if ($this->engines) {
            return $this->engines;
        }

        $sql = "SHOW ENGINES";
        if ($result = self::query($sql)) {
            $data = array();
            while ($row = self::fetch_assoc($result)) {
                if (strtolower($row["Support"]) != "NO") {
                    $data[strtolower($row["Engine"])] = strtolower($row["Engine"]);
                }
            }

            $this->engines = $data;
            return $data;
        }

        return array();
    }

    public function setStorageEngine($table, $engine)
    {
        $sql = "ALTER TABLE " . $table . " ENGINE = " . $engine . "";
        if (self::query($sql)) {
            return true;
        } else {
            return false;
        }
    }

    public function getServerVersion()
    {
        if ($this->version) {
            return $this->version;
        }

        $sql = "SHOW VARIABLES LIKE 'version'";
        if ($result = $this->Query($sql)) {
            if ($row = $this->fetch_assoc($result)) {
                $this->version = $row["Value"];
                return $this->version;
            }
        }

        return false;
    }

    public function listStorageEnginesByTable()
    {
        if ($this->tableStatuses) {
            return $this->tableStatuses;
        }

        $data = array();
        $sql = "SHOW TABLE STATUS";
        if ($result = $this->query($sql)) {
            while ($row = $this->fetch_assoc($result)) {
                $data[strtolower($row["Name"])] = $row;
            }

            $this->tableStatuses = $data;
            return $data;
        }

        return false;
    }

    /**
     * sets the charset to utf-8
     *
     * @name setCharsetUTF8
     * @access public
     */
    public function setCharsetUTF8()
    {
        $this->_db->set_charset("utf8");
    }
}
