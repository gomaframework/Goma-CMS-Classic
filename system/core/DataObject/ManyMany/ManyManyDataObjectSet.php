<?php defined("IN_GOMA") OR die();

/**
 * for many-many-relation
 *
 * @package     Goma\Model
 *
 * @license     GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author      Goma-Team
 *
 * @version     1.2
 */
class ManyMany_DataObjectSet extends RemoveStagingDataObjectSet implements ISortableDataObjectSet {

    const MANIPULATION_DELETE_SPECIFIC = "many_many_deleterecords";
    const MANIPULATION_DELETE_EXISTING = "many_many_deleteexisting";
    const MANIPULATION_INSERT_NEW = "many_many_insertnew";

    /**
     * value of $ownField
     *
     * @var DataObject
     */
    protected $ownRecord;

    /**
     * relationship for this DataSet.
     *
     * @var ModelManyManyRelationShipInfo
     */
    protected $relationShip;

    /**
     * current active data-set.
     * used to give possibility to override table.
     */
    protected $manyManyData;

    /**
     * indicates which version of data-source should be used.
     *
     * @var string
     */
    protected $dataSourceVersion;

    /**
     * update extra fields stage.
     */
    protected $updateFieldsStage;

    /**
     * ManyMany_DataObjectSet constructor.
     * @param array|IDataObjectSetDataSource|IDataObjectSetModelSource|null|string $class
     * @param array|null|string $filter
     * @param array|null|string $sort
     * @param array|null $join
     * @param array|null|string $search
     * @param null|string $version
     */
    public function __construct($class = null, $filter = null, $sort = null, $join = null, $search = null, $version = null)
    {
        parent::__construct($class, $filter, $sort, $join, $search, $version);

        $this->updateFieldsStage = new ArrayList();
    }

    /**
     * sets the relation-props
     *
     * @param ModelManyManyRelationShipInfo $relationShip
     * @param DataObject $ownRecord
     * @param string|null $dataSourceVersion
     */
    public function setRelationENV($relationShip, $ownRecord, $dataSourceVersion = null) {
        if(!is_a($relationShip, "ModelManyManyRelationShipInfo")) {
            throw new InvalidArgumentException("Relationship-Info must be type of ModelManyManyRelationShipInfo");
        }

        $this->relationShip = $relationShip;
        $this->ownRecord = $ownRecord;
        $this->dataSourceVersion = isset($dataSourceVersion) ? $dataSourceVersion : $relationShip->getSourceVersion();
    }

    /**
     * sets source data.
     * @param array $data
     */
    public function setSourceData($data) {
        if(!is_array($data) && !is_null($data))
            throw new InvalidArgumentException("Source-Data of ManyManySet must be type of array, but was " . gettype($data) . ".");

        $this->manyManyData = array();
        $existingData = $this->getRelationshipDataFromDB();
        $i = 0;
        foreach((array) $data as $possibleId => $recordData) {
            if(is_array($recordData)) {
                if(!$possibleId) {
                    throw new InvalidArgumentException("Each source-data-dictionary needs a valid id, which is defined by DB.");
                }

                $this->manyManyData[$possibleId] = array_merge(
                    isset($existingData[$possibleId]) ? $existingData[$possibleId] : array(),
                    $recordData
                );
                $this->manyManyData[$possibleId][$this->relationShip->getOwnerSortField()] = $i;
            } else {
                if(!$recordData) {
                    throw new InvalidArgumentException("Each source-data-dictionary needs a valid id, which is defined by DB.");
                }

                $this->manyManyData[$recordData] = isset($existingData[$recordData]) ? $existingData[$recordData] : array();
                $this->manyManyData[$recordData][$this->relationShip->getOwnerSortField()] = $i;
            }
            $i++;
        }

        $this->fetchMode = self::FETCH_MODE_EDIT;

        $this->clearCache();
    }

    /**
     * set source database.
     */
    public function setSourceDB() {
        $this->manyManyData = null;
        $this->fetchMode = self::FETCH_MODE_EDIT;

        $this->clearCache();
    }

