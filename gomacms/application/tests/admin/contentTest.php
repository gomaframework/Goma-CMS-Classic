<?php defined("IN_GOMA") OR die();
/**
 * Unit-Tests for content in admin-panel.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class ContentAdminTest extends GomaUnitTest implements TestAble
{
    static $area = "cms";
    /**
     * name
     */
    public $name = "content";

    public function testAddList() {
        $content = new contentAdmin();
        $request = new Request("get", "add");

        $form = $content->handleRequest($request);
        $this->assertTrue(is_string($form));
        $this->assertRegExp("/create/i", $form);
    }

    public function testGetModelForAdd() {
        $content = new contentAdmin();

        $method = new ReflectionMethod("contentAdmin", "getModelForAdd");
        $method->setAccessible(true);

        $viewData = $method->invoke($content);
        $this->assertIsA($viewData, "ViewAccessableData");
        $this->assertNotA($viewData, "Pages");
        $this->assertIsA($viewData->types, "DataSet");
        $this->assertTrue(is_string($viewData->adminuri));
    }

    public function testAddOfTypePage() {
        $content = new contentAdmin();
        $request = new Request("get", "add");
        $request->all_params["model"] = Page::class;

        /** @var GomaFormResponse $form */
        $form = $content->handleRequest($request);
        $this->assertInstanceOf(GomaFormResponse::class, $form);
        $this->assertInstanceOf(Page::class, $form->getForm()->getModel());
    }

    public function testAddOfTypeError() {
        $content = new contentAdmin();
        $request = new Request("get", "add");
        $request->all_params["model"] = errorPage::class;

        /** @var GomaFormResponse $form */
        $form = $content->handleRequest($request);
        $this->assertInstanceOf(GomaFormResponse::class, $form);
        $this->assertInstanceOf(errorPage::class, $form->getForm()->getModel());
    }
}
