<?php defined("IN_GOMA") OR die();
/**
 * Unit-Tests for Pages.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class PagesTest extends GomaUnitTest implements TestAble {


    static $area = "cms";
    /**
     * name
     */
    public $name = "pages";

    /**
     * tests if permissions are instantly written.
     */
    public function testAddPermissionWithoutWriting() {
        $page = new Page();
        $perm = new Permission();

        $page->addPermission($perm, "read_permission", false);

        $this->assertEqual($perm->id, 0);
        $this->assertEqual($page->id, 0);

        //$this->assertEqual($page->read_permission, $perm);
    }

    /**
     * tests sort.
     */
    public function testSortWhenNothingExists() {
        $page = new Page(array("parentid" => -1));
        $page->onBeforeWrite(new ModelWriter($page, IModelRepository::COMMAND_TYPE_PUBLISH, $page, Core::repository()));
        $this->assertEqual($page->sort, 0);
    }

    /**
     * tests parent-type.
     */
    public function testParentType() {

    }

    public function unitTestParentType($page, $expected) {

    }
}