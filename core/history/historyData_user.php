<?php
defined("IN_GOMA") or die();

/**
 * Generates all events for records logged by framework model "user". For more information see parent class.
 *
 * @package Goma
 * @author Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class historyData_user extends historyGenerator
{
    public function generateHistoryData()
    {
        if (!$this->getHistoryRecord()->newversion()) {
            return false;
        }
        $array = array();


        switch ($this->getHistoryRecord()->action) {
            case IModelRepository::COMMAND_TYPE_UPDATE:
            case IModelRepository::COMMAND_TYPE_PUBLISH:
            case "update":
            case "publish":
                if ($this->getHistoryRecord()->autorid == $this->getHistoryRecord()->newversion()->id) {
                    $array["text"] = lang("h_profile_update");
                    $array["icon"] = "system/images/icons/fatcow16/user_edit.png";
                } else {
                    if ($this->getHistoryRecord()->newversion()->status != $this->getHistoryRecord()->oldversion()->status) {
                        if ($this->getHistoryRecord()->newversion()->status == 2) {
                            $array[0]["text"] = lang("h_user_locked");
                            $array[0]["icon"] = "system/images/icons/fatcow16/user_delete.png";
                        } else {
                            $array[0]["text"] = lang("h_user_unlocked");
                            $array[0]["icon"] = "system/images/icons/fatcow16/user_add.png";
                        }
                    } else {
                        // admin changed profile
                        $array["text"] = lang("h_user_update");
                        $array["icon"] = "system/images/icons/fatcow16/user_edit.png";
                    }
                    // admin changed profile
                    $array[1]["text"] = lang("h_user_update");
                    $array[1]["icon"] = "system/images/icons/fatcow16/user_edit.png";
                }
                break;
            case IModelRepository::COMMAND_TYPE_INSERT:
            case "insert":
                $array["text"] = lang("h_user_create");
                $array["icon"] = "system/images/icons/fatcow16/user_add.png";
                break;
            case IModelRepository::COMMAND_TYPE_DELETE:
            case "remove":
                $array["text"] = lang("h_user_remove");
                $array["icon"] = "system/images/icons/fatcow16/user_delete.png";
                break;
            default:
                $array["text"] = "Unknown event " . $this->getHistoryRecord()->action;
                $array["icon"] = APPLICATION . "/icons/computer_edit.png";
        }
        if (isset($array["text"]) || isset($array["icon"]))
            $array = array($array);

        for ($i = 0; $i < count($array); $i++) {
            $array[$i]["text"] = str_replace('$userUrl', BASE_URI . "member/" . $this->getHistoryRecord()->newversion()->id . URLEND, $array[$i]["text"]);
            $array[$i]["text"] = str_replace('$euser', convert::Raw2text($this->getHistoryRecord()->newversion()->title), $array[$i]["text"]);
            if ($this->getHistoryRecord()->autor) {
                $user = '<a href="member/' . $this->getHistoryRecord()->autor->ID . URLEND . '" class="user" title="' . convert::raw2text($this->getHistoryRecord()->autor->title) . '">' . convert::Raw2text($this->getHistoryRecord()->autor->title) . '</a>';
            } else {
                $user = '<span style="font-style: italic;">System</span>';
            }

            $array[$i]["text"] = str_replace('$user', $user, $array[$i]["text"]);
        }
        return $array;
    }
}