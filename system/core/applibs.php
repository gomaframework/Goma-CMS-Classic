<?php defined("IN_GOMA") OR die();
/**
 * This file provides necessary functions for Goma.
 *
 * @package Goma\System\Core
 *
 * @author Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 *
 * @version 1.0.5
 */

$__errorCount = 0;

/**
 * Load a language file from /languages.
 *
 * @param string $name Filename
 * @param string $directory Subdirectory
 *
 * @return void
 */
function loadlang($name = "lang", $directory = "") {
	i18n::addLang($directory . '/' . $name);
}

/**
 * Generates a random string.
 *
 * @param int $length Length of the string.
 * @param boolean $numeric Are numbers allowed?
 *
 * @return string
 */
function randomString($length, $numeric = true) {
	$possible = "ABCDEFGHJKLMNPRSTUVWXYZabcdefghijkmnpqrstuvwxyz";
	if($numeric === true) {
		$possible .= "123456789";
	}
	$s = "";
	for($i = 0; $i < $length; $i++) {
		$s .= $possible{mt_rand(0, strlen($possible) - 1)};
	}
	return $s;
}

/**
 * Looks up for a localized version of a string.
 *
 * @param string $name Name identifier for the string.
 * @param string $default Default value for non existent localized strings.
 *
 * @return string Localized string or $default
 */
function lang($name, $default = "") {
	$name = strtoupper($name);
	if(isset($GLOBALS["lang"][$name])) {
		$lang = $GLOBALS["lang"][$name];
	} else if($default) {
		$lang = $default;
	} else {
		$lang = $name;
	}

	if(!strpos($lang, ">\n") && !strpos($lang, "</")) {
		return nl2br($lang);
	} else {
		return $lang;
	}
}

/**
 * Merges arrays recursive.
 *
 * Merges any number of arrays / parameters recursively, replacing
 * entries with string keys with values from latter arrays.
 * If the entry or the next value to be assigned is an array, then it
 * automagically treats both arguments as an array.
 * Numeric entries are appended, not replaced, but only if they are
 * unique.
 *
 * @author mark dot roduner at gmail dot com
 * @link http://php.net/manual/de/function.array-merge-recursive.php
 *
 * @return array
 **/
function array_merge_recursive_distinct() {
	$arrays = func_get_args();
	$base = array_shift($arrays);
	if(!is_array($base))
		$base = empty($base) ? array() : array($base);
	foreach($arrays as $append) {
		if(!is_array($append))
			$append = array($append);
		foreach($append as $key => $value) {
			if(!array_key_exists($key, $base) and !is_numeric($key)) {
				$base[$key] = $append[$key];
				continue;
			}
			if(is_array($value) or is_array($base[$key])) {
				$base[$key] = array_merge_recursive_distinct($base[$key], $append[$key]);
			} else if(is_numeric($key)) {
				if(!in_array($value, $base))
					$base[] = $value;
			} else {
				$base[$key] = $value;
			}
		}
	}
	return $base;
}

/**
 * Stores session data in a file.
 *
 * Because storing many data in a session is slow, the data is stored in a file.
 * This data can be accessed with an ID, that is stored in the session instead.
 *
 * @see session_restore() to restore data from a session.
 *
 * @param string $key Data identification key
 * @param mixed $data The data, that has to be stored.
 */
function session_store($key, $data) {
	GlobalSessionManager::globalSession()->set($key, $data);
}

/**
 * Accesses session data.
 *
 * Because storing many data in a session is slow, the data is stored in a file.
 * This data can be accessed with an ID, that is stored in the session instead.
 *
 * @see session_restore() to store data in a session.
 *
 * @param string $key Data identification key
 *
 * @return mixed Data on success, otherwise null.
 */
function session_restore($key) {
	return GlobalSessionManager::globalSession()->get($key);
}

/**
 * Checks for a key, if he is linked with session data.
 *
 * @param string $key Data identification key
 *
 * @return boolean
 */
function session_store_exists($key) {
	return GlobalSessionManager::globalSession()->hasKey($key);
}

/**
 * Gets the redirect.
 *
 * @param boolean $parentDir Get only the name of the parent directory in the
 * url.
 *
 * @return string
 * @deprecated
 */
function getRedirect($parentDir = false, $controller = null) {
	// AJAX Request
	if(Core::is_ajax() && isset($_SERVER["HTTP_X_REFERER"]) && isURLFromServer($_SERVER["HTTP_X_REFERER"], $_SERVER["SERVER_NAME"])) {
		return htmlentities($_SERVER["HTTP_X_REFERER"], ENT_COMPAT, "UTF-8", false);
	}

	if($parentDir) {
		if(isset($_GET["redirect"]) && isURLFromServer($_GET["redirect"], $_SERVER["SERVER_NAME"])) {
			return htmlentities($_GET["redirect"], ENT_COMPAT, "UTF-8", false);
		} else if(isset($controller)) {
			return htmlentities(ROOT_PATH . BASE_SCRIPT . $controller->originalNamespace, ENT_COMPAT, "UTF-8", false);
		} else {
			// TODO What is with redirect from other sites with other URLEND?
			if(URLEND == "/") {
				$uri = substr($_SERVER["REQUEST_URI"], 0, strrpos($_SERVER["REQUEST_URI"], "/"));
				return htmlentities(substr($uri, 0, strrpos($uri, "/")) . URLEND, ENT_COMPAT, "UTF-8", false);
			} else {
				return isset($_SERVER["REQUEST_URI"]) ? htmlentities(substr($_SERVER["REQUEST_URI"], 0, strrpos($_SERVER["REQUEST_URI"], "/")) . URLEND, ENT_COMPAT, "UTF-8", false) : null;
			}
		}
	} else {
		if(isset($_GET["redirect"]) && isURLFromServer($_GET["redirect"], $_SERVER["SERVER_NAME"])) {
			return htmlentities($_GET["redirect"], ENT_COMPAT, "UTF-8", false);
		} else if(isset($controller)) {
			return htmlentities(ROOT_PATH . BASE_SCRIPT . $controller->originalNamespace, ENT_COMPAT, "UTF-8", false);
		} else {
			return isset($_SERVER["REQUEST_URI"]) ? htmlentities($_SERVER["REQUEST_URI"], ENT_COMPAT, "UTF-8", false) : null;
		}

	}
}

