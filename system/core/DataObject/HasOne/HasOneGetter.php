<?php
defined("IN_GOMA") OR die();

/**
 * HasOneGetter-Extension for Goma Framework.
 *
 * Using HasOne for DataObjects:
 * <code>
 * class Model extends DataObject {
 *     static $has_one = array(
 *          "one" => "differentModelClass"
 *     );
 * }
 *
 * // create model
 * $model = new Model();
 * $model->one = new DifferentModelClass();
 * $differentModelClass = $model->one;
 *
 * // write model
 * $model->writeToDb();
 *
 * // query model
 * $modelFromDb = DataObject::get_one(Model::class, array("id" => $model->id));
 * $differentModel = $model->one; // will be of type DifferentModelClass.
 * </code>
 *
 * @package Goma
 *
 * @author Goma-Team
 * @license     GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @copyright 2016 Goma-Team
 *
 * @version 1.0
 *
 * @method DataObject getOwner()
 */
class HasOneGetter extends AbstractGetterExtension implements postArgumentsQuery {
    /**
     * extra-methods.
     */
    protected static $extra_methods = array(
        "HasOne",
        "GetHasOne"
    );

    /**
     * @var array
     */
    protected static $relationShips = array(

    );

    /**
     * create objects for all has-one-data.
     */
    public function initValues() {
        foreach($this->hasOne() as $name => $data) {
            if(is_array($this->getOwner()->fieldGet($name))) {
                $this->getOwner()->setField($name, $this->getOwner()->createNew($this->getOwner()->fieldGet($name)));
            } else if(!$this->getOwner()->fieldGet($name) && !$this->getOwner()->fieldGet($name . "id")) {
                $this->getOwner()->setField($name . "id", 0);
            }
        }
    }

    /**
     * define statics extension.
     */
    public function extendDefineStatics() {
        if ($has_one = $this->HasOne()) {
            foreach($has_one as $key => $val) {
                $this->linkMethodWithInstance(self::class, $key, $key, "getHasOne", "Something got wrong wiring the HasOne-Relationship.");
                $this->linkMethodWithInstance(self::class, "set" . $key, $key, "setHasOne", "Something got wrong wiring the HasOne-Relationship.");
                $this->linkMethodWithInstance(self::class, "set" . $key . "id", $key, "setHasOneId", "Something got wrong wiring the HasOne-Relationship.");
            }
        }
    }

    /**
     * returns one or many hasOne-Relationships.
     *
     * @name hasOne
     * @param string $component name of has-many-relation to give back.
     * @return ModelHasOneRelationShipInfo[]|ModelHasOneRelationShipInfo
     */
    public function hasOne($component = null) {
        $owner = $this->getOwner();

        if(!$owner) {
            return array();
        }

        if(!isset(self::$relationShips[$owner->classname]) ||
            (!self::$relationShips[$owner->classname] && ClassInfo::ClassInfoHasBeenRegenerated())) {
            $hasOneClasses = array();
            $has_one = isset(ClassInfo::$class_info[$owner->classname]["has_one"]) ? ClassInfo::$class_info[$owner->classname]["has_one"] : array();

            foreach($has_one as $name => $value) {
                $value['validatedInverse'] = true;
                $hasOneClasses[$name] = new ModelHasOneRelationshipInfo($owner->classname, $name, $value);
            }

            if ($classes = ClassInfo::dataclasses($owner->classname)) {
                foreach($classes as $class) {
                    if (isset(ClassInfo::$class_info[$class]["has_one"])) {
                        $has_one = array_merge(ClassInfo::$class_info[$class]["has_one"], $has_one);

                        foreach(ClassInfo::$class_info[$class]["has_one"] as $name => $value) {
                            $value['validatedInverse'] = true;
                            $hasOneClasses[$name] = new ModelHasOneRelationshipInfo($class, $name, $value);
                        }
                    }
                }
            }

            self::$relationShips[$owner->classname] = $hasOneClasses;
        }

        if(!isset($component)) {
            return self::$relationShips[$owner->classname];
        } else {
            return isset(self::$relationShips[$owner->classname][$component]) ? self::$relationShips[$owner->classname][$component] : null;
        }
    }

