<?php
defined("IN_GOMA") OR die();

/**
 * A GomaResponse object especially designed for Forms.
 *
 * @package Goma
 *
 * @author Goma Team
 * @copyright 2016 Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 *
 * @version 1.0
 */
class GomaFormResponse extends GomaResponse {
    /**
     * @var Form
     */
    protected $form;

    /**
     * prepended string.
     *
     * @var string[]
     */
    protected $prependString = array();

    /**
     * functions after rendering.
     */
    protected $functionsForRendering = array();

    /**
     * @var ViewAccessableData
     */
    protected $serveWithModel;

    /**
     * @var string
     */
    protected $template;

    /**
     * @var string
     */
    protected $templateName = "form";

    /**
     * @var string|GomaResponse
     */
    protected $renderedForm;

    /**
     * resolve-cache.
     */
    protected $resolveCache = array();

    /**
     * @var string|null
     */
    protected $inTplExpansion;

    /**
     * @param null|array $header
     * @param Form $form
     * @return static
     */
    public static function create($header = null, $form = null) {
        return new static($header, $form);
    }

    /**
     * GomaFormResponse constructor.
     * @param array|null $header
     * @param Form $form
     */
    public function __construct($header, $form)
    {
        parent::__construct($header);
        if(!isset($form)) {
            throw new InvalidArgumentException("Form must be not null.");
        }

        $this->form = $form;
    }

    public function isStringResponse() {
        $this->resolveForm();

        if(!is_a($this->renderedForm, "GomaResponse")) {
            if(is_object($this->renderedForm) && !method_exists($this->renderedForm, "__toString")) {
                throw new LogicException("Forms should return GomaResponse, other type or object with __toString");
            }

            return true;
        }

        return false;
    }

    /**
     *
     */
    public function resolveForm() {
        if(!isset($this->renderedForm)) {
            $this->renderedForm = $this->form->renderData();
            if(!is_array($this->renderedForm)) {
                if (!is_a($this->renderedForm, "GomaResponse")) {
                    parent::setBody($this->renderedForm);
                    $this->body->setIncludeResourcesInBody(!$this->form->getRequest()->is_ajax());
                } else if (!isset($this->renderedForm)) {
                    throw new LogicException("Form response can't be null.");
                }
            } else {
                parent::setBody(print_r($this->renderedForm, true));
                $this->body->setIncludeResourcesInBody(!$this->form->getRequest()->is_ajax());
            }
        }
    }

    /**
     * rendering.
     * @param string $content
     * @return string
     */
    protected function resolveRendering($content) {
        $key = md5($content);
        if(isset($this->resolveCache[$key])) {
            return $this->resolveCache[$key];
        }

        foreach($this->prependString as $string) {
            $content = $string . $content;
        }

        if($this->template != null) {
            $content = $this->serveWithModel->customise(array(
                $this->templateName => $content
            ))->renderWith($this->template, $this->inTplExpansion);
        }

        foreach($this->functionsForRendering as $function) {
            if(is_callable($function)) {
                $newContent = call_user_func_array($function, array($content, $this));
                if($newContent) {
                    $content = $newContent;
                }
            }
        }

        $this->resolveCache[$key] = $content;

        return $content;
    }

    /**
     * @internal
     * @return bool
     */
    public function shouldServe()
    {
        if($this->isStringResponse()) {
            if(!is_string($this->renderedForm)) {
                return false;
            }

            return $this->shouldServe;
        } else {
            return $this->renderedForm->shouldServe();
        }
    }

    /**
     * @internal
     * @param bool $shouldServe
     * @return $this
     */
    public function setShouldServe($shouldServe)
    {
        if(!$this->isStringResponse()) {
            $this->renderedForm->setShouldServe($shouldServe);
        } else {
            parent::setShouldServe($shouldServe);
        }
        return $this;
    }

    /**
     * @return GomaResponseBody
     */
    public function getBody()
    {
        if($this->isStringResponse()) {
            $body = clone parent::getBody();
            return $body->setBody($this->resolveRendering($body->getBody()));
        }
        return $this->renderedForm->getBody();
    }

