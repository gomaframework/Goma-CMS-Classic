<?php defined("IN_GOMA") OR die();

/**
 * Basic class for all models with DB-Connection of Goma.
 *
 * this is a Basic class for all Models that need DataBase-Connection
 * it creates tables based on db-fields, has-one-, has-many- and many-many-connections
 * it gets data and makes it available as normal attributes
 * it can write and remove data
 *
 * @package     Goma\Model
 *
 * @license     GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author      Goma-Team
 *
 * @version     4.7.30
 *
 * @property    int versionid
 * @property    int id
 * @property    string baseTable
 * @property    string baseClass
 */
abstract class DataObject extends ViewAccessableData implements PermProvider
{
    const VERSION_STATE = "state";
    const VERSION_PUBLISHED = "published";
    const VERSION_GROUP = "group";

    /**
     * default sorting
     *
     *@name default_sort
     */
    static $default_sort = "id";

    /**
     * enables or disabled history
     *
     *@name history
     *@access public
     */
    static $history = true;

    /**
     * show read-only edit if not enough rights
     *
     *@name showWithoutRight
     *@access public
     *@var bool
     *@default false
     */
    public $showWithoutRight = false;

    /**
     * prefix for table_name
     */
    public $prefix = "";

    /**
     * RIGHTS
     */

    /**
     * a personal-field, this must contain the id of the user, so the user can edit it
     *@name writeField
     *@access public
     */
    public $writeField = "";

    /**
     * rights needed for inserting data
     *@name insertRights
     *@access public
     */
    public $insertRights = "";

    /**
     * admin-rights
     *
     * admin can do everything, implemented in can-methods
     */
    public $admin_rights;


    // some form things ;)

    /**
     * cache for results
     */
    static $results = array();

    /**
     * field-titles
     */
    public $fieldTitles = array();

    /**
     * info helps users to understand, what the field means, so you should add info to each field, which is not really clear with the title
     *
     *@name fieldInfo
     *@access public
     *@var array
     */
    public $fieldInfo = array();


    /**
     * this var identifies with which version a DataObjectSet got the data
     * THIS doens't provide feedback if the version is published or not
     *
     *@name queryVersion
     *@access public
     */
    public $queryVersion = DataObject::VERSION_PUBLISHED;

    /**
     * view-cache
     *
     *@name viewcache
     *@access protected
     */
    protected $viewcache = array();

    /**
     * DEPRECATED API
     */
    public $versioned;

    /**
     * storage engine.
     */
    static $engine;

    /**
     * sort of many-many-tables.
     */
    static $many_many_sort = array();

    //!Global Static Methods
    /**
     * STATIC METHODS
     */

    /**
     * gets an instance of the class
     *
     * DEPRECATED!
     *
     *@name _get
     *@access public
     *@param string - name
     *@param array - where
     *@param array - fields
     *@param array - orderby
     */
    public static function _get($class, $filter = array(), $fields = array(), $sort = array(), $joins = array(), $limits = array(), $pagination = false, $groupby = false)
    {
        Core::Deprecate(2.0, "DataObject::get_versioned");
        $data = $data = self::get($class, $filter, $sort, $limits, $joins, null, $pagination);
        if ($groupby !== false) {
            return $data->groupBy($groupby);
        }

        return $data;
    }

    /*
	 * gets a DataObject versioned
	 *
	 *@name getVersioned
	 *@access public
	*/
    public static function get_versioned($class, $version = "publish" , $filter = array(), $sort = array(), $limits = array(), $joins = array(), $group = false, $pagination = false) {
        $data = self::get($class, $filter, $sort, $limits, $joins, $version, $pagination);
        if ($group !== false) {
            return $data->groupBy($group);
        }

        return $data;
    }

    /**
     * gets a DataObject versioned
     *
     *@name getVersioned
     *@access public
     */
    public static function get_version() {
        return call_user_func_array(array("DataObject", "get_Versioned"), func_get_args());
    }

    /**
     * returns a (@link DataObjectSet) with the given parameters
     *
     * @name get
     * @access public
     * @param string - class
     * @param array - filter
     * @param array - sort
     * @param array - limits
     * @param array - joins
     * @param null|string|int - version
     * @param bool - pagination
     * @return DataObjectSet
     */
    public static function get($class, $filter = null, $sort = null, $limits = null, $joins = null, $version = null, $pagination = null) {

        if (PROFILE) Profiler::mark("DataObject::get");

        $dataSet = new DataObjectSet($class, $filter, $sort, $limits, $joins, array(), $version);

        if (isset($pagination) && $pagination !== false) {

            if (is_int($pagination)) {
                $dataSet->activatePagination($pagination);
            } else {
                $dataSet->activatePagination();
            }
        }

        if (PROFILE) Profiler::unmark("DataObject::get");

        return $dataSet;
    }

    /**
     * counts the number of sets we can find for query.
     *
     * @name count
     * @param String DataObject
     * @param array $filter
     * @param array $froms joins
     * @param array $groupby
     * @return int
     */
    static function count($name = "", $filter = array(), $froms = array(), $groupby = "")
    {
        $data = self::get($name, $filter, array(), array(), $froms, null);

        if ($groupby != "") {
            return count($data->GroupBy($groupby));
        }
        return $data->Count();
    }

    /**
     * counts with given where on given table with raw-sql, so you just get back the result for exactly this where
     * if you use count, goma gets just results from given dataobject, so if you want to know, if just the id in an given dataobject exists,
     * but the record is not connected exactly with given dataobject, but with other child of base-DataObject, count give back 0, because of not linked exactly with the right DataObject
     * if you use this function instead, goma don't interpret the data, so you get all results from the table with given where and don't habe comfort
     *
     * ATTENTION: it's NOT recommended to use this function if you don't know the exact difference
     * if you use it, sometimes you get results, that are unexpected
     *
     *@name countRAW
     *@access public
     *@param string - DataObject-name
     *@param array - filter
     */
    public function countRAW($name, $filter)
    {
        $dataobject = Object::instance($name);

        $table_name = $dataobject->Table();

        $where = SQL::ExtractToWhere($filter);

        $sql = "SELECT 
						count(*) as count
					FROM 
						".DB_PREFIX.$table_name."
					".$where;
        if ($result = SQL::Query($sql))
        {
            $row = SQL::fetch_object($result);
            return $row->count;
        } else
        {
            throw new MySQLException();
        }
    }

    /**
     * updates data raw in the table and has not version-managment or multi-table-managment.
     *
     * You have to be familiar with the structure of goma when you use this method. It is much faster than all the other methods of writing, but also more complex.
     *
     * @param String $name Model or Table
     * @param array $data data to update
     * @param array $where where-clause
     * @param string $limit optional limit
     * @param boolean $silent if to change last-modified-date
     *
     * @return boolean
     */
    public static function update($name, $data, $where, $limit = "", $silent = false)
    {
        if (PROFILE) Profiler::mark("DataObject::update");
        //Core::Deprecate(2.0);

        if (ClassInfo::exists($name) && is_subclass_of($name, "DataObject")) {
            $DataObject = Object::instance($name);
            $table_name = $DataObject->Table();
        } else if (isset(ClassInfo::$database[$name])) {
            $table_name = $name;
        } else {
            throwError(6, "Table not found", "Table or model '" . $name . "' does not exist.");
        }

        if (!isset($data["last_modified"]) && !$silent)
        {
            $data["last_modified"] = NOW;
        }

        $updates = "";
        $i = 0;
        foreach($data as $field => $value)
        {
            if (!isset(ClassInfo::$database[$table_name][$field]))
            {
                continue;
            }

            if ($i == 0)
            {
                $i = 1;
            } else
            {
                $updates .= ", ";
            }
            $updates .= "".convert::raw2sql($field)." = '".convert::raw2sql($value)."'";
        }
        $where = SQL::ExtractToWhere($where);

        if ($limit != "") {
            if (is_array($limit)) {
                if (count($limit) > 1 && preg_match("/^[0-9]+$/", $limit[0]) && preg_match("/^[0-9]+$/", $limit[1]))
                    $limit = " LIMIT ".$limit[0].", ".$limit[1]."";
                else if (count($limit) == 1 && preg_match("/^[0-9]+$/", $limit[0]))
                    $limit = " LIMIT ".$limit[0];

            } else if (preg_match("/^[0-9]+$/", $limit)) {
                $limit = " LIMIT ".$limit;
            } else if (preg_match('/^\s*([0-9]+)\s*,\s*([0-9]+)\s*$/', $limit)) {
                $limit = " LIMIT ".$limit;
            } else {
                $limit = "";
            }
        }

        $alias = SelectQuery::getAlias($table_name);
        $sql = "UPDATE
						".DB_PREFIX . $table_name." AS ".$alias."
					SET 
						".$updates."
					".$where."
					".$limit."";

        if (SQL::query($sql))
        {
            if (PROFILE) Profiler::unmark("DataObject::update");
            return true;
        } else
        {
            throw new MySQLException();
        }
    }

    /**
     * gets one record of data or null when no record was found.
     *
     * @param string $dataClass
     * @param array $filter
     * @param array $sort
     * @param array $joins
     * @return DataObject
     */
    public static function get_one($dataClass, $filter = array(), $sort = array(), $joins = array())
    {
        if (PROFILE) Profiler::mark("DataObject::get_one");

        $output = self::get($dataClass, $filter, $sort, array(1), $joins)->first(false);

        if (PROFILE) Profiler::unmark("DataObject::get_one");

        return $output;

    }

    /**
     * gets a record by id
     *
     *@name get_by_id
     *@access public
     *@param string - name
     *@param numeric - id
     *@param array - joins
     */
    public static function get_by_id($class, $id, $joins = array()) {
        return self::get_one($class, array("id" => $id), array(), $joins);
    }


    /**
     * searches in a model
     *
     * @name search_object
     * @access public
     * @param string|object $class
     * @param array $search words to search
     * @param array $filter filter query
     * @param array $sort
     * @param array $limits
     * @param array $join
     * @param bool $pagination
     * @param bool $groupby
     * @return DataObjectSet|DataSet
     */
    public static function search_object($class, $search = array(),$filter = array(), $sort = array(), $limits = array(), $join = array(), $pagination = false, $groupby = false)
    {
        $DataSet = new DataObjectSet($class, $filter, $sort, $limits, $join, $search);

        if ($pagination !== false) {
            if (is_int($pagination)) {
                $DataSet->activatePagination($pagination);
            } else {
                $DataSet->activatePagination();
            }
        }

        if ($groupby !== false) {
            return $DataSet->getGroupedSet($groupby);
        }


        return $DataSet;
    }

    //!Init

    /**
     * this defines a right for advrights or rechte, which tests if an user is an admin
     *@name __construct
     *@param array
     *@param string|object
     *@param array|string - fields
     */
    public function __construct($record = null) {
        parent::__construct();

        $this->data = array_merge(array(
            "class_name"	=> $this->classname,
            "last_modified"	=> NOW,
            "created"		=> NOW,
            "autorid"		=> member::$id
        ), (array) $this->defaults, ArrayLib::map_key("strtolower", (array) $record));
    }

    /**
     * defines the methods.
     *
     * @access protected
     */
    public function defineStatics() {

        if ($has_many = $this->hasMany()) {

            foreach ($has_many as $key => $val) {
                Object::LinkMethod($this->classname, $key, array("this", "getHasMany"), true);
                Object::LinkMethod($this->classname, $key . "ids", array("this", "getRelationIDs"), true);
            }
        }

        if ($many_many_relationships = $this->ManyManyRelationships()) {
            foreach ($many_many_relationships as $key => $val) {
                Object::LinkMethod($this->classname, $key, array("this", "getManyMany"), true);
                Object::LinkMethod($this->classname, $key . "ids", array("this", "getRelationIDs"), true);
                Object::LinkMethod($this->classname, "set" . $key, array("this", "setManyMany"), true);
                Object::LinkMethod($this->classname, "set" . $key . "ids", array("this", "setManyManyIDs"), true);
            }
        }

        if ($has_one = $this->HasOne()) {
            foreach($has_one as $key => $val) {
                Object::LinkMethod($this->classname, $key, array("this", "getHasOne"), true);
            }
        }
    }

    //!Permissions

    /**
     * Permssions for dataobjects
     */
    public function providePerms()
    {
        return array(
            "DATA_MANAGE"	=> array(
                "title"		=> '{$_lang_data_manage}',
                "default"	=> array(
                    "type" => "admins"
                )
            )
        );
    }

