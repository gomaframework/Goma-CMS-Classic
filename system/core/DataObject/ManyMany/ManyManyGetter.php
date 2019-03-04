<?php
defined("IN_GOMA") OR die();

/**
 * Extends DataObject with Getters for ManyMany.
 *
 * @package Goma
 *
 * @author Goma Team
 * @copyright 2016 Goma-Team
 * @license GPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 *
 * @version 1.0
 *
 * @method DataObject getOwner()
 */
class ManyManyGetter extends AbstractGetterExtension implements PostArgumentsQuery {
    /**
     * extra-methods.
     */
    protected static $extra_methods = array(
        "getManyMany",
        "setManyMany",
        "setManyManyIDs"
    );

    /**
     * define statics extension.
     */
    public function extendDefineStatics() {
        if ($manyMany = $this->getOwner()->ManyManyRelationships()) {
            foreach ($manyMany as $key => $val) {
                $this->linkMethodWithInstance(self::class, "set" . $key . "ids", $key, "setManyManyIDs", "Something got wrong wiring the ManyMany-Relationship.");
                $this->linkMethodWithInstance(self::class, "set" . $key, $key, "setManyMany", "Something got wrong wiring the ManyMany-Relationship.");
                gObject::LinkMethod($this->getOwner()->classname, $key . "ids", array("this", "getRelationIDs"), true);
                $this->linkMethodWithInstance(self::class, $key, $key, "getManyMany", "Something got wrong wiring the ManyMany-Relationship.");
            }
        }
    }

    /**
     * create objects for all has-one-data.
     */
    public function initValues() {
        foreach($this->getOwner()->ManyManyRelationships() as $name => $relationship) {
            if(is_array($this->getOwner()->fieldGet($name . "ids"))) {
                $this->setManyManyIDs($name, $this->getOwner()->fieldGet($name . "ids"));
            } else if(is_array($this->getOwner()->fieldGet($name))) {
                $this->setManyManyIDs($name, $this->getOwner()->fieldGet($name));
            }
        }
    }

    /**
     * @param SelectQuery $query
     * @param string $version
     * @param array|string $filter
     * @param array|string $sort
     * @param array|string|int $limit
     * @param array|string|int $joins
     * @param bool $forceClasses if to only get objects of this type of every object from the table
     */
    public function postArgumentQuery($query, $version, $filter, $sort, $limit, $joins, $forceClasses)
    {
        $manyManyRelationships = $this->getOwner()->ManyManyRelationships();

        if(is_array($query->filter)) {
            $query->filter = $this->factorOutFilter($query->filter, $version, $forceClasses, $manyManyRelationships);
        }
    }

    /**
     * @param array $filterArray
     * @param string $version
     * @param bool $forceClasses
     * @param ModelManyManyRelationShipInfo[] $relationShips
     * @return array
     * @throws Exception
     */
    protected function factorOutFilter($filterArray, $version, $forceClasses, $relationShips) {
        foreach($filterArray as $key => $value) {
            if(isset($relationShips[strtolower($key)])) {
                if($value) {
                    if (!is_array($value) || ArrayLib::isAssocArray($value)) {
                        $value = array($value);
                    }

                    $combinedExistsFilter = array();
                    foreach ($value as $subFilterArray) {
                        if (is_string($subFilterArray) && strtolower(trim($subFilterArray)) == "or") {
                            $combinedExistsFilter[] = "OR";
                        } else {
                            $combinedExistsFilter[] = " EXISTS ( ".$this->buildRelationQuery(
                                    $relationShips[strtolower($key)],
                                    $version,
                                    $subFilterArray,
                                    $forceClasses
                                )->build()." ) ";
                        }
                    }
                    $filterArray[$key] = implode(" AND ", $combinedExistsFilter);
                    $filterArray[$key] = str_replace(array(" AND OR ", " OR AND "), " OR ", $filterArray[$key]);
                    $filterArray = ArrayLib::change_key($filterArray, $key, ArrayLib::findFreeInt($filterArray));
                } else {
                    unset($filterArray[$key]);
                }
            } else if(strtolower(substr($key, -6)) == ".count" && isset($relationShips[strtolower(substr($key, 0, -6))])) {
                $filterArray[$key] = SQL::parseValue(" (".
                    $this->buildRelationQuery($relationShips[strtolower(substr($key, 0, -6))], $version, array(), $forceClasses)->build("count(*)")
                    .")",  $value);
                $filterArray = ArrayLib::change_key($filterArray, $key, ArrayLib::findFreeInt($filterArray));
            } else {
                if (is_array($value)) {
                    $filterArray[$key] = $this->factorOutFilter($filterArray[$key], $version, $forceClasses, $relationShips);
                }
            }
        }

        return $filterArray;
    }

