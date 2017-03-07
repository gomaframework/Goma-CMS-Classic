<?php
defined("IN_GOMA") OR die();

/**
 * the basic class for each goma-controller, which handles models.
 *
 * @author        Goma-Team
 * @license        GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @package        Goma\Controller
 * @version        2.3.6
 */
class Controller extends RequestHandler
{
    /**
     * showform if no edit right
     *
     * @var bool
     * @default false
     */
    public static $showWithoutRight = false;

    /**
     * activates the live-counter on this controller
     *
     * @var bool
     */
    protected static $live_counter = false;

    /**
     * allowed actions
     */
    public $allowed_actions = array(
        "edit",
        "delete",
        "record",
        "version"
    );

    /**
     * template for this controller
     */
    public $template = "";

    /**
     * some vars for the template
     */
    public $tplVars = array();

    /**
     * url-handlers
     */
    public $url_handlers = array(
        '$Action/$id' => '$Action',
    );

    /**
     * @var Keychain
     */
    protected $keychain;

    /**
     * @var \Goma\Service\DefaultControllerService
     */
    protected $service;

    /**
     * Controller constructor.
     * @param \Goma\Service\DefaultControllerService $service
     * @param KeyChain $keychain
     */
    public function __construct($service = null, $keychain = null)
    {
        parent::__construct();

        $this->keychain = $keychain;
        $this->initService($service);
    }

    /**
     * @param ViewAccessableData $model
     * @return static
     */
    public static function InitWithModel($model) {
        if(isset($this)) {
            throw new InvalidArgumentException();
        }

        $controller = new static();
        $controller->setModelInst($model);

        return $controller;
    }

    /**
     * inits the controller:
     * - determining and loading model
     * - checking template
     * @param Request $request
     * @return $this
     */
    public function Init($request = null)
    {
        parent::Init($request);

        if ($this->template == "") {
            $this->template = $this->model() . ".html";
        }

        if(!$this->subController) {
            if (static::$live_counter) {
                // run the livecounter (statistics), just if it is activated or the visitor wasn't tracked already
                if (PROFILE) Profiler::mark("livecounter");
                livecounter::run();
                if (PROFILE) Profiler::unmark("livecounter");

                GlobalSessionManager::globalSession()->set(livecounter::SESSION_USER_COUNTED, TIME);
            }

            if ($title = $this->PageTitle()) {
                Core::setTitle($title);
                Core::addBreadCrumb($title, $this->namespace . URLEND);
            }
        }

        if (StaticsManager::hasStatic($this->classname, "less_vars")) {
            Resources::$lessVars = StaticsManager::getStatic($this->classname, "less_vars");
        }

        return $this;
    }

    /**
     * @param \Goma\Service\DefaultControllerService $service
     * @return \Goma\Service\DefaultControllerService|null
     */
    public function initService($service = null) {
        if(isset($service)) {
            if(!is_a($service, \Goma\Service\DefaultControllerService::class)) {
                throw new InvalidArgumentException();
            }

            $this->service = $service;
        }

        return $this->service;
    }

    /**
     * @param ViewAccessableData|null $model
     * @return \Goma\Service\DefaultControllerService
     */
    protected function defaultService($model = null) {
        return new \Goma\Service\DefaultControllerService(
            $this->guessModel($model)
        );
    }

    /**
     * @return \Goma\Service\DefaultControllerService
     */
    public function service() {
        return isset($this->service) ? $this->service : $this->initService($this->defaultService());
    }

    /**
     * if this method returns a title automatic title and breadcrumb will be set
     */
    public function PageTitle()
    {
        return null;
    }

    /**
     * sets the model.
     * @param ViewAccessableData $model
     * @return $this
     */
    public function setModelInst($model)
    {
        if(!$this->service) {
            $this->service = $this->defaultService($model);
        }

        $this->service()->setModel($model);
        return $this;
    }

    /**
     * returns the model-object
     *
     * @return ViewAccessableData|IDataSet
     */
    public function modelInst()
    {
        return $this->service()->getModel();
    }

    /**
     * @return DataObject|ViewAccessableData
     */
    protected function getSingleModel() {
        return $this->service()->getSingleModel($this->getParam("id"));
    }

