<?php
defined("IN_GOMA") OR die();

/**
 * GomaResponse is the BaseClass of all Response-Objects. It handlers all response-specific parts like
 * * status code
 * * headers
 * * body
 *
 * There is also the possibility to give parent controllers the information how to treat this request.
 * * isFullPage defines, that this request should not be packed within other's template, since it is finalized
 *
 * There are some static functions to generate some usecases of GomaResponse, for example
 * * redirect($url, $permanent = false) creates a redirect-response. permanent defines if 301 or 302
 *
 * @package Goma
 *
 * @author Goma-Team
 * @copyright 2016 Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 *
 * @version 1.0
 */
class GomaResponse extends gObject {
    /**
     * responsetypes
     *
     * @var array
     */
    public static $http_status_types = array(
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Moved Temporarily',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method not Allowed',
        406 => "Not acceptable",
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        511 => 'Network Authentication Required'
    );
    /**
     * header.
     *
     * @var array
     */
    protected $header = array();

    /**
     * body.
     *
     * @var GomaResponseBody
     */
    protected $body;

    /**
     * response-status.
     */
    protected $status = 200;

    /**
     * should serve?.
     * @var bool
     */
    protected $shouldServe = true;

    /**
     * @var bool
     */
    protected $isFullPage = null;

    /**
     * GomaResponse constructor.
     *
     * @param array|null $header
     * @param string|null $body
     * @return GomaResponse
     */
    public static function create($header = null, $body = null) {
        return new static($header, $body);
    }

    /**
     * GomaResponse constructor.
     *
     * @param array|null $header
     * @param string|null $body
     */
    public function __construct($header = null, $body = null)
    {
        parent::__construct();

        $this->setDefaultHeader();
        $this->setHeader((array) $header);
        $this->setBody($body);
    }

    protected function setDefaultHeader() {
        $this->setHeader("vary", "Accept-Encoding");
        $this->setHeader("X-Powered-By", "Goma ".strtok(GOMA_VERSION, ".")." with PHP " . PHP_MAIOR_VERSION);
        $this->setHeader("content-type", "text/html;charset=utf-8");
        $this->setHeader("x-base-uri", BASE_URI);
        $this->setHeader("x-root-path", ROOT_PATH);

        if(isset(ClassInfo::$appENV["app"]["name"]) && defined("APPLICATION_VERSION"))
            $this->setHeader('X-GOMA-APP', ClassInfo::$appENV["app"]["name"] . " " . strtok(APPLICATION_VERSION, "."));
    }

