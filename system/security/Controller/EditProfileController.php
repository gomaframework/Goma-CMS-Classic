<?php namespace Goma\Security\Controller;
use Goma\Controller\Category\AbstractCategoryController;

defined("IN_GOMA") OR die();

/**
 * Describe your class
 *
 * @package dLED
 *
 * @author D
 * @copyright 2016 D
 *
 * @version 1.0
 */
class EditProfileController extends AbstractCategoryController {
    /**
     * returns categories in form method => category title
     * @return array
     */
    public function provideCategories()
    {
        return array(
            "index" => lang("general")
        );
    }

    /**
     * @return string
     */
    public function index()
    {
        $data = $this->edit();
        if(is_object($data)) {
            return $data;
        }
        return '<h1>'.lang("edit_profile").'</h1>' . $data;
    }
}
