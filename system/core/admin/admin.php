<?php

defined("IN_GOMA") or die();

/**
 * The base model for the admin-panel.
 *
 * @package     Goma\Core\Admin
 *
 * @license     GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author      Goma-Team
 *
 * @version     1.5
 */
class admin extends ViewAccessableData implements PermProvider
{
    static $casting = array(
        "updatables_json" => "HTMLText",
        "updatables" => "HTMLText",
        "addcontent" => "HTMLText"
    );

    /**
     * @var User
     */
    public $currentUser;

    /**
     * user-bar
     *
     * @return array|string
     */
    public function userbar()
    {
        $userbar = new HTMLNode("div");
        $this->callExtending("userbar");
        adminController::activeController()->userbar($userbar);

        return $userbar->html();
    }

    /**
     * history-url
     *
     * @return string
     */
    public function historyURL()
    {
        return adminController::activeController()->historyURL();
    }

    public function TooManyLogs()
    {
        if (file_exists(ROOT . CURRENT_PROJECT . "/" . LOG_FOLDER . "/log")) {
            $count = count(scandir(ROOT . CURRENT_PROJECT . "/" . LOG_FOLDER . "/log"));
            if ($count > 45) {
                return $count;
            }

            return false;
        }

        return false;
    }

    /**
     * returns title
     */
    public function title()
    {
        $adminTitle = adminController::activeController()->Title();
        if ($adminTitle) {
            if (Core::$title)
                return $adminTitle . " / " . Core::$title;

            return $adminTitle;
        }

        if (Core::$title)
            return Core::$title;

        return false;
    }

    /**
     * returns content-classes
     */
    public function content_class()
    {
        return adminController::activeController()->ContentClass();
    }

    /**
     * returns the URL for the view Website button
     *
     * @return string
     */
    public function PreviewURL()
    {
        return adminController::activeController()->PreviewURL();
    }

    /**
     * provies all permissions of this dataobject
     */
    public function providePerms()
    {
        return array(
            "ADMIN"         => array(
                "title"       => '{$_lang_administration}',
                'default'     => array(
                    "type" => "admins"
                ),
                "description" => '{$_lang_permission_administration}'
            ),
            "ADMIN_HISTORY" => array(
                "title"    => '{$_lang_history}',
                "default"  => array(
                    "type" => "admins"
                ),
                "category" => "ADMIN"
            )
        );
    }

    /**
     * gets data fpr available points
     *
     * @return DataSet
     */
    public function this()
    {
        if(!$this->currentUser) {
            throw new InvalidArgumentException("\$currentUser must be set for model admin.");
        }

        $data = new DataSet();
        foreach (ClassInfo::getChildren("adminitem") as $child) {
            /** @var adminItem $class */
            $class = new $child;
            if (isset($class->text) && $class->text) {
                if ($class->visible($this->currentUser) && $this->currentUser->hasPermissions($class->rights)) {
                    if (adminController::activeController()->classname == $child)
                        $active = true;
                    else
                        $active = false;

                    $data->push(array('text'   => parse_lang($class->text),
                        'uname'  => str_replace("\\", "-", substr($class->classname, 0, -5)),
                        'sort'   => StaticsManager::getStatic($class, "sort", true),
                        "active" => $active,
                        "icon"   => ClassInfo::getClassIcon($class->classname)));
                }
            }
        }
        $data->sort("sort", "DESC");

        return $data;
    }

    /**
     * gets addcontent
     *
     * @return string
     */
    public function getAddContent()
    {
        return addcontent::get();
    }

    /**
     * lost_password
     *
     * @name getLost_password
     * @access public
     */
    public function getLost_password()
    {
        $profile = new ProfileController();
        return $profile->lost_password();
    }

    /**
     * returns a list of installed software at a given maximum number
     *
     * @return ViewAccessableData
     */
    public function Software($number = 7)
    {
        return G_SoftwareType::listAllSoftware();
    }

    /**
     * returns if store is available
     *
     * @return bool
     */
    public function isStoreAvailable()
    {
        return G_SoftwareType::isStoreAvailable();
    }

    /**
     * returns updatable packages
     *
     * @return DataSet
     */
    public function getUpdatables()
    {
        return new DataSet(G_SoftwareType::listUpdatablePackages());
    }

    /**
     * returns updatables as json
     *
     * @return string
     */
    public function getUpdatables_JSON()
    {
        return json_encode(G_SoftwareType::listUpdatablePackages());
    }
}
