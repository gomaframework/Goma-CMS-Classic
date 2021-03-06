<?php defined("IN_GOMA") OR die();

/**
 * handler for external form urls.
 * is also manages session-managment.
 *
 * @author Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @package Goma\Form
 * @version 2.2
 */
class ExternalFormController extends RequestHandler {
    /**
     * handles the requemst
     *
     * @param Request $request
     * @param bool $subController
     * @return mixed
     * @throws Exception
     */
    public function handleRequest($request, $subController = false) {
        try {
            $this->request = $request;
            $this->subController = $subController;

            $this->init();

            $form = $request->getParam("form");
            $field = $request->getParam("field");

            return $this->FieldExtAction($form, $field);
        } catch(Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * calls handle-Request on the FormField we found.
     * it also manages session-managment.
     *
     * @param string $form
     * @param string $field
     * @return bool|mixed
     * @throws Exception
     */
    public function FieldExtAction($form, $field) {
        $field = strtolower($field);

        /** @var Form $formInstance */
        if($formInstance = GlobalSessionManager::globalSession()->get(Form::SESSION_PREFIX . "." . strtolower($form))) {
            if(isset($formInstance->$field)) {
                $formInstance->checkForStateRestore();
                $oldRequest = $formInstance->getRequest();
                $oldControllerRequest = $formInstance->controller->getRequest();

                $formInstance->setRequest($this->request);
                $formInstance->controller->setRequest($this->request);

                $data = $formInstance->$field->handleRequest($this->request, true);

                $formInstance->setRequest($oldRequest);
                $formInstance->controller->setRequest($oldControllerRequest);
                GlobalSessionManager::globalSession()->set(Form::SESSION_PREFIX . "." . strtolower($form), $formInstance);
                GlobalSessionManager::globalSession()->set("form_state_" . $form, $formInstance->state->ToArray());
                return $data;
            }
            return false;

        }
        return false;
    }

}


Director::addRules(array('system/forms/$form!/$field!' => "ExternalFormController"), 50);