    /**
     * @param string $mode
     */
    public function setVersionMode($mode) {
        if($mode === null || $mode == DataObject::VERSION_MODE_CURRENT_VERSION || $mode == DataObject::VERSION_MODE_LATEST_VERSION) {
            $this->dataSourceVersion = $mode;

            $this->clearCache();
        } else {
            throw new InvalidArgumentException("Invalid version mode.");
        }
    }

    /**
     * @return DataObject
     */
    public function getOwnRecord()
    {
        return $this->ownRecord;
    }

    /**
     * attention this is not used to give you access to current dataset, but source of this set.
     * this can be null.
     * @return null|array
     */
    public function getManyManySourceData()
    {
        return $this->manyManyData;
    }

    /**
     * @return string
     */
    public function getDataSourceVersion()
    {
        return $this->dataSourceVersion;
    }

    /**
     * get the relation-props
     *
     * @return ModelManyManyRelationShipInfo
     */
    public function getRelationShip() {
        return $this->relationShip;
    }

    /**
     * returns value of field for this relationship.
     *
     * @return int
     */
    public function getRelationOwnValue() {
        return $this->ownRecord->versionid;
    }

    /**
     * @return mixed
     */
    public function getUpdateFieldsStage()
    {
        return $this->updateFieldsStage;
    }

    /**
     * returns current relationship ids.
     */
    public function getRelationshipIDs() {
        if(isset($this->manyManyData)) {
            return array_keys($this->manyManyData);
        }

        if($this->getQueryVersionID()) {
            $query = $this->getManyManyQuery(array($this->relationShip->getTargetField()));
            $query->execute();

            $ids = array();
            while ($row = $query->fetch_assoc()) {
                $ids[] = $row[$this->relationShip->getTargetField()];
            }

            /** @var DataObject $record */
            foreach ($this->staging as $record) {
                $ids[] = $record->versionid;
            }

            return $ids;
        }

        return array();
    }

    /**
     * @param null $oldId
     * @return array
     * @throws SQLException
     */
    protected function getRelationshipDataFromDB($oldId = null) {
        if(isset($this->manyManyData)) {
            return $this->manyManyData;
        }

        if($this->getQueryVersionID($oldId)) {
            $query = $this->getManyManyQuery(array("*", "recordid"), $oldId);
            $query->execute();

            $arr = array();
            while ($row = $query->fetch_assoc()) {
                $id = $row[$this->relationShip->getTargetField()];
                $arr[$id] = array(
                    "versionid"                          => $id,
                    "relationShipId"                     => $row["relationid"],
                    $this->relationShip->getOwnerField() => $row[$this->relationShip->getOwnerField()]
                );

                $arr[$id][$this->relationShip->getOwnerSortField()] = $row[$this->relationShip->getOwnerSortField()];
                $arr[$id][$this->relationShip->getTargetSortField()] = $row[$this->relationShip->getTargetSortField()];

                if ($updateObject = $this->updateFieldsStage->find("id", $row["recordid"])) {
                    $updateRecord = $updateObject->toArray();
                }

                foreach ($this->relationShip->getExtraFields() as $field => $pattern) {
                    if (isset($updateRecord)) {
                        $arr[$id][$field] = isset($updateRecord[$field]) ? $updateRecord[$field] : $row[$field];
                    } else {
                        $arr[$id][$field] = $row[$field];
                    }
                }
            }

            return $arr;
        }

        return array();
    }

    /**
     * @param null $oldId
     * @return int|null
     */
    protected function getQueryVersionID($oldId = null) {
        return $this->dataSourceVersion != DataObject::VERSION_MODE_CURRENT_VERSION ?
            ($this->queryVersion() == DataObject::VERSION_STATE ? $this->ownRecord->stateid : $this->ownRecord->publishedid) :
            ($oldId != null ? $oldId : $this->ownRecord->versionid);
    }

