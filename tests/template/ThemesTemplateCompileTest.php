<?php

namespace tests\template;
use Goma\Test\View\TemplateCompileTest;

defined("IN_GOMA") OR die();

/**
 * Unit-Tests for themes.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */

class ThemesTemplateCompileTest extends TemplateCompileTest
{
    static function template_path()
    {
        return "tpl";
    }
}