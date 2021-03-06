<?php
defined("IN_GOMA") OR die();

/**
 * Base-Class for FormFields and Form, which handles logic of result and model.
 *
 * @package vorort.news
 *
 * @author Goma-Team
 * @copyright 2016 Goma-Team
 *
 * @version 1.0
 */
abstract class AbstractFormComponent extends RequestHandler {
    /**
     * this var defines if the value of $this->form()->post[$name] should be set as value if it is set
     *
     * @var boolean
     */
    protected $POST = true;

    /**
     * result of this field.
     *
     * @var string|array
     * @internal
     */
    protected $result;

    /**
     * model of this field.
     *
     * @var array|string|ViewAccessableData
     */
    public $model;

    /**
     * defines if we should use state-data in sub-queries of this Form
     *
     * @var bool
     */
    public $useStateData = null;

    /**
     * the parent field of this field, e.g. a form or a fieldset
     *
     * @var AbstractFormComponentWithChildren
     */
    protected $parent;

    /**
     * name of this field
     *
     * @var string
     */
    protected $name;

    /**
     * name of this field for access-purpose.
     * @internal
     */
    public $__fieldname;

    /**
     * name of the data-relation
     *
     * @var string
     */
    protected $dbname;

    /**
     * defines if this field is disabled
     *
     * @var bool
     */
    private $disabled = false;

    /**
     * overrides the post-name
     *
     * @var string
     */
    public $overridePostName;

    /**
     * @var bool
     */
    public $hasNoValue = false;

    /**
     * @var array[]
     */
    protected $errors;

    /**
     * template-view.
     */
    protected $templateView;

    /**
     * @var string
     */
    protected $template;

    /**
     * created field.
     *
     * @param string $name
     * @param mixed $model
     * @param Form|null $parent
     */
    public function __construct($name = null, $model = null, &$parent = null)
    {
        parent::__construct();

        $this->name = preg_replace("/[^a-zA-Z0-9_\\.\\-\[\]\{\}]/", "_", $name);
        $this->__fieldname = strtolower(trim($this->name));
        $this->dbname = strtolower(trim($this->name));
        $this->setModel($model);

        if($this->template) {
            $this->templateView = new ViewAccessableData();
        }

        if ($parent) {
            $parent->add($this);
        }
    }

    /**
     * sets the parent form-object
     * @param AbstractFormComponentWithChildren $form
     * @return $this
     */
    public function setForm(&$form) {
        if(!is_a($form, "AbstractFormComponentWithChildren")) {
            throw new InvalidArgumentException("Form must be a AbstractFormComponentWithChildren");
        }

        $this->parent =& $form;
        $this->parent->registerField($this->name, $this);

        return $this;
    }

    /**
     * @var array|string|ViewAccessableData
     * @return $this
     */
    public function setModel($model) {
        $this->model = $model;

        return $this;
    }

    /**
     * @return array|string|ViewAccessableData
     */
    public function getModel() {
        if (!isset($this->hasNoValue) || !$this->hasNoValue) {
            if($this->POST) {
                if (!$this->isDisabled() && $this->parent && ($postData = $this->parent->getFieldPost($this->PostName())) !== null) {
                    return $postData;
                } else if ($this->model === null) {
                    return $this->parent ? $this->parent->getFieldValue($this->dbname) : null;
                }
            }
        }

        return $this->model;
    }

    /**
     * @param string $field
     * @return mixed|null|ViewAccessableData
     */
    public function getFieldValue($field) {
        $model = $this->getModel();
        if(is_a($model, "ViewAccessableData") && isset($model->{$field})) {
            return $model->{$field};
        } else if (is_array($model) && isset($model[$field])) {
            return $model[$field];
        }

        return null;
    }

    static $i = 0;

    /**
     * @param string $field
     * @return null
     */
    public function getFieldPost($field) {
        $field = strtolower($field);
        if($this->parent) {
            return $this->parent->getFieldPost($field);
        }


        return isset($this->getRequest()->post_params[$field]) ? $this->getRequest()->post_params[$field] : null;
    }

    /**
     * @param $value
     * @return mixed
     */
    protected function getModelFromRaw($value) {
        return $value;
    }

    /**
     * @return AbstractFormComponentWithChildren
     */
    public function form() {
        if($this->parent) {
            return $this->parent->form();
        }

        throw new LogicException("Field " . $this->name . " requires a form. ");
    }

    /**
     * the url for ajax
     *
     * @return string
     */
    public function externalURL()
    {
        if($this->namespace) {
            return $this->namespace;
        }

        return $this->form()->externalURL() . "/" . $this->name;
    }

    /**
     * this function returns the result of this field
     * it needs to ensure correct behaviour in the isDisabled() state
     *
     * @return mixed
     */
    public function result() {
        return $this->getModel();
    }

    /**
     * adds to result regardless if disabled or not.
     * Disabled is handled in result().
     * @var array $result
     */
    public function argumentResult(&$result) {
        $result[$this->dbname] = $this->result();
    }

