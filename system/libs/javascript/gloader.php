<?php defined("IN_GOMA") OR die();

/**
 * @package goma framework
 * @link http://goma-cms.org
 * @license: LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 * @author Goma-Team
 * last modified: 12.05.2013
 * $Version 1.1.3
 */
class gLoader extends RequestHandler
{
    const VERSION = "1.1.3";
    /**
     * url-handlers
     */
    public $url_handlers = array(
        "v2/\$name" => "deliver",
        "\$name" => "deliver"
    );

    /**
     * allowed actions
     */
    public $allowed_actions = array(
        "deliver"
    );

    /**
     * loadable resources
     */
    public static $resources = array();

    /**
     * preloaded resources
     */
    public static $preloaded = array();

    /**
     * adds a loadable resource
     * @param string - name
     * @param string - filename
     * @param array - required other resources
     */
    public static function addLoadAble($name, $file, $required = array())
    {

        self::$resources[$name] = array(
            "file" => $file,
            "required" => $required
        );
    }

    /**
     * this is the php-function for the js-function gloader.load, it loads it for pageload
     */
    public static function load($name)
    {
        if (!isset(self::$preloaded[$name])) {
            if (isset(self::$resources[$name])) {
                foreach (self::$resources[$name]["required"] as $_name) {
                    self::load($_name);
                }
                Resources::add(self::$resources[$name]["file"], "js", "preload");
            }
            self::$preloaded[$name] = true;
        }
    }

    /**
     * delivers a specified resource
     */
    public function deliver()
    {
        $name = $this->getParam("name");
        if (substr($name, -3) == ".js") {
            $name = substr($name, 0, -3);
        }

        $response = new GomaResponse(array(
            "content-type", "text/javascript"
        ), GomaResponseBody::create(null)->setIsFullPage(true));

        if (isset(self::$resources[$name])) {
            $response->setHeader('Cache-Control', 'public, max-age=5511045');
            $response->setHeader("pragma", "Public");

            $data = self::$resources[$name];
            if (file_exists($data["file"])) {
                $mtime = 0;
                $this->checkMTime($name, $data, $mtime);

                $etag = strtolower(md5("gload_" . $name . "_" . md5(var_export($data, true)) . "_" . $mtime));
                $response->setHeader("Etag", '"' . $etag . '"');

                // 304 by HTTP_IF_MODIFIED_SINCE
                if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
                    if (strtolower(gmdate('D, d M Y H:i:s', $mtime) . ' GMT') == strtolower($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
                        $response->setStatus(304);

                        return $response;
                    }
                }
                // 304 by ETAG
                if (isset($_SERVER["HTTP_IF_NONE_MATCH"])) {
                    if ($_SERVER["HTTP_IF_NONE_MATCH"] == '"' . $etag . '"') {
                        $response->setStatus(304);

                        return $response;
                    }
                }

                $temp = ROOT . CACHE_DIRECTORY . '/gloader.' . $name . self::VERSION . "." . md5(var_export($data, true)) . ".js";
                $expiresAdd = defined("DEV_MODE") ? 3 * 60 * 60 : 48 * 60 * 60;
                $response->setCacheHeader(NOW + $expiresAdd, $mtime, true);
                if (!file_exists($temp) || filemtime($temp) < $mtime) {
                    FileSystem::write($temp, $this->buildFile($name, $data));
                }

                if(PROFILE)
                    Profiler::end();
                $response->sendHeader();
                readfile($temp);
                exit;
            }
        }

        return $response;
    }

    /**
     * this is building the file and modifiing mtime
     *
     * @param $name
     * @param $data
     * @return string
     * @throws DataNotFoundException
     */
    protected function buildFile($name, $data)
    {
        $js = "";
        if ($data["required"]) {
            foreach ($data["required"] as $_name) {
                if (isset(self::$resources[$_name])) {
                    if (file_exists(self::$resources[$_name]["file"])) {
                        $js .= $this->buildFile($_name, self::$resources[$name]);
                    } else {
                        throw new DataNotFoundException();
                    }
                }
            }
        }

        $js .= '/* file ' . $data["file"] . " */
goma.ui.setLoaded('" . $name . "'); goma.ui.registerResource('js', '" . $data["file"] . "?" . filemtime($data["file"]) . "');\n\n";

        $js .= jsmin::minify(file_get_contents($data["file"]));

        return $js;
    }

    /**
     * this is for checking cache active
     *
     * @param string $name
     * @param array $data
     * @param int $mtime
     * @return bool
     */
    protected function checkMTime($name, $data, &$mtime)
    {
        if ($data["required"]) {
            foreach ($data["required"] as $_name) {
                if (isset(self::$resources[$_name])) {
                    if (file_exists(self::$resources[$_name]["file"])) {
                        $this->checkMTime($_name, self::$resources[$name], $mtime);
                    } else {
                        return false;
                    }
                }
            }
        }

        if ($mtime < filemtime($data["file"])) {
            $mtime = filemtime($data["file"]);
        }

        return true;
    }
}

StaticsManager::addSaveVar(gloader::class, "resources");
