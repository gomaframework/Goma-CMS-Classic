<?php
defined("IN_GOMA") OR die();

/**
 * This extension is used to have a better performance
 * when using parent and child-relationships.
 * It preserves a tree of published and state record hierarchy.
 * It provides features like "AllChildren", "getAllParents"
 * It has a table in the following format:
 * - id
 * - parentid
 * - height (distance to root of parentid)
 * - state (2 for published, 1 for state)
 *
 * Each record has at least itself as published and state record, which will be for root (ID=1):
 * (id, parentid, height, state)
 * (1, 1, 0, 2),
 * (1, 1, 0, 1)
 *
 * Additionally per parent it has an additional record, for example if 5 is root and 6 is child:
 * (id, parentid, height, state)
 * (5, 5, 0, 2),
 * (5, 5, 0, 1),
 * (6, 5, 0, 2),
 * (6, 5, 0, 1),
 * (6, 6, 1, 2),
 * (6, 6, 1, 1)
 *
 *
 * @package        Goma\Model
 * @method  DataObject getOwner()
 *
 * @author        Goma-Team
 * @license        GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @version     1.1
 */
class Hierarchy extends DataObjectExtension
{
    /**
     * extra-methods
     */
    protected static $extra_methods = array(
        "getAllChildren",
        "getAllParents",
        "getAllChildVersionIDs",
        "getAllChildrenIds",
        "getAllParentIds"
    );

    /**
     * has-one-extension
     */
    public static function has_one($class)
    {
        if (strtolower(get_parent_class($class)) != "dataobject") {
            return array();
        }

        return array(
            "parent" => array(
                DataObject::RELATION_TARGET => $class,
                DataObject::CASCADE_TYPE => DataObject::CASCADE_TYPE_UPDATEFIELD
            )
        );
    }

    /**
     * has-many-extension
     */
    public static function has_many($class)
    {
        if (strtolower(get_parent_class($class)) != "dataobject") {
            return array();
        }

        return array(
            "Children" => $class,
        );
    }

    /**
     * gets all children and subchildren to a record
     *
     * @param array|string|null $filter
     * @param array|string|null $sort
     * @param int|array|null $limit
     * @return DataObjectSet
     * @internal param $AllChildren
     */
    public function getAllChildren($filter = null, $sort = null, $limit = null)
    {
        $data = DataObject::get(
            $this->getOwner()->classname,
            array_merge(
                (array)$filter,
                array(
                    $this->getHierarchyTable() . ".parentid" => $this->getOwner()->id,
                    $this->getHierarchyTable() . ".id" => array("!=", $this->getOwner()->id)
                )
            ),
            array(),
            $limit
        );
        $dataSource = new HierarchyDataSource($data->getDbDataSource());
        $data->setDbDataSource($dataSource);
        $data->sort($sort);
        return $data;
    }

    /**
     * returns a dataset of all parents
     *
     * @param null $filter
     * @param null $sort
     * @param null $limit
     * @return DataObjectSet
     */
    public function getAllParents($filter = null, $sort = null, $limit = null)
    {
        if (!isset($sort)) {
            $sort = array($this->getHierarchyTable() . ".height" => "DESC");
        }

        $data = DataObject::get(
            $this->getOwner()->classname,
            array_merge(
                (array)$filter,
                array(
                    $this->getHierarchyTable() . ".id" => $this->getOwner()->id,
                    $this->getHierarchyTable() . ".parentid" => array("!=", $this->getOwner()->id)
                )
            ),
            array(),
            $limit
        );
        $dataSource = new HierarchyDataSource($data->getDbDataSource());
        $data->setDbDataSource($dataSource);
        $data->sort($sort);
        return $data;
    }

