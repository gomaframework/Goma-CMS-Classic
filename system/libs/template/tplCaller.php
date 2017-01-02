<?php defined("IN_GOMA") OR die();

/**
 * this class is a class for calling functions from the template
 * it provides functions to add allowed methods to the template
 *
 * @link            http://goma-cms.org
 * @license:        LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 * @author          Goma-Team
 * @package goma\template
 */
class tplCaller extends gObject
{
    /**
     * goma adds all caller in CONTROL xxx to this var
     * @internal
     */
    public $callers = array();

    /**
     * current template-file
     */
    protected $tpl;

    /**
     * current template-base
     */
    protected $tplBase;

    /**
     * sub-path, for example admin/ or history/
     * so this is not connected with template-root
     */
    protected $subPath;

    /**
     * this var contains the dataobject for this caller
     */
    private $dataobject;

    /**
     * cache-bufffar.
     */
    private $cacheBuffer = array();

    /**
     * cachers.
     */
    private $cacher = array();

    /**
     * @param gObject $dataobject
     * @param string $tpl
     */
    public function __construct(gObject &$dataobject, $tpl = "")
    {
        parent::__construct();

        $this->setTplPath($tpl);
        $this->dataobject = $dataobject;
        $this->inExpansion = $this->dataobject->inExpansion;

    }

    /**
     * sets tpl-paths
     *
     * @param $tpl
     * @param string $root
     * @internal param $setTplPath
     * @access public
     */
    public function setTplPath($tpl, $root = ROOT)
    {
        $this->tplBase = substr($tpl, 0, strrpos(str_replace("\\", "/", $tpl), "/"));
        if (str_replace("\\", "/", substr($this->tplBase, 0, strlen($root))) == str_replace("\\", "/", $root)) {
            $this->tplBase = substr($this->tplBase, strlen($root));
            while(substr($this->tplBase, 0, 1) == "/") {
                $this->tplBase = substr($this->tplBase, 1);
            }
        }

        if (substr(SYSTEM_TPL_PATH, -1) == "/") {
            $systemL = strlen(SYSTEM_TPL_PATH) + 1;
        } else {
            $systemL = strlen(SYSTEM_TPL_PATH);
        }

        if (substr(APPLICATION_TPL_PATH, -1) == "/") {
            $appL = strlen(APPLICATION_TPL_PATH) + 1;
        } else {
            $appL = strlen(APPLICATION_TPL_PATH);
        }

        if (substr($this->tplBase, 0, strlen("tpl/" . Core::getTheme())) == "tpl/" . Core::getTheme()) {
            $this->subPath = substr($this->tplBase, strlen("tpl/" . Core::getTheme()));
        } else if (substr($this->tplBase, 0, strlen(SYSTEM_TPL_PATH)) == SYSTEM_TPL_PATH) {
            $this->subPath = substr($this->tplBase, $systemL + 1);
        } else if (substr($this->tplBase, 0, strlen(APPLICATION_TPL_PATH)) == APPLICATION_TPL_PATH) {
            $this->subPath = substr($this->tplBase, $appL + 1);
        }

        if (isset($this->subPath) && !$this->subPath)
            $this->subPath = "";

        $this->tpl = $tpl;
    }

