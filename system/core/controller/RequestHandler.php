<?php
/**
 * @package		Goma\System\Core
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */

defined('IN_GOMA') OR die();

/**
 * This class is the basic class for each controller of Goma. It provides basic methods to handle requests and parsing URLs automatically and calling the correct Action.
 *
 * @package     Goma\System\Core
 * @version     2.3.1
 */
class RequestHandler extends gObject {

	/**
	 * url-handlers
	 *
	 * @var array
	 */
	public $url_handlers = array('$Action' => '$Action');

	/**
	 * requests, key is name of the request and value the function for it
	 *
	 * @var 	array
	 */
	public $allowed_actions = array("index");

	/**
	 * the url base-path of this controller
	 *
	 * @var string
	 */
	public $namespace;

	/**
	 * defines whether shift on success or not
	 *
	 * @var bool
	 */
	protected $shiftOnSuccess = true;

	/**
	 * original namespace, so always from first controller
	 *
	 * @var string
	 */
	public $originalNamespace;

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
	 * current depth of request-handlers
	 */
	private $requestHandlerKey;

    /**
     * @var string
     */
    protected $currentActionHandled;

	/**
	 * sets vars
	 *
	 * @name 	__construct
	 * @access 	public
	 */
	public function __construct() {
		parent::__construct();

		/* --- */

		if (PROFILE) Profiler::mark("RequestHandler::__construct");

		$this->allowed_actions = ArrayLib::map_key("strtolower", array_map("strtolower", $this -> allowed_actions), false);
		$this->url_handlers = array_map("strtolower", $this->url_handlers);

		if (isset(ClassInfo::$class_info[$this -> classname]["allowed_actions"]))
			$this->allowed_actions = array_merge(ClassInfo::$class_info[$this->classname]["allowed_actions"], $this->allowed_actions);

		if (isset(ClassInfo::$class_info[$this -> classname]["url_handlers"]))
			$this->url_handlers = array_merge(ClassInfo::$class_info[$this->classname]["url_handlers"], $this->url_handlers);

		if (PROFILE) Profiler::unmark('RequestHandler::__construct');
	}

