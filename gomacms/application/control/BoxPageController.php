<?php defined("IN_GOMA") OR die();

/**
 * @package goma cms
 * @link http://goma-cms.org
 * @license: LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 * @author Goma-Team
 */
class boxPageController extends PageController {
    /**
     * template of this controller
     * @var string
     */
    public $template = "pages/box.html";


    /**
     * generates a button switch-view
     */
    public function frontedBar()
    {
        $arr = parent::frontedBar();

        if ($this->modelInst()->can("Write")) {

            if (GlobalSessionManager::globalSession()->hasKey(SystemController::ADMIN_AS_USER)) {
                $arr[] = array(
                    "url"   => BASE_SCRIPT . "system/switchview" . URLEND . "?redirect=" . urlencode($_SERVER["REQUEST_URI"]),
                    "title" => lang("switch_view_edit_on", "enable edit-mode")
                );
            } else {
                $arr[] = array(
                    "url"   => BASE_SCRIPT . "system/switchview" . URLEND . "?redirect=" . urlencode($_SERVER["REQUEST_URI"]),
                    "title" => lang("switch_view_edit_off", "disable edit-mode")
                );
            }
        }

        return $arr;
    }
}
