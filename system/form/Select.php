<?php defined("IN_GOMA") OR die();

/**
 * Select field.
 * By default, it uses selectize to render the form.
 * JavaScript API is same as radioButton JS API and defined in radioButton.js.
 * Selectize can be disabled by calling disableSelectize().
 * Attention: If options shall be selected on javascript runtime, you have to disable selectize.
 *
 * @author Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @package Goma\Form
 * @version 1.0.5
 */
class Select extends RadioButton
{
    /**
     * @var bool
     */
    protected $allowSelectize = true;

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
     * @param string $name
     * @param string $value
     * @param string $title
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

        if (!is_array($title)) {
            $node = new HTMLNode("option", array("class" => "option", "name" => $name, "value" => $value), array(
                $title
            ));
        } else {
            $temp = $title;
            unset($temp['title']);
            $attr = array();
            $attr["class"] = "option";
            $attr["name"] = $name;
            $attr["value"] = $value;
            foreach ($temp as $key => $value ){
                $attr[$key] = $value;
            }
            $node = new HTMLNode("option", $attr, array(
                $title["title"]
            ));
        }

        if ($checked)
            $node->selected = "selected";

        if ($disabled)
            $node->disabled = "disabled";

        if (isset($disabled) && $disabled && $this->hideDisabled)
            $node->css("display", "none");

        $this->callExtending("renderOption", $node, $input, $_title);

        return $node;
    }

    /**
     * @param FormFieldRenderData $info
     * @return HTMLNode
     */
    public function field($info)
    {
        $info->addJSFile("system/form/select.js");
        $info->addJSFile("system/libs/thirdparty/selectize/dist/js/standalone/selectize.js");
        $info->addCSSFile("system/libs/thirdparty/selectize/dist/less/selectize.less");
        $info->addCSSFile("system/libs/thirdparty/selectize/dist/less/selectize.default.less");

        $container = parent::field($info);

        $node = $container->getNode(1);
        $node->removeClass("inputHolder");
        $node->setTag("select");
        $node->attr("name", $this->PostName());

        if ($this->isDisabled()) {
            $node->attr("disabled", "disabled");
        }

        if($this->placeholder) {
            $node->placeholder = $this->placeholder;
            if(!$this->getModel()) {
                $node->prepend(
                    '<option hidden selected="selected" value="">'.convert::raw2text($this->placeholder).'</option>'
                );
            }
        }

        if($this->allowSelectize()) {
            $this->container->addClass("allowSelectize");
        }

        $wrapper = new HTMLNode("div", array("class" => "select-wrapper non-selectize input"));
        if ($this->isDisabled()) {
            $wrapper->addClass("disabled");
        }
        $wrapper->append($node);

        $container->content[1] = $wrapper;

        return $container;
    }

    /**
     * determines if selectize is allowed or not.
     *
     * @return bool
     */
    protected function allowSelectize() {
        return $this->allowSelectize && count($this->disabledNodes) == 0;
    }

    /**
     * disables selectize.
     */
    public function disableSelectize() {
        $this->allowSelectize = false;
        return $this;
    }

    /**
     * reenables selectize. it will also be disabled if there are disabled options.
     */
    public function reenableSelectize() {
        $this->allowSelectize = true;
        return $this;
    }

    /**
     * @return string
     */
    public function js()
    {
        return "new goma.form.Select(form, field);";
    }
}
