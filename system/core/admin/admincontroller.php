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
    static $less_vars = "admin.less";
    /**
     * object of current admin-view
     */
    protected static $activeController;
    /**
     * some default url-handlers for this controller
     */
    static $url_handlers = array(
        "switchlang"              => "switchlang",
        "update"                  => "handleUpdate",
        "flushLog"                => "flushLog",
        "history"                 => "history",
        "admincontroller:\$item!" => "handleItem",
    );
    /**
     * we allow those actions
     */
    static $allowed_actions = array("handleItem", "switchlang", "handleUpdate", "flushLog", "history");
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
        "BASEURI" => BASE_URI,
    );

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
     * returns current controller
     *
     * @return adminController
     */
    static function activeController()
    {
        return (self::$activeController) ? self::$activeController : new adminController;
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
     * @throws Exception
     */
    public function handleItem()
    {
        if (!Permission::check("ADMIN")) {
            return null;
        }

        if ($class = $this->findAdminItemClassByName($this->request->getParam("item"))) {
            /** @var adminItem $controller */
            $controller = new $class;

            Core::$favicon = ClassInfo::getClassIcon($class);

            if (Permission::check(StaticsManager::getStatic($class, "rights", true))) {
                self::$activeController = $controller;

                return $controller->handleRequest($this->request);
            }
        }
    }

    /**
     * @param string $name
     * @return null|string
     */
    protected function findAdminItemClassByName($name) {
        $className = ClassManifest::resolveClassName($name);
        if(ClassInfo::exists($className) && ClassManifest::isOfType($className, adminItem::class)) {
            return $className;
        }

        if(ClassInfo::exists($className . "admin") && ClassManifest::isOfType($className . "admin", adminItem::class)) {
            return $className . "admin";
        }

        return null;
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
    public function flushLog($count = 40)
    {
        $count = $this->getParam("count") ? $this->getParam("count") : $count;

        if (Permission::check("superadmin")) {
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
        if (!$this->isManagingController($content) || $this->getRequest()->is_ajax()) {
            return parent::__output($content);
        }

        $admin = new Admin();
        $prepared = $admin->customise(
            array(
                "content" => Director::getStringFromResponse($content),
            )
        );

        if (!Permission::check("ADMIN")) {
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
     * @throws SQLException
     */
    public function index()
    {
        if (Permission::check("ADMIN")) {

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

        if (Permission::check("superadmin")) {
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
     * @throws Exception
     */
    public function history()
    {
        if (Permission::check("ADMIN")) {
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
}
