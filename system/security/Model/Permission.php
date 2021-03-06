<?php defined("IN_GOMA") OR die();

/**
 * this class provides some methods to check permissions of the current activated group or user
 *
 * @package     goma framework
 * @link        http://goma-cms.org
 * @license:    LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 * @author      Goma-Team
 * @version     2.2.1
 *
 * @property int parentid
 * @property string forModel
 * @property int id
 * @property string type
 * @property string password
 * @property string name
 * @property ManyMany_DataObjectSet groups
 * @property Permission|null parent
 * @method ManyMany_DataObjectSet Groups($filter = null, $sort = null)
 * @method HasMany_DataObjectSet Children($filter = null, $sort = null)
 */
class Permission extends DataObject
{
    /**
     * disable sort
     * @var bool
     */
    public static $default_sort = false;

    /**
     * defaults
     */
    static $default = array(
        "type" => "admins"
    );

    /**
     * all permissions, which are available in this object
     */
    public static $providedPermissions = array(
        "superadmin" => array(
            "title" => '{$_lang_full_admin_permissions}',
            "default" => array(
                "type" => "admins"
            ),
            "description" => '{$_lang_full_admin_permissions_info}'
        )
    );

    /**
     * cache for reordered permissions
     */
    static $reorderedPermissions;

    /**
     * fields of this set
     */
    static $db = array(
        "name" => "varchar(100)",
        "type" => "enum('all', 'users', 'admins', 'password', 'groups')",
        "password" => "varchar(100)",
        "forModel" => "varchar(100)"
    );

    static $search_fields = array(
        "name"
    );

    /**
     * groups-relation of this set
     */
    static $many_many = array(
        "groups" => "group"
    );

    /**
     * indexes
     */
    static $index = array(
        "name" => "INDEX"
    );

    /**
     * extensions for this class
     */
    static $extend = array(
        "Hierarchy"
    );

    /**
     * perm-cache
     */
    private static $perm_cache = array();

    /**
     * adds available Permission-groups
     * @param $perms
     */
    public static function addPermissions($perms)
    {
        self::$providedPermissions = ArrayLib::map_key("strtolower", array_merge(self::$providedPermissions, $perms), false);
    }

    /**
     * reorders all permissions as in hierarchy
     *
     * @return array
     */
    public static function reorderedPermissions()
    {
        if (isset(self::$reorderedPermissions)) {
            return self::$reorderedPermissions;
        }

        $perms = array();
        foreach (self::$providedPermissions as $name => $data) {
            if (!isset($data["category"]) && $name != "superadmin") {
                $perms[$name] = $data;
                // get children
                if ($children = self::reorderedPermissionsHelper($name)) {
                    $perms[$name]["children"] = $children;
                }
            }
        }

        $perms = array(
            "superadmin" => array_merge(self::$providedPermissions["superadmin"], array(
                "children" => array_merge($perms, self::reorderedPermissionsHelper("superadmin")),
                "forceSubOn1" => true
            ))
        );

        self::$reorderedPermissions = $perms;
        return $perms;

    }

    /**
     * helper which gets all children for given permission
     *
     * @return array
     */
    protected static function reorderedPermissionsHelper($perm)
    {
        $perms = array();
        $perm = strtolower($perm);
        foreach (self::$providedPermissions as $name => $data) {
            // get children for given perm
            if (isset($data["category"]) && strtolower($data["category"]) == $perm) {
                $perms[$name] = $data;

                // get children for current subperm
                if ($children = self::reorderedPermissionsHelper($name)) {
                    $perms[$name]["children"] = $children;
                }
            }
        }
        return $perms;
    }

    /**
     * checks if current user defined in Member::$loggedIn has given permission.
     *
     * @param string $permissionCode
     * @return bool
     * @throws PermissionException
     * @throws SQLException
     */
    public static function check($permissionCode)
    {
        if (!defined("SQL_INIT"))
            return true;

        if(Member::$loggedIn != null) {
            return Member::$loggedIn->hasPermissions($permissionCode);
        }

        if (RegexpUtil::isNumber($permissionCode)) {
            return $permissionCode < 2;
        }

        return false;
    }

