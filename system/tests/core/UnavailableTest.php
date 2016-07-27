<?php defined("IN_GOMA") OR die();
/**
 * Unit-Tests for 503-Handling.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class UnavailableTest extends GomaUnitTest implements TestAble {

	
	static $area = "framework";
	/**
	 * name
	*/
	public $name = "UnavailableTest";


	/**
	 * test availability functions.
	*/
	public function testAvailability() {
		$remoteAddr = isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : "cli";

		$this->assertEqual(isProjectUnavailable(), false);
		$this->assertEqual(isProjectUnavailableForIP($remoteAddr), false);

		makeProjectUnavailable();

		$this->assertEqual(isProjectUnavailable(), true);
		$this->assertEqual(isProjectUnavailableForIP($remoteAddr), false);
		$this->assertEqual(isProjectUnavailableForIP("1.2.3.4"), true);

		makeProjectAvailable();

		$this->assertEqual(isProjectUnavailable(), false);
		$this->assertEqual(isProjectUnavailableForIP($remoteAddr), false);
		$this->assertEqual(isProjectUnavailableForIP("1.2.3.4"), false);

		if(!isCommandLineInterface()) {
			makeProjectUnavailable(APPLICATION, "1.2.3.4");

			$this->assertEqual(isProjectUnavailable(), true);
			$this->assertEqual(isProjectUnavailableForIP($remoteAddr), true);
			$this->assertEqual(isProjectUnavailableForIP("1.2.3.4"), false);

			makeProjectUnavailable();

			$this->assertEqual(isProjectUnavailable(), true);
			$this->assertEqual(isProjectUnavailableForIP($remoteAddr), false);
			$this->assertEqual(isProjectUnavailableForIP("1.2.3.4"), true);

			makeProjectAvailable();

			$this->assertEqual(isProjectUnavailable(), false);
			$this->assertEqual(isProjectUnavailableForIP($remoteAddr), false);
			$this->assertEqual(isProjectUnavailableForIP("1.2.3.4"), false);
		}
	}
}