    /**
     * gets a has-one-dataobject
     *
     * @param string $name name of relationship
     * @return DataObject
     */
    public function getHasOne($name) {
        $name = trim(strtolower($name));

        // get info
        if($relationShip = $this->hasOne($name)) {
            // check field
            $instance = $this->getOwner()->fieldGet($name);
            if (!$instance || !is_a($instance, "DataObject")) {
                if(!$this->getOwner()->fieldGet($name . "id")) {
                    return null;
                }

                // if same
                if(ClassManifest::isOfType($relationShip->getTargetClass(), $relationShip->getOwner()) &&
                    $this->getOwner()->fieldGet($name . "id") == $this->getOwner()->id) {
                    return $this->getOwner();
                }

                $response = DataObject::get($relationShip->getTargetClass(), array(
                    "id" => $this->getOwner()->fieldGet($name . "id")
                ));

                if ($this->getOwner()->queryVersion == DataObject::VERSION_STATE && !$this->getOwner()->isPublished()) {
                    $response->setVersion(DataObject::VERSION_STATE);
                }

                $this->getOwner()->setField($name, $instance = $response->first(), true);
            }

            return $instance;
        } else {
            throw new InvalidArgumentException("No Has-one-relation '".$name."' on ".$this->classname);
        }
    }


    /**
     * sets has-one.
     * @param string $name
     * @param DataObject $value
     */
    public function setHasOne($name, $value) {
        $name = strtolower(trim($name));

        $has_one = $this->hasOne();
        if (isset($has_one[$name])) {
            if(!isset($value)) {
                $this->getOwner()->setField($name, $value);
                $this->getOwner()->setField($name  ."id", 0);
            } else if(is_a($value, "DataObject")) {
                $this->getOwner()->setField($name, $value);
                $this->getOwner()->setField($name  ."id", $value->id != 0 ? $value->id : null);
            } else {
                throw new InvalidArgumentException("setting HasOne-Relationship " .$name. " must be either DataObject or null.");
            }
        } else if(substr($name, 0, 3) == "set") {
            $this->setHasOne(substr($name, 3), $value);
        } else {
            throw new InvalidArgumentException("No Has-one-relation '".$name."' on ".$this->classname);
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
     * @return mixed
     */
    public function postArgumentQuery($query, $version, $filter, $sort, $limit, $joins, $forceClasses)
    {
        if (is_array($query->filter))
        {
            $has_one = $this->hasOne();
            $query->filter = $this->parseHasOnes($query->filter, $has_one, $version, $forceClasses);
        }
    }

    /**
     * @param ModelHasOneRelationshipInfo $relationShip
     * @param string $version
     * @param array $filter
     * @param bool $forceClasses
     * @return SelectQuery
     */
    protected function buildRelationQuery($relationShip, $version, $filter, $forceClasses) {
        $target = $relationShip->getTargetClass();
        /** @var DataObject $targetObject */
        $targetObject = new $target();
        $ownerTableName = ClassInfo::$class_info[$relationShip->getOwner()]["table"];
        $query = $targetObject->buildExtendedQuery($version, $filter, array(), array(), array(), $forceClasses);
        $query->addFilter($targetObject->baseTable . ".recordid = " . $ownerTableName . "." . $relationShip->getRelationShipName() . "id");

        return $query;
    }

    /**
     * @param array $filter
     * @param ModelHasOneRelationshipInfo[] $has_one
     * @return array
     */
    protected function parseHasOnes($filter, $has_one, $version, $forceClasses) {
        if (is_array($filter))
        {
            foreach($filter as $key => $value)
            {
                if (strpos($key, ".") !== false) {
                    // has one
                    $hasOnePrefix = strtolower(substr($key, 0, strpos($key, ".")));
                    if (isset($has_one[$hasOnePrefix])) {
                        $filter[$key] = " EXISTS ( ".
                            $this->buildRelationQuery($has_one[$hasOnePrefix], $version, array(substr($key, strlen($hasOnePrefix) + 1) => $value), $forceClasses)->build()
                            ." ) ";
                        $filter = ArrayLib::change_key($filter, $key, ArrayLib::findFreeInt($filter));
                    }
                } else {
                    if(is_array($value)) {
                        $filter[$key] = $this->parseHasOnes($value, $has_one, $version, $forceClasses);
                    }
                }
            }
        }

        return $filter;
    }

    /**
     * @param SelectQuery $query
     * @param string $aggregateField
     * @param array $aggregates
     */
    public function extendAggregate(&$query, &$aggregateField, &$aggregates, $version) {
        if (strpos($aggregateField, ".") !== false) {
            $has_one = $this->hasOne();

            $versionField = $version == DataObject::VERSION_STATE ? "stateid" : "publishedid";
            $hasOnePrefix = strtolower(substr($aggregateField, 0, strpos($aggregateField, ".")));
            if (isset($has_one[$hasOnePrefix])) {
                $baseClass = ClassInfo::$class_info[$has_one[$hasOnePrefix]->getTargetClass()]["baseclass"];
                $baseTable = (ClassInfo::$class_info[$baseClass]["table"]);
                $ownerTable = ClassInfo::$class_info[$has_one[$hasOnePrefix]->getOwner()]["table"];

                if(!$query->aliasExists($baseTable . "_" . $hasOnePrefix . "_state")) {
                    $query->innerJoin(
                        $baseTable . '_state',
                        $baseTable . "_" . $hasOnePrefix . '_state.id = ' . $ownerTable . '.'. $has_one[$hasOnePrefix]->getRelationShipName() .'id',
                        $baseTable . "_" . $hasOnePrefix . '_state',
                        false
                    );
                }

                // find table which maps the field
                $found = false;
                foreach(array_merge(array($baseTable), ClassInfo::DataClasses($baseClass)) as $table) {
                    if(isset(ClassInfo::$database[$table][substr($aggregateField, strlen($hasOnePrefix) + 1)])) {
                        if(!$query->aliasExists($hasOnePrefix . "_" . $table)) {
                            $query->leftJoin(
                                $table,
                                $hasOnePrefix . "_" . $table . ".id = " . $baseTable . "_" . $hasOnePrefix . '_state.' . $versionField,
                                $hasOnePrefix . "_" . $table
                            );
                        }
                        $found = true;
                        $aggregateField = $hasOnePrefix . "_" . $table . "." . substr($aggregateField, strlen($hasOnePrefix) + 1);
                    }
                }

                if(!$found) {
                    throw new InvalidArgumentException("Could not find field " . $aggregateField . " for has-one-relationship " . $hasOnePrefix);
                }
            }
        }
    }

    /**
     * @param array $result
     * @param SelectQuery $query
     * @param string $version
     */
    public function argumentQueryResult(&$result, $query, $version) {
        if(PROFILE) Profiler::mark("HasOneGetter::argumentQueryResult");

        $relationShips = $this->getHasOnesToFetch($result);
        if($relationShips) {
            foreach ($relationShips as $name => $relationShip) {
                // build ids
                $ids = array();
                foreach ($result as $key => $record) {
                    if (isset($record[$name . "id"]) && $record[$name . "id"] != 0) {
                        $id = $record[$name . "id"];
                        if (!isset($ids[$id])) {
                            $ids[$id] = array();
                        }
                        $ids[$id][] = $key;
                    }
                }

                if (count($ids) > 0) {
                    $relationShipData = DataObject::get_versioned($relationShip->getTargetClass(), $version, array(
                        "id" => array_keys($ids)
                    ));
                    /** @var DataObject $record */
                    foreach ($relationShipData as $record) {
                        foreach ($ids[$record->id] as $resultKey) {
                            $result[$resultKey][$name] = $record->ToArray();
                        }
                    }
                }
            }
        }

        if(PROFILE) Profiler::unmark("HasOneGetter::argumentQueryResult");
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function setHasOneId($name, $value) {
        $this->getOwner()->setField($name . "id", $value);
        $this->getOwner()->setField($name, null);
    }

    /**
     * @param array $result
     * @return ModelHasOneRelationshipInfo[]
     */
    protected function getHasOnesToFetch($result) {
        $hasOnes = array();
        if(count($result) > 0) {
            foreach ($this->hasOne() as $name => $relationShip) {
                if ($relationShip->getFetchType() == DataObject::FETCH_TYPE_EAGER) {
                    if(isset($result[0][$name . "id"])) {
                        $hasOnes[$name] = $relationShip;
                    }
                }
            }
        }
        return $hasOnes;
    }
}
gObject::extend("DataObject", "HasOneGetter");
