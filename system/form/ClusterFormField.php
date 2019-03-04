<?php
defined("IN_GOMA") OR die();

/**
 * A cluster form field.
 *
 * @author Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @package Goma\Form
 * @version 1.0.5
 */
class ClusterFormField extends FieldSet
{
    static $url_handlers = array(
        "\$field!" => "handleField"
    );

    /**
     * controller
     */
    public $controller;

    /**
     * @var bool
     */
    private $fieldsDefined = false;

    /**
     * constructing
     *
     * @param string|null $name
     * @param array|null $fields
     * @param string|null $title
     * @param string|ViewAccessableData|null $value
     * @param Form|null $form
     */
    public function __construct($name = null, $fields = null, $title = null, $value = null, &$form = null)
    {
        // support for both parameter ordering
        if(is_string($fields)) {
            $_fields = $title;
            $title = $fields;
            $fields = $_fields;
        }

        parent::__construct($name, $fields, $title, $form);

        $this->model = $value;
        $this->container->setTag("div");
    }

    /**
     *
     */
    protected function defineFields()
    {

    }

    /**
     * @param null $fieldErrors
     * @return FormFieldRenderData
     */
    public function exportBasicInfo($fieldErrors = null)
    {
        if (!$this->fieldsDefined) {
            $this->fieldsDefined = true;
            $this->defineFields();
        }

        return parent::exportBasicInfo($fieldErrors);
    }

    /**
     * checks if the action is available
     * we implement sub-namespaces for sub-items here
     *
     * @param string $action
     * @param string $classWithActionDefined
     * @return bool
     */
    public function hasAction($action, $classWithActionDefined = null)
    {
        if (isset($this->fields[strtolower($action)])) {
            return true;
        }

        if (parent::hasAction($action, $classWithActionDefined)) {
            return true;
        }

        return false;
    }

    /**
     * handles the action
     * we implement sub-namespaces for sub-items here
     *
     * @return null|string
     * @throws Exception
     */
    public function handleField()
    {
        $field = $this->getParam("field");
        if (isset($this->fields[strtolower($field)])) {
            return $this->fields[strtolower($field)]->handleRequest($this->request);
        }

        return null;
    }

    /**
     * generates an id for the field
     *
     * @return string
     */
    public function ID()
    {
        return "form_field_".ClassManifest::getUrlClassName($this->classname)."_".md5(
                $this->form()->getName()
            )."_".$this->name;
    }

    /**
     * result
     *
     * @return array|mixed|null
     */
    public function result()
    {
        $result = $this->getModel();
        if (!$result) {
            $result = array();
        }

        /** @var AbstractFormComponent $field */
        foreach ($this->fieldList as $field) {
            $field->argumentResult($result);
        }

        return $result;
    }

    /**
     * adds to result regardless if disabled or not.
     * Disabled is handled in result.
     *
     * @param array $result
     */
    public function argumentResult(&$result)
    {
        $result[$this->dbname] = $this->result();
    }

    /**
     * generates an name for this form
     *
     * @return null|string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @return array|null|string|ViewAccessableData
     */
    public function getModel()
    {
        if (!isset($this->hasNoValue) || !$this->hasNoValue) {
            if ($this->POST) {
                if (!$this->isDisabled() && $this->parent && ($postData = $this->parent->getFieldPost(
                        $this->PostName()
                    ))) {
                    return $postData;
                } else if ($this->model === null) {
                    $this->model = $this->parent ? $this->parent->getFieldValue($this->dbname) : null;
                }
            }
        }

        return $this->model;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isFieldToRender($name)
    {
        return ((isset($this->fields[strtolower($name)])) && !isset($this->renderedFields[strtolower($name)]));
    }

    /**
     * @return $this
     */
    public function form()
    {
        return $this;
    }

    /**
     * @return string
     */
    public function externalURL()
    {
        if ($this->namespace) {
            return $this->namespace;
        }

        return parent::form()->externalURL()."/".$this->name;
    }

    /**
     * @return null|string
     */
    public function js() {
        return parent::js() . "\nwindow[\"{$this->id()}\"] = field;";
    }

    /**
     * registers a field in this form
     *
     * @param string $name
     * @param AbstractFormComponent $field
     */
    public function registerField($name, $field)
    {
        if (!is_a($field, "AbstractFormComponent")) {
            throw new InvalidArgumentException('$field must be AbstractFormComponent');
        }

        $this->fields[strtolower($name)] = $field;
    }

    /**
     * unregisters the field from this form
     * this means that the field will not be rendered
     *
     * @param string $name
     */
    public function unRegisterField($name)
    {
        unset($this->fields[strtolower($name)]);
    }

    /**
     *
     */
    protected function setFormRegisterOnParent()
    {
    }

    /**
     * @return string
     */
    public function PostNamePrefix()
    {
        return parent::PostNamePrefix() . $this->PostName() . "_";
    }
}
