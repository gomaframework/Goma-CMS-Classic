<?php
/**
 * @package        Goma\System\Core
 *
 * @author        Goma-Team
 * @license        GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */

defined('IN_GOMA') OR die();

/**
 * This class is the basic class for each controller of Goma. It provides basic methods to handle requests and parsing URLs automatically and calling the correct Action.
 *
 * @package     Goma\System\Core
 * @version     2.3.1
 */
class RequestHandler extends gObject
{
    /**
     * url-handlers
     *
     * @var array
     */
    static $url_handlers = array();

    /**
     * this method defines rules to disallow action for specific groups.
     * By default all explicit mentioned actions in url_handlers are allowed.
     *
     * Permissions are defined like:
     * "actionName" => "PERMISSION_NAME",
     * "actionName" => "->methodWhichShouldReturnTrueIfAllowed"
     *
     * @var    array
     */
    static $allowed_actions = array();

    /**
     * the url base-path of this controller
     *
     * @var string
     */
    public $namespace;
    /**
     * original namespace, so always from first controller
     *
     * @var string
     */
    public $originalNamespace;
    /**
     * defines whether shift on success or not
     *
     * @var bool
     */
    protected $shiftOnSuccess = true;
    /**
     * defines if this is a sub-controller.
     * by default yes, because then handleRequest was not called.
     *
     * @var bool
     */
    protected $subController = true;

    /**
     * the current request
     *
     * @var     Request
     */
    protected $request;
    /**
     * @var string
     */
    protected $currentActionHandled;
    /**
     * current depth of request-handlers
     */
    private $requestHandlerKey;

    /**
     * cache for url-handler generation.
     * @var array
     */
    private static $urlHandlerCache = array();

    /**
     * cache for action-generation.
     *
     * @var array
     */
    private static $actionCache = array();

    /**
     * @return array
     */
    public static function getExtendedAllowedActions() {
        if(PROFILE) Profiler::mark("RequestHandler::getExtendedAllowedActions");

        if(!isset(self::$actionCache[static::class])) {
            $actions = (array)StaticsManager::getNotInheritedStatic(static::class, "allowed_actions");

            foreach (gObject::getExtensionsForClass(static::class, false) as $extensionsForClass) {
                $actions = array_merge(
                    $actions,
                    (array)StaticsManager::getNotInheritedStatic($extensionsForClass, "allowed_actions")
                );
            }

            self::$actionCache[static::class] = ArrayLib::map_key("strtolower", array_map(function($value){
                return is_string($value) ? strtolower($value) : $value;
            }, $actions));
        }

        if(PROFILE) Profiler::unmark("RequestHandler::getExtendedAllowedActions");

        return self::$actionCache[static::class];
    }

    /**
     * @return array
     */
    public static function getExtendedUrlHandlers() {
        if(PROFILE) Profiler::mark("RequestHandler::getExtendedUrlHandlers");

        if(!isset(self::$urlHandlerCache[static::class])) {
            $actions = (array)StaticsManager::getNotInheritedStatic(static::class, "url_handlers");

            foreach (gObject::getExtensionsForClass(static::class, false) as $extensionsForClass) {
                $actions = array_merge(
                    $actions,
                    (array)StaticsManager::getNotInheritedStatic($extensionsForClass, "url_handlers")
                );
            }

            self::$urlHandlerCache[static::class] = array_map("strtolower", $actions);
        }

        if(PROFILE) Profiler::unmark("RequestHandler::getExtendedUrlHandlers");

        return self::$urlHandlerCache[static::class];
    }

