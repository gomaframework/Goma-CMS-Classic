<?php
namespace Goma\Model\Group;
defined("IN_GOMA") OR die();

/**
 * A single group of records.
 *
 * @package Goma\Model\Group
 *
 * @license     GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author Goma-Team
 * @copyright 2016 Goma-Team
 *
 * @version 1.0
 */
class GroupDataObjectSet extends \DataObjectSet {
    /**
     * @var array
     */
    protected $groupFilter = array();

    /**
     * @return array
     */
    public function getGroupFilter()
    {
        return $this->groupFilter;
    }

    /**
     * @param array $groupFilter
     * @return $this
     */
    public function setGroupFilter($groupFilter)
    {
        $this->groupFilter = $groupFilter;
        return $this;
    }

    /**
     * sets first cache.
     * @param array $data
     * @return $this
     */
    public function setFirstCache($data) {
        $this->firstCache = $this->getConverted($data);
        return $this;
    }

    /**
     * @return array
     */
    protected function getFilterForQuery()
    {
        $filter = parent::getFilterForQuery();
        if($filter) {
            return array(parent::getFilterForQuery(), $this->groupFilter);
        }

        return $this->groupFilter;
    }
}
