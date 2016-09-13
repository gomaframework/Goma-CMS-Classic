<?php
/**
 * Main file of Goma-CMS.
 * 
 * @package Goma\System
 * 
 * @author Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * 
 * @version 2.6.11
 */

error_reporting(E_ERROR | E_WARNING | E_PARSE | E_COMPILE_ERROR | E_NOTICE);

/*
 * first check if we use a good version ;)
 *
 * PHP 5.5 is necessary
 */
if (version_compare(phpversion(), "5.5.0", "<")) {
	header("HTTP/1.1 500 Server Error");
	echo file_get_contents(dirname(__FILE__) . "/templates/framework/php5.html");
	exit(1);
}

if (function_exists("ini_set")) {
	if (!@ini_get('display_errors')) {
		@ini_set('display_errors', 1);
	}
}

if (ini_get('safe_mode')) {
	define("IN_SAFE_MODE", true);
} else {
	define("IN_SAFE_MODE", false);
}

/* --- */

// some loading

defined("EXEC_START_TIME") OR define("EXEC_START_TIME", microtime(true));
define("IN_GOMA", true);
defined("MOD_REWRITE") OR define("MOD_REWRITE", true);

if (isset($_REQUEST["profile"]) || defined("PROFILE")) {
	require_once (dirname(__FILE__) . '/core/profiler.php');
	Profiler::init();
	defined("PROFILE") OR define("PROFILE", true);
} else {
	define("PROFILE", false);
}

// check if we are running on nginx without mod_rewrite
if (isset($_SERVER["SERVER_SOFTWARE"]) && preg_match('/nginx/i', $_SERVER["SERVER_SOFTWARE"]) && !MOD_REWRITE) {
	header("HTTP/1.1 500 Server Error");
	echo (file_get_contents(dirname(__FILE__) . "/templates/framework/nginx_no_rewrite.html"));
	exit(2);
}

// check if we are running without mod-php-xml
if (!class_exists("DOMDocument")) {
	header("HTTP/1.1 500 Server Error");
	echo (file_get_contents(dirname(__FILE__) . "/templates/framework/no_php_xml.html"));
	exit(3);
}

/* --- */

/**
 * default language code
 */
define("DEFAULT_TIMEZONE", "Europe/Berlin");

/**
 * the language-directory
 */
define('LANGUAGE_DIRECTORY', 'system/lang/');

/**
 * you shouldn't edit anything below this if you don't know, what you do
 */

define("PHP_MAIOR_VERSION", strtok(PHP_VERSION, "."));
/**
 * root
 */
define('ROOT', realpath(dirname(__FILE__) . "/../") . "/");
define("FRAMEWORK_ROOT", ROOT . "system/");

/**
 * current date
 */
define('DATE', time());

/**
 * TIME
 */
define('TIME', DATE);
define("NOW", DATE);

/**
 * status-constants for config.php
 */
define('STATUS_ACTIVE', 1);
define('STATUS_MAINTANANCE', 2);
define('STATUS_DISABLED', 0);

// version
define("GOMA_VERSION", "2.0RC6");
define("BUILD_VERSION", "124");

// fix for debug_backtrace
defined("DEBUG_BACKTRACE_PROVIDE_OBJECT") OR define("DEBUG_BACKTRACE_PROVIDE_OBJECT", true);

chdir(ROOT);

define("GOMA_FREE_SPACE", 100000000000);

// require data

if (PROFILE)
	Profiler::mark("core_requires");

// core
require_once (FRAMEWORK_ROOT . 'core/applibs.php');
require_once (FRAMEWORK_ROOT . 'core/CoreLibs/StaticsManager.php');
require_once (FRAMEWORK_ROOT . 'core/Object.php');
require_once (FRAMEWORK_ROOT . 'core/CoreLibs/GlobalSessionManager.php');
require_once (FRAMEWORK_ROOT . 'core/ClassManifest.php');
require_once (FRAMEWORK_ROOT . 'core/ClassInfo.php');
require_once (FRAMEWORK_ROOT . 'core/controller/RequestHandler.php');
require_once (FRAMEWORK_ROOT . 'libs/file/FileSystem.php');
require_once (FRAMEWORK_ROOT . 'libs/template/tpl.php');
require_once (FRAMEWORK_ROOT . 'libs/http/httpresponse.php');
require_once (FRAMEWORK_ROOT . 'libs/http/GomaResponse.php');
require_once (FRAMEWORK_ROOT . 'libs/html/GomaResponseBody.php');
require_once (FRAMEWORK_ROOT . 'core/Core.php');
require_once (FRAMEWORK_ROOT . 'core/controller/Director.php');
require_once (FRAMEWORK_ROOT . 'security/ISessionManager.php');
require_once (FRAMEWORK_ROOT . 'core/CoreLibs/CacheManager.php');
require_once (FRAMEWORK_ROOT . 'libs/sql/sql.php');