    /**
     * @param ViewAccessableData|null $model
     * @return IDataSet|ViewAccessableData
     */
    protected function guessModel($model = null) {
        if(isset($model) && is_a($model, ViewAccessableData::class)) {
            return $model;
        }

        if($model = StaticsManager::getStatic($this, "model", true)) {
            if ($modelObject = $this->createDefaultSetFromModel($model)) {
                return $modelObject;
            }
        }

        if($modelObject = $this->createDefaultSetFromModel(substr($this->classname, 0, -10))) {
            return $modelObject;
        }

        if($modelObject = $this->createDefaultSetFromModel(substr($this->classname, 0, -11))) {
            return $modelObject;
        }

        $cleanFilename = substr($this->classname, strrpos($this->classname, "\\"));
        if($modelObject = $this->createDefaultSetFromModel(substr($cleanFilename, 0, -10))) {
            return $modelObject;
        }

        if($modelObject = $this->createDefaultSetFromModel(substr($cleanFilename, 0, -11))) {
            return $modelObject;
        }

        return new ViewAccessableData();
    }

    /**
     * @param string $model
     * @return ViewAccessableData|IDataSet
     */
    protected function createDefaultSetFromModel($model) {
        if(isset($model) && ClassInfo::exists($model)) {
            if(ClassInfo::hasInterface($model, "IDataObjectSetDataSource")) {
                return DataObject::get($model);
            } else if(is_subclass_of($model, "ViewAccessableData")) {
                return gObject::instance($model);
            }
        }

        return null;
    }

    /**
     * returns the controller-model
     *
     * @return null|string
     */
    public function model()
    {
        return $this->service()->getModel()->DataClass();
    }

    /**
     * gets this class with new model inst.
     * @param ViewAccessableData $model
     * @return Controller
     */
    public function getWithModel($model) {
        $class = clone $this;
        $class->service()->setModel($model);

        return $class;
    }

    /**
     * handles requests
     *
     * @param Request $request
     * @param bool $subController
     * @return false|mixed|null|string
     * @throws Exception
     */
    public function handleRequest($request, $subController = false)
    {
        try {
            return $this->__output(parent::handleRequest($request, $subController));
        } catch(Exception $e) {
            if($subController) throw $e;

            return $this->handleException($e);
        }
    }

    /**
     * output-layer
     * @param string|GomaResponse $content
     * @return string|GomaResponse
     */
    public function __output($content) {
        /** @var ControllerRedirectBackResponse $content */
        if(is_a($content, "ControllerRedirectBackResponse")) {
            if($content->getFromUrl() != $this->namespace && !$content->getHintUrl()) {
                $content->setHintUrl($this->namespace);
                $content->setParentControllerResolved(true);
            }
        }

        $this->callExtending("handleOutput", $content);

        return $content;
    }

    /**
     * this action will be called if no other action was found
     *
     * @return string|bool
     */
    public function index() {
        if ($this->template) {
            $this->tplVars["namespace"] = $this->namespace;
            return $this->modelInst()->customise($this->tplVars)->renderWith($this->template, $this->inExpansion);
        } else {
            throw new LogicException("No Template for Controller " . $this->classname . ". Please define \$template to activate the index-method.");
        }
    }

    /**
     * renders with given view
     *
     * @param string $template
     * @param ViewAccessableData|null $model
     * @return mixed
     */
    public function renderWith($template, $model = null)
    {
        if (!isset($model))
            $model = $this->modelInst();

        return $model->customise($this->tplVars)->renderWith($template);
    }

    /**
     * handles a request with a given record in it's controller
     *
     * @return string|false
     */
    public function record()
    {
        if($record = $this->service()->getSingleModel($this->getParam("id"))) {
            return $this->getWithModel($record)->handleRequest($this->request, $this->isSubController());
        }

        return $this->index();
    }

    /**
     * handles a request with a given versionid in it's controller
     *
     * @return mixed|string
     */
    public function version()
    {
        if($record = $this->service()->getSingleVersion($this->getParam("id"))) {
            $this->getWithModel($record)->handleRequest($this->request, $this->isSubController());
        }

        return $this->index();
    }

    /**
     * hook in this function to decorate a created record of record()-method
     * @param ViewAccessableData|IDataSet $record
     */
    public function decorateRecord(&$record)
    {

    }

