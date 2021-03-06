<?php
defined("IN_GOMA") OR die();

StaticsManager::addSaveVar("i18n", "languagefiles");
StaticsManager::addSaveVar("i18n", "defaultLanguagefiles");
StaticsManager::addSaveVar("i18n", "selectByHttp");

/**
 * Class for localization.
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 *
 * @package		Goma\System\Core
 * @version		1.4.5
 */
class i18n extends gObject {
	/**
	 * files to load
	 */
	public static $languagefiles = array();

	/**
	 * files to load
	 */
	public static $defaultLanguagefiles = array();

	public static $selectByHttp = true;

	/**
	 * adds new loader
	 */
	public static function addLang($name, $default = "de") {
		if(defined('GENERATE_CLASS_INFO')) {
			self::$languagefiles[] = $name;
			self::$defaultLanguagefiles[$name] = $default;
		}
	}

	/**
	 * timezones
	 *@var array
	 *@todo make a full list
	 */
	public static $timezones = array('Europe/Berlin', 'Europe/London', 'Europe/Paris', 'Europe/Helsinki', 'Europe/Moscow', 'Europe/Madrid', 'Pacific/Kwajalein', 'Pacific/Samoa', 'Pacific/Honolulu', 'America/Juneau', 'America/Los_Angeles', 'America/Denver', 'America/Mexico_City', 'America/New_York', 'America/Caracas', 'America/St_Johns', 'America/Argentina/Buenos_Aires', 'Atlantic/Azores', 'Atlantic/Azores', 'Asia/Tehran', 'Asia/Baku', 'Asia/Kabul', 'Asia/Karachi', 'Asia/Calcutta', 'Asia/Colombo', 'Asia/Bangkok', 'Asia/Singapore', 'Asia/Tokyo', 'Australia/ACT', 'Australia/Currie', 'Australia/Lindeman', 'Australia/Perth', 'Australia/Victoria', 'Australia/Adelaide', 'Australia/Darwin', 'Australia/Lord_Howe', 'Australia/Queensland', 'Australia/West', 'Australia/Brisbane', 'Australia/Eucla', 'Australia/Melbourne', 'Australia/South', 'Australia/Yancowinna', 'Australia/Broken_Hill', 'Australia/Hobart', 'Australia/North', 'Australia/Sydney', 'Australia/Canberra', 'Australia/LHI', 'Australia/NSW', 'Australia/Tasmania', 'Pacific/Guam', 'Asia/Magadan', 'Asia/Kamchatka', 'Africa/Abidjan', 'Africa/Asmera', 'Africa/Blantyre', 'Africa/Ceuta', 'Africa/Douala', 'Africa/Johannesburg', 'Africa/Windhoek', 'Africa/Sao_Tome', 'Africa/Timbuktu', 'Africa/Niamey');

	/**
	 * date-formats
	 *
	 */
	public static $date_formats = array("d.m.Y", "d-m-Y", "F j, Y", "F jS Y", "Y-m-d");

	public static $time_formats = array("H:i", "g:i a", "g.i a", "H.i");

	/**
	 * returns name for cache for language.
	 */
	public static function getLangCacheName() {
		if(isset(ClassInfo::$appENV["expansion"])) {
			return "lang_" . Core::$lang . count(i18n::$languagefiles) . count(ClassInfo::$appENV["expansion"]);
		} else {
			return "lang_" . Core::$lang . count(i18n::$languagefiles);
		}
	}

