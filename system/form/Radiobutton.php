<?php
defined("IN_GOMA") OR die();

/**
 * Radio-buttons.
 *
 * @author Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @package Goma\Form
 * @version 1.0.5
 */
class RadioButton extends FormField
{
    /**
     * options for this set
     */
    protected $options;

    /**
     * which radio-buttons are disabled
     */
    public $disabledNodes = array();

    /**
     * defines if we hide disabled nodes
     */
    public $hideDisabled = false;

    /**
     * creates field.
     * @param null $name
     * @param null $title
     * @param array $options
     * @param null $selected
     * @param null $form
     * @return static
     */
    public static function create($name = null, $title = null, $options = array(), $selected = null, $form = null) {
        return new static($name, $title, $options, $selected, $form);
    }

    /**
     * @param string|null $name
     * @param string|null $title
     * @param array $options
     * @param string|null $selected
     * @param Form null $form
     */
    public function __construct($name = null, $title = null, $options = array(), $selected = null, $form = null)
    {
        $this->options = $options;
        parent::__construct($name, $title, $selected, $form);

    }

    /**
     * generates the options
     *
     * @name options
     * @access public
     * @return array
     */
    public function options()
    {
        $this->callExtending("onBeforeOptions");
        return $this->getOptions();
    }

    /**
     * renders a option-record
     *
     * @param $name
     * @param $value
     * @param $title
     * @param null|bool $checked
     * @param null|bool $disabled
     * @return HTMLNode
     */
    public function renderOption($name, $value, $title, $checked = null, $disabled = null)
    {
        if (!isset($checked))
            $checked = false;

        if (!isset($disabled))
            $disabled = false;
        $id = "radio_" . md5($this->ID() . $name . $value);

        $node = new HTMLNode("div", array("class" => "option"), array(
            $input = new HTMLNode('input', array(
                "type" => "radio",
                "name" => $name,
                "value" => $value,
                "id" => $id
            )),
            $_title = new HTMLNode('label', array(
                "for" => $id
            ), $title)
        ));

        if ($checked)
            $input->checked = "checked";

        if ($disabled)
            $input->disabled = "disabled";

        if (isset($disabled) && $disabled && $this->hideDisabled)
            $node->css("display", "none");

        $this->callExtending("renderOption", $node, $input, $_title);

        return $node;
    }

    /**
     * renders the field
     *
     * @param FormFieldRenderData $info
     * @return HTMLNode
     */
    public function field($info)
    {
        $this->callExtending("beforeField");

        $this->container->append(new HTMLNode(
            "label",
            array(),
            $this->title
        ));

        $node = new HTMLNode("div", array("class" => "inputHolder"));

        foreach ($this->options() as $value => $title) {
            $node->append($this->renderOption(
                $this->PostName(),
                $value,
                $title,
                $this->valueMatches($value, $this->getModel()),
                $this->isDisabled() || isset($this->disabledNodes[$value])
            ));
        }

        $this->container->append($node);

        $this->callExtending("afterField");

        return $this->container;
    }

    /**
     * @param array|null $fieldErrors
     * @return FormFieldRenderData
     */
    public function exportBasicInfo($fieldErrors = null)
    {
        $data = parent::exportBasicInfo($fieldErrors);

        $nodes = array();
        foreach ($this->options as $value => $title) {
            $nodes[] = array(
                "value"     => $value,
                "title"     => $title,
                "disabled"  => $this->isDisabled() || isset($this->disabledNodes[$value]),
                "checked"   => $value == $this->value
            );
        }

        $data->setExtra("radioButtons", $nodes);

        return $data;
    }

    /**
     * @param FormFieldRenderData $info
     * @param bool $notifyField
     */
    public function addRenderData($info, $notifyField = true)
    {
        parent::addRenderData($info, $notifyField);

        $info->addJSFile("system/form/radioButton.js");
    }

    /**
     * adds an option
     *
     * @param string $key
     * @param mixed $val
     * @param bool $prepend if to prepend instead of append
     * @return $this
     */
    public function addOption($key, $val, $prepend = false)
    {
        if (!$prepend)
            $this->options[$key] = $val;
        else
            $this->options = array_merge(array($key => $val), $this->options);
        return $this;
    }

    /**
     * removes an option
     *
     * @param string $key
     * @return $this
     */
    public function removeOption($key)
    {
        unset($this->options[$key]);
        return $this;
    }

    /**
     * disables a specific radio-button
     *
     * @return $this
     */
    public function disableOption($id)
    {
        $this->disabledNodes[$id] = true;
        return $this;
    }

    /**
     * enables a specific radio-button
     *
     * @return $this
     */
    public function enableOption($id)
    {
        unset($this->disabledNodes[$id]);
        return $this;
    }

    /**
     * validation for security reason
     *
     * @name validate
     * @return bool
     */
    public function validate($value)
    {
        if (!isset($this->options[$value])) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function js()
    {
        return parent::js() . ' initRadioButton(field, field.divId);';
    }

    /**
     * resolves edge-case with 0 and "".
     *
     * @param mixed $value
     * @param mixed $compare
     * @return bool
     */
    private function valueMatches($value, $compare) {
        if($value === 0 && $compare === "") {
            return false;
        }

        if($compare === 0 && $value === "") {
            return false;
        }

        return $value == $compare;
    }

    /**
     * @return mixed
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param mixed $options
     * @return $this
     */
    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }
}
