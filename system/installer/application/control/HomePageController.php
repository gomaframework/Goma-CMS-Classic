<?php
defined('IN_GOMA') OR die();

/**
 * @package goma framework
 * @link http://goma-cms.org
 * @license: LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 * @author Goma-Team
 * last modified: 13.01.2012
 * $Version 1.1
 */
class HomePageController extends RequestHandler {
    /**
     * lang selected session key.
     */
    const SESSION_LANGSELECT = "langselected";

    /**
     * shows install fronted if language is already selected, else shows lang-select
     */
    public function index()
    {
        if (GlobalSessionManager::globalSession()->hasKey(self::SESSION_LANGSELECT) || isset($this->request->get_params["setlang"])) {
            GlobalSessionManager::globalSession()->set(self::SESSION_LANGSELECT, true);
            $controller = new InstallController();
            return $controller->handleRequest($this->request, $this->isSubController());
        } else {
            return $this->langSelect();
        }
    }

    /**
     * shows lang-select
     *
     * @return string
     */
    public function langSelect()
    {
        $data = new ViewAccessAbleData();

        return $data->customise(array("firstrun" => 1, "content" => tpl::Render("install/langselect.html")))->renderWith("install/install.html");
    }
}