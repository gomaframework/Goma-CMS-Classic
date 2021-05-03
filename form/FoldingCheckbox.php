<?php

defined("IN_GOMA") or die();

/**
 * A checkbox, which is showing additional fields once activated.
 *
 * @author Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @package Goma\Form
 * @version 1.1
 */
class FoldingCheckbox extends FieldSet
{
    /**
     * @var string
     */
    protected $template = "form/foldingCheckbox.html";

    /**
     * @return bool
     */
    public function getCheckboxModel()
    {
        if($this->POST) {
            if (!$this->isDisabled() && count($this->getRequest()->post_params) > 0) {
                return !!$this->parent->getFieldPost($this->PostName());
            }
        }

        return !!$this->getFieldValue($this->dbname);
    }

    /**
     * @param FormFieldRenderData $info
     * @param bool $notifyField
     */
    public function addRenderData($info, $notifyField = true)
    {
        $this->templateView->customise(array(
            "checkboxModel" => $this->getCheckboxModel()
        ));

        parent::addRenderData($info, $notifyField);

        $info->addJSFile("system/libs/javascript/checkbox/gCheckBox.js");
        $info->addJSFile("system/form/checkboxForm.js");
        $info->addJSFile("system/form/foldingCheckbox.js");
    }

    /**
     * returns the javascript for this field
     * @return string
     */
    public function js() {
        return 'form_initCheckbox(field, field.id); form_initFoldingCheckbox(field, field.id); ';
    }

    /**
     * the result of the field
     *
     * @return bool
     */
    public function result() {
        return !!$this->getCheckboxModel();
    }

    /**
     * @param array $result
     */
    public function argumentResult(&$result)
    {
        parent::argumentResult($result);

        $result[$this->dbname] = $this->result();
    }
}
