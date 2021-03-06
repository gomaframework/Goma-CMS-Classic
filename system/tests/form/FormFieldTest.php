<?php defined("IN_GOMA") OR die();
/**
 * Unit-Tests for FormField.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */

class FormFieldTest extends GomaUnitTest implements TestAble {
    /**
     * area
     */
    static $area = "Form";

    /**
     * internal name.
     */
    public $name = "FormField";

    public function testCreate() {
        $form = new Form(new Controller(), "test");

        $this->unitTestCreate("test", "blah", "blub", null);
        $this->unitTestCreate("test2", "blah", "blub", $form);

        $this->assertEqual($form->test2->getName(), "test2");
    }

    public function unitTestCreate($name, $title, $value, $parent) {
        $field = FormField::create($name, $title, $value, $parent);

        $this->assertEqual($field->getName(), $name);
        $this->assertEqual($field->getModel(), $value);
        $this->assertEqual($field->getTitle(), $title);

        if($parent != null) {
            $this->assertEqual($field->form(), $parent);
        } else {
            $this->assertThrows(function() use($field) {
                $field->form();
            }, "LogicException");
        }

        $this->assertEqual($field->PostName(), $name);
        $this->assertEqual($field->isDisabled(), false);
        $this->assertIsA($field->container, "HTMLNode");
        $this->assertIsA($field->input, "HTMLNode");
        $this->assertEqual($field->input->name, $name);
    }

    public function testDisable() {
        $field = new FormField();
        $this->assertFalse($field->isDisabled());

        $field->disable();
        $this->assertTrue($field->isDisabled());
        $this->assertEqual($field->input->disabled, null);
        $this->assertEqual($field->isDisabled(), true);

        $field->field($field->exportBasicInfo());
        $this->assertEqual($field->input->disabled, "disabled");

        $field->enable();
        $this->assertEqual($field->isDisabled(), false);

        $field->field($field->exportBasicInfo());
        $this->assertEqual($field->input->disabled, null);
    }

    public function testResult() {
        $form = new Form(new Controller(), "test");

        $field = new FormField("test", "", "1234", $form);

        $form->getRequest()->post_params = array(
            "test" => "123"
        );

        $this->assertEqual($field->result(), "123");

        $field->disable();
        $this->assertEqual($field->result(), "1234");

        $field->enable();
        $this->assertEqual($field->result(), "123");

        $form->disable();
        $this->assertEqual($field->result(), "1234");

        $form->enable();
        $this->assertEqual($field->result(), "123");

        $prop = new ReflectionProperty("FormField", "POST");
        $prop->setAccessible(true);
        $prop->setValue($field, false);

        $this->assertEqual($field->result(), "1234");
    }

    public function testgetValue() {
        $form = new Form(new Controller(), "test");

        $field = new FormField("test", "", "1234", $form);

        $field->getValue();
        $this->assertEqual($field->value, 1234);

        $form->result = new ViewAccessableData();
    }
}
