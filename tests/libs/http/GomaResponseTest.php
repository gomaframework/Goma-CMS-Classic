<?php
namespace Goma\Test;
defined("IN_GOMA") OR die();

/**
 * Tests Request-Class.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 *
 * @version 1.0
 */
class GomaResponseTest extends \GomaUnitTest {
    /**
     * tests if get body is cloned.
     */
    public function testGetBodyCloned() {
        $response = new \GomaResponse();
        $this->assertNotIdentical($response->getBody(), $response->getBody());
    }
}
