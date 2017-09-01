<?php defined("IN_GOMA") OR die();
/**
 * @package		Goma\Test\System\Core
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class i18nTest extends GomaUnitTest {
    /**
     * tests if compilation worked.
     */
    public function testL() {
        $this->assertEqual("LOGIN", L::LOGIN);
    }
}
