<?php defined("IN_GOMA") OR die();

/**
 * @package goma cms
 * @link http://goma-cms.org
 * @license: LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 * @author Goma-Team
 * last modified:  28.12.2012
 * $Version 2.1
 */
class FrontedController extends Controller
{
    /**
     * activates the live-counter on this controller
     */
    protected static $live_counter = true;

    /**
     * @var string
     */
    protected $baseTemplate = "site.html";

    /**
     * gets $view
     *
     * @return string
     */
    public function View()
    {
        if (GlobalSessionManager::globalSession()->hasKey(SystemController::ADMIN_AS_USER)) {
            return lang("user", "user");
        } else {
            return lang("admin", "admin");
        }
    }

    /**
     * gets addcontent
     *
     * @return string
     */
    public function addcontent()
    {
        return addcontent::get();
    }


    /**
     * title
     *
     * @return string
     */
    public function Title()
    {
        return Core::$title . TITLE_SEPERATOR . Core::getCMSVar("ptitle");
    }

    /**
     * meta-data
     */
    /**
     * own css-code
     *
     * @return null
     */
    public function own_css()
    {
        return settingsController::get('css_standard');
    }

    /**
     * fronted-bar for admins
     *
     * @return array
     */
    public function frontedBar()
    {
        return array();
    }

    /**
     * @param GomaResponse|string $content
     * @return GomaResponse|string
     */
    public function __output($content)
    {
        if($this->isManagingController($content) && !isset($this->getRequest()->get_params["ajaxfy"])) {
            $view = $this->getServeModel(
                Director::getStringFromResponse($content)
            );
            return parent::__output(
                \Director::setStringToResponse($content, $view->renderWith($this->baseTemplate, $this->inExpansion))
            );
        }

        return parent::__output($content);
    }

    /**
     * serve-model.
     * @param string $content
     * @return ViewAccessableData
     */
    protected function getServeModel($content) {
        $model = new ViewAccessableData();
        $model->customise($this->tplVars);
        $model->customise(array(
            "model"      => clone $this->modelInst(),
            "title"      => $this->Title(),
            "own_css"    => $this->own_css(),
            "addcontent" => $this->addcontent(),
            "view"       => $this->view(),
            "frontedbar" => new DataSet($this->frontedBar()),
            "content"    => $content,

            "appendedContent"   => $this->getAppendedContent(),
            "prependedContent"  => $this->getPrependedContent()
        ));

        return $model;
    }

    /**
     * gets prepended content
     *
     * @return string
     */
    public function getPrependedContent() {
        $object = new HTMLNode('div', array(
            "class" => "prependedContent"
        ));
        $this->callExtending("prependContent", $object);
        return $object->html();
    }

    /**
     * gets appended content
     *
     * @return string
     */
    public function getAppendedContent() {
        $object = new HTMLNode('div', array(
            "class" => "appendedContent"
        ));
        $this->callExtending("appendContent", $object);
        return $object->html();
    }
}
