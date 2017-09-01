<?php
defined("IN_GOMA") OR die();

/**
 * Describe your class
 *
 * @package tufast
 *
 * @author D
 * @copyright 2017 D
 *
 * @version 1.0
 */
class VersionsDataObjectExtension extends DataObjectExtension {

    /**
     * @var array
     */
    static $extra_methods = array(
        "versions", "versionsASC"
    );

    /**
     * @var DataObjectSet
     */
    protected $versionCache;

    /**
     * @var DataObjectSet
     */
    protected $versionAscCache;

    /**
     * gets versions of this ordered by time DESC
     *
     * @param array $where
     * @return array|DataObjectSet
     */
    public function versions($where = null) {
        if(!$this->versionCache) {
            $this->versionCache = DataObject::get_versioned($this->getOwner()->classname, false, array(
                "recordid"	=> $this->getOwner()->recordid
            ),  array("versionid", "desc"));
        }

        if($where) {
            $cache = clone $this->versionCache;
            $cache->addFilter($where);
            return $cache;
        }

        return $this->versionCache;
    }

    /**
     * gets versions of this ordered by time ASC
     *
     * @param array|null $where
     * @return array|DataObjectSet
     */
    public function versionsASC($where = null) {
        if(!$this->versionAscCache) {
            $this->versionAscCache = DataObject::get_versioned($this->getOwner()->classname, false, array(
                "recordid"	=> $this->getOwner()->recordid
            ),  array("versionid", "asc"));
        }

        if($where) {
            $cache = clone $this->versionAscCache;
            $cache->addFilter($where);
            return $cache;
        }

        return $this->versionAscCache;
    }
}

gObject::extend(DataObject::class, VersionsDataObjectExtension::class);
