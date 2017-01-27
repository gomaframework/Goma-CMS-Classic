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

    protected $parentIdForZero;

    /**
     *
     */
    public function setup() {
        if(DataObject::get(pages::class)->count() == 0) {
            $this->parentIdForZero = 0;
        } else {
            $this->parentIdForZero = DataObject::get_one(pages::class, array(
                "children.count" => 0
            ))->id;
        }
    }

    public function tearDown() {
        foreach(DataObject::get(pages::class, array("parentid" => $this->parentIdForZero)) as $page) {
            $page->remove(true);
        }
    }

    /**
     * tests if permissions are instantly written.
     */
    public function testAddPermissionWithoutWriting() {
        $page = new Page();
        $perm = new Permission();

        $page->addPermission($perm, "read_permission");

        $this->assertEqual($perm->id, 0);
        $this->assertEqual($page->id, 0);
        $this->assertEqual($page->read_permission, $perm);

        $this->assertEqual($perm->id, 0);
        $this->assertEqual($page->id, 0);
    }

    /**
     * tests sort when no other page in that set exists.
     */
    public function testSortWhenNothingExists() {
        $page = new Page(array("parentid" => $this->parentIdForZero));
        $page->onBeforeWrite(new ModelWriter($page, IModelRepository::COMMAND_TYPE_PUBLISH, $page, Core::repository()));
        $this->assertEqual($page->sort, 0);
    }

    /**
     * tests sort when something is existing.
     * @throws MySQLException
     */
    public function testSortWhenSomeExist() {
        $page = new Page(array("parentid" => $this->parentIdForZero));
        $page->writeToDB(false, true);

        $secondPage = new Page(array("parentid" => $this->parentIdForZero));
        $secondPage->onBeforeWrite(new ModelWriter($page, IModelRepository::COMMAND_TYPE_PUBLISH, $page, Core::repository()));
        $this->assertEqual($page->sort + 1, $secondPage->sort);
    }

    /**
     * tests parent-type.
     */
    public function testParentType() {

    }

    public function unitTestParentType($page, $expected) {

    }
}