    /**
     * generates a form
     *
     * @name form
     * @access public
     * @param string $name
     * @param ViewAccessableData|null $model
     * @param array $fields
     * @param bool $edit if calling getEditForm or getForm on model
     * @param string $submission
     * @param bool $disabled
     * @return string
     */
    public function form($name = null, $model = null, $fields = array(), $edit = false, $submission = null, $disabled = false)
    {
        return $this->buildForm($name, $model, $fields, $edit, $submission, $disabled)->render();
    }

    /**
     * builds the form
     *
     * @param string|null $name
     * @param ViewAccessableData|null $model
     * @param array $fields
     * @param bool $edit
     * @param callback|null $submission
     * @param bool $disabled
     * @return Form
     */
    public function buildForm($name = null, $model = null, $fields = array(), $edit = false, $submission = null, $disabled = false)
    {
        if (!isset($model) || !$model) {
            $model = clone $this->modelInst();
        }

        if(!isset($submission)) {
            $submission = "submit_form";
        }

        if (!gObject::method_exists($model, "generateForm")) {
            throw new LogicException("No Method generateForm for Model " . get_class($model));
        }

        /** @var DataObject $model */
        $name = !isset($name) ? $model->classname . "_" . $model->id . "_" . $model->versionid : $name;
        $form = $model->generateForm($name, $edit, $disabled, isset($this->request) ? $this->request : null, $this);
        $form->add(new HiddenField("class_name", $model->classname));
        $form->setSubmission($submission);

        foreach($fields as $field) {
            $form->add($field);
        }

        $this->callExtending("afterForm", $form);

        return $form;
    }

    /**
     * renders the form for this model
     * @param bool $name
     * @param array $fields
     * @param string $submission
     * @param bool $disabled
     * @param null|ViewAccessableData $model
     * @return string
     */
    public function renderForm($name = false, $fields = array(), $submission = "safe", $disabled = false, $model = null)
    {
        if (!isset($model))
            $model = $this->modelInst();

        return $this->form($name, $model, $fields, true, $submission, $disabled);
    }

    /**
     * edit-function
     *
     * @return GomaFormResponse|string
     */
    public function edit()
    {
        /** @var DataObject $model */
        if($model = $this->getSingleModel()) {
            if (!$model->can("Write")) {
                if ($this->showWithoutRight()) {
                    $disabled = true;
                } else {
                    return $this->actionComplete("less_rights");
                }
            } else {
                $disabled = false;
            }

            return $this->form("edit_" . $this->classname . $model->id, $model, array(), true, "safe", $disabled);
        }
    }

    /**
     * @return bool
     */
    protected function showWithoutRight() {
        return StaticsManager::getStatic($this->classname, "showWithoutRight", true) || StaticsManager::getStatic($this->modelInst(), "showWithoutRight", true);
    }

    /**
     * delete-function
     * this delete-function also implements ajax-functions
     *
     * @return bool|string
     */
    public function delete()
    {
        if($model = $this->getSingleModel()) {
            if(!$model->can("Delete")) {
                return $this->actionComplete("less_rights");
            }

            $description = $this->generateRepresentation($model);

            return $this->confirmByForm(lang("delete_confirm", "Do you really want to delete this record?"), function() use($model) {
                $preservedModel = clone $model;
                $this->service()->remove($model);
                if ($this->getRequest()->isJSResponse() || isset($this->getRequest()->get_params["dropdownDialog"])) {
                    $response = new AjaxResponse();
                    $data = $this->hideDeletedObject($response, $preservedModel);

                    return $data;
                } else {
                    return $this->actionComplete("delete_success", $preservedModel);
                }
            }, null, null, $description);
        }
    }

    /**
     * @param string $action
     * @param null $id
     * @return string
     * @throws DataNotFoundException
     */
    protected function buildUrlForActionAndModel($action, $id = null) {
        if(is_a($this->modelInst(), IDataSet::class)) {
            if(isset($id) && $this->modelInst()->find("id", $id)) {
                return $this->namespace . "/record/" . $id . "/" . $action . URLEND;
            } else {
                throw new DataNotFoundException();
            }
        } else {
            if(!isset($id) && $this->modelInst()->id != $id) {
                if(preg_match('/record\/([0-9]+)$/i', $this->namespace, $matches)) {
                    return substr($this->namespace, 0, 0 - strlen($matches[0])) . "/record/" . $id . "/" . $action . URLEND;
                }

                throw new DataNotFoundException();
            }

            return $this->namespace . "/" . $action;
        }
    }