    /**
     * forces that a specific permission exists
     *
     * @return Permission
     * @throws PermissionException
     * @throws SQLException
     * @throws FormInvalidDataException
     */
    public static function forceExisting($r)
    {
        $r = strtolower(trim($r));
        if (isset(self::$providedPermissions[$r])) {
            /** @var Permission $data */
            if ($data = DataObject::get_one(self::class, array("name" => array("LIKE", $r)))) {
                return $data;
            } else {
                if (isset(self::$providedPermissions[$r]["default"]["inherit"]) && strtolower(self::$providedPermissions[$r]["default"]["inherit"]) != $r) {
                    if ($data = self::forceExisting(self::$providedPermissions[$r]["default"]["inherit"])) {
                        $perm = clone $data;
                        $perm->consolidate();
                        $perm->id = 0;
                        $perm->parentid = 0;
                        $perm->name = $r;
                        $data->forModel = "permission";
                        self::$perm_cache[$r] = $perm->hasPermission();
                        $perm->writeToDB(true, true, 2, false, false);
                        return self::$perm_cache[$r];
                    }
                }
                $defaultInfo = isset(self::$providedPermissions[$r]["default"]) ? (array) self::$providedPermissions[$r]["default"] : array();
                $perm = new Permission(array_merge($defaultInfo, array("name" => $r)));

                if (isset(self::$providedPermissions[$r]["default"]["type"])) {
                    $perm->setType(self::$providedPermissions[$r]["default"]["type"]);
                }

                $perm->writeToDB(true, true, 2, false, false);

                return $perm;
            }
        } else {
            return null;
        }
    }

    /**
     * setting the parent-id
     * @param int $parentid
     */
    public function setParentID($parentid)
    {
        $this->setField("parentid", $parentid);
        /** @var Permission $perm */
        if ($this->parentid != 0 && $perm = DataObject::get_by_id(self::class, $this->parentid)) {
            if ($this->hasChanged()) {
                $this->type = $perm->type;
                $this->password = $perm->password;
                if ($this->type == "groups") {
                    $this->groupsids = $perm->groupsids;
                }
            }
        } else {
            $this->parentid = 0;
        }
    }

    /**
     * writing
     * @param ModelWriter $modelWriter
     * @throws FormInvalidDataException
     * @throws DataObjectSetCommitException
     */
    public function onBeforeWrite($modelWriter)
    {
        if ($this->parentid == $this->id) {
            $this->parentid = 0;
        }

        if ($this->name) {
            if ($this->type != "groups") {
                switch ($this->type) {
                    case "all":
                    case "users":
                        $this->groups()->addMany(DataObject::get("group"));
                        break;
                    case "admins":
                        $this->groups()->addMany(DataObject::get("group", array("type" => 2)));
                        break;
                }
                $this->groups = $this->groups();
                $this->type = "groups";
            }
        }

        if(!in_array($this->type, array("all", "users", "groups", "admins", "password"))) {
            throw new FormInvalidDataException("type", "Type of permission must be a valid type. " . $this->type . " given.");
        }

        if(isset($this->data["parent"]) && $this->data["parent"] != $this) {
            if(($mostParent = $this->getAllParents(array(
                "height" => 1
            ))->first()) === null) {
                $mostParent = $this->parent;
                while($mostParent->parent) {
                    $mostParent = $mostParent->parent;
                }
            }

            $this->type = $mostParent->type;
            $this->password = $mostParent->password;
            if ($this->type == "groups") {
                $this->groupsids = $mostParent->groupsids;
            }
        }

        parent::onBeforeWrite($modelWriter);
    }