    /**
     * Inits the RequestHandler with a request-object.
     *
     * It generates the current URL-namespace ($this->namespace) and registers the Controller as an activeController in Core as Core::$activeController
     *
     * @param   Request $request The Request Object
     * @return $this
     */
    public function Init($request = null)
    {
        if (!isset($request) && !isset($this->request)) {
            throw new InvalidArgumentException("RequestHandler".$this->classname." has no request-instance.");
        }

        $this->request = isset($request) ? $request : $this->request;
        $this->originalNamespace = $this->namespace;
        $this->namespace = $this->request->getShiftedPart();

        if (!isset($this->originalNamespace)) {
            $this->originalNamespace = $this->namespace;
        }

        $this->requestHandlerKey = count($this->request->getController());
        $this->request->addController($this, !$this->subController);

        return $this;
    }

    /**
     * handles requests
     * @param $request
     * @param bool $subController defines if controller should be pushed to history and used for Serve.
     *
     * @return false|null|string
     * @throws Exception
     */
    public function handleRequest($request, $subController = false)
    {
        if ($this->classname == "") {
            throw new LogicException(
                'Class '.get_class(
                    $this
                ).' has no class_name. Please make sure you call <code>parent::__construct();</code> before calling handleRequest.'
            );
        }

        try {
            $this->subController = $subController;
            $this->Init($request);

            // check for extensions
            $content = null;

            $this->callExtending("onBeforeHandleRequest", $request, $subController, $content);

            if ($content !== null) {
                return $content;
            }

            $preservedRequest = clone $this->request;

            $class = $this->classname;
            while (ClassManifest::isOfType($class, self::class)) {
                $method = new ReflectionMethod($class, "getExtendedUrlHandlers");
                $handlers = $method->invoke(null);
                foreach ($handlers as $pattern => $action) {
                    $this->request->setState($preservedRequest);
                    $data = $this->matchRuleWithResult($pattern, $action, $class, $request);
                    if ($data !== null && $data !== false) {
                        return $data;
                    }
                }

                $class = get_parent_class($class);
            }

            $this->request->setState($preservedRequest);

            return $this->handleAction("index");
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * matches a rule and returns result of action covered by the rule.
     *
     * @param string $rule
     * @param string $action
     * @param string $classWithRule
     * @param Request $request optional
     * @return string
     */
    public function matchRuleWithResult($rule, $action, $classWithRule, $request = null)
    {
        if (!isset($request)) {
            $request = $this->request;
        }

        if ($argument = $request->match($rule, $this->shiftOnSuccess, $this->classname)) {
            $action = str_replace('-', '_', $action);

            if (!$this->hasAction($action, $classWithRule)) {
                return null;
            }

            return $this->handleAction($action);
        }

        return null;
    }

    /**
     * in the end this function is called to do last modifications
     *
     * @param   string $content
     * @param GomaResponseBody $body
     * @internal
     * @return  string
     */
    public function serve($content, $body)
    {
        return $content;
    }

    /**
     * checks if this class has a given action.
     * it also checks for permissions.
     * It does not check if rule for an action exists. It means that any method which is *not* disallowed is
     * considered as action.
     *
     * @param   string $action
     * @param string $classWithActionDefined
     * @return bool
     */
    public function hasAction($action, $classWithActionDefined = null)
    {
        if(!isset($classWithActionDefined)) {
            $classWithActionDefined = $this->classname;
        }

        $hasAction = true;

        if (!gObject::method_exists($this, $action) || !$this->checkPermission($action, $classWithActionDefined)) {
            $hasAction = false;
        }

        $this->extendHasAction($action, $hasAction);
        $this->callExtending("extendHasAction", $action, $hasAction, $classWithActionDefined);

        return $hasAction;
    }

    /**
     * performs action handling which means extending action handling and calling method if not handled yet.
     *
     * @name    handleAction
     * @access  public
     * @return  mixed|null|false
     */
    public function handleAction($action)
    {
        $this->currentActionHandled = $action;
        $handleWithMethod = true;
        $content = null;

        $this->onBeforeHandleAction($action, $content, $handleWithMethod);
        $this->callExtending("onBeforeHandleAction", $action, $content, $handleWithMethod);

        if ($handleWithMethod && gObject::method_exists($this, $action)) {
            $content = call_user_func_array(array($this, $action), array());
        }

        $this->extendHandleAction($action, $content);
        $this->callExtending("extendHandleAction", $action, $content);

        return $content;
    }

    /**
     * on before handle action
     *
     * @param string $action
     * @param string $content
     * @param bool $handleWithMethod
     */
    public function onBeforeHandleAction($action, &$content, &$handleWithMethod)
    {

    }

    /**
     * @param string $action
     * @param string $content
     * @return void
     */
    public function extendHandleAction($action, &$content)
    {

    }

    /**
     * extends hasAction
     * @param string $action
     * @param boolean $hasAction
     */
    public function extendHasAction($action, &$hasAction)
    {

    }

    /**
     * default Action
     *
     * @return string
     */
    public function index()
    {
        return "";
    }

    /**
     * simple way for $this->request->getParam which also supports get and post.
     *
     * @param string $param
     * @param bool|string filter , options: true|false|get|post
     * @return mixed|null
     */
    public function getParam($param, $useall = true)
    {
        if (isset($this->request) && is_a($this->request, "request")) {
            return $this->request->getParam($param, $useall);
        }

        return null;
    }

    /**
     * handles exceptions.
     * @param Exception $e
     * @return string
     * @throws Exception
     */
    public function handleException($e)
    {
        if ($this->isSubController()) {
            throw $e;
        }

        $content = null;
        $this->callExtending("handleException", $e, $content);

        if (isset($content)) {
            return $content;
        }

        if (gObject::method_exists($e, "http_status")) {
            $status = $e->http_status();
        } else {
            $status = 500;
        }

        log_exception($e);

        if ($this->request->canReplyJavaScript() || $this->request->canReplyJSON()) {
            return GomaResponse::create(
                null,
                new JSONResponseBody(
                    array(
                        "status"     => $e->getCode(),
                        "error"      => $e->getMessage(),
                        "errorClass" => get_class($e),
                    )
                )
            )->setStatus($status);
        }

        return GomaResponse::create(null, $e->getCode().": ".get_class($e)."\n".$e->getMessage())->setStatus($status);
    }

    /**
     * gets parent controller of this
     */
    public function parentController()
    {
        return $this->request && $this->requestHandlerKey > 0 ?
            $this->request->getController()[$this->requestHandlerKey - 1] : null;
    }

    /**
     * returns if this controller is the next controller to the root of this type.
     * @param string $type
     * @param bool $ignoreSubController
     * @return bool
     */
    public function controllerIsNextToRootOfType($type, $ignoreSubController = false)
    {
        if (!is_a($this, $type)) {
            throw new InvalidArgumentException("You can only compare with types you are.");
        }

        if ($this->request) {
            foreach ($this->request->getController() as $controller) {
                if (is_a($controller, $type) && (!$ignoreSubController || !$controller->isSubController())) {
                    return spl_object_hash($controller) == spl_object_hash($this);
                }
            }

            throw new LogicException("Object not found in request-tree.");
        }

        // should be true if no request is set.
        return true;
    }

    /**
     * returns if this controller is the next controller to the root of this type.
     * @param string $type
     * @param bool $ignoreSubController
     * @return bool
     */
    public function controllerIsMostSpecialOfType($type, $ignoreSubController = false)
    {
        if (!is_a($this, $type)) {
            throw new InvalidArgumentException("You can only compare with types you are.");
        }

        if ($this->request) {
            $controllers = array_reverse($this->request->getController());
            foreach ($controllers as $controller) {
                if (is_a($controller, $type) && (!$ignoreSubController || !$controller->isSubController())) {
                    return spl_object_hash($controller) == spl_object_hash($this);
                }
            }

            throw new LogicException("Object not found in request-tree.");
        }

        // should be true if no request is set.
        return true;
    }

    /**
     * the root view controller is
     * - the most special
     * - non-subcontroller
     * - checks if response is not full page - Can be disabled by providing null as response
     *
     * @param null|GomaResponse|GomaResponseBody|string $response
     * @param string $type
     * @return bool
     */
    public function isManagingController($response = null, $type = null)
    {
        $type = isset($type) ? $type : self::class;

        return !$this->isSubController() && !\Director::isResponseFullPage($response) &&
            $this->controllerIsMostSpecialOfType($type, true);
    }

    /**
     * @return Request|null
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * sets the request.
     *
     * @param Request $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @return boolean
     */
    public function isSubController()
    {
        return $this->subController;
    }

    /**
     * @param gObject|string $sender
     * @return string
     * @deprecated
     */
    public function getRedirect($sender)
    {
        if (isset($this->request)) {
            if (isset($this->request->get_params["redirect"]) &&
                isURLFromServer($this->request->get_params["redirect"], $this->request->getServerName())
            ) {
                return convert::raw2text($this->request->get_params["redirect"]);
            } else if (isset($this->request->post_params["redirect"]) &&
                isURLFromServer($this->request->post_params["redirect"], $this->request->getServerName())
            ) {
                return convert::raw2text($this->request->post_params["redirect"]);
            }
        }

        if ($this->currentActionHandled != "index" ||
            (is_string($sender) && strtolower($sender) == "tothis") ||
            (is_object($sender) && $sender != $this)) {
            return ROOT_PATH.$this->namespace;
        }

        if ($this->parentController() && $this->parentController()->namespace) {
            return ROOT_PATH.$this->parentController()->namespace;
        }

        if ($this->namespace) {
            return substr($this->namespace, 0, strrpos($this->namespace, "/"));
        }

        return BASE_URI;
    }

    /**
     * @return string
     */
    public function getRedirectToSelf()
    {
        return $this->request->url.URLEND."?".$this->request->queryString();
    }

    /**
     * checks the permissions
     *
     * @param string $action
     * @param string $classWithActionDefined
     * @return bool
     */
    protected function checkPermission($action, $classWithActionDefined)
    {
        if (PROFILE) {
            Profiler::mark("RequestHandler::checkPermission");
        }

        $action = strtolower($action);
        $class = $this->classname;

        while (
            // no class which is more common than the definition of the rule can decide if action is allowed.
            ClassManifest::isOfType($class, $classWithActionDefined) &&
            gObject::method_exists($class, "checkPermissionsOnClass")
        ) {
            // check class
            $result = $this->checkPermissionsOnClass($class, $action);

            // if we have an result which is a boolean.
            if (is_bool($result)) {
                if (PROFILE) {
                    Profiler::unmark("RequestHandler::checkPermission");
                }

                return $result;
            }

            // check for parent class
            $class = get_parent_class($class);
        }

        if (PROFILE) {
            Profiler::unmark("RequestHandler::checkPermission");
        }

        // by default if no result has been gotten, it returns true since it's implicitly allowed due to url-handler.
        return true;
    }

    /**
     * checks permissions on provided class.
     *
     * @param string $className
     * @param string $action
     * @return null when no definition was found or a boolean when definition was found.
     */
    protected function checkPermissionsOnClass($className, $action)
    {
        $actionLower = strtolower($action);
        $allowedActions = (array) call_user_func_array(array($className, "getExtendedAllowedActions"), array());

        if(in_array($actionLower, array_values($allowedActions), true)) {
            return true;
        } else if (isset($allowedActions[$actionLower])) {
            $data = $allowedActions[$actionLower];

            // explicit allow
            if (is_bool($data)) {
                return $data;
            } else if (substr($data, 0, 2) == "->") {
                $func = substr($data, 2);
                if (gObject::method_exists($this, $func)) {
                    return $this->$func();
                } else {
                    return false;
                }
            } else if ($data == "admins") {
                return (member::$groupType == 2);
            } else if ($data == "users") {
                return (member::$groupType == 1);
            } else {
                return Permission::check($data);
            }
        }

        return null;
    }
}


class RequestException extends Exception
{
    /**
     * constructor.
     */
    public function __construct($message = "", $code = 8, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