    /**
     * @return string
     */
    protected function getQueryVersionField() {
        return  $this->dataSourceVersion != DataObject::VERSION_MODE_CURRENT_VERSION ?
            ($this->queryVersion() == DataObject::VERSION_STATE ? "stateid" : "publishedid") :
            "id";
    }

    /**
     * returns current relationship data.
     */
    public function getRelationshipData() {
        $arr = $this->getRelationshipDataFromDB();

        /** @var DataObject $record */
        foreach($this->staging as $record) {
            $id = $record->versionid;
            $arr[$id] = array(
                "versionid"                             => $id,
                "relationShipId"                        => 0,
                $this->relationShip->getOwnerField()    => $this->ownRecord->versionid
            );

            $arr[$id][$this->relationShip->getOwnerSortField()] = count($arr);
            $arr[$id][$this->relationShip->getTargetSortField()] = count($arr);

            foreach ($this->relationShip->getExtraFields() as $field => $pattern) {
                $arr[$id][$field] = $record->{$field};
            }
        }

        return $arr;
    }

    /**
     * @param array $fields
     * @param null $oldId
     * @return SelectQuery
     * @throws SQLException
     */
    protected function getManyManyQuery($fields, $oldId = null) {
        if(!$this->relationShip->getTargetBaseTableName()) {
            throw new LogicException("Target-Relationship needs at least basetable.");
        }

        $baseTable = $this->relationShip->getTargetBaseTableName();

        $recordIdQuerySQL = $this->getRecordIdQuery($oldId)->build("distinct recordid");

        $query = new SelectQuery($baseTable, $fields, $baseTable . ".recordid IN (".$recordIdQuerySQL.")");
        $query->db_fields["relationid"] = array($this->relationShip->getTableName(), "id");

        // filter for not existing records
        $query->leftJoin(
            $this->relationShip->getTableName(),
            $baseTable . '.id = '. $this->relationShip->getTableName() .'.' . $this->relationShip->getTargetField() .
            ' AND ' . $this->relationShip->getTableName().'.' . $this->relationShip->getOwnerField() . ' = \'' . $this->getQueryVersionID($oldId) . '\''
        );
        $query->innerJoin(
            $baseTable . "_state",
            "{$baseTable}_state.{$this->getQueryVersionField()} = {$baseTable}.id",
            "",
            false
        );

        $query->sort($this->getManyManySort());

        return $query;
    }

    /**
     * returns recorid-query.
     * @param null $oldId
     * @return SelectQuery
     */
    protected function getRecordIdQuery($oldId = null) {
        if(!$this->relationShip->getTargetBaseTableName()) {
            throw new LogicException("Target-Relationship needs at least basetable.");
        }

        $recordIdQuery = new SelectQuery($this->relationShip->getTargetBaseTableName(), array());
        $recordIdQuery->innerJoin($this->relationShip->getTableName(), " {$this->relationShip->getTableName()}.{$this->relationShip->getTargetField()} = " .
            "{$this->relationShip->getTargetBaseTableName()}.id AND {$this->relationShip->getTableName()}.{$this->relationShip->getOwnerField()} = '{$this->getQueryVersionID($oldId)}'");

        if (ClassManifest::isSameClass($this->relationShip->getTargetClass(), $this->ownRecord->DataClass()) ||
            is_subclass_of($this->relationShip->getTargetClass(), $this->ownRecord->DataClass()) ||
            is_subclass_of($this->ownRecord->DataClass(), $this->relationShip->getTargetClass())
        ) {
            $recordIdQuery->addFilter("{$this->relationShip->getTargetBaseTableName()}.recordid != '".$this->ownRecord->id."'");
        }

        if($excludedRecords = array_merge($this->staging->fieldToArray("id"), $this->removeStaging->fieldToArray("id"))) {
            $recordIdQuery->addFilter(" {$this->relationShip->getTargetBaseTableName()}.recordid NOT IN ('" . implode("','", $excludedRecords) . "') ");
        }

        return $recordIdQuery;
    }