    /**
     * @param string $field
     * @param array $filter
     * @param null $version
     * @param null|callable $modifyQuery
     * @return array
     * @throws SQLException
     */
    public function queryIds($field, $filter, $version = null, $modifyQuery = null) {
        if($version !== false) {
            $filter = array(
                array("state" => ($version == DataObject::VERSION_STATE) ? 1 : 2),
                $filter
            );
        }
        $query = new SelectQuery(
            $this->getHierarchyTable(),
            array($field),
            $filter
        );
        if($modifyQuery) {
            $newQuery = call_user_func_array($modifyQuery, array($query));
            if(isset($newQuery)) {
                $query = $newQuery;
            }
        }
        $query->sort("height", "ASC");
        $result = $query->execute();
        $ids = array();
        while ($row = $result->fetch_assoc()) {
            $ids[$row[$field]] = $row[$field];
        }

        return array_values($ids);
    }

    /**
     * @param string|null|false $version false means state AND published children are returned
     * @param null|int $recordId if to search for children of different record
     * @param bool $includeSelf
     * @return array
     * @throws SQLException
     */
    public function getAllChildrenIds($version = null, $recordId = null, $includeSelf = false)
    {
        $filter = array(
            "parentid" => $recordId ? $recordId : $this->getOwner()->id
        );
        if(!$includeSelf) {
            $filter["id"] = array("!=", $recordId ? $recordId : $this->getOwner()->id);
        }
        return $this->queryIds("id", $filter, $version);
    }

    /**
     * @param null|int $version
     * @return array
     * @throws SQLException
     */
    public function getAllChildVersionIDs($version = null) {
        $filter = array(
            "parentid" => $this->getOwner()->id,
            "id" => array("!=", $this->getOwner()->id)
        );
        $field = ($version == DataObject::VERSION_STATE) ? "stateid" : "publishedid";
        return $this->queryIds($field, $filter, $version, function($query) {
            $baseTableState = $this->getOwner()->baseTable . "_state";
            /** @var SelectQuery $query */
            $query->join("INNER", $baseTableState, $baseTableState . ".id = " . $this->getHierarchyTable() . ".id");
        });
    }

    /**
     * @param string|null|false $version false means state AND published children are returned
     * @param null|int $recordId if to search for children of different record
     * @param bool $includeSelf
     * @return array
     * @throws SQLException
     */
    public function getAllParentIds($version = null, $recordId = null, $includeSelf = false)
    {
        $filter = array(
            "id" => $recordId ? $recordId : $this->getOwner()->id
        );
        if(!$includeSelf) {
            $filter["id"] = array("!=", $recordId ? $recordId : $this->getOwner()->id);
        }
        return $this->queryIds("parentid", $filter, $version);
    }

