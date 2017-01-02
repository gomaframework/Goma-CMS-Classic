<?php
defined('IN_GOMA') OR die();

/**
 * Controller-Extension wraps request and other stuff to this model.
 *
 * @package		Goma\Security\Users
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @version		2.3.2
 */
abstract class ControllerExtension extends Controller implements ExtensionModel {
    /**
     * works the same as on {@link requestHandler}
     */
    public $url_handlers = array();

    /**
     * works the same as on {@link requestHandler}
     */
    public $allowed_actions = array();

    /**
     * the owner-class
     *
     * @var RequestHandler
     */
    protected $owner;

    /**
     * sets the owner-class
     * @param Controller $object
     * @return $this
     */
    public function setOwner($object)
    {
        if(isset($object)) {
            if (!is_a($object, "RequestHandler")) {
                throw new InvalidArgumentException('$object isn\'t a object of type RequestHandler.');
            }

            $this->request = $object->getRequest();
            $this->namespace = $object->namespace;
            $this->originalNamespace = $object->originalNamespace;
            $this->subController = $object->isSubController();
            $this->owner = $object;
        } else {
            $this->owner = $this->subController = $this->originalNamespace = $this->namespace = $this->request = null;
        }
        return $this;
    }

    /**
     * gets the owner of class
     *
     * @return RequestHandler
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * gets the url handlers
     */
    public function url_handlers()
    {
        return $this->url_handlers;
    }

    /**
     * gets the allowed_actions
     */
    public function allowed_actions()
    {
        return $this->allowed_actions;
    }
}
