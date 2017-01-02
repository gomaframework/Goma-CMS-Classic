<?php
defined("IN_GOMA") OR die();

/**
 * PageCommentsControllerTest.
 *
 * @package Goma
 *
 * @author Goma-Team
 * @copyright 2016 Goma-Team
 *
 * @version 1.0
 */
class PageCommentsControllerTest extends AbstractControllerTest {

    public $name = "PageCommentsController";

    protected function getUrlsForFirstResponder()
    {
        return array(

        );
    }

    public function testPageCommentsLoaded() {
        $request = new Request("get", "test");
        $page = new Page();
        $page->title = "My Page";
        $page->showcomments = true;

        $contentController = new ContentController();
        $contentController->setModelInst($page);
        $response = Director::serve($contentController->handleRequest($request), $request, false);

        $this->assertTrue(strpos($response, lang("co_add_comment")) !== false);
    }
}
