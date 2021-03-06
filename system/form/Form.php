<?php
use Goma\Form\Exception\DuplicateActionException;

defined("IN_GOMA") OR die();

loadlang('form');

require_once (FRAMEWORK_ROOT . "form/FormField.php");
require_once (FRAMEWORK_ROOT . "libs/html/HTMLNode.php");
require_once(FRAMEWORK_ROOT . "form/actions/FormAction.php");
require_once (FRAMEWORK_ROOT . "form/Hiddenfield.php");

/**
 * The basic class for every Form in the Goma-Framework. It can have FormFields
 * in it.
 *
 * @package Goma\Form
 * @author Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @version 2.4.2
 *
 * @method enableActions
 * @method disableActions
 */
class Form extends AbstractFormComponentWithChildren {

	/**
	 * session-prefix for form.
	 */
	const SESSION_PREFIX = "form";
    const SESSION_STATE_PREFIX = "form_state_";
	const DEFAULT_SUBMSSION = "@default";

	/**
	 * you can use data-handlers, to edit data before it is given to the
	 * submission-method
	 */
	protected $dataHandlers = array();

	/**
	 * actions
	 * @var array
	 */
	public $actions = array();

	/**
	 * the form-tag
	 *
	 * @var HTMLNode
	 */
	public $form;

	/**
	 * default submission
	 * @var string
	 */
	protected $submission;

	/**
	 * controller of this form
	 *
	 * @var RequestHandler
	 */
	public $controller;

	/**
	 * form-secret-key
	 *
	 * @var string
	 */
	protected $secretKey;

	/**
	 * validators of the form
	 * @var FormValidator[]
	 */
	public $validators = array();

	/**
	 * result of the form
	 *
	 * @var array
	 */
	public $result = array();

	/**
	 * restore-class
	 */
	public $restorer;

	/**
	 * current state of this form
	 *
	 * @var FormState
	 */
	public $state;

	/**
	 * leave-check.
	 * @var bool
	 */
	protected $leaveCheck = true;

	/**
	 * session-manager.
	 *
	 * @var ISessionManager
	 */
	protected $session;

	/**
	 * submit-request.
	 */
	protected $submitRequest;

	/**
	 * @param RequestHandler|null $controller
	 * @param string|null $name
	 * @param array $fields
	 * @param array $actions
	 * @param Request|null $request
	 * @param array|ViewAccessableData|null $model
	 * @return Form
	 */
	public static function create($controller = null, $name = null, $fields = array(), $actions = array(), $request = null, $model = null) {
		return new Form($controller, $name, $fields, $actions, array(), $request, $model);
	}

	/**
	 * @param RequestHandler $controller
	 * @param string $name
	 * @param array $fields
	 * @param array $actions
	 * @param array $validators
	 * @param Request|null $request
	 * @param ViewAccessableData|null $model
	 * @param ISessionManager $session
	 */
	public function __construct($controller = null, $name = null, $fields = array(), $actions = array(), $validators = array(), $request = null, $model = null, $session = null) {

		parent::__construct(strtolower($name), $fields, $model);

		if(!isset($controller))
			return;

		if(PROFILE)
			Profiler::mark("form::__construct");

		$this->session = isset($session) ? $session : GlobalSessionManager::globalSession();
		$this->initWithRequest($controller, $request);

		$this->addFields(array(), $actions, $validators);

		$this->checkForRestore();

		// create form tag
		$this->form = $this->createFormTag();

		if(PROFILE)
			Profiler::unmark("form::__construct");
	}

	/**
	 * adds field to the form.
	 * @param FormField[] $fields
	 * @param FormAction[] $actions
	 * @param FormValidator[] $validators
	 */
	public function addFields($fields, $actions, $validators) {
		// register fields
		/** @var FormField $field */
		foreach($fields as $sort => $field) {
			$this->add($field);
		}

		// register actions
		/** @var FormAction $action */
		foreach($actions as $action) {
			$this->addAction($action);
		}

		foreach($validators as $key => $value) {
			$this->addValidator($value, $key);
		}
	}

