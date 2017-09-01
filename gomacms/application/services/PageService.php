<?php
defined("IN_GOMA") OR die();

/**
 * provides loader functions.
 *
 * @package Goma CMS
 *
 * @author Goma-Team
 * @copyright 2017 Goma-Team
 *
 * @version 1.0
 */
class PageService extends \Goma\Service\DefaultControllerService {
    /**
     * get by filter versioned + fallback.
     * @param array $filter
     * @param bool $state
     * @return Pages|null
     */
    public function getPageWithState($filter, $state = false) {
        $set = DataObject::get(Pages::class, $filter);
        if($state) {
            $set->setVersion(DataObject::VERSION_STATE);
            if(!$set->first() || !$set->first()->can("write")) {
                $set->setVersion(DataObject::VERSION_PUBLISHED);
            }
        }

        return $set->first();
    }
}
