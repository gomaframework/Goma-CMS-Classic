<?php
defined("IN_GOMA") OR die();
/**
 * Unit-Tests for HiddenField.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */

class HiddenFieldTest extends GomaUnitTest implements TestAble {
    /**
     * tests if result returns value.
     */
    public function testResult() {
        $hidden = new HiddenField("test", "123");
        $this->assertEqual("123", $hidden->result());
    }

    /**
     * tests if argumentResult adds value with key test.
     */
    public function testArgumentResult() {
        $hidden = new HiddenField("test", "123");
        $result = array();
        $hidden->argumentResult($result);
        $this->assertEqual(array("test" => "123"), $result);
    }

    /**
     * tests that post-data is not able to change value of hidden field.
     */
    public function testHiddenPost() {
        $form = new Form(new Controller(), "test");
        $request = new Request("post", "", array(), array("test" => 1234));
        $form->setRequest($request);
        $form->add($hidden = new HiddenField("test", "123"));
        $result = array();
        $hidden->argumentResult($result);
        $this->assertEqual(array("test" => "123"), $result);
    }
}
