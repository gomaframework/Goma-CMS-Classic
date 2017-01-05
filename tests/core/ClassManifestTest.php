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

    /**
     * tests parse usings.
     */
    public function testParseUsings() {
        $method = new ReflectionMethod("ClassManifest", "parseUsings");
        $method->setAccessible(true);

        $this->assertEqual(array(), $method->invoke(null, ""));
        $this->assertEqual(array(
            "blub"      => "chobie\\blub",
            "api"       => "api",
            "core"      => "Goma\\Core",
        ), $method->invoke(null, '<?php use chobie\\blub;use api;use Goma\\Core;'));
        $this->assertEqual(array(
            "blub"      => "chobie\\blub",
            "api"       => "api",
            "core"      => "Goma\\Core",
        ), $method->invoke(null, '<?php use chobie\\blub;
        use api;
        use Goma\\Core;'));
    }

    /**
     * tests how good it is in parsing one interface.
     */
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

    /**
     * tests how good it is in parsing interfaces.
     */
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

    /**
     * tests how good it is in parsing classes + interfaces. without namespace here.
     */
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

    /**
     * tests how good it is in parsing classes + interfaces. With namespace here.
     */
    public function testParseClassAndInterfaceWithNamespace()
    {
        $classes = $class_info = array();

        $method = new ReflectionMethod("ClassManifest", "parsePHPFile");
        $method->setAccessible(true);
        $method->invokeArgs(null, array(
            "system/tests/core/ClassManifestTestPath/classAndInterfaceWithNamespaceTest.php", &$classes, &$class_info
        ));

        $this->assertEqual(array (
            'blub\\test' =>
                array (
                ),
            'blub\\test3' =>
                array (
                ),
            'blub\\myclass' =>
                array (
                    'parent' => 'blub\\test',
                    'interfaces' =>
                        array (
                            0 => 'blub\\i1',
                        ),
                ),
            'blub\\myclass2' =>
                array (
                    'parent' => 'blub\\test',
                    'interfaces' =>
                        array (
                            0 => 'blub\\i1',
                            1 => 'blub\\i3',
                            2 => 'blub\\i4',
                        ),
                ),
            'blub\\i1' =>
                array (
                    'abstract' => true,
                    'interface' => true,
                ),
            'blub\\i3' =>
                array (
                    'abstract' => true,
                    'interface' => true,
                ),
            'blub\\i4' =>
                array (
                    'abstract' => true,
                    'interface' => true,
                ),
        ), $class_info);

        $this->assertEqual(array (
            'blub\\test' => 'system/tests/core/ClassManifestTestPath/classAndInterfaceWithNamespaceTest.php',
            'blub\\test3' => 'system/tests/core/ClassManifestTestPath/classAndInterfaceWithNamespaceTest.php',
            'blub\\myclass' => 'system/tests/core/ClassManifestTestPath/classAndInterfaceWithNamespaceTest.php',
            'blub\\myclass2' => 'system/tests/core/ClassManifestTestPath/classAndInterfaceWithNamespaceTest.php',
            'blub\\i1' => 'system/tests/core/ClassManifestTestPath/classAndInterfaceWithNamespaceTest.php',
            'blub\\i3' => 'system/tests/core/ClassManifestTestPath/classAndInterfaceWithNamespaceTest.php',
            'blub\\i4' => 'system/tests/core/ClassManifestTestPath/classAndInterfaceWithNamespaceTest.php',
        ), $classes);
    }

    /**
     * tests how good it is in parsing classes + interfaces. With namespace here.
     * We also use usings.
     */
    public function testParseClassAndInterfaceWithNamespacAndUsing()
    {
        $classes = $class_info = array();

        $method = new ReflectionMethod("ClassManifest", "parsePHPFile");
        $method->setAccessible(true);
        $method->invokeArgs(null, array(
            "system/tests/core/ClassManifestTestPath/classAndInterfaceWithNamespaceAndUsingTestMock.php", &$classes, &$class_info
        ));

        $this->assertEqual(array (
            'blub\\test' =>
                array (
                    "parent" => "goma\\blub"
                ),
            'blub\\test3' =>
                array (
                ),
            'blub\\myclass' =>
                array (
                    'parent' => 'blub\\test',
                    'interfaces' =>
                        array (
                            0 => 'blub\\i1',
                        ),
                ),
            'blub\\myclass2' =>
                array (
                    'parent' => 'blub\\test',
                    'interfaces' =>
                        array (
                            0 => 'blub\\i1',
                            1 => 'blub\\i3',
                            2 => 'blub\\i4',
                            3 => "goma\\i5"
                        ),
                ),
            'blub\\i1' =>
                array (
                    'abstract' => true,
                    'interface' => true,
                ),
            'blub\\i3' =>
                array (
                    'abstract' => true,
                    'interface' => true,
                ),
            'blub\\i4' =>
                array (
                    'abstract' => true,
                    'interface' => true,
                ),
        ), $class_info);

        $this->assertEqual(array (
            'blub\\test' => 'system/tests/core/ClassManifestTestPath/classAndInterfaceWithNamespaceAndUsingTestMock.php',
            'blub\\test3' => 'system/tests/core/ClassManifestTestPath/classAndInterfaceWithNamespaceAndUsingTestMock.php',
            'blub\\myclass' => 'system/tests/core/ClassManifestTestPath/classAndInterfaceWithNamespaceAndUsingTestMock.php',
            'blub\\myclass2' => 'system/tests/core/ClassManifestTestPath/classAndInterfaceWithNamespaceAndUsingTestMock.php',
            'blub\\i1' => 'system/tests/core/ClassManifestTestPath/classAndInterfaceWithNamespaceAndUsingTestMock.php',
            'blub\\i3' => 'system/tests/core/ClassManifestTestPath/classAndInterfaceWithNamespaceAndUsingTestMock.php',
            'blub\\i4' => 'system/tests/core/ClassManifestTestPath/classAndInterfaceWithNamespaceAndUsingTestMock.php',
        ), $classes);
    }

    /**
     * tests if resolving works with namespaces.
     */
    public function testResolveFromUsing() {
        $method = new ReflectionMethod(ClassManifest::class, "resolveClassNameWithUsings");
        $method->setAccessible(true);

        $this->assertEqual("Test\\test", $method->invoke(null, "Test", "Test\\", array()));
        $this->assertEqual("lala\\test", $method->invoke(null, "Test", "Test\\", array(
            "test" => "lala\\test"
        )));
        $this->assertEqual("lala\\test", $method->invoke(null, "TEST", "Test\\", array(
            "test" => "LALA\\test"
        )));
    }

    /**
     * tests if resolving works with classes which are full qualified.
     */
    public function testResolveFromUsingWithFullQualified() {
        $method = new ReflectionMethod(ClassManifest::class, "resolveClassNameWithUsings");
        $method->setAccessible(true);

        $this->assertEqual("test", $method->invoke(null, "\\Test", "Test\\", array()));
        $this->assertEqual("test", $method->invoke(null, "\\Test", "Test\\", array(
            "test" => "lala\\test"
        )));
        $this->assertEqual("test", $method->invoke(null, "\\TEST", "Test\\", array(
            "test" => "LALA\\test"
        )));
    }
}