    /**
     * recreate tree before writing.
     *
     * @param ModelWriter $modelWriter
     * @param array $manipulation
     * @param string $job
     * @throws SQLException
     */
    public function onBeforeManipulate($modelWriter, &$manipulation, $job)
    {
        if ($job == "write" && isset(ClassInfo::$database[$this->getHierarchyTable()])) {
            $isVersioned = DataObject::Versioned($this->getOwner());
            $isPublish = !$isVersioned || $modelWriter->getWriteType() == IModelRepository::WRITE_TYPE_PUBLISH;

            // update and delete query only required when updating object
            if($modelWriter->getObjectToUpdate() !== null) {
                $manipulation["tree_table_truncate"] = array(
                    "command"    => "delete",
                    "table_name" => $this->getHierarchyTable(),
                    "where"      => array(),
                );
                if($isPublish) {
                    $manipulation["tree_table_update_height_publish"] = array(
                        "command" => "rawupdate"
                    );
                }

                $manipulation["tree_table_update_height_state"] = array(
                    "command" => "rawupdate"
                );
            }

            $manipulation["tree_table"] = array(
                "command"    => "insert",
                "table_name" => $this->getHierarchyTable(),
                "fields"     => array(),
            );
            $oldParentId = $modelWriter->getObjectToUpdate() && $modelWriter->getObjectToUpdate()->parent ?
                $modelWriter->getObjectToUpdate()->parent->id : null;
            $parentId = $this->getOwner()->parent ? $this->getOwner()->parent->id : 0;

            $parentsPublish = $this->getAllParentIds(null, $parentId, true);
            $parentsState = $isVersioned ? $this->getAllParentIds(DataObject::VERSION_STATE, $parentId, true) : $parentsPublish;

            // insert new information when creating new object
            if($modelWriter->getObjectToUpdate() !== null) {
                $oldParentsPublished = $this->getAllParentIds(null, $oldParentId, true);
                $oldParentsState = $isVersioned ? $this->getAllParentIds(DataObject::VERSION_STATE, $oldParentId, true) : $oldParentsPublished;
                $allChildrenPublished = $this->getAllChildrenIds();
                $allChildrenState = $isVersioned ? $this->getAllChildrenIds(DataObject::VERSION_STATE) : $allChildrenPublished;

                $allChildrenPublishedAndMe = array_merge($allChildrenPublished, array($this->getOwner()->id));
                $allChildrenStateAndMe = array_merge($allChildrenState, array($this->getOwner()->id));

                // delete old hierarchy
                $manipulation["tree_table_truncate"]["where"] = array(
                    array(
                        "id" => array_merge($allChildrenState, array($this->getOwner()->id)),
                        "parentid" => $oldParentsState,
                        "state" => 1
                    ),
                );
                if($isPublish) {
                    $manipulation["tree_table_truncate"]["where"][] = "OR";
                    $manipulation["tree_table_truncate"]["where"][] = array(
                        "id" => $allChildrenPublishedAndMe,
                        "parentid" => $oldParentsPublished,
                        "state" => 2
                    );
                }

                // Update child hierarchy "height" fields to reflect new heights
                $heightDiffPublish = count($oldParentsPublished) - count($parentsPublish);
                $heightDiffState = count($oldParentsState) - count($parentsState);

                if($isPublish) {
                    $manipulation["tree_table_update_height_publish"]["sql"] = "UPDATE ".DB_PREFIX.$this->getHierarchyTable(
                        )." SET height = height + ".$heightDiffPublish." ".SQL::extractToWhere(
                            array(
                                "id"    => $allChildrenPublishedAndMe,
                                "state" => 2
                            )
                        );
                }

                $manipulation["tree_table_update_height_state"]["sql"] =
                    "UPDATE " . DB_PREFIX . $this->getHierarchyTable() .
                    " SET height = height + " . $heightDiffState .
                    " " . SQL::extractToWhere(array(
                        "id" => $allChildrenStateAndMe,
                        "state" => 1
                    ));

                // Insert all new parents including child hierarchies
                if($isPublish) {
                    $this->generateManipulationForParents($parentsPublish, 2, $allChildrenPublished, $manipulation);
                }

                $this->generateManipulationForParents($parentsState, 1, $allChildrenState, $manipulation);
            }

            // insert my hierarchy (again if update)
            if($isPublish) {
                $this->generateManipulationForParentsAndMe($parentsPublish, 2, $manipulation);
            }
            $this->generateManipulationForParentsAndMe(
                $parentsState,
                1,
                $manipulation
            );
        }
    }

    /**
     * @param int[] $parents
     * @param int $state
     * @param int[] $records
     * @param array $manipulation
     */
    protected function generateManipulationForParents($parents, $state, $records, &$manipulation) {
        foreach($records as $recordId) {
            foreach($parents as $sort => $parent) {
                $manipulation["tree_table"]["fields"][] = array(
                    "id" => $recordId,
                    "parentid" => $parent,
                    "height" => $sort,
                    "state" => $state
                );
            }
        }
    }

    /**
     * @param int[] $parents
     * @param int $state
     * @param array $manipulation
     */
    protected function generateManipulationForParentsAndMe($parents, $state, &$manipulation) {
        $this->generateManipulationForParents($parents, $state, array($this->getOwner()->id), $manipulation);
        $manipulation["tree_table"]["fields"][] = array(
            "id" => $this->getOwner()->id,
            "parentid" => $this->getOwner()->id,
            "height" => count($parents),
            "state" => $state
        );
    }

    /**
     * before removing data
     * @param array $manipulation
     * @throws SQLException
     */
    public function onBeforeRemove(&$manipulation)
    {
        if (!DataObject::versioned($this->getOwner()->classname) && isset(
                ClassInfo::$database[$this->getHierarchyTable()]
            )) {
            $manipulation["delete_tree"] = array(
                "table"   => $this->getHierarchyTable(),
                "command" => "delete",
                "where"   => array(
                    "id" => array_merge(
                        array($this->getOwner()->id),
                        $this->getAllChildrenIds(false)
                    )
                ),
            );
        }
    }

