<?php defined("IN_GOMA") OR die();
/**
 * Unit-Tests for HTMLText-Field.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class PermissionCheckerTest extends GomaUnitTest implements Testable {
	

	/**
	 * area
	*/
	static $area = "framework";

	/**
	 * internal name.
	*/
	public $name = "PermissionChecker";
	/**
	 * setup test
	*/
	public function setUp() {

		if(file_exists(FRAMEWORK_ROOT . "temp/testNotWritable")) {
			$this->tearDown();
		}

		mkdir(FRAMEWORK_ROOT . "temp/testNotWritable", 0000, true);
		mkdir(FRAMEWORK_ROOT . "temp/testWritable", 0777, true);
	}

	public function tearDown() {
		@rmdir(FRAMEWORK_ROOT . "temp/testNotWritable");
		@rmdir(FRAMEWORK_ROOT . "temp/testWritable");
		@rmdir(FRAMEWORK_ROOT . "temp/testNewFolder");
	}

	public function testPermissionChecker() {
		$permChecker = new PermissionChecker();
		$permChecker->addFolders(array(
			"system/temp/testNotWritable",
			"system/temp/testWritable"
		));
		$permChecker->setPermissionMode(false);

		$this->assertEqual($permChecker->tryWrite(), array("system/temp/testNotWritable"));
		$permChecker->setPermissionMode(0777);
		$this->assertEqual($permChecker->tryWrite(), array());

		$permChecker->addFolders(array(
			"system/temp/testNewFolder"
		));

		$this->assertEqual($permChecker->tryWrite(), array());
		$this->assertTrue(file_exists("system/temp/testNewFolder"));
	}

	public function testPermissionOptions() {
		$perms = array(0777, 0755, 0775, 0774, 0111, 000, 0444, 0111, 0222, 0555, 0711, 0744, 999);

		foreach($perms as $perm) {
			$this->assertEqual(PermissionChecker::isValidPermission($perm), true, "Test Permission $perm; Should pass.");
		}

		$permsNotMatch = array(1234, -1, 55555);

		foreach($permsNotMatch as $perm) {
			$this->assertEqual(PermissionChecker::isValidPermission($perm), false, "Test Permission $perm; Should fail.");
		}
	}

    /**
     * tests simple hierarchy
     *
     * 1. Create Permission $parent
     * 2. Create Permission $child, set parent $parent
     * 3. Write $parent
     * 4. Write $child
     * 5. Assert that $parent->children() contains 1 Permission
     * 6. Assert that $parent->children()->first() is equal to $child
     * 7. Assert that $parent->getAllChildVersionIDs() returns array with $child->versionid
     */
    public function testHierarchyChildren() {
        try {
            $parent = new Permission(array(
                "type" => "all"
            ));
            $child = new Permission(array(
                "type" => "users",
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
     * tests complex hierarchy
     *
     * 1. Create Permission $parent
     * 2. Create Permission $child, set parent $parent
     * 3. Create Permission $childChild, set parent to $child
     * 4. Write $parent
     * 5. Write $child
     * 6. Write $childChild
     * 7. Assert that $parent->getAllChildren() contains two objects
     * 8. Assert that $childChild->getAllParents() contains two objects
     */
    public function testComplexHierarchyChildren() {
        try {
            $parent = new Permission(array(
                "type" => "users"
            ));
            $child = new Permission(array(
                "type" => "all",
                "parent" => $parent
            ));
            $childChild = new Permission(array(
                "type" => "all",
                "parent" => $child
            ));

            $parent->writeToDB(false, true, 1);
            $child->writeToDB(false, true, 1);
            $childChild->writeToDB(false, true);

            $this->assertEqual(2, $parent->getAllChildren()->count());
            $this->assertEqual(2, $childChild->getAllParents()->count());
        } finally {
            if($parent) {
                $parent->remove(true);
            }

            if($child) {
                $child->remove(true);
            }

            if($childChild) {
                $childChild->remove(true);
            }
        }
    }

    /**
     * tests complex permission hierarchy + hasPermission
     *
     * 1. Create Permission $parent
     * 2. Create Permission $child, set parent $parent
     * 3. Create Permission $childChild, set parent to $child
     * 4. Write $parent
     * 5. Write $child
     * 6. Write $childChild
     * 7. Assert that $childChild->hasPermission(null) is false
     * 8. Assert that $childChild->hasPermission(new User()) is true
     */
    public function testComplexHierarchyHasPermission() {
        try {
            $currentUser = Member::$loggedIn;
            Member::InitUser(null);

            $parent = new Permission(array(
                "type" => "users"
            ));
            $child = new Permission(array(
                "type" => "all",
                "parent" => $parent
            ));
            $childChild = new Permission(array(
                "type" => "all",
                "parent" => $child
            ));

            $parent->writeToDB(false, true, 1);
            $child->writeToDB(false, true, 1);
            $childChild->writeToDB(false, true);

            $this->assertFalse($childChild->hasPermission());
            $this->assertTrue($childChild->hasPermission(new User()));
        } finally {
            Member::InitUser($currentUser);
            if($parent) {
                $parent->remove(true);
            }

            if($child) {
                $child->remove(true);
            }

            if($childChild) {
                $childChild->remove(true);
            }
        }
    }
}