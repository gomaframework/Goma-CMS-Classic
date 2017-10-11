<?php defined("IN_GOMA") OR die();

/**
 * The base controller for the admin-panel.
 *
 * @package     Goma\Core\Admin
 *
 * @license     GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author      Goma-Team
 *
 * @version     1.5.2
 */
class adminController extends Controller
{
    /**
     * current title
     */
    static $title;

    /**
     * object of current admin-view
     */
    protected static $activeController;

    /**
     * some default url-handlers for this controller
     */
    public $url_handlers = array(
        "switchlang"              => "switchlang",
        "update"                  => "handleUpdate",
        "flushLog"                => "flushLog",
        "history"                 => "history",
        "admincontroller:\$item!" => "handleItem"
    );

    /**
     * we allow those actions
     */
    public $allowed_actions = array("handleItem", "switchlang", "handleUpdate", "flushLog", "history");

    /**
     * this var contains the templatefile
     * the str {admintpl} will be replaced with the current admintpl
     *
     * @var string
     */
    public $template = "admin/index.html";

    /**
     * tpl-vars
     */
    public $tplVars = array(
        "BASEURI" => BASE_URI
    );

    static $less_vars = "admin.less";

    /**
     * returns current controller
     *
     * @return adminController
     */
    static function activeController()
    {
        return (self::$activeController) ? self::$activeController : new adminController;
    }

    /**
     * @param null $service
     * @param null $keyChain
     */
    public function __construct($service = null, $keyChain = null)
    {
        parent::__construct($service, $keyChain);

        Resources::addData("goma.ENV.is_backend = true;");
        defined("IS_BACKEND") OR define("IS_BACKEND", true);
        Core::setHeader("robots", "noindex, nofollow");
    }

    /**
     * global admin-enabling
     *
     * @param Request $request
     * @param bool $subController
     * @return false|string
     * @throws Exception
     * @internal param $handleRequest
     * @access public
     */
    public function handleRequest($request, $subController = false)
    {
        if (isset(ClassInfo::$appENV["app"]["enableAdmin"]) && !ClassInfo::$appENV["app"]["enableAdmin"]) {
            HTTPResponse::redirect(BASE_URI);
        }

        HTTPResponse::unsetCacheable();

        return parent::handleRequest($request, $subController);
    }

    /**
     * hands the control to admin-controller
     *
     * @return mixed
     */
    public function handleItem()
    {
        if (!$this->request->userHasPermission("ADMIN")) {
            return null;
        }

        $class = str_replace("-", "\\", $this->request->getParam("item")) . "admin";

        if (ClassInfo::exists($class) && ClassManifest::isOfType($class, adminItem::class)) {
            /** @var adminItem $controller */
            $controller = new $class;

            Core::$favicon = ClassInfo::getClassIcon($class);

            if ($this->request->userHasPermission($controller->rights)) {
                self::$activeController = $controller;

                return $controller->handleRequest($this->request);
            }
        }
    }

    /**
     * title
     *
     * @return string
     */
    public function title()
    {
        return "";
    }

    /**
     * returns title, alias for title
     *
     * @return string
     */
    final public function adminTitle()
    {
        return $this->Title();
    }

    /**
     * returns the URL for the View Website-Button
     *
     * @return string
     */
    public function PreviewURL()
    {
        return BASE_URI;
    }

    /**
     * switch-lang-template
     *
     * @name switchLang
     * @access public
     * @return string
     */
    public function switchLang()
    {
        return tpl::render("switchlang.html");
    }

    /**
     * flushes all log-files. This method stops the session before flushing to prevent user-blocking.
     *
     * @param int $count number of days log should be stored.
     * @return mixed|string
     */
    public function flushLog($count = 40) {
        $count = $this->getParam("count") ? $this->getParam("count") : $count;

        if ($this->request->userHasPermission("superadmin")) {
            PushController::enablePush();
            GlobalSessionManager::globalSession()->stopSession();
            ignore_user_abort(true);
            // we delete all logs that are older than 30 days
            Core::CleanUpLog($count);

            if (!$this->getRequest()->is_ajax()) {
                AddContent::addSuccess(lang("flush_log_success"));
                return $this->redirectBack();
            } else {
                $response = new GomaResponse();
                $response->setHeader("content-type", "text/x-json");

                Notification::notify($this->classname, lang("flush_log_success"), null, "PushNotification");

                GlobalSessionManager::Init();
                PushController::disablePush();

                $response->setBody(new JSONResponseBody(1));
                $response->output();
                exit;
            }
        }

        $this->template = "admin/index_not_permitted.html";

        return parent::index();
    }

    /**
     * @param GomaResponse|string $content
     * @return GomaResponse|string
     */
    public function __output($content)
    {
        Core::setHeader("robots", "noindex,nofollow");
        if(!$this->isManagingController($content) || $this->getRequest()->is_ajax()) {
            return parent::__output($content);
        }

        if (Permission::check("ADMIN")) {
            $data = $this->helpData();
            $data["#help-button a"] = lang("HELP.HELP");
            Resources::addJS("addHelp(" . json_encode($data) . ");");
        }

        $admin = new Admin();
        $admin->currentUser = $this->request->getUser();
        $prepared = $admin->customise(array(
            "content" => Director::getStringFromResponse($content)
        ));

        if (!$this->request->userHasPermission("ADMIN")) {
            $newContent = $prepared->renderWith("admin/index_not_permitted.html");
        } else {
            $newContent = $prepared->renderWith("admin/index.html");
        }

        return parent::__output(
            Director::setStringToResponse($content, $newContent)
        );
    }

    /**
     * loads content and then loads page
     *
     * @return bool|string
     */
    public function index()
    {
        if ($this->request->userHasPermission("ADMIN")) {

            if (isset($this->getRequest()->get_params["flush"])) {
                Core::deleteCache(true);

                AddContent::addSuccess(lang("cache_deleted"));
            }

            return static::class == self::class ? "" : parent::index();
        } else {
            $this->template = "admin/index_not_permitted.html";

            return static::class == self::class ? "" : parent::index();
        }
    }

    /**
     * update action
     */
    public function handleUpdate()
    {

        if ($this->request->userHasPermission("superadmin")) {
            $controller = new UpdateController();
            self::$activeController = $controller;

            return $controller->handleRequest($this->request);
        }

        $this->template = "admin/index_not_permitted.html";

        return parent::index();
    }

    /**
     * history
     *
     * @return bool|string
     */
    public function history()
    {
        if ($this->request->userHasPermission("ADMIN")) {
            $controller = new HistoryController();

            return $controller->handleRequest($this->request, true);
        }

        $this->template = "admin/index_not_permitted.html";

        return false;
    }

    /**
     * extends the userbar
     *
     * @name userbar
     * @access public
     */
    public function userbar(&$bar)
    {

    }

    /**
     * here you can modify classes content-div
     *
     * @return string
     */
    public function contentClass()
    {
        return $this->classname;
    }

    /**
     * history-url
     *
     * @return string
     */
    public function historyURL()
    {
        return "admin/history";
    }

    /**
     * help-texts.
     */
    public function helpData()
    {
        return array(
            "#navi-toggle span span" => array(
                "text" => lang("HELP.SHOW-MENU")
            ),
            "#history"          => array(
                "text"     => lang("HELP.HISTORY"),
                "position" => "left"
            )
        );
    }
}
