<?php
namespace Goma\Test;
use tpl;

defined("IN_GOMA") OR die();
/**
 * Tests tpl-class.
 *
 * @package Goma
 *
 * @author Goma Team
 * @copyright 2017 Goma Team
 *
 * @version 1.0
 */
class TplTest extends \GomaUnitTest {
    /**
     * tests if empty string is compiling to empty string.
     */
    public function testCompileEmpty() {
        $this->assertEqual("", tpl::compile(""));
        $this->assertSyntax(tpl::compile(""));
    }

    /**
     * tests if control is compiling.
     */
    public function testCompileControl() {
        $template = "<% CONTROL this() %> \$this.name <% ENDCONTROL %>";

        $this->assertNotEqual("", tpl::compile($template));
        $this->assertSyntax(tpl::compile($template));
    }

    /**
     * tests if control with as is compiling.
     */
    public function testCompileControlAs() {
        $template = "<% CONTROL this() AS \$blub %> \$blub.name <% ENDCONTROL %>";
        $this->assertNotEqual("", tpl::compile($template));
        $this->assertSyntax(tpl::compile($template));
    }

    /**
     * tests if control with dollar sign is compiling
     */
    public function testCompileControlWithDollar() {
        $template1 = "<% CONTROL \$this() %> \$this.name <% ENDCONTROL %>";
        $template2 = "<% CONTROL this() %> \$this.name <% ENDCONTROL %>";

        $this->assertNotEqual("", tpl::compile($template1));

        $this->assertSyntax(tpl::compile($template1));
        $this->assertSyntax(tpl::compile($template2));
    }


    /**
     * tests if control with dollar sign and as is compiling
     */
    public function testCompileControlWithDollarAs() {
        $template1 = "<% CONTROL \$this() AS \$blub %> \$blub.name <% ENDCONTROL %>";
        $template2 = "<% CONTROL this()  AS \$blub  %> \$blub.name <% ENDCONTROL %>";
        $template3 = "<% CONTROL this() AS \$blub %> \$blub.name <% ENDCONTROL %>";

        $this->assertNotEqual("", tpl::compile($template1));
        $this->assertEqual(tpl::compile($template2)
            , tpl::compile($template3));

        $this->assertSyntax(tpl::compile($template1));
        $this->assertSyntax(tpl::compile($template2));
    }

    /**
     * tests for if statement with not: <% IF $notification.blah %> <% END %>
     *
     * This shouldn't compile the not to an exclamation mark
     */
    public function testNotNotExclamation() {
        $template = "<% IF \$notification.blah %> yes <% END %>";

        $this->assertSyntax( tpl::compile($template));
        $this->assertEqual('<?php if(($data->doObject("notification")->doObject("blah") && $data->doObject("notification")->doObject("blah")->bool())) { ?> yes <?php }  ?>',
            tpl::compile($template));
    }

    /**
     * tests for if statement with not: <% IF NOT $blah %> <% END %>
     *
     * This should compile the not to an exclamation mark
     */
    public function testNotExclamation() {
        $template = "<% IF NOT \$blah %> <% END %>";

        $this->assertSyntax( tpl::compile($template));
        $this->assertEqual('<?php if((!$data->doObject("blah") || !$data->doObject("blah")->bool())) { ?> <?php }  ?>',
            tpl::compile($template));
    }
}
