<?php
namespace Goma\Test\Form;
use ObjectRadioButton;

defined("IN_GOMA") OR die();
/**
 * Unit-Tests for LangSelect.
 *
 * @package        Goma\Test
 *
 * @author        Goma-Team
 * @license        GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class ObjectRadioButtonTest extends \GomaUnitTest {
    /**
     * test init.
     */
    public function testInitObjectRadioButton() {
        $radioButton = new \ObjectRadioButton();
        $this->assertFalse($radioButton->useResultFromField);
        $this->assertTrue($radioButton->hideDisabled);
    }

    /**
     * tests if option useResultFormField has Problems if no fields are selected
     */
    public function testWithResultFromField() {
        $form = new \Form(new \Controller(), "test", array(
            $obj = new ObjectRadioButton("number", "zahl", array(
                0 => "0",
                1 => "1",
                "custom" => array(
                    "custom", "custom"
                )
            )),
            new \NumberField("custom", "custom")
        ));
        $obj->useResultFromField = true;
        $form->setModel($data = array(
            "number" => "0",
            "custom" => 2
        ));
        $this->assertEqual($data, $form->gatherResultForSubmit());
    }

    /**
     * tests if option useResultFormField works, so data is overwritten
     */
    public function testUseResultFromField() {
        $form = new \Form(new \Controller(), "test", array(
            $obj = new ObjectRadioButton("number", "zahl", array(
                0 => "0",
                1 => "1",
                "custom" => array(
                    "custom", "custom"
                )
            )),
            new \NumberField("custom", "custom")
        ));
        $obj->useResultFromField = true;
        $form->setModel($data = array(
            "number" => "custom",
            "custom" => 3
        ));
        $this->assertEqual(array(
            "number" => 3,
            "custom" => 3
        ), $form->gatherResultForSubmit());
    }
}

