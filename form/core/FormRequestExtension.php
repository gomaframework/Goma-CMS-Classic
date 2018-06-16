<?php defined("IN_GOMA") OR die();

/**
 * enables all kind of RequestHandlers to have a form-action.
 *
 * @author Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @package Goma\Form
 * @version 2.2
 *
 * @method RequestHandler getOwner()
 */
class FormRequestExtension extends ControllerExtension {

    /**
     * external form controller.
     */
    protected $externalFormController;

    static $url_handlers = array(
        "forms/form" => "handleForm"
    );

    static $extra_methods = array(
        "handleForm"
    );

    static $allowed_actions = array(
        "handleForm"
    );

    /**
     * FormRequestExtension constructor.
     * @param ExternalFormController|null $externalFormController
     */
    public function __construct($externalFormController = null)
    {
        parent::__construct();

        $this->externalFormController = isset($externalFormController) ? $externalFormController : new ExternalFormController();
    }

    /**
     * @return mixed|null
     */
    public function handleForm() {
        $formRequest = clone $this->getOwner()->getRequest();
        if ($arguments = $formRequest->match('$form!/$field!', true)) {
            return $this->externalFormController->handleRequest($formRequest, true);
        }

        return null;
    }
}

gObject::extend("RequestHandler", "FormRequestExtension");
