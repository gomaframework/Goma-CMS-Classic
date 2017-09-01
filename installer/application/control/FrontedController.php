<?php defined("IN_GOMA") OR die();

/**
 * @package goma framework
 * @link http://goma-cms.org
 * @license: LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 * @author Goma-Team
 * last modified: 26.08.2011
 * $Version 001
 */
class FrontedController extends Controller {
    /**
     * @param string|GomaResponse $content
     * @return GomaResponse|string
     */
    public function __output($content)
    {
        if(!Director::isResponseFullPage($content)) {
            $data = new ViewAccessAbleData();
            $content = Director::setStringToResponse($content,
                $data->customise(array("content" => Director::getStringFromResponse($content)))->renderWith("install/install.html")
            );
        }
        return parent::__output($content);
    }
}
