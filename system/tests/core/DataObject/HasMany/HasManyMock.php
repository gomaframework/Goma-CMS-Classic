<?php

namespace Goma\Test\Model;

defined("IN_GOMA") OR die();
/**
 * Mock classes for HasMany Tests.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */

/**
 * Class MockHasOneClass
 * @property MockHasManyClass one
 * @package Goma\Test\Model
 */
class MockHasOneClass extends \DataObject {

    static $db = array(
        "val" => "int(10)"
    );

    static $has_one = array(
        "one" => MockHasManyClass::class
    );

    static $search_fields = false;

    /**
     * checks if one is set.
     *
     * @param \ModelWriter $modelWriter
     * @throws \FormInvalidDataException
     */
    public function onBeforeWrite($modelWriter)
    {
        parent::onBeforeWrite($modelWriter);

        if(!$this->one && !$this->override) {
            throw new \FormInvalidDataException("one");
        }
    }
}

/**
 * Class MockHasManyClass
 * @property MockHasManyClass one
 * @package Goma\Test\Model
 * @method HasMany_DataObjectSet many($filter = null, $sort = null)
 */
class MockHasManyClass extends \DataObject {
    static $search_fields = false;

    static $has_many = array(
        "many"  => MockHasOneClass::class
    );
}