if (PROFILE)
	Profiler::unmark("core_requires");

// set error-handler
set_error_handler("Goma_ErrorHandler");

set_exception_handler("Goma_ExceptionHandler");

if(isCommandLineInterface()) {
	$args = getCommandLineArgs();
	if(isset($args["--configure"])) {
		define("DEV_MODE", true);
		include FRAMEWORK_ROOT . "installer/application/configure.php";
	}
}

if (file_exists(ROOT . '_config.php')) {
	// load configuration
	require (ROOT . '_config.php');

	// define the defined vars in config
	if (isset($logFolder)) {
		define("LOG_FOLDER", $logFolder);
	} else {
		writeSystemConfig();
		require (ROOT . '_config.php');
		define("LOG_FOLDER", $logFolder);
	}

	define("URLEND", $urlend);
	define("PROFILE_DETAIL", $profile_detail);

	defined("DEV_MODE") OR define("DEV_MODE", $dev || isPHPUnit() || isDevModeCLI());
	define("BROWSERCACHE", $browsercache);

	define('SQL_DRIVER', $sql_driver);
	define("SLOW_QUERY", isset($slowQuery) ? $slowQuery : 50);
	if (isset($defaultLang)) {
		define("DEFAULT_LANG", $defaultLang);
	} else {
		define("DEFAULT_LANG", "de");
	}

	if (DEV_MODE) {
		// error-reporting
		error_reporting(E_ERROR | E_WARNING | E_PARSE | E_COMPILE_ERROR | E_NOTICE);
	} else {
		error_reporting(E_ERROR | E_WARNING | E_PARSE);
		ClassManifest::addUnitTest();
	}

	// get a temporary root_path
	$root_path = str_replace("\\", "/", substr(__FILE__, 0, -22));
	$root_path = substr($root_path, strlen(realpath($_SERVER["DOCUMENT_ROOT"])));

	/*
	 * get the current application
	 */
	/** @var array $apps */
	if ($apps) {
		foreach ($apps as $data) {
			$subUrl = $root_path . "selectDomain/" . $data["directory"] . "/";
			if(isCommandLineInterface()) {
				if(isset($args["p"]) && $args["p"] == $data["directory"]) {
					$application = $data["directory"];
				}
			} else {
				if (substr($_SERVER["REQUEST_URI"], 0, strlen($subUrl)) == $subUrl) {
					$application = $data["directory"];
					define("BASE_SCRIPT", "selectDomain/" . $data["directory"] . "/");
					break;
				}
				if (isset($data['domain'])) {
					if (preg_match('/' . str_replace($data['domain'], '/', '\\/') . '$/i', $_SERVER['SERVER_NAME'])) {
						$application = $data["directory"];
						define("DOMAIN_LOAD_DIRECTORY", $data["directory"]);

						break;
					}
				}
			}
		}
		// no app found
		if (!isset($application)) {
			$application = $apps[0]["directory"];
		}
	} else {
		$application = "mysite";
	}
} else {
	$application = "mysite";

	define("URLEND", "/");
	define("PROFILE_DETAIL", false);

	define("DEV_MODE", false);
	define("BROWSERCACHE", true);

	define('SQL_DRIVER', "mysqli");

	define("LOG_FOLDER", "log");
	define("DEFAULT_LANG", "de");
}

define("SYSTEM_TPL_PATH", "system/templates");

// set timezone for security
date_default_timezone_set(DEFAULT_TIMEZONE);

loadApplication($application);
