<?php defined("IN_GOMA") OR die();

/**
 * @package goma cms
 * @link http://goma-cms.org
 * @license: LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 * @author Goma-Team
 */
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
                    $errorController = new errorPageController();
                    return $errorController->getWithModel($error)->handleRequest($this->request, $this->isSubController());
                }
                unset($error);
            }

        }
    }
}
