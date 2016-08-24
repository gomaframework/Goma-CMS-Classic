<?php
defined("IN_GOMA") OR die();

/**
 * Data-Source for Uploads-Backtracking.
 *
 * @package Goma
 *
 * @author Goma-Team
 * @copyright 2016 Goma-Team
 * @license 	LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 *
 * @version 1.0
 */
class UploadsBackTrackDataSource implements IDataObjectSetDataSource {
    const FETCH_MODE_SINGLE = "single";
    const FETCH_MODE_GROUP = "group";

    /**
     * Uploads-Object.
     *
     * @var Uploads
     */
    protected $upload;

    /**
     * mode.
     */
    protected $fetchMode = self::FETCH_MODE_SINGLE;

    /**
     * @var array
     */
    private static $linkingModelCache = array();

    /**
     * @var array
     */
    private $countCache = array();

    /**
     * UploadsBackTrackDataSource constructor.
     * @param Uploads $upload
     */
    public function __construct($upload)
    {
        $this->upload = $upload;
    }

    /**
     * gets records.
     *
     * @param string $version
     * @param array $filter
     * @param array $sort
     * @param array $limit
     * @param array $joins
     * @param array $search
     * @return ViewAccessableData[]|array
     */
    public function getRecords($version, $filter = array(), $sort = array(), $limit = array(), $joins = array(), $search = array())
    {
        $limitLength = isset($limit[1]) ? $limit[1] : ((is_int($limit)) ? $limit : PHP_INT_MAX);
        $start = isset($limit[0]) ? $limit[0] : 0;

        $records = array();

        $models = self::findLinkingModels(Uploads::class);
        $i = 0;
        foreach ($models as $model => $info) {
            foreach ($info as $field => $fetchInfo) {
                if($fetchInfo == "n") {
                    if($this->fetchMode == self::FETCH_MODE_SINGLE) {
                        $currentData = DataObject::get($model, $filter)->addFilter(array(
                            $field => array(
                                "id" => $this->upload->id,
                                "OR",
                                "sourceImageId" => $this->upload->id
                            )
                        ));
                    } else {
                        $currentData = DataObject::get($model, $filter)->addFilter(array(
                            $field => array(
                                "md5" => $this->upload->md5,
                                "OR",
                                "sourceImageId" => $this->upload->id
                            )
                        ));
                    }
                } else {
                    if($this->fetchMode == self::FETCH_MODE_SINGLE) {
                        $currentData = DataObject::get($model, $filter)->addFilter(array(
                            $field . "id"           => $this->upload->id,
                            "OR",
                            $field .".sourceImageId" => $this->upload->id
                        ));
                    } else {
                        $currentData = DataObject::get($model, $filter)->addFilter(array(
                            $field . ".md5" => $this->upload->md5,
                            "OR",
                            $field.".sourceImageId" => $this->upload->id
                        ));
                    }
                }

                $remaining = ($limitLength - $i);
                if($currentData->count() > $remaining) {
                    $records = array_merge($records, $currentData->getArrayRange(0, $remaining));
                    break;
                } else {
                    if($i > $start) {
                        $records = array_merge($records, $currentData->getArrayRange(0, $currentData->count()));
                    } else if($i + $currentData->count() > $start) {
                        $records = array_merge($records, $currentData->getArrayRange($start - $i, $currentData->count()));
                    }

                    $i += $currentData->count();
                }
            }

            if (gObject::method_exists($model, "provideLinkingUploads")) {
                $data = call_user_func_array(array($model, "provideLinkingUploads"), array($model, $this->upload));
                if(is_array($data)) {
                    /** @var IDataSet $source */
                    foreach($data as $source) {
                        if(!is_a($source, IDataSet::class)) {
                            throw new InvalidArgumentException();
                        }

                        $i += $source->count();
                    }
                } else {
                    if(!is_a($data, IDataSet::class)) {
                        throw new InvalidArgumentException();
                    }

                    $i += $data->count();
                }
            }
        }

        return $records;
    }

    /**
     * gets specific aggregate like max, min, count, sum
     *
     * @param string $version
     * @param string|array $aggregate
     * @param string $aggregateField
     * @param bool $distinct
     * @param array $filter
     * @param array $sort
     * @param array $limit
     * @param array $joins
     * @param array $search
     * @param array $groupby
     * @return mixed
     */
    public function getAggregate($version, $aggregate, $aggregateField = "*", $distinct = false, $filter = array(), $sort = array(), $limit = array(), $joins = array(), $search = array(), $groupby = array())
    {
        $data = array();
        foreach((array) $aggregate as $singleAggregate) {
            if(strtolower($singleAggregate) == "count") {
                $data[$singleAggregate] = $this->count($filter);
            } else {
                $data[$singleAggregate] = 0;
            }
        }

        if(count($data) == 1) {
            return array_values($data)[0];
        }

        return $data;
    }

