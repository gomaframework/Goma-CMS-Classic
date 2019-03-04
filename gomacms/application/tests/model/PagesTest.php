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

    /**
     * checks if canInsert is trying to solve the problem with parent.
     *
     * 1. Ensure no suer is logged in
     * 2. Create Page $parent
     * 3. Assign PermissionMock to $parent->edit_permission, $has = true
     * 4. Write page $parent
     * 5. Create Page $child
     * 6. Assign $child->parent to $parent
     * 7. Assert that $child->can("Insert") is true
     * @throws Exception
     */
    public function testCanInsertParent() {
        try {
            $current = Member::$loggedIn;
            Member::InitUser(null);

            $parent = new Page();
            $mock = new PermissionMock(array("type" => "users"));
            $mock->has = true;
            $parent->setEdit_Permission($mock);
            $parent->writeToDB(false, true);

            $child = new Page();
            $child->parent = $parent;
            $this->assertTrue($child->can("Insert"));
        } finally {
            Member::InitUser($current);

            if($parent) {
                $parent->remove(true);
            }
        }
    }

    /**
     * tests simple hierarchy
     *
     * 1. Create page "Test" $parent
     * 2. Create page "Child" $child, set parent $parent
     * 3. Write $parent
     * 4. Write $child
     * 5. Assert that $parent->children() contains 1 page
     * 6. Assert that $parent->children()->first() is equal to $child
     * 7. Assert that $parent->getAllChildVersionIDs() returns array with $child->versionid
     * @throws Exception
     */
    public function testHierarchyChildren() {
        try {
            $parent = new Page(array(
                "title" => "Test"
            ));
            $child = new Page(array(
                "title" => "Child",
                "parent" => $parent
            ));
            $parent->writeToDB(false, true);
            $child->writeToDB(false, true);

            $this->assertEqual(1, $parent->children()->count());
            $this->assertEqual($child->id, $parent->children()->first()->id);
            $this->assertEqual(array($child->versionid), $parent->getAllChildVersionIDs());
        } finally {
            if($parent) {
                $parent->remove(true);
            }

            if($child) {
                $child->remove(true);
            }
        }
    }

    /**
     * tests simple hierarchy
     *
     * 1. Create page "Test" $parent
     * 2. Create page "Child" $child, set parent $parent
     * 3. Write $parent as state
     * 4. Write $child as state
     * 5. Assert that $parent->children()->setVersion(DataObject::VERSION_DATA) contains 1 page
     * 6. Assert that $parent->children()->setVersion(DataObject::VERSION_DATA)->first() is equal to $child
     * @throws Exception
     */
    public function testHierarchyChildrenState() {
        try {
            $parent = new Page(array(
                "title" => "Test"
            ));
            $child = new Page(array(
                "title" => "Child",
                "parent" => $parent
            ));
            $parent->writeToDB(false, true, 1);
            $child->writeToDB(false, true, 1);

            $this->assertEqual(1, $parent->children()->setVersion(DataObject::VERSION_STATE)->count());
            $this->assertEqual($child->id, $parent->children()->setVersion(DataObject::VERSION_STATE)->first()->id);
            $this->assertEqual(array($child->versionid), $parent->getAllChildVersionIDs(DataObject::VERSION_STATE));
        } finally {
            if($parent) {
                $parent->remove(true);
            }

            if($child) {
                $child->remove(true);
            }
        }
    }

    /**
     * tests simple hierarchy
     *
     * 1. Create page "Test" $parent
     * 2. Create page "Child" $child, set parent $parent
     * 3. Write $parent as state
     * 4. Write $child as state
     * 5. Assert that $parent->children()->setVersion(DataObject::VERSION_DATA) contains 1 page
     * 6. Assert that $parent->children()->setVersion(DataObject::VERSION_DATA)->first() is equal to $child
     * 7. Assert that $parent->getAllChildVersionIDs(DataObject::VERSION_STATE) is array of $child->versionid
     * 8. Store $child->versionid as $oldChildVersionId
     * 9. Publish $parent and $child
     * 10. Assert that $child->versionid is equal to $oldChildVersionId (Publish has happened, but no write)
     * 10. Assert that $parent->getAllChildVersionIDs() is array of $child->versionid
     * @throws Exception
     */
   /* TODO: Make it work
   public function testHierarchyChildrenStateBecomingPublish() {
        try {
            $parent = new Page(array(
                "title" => "Test"
            ));
            $child = new Page(array(
                "title" => "Child",
                "parent" => $parent
            ));
            $parent->writeToDB(false, true, 1);
            $child->writeToDB(false, true, 1);

            $this->assertEqual(1, $parent->children()->setVersion(DataObject::VERSION_STATE)->count());
            $this->assertEqual($child->id, $parent->children()->setVersion(DataObject::VERSION_STATE)->first()->id);
            $this->assertEqual(array($child->versionid), $parent->getAllChildVersionIDs(DataObject::VERSION_STATE));

            $oldChildVersionId = $child->versionid;
            $parent->writeToDB(false, true, 2, false, true, true);
            $child->writeToDB(false, true, 2, false, true, true);
            $this->assertEqual($oldChildVersionId, $child->versionid);
            $this->assertEqual(array($child->versionid), $parent->getAllChildVersionIDs());

        } finally {
            if($parent) {
                $parent->remove(true);
            }

            if($child) {
                $child->remove(true);
            }
        }
    }*/

    /**
     * tests if pages class can create filename which is not taken yet.
     * 1. Create random path $randomPath
     * 2. Create $page1 with title $randomPath
     * 3. Write $page1 as state
     * 4. Create $page2 with title $randomPath
     * 5. Write $page2 as state
     * 6. Assert that $page1->path is different from $page2->path
     */
    public function testFindFilenameNotSelectedState() {
        try {
            $randomPath = randomString(10);
            $page1 = new Page(array(
                "title" => $randomPath
            ));
            $page1->writeToDB(false, true, 1);

            $page2 = new Page(array(
                "title" => $randomPath
            ));
            $page2->writeToDB(false, true, 1);

            $this->assertNotEqual($page1->path, $page2->path);
        } finally {
            if($page1) {
                $page1->remove(true);
            }

            if($page2) {
                $page2->remove(true);
            }
        }
    }
}

class PermissionMock extends Permission {
    /**
     * @var bool
     */
    static $db = array("has" => "Switch");

    /**
     * @param null $user
     * @return bool
     */
    public function hasPermission($user = null)
    {
        return (bool) $this->has;
    }
}