	/**
	 * inits i18n
	 *
	 * @param string $language which language to initialize
	 */
	public static function Init($language) {
		if(!self::LangExists($language)) {
			throw new InvalidArgumentException("Language $language not found.");
		}

		if(PROFILE)
			Profiler::mark("i18n::Init");

		// check lang selection
		Core::$lang = $language;

		global $lang;
		$lang = array();

		$cacher = new Cacher(self::getLangCacheName());
		if($cacher->checkvalid()) {
			$lang = $cacher->getData();
		} else {
			require_once (ROOT . LANGUAGE_DIRECTORY . '/' . Core::$lang . '/lang.php');

			foreach(self::$languagefiles as $file) {
				if(file_exists(ROOT . LANGUAGE_DIRECTORY . '/' . Core::$lang . '/' . $file . '.php')) {
					require_once (ROOT . LANGUAGE_DIRECTORY . '/' . Core::$lang . '/' . $file . '.php');
				} else if(isset(self::$defaultLanguagefiles[$file])) {
					if(file_exists(ROOT . LANGUAGE_DIRECTORY . '/' . self::$defaultLanguagefiles[$file] . '/' . $file . '.php')) {
						copy(ROOT . LANGUAGE_DIRECTORY . '/' . self::$defaultLanguagefiles[$file] . '/' . $file . '.php', ROOT . LANGUAGE_DIRECTORY . '/' . Core::$lang . '/' . $file . '.php');
						require_once (ROOT . LANGUAGE_DIRECTORY . '/' . Core::$lang . '/' . $file . '.php');
					}
				}
			}

			$lang = ArrayLib::map_key("strtoupper", $lang);

			// load app-language
			if(isset(ClassInfo::$appENV["app"]["langPath"])) {
				$langName = isset(ClassInfo::$appENV["app"]["langName"]) ? ClassInfo::$appENV["app"]["langName"] : ClassInfo::$appENV["app"]["name"];
				if(file_exists(ROOT . APPLICATION . "/" . ClassInfo::$appENV["app"]["langPath"] . "/" . Core::$lang . ".php")) {
					self::loadExpansionLang(ROOT . APPLICATION . "/" . ClassInfo::$appENV["app"]["langPath"] . "/" . Core::$lang . ".php", $langName, $lang);
				} else if(isset(ClassInfo::$appENV["app"]["defaultLang"])) {
					$default = ClassInfo::$appENV["app"]["defaultLang"];
					if(file_exists(ROOT . APPLICATION . "/" . ClassInfo::$appENV["app"]["langPath"] . "/" . $default . ".php")) {
						self::loadExpansionLang(ROOT . APPLICATION . "/" . ClassInfo::$appENV["app"]["langPath"] . "/" . $default . ".php", $langName, $lang);
					}
					unset($default);
				}
			}

			if(isset(ClassInfo::$appENV["expansion"])) {
				foreach(ClassInfo::$appENV["expansion"] as $name => $data) {
					$folder = $data["folder"];
					if(isset($data["langFolder"])) {
						if(file_exists($folder . $data["langFolder"] . "/" . Core::$lang . ".php")) {
							self::loadExpansionLang($folder . $data["langFolder"] . "/" . Core::$lang . ".php", "exp_" . $name, $lang);
						} else if(isset($data["defaultLang"]) && file_exists($folder . $data["langFolder"] . "/" . $data["defaultLang"] . ".php")) {
							self::loadExpansionLang($folder . $data["langFolder"] . "/" . $data["defaultLang"] . ".php", "exp_" . $name, $lang);
						}
					} else if(is_dir($folder . "languages")) {
						if(file_exists($folder . "languages/" . Core::$lang . ".php")) {
							self::loadExpansionLang($folder . "languages/" . Core::$lang . ".php", "exp_" . $name, $lang);
						} else if(isset($data["defaultLang"]) && file_exists($folder . "languages/" . $data["defaultLang"] . ".php")) {
							self::loadExpansionLang($folder . "languages/" . $data["defaultLang"] . ".php", "exp_" . $name, $lang);
						}
						unset($default);
					}
				}
			}

			$cacher->write($lang, 600);

            if($language == DEFAULT_LANG) {
                self::createLangClass();
            } else if(!file_exists(APPLICATION . "/application/l.php")) {
                i18n::Init(DEFAULT_LANG);
                i18n::Init($language);
            }
		}

		if(PROFILE)
			Profiler::unmark("i18n::Init");
	}