	/**
	 * inits form with request.
	 *
	 * @param RequestHandler $controller
	 * @param Request $request
	 * @return string
	 */
	protected function initWithRequest($controller, $request) {
		if(!is_a($controller, "RequestHandler")) {
			throw new InvalidArgumentException('Controller "' . get_class($controller) . '" is not a request-handler.');
		}

		$this->controller = $controller;
		$this->request = isset($request) ? $request : $controller->getRequest();

		if(!$this->getRequest()) {
			$this->request = new Request(isset($_POST) ? "post" : "get", URL, $_GET, $_POST);
		}

		$this->url = $this->buildUrlFromRequest();

		if(isset($this->controller->originalNamespace) && $this->controller->originalNamespace) {
			$this->namespace = ROOT_PATH . BASE_SCRIPT . $this->controller->originalNamespace . "/forms/form/" . $this->name;
		} else {
			$this->namespace = ROOT_PATH . BASE_SCRIPT . "system/forms/" . $this->name;
		}
	}

	/**
	 * checks for form-restore and inits state.
	 */
	protected function checkForRestore() {
		// if we restore form
		if(
			$this->session->hasKey("form_restore." . $this->name()) &&
			$this->session->hasKey(self::SESSION_PREFIX . "." . strtolower($this->name))
		) {
			$data = $this->session->get(self::SESSION_PREFIX . "." . strtolower($this->name));
			$this->useStateData = $data->useStateData;
			$this->result = $data->result;
			$this->state = $data->state;
			$this->restorer = $data;

			if($data->secretKey) {
				$this->activateSecret($data->secretKey);
			}

			$this->session->remove("form_restore." . $this->name());
		} else {
			// get form-state
			if(!$this->checkForStateRestore()) {
				$this->state = new FormState();
				$this->activateSecret();
			}
		}
	}

	public function checkForStateRestore() {
		if($this->session->hasKey(self::SESSION_STATE_PREFIX . $this->name)) {
			$this->state = new FormState($this->session->get(self::SESSION_STATE_PREFIX . $this->name));
			$this->activateSecret($this->state->secret);
			return true;
		}
		return false;
	}

	/**
	 * creates the Form-Tag
	 */
	protected function createFormTag() {
		return new HTMLNode('form', array(
			'method' => 'post',
			'name' => $this->name(),
			'id' => $this->ID(),
			"class" => "form " . $this->name,
		));
	}

	/**
	 * activates restore for next generate
	 */
	public function activateRestore() {
		$this->session->set("form_restore." . $this->name, true);
	}

	/**
	 * disables restore for next generate
	 */
	public function disableRestore() {
		$this->session->remove("form_restore." . $this->name);
	}

	/**
	 * redirects to form
	 */
	public function redirectToForm() {
		$this->saveToSession();
		$this->activateRestore();
		HTTPResponse::redirect($this->url);
	}

	/**
	 * generates default fields for this form
	 */
	protected function defaultFields() {
		$this->add(new HiddenField("form_submit_" . $this->name(), "1"));

		Resources::add("system/form/form.js", "js", "tpl");

		if(!isset($this->fields["redirect"]))
			$this->add(new HiddenField("redirect", $this->controller->getRedirect($this)));
	}