    /**
     * prints a debug
     *
     * @name printDebug
     * @access public
     */
    public function printDebug()
    {
        $data = debug_backtrace();
        unset($data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6]);
        $data = array_values($data);
        if (count($data) > 6) {
            $data = array($data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6]);
        }
        echo convert::raw2text(print_r($data, true));
    }

    /**
     * gets resource-path of given expansion or class-expansion
     *
     * @name resource_path
     * @access public
     * @return string
     */
    public function resource_path($exp = null)
    {
        if (!isset($exp))
            $exp = $this->dataobject->inExpansion;

        if (!$exp)
            return "";

        if (!isset(ClassInfo::$appENV["expansion"][$exp]))
            return "";

        $extFolder = ExpansionManager::getExpansionFolder($exp, false);

        return isset(ClassInfo::$appENV["expansion"][$exp]["resourceFolder"]) ? $extFolder . ClassInfo::$appENV["expansion"][$exp]["resourceFolder"] : $extFolder . "resources";
    }

    /**
     * gets resource-path of given expansion or class-expansion
     * @param string|null $exp
     * @return string
     */
    public function ResourcePath($exp = null)
    {
        return $this->resource_path($exp);
    }

    /**
     * to include another template
     * @param string $name
     * @param array|null $data
     * @throws Exception
     */
    public function _include($name, $data = null)
    {
        if (tpl::getFilename($name, $this->dataobject, true)) {
            $tpl = tpl::getIncludeName($name, $this->dataobject);
        } else if (tpl::getFilename($this->subPath . "/" . $name, $this->dataobject, true)) {
            $tpl = tpl::getIncludeName($this->subPath . "/" . $name, $this->dataobject);
        } else {
            throwError(7, "Template-file missing", "Could not include Template-File '" . $name . "'");
        }

        $caller = clone $this;
        $caller->setTplPath($tpl[1]);
        if (!isset($data))
            $data = $this->dataobject;

        $caller->dataobject = $data;

        $callerStack = array();
        $dataStack = array();
        include($tpl[0]);
    }

    /**
     * gets a variable of this dataobject by name
     *
     * @param string $name
     */
    public function getVar($name)
    {
        return $this->getTemplateVar($name);
    }

    /**
     * returns if the current admin wants to see the view as user
     */
    public function adminAsUser()
    {
        return Core::adminAsUser();
    }

    /**
     * shows statistics
     *
     * @name stats
     * @access public
     * @param text
     */
    function stats($text)
    {
        $c = new statistics;
        $parts = explode("|", $text);
        $c->today = $parts[0];
        $c->last2 = $parts[1];
        $c->last30d = $parts[2];
        $c->whole = $parts[3];
        $c->clicks = $parts[4];
        $c->online = $parts[5];

        return $c->getBoxContent();
    }

    /**
     * starts a cache-block.
     */
    public function cached()
    {
        if (PROFILE) Profiler::mark("tplcaller::cached");

        $args = func_get_args();
        foreach ($args as $k => $v) {
            if (is_object($v)) {
                if (is_a($v, "DataObjectSet")) {
                    $args[$k] = md5(serialize($v->forceData()->toArray()));
                } else if (gObject::method_exists($v, "toArray")) {
                    $args[$k] = md5(serialize($v->ToArray()));
                } else {
                    $args[$k] = md5(serialize($v));
                }
            } else if (is_array($v)) {
                $args[$k] = md5(serialize($v));
            }
        }

        array_push($this->cacheBuffer, ob_get_clean());
        ob_start();
        $cacher = new Cacher("tpl_" . $this->tpl . "_" . $this->dataobject->versionid . "_" . implode("_", $args));
        array_push($this->cacher, $cacher);
        if ($cacher->checkValid()) {
            echo array_pop($this->cacheBuffer);
            echo $cacher->getData();

            if (PROFILE) Profiler::unmark("tplcaller::cached", "tplcaller::cached load");

            return false;
        } else {
            if (PROFILE) Profiler::unmark("tplcaller::cached");

            return true;
        }
    }

    /**
     * ends cache-block.
     */
    public function endcached()
    {
        $dataUntilNow = ob_get_clean();
        ob_start();
        if ($cacher = array_pop($this->cacher)) {
            $cacher->write($dataUntilNow, tpl::$cacheTime);
        }
        echo array_pop($this->cacheBuffer);
        echo $dataUntilNow;
    }

    /**
     * gets the current theme
     *
     * @name getTheme
     * @access public
     */
    public function getTheme()
    {
        return self::getTheme();
    }

    /**
     * includes CSS
     *
     * @param string $name
     */
    public function include_css($name)
    {
        $this->includeCssByType($name);
    }

    /**
     * includes CSS in main-class
     *
     * @param string $name
     */
    public function include_css_main($name)
    {
        $this->includeCssByType($name, "main");
    }

    /**
     * includes css.
     *
     * @param string $name
     * @param string $type
     * @return string
     */
    protected function includeCssByType($name, $type = "") {
        if (preg_match("/\.(css|scss|sass|less)$/i", $name)) {
            if (isset($this->subPath)) {
                if (self::file_exists("tpl/" . Core::getTheme() . "/" . $this->subPath . "/" . $name)) {
                    $name = "tpl/" . Core::getTheme() . "/" . $this->subPath . "/" . $name;
                    Resources::add($name, "css", $type);

                    return "";
                } else if (self::file_exists(APPLICATION_TPL_PATH . $this->subPath . "/" . $name)) {
                    $name = APPLICATION_TPL_PATH . $this->subPath . "/" . $name;
                    Resources::add($name, "css", $type);

                    return "";
                } else if (self::file_exists(SYSTEM_TPL_PATH . $this->subPath . "/" . $name)) {
                    $name = SYSTEM_TPL_PATH . $this->subPath . "/" . $name;
                    Resources::add($name, "css", $type);

                    return "";
                }
            }

            if (self::file_exists($this->tplBase . "/" . $name)) {
                $name = $this->tplBase . "/" . $name;
            }
        }
        Resources::add($name, "css", $type);
    }

    /**
     * includes JS
     * @param string $name
     * @return string
     */
    public function include_js($name)
    {
        if (preg_match("/\.js$/i", $name)) {
            if (isset($this->subPath)) {
                if (self::file_exists("tpl/" . Core::getTheme() . "/" . $this->subPath . "/" . $name)) {
                    $name = "tpl/" . Core::getTheme() . "/" . $this->subPath . "/" . $name;
                    Resources::add($name, "js", "tpl");

                    return "";
                } else if (self::file_exists(APPLICATION_TPL_PATH . $this->subPath . "/" . $name)) {
                    $name = APPLICATION_TPL_PATH . $this->subPath . "/" . $name;
                    Resources::add($name, "js", "tpl");

                    return "";
                } else if (self::file_exists(SYSTEM_TPL_PATH . $this->subPath . "/" . $name)) {
                    $name = SYSTEM_TPL_PATH . $this->subPath . "/" . $name;
                    Resources::add($name, "js", "tpl");

                    return "";
                }
            }
            if (self::file_exists($this->tplBase . "/" . $name)) {
                $name = $this->tplBase . "/" . $name;
            } else if (!isset($this->subPath) && file_exists("tpl/" . Core::getTheme() . "/" . $name)) {
                $name = "tpl/" . Core::getTheme() . "/" . $name;
            }
        }
        Resources::add($name, "js", "tpl");
    }

    /**
     * includes JS as "main"
     * @param string $name
     * @return string
     */
    public function include_js_main($name)
    {
        if (preg_match("/\.js$/i", $name)) {
            if (isset($this->subPath)) {
                if (self::file_exists("tpl/" . Core::getTheme() . "/" . $this->subPath . "/" . $name)) {
                    $name = "tpl/" . Core::getTheme() . "/" . $this->subPath . "/" . $name;
                    Resources::add($name, "js", "main");

                    return "";;
                } else if (self::file_exists(APPLICATION_TPL_PATH . $this->subPath . "/" . $name)) {
                    $name = APPLICATION_TPL_PATH . $this->subPath . "/" . $name;
                    Resources::add($name, "js", "main");

                    return "";
                } else if (self::file_exists(SYSTEM_TPL_PATH . $this->subPath . "/" . $name)) {
                    $name = SYSTEM_TPL_PATH . $this->subPath . "/" . $name;
                    Resources::add($name, "js", "main");

                    return "";
                }
            }
            if (self::file_exists($this->tplBase . "/" . $name)) {
                $name = $this->tplBase . "/" . $name;
            } else if (!isset($this->subPath) && file_exists("tpl/" . Core::getTheme() . "/" . $name)) {
                $name = "tpl/" . Core::getTheme() . "/" . $name;
            }
        }
        Resources::add($name, "js", "main");
    }

    /**
     * returns if homepage
     */
    public function is_homepage()
    {
        return (defined("HOMEPAGE") && HOMEPAGE);
    }

    /**
     * returns if homepage
     */
    public function isHomepage()
    {
        return (defined("HOMEPAGE") && HOMEPAGE);
    }

    /**
     * for language
     *
     * @param string $name
     * @param string $default if not set use this
     * @return string
     */
    public function lang($name, $default = "")
    {
        return lang($name, $default);
    }

    /**
     * gloader::load
     */
    public function gload($name)
    {
        gloader::load($name);
    }
    /**
     * Layer for right-management
     */

    /**
     * returns true if current user is admin
     */
    public function admin()
    {
        return Permission::check("ADMIN");
    }

    /**
     * returns true if the user is logged in
     */
    public function login()
    {
        return member::login();
    }

    /**
     * returns true if the current user is not logged in
     */
    public function logout()
    {
        return member::logout();
    }

    /**
     * checks given permission
     */
    public function permission($perm)
    {
        return Permission::check($perm);
    }

    /**
     * gets all languages
     */
    public function languages()
    {
        $set = new DataSet();
        $data = i18n::listLangs();
        foreach ($data as $lang => $contents) {
            $set->add(array_merge($contents, array(
                "code" => $lang,
                "name" => $lang,
                "active" => $lang == Core::$lang
            )));

        }

        return $set;
    }

    /**
     * returns info about current lang
     */
    public function currentLang()
    {
        return new ViewAccessableData(array_merge(i18n::getLangInfo(), array("code" => Core::$lang)));
    }

    /**
     * breadcrumbs
     */
    public function breadcrumbs()
    {
        $data = new DataSet();
        foreach (Core::$breadcrumbs as $link => $title) {
            $data->push(array('link' => $link, 'title' => convert::raw2text($title)));
        }

        return $data;
    }

    /**
     * gets all headers
     */
    public function header()
    {
        return Core::getHeader();
    }

    /**
     * gets all headers as HTML
     * @return string
     */
    public function headerHTML()
    {
        return Core::getHeaderHTML();
    }

    /**
     * some math operations
     */

    /**
     * sums all given values
     *
     * @sum
     * @access public
     */
    public function sum()
    {
        $args = func_get_args();
        $value = 0;
        foreach ($args as $val) {
            $value += $val;
        }

        return $value;
    }

    /**
     * multiply
     *
     * @return null|int
     */
    public function multiply()
    {
        $value = null;
        $args = func_get_args();
        foreach ($args as $val) {
            if (!isset($value)) {
                $value = $val;
            } else {
                $value = $value * $val;
            }
        }

        return $value;
    }

    /**
     * date-method
     *
     * @param string $format
     * @param int $time
     * @return bool|mixed|string
     */
    public function date($format, $time = NOW)
    {
        return goma_date($format, $time);
    }

    /**
     * adds URL-param to URL
     * @param string $url
     * @param string $param
     * @param string $value
     * @return string
     */
    public function addParamToUrl($url, $param, $value)
    {
        return Controller::addParamToUrl($url, $param, $value);
    }

    /**
     * returns if matches url.
     * @param string $regexp
     * @param mixed|string $url
     * @return int
     */
    public function urlMatches($regexp, $url = URL)
    {
        $regexp = str_replace('/', '\\/', $regexp);

        return preg_match('/' . $regexp . '/Usi', $url);
    }

    /**
     * returns Core::$title
     */
    public function title()
    {
        return Core::$title;
    }


    /**
     * returns addcontent
     * @param bool $flush
     * @return string
     */
    public function addcontent($flush = false)
    {
        $c = addcontent::get();
        if ($flush) {
            addcontent::flush();
        }

        return $c;
    }

    /**
     * API for resizing images
     * @param string $file
     * @param int $width
     * @param int $height
     * @return string
     * @throws FileNotPermittedException
     */
    public function imageSetSize($file, $width, $height)
    {
        $url = "images/resampled/" . $width . "/" . $height . "/" . $file;
        if (!file_exists($url)) {
            FileSystem::requireDir(dirname($url));
            FileSystem::Write($url . ".permit", "");
        }

        return $url;
    }

    /**
     * API for resizing images by width
     * @param string $file
     * @param int $width
     * @return string
     * @throws FileNotPermittedException
     */
    public function imageSetWidth($file, $width)
    {
        $url = "images/resampled/" . $width . "/" . $file;

        if (!file_exists($url)) {
            FileSystem::requireDir(dirname($url));
            FileSystem::Write($url . ".permit", "");
        }

        return $url;
    }

    /**
     * API for resizing images by height
     * @param string $file
     * @param int $height
     * @return string
     * @throws FileNotPermittedException
     */
    public function imageSetHeight($file, $height)
    {
        $url = "images/resampled/x/" . $height . "/" . $file;

        if (!file_exists($url)) {
            FileSystem::requireDir(dirname($url));
            FileSystem::Write($url . ".permit", "");
        }

        return $url;
    }

    /**
     * on clone
     */
    public function __clone()
    {
        $this->dataobject = clone $this->dataobject;
        if ($this->callers)
            foreach ($this->callers as $key => $caller) {
                $this->callers[$key] = clone $caller;
            }
    }

    /**
     * checks if a file exists
     * @param string $filename
     * @return bool
     */
    protected static function file_exists($filename)
    {
        return Resources::file_exists($filename);
    }

    /**
     * gets current object
     */
    public function doObject()
    {
        return $this;
    }

    /**
     * checks if method can call
     * @param string $name
     * @return bool
     */
    public function __cancall($name)
    {
        if (parent::method_exists($this->classname, $name)) {
            return true;
        } else if (parent::method_exists($this->classname, "_" . $name)) {
            return true;
        } else {
            if (gObject::method_exists($this->dataobject, $name)) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * @param string $methodName
     * @param array $args
     * @return bool|mixed
     */
    public function __call($methodName, $args)
    {
        if (gObject::method_exists($this->classname, $methodName)) {
            if (method_exists($this->classname, $methodName))
                return call_user_func_array(array("gObject", $methodName), $args);
            else
                return call_user_func_array(array("gObject", "__call"), array($methodName, $args));
        } else if (gObject::method_exists($this->classname, "_" . $methodName)) {
            return call_user_func_array(array($this, "_" . $methodName), $args);
        } else if (isset($this->callers[strtolower($methodName)])) {
            return $this->callers[strtolower($methodName)];
        } else {
            if (gObject::method_exists($this->dataobject, $methodName)) {
                return call_user_func_array(array($this->dataobject, $methodName), $args);
            } else {
                return false;
            }
        }
    }

    /**
     * retina.
     * @param string $file
     * @return string
     */
    public function RetinaPath($file)
    {
        return RetinaPath($file);
    }
}
