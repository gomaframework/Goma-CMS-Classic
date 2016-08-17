<?php defined("IN_GOMA") OR die();
/**
 * Unit-Tests for ClassManifest.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class ClassManifestTest extends GomaUnitTest
{
    static $area = "framework";
    /**
     * name
     */
    public $name = "ClassManifest";


    public function testParseInterface()
    {
        $classes = $class_info = array();

        $method = new ReflectionMethod("ClassManifest", "parsePHPFile");
        $method->setAccessible(true);
        $method->invokeArgs(null, array(
            "system/tests/core/ClassManifestTestPath/interfaceOnlyTest.php", &$classes, &$class_info
        ));

        $this->assertEqual(array(
            "myinterface" => "system/tests/core/ClassManifestTestPath/interfaceOnlyTest.php"
        ), $classes);

        $this->assertEqual(array(
            "myinterface" => array(
                "abstract" => true,
                "interface" => true
            )
        ), $class_info);
    }

    public function testParseInterfaces()
    {
        $classes = $class_info = array();

        $method = new ReflectionMethod("ClassManifest", "parsePHPFile");
        $method->setAccessible(true);
        $method->invokeArgs(null, array(
            "system/tests/core/ClassManifestTestPath/interfacesOnlyTest.php", &$classes, &$class_info
        ));

        $this->assertEqual(array(
            "myinterface1" => "system/tests/core/ClassManifestTestPath/interfacesOnlyTest.php",
            "myinterface2" => "system/tests/core/ClassManifestTestPath/interfacesOnlyTest.php",
            "myinterface3" => "system/tests/core/ClassManifestTestPath/interfacesOnlyTest.php",
            "myinterface4" => "system/tests/core/ClassManifestTestPath/interfacesOnlyTest.php",
            "myinterface5" => "system/tests/core/ClassManifestTestPath/interfacesOnlyTest.php"
        ), $classes);

        $this->assertEqual(array(
            "myinterface1" => array(
                "abstract" => true,
                "interface" => true
            ),
            "myinterface2" => array(
                "abstract" => true,
                "interface" => true
            ),
            "myinterface3" => array(
                "abstract" => true,
                "interface" => true
            ),
            "myinterface4" => array(
                "abstract" => true,
                "interface" => true,
                "parent" => "myinterface3"
            ),
            "myinterface5" => array(
                "abstract" => true,
                "interface" => true,
                "parent" => "myinterface1"
            )
        ), $class_info);
    }

    public function testParseClassAndInterface()
    {
        $classes = $class_info = array();

        $method = new ReflectionMethod("ClassManifest", "parsePHPFile");
        $method->setAccessible(true);
        $method->invokeArgs(null, array(
            "system/tests/core/ClassManifestTestPath/classAndInterfaceTest.php", &$classes, &$class_info
        ));

        $this->assertEqual(array (
            'test' =>
                array (
                ),
            'test3' =>
                array (
                ),
            'myclass' =>
                array (
                    'parent' => 'test',
                    'interfaces' =>
                        array (
                            0 => 'i1',
                        ),
                ),
            'myclass2' =>
                array (
                    'parent' => 'test',
                    'interfaces' =>
                        array (
                            0 => 'i1',
                            1 => 'i3',
                            2 => 'i4',
                        ),
                ),
            'i1' =>
                array (
                    'abstract' => true,
                    'interface' => true,
                ),
            'i3' =>
                array (
                    'abstract' => true,
                    'interface' => true,
                ),
            'i4' =>
                array (
                    'abstract' => true,
                    'interface' => true,
                ),
        ), $class_info);

        $this->assertEqual(array (
            'test' => 'system/tests/core/ClassManifestTestPath/classAndInterfaceTest.php',
            'test3' => 'system/tests/core/ClassManifestTestPath/classAndInterfaceTest.php',
            'myclass' => 'system/tests/core/ClassManifestTestPath/classAndInterfaceTest.php',
            'myclass2' => 'system/tests/core/ClassManifestTestPath/classAndInterfaceTest.php',
            'i1' => 'system/tests/core/ClassManifestTestPath/classAndInterfaceTest.php',
            'i3' => 'system/tests/core/ClassManifestTestPath/classAndInterfaceTest.php',
            'i4' => 'system/tests/core/ClassManifestTestPath/classAndInterfaceTest.php',
        ), $classes);
    }
}