	/**
	 * renders the form. This is done within GomaFormResponse.
	 *
	 * @return GomaResponse
	 */
	protected function submitOrRenderForm() {
		Resources::add("form.less", "css");

		$this->defaultFields();

		$this->form->html("");
		$notSavedYet = false;

		// check for submit or append info for user to resubmit.
		if((isset($this->getRequest()->post_params["form_submit_" . $this->name()]) &&
			$this->session->hasKey(self::SESSION_PREFIX . "." . strtolower($this->name)))) {

			// check secret
			if($this->secretKey && isset($this->getRequest()->post_params["secret_" . $this->ID()]) &&
				$this->getRequest()->post_params["secret_" . $this->ID()] == $this->state->secret) {
				return $this->trySubmit();
			} else if(!$this->secretKey) {
				return $this->trySubmit();
			} else {
				$notSavedYet = true;
			}
		} else if(isset($this->getRequest()->post_params["__form_step_done_" . $this->getName()])) {
			$this->submitRequest = $this->getRequest();

			if($data = $this->session->get(self::SESSION_PREFIX . "_multistep_" . $this->getName())) {
				$this->request = $data["request"];

				$response = $this->trySubmit();

				$this->request = $this->submitRequest;

				return $response;
			}
		}

		if($data = $this->checkForSubfield()) {
			return $data;
		}

		$this->session->remove("form_secrets." . $this->name());

		return $this->renderFormFields(array(), $notSavedYet);
	}

	/**
	 * @return GomaFormResponse
	 */
	public function render() {
		return new GomaFormResponse(null, $this);
	}

	/**
	 * @param string $view
	 * @param string|ViewAccessableData $model
	 * @param string $formName
	 * @param string|null $inExpansion
	 * @return GomaFormResponse
	 */
	public function renderWith($view, $model = null, $formName = "form", $inExpansion = null) {
		return GomaFormResponse::create(null, $this)->setRenderWith($view, $model, $formName, $inExpansion);
	}

	/**
	 * @param string $content
	 * @return GomaFormResponse
	 */
	public function renderPrependString($content) {
		return GomaFormResponse::create(null, $this)->prependContent($content);
	}

	/**
	 * checks for rendering of sub-field.
	 */
	protected function checkForSubfield() {
		// check get
		if(isset($this->request) && isset($this->request->get_params)) {
			foreach ($this->request->get_params as $key => $value) {
				if (preg_match("/^field_action_([a-zA-Z0-9_]+)_([a-zA-Z0-9_]+)$/", $key, $matches)) {
					if (isset($this->fields[$matches[1]]) && $this->fields[$matches[1]]->hasAction($matches[2])) {
						$this->activateRestore();
						if ($data = $this->session->get(self::SESSION_PREFIX . "." . strtolower($this->name))) {
							$this->result = $data->result;
							$this->post = $data->post;
							$this->restorer = $data;
						}

						return $this->fields[$matches[1]]->handleAction($matches[2]);
					}
				}
			}
		}

		return false;
	}

	/**
	 * renders the form
	 *
	 * @param array $errors
	 * @param bool $notSavedYet
	 * @return mixed|string
	 */
	protected function renderFormFields($errors = array(), $notSavedYet = false) {
		if($errors || $notSavedYet) {
			if($this->getRequest()->canReplyJavaScript()) {
				return $this->replyJSErrors($errors, $notSavedYet);
			}
		}

		$this->renderedFields = array();
		if(PROFILE)
			Profiler::mark("Form::renderForm");
		$this->callExtending("beforeRender");

		$this->form->action = $this->url;

		$fieldDataSet = new DataSet();
		$actionDataSet = new DataSet();

		$jsonData = array();

		$errorSet = $this->getErrorDataset($errors, $fieldErrors);
		$fields = $this->getFormFields($fieldErrors);
		$actions = $this->getActionFields();
		$validators = $this->getValidator($fields, $actions);

		/** @var FormFieldRenderData $field */
		foreach($fields as $field) {
			$fieldDataSet->add($field->ToRestArray(true, false));
			$jsonData[] = $field->ToRestArray();
		}

		/** @var FormFieldRenderData $action */
		foreach($actions as $action) {
			$actionDataSet->add($action->ToRestArray(true, false));
			$jsonData[] = $action->ToRestArray();
		}

		foreach($validators as $validator) {
			$jsonData[] = $validator;
		}

		$view = new ViewAccessableData();
		$view->fields = $fieldDataSet;
		$view->actions = $actionDataSet;

		$this->form->append($view->customise(array("errors" => new DataSet($errorSet), "showSavedYetIssue" => $notSavedYet))->renderWith("form/form.html"));

		$this->callExtending("afterRender");

		$this->form->id = $this->ID();

		if(PROFILE)
			Profiler::mark("Form::renderForm::render");

		$data = $this->form->render();
		$js = 'var form = new goma.form(' . var_export($this->ID(), true) . ', '.var_export($this->leaveCheck, true).', '.json_encode($jsonData).', '.json_encode($errorSet).');';
		if(count($errors) > 0) {
			$js .= "form.setLeaveCheck(true);";
		}
		Resources::addJS('$(function(){ '.$js.' });');

		if(PROFILE)
			Profiler::unmark("Form::renderForm::render");

		$this->session->set(self::SESSION_STATE_PREFIX . $this->name, $this->state->ToArray());

		$this->saveToSession();

		if(PROFILE)
			Profiler::unmark("Form::renderForm");

		return $data;
	}

