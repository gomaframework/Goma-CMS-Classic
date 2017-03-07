<?php
namespace Goma\Test;
use tpl;

defined("IN_GOMA") OR die();
/**
 * Tests tpl-class.
 *
 * @package GerSozi
 *
 * @author Goma Team
 * @copyright 2017 Goma Team
 *
 * @version 1.0
 */

class TplTest extends \GomaUnitTest {
    /**
     *
     */
    public function testCompileEmpty() {
        $this->assertEqual("", tpl::compile(""));
    }

    /**
     *
     */
    public function testCompileControl() {
        $this->assertNotEqual("", tpl::compile("<% CONTROL this() %> \$this.name <% ENDCONTROL %>"));
    }

    /**
     *
     */
    public function testCompileControlAs() {
        $this->assertNotEqual("", tpl::compile("<% CONTROL this() AS \$blub %> \$blub.name <% ENDCONTROL %>"));
    }

    /**
     *
     */
    public function testCompileControlWithDollar() {
        $this->assertNotEqual("", tpl::compile("<% CONTROL \$this() %> \$this.name <% ENDCONTROL %>"));
        $this->assertEqual(tpl::compile("<% CONTROL this() %> \$this.name <% ENDCONTROL %>")
            , tpl::compile("<% CONTROL this() %> \$this.name <% ENDCONTROL %>"));
    }


    /**
     *
     */
    public function testCompileControlWithDollarAs() {
        $this->assertNotEqual("", tpl::compile("<% CONTROL \$this() AS \$blub %> \$blub.name <% ENDCONTROL %>"));
        $this->assertEqual(tpl::compile("<% CONTROL this()  AS \$blub  %> \$blub.name <% ENDCONTROL %>")
            , tpl::compile("<% CONTROL this() AS \$blub %> \$blub.name <% ENDCONTROL %>"));
    }
}
