<?php use Goma\Template\tplcacher;

defined("IN_GOMA") OR die();
/**
 * @package goma framework
 * @subpackage template framework
 * @link http://goma-cms.org
 * @license: LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 * @author Goma-Team
 */
class tpl extends gObject
{
	/**
	 * @var string
	 */
	public static $tplpath = "tpl/";

	/**
	 * currently parsed template
	 *
	 * @name tpl
	 * @access public
	 */
	public static $tpl = "";

	/**
	 * dataStack
	 *
	 * @name dataStack
	 */
	public static $dataStack = array();

	/**
	 * some words you can use like this: <% WORD data %> will be like <% word(data); %>
	 *
	 * @name language_reserved_words
	 * @access public
	 */
	public static $language_reserved_words = array(
		"INCLUDE_JS_MAIN",
		"INCLUDE_JS",
		"INCLUDE_CSS",
		"INCLUDE",
		"INCLUDE_CSS_MAIN",
		"GLOAD",
		"CACHED",
		"ENDCACHED"
	);

	static $cacheTime = 86400;

	/**
	 * this is a static array for convert_vars
	 *
	 * @name convert_var_temp
	 * @access public
	 */
	private static $convert_vars_temp = array();

	/**
	 * @access public
	 * @param string - filename
	 * @param bollean - to follow <!tpl inc:"neu"> or not
	 * @param array - for replacement like {$content}
	 * @use: parse tpl
	 * @return mixed
	 */
	public static function init($name, $follow = true, $replacement = array(), $ifsa = array(), $blockvars = array(), $class = "")
	{
		Core::deprecate(2.0, "TPL::render");
		$file = self::getFilename($name, $class);
		if ($file !== false) {
			return self::parser($file, $replacement, realpath($file), $class, $required_areas);
		} else {
			/* an error so show an error ;-) */
			throw new TemplateNotFoundException($name, "Could not open Template-File.");
		}
	}

	/**
	 * new init method
	 *
	 * @param string $name
	 * @param array $replacement
	 * @param string $class
	 * @param null $expansion
	 * @return mixed
	 */
	public static function render($name, $replacement = array(), $class = "", $expansion = null)
	{
		$file = self::getFilename($name, $class, false, $expansion);
		if ($file !== false) {
			return self::parser($file, $replacement, realpath($file), $class);
		} else {
			throw new TemplateNotFoundException($name, "Could not open Template-File.");
		}
	}

	/**
	 * gets the filename of a given template-name
	 *
	 * @param string $name
	 * @param object|string $class
	 * @param bool $inc
	 * @param string|null $expansion
	 * @return bool|string
	 */
	public static function getFilename($name, $class = "", $inc = false, $expansion = null)
	{
		if (preg_match('/^\//', $name)) {
			if (is_file(ROOT . $name)) {
				return ROOT . $name;
			} else {
				return false;
			}
		} else {
			if (is_file(ROOT . self::$tplpath . Core::getTheme() . "/" . $name)) {
				return ROOT . self::$tplpath . Core::getTheme() . "/" . $name;
			}

			if ($inc === true && is_file(ROOT . self::$tplpath . Core::getTheme() . "/includes/" . $name)) {
				return ROOT . self::$tplpath . Core::getTheme() . "/includes/" . $name;
			}

			if (Resources::file_exists(APPLICATION_TPL_PATH . "/" . $name)) {
				return ROOT . APPLICATION_TPL_PATH . '/' . $name;
			}

			if ($inc === true && Resources::file_exists(APPLICATION_TPL_PATH . "/includes/" . $name)) {
				return ROOT . APPLICATION_TPL_PATH . '/includes/' . $name;
			}

			if (is_object($class) && $class->inExpansion) {
				$viewpath = isset(ClassInfo::$appENV["expansion"][$class->inExpansion]["viewFolder"]) ? ExpansionManager::getExpansionFolder($class->inExpansion) . ClassInfo::$appENV["expansion"][$class->inExpansion]["viewFolder"] : ExpansionManager::getExpansionFolder($class->inExpansion) . "views";
				if (Resources::file_exists($viewpath . "/" . $name)) {
					return $viewpath . "/" . $name;
				} else if ($inc === true && Resources::file_exists($viewpath . "/includes/" . $name)) {
					return $viewpath . "/includes/" . $name;
				}
			}

			if (isset($expansion)) {
				$viewpath = isset(ClassInfo::$appENV["expansion"][$expansion]["viewFolder"]) ? ExpansionManager::getExpansionFolder($expansion) . ClassInfo::$appENV["expansion"][$expansion]["viewFolder"] : ExpansionManager::getExpansionFolder($expansion) . "views";
				if (Resources::file_exists($viewpath . "/" . $name)) {
					return $viewpath . "/" . $name;
				} else if ($inc === true && Resources::file_exists($viewpath . "/includes/" . $name)) {
					return $viewpath . "/includes/" . $name;
				}
			}

			if (Resources::file_exists(SYSTEM_TPL_PATH . "/" . $name)) {
				return ROOT . SYSTEM_TPL_PATH . '/' . $name;
			}

			if ($inc === true && Resources::file_exists(SYSTEM_TPL_PATH . '/includes/' . $name)) {
				return ROOT . SYSTEM_TPL_PATH . '/includes/' . $name;
			}

			return self::getFileNameUncached($name, $class, $inc, $expansion);
		}
	}

