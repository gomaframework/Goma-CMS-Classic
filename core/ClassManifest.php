<?php defined("IN_GOMA") OR die();

/**
 * This class generates the class manifest as well provides methods to identify relationship of classes and
 * its names.
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @package		Goma\Framework
 * @version		3.4
 */
class ClassManifest {
	/**
	 * Files, that are loaded at each request.
	 */
	public static $preload = array();

	/**
	 * Class cache.
	 */
	public static $classes = array();

	/**
	 * Array of all directories, that will be scanned recursively.
	 */
	static public $directories = array('system');

	/**
	 * List of class aliases.
	 */
	private static $class_alias = array(
        "showsitecontroller" => "frontedcontroller",
        "imageupload" => "ImageUploadField",
        "object" => "gObject",
        "_array" => "arraylib",
        "dataobjectholder" => "viewaccessabledata",
        "autoloader" => "ClassManifest",
        "testsuite" => "Object"
    );

	/**
	 * Loads a class.
	 *
	 * @param	string $class Classname
	 *
	 * @return	void
	 */
	public static function load($class) {
		$class = self::resolveClassName($class);

		if(PROFILE)
			Profiler::mark("Manifest::load");

		self::loadInterface($class) || self::loadClass($class) || self::generateAlias($class);
		
		if(class_exists('Core', false)) {
			Core::callHook('loadedClass', $class);
		}
		
		if(PROFILE)
			Profiler::unmark("Manifest::load");
	}

	/**
	 * tries to include a file.
	*/
	public static function tryToInclude($class, $file) {

		$class = self::resolveClassName($class);

		if(!class_exists($class, false)) {
			include($file);
		}
	}

	/**
	 * loads interface.
	*/
	protected static function loadInterface($class) {
		if(isset(ClassInfo::$interfaces[$class]) && !interface_exists($class, false)) {
            if(strpos($class, "\\") !== false) {
                $namespace = 'namespace ' . substr($class, 0, strrpos($class, "\\")) . ";";
                $class = substr($class, strrpos($class, "\\") + 1);
            } else {
                $namespace = "";
            }

			if(isset(ClassInfo::$interfaces[$class]) && ClassInfo::$interfaces[$class]) {
				eval($namespace . 'interface '.$class.' extends '.ClassInfo::$interfaces[$class].' {}');
			} else {
				eval($namespace . 'interface '.$class.' {}');
			}
			
			return true;
		}
	}

    /**
     * loads class.
     * @param string $class
     * @return bool
     */
	protected static function loadClass($class) {
		if(isset(ClassInfo::$files[$class])) {
			if(!include(ClassInfo::$files[$class])) {
				ClassInfo::Delete();
				throw new LogicException("Could not include " . ClassInfo::$files[$class] . ". ClassInfo seems to be old.");
			}

			return true;
		}
	}

	/**
	 * generates alias.
	*/
	protected static function generateAlias($class) {
		if(isset(self::$class_alias[$class])) {
			if(defined("DEV_MODE") && DEV_MODE) {
				// we log this, because it's not good, aliases are just for deprecation.
				logging("Making alias " . self::$class_alias[$class] . " of " . $class . "");
			}

			// make a alias
			class_alias(self::$class_alias[$class], $class);

			return true;
		}

        if($class == "phpunit_framework_testcase" && !isPHPUnit()) {
            class_alias("gObject", $class);
            return true;
        }
	}

	/**
	 * Generates class manifest for all in $directories defined folders.
	 *
	 * @param	string &$classes
	 * @param	string &class_info
	 * @param	array[] &$env
	 *
	 * @return	void
	 */
	public static function generate_all_class_manifest(&$classes, &$class_info, &$env) {
		foreach(self::$directories as $dir) {
			self::generate_class_manifest($dir, $classes, $class_info, $env);
		}
	}

