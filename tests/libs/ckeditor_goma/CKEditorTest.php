<?php
namespace Goma\Test;
use GomaCKEditor;
use GomaUnitTest;

defined("IN_GOMA") OR die();
/**
 * Unit-Tests for CKEditor-Class.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class CKEditorTest extends GomaUnitTest {

    static $hookCalled = 0;

    /**
     * hook
     */
    public static function ckhook() {
        self::$hookCalled++;
    }

    /**
     * tests if hook is called.
     */
    public function testCKHook() {
        self::$hookCalled = 0;
        $this->assertEqual(0, self::$hookCalled);

        $editor = new GomaCKEditor();
        $editor->addEditorJS("test", "html", "lalala");

        $this->assertEqual(1, self::$hookCalled);
    }
}

\Core::addToHook(\GomaCKEditor::ADD_JS_HOOK, array(CKEditorTest::class, "ckhook"));