    /**
     * generates some ClassInfo
     * @param bool $force
     */
    public function generateClassInfo($force = false)
    {
        if ($force || defined("SQL_LOADUP") && $this->getOwner() && SQL::getFieldsOfTable($this->getHierarchyTable())) {
            ClassInfo::$database[$this->getHierarchyTable()] = array(
                "id"       => "int(10)",
                "parentid" => "int(10)",
                "height"   => "int(10)",
                "state"    => "int(1)"
            );
        }
    }

    /**
     * @return string
     */
    public function getHierarchyTable() {
        return $this->getOwner()->baseTable."_hierarchy";
    }

    /**
     * build a seperate tree-table
     *
     * @param $prefix
     * @param $log
     * @return void
     * @throws SQLException
     */
    public function buildDB($prefix, &$log)
    {

        if (strtolower(get_parent_class($this->getOwner()->classname)) != "dataobject") {
            return;
        }

        $migrate = !SQL::getFieldsOfTable($this->getHierarchyTable());

        $log .= SQL::requireTable(
            $this->getHierarchyTable(),
            array(
                "id"       => "int(10)",
                "parentid" => "int(10)",
                "height"   => "int(10)",
                "state"    => "int(1)"
            ),
            array(),
            array(),
            $prefix
        );

        $this->generateClassInfo(true);

        if ($migrate !== false) {
            $sql = "SELECT b.recordid, b.parentid, b.id as versionid, s.stateid, s.publishedid FROM ".
                $prefix.$this->getOwner()->baseTable." b,  ".
                $prefix.$this->getOwner()->baseTable."_state s WHERE s.stateid = b.id OR s.publishedid = b.id ORDER BY s.id DESC";
            $directStateParents = array();
            $directPublishedParents = array();

            $i = 0;
            if ($result = SQL::query($sql)) {
                while ($row = SQL::fetch_object($result)) {
                    if($row->stateid == $row->versionid) {
                        if (!isset($directStateParents[$row->recordid])) {
                            $directStateParents[$row->recordid] = $row->parentid;
                        }
                    }

                    if($row->publishedid == $row->versionid) {
                        if (!isset($directPublishedParents[$row->recordid])) {
                            $directPublishedParents[$row->recordid] = $row->parentid;
                        }
                    }

                    $i++;
                }
            } else {
                throw new SQLException();
            }

            if (count($directPublishedParents) > 0) {
                $insert = "INSERT INTO ".$prefix.$this->getHierarchyTable()." (id, parentid, height, state) VALUES ";

                $firstLine = true;
                $insert .= $this->buildInsert($directStateParents, 1, $firstLine);
                $insert .= $this->buildInsert($directPublishedParents, 2, $firstLine);

                if (!SQL::Query($insert)) {
                    throw new SQLException();
                }
            }
        }
    }

    /**
     * @param array$parentArray
     * @param int $state
     * @param bool $firstLine
     * @return string
     */
    protected function buildInsert($parentArray, $state, &$firstLine)
    {
        $insert = "";
        foreach ($parentArray as $recordId => $parent) {
            if ($firstLine) {
                $firstLine = false;
            } else {
                $insert .= ", ";
            }

            // calc height
            $publishedHeight = 0;
            $tid = $parent;
            while (isset($parentArray[$tid])) {
                $tid = $parentArray[$tid];
                $publishedHeight++;
            }

            $parentid = ((int)$parent != 0) ? (int) $parent : $recordId;
            $insert .= "(".$recordId.", ".(int)$parentid.", $publishedHeight, $state)";
            $tid = $parent;
            while (isset($parentArray[$tid])) {
                $tid = $parentArray[$tid];
                $publishedHeight--;
                $parentid = ((int)$tid != 0) ? (int) $tid : $recordId;
                $insert .= ",(".$recordId.", ".$parentid.", $publishedHeight, $state)";
            }
        }

        return $insert;
    }
}
