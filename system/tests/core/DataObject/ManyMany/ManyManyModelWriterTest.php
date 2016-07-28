<?php defined("IN_GOMA") OR die();
/**
 * Unit-Tests for DataObject-Field-Implementation.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */

class ManyManyModelWriterTest extends GomaUnitTest implements TestAble
{
    /**
     * area
     */
    static $area = "ManyMany";

    /**
     * internal name.
     */
    public $name = "ManyManyModelWriterTest";

    /**
     *
     */
    public function testInit() {
        $model = new ManyManyModelWriter();
        $this->assertIsA($model, ManyManyModelWriter::class);
    }
}
