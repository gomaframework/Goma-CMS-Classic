<?php
defined("IN_GOMA") OR die();

/**
 * Sortable Set.
 *
 * @package Goma-Team
 *
 * @author Goma-Team
 * @copyright 2016 Goma-Team
 * @license     GNU Lesser General Public License, version 3; see "LICENSE.txt"
 *
 * @version 1.0
 */
interface SortableDataObjectSet extends IDataSet {
    /**
     * moves item to given position.
     *
     * @param DataObject $item
     * @param int $position
     * @return mixed
     */
    public function move($item, $position);

    /**
     * sets sort by array of ids.
     *
     * @param int[]
     */
    public function setSortByIdArray($ids);

    /**
     * uasort.
     *
     * @param Callable
     */
    public function sortCallback($callback);
}