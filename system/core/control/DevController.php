<?php defined("IN_GOMA") OR die();

/**
 * Controller for Dev-Mode of Goma-Framework. Handles stuff like rebuilding DB or
 * building versions
 *
 * @package        Goma\Core
 * @version        2.1.1
 */
class Dev extends RequestHandler
{
    /**
     * session-key for dev without perm.
     */
    const SESSION_DEV_WITHOUT_PERM = "dev_without_perm";

    /**
     * title of current view
     */
    public static $title = "Creating new Database";

    static $url_handlers = array(
        "build"                         => "builddev",
        "rebuildcaches"                 => "rebuild",
        "flush"                         => "flush",
        "buildDistro/\$name!/\$subname" => "buildAppDistro",
        "buildDistro"                   => "buildDistro",
        "cleanUpVersions"               => "cleanUpVersions",
        "setChmod777"                   => "setChmod777",
        "setPermissionsSafeMode"        => "setPermissionsSafeMode",
        "test"                          => "test",
        "rebuild"                       => "rebuild",
        "builddev"                      => "builddev",
        "buildExpDistro"                => "buildExpDistro",
        "fixPermissions"                => "fixPermissions"
    );

    static $allowed_actions = array(
        "buildDistro"            => "->isDev",
        "buildAppDistro"         => "->isDev",
        "buildExpDistro"         => "->isDev",
        "cleanUpVersions"        => "->isDev",
        "setPermissionsSafeMode" => "->isDev",
        "fixPermissions"         => "superadmin",
    );

    /**
     * runs dev and redirects back to REDIRECT
     *
     */
    public static function redirectToDev()
    {
        if (GlobalSessionManager::globalSession() == null) {
            ClassManifest::tryToInclude("SessionManager", "system/security/Services/SessionManager.php");
            GlobalSessionManager::__setSession(SessionManager::startWithIdAndName(null));
        }

        GlobalSessionManager::globalSession()->set(self::SESSION_DEV_WITHOUT_PERM, true);
        header("Location: ".BASE_URI.BASE_SCRIPT."/dev?redirect=".getredirect(false));
        exit;
    }

    /**
     * @return string
     */
    public static function buildDevCLI()
    {
        $data = "";
        if (defined("SQL_LOADUP")) {
            // remake db
            foreach (classinfo::getChildren("dataobject") as $value) {
                $obj = new $value;

                $data .= nl2br($obj->buildDB(DB_PREFIX));
            }
        }

        logging(strip_tags(preg_replace("/(\<br\s*\\\>|\<\/div\>)/", "\n", $data)));

        // after that rewrite classinfo
        ClassInfo::write();

        return $data;
    }

    /**
     * checks for redirect without dev-permissions or normal redirect.
     *
     * @name checkForRedirect
     */
    public static function checkForRedirect()
    {
        // redirect if needed
        if (isset($_GET["redirect"])) {
            GlobalSessionManager::globalSession()->remove(self::SESSION_DEV_WITHOUT_PERM);
            header("Location: ".$_GET["redirect"]);
            exit;
        }

        // redirect to BASE if needed
        if (GlobalSessionManager::globalSession()->remove(self::SESSION_DEV_WITHOUT_PERM)) {
            header("Location: ".ROOT_PATH);
            Core::callHook("onBeforeShutDown");
            exit;
        }
    }

    /**
     * @param array $args
     * @param int $code
     */
    public static function cli($args, &$code = 0)
    {
        if (isset($args["-builddistro"])) {
            $name = $args["-builddistro"];
            $subname = isset($args["subname"]) ? $args["subname"] : null;
            if (isset($args["distrofile"])) {
                if (ClassInfo::exists("G_".$name."SoftwareType") && is_subclass_of(
                        "G_".$name."SoftwareType",
                        G_SoftwareType::class
                    )) {
                    if (file_exists($args["distrofile"])) {
                        echo "Distrofile should not exist.\n";
                        $code = 13;
                    } else {
                        call_user_func_array(
                            array(
                                call_user_func_array(array("G_".$name."SoftwareType", "instance"), array()),
                                "finalizeDistro",
                            ),
                            array(
                                array(
                                    "file"      => $args["distrofile"],
                                    "expname"   => $subname,
                                    "changelog" => isset($args["changelog"]) ? $args["changelog"] : null,
                                ),
                            )
                        );
                    }
                } else {
                    echo "Distro-class not found.\n";
                    $code = 14;
                }
            } else {
                echo "Distro required distro-filename.\n";
                $code = 12;
            }
        }
    }

