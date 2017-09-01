<?php defined("IN_GOMA") OR die();
/**
 * Unit-Tests for one TableField-Component.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */

class TableFieldDataColumnsTest extends TableComponentFieldTests
{
    /**
     * internal name.
     */
    public $name = "TableFieldDataColumns";

    public function GetField()
    {
        $field = new TableFieldDataColumns();
        $field->setDisplayFields(array(
            "test" => "blah"
        ));
        return $field;
    }

    /**
     * tests if setDisplayFields works.
     */
    public function testSetDisplayFields() {
        $model = new FakeModelWithsummaryFields();
        $model->_data = true;

        $tableField = new TableField("test", "blah", $model);

        $field = new TableFieldDataColumns();
        $this->assertEqual(array(), $field->getDisplayFields($tableField));

        $fields = array(
            "test" => "blah"
        );
        $field->setDisplayFields($fields);

        $this->assertEqual($field->getDisplayFields($tableField), $fields);
    }
}

class FakeModelWithsummaryFields extends ViewAccessableData {
    public static $summaryFields;
    public $_data;

    public function summaryFields() {
        if($this->_data) {
            throw new LogicException("SummaryFields is not allowed to be called on Instance of object.");
        }

        return self::$summaryFields;
    }
}