    /**
     * generates an id for the field
     *
     * @return string
     */
    public function ID()
    {
        $formId = $this->parent ? $this->form()->getName() : "";
        return "form_field_" . str_replace(array("\\", "[", "]"), "_", $this->classname) . "_" . $formId . "_" .
            str_replace(array("\\", "[", "]"), "_", $this->name);
    }

    /**
     * generates an id for the div
     *
     * @return string
     */
    public function divID()
    {
        return $this->ID() . "_div";
    }

    /**
     *
     */
    public function remove() {
        if($this->parent) {
            $this->parent->remove($this->name);
        }
    }

    /**
     * disables this field
     */
    final public function disable()
    {
        $this->disabled = true;
        return $this;
    }

    /**
     * reenables the field
     */
    final public function enable()
    {
        $this->disabled = false;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDisabled() {
        return $this->disabled || ($this->parent && $this->parent->isDisabled());
    }

    /**
     * returns the post-name
     *
     * @return string
     */
    public function PostName()
    {
        return $this->parent ? $this->parent->PostNamePrefix() . $this->dbname : $this->dbname;
    }

    /**
     * returns name.
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return Request|null
     */
    public function getRequest()
    {
        return $this->request ? $this->request : ($this->parent ? $this->parent->getRequest() : null);
    }

    /**
     * @param Request $request
     * @param bool $subController
     * @return false|null|string
     * @throws Exception
     */
    public function handleRequest($request, $subController = false)
    {
        $oldRequest = $this->request;

        $output = parent::handleRequest($request, $subController);

        $this->request = $oldRequest;

        return $output;
    }

    /**
     * exports basic field info.
     *
     * @param array|null $fieldErrors
     * @return FormFieldRenderData
     */
    public function exportBasicInfo($fieldErrors = null) {
        if(isset($fieldErrors[strtolower($this->name)])) {
            $this->errors = $fieldErrors[strtolower($this->name)];
        }

        return $this->createsRenderDataClass()
            -> setIsDisabled($this->isDisabled())
            -> setField($this)
            -> setHasError(count($this->errors) > 0)
            -> setPostName($this->PostName());
    }

    /**
     * @param FormFieldRenderData $info
     * @param bool $notifyField
     */
    public function addRenderData($info, $notifyField = true) {
        try {
            if($this->parent) {
                $this->parent->registerRendered($info->getName());
            }

            if($this->parent) {
                $info->setExternalUrl($this->externalURL());
            }

            $this->callExtending("beforeRender", $info);

            $fieldData = $this->field($info);

            $info->setRenderedField($fieldData)
                ->setJs($this->js());

            if ($notifyField) {
                $this->callExtending("afterRenderFormResponse", $info);
            }
        } catch(Exception $e) {
            if($info->getRenderedField() == null) {
                $info->setRenderedField(new HTMLNode("div", array("class" => "form_field")));
            }
            $info->getRenderedField()->append('<div class="error">' . $e->getMessage() . '</div>');
        }
    }

    /**
     * this function generates some JSON for using client side stuff.
     *
     * @param array|null $fieldErrors
     * @return FormFieldRenderData
     */
    final public function exportFieldInfo($fieldErrors = null) {
        $info = $this->exportBasicInfo($fieldErrors);

        $this->addRenderData($info);

        return $info;
    }

    /**
     * @param FormFieldRenderData $info
     * @return HTMLNode
     */
    abstract public function field($info);

    /**
     * @return string
     */
    abstract public function js();

    /**
     * @return FormFieldRenderData
     */
    protected function createsRenderDataClass() {
        return FormFieldRenderData::create($this->name, $this->classname, $this->ID(), $this->divID());
    }

    /**
     * getter-method for state
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (strtolower($name) == "state") {
            return $this->form()->state->{$this->classname . $this->name};
        } else if (property_exists($this, $name)) {
            return $this->$name;
        } else {
            throw new LogicException("\$" . $name . " is not defined in " . $this->classname . " with name " . $this->name . ".");
        }
    }

    /**
     * @return string
     */
    public function getDbname()
    {
        return $this->dbname;
    }

    /**
     * @return AbstractFormComponentWithChildren
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param \gObject $sender
     * @return string
     */
    public function getRedirect($sender)
    {
        if($this->form() && isset($this->form()->controller)) {
            return $this->form()->controller->getRedirect($sender);
        }

        return parent::getRedirect($sender);
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param string $template
     * @return $this
     */
    public function setTemplate($template)
    {
        $this->template = $template;
        if($template && !$this->templateView) {
            $this->templateView = new ViewAccessableData();
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTemplateView()
    {
        return $this->templateView;
    }

    /**
     * @param mixed $templateView
     * @return $this
     */
    public function setTemplateView($templateView)
    {
        $this->templateView = $templateView;
        return $this;
    }

    /**
     * @return bool
     */
    protected function isStateData() {
        if(is_bool($this->useStateData)) {
            return $this->useStateData;
        }

        if($this->parent && is_bool($this->parent->useStateData)) {
            return $this->parent->useStateData;
        }

        if(is_a($this->model, ViewAccessableData::class) && $this->model->queryVersion == DataObject::VERSION_STATE) {
            return true;
        }

        return false;
    }
}