	/**
	 * gets the filename of a given template-name uncached!
	 * just returns false
	 *
	 * @param string $name
	 * @param object|string $class
	 * @param bool $inc use include folder
	 * @param string $expansion
	 * @return bool|string
	 */
	public static function getFilenameUncached($name, $class = "", $inc = false, $expansion = null)
	{
		if (preg_match('/^\//', $name)) {
			if (is_file(ROOT . $name)) {
				return ROOT . $name;
			} else {
				return false;
			}
		} else {

			if (is_file(ROOT . self::$tplpath . Core::getTheme() . "/" . $name)) {
				return ROOT . self::$tplpath . Core::getTheme() . "/" . $name;
			}

			if ($inc === true && is_file(ROOT . self::$tplpath . Core::getTheme() . "/includes/" . $name)) {
				return ROOT . self::$tplpath . Core::getTheme() . "/includes/" . $name;
			}

			if (file_exists(APPLICATION_TPL_PATH . "/" . $name)) {
				return ROOT . APPLICATION_TPL_PATH . '/' . $name;
			}

			if ($inc === true && file_exists(APPLICATION_TPL_PATH . "/includes/" . $name)) {
				return ROOT . APPLICATION_TPL_PATH . '/includes/' . $name;
			}

			if (is_object($class) && $class->inExpansion) {
				$viewpath = isset(ClassInfo::$appENV["expansion"][$class->inExpansion]["viewFolder"]) ? ExpansionManager::getExpansionFolder($class->inExpansion) . ClassInfo::$appENV["expansion"][$class->inExpansion]["viewFolder"] : ExpansionManager::getExpansionFolder($class->inExpansion) . "views";
				if (file_exists($viewpath . "/" . $name)) {
					return $viewpath . "/" . $name;
				} else if ($inc === true && file_exists($viewpath . "/includes/" . $name)) {
					return $viewpath . "/includes/" . $name;
				}
			}

			if (isset($expansion)) {
				$viewpath = isset(ClassInfo::$appENV["expansion"][$expansion]["viewFolder"]) ? ExpansionManager::getExpansionFolder($expansion) . ClassInfo::$appENV["expansion"][$expansion]["viewFolder"] : ExpansionManager::getExpansionFolder($expansion) . "views";
				if (file_exists($viewpath . "/" . $name)) {
					return $viewpath . "/" . $name;
				} else if ($inc === true && file_exists($viewpath . "/includes/" . $name)) {
					return $viewpath . "/includes/" . $name;
				}
			}

			if (file_exists(SYSTEM_TPL_PATH . "/" . $name)) {
				return ROOT . SYSTEM_TPL_PATH . '/' . $name;
			}

			if ($inc === true && file_exists(SYSTEM_TPL_PATH . '/includes/' . $name)) {
				return ROOT . SYSTEM_TPL_PATH . '/includes/' . $name;
			}

			return false;

		}
	}

