<?php defined("IN_GOMA") OR die();

/**
 * DataSet for has-many-relationships.
 *
 * @package     Goma\Model
 *
 * @license     GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author      Goma-Team
 *
 * @version     1.2.1
 */
class HasMany_DataObjectSet extends RemoveStagingDataObjectSet {

    /**
     * field for the relationship-info.
     *
     * @var ModelHasManyRelationShipInfo
     */
    protected $relationShipInfo;

    /**
     * @var int
     */
    protected $relationShipValue;

    /**
     * @var string
     */
    protected $relationShipField;

    /**
     * @var DataObject
     */
    protected $ownRecord;

    /**
     * sets the relation-props
     *
     * @param ModelHasManyRelationShipInfo $relationShipInfo
     * @param int $value
     * @param DataObject $ownRecord
     */
    public function setRelationENV($relationShipInfo, $value, $ownRecord) {
        if(!isset($relationShipInfo)) {
            throw new InvalidArgumentException("First argument of setRelationENV needs to be type of ModelHasManyRelationShipInfo. Null given.");
        }

        $this->relationShipInfo = $relationShipInfo;
        $this->relationShipValue = $value;
        $this->relationShipField = $relationShipInfo->getInverse() . "id";
        $this->ownRecord = $ownRecord;

        if($this->getFetchMode() != self::FETCH_MODE_CREATE_NEW && $this->first() && $this->first()->{$this->relationShipField} != $this->relationShipValue) {
            throw new InvalidArgumentException("You cannot move HasManyRelationship to another object. Please copy data with value '".$value."' by yourself for: " . $relationShipInfo->getRelationShipName());
        }

        foreach($this->staging as $record) {
            $record->{$this->relationShipField} = $this->relationShipValue;
        }
    }

    /**
     * get the relation-props
     */
    public function getRelationENV() {
        return array("info" => $this->relationShipInfo, "value" => $this->relationShipValue);
    }

    /**
     * generates a form
     *
     * @param string $name
     * @param bool $edit if edit form
     * @param bool $disabled
     * @param null $request
     * @param null $controller
     * @param null $submission
     * @return Form
     */
    public function generateForm($name = null, $edit = false, $disabled = false, $request = null, $controller = null, $submission = null) {
        $form = parent::generateForm($name, $edit, $disabled, $request, $controller, $submission);

        if(($id = $this->getRelationID()) !== null) {
            $form->add(new HiddenField($this->relationShipField, $id));
        }

        return $form;
    }

    /**
     * @param bool $forceInsert
     * @param bool $forceWrite
     * @param int $snap_priority
     * @param null|IModelRepository $repository
     * @param array $options
     * @param array $exceptions
     * @param array $errorRecords
     */
    public function writeCommit($forceInsert, $forceWrite, $snap_priority, $repository, $options, &$exceptions, &$errorRecords)
    {
        if($this->fetchMode == DataObjectSet::FETCH_MODE_CREATE_NEW) {
            $records = $this->dbDataSource()->getRecords($this->version, array(
                $this->relationShipField => $this->relationShipValue,
                "recordid NOT in ('".implode("','", array_merge($this->staging->fieldToArray("id"), $this->removeStaging->fieldToArray("id")))."')"
            ));

            foreach($records as $record) {
                $this->removeStaging->add($record);
            }
        }

        if(($id = $this->getRelationID()) !== null) {
            /** @var DataObject $ownRecord */
            $ownRecord = new $this->ownRecord->classname(
                array_merge(
                    $this->ownRecord->ToArray(),
                    array(
                        "id" => $this->getRelationID()
                    )
                )
            );

            foreach($this->staging as $record) {
                $record->{$this->relationShipField} = $this->getRelationID();
                $record->setField($this->relationShipInfo->getInverse(), clone $ownRecord);
            }
        }

        parent::writeCommit($forceInsert, $forceWrite, $snap_priority, $repository, $options, $exceptions, $errorRecords);
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function createNewModel($data = array())
    {
        $record = parent::createNewModel($data);

        if(($id = $this->getRelationID()) !== null) {
            $record->{$this->relationShipField} = $id;
            $record->setField($this->relationShipInfo()->getInverse(), $this->ownRecord);
        }

        return $record;
    }

    /**
     * @param DataObject $record
     * @param bool $write
     * @return $this
     */
    public function push($record, $write = false)
    {
        if(($id = $this->getRelationID()) !== null) {
            $record->{$this->relationShipField} = $id;
            $record->setField($this->relationShipInfo()->getInverse(), $this->ownRecord);
        }

        return parent::push($record, $write);
    }

    /**
     * @param DataObject $item
     * @return object
     */
    public function getConverted($item)
    {
        $record = parent::getConverted($item);
        if(is_a($record, ViewAccessableData::class) && $this->ownRecord) {
            if(!is_a($record->fieldGet($this->relationShipInfo()->getInverse()), $this->ownRecord->classname)) {
                $record->setField($this->relationShipInfo()->getInverse(), $this->ownRecord);
            }
        }
        return $record;
    }

    /**
     * gets id.
     *
     * @return null|int
     */
    protected function getRelationID() {
        if(isset($this->relationShipValue)) {
            return $this->relationShipValue == 0 ? -1 : $this->relationShipValue;
        } else if(isset($this->filter[$this->relationShipField]) && (is_string($this->filter[$this->relationShipField]) || is_int($this->filter[$this->relationShipField]))) {
            return $this->filter[$this->relationShipField];
        } else {
            return $this->ownRecord ? $this->ownRecord->id : null;
        }
    }

    /**
     * @param IModelRepository $repository
     * @param bool $forceWrite
     * @param int $snap_priority
     * @return mixed
     * @throws MySQLException
     * @throws PermissionException
     */
    public function commitRemoveStaging($repository, $forceWrite = false, $snap_priority = 2) {
        if($this->removeStaging->count() > 0) {
            /** @var DataObject $item */
            foreach ($this->removeStaging as $item) {
                if ($this->relationShipInfo()->shouldRemoveData()) {
                    $item->remove($forceWrite);
                } else {
                    $item->{$this->relationShipField} = 0;
                    $item->setField($this->relationShipInfo()->getInverse(), null);
                    $item->writeToDBInRepo($repository, false, $forceWrite, $snap_priority);
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getFilterForQuery()
    {
        $filter = parent::getFilterForQuery();

        if(($id = $this->getRelationID()) !== null) {
            $filter = array($filter, array($this->relationShipField  => $id));
        } else {
            throw new InvalidArgumentException("HasMany_DataObjectSet needs relationship-info for query.");
        }

        return $filter;
    }

    /**
     * @param array|string $filter
     * @return array
     */
    protected function argumentFilterForHidingRemovedStageForQuery($filter) {
        if($ids = $this->removeStaging->fieldToArray("id")) {
            if (!is_array($filter)) {
                $filter = (array)$filter;
            }

            $filter[] = $this->dbDataSource()->table() . ".recordid NOT IN ('" . implode("','", $this->removeStaging->fieldToArray("id")) . "') ";
        }

        return $filter;
    }

    /**
     * @return ModelHasManyRelationShipInfo
     */
    protected function relationShipInfo()
    {
        if(!isset($this->relationShipInfo)) {
            throw new InvalidArgumentException("You have to set RelationshipInfo if you want to make changes on this relationship.");
        }

        return $this->relationShipInfo;
    }
}