function isURLFromServer($url, $server) {
	if(preg_match('/^(http|https|ftp)\:\/\/(.*)/i', $url, $matches)) {
		if(!isset($server)) {
			$server = $_SERVER["SERVER_NAME"];
		}

		if(strtolower(substr($matches[2], 0, strlen($server))) != strtolower($server)) {
			return "";
		} else {
			if(strtolower($matches[2]) != $server && strtolower(substr($matches[2], 0, strlen($server) + 1)) != strtolower($server) . "/") {
				return "";
			}
		}
	}

	return $url;
}

/**
 * generates a translated date.
 */
function goma_date($format, $date = NOW) {

	$str = date($format, $date);

	require (ROOT . LANGUAGE_DIRECTORY . Core::getCMSVar("lang") . "/calendar.php");

	/** @var array $calendar */
	$str = str_replace(array_keys($calendar), array_values($calendar), $str);
	return $str;
}

/**
 * Places a file in the application folder, that indicates Goma, that this
 * project is unavailable.
 *
 * @see makeProjectAvailable() to enable projects.
 * @see isProjectUnavailable() to check, if a project is disabled.
 *
 * @param string $project Name of the project, default is the current
 * application.
 *
 * @param string|null $ip
 */
function makeProjectUnavailable($project = APPLICATION, $ip = null) {
	$ip = isCommandLineInterface() ? "cli" : ((isset($ip) ? $ip : $_SERVER["REMOTE_ADDR"]));
	if(!file_put_contents(ROOT . $project . "/503.goma", $ip, LOCK_EX)) {
		echo ("Could not make project unavailable.");
		exit(11);
	}
	chmod(ROOT . $project . "/503.goma", 0777);
}

/**
 * Removes the project unavailable file, that indicates Goma, that this project
 * is unavailable.
 *
 * @see makeProjectUnavailable() to disable projects.
 * @see isProjectUnavailable() to check, if a project is disabled.
 *
 * @param string $project Name of the project, default is the current
 * application.
 *
 * @return void
 */
function makeProjectAvailable($project = APPLICATION) {
	if(file_exists(ROOT . $project . "/503.goma")) {
		@unlink(ROOT . $project . "/503.goma");
	}
}

/**
 * Checks, if a project is unavailable.
 *
 * @see makeProjectUnavailable() to disable projects.
 * @see makeProjectAvailable() to enable projects.
 *
 * @param string $project Name of the project, default is the current
 * application.
 *
 * @return bool
 */
function isProjectUnavailable($project = APPLICATION) {
	clearstatcache();
	return (file_exists(ROOT . $project . "/503.goma") && filemtime(ROOT . $project . "/503.goma") > NOW - 10);
}

function isProjectUnavailableForIP($ip, $project = APPLICATION) {
	return isProjectUnavailable($project) && file_get_contents(ROOT . $project . "/503.goma") != $ip;
}


/**
 * Writes the system configuration.
 *
 * @see writeProjectConfig() to write the config for a project.
 *
 * @param array[] $data An array with configuration variables.
 *
 * @throws ProjectConfigWriteException
 */
function writeSystemConfig($data = array()) {

	// first set defaults
	$apps = array();
	$sql_driver = "mysqli";
	$dev = false;
	$urlend = "/";
	$logFolder = "log_" . randomString(5);
	$privateKey = randomString(15);
	$defaultLang = defined("DEFAULT_LANG") ? DEFAULT_LANG : "de";
	$slowQuery = 50;

	if(file_exists(ROOT . "_config.php"))
		include (ROOT . "_config.php");

	foreach($data as $key => $val) {
		if(isset($$key))
			$$key = $val;
	}

	$contents = file_get_contents(FRAMEWORK_ROOT . "core/samples/config_main.sample.php");
	preg_match_all('/\{([a-zA-Z0-9_]+)\}/Usi', $contents, $matches);
	foreach($matches[1] as $name) {
		if(isset($$name))
			$contents = str_replace('{' . $name . '}', var_export($$name, true), $contents);
		else
			$contents = str_replace('{' . $name . '}', var_export("", true), $contents);
	}

	if(@file_put_contents(ROOT . "_config.php", $contents, LOCK_EX)) {
		@chmod(ROOT . "_config.php", 0644);
	} else {
		throw new ProjectConfigWriteException("./_config.php", "Could not write System-Config.");
	}
}

/**
 * Writes the config of a project.
 *
 * @see writeSystemConfig() to write the system config.
 *
 * @param array[] $data An array with configuration variables.
 * @param string $project Name of the project, default is CURRENT_PROJECT.
 *
 * @throws ProjectConfigWriteException
 */