	private static $cacheCache = array();

	/**
	 * build all files needed for a template
	 *
	 * @param string $template
	 * @param string $tmpname
	 * @return bool|string
	 */
	public static function buildFilesForTemplate($template, $tmpname)
	{
		if (PROFILE) Profiler::mark("tpl::buildFilesForTemplate");

		if (isset(self::$cacheCache[$tmpname])) {
			if (PROFILE) Profiler::unmark("tpl::buildFilesForTemplate");

			return self::$cacheCache[$tmpname]->filename();
		}

		// caching
		$lastModified = filemtime($tmpname);

		$cacher = new tplcacher($tmpname, $lastModified);
		self::$cacheCache[$tmpname] = $cacher;
		if ($cacher->checkvalid() === true) {
			if (PROFILE) Profiler::unmark("tpl::buildFilesForTemplate");

			return $cacher->filename();
		} else {
			$data = file_get_contents($template);

			array_push(self::$dataStack, array("tpl" => self::$tpl));

			self::$tpl = $template;
			$tpldata = self::compile($data);

			$olddata = array_pop(self::$dataStack);
			self::$tpl = $olddata["tpl"];


			if ($cacher->write($tpldata)) {
				unset($data, $tpldata, $olddata);
				if (PROFILE) Profiler::unmark("tpl::buildFilesForTemplate");

				return $cacher->filename();
			} else {
				if (PROFILE) Profiler::unmark("tpl::buildFilesForTemplate");

				return false;
			}
		}


	}

	/**
	 * @param string $tpl
	 * @param array $replacement
	 * @param string $tmpname
	 * @param string|gObject $class
	 * @return mixed
	 */
	public static function parser($tpl, $replacement = array(), $tmpname, $class)
	{
		if (PROFILE) Profiler::mark("tpl::parser");

		$filename = self::buildFilesForTemplate($tpl, $tmpname);
		if ($filename === false) {
			throw new TemplateNotFoundException($filename, "Could not open Template-File.");
		}


		if (!is_object($class)) {
			$class = gObject::instance("viewaccessabledata");
		}
		$class->customise($replacement);

		if (PROFILE) Profiler::mark("tpl::parser Run " . $tmpname);

		$caller = new tplCaller($class, $tpl);
		$data = $class;
		$callerStack = array();
		$dataStack = array();
		$cacheBuffer = array();
		$cacher = array();


		$hash = "<!--" . microtime(true) . '-->';
		ob_start();
		echo $hash;
		$filename = str_replace('//', '/', $filename);
		include($filename);

		$content = ob_get_contents(); // get contents

		unset($data, $callerStack, $dataStack, $caller);

		ob_end_clean(); // clean contents

		if ($contents = explode($hash, $content)) {
			if (count($contents) > 1) {
				echo $contents[0];
				$tpl = $contents[1];
			} else {
				$tpl = str_replace($hash, '', $content);
			}
		} else {
			$tpl = str_replace($hash, '', $content);
		}

		if (PROFILE) Profiler::unmark("tpl::parser Run " . $tmpname);
		if (PROFILE) Profiler::unmark("tpl::parser");

		return $tpl;
	}

