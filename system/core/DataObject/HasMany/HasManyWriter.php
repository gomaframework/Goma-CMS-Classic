<?php defined("IN_GOMA") OR die();

/**
 * Basic Class for Writing Has-Many-Relationships of Models to DataBase.
 *
 * @package     Goma\Model
 *
 * @license     GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author      Goma-Team
 *
 * @version    1.0.1
 *
 * @method ModelWriter getOwner()
 */
class HasManyWriter extends Extension {
    /**
     * writes has-many-relationships.
     */
    protected function onBeforeWriteData() {
        $owner = $this->getOwner();

        // has-many
        /** @var HasManyGetter $hasManyExtension */
        $owner->getModel()->workWithExtensionInstance(HasManyGetter::class, function($hasManyExtension) use($owner) {
            $data = $owner->getData();
            /** @var HasManyGetter $hasManyExtension */
            if ($has_many = $hasManyExtension->hasMany()) {
                foreach ($has_many as $name => $info) {
                    if (isset($data[$name]) && is_object($data[$name]) && is_a($data[$name], "HasMany_DataObjectSet")) {
                        /** @var HasMany_DataObjectSet $hasManyObject */
                        $hasManyObject = $data[$name];

                        $hasManyObject->setRelationENV(
                            $has_many[$name],
                            $owner->getModel()->id,
                            $owner->getModel()
                        );

                        if($this->shouldUpdateData($info) && $hasManyObject->hasChanged()) {
                            $hasManyObject->commitStaging(false, true, $owner->getWriteType());
                        } else if($this->getOwner()->getObjectToUpdate() == null) {
                            $hasManyObject->setFetchMode(DataObjectSet::FETCH_MODE_EDIT);
                        }
                    } else {
                        if (isset($data[$name]) && !isset($data[$name . "ids"]) && is_array($data[$name])) {
                            $data[$name . "ids"] = $data[$name];
                        }

                        if (isset($data[$name . "ids"]) && $this->validateIDsData($data[$name . "ids"])) {
                            if (in_array(0, $data[$name . "ids"])) {
                                throw new InvalidArgumentException("HasMany-Relationship must contain only already written records.");
                            }

                            $this->removeFromRelationShip($info->getTargetClass(), $has_many[$name]->getInverse() . "id", $owner->getModel()->id, $data[$name . "ids"], $has_many[$name]->shouldRemoveData());
                            $this->updateRelationship($data[$name . "ids"], $has_many[$name]);
                        }
                    }
                }
            }
            $owner->setData($data);
        });
    }

    /**
     * set field to 0 for all elements which have at the moment the given id on that field, but
     * the recordid is not in the given array.
     *
     * @param string $class
     * @param string $field
     * @param int $key
     * @param int[] $excludeRecordIds
     * @param bool $removeFromDatabase
     * @throws MySQLException
     * @throws PermissionException
     * @throws SQLException
     */
    protected function removeFromRelationShip($class, $field, $key, $excludeRecordIds, $removeFromDatabase) {
        /** @var ModelWriter $owner */
        $owner = $this->getOwner();

        foreach(DataObject::get($class,
            "$field = '".$key."' AND recordid NOT IN ('".implode("','", $excludeRecordIds)."')"
        ) as $notExistingElement) {
            if($removeFromDatabase) {
                $notExistingElement->remove();
            } else {
                $notExistingElement->$field = 0;
                $writer = $owner->getRepository()->buildWriter(
                    $notExistingElement,
                    -1,
                    $owner->getSilent(),
                    $owner->getUpdateCreated(),
                    $owner->getWriteType(),
                    $owner->getDatabaseWriter());
                $writer->write();
            }
        }
    }

    /**
     * @param array $ids
     * @param ModelHasManyRelationShipInfo $relationShip
     * @throws PermissionException
     */
    protected function updateRelationship($ids, $relationShip) {
        $owner = $this->getOwner();

        /** @var DataObject $record */
        foreach(DataObject::get($relationShip->getTargetClass(), array("id" => $ids)) as $record) {
            $record->{$relationShip->getInverse() . "id"} = $this->getOwner()->getModel()->id;
            $writer = $owner->getRepository()->buildWriter(
                $record,
                -1,
                $owner->getSilent(),
                $owner->getUpdateCreated(),
                $owner->getWriteType(),
                $owner->getDatabaseWriter());
            $writer->write();
        }
    }

    /**
     * validates if input is correct.
     *
     * @param $data
     * @return bool
     */
    private function validateIDsData($data)
    {
        if(!is_array($data)) {
            return false;
        }

        foreach($data as $record) {
            if(!is_string($record) && !is_int($record)) {
                return false;
            }
        }

        return true;
    }

    /**
     * extends hasChanged-Method.
     * @param bool $changed
     */
    public function extendHasChanged(&$changed) {
        /** @var ModelWriter $owner */
        $owner = $this->getOwner();
        /** @var HasManyGetter $extensionInstance */
        $owner->getModel()->workWithExtensionInstance(HasManyGetter::class, function($extensionInstance) use($changed, $owner) {
            // has-many
            if ($has_many = $extensionInstance->hasMany()) {
                if ($owner->checkForChangeInRelationship(array_keys($has_many), true, "HasMany_DataObjectSet")) {
                    $changed = true;

                    return;
                }
            }
        });
    }

    /**
     * @param ModelHasManyRelationShipInfo $info
     * @return bool
     */
    protected function shouldRemoveData($info) {
        return (substr($info->getCascade(), 0, 1) == 1);
    }

    /**
     * @param ModelHasManyRelationShipInfo $info
     * @return bool
     */
    protected function shouldUpdateData($info) {
        return (substr($info->getCascade(), 1, 1) == 1);
    }
}
gObject::extend("ModelWriter", "HasManyWriter");