    /**
     * returns many-many-sort.
     * @param array|null $sort
     * @return string
     */
    protected function getManyManySort($sort = null) {
        if(!isset($sort) || !$sort) {
            $name = $this->relationShip->getRelationShipName();
            $sorts = ArrayLib::map_key("strtolower", StaticsManager::getStatic($this->getOwnRecord()->DataClass(), "many_many_sort"));
            if(isset($sorts[$name]) && $sorts[$name]) {
                return call_user_func_array(array(static::class, "parseSort"), array($sorts[$name]));
            } else {
                return array(
                    $this->relationShip->getTableName() . ".".$this->relationShip->getOwnerSortField() => "ASC",
                    $this->relationShip->getTableName() . ".id " => "ASC"
                );
            }
        }

        return $sort;
    }

    /**
     * converts the item to the right format
     *
     * @param DataObject $item
     * @return DataObject
     */
    public function getConverted($item) {
        /** @var DataObject $item */
        $item = parent::getConverted($item);

        if($item) {
            if (isset($this->relationShip)) {
                $item->extendedCasting = array_merge($item->extendedCasting, $this->relationShip->getExtraFields());
            }

            if (isset($this->manyManyData) && isset($this->manyManyData[$item->versionid])) {
                foreach ($this->manyManyData[$item->versionid] as $key => $data) {
                    $item->setField($key, $data);
                }
            }
        }

        return $item;
    }

    /**
     * updates extra fields for record.
     *
     * @param DataObject $record
     * @param bool $onlyChangedMany
     */
    public function updateFields($record, $onlyChangedMany = false) {
        if($record->hasChanged() && $onlyChangedMany) {
            $record->__onlymanyChanged = true;
        }

        if($toRemove = $this->updateFieldsStage->find("versionid", $record->versionid)) {
            $this->updateFieldsStage->remove($toRemove);
        }

        $this->updateFieldsStage->add($record);
    }

    /**
     * removes record from update extra fields stage.
     * @param DataObject $record
     */
    public function removeFromUpdateExtraFields($record) {
        if($toRemove = $this->updateFieldsStage->find("versionid", $record->versionid)) {
            $this->updateFieldsStage->remove($toRemove);
        }
    }