    /**
     * @return string
     */
    public function getResponseBodyString()
    {
        return (string) (!$this->isStringResponse() ? $this->renderedForm->getResponseBodyString() : $this->resolveRendering(
            parent::getResponseBodyString()
        ));
    }

    /**
     * @param string $body
     * @return $this
     */
    public function setBodyString($body, $resetCustomisation = true)
    {
        if(!$this->isStringResponse()) {
            $this->renderedForm->setBodyString($body);
        } else {
            parent::setBodyString($body);
        }

        $this->template = null;
        $this->functionsForRendering = array();
        $this->prependString = array();

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        if(!$this->isStringResponse()) {
            return $this->renderedForm->getStatus();
        } else {
            return parent::getStatus();
        }
    }

    public function getResult() {
        $this->resolveForm();

        return $this->renderedForm;
    }

    /**
     * @param mixed $status
     * @return $this|void
     */
    public function setStatus($status)
    {
        if(!$this->isStringResponse()) {
            $this->renderedForm->setStatus($status);
        } else {
            parent::setStatus($status);
        }

        return $this;
    }

    /**
     * @param GomaResponseBody|string $body
     * @return $this
     */
    public function setBody($body)
    {
        if(isset($body)) {
            $this->resolveForm();
        }

        if(is_a($this->renderedForm, "GomaResponse")) {
            $this->renderedForm->setBody($body);
        } else {
            parent::setBody($body);
        }

        return $this;
    }

    /**
     * sets a header.
     *
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function setHeader($name, $value = "") {
        if(is_a($this->renderedForm, "GomaResponse")) {
            $this->renderedForm->setHeader($name, $value);
        } else {
            parent::setHeader($name, $value);
        }
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function removeHeader($name) {
        if(is_a($this->renderedForm, "GomaResponse")) {
            $this->renderedForm->removeHeader($name);
        } else {
            parent::removeHeader($name);
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getHeader()
    {
        if(!$this->isStringResponse()) {
            return $this->renderedForm->getHeader();
        } else {
            return parent::getHeader();
        }
    }

    /**
     * @param string $content
     * @return $this
     */
    public function prependContent($content) {
        $this->resolveCache = array();
        $this->prependString[] = $content;
        return $this;
    }

    /**
     * @param string $view
     * @param gObject $model
     * @param string $formName default: "form"
     * @param string|null $inExpansion
     * @return $this
     */
    public function setRenderWith($view, $model = null, $formName = null, $inExpansion = null) {
        if(!isset($formName)) {
            $formName = "form";
        }

        $this->resolveCache = array();
        if(!isset($model)) {
            $model = new ViewAccessableData();
        } else if(is_string($model)) {
            $currentView = $model;
            $model = $view;
            $view = $currentView;
        }

        $this->serveWithModel = $model;
        $this->template = $view;
        $this->templateName = isset($formName) ? $formName : "form";
        $this->inTplExpansion = $inExpansion;

        return $this;
    }

    /**
     * outputs data.
     */
    public function output()
    {
        if(!$this->isStringResponse()) {
            $this->renderedForm->output();
        } else {
            parent::output();
        }
    }

    /**
     * @return string
     */
    public function render() {
        return $this->resolveRendering(parent::render());
    }

    /**
     * @return Form
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @param Form $form
     */
    public function setForm($form)
    {
        $this->form = $form;
    }

    public function __toString()
    {
        try {
            return $this->getResponseBodyString();
        } catch(Exception $e) {
            log_exception($e);
            return $e->getCode() . ": " . $e->getMessage();
        }
    }

    /**
     * @return bool
     */
    public function isRendered()
    {
        return isset($this->renderedForm);
    }

    /**
     * @return bool
     */
    public function isFullPage()
    {
        return !$this->isStringResponse() && Director::isResponseFullPage($this->renderedForm);
    }

    /**
     * @param Callable $function
     * @return $this
     */
    public function addRenderFunction($function) {
        if(isset($this->renderedForm)) {
            throw new InvalidArgumentException("Rendering has been finished.");
        }

        if(!is_callable($function)) {
            throw new InvalidArgumentException("Function must be callable.");
        }

        $this->functionsForRendering[] = $function;
        $this->resolveCache = array();
        return $this;
    }
}
