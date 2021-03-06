<?php defined('IN_GOMA') OR die();

/**
 * checks for default users and permissions.
 *
 * @package		Goma\Security\Users
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 *
 * @version		1.0.1
 */
class DefaultPermission {
    /**
     * default check cache.
     */
    const CACHE_DEFAULT_CHECK = "groups-checkDefaults";

    /**
     * checks for default admin and basic groups
     */
    public static function checkDefaults() {
        $cacher = new Cacher(self::CACHE_DEFAULT_CHECK);
        if(!$cacher->checkValid()) {
            if(DataObject::count("group", array(
                    "permissions" => array(
                        "name" => "superadmin"
                    )
                )) === 0) {
                $group = new Group();
                $group->name = lang("admins", "admin");
                $group->type = 2;
                $group->permissions()->add(Permission::forceExisting("superadmin"));
                $group->writeToDB(true, true, 2, false, false);
            }

            if(DataObject::count("group", array("usergroup" => 1)) === 0) {
                $group = new Group();
                $group->name = lang("user", "users");
                $group->usergroup = 1;
                $group->type = 1;
                $group->writeToDB(true, true, 2, false, false);
            }


            if(isset(Member::$default_admin) && DataObject::count("user") === 0) {
                $user = new User();
                $user->nickname = Member::$default_admin["nickname"];
                $user->password = Member::$default_admin["password"];
                $user->writeToDB(true, true);
                $user->groups()->add(DataObject::get_one("group", array("type" => 2)));
                $user->groups()->commitStaging(false, true);
            }

            $cacher->write(true, 3600);
        }
    }

    /**
     * forces group-type to be an integer.
     *
     * @param User $user
     * @return int
     */
    public static function forceGroupType($user) {
        if($user->getGroupType() === null) {
            $groups = $user->groups();
            $group = self::getDefaultGroup();

            $groups->add($group);
            $groups->commitStaging(false, true, 2);
        }

        return $user->getGroupType();
    }

    /**
     * returns a group which any user can be assigned to safely, based on permissions.
     *
     * @name 	getDefaultGroup
     * @return 	Group
     */
    protected static function getDefaultGroup() {
        // check for default user group
        $defaultGroup = DataObject::get_one("group", array("usergroup" => 1));
        if(!$defaultGroup) {

            // check if any group exists, which a user can be safely asigned to without giving him admin permission
            $groupCount = DataObject::count("group", array("type" => 1));

            // validate group and permissions
            if($groupCount == 0 || ($groupCount == 1 && DataObject::get_one("group", array("type" => 1))->permissions()->Count() > 0)) {
                // create new
                $defaultGroup = new Group(array("name" => lang("user"), "type" => 1, "usergroup" => 1));
                $defaultGroup->writeToDB(true, true, 2, false, false);
            } else {
                // iterate trough all groups with type 1 and set default group to the first one without permissions
                foreach(DataObject::get("group", array("type" => 1)) as $defaultGroup) {
                    if($defaultGroup->permissions()->count() === 0) {
                        $defaultGroup->usergroup = 1;
                        $defaultGroup->writeToDB(false, true, 2, true, false);
                        break;
                    } else {
                        unset($defaultGroup);
                    }
                }

                if(!isset($defaultGroup)) {
                    $defaultGroup = new Group(array("name" => lang("user"), "type" => 1, "usergroup" => 1));
                    $defaultGroup->writeToDB(true, true, 2, false, false);
                }
            }
        }

        return $defaultGroup;
    }
}