    /**
     * @param DataObject $model
     * @param bool $link
     * @return string
     */
    protected function generateRepresentation($model, $link = false) {
        $description = $model->generateRepresentation($link);

        // find link.
        if(!preg_match('/<a\s+/i', $description)) {
            $link = false;
        }

        if(!$link) {
            if ($this->modelInst() == $model) {
                return '<a href="' . $this->namespace . '/edit' . URLEND . '">' . $description . '</a>';
            }

            if (is_a($this->modelInst(), "DataObjectSet")) {
                if ($this->modelInst()->find("id", $model->id)) {
                    return '<a href="' . $this->namespace . '/edit/' . $model->id . URLEND . '">' . $description . '</a>';
                }
            }
        }

        return $description;
    }

    /**
     * hides the deleted object
     * @param AjaxResponse $response
     * @param array|DataObject $data
     * @return AjaxResponse
     */
    public function hideDeletedObject($response, $data)
    {
        $response->exec("location.reload();");
        return $response;
    }

    /**
     * Alias for Controller::submit_form.
     *
     * @param array $data
     * @param Form $form
     * @param gObject $controller
     * @param bool $overrideCreated
     * @param int $priority
     * @param string $action
     * @return string
     * @throws Exception
     * @deprecated
     */
    public function safe($data, $form = null, $controller = null, $overrideCreated = false, $priority = 1, $action = 'save_success')
    {
        /** @var DataObject $givenModel */
        $givenModel = isset($form) ? $form->getModel() : $this->modelInst();
        $model = $this->saveModel($givenModel, $data, $priority, false, false, $overrideCreated);

        if ($model) {
            return $this->actionComplete($action, $model);
        } else {
            throw new LogicException('saveModel should either throw an exception or give a model.');
        }
    }

    /**
     * saves data to database and marks the record as draft if versions are enabled.
     *
     * Saves data to the database. It decides if to create a new record or not whether an id is set or not.
     * It marks the record as draft if versions are enabled on this model.
     *
     * @param    array $data
     * @param Form $form
     * @param gObject $controller
     * @return string
     * @throws Exception
     */
    public function submit_form($data, $form = null, $controller = null)
    {
        return $this->actionComplete("save_success", $this->service()->save($form->getModel(), $data));
    }

    #endregion

    /**
     * saves data to database and marks the record published.
     *
     * Saves data to the database. It decides if to create a new record or not whether an id is set or not.
     * It marks the record as published.
     *
     * @access    public
     * @param    array $data
     * @param Form $form
     * @param null $controller
     * @return string
     * @throws Exception
     */
    public function publish($data, $form = null, $controller = null)
    {
        return $this->actionComplete("publish_success",
            $this->service()->save($form->getModel(), $data)
        );
    }

    /**
     * @param string $action
     * @param ViewAccessableData|null $record
     * @return ControllerRedirectBackResponse|string
     */
    protected function getActionCompleteText($action, $record) {
        if(isset($record)) {
            if(lang($action . "_" . $record->classname, "-") !== "-") {
                return lang($action . "_" . $record->classname, null);
            }

            if(isset($record->baseClass) && lang($action . "_" . $record->baseClass, "-") !== "-") {
                return lang($action . "_" . $record->baseClass, null);
            }
        }

        switch ($action) {
            case "publish_success":
                return lang("successful_published", "The entry was successfully published.");
            case "save_success":
                return lang("successful_saved", "The data was successfully saved.");
            case "delete_success":
                return lang("successful_deleted");
        }

        return lang("success", "Success: ") . ": " . $this->classname . "/" . $action;
    }

    /**
     * this is the method, which is called when a action was completed successfully or not.
     *
     * it is called when actions of this controller are completed and the user should be notified. For example if the user saves data and it was successfully saved, this method is called with the param save_success. It is also called if an error occurs.
     *
     * @param    string $action the action called
     * @param    ViewAccessableData|null $record
     * @return string
     */
    public function actionComplete($action, $record = null)
    {
        if($text = $this->getActionCompleteText($action, $record)) {
            AddContent::addSuccess($text);
        }

        return $this->redirectback();
    }

