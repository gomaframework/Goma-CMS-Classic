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
 * @property    string class_name
 * @property    int last_modified timestamp
 * @property    int created timestamp
 * @property    int recordid
 * @property    int stateid
 * @property    int publishedid
 *
 * @property    User autor
 *
 * @method      string[] hasMany($component = null)
 * @method      DataObject|null getHasOne($name)
 * @method      HasMany_DataObjectSet getHasMany($name, $filter = null, $sort = null)
 * @method      ManyMany_DataObjectSet getManyMany($name, $filter = null, $sort = null)
 * @method      ModelHasOneRelationshipInfo[]|ModelHasOneRelationshipInfo hasOne($component = null)
 */
abstract class DataObject extends ViewAccessableData implements PermProvider,
    IDataObjectSetDataSource, IDataObjectSetModelSource, IFormForModelGenerator
{
    const VERSION_STATE = "state";
    const VERSION_PUBLISHED = "published";
    const VERSION_GROUP = "group";

    const RELATION_TARGET = "target";
    const RELATION_INVERSE = "inverse";

    const FETCH_TYPE = "fetch";
    const FETCH_TYPE_LAZY = "lazy";
    const FETCH_TYPE_EAGER = "eager";

    const CASCADE_TYPE = "cascade";
    const CASCADE_TYPE_UPDATE = "01";
    const CASCADE_TYPE_UPDATEFIELD = "00";
    const CASCADE_TYPE_REMOVE = "10";
    const CASCADE_TYPE_UNIQUE = "unique";
    const CASCADE_TYPE_ALL = "11";
    const CASCADE_UNIQUE_LIKE = "uniqueLike";

    const MANY_MANY_VERSION_MODE = "versionMode";
    const VERSION_MODE_CURRENT_VERSION = "current";
    const VERSION_MODE_LATEST_VERSION = "latest";
    const JOIN_TYPE = "type";
    const JOIN_STATEMENT = "statement";
    const JOIN_TABLE = "table";
    const JOIN_ALIAS = "alias";
    const JOIN_INCLUDEDATA = "includeFields";

    /**
     * default sorting
     */
    static $default_sort = "id";

    /**
     * enables or disabled history
     */
    static $history = true;

    /**
     * prefix for table_name
     */
    public $prefix = "";

    /**
     * admin-rights
     *
     * admin can do everything, implemented in can-methods
     */
    public $admin_rights;

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
     */
    public $queryVersion = DataObject::VERSION_PUBLISHED;

    /**
     * storage engine.
     */
    static $engine;

    /**
     * sort of many-many-tables.
     */
    static $many_many_sort = array();

    public $baseClass;
    public $baseTable;

    //!Global Static Methods
    /**
     * STATIC METHODS
     */

    /**
     * @param string $class
     * @return DataObject
     */
    public static function getModelDataSource($class) {
        return !ClassInfo::isAbstract($class) ? gObject::instance($class) : null;
    }

    /**
     * @param string $class
     * @return DataObject
     */
    public static function getDbDataSource($class) {
        return !ClassInfo::isAbstract($class) ?  gObject::instance($class) : null;
    }

    /**
     * gets a DataObject versioned
     *
     * @name getVersioned
     * @access public
     * @return array|DataObjectSet|DataObject[]
     */
    public static function get_versioned($class, $version = null, $filter = array(), $sort = array(), $limits = array(), $joins = array(), $group = false, $pagination = false) {
        if(!is_null($version) && !is_bool($version) &&
            $version != self::VERSION_PUBLISHED && $version != self::VERSION_STATE
            && $version != self::VERSION_GROUP) {
            throw new InvalidArgumentException("Version must be boolean, null or valid string.");
        }

        $data = self::get($class, $filter, $sort, $limits, $joins, $version, $pagination);
        if ($group !== false) {
            return $data->groupBy($group);
        }

        return $data;
    }

    /**
     * gets a DataObject versioned
     *
     * @name getVersioned
     * @access public
     * @return array|DataObjectSet|DataObject[]
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
     * @return DataObjectSet|DataObject[]
     */
    public static function get($class, $filter = null, $sort = null, $limits = null, $joins = null, $version = null, $pagination = null) {

        if (PROFILE) Profiler::mark("DataObject::get");

        $dataSet = new DataObjectSet($class, $filter, $sort, $joins, array(), $version);

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
     * @param String DataObject
     * @param array|string $filter
     * @param array $froms joins
     * @param array|string $groupby
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
     * updates data raw in the table and has not version-managment or multi-table-managment.
     *
     * You have to be familiar with the structure of goma when you use this method. It is much faster than all the other methods of writing, but also more complex.
     *
     * @param String $name Model or Table
     * @param array $data data to update
     * @param array $where where-clause
     * @param string $limit optional limit
     * @param boolean $silent if to change last-modified-date
     * @return bool
     * @throws MySQLException
     */
    public static function update($name, $data, $where, $limit = "", $silent = false)
    {
        if (PROFILE) Profiler::mark("DataObject::update");
        //Core::Deprecate(2.0);

        if (ClassInfo::exists($name) && is_subclass_of($name, "DataObject")) {
            $DataObject = gObject::instance($name);
            $table_name = $DataObject->Table();
        } else if (isset(ClassInfo::$database[$name])) {
            $table_name = $name;
        } else {
            throw new LogicException("Table or model '" . $name . "' does not exist.");
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
     * @return DataObject|null
     */
    public static function get_one($dataClass, $filter = array(), $sort = array(), $joins = array())
    {
        if (PROFILE) Profiler::mark("DataObject::get_one");

        if(is_int($filter)) {
            $filter = array("id" => $filter);
        }

        $output = self::get($dataClass, $filter, $sort, array(1), $joins)->first();

        if (PROFILE) Profiler::unmark("DataObject::get_one");

        return $output;
    }

    /**
     * gets a record by id
     *
     * @param string $class
     * @param int $id
     * @param array $joins
     * @return DataObject|null
     */
    public static function get_by_id($class, $id, $joins = array()) {
        return self::get_one($class, array("id" => $id), array(), $joins);
    }


    /**
     * searches in a model
     *
     * @name search_object
     * @access public
     * @param string|gObject $class
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
        $DataSet = new DataObjectSet($class, $filter, $sort, $join, $search);

        if ($pagination !== false) {
            if (is_int($pagination)) {
                $DataSet->activatePagination($pagination);
            } else {
                $DataSet->activatePagination();
            }
        }

        if ($groupby !== false) {
            return $DataSet->groupBy($groupby);
        }


        return $DataSet;
    }

    //!Init

    /**
     * generates a new DataObject with given record-info.
     *
     *@param array|null $record
     */
    public function __construct($record = null) {
        parent::__construct();

        $this->data = $this->original = array_merge(array(
            "class_name"	=> $this->classname,
            "last_modified"	=> time(),
            "created"		=> time(),
            "autorid"		=> member::$id,
            "id"            => 0,
            "versionid"     => 0
        ), (array) $this->defaults, ArrayLib::map_key("strtolower", (array) $record));

        $this->baseClass = $this->BaseClass();
        $this->baseTable = $this->BaseTable();

        $this->initValues();
    }

    /**
     * inits values.
     */
    public function initValues() {
        $this->callExtending("initValues");
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
     * checks if one of the given permissions is allowed for the current user.
     *
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

            if (gObject::method_exists($this->classname, "can" . $perm)) {
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
     * @param History $record
     * @return bool
     */
    public static function canViewHistory($record) {
        if (is_a($record, History::class)) {
            if ($record->oldversion && $record->newversion) {
                return ($record->oldversion->can(ModelPermissionManager::PERMISSION_TYPE_WRITE, $record->oldversion) && $record->newversion->can(ModelPermissionManager::PERMISSION_TYPE_WRITE, $record->newversion));
            } else if ($record->newversion) {
                return $record->newversion->can(ModelPermissionManager::PERMISSION_TYPE_WRITE, $record->newversion);
            } else if ($record->record) {
                return $record->record->can(ModelPermissionManager::PERMISSION_TYPE_WRITE, $record->record);
            }
        } else {
            throw new InvalidArgumentException("Invalid first argument for DataObject::canViewRecord. History required.");
        }
    }

    /**
     * @param DataObject $row
     * @param string $name
     * @return bool
     */
    protected function checkPermission($row, $name) {
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

                if (preg_match("/".preg_quote($name, "/")."/i", $key))
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
     * returns if a given record can be written to db
     *
     * @param  DataObject|null $row
     * @return bool
     */
    public function canWrite($row)
    {
        return $this->checkPermission($row, "write");
    }

    /**
     * returns if a given record can deleted in database
     *
     * @param DataObject $row
     * @return bool
     */
    public function canDelete($row)
    {
        return $this->checkPermission($row, "delete");
    }

    /**
     * returns if a given record can be inserted in database
     *
     * @param DataObject $row
     * @return bool
     */
    public function canInsert($row)
    {
        return $this->checkPermission($row, "insert");
    }

    /**
     * gets the writeaccess
     *
     * @return bool
     */
    public function getWriteAccess()
    {
        if (!self::Versioned($this->classname) && $this->can("Write")) {
            return true;
        } else if ($this->can("Publish")) {
            return true;
        } else if ($this->can("Delete")) {
            return true;
        }

        return false;
    }

    /**
     * returns if publish-right is available
     *
     * @param DataObject $record
     * @return bool
     */
    public function canPublish($record) {
        if(self::Versioned($this->classname)) {
            return $this->checkPermission($record, "publish");
        }

        return $record->id == 0 ? $this->canInsert($record) : $this->canWrite($record);
    }

    /**
     *
     */
    public function onBeforeRemove(&$manipulation)
    {

    }

    /**
     *
     */
    public function onAfterRemove()
    {

    }

    public function onBeforeRead(&$data)
    {
        $this->callExtending("onBeforeRead", $data);
    }


    /**
     * will be called before write
     *
     * @param ModelWriter $modelWriter
     */
    public function onBeforeWrite($modelWriter)
    {
        $this->callExtending("onBeforeWrite", $modelWriter);
    }

    /**
     * @param ModelWriter $modelWriter
     */
    public function onBeforeDBWriter($modelWriter)
    {
        $this->callExtending("onBeforeDBWriter", $modelWriter);
    }

    /**
     * will be called after write
     *
     * @param ModelWriter $modelWriter
     */
    public function onAfterWrite($modelWriter)
    {
        $this->callExtending("onBeforeWrite", $modelWriter);
    }


    /**
     * before manipulating the data
     *
     * @param array $manipulation
     * @param string $job
     */
    public function onBeforeManipulate(&$manipulation, $job)
    {

    }

    /**
     * before manipulating many-many data over @link ManyMany_DataObjectSet::write
     *
     * @param array $manipulation
     * @param ManyMany_DataObjectSet $dataset
     * @param array $writeData
     * @return mixed|void
     */
    public function onBeforeManipulateManyMany(&$manipulation, $dataset, $writeData) {

    }

    /**
     * before updating data-tables to write data
     * @param iDataBaseWriter $iDataBaseWriter
     */
    public function onBeforeWriteData($iDataBaseWriter) {

    }

    /**
     * is called before unpublish
     */
    public function onBeforeUnPublish() {

    }

    /**
     * is called before publish
     */
    public function onBeforePublish() {

    }

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
     * @deprecated
     */
    public function write($forceInsert = false, $forceWrite = false, $snap_priority = 2, $forcePublish = false, $history = true, $silent = false)
    {
        try {
            $this->writeToDB($forceInsert, $forceWrite, $snap_priority, $forcePublish, $history, $silent);
            return true;
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
     * @return void
     */
    public function writeToDB($forceInsert = false, $forceWrite = false, $snap_priority = 2, $forcePublish = false, $history = true, $silent = false, $overrideCreated = false)
    {
        $this->writeToDBInRepo(Core::repository(), $forceInsert, $forceWrite, $snap_priority, $history, $silent, $overrideCreated);
    }

    /**
     * writes changed data and throws exceptions.
     *
     * @param IModelRepository $repository
     * @param bool $forceInsert
     * @param bool $forceWrite
     * @param int $writeType
     * @param bool $history
     * @param bool $silent
     * @param bool $overrideCreated
     */
    public function writeToDBInRepo($repository, $forceInsert = false, $forceWrite = false, $writeType =  IModelRepository::WRITE_TYPE_PUBLISH, $history = true, $silent = false, $overrideCreated = false) {
        if(!$history) {
            HistoryWriter::disableHistory();
        }

        if($writeType >  IModelRepository::WRITE_TYPE_SAVE) {
            if($forceInsert) {
                $repository->add($this, $forceWrite, $silent, $overrideCreated);
            } else {
                $repository->write($this, $forceWrite, $silent, $overrideCreated);
            }
        } else {
            if($forceInsert) {
                $repository->addState($this, $forceWrite, $silent, $overrideCreated);
            } else {
                $repository->writeState($this, $forceWrite, $silent, $overrideCreated);
            }
        }

        if(!$history) {
            HistoryWriter::enableHistory();
        }
    }

    /**
     * writes changed data silently, so without chaning last-modified and other stuff than manually changed
     *
     * @param bool - to force insert (default: false)
     * @param bool - to force write (default: false)
     * @param int - priority of the snapshop: autosave 0, save 1, publish 2
     * @param bool - if to force publishing also when not permitted (default: false)
     * @param bool - whether to track in history (default: true)
     * @param bool - whether to write silently, so without chaning anything automatically e.g. last_modified (default: false)
     * @deprecated
     * @return bool
     */
    public function writeSilent($forceInsert = false, $forceWrite = false, $snap_priority = 2, $forcePublish = false, $history = true)
    {
        return $this->write($forceInsert, $forceWrite, $snap_priority, $forcePublish, $history, true);
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
     * @param bool $force
     * @param bool $history
     * @return bool
     * @throws PermissionException
     * @access public
     */
    public function unpublish($force = false, $history = true) {
        if ((!$this->can("Publish")) && !$force)
            throw new PermissionException("Record {$this->id} of type {$this->classname} can't " .
                "be unpublished cause of missing publish permissions.",
                ExceptionManager::PERMISSION_ERROR,
                "publish");

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
                History::push($this->classname, $this->versionid, $this->versionid, $this->id, IModelRepository::COMMAND_TYPE_UNPUBLISH);
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
        $baseTable = $this->baseTable;
        $baseClass = $this->baseClass;
        // check if table in db and if not, create it
        if ($baseTable != "" && !isset(ClassInfo::$database[$baseTable])) {
            if($this->classname != $this->baseClass) {
                gObject::instance($this->baseClass)->buildDB();
            }

            foreach(array_merge(array($this->classname), ClassInfo::getChildren($this->classname)) as $child) {
                gObject::instance($child)->buildDB();
            }

            ClassInfo::write();
        }

        $manipulation = array();

        if (!isset($this->data))
            return true;

        if ($force || $this->can(ModelPermissionManager::PERMISSION_TYPE_DELETE))
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
            if (!isset($manipulation[$baseTable . "_state"]))
                $manipulation[$baseTable . "_state"] = array(
                    "command"		=> "delete",
                    "table_name"	=> $baseTable . "_state",
                    "where"			=> array(

                    ));

            $manipulation[$baseTable . "_state"]["where"]["id"][] = $this->id;

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
                /** @var ModelManyManyRelationShipInfo $relationShip */
                foreach($this->ManyManyRelationships() as $relationShip) {
                    $manipulation[$relationShip->getTableName()] = array(
                        "table_name"=> $relationShip->getTableName(),
                        "command"	=> "delete",
                        "where"		=> array(
                            $relationShip->getOwnerField() => $ids
                        )
                    );
                }

            }
        } else {
            return false;
        }

        $this->clearCache();

        $this->onBeforeRemove($manipulation);
        $this->callExtending("onBeforeRemove", $manipulation);
        if (SQL::manipulate($manipulation)) {
            if (StaticsManager::getStatic($this->classname, "history") && $history) {
                History::push($this->classname, $this->versionid, 0, $this->id, "remove");
            }
            $this->onAfterRemove();
            $this->callExtending("onAfterRemove", $this);
            $this->queryVersion = null;
            unset($this->data);
            return true;
        } else {
            return false;
        }
    }

    //!Current Data-State
    /**
     * returns if this version of the record is published
     *
     * @access public
     * @return bool
     * @throws MySQLException
     * @throws SQLException
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
     * @name isPublished
     * @access public
     * @return bool
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
     * @return bool
     */
    public function isDeleted() {
        return (!$this->isPublished() &&
            (   !isset($this->data["publishedid"]) ||
                $this->data["publishedid"] == 0) &&
                $this->stateid == 0);
    }

    //!Forms

    /**
     * gets the form
     *
     * @param Form $form
     */
    public function getForm(&$form)
    {

    }

    /**
     * geteditform
     *
     * @param Form $form
     */
    public function getEditForm(&$form)
    {
        $this->getForm($form);
    }

    /**
     * gets the form-actions
     *
     * @param Form $form
     * @param bool $edit
     */
    public function getActions(&$form, $edit = false)
    {

    }

    /**
     * returns a list of fields you want to show if we use the history-compare-view
     */
    public function getVersionedFields() {
        return array();
    }

    /**
     * getFormFromDB
     * generates the form-fields from the db-fields
     * @param Form $form
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
     * @param    string|null $name
     * @param    bool $edit edit-form or normal form. this changes if getForm() or getEditForm() get called.
     * @param    bool $disabled
     * @param    Request $request
     * @param    Controller $controller
     * @param    string|array|callback $submission
     * @return   Form
     */
    public function generateForm($name, $edit = false, $disabled = false, $request = null, $controller = null, $submission = null) {
        $form = new Form($controller, $name, array(), array(), array(), $request, $this);

        // default submission
        $form->setSubmission(isset($submission) ? $submission : "submit_form");
        $form->add(new HiddenField("class_name", $this->DataClass()));

        $form->setModel(clone $this);

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
     * gets relationship ids
     *
     * @param string $relationshipName
     * @return array|bool
     */
    public function getRelationIDs($relationshipName) {
        $relationshipName = trim(strtolower($relationshipName));

        if (substr($relationshipName, -3) == "ids") {
            $relationshipName = substr($relationshipName, 0, -3);
        }

        // get all config
        $has_many = $this->hasMany();
        $manyManyRelationships = $this->ManyManyRelationships();

        if (isset($has_many[$relationshipName])) {
            // has-many
            /**
             * getMany returns a DataObjectSet
             * parameters:
             * name of relation
             * where
             * fields
             */
            /** @var HasMany_DataObjectSet $data */
            if ($data = $this->getHasMany($relationshipName)) {
                return $data->fieldToArray("id");
            } else {
                return array();
            }
        } else if (isset($manyManyRelationships[$relationshipName])) {
            /** @var ManyMany_DataObjectSet $set */
            $set = $this->getManyMany($relationshipName);
            return $set->getRelationshipIDs();
        } else {
            return false;
        }
    }

    /**
     * gets relation-data
     *
     * @param string $relationshipName
     * @return array|bool
     */
    public function getRelationData($relationshipName) {
        $relationshipName = trim(strtolower($relationshipName));

        if (substr($relationshipName, -3) == "ids") {
            $relationshipName = substr($relationshipName, 0, -3);
        }

        $relationShips = $this->ManyManyRelationships();

        if (isset($relationShips[$relationshipName])) {
            /** @var ManyMany_DataObjectSet $set */
            $set = $this->getManyMany($relationshipName);
            return $set->getRelationshipData();
        } else {
            return $this->getRelationIDs($relationshipName);
        }
    }

    /**
     * gets information about many-many-relationship or throws exception.
     *
     * @param string $name
     * @param string|null $class given class
     * @return ModelManyManyRelationShipInfo
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
     * gets the editor-user.
     *
     * @return User
     */
    public function editor() {
        if ($this->fieldGet("editorid") != 0) {
            return DataObject::get_one("user",array('id' => $this['editorid']));
        } else {
            return $this->autor();
        }
    }

    /**
     * gets the id
     */
    public function ID() {
        return ($this->isField("recordid")) ? $this->fieldGet("recordid") : $this->fieldGet("id");
    }

    /**
     * gets the versionid
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
     * @param $val
     */
    public function setID($val) {
        $this->setField("id", $val);
        $this->setField("recordid", $val);
    }

    /**
     * returns the representation of this record
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

    /**
     * cache the part, which is the same every DataObject
     * @var SelectQuery[]
     */
    protected static $query_cache = array();

    /**
     * builds the Query
     *
     * @param string|int - version
     * @param array $filter
     * @param array $sort
     * @param array $limit
     * @param array $join
     * @param bool $forceClasses if to include class-filter
     * @return SelectQuery
     */
    public function buildQuery($version, $filter, $sort = array(), $limit = array(), $join = array(), $forceClasses = true)
    {
        if (PROFILE) Profiler::mark("DataObject::buildQuery");

        $baseTable = $this->baseTable;
        $baseClass = $this->baseClass;
        // check if table in db and if not, create it
        if ($baseTable != "" && !isset(ClassInfo::$database[$baseTable])) {

            if($this->classname != $baseClass) {
                gObject::instance($baseClass)->buildDB();
            }

            foreach(array_merge(array($this->classname), ClassInfo::getChildren($this->classname)) as $child) {
                gObject::instance($child)->buildDB();
            }
            ClassInfo::write();
        }

        if (PROFILE) Profiler::mark("DataObject::buildQuery hairy");

        // cache the most hairy part
        if (!isset(self::$query_cache[$baseClass]))
        {
            $query = new SelectQuery($baseTable);

            if ($classes = ClassInfo::dataclasses($baseClass))
            {
                foreach($classes as $class => $table)
                {
                    if ($class != $baseClass && isset(ClassInfo::$database[$table]) && ClassInfo::$database[$table])
                    {
                        $query->leftJoin($table, " ".$table.".id = ".$baseTable.".id");
                    }
                }
            }

            self::$query_cache[$baseClass] = $query;
        }

        /** @var SelectQuery $query */
        $query = clone self::$query_cache[$baseClass];

        if (PROFILE) Profiler::unmark("DataObject::buildQuery hairy");

        if (is_array($filter)) {
            if (isset($filter["versionid"])) {
                $filter["".$baseTable.".id"] = $filter["versionid"];
                unset($filter["versionid"]);

                if($version === null) {
                    $version = false;
                }
            }
        }

        // some specific fields
        $query->db_fields["autorid"] = $baseTable;
        $query->db_fields["editorid"] = $baseTable;
        $query->db_fields["last_modified"] = $baseTable;
        $query->db_fields["class_name"] = $baseTable;
        $query->db_fields["created"] = $baseTable;
        $query->db_fields["versionid"] = array($baseTable, "id");

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

                    $query->db_fields["id"] = array($baseTable, "recordid");
                    // if we just get all, but we group
                } else if ($version == DataObject::VERSION_GROUP) {
                    $query->addFilter($baseTable.'.id IN (
							SELECT max(where_'.$baseTable.'.id) FROM '.DB_PREFIX . $baseTable.' AS where_'.$baseTable.' WHERE where_'.$baseTable.'.recordid = '.$baseTable.'.recordid GROUP BY where_'.$baseTable.'.recordid
						)');

                    // integrate state-table
                    $query->leftJoin($baseTable . "_state", " ".$baseTable."_state.id = ".$baseTable.".recordid");

                    $query->db_fields["id"] = array($baseTable, "recordid");
                }
            } else {
                // if we make no versioning, we just merge state-table-information
                // unmerge deleted records
                $query->leftJoin($baseTable . "_state", " ".$baseTable."_state.id = ".$baseTable.".recordid");
                $query->db_fields["id"] = array($baseTable, "recordid");
            }
        }

        // sort
        if (!empty($sort)) {
            $query->sort($sort);
        } else if($sort !== false) {
            if($sort = StaticsManager::getStatic($this->classname, "default_sort")) {
                $query->sort($sort);
            }
        }

        // limiting
        $query->limit($limit);

        if ($join) {
            foreach ($join as $currentJoin) {
                if (isset($currentJoin[self::JOIN_TYPE], $currentJoin[self::JOIN_TABLE], $currentJoin[self::JOIN_STATEMENT])) {
                    $query->join(
                        $currentJoin[self::JOIN_TYPE],
                        $currentJoin[self::JOIN_TABLE],
                        $currentJoin[self::JOIN_STATEMENT],
                        isset($currentJoin[self::JOIN_ALIAS]) ? $currentJoin[self::JOIN_ALIAS] : "",
                        isset($currentJoin[self::JOIN_INCLUDEDATA]) ? $currentJoin[self::JOIN_INCLUDEDATA] : true
                    );
                } else {
                    throw new InvalidArgumentException("Joins must follow Join-Format. array(type=>,table=>,statement=>[,alias=>][,includeFields=>]), got " . print_r($currentJoin, true));
                }
            }
        }

        // don't forget filtering on class-name
        if ($forceClasses) {
            $class_names = array_merge(array($this->classname), ClassInfo::getChildren($this->classname));

            if(!isset($query->filter["class_name"])) {
                $query->addFilter(array("class_name" => $class_names));
            }
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
     * @param array $searchQuery
     * @param array $filter
     * @param array $sort
     * @param array $limit
     * @param array $join
     * @param bool $version
     * @param bool $forceClasses
     * @return SelectQuery
     */
    public function buildSearchQuery($searchQuery = array(), $filter = array(), $sort = array(), $limit = array(), $join = array(), $version = false, $forceClasses = true) {
        if (PROFILE) Profiler::mark("DataObject::buildSearchQuery");

        $query = $this->buildQuery($version, $filter, $sort, $limit, $join);

        $this->decorateSearchQuery($query, $searchQuery);

        $this->callExtending("argumentQuery", $query, $version, $filter, $sort, $limit, $join, $forceClasses);
        $this->callExtending("argumentSearchSQL", $query, $searchQuery, $version, $filter, $sort, $limit, $join, $forceClasses);

        $this->argumentQuery($query);

        if (PROFILE) Profiler::unmark("DataObject::buildSearchQuery");

        return $query;
    }

    /**
     * decorates a query with search
     *
     * @param SelectQuery $query
     * @param array $searchQuery
     */
    protected function decorateSearchQuery($query, $searchQuery) {
        if ($searchQuery) {
            $filter = array();

            if(!is_array($searchQuery))
                $searchQuery = array($searchQuery);

            foreach($searchQuery as $word) {
                $i = 0;
                $table_name = $this->baseTable();
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

            if ($filter) {
                $query->addFilter(array($filter));
            } else {
                $searchQuery = var_export($searchQuery, true);
                throw new LogicException("Could not search for " . $searchQuery . ". No Search-Fields defined in {$this->baseClass}.");
            }
        }
    }

    /**
     * local argument sql
     * @param SelectQuery $query
     */
    protected function argumentQuery(&$query) {

    }

    /**
     * @param SelectQuery $query
     * @param string $aggregateField
     * @param string $aggregate
     * @param string $version
     */
    protected function extendAggregate(&$query, &$aggregateField, &$aggregate, $version) {

    }

    /**
     * @param string $version
     * @param array $filter
     * @param array $sort
     * @param array $limit
     * @param array $joins
     * @param bool $forceClasses
     * @return SelectQuery
     */
    public function buildExtendedQuery($version, $filter = array(), $sort = array(), $limit = array(), $joins = array(), $forceClasses = true)
    {
        if (PROFILE) Profiler::mark("DataObject::buildExtendedQuery");
        $query = $this->buildQuery($version, $filter, $sort, $limit, $joins, $forceClasses);

        $this->callExtending("argumentQuery", $query, $version, $filter, $sort, $limit, $joins, $forceClasses);

        $this->argumentQuery($query);
        if (PROFILE) Profiler::unmark("DataObject::buildExtendedQuery");
        return $query;
    }

    /**
     * @param string $version
     * @param array $filter
     * @param array $sort
     * @param array $limit
     * @param array $joins
     * @param array $search
     * @return array
     * @throws SQLException
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
            $joinhash = (empty($joins)) ? "" : md5(var_export($joins, true));
            $searchhash = (is_array($search)) ? implode($search) : $search;
            $basehash = "record_" . $limithash . serialize($sort) . $joinhash . $searchhash . md5(var_export($version, true));
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
        }

        $this->callExtending("argumentQueryResult", $arr, $query, $version, $filter, $sort, $limit, $joins, $search);

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
        foreach($query->getFrom() as $table => $data) {
            if (!isset(ClassInfo::$database[$data["table"]])) {
                // try to create the tables
                $this->buildDB();
            }
        }
    }

    /**
     * @param string $version
     * @param string $groupField
     * @param array $filter
     * @param array $sort
     * @param array $limit
     * @param array $joins
     * @param array $search
     * @return array
     * @throws SQLException
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

        $query->groupby($groupField);
        $this->tryToBuild($query);

        $query->execute();

        while($row = $query->fetch_assoc()) {
            if ($id = $this->getGroupIdentifier($groupField, $row)) {
                $set = new Goma\Model\Group\GroupDataObjectSet($this->classname, $filter, $sort, $joins, array(), $version);
                $set->setGroupFilter($this->getGroupFilter($groupField, $row));
                $set->setFirstCache($row);
                $data[$id] = $set;
            }
            unset($row);
        }
        $query->free();

        if (PROFILE) Profiler::unmark("DataObject::getGroupedRecords");

        return $data;
    }

    /**
     * @param array|string $groupField
     * @param array $row
     * @return null|string
     */
    private function getGroupIdentifier($groupField, $row) {
        if(is_string($groupField)) {
            return isset($row[$groupField]) ? $row[$groupField] : null;
        }

        $id = "";
        foreach($groupField as $field) {
            if(isset($row[$field])) {
                $id .= $row[$field];
            }
        }

        return $id ? md5($id) : null;
    }

    private function getGroupFilter($groupField, $row) {
        if(is_string($groupField)) {
            return array($groupField => $row[$groupField]);
        }

        $filter = array();
        foreach($groupField as $field) {
            if(isset($row[$field])) {
                $filter[$field] =  $row[$field];
            }
        }
        return $filter;
    }


    /**
     * gets specific aggegrate.
     *
     * @param string $version
     * @param string $aggregate
     * @param string $aggregateField
     * @param bool $distinct
     * @param array $filter
     * @param array $sort
     * @param array $limit
     * @param array $joins
     * @param array $search
     * @param array $groupby
     * @return array
     * @throws SQLException
     */
    public function getAggregate($version, $aggregate, $aggregateField = "*", $distinct = false, $filter = array(), $sort = array(), $limit = array(), $joins = array(), $search = array(), $groupby = array()) {
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

        $aggregates = (array) $aggregate;

        $this->extendAggregate($query, $aggregateField, $aggregatesm, $version);
        $this->callExtending("extendAggregate", $query, $aggregateField, $aggregates, $version);

        $distinctSQL = $distinct ? "distinct" : "";

        $aggregateSQL = "";
        $i = 0;
        $aggregateField = $query->getFieldIdentifier($aggregateField);
        foreach($aggregates as $singleAggregate) {
            if($i == 0) $i++;
            else $aggregateSQL .= ",";

            $aggregateSQL .= $singleAggregate . "( " . $distinctSQL . " " . $aggregateField . ") as " . strtolower($singleAggregate);
        }

        if ($query->execute($aggregateSQL)) {
            if($row = $query->fetch_assoc()) {
                $data = $row;
                unset($row);
            } else {
                $data = null;
            }
        }

        if (PROFILE) Profiler::unmark("DataObject::getAggregate");

        if(count($data) == 1) {
            $values = array_values($data);
            return $values[0];
        }

        return $data;
    }

    //!Connection to the Controller

    /**
     * sets the controller
     *
     * @param RequestHandler $controller
     */
    public function setController($controller)
    {
        if(!is_a($controller, "RequestHandler")) {
            throw new InvalidArgumentException("Argument must be a RequestHandler.");
        }
        $this->controller = $controller;
    }

    /**
     * gets the controller for this class
     *
     * @param Controller|null $controller
     * @return Controller|null
     * @deprecated
     */
    public function controller($controller = null)
    {
        if (isset($controller)) {
            /** @var Controller $controller */
            $controller = gObject::instance($controller);
            return $this->linkController($controller);
        }

        if (isset($this->controller) && is_object($this->controller))
        {
            return $this->controller;
        }

        /* --- */

        if (isset($this->controller)  && $this->controller != "")
        {
            /** @var Controller $controller */
            $controller = gObject::instance($this->controller);
            return $this->linkController($controller);
        } else {

            if (ClassInfo::exists($this->classname . "controller"))
            {
                /** @var Controller $controller */
                $controller = gObject::instance($this->classname . "controller");
                return $this->linkController($controller);
            } else {

                // find existing controller in parent classes.
                if (ClassInfo::getParentClass($this->classname) != "dataobject") {
                    $parent = $this->classname;
                    while(($parent = ClassInfo::getParentClass($parent)) != "dataobject") {
                        if (!$parent)
                            return null;

                        if (ClassInfo::exists($parent . "controller")) {
                            /** @var Controller $controller */
                            $controller = gObject::instance($parent . "controller");
                            return $this->linkController($controller);
                        }
                    }
                }
            }
        }
        return null;
    }

    /**
     * links given controller to this model.
     *
     * @param Controller $controller
     * @return Controller
     */
    protected function linkController ($controller) {
        $this->controller = clone $controller;
        $this->controller->setModelInst($this);
        return $this->controller;
    }

    //! APIs

    /**
     * resets the DataObject
     */
    public function reset()
    {
        parent::reset();
        $this->data = array();
    }

    /**
     * gets the class as an instance of the given class-name.
     *
     * @param   string $type of object
     * @return  gObject of type $value
     */
    public function getClassAs($type) {
        if (is_subclass_of($type, $this->baseClass)) {
            return new $type(array_merge($this->data, array("class_name" => $type)));
        }

        return $this;
    }

    /**
     * checks if we can sort by a specified field
     *
     * @param string $field
     * @return bool
     */
    public function canSortBy($field) {
        if(strpos($field, ".") !== false) {
            return false;
        }

        $field = strtolower(trim($field));
        $fields = array_merge(array("versionid" => "int(10)"), $this->DataBaseFields(true));
        return isset($fields[$field]);
    }

    /**
     * checks if we can filter by a specified field
     *
     * @param string $field
     * @return bool
     */
    public function canFilterBy($field) {
        $field = strtolower(trim($field));
        if (strpos($field, ".") !== false) {
            $has_one = $this->HasOne();

            $key = strtolower(substr($field, 0, strpos($field, ".")));
            if (isset($has_one[$key])) {
                return gObject::instance($has_one[$key]->getTargetClass())->canFilterBy(substr($field, strpos($field, ".") + 1));
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
     * @return $this
     */
    public function duplicate() {
        $this->consolidate();
        /** @var DataObject $data */
        $data = parent::duplicate();

        $data->id = 0;
        $data->versionid = 0;

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

    //!API for Config

    /**
     * returns DataBase-Fields of this record
     * @param bool $recursive
     * @return array
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
     * @return array
     */
    public function indexes() {
        return isset(ClassInfo::$class_info[$this->classname]["index"]) ? ClassInfo::$class_info[$this->classname]["index"] : array();
    }

    /**
     * returns the search-fields
     *
     * @return array
     */
    public function searchFields() {
        return isset(ClassInfo::$class_info[$this->classname]["search"]) ? ClassInfo::$class_info[$this->classname]["search"] : array();
    }

    /**
     * table
     *
     * @name Table
     * @return bool
     */
    public function Table() {
        return isset(ClassInfo::$class_info[$this->classname]["table"]) ? ClassInfo::$class_info[$this->classname]["table"] : false;
    }

    /**
     * table
     *
     * @return bool
     */
    public function hasTable() {
        return ((isset(ClassInfo::$class_info[$this->classname]["table_exists"]) ? ClassInfo::$class_info[$this->classname]["table_exists"] : false) && $this->Table());
    }

    /**
     * returns casting-values
     */
    public function casting() {
        $casting = parent::casting();

        return array_merge($this->DataBaseFields(true), $casting);
    }

    /**
     * returns array of ModelManyManyRelationShipInfo Objects
     *
     * @return ModelManyManyRelationShipInfo[]
     */
    public function ManyManyRelationships() {
        return DataObjectClassInfo::getManyManyRelationships($this->classname);
    }


    /**
     * returns if a DataObject is versioned
     *
     * @return bool
     */
    public static function Versioned($class) {
        if (StaticsManager::hasStatic($class, "versions") && StaticsManager::getStatic($class, "versions") == true)
            return true;

        $inst = gObject::instance($class);
        if (property_exists($inst, "versioned"))
            if($inst->versioned === true)
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
     */
    public function BaseTable() {
        return isset(ClassInfo::$class_info[$this->BaseClass()]["table"]) ? ClassInfo::$class_info[$this->BaseClass()]["table"] : null;
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

            $defaults = $this->defaults;
            // get correct SQL-Types for Goma-Field-Types
            foreach($db_fields as $field => $type) {
                if (isset($casting[strtolower($field)])) {
                    if ($casting[strtolower($field)] = DBField::parseCasting($casting[strtolower($field)])) {
                        $defaults[$field] = call_user_func_array(array($casting[strtolower($field)]["class"], "getSQLDefault"), array(
                            $field,
                            isset($defaults[$field]) ? $defaults[$field] : null,
                            (isset($casting[strtolower($field)]["args"])) ? $casting[strtolower($field)]["args"] : array()
                        ));
                    }
                }
            }

            ClassInfo::$database[$this->table()] = $db_fields;

            // now require table
            $log .= SQL::requireTable($this->table(), $db_fields, $indexes , $defaults, $prefix);
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

        $this->callExtending("buildDB", $prefix, $log);

        $this->preserveDefaults($prefix, $log);
        $this->cleanUpDB($prefix, $log);

        $this->callExtending("afterBuildDB", $prefix, $log);

        $output = '<div style="padding-top: 6px;"><img src="system/images/success.png" height="16" alt="Success" /> Checking Database of '.$this->classname."</div><div style=\"padding-left: 21px;width: 550px;\">";
        $output .= str_replace("\n", "<br />",$log);
        $output .= "</div>";
        return $output;
    }

    /**
     * generates some ClassInfo
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

        $db_fields = $this->DataBaseFields();
        $casting = $this->casting();
        // decorate class-info with type-specific info
        foreach($db_fields as $field => $type) {
            if(!in_array($field, array("id", "versionid", "created", "autorid", "class_name", "last_modified"))) {
                if (isset($casting[strtolower($field)])) {
                    if ($casting[strtolower($field)] = DBField::parseCasting($casting[strtolower($field)])) {
                        call_user_func_array(
                            array($casting[strtolower($field)]["class"], "argumentClassInfo"),
                            array(
                                $this,
                                $field,
                                (isset($casting[strtolower($field)]["args"])) ? $casting[strtolower($field)]["args"] : array(),
                                $type
                            )
                        );
                    }
                }
            }
        }

        $this->callExtending("generateClassInfo");
    }

    /**
     * preserve Defaults
     *
     * @return bool
     */
    public function preserveDefaults($prefix = DB_PREFIX, &$log) {
        $this->callExtending("preserveDefaults", $prefix);

        if ($this->hasTable()) {
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

        if ($this->Table() && $this->Table() == $this->baseTable) {
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

    private static $tableHasBeenCleanedUp = array();

    /**
     * clean up DB
     */
    public function cleanUpDB($prefix = DB_PREFIX, &$log = null) {
        $this->callExtending("cleanUpDB", $prefix);

        // clean up many-many-tables
        /** @var ModelManyManyRelationShipInfo $relationShip */
        foreach($this->ManyManyRelationships() as $relationShip) {
            if(!isset(self::$tableHasBeenCleanedUp[$relationShip->getTableName()])) {
                $sql = "DELETE FROM ". DB_PREFIX . $relationShip->getTableName() ." WHERE ". $relationShip->getOwnerField() ." = 0 OR ". $relationShip->getTargetField() ." = 0";
                if (SQL::Query($sql)) {
                    if (SQL::affected_rows() > 0)
                        $log .= 'Clean-Up Many-Many-Table '. $relationShip->getTableName()  . "\n";
                } else {
                    $log .= 'Failed to clean-up Many-Many-Table '. $relationShip->getTableName() . "\n";
                }

                if(isset(ClassInfo::$class_info[$relationShip->getTargetClass()]["baseclass"])) {
                    $extBaseTable = ModelInfoGenerator::ClassTable(ClassInfo::$class_info[$relationShip->getTargetClass()]["baseclass"]);
                    $sql = "DELETE FROM ". DB_PREFIX . $relationShip->getTableName() ." WHERE ". $relationShip->getOwnerField() ." NOT IN (SELECT id FROM ".DB_PREFIX . $this->baseTable.") OR ". $relationShip->getTargetField() ." NOT IN (SELECT id FROM ".DB_PREFIX . $extBaseTable.")";
                    sql::query($sql);
                }

                self::$tableHasBeenCleanedUp[$relationShip->getTableName()] = true;
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
                'class_name' 	=> 'enum("'.implode('","', array_map(function($class) {
                        return str_replace("\\", "\\\\", $class);
                    }, array_merge(Classinfo::getChildren($class), array($class)))).'")',
                "created"		=> "DateTime()"
            );
        } else {
            return array(
                'id'			=> 'INT(10) AUTO_INCREMENT  PRIMARY KEY'
            );
        }
    }

    public function getInExpansion() {
        return $this->inExpansion;
    }

    /**
     * @return void
     */
    public function clearCache() {
        DataObjectQuery::clearCache($this->baseClass);
    }


    /**
     * @param array $manipulation
     * @return bool
     */
    public function manipulate($manipulation) {
        return SQL::manipulate($manipulation);
    }
}

class DBFieldNotValidException extends Exception {
    public function __construct($field) {
        parent::__construct("DB-Field-Name is not valid: " . $field);
    }
}
