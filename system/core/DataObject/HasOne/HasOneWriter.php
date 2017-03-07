<?php defined("IN_GOMA") OR die();

/**
 * Basic Class for Writing Has-One-Relationships of Models to DataBase.
 *
 * @package     Goma\Model
 *
 * @license     GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author      Goma-Team
 *
 * @version    1.0
 */
class HasOneWriter extends Extension {
    /**
     * iterates through has-one-relationships and checks if there is something to write.
     */
    public function onBeforeDBWriter() {

        /** @var ModelWriter $owner */
        $owner = $this->getOwner();

        $data = $owner->getData();

        if ($has_one = $owner->getModel()->hasOne()) {
            foreach($has_one as $key => $value) {
                if (isset($data[$key]) && is_object($data[$key]) && is_a($data[$key], "DataObject")) {
                    /** @var DataObject $record */
                    $record = $data[$key];

                    if($has_one[$key]->getCascade() == DataObject::CASCADE_TYPE_UNIQUE) {
                        $fields = ArrayLib::map_key("strtolower", StaticsManager::getStatic($has_one[$key]->getTargetClass(), "unique_fields"));
                        $info = array();
                        foreach($fields as $field) {
                            $info[$field] = $record->$field;
                        }

                        // find object
                        $record = DataObject::get_one($has_one[$key]->getTargetClass(), $this->getFilterForUnique($has_one[$key], $info));
                        if(!isset($record)) {
                            $record = $this->getRecordForUnique($has_one[$key], $data[$key], $info);
                            $this->writeObject($record);
                        }

                        $data[$key . "id"] = $record->id;
                        unset($data[$key]);
                    } else {
                        if($this->shouldUpdateData($has_one[$key])) {
                            if($record != $owner->getModel()) { // check if it is a relationship to itself.
                                if ($record->wasChanged() || $record->id == 0) {
                                    $this->writeObject($record);
                                }
                            } else if($record->id == 0) {
                                continue;
                            }
                        }

                        if($record->id == 0) {
                            throw new InvalidArgumentException("You have to Write Has-One-Objects before adding it to a DataObject and writing it.");
                        }
                        // get id from object
                        $data[$key . "id"] = $record->id;
                        unset($data[$key]);
                    }
                }
            }
        }

        $owner->setData($data);
    }

    /**
     * @param array $data
     */
    public function afterInsertBaseClassAndGetVersionId(&$data, &$manipulation) {
        /** @var ModelWriter $owner */
        $owner = $this->getOwner();

        if ($has_one = $owner->getModel()->hasOne()) {
            foreach($has_one as $key => $value) {
                if (isset($data[$key]) && is_object($data[$key]) && is_a($data[$key], "DataObject")) {
                    /** @var DataObject $record */
                    $record = $data[$key];

                    if($record == $owner->getModel()) {
                        $data[$key . "id"] = $owner->getModel()->id;
                        $owner->getModel()->{$key . "id"} = $owner->getModel()->id;
                        unset($data[$key]);

                        // update base-table if field is in base-table
                        if(isset(ClassInfo::$database[$owner->getModel()->baseTable][$key . "id"])) {
                            $manipulation["update_hasone_" . $key . "_" . $owner->getModel()->baseTable] = array(
                                "table_name"=> $owner->getModel()->baseTable,
                                "id"        => $owner->getModel()->versionid,
                                "command"   => "update",
                                "fields"    => array(
                                    $key . "id" => $owner->getModel()->id
                                )
                            );
                        }
                    } else {
                        throw new LogicException("There should not be any has-one-object at this point except for same object cases.");
                    }
                }
            }
        }
    }

    /**
     * @param ModelHasOneRelationshipInfo $info
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
     * @param ModelHasOneRelationshipInfo $info
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

    /**
     * @param DataObject $record
     */
    protected function writeObject($record) {
        /** @var ModelWriter $owner */
        $owner = $this->getOwner();

        $writer = $owner->getRepository()->buildWriter(
            $record,
            -1,
            $owner->getSilent(),
            $owner->getUpdateCreated(),
            $owner->getWriteType(),
            $owner->getDatabaseWriter(),
            $owner->isForceWrite()
        );
        $writer->write();
    }
    /**
     * @param ModelHasOneRelationshipInfo $info
     * @return bool
     */
    protected function shouldRemoveData($info) {
        return (substr($info->getCascade(), 0, 1) == 1);
    }

    /**
     * @param ModelHasOneRelationshipInfo $info
     * @return bool
     */
    protected function shouldUpdateData($info) {
        return (substr($info->getCascade(), 1, 1) == 1);
    }
}

gObject::extend("ModelWriter", "HasOneWriter");