	/**
	 * @param Exception[] $errors
	 * @param bool $notSavedYet
	 * @return FormAjaxResponse
	 */
	protected function replyJSErrors($errors, $notSavedYet) {
		$response = new FormAjaxResponse($this);
		$response->exec('$("#' . $this->form()->{"secret_" . $this->form()->id()}->id() . '").val("' . convert::raw2js($this->getSecretKey()) . '");');

		if($notSavedYet) {
			array_unshift($errors, new Exception(lang("form_not_saved_yet", "The Data hasn't saved yet.")));
		}

		$response = $this->addErrorsToJSONResponse($response, $errors);

		return $response;
	}

	/**
	 * @param FormAjaxResponse $response
	 * @param Exception[] $errors
	 * @internal
	 * @return FormAjaxResponse
	 */
	public function addErrorsToJSONResponse($response, $errors) {
		$response = clone $response;
		/** @var Exception $error */
		foreach ($errors as $error) {
			if (is_a($error, "FormMultiFieldInvalidDataException")) {
				/** @var FormMultiFieldInvalidDataException $error */
				foreach ($error->getFieldsMessages() as $field => $message) {
					if ($message) {
						$response->addError(
							str_replace('$title', $this->getTitleForFieldOrDefault($field), lang($message, $message))
						);
					}

					$response->addErrorField($field);
				}

                if(!$response->getErrorFields()) {
                    log_exception($error);
                }
			} else if (is_a($error, FormInvalidDataException::class)) {
				/** @var FormInvalidDataException $error */
				if ($error->getMessage()) {
					$response->addError(
					    $this->getFieldMessage($error, $error->getMessage(), $this->getTitleForFieldOrDefault($error->getField()))
					);
				}

				$response->addErrorField($error->getField());
			} else {
				log_exception($error);

				$response->addError($this->getFieldMessage($error, $error->getMessage()));
			}
		}

        if(!$response->getErrorFields() && !$response->getErrors()) {
            $response->addError("Unknown Errors Occurres. Please contact your developer.");
        }

		return $response;
	}

    /**
     * @param string $field
     * @param null|string $default
     * @return string
     */
    protected function getTitleForFieldOrDefault($field, $default = null) {
        return is_a($this->$field, AbstractFormComponent::class) ? $this->$field->getTitle() :
            (isset($default) ? $default : $field);
    }