    /**
     * @param ModelManyManyRelationShipInfo $relationShip
     * @param string $version
     * @param array $filter
     * @param bool $forceClasses
     * @return SelectQuery
     */
    protected function buildRelationQuery($relationShip, $version, $filter, $forceClasses) {
        $target = $relationShip->getTargetClass();
        /** @var DataObject $targetObject */
        $targetObject = new $target();
        $query = $targetObject->buildExtendedQuery($version, $filter, array(), array(), array(
            array(
                DataObject::JOIN_TYPE => "INNER",
                DataObject::JOIN_TABLE => $relationShip->getTableName(),
                DataObject::JOIN_STATEMENT => $relationShip->getTableName() . "." . $relationShip->getTargetField() . " = " . $relationShip->getTargetBaseTableName() . ".id",
                DataObject::JOIN_INCLUDEDATA => false
            )
        ), $forceClasses);
        $query->addFilter($relationShip->getTableName()  . "." . $relationShip->getOwnerField() . " = " . $this->getOwner()->baseTable . ".id");

        return $query;
    }

    /**
     * gets many-many-objects
     *
     * @param string $name
     * @param array|string $filter
     * @param array|string $sort
     * @return ManyMany_DataObjectSet
     * @throws MySQLException
     * @throws SQLException
     */
    public function getManyMany($name, $filter = null, $sort = null) {
        $name = trim(strtolower($name));

        // get info
        $relationShip = $this->getOwner()->getManyManyInfo($name);

        // check field
        $instance = $this->getOwner()->fieldGet($name);
        if(!$instance || !is_a($instance, "ManyMany_DataObjectSet")) {
            $instance = new ManyMany_DataObjectSet($relationShip->getTargetClass());
            $instance->setRelationENV($relationShip, $this->getOwner());
            if($this->getOwner()->queryVersion === false) {
                $instance->setVersionMode(DataObject::VERSION_MODE_CURRENT_VERSION);
            }

            $this->getOwner()->setField($name, $instance, true);

            if ($this->getOwner()->queryVersion == DataObject::VERSION_STATE && !$this->getOwner()->isPublished()) {
                $instance->setVersion(DataObject::VERSION_STATE);
            } else {
                $instance->setVersion(DataObject::VERSION_PUBLISHED);
            }
        }

        if(!$filter && !$sort) {
            return $instance;
        }

        $version = clone $instance;
        $version->filter($filter);
        $version->sort($sort);

        return $version;
    }

    /**
     * sets many-many-data
     *
     * @param string $name
     * @param array|DataObjectSet|object $value
     */
    public function setManyMany($name, $value) {
        $relationShipInfo = $this->getOwner()->getManyManyInfo($name);

        if (is_a($value, "DataObjectSet")) {
            if(!is_a($value, "ManyMany_DataObjectSet")) {
                $instance = new ManyMany_DataObjectSet($relationShipInfo->getTargetClass());
                $instance->setVersion($this->getOwner()->queryVersion);
                $instance->setRelationEnv($relationShipInfo, $this->getOwner());
                $instance->setFetchMode(DataObjectSet::FETCH_MODE_CREATE_NEW);
                $instance->addMany($value);
            } else {
                $instance = $value;
            }

            $this->getOwner()->setField($name, $instance);

            return;
        }

        $this->setManyManyIDs($name, $value);
    }

    /**
     * sets many-many-ids
     * @param string $name
     * @param array $ids
     */
    public function setManyManyIDs($name, $ids) {
        if(is_a($ids, "DataObjectSet")) {
            $this->setManyMany($name, $ids);
        } else {
            $this->getManyMany($name)->setSourceData($ids);
        }
    }
}
gObject::extend("DataObject", "ManyManyGetter");
