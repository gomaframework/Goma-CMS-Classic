<?php defined("IN_GOMA") OR die();
/**
 * Unit-Tests for DataObject-Field-Implementation.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */

class ManyManyModelWriterTest extends GomaUnitTest implements TestAble
{
    /**
     * area
     */
    static $area = "ManyMany";

    /**
     * internal name.
     */
    public $name = "ManyManyModelWriterTest";

    /**
     *
     */
    public function testInit() {
        $model = new ManyManyModelWriter();
        $this->assertIsA($model, ManyManyModelWriter::class);
    }

    /**
     * tests adding stuff to ManyManyRelationship before writing object first time.
     */
    public function testAddToManyManyBeforeExisting() {
        try {
            $user = new \User(array(
                "email"    => "my@goma-cms.org",
                "password" => "1234"
            ));
            $user->writeToDB(false, true);

            $permission = \Permission::forceExisting(User::USERS_PERMISSION);
            $this->assertInstanceOf(\Permission::class, $permission);

            $group = new \Group(array(
                "name" => "notifiergroup"
            ));
            $group->permissions()->add($permission);
            $group->users()->add($user);
            $group->writeToDB(false, true);

            $this->assertEqual($group->users()->first(), $user);
            $this->assertEqual(User::USERS_PERMISSION, $group->permissions()->first()->name);

            $groupFromDB = DataObject::get(Group::class, $group->id);
            $this->assertEqual($groupFromDB->users()->first(), $user);
            $this->assertEqual(User::USERS_PERMISSION, $groupFromDB->permissions()->first()->name);
        } catch(Exception $e) {
            if($user) {
                $user->remove(true);
            }

            if($group) {
                $group->remove(true);
            }
        }
    }
}
