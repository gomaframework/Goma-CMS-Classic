<?php defined("IN_GOMA") OR die();
/**
 * Unit-Tests for Livecounter-Class.
 *
 * @package		Goma\Livecounter
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class LiveCounterTest extends GomaUnitTest
{
    /**
     * tests getSSOHost for webservice.soredi-touch-systems.com
     */
    public function testGetSSOHostWebservice() {
        $reflectionMethod = new ReflectionMethod(livecounter::class, "getSSOHost");
        $reflectionMethod->setAccessible(true);
        $this->assertEqual(".soredi-touch-systems.com", $reflectionMethod->invoke(null, "webservice.soredi-touch-systems.com"));
    }

    /**
     * tests getSSOHost for soredi-touch-systems.com
     */
    public function testGetSSOHostSOREDI() {
        $reflectionMethod = new ReflectionMethod(livecounter::class, "getSSOHost");
        $reflectionMethod->setAccessible(true);
        $this->assertEqual(".soredi-touch-systems.com", $reflectionMethod->invoke(null, "soredi-touch-systems.com"));
    }

    /**
     * tests getSSOHost for 127.0.0.1
     */
    public function testGetSSOHos127001() {
        $reflectionMethod = new ReflectionMethod(livecounter::class, "getSSOHost");
        $reflectionMethod->setAccessible(true);
        $this->assertEqual(null, $reflectionMethod->invoke(null, "127.0.0.1"));
    }
}
