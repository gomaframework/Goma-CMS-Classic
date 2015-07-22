<?php
defined("IN_GOMA") OR die();

loadlang('form');

require_once (FRAMEWORK_ROOT . "form/FormField.php");
require_once (FRAMEWORK_ROOT . "libs/html/HTMLNode.php");
require_once (FRAMEWORK_ROOT . "form/FormAction.php");
require_once (FRAMEWORK_ROOT . "form/Hiddenfield.php");

/**
 * The basic class for every Form in the Goma-Framework. It can have FormFields
 * in it.
 *
 * @package Goma\Form
 * @author Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @version 2.4.1
 */
class Form extends object {
	/**
	 * name of the form
	 *@name name
	 *@access protected
	 *@var string
	 */
	protected $name;

	/**
	 * you can use data-handlers, to edit data before it is given to the
	 * submission-method
	 *
	 *@name dataHandlers
	 *@access public
	 */
	public $dataHandlers = array();

	/**
	 * all available fields in this form
	 *
	 *@name fields
	 *@access public
	 *@var array
	 */
	public $fields = array();

	/**
	 * already rendered fields
	 *
	 *@name renderedFields
	 *@access public
	 */
	public $renderedFields = array();

	/**
	 * all fields the form has to generate from this object
	 *@name showFields
	 *@access public
	 *@var arrayList
	 */
	public $showFields;

	public $fieldSort;

	public $fieldList;

	/**
	 * actions
	 *@name actions
	 *@access public
	 *@var array
	 */
	public $actions = array();

	/**
	 * the form-tag
	 *@name form
	 *@access public
	 */
	public $form;

	/**
	 * default submission
	 *@name submission
	 *@access protected
	 *@var string
	 */
	protected $submission;

	/**
	 * controller of this form
	 *@name controller
	 *@access public
	 *@var object
	 */
	public $controller;

	/**
	 * the model, which belongs to this form
	 *
	 *@name model
	 *@access public
	 */
	public $model;

	/**
	 * form-secret-key
	 *@name secretKey
	 *@access public
	 */
	protected $secretKey;

	/**
	 * validators of the form
	 *@name validators
	 *@access public
	 *@var array
	 */
	public $validators = array();

	/**
	 * result of the form
	 *@name result
	 *@access public
	 *@var array
	 */
	public $result = array();

	/**
	 * if we add secret key to the form
	 *@name secret
	 *@access public
	 *@var bool
	 */
	protected $secret = true;

	/**
	 * url of this form
	 *
	 *@name url
	 *@access public
	 */
	public $url;

	/**
	 * post-data
	 *
	 *@name post
	 *@access public
	 */
	public $post;

	/**
	 * restore-class
	 *
	 *@name restorer
	 *@access public
	 */
	public $restorer;

	/**
	 * defines if we should use state-data in sub-queries of this Form
	 *
	 *@name useStateData
	 *@access public
	 */
	public $useStateData = false;

	/**
	 * current state of this form
	 *
	 *@name state
	 *@access public
	 */
	public $state;

	/**
	 * request
	 *
	 *@name request
	 */
	public $request;

	/**
	 * @var bool
	 */
	public $disabled = false;


	/**
	 * @param RequestHandler $controller
	 * @param string $name
	 * @param array $fields
	 * @param array $actions
	 * @param array $validators
	 * @param Request|null $request
	 * @param ViewAccessableData|null $model
     */
	public function __construct(RequestHandler $controller, $name, $fields = array(), $actions = array(), $validators = array(), $request = null, $model = null) {

		parent::__construct();

		if(PROFILE)
			Profiler::mark("form::__construct");

		$this->name = $name;

		$this->initWithRequest($controller, $request);

		$this->initModel($controller, $model);

		$this->checkForRestore();

		//$this->showFields = array();
		$this->fieldList = new ArrayList();

		$this->addFields($fields, $actions, $validators);

		// create form tag
		$this->form = $this->createFormTag();

		if(PROFILE)
			Profiler::unmark("form::__construct");
	}

