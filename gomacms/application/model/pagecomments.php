<?php defined("IN_GOMA") OR die();
/**
 * @package goma cms
 * @link http://goma-cms.org
 * @license: LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 * @author Goma-Team
 * last modified: 17.01.2013
 * $Version 1.1.6
 */

loadlang('comments');

class PageComments extends DataObject {

    static $db = array('name' => 'varchar(200)',
                       'text' => 'text');

    /**
     * has-one-relation to page
     */
    static $has_one = array('page' => 'pages'); // has one page

    /**
     * sort
     */
    static $default_sort = "created DESC";

    /**
     * indexes for faster look-ups
     */
    static $index = array("name" => true);

    static $search_fields = array(
        "name", "text"
    );

    /**
     * insert is always okay
     */
    public function canInsert()
    {
        return true;
    }

    /**
     * generates the form
     * @param Form $form
     * @throws \Goma\Form\Exception\DuplicateActionException
     */
    public function getForm(&$form)
    {
        if (member::$loggedIn) {
            $form->add(new HiddenField("name", member::$loggedIn->title()));
        } else {
            $form->add(new TextField("name", lang("name", "Name")));
        }

        $form->add(new BBCodeEditor("text", lang("text", "text"), null, null, null, array("showAlign" => false)));
        if (!isset(Member::$loggedIn)) {
            $form->add(new Captcha("captcha"));
            $form->addValidator(new RequiredFields(array("text", "name", "captcha")), "fields");
        } else {
            $form->addValidator(new RequiredFields(array("text", "name")), "fields");
        }
        $form->addAction(new AjaxSubmitButton("save", lang("co_add_comment", "add comment"), "ajaxsave", "safe", array("green")));
    }

    /**
     * edit-form
     *
     * @name getEditForm
     * @access public
     * @throws \Goma\Form\Exception\DuplicateActionException
     */
    public function getEditForm(&$form)
    {
        $form->add(new HTMLField("heading", "<h3>" . lang("co_edit", "edit comments") . "</h3>"));
        $form->add(new BBCodeEditor("text", lang("text", "text")));

        $form->addAction(new CancelButton("cancel", lang("cancel", "cancel")));
        $form->addAction(new FormAction("save", lang("save", "save"), null, array("green")));
    }

    public function timestamp()
    {
        return $this->created();
    }

    /**
     * @return bool
     */
    public function getWriteAccess()
    {
        if (!self::Versioned($this->classname) && $this->can("Write")) {
            return true;
        } else if ($this->can("Publish")) {
            return true;
        } else if ($this->can("Delete")) {
            return true;
        }

        return false;
    }

    /**
     * returns the representation of this record
     *
     * @param bool $link
     * @return string
     */
    public function generateRepresentation($link = false)
    {
        return lang("CO_COMMENT") . " " . lang("CO_OF") . ' ' . convert::raw2text($this->name) . ' ' . lang("CO_ON") . ' ' . $this->created()->date() . '';
    }
}


/**
 * extends the page
 */
class PageCommentsDataObjectExtension extends DataObjectExtension {
    /**
     * make relation
     */
    static $has_many = array(
        "comments" => "pagecomments"
    );
    /**
     * make field for enable/disable
     */
    static $db = array(
        "showcomments" => "int(1)"
    );

    static $default = array(
        "showcomments" => 0
    );

    /**
     * make extra fields to form
     */
    public function getForm(&$form)
    {
        $form->meta->add(new Checkbox("showcomments", lang("co_comments")));
    }
}

gObject::extend("pages", "PageCommentsDataObjectExtension");
