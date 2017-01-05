<?php
defined("IN_GOMA") OR die();

/**
 * Controls Page-Comments-Stuff.
 *
 * @package Goma
 *
 * @author Goma-Team
 * @copyright 2016 Goma-Team
 * @license: LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 *
 * @version 1.0
 */
class PageCommentsController extends FrontedController {
    public $allowed_actions = array("edit", "delete");

    public $template = "comments/comments.html";

    /**
     * ajax-save
     * @param array $data
     * @param AjaxResponse $response
     * @param Form $form
     * @return AjaxResponse
     */
    public function ajaxsave($data, $response, $form)
    {
        $model = $this->save($data);
        $response->prepend(".comments", $model->customise(array(
        "namespace" => $this->parentController()->namespace
        ))->renderWith("comments/onecomment.html"));
        $response->exec('$(".comments").find(".comment:first").css("display", "none").slideDown("fast");');
        $response->exec("$('#" . $form->fields["text"]->id() . "').val(''); $('#" . $form->fields["text"]->id() . "').change();");

        return $response;
    }


    /**
     * hides the deleted object
     *
     * @name hideDeletedObject
     * @access public
     * @return AjaxResponse
     */
    public function hideDeletedObject($response, $data)
    {
        $response->exec("$('#comment_" . $data["id"] . "').slideUp('fast', function() { \$('#comment_" . $data["id"] . "').remove();});");

        return $response;
    }
}

/**
 * extends the controller
 *
 * @method contentController getOwner()
 */
class PageCommentsControllerExtension extends ControllerExtension {
    /**
     * make the method work
     */
    public static $extra_methods = array(
        "pagecomments"
    );
    public $allowed_actions = array(
        "pagecomments"
    );

    public function pagecomments()
    {
        if ($this->getOwner()->modelInst()->showcomments)
            return ControllerResolver::instanceForModel($this->getOwner()->modelInst()->comments())->handleRequest($this->getOwner()->request);

        return "";
    }

    /**
     * append content to s ites if needed
     * @param HTMLNode $object
     */
    public function appendContent(&$object)
    {
        if ($this->getOwner()->modelInst()->showcomments) {
            /** @var HasMany_DataObjectSet $comments */
            $comments = $this->getOwner()->modelInst()->comments();

            /** @var GomaFormResponse $form */
            $form = gObject::instance("PageCommentsController")->Init($this->request)->setModelInst($comments)->form("add");
            if(Director::isResponseFullPage($form)) {
                Director::serve($form, $this->getOwner()->getRequest());
                exit;
            }

            $object->append($comments->customise(array(
                "page"  => $this->getOwner()->modelInst(),
                "form" => $form,
                "namespace" => $this->getOwner()->namespace
            ))->renderWith("comments/comments.html"));
        }
    }
}

gObject::extend(contentController::class, PageCommentsControllerExtension::class);