    /**
     * shows dev-site or not
     * @param $request
     * @param bool $subController
     * @return false|null|string
     * @throws Exception
     * @throws PermissionException
     */
    public function handleRequest($request, $subController = false)
    {
        define("DEV_CONTROLLER", true);

        HTTPResponse::unsetCacheable();

        if (!GlobalSessionManager::globalSession()->hasKey(self::SESSION_DEV_WITHOUT_PERM) && !Permission::check(
                "ADMIN"
            )) {
            makeProjectAvailable();

            throw new PermissionException();
        }

        return $this->__output(parent::handleRequest($request, $subController));

    }

    /**
     * @param $content
     * @return GomaResponse|GomaResponseBody|mixed|string
     */
    public function __output($content)
    {
        if (!$this->isManagingController($content) || isset($this->getRequest()->get_params["ajaxfy"])) {
            return $content;
        }

        $viewabledata = new ViewAccessableData();
        $viewabledata->content = Director::getStringFromResponse($content);
        $viewabledata->title = self::$title;

        return Director::setStringToResponse($content, $viewabledata->renderWith("framework/dev.html"));
    }

    /**
     * sets chmod 0777 to the whole system
     *
     */
    public function setChmod777()
    {
        FileSystem::chmod(ROOT, 0777, false);

        return "Okay";
    }

    /**
     * returns if we are in dev-mode
     *
     */
    public function isDev()
    {

        return DEV_MODE;
    }

    /**
     * the index site of the dev-mode
     *
     * @name index
     * @return string
     */
    public function index()
    {
        // make 503
        makeProjectUnavailable();

        ClassInfo::delete();
        Core::callHook("deleteCachesInDev");

        // check if dev-without-perms, so redirect directly
        if (GlobalSessionManager::globalSession()->hasKey(self::SESSION_DEV_WITHOUT_PERM)) {
            $url = ROOT_PATH.BASE_SCRIPT."dev/rebuildcaches".URLEND."?redirect=".urlencode($this->getRedirect($this));
            header("Location: ".$url);
            echo "<script>location.href = '".$url."';</script><br /> Redirecting to: <a href='".$url."'>'.$url.'</a>";
            Core::callHook("onBeforeShutDown");
            exit;
        }

        return $this->template("Dev/dev.html", array("url" => "dev/rebuildcaches"));
    }

    /**
     * this step regenerates the cache
     *
     * @name rebuild
     * @return string
     */
    public function rebuild()
    {
        // 503
        makeProjectUnavailable();

        Core::callHook("rebuildCachesInDev");

        // generate class-info
        defined('GENERATE_CLASS_INFO') OR define('GENERATE_CLASS_INFO', true);
        define("DEV_BUILD", true);

        // redirect if needed
        if (GlobalSessionManager::globalSession()->hasKey(self::SESSION_DEV_WITHOUT_PERM)) {
            $url = ROOT_PATH.BASE_SCRIPT."dev/builddev".URLEND."?redirect=".urlencode($this->getRedirect($this));
            header("Location: ".$url);
            echo "<script>location.href = '".$url."';</script><br /> Redirecting to: <a href='".$url."'>'.$url.'</a>";
            Core::callHook("onBeforeShutDown");
            exit;
        }

        return $this->template("Dev/dev.html", array("rebuilt_caches" => true, "url" => "dev/builddev"));
    }

    /**
     * this step regenerates the db
     */
    public function builddev()
    {
        // 503
        makeProjectUnavailable();

        // patch
        StaticsManager::setStatic(gObject::class, "cache_singleton_classes", array(), true);

        $data = self::buildDevCLI();

        unset($obj);
        $data .= "<br />";

        Core::callHook("rebuildDBInDev");

        // restore page, so delete 503
        makeProjectAvailable();

        self::checkForRedirect();

        return $this->template("Dev/dev.html", array("rebuilt_caches" => true, "rebuilt_db" => $data));
    }

    /**
     * just for flushing the whole (!) cache
     *
     * @name flush
     */
    public function flush()
    {
        defined('GENERATE_CLASS_INFO') OR define('GENERATE_CLASS_INFO', true);
        define("DEV_BUILD", true);

        classinfo::delete();
        classinfo::loadfile();

        header("Location: ".ROOT_PATH."");
        Core::callHook("onBeforeShutDown");
        exit;
    }

    /**
     * builds a distributable of the application
     *
     */
    public function buildDistro()
    {
        self::$title = lang("DISTRO_BUILD");

        return g_SoftwareType::listAllSoftware()->renderWith("framework/buildDistro.html");
    }

