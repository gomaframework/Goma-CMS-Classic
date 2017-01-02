<?php
namespace Goma\Controller\Category;
defined("IN_GOMA") OR die();

/**
 * Extension for AbstractCategoryController.
 *
 * @package Goma\Controller
 *
 * @author Goma-Team
 * @copyright 2016 Goma-Team
 * @license        GNU Lesser General Public License, version 3; see "LICENSE.txt"
 *
 * @version 1.0
 */
abstract class CategoryControllerExtension extends \ControllerExtension {
    /**
     * @param array $categories
     * @return void
     */
    abstract public function decorateCategories(&$categories);

    /**
     * @param string $title
     * @param string $action
     */
    public function decorateActionTitle(&$title, $action) {

    }
}
