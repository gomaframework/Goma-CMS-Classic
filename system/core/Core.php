<?php defined("IN_GOMA") OR die();

/**
 * Goma Core.
 *
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @package		Goma\Core
 * @version		3.4
 */
class Core extends gObject {
	const HEADER_HTML_HOOK = "getHeaderHTML";
	/**
	 *@var array
	 */
	public static $breadcrumbs = array();

	/**
	 * title of the page
	 *
	 */
	public static $title = "";

	/**
	 * headers
	 *
	 */
	public static $header = array();

	/**
	 * current languages
	 */
	public static $lang;

	/**
	 * An array that contains all CMS Variables.
	 *
	 * This arrays contains system wide system variables. They can be accessed by
	 * using the mehtods Core::setCMSVar and Core::getCMSVar.
	 *
	 * @see Core::setCMSVar() to set variables.
	 * @see Core::getCMSVar() to get variables.
	 */
	private static $cms_vars = array();

	/**
	 * this var contains the site_mode
	 */
	public static $site_mode = STATUS_ACTIVE;

	/**
	 * @var string
	 */
	public static $favicon;

	/**
	 * global hooks
	 */
	private static $hooks = array();

    /**
     * @var array
     */
    private static $localHooks = array();

	/**
	 * file which contains data from php://input
	 *
	 *@name phpInputFile
	 *@accesss public
	 */
	protected static $phpInputFile;

	/**
	 * callbacks for $_cms_blah
	 *
	 *@name cmsVarCallbacks
	 */
	private static $cmsVarCallbacks = array();
	
	/**
	 * cache-managers.
	*/
	public static $cacheManagerFramework;
	public static $cacheManagerApplication;

	/**
	 * repository.
	 *
	 * @var IModelRepository
	 */
	protected static $repository;

	/**
	 * inits the core
	 *
	 */
	public static function Init() {
		if(!isCommandLineInterface())
            ob_start();

		StaticsManager::addSaveVar("gObject", "extensions");
		StaticsManager::addSaveVar("gObject", "all_extra_methods");
		StaticsManager::AddSaveVar(Core::class, "hooks");
		StaticsManager::AddSaveVar(Core::class, "cmsVarCallbacks");
		StaticsManager::AddSaveVar("Director", "rules");

		self::callHook("beforeInitCore");

		if(isset($_SERVER['HTTP_X_IS_BACKEND']) && $_SERVER['HTTP_X_IS_BACKEND'] == 1) {
			Resources::addData("goma.ENV.is_backend = true;");
			define("IS_BACKEND", true);
		}

		// now init session
		if(PROFILE)
			Profiler::mark("session");
		GlobalSessionManager::Init();
		if(PROFILE)
			Profiler::unmark("session");
			
			
		self::initLang();

		if(defined("SQL_LOADUP"))
			member::Init();


		if(PROFILE)
			Profiler::mark("Core::Init");
		
		gObject::instance("Core")->callExtending("construct");
		self::callHook("init");

		if(PROFILE)
			Profiler::unmark("Core::Init");
	}

	/**
	 * init lang and rebuilds if rebuild is required.
	 */
	protected static function initLang() {
		$args = getCommandLineArgs();
		if(isset($args["--rebuild"]) || isset($args["-rebuild"])) {
			foreach(scandir(ROOT . LANGUAGE_DIRECTORY) as $file) {
				if($file != "." && $file != ".." && is_dir(ROOT . LANGUAGE_DIRECTORY . "/" . $file)) {
					i18n::Init($file);
				}
			}
		}

		// init language-support
		i18n::Init(i18n::SetSessionLang());
	}

	/**
	 * returns repository or throws error.
	 *
	 * @return IModelRepository
	 */
	public static function repository() {
		if(!isset(self::$repository)) {
			throw new LogicException("Repository not defined.");
		}

		return self::$repository;
	}

	/**
	 * returns repository or null.
	 *
	 * @return IModelRepository|null
	 */
	public static function getRepository() {
		return self::$repository;
	}

	/**
	 * sets repository.
	 * @param IModelRepository $repository
	 */
	public static function __setRepo($repository) {
		self::$repository = $repository;
	}

	/**
	 * returns session.
	 *
	 * @return ISessionManager
	 */
	public static function globalSession()
	{
		return GlobalSessionManager::globalSession();
	}