    /**
     * public function to make permission-calls
     *
     * @name can
     * @access public
     * @param string|array $permissions name(s) of permission
     * @param DataObject $record optional
     * @return bool
     */
    public function can($permissions, $record = null) {

        if ($this->classname != "permission") {
            if (Permission::check("superadmin")) {
                return true;
            }
        }

        if (!is_array($permissions)) {
            $permissions = array($permissions);
        }

        $usedRecord = isset($record) ? $record : $this;
        foreach($permissions as $perm) {
            $perm = strtolower($perm);
            $can = false;

            if (isset(Permission::$providedPermissions[$this->baseClass . "::" . $perm])) {
                $can = Permission::check($this->baseClass . "::" . $perm);
            }

            if (Object::method_exists($this->classname, "can" . $perm)) {
                $c = call_user_func_array(array($this, "can" . $perm), array($usedRecord));
                if (is_bool($c)) {
                    $can = $c;
                }
            }

            $this->callExtending("can" . $perm, $can, $usedRecord);

            if ($can === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * returns if you can access a specific history-record
     *
     * @param string|object $record
     * @return bool
     */
    public static function canViewHistory($record = null) {
        if (is_object($record)) {
            if ($record->oldversion && $record->newversion) {
                return ($record->oldversion->can("Write", $record->oldversion) && $record->newversion->can("Write", $record->newversion));
            } else if ($record->newversion) {
                return $record->newversion->can("Write", $record->newversion);
            } else if ($record->record) {
                return $record->record->can("Write", $record->record);
            }
        }

        if (is_object($record)) {
            $c = new $record->dbobject;
        } else if (is_string($record)) {
            $c = new $record;
        } else {
            throw new InvalidArgumentException("Invalid first argument for DataObject::canViewRecord object or class-name required");
        }
        return $c->can("Write");
    }

    /**
     * returns if a given record can be written to db
     *
     * @name canWrite
     * @access public
     * @param array - record
     * @return bool
     */
    public function canWrite($row = null)
    {
        $provided = $this->providePerms();
        if (count($provided) == 1) {
            $keys = array_keys($provided);

            if (Permission::check($keys[0]))
                return true;
        } else if (count($provided) > 1) {
            foreach($provided as $key => $arr)
            {
                if (preg_match("/all$/i", $key))
                {
                    if (Permission::check($key))
                        return true;
                }

                if (preg_match("/write$/i", $key))
                {
                    if (Permission::check($key))
                        return true;
                }
            }
        }

        if (is_object($row) && $row->admin_rights) {
            return Permission::check($row->admin_rights);
        }

        if ($this->admin_rights) {
            return Permission::check($this->admin_rights);
        }

        return false;
    }

    /**
     * returns if a given record can deleted in database
     *
     *@name canDelete
     *@access public
     *@param array - reocrd
     */
    public function canDelete($row = null)
    {
        $provided = $this->providePerms();
        if (count($provided) == 1) {
            $keys = array_keys($provided);

            if (Permission::check($keys[0]))
                return true;
        } else if (count($provided) > 1) {
            foreach($provided as $key => $arr)
            {
                if (preg_match("/all$/i", $key))
                {
                    if (Permission::check($key))
                        return true;
                }

                if (preg_match("/delete$/i", $key))
                {
                    if (Permission::check($key))
                        return true;
                }
            }
        }

        if (is_object($row) && $row->admin_rights) {
            return Permission::check($row->admin_rights);
        }

        if ($this->admin_rights) {
            return Permission::check($this->admin_rights);
        }

        return false;
    }

    /**
     * returns if a given record can be inserted in database
     *
     *@name canInsert
     *@access public
     *@param array - reocrd
     */
    public function canInsert($row = null)
    {
        if ($this->insertRights) {
            if (Permission::check($this->insertRights))
                return true;
        }
        $provided = $this->providePerms();
        if (count($provided) == 1) {
            $keys = array_keys($provided);

            if (Permission::check($keys[0]))
                return true;
        } else if (count($provided) > 1) {
            foreach($provided as $key => $arr)
            {
                if (preg_match("/all$/i", $key))
                {
                    if (Permission::check($key))
                        return true;
                }

                if (preg_match("/insert$/i", $key))
                {
                    if (Permission::check($key))
                        return true;
                }
            }
        }

        if (is_object($row) && $row->admin_rights) {
            return Permission::check($row->admin_rights);
        }

        if ($this->admin_rights) {
            return Permission::check($this->admin_rights);
        }

        return false;
    }

    /**
     * gets the writeaccess
     *@name getWriteAccess
     *@access public
     */
    public function getWriteAccess()
    {
        if (!self::Versioned($this->classname) && $this->can("Write"))
        {
            return true;
        } else if ($this->can("Publish")) {
            return true;
        } else if ($this->can("Delete"))
        {
            return true;
        }

        return false;
    }

    /**
     * returns if publish-right is available
     *
     *@name canPublish
     *@access public
     */
    public function canPublish($record = null) {
        return true;
    }

    /**
     * right-management
     */

    //!Events
    /**
     * events
     */
    /**
     *@name onBeforeDelete
     *@return bool
     */
    public function onBeforeRemove(&$manipulation)
    {

    }

    /**
     *@name onAfterRemove
     *@return bool
     */
    public function onAfterRemove()
    {

    }

    /**
     *@name beforeRead
     *@return bool
     */
    public function onbeforeRead(&$data)
    {
        $this->callExtending("onBeforeRead", $data);
    }

    /**
     * will be called before write
     *@name onBeforeWrite
     *@access public
     */
    public function onBeforeWrite()
    {
        $dummy = null;
        $this->callExtending("onBeforeWrite", $dummy);
    }

    /**
     * will be called after write
     *@name onAfterWrite
     *@access public
     */
    public function onAfterWrite()
    {

    }


    /**
     * before manipulating the data
     *
     *@name onbeforeManipulate
     *@access public
     *@param manipulation
     */
    public function onbeforeManipulate(&$manipulation, $job)
    {

    }

    /**
     * before manipulating many-many data over @link ManyMany_DataObjectSet::write
     *
     *@name onBeforeManipulateManyMany
     */
    public function onBeforeManipulateManyMany(&$manipulation, $dataset, $writeData) {

    }

    /**
     * before updating data-tables to write data
     *
     *@name onBeforeWriteData
     *@access public
     */
    public function onBeforeWriteData() {

    }

    /**
     * is called before unpublish
     */
    public function onBeforeUnpublish() {

    }

    /**
     * is called before publish
     */
    public function onBeforePublish() {

    }

    //!Data-Manipulation

    /**
     * writes changed data without throwing exceptions.
     *
     *@name write
     *@access public
     *@param bool - to force insert (default: false)
     *@param bool - to force write (default: false)
     *@param int - priority of the snapshop: autosave 0, save 1, publish 2
     *@param bool - if to force publishing also when not permitted (default: false)
     *@param bool - whether to track in history (default: true)
     *@param bool - whether to write silently, so without chaning anything automatically e.g. last_modified (default: false)
     *@return bool
     */
    public function write($forceInsert = false, $forceWrite = false, $snap_priority = 2, $forcePublish = false, $history = true, $silent = false)
    {
        try {
            return $this->writeToDB($forceInsert, $forceWrite, $snap_priority, $forcePublish, $history, $silent);

        } catch(Exception $e) {
            log_exception($e);
            return false;
        }

    }

    /**
     * writes changed data and throws exceptions.
     *
     * @param bool $forceInsert
     * @param bool $forceWrite
     * @param int $snap_priority
     * @param bool $forcePublish
     * @param bool $history
     * @param bool $silent
     * @param bool $overrideCreated
     * @throws Exception
     * @throws PermissionException
     * @throws SQLException
     * @return bool
     */
    public function writeToDB($forceInsert = false, $forceWrite = false, $snap_priority = 2, $forcePublish = false, $history = true, $silent = false, $overrideCreated = false)
    {

        if(!$history) {
            HistoryWriter::disableHistory();
        }

        if($snap_priority > 1) {
            if($forceInsert) {
                Core::repository()->add($this, $forceWrite, $silent, $overrideCreated);
            } else {
                Core::repository()->write($this, $forceWrite, $silent, $overrideCreated);
            }
        } else {
            if($forceInsert) {
                Core::repository()->addState($this, $forceWrite, $silent, $overrideCreated);
            } else {
                Core::repository()->writeState($this, $forceWrite, $silent, $overrideCreated);
            }
        }

        if(!$history) {
            HistoryWriter::enableHistory();
        }

        return true;
    }

    /**
     * writes changed data silently, so without chaning last-modified and other stuff than manually changed
     *
     *@name writeSilent
     *@access public
     *@param bool - to force insert (default: false)
     *@param bool - to force write (default: false)
     *@param numeric - priority of the snapshop: autosave 0, save 1, publish 2
     *@param bool - if to force publishing also when not permitted (default: false)
     *@param bool - whether to track in history (default: true)
     *@param bool - whether to write silently, so without chaning anything automatically e.g. last_modified (default: false)
     *@return bool
     */
    public function writeSilent($forceInsert = false, $forceWrite = false, $snap_priority = 2, $forcePublish = false, $history = true)
    {
        return $this->write($forceInsert, $forceWrite, $snap_priority, $forcePublish, $history, true);
    }

    /**
     * returns versionid from given relationship-info-array. it creates new record if no one can be found.
     * it also updates the record.
     *
     * @param ModelManyManyRelationShipInfo $relationShip
     * @param int $key
     * @param array $record
     * @param bool $forceWrite
     * @param int $snap_priority
     * @param bool $history
     * @return int
     */
    protected function getRelationShipIdFromRecord($relationShip, $key, $record, $forceWrite = false, $snap_priority = 2, $history = true) {

        // validate versionid
        if(isset($record["versionid"])) {
            $id = $record["versionid"];
        } else {
            if(DataObject::count($relationShip->getTarget(), array("versionid" => $key))) {
                $id = $key;
            }
        }

        // did not find versionid, so generate one
        if(!isset($id) || $id == 0) {

            $target = $relationShip->getTarget();
            /** @var DataObject $dataObject */
            $dataObject = new $target(array_merge($record, array("id" => 0, "versionid" => 0)));
            $dataObject->write(true, $forceWrite, $snap_priority, $forceWrite, $history);

            return $dataObject->versionid;
        } else {

            // we want to update many-many-extra
            $databaseRecord = null;
            $db = Object::instance($relationShip->getTarget())->DataBaseFields(true);

            // just find out if we may be update the record given.
            foreach($record as $field => $v) {
                if(isset($db[strtolower($field)]) && !in_array(strtolower($field), array("versionid", "id", "recordid"))) {
                    if(!isset($databaseRecord)) {
                        $databaseRecord = DataObject::get_one($relationShip->getTarget(), array("versionid" => $id));
                    }

                    $databaseRecord[$field] = $v;
                }
            }

            // we found many-many-extra which can be updated so write please
            if(isset($databaseRecord)) {
                $databaseRecord->write(false, $forceWrite, $snap_priority, $forceWrite, $history);
                return $databaseRecord->versionid;
            }

            return $id;
        }
    }

    /**
     * returns maximum target-sort.
     *
     * @param ModelManyManyRelationShipInfo $relationShip
     * @param array $existing
     * @return int
     */
    protected function maxTargetSort($relationShip, $existing) {
        $maxSort = 0;
        foreach($existing as $record) {
            if($record[$relationShip->getTargetSortField()] > $maxSort) {
                $maxSort = $record[$relationShip->getTargetSortField()];
            }
        }

        return $maxSort;
    }

    /**
     * unpublishes the record
     *
     *@name unpublish
     *@access public
     */
    public function unpublish($force = false, $history = true) {
        if ((!$this->can("Publish")) && !$force)
            return false;

        $manipulation = array(
            $this->baseTable . "_state" => array(
                "table_name" 	=> $this->baseTable . "_state",
                "command"		=> "update",
                "id"			=> $this->recordid,
                "fields"		=> array(
                    "publishedid"	=> 0
                )
            )
        );

        $this->onBeforeUnPublish();
        $this->callExtending("OnBeforeUnPublish");

        $this->onBeforeManipulate($manipulation, $b = "unpublish");
        $this->callExtending("onBeforeManipulate", $manipulation, $b = "unpublish");

        if (SQL::manipulate($manipulation)) {
            if (StaticsManager::getStatic($this->classname, "history") && $history) {
                History::push($this->classname, 0, $this->versionid, $this->id, "unpublish");
            }
            return true;
        }

        return false;
    }

    /**
     * publishes the record
     *
     *@name publish
     *@access public
     */
    public function publish($force = false, $history = true) {
        if ((!$this->can("Publish")) && !$force)
            return false;

        if ($this->isPublished())
            return true;

        $manipulation = array(
            $this->baseTable . "_state" => array(
                "table_name" 	=> $this->baseTable . "_state",
                "command"		=> "update",
                "id"			=> $this->recordid,
                "fields"		=> array(
                    "publishedid"	=> $this->versionid
                )
            )
        );

        $this->onBeforePublish();
        $this->callExtending("OnBeforePublish");

        $this->onBeforeManipulate($manipulation, $b = "publish");
        $this->callExtending("onBeforeManipulate", $manipulation, $b = "publish");

        if (SQL::manipulate($manipulation)) {
            if (StaticsManager::getStatic($this->classname, "history") && $history) {
                History::push($this->classname, 0, $this->versionid, $this->id, "publish");
            }
            return true;
        }

        return false;
    }

    /**
     * deletes the record
     *
     * @param bool $force force delete
     * @param bool $forceAll if force to delete versions, too
     * @param bool $history if we put this action into history
     * @return bool
     * @throws MySQLException
     * @throws SQLException
     */
    public function remove($force = false, $forceAll = false, $history = true)
    {
        // check if table in db and if not, create it
        if ($this->baseTable != "" && !isset(ClassInfo::$database[$this->baseTable])) {
            foreach(array_merge(ClassInfo::getChildren($this->classname), array($this->classname)) as $child) {
                Object::instance($child)->buildDB();
            }
            ClassInfo::write();
        }

        $manipulation = array();
        $baseClass = ClassInfo::$class_info[$this->RecordClass]["baseclass"];

        if (!isset($this->data))
            return true;

        if ($force || $this->can("Delete"))
        {
            // get the ids which are needed
            $ids = array();
            $query = new SelectQuery($this->baseTable, array("id"), array("recordid" => $this->id));
            if ($query->execute()) {
                while($row = $query->fetch_object())
                    $ids[] = $row->id;
            } else {
                throw new MySQLException();
            }
            // delete connection in state-table

            // base class
            if (!isset($manipulation[$baseClass . "_state"]))
                $manipulation[$baseClass . "_state"] = array(
                    "command"		=> "delete",
                    "table_name"	=> $this->baseTable . "_state",
                    "where"			=> array(

                    ));

            $manipulation[$baseClass . "_state"]["where"]["id"][] = $this->id;

            // if not versioning, delete data, too
            if (!self::Versioned($this->classname) || $forceAll || !isset($this->data["stateid"])) {
                // clean up data-tables

                if (!isset($manipulation[$baseClass])) {
                    $manipulation[$baseClass] = array(
                        "command"	=> "delete",
                        "where" 	=> array()
                    );
                }
                if (!isset($manipulation[$baseClass]["where"]["id"]))
                    $manipulation[$baseClass]["where"]["id"] = array();

                $manipulation[$baseClass]["where"]["id"] = array_merge($manipulation[$baseClass]["where"]["id"], $ids);

                // subclasses
                if ($classes = ClassInfo::dataclasses($this->classname))
                {
                    foreach($classes as $class => $table)
                    {
                        if (isset(ClassInfo::$database[$table]) && $class != $this->classname)
                        {
                            if (!isset($manipulation[$class])) {
                                $manipulation[$class] = array(
                                    "command"	=> "delete",
                                    "where" 	=> array()
                                );
                            }
                            if (!isset($manipulation[$class]["where"]["id"]))
                                $manipulation[$class]["where"]["id"] = array();

                            $manipulation[$class]["where"]["id"] = array_merge($manipulation[$class]["where"]["id"], $ids);
                        }
                    }
                }

                // clean-up-many-many
                foreach($this->ManyManyTables() as $data) {
                    $manipulation[$data["table"]] = array(
                        "table" 	=> $data["table"],
                        "command"	=> "delete",
                        "where"		=> array(
                            $data["field"] => $ids
                        )
                    );
                }

            }
        } else {
            return false;
        }

        $this->disconnect();

        DataObjectQuery::$datacache[$this->caseClass] = array();

        $this->onBeforeRemove($manipulation);
        $this->callExtending("onBeforeRemove", $manipulation);
        if (SQL::manipulate($manipulation)) {
            if (StaticsManager::getStatic($this->classname, "history") && $history) {
                History::push($this->classname, 0, $this->versionid, $this->id, "remove");
            }
            $this->onAfterRemove($this);
            $this->callExtending("onAfterRemove", $this);
            unset($this->data);
            return true;
        } else {
            return false;
        }


    }

    /**
     * disconnects from relation
     *
     *@name disconnect
     *@access public
     */
    public function disconnect($val = null) {
        if (isset($this->dataset)) {
            $this->dataset->removeRecord($this->dataSetPosition);
            if (isset($val))
                $val->removeRecord($this->dataSetPosition);
        }
    }

    //!Current Data-State
    /**
     * returns if this version of the record is published
     *
     * @name isPublished
     * @access public
     * @return bool
     */
    public function isPublished() {

        if (isset($this->data["publishedid"])) {
            return ($this->publishedid != 0 && $this->versionid == $this->publishedid);
        } else {
            $query = new SelectQuery($this->baseTable . "_state", array("publishedid", "stateid"), array("id" => $this->recordid));
            if ($query->execute()) {
                while($row = $query->fetch_object()) {
                    $this->publishedid = $row->publishedid;
                    $this->stateid = $row->stateid;
                    break;
                }
                if (isset($this->data["publishedid"])) {
                    return ($this->publishedid != 0 && $this->versionid == $this->publishedid);
                } else {
                    $this->publishedid = 0;
                    $this->stateid = 0;
                    return false;
                }
            } else {
                throw new MySQLException();
            }
        }
    }

    /**
     * returns if original version of the record is published
     *
     * @name isrOrgPublished
     * @access public
     * @return bool
     */
    public function isOrgPublished() {
        if (isset($this->original["publishedid"])) {
            return ($this->original["publishedid"] != 0 && $this->original["versionid"] == $this->original["publishedid"]);
        } else {
            return false;
        }
    }

    /**
     * gives back if ever published
     *
     *@name isPublished
     *@access public
     */
    public function everPublished() {
        if ($this->isPublished()) {
            return true;
        }

        if (isset($this->data["publishedid"]) && $this->data["publishedid"]) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * returns if baseRecord is deleted
     *
     *@name isDeleted
     *@access public
     */
    public function isDeleted() {
        return (!$this->isPublished() &&
            (   !isset($this->data["publishedid"]) ||
                $this->data["publishedid"] == 0 ||
                $this->data["stateid"] == 0));
    }

    //!Forms

    /**
     * gets the form
     *@name getForm
     *@param object - form-object
     */
    public function getForm(&$form)
    {
        $form->setResult($this);
    }

    /**
     * geteditform
     *@name geteditform
     *@param object
     *@param array - data
     */
    public function getEditForm(&$form)
    {
        $form->setResult($this);
        $this->getForm($form);
    }

    /**
     * gets the form-actions
     *@name getActions
     *@param object - form-object
     */
    public function getActions(&$form, $edit = false)
    {
        $form->setResult($this);

    }

    /**
     * returns a list of fields you want to show if we use the history-compare-view
     *
     *@name getVersionedFields
     *@access public
     */
    public function getVersionedFields() {
        return array();
    }

    /**
     * getFormFromDB
     * generates the form-fields from the db-fields
     *
     *@name getFormFromDB
     *@access public
     */
    public function getFormFromDB(&$form) {
        $this->fieldTitles = ArrayLib::map_key("strtolower", array_merge($this->fieldTitles, $this->getFieldTitles()));
        $this->fieldInfo = ArrayLib::map_key("strtolower", array_merge($this->fieldInfo, $this->getFieldInfo()));

        foreach($this->DataBaseFields() as $field => $type) {
            if (isset($this->fieldTitles[$field])) {
                $form->add($formfield = $this->doObject($field)->formField($this->fieldTitles[$field]));
                if (isset($this->fieldInfo[$field])) {
                    $formfield->info = parse_lang($this->fieldInfo[$field]);
                }
                unset($formfield);
            }
        }
    }

    /**
     * gets on the fly generated field titles
     */
    public function getFieldTitles() {
        return array();
    }

    /**
     * gets on the fly generated field-info
     */
    public function getFieldInfo() {
        return array();
    }

    /**
     * generates a form
     *
     * @name 	form
     * @access 	public
     * @param 	string  	name
     * @param  	bool  		edit-form or normal form. this changes if getForm() or getEditForm() get called.
     * @param 	bool  		disabled-state by default
     * @param 	Request 	Request-Object
     * @param 	controller 	Controller
     */
    public function generateForm($name = null, $edit = false, $disabled = false, $request = null, $controller = null, $submission = null) {

        // if name is not set, we generate a name from this model
        if (!isset($name)) {
            $name = $this->classname . "_" . $this->versionid . "_" . $this->id;
        }

        $controller = isset($controller) ? $controller : $this->controller;

        $form = new Form($controller, $name, array(), array(), array(), $request, $this);

        // default submission
        $form->setSubmission(isset($submission) ? $submission : "submit_form");

        $form->addValidator(new DataValidator($this), "datavalidator");

        $form->setResult(clone $this);

        // some default fields
        if ($this->recordid) {
            $form->add(new HiddenField("id", $this->recordid));
            $form->add(new HiddenField("versionid", $this->versionid));
            $form->add(new HiddenField("recordid", $this->recordid));
        }

        $form->add(new HiddenField("class_name", $this->classname));

        // render form
        if ($edit) {
            $this->getEditForm($form);
        } else {
            $this->getForm($form);
        }

        $this->callExtending('getForm', $form, $edit);
        $this->getActions($form, $edit);
        $this->callExtending('getActions', $form, $edit);

        if ($disabled) {
            $form->disable();
        }

        return $form;
    }

    /**
     * gets a list of all fields with according titles of this object
     *
     *@name summaryFields
     *@access public
     */
    public function summaryFields() {
        $f = ArrayLib::key_value(array_keys($this->DataBaseFields()));
        $this->fieldTitles = array_merge($this->fieldTitles, $this->getFieldTitles());

        unset($f["autorid"]);
        unset($f["editorid"]);

        $fields = array();

        foreach($f as $field) {
            $field = trim($field);

            if (isset($this->fieldTitles[$field])) {
                $fields[$field] = parse_lang($this->fieldInfo[$field]);
            } else {
                if ($field == "name") {
                    $fields[$field] = lang("name");
                } else if ($field == "title") {
                    $fields[$field] = lang("title");
                } else if ($field == "description") {
                    $fields[$field] = lang("description");
                } else if ($field == "content") {
                    $fields[$field] = lang("content");
                } else if ($field == "filename") {
                    $fields[$field] = lang("filename");
                } else if ($field == "email") {
                    $fields[$field] = lang("email");
                } else {
                    $fields[$field] = $field;
                }
            }
        }

        return $fields;
    }

    /**
     * relation-management
     */

    //!Relation Methods

    /**
     * set many-many-connection-data
     *
     * @name set_many_many
     * @param string $name of relationship
     * @param array $data
     * @param bool $force
     * @access public
     * @return bool
     */
    public function set_many_many($name, $data, $force = false)
    {
        if ($force || $this->can("Write", $this)) {
            if ($force || !$this->isPublished() || $this->can("Publish", $this)) {
                $manipulation = $this->set_many_many_manipulation(array(), $name, $data);

                $this->onBeforeManipulate($manipulation, $b = "set_many_many");
                $this->callExtending("onBeforeManipulate", $manipulation, $b = "set_many_many");

                return SQL::manipulate($manipulation);
            }
        }

        return false;
    }

    /**
     * gets relation ids
     *
     *@name getRelationIDs
     *@access public
     */
    public function getRelationIDs($relname) {
        $relname = trim(strtolower($relname));

        if (substr($relname, -3) == "ids") {
            $relname = substr($relname, 0, -3);
        }

        // get all config
        $has_many = $this->hasMany();
        $many_many = $this->ManyMany();
        $belongs_many_many = $this->BelongsManyMany();
        $many_many_tables = $this->ManyManyTables();

        if (isset($has_many[$relname])) {
            // has-many
            /**
             * getMany returns a DataObjectSet
             * parameters:
             * name of relation
             * where
             * fields
             */
            if ($data = $this->getHasMany($relname)) {

                // then get all data in one array with key - id pairs

                $arr = array();
                foreach($data->ToArray() as $key => $value)
                {
                    $arr[] = $value["id"];
                }
                return $arr;
            } else {
                return array();
            }
        } else if (isset($many_many[$relname]) || isset($belongs_many_many[$relname])) {
            if (isset($this->data[$relname . "ids"])) {
                return $this->data[$relname . "ids"];
            }

            /**
             * there is the var many_many_tables, which contains data for the table, which stores the relation
             * for exmaple: array(
             * "table"	=> "my_many_many_table_generated_by_system",
             * "field"	=> "myclassid"
             * )
             */

            $relationShip = $this->getManyManyInfo($relname);

            $query = $this->getManyManyQuery($relationShip, array($relationShip->getTargetField()));
            $query->execute();
            $arr = array();
            while($row = $query->fetch_assoc())
            {
                if($row[$relationShip->getTargetField()] != $this->versionid) {
                    $arr[] = $row[$relationShip->getTargetField()];
                }
            }

            $this->data[$relname . "ids"] = $arr;
            return $arr;
        } else {
            return false;
        }
    }

    /**
     * creates many-many-sql-query for getting specific field-info.
     *
     * @param ModelManyManyRelationShipInfo $relationShip
     * @param array$fields
     * @param string $fieldInTable
     * @param int $versionId
     * @return SelectQuery
     */
    protected function getManyManyQuery($relationShip, $fields, $fieldInTable = null, $versionId = null) {
        $extTable = $relationShip->getTargetTableName();

        $versionId = isset($versionId) ? $versionId : $this->versionid;

        if(!isset($fieldInTable)) {
            $fieldInTable = $relationShip->getTargetField();
        }

        $query = new SelectQuery($relationShip->getTableName(), $fields, array($relationShip->getOwnerField() => $versionId));

        // just that some errros are not happening.
        if ($extTable && (
                ClassManifest::isSameClass($relationShip->getTarget(), $this->classname) ||
                is_subclass_of($relationShip->getTarget(), $this->classname) ||
                is_subclass_of($this->classname, $relationShip->getTarget())
            )
        ) {
            // filter for not existing records
            $query->from[] = ' INNER JOIN ' . DB_PREFIX . $extTable . ' AS '. $extTable .
                ' ON ' . $extTable . '.id = '.$relationShip->getTableName().'.' . $fieldInTable . ' AND '.$extTable.'.recordid != "'.$this["id"].'"';
        } else if($extTable) {
            // filter for not existing records
            $query->from[] = ' INNER JOIN ' . DB_PREFIX . $extTable . ' AS '. $extTable .
                ' ON ' . $extTable . '.id = '. $relationShip->getTableName() .'.' . $fieldInTable;
        }

        $query->sort($this->getManyManySort($relationShip));

        return $query;
    }

    /**
     * gets relation-data
     *
     * @name getRelationData
     * @access public
     * @return array|bool
     */
    public function getRelationData($relname) {
        $relname = trim(strtolower($relname));

        if (substr($relname, -3) == "ids") {
            $relname = substr($relname, 0, -3);
        }

        // get all config
        $has_many = $this->hasMany();
        $many_many_tables = $this->ManyManyTables();

        if (isset($has_many[$relname])) {
            // has-many
            /**
             * getMany returns a DataObject
             * parameters:
             * name of relation
             * where
             * fields
             */
            if ($relationShip = $this->getHasMany($relname)) {

                // then get all data in one array with key - id pairs

                $arr = array();
                foreach($relationShip->ToArray() as $key => $value)
                {
                    $arr[] = $value["id"];
                }
                return $arr;
            } else {
                return array();
            }
        } else if (isset($many_many_tables[$relname])) {
            if (isset($this->data[$relname . "_data"])) {
                return $this->data[$relname . "_data"];
            }

            /**
             * there is the var many_many_tables, which contains data for the table, which stores the relation
             * for exmaple: array(
             * "table"	=> "my_many_many_table_generated_by_system",
             * "field"	=> "myclassid"
             * )
             */

            $relationShip = $this->getManyManyInfo($relname);

            $data = $this->getManyManyRelationShipData($relationShip);

            $this->data[$relname . "_data"] = $data;
            return $data;
        } else {
            return false;
        }
    }

    /**
     * queries database for existing Relationship-data for many-many-connections.
     *
     * @param ModelManyManyRelationShipInfo $relationShip
     * @param string $fieldInTable
     * @param int $versionId
     * @return array
     */
    protected function getManyManyRelationShipData($relationShip, $fieldInTable = null, $versionId = null) {

        $query = $this->getManyManyQuery($relationShip, array("*"), $fieldInTable, $versionId);

        $query->execute();

        $arr = array();
        while($row = $query->fetch_assoc()) {

            $id = $row[$relationShip->getTargetField()];
            $arr[$id] = array(
                "versionid"                     => $id,
                "relationShipId"                => $row["id"],
                $relationShip->getOwnerField()  => $row[$relationShip->getOwnerField()]
            );

            $arr[$id][$relationShip->getOwnerSortField()] = $row[$relationShip->getOwnerSortField()];
            $arr[$id][$relationShip->getTargetSortField()] = $row[$relationShip->getTargetSortField()];

            foreach ($relationShip->getExtraFields() as $field => $pattern) {
                $arr[$id][$field] = $row[$field];
            }
        }

        return $arr;
    }

    /**
     * gets the data object for a has-many-relation
     *
     *@name getHasMany
     *@access public
     */
    public function getHasMany($name, $filter = null, $sort = null, $limit = null) {

        $name = trim(strtolower($name));

        $has_many = $this->hasMany();
        if (!isset($has_many[$name]))
        {
            throw new LogicException("No Has-many-relation '".$name."' on ".$this->classname);
        }

        $cache = "has_many_{$name}_".var_export($filter, true)."_".var_export($sort, true) . "_" . var_export($limit, true);
        if (isset($this->viewcache[$cache])) {
            return $this->viewcache[$cache];
        }

        $class = $has_many[$name];
        $inverse = null;
        if(is_array($has_many[$name]) && isset($has_many[$name]["class"])) {
            $class = $has_many[$name]["class"];
            if(isset($has_many[$name]["inverse"]) && isset(ClassInfo::$class_info[$class]["has_one"][$has_many[$name]["inverse"]])) {
                $inverse = $has_many[$name]["inverse"];
            }
        }

        if(!isset($inverse)) {

            $inverse = array_search($this->classname, ClassInfo::$class_info[$class]["has_one"]);
            if ($inverse === false)
            {
                $c = $this->classname;
                while($c = ClassInfo::getParentClass($c))
                {
                    if ($inverse = array_search($c, ClassInfo::$class_info[$class]["has_one"]))
                    {
                        break;
                    }
                }
            }

            if ($inverse === false)
            {
                throw new LogicException("No inverse has-one-relationship on '" . $class . "' found.");
            }
        }

        $filter[$inverse . "id"] = $this["id"];
        $set = new HasMany_DataObjectSet($class, $filter, $sort, $limit);
        $set->setRelationENV($name, $inverse . "id");

        if ($this->queryVersion == DataObject::VERSION_STATE) {
            $set->setVersion(DataObject::VERSION_STATE);
        }

        $this->viewcache[$cache] = $set;

        return $set;
    }

    /**
     * gets a has-one-dataobject
     *
     * @param string $name name of relationship
     * @param array $filter
     * @return DataObject
     */
    public function getHasOne($name, $filter = null) {

        $name = trim(strtolower($name));

        if (PROFILE) Profiler::mark("getHasOne");

        $cache = "has_one_{$name}_".var_export($filter, true) . $this[$name . "id"];
        if (isset($this->viewcache[$cache])) {
            if (PROFILE) Profiler::unmark("getHasOne", "getHasOne viewcache");
            return $this->viewcache[$cache];
        }

        $has_one = $this->hasOne();
        if (isset($has_one[$name])) {
            if ($this->isField($name) && is_object($this->fieldGet($name)) && is_a($this->fieldGet($name), $has_one[$name]) && !$filter) {
                if (PROFILE) Profiler::unmark("getHasOne");
                return $this->fieldGet($name);
            }

            if ($this[$name . "id"] == 0) {

                if (PROFILE) Profiler::unmark("getHasOne");
                return null;
            }

            $filter["id"] = $this[$name . "id"];

            if (isset(DataObjectQuery::$datacache[$this->baseClass][$cache])) {
                if (PROFILE) Profiler::unmark("getHasOne", "getHasOne datacache");
                $this->viewcache[$cache] = clone DataObjectQuery::$datacache[$this->baseClass][$cache];
                return $this->viewcache[$cache];
            }

            $response = DataObject::get($has_one[$name], $filter);

            if ($this->queryVersion == DataObject::VERSION_STATE) {
                $response->setVersion(DataObject::VERSION_STATE);
            }

            if (($this->viewcache[$cache] = $response->first(false))) {
                DataObjectQuery::$datacache[$this->baseClass][$cache] = clone $this->viewcache[$cache];
                if (PROFILE) Profiler::unmark("getHasOne");
                return $this->viewcache[$cache];
            } else {
                if (PROFILE) Profiler::unmark("getHasOne");
                return null;
            }
        } else {
            throw new LogicException("No Has-one-relation '".$name."' on ".$this->classname);
        }
    }

    /**
     * gets many-many-objects
     *
     * @param $name
     * @param array|string $filter
     * @param array|string $sort
     * @param array|int $limit
     * @return ManyMany_DataObjectSet
     *
     */
    public function getManyMany($name, $filter = null, $sort = null, $limit = null) {

        $name = trim(strtolower($name));

        // first a little bit of caching ;)
        $cache = "many_many_".$name."_".md5(var_export($filter, true))."_".md5(var_export($sort, true))."_".md5(var_export($limit, true))."";
        if (isset($this->viewcache[$cache])) {
            return $this->viewcache[$cache];
        }

        // get info
        $relationShip = $this->getManyManyInfo($name);

        $where = (array) $filter;
        // if we know the ids
        if (isset($this->data[$name . "ids"]))
        {
            $where["versionid"] = $this->data[$name . "ids"];
            // this relation was modfied, so we use the data from the datacache
            $instance = new ManyMany_DataObjectSet($relationShip->getTarget(), $where, $sort, $limit);
            $instance->setRelationEnv($relationShip, $this->versionid);

            if(!$sort) {
                $instance->sort($this->data[$name . "ids"], "versionid");
            }

            if ($this->queryVersion == DataObject::VERSION_STATE) {
                $instance->setVersion(DataObject::VERSION_STATE);
            } else {
                $instance->setVersion(DataObject::VERSION_PUBLISHED);
            }

            $this->viewcache[$cache] =& $instance;

            return $instance;
        }

        $where[$relationShip->getTableName() . "." . $relationShip->getOwnerField()] = $this["versionid"];
        $sort = $this->getManyManySort($relationShip, $sort);

        $instance = new ManyMany_DataObjectSet($relationShip->getTarget(), $where, $sort, $limit, array(
            ' INNER JOIN '.DB_PREFIX . $relationShip->getTableName().' AS '.$relationShip->getTableName().
            ' ON '.$relationShip->getTableName().'.'. $relationShip->getTargetField() . ' = '. $relationShip->getTargetTableName().'.id ' // Join other Table with many-many-table
        ));

        $instance->setRelationEnv($relationShip, $this->versionid);
        if ($this->queryVersion == DataObject::VERSION_STATE) {
            $instance->setVersion(DataObject::VERSION_STATE);
        }

        $this->viewcache[$cache] = $instance;

        return $instance;
    }

    /**
     * returns many-many-sort.
     * @param ModelManyManyRelationShipInfo $relationShip
     * @param array|string $sort
     * @return string
     */
    protected function getManyManySort($relationShip, $sort = null) {
        if(!isset($sort) || !$sort) {
            $name = $relationShip->getRelationShipName();
            $sorts = ArrayLib::map_key("strtolower", StaticsManager::getStatic($this->classname, "many_many_sort"));
            if(isset($sorts[$name]) && $sorts[$name]) {
                return $sorts[$name];
            } else {
                return $relationShip->getTableName() . ".".$relationShip->getOwnerSortField()." ASC , " .
                $relationShip->getTableName() . ".id ASC";
            }
        }

        return $sort;
    }

    /**
     * gets information about many-many-relationship or throws exception.
     *
     * @param name
     * @return ModelManyManyRelationShipInfo
     * @throws LogicException
     */
    public function getManyManyInfo($name, $class = null) {
        // get config

        if(is_string($class) && ClassInfo::exists($class)) {
            $many_many = DataObjectClassInfo::getManyManyRelationships($class);
        } else {
            $many_many = $this->ManyManyRelationships();
        }


        if (!isset($many_many[$name])) {
            throw new LogicException("Many-Many-Relation ".convert::raw2text($name)." does not exist!");
        }

        return $many_many[$name];
    }

    /**
     * sets many-many-data
     *
     * @name setManyMany
     * @access public
     * @return bool
     */
    public function setManyMany($name, $value) {
        $name = substr($name, 3);

        $relationShipInfo = $this->getManyManyInfo($name);

        if (is_object($value)) {
            if (is_a($value, "DataObjectSet")) {

                $relationShip = $this->getManyManyInfo($name);

                $instance = new ManyMany_DataObjectSet($relationShipInfo->getTarget());
                $instance->setRelationEnv($relationShip, $this->versionid);
                $instance->addMany($value);
                $this->setField($name, $instance);

                unset($instance);
                return true;
            }
        }

        unset($this->data[$name . "ids"]);
        return $this->setField($name, $value);
    }

    /**
     * sets many-many-ids
     *
     * @name setManyManyIDs
     * @access public
     * @return bool|void
     */
    public function setManyManyIDs($name, $ids) {

        if (!is_array($ids))
            return false;

        $name = substr($name, 3, -3);

        $name = trim(strtolower($name));

        // get config
        $many_many = $this->ManyMany();
        $belongs_many_many = $this->BelongsManyMany();

        // first we get the object for this connection
        if (!isset($many_many[$name]) && !isset($belongs_many_many[$name])) {
            throw new LogicException("Many-Many-Relation ".convert::raw2text($name)." does not exist!");
        }

        if (isset($this->data[$name]) && is_object($this->data[$name]) && is_a($this->data[$name], "ManyMany_DataObjectSet")) {
            unset($this->data[$name]);
        }

        return $this->setField($name . "ids", $ids);
    }

    /**
     * GETTERS AND SETTERS
     */
    //!GETTERS AND SETTERS

    /**
     * gets versions of this ordered by time DESC
     *
     *@name versions
     *@access public
     */
    public function versions($limit = null, $where = array(), $orderasc = false) {
        $ordertype = ($orderasc === true) ? "ASC" : "DESC";
        return DataObject::get_versioned($this->classname, false, array_merge($where,array(
            "recordid"	=> $this->recordid
        )),  array($this->baseTable . ".id", $ordertype), $limit);
    }

    /**
     * gets versions of this ordered by time ASC
     *
     *@name versions
     *@access public
     */
    public function versionsASC($limit = null, $where = array()) {
        return $this->versions($limit, $where, true);
    }

    /**
     * gets the editor
     *
     *@name editor
     *@access public
     */
    public function editor() {
        if ($this->fieldGet("editorid") != 0) {
            return DataObject::get_one("user",array('id' => $this['editorid']));
        } else
            return $this->autor();
    }

    /**
     * generates the form via controller
     *
     *@name form
     *@access public
     */
    public function form() {
        return $this->controller()->form(null, $this);
    }

    /**
     * gets the class of the current record
     *@name getRecordClass
     *@access public
     */
    public function RecordClass()
    {
        return $this->classname;
    }
    /**
     * gets the id
     *
     *@name getID
     *@access public
     */
    public function ID() {
        return ($this->isField("recordid")) ? $this->fieldGet("recordid") : $this->fieldGet("id");
    }

    /**
     * gets the versionid
     *@name getVersionId
     *@access public
     */
    public function VersionId()
    {
        if (isset($this->data["versionid"]))
        {
            return $this->data["versionid"];
        } else
        {
            return isset($this->data["id"]) ? $this->data["id"] : 0;
        }
    }

    /**
     * sets the id
     *
     *@name setID
     *@access public
     */
    public function setID($val) {
        if ($val == 0) {
            $this->setField("id", 0);
            $this->setField("versionid", 0);
            $this->setField("recordid", 0);
        } else {
            $this->setField("id", $val);
            $this->setField("recordid", $val);

            $vID = 0;
            $query = new SelectQuery($this->baseTable . "_state", array("publishedid"), array("id" => $val));
            if ($query->execute()) {
                if ($row = $query->fetch_object()) {
                    $vID = $row->publishedid;
                }
            }

            $this->setField("versionid", $vID);
        }
    }

    /**
     * returns the representation of this record
     *
     *@name generateResprensentation
     *@access public
     */
    public function generateRepresentation($link = false) {
        if ($this->title)
            $title = $this->title;

        else if ($this->name)
            $title = $this->name;
        else {
            $fields = array_values($this->DataBaseFields());
            if (isset($fields[0]))
                $title = $this[$fields[0]];
            else
                return null;
        }

        if (ClassInfo::findFile(StaticsManager::getStatic($this->classname, "icon"), $this->classname)) {
            $title = '<img src="'.ClassInfo::findFile(StaticsManager::getStatic($this->classname, "icon"), $this->classname).'" /> ' . $title;
        }

        return $title;
    }

    //!Queries

    /**
     * extensions
     */

    /**
     * cache the part, which is the same every DataObject
     *@name query_cache
     *@access protected
     *@var array
     */
    protected static $query_cache = array();

    /**
     * builds the Query
     * @name buildQuery
     * @access public
     * @param string|int - version
     * @param array - filter
     * @param array - sort
     * @param array - limit
     * @param array - joins
     * @param bool - if to include class-filter
     * @return SelectQuery
     */
    public function buildQuery($version, $filter, $sort = array(), $limit = array(), $join = array(), $forceClasses = true)
    {
        if (PROFILE) Profiler::mark("DataObject::buildQuery");


        // check if table in db and if not, create it
        if ($this->baseTable != "" && !isset(ClassInfo::$database[$this->baseTable])) {
            foreach(array_merge(ClassInfo::getChildren($this->classname), array($this->classname)) as $child) {
                Object::instance($child)->buildDB();
            }
            ClassInfo::write();
        }

        if (PROFILE) Profiler::mark("DataObject::buildQuery hairy");

        $baseClass = $this->baseClass;
        $baseTable = $this->baseTable;

        // cache the most hairy part
        if (!isset(self::$query_cache[$this->baseClass]))
        {
            $query = new SelectQuery($baseTable);

            if ($classes = ClassInfo::dataclasses($this->baseClass))
            {
                foreach($classes as $class => $table)
                {
                    if ($class != $baseClass && isset(ClassInfo::$database[$table]) && ClassInfo::$database[$table])
                    {
                        $query->leftJoin($table, " ".$table.".id = ".$baseTable.".id");
                    }
                }
            }

            self::$query_cache[$this->baseClass] = $query;


        }

        $query = clone self::$query_cache[$this->baseClass];

        if (PROFILE) Profiler::unmark("DataObject::buildQuery hairy");

        if (is_array($filter)) {
            if (isset($filter["versionid"])) {
                $filter["".$this->baseTable.".id"] = $filter["versionid"];
                unset($filter["versionid"]);

                if($version === null) {
                    $version = false;
                }
            }
        }

        if ($version !== false && self::versioned($this->classname)) {
            if (isset($_GET[$baseClass . "_version"]) && $this->memberCanViewVersions($version)) {
                $version = $_GET[$baseClass . "_version"];
            }

            if (isset($_GET[$baseClass . "_state"]) && $this->memberCanViewVersions($version)) {
                $version =DataObject::VERSION_STATE;
            }
        }

        // some specific fields
        $query->db_fields["autorid"] = $baseTable;
        $query->db_fields["editorid"] = $baseTable;
        $query->db_fields["last_modified"] = $baseTable;
        $query->db_fields["class_name"] = $baseTable;
        $query->db_fields["created"] = $baseTable;
        $query->db_fields["versionid"] = $baseTable . ".id AS versionid";

        // set filter
        $query->filter($filter);

        // VERSIONS
        // join state-table, also if we don't have versioned enabled ;)
        if (isset(ClassInfo::$database[$baseTable . "_state"])) {
            if ($version !== false) {
                // if we get as normal, so just published records
                if ($version === null || $version == DataObject::VERSION_PUBLISHED) {
                    $query->data["includedVersionTable"] = true;
                    $query->innerJoin($baseTable . "_state", " ".$baseTable."_state.publishedid = ".$baseTable.".id AND ".$baseTable."_state.id = ".$baseTable.".recordid");
                    $query->db_fields["id"] = $baseTable . "_state";

                    // if we use state mode
                } else if ($version == DataObject::VERSION_STATE) {
                    $query->data["includedVersionTable"] = true;
                    $query->innerJoin($baseTable . "_state", " ".$baseTable."_state.stateid = ".$baseTable.".id AND ".$baseTable."_state.id = ".$baseTable.".recordid");
                    $query->db_fields["id"] = $baseTable . "_state";

                    // if we prefer specific versions
                } else if (preg_match('/^[0-9]+$/', $version)) {
                    $query->addFilter($baseTable.'.id = (
							SELECT where_'.$baseTable.'.id FROM '.DB_PREFIX . $baseTable.' AS where_'.$baseTable.' WHERE where_'.$baseTable.'.recordid = '.$baseTable.'.recordid ORDER BY (where_'.$baseTable.'.id = '.$version.') DESC LIMIT 1
						)');

                    if (isset($query->filter["id"])) {
                        $query->filter["recordid"] = $query->filter["id"];
                    }

                    unset($query->filter["id"]);


                    // unmerge deleted records
                    $query->innerJoin($baseTable . "_state", " ".$baseTable."_state.id = ".$baseTable.".recordid");

                    // if we just get all, but we group
                } else if ($version == DataObject::VERSION_GROUP) {
                    $query->addFilter($baseTable.'.id IN (
							SELECT max(where_'.$baseTable.'.id) FROM '.DB_PREFIX . $baseTable.' AS where_'.$baseTable.' WHERE where_'.$baseTable.'.recordid = '.$baseTable.'.recordid GROUP BY where_'.$baseTable.'.recordid
						)');

                    // unmerge deleted records
                    $query->leftJoin($baseTable . "_state", " ".$baseTable."_state.id = ".$baseTable.".recordid");
                }
            } else {
                // if we make no versioning, we just get all records matching state-table.id to table.recordid
                // unmerge deleted records
                $query->leftJoin($baseTable . "_state", " ".$baseTable."_state.id = ".$baseTable.".recordid");
                $query->db_fields["id"] = $baseTable . "_state";
            }
        }

        $hasOnes = array();
        $has_one = $this->hasOne();

        // some specific addons for relations
        if (is_array($query->filter))
        {
            foreach($query->filter as $key => $value)
            {
                // many-many
                if (isset(ClassInfo::$class_info[$this->classname]["many_many"][$key]) || isset(ClassInfo::$class_info[$this->classname]["belongs_many_many"][$key]))
                {
                    if (is_array($value))
                    {
                        $object = isset(ClassInfo::$class_info[$this->classname]["many_many"][$key]) ? ClassInfo::$class_info[$this->classname]["many_many"][$key] : ClassInfo::$class_info[$this->classname]["belongs_many_many"][$key];
                        if ($object)
                        {
                            $objectTable = ClassInfo::$class_info[ClassInfo::$class_info[$object]["baseclass"]]["table"];
                            if (isset(ClassInfo::$class_info[$this->classname]["many_many_tables"][$key]))
                            {
                                $table = ClassInfo::$class_info[$this->classname]["many_many_tables"][$key]["table"];
                                $data = ClassInfo::$class_info[$this->classname]["many_many_tables"][$key];
                            } else
                            {
                                continue;
                            }
                            $query->from[] = ' INNER JOIN 
																			'.DB_PREFIX . $table.' 
																		AS 
																			'.$table.' 
																		ON 
																			'.$table.'.'.$data["field"]. ' = '.$baseTable.'.id
																		'; // join many-many-table with BaseTable table
                            $query->from[] = ' INNER JOIN 
																			'.DB_PREFIX . $objectTable.' 
																		AS 
																			'.$objectTable.' 
																		ON 
																			'.$table.'.'. $data["extfield"] . ' = '.$objectTable.'.id 
																		 '; // join other table with many-many-table
                        }
                        foreach($value as $field => $val)
                        {
                            if($field == "id") {
                                $field = "recordid";
                            }

                            if($field == "versionid") {
                                $field = "id";
                            }
                            $query->filter[$objectTable . '.' . $field] = $val;
                        }

                        $query->removeFilter($key);
                    } else
                    {
                        if (ClassInfo::$class_info[$this->classname]["many_many"][$key])
                        {
                            if (isset(ClassInfo::$class_info[$this->classname]["many_many_tables"][$key]))
                            {
                                $table = ClassInfo::$class_info[$this->classname]["many_many_tables"][$key]["table"];
                                $data = ClassInfo::$class_info[$this->classname]["many_many_tables"][$key];
                            } else
                            {
                                continue;
                            }
                            $query->from[] = ' INNER JOIN 
																			'.DB_PREFIX . $table.' 
																		AS 
																			'.$table.' 
																	ON  
																		 '.$table.'.'.$data["field"] . ' = '.$baseTable.'.id
																		 '; // join BaseTable with many-many-table
                        }
                        $query->removeFilter($key);
                    }
                } else

                    // has-one

                    if (strpos($key, ".") !== false) {
                        if (isset($has_one[strtolower(substr($key, 0, strpos($key, ".")))])) {
                            $has_oneField = substr($key, strpos($key, ".") + 1);
                            $hasOnes[strtolower(substr($key, 0, strpos($key, ".")))][$has_oneField] = $value;
                            $query->removeFilter($key);
                        }
                    }
                unset($key, $value, $table, $data, $__table, $_table);
            }
        }

        if (count($hasOnes) > 0) {
            foreach($hasOnes as $key => $fields) {
                $c = $has_one[$key];
                $table = ClassInfo::$class_info[$c]["table"];
                $hoBaseTable = (ClassInfo::$class_info[ClassInfo::$class_info[$c]["baseclass"]]["table"]);

                foreach($fields as $field => $val) {
                    $fields[$table . "." . $field] = $val;
                    unset($fields[$field]);
                }

                $query->from[] = ' INNER JOIN 
													'.DB_PREFIX . $table.' 
												AS 
													'.$table.' 
												ON  
												 '.$table.'.recordid = '.$this->Table().'.'.$key.'id AND ('.SQL::ExtractToWhere($fields, false).')';
                $query->from[] = ' INNER JOIN 
													'.DB_PREFIX . $hoBaseTable.'_state 
												AS 
													'.$hoBaseTable.'_state 
												ON  
												 '.$hoBaseTable.'_state.publishedid = '.$table.'.id';
            }
        }

        // sort
        if (!empty($sort)) {
            $query->sort($sort);
        } else {
            $query->sort(StaticsManager::getStatic($this->classname, "default_sort"));
        }



        // limiting
        $query->limit($limit);

        if ($join)
            foreach($join as $table => $statement)
            {
                if (preg_match('/^[0-9]+$/', $table) && is_numeric($table))
                    $query->from[] = $statement;
                else if ($statement == "")
                    $query->from[$table] = "";
                else if (strpos(strtolower($statement), "join"))
                    $query->from[$table] = $statement;
                else
                    $query->from[$table] = " LEFT JOIN ".DB_PREFIX.$table." AS ".$table." ON " . $statement;
            }

        // don't forget filtering on class-name
        if ($forceClasses) {
            $class_names = array_merge(array($this->classname), ClassInfo::getChildren($this->classname));
            $query->addFilter(array("class_name" => $class_names));
        }


        // free memory
        unset($baseClass, $baseTable, $sort, $filter);

        if (PROFILE) Profiler::unmark("DataObject::buildQuery");

        return $query;
    }

    /**
     * builds a SearchQuery and adds Search-Filter
     * after that decorates the query with argumentQuery and argumentSelectQuery on Extensions and local
     *
     * @name buildSearchQuery
     * @access public
     * @param array - search
     * @param array - filter
     * @param array - sort
     * @param array - limit
     * @param array - join
     * @param string|int|false - version
     * @return SelectQuery
     */
    public function buildSearchQuery($searchQuery = array(), $filter = array(), $sort = array(), $limit = array(), $join = array(), $version = false, $forceClasses = true) {
        if (PROFILE) Profiler::mark("DataObject::buildSearchQuery");

        $query = $this->buildQuery($version, $filter, $sort, $limit, $join);

        $query = $this->decorateSearchQuery($query, $searchQuery);

        foreach($this->getextensions() as $ext)
        {
            if (ClassInfo::hasInterface($ext, "argumentsQuery")) {
                $newquery = $this->getinstance($ext)->setOwner($this)->argumentQuery($query, $version, $filter, $sort, $limit, $join, $forceClasses);
                if (is_object($newquery) && (strtolower(get_class($newquery)) == "dbquery" || is_subclass_of($newquery, "DBQuery"))) {
                    $query = $newquery;
                    unset($newquery);
                }
            }

            if (ClassInfo::hasInterface($ext, "argumentsSearchQuery")) {
                $newquery = $this->getinstance($ext)->setOwner($this)->argumentSearchSQL($query, $searchQuery, $version, $filter, $sort, $limit, $join, $forceClasses);
                if (is_object($newquery) && (strtolower(get_class($newquery)) == "dbquery" || is_subclass_of($newquery, "DBQuery"))) {
                    $query = $newquery;
                    unset($newquery);
                }
            }
            unset($ext);
        }
        $this->argumentQuery($query);

        if (PROFILE) Profiler::unmark("DataObject::buildSearchQuery");

        return $query;
    }

    /**
     * returns whether user is permitted to use versions.
     *
     * @name _canVersion
     * @access public
     * @return bool
     */
    public function memberCanViewVersions($version) {
        if($version == true || $this->can("viewVersions")) {
            return true;
        }

        if (member::login()) {
            $perms = $this->providePerms();
            foreach($perms as $key => $val) {
                if (preg_match("/publish/i", $key) || preg_match("/edit/i", $key) || preg_match("/write/i", $key)) {
                    if (Permission::check($key)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * decorates a query with search
     *
     *@name decorateSearchQuery
     *@access public
     *@param object - query
     *@param words
     */
    public function decorateSearchQuery($query, $searchQuery) {
        if ($searchQuery) {
            $filter = array();

            if(!is_array($searchQuery))
                $searchQuery = array($searchQuery);

            foreach($searchQuery as $word) {
                $i = 0;
                $table_name = ClassInfo::$class_info[$this->baseClass]["table"];
                if ($table_name != "")
                {
                    if ($this->searchFields())
                        foreach($this->searchFields() as $field) {
                            if (isset(ClassInfo::$database[$table_name][$field])) {
                                if ($i == 0) {
                                    $i++;
                                } else {
                                    $filter[] = "OR";
                                }

                                $filter[$table_name . "." . $field] = array(
                                    "LIKE",
                                    "%" . $word . "%"
                                );
                            }
                        }
                }

                if ($classes = ClassInfo::DataClasses($this->baseClass)) {
                    foreach($classes as $class => $table) {
                        $table_name = ClassInfo::$class_info[$class]["table"];
                        if ($table_name != "") {
                            if ($this->searchFields())
                                foreach($this->searchFields() as $field) {
                                    if (isset(ClassInfo::$database[$table_name][$field])) {
                                        if ($i == 0) {
                                            $i++;
                                        } else {
                                            $filter[] = "OR";
                                        }
                                        $filter[$table_name . "." . $field] = array(
                                            "LIKE",
                                            "%" . $word . "%"
                                        );
                                    }
                                }
                        }
                    }
                }


            }

            if ($filter)
                $query->addFilter(array($filter));
            else
                throw new LogicException("Could not search for ".$searchQuery.". No Search-Fields defined in {$this->baseClass}.");
        }
        return $query;
    }
    /**
     * local argument sql
     *
     *@name argumentQuery
     *@access public
     */

    public function argumentQuery(&$query) {

    }
    /**
     * builds an SQL-Query and arguments it through extensions
     *
     *@name buildQuery
     *@access public
     *@param string|int|false - version
     *@param array - filter
     *@param array - sort
     *@param array - limit
     *@param array - joins
     *@param bool - to force recordclass is part of this class or a subclass
     *@return SelectQuery-Object
     */
    public function buildExtendedQuery($version, $filter = array(), $sort = array(), $limit = array(), $joins = array(), $forceClasses = true)
    {

        if (PROFILE) Profiler::mark("DataObject::buildExtendedQuery");
        $query = $this->buildQuery($version, $filter, $sort, $limit, $joins, $forceClasses);
        foreach($this->getextensions() as $ext)
        {
            if (ClassInfo::hasInterface($ext, "argumentsQuery")) {
                $newquery = $this->getinstance($ext)->setOwner($this)->argumentQuery($query, $version, $filter, $sort, $limit, $joins, $forceClasses);
                if (is_object($newquery) && (strtolower(get_class($newquery)) == "dbquery" || is_subclass_of($newquery, "DBQuery"))) {
                    $query = $newquery;
                    unset($newquery);
                }
            }
            unset($ext);
        }
        $this->argumentQuery($query);
        if (PROFILE) Profiler::unmark("DataObject::buildExtendedQuery");
        return $query;
    }

    /**
     * gets all the records of one query as an array
     *@name getRecords
     *@param string|int|false - version
     *@param array - filter
     *@param array - sort
     *@param array - limit
     *@param array - joins
     *@param array - search
     *@return array
     */
    public function getRecords($version, $filter = array(), $sort = array(), $limit = array(), $joins = array(), $search = array())
    {
        if (!isset(ClassInfo::$class_info[$this->baseClass]["table"]) || !ClassInfo::$class_info[$this->baseClass]["table"] || !defined("SQL_LOADUP")) {
            return array();
        }

        if (PROFILE) Profiler::mark("DataObject::getRecords");

        /* --- */

        // generate hash for caching
        if (empty($groupby)) {
            if (PROFILE) Profiler::mark("getRecords::hash");
            $limithash = (is_array($limit)) ? implode($limit) : $limit;
            $joinhash = (empty($joins)) ? "" : implode($joins);
            $searchhash = (is_array($search)) ? implode($search) : $search;
            $basehash = "record_" . $limithash . serialize($sort) . $joinhash . $searchhash . $version;
            if (is_array($filter)) {
                $hash = $basehash . md5(serialize($filter));
            } else {
                $hash = $basehash . "_all_" . md5($filter);
            }
            unset($limithash, $joinhash, $searchhash);
            if (PROFILE) Profiler::unmark("getRecords::hash");
            if (isset(DataObjectQuery::$datacache[$this->baseClass][$hash])) {
                return DataObjectQuery::$datacache[$this->baseClass][$hash];
            }
        }

        /* --- */


        if (empty($search)) {
            $query = $this->buildExtendedQuery($version, $filter, $sort, $limit, $joins);
        } else {
            $query = $this->buildSearchQuery($search, $filter, $sort, $limit, $joins, $version);
        }

        $this->tryToBuild($query);

        $query->execute();

        $arr = array();

        while($row = sql::fetch_assoc($query->result))
        {
            $arr[] = $row;
            // store id in cache
            if (isset($basehash))
                DataObjectQuery::$datacache[$this->baseClass][$basehash . md5(serialize(array("id" => $row["id"])))] = array($row);
            
            // cleanup
            unset($row);
        }

        /** @var String $hash */
        DataObjectQuery::$datacache[$this->baseClass][$hash] = $arr;

        $query->free();
        unset($hash, $basehash, $limits, $sort, $filter, $query); // free memory
        if (PROFILE) Profiler::unmark("DataObject::getRecords");

        return $arr;
    }

    /**
     * validates if all tables exist and if not, tries to build them.
     *
     * @param SelectQuery
     */
    public function tryToBuild(SelectQuery $query) {
        // validate from
        foreach($query->from as $table => $data) {
            if (is_string($table) && !preg_match('/^[0-9]+$/', $table)) {
                if (!isset(ClassInfo::$database[$table])) {
                    // try to create the tables
                    $this->buildDev();
                }
            }
        }
    }

    /**
     * gets records grouped
     *
     * @name getGroupedRecords
     * @access public
     * @param int|false|string - version
     * @param string - field to group
     * @param array - filter
     * @param array - sort
     * @param array - limits
     * @param array - joins
     * @param array - search
     * @return array
     */
    public function getGroupedRecords($version, $groupField, $filter = array(), $sort = array(), $limit = array(), $joins = array(), $search = array()) {
        if (!isset(ClassInfo::$class_info[$this->baseClass]["table"]) || !ClassInfo::$class_info[$this->baseClass]["table"] || !defined("SQL_LOADUP"))
            return array();

        if (PROFILE) Profiler::mark("DataObject::getGroupedRecords");

        $data = array();


        if (empty($search)) {
            $query = $this->buildExtendedQuery($version, $filter, $sort, $limit, $joins);
        } else {
            $query = $this->buildSearchQuery($search, $filter, $sort, $limit, $joins, $version);
        }

        $query->distinct = true;

        $query->fields = array($groupField);

        $this->tryToBuild($query);

        $query->execute();

        while($row = $query->fetch_assoc()) {
            if (isset($row[$groupField])) {
                if (is_array($filter)) {
                    $filter[$groupField] = $row[$groupField];
                } else {
                    $filter = array($groupField => $row[$groupField], $filter);
                }

                $data[$row[$groupField]] = DataObject::get($this->classname, $filter, $sort, array(), $joins, $version);
            }
            unset($row);
        }
        $query->free();

        if (PROFILE) Profiler::unmark("DataObject::getGroupedRecords");

        return $data;
    }

    /**
     * this is the most flexible method of all the methods, but you need to know much
     * you can define here fields and groupby at once and get an array as result back
     *
     * @name getAggregate
     * @access public
     * @param false|int|string - version
     * @param string|array - fields
     * @param array - filter
     * @param array - sort
     * @param array - limits
     * @param array - joins
     * @param array - search
     * @param array - groupby
     * @return array
     */
    public function getAggregate($version, $aggregate, $filter = array(), $sort = array(), $limit = array(), $joins = array(), $search = array(), $groupby = array()) {
        if (!isset(ClassInfo::$class_info[$this->baseClass]["table"]) || !ClassInfo::$class_info[$this->baseClass]["table"] || !defined("SQL_LOADUP"))
            return array();

        if (PROFILE) Profiler::mark("DataObject::getAggregate");

        $data = array();

        if (empty($search)) {
            $query = $this->buildExtendedQuery($version, $filter, $sort, $limit, $joins);
        } else {
            $query = $this->buildSearchQuery($search, $filter, $sort, $limit, $joins, $version);
        }

        $this->tryToBuild($query);
        $query->groupby($groupby);

        if ($query->execute($aggregate)) {

            while($row = $query->fetch_assoc()) {
                $data[] = $row;
                unset($row);
            }

        }

        if (PROFILE) Profiler::unmark("DataObject::getAggregate");

        return $data;
    }

    //!Connection to the Controller

    /**
     * controller
     *@name controller
     *@access public
     */
    public $controller = "";

    /**
     * sets the controller
     *@name setController
     *@access public
     */
    public function setController(Object &$controller)
    {
        $this->controller = $controller;
    }
    /**
     * gets the controller for this class
     *@name controller
     *@access public
     */
    public function controller($controller = null)
    {
        if (isset($controller)) {
            $this->controller = clone $controller;
            $this->controller->setModelInst($this);
            $this->controller->request = null;
            return $this->controller;
        }

        if (is_object($this->controller))
        {
            return $this->controller;
        }

        /* --- */

        if ($this->controller != "")
        {
            $this->controller = new $this->controller;
            $this->controller->setModelInst($this);
            return $this->controller;
        } else {

            if (ClassInfo::exists($this->classname . "controller"))
            {
                $c = $this->classname . "controller";
                $this->controller = new $c();
                $this->controller->setModelInst($this);
                return $this->controller;
            } else {

                // find existing controller in parent classes.
                if (ClassInfo::getParentClass($this->classname) != "dataobject") {
                    $parent = $this->classname;
                    while(($parent = ClassInfo::getParentClass($parent)) != "dataobject") {
                        if (!$parent)
                            return false;

                        if (ClassInfo::exists($parent . "controller")) {
                            $c = $parent . "controller";
                            $this->controller = new $c();
                            $this->controller->setModelInst($this);
                            return $this->controller;
                        }
                    }
                }
            }
        }
        return false;
    }

    //! APIs

    /**
     * resets the DataObject
     *@name reset
     *@access public
     */
    public function reset()
    {
        parent::reset();
        $this->viewcache = array();
        $this->data = array();
    }

    /**
     * gets the class as an instance of the given class-name.
     *
     * @param   string type of object
     * @return  Object of type $value
     */
    public function getClassAs($value) {
        if (is_subclass_of($value, $this->baseClass)) {
            return new $value(array_merge($this->data, array("class_name" => $value)));
        }

        return $this;
    }

    /**
     * checks if we can sort by a specified field
     *
     *@name canSortBy
     *@access public
     */
    public function canSortBy($field) {
        $fields = $this->DataBaseFields(true);
        return isset($fields[$field]);
    }

    /**
     * checks if we can filter by a specified field
     *
     *@name canSortBy
     *@access public
     */
    public function canFilterBy($field) {
        if (strpos($field, ".") !== false) {
            $has_one = $this->HasOne();

            if (isset($has_one[strtolower(substr($field, 0, strpos($field, ".")))])) {
                return true;
            }
        }

        $fields = $this->DataBaseFields(true);
        return isset($fields[$field]);
    }

    /**
     * this method consolidates all relation data in data
     *
     * @name consolidate
     * @access public
     * @return $this
     */
    public function consolidate() {
        foreach($this->ManyManyRelationships() as $name => $data) {
            $this->getRelationIDs($name);
        }
        return $this;
    }

    /**
     * gets a object of this record with id and versionid set to 0.
     * it also adds hasmany-relations.
     *
     *@name duplicate
     *@access public
     */
    public function duplicate() {
        $this->consolidate();
        $data = clone $this;

        $data->id = 0;
        $data->versionid = 0;

        foreach($this->hasMany() as $name => $class) {
            $data->data[$name] = $this->getHasMany($name);
        }

        return $data;
    }

    public function _clone() {
        $this->consolidate();
        $data = clone $this;

        $data->id = 0;
        $data->versionid = 0;

        return $data;
    }

    /**
     * duplicates given number of this model and writes them to the database.
     *
     * @param int $num
     * @param null $fieldToRise
     * @param bool $forceWrite
     * @param int $snap_priority
     * @return bool
     */
    public function duplicateWrite($num = 1, $fieldToRise = null, $forceWrite = false, $snap_priority = 2) {
        $fieldValue = array();
        for($i = 0; $i < $num; $i++) {
            $data = $this->duplicate();

            // rise field(s)
            if (isset($fieldToRise)) {
                if (is_array($fieldToRise)) {
                    foreach($fieldToRise as $field) {
                        $this->riseField($data, $field, $i);
                    }
                } else {
                    $this->riseField($data, $fieldToRise, $i);
                }
            }

            if (!$data->write(false, $forceWrite, $snap_priority)) {
                return false;
            }
        }
        return true;
    }

    /**
     * tries to rise field on object.
     *
     * @name riseField
     * @param   Object model
     * @param   string $field
     * @return  Object
     */
    protected function riseField($model, $field, $i) {
        $val = $model->$field;
        if (preg_match('/^(.*)([0-9]+)$/Us', $val, $m)) {
            $model->$field = $m[1] . ($m[2] + $i + 1);
        } else {
            $model->$field = $val . " " . ($i + 1);
        }
    }

    /**
     * bool
     */
    public function bool() {
        return (array_merge(array(
                "class_name"	=> $this->classname,
                "last_modified"	=> NOW,
                "created"		=> NOW,
                "autorid"		=> member::$id
            ), (array) $this->defaults) != $this->data);
    }

    /**
     * clears the data-cache
     *
     *@name clearDataCache
     *@access public
     */
    public static function clearDataCache($class = null) {
        if (isset($class)) {
            $class = strtolower($class);
            DataObjectQuery::$datacache[$class] = array();
        } else {
            DataObjectQuery::$datacache = array();
        }
    }

    /**
     * to array if we need data for REST-API.
     *
     * It also embeds has-one-relations to this record.
     */
    public function ToRESTArray($additional_fields = array(), $includeHasOne = false) {
        $data = parent::ToRestArray($additional_fields);

        foreach($this->HasOne() as $name => $class) {
            if($includeHasOne || isset($additional_fields[$name])) {
                if ($this->$name && Object::method_exists($this->$name, "ToRestArray")) {
                    $data[$name] = $this->$name()->ToRestArray();
                }
            }
        }

        return $data;
    }

    //!API for Config

    /**
     * returns DataBase-Fields of this record
     *
     *@name DataBaseFields
     */
    public function DataBaseFields($recursive = false) {
        if ($recursive) {
            $db = array();
            if (isset(ClassInfo::$class_info[$this->baseClass]["db"])) {
                $db = array_merge($db, ClassInfo::$class_info[$this->baseClass]["db"]);
            }

            if ($dataClasses = ClassInfo::DataClasses($this->baseClass)) {
                foreach ($dataClasses as $dataClass => $table) {
                    if (isset(ClassInfo::$class_info[$dataClass]["db"])) {
                        $db = array_merge($db, ClassInfo::$class_info[$dataClass]["db"]);
                    }
                }
            }

            return $db;
        } else
            return isset(ClassInfo::$class_info[$this->classname]["db"]) ? ClassInfo::$class_info[$this->classname]["db"] : array();
    }

    /**
     * returns the indexes
     *
     *@name indexes
     */
    public function indexes() {
        return isset(ClassInfo::$class_info[$this->classname]["index"]) ? ClassInfo::$class_info[$this->classname]["index"] : array();
    }

    /**
     * returns the search-fields
     *
     *@name searchFields
     */
    public function searchFields() {
        return isset(ClassInfo::$class_info[$this->classname]["search"]) ? ClassInfo::$class_info[$this->classname]["search"] : array();
    }

    /**
     * table
     *
     *@name Table
     */
    public function Table() {
        return isset(ClassInfo::$class_info[$this->classname]["table"]) ? ClassInfo::$class_info[$this->classname]["table"] : false;
    }

    /**
     * table
     *
     *@name hasTable
     */
    public function hasTable() {
        return ((isset(ClassInfo::$class_info[$this->classname]["table_exists"]) ? ClassInfo::$class_info[$this->classname]["table_exists"] : false) && $this->Table());
    }

    /**
     * has-one-relations
     *
     *@name hasOne
     */
    public function hasOne($component = null) {
        if ($component === null) {
            $has_one = (isset(ClassInfo::$class_info[$this->classname]["has_one"]) ? ClassInfo::$class_info[$this->classname]["has_one"] : array());

            if ($classes = ClassInfo::dataclasses($this->classname)) {
                foreach($classes as $class) {
                    if (isset(ClassInfo::$class_info[$class]["has_one"]))
                        $has_one = array_merge(ClassInfo::$class_info[$class]["has_one"], $has_one);
                }
            }

            return $has_one;
        } else {
            if (isset(ClassInfo::$class_info[$this->classname]["has_one"][$component])) {
                return ClassInfo::$class_info[$this->classname]["has_one"][$component];
            }
        }
    }

    /**
     * returns one or many hasMany-Relations.
     *
     * @name hasMany
     * @param name of has-many-relation to give back.
     * @param if to only give back the class or also give the relation to inverse back.
     */
    public function hasMany($component = null, $classOnly = true) {
        $has_many = (isset(ClassInfo::$class_info[$this->classname]["has_many"]) ? ClassInfo::$class_info[$this->classname]["has_many"] : array());

        if ($classes = ClassInfo::dataclasses($this->classname)) {
            foreach($classes as $class) {
                if (isset(ClassInfo::$class_info[$class]["has_many"]))
                    $has_many = array_merge(ClassInfo::$class_info[$class]["has_many"], $has_many);
            }
        }

        if($component) {
            if($has_many && isset($has_many[strtolower($component)])) {
                $has_many = $has_many[strtolower($component)];
            } else {
                return false;
            }
        }

        if($has_many && $classOnly) {
            return preg_replace('/(.+)?\..+/', '$1', $has_many);
        } else {
            return $has_many ? $has_many : array();
        }
    }

    /**
     * many-many-relations
     *
     *@name ManyMany
     */
    public function ManyMany() {
        $many_many = (isset(ClassInfo::$class_info[$this->classname]["many_many"]) ? ClassInfo::$class_info[$this->classname]["many_many"] : array());

        if ($classes = ClassInfo::dataclasses($this->classname)) {
            foreach($classes as $class) {
                if (isset(ClassInfo::$class_info[$class]["many_many"]))
                    $many_many = array_merge(ClassInfo::$class_info[$class]["many_many"], $many_many);
            }
        }

        return $many_many;
    }

    /**
     * many-many-relations belonging to this
     *
     *@name BelongsManyMany
     */
    public function BelongsManyMany() {
        $belongs_many_many = (isset(ClassInfo::$class_info[$this->classname]["belongs_many_many"]) ? ClassInfo::$class_info[$this->classname]["belongs_many_many"] : array());

        if ($classes = ClassInfo::dataclasses($this->classname)) {
            foreach($classes as $class) {
                if (isset(ClassInfo::$class_info[$class]["many_many"]))
                    $belongs_many_many = array_merge(ClassInfo::$class_info[$class]["belongs_many_many"], $belongs_many_many);
            }
        }

        return $belongs_many_many;
    }

    /**
     * returns casting-values
     *
     */
    public function casting() {
        $casting = parent::casting();

        return array_merge($this->DataBaseFields(true), $casting);
    }

    /**
     * many-many-tables belonging to this
     *
     * @name ManyManyTables
     * @return array
     */
    public function ManyManyTables() {
        Core::Deprecate(2.0, "DataObject::ManyManyTables");
        $relationShips = $this->ManyManyRelationships();

        $info = array();
        foreach($relationShips as $relationShip) {
            /** @var ModelManyManyRelationShipInfo $relationShip */
            $relationInfo = array();
            $relationInfo['table'] = $relationShip->getTableName();
            $relationInfo['extfield'] = $relationShip->getTargetField();
            $relationInfo['field'] = $relationShip->getOwnerField();
            $relationInfo['object'] = $relationShip->getTarget();

            $info[$relationShip->getRelationShipName()] = $relationInfo;
        }

        return $info;
    }

    /**
     * returns array of ModelManyManyRelationShipInfo Objects
     *
     * @return ManyManyRelationships
     */
    public function ManyManyRelationships() {
        return DataObjectClassInfo::getManyManyRelationships($this->classname);
    }


    /**
     * returns if a DataObject is versioned
     *
     *@name versioned
     */
    public static function Versioned($class) {
        if (StaticsManager::hasStatic($class, "versions") && StaticsManager::getStatic($class, "versions") == true)
            return true;

        if (Object::instance($class)->versioned == true)
            return true;

        return false;
    }

    /**
     * gets the baseclass of the current record
     *
     * @return string
     */
    public function BaseClass()
    {
        return isset(ClassInfo::$class_info[$this->classname]["baseclass"]) ? ClassInfo::$class_info[$this->classname]["baseclass"] : $this->classname;
    }

    /**
     * gets the base-table
     *
     *@name getBaseTable
     *@access public
     */
    public function BaseTable() {
        return (ClassInfo::$class_info[ClassInfo::$class_info[$this->classname]["baseclass"]]["table"]);
    }

    //!Dev-Area: Generation of DataBase

    /**
     * dev
     *
     * @param string $prefix optional
     * @throws MySQLException
     * @access public
     * @return string
     */
    public function buildDB($prefix = DB_PREFIX) {
        $log = "";
        $this->callExtending("beforeBuildDB", $prefix, $log);

        // first get all fields with translated types
        $db_fields = $this->DataBaseFields();
        $indexes = $this->indexes();
        $casting = $this->casting();

        // add some fields for versioning
        if ($this->Table() && $this->Table() == $this->baseTable) {
            if (!isset($db_fields["recordid"]))
                $db_fields["recordid"] = "int(10)";

            if (self::Versioned($this->classname)) {
                $db_fields["snap_priority"] = "int(10)";
            }

            if (!isset($indexes["recordid"]))
                $indexes["recordid"] = "INDEX";
        }

        if ($this->Table()) {

            // get correct SQL-Types for Goma-Field-Types
            foreach($db_fields as $field => $type) {
                if (isset($casting[strtolower($field)])) {

                    if ($casting[strtolower($field)] = DBField::parseCasting($casting[strtolower($field)])) {

                        $type = call_user_func_array(array($casting[strtolower($field)]["class"], "getFieldType"), (isset($casting[strtolower($field)]["args"])) ? $casting[strtolower($field)]["args"] : array());
                        if ($type != "")
                            $db_fields[$field] = $type;
                    }
                }
            }

            // now require table
            $log .= SQL::requireTable($this->table(), $db_fields, $indexes , $this->defaults, $prefix);
        }

        // versioned
        if ($this->Table() && $this->table() == $this->baseTable) {
            if (!SQL::getFieldsOfTable($this->baseTable . "_state")) {
                $exists = false;
            } else {
                $exists = true;
            }

            // force table
            $log .= SQL::requireTable(	$this->baseTable . "_state",
                array(	"id" => "int(10) PRIMARY KEY auto_increment",
                    "stateid" => "int(10)",
                    "publishedid" => "int(10)"
                ),
                array(	"publishedid" => array(	"name" => "publishedid",
                    "fields" => array("publishedid"),
                    "type" => "index"
                ),
                    "stateid" => array(	"name" => "stateid",
                        "fields" => array("stateid"),
                        "type" => "index"
                    )
                ),
                array(),
                $prefix
            );
            if (!$exists) {
                // now copy records from old table to new
                $sql = "INSERT INTO ".$prefix . $this->baseTable."_state (id, stateid, publishedid) SELECT id AS id, id AS stateid, id AS publishedid FROM ".$prefix . $this->baseTable."";
                if (self::Versioned($this->classname)) {
                    $sql2 = "UPDATE ".$prefix.$this->baseTable." SET snap_priority = 2, recordid = id, editorid = autorid";
                } else {
                    $sql2 = "UPDATE ".$prefix.$this->baseTable." SET recordid = id, editorid = autorid";
                }
                if (sql::query($sql) && sql::query($sql2))
                    $log .= "Copying Version-Data\n";
                else
                    throw new MySQLException();
            }

            // set Database-Record
            ClassInfo::$database[$this->baseTable . "_state"] = array(
                "id" => "int(10)",
                "stateid" => "int(10)",
                "publishedid" => "int(10)"
            );
        }

        $engine = StaticsManager::getStatic($this->classname, "engine");
        if($engine) {

            $engines = array_map("strtolower", SQL::listStorageEngines());

            if(in_array(strtolower($engine), $engines)) {
                SQL::setStorageEngine($prefix . $this->baseTable . "_state", $engine);
                SQL::setStorageEngine($prefix . $this->table(), $engine);
            }
        }

        $relationships = DataObjectClassInfo::getManyManyRelationships($this->classname);

        if(!empty($relationships)) {
            foreach($relationships as $relationShip) {
                /** @var ModelManyManyRelationShipInfo $relationShip */
                $fields = $relationShip->getPlannedTableLayout();
                $tableName = $relationShip->getTableName();

                $log .= SQL::requireTable(
                    $tableName,
                    $fields,
                    $relationShip->getIndexes(),
                    array(),
                    $prefix
                );
                ClassInfo::$database[$tableName] = $fields;
            }
        }

        // sort of table
        $sort = StaticsManager::getStatic($this->classname, "default_sort");

        if (is_array($sort)) {
            if (isset($sort["field"], $sort["type"])) {
                $field = $sort["field"];
                $type = $sort["type"];
            } else {
                $sort = array_values($sort);
                $field = $sort[0];
                $type = isset($sort[1]) ? $sort[1] : "ASC";
            }
        } else if (preg_match('/^([a-zA-Z0-9_\-]+)\s(DESC|ASC)$/Usi', $sort, $matches)) {
            $field = $sort[1];
            $type = $sort[2];
        } else {
            $field = $sort;
            $type = "ASC";
        }
        if (isset(ClassInfo::$database[$this->Table()][$field])) {
            SQL::setDefaultSort($this->Table(), $field, $type);
        }

        $this->callExtending("buildDB", $prefix, $log);

        $this->preserveDefaults($prefix, $log);
        $this->cleanUpDB($prefix, $log);

        $this->callExtending("afterBuildDB", $prefix, $log);

        $output = '<div style="padding-top: 6px;"><img src="images/success.png" height="16" alt="Success" /> Checking Database of '.$this->classname."</div><div style=\"padding-left: 21px;width: 550px;\">";
        $output .= str_replace("\n", "<br />",$log);
        $output .= "</div>";
        return $output;
    }

    /**
     * generates some ClassInfo
     *
     *@name generateClassInfo
     *@access public
     */
    public function generateClassInfo() {
        if (defined("SQL_LOADUP") && SQL::getFieldsOfTable($this->baseTable . "_state")) {
            // set Database-Record
            ClassInfo::$database[$this->baseTable . "_state"] = array(
                "id" => "int(10)",
                "stateid" => "int(10)",
                "publishedid" => "int(10)"
            );
        }

        $this->callExtending("generateClassInfo");
    }

    /**
     * preserve Defaults
     *
     * @name preserveDefaults
     * @access public
     * @return bool
     */
    public function preserveDefaults($prefix = DB_PREFIX, &$log) {
        $this->callExtending("preserveDefaults", $prefix);

        if ($this->hasTable()) {
            //@todo bugfix
            if (count($this->defaults) > 0) {
                foreach($this->defaults as $field => $value) {
                    if (isset(ClassInfo::$database[$this->Table()][$field])) {
                        $sql = "UPDATE ".DB_PREFIX . $this->Table()." SET ".$field." = '".$value."' WHERE ".$field." = '' AND ".$field." != '0'";
                        if (!sql::query($sql, false, $prefix)) {
                            return false;
                        }
                    }
                }
            }

            if ($this->baseClass == $this->classname) {
                // set record ids
                $sql = "UPDATE ".DB_PREFIX . $this->Table()." SET recordid = id WHERE recordid = 0";
                SQL::query($sql);

                $sql = "UPDATE ".DB_PREFIX . $this->Table()." SET editorid = autorid WHERE editorid = 0";
                SQL::query($sql);
            }
        }

        if ($this->Table() == $this->baseTable) {
            // clean-up recordid-0s
            $sql = "SELECT * FROM ".DB_PREFIX . $this->Table()." WHERE recordid = '0'";
            if ($result = SQL::Query($sql)) {
                while($row = SQL::fetch_object($result)) {
                    $_sql = "SELECT * FROM ".DB_PREFIX . $this->baseTable."_state WHERE publishedid = '".$row->id."' OR stateid = '".$row->id."'";
                    if ($_result = SQL::Query($_sql)) {
                        if ($_row = SQL::fetch_object($_result)) {
                            $update = "UPDATE ".DB_PREFIX . $this->Table()." SET recordid = '".$_row->id."' WHERE id = '".$row->id."'";
                            SQL::Query($update);
                        }
                    }
                }
            }
        }

        return true;
    }

    public static $cleanUp = array();

    /**
     * clean up DB
     *
     *@name cleanUpDB
     *@åccess public
     */
    public function cleanUpDB($prefix = DB_PREFIX, &$log = null) {
        $this->callExtending("cleanUpDB", $prefix);

        if (self::Versioned($this->classname) && $this->baseClass == $this->classname) {
            $recordids = array();
            $ids = array();
            // first recordids
            $sql = "SELECT * FROM ".DB_PREFIX.$this->baseTable."_state";
            if ($result = SQL::Query($sql)) {
                while($row = SQL::fetch_object($result)) {
                    $recordids[$row->id] = $row->id;
                    $ids[$row->publishedid] = $row->publishedid;
                    $ids[$row->stateid] = $row->stateid;
                }
            }

            $deleteids = array();

            $last_modified = NOW-(180*24*60*60);

            // now generate ids to delete
            $sql = "SELECT id FROM ".DB_PREFIX . $this->baseTable." WHERE (id NOT IN('".implode("','", $ids)."') OR recordid NOT IN ('".implode("','", $recordids)."')) AND (last_modified < ".$last_modified.")";
            if ($result = SQL::Query($sql)) {
                while($row = SQL::fetch_object($result)) {
                    $deleteids[] = $row->id;
                }
            }

            $log .= 'Checking for old versions of '.$this->classname."\n";
            if (count($deleteids) > 10) {
                // now delete

                // first generate tables
                $tables = array($this->baseTable);
                foreach(ClassInfo::dataClasses($this->classname) as $class => $table) {
                    if ($this->baseTable != $table && isset(ClassInfo::$database[$table])) {
                        $tables[] = $table;
                    }
                }

                foreach($tables as $table) {
                    $sql = "DELETE FROM " . DB_PREFIX . $table . " WHERE id IN('".implode("','", $deleteids)."')";
                    if (SQL::Query($sql))
                        $log .= 'Delete old versions of '.$table."\n";
                    else
                        $log .= 'Failed to delete old versions of '.$table."\n";
                }
            }
        }

        // clean up many-many-tables
        foreach($this->manyManyTables() as $name => $data) {
            if(!isset(self::$cleanUp[$data["table"]])) {
                $sql = "DELETE FROM ". DB_PREFIX . $data["table"] ." WHERE ". $data["field"] ." = 0 OR ". $data["extfield"] ." = 0";
                if (SQL::Query($sql)) {
                    if (SQL::affected_rows() > 0)
                        $log .= 'Clean-Up Many-Many-Table '.$data["table"]  . "\n";
                } else {
                    $log .= 'Failed to clean-up Many-Many-Table '.$data["table"]. "\n";
                }

                if(isset(ClassInfo::$class_info[$data["object"]]["baseclass"])) {
                    $extBaseTable = ClassInfo::ClassTable(ClassInfo::$class_info[$data["object"]]["baseclass"]);
                    $sql = "DELETE FROM ". DB_PREFIX . $data["table"] ." WHERE ". $data["field"] ." NOT IN (SELECT id FROM ".DB_PREFIX . $this->baseTable.") OR ". $data["extfield"] ." NOT IN (SELECT id FROM ".DB_PREFIX . $extBaseTable.")";
                    register_shutdown_function(array("sql", "queryAfterDie"), $sql);
                }

                self::$cleanUp[$data["table"]] = true;
            }
        }
    }

    public static function cleanUpOldVersions($class, $recordid) {

    }

    //!Generate Information for ClassInfo
    /**
     * gets default SQL-Fields
     *
     * @param string $class name of class
     * @return array
     */
    public static function DefaultSQLFields($class) {
        if (strtolower(get_parent_class($class)) == "dataobject") {
            return array(
                'id'			=> 'INT(10) AUTO_INCREMENT  PRIMARY KEY',
                'last_modified' => 'DateTime()',
                'class_name' 	=> 'enum("'.implode('","', array_merge(Classinfo::getChildren($class), array($class))).'")',
                "created"		=> "DateTime()"
            );
        } else {
            return array(
                'id'			=> 'INT(10) AUTO_INCREMENT  PRIMARY KEY'
            );
        }
    }
}

class DBFieldNotValidException extends Exception {
    public function __construct($field) {
        parent::__construct("DB-Field-Name is not valid: " . $field);
    }
}