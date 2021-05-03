<?php
defined("IN_GOMA") or die();

/**
 * Generates all events for records logged by framework model "userauthentification". For more information see parent class.
 *
 * @package Goma
 * @author Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class historyData_userauthentication extends historyGenerator
{

    public function generateHistoryData()
    {
        $array = array();
        /** @var UserAuthentication $version */
        switch ($this->getHistoryRecord()->action) {
            case "remove":
            case IModelRepository::COMMAND_TYPE_DELETE:
                if ($this->getHistoryRecord()->created - $this->getHistoryRecord()->oldversion()->last_modified < AuthenticationService::$expirationLimit) {
                    $array["text"] = lang("h_user_logout");
                    if ($this->getHistoryRecord()->oldversion()) {
                        $version = $this->getHistoryRecord()->oldversion();
                    }
                    $array["icon"] = "system/images/icons/fatcow16/user_go.png";
                } else {
                    return false;
                }
                break;
            default:
                $array["text"] = lang("h_user_login");
                $version = $this->getHistoryRecord()->newversion();
                $array["icon"] = "system/images/icons/fatcow16/user_go.png";
        }
        if (isset($version)) {
            $array["text"] = str_replace('$userUrl', "member/" . $version->user->id . URLEND, $array["text"]);
            $array["text"] = str_replace('$euser', convert::Raw2text($version->user->title), $array["text"]);
        } else {
            $array["text"] = str_replace('$userUrl', "", $array["text"]);
            $array["text"] = str_replace('$euser', "Unbekannt", $array["text"]);
        }
        return $array;
    }
}