    /**
     * returns true if two classes can be treated as the same.
     * @param string|gObject $class1
     * @param string|gObject $class2
     * @return bool
     */
    public static function isSameClass($class1, $class2) {
        return (self::resolveClassName($class1) == self::resolveClassName($class2));
    }

    /**
     * returns true if two classes can be treated as the same or are subclasses of each other.
     *
     * @param string|gObject $class1
     * @param string|gObject $class2
     * @return bool
     */
    public static function classesRelated($class1, $class2) {

        // force strings
        $class1 = self::resolveClassName($class1);
        $class2 = self::resolveClassName($class2);

        return self::isSameClass($class1, $class2) || is_subclass_of($class1, $class2) || is_subclass_of($class2, $class1);
    }

    /**
     * returns true if is same type, so also if given class is subclass of parent.
     *
     * @param string|gObject $child
     * @param string|gObject $parent
     * @return bool
     */
    public static function isOfType($child, $parent) {
        return self::isSameClass($child, $parent) || is_subclass_of(self::resolveClassName($child), self::resolveClassName($parent));
    }

    /**
     * Resolves qualified class name out of string.
     * It converts - to backslash.
     * It removed leading and trailing backslash.
     *
     * @param string|object $class
     * @return string
     */
    public static function resolveClassName($class) {
        if(is_object($class)) {
            if(isset($class->classname)) {
                return $class->classname;
            }

            $class = strtolower(get_class($class));
        } else if(is_array($class)) {
            throw new InvalidArgumentException("Classname should be string or object.");
        } else {
            $class = strtolower(trim($class));
        }

        if(strpos($class, "-")) {
            $class = str_replace("-", "\\", $class);
        }

        if(substr($class, -1) == "\\") {
            $class = substr($class, 0, -1);
        }

        if(substr($class, 0, 1) == "\\") {
            $class = substr($class, 1);
        }

        return $class;
	}

    /**
     * gets class name, which can be used in urls or css class names.
     * It gets full qualified class name via resolveClassName and replaces all backslashes with minuses.
     *
     * @param string|object $class
     * @return mixed
     */
    public static function getUrlClassName($class) {
        return str_replace("\\", "-", self::resolveClassName($class));
    }

    /**
     * Generates the class-manifest for a given directory.
     *
     * @param string $dir
     * @param array &$classes
     * @param array $class_info
     * @param array &$env
     */
	public static function generate_class_manifest($dir, &$classes, &$class_info, &$env) {
        $dir = realpath($dir);

        if (self::shouldBeScanned($dir, $classes, $class_info, $env)) {

            foreach (scandir($dir) as $file) {
                if ($file != "." && $file != "..") {
                    if (is_dir($dir . "/" . $file)) {
                        self::generate_class_manifest($dir . "/" . $file, $classes, $class_info, $env);
                    } else if (preg_match('/\.php$/i', $file) && $file != "ClassManifest.php") {
                        self::parsePHPFile($dir . "/" . $file, $classes, $class_info);
                    }

                    if ($file == "_config.php") {
                        self::addPreload($dir . "/" . $file);
                    }
                }
            }
        }
    }

    /**
     * checks if current folder should be scanned.
     *
     * returns true when should be and false when not.
     */
    protected static function shouldBeScanned($dir, &$classes, &$class_info, &$env) {
        if(file_exists($dir . "/_exclude.php")) {
            include_once ($dir . "/_exclude.php");
            return false;
        }

        if(file_exists($dir . "/autoloader_exclude")) {
            return false;
        }

        if(!DEV_MODE && file_exists($dir . "/autoloader_non_dev_exclude")) {
            return false;
        }

        // Extension-Layer
        if(file_exists($dir . '/contents/info.plist')) {
            $data = self::getPropertyList($dir . '/contents/info.plist');

            self::generateExtensionData($data, $dir, $classes, $class_info, $env);
            return false;
        }

        return true;
    }

