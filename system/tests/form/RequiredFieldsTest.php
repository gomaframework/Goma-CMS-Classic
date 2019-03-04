<?php defined("IN_GOMA") OR die();
/**
 * Unit-Tests for RequiredFields.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */

class RequiredFieldsTest extends GomaUnitTest implements TestAble {
    /**
     * tests fields.
     */
    public function testRequiredFields() {
        $this->assertNull($this->unitTestRequiredFields(array(
            new TextField("test", "test")
        ), array(
            "test" => 1
        ), array("test")));

        $this->assertNull($this->unitTestRequiredFields(array(
            new TextField("test", "test")
        ), array(
            "test" => 1
        ), array("TEST")));

        $this->assertThrows(function() {
            $this->unitTestRequiredFields(array(
                new TextField("test", "test")
            ), array(
                "test" => ""
            ), array("TEST"));
        }, "FormMultiFieldInvalidDataException");

        $this->assertThrows(function() {
            $this->unitTestRequiredFields(array(
                new TextField("test", "test")
            ), array(
                "test" => 0
            ), array("TEST"));
        }, "FormMultiFieldInvalidDataException");

        $this->assertThrows(function() {
            $this->unitTestRequiredFields(array(
                new TextField("test", "test")
            ), array(
                "test" => array()
            ), array("TEST"));
        }, "FormMultiFieldInvalidDataException");

        $this->assertThrows(function() {
            $this->unitTestRequiredFields(array(
                new TextField("test", "test")
            ), array(
                "test" => new ViewAccessableData()
            ), array("TEST"));
        }, "FormMultiFieldInvalidDataException");

        $this->assertNull($this->unitTestRequiredFields(array(
            new TextField("test", "test")
        ), array(
            "test" => new ViewAccessableData(array(
                "test" => 1
            ))
        ), array("TEST")));

        $this->assertThrows(function() {
            $this->unitTestRequiredFields(array(
                new TextField("test", "test")
            ), array(
                "test" => new BoolTestClass(false)
            ), array("TEST"));
        }, "FormMultiFieldInvalidDataException");

        $this->assertNull($this->unitTestRequiredFields(array(
            new TextField("test", "test")
        ), array(
            "test" => new BoolTestClass(true)
        ), array("TEST")));

        try {
            $this->unitTestRequiredFields(array(
                new TextField("test0", "test1"),
                new TextField("test1", "test2"),
                new TextField("test2", "test3"),
                new TextField("test3", "test4")
            ), array(
                "test0" => 0,
                "test1" => "",
                "test2" => "",
                "test3" => ""
            ), array("TEST1", "test0", "test2", "test3"));

            $this->assertTrue(false);
        } catch(FormMultiFieldInvalidDataException $e) {
            $keys = array_keys($e->getFieldsMessages());
            array_shift($keys);
            $this->assertEqual($keys, array("TEST1", "test0", "test2", "test3"));
        }

        try {
            $this->unitTestRequiredFields(array(), array(), "");

            $this->assertTrue(false);
        } catch(Exception $e) {
            $this->assertIsA($e, "InvalidArgumentException");
        }
    }

    /**
     * unit-test.
     *
     * @param array $fields
     * @param array $result
     * @param array $requiredFields
     * @return bool|string
     * @throws Exception
     */
    protected function unitTestRequiredFields($fields, $result, $requiredFields) {
        $form = new Form(new RequestHandler(), "test", $fields);

        $form->addValidator($required = new RequiredFields($requiredFields), "require");
        $form->result = $result;

        $required->validate();
    }

    /**
     * tests if RequiredFields validates if fields are existing.
     *
     * 1. Create Form with Field test1
     * 2. Add Validator with test1 and test2
     * 3. Assert that InvalidArgumentException has been thrown.
     */
    public function testRequiredFieldsNotExistingValidation() {
        $form = new Form(new RequestHandler(), "test", array(
            new TextField("test1", "test")
        ));

        $this->assertThrows(function() use($form) {
            $form->addValidator($required = new RequiredFields(array("test1", "test2")), "require");
        }, InvalidArgumentException::class);
    }

    /**
     * tests if RequiredFields validates if fields are existing, but doesn't throw if everything is ok.
     *
     * 1. Create Form with Field test1
     * 2. Add Validator with test1
     * 3. Assert that still no exception has been thrown.
     */
    public function testRequiredFieldsExistingValidation() {
        $form = new Form(new RequestHandler(), "test", array(
            new TextField("test1", "test")
        ));

        $form->addValidator($required = new RequiredFields(array("test1")), "require");
        $this->assertTrue(true);
    }
}

class BoolTestClass {
    protected $bool;

    public function __construct($bool) {
        $this->bool = $bool;
    }

    public function bool() {
        return $this->bool;
    }
}
