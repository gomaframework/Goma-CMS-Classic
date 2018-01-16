<?php

namespace Goma\Test;

defined("IN_GOMA") or die();

/**
 * Test SelectSQLField Class.
 *
 * @package Goma
 *
 * @author Goma-Team
 * @copyright 2017 Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class SelectSQLFieldTest extends \GomaUnitTest
{
    /**
     * tests if SelectSQLField is parsing FieldType to correct DB Field type for basic example with quote.
     */
    public function testParseToCorrectDBTypeBasicWithQuote() {
        $this->assertEqual('enum("1", "a\"b") ', \DBField::getDBFieldTypeForCasting('Select("1", "a\"b")'));
    }

    /**
     * tests if SelectSQLField is parsing FieldType to correct DB Field type
     * for assoc array.
     */
    public function testParseToCorrectDBTypeAssocArray() {
        $this->assertEqual('enum("1", "a\"b") ',
            \DBField::getDBFieldTypeForCasting('Select('.json_encode(array("1" => "blub", "a\"b" => "blah")).')'));
    }
}