	/**
	 * adds field to the form.
	 */
	public function addFields($fields, $actions, $validators) {
		// register fields
		/** @var FormField $field */
		foreach($fields as $sort => $field) {
			$this->fieldList->push($field);
			$field->setForm($this);
		}

		// register actions
		/** @var FormAction $action */
		foreach($actions as $submit => $action) {
			$action->setForm($this);
			$this->actions[$action->name] = array(
				"field" => $action,
				"submit" => $action->getSubmit()
			);
		}

		$this->validators = array_merge($this->validators, (array) $validators);
	}

	/**
	 * inits form with request.
	 *
	 * @param RequestHandler $controller
	 * @param Request $request
	 */
	protected function initWithRequest($controller, $request) {
		if(!is_a($controller, "RequestHandler")) {
			throw new InvalidArgumentException('Controller "' . get_class($controller) . '" is not a request-handler.');
		}

		$this->controller = $controller;

		$this->secretKey = randomString(30);
		$this->url = str_replace('"', '', $_SERVER["REQUEST_URI"]);
		$this->request = isset($request) ? $request : $controller->getRequest();

		if(isset($this->request)) {
			$this->post = $request->post_params;
		} else {
			$this->post = $_POST;
		}
	}

	/**
	 * inits model.
	 *
	 * @param Controller $controller
	 * @param ViewAccessableData|null $model
	 */
	protected function initModel($controller, $model) {
		// set model
		if(isset($model)) {
			$this->model = $model;
		} else if(Object::method_exists($controller, "modelInst") && $controller->modelInst()) {
			$this->model = $controller->modelInst();
		}
	}

	/**
	 * checks for form-restore and inits state.
	 */
	protected function checkForRestore() {
		// if we restore form
		if(isset($_SESSION["form_restore_" . $this->name]) && session_store_exists("form_" . strtolower($this->name))) {
			$data = session_restore("form_" . strtolower($this->name));
			$this->useStateData = $data->useStateData;
			$this->result = $data->result;
			$this->post = $data->post;
			$this->state = $data->state;
			$this->restorer = $data;
			unset($_SESSION["form_restore_" . $this->name]);
		}

		// get form-state
		if(session_store_exists("form_state_" . $this->name) && isset($this->post)) {
			$this->state = new FormState(session_restore("form_state_" . $this->name));
		} else {
			$this->state = new FormState();
		}
	}

	/**
	 * creates the Form-Tag
	 */
	protected function createFormTag() {
		return new HTMLNode('form', array(
			'method' => 'post',
			'name' => $this->name(),
			'id' => $this->ID(),
			"class" => "form " . $this->name
		));
	}

	/**
	 * activates restore for next generate
	 *
	 *@name activateRestore
	 *@access public
	 */
	public function activateRestore() {
		$_SESSION["form_restore_" . $this->name] = true;
	}

	/**
	 * disables restore for next generate
	 *
	 *@name disableRestore
	 *@access public
	 */
	public function disableRestore() {
		unset($_SESSION["form_restore_" . $this->name]);
	}

	/**
	 * redirects to form
	 *
	 *@name redirectToForm
	 *@access public
	 */
	public function redirectToForm() {

		$this->saveToSession();
		$this->activateRestore();
		HTTPResponse::redirect($this->url);
	}

	/**
	 * generates default fields for this form
	 *@name defaultFields
	 *@access public
	 */
	public function defaultFields() {
		if($this->secret) {
			$this->add(new HiddenField("secret_" . $this->ID(), $this->secretKey));
			$this->state->secret = $this->secretKey;
		}

		$this->add(new HiddenField("form_submit_" . $this->name(), "1"));
		// add that this is a submit-function
		$this->add(new JavaScriptField("leave_check", '$(function(){new goma.form(' . var_export($this->ID(), true) . '); });'));

		Resources::add("system/form/form.js", "js", "tpl");

		if(!isset($this->fields["redirect"]))
			$this->add(new HiddenField("redirect", getredirect()));
	}

