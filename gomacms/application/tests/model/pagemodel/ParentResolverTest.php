<?php defined("IN_GOMA") or die();

/**
 * Unit-Tests for ParentResolver.
 *
 * @package        Goma\Test
 *
 * @author        Goma-Team
 * @license        GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class ParentResolverTest extends GomaUnitTest implements TestAble
{


    static $area = "cms";
    /**
     * name
     */
    public $name = "ParentResolver";

    public function testFilterParents()
    {
        $pages = new ParentResolver("mockAbc", "mockPage");
        $reflectionMethod = new ReflectionMethod("ParentResolver", "filterParents");
        $reflectionMethod->setAccessible(true);

        $allowParents1 = array("test", "abc");

        mockAbc::$allow_parents = $allowParents1;
        $this->assertEquals($allowParents1, $reflectionMethod->invoke($pages, $allowParents1));

        mockAbc::$allow_parents = array("mockAbc");
        $this->assertEquals(array("mockabc"), $reflectionMethod->invoke($pages, array("mockabc")));

        mockAbc::$allow_parents = array(" MOCKABC ");
        $this->assertEquals(array("mockabc"), $reflectionMethod->invoke($pages, array("mockabc")));

        mockAbc::$allow_parents = array("testMock");
        $this->assertEquals(array("testmock"), $reflectionMethod->invoke($pages, array("testmock")));

        mockAbc::$allow_parents = array(" TESTMOCK ");
        $this->assertEquals(array("testmock"), $reflectionMethod->invoke($pages, array("testmock")));

        mockAbc::$allow_parents = array();
        $this->assertEquals(array(), $reflectionMethod->invoke($pages, array()));
    }
}

class mockPage extends Page
{
    static $cname = "";
}

class mockAbc extends mockPage
{
    static $allow_parents = array();
    static $cname = "";
}

class testMock extends mockPage
{
    static $cname = "";
}