    /**
     * @param bool $forceInsert
     * @param bool $forceWrite
     * @param int $snap_priority
     * @param IModelRepository $repository
     * @param array $options
     * @param array $exceptions
     * @param array $errorRecords
     * @throws MySQLException
     * @throws PermissionException
     */
    protected function writeCommit($forceInsert, $forceWrite, $snap_priority, $repository, $options, &$exceptions, &$errorRecords)
    {
        if(!$forceWrite && !$this->ownRecord->can("Write")) {
            throw new PermissionException();
        }

        if($this->ownRecord->id == 0) {
            throw new LogicException("Many-Many-Relationship can only be written when record has been written. " .
                "Call writeToDB on record, this will also trigger writing Relationship.");
        }

        $updated = array();
        $copyOfAddStage = $this->prepareStageForWriting($updated);

        parent::writeCommit($forceInsert, $forceWrite, $snap_priority, $repository, $options, $exceptions, $errorRecords);

        $manipulation = array();
        $sort = 0;
        $addedRecords = array();

        if($this->fetchMode == self::FETCH_MODE_CREATE_NEW) {
            $manipulation[self::MANIPULATION_DELETE_EXISTING] = array(
                "command"		=> "delete",
                "table_name"	=> $this->relationShip->getTableName(),
                "where"			=> array(
                    $this->relationShip->getOwnerField() => $this->ownRecord->versionid
                )
            );
        } else {
            if($this->ownRecord->versionid != $this->ownRecord->publishedid || isset($options["oldid"]) || $this->manyManyData || $this->updateFieldsStage->count() > 0) {
                $relationData = $this->getRelationshipDataFromDB(isset($options["oldid"]) ? $options["oldid"] : null);

                $manipulation[self::MANIPULATION_DELETE_EXISTING] = array(
                    "command"		=> "delete",
                    "table_name"	=> $this->relationShip->getTableName(),
                    "where"			=> array(
                        $this->relationShip->getOwnerField() => $this->ownRecord->versionid
                    )
                );

                if(!empty($relationData)) {
                    $manipulation[self::MANIPULATION_INSERT_NEW] = array(
                        "command"       => "insert",
                        "table_name"	=> $this->relationShip->getTableName(),
                        "fields"        => array()
                    );

                    if($this->ownRecord->versionid == 0) {
                        throw new LogicException("Ownrecord must be written in order to write ManyMany-Relationship.");
                    }

                    foreach ($relationData as $id => $record) {
                        $updatedRecord = isset($updated[$id]) ? $updated[$id] : null;
                        if($this->relationShip->isBidirectional()) {
                            if(!isset($manipulation[self::MANIPULATION_INSERT_NEW]["fields"][$id . "_" . $this->ownRecord->versionid])) {
                                $manipulation[self::MANIPULATION_INSERT_NEW]["fields"][$id . "_" . $this->ownRecord->versionid] =
                                    $this->getBiDirRecordFromRelationData($id, $sort, $record, $updatedRecord);
                            }
                        }

                        $manipulation[self::MANIPULATION_INSERT_NEW]["fields"][$this->ownRecord->versionid . "_" . $id] =
                            $this->getRecordFromRelationData($id, $sort, $record, $updatedRecord);

                        $addedRecords[$id] = false;
                        $sort++;
                    }
                }
            }
        }

        // remove bidirectional when is bidirectional, which means you reference basically objects of same type
        if(isset($manipulation[self::MANIPULATION_DELETE_EXISTING])) {
            if($this->relationShip->isBidirectional()) {
                $manipulation[self::MANIPULATION_DELETE_EXISTING]["where"][] = "OR";
                $manipulation[self::MANIPULATION_DELETE_EXISTING]["where"][$this->relationShip->getTargetField()] = $this->ownRecord->versionid;
            }
        }

        /** @var DataObject $record */
        foreach($copyOfAddStage as $record) {
            if(is_array($record)) {
                $record = $this->getConverted($record);
            }

            if(!isset($manipulation[self::MANIPULATION_INSERT_NEW])) {
                $manipulation[self::MANIPULATION_INSERT_NEW] = array(
                    "command"       => "insert",
                    "ignore"        => true,
                    "table_name"	=> $this->relationShip->getTableName(),
                    "fields"        => array()
                );
            }

            if($this->relationShip->isBidirectional()) {
                if(!isset($manipulation[self::MANIPULATION_INSERT_NEW]["fields"][$record->versionid . "_" . $this->ownRecord->versionid])) {
                    $manipulation[self::MANIPULATION_INSERT_NEW]["fields"][$record->versionid . "_" . $this->ownRecord->versionid] =
                        $this->getBiDirRecordFromRelationData($record->versionid, $sort, $record->ToArray());
                }
            }

            $manipulation[self::MANIPULATION_INSERT_NEW]["fields"][$this->ownRecord->versionid . "_" . $record->versionid] =
                $this->getRecordFromRelationData($record->versionid, $sort, $record->ToArray());

            $addedRecords[$record->versionid] = true;
            $sort++;
        }

        $this->updateLastModifiedOnAddedRecords($addedRecords);

        $this->dbDataSource()->clearCache();
        $this->ownRecord->onBeforeManipulateManyMany($manipulation, $this, $addedRecords);
        $this->ownRecord->callExtending("onBeforeManipulateManyMany", $manipulation, $this, $addedRecords);
        if(!$this->dbDataSource()->manipulate($manipulation)) {
            $exceptions[] = new LogicException(
                "Could not manipulate Database. Manipulation corrupted. <pre>".print_r($manipulation, true)."</pre>"
            );
        }

        if(!isset($options["callRemove"]) || $options["callRemove"] === true) {
            $this->ownRecord->onAfterWriteManyMany($this);
        }
    }

