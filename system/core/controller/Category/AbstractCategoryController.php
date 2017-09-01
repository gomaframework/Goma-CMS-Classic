<?php
namespace Goma\Controller\Category;
defined("IN_GOMA") OR die();

/**
 * Gives a UI which uses categories.
 *
 * @package Goma\Controller
 *
 * @author Goma-Team
 * @copyright 2016 Goma-Team
 * @license        GNU Lesser General Public License, version 3; see "LICENSE.txt"
 *
 * @version 1.0
 */
abstract class AbstractCategoryController extends \Controller {
    /**
     * @var array
     */
    public $url_handlers = array(
        "\$Action" => "\$Action"
    );

    /**
     * if to allow extensions on this controller.
     *
     * @var bool
     */
    protected $allowExtensions = true;

    /**
     * @var string
     */
    protected $currentAction;

    /**
     * returns categories in form method => category title
     * @return array
     */
    abstract public function provideCategories();

    /**
     * @var array
     */
    private $categoryCache;

    /**
     * @param string $content
     * @return string
     */
    public function __output($content)
    {
        if(!\Director::isResponseFullPage($content)) {
            $view = new \ViewAccessableData();
            $view->customise(array(
                "categories"    => $this->getCategorySet(),
                "content"       => \Director::getStringFromResponse($content),
                "namespace"     => $this->namespace,
                "currentAction" => $this->currentAction,
                "activeTitle"   => $this->getExtendedCategories()[$this->currentAction],
                "cid"           => randomString(5)
            ));
            return parent::__output(
                \Director::setStringToResponse($content, $view->renderWith("framework/categoryView.html", $this->inExpansion))
            );
        }

        return parent::__output($content);
    }

    /**
     * @return string|null
     */
    public function index()
    {
        $categories = $this->getExtendedCategories();
        foreach($categories as $method => $category) {
            if($this->checkPermission($method)) {
                return $this->handleAction($method);
            }
        }

        return null;
    }

    /**
     * @param string $action
     * @return false|mixed|null
     */
    public function handleAction($action)
    {
        $this->currentAction = $action;

        if($action != "index" && $title = $this->getActiveActionTitle()) {
            \Core::setTitle($title);
            \Core::addBreadcrumb($title, $this->namespace . "/" . $action . URLEND);
        }

        return parent::handleAction($action);
    }

    /**
     * @return array
     */
    protected function getExtendedCategories() {
        if($this->categoryCache) {
            return $this->categoryCache;
        }

        $categories = $this->provideCategories();
        if($this->allowExtensions) {
            $this->callExtending("decorateCategories", $categories);
        }
        $this->categoryCache = $categories;
        return $categories;
    }

    /**
     * @return string
     */
    protected function getActiveActionTitle() {
        $categories = $this->getExtendedCategories();
        if($this->currentAction && isset($categories[$this->currentAction])) {
            $title = $categories[$this->currentAction];
        } else {
            $title = isset($categories["index"]) ? $categories["index"] : null;
        }

        $this->callExtending("decorateActionTitle", $title, $this->currentAction);

        return $title;
    }

    /**
     * @return string
     */
    protected function getRedirectAppendix() {
        if(isset($this->request)) {
            if(isset($this->request->get_params["redirect"]) &&
                isURLFromServer($this->request->get_params["redirect"], $this->request->getServerName())) {
                return "?redirect=" . urlencode(\convert::raw2text($this->request->get_params["redirect"]));
            } else if(isset($this->request->post_params["redirect"]) &&
                isURLFromServer($this->request->post_params["redirect"], $this->request->getServerName())) {
                return "?redirect=" . urlencode(\convert::raw2text($this->request->post_params["redirect"]));
            }
        }

        return "";
    }

    /**
     * @return \DataSet
     */
    protected function getCategorySet() {
        $set = new \DataSet();
        foreach($this->getExtendedCategories() as $method => $category) {
            if($method != "index" && !$this->hasAction($method)) {
                throw new \InvalidArgumentException("Action $method does not exist.");
            }

            $redirect = $this->getRedirectAppendix();
            $set->push(array(
                "active"        => strtolower($this->currentAction) == strtolower($method) ||
                    ($method == "index" && !$this->currentAction),
                "url"           => $method == "index" ? $this->namespace . URLEND . $redirect :
                    $this->namespace . "/" . $method . URLEND . $redirect,
                "action"        => $method,
                "title"         => $category
            ));
        }

        return $set;
    }

    /**
     * @param \gObject|string $sender - action or sender
     * @return string
     */
    public function getRedirect($sender)
    {
        if($sender == "index") {
            return parent::getRedirect($sender);
        }

        if($this->currentAction) {
            if($this->currentAction == "index") {
                return parent::getRedirect($sender);
            }

            return $this->namespace . "/" . $this->currentAction . URLEND;
        }

        return $this->namespace;
    }
}