    /**
     * @param array $filter
     * @return int
     */
    public function count($filter) {
        $hash = md5(var_export($filter, true));

        if(!isset($this->countCache[$hash])) {
            $models = self::findLinkingModels(Uploads::class);
            $i = 0;

            foreach ($models as $model => $info) {
                foreach ($info as $field => $fetchInfo) {
                    if($fetchInfo == "n") {
                        if($this->fetchMode == self::FETCH_MODE_SINGLE) {
                            $i += DataObject::get($model, $filter)->addFilter(array(
                                $field => array(
                                    "id" => $this->upload->id
                                )
                            ))->count();
                        } else {
                            $i += DataObject::get($model, $filter)->addFilter(array(
                                $field => array(
                                    "md5" => $this->upload->md5
                                )
                            ))->count();
                        }
                    } else {
                        if($this->fetchMode == self::FETCH_MODE_SINGLE) {
                            $i += DataObject::get($model, $filter)->addFilter(array(
                                $field . "id" => $this->upload->id
                            ))->count();
                        } else {
                            $i += DataObject::get($model, $filter)->addFilter(array(
                                $field . ".md5" => $this->upload->md5
                            ))->count();
                        }
                    }
                }

                if (gObject::method_exists($model, "provideLinkingUploads")) {
                    $data = call_user_func_array(array($model, "provideLinkingUploads"), array($model, $this->upload));
                    if(is_array($data)) {
                        /** @var IDataSet $source */
                        foreach($data as $source) {
                            if(!is_a($source, IDataSet::class)) {
                                throw new InvalidArgumentException();
                            }

                            $i += $source->count();
                        }
                    } else {
                        if(!is_a($data, IDataSet::class)) {
                            throw new InvalidArgumentException();
                        }

                        $i += $data->count();
                    }
                }
            }

            $this->upload->propLinks = $i;
            $this->upload->writeToDB(false, true, 2, false, false);

            $this->countCache[$hash] = $i;
        }

        return $this->countCache[$hash];
    }


    /**
     * @param string|null $class
     * @return array
     */
    public static function findLinkingModels($class = null) {
        $class = isset($class) ? $class : Uploads::class;

        if(isset(self::$linkingModelCache[$class])) {
            return self::$linkingModelCache[$class];
        }

        $models = array();
        foreach(ClassInfo::getChildren(DataObject::class) as $model) {
            if(!ClassManifest::isOfType($model, Uploads::class)) {
                if (isset(ClassInfo::$class_info[$model]["has_one"])) {
                    /** @var ModelHasOneRelationshipInfo $relationShip */
                    foreach (gObject::instance($model)->hasOne() as $relationShip) {
                        if (ClassManifest::isOfType($relationShip->getTargetClass(), $class)) {
                            $models[$model][$relationShip->getRelationShipName()] = "1";
                        }
                    }
                } else if (isset(ClassInfo::$class_info[$model]["many_many"])) {
                    /** @var ModelManyManyRelationShipInfo $relationShip */
                    foreach (gObject::instance($model)->ManyManyRelationships() as $relationShip) {
                        if (ClassManifest::isOfType($relationShip->getTargetClass(), $class)) {
                            $models[$model][$relationShip->getRelationShipName()] = "n";
                        }
                    }
                }

                if (gObject::method_exists($model, "provideLinkingUploads")) {
                    $model[$model] = array();
                }
            }
        }

        self::$linkingModelCache[$class] = $models;

        return $models;
    }

    /**
     * @param string $version
     * @param string $groupField
     * @param array $filter
     * @param array $sort
     * @param array $limit
     * @param array $joins
     * @param array $search
     * @return ViewAccessableData[]|array
     */
    public function getGroupedRecords($version, $groupField, $filter = array(), $sort = array(), $limit = array(), $joins = array(), $search = array())
    {
        return array();
    }

    /**
     * @param string $field
     * @return bool
     */
    public function canFilterBy($field)
    {
        return isset(DataObject::DefaultSQLFields(Uploads::class)[$field]);
    }

    /**
     * @param string $field
     * @return bool
     */
    public function canSortBy($field)
    {
        return false;
    }

    /**
     * @return string
     */
    public function DataClass()
    {
        return "DataObject";
    }

    /**
     * @return string
     */
    public function getInExpansion()
    {
        return null;
    }

    /**
     * @return string
     */
    public function table()
    {
        return null;
    }

    /**
     * @return string
     */
    public function baseTable()
    {
        return null;
    }

    /**
     * @param array $manipulation
     * @param ManyMany_DataObjectSet $set
     * @param array $writeData array of versionid => boolean
     * @return mixed
     */
    public function onBeforeManipulateManyMany(&$manipulation, $set, $writeData)
    {

    }

    /**
     * @return void
     */
    public function clearCache()
    {
        $this->countCache = array();
    }

    /**
     * @param array $manipulation
     * @return bool
     */
    public function manipulate($manipulation)
    {
        return null;
    }

    /**
     * @return mixed
     */
    public function getFetchMode()
    {
        return $this->fetchMode;
    }

    /**
     * @param mixed $fetchMode
     * @return $this
     */
    public function setFetchMode($fetchMode)
    {
        if($fetchMode != self::FETCH_MODE_GROUP && $fetchMode != self::FETCH_MODE_SINGLE) {
            throw new InvalidArgumentException("Fetch-Mode not allowed.");
        }

        $this->fetchMode = $fetchMode;
        return $this;
    }

    /**
     * @return Uploads
     */
    public function getUpload()
    {
        return $this->upload;
    }

    /**
     * @param $version
     * @param array $filter
     * @param array $sort
     * @param array $limit
     * @param array $joins
     * @param bool $forceClasses
     * @return SelectQuery
     */
    public function buildExtendedQuery($version, $filter = array(), $sort = array(), $limit = array(), $joins = array(), $forceClasses = true)
    {
        throw new LogicException("This datasource does not support building queries.");
    }
}
