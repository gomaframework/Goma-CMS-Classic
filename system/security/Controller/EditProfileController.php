<?php namespace Goma\Security\Controller;
use AddContent;
use DataNotFoundException;
use DataObject;
use FormAction;
use FormInvalidDataException;
use FormValidator;
use Goma\Controller\Category\AbstractCategoryController;
use GomaResponse;
use Hash;
use Member;
use MySQLException;
use PasswordField;
use Permission;

defined("IN_GOMA") OR die();

/**
 * It is the new edit-profile page.
 *
 * @package Goma\Security
 *
 * @author Goma-Team
 * @copyright 2016 Goma-Team
 *
 * @version 1.0
 */
class EditProfileController extends AbstractCategoryController {

    public $allowed_actions = array(
        "password"
    );

    /**
     * returns categories in form method => category title
     * @return array
     */
    public function provideCategories()
    {
        return array(
            "index"     => lang("general"),
            "password"  => lang("edit_password")
        );
    }

    /**
     * @return string
     */
    public function index()
    {
        return $this->edit();
    }

    /**
     *
     */
    public function password() {
        $form = new \Form($this, "password", array(
            new \HiddenField("id", $this->modelInst()->id),
            new PasswordField("password",lang("NEW_PASSWORD")),
            new PasswordField("repeat", lang("REPEAT"))
        ));

        // check if user needs to give old password or permissions are enough to not adding old one.
        if(Permission::check("USERS_MANAGE") && $this->modelInst()->id != Member::$id) {
            $form->addValidator(new FormValidator(array(static::class, "validateNewAndRepeatPwd")), "pwdvalidator");
        } else {
            $form->add(new PasswordField("oldpwd", lang("OLD_PASSWORD")), 0);
            $form->addValidator(new FormValidator(array(static::class, "validatepwd")), "pwdvalidator");
        }

        $form->addAction(new FormAction("submit", lang("edit_password", "save password"), "pwdsave"));

        $this->callExtending("passwordform", $form);

        return $form->render();
    }


    /**
     * validates new and old passwords and returns error string when error happened.
     * @param FormValidator $obj
     * @throws DataNotFoundException
     * @throws FormInvalidDataException
     */
    public static function validatepwd($obj) {
        if(isset($obj->getForm()->result["oldpwd"]))
        {
            $data = DataObject::get_one("user", array("id" => $obj->getForm()->result["id"]));
            if($data) {
                // export data
                $data = $data->ToArray();
                $pwd = $data["password"];

                // check old password
                if(Hash::checkHashMatches($obj->getForm()->result["oldpwd"], $pwd))
                {
                    self::validateNewAndRepeatPwd($obj);
                } else {
                    throw new FormInvalidDataException("oldpwd", "password_wrong");
                }
            } else {
                throw new DataNotFoundException("error");
            }
        } else
        {
            throw new FormInvalidDataException("oldpwd", "password_wrong");
        }
    }

    /**
     * validates new password and repeat matches.
     *
     * @param FormValidator $obj
     * @throws FormInvalidDataException
     */
    public static function validateNewAndRepeatPwd($obj) {
        if(isset($obj->getForm()->result["password"], $obj->getForm()->result["repeat"]) && $obj->getForm()->result["password"] != "")
        {
            if($obj->getForm()->result["password"] != $obj->getForm()->result["repeat"])
            {
                throw new FormInvalidDataException("repeat", "passwords_not_match");
            }
        } else {
            throw new FormInvalidDataException("password", "password_cannot_be_empty");
        }
    }


    /**
     * saves the user-pwd
     *
     * @param array $result
     * @return GomaResponse
     * @throws MySQLException
     */
    public function pwdsave($result)
    {
        AddContent::add('<div class="success">'.lang("edit_password_ok", "The password were successfully changed!").'</div>');
        DataObject::update("user", array("password" => Hash::getHashFromDefaultFunction($result["password"])), array('recordid' => $result["id"]));
        return $this->redirectback();
    }
}