    /**
     * @return bool
     */
    public function hasChanged()
    {
        return parent::hasChanged() || $this->updateFieldsStage->count() > 0;
    }

    /**
     * @param array $addedRecords
     * @throws MySQLException
     */
    protected function updateLastModifiedOnAddedRecords($addedRecords) {
        // update not written records to indicate changes
        $baseClassTarget = ClassInfo::$class_info[$this->relationShip->getTargetClass()]["baseclass"];
        if($ids = array_keys(
            array_filter($addedRecords,
                function($item){
                    return !$item;
                }
            )
        )) {
            DataObject::update($baseClassTarget, array("last_modified" => NOW),
                array(
                    "id" => $ids
                )
            );
        }
    }

    /**
     * gets record from relationdata.
     * @param int $id
     * @param int $sort
     * @param array $record
     * @param null|DataObject $updatedRecord
     * @return array
     */
    protected function getRecordFromRelationData($id, $sort, $record, $updatedRecord = null) {
        $record = $this->mergeRecordWithUpdate($record, $updatedRecord);

        $newRecord = array(
            $this->relationShip->getOwnerField()        => $this->ownRecord->versionid,
            $this->relationShip->getTargetField()       => isset($updatedRecord) ? $updatedRecord->versionid : $id,
            $this->relationShip->getTargetSortField()   => isset($record[$this->relationShip->getTargetSortField()]) ?
                $record[$this->relationShip->getTargetSortField()] : 0,
            $this->relationShip->getOwnerSortField()    => $sort
        );

        /** @var DataObject $changedRecord */
        if(($changedRecord = $this->updateFieldsStage->find("versionid", $id)) && $changedRecord->hasChanged()) {
            foreach($this->relationShip->getExtraFields() as $field => $type) {
                $newRecord[$field] = isset($changedRecord->{$field}) ? $changedRecord->{$field} : null;
            }
        } else {
            foreach($this->relationShip->getExtraFields() as $field => $type) {
                $newRecord[$field] = isset($record[$field]) ? $record[$field] : null;
            }
        }

        return $newRecord;
    }

    /**
     * gets record from relationdata.
     * @param int $id
     * @param int $sort
     * @param array $record
     * @param DataObject $updatedRecord
     * @return array
     */
    protected function getBiDirRecordFromRelationData($id, $sort, $record, $updatedRecord = null) {
        $record = $this->mergeRecordWithUpdate($record, $updatedRecord);

        $ownerSort = isset($record[$this->relationShip->getTargetSortField()]) ?
            $record[$this->relationShip->getTargetSortField()] : 0;
        $record[$this->relationShip->getTargetSortField()] = $sort;

        $newRecord = $this->getRecordFromRelationData($id, $ownerSort, $record, $updatedRecord);
        $newRecord[$this->relationShip->getTargetField()] = $this->ownRecord->versionid;
        $newRecord[$this->relationShip->getOwnerField()] = isset($updatedRecord) ? $updatedRecord->versionid : $id;
        return $newRecord;
    }

    /**
     * @param array $record
     * @param DataObject $updated
     * @return array
     */
    private function mergeRecordWithUpdate($record, $updated) {
        if(isset($updated)) {
            foreach($record as $k => $v) {
                if($updated->isField($k)) {
                    $record[$k] = $updated->fieldGet($k);
                }
            }
        }

        return $record;
    }

    /**
     * @return array
     */
    public function getSortForQuery()
    {
        $sort = parent::getSortForQuery();
        if(isset($this->manyManyData)) {
            if ($sort) {
                return array_merge((array)$sort, array("versionid" => array_keys($this->manyManyData)));
            } else {
                return array(array("versionid" => array_keys($this->manyManyData)));
            }
        } else {
            if(is_array($sort)) {
                $sort = array_merge($sort, $this->getManyManySort());
            } else {
                $sort = $this->getManyManySort($sort);
            }
        }

        return $sort;
    }

