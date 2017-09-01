<?php defined('IN_GOMA') OR die();

/**
 * Basic FormAction class which represents a basic "button".
 *
 * @package     Goma\Form
 *
 * @license     GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author      Goma-Team
 *
 * @version     1.2
 */
class HTMLAction extends FormAction
{
    /**
     * this var stores the html for this field
     */
    public $html;

    /**
     * constructor
     * @param string|null $name
     * @param string|null $html
     * @param Form|null $form
     */
    public function __construct($name = null, $html = null, $form = null)
    {
        parent::__construct($name, null, null, $form);
        $this->html = $html;
    }

    /**
     * renders the field
     * @param FormFieldRenderData $info
     * @return HTMLNode
     */
    public function field($info)
    {
        if (PROFILE) Profiler::mark("FormAction::field");

        $this->callExtending("beforeField");

        $this->container->append($this->html);

        $this->container->setTag("span");
        $this->container->addClass("formaction");

        $this->callExtending("afterField");

        if (PROFILE) Profiler::unmark("FormAction::field");

        return $this->container;
    }
}
