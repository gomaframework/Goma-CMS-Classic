<?php defined("IN_GOMA") OR die();
/**
 * Unit-Tests for Member-Class.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */


class MemberTest extends GomaUnitTest {
	/**
	 * area
	*/
	static $area = "User";

	/**
	 * internal name.
	*/
	public $name = "Member";


	/**
	 * @throws MySQLException
	 */
	public function testLogin() {
		try {
			$newUser = new \User();
			$newUser->nickname = $newUser->email = "beta@goma-cms.org";
			$newUser->password = "1234";
			$newUser->writeToDB(false, true);

			$loggedInUser = \AuthenticationService::sharedInstance()->checkLogin("beta@goma-cms.org", "1234");
			$this->assertInstanceOf(\User::class, $loggedInUser);
			$this->assertEqual($loggedInUser->id, $newUser->id);
		} finally {
			if($newUser) {
				$newUser->remove(true);
			}
		}
	}
}
