<?php
defined("IN_GOMA") or die();

/**
 * Generates all events for records logged by framework model "group". For more information see parent class.
 *
 * @package Goma
 * @author Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class historyData_group extends historyGenerator
{
    public function generateHistoryData()
    {
        $array = array();

        switch ($this->getHistoryRecord()->action) {
            case "update":
            case "publish":
                $array["text"] = lang("h_group_update", '$user updated the group <a href="$groupUrl">$group</a>');
                $array["icon"] = "system/images/icons/fatcow16/group_edit.png";
                break;
            case "insert":
                $array["text"] = lang("h_group_create", '$user created the group <a href="$groupUrl">$group</a>');
                $array["icon"] = "system/images/icons/fatcow16/group_add.png";
                break;
            case "remove":
                $array["text"] = lang("h_user_remove", '$user removed the group $group');
                $array["icon"] = "system/images/icons/fatcow16/group_delete.png";
                break;
            default:
                $array["text"] = "Unknown event " . $this->getHistoryRecord()->action;
                $array["icon"] = "system/images/icons/fatcow16/group_edit.png";
        }

        $array["text"] = str_replace('$groupUrl', "admin/group/" . $this->getHistoryRecord()->record()->id . URLEND, $array["text"]);
        $array["text"] = str_replace('$group', convert::Raw2text($this->getHistoryRecord()->record()->name), $array["text"]);

        return $array;
    }
}