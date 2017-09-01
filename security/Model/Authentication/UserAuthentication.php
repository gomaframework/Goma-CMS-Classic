<?php defined('IN_GOMA') OR die();

/**
 * authentication-model.
 *
 * @property    string token
 * @property    User user
 * @method      User user()
 *
 * @package        Goma\Security\Users
 *
 * @author        Goma-Team
 * @license        GNU Lesser General Public License, version 3; see "LICENSE.txt"
 *
 * @version        1.0
 */
class UserAuthentication extends DataObject
{

    /**
     * versioned.
     */
    static $versions = true;

    /**
     * no default sort.
     */
    static $default_sort = false;

    /**
     * db.
     */
    static $db = array(
        "token" => "varchar(100)"
    );

    /**
     * has one user.
     */
    static $has_one = array(
        "user" => "User"
    );

    /**
     * index
     */
    static $index = array(
        "token" => true
    );

    /**
     * @var bool
     */
    static $search_fields = false;
}