    /**
     *
     */
    public function getFilterForQuery()
    {
        $filter = (array) parent::getFilterForQuery();

        $baseTable = $this->relationShip->getTargetBaseTableName();
        if(isset($this->manyManyData)) {
            $recordidQuery = new SelectQuery($baseTable, "", array(
                "id" => array_keys($this->manyManyData)
            ));
            $filter[] = $baseTable . ".recordid IN (".$recordidQuery->build("distinct recordid").") ";
        } else {
            $filter[] = " {$baseTable}.recordid IN (".$this->getRecordIdQuery()->build("distinct recordid").") ";
        }

        return $filter;
    }

    /**
     * joins stuff.
     * @return array
     */
    public function getJoinForQuery()
    {
        $join = parent::getJoinForQuery();

        $relationTable = $this->relationShip->getTableName();
        // search second join
        foreach ((array)$join as $table => $data) {
            if (strpos($data, (string) $relationTable)) {
                unset($join[$table]);
            }
        }

        if($this->getQueryVersionID()) {
            $join[$relationTable] = array(
                DataObject::JOIN_TYPE      => $this->manyManyData ? "LEFT" : "INNER",
                DataObject::JOIN_TABLE     => $relationTable,
                DataObject::JOIN_STATEMENT => $relationTable . "." . $this->relationShip->getTargetField() . " = " . $this->dbDataSource()->baseTable() . ".id AND " .
                    $relationTable . "." . $this->relationShip->getOwnerField() . " = '" . $this->getQueryVersionID() . "'"
            );
        } else {
            $join[$relationTable] = array(
                DataObject::JOIN_TYPE      => $this->manyManyData ? "LEFT" : "INNER",
                DataObject::JOIN_TABLE     => $relationTable,
                DataObject::JOIN_STATEMENT => "0 = 1"
            );
        }

        return $join;
    }

    /**
     * @param null|IModelRepository $repository
     * @param bool $forceWrite
     * @param int $snap_priority
     * @param IModelRepository $repository
     * @return mixed
     * @throws SQLException
     */
    public function commitRemoveStaging($repository, $forceWrite = false, $snap_priority = 2)
    {
        if($this->removeStaging->count() > 0) {
            $versionQuery = new SelectQuery(
                $this->relationShip->getTargetBaseTableName(), array("id"), array(
                    "recordid" => $this->removeStaging->fieldToArray("recordid")
                )
            );

            $manipulation[self::MANIPULATION_DELETE_SPECIFIC] = array(
                "command" => "delete",
                "table_name" => $this->relationShip->getTableName(),
                "where" => " {$this->relationShip->getTargetField()} IN (".$versionQuery->build().") "
            );

            if ($this->relationShip->isBidirectional()) {
                $manipulation[self::MANIPULATION_DELETE_SPECIFIC]["where"] .= " OR {$this->relationShip->getOwnerField()} IN (".$versionQuery->build().") ";
            }

            $insertedRelationships = array();
            $this->ownRecord->onBeforeManipulateManyMany($manipulation, $this, $insertedRelationships);
            if (!$this->dbDataSource()->manipulate($manipulation)) {
                throw new LogicException(
                    "Could not manipulate Database. Manipulation corrupted. <pre>".print_r($manipulation, true)."</pre>"
                );
            }
        }

        $this->dbDataSource()->clearCache();
    }

    /**
     * @param array|string $filter
     * @return array
     */
    protected function argumentFilterForHidingRemovedStageForQuery($filter)
    {
        return $filter;
    }

    /**
     * checks if we can sort by a specified field
     *
     * @param string $field
     * @return bool
     */
    public function canSortBy($field) {
        $extra = $this->relationShip ? $this->relationShip->getExtraFields() : array();
        return isset($extra[strtolower(trim($field))]) || parent::canSortBy($field);
    }

    /**
     * moves item to given position.
     *
     * @param DataObject $item
     * @param int $position
     * @return $this
     */
    public function move($item, $position)
    {
        $this->setModifyAllMode();
        $this->staging->move($item, $position);
        $this->items =& $this->staging->ToArray();
        return $this;
    }

