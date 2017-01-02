<?php
defined("IN_GOMA") OR die();


/**
 * Wrapper used to render tabsets.
 *
 * @package Goma\Form
 * @author Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @version 1.0
 */
class TabSetRenderData extends FormFieldRenderData {
    /**
     * @var bool
     */
    protected $shouldRenderTabsIfOnlyOne;

    /**
     * @return boolean
     */
    public function isShouldRenderTabsIfOnlyOne()
    {
        return $this->shouldRenderTabsIfOnlyOne;
    }

    /**
     * @param boolean $shouldRenderTabsIfOnlyOne
     * @return $this
     */
    public function setShouldRenderTabsIfOnlyOne($shouldRenderTabsIfOnlyOne)
    {
        $this->shouldRenderTabsIfOnlyOne = $shouldRenderTabsIfOnlyOne;
        return $this;
    }

    /**
     * @param bool $includeRendered
     * @param bool $includeChildren
     * @return array
     */
    public function ToRestArray($includeRendered = false, $includeChildren = true)
    {
        $data = parent::ToRestArray($includeRendered, $includeChildren);

        $data["shouldRenderTabsIfOnlyOne"] = $this->shouldRenderTabsIfOnlyOne;

        return $data;
    }
}