    /**
     * parses PHP-file and fill classes-array.
     * @param string $file
     * @param array $classes
     * @param array $class_info
     */
    protected static function parsePHPFile($file, &$classes, &$class_info) {
        $contents = file_get_contents($file);

        // remove everyting that is not php
        $contents = preg_replace('/\/\*(.*)\*\//Usi', '', $contents);
        $contents = preg_replace('/\?\>(.*)\<?php/Usi', '', $contents);

        $namespace = self::getNamespace($contents);
        $usings = self::parseUsings($contents);

        preg_match_all('/(abstract\s+)?class\s+([a-zA-Z0-9\\\\_]+)(\s+extends\s+([a-zA-Z0-9\\\\_]+))?(\s+implements\s+([a-zA-Z0-9\\\\_,\s]+?))?\s*\{/Usi', $contents, $parts);
        foreach($parts[2] as $key => $class) {

            $class = self::resolveClassName($namespace . trim($class));

            if(!self::classHasAlreadyBeenIndexed($classes, $class, $file, count($parts[2]) == 1)) {
                self::generateDefaultClassInfo($class, $file, $parts[4][$key], $classes, $class_info, false, $namespace, $usings);
            }

            if($parts[6][$key]) {
                $interfaces = explode(",", $parts[6][$key]);
                $class_info[$class]["interfaces"] = array_map(function($interface) use($usings, $namespace) {
                    return self::resolveClassNameWithUsings($interface, $namespace, $usings);
                }, $interfaces);
            }

            if($parts[1][$key]) {
                $class_info[$class]["abstract"] = true;
            }
        }

        // index interfaces too
        preg_match_all('/interface\s+([a-zA-Z0-9\\\\_]+)(\s+extends\s+([a-zA-Z\\\\0-9_]+))?\s*\{/Usi', $contents, $parts);
        foreach($parts[1] as $key => $class) {
            $class = self::resolveClassName($namespace . trim($class));

            if(!self::classHasAlreadyBeenIndexed($classes, $class, $file, count($parts[1]) == 1)) {
                self::generateDefaultClassInfo($class, $file, $parts[3][$key], $classes, $class_info, true, $namespace, $usings);
            }
        }
    }

    /**
     * @param string $className
     * @param string $namespace
     * @param array $usings
     * @return string
     */
    protected static function resolveClassNameWithUsings($className, $namespace, $usings) {
        $resolvedClassName = self::resolveClassName($className);

        if(strpos($className, "\\") === false) {
            if(isset($usings[$resolvedClassName])) {
                return self::resolveClassName($usings[$resolvedClassName]);
            }

            if(!$namespace) {
                return $resolvedClassName;
            }

            return $namespace . $resolvedClassName;
        }

        if(substr(trim($className), 0, 1) == "\\") {
            return $resolvedClassName;
        }

        $qualifier = substr($resolvedClassName, 0, strpos($resolvedClassName, "\\"));
        if (isset($usings[$qualifier])) {
            return $usings[$qualifier] . "\\" . self::resolveClassName(
                substr($resolvedClassName, strpos($resolvedClassName, "\\") + 1)
            );
        }

        throw new InvalidArgumentException("Could not find class " . $className . ". Please define correct usings and namespace.");
    }

    /**
     * @param string $contents
     * @return array
     */
    protected static function parseUsings($contents) {
        $usings = array();
        preg_match_all('/use\s+([a-zA-Z0-9_\\\\]+)\;/Usi', $contents, $parts);
        foreach($parts[1] as $part) {
            $qualifiedName = strrpos($part, "\\") !== false ? substr($part, strrpos($part, "\\") + 1) : $part;
            $usings[strtolower($qualifiedName)] = $part;
        }

        return $usings;
    }