function writeProjectConfig($data = array(), $project = CURRENT_PROJECT) {
	$config = $project . "/config.php";

	if(file_exists($config)) {
		// get current data
		include ($config);
		$defaults = (array)$domaininfo;
	} else {
		$defaults = array(
			"status" => 1,
			"date_format_date" => "d.m.Y",
			"date_format_time"	=> " H:i",
			"timezone" => DEFAULT_TIMEZONE,
			"lang" => DEFAULT_LANG,
			"safe_mode"	=> false
		);
	}

	if(!isset($defaults["lang"]) || !isset($defaults["status"])) {
		throw new LogicException("Something got wrong initializing project config.");
	}

	$new = array_merge($defaults, $data);
	$info = array();
	$info["status"] = $new["status"];
	
	if(isset($new["date_format_date"]))
		$info["date_format_date"] = $new["date_format_date"];
		
	if(isset( $new["date_format_time"]))
		$info["date_format_time"] = $new["date_format_time"];
	
	$info["timezone"] = $new["timezone"];
	$info["lang"] = $new["lang"];

	$info["safe_mode"] = (bool)(isset($new["safe_mode"]) ? $new["safe_mode"] : false);

	if(isset($new["db"]))
		$info["db"] = $new["db"];

	if(defined("SQL_DRIVER_OVERRIDE") && !isset($info["sql_driver"])) {
		$info["sql_driver"] = SQL_DRIVER_OVERRIDE;
	}

	$config_content = file_get_contents(FRAMEWORK_ROOT . "core/samples/config_locale.sample.php");
	$config_content = str_replace('{info}', var_export($info, true), $config_content);
	$config_content = str_replace('{folder}', $project, $config_content);
	if(@file_put_contents($config, $config_content, LOCK_EX)) {
		@chmod($config, 0644);
	} else {
        throw new ProjectConfigWriteException($config, "Could not write Project-Config.");
	}
}

/**
 * Gets the private key of the installation.
 *
 * @return string 15 chars private key
 * @throws ProjectConfigWriteException
 */
function getPrivateKey() {
	if(!file_exists(ROOT . "_config.php")) {
		writeSystemConfig();
	}

	include (ROOT . "_config.php");

	return $privateKey;
}

/******************** project management ********************/

/**
 * sets a project-folder in the project-stack
 *
 * @param $project
 * @param null $domain
 * @return bool|void
 * @throws ProjectConfigWriteException
 * @access public
 */
function setProject($project, $domain = null) {
	if(file_exists(ROOT . "_config.php")) {
		include (ROOT . "_config.php");
	} else {
		$apps = array();
	}

	$app = array("directory" => $project);
	if(isset($domain)) {
		$app["domain"] = $domain;
	}

	// first check existing
	foreach($apps as $key => $data) {
		if($data["directory"] == $app["directory"]) {
			if(!isset($app["domain"]) || (isset($data["domain"]) && $data["domain"] == $app["domain"])) {

				return true;
			} else {
				$apps[$key]["domain"] = $app["domain"];
				writeSystemConfig(array("apps" => $apps));
				return;
			}
		}
	}
	$apps[] = $app;

	writeSystemConfig(array("apps" => $apps));
}

/**
 * removes a given project from project-stack
 *
 * @name removeProject
 * @return bool|void
 * @throws ProjectConfigWriteException
 * @access public
 */
function removeProject($project) {
	if(file_exists(ROOT . "_config.php")) {
		include (ROOT . "_config.php");
	} else {
		return true;
	}

	foreach($apps as $key => $data) {
		if($data["directory"] == $project) {
			unset($apps[$key]);
		}
	}

	$apps = array_values($apps);

	writeSystemConfig(array("apps" => $apps));
}

// alias for setProject
function addProject($project, $domain = null) {
	return setProject($project, $domain);
}

/**
 * @url http://de3.php.net/manual/en/function.intval.php#79766
 */
function str2int($string, $concat = true) {
	$length = strlen($string);
	for($i = 0, $int = '', $concat_flag = true; $i < $length; $i++) {
		if(is_numeric($string[$i]) && $concat_flag) {
			$int .= $string[$i];
		} else if(!$concat && $concat_flag && strlen($int) > 0) {
			$concat_flag = false;
		}
	}

	return (int)$int;
}

/**
 * this parses lanuage variables in a string, e.g. {$_lang_imprint}
 *
 * @param string - the string to parse
 * @param array - a array of variables in the lanuage like %e%
 * @return string - the parsed string
 */
function parse_lang($str, $arr = array()) {
	return preg_replace_callback('/\{\$_lang_(.*)\}/Usi', "var_lang_callback", $str);
	// find lang vars
}

function var_lang_callback($data) {
	return var_lang($data[1]);
}

/**
 * parses the %e% in the string
 *
 * @param string - the name of the languagevar
 * @param array - the array of variables
 * @return string - the parsed string
 */
function var_lang($str, $replace = array()) {
	if(!is_string($str))
		throw new InvalidArgumentException("first argument of var_lang must be string.");
	
	$language = lang($str, $str);
	preg_match_all('/%(.*)%/', $language, $regs);
	foreach($regs[1] as $key => $value) {

		$re = $replace[$value];
		$language = preg_replace("/%" . preg_quote($value, '/') . "%/", $re, $language);
	}

	return $language;
}

/**
 * in goma we now compare version and buildnumber seperate
 *
 * @return bool|int
 */
function goma_version_compare($v1, $v2, $operator = null) {
	// first split version
	if(strpos($v1, "-") !== false) {
		$version1 = substr($v1, 0, strpos($v1, "-"));
		$build1 = substr($v1, strpos($v1, "-") + 1);
	} else {
		$version1 = $v1;
	}

	if(strpos($v2, "-") !== false) {
		$version2 = substr($v2, 0, strpos($v2, "-"));
		$build2 = substr($v2, strpos($v2, "-") + 1);
	} else {
		$version2 = $v2;
	}

	if(!isset($build1) || !isset($build2)) {
		return version_compare($version1, $version2, $operator);
	}

	if(isset($operator)) {
		switch($operator) {
			case "gt":
			case ">":
				return version_compare($build1, $build2, ">");
				break;
			case "lt":
			case "<":
				return version_compare($build1, $build2, "<");
				break;
			case "eq":
			case "=":
			case "==":
				if(version_compare($version1, $version2, "==") && version_compare($build1, $build2, "==")) {
					return true;
				}
				return false;
				break;
			case ">=":
			case "ge":
				return version_compare($build1, $build2, ">=");
				break;
			case "<=":
			case "le":
				return version_compare($build1, $build2, "<=");
				break;
			case "!=":
			case "<>":
			case "ne":
				return version_compare($build1, $build2, "<>");
				break;
		}
	} else {
		if(version_compare($build1, $build2, ">")) {
			return 1;
		} else if(version_compare($build1, $build2, "==")) {
			return 0;
		} else {
			return -1;
		}
	}

	return false;
}

