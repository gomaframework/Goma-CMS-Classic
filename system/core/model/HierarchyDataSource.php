<?php

defined("IN_GOMA") or die();

/**
 * datasource for hierarchy queries.
 *
 * @package        Goma\Model
 *
 * @author        Goma-Team
 * @license        GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @version     1.1
 */
class HierarchyDataSource implements IDataObjectSetDataSource
{
    /**
     * @var IDataObjectSetDataSource
     */
    protected $dataSource;

    /**
     * GroupedDataObjectSetModelSource constructor.
     * @param \IDataObjectSetDataSource $datasource
     */
    public function __construct($datasource)
    {
        $this->dataSource = $datasource;
    }

    /**
     * @param string $version
     * @param array $filter
     * @param array $join
     */
    protected function updateParams(&$version, &$filter, &$join) {
        $join[$this->getHierarchyTable()] = array(
            DataObject::JOIN_TYPE        => "INNER",
            DataObject::JOIN_TABLE       => $this->getHierarchyTable(),
            DataObject::JOIN_STATEMENT   => $this->getHierarchyTable().".id = ".$this->baseTable()."_state.id",
            DataObject::JOIN_INCLUDEDATA => false
        );

        if($version == DataObject::VERSION_STATE) {
            $filter = array($filter, $this->getHierarchyTable() . ".state = 1");
        } else {
            $filter = array($filter, $this->getHierarchyTable() . ".state = 2");
        }
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
    public function getRecords(
        $version,
        $filter = array(),
        $sort = array(),
        $limit = array(),
        $joins = array(),
        $search = array()
    ) {
        $this->updateParams($version, $filter, $joins);

        return $this->dataSource->getRecords($version, $filter, $sort, $limit, $joins, $search);
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
    public function getAggregate(
        $version,
        $aggregate,
        $aggregateField = "*",
        $distinct = false,
        $filter = array(),
        $sort = array(),
        $limit = array(),
        $joins = array(),
        $search = array(),
        $groupby = array()
    ) {
        $this->updateParams($version, $filter, $joins);

        return $this->dataSource->getAggregate($version, $aggregate, $aggregateField, $distinct, $filter, $sort, $limit, $joins, $search, $groupby);
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
    public function getGroupedRecords(
        $version,
        $groupField,
        $filter = array(),
        $sort = array(),
        $limit = array(),
        $joins = array(),
        $search = array()
    ) {
        $this->updateParams($version, $filter, $joins);

        return $this->dataSource->getGroupedRecords($version, $groupField, $filter, $sort, $limit, $joins, $search);
    }

    /**
     * @param string $field
     * @return bool
     */
    public function canFilterBy($field)
    {
        return $this->dataSource->canFilterBy($field);
    }

    /**
     * @param string $field
     * @return bool
     */
    public function canSortBy($field)
    {
        if(strpos($field, ".") !== false) {
            if(strtolower(substr($field, 0, strpos($field, "."))) == $this->getHierarchyTable()) {
                $fieldName = substr($field,  strpos($field, ".") + 1);
                if(isset(ClassInfo::$database[$this->getHierarchyTable()][$fieldName])) {
                    return true;
                }
            }
        }

        return $this->dataSource->canSortBy($field);
    }

    /**
     * @return string
     */
    public function DataClass()
    {
        return $this->dataSource->DataClass();
    }

    /**
     * @return string
     */
    public function getInExpansion()
    {
        return $this->dataSource->getInExpansion();
    }

    /**
     * @return string
     */
    public function table()
    {
        return $this->dataSource->table();
    }

    /**
     * @return string
     */
    public function baseTable()
    {
        return $this->dataSource->baseTable();
    }

    /**
     * @return void
     */
    public function clearCache()
    {
        $this->dataSource->clearCache();
    }

    /**
     * @param Closure $closure
     * @return Closure
     */
    public function registerCacheCallback($closure) {
        return $this->dataSource->registerCacheCallback($closure);
    }


    /**
     * @param array $manipulation
     * @return bool
     */
    public function manipulate($manipulation)
    {
        return $this->dataSource->manipulate($manipulation);
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
    public function buildExtendedQuery(
        $version,
        $filter = array(),
        $sort = array(),
        $limit = array(),
        $joins = array(),
        $forceClasses = true
    ) {
        $this->updateParams($version, $filter, $joins);

        return $this->dataSource->buildExtendedQuery($version, $filter, $sort, $limit, $joins, $forceClasses);
    }

    /**
     * @return string
     */
    protected function getHierarchyTable() {
        return $this->baseTable() . "_hierarchy";
    }
}