    /**
     * checks if class has already been indexed from another file.
     *
     * @param string $class
     * @param string $file
     * @param bool $shouldMoveThisFile
     * @return bool
     */
    protected static function classHasAlreadyBeenIndexed($classes, $class, $file, $shouldMoveThisFile) {
        if(isset($classes[$class]) && $classes[$class] != $file && file_exists($classes[$class])) {

            // check if given file is older and move it when possible
            if(filemtime($classes[$class]) > filemtime($file)) {
                if($shouldMoveThisFile) {
                    self::moveOldClass($file);

                    return true;
                }
            } else if(filemtime($classes[$class]) < filemtime($file)) {
                // check if indexed file is younger and move it when possible.
                // but cause it should be reindexed, we do not return true.
                if(count(array_keys($classes, $classes[$class])) == 1) {
                    self::moveOldClass($classes[$class]);
                }
            }
        }

        return false;
    }

    /**
     * generates default info for class.
     *
     * @param string $class resolved class-name
     * @param string $file
     * @param string $parent
     * @param array $classes
     * @param array $class_info
     * @param bool $interface
     * @param string $namespace
     * @param array $usings
     */
    protected static function generateDefaultClassInfo($class, $file, $parent, &$classes, &$class_info, $interface, $namespace, $usings) {
        $classes[$class] = $file;

        if(!isset($class_info[$class])) {
            $class_info[$class] = array();
        }

        if($parent) {
            $class_info[$class]["parent"] = self::resolveClassNameWithUsings($parent, $namespace, $usings);
            if($class_info[$class]["parent"] == $class) {
                if($interface) {
                    throw new LogicException("Interface '" . $class . "' can not extend itself in " . $file . ".");
                } else {
                    throw new LogicException("Class '" . $class . "' can not extend itself in " . $file . ".");
                }

            }
        }

        if($interface) {
            $class_info[$class]["abstract"] = true;
            $class_info[$class]["interface"] = true;
        }
    }

    /**
     * returns namespace out of file-contents.
     *
     * @param string $contents
     * @return string
     */
    protected static function getNamespace($contents) {
        if(preg_match("/namespace\s+([a-zA-Z0-9\\\\\s_]+)\;/Usi", $contents, $matches)) {
            $namespace = strtolower($matches[1]);
            if(substr($namespace, -1) == "\\") {
                $namespace = substr($namespace, 0, -1);
            }

            if(substr($namespace, 0, 1) == "\\") {
                $namespace = substr($namespace, 1);
            }

            return $namespace . "\\";
        }

        return "";
    }

    /**
     * moves an old class-file to another location when allowed.
     *
     * @param string $oldFile
     */
    protected static function moveOldClass($oldFile) {
        if(isset(ClassInfo::$appENV["app"]["allowDeleteOld"]) && ClassInfo::$appENV["app"]["allowDeleteOld"]) {
            logging("Delete " . $oldFile . ", because old Class!");
            if(!DEV_MODE) {
                // unlink file
                FileSystem::requireDir(ROOT . "__oldclasses/" . substr($oldFile, 0, strrpos($oldFile, "/")));
                rename($oldFile, ROOT . "__oldclasses/" . $oldFile);
            }
        }
    }

    /**
     * returns array of data from PropertiyList.
     */
    public static function getPropertyList($file) {
        self::tryToInclude('CFPropertyList', 'system/libs/thirdparty/plist/CFPropertyList.php');
        $plist = new CFPropertyList($file);
        return $plist->ToArray();
    }