    /**
     * on before manipulate
     * @param ModelWriter $modelWriter
     * @param array $manipulation
     * @param string $job
     */
    public function onBeforeManipulate($modelWriter, &$manipulation, $job)
    {
        parent::onBeforeManipulate($modelWriter, $manipulation, $job);

        if ($this->id != 0 && $job == "write") {
            $subversions = $this->getAllChildVersionIDs();
            if (count($subversions) > 0) {

                if ($this->type == "groups") {
                    $relationShip = $this->getManyManyInfo("groups");
                    $table = $relationShip->getTableName();
                    $manipulation["perm_groups_delete"] = array(
                        "table_name" => $table,
                        "command" => "delete",
                        "where" => array(
                            $relationShip->getOwnerField() => $subversions
                        )
                    );

                    $manipulation["perm_groups_insert"] = array(
                        "table_name" => $table,
                        "command" => "insert",
                        "ignore" => true,
                        "fields" => array()
                    );

                    if ($this->groupsids && count($this->groupsids) > 0) {
                        $i = 10000;
                        foreach ($subversions as $version) {
                            foreach ($this->groupsids as $groupid) {
                                if (is_array($groupid)) {
                                    $groupid = $groupid["versionid"];
                                }

                                $manipulation["perm_groups_insert"]["fields"][] = array(
                                    $relationShip->getOwnerField() => $version,
                                    $relationShip->getTargetField() => $groupid,
                                    $relationShip->getOwnerSortField()  => $i,
                                    $relationShip->getTargetSortField() => $i
                                );
                                $i++;
                            }
                        }
                    }
                }

                $manipulation["perm_update"] = array(
                    "command" => "update",
                    "table_name" => $this->baseTable,
                    "fields" => array(
                        "type" => $this->type,
                        "password" => $this->password
                    ),
                    "where" => array(
                        "id" => $subversions
                    )
                );
            }
        }
    }

    /**
     * on before manipulate many-many-relation
     *
     * @param array $manipulation
     * @param ManyMany_DataObjectSet $dataset
     * @param array $writeData
     * @return mixed|void
     * @access public
     */
    public function onBeforeManipulateManyMany(&$manipulation, $dataset, $writeData)
    {
        parent::onBeforeManipulateManyMany($manipulation, $dataset, $writeData);

        $ownValue = $dataset->getRelationOwnValue();
        $relationShip = $dataset->getRelationShip();

        $i = 10000;
        foreach ($writeData as $id => $bool) {
            if ($data = DataObject::get_one("Permission", array("versionid" => $id))) {
                foreach ($data->getAllChildVersionIDs() as $childVersionId) {
                    $manipulation[ManyMany_DataObjectSet::MANIPULATION_INSERT_NEW]["fields"][$childVersionId] = array(
                        $relationShip->getOwnerField() => $ownValue,
                        $relationShip->getTargetField() => $childVersionId,
                        $relationShip->getOwnerSortField()  => $i,
                        $relationShip->getTargetSortField() => $i
                    );

                    $i++;
                }
            }
        }
    }

    /**
     * sets the type
     * @param string $type
     */
    public function setType($type)
    {
        switch ($type) {
            case "all":
            case "every":
            case "everyone":
                $type = "all";
                break;

            case "group":
            case "groups":
                $type = "groups";
                break;

            case "admin":
            case "admins":
            case "root":
                $type = "admins";
                break;

            case "password":
                $type = "password";
                break;

            case "user":
            case "users":
                $type = "users";
                break;
        }

        $this->setField("type", $type);
    }

    /**
     * preserve Defaults
     *
     * @param mixed $prefix
     * @param $log
     * @return bool|void
     */
    public function preserveDefaults($prefix = DB_PREFIX, &$log)
    {
        parent::preserveDefaults($prefix, $log);

        foreach (self::$providedPermissions as $name => $data) {
            self::forceExisting($name);
        }
    }

    /**
     * checks if the current user has the permission to do this
     *
     * @param User $user
     * @return bool
     */
    public function hasPermission($user = null)
    {
        if (!defined("SQL_INIT")) {
            return true;
        }

        if ($this->type == "all") {
            return true;
        }

        if(!isset($user)) {
            if(!isset(Member::$loggedIn)) {
                return false;
            }

            $user = Member::$loggedIn;
        }

        if ($this->type == "users") {
            return true;
        }

        if ($this->type == "admins") {
            return ($user->getGroupType() > 1);
        }

        if ($this->type == "password") {

        }

        if ($this->type == "groups") {
            return $this->Groups(array(
                "id" => $user->groupIds()
            ))->count() > 0;
        }

        return (member::$groupType > 0);
    }
}
StaticsManager::addSaveVar("Permission", "providedPermissions");