/**
 * PHP-Error-Handdling
 */
function Goma_ErrorHandler($errno, $errstr, $errfile, $errline, $errcontext) {
	$uri = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : (isset($_SERVER["argv"]) ? implode(" ", $_SERVER["argv"]) : null);

	switch ($errno) {
		case E_ERROR:
		case E_CORE_ERROR:
		case E_COMPILE_ERROR:
		case E_PARSE:
		case E_USER_ERROR:
		case E_RECOVERABLE_ERROR:
			log_error("PHP-USER-Error: " . $errno . " " . $errstr . " in " . $errfile . " on line " . $errline . ".");

			if(!isCommandLineInterface()) {
				HTTPResponse::setResHeader(500);
				HTTPResponse::sendHeader();
				$content = file_get_contents(ROOT . "system/templates/framework/phperror.html");
				$content = str_replace('{BASE_URI}', BASE_URI, $content);
				$content = str_replace('{$errcode}', 6, $content);
				$content = str_replace('{$errname}', "PHP-Error $errno", $content);
				$content = str_replace('{$errdetails}', $errstr . " on line $errline in file $errfile", $content);
				$content = str_replace('$uri', $uri, $content);
				echo $content;
			}
			exit(7);
			break;

		case E_WARNING:
		case E_CORE_WARNING:
		case E_COMPILE_WARNING:
		case E_USER_WARNING:
			if(shouldOutputLogs()) {
				if (strpos($errstr, "chmod") === false && strpos($errstr, "unlink") === false) {
					log_error("PHP-USER-Warning: " . $errno . " " . $errstr . " in " . $errfile . " on line " . $errline . ".");
					if (DEV_MODE && !isset($_GET["ajax"]) && (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != "XMLHttpRequest")) {
						echo "<b>WARNING:</b> [$errno] $errstr in $errfile on line $errline<br />\n";
					}
				}
			}
			break;
		case E_USER_NOTICE:
		case E_NOTICE:
			if(shouldOutputLogs()) {
				if (strpos($errstr, "chmod") === false && strpos($errstr, "unlink") === false) {
					logging("Notice: [$errno] $errstr in $errfile on line $errline");
					if (DEV_MODE && !isset($_GET["ajax"]) && (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != "XMLHttpRequest"))
						echo "<b>NOTICE:</b> [$errno] $errstr in $errfile on line $errline<br />\n";
				}
			}
			break;
		case E_STRICT:
			// nothing
			break;
		default:
			log_error("PHP-Error: " . $errno . " " . $errstr . " in " . $errfile . " on line " . $errline . ".");

			if(!isCommandLineInterface()) {
				HTTPResponse::setResHeader(500);
				HTTPResponse::sendHeader();
				$content = file_get_contents(ROOT . "system/templates/framework/phperror.html");
				$content = str_replace('{BASE_URI}', BASE_URI, $content);
				$content = str_replace('{$errcode}', 6, $content);
				$content = str_replace('{$errname}', "PHP-Error: " . $errno, $content);
				$content = str_replace('{$errdetails}', $errstr . " on line $errline in file $errfile", $content);
				$content = str_replace('$uri', $uri, $content);
				echo $content;
			}
			exit(4);
	}

	global $__errorCount;
	$__errorCount++;

	// block PHP's internal Error-Handler
	return true;
}

/**
 * @param Exception $exception
 */
function Goma_ExceptionHandler($exception) {
	$uri = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : (isset($_SERVER["argv"]) ? implode(" ", $_SERVER["argv"]) : null);

	if(isset($exception->isIgnorable) && $exception->isIgnorable) {
		return;
	}

	log_exception($exception);

	$details = getUserDetailsFromException($exception);
	$current = $exception;
	while($current = $current->getPrevious()) {
		$details .= getUserDetailsFromException($current);
	}

	$content = file_get_contents(ROOT . "system/templates/framework/phperror.html");
	$content = str_replace('{BASE_URI}', BASE_URI, $content);
	$content = str_replace('{$errcode}', $exception->getCode(), $content);
	$content = str_replace('{$errname}', get_class($exception), $content);
	$content = str_replace('{$errdetails}', $details, $content);
	$content = str_replace('$uri', $uri, $content);

	$exitCode = $exception->getCode() != 0 ? $exception->getCode() : 8;

	if(!isCommandLineInterface()) {
		if (gObject::method_exists($exception, "http_status")) {
			HTTPResponse::setResHeader($exception->http_status());
		} else {
			HTTPResponse::setResHeader(500);
		}
		HTTPResponse::sendHeader();
		echo $content;

		echo("\nExiting with code " . $exitCode);
	}

	exit($exitCode);
}

/**
 * @param Throwable $e
 * @return string
 */
function getUserDetailsFromException($e) {
    $trace = method_exists($e, "getTraceForUser") ? $e->getTraceForUser() : $e->getTraceAsString();
    return getExceptionMessageOrClass($e) . "\n<br />\nin " . $e->getFile() . " on line ".$e->getLine() . "<br />\n<textarea style=\"width: 100%; height: 300px;\">" . $trace . "</textarea>";
}

/**
 * @param Throwable $e
 * @return string
 */
function getExceptionMessageOrClass($e) {
    return $e->getMessage() ? $e->getMessage() : get_class($e) . ": " . $e->getCode();
}

/**
 * @param Throwable $exception
 */