    /**
     * sets a header.
     *
     * @param array|string $name
     * @param string $value
     * @return $this
     */
    public function setHeader($name, $value = "") {
        if(is_array($name)) {
            foreach($name as $key => $header) {
                $this->setHeader($key, $header);
            }
            return $this;
        } else if(!$value) {
            return $this->removeHeader($name);
        }

        $this->header[strtolower($name)] = $value;
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function removeHeader($name) {
        unset($this->header[strtolower($name)]);
        return $this;
    }

    /**
     * @return array
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @return GomaResponseBody
     */
    public function getBody()
    {
        return clone $this->body;
    }

    public function getResponseBodyString() {
        return (string) $this->body;
    }

    /**
     * @param string|GomaResponseBody $body
     * @return $this
     */
    public function setBody($body)
    {
        if(is_a($body, "GomaResponse")) {
            throw new InvalidArgumentException("Body is of class GomaResponse, should be string or GomaResponseBody.");
        } else if(is_a($body, "GomaResponseBody")) {
            $this->body = $body;
        } else {
            if(!isset($this->body)) {
                $this->body = new GomaResponseBody($body);
            } else {
                $this->body->setBody($body);
            }
        }
        return $this;
    }

    /**
     * @param string $body
     * @return $this
     */
    public function setBodyString($body) {
        if(!is_a($body, "GomaResponseBody")) {
            $this->body->setBody($body);
        } else {
            $this->setBody($body);
        }
        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     * @return $this
     */
    public function setStatus($status)
    {
        if(!isset(self::$http_status_types)) {
            throw new InvalidArgumentException("HTTP Status $status not known.");
        }

        $this->status = $status;

        return $this;
    }

    /**
     * @param string $url
     * @param bool $permanent
     * @return GomaResponse
     */
    public function redirectRequest($url, $permanent = false)
    {
        if(!preg_match('/^(http|https|ftp)\:\/\//i', $url) && !preg_match("/^(\/|\.\/)/i", $url)) {
            $url = BASE_URI . BASE_SCRIPT . $url;
        }

        $request = clone $this;

        $request->setStatus($permanent ? 301 : 302);
        $request->setHeader("location", $url);
        $request->setBody(
            GomaResponseBody::create(
                '<script type="text/javascript">location.href = "'.addSlashes($url).'";</script><br /> Redirecting to: <a href="'.addSlashes($url).'">'.convert::raw2text($url).'</a>'
            )->setParseHTML(false)->setIsFullPage(true)
        );
        $request->setShouldServe(false);

        return $request;
    }

    /**
     * redirect by javascript
     *
     * @param string $url
     * @return GomaResponse
     */
    public function redirectByJavaScript($url) {
        $response = new AjaxResponse($this->header, $this->body);

        $response->exec("window.location.href = " . var_export($url, true) . ";");

        return $response;
    }

    /**
     * @param string $url
     * @param bool $permanent
     * @return GomaResponse
     */
    public static function redirect($url, $permanent = false)
    {
        $response = new GomaResponse();
        return $response->redirectRequest($url, $permanent);
    }

    /**
     * Sends file with redirect to FileSender.
     *
     * @param string $file
     * @return GomaResponse
     */
    public function sendFile($file, $filename = null) {
        if(!file_exists($file))
            throw new InvalidArgumentException("File must exist.");

        $hash = randomString(20);
        FileSystem::write(FRAMEWORK_ROOT . "temp/download." . $hash . ".goma", serialize(array("file" => realpath($file), "filename" => $filename)));

        return $this->redirectRequest(ROOT_PATH . "system/libs/file/Sender/FileSender.php?downloadID=" . $hash);
    }

    /**
     * sets Pragma, Last-Modified, Expires and Cache-Control.
     *
     * @param int $expires
     * @param int $lastModfied
     * @param bool $includeProxy
     * @return $this
     */
    public function setCacheHeader($expires, $lastModfied, $includeProxy = false)
    {
        if($includeProxy) {
            $this->setHeader("Pragma", "public");
        } else {
            $this->setHeader("Pragma", "No-Cache");
        }

        $this->setHeader("Last-Modified", gmdate('D, d M Y H:i:s', $lastModfied).' GMT');
        $this->setHeader("Expires", gmdate('D, d M Y H:i:s', $expires).' GMT');
        $age = $expires - time();
        $this->setHeader("cache-control", "public; max-age=".$age."");
        return $this;
    }

    /**
     * sets Pragma, Last-Modified, Expires and Cache-Control.
     * Browser won't cache also when back is pressed.
     */
    public function forceNoCache()
    {
        $this->setHeader("Pragma", "No-Cache");
        $this->setHeader("Last-Modified", '');
        $this->setHeader("Expires", '0');
        $this->setHeader("cache-control", " no-cache, max-age=0, must-revalidate, no-store");
        return $this;
    }

    /**
     * renders body.
     */
    public function render() {
        if(!is_a($this->body, "GomaResponseBody")) {
            $body = new GomaResponseBody($this->body);
            return $body->toServableBody($this);
        }

        return $this->body->toServableBody($this);
    }

    /**
     * sends header and response body.
     */
    public function output()
    {
        if($this->status == 301 || $this->status == 302) {
            $isPermanent = $this->status == 301;
            Core::callHook("beforeRedirect", $this->header["location"], $isPermanent, $this);
        }

        $content = $this->render();
        $this->addResourcesToHeaders();

        $this->callExtending("beforeOutput");

        $this->sendHeader();

        Core::callHook("onbeforeoutput");

        $data = ob_get_contents();
        ob_end_clean();

        if($data != null) {
            $outputException = new OutputException("There should not be any output than body.", $data);
            if(DEV_MODE) {
                log_exception($outputException);
            } else {
                throw $outputException;
            }
        }

        ob_start("ob_gzhandler");

        echo $content;
        echo $data;

        ob_end_flush();
    }

    /**
     * Adds resources to header.
     */
    protected function addResourcesToHeaders() {
        if(class_exists("Resources", false)) {
            $data = Resources::get(false, true, true, true);
            $this->setHeader("X-JavaScript-Load", implode(";", $data["js"]));
            $this->setHeader("X-CSS-Load", implode(";", $data["css"]));
        }
    }

    /**
     * sends header.
     *
     * @internal
     */
    public function sendHeader()
    {
        if(DEV_MODE || PROFILE) {
            $time =  microtime(true) - EXEC_START_TIME;
            $this->setHeader("X-Time", $time);
        }

        header('HTTP/1.1 ' . $this->status . " " . self::$http_status_types[$this->status]);
        foreach($this->header as $key => $value) {
            header($key . ": " . $value);
        }
        return $this;
    }

    /**
     *
     */
    public function shouldServe()
    {
        return $this->shouldServe;
    }

    /**
     * @deprecated
     * @return bool
     */
    public function getShouldServe()
    {
        return $this->shouldServe();
    }

    /**
     * @param bool $shouldServe
     * @return $this
     */
    public function setShouldServe($shouldServe)
    {
        $this->shouldServe = $shouldServe;
        return $this;
    }

    /**
     * @param GomaResponse $response
     * @internal
     * @return $this
     */
    public function merge($response) {
        $this->header = array_merge($response->header, $this->header);
        if($this->status == 200 && $response->status != 200) {
            $this->status = $response->status;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getRawBody() {
        return $this->getBody()->getBody();
    }

    /**
     * defines if this response is part of a full page.
     * false means it should stand for itself.
     *
     * @return bool
     */
    public function isFullPage() {
        return is_bool($this->isFullPage) ? $this->isFullPage : $this->getBody()->isFullPage();
    }

    /**
     * @param boolean|null $isFullPage
     * @return $this
     */
    public function setIsFullPage($isFullPage)
    {
        if(isset($isFullPage) && !is_bool($isFullPage)) {
            throw new InvalidArgumentException();
        }

        $this->isFullPage = $isFullPage;
        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getResponseBodyString();
    }
}