    /**
     * builds an app-distro
     *
     * @param string|null $name
     * @param string|null $subname
     * @return bool|mixed
     */
    public function buildAppDistro($name = null, $subname = null)
    {
        if (!isset($name)) {
            $name = $this->getParam("name");
        }

        if (!isset($subname)) {
            $subname = $this->getParam("subname");
        }

        self::$title = lang("DISTRO_BUILD");

        if (!$name) {
            return false;
        }

        if (ClassInfo::exists("G_".$name."SoftwareType") && is_subclass_of(
                "G_".$name."SoftwareType",
                G_SoftwareType::class
            )) {
            $filename = call_user_func_array(
                array("G_".$name."SoftwareType", "generateDistroFileName"),
                array($subname)
            );
            if ($filename === false) {
                return false;
            }
            $file = ROOT.CACHE_DIRECTORY."/".$filename;

            /** @var GomaFormResponse $return */
            $return = call_user_func_array(
                array("G_".$name."SoftwareType", "buildDistro"),
                array($file, $subname, $this)
            );
            if (!is_bool($return) && (!is_a($return, "GomaFormResponse") || !is_bool($return->getResult()))) {
                return $return;
            }

            return FileSystem::sendFile($file, null, $this->request);
        }

        return false;
    }

    /**
     * cleans up versions
     *
     */
    public function cleanUpVersions()
    {
        $log = "";
        foreach (ClassInfo::getChildren("DataObject") as $child) {
            if (ClassInfo::getParentClass($child) == "dataobject") {
                $c = new $child;
                if (DataObject::versioned($child)) {

                    $baseTable = ClassInfo::$class_info[$child]["table"];
                    if (isset(ClassInfo::$database[$child."_state"])) {
                        // first get ids NOT to delete

                        $recordids = array();
                        $ids = array();
                        // first recordids
                        $sql = "SELECT * FROM ".DB_PREFIX.$baseTable."_state";
                        if ($result = SQL::Query($sql)) {
                            while ($row = SQL::fetch_object($result)) {
                                $recordids[$row->id] = $row->id;
                                $ids[$row->publishedid] = $row->publishedid;
                                $ids[$row->stateid] = $row->stateid;
                            }
                        }

                        $deleteids = array();
                        // now generate ids to delete
                        $sql = "SELECT id FROM ".DB_PREFIX.$baseTable." WHERE id NOT IN('".implode(
                                "','",
                                $ids
                            )."') OR recordid NOT IN ('".implode("','", $recordids)."')";
                        if ($result = SQL::Query($sql)) {
                            while ($row = SQL::fetch_object($result)) {
                                $deleteids[] = $row->id;
                            }
                        }

                        // now delete

                        // first generate tables
                        $tables = array(ClassInfo::$class_info[$child]["table"]);
                        foreach (ClassInfo::dataClasses($child) as $class => $table) {
                            if ($baseTable != $table && isset(ClassInfo::$database[$table])) {
                                $tables[] = $table;
                            }
                        }

                        foreach ($tables as $table) {
                            $sql = "DELETE FROM ".DB_PREFIX.$table." WHERE id IN('".implode("','", $deleteids)."')";
                            if (SQL::Query($sql)) {
                                $log .= '<div><img src="system/images/success.png" height="16" alt="Loading..." /> Delete versions of '.$table.'</div>';
                            } else {
                                $log .= '<div><img src="system/images/16x16/del.png" height="16" alt="Loading..." /> Failed to delete versions of '.$table.'</div>';
                            }
                        }
                    }

                }
            }
        }

        return '<h3>DB-Cleanup</h3>'.$log;
    }

    /**
     * safe-mode.
     */
    public function setPermissionsSafeMode()
    {
        if (isset($_GET["safemode"])) {
            if ($_GET["safemode"] == 1 || $_GET["safemode"] == 0) {
                FileSystem::$safe_mode = (boolean)$_GET["safemode"];
                writeProjectConfig(array("safe_mode" => (boolean)$_GET["safemode"]));
            }
        }

        FileSystem::applySafeMode(null, null, true);

        return "OK";
    }

    /**
     *
     */
    public function fixPermissions() {
        $permissions = DataObject::get(Permission::class);
        $permissions->activatePagination($this->getParam("page"));
        foreach($permissions as $permission) {
            // force rewrite
            try {
                $permission->didChangeObject = true;
                $permission->writeToDB(false, true);
            } catch (Exception $e) {
                log_exception($e);
                AddContent::addError($e->getMessage());
            }
        }

        $view = new ViewAccessableData();
        $view->page = $permissions->getPage();
        $view->allPages = $permissions->getPageCount();
        $view->nextPage = $permissions->isNextPage() ? $this->request->getFullPath() . "?page=" . $permissions->nextPage() : null;
        return $view->renderWith("Dev/fixPermissions.html");
    }

    /**
     * templating.
     */
    protected function template($name, $data = array())
    {
        $view = new ViewAccessableData();

        return $view->customise($data)->renderWith($name);
    }
}

Core::addToHook("cli", array(Dev::class, "cli"));