function log_exception($exception) {
	$uri = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : (isset($_SERVER["argv"]) ? implode(" ", $_SERVER["argv"]) : null);

	$message = get_class($exception) . " " . $exception->getCode() . ":\n\n" . $exception->getMessage() . "\n".
		exception_get_dev_message($exception)." in ".
		$exception->getFile() . " on line ".$exception->getLine().".\n\n Backtrace: " . $exception->getTraceAsString();
	$current = $exception;
	while($current = $current->getPrevious()) {
		$message .= "\nPrevious: " . $current->getMessage() . "\n" . exception_get_dev_message($current)."\n in "
			. $current->getFile() . " on line ".$current->getLine() . ".\n" . $current->getTraceAsString();
	}
	log_error($message);
	
	$debugMsg = "URL: " . $uri . "\nGoma-Version: " . GOMA_VERSION . "-" . BUILD_VERSION . "\nApplication: " . print_r(ClassInfo::$appENV, true) . "\n\n" . $message;
	debug_log($debugMsg);
}

function exception_get_dev_message($exception) {
	if(method_exists($exception, "getDeveloperMessage")) {
		return "\n\t\t" . str_replace("\n", "\n\t\t", $exception->getDeveloperMessage()) . "\n";
	}

	return "";
}

/**
 * @return int
 */
function getMemoryLimit() {
	if(function_exists("ini_get")) {
		$memory_limit = ini_get('memory_limit');
		if (preg_match('/^(\d+)(.)$/', $memory_limit, $matches)) {
			if ($matches[2] == 'M') {
				return $matches[1] * 1024 * 1024; // nnnM -> nnn MB
			} else if ($matches[2] == 'K') {
				return $matches[1] * 1024; // nnnK -> nnn KB
			}
		}
	}

	return 64 * 1024 * 1024;
}

//!Logging

/**
 * logging
 *
 * log an error
 * @param string $errorString error
 */
function log_error($errorString) {
	if(defined("CURRENT_PROJECT")) {
		if (PROFILE)
			Profiler::mark("log_error");
		FileSystem::requireFolder(ROOT . CURRENT_PROJECT . "/" . LOG_FOLDER . "/error/");
		if (isset($GLOBALS["error_logfile"])) {
			$file = $GLOBALS["error_logfile"];
		} else {
			FileSystem::requireFolder(ROOT . CURRENT_PROJECT . "/" . LOG_FOLDER . "/error/" . date("m-d-y"));
			$folder = ROOT . CURRENT_PROJECT . "/" . LOG_FOLDER . "/error/" . date("m-d-y") . "/";
			$file = $folder . "1.log";
			$i = 1;
			while (file_exists($folder . $i . ".log") && filesize($file) > 10000) {
				$i++;
				$file = $folder . $i . ".log";
			}
			$GLOBALS["error_logfile"] = $file;
		}
		$date_format = (defined("DATE_FORMAT")) ? DATE_FORMAT : "Y-m-d H:i:s";
		if (!file_exists($file)) {
			FileSystem::write($file, date($date_format) . ': ' . $errorString . "\n\n", null, 0777);
		} else {
			FileSystem::write($file, date($date_format) . ': ' . $errorString . "\n\n", FILE_APPEND, 0777);
		}
	}

	if(shouldOutputLogs()) {
		echo $errorString . "\n";
	}

	if(PROFILE)
		Profiler::unmark("log_error");
}

/**
 * log things
 *
 * @param string - log-string
 */
function logging($string) {
	if(defined("CURRENT_PROJECT")) {
		if (PROFILE)
			Profiler::mark("logging");

		FileSystem::requireFolder(ROOT . CURRENT_PROJECT . "/" . LOG_FOLDER . "/log/");
		$date_format = (defined("DATE_FORMAT")) ? DATE_FORMAT : "Y-m-d H:i:s";
		if (isset($GLOBALS["log_logfile"])) {
			$file = $GLOBALS["log_logfile"];
		} else {
			FileSystem::requireFolder(ROOT . CURRENT_PROJECT . "/" . LOG_FOLDER . "/log/" . date("m-d-y"));
			$folder = ROOT . CURRENT_PROJECT . "/" . LOG_FOLDER . "/log/" . date("m-d-y") . "/";
			$file = $folder . "1.log";
			$i = 1;
			while (file_exists($folder . $i . ".log") && filesize($file) > 10000) {
				$i++;
				$file = $folder . $i . ".log";
			}
			$GLOBALS["log_logfile"] = $file;
		}
		if (!file_exists($file)) {
			FileSystem::write($file, date($date_format) . ': ' . $string . "\n\n", null, 0777);
		} else {
			FileSystem::write($file, date($date_format) . ': ' . $string . "\n\n", FILE_APPEND, 0777);
		}
	}

	if(shouldOutputLogs()) {
		echo $string . "\n";
	}

	if(PROFILE)
		Profiler::unmark("logging");
}

/**
 * logs debug-information
 *
 * this information may uploaded to the goma-server for debug-use
 *
 *@param string - debug-string
 */
function debug_log($data) {
	if(!defined("CURRENT_PROJECT")) {
		return;
	}

	FileSystem::requireFolder(ROOT . CURRENT_PROJECT . "/" . LOG_FOLDER . "/debug/");
	FileSystem::requireFolder(ROOT . CURRENT_PROJECT . "/" . LOG_FOLDER . "/debug/" . date("m-d-y"));
	$folder = ROOT . CURRENT_PROJECT . "/" . LOG_FOLDER . "/debug/" . date("m-d-y") . "/" . date("H_i_s");
	$file = $folder . "-1.log";
	$i = 1;
	while(file_exists($folder . "-" . $i . ".log")) {
		$i++;
		$file = $folder . "-" . $i . ".log";
	}

	FileSystem::write($file, $data, null, 0777);
}

/**
 * checks for available retina-file on file-path.
 *
 *??@param file
 * @return string
 */
function RetinaPath($file) {
	$retinaPath = substr($file, 0, strrpos($file, ".")) . "@2x." . substr($file, strpos($file, ".") + 1);
	if(file_exists($retinaPath))
		return $retinaPath;
	
	return $file;
}