    /**
     * redirects back to the page before based on some information by the user.
     *
     * it detects redirect-params with GET and POST-Vars. It uses the Referer and as a last instance it redirects to homepage.
     * you can define params to add to the redirect if you want.
     *
     * @access    public
     * @param    string $param get-parameter
     * @param    string $value value of the get-parameter
     * @return ControllerRedirectBackResponse
     */
    public function redirectback($param = null, $value = null)
    {
        if (isset($this->request->get_params["redirect"])) {
            $redirect = $this->request->get_params["redirect"];
        } else if (isset($this->request->post_params["redirect"])) {
            $redirect = $this->request->post_params["redirect"];
        } else {
            $redirect = null;
        }

        $this->callExtending("redirectback", $redirect);

        return ControllerRedirectBackResponse::create(
            $redirect,
            $this->request ? $this->request->getShiftedPart() : null,
            $this->request ? $this->request->canReplyJavaScript() : false
        )->setParam($param, $value);
    }

    /**
     * asks the user if he want's to do sth
     *
     * @name confirm
     * @access public
     * @param string - question
     * @param string - title of the okay-button, if you want to set it, default: "yes"
     * @param string|object|null - redirect on cancel button
     * @return bool
     * @deprecated
     */
    public function confirm($title, $btnokay = null, $redirectOnCancel = null, $description = null)
    {
        $data = $this->confirmByForm($title, function() {
            return true;
        }, function() use($redirectOnCancel) {
            if($redirectOnCancel) {
                return GomaResponse::redirect($redirectOnCancel);
            }

            return false;
        }, $btnokay, $description);
        if(!is_bool($data->getRawBody())) {
            Director::serve($data, $this->request);
            exit;
        }

        return $data->getRawBody();
    }

    /**
     * @param string $title
     * @param Callable $successCallback
     * @param Callable $errorCallback
     * @param null $btnokay
     * @param null $description
     * @return GomaFormResponse
     */
    public function confirmByForm($title, $successCallback, $errorCallback = null, $btnokay = null, $description = null) {
        $form = new ConfirmationForm($this, "confirm_" . $this->classname, array(
            new HTMLField("confirm", '<div class="text">' . $title . '</div>')
        ), array(
            $cancel = new FormAction("cancel", lang("cancel"), array($this, "_confirmCancel")),
            new FormAction("save", $btnokay ? $btnokay : lang("yes"))
        ));
        $form->setSubmission(array($this, "_confirmSuccess"));
        $cancel->setSubmitWithoutData(true);

        if (isset($description)) {
            if(is_object($description)) {
                if(gObject::method_exists($description, "generateRepresentation")) {
                    /** @var DataObject $description */
                    $description = $description->generateRepresentation(true);
                } else {
                    throw new LogicException("Description-Object must have generateRepresentation-Method.");
                }
            }

            $form->add(new HTMLField("description", '<div class="confirmDescription">' . $description . '</div>'));
        }

        self::$successCallback = $successCallback;
        self::$errorCallback = $errorCallback;

        $data = $form->render();
        $data->addRenderFunction(
            function($responseString, $data){
                /** @var GomaFormResponse $data */
                if(!$data->isFullPage()) {
                    return $this->showWithDialog($responseString, lang("confirm", "Confirm..."));
                }
            });
        return $data;
    }

    private static $errorCallback;
    private static $successCallback;


    /**
     * @internal
     * @return bool
     */
    public function _confirmSuccess() {
        return call_user_func_array(self::$successCallback, array());
    }

    /**
     * @internal
     * @return bool
     */
    public function _confirmCancel() {
        return self::$errorCallback ? call_user_func_array(self::$errorCallback, array()) : $this->redirectback();
    }

    /**
     * prompts the user
     *
     * @param $messsage
     * @param array $validators
     * @param string $defaultValue
     * @param null|bool $redirectOnCancel
     * @param null|bool $usePwdField
     * @return mixed
     * @deprecated
     */
    public function prompt($messsage, $validators = array(), $defaultValue = null, $redirectOnCancel = null, $usePwdField = null)
    {
        $data = $this->promptByForm($messsage, function($text) {
            return array($text);
        }, function() use($redirectOnCancel) {
            if($redirectOnCancel) {
                return GomaResponse::redirect($redirectOnCancel);
            }

            return false;
        }, $defaultValue, $validators, $usePwdField);
        if(is_array($data)) {
            return $data[0];
        }

        if(!is_bool($data->getRawBody())) {
            Director::serve($data, $this->request);
            exit;
        }
        return $data->getRawBody();
    }

