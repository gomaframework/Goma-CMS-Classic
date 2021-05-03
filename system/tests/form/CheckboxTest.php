<?php

namespace Goma\Form\Test;

use Controller;

defined("IN_GOMA") or die();

/**
 * Unit-Tests for CheckBox-Class.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class CheckboxTest extends \GomaUnitTest
{
    /**
     * tests if getModel is not using post_data if none is given.
     */
    public function testPostDataNotUsed() {
        $checkBox = new \CheckBox("check");
        $checkBox->setRequest(new \Request("post", "data"));

        $form = new \Form(new Controller(), "form");
        $form->setModel(new \ViewAccessableData(array("check" => true)));
        $checkBox->setForm($form);

        $this->assertTrue($checkBox->getModel());
    }

    /**
     * tests if getModel is not using post_data if it is given.
     */
    public function testPostDataUsed() {
        $checkBox = new \CheckBox("check");
        $checkBox->setRequest(new \Request("post", "data", array(), array("check" => 0)));

        $form = new \Form(new Controller(), "form");
        $form->setModel(new \ViewAccessableData(array("check" => true)));
        $checkBox->setForm($form);

        $this->assertFalse($checkBox->getModel());
    }
}