/**
 * Writes the server configuration file
 */
function writeServerConfig() {
	if(!defined("ROOT_PATH")) {
		return;
	}

	$args = getCommandLineArgs();
	$server = isset($_SERVER["SERVER_SOFTWARE"]) ? $_SERVER["SERVER_SOFTWARE"] :
		(isset($args["server"]) ? $args["server"] : "");
	if(strpos(strtolower($server), "apache") !== false) {
		$file = "htaccess";
		$toFile = ".htaccess";
	} else if(strpos(strtolower($server), "iis") !== false) {
		$file = "web.config";
		$toFile = "web.config";
	} else {
		return;
	}

	require (ROOT . "system/resources/" . $file . ".php");

	if(!file_put_contents(ROOT . $toFile, $serverconfig, FILE_APPEND | LOCK_EX)) {
		echo ("Could not write " . $file);
		exit(6);
	}
}

function GUID()
{
	if (function_exists('com_create_guid') === true)
	{
		return trim(com_create_guid(), '{}');
	}

	return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
}

function getCommandLineArgs() {
	$args = isset($_SERVER["argv"]) ? $_SERVER["argv"] : array();
	$parsedArgs = array();
	foreach($args as $arg) {
		$parts = explode("=", $arg);
		if(isset($parts[1])) {
			$parsedArgs[$parts[0]] = $parts[1];
		} else {
			$parsedArgs[$parts[0]] = $parts[0];
		}
	}
	return $parsedArgs;
}


/**
 * loads the autoloader for the framework
 */
function loadFramework($modelRepository = null) {
	if (defined("CURRENT_PROJECT")) {
		// if we have this directory, we have to install some files
		$directory = CURRENT_PROJECT;
		if (is_dir(ROOT . $directory . "/" . getPrivateKey() . "-install/")) {
			foreach (scandir(ROOT . $directory . "/" . getPrivateKey() . "-install/") as $file) {
				if ($file != "." && $file != ".." && is_file(ROOT . $directory . "/" . getPrivateKey() . "-install/" . $file)) {
					if (preg_match('/\.sql$/i', $file)) {
						$sqls = file_get_contents(ROOT . $directory . "/" . getPrivateKey() . "-install/" . $file);

						$sqls = SQL::split($sqls);

						foreach ($sqls as $sql) {
							$sql = str_replace('{!#PREFIX}', DB_PREFIX, $sql);
							$sql = str_replace('{!#CURRENT_PROJECT}', CURRENT_PROJECT, $sql);
							$sql = str_replace('\n', "\n", $sql);

							SQL::Query($sql);
						}
					} else if (preg_match('/\.php$/i', $file)) {
						include_once (ROOT . $directory . "/" . getPrivateKey() . "-install/" . $file);
					}

					@unlink(ROOT . $directory . "/" . getPrivateKey() . "-install/" . $file);
				}
			}

			FileSystem::delete(ROOT . $directory . "/" . getPrivateKey() . "-install/");
		}
	} else {
		throw new Exception("Calling loadFramework() without defined CURRENT_PROJECT is illegal.");
	}

	if (PROFILE)
		Profiler::mark("Manifest");

	Core::InitCache();
	ClassInfo::loadfile();

	if (PROFILE)
		Profiler::unmark("Manifest");

	Director::Init();
	Core::Init();
}

/**
 * this function loads an application
 * @param $directory
 * @throws Exception
 */
function loadApplication($directory) {
	if(getMemoryLimit() < 64 * 1024 * 1024) {
		throw new Exception("Memory of at least 64M is required.");
	}

	define("URL", parseUrl());

    validateServerConfig();

	if (is_dir(ROOT . $directory) && file_exists(ROOT . $directory . "/application/application.php")) {
		// defines
		define("CURRENT_PROJECT", $directory);
		define("APPLICATION", $directory);
		define("APP_FOLDER", ROOT . $directory . "/");
		defined("APPLICATION_TPL_PATH") OR define("APPLICATION_TPL_PATH", $directory . "/templates");
		defined("CACHE_DIRECTORY") OR define("CACHE_DIRECTORY", $directory . "/temp/");
		defined("UPLOAD_DIR") OR define("UPLOAD_DIR", $directory . "/uploads/");

		// cache-directory
		if (!is_dir(ROOT . CACHE_DIRECTORY)) {
			mkdir(ROOT . CACHE_DIRECTORY, 0777, true);
			@chmod(ROOT . CACHE_DIRECTORY, 0777);
		}

		// load config
		if (file_exists(ROOT . $directory . "/config.php")) {

			require (ROOT . $directory . "/config.php");

			if (isset($domaininfo["db"])) {
				foreach ($domaininfo['db'] as $key => $value) {
					$GLOBALS['db' . $key] = $value;
				}
				define('DB_PREFIX', $GLOBALS["dbprefix"]);
			}

			$domaininfo['date_format_date'] = isset($domaininfo['date_format_date']) ? $domaininfo['date_format_date'] : "d.m.Y";
			$domaininfo['date_format_time'] = isset($domaininfo['date_format_time']) ? $domaininfo['date_format_time'] : "H:i";

			FileSystem::$safe_mode = isset($domaininfo["safe_mode"]) ? $domaininfo["safe_mode"] : false;

			define('DATE_FORMAT', $domaininfo['date_format_date'] . " - " . $domaininfo['date_format_time']);
			define('DATE_FORMAT_DATE', $domaininfo['date_format_date']);
			define('DATE_FORMAT_TIME', $domaininfo['date_format_time']);
			define("SITE_MODE", $domaininfo["status"]);
			define("PROJECT_LANG", $domaininfo["lang"]);
			define("PROJECT_TIMEZONE", $domaininfo["timezone"]);

			Core::setCMSVar("TIMEZONE", $domaininfo["timezone"]);
			Core::$site_mode = SITE_MODE;

			if (isset($domaininfo["sql_driver"])) {
				define("SQL_DRIVER_OVERRIDE", $domaininfo["sql_driver"]);
			}
		} else {
			define("DATE_FORMAT", "d.m.Y - H:i");
			Core::setCMSVar("TIMEZONE", DEFAULT_TIMEZONE);
		}

		ClassManifest::$directories[] = $directory . "/code/";
		ClassManifest::$directories[] = $directory . "/application/";

		if(!isCommandLineInterface() && isProjectUnavailableForIP($_SERVER["REMOTE_ADDR"], basename($directory))) {
			$content = file_get_contents(ROOT . "system/templates/framework/503.html");
			$content = str_replace('{BASE_URI}', BASE_URI, $content);
			header('HTTP/1.1 503 Service Temporarily Unavailable');
			header('Status: 503 Service Temporarily Unavailable');
			header('Retry-After: 10');
			echo ($content);
			exit(503);
		}

		if(isCommandLineInterface()) {
			if(file_exists(ROOT . $directory . "/application/cli-application.php")) {
				require (ROOT . $directory . "/application/cli-application.php");
			} else {
				echo("CLI is not supported by that project.\n");
				exit(9);
			}
		} else {
			require (ROOT . $directory . "/application/application.php");
		}
	} else {
		define("PROJECT_LOAD_DIRECTORY", $directory);
		// this doesn't look like an app, load installer
		loadApplication("system/installer");
	}
}

