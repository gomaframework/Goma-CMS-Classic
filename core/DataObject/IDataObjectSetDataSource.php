<?php
defined("IN_GOMA") OR die();

/**
 * interface for data fetcher for DataObjectSet.
 *
 * @package Goma
 *
 * @author Goma-Team
 * @copyright 2016 Goma-Team
 *
 * @version 1.0
 */
interface IDataObjectSetDataSource {
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
    public function getRecords($version, $filter = array(), $sort = array(), $limit = array(), $joins = array(), $search = array());

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
    public function getAggregate($version, $aggregate, $aggregateField = "*", $distinct = false, $filter = array(), $sort = array(), $limit = array(), $joins = array(), $search = array(), $groupby = array());

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
    public function getGroupedRecords($version, $groupField, $filter = array(), $sort = array(), $limit = array(), $joins = array(), $search = array());

    /**
     * @param string $field
     * @return bool
     */
    public function canFilterBy($field);

    /**
     * @param string $field
     * @return bool
     */
    public function canSortBy($field);

    /**
     * @return string
     */
    public function DataClass();

    /**
     * @return string
     */
    public function getInExpansion();

    /**
     * @return string
     */
    public function table();

    /**
     * @return string
     */
    public function baseTable();

    /**
     * @return void
     */
    public function clearCache();

    /**
     * @param Closure $closure
     * @return Closure
     */
    public function registerCacheCallback($closure);

    /**
     * @param array $manipulation
     * @return bool
     */
    public function manipulate($manipulation);

    /**
     * @param $version
     * @param array $filter
     * @param array $sort
     * @param array $limit
     * @param array $joins
     * @param bool $forceClasses
     * @return SelectQuery
     */
    public function buildExtendedQuery($version, $filter = array(), $sort = array(), $limit = array(), $joins = array(), $forceClasses = true);
}

interface IFormForModelGenerator {
    /**
    * @param Form $form
    */
    public function getForm(&$form);

    /**
     * @param Form $form
     */
    public function getEditForm(&$form);

    /**
     * @param Form $form
     * @internal
     */
    public function getActions(&$form);
}

interface IDataObjectSetModelSource {
    /**
     * @param array $data
     * @return ViewAccessableData
     */
    public function createNew($data = array());

    /**
     * @return string
     */
    public function DataClass();
}
