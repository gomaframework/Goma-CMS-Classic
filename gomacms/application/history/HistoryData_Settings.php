<?php
defined("IN_GOMA") OR die();

/**
 * Generates all events for records logged by cms model "NewSettings". For more information see parent class.
 *
 * @package     Goma-CMS\Pages
 *
 * @license     GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author      Goma-Team
 *
 * @version     1.0
 */
class HistoryData_Settings extends historyGenerator
{
    /**
     * support for NewSettings.
     *
     * @return array
     */
    public static function modelTypes()
    {
        return array_merge(array(Newsettings::class), ClassInfo::getChildren(Newsettings::class));
    }

    /**
     * @return array
     */
    public function generateHistoryData()
    {
        $lang = lang("h_settings", '$user updated the <a href="$url">settings</a>.');
        $icon = "system/images/icons/fatcow16/setting_tools.png";
        $lang = str_replace('$url', "admin/settings" . URLEND, $lang);

        return array("icon" => $icon, "text" => $lang, "relevant" => true);
    }
}
