<?php
defined("IN_GOMA") OR die();

/**
 * A simple FormAction, which submits data via Ajax and calls the
 * ajax-response-handler given.
 *
 * you should return the given AjaxResponse-Object or Plain JavaScript in
 * Ajax-Response-Handler.
 * a handler could look like this:
 * public function ajaxSave($data, $response) {
 *      $response->exec("alert('Nice!')");
 *      return $response;
 * }
 *
 * @author Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @package    Goma\Form
 * @version    2.1.9
 */
class AjaxSubmitButton extends FormAction {
    /**
     * the action for ajax-submission
     */
    protected $ajaxsubmit;

    /**
     * @name __construct
     * @access public
     * @param string - name
     * @param string - title
     * @param string - ajax submission
     * @param string - optional submission
     * @param object - form
     */
    public function __construct($name = "", $value = "", $ajaxsubmit = null, $submit = null, $classes = null, &$form = null)
    {

        parent::__construct($name, $value, null, $classes);
        if ($submit === null)
            $submit = "@default";

        $this->submit = $submit;
        $this->ajaxsubmit = $ajaxsubmit;
        if ($form != null) {
            $this->parent = &$form;
            $this->setForm($form);
        }
    }

    /**
     * @param FormFieldRenderData $info
     * @param bool $notifyField
     */
    public function addRenderData($info, $notifyField = true)
    {
        $info->addJSFile("system/form/actions/AjaxSubmitButton.js");

        parent::addRenderData($info, $notifyField);
    }

    /**
     * generates the js
     *
     * @return string
     */
    public function js()
    {
        return 'initAjaxSubmitbutton(' . var_export($this->ID(), true) . ', ' . var_export($this->divID(), true) . ', form, field, ' . var_export($this->form()->url, true) . ', "");';
    }

    public function canSubmit($data)
    {
        return !!$data;
    }

    public function getSubmit() {
        return $this->submit;
    }

    /**
     * @return null|string
     */
    public function __getSubmit() {
        if($this->getRequest()->canReplyJavaScript()) {
            return array($this, "submit");
        }

        return $this->submit;
    }

    /**
     * submit-function
     * @param array $data
     * @param Form $form
     * @param Controller $controller
     * @return mixed
     */
    public function submit($data, $form, $controller)
    {
        $response = new FormAjaxResponse($form, $this);

        $response->exec('$("#' . $form->{"secret_" . $form->id()}->id() . '").val("' . convert::raw2js($form->getSecretKey()) . '");');

        try {
            $response = $this->handleSubmit($data, $form, $response, $controller);

            if (is_a($response, FormAjaxResponse::class) && $response->getLeaveCheck() === null) {
                $response->setLeaveCheck(false);
            }

            return $response;
        } catch (Exception $e) {
            if (is_a($e, "FormNotValidException")) {
                /** @var FormNotValidException $e */
                $errors = $e->getErrors();
            } else {
                $errors = array($e);
            }

            /** @var Form $form */
            if(($form = $this->form()) && is_a($form, Form::class)) {
                $response= $form->addErrorsToJSONResponse($response, $errors);
            } else {
                throw new LogicException();
            }

            return $response;
        }
    }

    /**
     * @param array $result
     * @param Form $form
     * @param FormAjaxResponse $response
     * @param Controller $controller
     * @return mixed
     */
    protected function handleSubmit($result, $form, $response, $controller)
    {
        $submission = $this->ajaxsubmit;

        if (is_callable($submission) && !is_string($submission)) {
            return call_user_func_array($submission, array(
                $result,
                $response,
                $form,
                $controller
            ));
        } else {
            return call_user_func_array(array(
                $controller,
                $submission
            ), array(
                $result,
                $response,
                $form,
                $controller
            ));
        }
    }

    /**
     * sets the submit-method and ajax-submit-method
     *
     * @name setSubmit
     * @access public
     * @param string - submit
     * @param string - ajaxsubmit
     * @return $this
     */
    public function setSubmit($submit, $ajaxsubmit = null)
    {
        $this->submit = $submit;
        if (isset($ajaxsubmit))
            $this->ajaxsubmit = $ajaxsubmit;
        return $this;
    }
    /**
     * returns the ajax-submit-method
     */
    public function getAjaxSubmit()
    {
        return $this->ajaxsubmit;
    }
}
