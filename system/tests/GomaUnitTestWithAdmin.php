<?php
namespace Goma\Test;
use DataObject;
use User;


/**
 * Base-Class with the feature of an admin-user.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class GomaUnitTestWithAdmin extends \GomaUnitTest
{
    /**
     * @var null|\User
     */
    protected $adminUser = null;

    public function setUp() {
        if($group = DataObject::get_one("group", array(
            "permissions" => array(
                "name" => "superadmin"
            )
        ))) {
            $this->adminUser = new User();
            $this->adminUser->nickname = "admintest@vorort.news";
            $this->adminUser->email = $this->adminUser->nickname;
            $this->adminUser->password = randomString(10);
            $this->adminUser->name = "MyAdmin";
            $this->adminUser->writeToDB(false, true);

            $this->adminUser->groups()->add($group)->commitStaging(false, true);
        }
    }

    public function tearDown() {
        if($this->adminUser) {
            $this->adminUser->remove(true);
        }
    }
}
