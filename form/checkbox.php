<?php
defined("IN_GOMA") OR die();

/**
 * A simple check box.
 *
 * @author Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @package Goma\Form
 * @version 1.1
 */
class CheckBox extends FormField {

    /**
     * @var string
     */
    protected $template = "form/checkbox.html";

	/**
	 * @return bool
	 */
	public function getModel()
	{
		if($this->POST) {
			if (!$this->isDisabled() && $this->getRequest() &&
                count($this->getRequest()->post_params) > 0 && !$this->parent->getFieldPost($this->PostName())) {
				return false;
			}
		}

		return parent::getModel();
	}

    /**
     * @param FormFieldRenderData $info
     * @param bool $notifyField
     */
	public function addRenderData($info, $notifyField = true)
	{
		parent::addRenderData($info, $notifyField);

		$info->addJSFile("system/libs/javascript/checkbox/gCheckBox.js");
		$info->addJSFile("system/form/checkboxForm.js");
	}

	/**
	 * returns the javascript for this field
	 * @return string
	 */
	public function js() {
		return 'form_initCheckbox(field, field.id);';
	}

	/**
	 * the result of the field
	 *
	 * @return bool
	 */
	public function result() {
		return !!parent::result();
	}

}
