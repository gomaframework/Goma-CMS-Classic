<?php
defined("IN_GOMA") OR die();

/**
 * Confirmation-Form.
 *
 * @package     Goma\Form
 *
 * @license     GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author      Goma-Team
 *
 * @version     1.0
 */
class ConfirmationForm extends Form {

    /**
     * element for ajax.
     */
    protected $ajaxElement;

    /**
     * callback-set.
     *
     * @var array
     */
    protected $callbacks = array();

    /**
     * dialog.
     *
     * @var string
     */
    protected $ajaxSubmitID;

    /**
     * @param string $methodName
     * @param array $args
     * @return mixed
     */
    public function __call($methodName, $args)
    {
        if(strtolower(substr($methodName, 0, 7)) == "submit_") {
            $name = strtolower(substr($methodName, 7));
            if(isset($this->callbacks[$name]) && isset($args[0], $args[1], $args[2])) {
                $response = call_user_func_array($this->callbacks[$name], $args);

                if(is_a($response, AjaxResponse::class)) {
                    $response->exec("var id = $('#".$this->ajaxSubmitID."').parents('.dropdownDialog').attr('id');if(id) {
                        var dialog = dropdownDialog.get(id);
                        if(dialog) {
                            dialog.hide();
                        }
                    }");
                }

                return $response;
            }
        }

        return parent::__call($methodName, $args);
    }

    /**
     * @param array $errors
     * @param bool $notSavedYet
     * @return mixed|string
     */
    protected function renderFormFields($errors = array(), $notSavedYet = false)
    {
        if($this->getRequest()->canReplyJavaScript() || isset($this->request->get_params["ajaxfy"])) {
            foreach($this->actions as $actionData) {
                /** @var FormAction $action */
                $action = $actionData["field"];
                if(!is_a($action, "AjaxSubmitButton") && is_a($action, "FormAction")) {
                    $this->removeAction($action->name);
                    $this->callbacks[strtolower($action->name)] = $action->getSubmit() == Form::DEFAULT_SUBMSSION ? $this->getSubmission() : $action->getSubmit();
                    $this->addAction($ajax = new AjaxSubmitButton(
                        $action->name,
                        $action->getTitle(),
                        array($this, "submit_" . $action->name),
                        array($this, "submit_" . $action->name),
                        $action->getClasses()
                    ));
                    $this->ajaxSubmitID = $ajax->id();
                }
            }
        }

        $content = parent::renderFormFields($errors, $notSavedYet);

        if($this->getRequest()->canReplyJavaScript()) {
            $ajaxResponse = new AjaxResponse();

            $ajaxResponse->append("body", '<div style="display: none;" id="'.$this->ajaxSubmitID.'">'.$content.'</div>');
            $ajaxResponse->exec("gloader.loadAsync('dropdownDialog').done(function(){
                window['confirm_".$this->ajaxSubmitID."'] = new dropdownDialog('#".$this->ajaxSubmitID."', null, 'fixed', {
                    copyElement: false
                });
            });");

            return $ajaxResponse;
        }

        return $content;
    }

    /**
     * @return mixed
     */
    public function getAjaxElement()
    {
        return $this->ajaxElement;
    }

    /**
     * @param mixed $ajaxElement
     * @return $this
     */
    public function setAjaxElement($ajaxElement)
    {
        $this->ajaxElement = $ajaxElement;
        return $this;
    }
}