	/**
	 * sets global session.
	 *
	 * @param ISessionManager $session
	 */
	public static function __setSession($session)
	{
		GlobalSessionManager::__setSession($session);
	}

	/**
	 * inits cache-managers.
	*/
	public static function initCache() {
		self::$cacheManagerApplication = new CacheManager(ROOT . APPLICATION . "/temp");
		self::$cacheManagerFramework = new CacheManager(ROOT . "system/temp");
	}

	/**
	 * delete-cache.
	 * @param bool $force
	 * @throws SQLException
	 */
	public static function deleteCache($force = false) {

		if(PROFILE) Profiler::mark("delete_cache");

		if($force) {
			logging('Deleting FULL Cache');

			self::$cacheManagerApplication->deleteCache(0, true);
			self::$cacheManagerFramework->deleteCache(7200, true);

            g_SoftwareType::cleanUpUpdates();
		} else if(self::$cacheManagerApplication->shouldDeleteCache()) {
			logging("Deleting Cache");

			self::$cacheManagerApplication->deleteCache();
		}

		if(PROFILE) Profiler::unmark("delete_cache");
	}

	/**
	 * inits framework-resources.
	*/
	public static function InitResources() {
		// some vars for javascript
		Resources::addData("if(typeof current_project == 'undefined'){ var current_project = '" . CURRENT_PROJECT . "';var root_path = '" . ROOT_PATH . "';var ROOT_PATH = '" . ROOT_PATH . "';var BASE_SCRIPT = '" . BASE_SCRIPT . "'; goma.ENV.framework_version = '" . GOMA_VERSION . "-" . BUILD_VERSION . "'; var activelang = '".Core::$lang."'; }");


		Resources::add("system/libs/thirdparty/modernizr/modernizr.js", "js", "main");
		Resources::add("system/libs/thirdparty/jquery/jquery.js", "js", "main");
		Resources::add("system/libs/thirdparty/jquery/jquery.ui.js", "js", "main");
		Resources::add("system/libs/thirdparty/jResize/jResize.js", "js", "main");
		Resources::add("system/libs/javascript/loader.js", "js", "main");
		Resources::add("box.css", "css", "main");

		Resources::add("default.css", "css", "main");
		Resources::add("goma_default.css", "css", "main");

        if(isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/(?i)msie [5-8]/', $_SERVER['HTTP_USER_AGENT'])) {
            Resources::add("system/libs/thirdparty/respond/respond.min.js", "js", "main");
        }

		if(isset($_GET["debug"])) {
			Resources::enableDebug();
		}
	}

	/**
	 * @param string $title
	 * @param string $link
	 * @return bool
	 */
	public static function addBreadcrumb($title, $link) {
		self::$breadcrumbs[$link] = $title;
		return true;
	}

	/**
	 * @access public
	 * @param string $title
	 * @return bool
	 */
	public static function setTitle($title) {
		self::$title = convert::raw2text($title);
		return true;
	}

	/**
	 * @return string
	 */
	public static function getTitle()
	{
		return self::$title;
	}

	/**
	 * adds a callback to a hook
	 *
	 * @param string $name
	 * @param array $callback
	 */
	public static function addToHook($name, $callback) {
        if(is_a($name, Closure::class)) {
            throw new InvalidArgumentException();
        }

		if(!isset(self::$hooks[strtolower($name)]) || !in_array($callback, self::$hooks[strtolower($name)])) {
			self::$hooks[strtolower($name)][] = $callback;
		}
	}

    /**
     * adds a closure to a hook
     *
     * @param string $name
     * @param Closure $callback
     */
    public static function addToLocalHook($name, $callback) {
        if(!is_a($callback, Closure::class)) {
            throw new InvalidArgumentException();
        }

        if(!isset(self::$localHooks[strtolower($name)]) || !in_array($callback, self::$localHooks[strtolower($name)])) {
            self::$localHooks[strtolower($name)][] = $callback;
        }
    }