	/**
	 * @param array $errors
	 * @param array $fieldErrors
	 * @return array
	 */
	protected function getErrorDataset($errors, &$fieldErrors) {
		$set = array();
		$fieldErrors = array();

		/** @var Exception $error */
		foreach($errors as $error) {
			if(is_a($error, "FormMultiFieldInvalidDataException")) {
				/** @var FormMultiFieldInvalidDataException $error */
				foreach($error->getFieldsMessages() as $field => $message) {
				    $fieldMessage = str_replace('$title', $this->getTitleForFieldOrDefault($field),
                        lang($message, $message)
                    );
					$set[] = array(
						"message" 	=> $fieldMessage,
						"field" 	=> $field,
						"type"		=> "FormInvalidDataException"
					);
				}
			} else if(is_a($error, "FormInvalidDataException")) {
				/** @var FormInvalidDataException $error */
				$set[] = array(
					"message" 	=> $this->getFieldMessage($error, $error->getMessage(), $this->getTitleForFieldOrDefault($error->getField())),
					"field" 	=> $error->getField(),
					"type"		=> "FormInvalidDataException"
				);
			} else {
                log_exception($error);

				$set[] = array(
					"message" 	=> $this->getFieldMessage($error, $error->getMessage()),
					"type"		=> get_class($error)
				);
			}
		}

		foreach($set as $error) {
			if(isset($error["field"])) {
				$field = strtolower($error["field"]);
				if(!isset($fieldErrors[$field])) {
					$fieldErrors[$field] = array($error);
				} else {
					$fieldErrors[$field][] = $error;
				}
			}
		}

		return $set;
	}

    /**
     * @param Exception $error
     * @param String $message
     * @param null|String $title
     * @return mixed|string
     */
	protected function getFieldMessage($error, $message, $title = null) {
	    $message = lang($message, $message);

	    if(isset($title)) {
	        $message = str_replace('$title', $title, $message);
        }

	    return $message ? $message : get_class($error) . ": $title <br />" . nl2br(convert::raw2text($error->getTraceAsString()));
    }

	protected function getFormFields($fieldErrors) {
		$fields = array();

		/** @var FormField $field */
		foreach($this->fieldList as $field) {
			try {
				if ($this->isFieldToRender($field->name)) {
					$this->registerRendered($field->name);

					$fields[] = $field->exportFieldInfo($fieldErrors);
				}
			} catch(Exception $e) {
				$fields[] = new FormFieldErrorRenderData($field->name, $e);
			}
		}

		return $fields;
	}

	protected function getActionFields() {
		$actions = array();

		/** @var array $action */
		foreach($this->actions as $action) {
			try {
				$actions[] = $action["field"]->exportFieldInfo();
			} catch(Exception $e) {
				$actions[] = new FormFieldErrorRenderData($action["field"]->name, $e);
			}
		}

		return $actions;
	}

	protected function getValidator(&$fields, &$actions) {
		$validators = array();

		/** @var FormValidator $validator */
		foreach($this->validators as $name => $validator) {
			try {
				$data = $validator->exportFieldInfo($fields, $actions);
				if($data) {
					$data["name"] = $name;
					$validators[] = $data;
				}
			} catch(Exception $e) {
				$validators[] = new FormFieldErrorRenderData($name, $e);
			}
		}

		return $validators;
	}

	/**
	 * sets the result
	 *
	 * @param array|ViewAccessableData $result
	 * @return bool
	 */
	public function setResult($result) {
		if(is_object($result)) {
			if(is_a($result, "viewaccessabledata")) {
				$this->useStateData = ($result->queryVersion == "state");
			}
		}

		if(is_object($result) || is_array($result)) {
			$this->result = $result;
			return true;
		}

		return false;
	}

    /**
     * Submits the form with the post-parameters provided.
     * This can be used to manually submit the form with some manual parameters.
     *
     * @param array $post_params
     * @return Form|GomaFormResponse|mixed|string
     */
	public function submitWithPostParamsAndThrow($post_params) {
	    $oldRequest = isset($this->request) ? clone $this->request : null;
	    if(isset($this->request)) {
	        $this->request->post_params = $post_params;
        } else {
	        $this->request = new Request("post", "", array(), $post_params);
        }

        try {
	        if(!$this->session->hasKey(self::SESSION_PREFIX . "." . strtolower($this->name))) {
	            $this->renderFormFields();
            }

            return $this->submitForm();
        } finally {
	        $this->request = $oldRequest;
        }
    }

