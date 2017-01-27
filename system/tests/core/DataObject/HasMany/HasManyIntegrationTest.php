<?php
namespace Goma\Test\Model;
use GomaUnitTest;
use HasMany_DataObjectSet;
use TestAble;

defined("IN_GOMA") OR die();
/**
 * Integration-Tests for DataObject-HasMany-HasOne-Implementation.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */

class HasManyIntegrationTest extends GomaUnitTest implements TestAble
{
    /**
     * area
     */
    static $area = "HasMany";

    /**
     * internal name.
     */
    public $name = "HasManyIntegrationTest";

    /**
     *
     */
    public function testInit() {
        $model = new HasMany_DataObjectSet();
        $this->assertIsA($model, HasMany_DataObjectSet::class);
    }
}
