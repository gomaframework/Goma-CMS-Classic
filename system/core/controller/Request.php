<?php defined("IN_GOMA") OR die();

/**
 * Requests are represented by this class.
 *
 * @package		Goma\System\Core
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @version		2.0.3
 */
class Request extends gObject {
	/**
	 * url of the request
	 */
	public $url;

	/**
	 * all params
	 */
	public $all_params = array();

	/**
	 * current params
	 */
	public $params = array();

	/**
	 * get params
	 *
	 * @var array
	 */
	public $get_params = array();

	/**
	 * post params
	 *
	 * @var array
	 */
	public $post_params = array();

	/**
	 * headers of this request
	 *
	 * @var array
	 */
	protected $headers;

	/**
	 * the method of this request
	 * POST, GET, PUT, DELETE or HEAD
	 *
	 * @var String
	 */
	protected $request_method;

	/**
	 * url-parts
	 *
	 * @var array
	 */
	protected $url_parts = array();

	/**
	 * this var contains a sizeof params, which were parsed but not shifted
	 *
	 * @var int
	 */
	protected $unshiftedButParsedParams = 0;

	/**
	 * shifted path until now
	 *
	 * @var string
	 */
	protected $shiftedPart = "";

	/**
	 * server-name.
	 * @var string
	 */
	protected $serverName;

	/**
	 * server-port.
	 * @var int
	 */
	protected $serverPort;

	/**
	 * is-ssl.
	 * @var bool
	 */
	protected $isSSL = false;

	/**
	 * remote-addr.
	 */
	protected $remoteAddr;

	/**
	 * php-input.
	 */
	protected $phpInputFile;

	/**
	 * @param string $method
	 * @param string $url
	 * @param array $get_params
	 * @param array $post_params
	 * @param array $headers
	 * @param string $serverName
	 * @param int $serverPort
	 * @param bool $isSSL
	 * @param string $remoteAddr
	 */
	public function __construct(
		$method, $url, $get_params = array(), $post_params = array(), $headers = array(),
		$serverName = null, $serverPort = null, $isSSL = false, $remoteAddr = null
	) {
		parent::__construct();

		$this -> request_method = strtoupper(trim($method));
		$this -> url = $url;
		$this -> get_params = ArrayLib::map_key("strtolower", $get_params);
		$this -> post_params = ArrayLib::map_key("strtolower", $post_params);
		$this -> headers = ArrayLib::map_key("strtolower", $headers);
		$this -> url_parts = explode('/', $url);
		$this -> serverName = isset($serverName) ? $serverName : (isset($_SERVER["SERVER_NAME"]) ? $_SERVER["SERVER_NAME"] : null);
		$this -> serverPort = isset($serverPort) ? $serverPort : (isset($_SERVER["SERVER_PORT"]) ? $_SERVER["SERVER_PORT"] : null);
		$this -> remoteAddr = isset($remoteAddr) ? $remoteAddr :
			(isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : "");
		$this -> isSSL = $isSSL;
	}

	/**
	 * sets url-parts.
	 *
	 * @var array
	 */
	public function setUrlParts($url_parts) {
		$this->url_parts = $url_parts;
	}

	/**
	 * checks if POST
	 *
	 * @return bool
	 */
	public function isPOST() {
		return ($this -> request_method == "POST");
	}

	/**
	 * checks if GET
	 *
	 * @return bool
	 */
	public function isGET() {
		return ($this -> request_method == "GET");
	}

	/**
	 * checks if PUT
	 *
	 * @return bool
	 */
	public function isPUT() {
		return ($this -> request_method == "PUT");
	}

	/**
	 * checks if DELETE
	 *
	 * @return bool
	 */
	public function isDELETE() {
		return ($this -> request_method == "DELETE");
	}

	/**
	 * checks if HEAD
	 *
	 * @return bool
	 */
	public function isHEAD() {
		return ($this->request_method == "HEAD");
	}

	/**
	 * checks if OPTIONS
	 *
	 * @return bool
	 */
	public function isOPTIONS() {
		return ($this->request_method == "OPTIONS");
	}

	/**
	 * @return string
	 */
	public function getServerName() {
		return $this->serverName;
	}

	/**
	 * @return boolean
	 */
	public function isSSL()
	{
		return $this->isSSL;
	}

	/**
	 * @return int
	 */
	public function getServerPort()
	{
		return $this->serverPort;
	}

	/**
	 * @return mixed
	 */
	public function getRemoteAddr()
	{
		return $this->remoteAddr;
	}