    /**
     *
     */
    public static function createLangClass() {
        $class = '<?php class MMM___LLL L {' . "\n";

        foreach(array_keys($GLOBALS["lang"]) as $key) {
            $const = strtoupper(preg_replace('/[^a-zA-Z0-9_]/', '_', $key));
            if(in_array(strtolower($const), array("new", "class", "interface", "method", "function", "var", "const"))) {
                $const = "_" . $const;
            }
            $class .= " const " . $const . " = ".var_export($key, true).";\n";
        }

        $class .= "\n}";

        $class = str_replace("MMM___LLL ", "MMM___LLL", $class);
        try {
            eval(substr($class, 5));
        } catch(Error $e) {
            var_dump($class);
            log_exception($e);
            if(isPHPUnit()) {
                throw $e;
            }
            return;
        }

        $class = str_replace("MMM___LLL", "", $class);

        ClassInfo::$files["l"] = APPLICATION . "/application/l.php";

        if(!FileSystem::write(ROOT . APPLICATION . "/application/l.php", $class)) {
			throw new LogicException("Could not write ".APPLICATION."/application/l.php. Please set permissions of ".APPLICATION."/application to 0777.");
		}
    }

	/**
	 * sets session-language.
	 * @param null|string $lang
	 * @return null|string
	 */
	public static function SetSessionLang($lang = null) {
		if(!isset($lang) || !self::LangExists($lang)) {
			$lang = self::AutoSelectLang();
		}

		if(!isCommandLineInterface()) {
			GlobalSessionManager::globalSession()->set("lang", $lang);
			setCookie('g_lang', $lang, TIME + 365 * 24 * 60 * 60, '/', GlobalSessionManager::getCookieHost());
		}

		return $lang;
	}

	/**
	 * load template language.
	 */
	public static function loadTPLLang($tpl) {

		if(file_exists(ROOT . "/tpl/" . $tpl . "/lang/" . Core::$lang . ".php")) {
			self::loadExpansionLang(ROOT . "tpl/" . $tpl . "/lang/" . Core::$lang . ".php", "tpl", $GLOBALS["lang"]);
		}
	}

	/**
	 * loads expansion-lang
	 *
	 */
	public static function loadExpansionLang($file, $name, &$language) {
		require ($file);
		/** @var array $lang */
		foreach($lang as $key => $val) {
			$language[strtoupper($name . "." . $key)] = $val;
		}

		if(isset($overrideLang)) {
			foreach($overrideLang as $key => $value) {
				$language[strtoupper($key)] = $value;
			}
		}
	}

	/**
	 * lists all languages
	 *
     * @return array[]
	 */
	public static function listLangs() {
		if(PROFILE)
			Profiler::mark("i18n::listLangs");

		$data = array();
		foreach(scandir(ROOT . LANGUAGE_DIRECTORY) as $lang) {
			if($lang != "." && $lang != ".." && is_dir(ROOT . LANGUAGE_DIRECTORY . "/" . $lang) && file_exists(ROOT . LANGUAGE_DIRECTORY . "/" . $lang . "/info.plist") && file_exists(ROOT . LANGUAGE_DIRECTORY . "/" . $lang . "/lang.php")) {
				$plist = new CFPropertyList();
				$plist->parse(file_get_contents(ROOT . LANGUAGE_DIRECTORY . "/" . $lang . "/info.plist"));
				$contents = $plist->ToArray();
				if(isset($contents["title"], $contents["type"], $contents["icon"]) && $contents["type"] == "language") {
					$contents["icon"] = LANGUAGE_DIRECTORY . "/" . $lang . "/" . $contents["icon"];
					$contents["code"] = $lang;
					$data[$lang] = $contents;
				}
			}
		}

		uksort($data, array("i18n", "sortByCurrent"));

		if(PROFILE)
			Profiler::unmark("i18n::listLangs");



		return $data;
	}