    /**
     * sets sort by array of ids.
     * Should only be used if all items has already been written and has ids.
     *
     * @param int []
     * @return $this
     */
    public function setSortByIdArray($ids)
    {
        if(!is_array($ids)) {
            throw new InvalidArgumentException();
        }

        if($this->fetchMode == self::FETCH_MODE_EDIT) {
            $info = array();
            $data = $this->getRelationshipData();
            foreach ($ids as $id) {
                if ($record = $this->find("id", $id)) {
                    $info[$record->versionid] = isset($data[$record->versionid]) ? $data[$record->versionid] : array();
                    unset($data[$record->versionid]);
                }
            }

            foreach ($data as $id => $record) {
                if (isset($record)) {
                    $info[$id] = $record;
                }
            }

            $this->setSourceData($info);

            return $this;
        } else {
            $this->staging = $this->staging->sortByFieldArray($ids);
            return $this;
        }
    }

    /**
     * uasort.
     *
     * @param Callable
     * @return $this
     */
    public function sortCallback($callback)
    {
        $this->setModifyAllMode();
        $items = $this->staging->ToArray();

        uasort($items, $callback);
        $this->staging = new ArrayList($items);
        $this->items =& $this->staging->ToArray();

        return $this;
    }

    /**
     * prepares staging for unique relationships.
     * it returns a list of objects, which have been added to the stage.
     * Objects, which are only updated, are not returned.
     *
     * @param array $updated
     * @return array
     */
    protected function prepareStageForWriting(&$updated) {
        if($this->getRelationShip()->getCascade() == DataObject::CASCADE_TYPE_UNIQUE) {
            $oldData = array_merge($this->staging->ToArray(), $this->updateFieldsStage->ToArray());
            $dataToWrite = $dataForRelationship = array();
            foreach($oldData as $currentRecord) {
                $targetClass = $this->getRelationShip()->getTargetClass();
                $fields = ArrayLib::map_key("strtolower", StaticsManager::getStatic($targetClass, "unique_fields"));
                $info = array();
                foreach($fields as $field) {
                    $info[$field] = $currentRecord->$field;
                }

                // find object
                $record = DataObject::get_versioned($targetClass, $this->queryVersion(),
                    $this->getFilterForUnique($this->getRelationShip(), $info))->first();
                if(!isset($record)) {
                    $record = $this->getRecordForUnique($this->getRelationShip(), $currentRecord, $info);
                    $dataToWrite[] = $record;
                }

                if($this->staging->find("versionid", $currentRecord->versionid)) {
                    $dataForRelationship[] = $record;
                } else {
                    $updated[$currentRecord->versionid] = $currentRecord;
                }
            }

            $this->staging = new ArrayList($dataToWrite);

            return $dataForRelationship;
        } else {
            // since the following stages are only stages, where the extra-fields changed we do not need to give back a copy.
            $staging = $this->staging->ToArray();
            foreach($this->updateFieldsStage as $record) {
                $updated[$record->versionid] = $record;

                if($record->hasChanged() && !$record->__onlymanyChanged) {
                    $this->staging->add($record);
                }
            }
            return $staging;
        }
    }

    /**
     * @param ModelManyManyRelationShipInfo $info
     * @param array $data
     * @return array
     */
    protected function getFilterForUnique($info, $data) {
        if($info->isUniqueLike()) {
            foreach($data as $key => $value) {
                $data[$key] = array("LIKE", trim($value));
            }
        }

        return $data;
    }
    /**
     * @param ModelManyManyRelationShipInfo $info
     * @param DataObject $record
     * @param array $data
     * @return DataObject
     */
    protected function getRecordForUnique($info, $record, $data) {
        if($info->isUniqueLike()) {
            foreach($data as $k => $v) {
                $record->{$k} = trim($v);
            }
        }

        return $record;
    }
}
