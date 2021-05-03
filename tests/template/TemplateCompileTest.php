<?php
namespace Goma\Test\View;
defined("IN_GOMA") OR die();
/**
 * Unit-Tests for framework templates.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */

class TemplateCompileTest extends \GomaUnitTest {
    /**
     * template-path.
     */
    static function template_path () {
        return "system/templates";
    }

    /**
     * scans template path and tries to compile.
     */
    public function testCompile() {
        $this->assertTrue(file_exists(static::template_path()));

        $errors = array();

        $this->scanPathRecursive(static::template_path(), $errors);
        $this->assertEqual(array(), $errors, 'Problems in templates: ' . print_r($errors, true));
    }

    /**
     * scans path recursivly and checks.
     *
     * @param string $path
     * @param array $errors
     */
    protected function scanPathRecursive($path, &$errors) {
        foreach(scandir($path) as $file) {
            if($file == "." || $file == "..") {
                continue;
            }

            if(is_dir($path . "/" . $file)) {
                $this->scanPathRecursive($path . "/" . $file, $errors);
            } else if(substr($file, -5) == ".html") {
                if(!$this->checkSyntax(\tpl::compile(file_get_contents($path . "/" . $file)))) {
                    $errors[] = $path . "/" . $file;
                }
            }
        }
    }
}