    /**
     * Submits the form with given request-data.
     * This will throw an exception if something did not work out.
     *
     * @param bool $validateUrl
     * @return Form|GomaFormResponse|mixed|string
     * @throws FormNotSubmittedException
     */
    protected function submitForm($validateUrl = false) {
        /** @var Form $data */
        $data = $this->session->get(self::SESSION_PREFIX . "." . strtolower($this->name));
        if($validateUrl && $data->url != $this->url) {
            throw new FormNotSubmittedException("Form not submitted due to url mismatch.");
        }

        $data->request = $this->request;
        $data->submitRequest = $this->submitRequest;
        $data->secretKey = $this->secretKey;
        $data->state = $this->state;

        $this->session->set(self::SESSION_STATE_PREFIX . $this->name, $this->state->ToArray());

        $content = $data->handleSubmit();

        /** @var Form|GomaFormResponse $content */
        if(is_a($content, "Form")) {
            $content = $this->handleNextForm($content->render());
        } else if(is_a($content, "GomaFormResponse")) {
            /** @var GomaFormResponse $content */
            $content = $this->handleNextForm($content);
        }

        return $content;
    }

    /**
     * This method is called when a submission was detected.
     * It handles external field actions.
     * It recreated the secret-key if was set, since this should only called once per request.
     * It tries to submit the form with the submitForm-method and catches its exceptions.
     *
     * @return GomaFormResponse|mixed|string
     */
	public function trySubmit() {
		foreach($this->request->post_params as $key => $value) {
			if(preg_match("/^field_action_([a-zA-Z0-9_]+)_([a-zA-Z_0-9]+)$/", $key, $matches)) {
				if(isset($this->fields[$matches[1]]) && $this->fields[$matches[1]]->hasAction($matches[2])) {
					$this->activateRestore();
					return $this->fields[$matches[1]]->handleAction($matches[2]);
				}
			}
		}

		if($this->secretKey) {
			$this->activateSecret();
		}

		try {
			return $this->submitForm(true);
		} catch(Exception $e) {
			if (is_a($e, FormNotValidException::class)) {
				/** @var FormNotValidException $e */
				$errors = $e->getErrors();
			} else if (!is_a($e, FormNotSubmittedException::class) || $this->getRequest()->canReplyJavaScript()) {
				if(!is_a($e, FormInvalidDataException::class)) {
					log_exception($e);
				}

				$errors = array($e);
			} else {
				$errors = array();
			}

			$this->activatesecret();
			$this->session->set(self::SESSION_STATE_PREFIX . $this->name, $this->state->ToArray());

			return $this->renderFormFields($errors);
		}
	}

	/**
	 * @param GomaFormResponse $formResponse
	 * @return GomaFormResponse
	 */
	protected function handleNextForm($formResponse) {
		if($formResponse->getForm()->getName() == $this->getName()) {
			throw new InvalidArgumentException("Multi-Step-Forms require different names.");
		}

		if($formResponse->isRendered()) {
			throw new LogicException("You can't use multi step when form is already rendered.");
		}

		$formResponse->getForm()->add(new HiddenField("__form_step_done_" . $this->getName(), 1));

		$this->session->set(self::SESSION_PREFIX . "_multistep_" . $this->getName(), array(
			"request"	=> $this->request
		));

		if(isset($this->getRequest()->post_params["secret_" . $this->ID()])) {
			$this->activateSecret($this->getRequest()->post_params["secret_" . $this->ID()]);
		}

		return $formResponse;
	}