    /**
     * generates data for extension.
     *
     * @param $data info about extension
     * @param String $dir
     * @param $classes class-index
     * @param $class_info classinfo-index
     * @param $env environment info
     * @return bool
     */
    public static function generateExtensionData($data, $dir, &$classes, &$class_info, &$env) {
        // test compatiblity
        if (self::isCompatibleAndNotDisabled($dir, $data)) {

            // let's remove some data to avoid saving too much data
            unset($data["requireFrameworkVersion"], $data["requireApp"], $data["requireAppVersion"]);

            $data["folder"] = $dir . "/contents/";
            // register in environment
            $env["expansion"][strtolower($data["name"])] = $data;

            // load code
            if (is_array($data["loadCode"])) {
                $env[strtolower($data["type"])][strtolower($data["name"])]["classes"] = array();
                foreach ($data["loadCode"] as $ldir) {
                    $env[strtolower($data["type"])][strtolower($data["name"])]["classes"] +=
                        self::loadFolder($dir . "/contents/" . $ldir, $classes, $class_info, $data["name"]);
                }
            } else {
                $env[strtolower($data["type"])][strtolower($data["name"])]["classes"] =
                    self::loadFolder($dir . "/contents/" . $data["loadCode"], $classes, $class_info, $data["name"]);
            }

            // load tests
            if (isset($data["tests"]) && DEV_MODE) {
                self::loadFolder($dir . "/contents/" . $data["tests"], $classes, $class_info, $data["name"]);
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * returns true when the extension is compatible and wasn't disabled.
     *
     * @param string $dir
     * @param array $data
     * @return bool
     */
    protected static function isCompatibleAndNotDisabled($dir, $data) {

        // check for data
        if(isset($data["name"], $data["type"], $data["loadCode"], $data["version"]) && ($data["type"] == "expansion" || $data["type"] == "extension")) {

            // check PHP-Version
            if (isset($data["requiredPHPVersion"]) && version_compare($data["requiredPHPVersion"], phpversion(), ">")) {
                return false;
            }

            if (isset($data["requireFrameworkVersion"]) &&
                goma_version_compare($data["requireFrameworkVersion"], GOMA_VERSION . "-" . BUILD_VERSION, ">")
            ) {
                return false;
            }

            if (isset($data["requireApp"]) && $data["requireApp"] != ClassInfo::$appENV["app"]["name"]) {
                return false;
            }

            if (isset($data["requireAppVersion"]) &&
                isset($data["requireApp"]) &&
                goma_version_compare($data["requireAppVersion"], ClassInfo::$appENV["app"]["version"] . "-" . ClassInfo::$appENV["app"]["build"], ">")
            ) {
                return false;
            }

            if (file_exists($dir . "/contents/.g_" . APPLICATION . ".disabled")) {
                return false;
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * loads code from given folder and defines inExpansion-Flag in classinfo for all classes.
     *
     * @param string $folder
     * @param array $classes class-file-index
     * @param array $class_info classinfo
     * @param string $expansion
     * @return array list of classes
     */
    protected static function loadFolder($folder, &$classes, &$class_info, $expansion = null) {
        if(is_dir($folder)) {
            $classesInFolder = array();
            self::generate_class_manifest($folder, $classesInFolder, $class_info, $env);
            foreach($classesInFolder as $class => $file) {
                if(isset($expansion)) {
                    $class_info[$class]["inExpansion"] = strtolower($expansion);
                }
                $classes[$class] = $file;
            }

            return array_keys($classesInFolder);
        } else {
            throw new LogicException("ClassManifest::loadFolder: $folder must exist and be a folder.");
        }
    }

	/**
	 * Add file for preload array.
	 *
	 * @param string $file Filename
	 *
	 * @return void
	 */
	public static function addPreload($file) {
		self::$preload[$file] = $file;
	}

	/**
	 * Include all files.
	 *
	 * @return void
	 */
	public static function include_all() {
		foreach(ClassInfo::$files as $class => $file) {
			if(!class_exists($class, false) && !interface_exists($class, false) && strtolower($class) != "l") {
				self::load($class);
			}
		}
	}

	public static function addUnitTest () {
		self::$class_alias["unittestcase"] = gObject::class;
	}
}

spl_autoload_register("ClassManifest::load");

// This method does not exist in each PHP-Build
if(!function_exists("class_alias")) {
	function class_alias($org, $alias) {
		eval("class " . $org . " extends " . $alias . " {}");
	}
}