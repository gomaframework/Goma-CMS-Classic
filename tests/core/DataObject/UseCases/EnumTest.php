<?php
namespace Goma\Core\Model\Usecase;
defined("IN_GOMA") OR die();
/**
 * Tests default value of enums.
 *
 * @package Goma\Test
 *
 * @author Goma-Team
 * @copyright 2017 Goma-Team
 *
 * @version 1.0
 */
class EnumDefaultTest extends \GomaUnitTest {
    /**
     * tests enum model without default value.
     */
    public function testInitEnumModel() {
        $model = new EnumModel();
        $model->writeToDB(false, true);

        $this->assertInstanceOf(EnumModel::class, $model);
        $modelFromDb = \DataObject::get_by_id(EnumModel::class, $model->id);
        $this->assertEqual("blah", $modelFromDb->enum);
    }
}

class EnumModel extends \DataObject {
    static $db = array(
        "enum" => "enum('blah', 'blub')"
    );

    static $search_fields = false;
}