	/**
	 * gets the result of the form and submits it to
	 * - validators
	 * - data-handlers
	 * - gets submission
	 * - submission
	 * @return mixed|string
	 * @throws FormNotSubmittedException
	 * @throws FormNotValidException
	 */
	protected function handleSubmit() {
		if(!self::submissionPossible($this, $this->getRequest()->post_params)) {
			throw new FormNotSubmittedException();
		}

		$submissionWithoutValidation = self::findSubmission($this, $this->getRequest()->post_params, null);

		$result = $this->gatherResultForSubmit(is_null($submissionWithoutValidation));

		$submission = isset($submissionWithoutValidation) ?
			$submissionWithoutValidation :
			self::findSubmission($this, $this->getRequest()->post_params, $result);

		if(!$submission) {
			throw new FormNotSubmittedException();
		}

		$this->controller->setRequest($this->submitRequest ? $this->submitRequest : $this->getRequest());

		if(is_callable($submission) && !is_string($submission)) {
			return call_user_func_array($submission, array(
				$result,
				$this,
				$this->controller
			));
		} else {
			return call_user_func_array(array(
				$this->controller,
				$submission
			), array(
				$result,
				$this,
				$this->controller
			));
		}
	}

	/**
	 * @param bool $validate if to validate result
	 * @return array|mixed
	 * @throws FormNotValidException
	 */
	public function gatherResultForSubmit($validate = true) {
		$this->callExtending("beforeSubmit");

		$this->result = $result = $this->fetchResultWithDataHandlers();

		if($validate) {
			// validation
			$errors = array();

			foreach ($this->validators as $validator) {
				/** @var FormValidator $validator */
				$validator->setForm($this);
				try {
					$validator->validate();
				} catch (Exception $e) {
					$errors[] = $e;
				}
			}

			if (count($errors) > 0) {
				throw new FormNotValidException($errors);
			}
		}

		$this->callExtending("afterSubmit", $result);

		return $result;
	}

	/**
	 * @return array
	 */
	protected function fetchResultWithDataHandlers() {
		$result = array();

		// get data
		/** @var AbstractFormComponent $field */
		foreach($this->fieldList as $field) {
			if($field->name != "secret_" . $this->ID()) {
				$field->argumentResult($result);
			}
		}

		$this->callExtending("getResult", $result);

		foreach($this->getDataHandlers() as $callback) {
			$result = call_user_func_array($callback, array($result, $this));
		}

		return $result;
	}