	/**
	 * renders the form
	 *
	 * @return mixed|string
	 */
	public function render() {
		if($this->controller && isset($this->controller->request) && is_a($this->controller->request, "Request")) {
			$params = array_values($this->controller->request->params);
			
			// just watch out for external-form.
			if(count($this->controller->request->url_parts) > 2 && strtolower($this->controller->request->url_parts[0]) == "forms" && strtolower($this->controller->request->url_parts[1]) == strtolower($this->name)) {
				$request = $this->controller->request;
				$request->params = array("form" => strtolower($request->url_parts[1]), "field" => strtolower($request->url_parts[2]));
				$request->shift(3);
				$externForm = new ExternalFormController();
				Core::serve($externForm->handleRequest($request));
			} else if(count($this->controller->request->url_parts) > 1 && strtolower($this->controller->request->url_parts[0]) == strtolower($this->name) && $params[count($params) - 1] == "forms") {
				$request = $this->controller->request;
				$request->params = array("form" => strtolower($request->url_parts[0]), "field" => strtolower($request->url_parts[1]));
				$request->shift(2);
				$externForm = new ExternalFormController();
				Core::serve($externForm->handleRequest($request));
			}
		}
	
		Resources::add("form.css", "css");
		if(isset($_POST["form_submit_" . $this->name()]) && session_store_exists("form_" . strtolower($this->name))) {
			// check secret
			if($this->secret && $_POST["secret_" . $this->ID()] == $this->state->secret) {
				$this->defaultFields();
				return $this->submit();
			} else if(!$this->secret) {
				$this->defaultFields();
				return $this->submit();
			} else {
				$this->form->append(new HTMLNode("div", array("class" => "notice", ), lang("form_not_saved_yet", "The Data hasn't saved yet.")));
			}
		}

		unset($_SESSION["form_secrets"][$this->name()]);
		$this->defaultFields();
		return $this->renderForm();
	}

	/**
	 * renders the form
	 *
	 * @name renderForm
	 * @access public
	 * @return mixed|string
	 */
	public function renderForm() {
		$this->renderedFields = array();
		if(PROFILE)
			Profiler::mark("Form::renderForm");
		$this->callExtending("beforeRender");

		// check get
		foreach($_GET as $key => $value) {
			if(preg_match("/^field_action_([a-zA-Z0-9_]+)_([a-zA-Z0-9_]+)$/", $key, $matches)) {

				if(isset($this->fields[$matches[1]]) && $this->fields[$matches[1]]->hasAction($matches[2])) {
					$this->activateRestore();
					if(session_store_exists("form_" . strtolower($this->name))) {
						$data = session_restore("form_" . strtolower($this->name));
						$this->result = $data->result;
						$this->post = $data->post;
						$this->restorer = $data;
					}
					return $this->fields[$matches[1]]->handleAction($matches[2]);
				}
			}
		}

		//$this->saveToSession();

		$this->form->action = $this->url;

		$this->form->append('<input type="submit" name="default_submit" value="" class="default_submit" style="position: absolute;bottom: 0px;right: 0px;height: 0px !important;width:0px !important;background: transparent;color: transparent;border: none;-webkit-box-shadow: none;box-shadow:none;-moz-box-shadow:none;outline: 0;padding: 0;margin:0;" />');

		// first we have to sort the fields
		//usort($this->showFields, array($this, "sort"));
		$i = 0;

		$fields = "";

		foreach($this->fieldList as $field) {
			$name = strtolower($field->name);
			if($this->isFieldToRender($field->name)) {
				$this->registerRendered($field->name);
				$div = $field->field();
				if(is_object($div) && !$div->hasClass("hidden")) {
					if($i == 0) {
						$i++;
						$div->addClass("one");
					} else {
						$i = 0;
						$div->addClass("two");
					}
					$div->addClass("visibleField");
				}
				$fields .= $div;
			}
		}

		unset($field);

		$i = 0;
		$actions = "";
		foreach($this->actions as $action) {
			$field = $action["field"];
			$container = $field->field();
			if($i == 0) {
				$i++;
				$container->addClass("action_one");
			} else {
				$i = 0;
				$container->addClass("action_two");
			}
			$actions .= $container;
		}

		unset($div, $i, $container);

		// javascript
		$js = '(function($){
						$(function(){ 
							$("#form_' . $this->form->name . ' .err").remove(); 
							$("#form_' . $this->form->name . '").bind("formsubmit", function() {
								$("#form_' . $this->form->name . ' .err").remove();
							});
						 });';

		foreach($this->fields as $field) {
			$js .= $field->JS();
		}

		foreach($this->validators as $validator) {
			if(is_object($validator)) {
				$validator->setForm($this);
				$js .= $validator->JS();
			}
		}

		$js .= "})(jQuery);";

		$view = new ViewAccessableData();
		$view->fields = $fields;
		$view->actions = $actions;

		$this->form->append($view->renderWith("form/form.html"));

		$this->callExtending("afterRender");

		$this->form->id = $this->ID();

		if(PROFILE)
			Profiler::mark("Form::renderForm::render");
		$data = $this->form->render("          ");
		Resources::addJS($js);
		if(PROFILE)
			Profiler::unmark("Form::renderForm::render");

		session_store("form_state_" . $this->name, $this->state->ToArray());

		$this->saveToSession();

		if(PROFILE)
			Profiler::unmark("Form::renderForm");

		return $data;

	}

