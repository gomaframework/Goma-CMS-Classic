<?php defined("IN_GOMA") OR die();

/**
 * Base-Class for Request-Handling.
 *
 * @package		Goma\System\Core
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @version		1.0
 */
class Director extends gObject {

    /**
     * addon urls by modules or others
     */
    public static $rules = array();

    /**
     * Controllers used in this Request
     */
    public static $controller = array();

    /**
     * the current active controller
     *
     * @var RequestHandler|Controller
     */
    public static $requestController;

    /**
     * sorted rules.
     *
     * @var array
     */
    private static $sortedRules;

    /**
     * Init.
     */
    public static function Init() {

    }

    /**
     * adds some rules to controller
     *@param array $rules
     *@param int $priority
     */
    public static function addRules($rules, $priority = 50) {
        self::$sortedRules = null;

        if(isset(self::$rules[$priority])) {
            self::$rules[$priority] = array_merge(self::$rules[$priority], $rules);
        } else {
            self::$rules[$priority] = $rules;
        }
    }

    /**
     * gets all active rules sorted.
     */
    public static function getSortedRules() {
        if(!isset(self::$sortedRules)) {
            self::$sortedRules = self::$rules;
            krsort(self::$sortedRules);
        }

        return self::$sortedRules;
    }

    /**
     * serves the output given
     *
     * @param $output
     * @param $request
     */
    public static function serve($output, $request = null) {
        if(PROFILE)
            Profiler::unmark("render");

        if(PROFILE)
            Profiler::mark("serve");

        Core::callHook("serve", $output);

        if(isset(self::$requestController)) {
            if(self::$requestController->getRequest() == null) {
                self::$requestController->setRequest($request);
            }

            /** @var GomaResponse|GomaResponseBody|string $output */
            if(!is_a($output, GomaResponse::class) || $output->shouldServe()) {
                $output = self::setStringToResponse($output,
                    self::$requestController->serve(
                        self::getStringFromResponse($output),
                        self::getBodyObjectFromResponse($output)
                    )
                );
            }
        }
        if(PROFILE)
            Profiler::unmark("serve");

        Core::callHook("onBeforeServe", $output);

        if(!is_a($output, "GomaResponse")) {
            $output = new GomaResponse(HTTPResponse::gomaResponse()->getHeader(), $output);
            $output->setStatus(HTTPResponse::gomaResponse()->getStatus());

            $output->getBody()->setIncludeResourcesInBody(!Core::is_ajax());
        } else {
            $output->merge(HTTPResponse::gomaResponse());
        }

        $output->output();

        Core::callHook("onBeforeShutdown");
    }

    /**
     * @param string|GomaResponse|GomaResponseBody $response
     * @return GomaResponse|GomaResponseBody
     */
    public static function getBodyObjectFromResponse($response) {
        if(is_a($response, "GomaResponse")) {
            /** @var GomaResponse $response */
            return $response->getBody();
        }

        if(is_a($response, "GomaResponseBody")) {
            /** @var GomaResponseBody $response */
            return $response;
        }

        return new GomaResponseBody($response);
    }

    /**
     * @param string|GomaResponse|GomaResponseBody $response
     * @return bool
     */
    public static function isResponseFullPage($response) {
        if(is_a($response, "GomaResponse")) {
            /** @var GomaResponse $response */
            return $response->isFullPage();
        }

        if(is_a($response, "GomaResponseBody")) {
            /** @var GomaResponseBody $response */
            return $response->isFullPage();
        }

        return true;
    }

    /**
     * @param string|GomaResponse|GomaResponseBody $response
     * @return string|mixed
     */
    public static function getStringFromResponse($response) {
        if(is_a($response, "GomaResponse")) {
            /** @var GomaResponse $response */
            return $response->getResponseBodyString();
        }

        if(is_a($response, "GomaResponseBody")) {
            /** @var GomaResponseBody $response */
            return $response->getBody();
        }

        return $response;
    }

    /**
     * @param string|GomaResponse|GomaResponseBody $response
     * @param string $content
     * @return string|GomaResponse|GomaResponseBody|mixed
     */
    public static function setStringToResponse($response, $content) {
        if(is_a($response, "GomaResponse")) {
            /** @var GomaResponse $response */
            $response->setBodyString($content);
            return $response;
        }

        if(is_a($response, "GomaResponseBody")) {
            /** @var GomaResponseBody $response */
            $response->setBody($content);
            return $response;
        }

        return $content;
    }

        /**
     * renders the page
     * @param string $url
     * @return string|void
     * @throws Exception
     */
    public static function direct($url) {

        if(PROFILE)
            Profiler::mark("render");

        // we will merge $_POST with $_FILES, but before we validate $_FILES
        foreach($_FILES as $name => $arr) {
            if(is_array($arr["tmp_name"])) {
                foreach($arr["tmp_name"] as $tmp_file) {
                    if($tmp_file && !is_uploaded_file($tmp_file)) {
                        throw new LogicException($tmp_file . " is no valid upload! Please try again uploading the file.");
                    }
                }
            } else {
                if($arr["tmp_name"] && !is_uploaded_file($arr["tmp_name"])) {
                    throw new LogicException($arr["tmp_name"] . " is no valid upload! Please try again uploading the file.");
                }
            }
        }

        $request = new Request(
            (isset($_SERVER['X-HTTP-Method-Override'])) ? $_SERVER['X-HTTP-Method-Override'] : $_SERVER['REQUEST_METHOD'],
            $url,
            $_GET,
            array_merge((array)$_POST, (array)$_FILES),
            getallheaders(),
            $_SERVER["SERVER_NAME"],
            $_SERVER["SERVER_PORT"],
            (isset($_SERVER["HTTPS"])) && $_SERVER["HTTPS"] != "off",
            isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : ""
        );

        $ruleMatcher = RuleMatcher::initWithRulesAndRequest(self::getSortedRules(), $request);
        while($nextController = $ruleMatcher->matchNext()) {
            if(!ClassInfo::exists($nextController)) {
                ClassInfo::delete();
                throw new LogicException("Controller $nextController does not exist.");
            }

            $inst = new $nextController;
            self::$requestController = $inst;
            self::$controller = array($inst);

            /** @var RequestHandler $inst */
            $data = $inst->handleRequest($ruleMatcher->getCurrentRequest());
            if($data !== false && $data !== null) {
                self::serve($data, $request);
                return;
            }
        }

        return "404";
    }
}
