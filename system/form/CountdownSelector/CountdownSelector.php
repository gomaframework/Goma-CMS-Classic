<?php defined("IN_GOMA") OR die();

/**
 * Date-Field for SQL-Date.
 *
 * @package	Goma\Forms
 * @link	http://goma-cms.org
 * @license LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 * @author 	Goma-Team
 * @version 2.0
 */
class CountdownSelector extends FormField
{

	/**
	 * @var bool
	 */
	protected $template = "form/CoundownSelector.html";


	/**
	 * @param FormFieldRenderData $info
	 * @param bool $notifyField
	 */
	public function addRenderData($info, $notifyField = true)
	{
		parent::addRenderData($info, $notifyField);

		$info->addJSFile("system/form/CountdownSelector/CountdownSelector.js");
		$info->addCSSFile("system/form/CountdownSelector/CountdownSelector.less");
		$info->addCSSFile("font-awsome/font-awesome.css");

		$info->getRenderedField()->addClass("countdownSelector");
	}


	/**
	 * render JavaScript
	 */
	public function JS()
	{
		return 'new gCountdownSelectorField(field.id);';
	}


    /**
     * @return array|bool|string|ViewAccessableData
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
}
