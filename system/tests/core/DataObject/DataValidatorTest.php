<?php
namespace Goma\Test\Model;
use FormMultiFieldInvalidDataException;
use Goma\Model\Validation\DataValidator;
use GomaUnitTest;

defined("IN_GOMA") OR die();

/**
 * Unit-Tests for DataValidator-Class.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class DataValidatorTest extends GomaUnitTest {
    /**
     * tests if required fields are checked
     */
    public function testRequiredFields() {
        $model = new \ViewAccessableData(array(
            "blah" => 0
        ));
        $dataValidator = new DataValidator($model, array(
            "blah" => "blah"
        ));
        try {
            $dataValidator->validate();
            $this->assertFalse(true, "Exceptions should have been thrown.");
        } catch(\Exception $e) {
            $this->assertInstanceOf(FormMultiFieldInvalidDataException::class, $e);
            /** @var FormMultiFieldInvalidDataException $e */
            $this->assertEqual(2, count($e->getFieldsMessages()));
            $this->assertTrue(isset($e->getFieldsMessages()["blah"]));
        }
    }

    /**
     * tests if required fields are checked
     */
    public function testRequiredFieldsValid() {
        $model = new \ViewAccessableData(array(
            "blah" => 1
        ));
        $dataValidator = new DataValidator($model, array(
            "blah" => "blah"
        ));
        $dataValidator->validate();
        $this->assertTrue(true);
    }
    
    /**
     * tests if required fields are checked, even if ToArray does not provide them.
     */
    public function testRequiredFieldsWithoutHavingItInToArray() {
        $model = new \ViewAccessableData(array(
            "blub" => 1
        ));
        $dataValidator = new DataValidator($model, array(
            "blah" => "blah"
        ));
        try {
            $dataValidator->validate();
            $this->assertFalse(true, "Exceptions should have been thrown.");
        } catch(\Exception $e) {
            $this->assertInstanceOf(FormMultiFieldInvalidDataException::class, $e);
            /** @var FormMultiFieldInvalidDataException $e */
            $this->assertEqual(2, count($e->getFieldsMessages()));
            $this->assertTrue(isset($e->getFieldsMessages()["blah"]));
        }
    }
}
