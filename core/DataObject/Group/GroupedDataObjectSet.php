<?php
namespace Goma\Model\Group;

use ManyMany_DataObjectSet;
use SelectQuery;
use ViewAccessableData;

defined("IN_GOMA") OR die();

/**
 * Set of grouped records.
 *
 * @package Goma\Model\Group
 *
 * @license     GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author Goma-Team
 * @copyright 2016 Goma-Team
 *
 * @version 1.0
 */
class GroupedDataObjectSetDataSource implements \IDataObjectSetDataSource {
    /**
     * original datasource.
     *
     * @var \IDataObjectSetDataSource
     */
    protected $datasource;

    /**
     * model source.
     *
     * @var \IDataObjectSetModelSource
     */
    protected $modelSource;

    /**
     * @var string|array
     */
    protected $groupField;

    /**
     * GroupedDataObjectSetModelSource constructor.
     * @param \IDataObjectSetDataSource $datasource
     * @param \IDataObjectSetModelSource $modelSource
     * @param string|array $groupField
     */
    public function __construct($datasource, $modelSource, $groupField)
    {
        $this->datasource = $datasource;
        $this->modelSource = $modelSource;
        $this->groupField = $groupField;
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
        return $this->datasource->getGroupedRecords($version, $this->groupField, $filter, $sort, $limit, $joins, $search);
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
        if(is_string($aggregate)) {
            if($aggregateField == "*") {
                $aggregateField = $this->groupField;
            }
        } else {
            foreach($aggregate as $singleAggregate) {
                if(strtolower($singleAggregate) == "count") {
                    $countAggregate = $singleAggregate;
                    $count = $this->datasource->getAggregate($version, $singleAggregate, $this->groupField, true, $filter, $sort, $limit, $joins, $search, $groupby);
                }
            }
        }

        $data = $this->datasource->getAggregate($version, $aggregate, $aggregateField, true, $filter, $sort, $limit, $joins, $search, $groupby);
        if(isset($count, $countAggregate)) {
            $data[$countAggregate] = $count;
        }

        return $data;
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
        return new \DataObjectSet(
            array(
                new GroupedDataObjectSetDataSource($this->datasource, $this->modelSource, $groupField),
                $this->modelSource
            )
        );
    }

    /**
     * @param string $field
     * @return bool
     */
    public function canFilterBy($field)
    {
        return $this->datasource->canFilterBy($field);
    }

    /**
     * @param string $field
     * @return bool
     */
    public function canSortBy($field)
    {
        return $this->datasource->canSortBy($field);
    }

    /**
     * @return string
     */
    public function DataClass()
    {
        return $this->datasource->DataClass();
    }

    /**
     * @return string
     */
    public function getInExpansion()
    {
        return $this->datasource->getInExpansion();
    }

    /**
     * @return string
     */
    public function table()
    {
        return $this->datasource->table();
    }

    /**
     * @return string
     */
    public function baseTable()
    {
        return $this->datasource->baseTable();
    }

    /**
     * @param array $manipulation
     * @param ManyMany_DataObjectSet $set
     * @param array $writeData array of versionid => boolean
     * @return mixed
     */
    public function onBeforeManipulateManyMany(&$manipulation, $set, $writeData)
    {
        return $this->datasource->onBeforeManipulateManyMany($manipulation, $set, $writeData);
    }

    /**
     * @return void
     */
    public function clearCache()
    {
        $this->datasource->clearCache();
    }

    /**
     * @param array $manipulation
     * @return bool
     */
    public function manipulate($manipulation)
    {
        return $this->datasource->manipulate($manipulation);
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
        $query = $this->datasource->buildExtendedQuery($version, $filter, $sort, $limit, $joins, $forceClasses);
        $query->groupby($this->groupField);
        return $query;
    }

    /**
     * @return \IDataObjectSetDataSource
     */
    public function getDatasource()
    {
        return $this->datasource;
    }

    /**
     * @return \IDataObjectSetModelSource
     */
    public function getModelSource()
    {
        return $this->modelSource;
    }

    /**
     * @return array|string
     */
    public function getGroupField()
    {
        return $this->groupField;
    }

    /**
     * @param array|string $groupField
     * @return $this
     */
    public function setGroupField($groupField)
    {
        $this->groupField = $groupField;
        return $this;
    }
}
