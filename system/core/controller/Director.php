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
     * @param array $rules
     * @param int $priority
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
     * @param Request $request
     * @param bool $serve
     * @return GomaResponse|null if serve is false GomaResponse
     */
    public static function serve($output, $request, $serve = true) {
        if(PROFILE)
            Profiler::unmark("render");

        if(PROFILE)
            Profiler::mark("serve");

        Core::callHook("serve", $output);

        if($request && $request->getRequestController()) {
            /** @var GomaResponse|GomaResponseBody|string $output */
            if(!is_a($output, GomaResponse::class) || $output->shouldServe()) {
                $output = self::setStringToResponse($output,
                    $request->getRequestController()->serve(
                        self::getStringFromResponse($output),
                        self::getBodyObjectFromResponse($output)
                    )
                );
                self::setResponseFullPage($output, true);
            }
        }
        if(PROFILE)
            Profiler::unmark("serve");

        Core::callHook("onBeforeServe", $output, $request);

        if(!is_a($output, "GomaResponse")) {
            $output = new GomaResponse(HTTPResponse::gomaResponse()->getHeader(), $output);
            $output->setStatus(HTTPResponse::gomaResponse()->getStatus());

            $output->setBody(
                $output->getBody()->setIncludeResourcesInBody(!$request->is_ajax())
            );
        } else {
            $output->merge(HTTPResponse::gomaResponse());
        }

        if(!$serve) {
            return $output;
        }

        $output->output();

        Core::callHook("onBeforeShutdown");
    }

    /**
     * @param string|GomaResponse|GomaResponseBody $response
     * @param bool $boolean
     * @return $this|GomaResponse|GomaResponseBody
     */
    public static function setResponseFullPage($response, $boolean) {
        if(is_a($response, "GomaResponse")) {
            /** @var GomaResponse $response */
            $response->setBody(
                $response->getBody()->setIsFullPage($boolean)
            );
            return $response;
        }

        if(is_a($response, "GomaResponseBody")) {
            /** @var GomaResponseBody $response */
            return $response->setIsFullPage($boolean);
        }

        if(is_string($response) && $boolean) {
            return GomaResponseBody::create($response)->setIsFullPage($boolean);
        }

        return $response;
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

        if($response === null) {
            return true;
        }

        return false;
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
     * @param string $url
     * @param array $server
     * @param array $get
     * @param array $post
     * @param array $files
     * @param array $headers
     * @return Request
     */
    public static function createRequestWithData($url, $server, $get, $post, $files, $headers) {
        if(!isset($server["SERVER_NAME"], $server["SERVER_PORT"], $server["REQUEST_METHOD"])) {
            throw new InvalidArgumentException("Request Method, Server name and port are required.");
        }

        // we will merge $_POST with $_FILES, but before we validate $_FILES
        foreach($files as $name => $arr) {
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

        return new Request(
            (isset($server['X-HTTP-Method-Override'])) ? $server['X-HTTP-Method-Override'] : $server['REQUEST_METHOD'],
            $url,
            $get,
            array_merge((array)$post, (array)$files),
            $headers,
            isset($server["SERVER_NAME"]) ? $server["SERVER_NAME"] : null,
            isset($server["SERVER_PORT"]) ? $server["SERVER_PORT"] : null,
            (isset($server["HTTPS"])) && $_SERVER["HTTPS"] != "off",
            isset($server["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : ""
        );
    }

    /**
     * @param string $url
     * @return Request
     */
    public static function createRequestFromEnvironment($url) {
        $server = $_SERVER;
        $get = $_GET;
        $post = $_POST;
        $files = $_FILES;
        $headers = getallheaders();

        Core::callHook("getEnvironment", $server, $get, $post, $files, $headers);

        return self::createRequestWithData($url, $server, $get, $post, $files, $headers);
    }

    /**
     * renders the page
     * @param string $url
     * @param bool $serve
     * @return string|void
     * @throws DataNotFoundException
     */
    public static function direct($url, $serve = true) {

        if(PROFILE)
            Profiler::mark("render");

        if(!is_string($url)) {
            throw new InvalidArgumentException("\$url must be string for Director::direct. For Requests use Director::directRequest");
        }

        $request = self::createRequestFromEnvironment($url);

        return self::directRequest($request, $serve);
    }

    /**
     * @param Request $request
     * @param bool $serve
     * @return GomaResponse|null
     * @throws DataNotFoundException
     * @throws Exception
     */
    public static function directRequest($request, $serve = true) {
        $ruleMatcher = RuleMatcher::initWithRulesAndRequest(self::getSortedRules(), $request);
        while($nextController = $ruleMatcher->matchNext()) {
            if(!ClassInfo::exists($nextController)) {
                ClassInfo::delete();
                throw new LogicException("Controller $nextController does not exist.");
            }

            $inst = new $nextController;
            /** @var RequestHandler $inst */
            $data = $inst->handleRequest($request = $ruleMatcher->getCurrentRequest());

            if($data !== false && $data !== null) {
                return self::serve($data, $request, $serve);
            }
        }

        throw new DataNotFoundException();
    }
}