function getRootPath($file = __FILE__, $docRoot = null) {
	if(isset($docRoot) || !isCommandLineInterface()) {
		$root_path = str_replace("\\", "/", substr($file, 0, 0 - strlen(FRAMEWORK_DIRECTORY . "/core/applibs.php")));
		$root_path = substr($root_path, strlen(isset($docRoot) ? $docRoot : realpath($_SERVER["DOCUMENT_ROOT"])));

        if(substr($root_path, 0, 1) != "/") {
            $root_path = "/" . $root_path;
        }

		return $root_path;
	}

	return str_replace("\\", "/", substr(__FILE__, 0, strlen(FRAMEWORK_DIRECTORY . "/core/applibs.php")));
}

/**
 * parses the URL, so that we have a clean url
 */
function parseUrl() {
	defined("BASE_SCRIPT") OR define("BASE_SCRIPT", "");

	if(!isCommandLineInterface()) {
		define('ROOT_PATH', getRootPath());

		// generate BASE_URI
		$http = (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && strtolower($_SERVER["HTTP_X_FORWARDED_PROTO"]) == "https")
        || ((isset($_SERVER["HTTPS"])) && $_SERVER["HTTPS"] != "off") ? "https" : "http";
		$port = $_SERVER["SERVER_PORT"];
		if ($http == "http" && $port == 80) {
			$port = "";
		} else if ($http == "https" && $port == 443 || ($port == 80 && isset($_SERVER["HTTP_X_FORWARDED_PROTO"]))) {
			$port = "";
		} else {
			$port = ":" . $port;
		}

		define("BASE_URI", $http . '://' . $_SERVER["SERVER_NAME"] . $port . ROOT_PATH);

		// generate URL
		$url = isset($GLOBALS["url"]) ? $GLOBALS["url"] : $_SERVER["REQUEST_URI"];
		$url = urldecode($url);
		// we should do this, because the url is not correct else
		if (preg_match('/\?/', $url)) {
			$url = substr($url, 0, strpos($url, '?'));
		}

		$url = substr($url, strlen(ROOT_PATH . BASE_SCRIPT));

		// parse URL
		if (substr($url, 0, 1) == "/")
			$url = substr($url, 1);

		// URL-END
		if (preg_match('/^(.*)' . preg_quote(URLEND, "/") . '$/Usi', $url, $matches)) {
			$url = $matches[1];
		} else if (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) == 'GET' && $url != "" && !Core::is_ajax() && !preg_match('/\.([a-zA-Z]+)$/i', $url) && count($_POST) == 0) {
			// enforce URLEND
			$get = "";
			$i = 0;
			foreach ($_GET as $k => $v) {
				if ($i == 0)
					$i++;
				else
					$get .= "&";

				$get .= urlencode($k) . "=" . urlencode($v);
			}

			if ($get) {
				header("Location: " . BASE_URI . BASE_SCRIPT . $url . URLEND . "?" . $get);
			} else {
				header("Location: " . BASE_URI . BASE_SCRIPT . $url . URLEND);
			}
			exit;
		}

		$url = str_replace('//', '/', $url);

		return $url;
	} else {
		define('ROOT_PATH', "/");
		define("BASE_URI", "/");

		return isset($argv[0]) ? $argv[0] : "";
	}
}

function umlautMap($str){
	$bad = array(
		'??', '??', '??', '??', '??', '??', '??'
	);

	$good = array(
		'ue', 'Ue', 'ae', 'Ae', 'Oe', 'oe',' ss'
	);
	return str_replace($bad,$good,$str);
}

function validateServerConfig() {
    if (!file_exists(ROOT . ".htaccess") && !file_exists(ROOT . "web.config")) {
        writeServerConfig();
    }

// some hacks for changes in .htaccess
    if (file_exists(ROOT . ".htaccess") && !strpos(file_get_contents(".htaccess"), "ErrorDocument 404")) {
        if (!file_put_contents(ROOT . ".htaccess", "\nErrorDocument 404 " . ROOT_PATH . "system/application.php", FILE_APPEND)) {
            die("Could not write .htaccess");
        }
    }

    if (file_exists(ROOT . ".htaccess") && !strpos(file_get_contents(".htaccess"), "ErrorDocument 500")) {
        if (!file_put_contents(ROOT . ".htaccess", "\nErrorDocument 500 " . ROOT_PATH . "system/templates/framework/500.html", FILE_APPEND)) {
            die("Could not write .htaccess");
        }
    }

    if (file_exists(ROOT . ".htaccess") && (strpos(file_get_contents(".htaccess"), " system"))) {
        $contents = file_get_contents(ROOT . ".htaccess");
        $contents = str_replace(' system', ' ' . ROOT_PATH . "system", $contents);
        if (!file_put_contents(ROOT . ".htaccess", $contents)) {
            die("Could not write .htaccess");
        }
        unset($contents);
    }
}

