<?php
defined("IN_GOMA") OR die();

/**
 * A cancel button.
 *
 * @author Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @package Goma\Form
 * @version 1.0
 */
class CancelButton extends FormAction
{
    /**
     * the javascript for this button on cancel
     */
    public $js;

    /**
     * @var null|string
     */
    protected $redirect;

    /**
     * @name __construct
     * @access public
     * @param string - name
     * @param string - title
     * @param string - optional submission
     * @param object - form
     */
    public function __construct($name = null, $value = null, $redirect = null, $js = "", &$form = null)
    {
        $this->js = $js;
        parent::__construct($name, $value);
    }

    /**
     * creates the node
     *
     * @name createNodes
     * @return HTMLNode
     */
    public function createNode()
    {
        $node = parent::createNode();
        $node->onClick = $this->js;
        $node->addClass("cancel");
        return $node;
    }

    /**
     * just don't let the system submit and redirect back
     * @param $data
     * @return bool
     */
    public function canSubmit($data)
    {
        return true;
    }

    /**
     * @return array
     */
    public function getSubmit()
    {
        return array($this, "redirect");
    }

    /**
     * @return null|string
     */
    public function __getSubmit() {
        return array($this, "redirect");
    }

    /**
     * @return GomaResponse
     */
    public function redirect() {
        if($this->redirect) {
            return GomaResponse::redirect($this->redirect);
        }

        return GomaResponse::redirect($this->form()->getController()->getRedirect());

    }

    /**
     * @return null|string
     */
    public function getRedirect()
    {
        return $this->redirect;
    }

    /**
     * @param null|string $redirect
     * @return $this
     */
    public function setRedirect($redirect)
    {
        $this->redirect = $redirect;
        return $this;
    }
}