	/**
	 * gets host with dot before, so we can use it for cookies.
	 *
	 * @return string
	 */
	public function getCookieHost() {
		if (!preg_match('/^[0-9]+/', $this->serverName) && $this->serverName != "localhost" && strpos($this->serverName, ".") !== false)
			return "." . $this->serverName;

		return $this->serverName;
	}

	/**
	 * matches the data with the url
	 *
	 * @param string $pattern
	 * @param bool $shiftOnSuccess
	 * @param string{null $class
	 * @return array
	 */
	public function match($pattern, $shiftOnSuccess = false, $class = null) {
		if (PROFILE)
			Profiler::mark("request::match");
		// class check
		if (preg_match("/^([a-zA-Z0-9_]+)\:([a-zA-Z0-9\$_\-\/\!\s]+)$/si", $pattern, $matches)) {
			$_class = $matches[1];
			$pattern = $matches[2];
			if (strtolower($_class) != $class)
				return false;
		}

		if (preg_match("/^(POST|PUT|DELETE|HEAD|GET)\s+([a-zA-Z0-9\$_\-\/\!]+)$/Usi", $pattern, $matches)) {
			$method = strtoupper($matches[1]);
			$pattern = $matches[2];
			if ($this->request_method != $method) {
				Profiler::unmark("request::match");
				return false;
			}
		}

		if (substr($pattern, -1) == "/") {
			$pattern = substr($pattern, 0, -1);
		}

		if (preg_match("/^\/\//", $pattern)) {
			$shiftOnSuccess = false;
			$pattern = substr($pattern, 2);
		}

		// // is the point to shift the url
		$shift = strpos($pattern, "//");
		if ($shift) {
			$shiftCount = substr_count(substr($pattern, 0, $shift), '/') + 1;
			$pattern = str_replace('//', '/', $pattern);
		} else {
			$shiftCount = count(explode("/", $pattern));
		}

		$patternParts = explode("/", $pattern);

		$params = array();
		for($i = 0; $i < count($patternParts); $i++) {
			$part = $patternParts[$i];

			// vars
			if (isset($part{0}) && $part{0} == '$') {
				if (substr($part, -1) == '!') {
					if (!isset($this -> url_parts[$i]) || $this -> url_parts[$i] == "") {
						if (PROFILE)
							Profiler::unmark("request::match");
						return false;
					}
					$name = substr($part, 1, -1);
				} else {
					if (!isset($this -> url_parts[$i])) {
						continue;
					}
					$name = substr($part, 1);
				}

				$data = $this -> url_parts[$i];
				if ($name == "controller" && !classinfo::exists($data)) {
					if (PROFILE)
						Profiler::unmark("request::match");
					return false;
				}

				$params[strtolower($name)] = $data;
			} else {
				// literal parts are important!
				if (!isset($this -> url_parts[$i]) || strtolower($this -> url_parts[$i]) != strtolower($part)) {
					if (PROFILE)
						Profiler::unmark("request::match");
					return false;
				}

				$params[$i] = $this -> url_parts[$i];
			}
		}

		if ($shiftOnSuccess) {
			$this -> shift($shiftCount);
			$this -> unshiftedButParsedParams = count($this -> url_parts) - $shiftCount;
		}

		$this -> params = $params;
		$this -> all_params = array_merge($this -> all_params, $params);

		if ($params === array())
			$params['_matched'] = true;
		if (PROFILE)
			Profiler::unmark("request::match");
		return $params;

	}

	/**
	 * shifts remaining url-parts
	 *@name shift
	 *@access public
	 *@param numeric - show much to shift
	 */
	public function shift($count) {
		for ($i = 0; $i < $count; $i++) {
			$url = array_shift($this -> url_parts);
			if($url) {
				if ($this -> shiftedPart == "") {
					$this -> shiftedPart .= $url;
				} else {
					$this -> shiftedPart .= "/" . $url;
				}
			}
		}
	}

	/**
	 * gets a param
	 *
	 * @param string $param
	 * @param bool|string $useall
	 * @return mixed|null
	 */
	public function getParam($param, $useall = true) {
		$param = strtolower($param);
		if (strtolower($useall) == "get") {
			return isset($this -> get_params[$param]) ? $this -> get_params[$param] : null;
		}

		if (strtolower($useall) == "post") {
			return isset($this -> post_params[$param]) ? $this -> post_params[$param] : null;
		}

		if (isset($this -> params[$param])) {
			return utf8_encode($this -> params[$param]);
		} else if (isset($this -> get_params[$param])) {
			return $this -> get_params[$param];
		} else if (isset($this -> post_params[$param])) {
			return $this -> post_params[$param];
		} else if (isset($this -> all_params[$param]) && $useall) {
			return $this -> all_params[$param];
		} else {
			return null;
		}
	}

