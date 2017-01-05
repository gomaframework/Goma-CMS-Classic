<?php
defined("IN_GOMA") OR die();

/**
 * Rating-Controller extension.
 *
 * @package Goma CMS
 *
 * @method Controller getOwner()
 *
 * @author Goma-Team
 * @copyright 2017 Goma-Team
 * @license: LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 *
 * @version 1.0
 */
class RatingControllerExtension extends ControllerExtension {
    /**
     * appends rating
     * @param HTMLNode $content
     */
    public function appendContent(&$content)
    {
        if ($this->getOwner()->modelInst()->rating) {
            $content->prepend(Rating::draw("page_" . $this->getOwner()->modelInst()->id));
        }
    }
}

gObject::extend(ContentController::class, RatingControllerExtension::class);
