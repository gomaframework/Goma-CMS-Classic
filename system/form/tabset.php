<?php defined("IN_GOMA") OR die();


/**
 * This is a fieldset which is used as a tabsetb, which holds several tabs.
 *
 * @package     Goma\Form
 *
 * @license     GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author      Goma-Team
 *
 * @version     1.5
 */
class TabSet extends FieldSet
{
    /**
     * manual set name of active tab.
     *
     * @var string|null
     */
    public $activeTab = null;

    /**
     * @var string
     */
    protected $template = "form/TabSet.html";

    /**
     * @var bool
     */
    protected $hideTabsIfOnlyOne = false;

    /**
     * @param string|null $name
     * @param array $fields
     * @param Form|null $form
     */
    public function __construct($name = null, $fields = array(), &$form = null)
    {
        parent::__construct($name, $fields, null, $form);

        $this->container->setTag("div");
        $this->container->addClass("tabs");
    }

    /**
     * @param FormFieldRenderData $info
     * @param bool $notifyField
     */
    public function addRenderData($info, $notifyField = true)
    {
        $info->addCSSFile("tabs.less");
        $info->addJSFile("system/libs/tabs/tabs.js");

        foreach($info->getChildren() as $child) {
            /** @var FormFieldRenderData $child */
            if($child->getField()->hidden()) {
                $info->removeChild($child);
            }
        }

        $this->markTabActive($info);

        parent::addRenderData($info, false);

        if($notifyField) {
            $this->callExtending("afterRenderFormResponse", $info);
        }
    }

    /**
     * @return FormFieldRenderData
     */
    public function createsRenderDataClass()
    {
        return TabSetRenderData::create($this->name, $this->classname, $this->ID(), $this->divID());
    }

    /**
     * @param null $fieldErrors
     * @return mixed
     */
    public function exportBasicInfo($fieldErrors = null)
    {
        return parent::exportBasicInfo($fieldErrors)->setShouldRenderTabsIfOnlyOne(!$this->hideTabsIfOnlyOne);
    }

    /**
     * @param FormFieldRenderData $info
     */
    protected function markTabActive($info) {
        $activeTabFound = false;
        $children = $info->getChildren();

        if(count($children) == 0)
            return;

        for($i = 0; $i < count($children); $i++) {
            /** @var TabRenderData $child */
            $child = $children[$i];
            $child->setSubmitName("tabs_" . $child->getName());
            if ((isset($this->form()->getRequest()->post_params["tabs_" . $child->getName()])) && !$activeTabFound) {
                $activeTabFound = true;
                setcookie("tabs_" . $this->name, $child->getName(), 0, "/");
                $child->setTabActive(true);
            }
        }

        if (!$activeTabFound) {
            // check session
            $active = isset($this->activeTab) ? $this->activeTab : (isset($_COOKIE["tabs_" . $this->name]) ? $_COOKIE["tabs_" . $this->name] : null);
            if (isset($active)) {
                $i = 0;
                /** @var TabRenderData $item */
                foreach ($children as $item) {
                    if ($item->getName() == $active) {
                        $item->setTabActive(true);
                        $activeTabFound = true;
                        break;
                    }

                    $i++;
                }
            }

            if (!$activeTabFound) {
                // make first tab active
                $children[0]->setTabActive(true);
            }
        }
    }

    /**
     * generates js
     *
     * @return string
     */
    public function JS()
    {
        return '$(function(){ $("#' . $this->divID() . '").gtabs({"animation": true, "cookiename": "tabs_' . $this->name . '"}); });' .
            parent::JS();
    }

    /**
     * @return null|string
     */
    public function getActiveTab()
    {
        return $this->activeTab;
    }

    /**
     * @param null|string $activeTab
     * @return $this
     */
    public function setActiveTab($activeTab)
    {
        $this->activeTab = $activeTab;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isHideTabsIfOnlyOne()
    {
        return $this->hideTabsIfOnlyOne;
    }

    /**
     * @param boolean $hideTabsIfOnlyOne
     * @return $this
     */
    public function setHideTabsIfOnlyOne($hideTabsIfOnlyOne)
    {
        $this->hideTabsIfOnlyOne = $hideTabsIfOnlyOne;
        return $this;
    }
}