	/**
	 * Inits the RequestHandler with a request-object.
	 *
	 * It generates the current URL-namespace ($this->namespace) and registers the Controller as an activeController in Core as Core::$activeController
	 *
	 * @param   Request $request The Request Object
	 * @return $this
	 */
	public function Init($request = null) {
		if (!isset($request) && !isset($this->request)) {
			throw new InvalidArgumentException("RequestHandler" . $this->classname . " has no request-instance.");
		}

		$this->request = isset($request) ? $request : $this->request;
		$this->originalNamespace = $this->namespace;
		$this->namespace = $this->request->getShiftedPart();

		if(!isset($this->originalNamespace)) $this->originalNamespace = $this->namespace;

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
	public function handleRequest($request, $subController = false) {
		if ($this -> classname == "") {
			throw new LogicException('Class ' . get_class($this) . ' has no class_name. Please make sure you call <code>parent::__construct();</code> ');
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
			while ($class && !ClassInfo::isAbstract($class)) {
                $handlers = gObject::instance($class)->url_handlers;
				foreach ($handlers as $pattern => $action) {
					$this->request->setState($preservedRequest);
					$data = $this->matchRuleWithResult($pattern, $action, $request);
					if ($data !== null && $data !== false) {
						return $data;
					}
				}

				$class = get_parent_class($class);
			}

			$this->request->setState($preservedRequest);
            return $this->handleAction("index");
		} catch(Exception $e) {
			if($subController) {
				throw $e;
			}

			return $this->handleException($e);
		}
	}

	/**
	 * matches a rule and returns result of action covered by the rule.
	 *
	 * @param string $rule
	 * @param string $action
	 * @param Request $request optional
	 * @return string
	 */
	public function matchRuleWithResult($rule, $action, $request = null) {
		if(!isset($request)) {
			$request = $this->request;
		}

		if ($argument = $request -> match($rule, $this -> shiftOnSuccess, $this -> classname)) {
			if ($action{0} == "$") {
				$action = substr($action, 1);
				if ($this -> getParam($action, false)) {
					$action = $this -> getParam($action, false);
				} else {
					return null;
				}
			}

			$action = str_replace('-', '_', $action);

			if (!$this -> hasAction($action)) {
				return null;
			}

			return $this -> handleAction($action);
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
	public function serve($content, $body) {
		return $content;
	}

	/**
	 * checks if this class has a given action.
	 * it also checks for permissions.
	 *
	 * @param   string $action
	 * @return  bool
	 */
	public function hasAction($action) {
		$hasAction = true;
		if (!gObject::method_exists($this, $action) || !$this -> checkPermission($action)) {
			$hasAction = false;
		}

		$this -> extendHasAction($action, $hasAction);
		$this -> callExtending("extendHasAction", $action, $hasAction);

		return $hasAction;
	}

	/**
	 * handles the action.
	 *
	 * @name    handleAction
	 * @access  public
	 * @return  mixed|null|false
	 */
	public function handleAction($action) {
        $this->currentActionHandled = $action;
		$handleWithMethod = true;
		$content = null;

		$this -> onBeforeHandleAction($action, $content, $handleWithMethod);
		$this -> callExtending("onBeforeHandleAction", $action, $content, $handleWithMethod);

		if ($handleWithMethod && gObject::method_exists($this, $action))
			$content = call_user_func_array(array($this, $action), array());

		$this -> extendHandleAction($action, $content);
		$this -> callExtending("extendHandleAction", $action, $content);

		return $content;
	}

	/**
	 * on before handle action
	 *
	 *@param string $action
	 *@param string $content
	 *@param bool $handleWithMethod
	 */
	public function onBeforeHandleAction($action, &$content, &$handleWithMethod) {

	}

	/**
	 *@param string $action
	 *@param string $content
	 * @return void
	 */
	public function extendHandleAction($action, &$content) {

	}

	/**
	 * extends hasAction
	 * @param string $action
	 * @param boolean $hasAction
	 */
	public function extendHasAction($action, &$hasAction) {

	}

	/**
	 * checks the permissions
	 *
	 * @param string - permission
	 * @return bool
	 */
	protected function checkPermission($action) {
		if (PROFILE)
			Profiler::mark("RequestHandler::checkPermission");

		$class = $this;

		while ($class != null && gObject::method_exists($class, "checkPermissionsOnClass")) {
			// check class
			$result = $class->checkPermissionsOnClass($action);

			// if we have an result which is a boolean.
			if (is_bool($result)) {
				if (PROFILE)
					Profiler::unmark("RequestHandler::checkPermission");

				return $result;
			}

			// check for parent class
			$class = !ClassInfo::isAbstract(get_parent_class($class)) ? gObject::instance(get_parent_class($class)) : null;
		}

		if (PROFILE)
			Profiler::unmark("RequestHandler::checkPermission");
		return false;
	}

	/**
	 * checks permissions on this class.
	 *
	 * @return 	null when no definition was found or a boolean when definition was found.
	 */
	protected function checkPermissionsOnClass($action) {
		$actionLower = strtolower($action);

		if (in_array($actionLower, $this->allowed_actions)) {
			return true;
		} else if (isset($this->allowed_actions[$actionLower])) {
			$data = $this->allowed_actions[$actionLower];

			// advanced options for Action.
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

	/**
	 * default Action
	 *
	 * @return string
	 */
	public function index() {
		return "";
	}

	/**
	 * simple way for $this->request->getParam which also supports get and post.
	 *
	 * @param string $param
	 * @param bool|string filter, options: true|false|get|post
	 * @return mixed|null
	 */
	public function getParam($param, $useall = true) {
		if (isset($this -> request) && is_a($this -> request, "request")) {
			return $this -> request -> getParam($param, $useall);
		}

		return null;
	}

	/**
	 * handles exceptions.
	 * @param Exception $e
	 * @return string
	 * @throws Exception
	 */
	public function handleException($e) {
		$content = null;
		$this->callExtending("handleException", $e, $content);

		if(isset($content)) {
			return $content;
		}

		if(is_a($e, "LogicException")) {
			throw $e;
		} else {
			log_exception($e);
		}

		if($this->request->canReplyJavaScript()) {
			return new JSONResponseBody(array(
				"status" => $e->getCode(),
				"error" => $e->getMessage(),
				"errorClass" => get_class($e)
			));
		}

		return $e->getCode() . ": " . get_class($e) . "\n" . $e->getMessage();
	}

	/**
	 * gets parent controller of this
	 */
	public function parentController() {
		return $this->request && $this->requestHandlerKey > 0 ?
            $this->request->getController()[$this->requestHandlerKey - 1] : null;
	}

	/**
	 * returns if this controller is the next controller to the root of this type.
	 * @param string $type
	 * @param bool $ignoreSubController
	 * @return bool
	 */
	public function controllerIsNextToRootOfType($type, $ignoreSubController = false) {
		if(!is_a($this, $type)) {
			throw new InvalidArgumentException("You can only compare with types you are.");
		}

		if($this->request) {
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
	public function controllerIsMostSpecialOfType($type, $ignoreSubController = false) {
		if(!is_a($this, $type)) {
			throw new InvalidArgumentException("You can only compare with types you are.");
		}

		if($this->request) {
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
	public function isManagingController($response = null, $type = null) {
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
	public function setRequest($request) {
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
	 */
	public function getRedirect($sender)
	{
        if(isset($this->request)) {
            if(isset($this->request->get_params["redirect"]) &&
                isURLFromServer($this->request->get_params["redirect"], $this->request->getServerName())) {
                return convert::raw2text($this->request->get_params["redirect"]);
            } else if(isset($this->request->post_params["redirect"]) &&
                isURLFromServer($this->request->post_params["redirect"], $this->request->getServerName())) {
                return convert::raw2text($this->request->post_params["redirect"]);
            }
        }

        if($this->currentActionHandled != "index" || strtolower($sender) == "tothis" || (is_object($sender) && $sender != $this)) {
            return ROOT_PATH . $this->namespace;
        }

		if($this->parentController() && $this->parentController()->namespace) {
			return ROOT_PATH . $this->parentController()->namespace;
		}

        if($this->namespace) {
            return substr($this->namespace, 0, strrpos($this->namespace, "/"));
        }

		return BASE_URI;
	}
}


class RequestException extends Exception {
	/**
	 * constructor.
	 */
	public function __construct($m = "", $code = 8, Exception $previous = null) {
		parent::__construct($m, $code, $previous);
	}
}