	/**
	 * sets the result
	 *
	 *@name setResult
	 *@access public
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
	 * submission
	 *@name submit
	 *@access public
	 */
	public function submit() {
		$this->callExtending("beforeSubmit");

		$this->post = $_POST;

		$_SESSION["form_secrets"] = array();

		$i = 0;

		foreach($this->post as $key => $value) {
			if(preg_match("/^field_action_([a-zA-Z0-9_]+)_([a-zA-Z_0-9]+)$/", $key, $matches)) {
				if(isset($this->fields[$matches[1]]) && $this->fields[$matches[1]]->hasAction($matches[2])) {
					$this->activateRestore();
					return $this->fields[$matches[1]]->handleAction($matches[2]);
				}
			}
		}

		$data = session_restore("form_" . strtolower($this->name));
		$data->post = $this->post;

		// just write it
		$this->saveToSession();

		$allowed_result = array();
		$this->result = array();
		// reset result

		// get data
		foreach($data->fields as $field) {
			$result = $field->result();

			if($result !== null) {
				$this->result[$field->dbname] = $result;
				$allowed_result[$field->dbname] = true;
			}
		}

		// validation
		$valid = true;
		$errors = new HTMLNode('div', array('class' => "error"), array(new HTMLNode('ul', array())));

		$data->result = $this->result;

		foreach($data->validators as $validator) {
			$validator->setForm($data);
			$v = $validator->validate();
			if($v !== true) {
				$valid = false;
				$errors->getNode(0)->append(new HTMLNode('li', array('class' => 'erroritem'), $v));
			}
		}

		$result = $this->result;
		if(is_object($result) && is_subclass_of($result, "dataobject")) {
			$result = $result->to_array();

		}

		// validate result
		$realresult = array();
		// now check which fields has edited
		foreach($result as $key => $value) {
			if(isset($allowed_result[$key])) {
				$realresult[$key] = $value;
			}
		}

		$data->callExtending("getResult", $realresult);

		$result = $realresult;
		unset($realresult, $allowed_result);

		foreach($data->dataHandlers as $callback) {
			$result = call_user_func_array($callback, array($result));
		}

		// find actions in fields
		foreach($data->fields as $field) {
			if(is_a($field, "FormActionHandler")) {
				if(isset($_POST[$field->postname()]) || (isset($_POST["default_submit"]) && !$field->input->hasClass("cancel") && !$field->input->name != "cancel")) {
					$i++;
					if($field->canSubmit($result) && $submit = $field->getSubmit($result)) {
						if($submit == "@default") {
							$submission = $this->submission;
						} else {
							$submission = $submit;
						}
						break;
					} else {
						$this->state = $data->state;
						$this->defaultFields();
						return $this->renderForm();
					}
				}
			}
		}

		if($valid !== true) {
			$_SESSION["form_secrets"][$this->name()] = $this->__get("secret_" . $this->ID())->value;
			$this->form->append($errors);
			return $this->renderForm();
		}

		// no registered action has submitted the form
		if($i == 0) {
			$this->state = $data->state;
			$this->defaultFields();
			return $this->renderForm();
		}

		$data->callExtending("afterSubmit", $result);

		session_store("form_state_" . $this->name, $this->state->ToArray());

		if(is_callable($submission)) {
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
		if(is_callable($callback))
			$this->dataHandlers[] = $callback;
		else
			throwError(6, "Invalid Argument", "Argument 1 for Form::addDataHandler should be a valid callback.");
	}

	/**
	 * gets the default submission
	 *@name getSubmission
	 *@access public
	 */
	public function getSubmission() {
		return $this->submission;
	}

    /**
     * returns name.
     */
    public function getName() {
        return $this->name;
    }

	/**
	 * sets the default submission
	 *@name setSubmission
	 *@access public
	 */
	public function setSubmission($submission) {
		if($submission)
			if(Object::method_exists($this->controller, $submission)) {
				$this->submission = $submission;
			} else {
				throwError('6', 'Logical Exception', 'Unknowen function "' . $submission . '" for Controller ' . get_class($this->controller) . '. Please create function and run dev.');
			}
	}

	/**
	 * removes a field
	 *@name remove
	 *@access public
	 */
	public function remove($field) {
		if(isset($this->fields[$field])) {
			unset($this->fields[$field]);
		}

		if(is_string($field)) {
			$this->fieldList->remove($this->fieldList->find("name", $field, true));
		}

		if(isset($this->actions[$field])) {
			unset($this->actions[$field]);
		}

		foreach($this->fieldList as $_field) {
			if(is_subclass_of($_field, "FieldSet")) {
				$_field->remove($field);
			}
		}
	}

	/**
	 * adds a field.
	 *
	 * @param 	FormField $field
	 * @param 	integer $sort sort, 0 is on top, and count means after which field the
	 * field is rendered, null means default
	 * @param 	String $to where the field is added, for example as a subfield to a
	 * tab
	 */
	public function add($field, $sort = null, $to = null) {
		if($to == "this" || !isset($to)) {

			// if it already exists, we should remove it.
			if($this->fieldList->find("name", $field->name)) {
				$this->fieldList->remove($this->fieldList->find("name", $field->name));
			}

			if(isset($sort))
				$this->fieldList->move($field, $sort, true);
			else
				$this->fieldList->add($field);

			$field->setForm($this);
		} else {
			if(isset($this->$to)) {
				$this->$to->add($field, $sort);
			}

		}
	}

	/**
	 * adds a field. alias to @see Form::add.
	 */
	public function addField($field, $sort = null, $to = null) {
		return $this->add($field, $sort, $to);
	}

	/**
	 * adds a field to a given fieldset.
	 *
	 * @param 	String $fieldname fieldset
	 * @param 	FormField $field the field
	 * @param 	int $sort
	 */
	public function addToField($fieldname, $field, $sort = 0) {
		return $this->add($field, $sort, $fieldname);
	}

	/**
	 * adds an action
	 *@name addAction
	 *@access public
	 */
	public function addAction($action) {
		$action->setForm($this);
		$this->actions[$action->name] = array(
			"field" => $action,
			"submit" => $action->getSubmit()
		);
	}

	/**
	 * removes an action
	 *@name removeAction
	 *@access public
	 */
	public function removeAction($action) {
		if(is_object($action)) {
			$action = $action->name;
		}

		unset($this->actions[$action]);
	}

	/**
	 * adds a validator
	 *@name addValidator
	 *@access public
	 */
	public function addValidator($validator, $name) {
		if(is_string($validator) && is_object($name)) {
			$_name = $validator;
			$validator = $name;
			$name = $_name;
			unset($_name);
		}

		if(is_object($validator) && isset($name)) {
			$this->validators[$name] = $validator;
			$validator->setForm($this);
		} else {
			throwError(6, "Invalid Argument", "Form::addValidator - No Object or name given. First parameter needs to be object and second string.");
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
	 *@name removeSecret
	 *@acess public
	 */
	public function removeSecret() {
		$this->secret = false;
	}

	/**
	 * activates the secret key
	 *
	 *@name activateSecret
	 *@acess public
	 */
	public function activateSecret() {
		$this->secret = true;
	}

	/**
	 * gets the secret
	 *
	 * @name getSecret
	 * @access public
	 * @return string
	 */
	public function getSecret() {
		return $this->secret;
	}

	/**
	 * gets the field by the given name or returns null.
	 *
	 * @param string $name
	 * @return FormField|null
	 */
	public function getField($name) {

		return (isset($this->fields[strtolower($name)])) ? $this->fields[strtolower($name)] : null;
	}

	/**
	 * returns if a field exists in this form
	 *
	 * @param string $name
	 * @return bool
	 */
	public function hasField($name) {
		return (isset($this->fields[strtolower($name)]));
	}

	/**
	 * returns if a field exists and wasn't rendered in this form
	 *
	 * @name isField
	 * @access public
	 * @return bool
	 */
	public function isFieldToRender($name) {
		return ((isset($this->fields[strtolower($name)])) && !isset($this->renderedFields[strtolower($name)]));
	}

	/**
	 * registers a field in this form
	 *
	 * @param string $name
	 * @param FormField $field
	 */
	public function registerField($name, $field) {
		$this->fields[strtolower($name)] = $field;
	}

	/**
	 * unregisters the field from this form
	 * this means that the field will not be rendered
	 *
	 * @param string $name
	 */
	public function unRegister($name) {
		unset($this->fields[strtolower($name)]);
	}

	/**
	 * registers the field as rendered
	 *
	 * @param string $name
	 */
	public function registerRendered($name) {
		$this->renderedFields[strtolower($name)] = true;
	}

	/**
	 * removes the registration as rendered
	 *
	 *@name unregisterRendered
	 *@access public
	 *@param string - name
	 */
	public function unregisterRendered($name) {
		unset($this->renderedFields[strtolower($name)]);
	}

    /**
     * unregisters a field.
     *
     * @param string name
     * @return void
     */
    public function unregisterField($name) {
        if(isset($this->fields[$name])) {
            unset($this->fields[$name]);
        }
    }

	//!Overloading
	/**
	 * Overloading
	 */

	/**
	 * returns a field in this form by name
	 * it's not relevant how deep the field is in this form if the field is *not*
	 * within a ClusterFormField
	 *
	 *@name __get
	 *@access public
	 */
	public function __get($offset) {
		return $this->getField($offset);
	}

	/**
	 * currently set doesn't do anything
	 *
	 *@name __set
	 *@access public
	 */
	public function __set($offset, $value) {
		// currently there is no option to overload a form with fields
	}

	/**
	 * returns if a field exists in this form
	 *
	 * @param string $offset
	 * @return bool
	 */
	public function __isset($offset) {
		return $this->hasField($offset);
	}

	/**
	 * removes a field from this form
	 *
	 *@name __unset
	 *@access public
	 */
	public function __unset($offset) {
		unset($this->fields[$offset]);
	}

	//!Mostly internal APIs
	/**
	 * saves current form to session
	 */
	public function saveToSession() {
		session_store("form_" . strtolower($this->name), $this);
	}

	/**
	 * external url of this form
	 *
	 *@name externalURL
	 *@access public
	 */
	public function externalURL() {
		if(isset($this->controller->originalNamespace) && $this->controller->originalNamespace) {
			return ROOT_PATH . BASE_SCRIPT . $this->controller->originalNamespace . "/forms/form/" . $this->name;
		} else {
			return ROOT_PATH . BASE_SCRIPT . "system/forms/" . $this->name;
		}
	}

	/**
	 * sorts the items
	 *@name sort
	 *@access public
	 */
	public function sort($a, $b) {
		if($this->fieldSort[$a->name] == $this->fieldSort[$b->name]) {
			return 0;
		}

		return ($this->fieldSort[$a->name] > $this->fieldSort[$b->name]) ? 1 : -1;
	}

	/**
	 * returns the current real form-object
	 *@name form
	 *@access public
	 */
	public function & form() {
		return $this;
	}



	/**
	 * genrates an id for this form
	 *@name ID
	 *@access public
	 */
	public function ID() {
		return "form_" . md5($this->name);
	}

	/**
	 * generates an name for this form
	 *@name name
	 *@access public
	 */
	public function name() {
		return $this->name;
	}

	public function __wakeup() {
		parent::__wakeup();
		
		foreach($this->fields as $f) {
			if(is_object($f)) {
				$f->__wakeup();
			}
		}
		
		foreach($this->actions as $f) {
			if(is_object($f)) {
				$f->__wakeup();
			}
			
		}
		
		foreach($this->validators as $v) {
			if(is_object($f)) {
				$v->__wakeup();
			}
		}
		
		if($this->controller) {
			if(is_object($this->controller)) {
				$this->controller->__wakeup();
			}
		}
	}

}