	/**
	 * compiles a tpl-file
	 *
	 * @param $tpl
	 * @return string
	 * @throws TemplateParserException
	 */
	public static function compile($tpl)
	{
		if (PROFILE) Profiler::mark("tpl::compile");

		/**
		 * replace comments
		 * <!--- comment --->
		 */
		$tpl = preg_replace('/<!---(.*)--->/Usi', "", $tpl);
		// we need an empty array
		self::$convert_vars_temp = array();

		// constants
		while (preg_match('/([^{]){([a-zA-Z0-9_]+)}([^}])/Usi', $tpl))
			$tpl = preg_replace('/([^{]){([a-zA-Z0-9_]+)}([^}])/Usi', '\\1<?php echo defined("\\2") ? constant("\\2") : null; ?>\\3', $tpl);

		$tpl = preg_replace('/<%\s*(' . implode("|", self::$language_reserved_words) . ')\s+(.*)\s*%>/Usi', '<% \\1(\\2) %>', $tpl);

		// this array is for storing the percent and prohibit twice parsing of variables
		$percents = array();
		/* variables in <% %> */
		preg_match_all("/<%(.*)%>/Usi", $tpl, $percent);
		foreach ($percent[1] as $key => $data) {
			if (strtolower(trim($data)) == "ignore_vars" || strtolower(trim($data)) == "end_ignore_vars") {
				continue;
			}

			$data = preg_replace_callback('/([a-zA-Z0-9_\.]+)\(/si', array("tpl", "functions"), $data);

			$data = preg_replace_callback('/\{?\$([a-zA-Z0-9_][a-zA-Z0-9_\-\.]+)\.([a-zA-Z0-9_]+)\((.*?)\)}?/si', array("tpl", "convert_vars"), $data);
			$data = preg_replace_callback('/\$([a-zA-Z0-9_][a-zA-Z0-9_\.]+)/si', array("tpl", "percent_vars"), $data);

			$data = preg_replace('/exit;?\s*$/Usi', "", $data);
			$data = str_replace('$caller->.', '->', $data);
			$data = preg_replace('/\)\.([a-zA-Z_])/', ")->\\1", $data);

			$_key = md5($data);
			$percents[$_key] = $data;
			$tpl = preg_replace('/' . preg_quote($percent[0][$key], '/') . '/si', '<%' . $_key . '%>', $tpl, 1);
			unset($data);
		}
		unset($percent);

		$ignores = array();
		preg_match_all("/<%\s*IGNORE_VARS\s*%>(.*)<%\s*END_IGNORE_VARS\s*%>/si", $tpl, $matches);
		foreach ($matches[1] as $key => $val) {
			$ignores[$key] = $val;
			$tpl = str_replace($matches[0][$key], "ignore______that_____" . $key, $tpl);
		}

		// normal vars
		$tpl = preg_replace_callback('/\{?\$([a-zA-Z0-9_][a-zA-Z0-9_\-\.]+)\.([a-zA-Z0-9_]+)\((.*?)\)}?/si', array("tpl", "convert_vars_echo"), $tpl);
		$tpl = preg_replace_callback('/\{?\$([a-zA-Z0-9_][a-zA-Z0-9_\-\.]+)}?/si', array("tpl", "vars"), $tpl);

		foreach ($ignores as $key => $val) {
			$tpl = str_replace("ignore______that_____" . $key, $val, $tpl);
		}

		foreach ($percents as $key => $data) {
			$tpl = str_replace('<%' . $key . '%>', '<%' . $data . '%>', $tpl);
		}
		foreach (self::$convert_vars_temp as $key => $value) {
			$tpl = str_replace('\\convert_var_' . $key . '\\', $value, $tpl);
		}

		// free memory
		self::$convert_vars_temp = array();

		$tpl = preg_replace_callback('/<%\s*IF\s+(.+)\s*%>/Usi', array("tpl", "PHPrenderIF"), $tpl);
		$tpl = preg_replace_callback('/<%\s*ELSE\s*IF\s+(.+)\s*%>/Usi', array("tpl", "PHPrenderELSEIF"), $tpl);
		$tpl = preg_replace('/<%\s*ELSE\s*%>/Usi', '<?php } else { ?>', $tpl);
		$tpl = preg_replace('/<%\s*ENDIF\s*%>/Usi', '<?php } ?>', $tpl);
		$tpl = preg_replace('/<%\s*END\s*%>/Usi', '<?php }  ?>', $tpl);
		// parse cached
		$tpl = preg_replace('/<%\s*(\$)(caller\-\>Cached)\((.*)\);?\s*%>/Usi', '<?php if(\\1\\2(\\3)) { ?>', $tpl);
		$tpl = preg_replace('/<%\s*(\$)(caller\-\>ENDCached)\((.*)\);?\s*%>/Usi', '<?php \\1\\2(\\3); } ?>', $tpl);

		// parse functions
		$tpl = preg_replace('/<%\s*(\$)([a-z0-9_\.\->\(\)\$\-]+)\((.*)\);?\s*%>/Usi', '<?php $currentObjectForPrinting = \\1\\2(\\3); if(!is_object($currentObjectForPrinting) && !is_array($currentObjectForPrinting)) { echo $currentObjectForPrinting; } ?>', $tpl);

		$tpl = preg_replace('/<%\s*echo\s+(.*)\s*%>/Usi', '<?php echo \\1; ?>', $tpl);
		$tpl = preg_replace('/<%\s*print\s+(.*)\s*%>/Usi', '<?php print \\1; ?>', $tpl);
		$tpl = preg_replace('/<%\s*config\s+(.*)\s*%>/Usi', '<?php \\1; ?>', $tpl);

		$controlCount = 0;

		// CONTROL
		$tpl = preg_replace('/<%\s*CONTROL\s+(\$)([a-z0-9_\.\-\>\(\)\s]+)\->([a-zA-Z0-9_]+)\(([^%]*)\)\s+as\s+\$data\[["\']([a-zA-Z0-9_\-]+)["\']\]\s*%>/Usi',
			'
<?php 
	// begin control
	$callerStack[] = clone $caller;
	$dataStack[] = clone $data;

	$value = \\1\\2->\\3(\\4);
	if(is_object($value) && is_a($value, "DataObject")) {
		$value = new DataSet(array($value));
	}
	if(is_array($value) || (is_object($value) && $value instanceof Traversable))
		foreach($value as $key => $data_loop) {
			$data->customise(array("\\5" => $data_loop, "\\5_index" => $key));
			if(is_object($data_loop)) 
				$caller->callers[strtolower("\\5")] = new tplCaller($data_loop); 
			else  
				$caller->callers[strtolower("\\5")] = new tplCaller(new ViewAccessableData(array($data_loop))); 
?>
', $tpl, -1, $controlCount);
		$controlCount2 = 0;
		$tpl = preg_replace('/<%\s*CONTROL\s+(\$)([a-z0-9_\.\-\>\(\)]+?)\->([a-z0-9_]+)\((.*)\)\s*%>/Usi',
			'
<?php 
	// begin control
	$callerStack[] = clone $caller;
	$dataStack[] = clone $data;
	
	$value = \\1\\2->\\3(\\4);
	if(is_object($value) && is_a($value, "DataObject")) {
		$value = new DataSet(array($value));
	}
	if(is_array($value) || (is_object($value) && $value instanceof Traversable))
		foreach($value as $key => $data_loop) {
			$data->customise(array("\\3" => $data_loop, "\\3_index" => $key));
			if(is_object($data_loop)) 
				$caller->callers[strtolower("\\3")] = new tplCaller($data_loop); 
			else  
				$caller->callers[strtolower("\\3")] = new tplCaller(new ViewAccessableData(array($data_loop))); 
?>
', $tpl, -1, $controlCount2);

		$controlCount3 = 0;
		$tpl = preg_replace('/<%\s*CONTROL\s+array\((.*)\)\s+as\s+\$data\[["\']([a-zA-Z0-9_\-]+)["\']\]\s*%>/Usi',
			'
<?php 
	// begin control
	$callerStack[] = clone $caller;
	$dataStack[] = clone $data;
	
	$value = array(\\1);
	if(is_object($value) && is_a($value, "DataObject")) {
		$value = new DataSet(array($value));
	}
	if(is_array($value) || (is_object($value) && $value instanceof Traversable))
		foreach($value as $key => $data_loop) {
			$data->customise(array("\\2" => $data_loop, "\\2_index" => $key));
			if(is_object($data_loop)) 
				$caller->callers[strtolower("\\2")] = new tplCaller($data_loop); 
			else  
				$caller->callers[strtolower("\\2")] = new tplCaller(new ViewAccessableData(array($data_loop))); 
?>
', $tpl, -1, $controlCount3);

		$endControlCount = 0;

		$tpl = preg_replace('/<%\s*ENDCONTROL(.*)\s*%>/Usi', '
<?php 
// end control
		}

$caller = array_pop($callerStack); 
$data = array_pop($dataStack); 
?>
', $tpl, -1, $endControlCount);

		$controlCount = $controlCount + $controlCount2 + $controlCount3;
		unset($controlCount2);

		// validate counters
		if ($controlCount > $endControlCount) {
			throw new TemplateParserException('Expected <% ENDCONTROL %> ' . ($controlCount - $endControlCount) . ' more time(s) in ' . self::$tpl . '.', self::$tpl);
		} else if ($endControlCount > $controlCount) {
			throw new TemplateParserException('Expected <% CONTROL [method] %> ' . ($endControlCount - $controlCount) . ' more time(s) in ' . self::$tpl . '.', self::$tpl);
		}

		// areas, DEPRECATED!
		$tpl = preg_replace_callback('/\<garea\s+name=("|\')([a-zA-Z0-9_-]+)\1\s*\>(.*?)\<\/garea\s*\>/si', array("tpl", "areas"), $tpl);
		$tpl = preg_replace_callback('/\<garea\s+name=("|\')([a-zA-Z0-9_-]+)\1\s+reload=("|\')([a-zA-Z0-9_-]+)\3\s*\>(.*?)\<\/garea\s*\>/si', array("tpl", "iareas"), $tpl);

		// check areas in includes
		preg_match_all('/' . preg_quote('<?php echo $caller->INCLUDE(', '/') . '("|\')([a-zA-Z0-9_\.\-\/]+)\1\); \?\>/Usi', $tpl, $matches);
		foreach ($matches[2] as $file) {
			$filename = self::getFilename($file, "", true);
			if (self::$tpl == $file) {
				continue;
			}
			self::buildFilesForTemplate($filename, realpath($filename));
		}
		unset($matches);

		// you can hook into it
		Core::callHook("compileTPL", $tpl);
		if (PROFILE) Profiler::unmark("tpl::compile");

		return $tpl;
	}

	/**
	 * this function parses functions in the tpl
	 *
	 * @name functions
	 * @return string
	 */
	public static function functions(array $matches)
	{
		$name = trim(strtolower($matches[1]));
		if ($name == "print" || $name == "echo" || $name == "array") {
			return $matches[0];
		} else {
			if (strpos($name, ".")) {
				$names = explode(".", $name);
				$count = count($names);
				$data = '$caller';
				foreach ($names as $key => $name) {
					if ($key == ($count - 1)) {
						$data .= '->' . $name;
					} else {
						$data .= '->' . $name . '()';
					}
				}
				$name = $data;
			} else {
				$name = '$caller->' . $matches[1];
			}

			$php = $name . '(';

			return $php;
		}
	}

	/**
	 * parses areas
	 *
	 * @name areas
	 * @access public
	 * @return string
	 */
	public static function areas($matches)
	{
		Core::deprecate(2.0, "Use of areas is Deprecated! Please use normal vars instead in " . self::$tpl);

		return '<?php if($data->getTemplateVar(' . var_export($matches[2], true) . ')) { echo $data->getTemplateVar(' . var_export($matches[2], true) . '); } else { ?>' . $matches[3] . "<?php } ?>";
	}

	/**
	 * parses areas
	 *
	 * @name areas
	 * @access public
	 * @return string
	 */
	public static function iareas($matches)
	{
		Core::deprecate(2.0, "Use of areas is Deprecated! Please use normal vars instead in " . self::$tpl);

		return '<?php if($data->getTemplateVar(' . var_export($matches[2], true) . ')) { echo $data->getTemplateVar(' . var_export($matches[2], true) . '); } else { ?>' . $matches[5] . "<?php } ?>";
	}


	/**
	 * renders the IF with php-tags
	 *
	 * @name PHPRenderIF
	 * @access public
	 * @return string
	 */
	public static function PHPRenderIF($matches)
	{
		return '<?php ' . self::renderIF($matches) . ' ?>';
	}

	/**
	 * renders the ELSEIF with php-tags
	 *
	 * @name PHPRenderELSEIF
	 * @access public
	 * @return string
	 */
	public static function PHPRenderELSEIF($matches)
	{
		return '<?php ' . self::renderELSEIF($matches) . ' ?>';
	}

	/**
	 * callback for vars in <% %>
	 *
	 * @name percent_vars
	 * @return string
	 */
	public static function percent_vars($matches)
	{

		$name = $matches[1];

		if ($name == "caller")
			return '$caller';

		if ($name == "data")
			return '$data';

		if (substr($name, -1) == ".") {
			$name = substr($name, 0, -1);
			$point = ".";
		} else {
			$point = "";
		}

		if (preg_match('/^_lang_([a-zA-Z0-9\._-]+)/i', $name, $data)) {
			return 'lang("' . $data[1] . '", "' . $data[1] . '")' . $point;
		}

		if (preg_match('/^_cms_([a-zA-Z0-9_-]+)/i', $name, $data)) {
			return 'Core::getCMSVar(' . var_export($data[1], true) . ')' . $point;
		}


		if (strpos($name, ".")) {
			$parts = explode(".", $name);
			$php = '$data';
			foreach ($parts as $part) {
				$php .= '[' . var_export($part, true) . ']';
			}

			return $php . $point;
		} else {
			return '$data[' . var_export($name, true) . ']' . $point;
		}
	}

	/**
	 * vars with convertion
	 *
	 * @name convert_vars
	 * @access public
	 * @return string
	 */
	public static function convert_vars($matches)
	{

		$php = '$data';
		$var = $matches[1];
		$function = $matches[2];
		$params = $matches[3];

		// isset-part
		$isset = 'isset($data';
		// parse params
		$params = preg_replace_callback('/\$([a-zA-Z0-9_][a-zA-Z0-9_\.]+)/si', array("tpl", "percent_vars"), $params);
		// parse functions in params
		$params = preg_replace_callback('/([a-zA-Z0-9_\.]+)\((.*)\)/si', array("tpl", "functions"), $params);

		if (strpos($var, ".")) {
			$varparts = explode(".", $var);
			$i = 0;
			$count = count($varparts);
			$count--;
			foreach ($varparts as $part) {
				if ($count == $i) {
					// last
					$php .= '->doObject("' . $part . '")';
					$isset .= '["' . $part . '"]';
				} else {
					$php .= '["' . $part . '"]';
					$isset .= '["' . $part . '"]';
				}
				$i++;
			}
		} else {
			$php .= '->doObject("' . $var . '")';
			$isset .= '["' . $var . '"]';
		}
		$isset .= ')';
		$php .= "->" . $function . "(" . $params . ")";
		$php = '(' . $isset . ' ? ' . $php . ' : "")';

		$key = count(self::$convert_vars_temp);
		self::$convert_vars_temp[$key] = $php;

		return '\\convert_var_' . $key . '\\';
	}

	/**
	 * convert vars with echo
	 *
	 * @name convert_vars_echo
	 * @access public
	 * @return string
	 */
	public static function convert_vars_echo($matches)
	{
		return '<?php echo ' . self::convert_vars($matches) . '; ?>';
	}

	/**
	 * callback for vars
	 *
	 * @name vars
	 * @return string
	 */
	public static function vars($matches)
	{
		$name = $matches[1];
		if (preg_match('/^_lang_([a-zA-Z0-9\._-]+)/i', $name, $data)) {
			return '<?php echo lang("' . $data[1] . '", "' . $data[1] . '"); ?>';
		}

		if (preg_match('/^_cms_([a-zA-Z0-9_-]+)/i', $name, $data)) {
			return '<?php echo Core::getCMSVar(' . var_export($data[1], true) . '); ?>';
		}

		return '<?php echo $data->getTemplateVar(' . var_export($name, true) . '); ?>';
	}

	/**
	 * renders IF-clauses
	 *
	 * @name renderIF
	 * @access public
	 * @return string
	 */
	public static function renderIF($matches)
	{
		$clause = $matches[1];
		// first parse
		$clause = str_replace("=", '==', $clause);
		$clause = str_replace("====", '==', $clause);
		$clause = str_replace("!==", '!=', $clause);
		$clause = preg_replace('/NOT/i', '!', $clause);

		// second partse parts for just bool-values
		$clauseparts = preg_split('/( or | and |\|\||&&)/i', $clause, -1, PREG_SPLIT_DELIM_CAPTURE);
		$newclause = "";
		foreach ($clauseparts as $part) {
			if (strtolower($part) == " and " || $part == "&&") {
				$newclause .= "&&";
			} else if (strtolower($part) == " or " || $part == "||") {
				$newclause .= "||";
			} else {
				if (preg_match("/\=/", $part)) { // clause with =
					$newclause .= $part;
				} else if (preg_match('/\$data\[["\'](.*)["\']\]$/', trim($part), $matches)) {
					$dataparts = preg_split('/["\']\]\[["\']/', $matches[1]);
					$cond = '$data';
					foreach ($dataparts as $_part) {
						$cond .= '->doObject("' . $_part . '")';
					}
					unset($dataparts, $_part, $matches);
					if (preg_match('/!/', trim($part))) {
						$newclause .= '(!' . $cond . ' || !' . $cond . "->bool())";
					} else {
						$newclause .= '(' . $cond . ' && ' . $cond . "->bool())";
					}

				} else {
					$newclause .= $part;
				}
			}
		}

		$newclause = str_replace('$$', '$', $newclause);

		// render clause
		return 'if(' . $newclause . ') {';
	}

	/**
	 * renders ELSEIF-clauses
	 *
	 * @name renderELSEIF
	 * @access public
	 * @return string
	 */
	public static function renderELSEIF($matches)
	{
		return '} else ' . self::renderIF($matches) . " ";
	}

	/**
	 * returns a filename, which you can include
	 *
	 * @param string $name
	 * @param string $class
	 * @return array
	 */
	public static function getIncludeName($name, $class = "")
	{
		$file = self::getFilename($name, $class, true);
		$filename = self::BuildFilesForTemplate($file, realpath($file));
		if ($filename === false) {
			throw new LogicException("Could not create Template-Cache-Files for Template <strong>" . $file . "</strong>");
		}
		unset($tpl);

		return array($filename, $file);
	}

	/**
	 * @access public
	 * @param string - filename
	 * @param bollean - to follow <!tpl inc:"neu"> or not
	 * @param array - for replacement like {$content}
	 * @use: parse tpl
	 * @return string
	 */
	public static function includeparser($tpl, $tmpname)
	{
		if (PROFILE) Profiler::mark("tpl::includeparser");

		if (!($t = filemtime($tmpname))) {
			$t = 0;
		}

		$cacher = new tplcacher($tmpname, $t);
		if ($cacher->checkvalid() !== true) {
			$data = file_get_contents($tpl);
			$tpldata = self::compile($data);
			$cacher->write($tpldata);
			unset($tpldata, $data);
		}

		if (PROFILE) Profiler::unmark("tpl::includeparser");

		return $cacher->filename();
	}
}

class TemplateNotFoundException extends LogicException {
	/**
	 * @var string
	 */
	protected $templateName;

	/**
	 * TemplateNotFoundException constructor.
	 * @param string $templateName
	 * @param int $message
	 * @param int $code
	 * @param Exception $previous
	 */
	public function __construct($templateName, $message, $code = ExceptionManager::TPL_NOT_FOUND, Exception $previous = null)
	{
		$this->templateName = $templateName;

		parent::__construct($message . " Template: " . $templateName, $code, $previous);
	}
}
