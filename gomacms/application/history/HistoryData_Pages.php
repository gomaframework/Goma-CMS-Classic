<?php
defined("IN_GOMA") OR die();

/**
 * Generates all events for records logged by cms model "pages". For more information see parent class.
 *
 * @package     Goma-CMS\Pages
 *
 * @license     GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author      Goma-Team
 *
 * @version     1.0
 */
class HistoryData_Pages extends historyGenerator
{
    public function generateHistoryData()
    {
        $compared = false;
        $relevant = true;

        switch($this->getHistoryRecord()->action) {
            case IModelRepository::COMMAND_TYPE_UPDATE:
            case "update":
                if($this->getHistoryRecord()->writeType == IModelRepository::WRITE_TYPE_PUBLISH) {
                    $lang = lang("gomacms.h_pages_updatepublish", '$user updated and published the page <a href="$pageUrl">$page</a>');
                    $icon = "system/images/icons/fatcow16/page_white_get.png";
                    $compared = true;
                } else {
                    $lang = lang("gomacms.h_pages_update", '$user updated the page <a href="$pageUrl">$page</a>');
                    $icon = "system/images/icons/fatcow16/page_white_edit.png";
                    $compared = true;
                }
                break;
            case IModelRepository::COMMAND_TYPE_INSERT:
            case "insert":
                $lang = lang("gomacms.h_pages_create", '$user created the page <a href="$pageUrl">$page</a>');
                $icon = "system/images/icons/fatcow16/page_white_add.png";
                break;
            case IModelRepository::COMMAND_TYPE_PUBLISH:
            case "publish":
                $lang = lang("gomacms.h_pages_publish", '$user published the page <a href="$pageUrl">$page</a>');
                $icon = "system/images/icons/fatcow16/page_white_get.png";
                $compared = true;
                break;
            case IModelRepository::COMMAND_TYPE_DELETE:
            case "remove":
                $lang = lang("gomacms.h_pages_remove", '$user removed the page <a href="$pageUrl">$page</a>');
                $icon = "system/images/icons/fatcow16/page_white_delete.png";
                $this->getHistoryRecord()->setField("newversion", $this->getHistoryRecord()->oldversionid);
                break;
            case IModelRepository::COMMAND_TYPE_UNPUBLISH:
            case "unpublish":
                $lang = lang("gomacms.h_pages_unpublish", '$user unpublished the page <a href="$pageUrl">$page</a>');
                $icon = "system/images/icons/fatcow16/page_white_edit.png";
                break;
            default:
                $lang = "unknown event " . $this->getHistoryRecord()->action;
                $icon = "system/images/icons/fatcow16/page_white_edit.png";
                break;
        }

        $lang = str_replace('$pageUrl', "admin/content/record/" . $this->getHistoryRecord()->newversion()->id . "/edit" . URLEND, $lang);
        $lang = str_replace('$page', convert::Raw2text($this->getHistoryRecord()->newversion()->title), $lang);

        return array(
            "icon" => $icon,
            "text" => $lang,
            "versioned" => true,
            "compared" => $compared,
            "editurl" => "admin/content/record/" . $this->getHistoryRecord()->newversion()->id . "/edit" . URLEND,
            "relevant" => $relevant
        );
    }
}