	/**
	 * returns given header.
	 *
	 * @param string $header
	 * @return string|null
	 */
	public function getHeader($header) {
		return isset($this->headers[strtolower($header)]) ? $this->headers[strtolower($header)] : null;
	}

	/**
	 * gets the remaining parts
	 *
	 * @return string
	 */
	public function remaining() {
		return implode("/", $this -> url_parts);
	}

	/**
	 * @return mixed
	 */
	public function getShiftedPart()
	{
		return $this->shiftedPart;
	}

	/**
	 * @return mixed
	 */
	public function getUnshiftedButParsedParams()
	{
		return $this->unshiftedButParsedParams;
	}

	/**
	 * @return array
	 */
	public function getUrlParts()
	{
		return $this->url_parts;
	}

	/**
	 * checks if ajax response is needed
	 *
	 * @return bool
	 */
	public function isJSResponse() {
		return $this->is_ajax() && $this->getParam("ajaxfy");
	}

	/**
	 * checks if is ajax
	 *
	 * @return bool
	 */
	public function is_ajax() {
		return $this->getParam("ajax") || (isset($this->headers["x-requested-with"]) && $this->headers["x-requested-with"] == "XMLHttpRequest");
	}

	/**
	 * Checks whether the browser supports GZIP (it should send in this case the header Accept-Encoding:gzip)
	 * @return bool If true the browser supports it, otherwise not
	 */
	public static function CheckBrowserGZIPSupport() {
		if (file_exists(ROOT . ".htaccess"))
			if (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
				if ((stripos($_SERVER['HTTP_ACCEPT_ENCODING'], "gzip") !== false))
					return true;
				else
					return false;
			} else {
				return false;
			}
		return false;
	}

	/**
	 * Checks whether the browser supports deflate (it should send in this case the header Accept-Encoding:deflate)
	 * @return bool If true the browser supports it, otherwise not
	 */
	public static function CheckBrowserDeflateSupport() {
		if (file_exists(ROOT . ".htaccess"))
			if (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
				if ((stripos($_SERVER['HTTP_ACCEPT_ENCODING'], "deflate") !== false))
					return true;
				else
					return false;
			} else {
				return false;
			}
		return false;
	}

	/**
	 * @return bool|string
	 * @throws FileNotPermittedException
	 */
	public function inputStreamFile() {
		if(!isset($this->phpInputFile)) {
			if ($handle = @fopen("php://input", "rb")) {
				if (PROFILE)
					Profiler::mark("php://input read");

				$random = randomString(20);
				if (!file_exists(FRAMEWORK_ROOT . "temp/")) {
					FileSystem::requireDir(FRAMEWORK_ROOT . "temp/");
				}
				$filename = FRAMEWORK_ROOT . "temp/php_input_" . $random;

				$file = fopen($filename, 'wb');
				stream_copy_to_stream($handle, $file);
				fclose($handle);
				fclose($file);
				$this->phpInputFile = $filename;

				register_shutdown_function(array($this, "cleanUpInput"));

				if (PROFILE)
					Profiler::unmark("php://input read");
			} else {
				$this->phpInputFile = false;
			}
		}

		return $this->phpInputFile;
	}

	/**
	 * clean-up for saved file-data
	 */
	public function cleanUpInput() {
		if(isset($this->phpInputFile) && file_exists($this->phpInputFile)) {
			@unlink($this->phpInputFile);
		}
	}

	/**
	 * @return bool
	 */
	public function canReplyJavaScript() {
		return (isset($this->headers["accept"]) && preg_match('/text\/javascript/i', $this->headers["accept"]));
	}

	/**
	 * @return bool
	 */
	public function canReplyHTML() {
		return (isset($this->headers["accept"]) && preg_match('/text/html/i', $this->headers["accept"]));
	}

	/**
	 * @return bool
	 */
	public function canReplyPlain() {
		return (isset($this->headers["accept"]) && preg_match('/text/plain/i', $this->headers["accept"]));
	}
	/**
	 * @return bool
	 */
	public function canReplyJSON() {
		return (isset($this->headers["accept"]) && preg_match('/text/json/i', $this->headers["accept"]));
	}
}