/**
 * returns all http-headers.
 */
if (!function_exists('getallheaders'))
{
	function getallheaders()
	{
		$headers = '';
		foreach ($_SERVER as $name => $value)
		{
			if (substr($name, 0, 5) == 'HTTP_')
			{
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
			}
		}
		return $headers;
	}
}

$__isCommandLineInterface = null;
function isCommandLineInterface()
{
    global $__isCommandLineInterface;

    if(!isset($__isCommandLineInterface)) {
        $__isCommandLineInterface = (!isset($_SERVER['SERVER_SOFTWARE']) && (php_sapi_name() == 'cli' || (is_numeric($_SERVER['argc']) && $_SERVER['argc'] > 0)));
    }

    return $__isCommandLineInterface;
}

function isPHPUnit() {
	$args = isset($_SERVER["argv"]) ? $_SERVER["argv"] : array();

	return isset($args[0]) && strpos($args[0], "phpunit") !== false;
}
function shouldOutputLogs() {
	return isCommandLineInterface();
}

function isDevModeCLI() {
	$args = getCommandLineArgs();

	return isset($args["--dev"]);
}

function fixJSON($json) {
    $regex = <<<'REGEX'
~
    "[^"\\]*(?:\\.|[^"\\]*)*"
    (*SKIP)(*F)
  | '([^'\\]*(?:\\.|[^'\\]*)*)'
~x
REGEX;

    return preg_replace_callback($regex, function($matches) {
        return '"' . preg_replace('~\\\\.(*SKIP)(*F)|"~', '\\"', $matches[1]) . '"';
    }, $json);
}

if(!function_exists("mb_strcut")) {
    function mb_strcut() {
        return call_user_func_array("substr", func_get_args());
    }
}

class SQLException extends GomaException {
	protected $standardCode = ExceptionManager::SQL_EXCEPTION;
	/**
	 * constructor.
	 */
	public function __construct($message = "", $code = null, Exception $previous = null) {
		$sqlerr = SQL::errno() . ": " . sql::error() . "<br /><br />\n\n <strong>Query:</strong> <br />\n<code>" . sql::$last_query . "</code>\n";
		$message = $sqlerr . "\n" . $message;
		parent::__construct($message, $code, $previous);
	}

}

class GomaException extends Exception {
	/**
	 * @var int
	 */
	protected $standardCode = ExceptionManager::EXCEPTION;

	/**
	 * GomaException constructor.
	 *
	 * @param string $message
	 * @param null|int $code
	 * @param Exception|null $previous
	 */
	public function __construct($message = "", $code = null, Exception $previous = null) {
		if(!isset($code)) {
			$code =  $this->standardCode;
		}

		parent::__construct($message, $code, $previous);
	}

	public function http_status() {
		return 500;
	}

    public function getDeveloperMessage() {
        return $this->http_status() != 200 ? " Status: " . $this->http_status() : "";
    }
}

class ProjectConfigWriteException extends GomaException {
    protected $standardCode = ExceptionManager::PROJECT_CONFIG_WRITE_ERROR;
    protected $config;

    /**
     * ProjectConfigWriteException constructor.
     * @param string $file
     * @param string $message
     * @param null $code
     * @param Exception|null $previous
     */
    public function __construct($file, $message = "Could not write Project-Config", $code = null, Exception $previous = null)
    {
        $this->config = $file;

        parent::__construct($message . " File: " . $file, $code, $previous);
    }

    /**
     * @return mixed
     */
    public function getConfig()
    {
        return $this->config;
    }
}

class MySQLException extends SQLException {
}

class SecurityException extends GomaException {
	protected $standardCode = ExceptionManager::SECURITY_ERROR;
}

class PermissionException extends GomaException {

	protected $standardCode = ExceptionManager::PERMISSION_ERROR;

    /**
     * which permission is missing.
     *
     * @var string
     */
    protected $missingPerm;

    /**
     * constructor.
     * @param string $message
     * @param int $code
     * @param string $missingPerm
     * @param Exception $previous
     */
	public function __construct($message = null, $code = null, $missingPerm = null, Exception $previous = null) {
        $this->missingPerm = $missingPerm;
		parent::__construct(isset($message) ? $message : lang("less_rights"), $code, $previous);
	}

    public function getMissingPerm() {
        return $this->missingPerm;
    }

    public function getDeveloperMessage()
    {
        return parent::getDeveloperMessage() . " Missing Permission: " . $this->missingPerm;
    }

}

class PHPException extends GomaException {
	protected $standardCode = ExceptionManager::PHP_ERROR;
	/**
	 * constructor.
	 */
	public function __construct($message = "PHP-Error", $code = null, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}
}

class DBConnectError extends MySQLException {
	/**
	 * constructor.
	 */
	public function __construct($message = "DB-Connect-Error", $code = ExceptionManager::DB_CONNECT_ERROR, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}

	public function getTraceForUser() {
	    return "";
    }
}

class ServiceUnavailable extends GomaException {
	/**
	 * constructor.
	 */
	public function __construct($message = "Temporary Unavailable", $code = ExceptionManager::SERVICE_UNAVAILABLE, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}

	public function http_status() {
		return 503;
	}

}

class InvalidStateException extends GomaException {
	protected $standardCode = ExceptionManager::INVALID_STATE;
	/**
	 * constructor.
	 * @param string $message
	 * @param null $code
	 * @param Exception $previous
	 */
	public function __construct($message = "Invalid State", $code = null, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}
}