	/**
	 * helper for sort
	 */
	static function sortByCurrent($a, $b) {
		if($a == Core::$lang) {
			return -1;
		} else if($b == Core::$lang) {
			return 1;
		} else {
			return 0;
		}
	}

	/**
	 * returns contents of info.plist of current or given language
	 *
	 * @param string - language
	 * @return bool|mixed
	 */
	public static function getLangInfo($lang = null) {
		if(!isset($lang))
			$lang = Core::$lang;

		if(is_dir(ROOT . LANGUAGE_DIRECTORY . "/" . $lang) && file_exists(ROOT . LANGUAGE_DIRECTORY . "/" . $lang . "/info.plist")) {
			$plist = new CFPropertyList();
			$plist->parse(file_get_contents(ROOT . LANGUAGE_DIRECTORY . "/" . $lang . "/info.plist"));
			$contents = $plist->ToArray();
			if(isset($contents["title"], $contents["type"], $contents["icon"]) && $contents["type"] == "language") {
				$contents["icon"] = LANGUAGE_DIRECTORY . "/" . $lang . "/" . $contents["icon"];
				return $contents;
			}
		}
		return false;
	}

	/**
	 * select language based on current data
	 *
	 * @name AutoSelectLang
	 * @return string
	 */
	public static function AutoSelectLang() {
		// if a user want to have another language
		if(isset($_GET['setlang']) && !empty($_GET["setlang"])) {
			if(self::LangExists($_GET["setlang"]))
				return $_GET["setlang"];
		} else if(isset($_POST['setlang']) && !empty($_POST["setlang"])) {
			if(self::LangExists($_POST["setlang"]))
				return $_POST['setlang'];
		}

		// if a user want to have another language
		if(isset($_GET['locale']) && !empty($_GET["locale"])) {
			if(self::LangExists($_GET["locale"]))
				return $_GET['locale'];
		} else if(isset($_POST['locale']) && !empty($_POST["locale"])) {
			if(self::LangExists($_POST["locale"]))
				return $_POST['locale'];
		}

		// define current language
		if(self::LangExists(GlobalSessionManager::globalSession()->get("lang"))) {
			return GlobalSessionManager::globalSession()->get("lang");
		} else if(isset($_COOKIE["g_lang"]) && self::langExists($_COOKIE["g_lang"])) {
			return $_COOKIE["g_lang"];
		} else if(self::$selectByHttp) {
			return self::prefered_language(array_keys(self::listLangs()));
		} else if(defined("PROJECT_LANG")) {
			return PROJECT_LANG;
		} else {
			return DEFAULT_LANG;
		}
	}

	/**
	 * select language based on lang-code
	 *
	 * @name selectLang
	 * @return string
	 */
	public static function selectLang($code) {

		if($code != "." && $code != ".." && is_dir(LANGUAGE_DIRECTORY . "/" . $code)) {
			return $code;
		} else {
			$db = self::getLangDB();
			if(isset($db[$code])) {
				return $db[$code];
			}

			if(defined("PROJECT_LANG") && self::LangExists("PROJECT_LANG")) {
				return self::selectLang(PROJECT_LANG);
			} else {
				if(self::LangExists(DEFAULT_LANG)) {
					return DEFAULT_LANG;
				} else {
					throw new LogicException("No language found. Please define at least an existing DEFAULT_LANG as constant.");
				}
			}
		}
	}

	/**
	 * returns if given lang exists
	 *
	 * @name LangExists
	 * @return bool
	 */
	public static function LangExists($code) {
		if(!$code || $code == "." || $code == "..")
			return false;

		if(is_dir(LANGUAGE_DIRECTORY . "/" . $code)) {
			return true;
		} else {
			$db = self::getLangDB();
			if(isset($db[$code])) {
				return true;
			}

			return false;
		}
	}