	/**
	 * calls all callbacks for a hook
	 *
	 * @param 		string 	$name of the hook
	 * @params.. 	mixed 	additional params up to 7
	 */
	public static function callHook($name, &$p1 = null, &$p2 = null, &$p3 = null, &$p4 = null, &$p5 = null, &$p6 = null, &$p7 = null) {
		if(isset(self::$hooks[strtolower($name)]) && is_array(self::$hooks[strtolower($name)])) {
			foreach(self::$hooks[strtolower($name)] as $callback) {
				if(is_callable($callback)) {
					call_user_func_array($callback, array(&$p1, &$p2, &$p3, &$p4, &$p5, &$p6, &$p7));
				}
			}
		}

        if(isset(self::$localHooks[strtolower($name)]) && is_array(self::$localHooks[strtolower($name)])) {
            foreach(self::$localHooks[strtolower($name)] as $callback) {
                if(is_callable($callback)) {
                    call_user_func_array($callback, array(&$p1, &$p2, &$p3, &$p4, &$p5, &$p6, &$p7));
                }
            }
        }
	}

	/**
	 * registers an CMS-Var-Callback
	 *
	 * @param 	Closure
	 * @param 	int $priority
	 */
	public static function addCMSVarCallback($callback, $priority = 10) {
		if(is_callable($callback)) {
			self::$cmsVarCallbacks[$priority][] = $callback;
		}
	}

	/**
	 * Sets a CMS variable.
	 *
	 * @see Core::$cms_vars for the variable containing array.
	 * @see Core::getCMSVar() to get variables.
	 *
	 * @param string $name Variable name.
	 * @param string $value Value of the variable.
	 *
	 * @return void
	 */
	public static function setCMSVar($name, $value) {
		self::$cms_vars[$name] = $value;
	}

	/**
	 * Returns a CMS variable.
	 *
	 * @see Core::$cms_vars for the variable containing array.
	 * @see Core::setCMSVar() to set variables.
	 *
	 * @param string $name Variable name.
	 *
	 * @return mixed Value of the variable.
	 */
	public static function getCMSVar($name) {
		if(PROFILE)
			Profiler::mark("Core::getCMSVar");
		if($name == "lang") {
			if(PROFILE)
				Profiler::unmark("Core::getCMSVar");
			return self::$lang;
		}

		if(isset(self::$cms_vars[$name])) {
			if(PROFILE)
				Profiler::unmark("Core::getCMSVar");
			return self::$cms_vars[$name];

		}

		if($name == "year") {
			if(PROFILE)
				Profiler::unmark("Core::getCMSVar");
			return date("Y");

		}

		if($name == "tpl") {
			if(PROFILE)
				Profiler::unmark("Core::getCMSVar");
			return self::getTheme();
		}

		if($name == "user") {
			self::$cms_vars["user"] = (member::$loggedIn) ? convert::raw2text(member::$loggedIn->title()) : null;
			if(PROFILE)
				Profiler::unmark("Core::getCMSVar");
			return self::$cms_vars["user"];
		}

		krsort(self::$cmsVarCallbacks);
		foreach(self::$cmsVarCallbacks as $callbacks) {
			foreach($callbacks as $callback) {
				if(($data = call_user_func_array($callback, array($name))) !== null) {
					if(PROFILE)
						Profiler::unmark("Core::getCMSVar");
					return $data;
				}
			}
		}

		if(PROFILE)
			Profiler::unmark("Core::getCMSVar");
		return isset($GLOBALS["cms_" . $name]) ? $GLOBALS["cms_" . $name] : null;

	}

	/**
	 * sets the theme
	 *
	 */
	public static function setTheme($theme) {
		self::setCMSVar("theme", $theme);
	}

	/**
	 * gets the theme
	 *
	 */
	public static function getTheme() {
		return self::getCMSVar("theme") ? self::getCMSVar("theme") : "default";
	}

	/**
	 * sets a header-field
	 *
	 * @param string $name
	 */
	public static function setHeader($name, $value, $overwrite = true) {
		if($overwrite || !isset(self::$header[strtolower($name)]))
			self::$header[strtolower($name)] = array("name" => $name, "value" => $value);
	}

	/**
	 * sets a http-equiv header-field
	 *
	 */
	public static function setHTTPHeader($name, $value, $overwrite = true) {
		if($overwrite || !isset(self::$header[strtolower($name)]))
			self::$header[strtolower($name)] = array("name" => $name, "value" => $value, "http" => true);
	}