    private static $successPromptCallback;

    /**
     * @param $message
     * @param $successCallback
     * @param $errorCallback
     * @param null $defaultValue
     * @param array $validators
     * @param bool $usePwdField
     * @return GomaFormResponse
     */
    public function promptByForm($message, $successCallback, $errorCallback = null, $defaultValue = null, $validators = array(), $usePwdField = false) {
        $field = ($usePwdField) ? new PasswordField("prompt_text", $message, $defaultValue) :
            new TextField("prompt_text", $message, $defaultValue);
        $form = new ConfirmationForm($this, "prompt_" . $this->classname, array(
            $field
        ), array(
            $cancel = new FormAction("cancel", lang("cancel"), array($this, "_confirmCancel")),
            new FormAction("save", lang("ok"))
        ), $validators);
        $cancel->setSubmitWithoutData(true);
        $form->setSubmission(array($this, "_promptSuccess"));

        self::$successPromptCallback = $successCallback;
        self::$errorCallback = $errorCallback;

        $data = $form->render();
        $data->addRenderFunction(
            function($responseString, $data){
                /** @var GomaFormResponse $data */
                if(!$data->isFullPage()) {
                    return $this->showWithDialog($responseString, lang("prompt", "Insert Text..."));
                }
            });
        return $data;
    }

    /**
     * catches problem when $data is not a string.
     *
     * @param string|object $data
     * @param string $title
     * @return string
     */
    protected function showWithDialog($data, $title) {
        if(!is_string($data)) {
            return $data;
        }

        $view = new ViewAccessableData();
        return $view->customise(
            array("content" => $data, "title" => $title)
        )->renderWith("framework/dialog.html");
    }

    /**
     * @internal
     * @param array $data
     * @return mixed
     */
    public function _promptSuccess($data) {
        return call_user_func_array(self::$successPromptCallback, array($data["prompt_text"]));
    }

    /**
     * @return Keychain
     */
    public function keychain() {
        return isset($this->keychain) ? $this->keychain : Keychain::sharedInstance();
    }


    /**
     * global save method for the database.
     *
     * it saves data to the database. you can define which priority should be selected and if permissions are relevant.
     *
     * @param    array $data data
     * @param    integer $priority Defines what type of save it is: 0 = autosave, 1 = save, 2 = publish
     * @param    boolean $forceInsert forces the database to insert a new record of this data and neglect permissions
     * @param    boolean $forceWrite forces the database to write without involving permissions
     * @param bool $overrideCreated
     * @param null|DataObject $givenModel
     * @return bool|DataObject
     * @deprecated
     */
    public function save($data, $priority = 1, $forceInsert = false, $forceWrite = false, $overrideCreated = false, $givenModel = null)
    {
        return $this->service()->saveData($data, $priority, $forceInsert, $forceWrite, $overrideCreated, $givenModel);
    }

    /**
     * @param DataObject $model
     * @param array $data
     * @param int $priority
     * @param bool $forceInsert
     * @param bool $forceWrite
     * @param bool $overrideCreated
     * @return DataObject
     */
    public function saveModel($model, $data, $priority = 1, $forceInsert = false, $forceWrite = false, $overrideCreated = false) {
        return $this->service()->saveModel($model, $data, $priority, $forceInsert, $forceWrite, $overrideCreated);
    }

    /**
     * adds a get-param to the query-string of given url.
     *
     * @param string $url
     * @param string $param
     * @param string $value
     * @return string
     */
    public static function addParamToUrl($url, $param, $value)
    {
        if (!strpos($url, "?")) {
            $modified = $url . "?" . $param . "=" . urlencode($value);
        } else {
            $url = preg_replace('/' . preg_quote($param, "/") . '\=([^\&]+)\&/Usi', "", $url);
            $url = preg_replace('/' . preg_quote($param, "/") . '\=([^\&]+)$/Usi', "", $url);
            $modified = str_replace(array("?&", "&&"), array('?', "&"), $url . "&" . $param . "=" . urlencode($value));
        }

        return convert::raw2text($modified);
    }
}