	/**
	 * this function builds a cache which has a langcode-lang-database
	 */
	protected static function getLangDB() {
		$cacher = new Cacher("langDB" . count(scandir(LANGUAGE_DIRECTORY)));
		if($cacher->checkValid()) {
			return $cacher->getData();
		} else {
			$db = array();
			foreach(scandir(LANGUAGE_DIRECTORY) as $lang) {
				$data = self::getLangInfo($lang);
				$db[$lang] = $lang;
				if(isset($data["langCodes"]))
					foreach($data["langCodes"] as $code) {
						if(isset($db[$code]) && $lang != PROJECT_LANG) {
							$db[$code] = array_merge((array) $db[$code], array($lang));
						} else {
							$db[$code] = $lang;
						}
					}
			}
			$cacher->write($db, 86400);
			return $db;
		}
	}

	/**
	 * returns an array of locale-codes for given code
	 *
	 * @name getLangCodes
	 * @return array
	 */
	public static function getLangCodes($code) {
		$data = self::getLangInfo(self::selectLang($code));
		if(isset($data["langCodes"]))
			return array_merge($data["areaCodes"], array($code));
		else
			return array($code);
	}

	/**
	 * @param null|string $lang code of language
	 * @return array
	 */
	public static function getMonthArray($lang = null) {
		if(!isset($lang) || !file_exists(LANGUAGE_DIRECTORY . "/" . $lang)) {
			$lang = Core::$lang;
		}

		/** @var $calendar */
		require LANGUAGE_DIRECTORY . "/" . $lang . "/calendar.php";
		return array(
			1 => $calendar["January"],
			2 => $calendar["February"],
			3 => $calendar["March"],
			4 => $calendar["April"],
			5 => $calendar["May"],
			6 => $calendar["June"],
			7 => $calendar["July"],
			8 => $calendar["August"],
			9 => $calendar["September"],
			10 => $calendar["October"],
			11 => $calendar["November"],
			12 => $calendar["December"]
		);
	}

    /**
     * extracts preferred language from header.
     *
     * @param $available_languages
     * @param string $http_accept_language
     * @return string
     */
	public static function prefered_language ($available_languages, $http_accept_language="auto") {
		// if $http_accept_language was left out, read it from the HTTP-Header
		if ($http_accept_language == "auto") $http_accept_language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';

		// standard  for HTTP_ACCEPT_LANGUAGE is defined under
		// http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.4
		// pattern to find is therefore something like this:
		//    1#( language-range [ ";" "q" "=" qvalue ] )
		// where:
		//    language-range  = ( ( 1*8ALPHA *( "-" 1*8ALPHA ) ) | "*" )
		//    qvalue         = ( "0" [ "." 0*3DIGIT ] )
		//            | ( "1" [ "." 0*3("0") ] )
		preg_match_all("/([[:alpha:]]{1,8})(-([[:alpha:]|-]{1,8}))?" .
			"(\s*;\s*q\s*=\s*(1\.0{0,3}|0\.\d{0,3}))?\s*(,|$)/i",
			$http_accept_language, $hits, PREG_SET_ORDER);

		// default language (in case of no hits) is the first in the array
		$bestlang = $available_languages[0];
		$bestqval = 0;

		foreach ($hits as $arr) {
			// read data from the array of this hit
			$langprefix = strtolower ($arr[1]);
			if (!empty($arr[3])) {
				$langrange = strtolower ($arr[3]);
				$language = $langprefix . "-" . $langrange;
			}
			else $language = $langprefix;
			$qvalue = 1.0;
			if (!empty($arr[5])) $qvalue = floatval($arr[5]);

			// find q-maximal language
			if (in_array($language,$available_languages) && ($qvalue > $bestqval)) {
				$bestlang = $language;
				$bestqval = $qvalue;
			}
			// if no direct hit, try the prefix only but decrease q-value by 10% (as http_negotiate_language does)
			else if (in_array($langprefix,$available_languages) && (($qvalue*0.9) > $bestqval)) {
				$bestlang = $langprefix;
				$bestqval = $qvalue*0.9;
			}
		}
		return $bestlang;
	}
}