	/**
	 * @param Form $form
	 * @param array $post
	 * @return bool
	 */
	protected static function submissionPossible($form, $post) {
		foreach($form->fields as $field) {
			if (is_a($field, "FormActionHandler")) {
				if (isset($post[$field->postname()]) ||
					(isset($post["default_submit"]) && !$field->input->hasClass("cancel") && !$field->input->name != "cancel")
				) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * finds a submission for the form. returns null when not found.
	 *
	 * @param Form $form
	 * @param array $post
	 * @param array $result
	 * @return null|string
	 * @throws FormNotValidException
	 */
	protected static function findSubmission($form, $post, $result) {
		$submission = null;
		// find actions in fields
		/** @var FormAction $field */
		foreach($form->fields as $field) {
			if(is_a($field, "FormActionHandler")) {
				if(isset($post[$field->postname()]) ||
					(isset($post["default_submit"]) && !$field->input->hasClass("cancel") && !$field->input->name != "cancel")) {
					if($field->canSubmit($result) && $submit = $field->__getSubmit($result)) {
						if($submit == self::DEFAULT_SUBMSSION) {
							$submission = $form->submission;
						} else {
							$submission = $submit;
						}

						if(!$submission) {
							throw new FormNotValidException("Neither your Form nor your FormAction defines a submission. Please define one.");
						}
						break;
					} else {
						return null;
					}
				}
			}
		}

		return $submission;
	}

	//! Manipulate the form
	/**
	 * you can use data-handlers, to edit data before it is given to the
	 * submission-method
	 * you give a callback and you get a result
	 *
	 *@name addDataHandler
	 *@access public
	 *@param callback
	 */
	public function addDataHandler($callback) {
		if(is_callable($callback)) {
			$this->dataHandlers[] = $callback;
		} else {
			throw new InvalidArgumentException("Argument 1 for Form::addDataHandler should be a valid callback.");
		}
	}

	/**
	 * @return array<Callback>
	 */
	public function getDataHandlers()
	{
		return $this->dataHandlers;
	}

	/**
	 * gets the default submission
	 */
	public function getSubmission() {
		return $this->submission;
	}

	/**
	 * @return RequestHandler
	 */
	public function getController()
	{
		return $this->controller;
	}

	/**
	 * sets the default submission
	 * @param string|array $submission
	 * @return $this
	 */
	public function setSubmission($submission) {
		if (is_callable($submission) || gObject::method_exists($this->controller, $submission)) {
			$this->submission = $submission;
			return $this;
		} else {
			throw new LogicException("Unknown Function '$submission'' for Controller {$this->controller->classname}.");
		}
	}

	/**
	 * adds an action
	 * @param FormAction $action
	 */
	public function addAction($action) {
	    if(isset($this->actions[$action->name])) {
	        throw new DuplicateActionException("Action is already existing in form. Please call removeAction first.");
        }

		$action->setForm($this);
		$this->actions[$action->name] = array(
			"field" => $action,
			"submit" => $action->getSubmit()
		);
	}

	/**
	 * removes an action
	 * @param FormAction|string $action
	 */
	public function removeAction($action) {
		if(is_object($action)) {
			$action = $action->name;
		}

		if (isset($this->fields[$action])) {
			unset($this->fields[$action]);
		}

		unset($this->actions[$action]);
	}

	/**
	 * adds a validator
	 *
	 * @param FormValidator $validator
	 * @param string $name
	 */
	public function addValidator($validator, $name) {
		if(is_string($validator) && is_object($name)) {
			$_name = $validator;
			$validator = $name;
			$name = $_name;
			unset($_name);
		}

		if(is_object($validator) && is_a($validator, "FormValidator") && isset($name)) {
			$this->validators[$name] = $validator;
			$validator->setForm($this);
		} else {
			throw new InvalidArgumentException("Form::addValidator - No Object or name given. First parameter needs to be object and second string.");
		}
	}

	/**
	 * removes an validator
	 *@name removeValidator
	 *@access public
	 */
	public function removeValidator($name) {
		unset($this->validators[$name]);
	}

	/**
	 * removes the secret key
	 * DON'T DO THIS IF YOU DON'T KNOW WHAT YOU DO!
	 */
	public function removeSecret() {
		$this->secretKey = null;
		$this->remove("secret_" . $this->ID());
		$this->state->secret = null;
	}

	/**
	 * activates the secret key
	 * @param string|null $secret
	 */
	public function activateSecret($secret = null) {
		if($this->secretKey) $this->removeSecret();

		$this->secretKey = is_string($secret) ? $secret : randomString(30);
		$this->add(new HiddenField("secret_" . $this->ID(), $this->secretKey));
		$this->state->secret = $this->secretKey;
	}

	/**
	 * gets the secret
	 *
	 * @return bool
	 */
	public function getSecret() {
		return !!$this->secretKey;
	}


	//!Mostly internal APIs
	/**
	 * saves current form to session
	 */
	public function saveToSession() {
		$this->session->set(self::SESSION_PREFIX . "." . strtolower($this->name), $this);
	}

	/**
	 * genrates an id for this form
	 */
	public function ID() {
		return "form_" . md5($this->name);
	}

	/**
	 * generates an name for this form
	 */
	public function name() {
		return $this->name;
	}

	/**
	 * @return bool
	 */
	public function getLeaveCheck()
	{
		return $this->leaveCheck;
	}

	/**
	 * @param bool $leaveCheck
	 * @return $this
	 */
	public function setLeaveCheck($leaveCheck)
	{
		$this->leaveCheck = $leaveCheck;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getSecretKey()
	{
		return $this->secretKey;
	}

	public function field($info)
	{
		throw new InvalidArgumentException("Can't add Form to a Form below.");
	}

	public function js()
	{
		// TODO: Implement js() method.
	}
}
