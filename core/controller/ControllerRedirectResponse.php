<?php
defined("IN_GOMA") OR die();

/**
 * Used to redirect-back.
 *
 * @package Goma
 *
 * @author Goma-Team
 * @copyright 2016 Goma-Team
 * @license        GNU Lesser General Public License, version 3; see "LICENSE.txt"
 *
 * @version 1.0
 */
class ControllerRedirectResponse extends GomaResponse {
    /**
     * message types.
     */
    const MESSAGE_TYPE_NOTICE = "notice";
    const MESSAGE_TYPE_SUCCESS = "success";
    const MESSAGE_TYPE_ERROR = "error";

    /**
     * hinted.
     */
    protected $hintUrl;

    /**
     * from url.
     *
     * TODO: Find out why we need this?
     */
    protected $fromUrl;

    /**
     * used.
     */
    protected $parentControllerResolved;

    /**
     * values.
     */
    protected $params = array();

    /**
     * @var bool
     */
    protected $useJavascript;

    /**
     * messages.
     */
    protected $messages = array();

    /**
     * ControllerRedirectBackResponse constructor.
     *
     * @param string $hintUrl
     * @param string $fromUrl
     * @param bool $useJavaScript
     * @return static
     */
    public static function create($hintUrl = null, $fromUrl = null, $useJavaScript = false) {
        return new static($hintUrl, $fromUrl, $useJavaScript);
    }

    /**
     * @return void
     */
    public function output() {
        $url = $this->hintUrl ? $this->hintUrl : BASE_URI;
        foreach($this->params as $key => $value) {
            $url = Controller::addParamToUrl($url, $key, $value);
        }

        try {
            AddContent::add(AddContent::get());
        } catch(Exception $e) {} catch(Throwable $e) {}

        foreach($this->messages as $message) {
            if($message[1] == self::MESSAGE_TYPE_ERROR) {
                AddContent::addError($message[0]);
            } else if($message[1] == self::MESSAGE_TYPE_NOTICE) {
                AddContent::addNotice($message[0]);
            } else if($message[1] == self::MESSAGE_TYPE_SUCCESS) {
                AddContent::addSuccess($message[0]);
            } else {
                AddContent::add($message[0]);
            }
        }

        if($this->useJavascript) {
            GomaResponse::create($this->header, $this->body)->redirectByJavaScript($url)->output();
        } else {
            GomaResponse::create($this->header, $this->body)->redirectRequest($url)->output();
        }
    }

    /**
     * @param string $hintUrl
     * @param null $fromUrl
     * @param bool $useJavaScript
     */
    public function __construct($hintUrl, $fromUrl = null, $useJavaScript = false)
    {
        parent::__construct();

        $this->fromUrl = $fromUrl;
        $this->useJavascript = $useJavaScript;
        $this->hintUrl = $hintUrl;
    }

    /**
     * @return mixed
     */
    public function getHintUrl()
    {
        return $this->hintUrl;
    }

    /**
     * @param mixed $hintUrl
     * @return $this
     */
    public function setHintUrl($hintUrl)
    {
        $this->hintUrl = $hintUrl;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getParentControllerResolved()
    {
        return $this->parentControllerResolved;
    }

    /**
     * @param mixed $parentControllerResolved
     * @return $this
     */
    public function setParentControllerResolved($parentControllerResolved)
    {
        $this->parentControllerResolved = $parentControllerResolved;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFromUrl()
    {
        return $this->fromUrl;
    }

    /**
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function setParam($name, $value) {
        if(isset($name)) {
            $this->params[$name] = $value;
        }
        return $this;
    }

    /**
     * @return mixed
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param mixed $params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * @return boolean
     */
    public function isUseJavascript()
    {
        return $this->useJavascript;
    }

    /**
     * @param boolean $useJavascript
     */
    public function setUseJavascript($useJavascript)
    {
        $this->useJavascript = $useJavascript;
    }

    /**
     * clears all messages.
     */
    public function clear() {
        $this->messages = array();
    }

    /**
     * @param string $message HTML Message
     * @param string $type MessageType
     * @return $this
     */
    public function addMessage($message, $type = self::MESSAGE_TYPE_NOTICE) {
        $this->messages[] = array($message, $type);
        return $this;
    }
}