	/**
	 * makes a new entry in the log, because the method is deprecated
	 * but if the given version is higher than the current, nothing happens
	 * if DEV_MODE is not true, nothing happens
	 *
	 *@param int - version
	 *@param string - method
	 */
	public static function Deprecate($version, $newmethod = "") {
		if(DEV_MODE) {
			if(!version_compare(GOMA_VERSION . "-" . BUILD_VERSION, $version, "<")) {

				$trace = @debug_backtrace();

				$method = (isset($trace[1]["class"])) ? $trace[1]["class"] . "::" . $trace[1]["function"] : $trace[1]["function"];
				$file = isset($trace[1]["file"]) ? $trace[1]["file"] : (isset($trace[2]["file"]) ? $trace[2]["file"] : "Undefined");
				$line = isset($trace[1]["line"]) ? $trace[1]["line"] : (isset($trace[2]["line"]) ? $trace[2]["line"] : "Undefined");
				if($newmethod == "")
					log_error("DEPRECATED: " . $method . " is marked as DEPRECATED in " . $file . " on line " . $line);
				else
					log_error("DEPRECATED: " . $method . " is marked as DEPRECATED in " . $file . " on line " . $line . ". Please use " . $newmethod . " instead.");
			}
		}
	}

	/**
	 * gets all headers
	 *
	 */
	public static function getHeaderHTML() {
		$html = "";
		$i = 0;
		foreach(self::getHeader() as $data) {
			if($i == 0)
				$i++;
			else
				$html .= "		";
			if(isset($data["http"])) {
				$html .= "<meta http-equiv=\"" . $data["name"] . "\" content=\"" . $data["value"] . "\" />\n";
			} else {
				$html .= "<meta name=\"" . $data["name"] . "\" content=\"" . $data["value"] . "\" />\n";
			}
		}

		if(!empty(self::$favicon)) {
			$html .= '		<link rel="icon" href="' . self::$favicon . '" type="image/x-icon" />';
			$html .= '		<link rel="apple-touch-icon-precomposed" href="'.RetinaPath(self::$favicon).'" />';
		}

		Core::callHook(self::HEADER_HTML_HOOK, $html);

		return $html;
	}

	/**
	 * gets all headers
	 *
	 */
	public static function getHeader() {

		self::callHook("setHeader");

		self::setHeader("generator", "Goma " . GOMA_VERSION . " with " . ClassInfo::$appENV["app"]["name"], false);

		return self::$header;
	}

	/**
	 * checks if ajax
	 * @return bool
	 */
	public static function is_ajax() {
		return (isset($_REQUEST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest"));
	}

	/**
	 * clean-up for log-files
	 *
	 *@param int - days
	 */
	public static function cleanUpLog($count = 30) {
		$logDir = ROOT . CURRENT_PROJECT . "/" . LOG_FOLDER;
		foreach(scandir($logDir) as $type) {
			if($type != "." && $type != ".." && is_dir($logDir . "/" . $type)) {
				foreach (scandir($logDir . "/" . $type . "/") as $date) {
					if ($date != "." && $date != "..") {

						if (preg_match('/^(\d{2})\-(\d{2})\-(\d{2})$/', $date, $matches)) {
							$time = mktime(0, 0, 0, $matches[1], $matches[2], $matches[3]);
							if ($time < NOW - 60 * 60 * 24 * $count || isset($_GET["forceAll"])) {
								FileSystem::delete($logDir . "/" . $type . "/" . $date);
							}
						}
					}
				}
			}
		}
	}

	/**
	 * checks if debug-mode
	 *
	 */
	public static function is_debug() {
		return (Permission::check(10) && isset($_GET["debug"]));
	}

	/**
	 * gives back if the current logged in admin want's to be see everything as a
	 * simple user
	 *
	 */
	public static function adminAsUser() {
		return (!defined("IS_BACKEND") && GlobalSessionManager::globalSession()->hasKey(SystemController::ADMIN_AS_USER));
	}

	/**
	 * renders the page
	 * @param string $url
	 */
	public function render($url) {
		self::InitResources();

		Director::direct($url);
	}

	/**
	 * renders cli.
	 */
	public function cli()
	{
        $args = getCommandLineArgs();
        if(isset($args["-cron"])) {
            CronController::handleCron();
        }

        $code = 0;
		Core::callHook("cli", $args, $code);

        Core::callHook("onBeforeShutdown");

        if($code != 0) {
            exit($code);
        }
	}
}
