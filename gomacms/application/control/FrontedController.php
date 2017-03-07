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
     * @name frontedBar
     * @access public
     * @return array
     */
    public function frontedBar()
    {
        return array();
    }

    /**
     * handles the request with showing as site
     * @param string $content
     * @param GomaResponseBody $body
     * @return mixed|string
     */
    public function serve($content, $body)
    {
        if ((Core::is_ajax() && isset($_GET["dropdownDialog"])) || Director::isResponseFullPage($body)) {
            return $content;
        }

        if(strpos(strtolower($content), "</body") !== false) {
            throw new LogicException("Before FrontedController serve, no HTML-Body should be generated. Seems like somebody called serve twice.");
        }

        if (SITE_MODE == STATUS_MAINTANANCE && !Permission::check("ADMIN")) {
            return $this->getServeModel($content)->renderWith("page_maintenance.html");
        }


        return $this->renderWith("site.html", $this->getServeModel($content));
    }

    /**
     * serve-model.
     * @param string $content
     * @return ViewAccessableData
     */
    protected function getServeModel($content) {
        $model = is_object($this->modelInst()) ? $this->modelInst() : new ViewAccessableData();

        $model->customise(array(
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

class siteController extends Controller
{
    public $shiftOnSuccess = false;
    public static $keywords;
    public static $description;

    /**
     * @var PageService
     */
    protected $pageService;

    /**
     * siteController constructor.
     * @param KeyChain $keychain
     * @param PageService $pageService
     */
    public function  __construct($keychain = null, $pageService = null)
    {
        parent::__construct($keychain);

        $this->pageService = isset($pageService) ? $pageService : new PageService();
    }

    public function handleRequest($request, $subController = false)
    {
        if (SITE_MODE == STATUS_MAINTANANCE && !Permission::check("ADMIN")) {
            $data = new ViewAccessAbleData();
            return $data->customise()->renderWith("page_maintenance.html");
        }

        return parent::handleRequest($request, $subController);
    }

    /**
     * gets the content
     *
     * @return bool|false|mixed|null|string
     */
    public function index()
    {
        $path = $this->getParam("path");
        if ($path) {
            /** @var Page $page */
            $page = $this->pageService->getPageWithState(
                array("path" => array("LIKE", $path), "parentid" => 0),
                isset($this->request->get_params["pages_state"])
            );
            if ($page) {
                return ControllerResolver::instanceForModel($page)->handleRequest($this->request, $this->isSubController());
            } else {

                unset($page, $path);
                $error = DataObject::get_one("errorpage");
                if ($error) {
                    return $error->controller()->handleRequest($this->request, $this->isSubController());
                }
                unset($error);
            }

        }
    }
}
