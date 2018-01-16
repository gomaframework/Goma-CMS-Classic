<?php

namespace tests\libs\sql;

use SQL;

defined("IN_GOMA") OR die();

/**
 * Tests SQL Class.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class SQLTest extends \GomaUnitTest
{
    /**
     * tests empty conjunctions in extract to where.
     */
    public function testExtractToWhereEmpty()
    {
        $this->assertEqual("", SQL::extractToWhere(array(array())));
    }

    /**
     * tests empty conjunctions in extract to where.
     */
    public function testExtractToWhereEmptyWithoutWhere()
    {
        $this->assertEqual("", SQL::extractToWhere(array(array()), false));
    }

    /**
     * tests non empty conjunctions and with empty conjunction in extract to where.
     */
    public function testExtractToWhereNonEmptyEmpty()
    {
        $this->assertEqual(" WHERE ( test = \"1\")", SQL::extractToWhere(array(array("test" => 1), array())));
    }

    /**
     * tests empty conjunctions and with non empty conjunction in extract to where.
     */
    public function testExtractToWhereEmptyNonEmpty()
    {
        $this->assertEqual(" WHERE ( test = \"1\")", SQL::extractToWhere(array(array(), array("test" => 1))));
    }

    /**
     * tests empty conjunctions and with non empty conjunction in extract to where.
     */
    public function testExtractToWhereEmptyNonEmptyOr()
    {
        $this->assertEqual(" WHERE ( test IN (\"1\",\"2\"))", SQL::extractToWhere(array(array(), array("test" => array(1, 2)))));
    }

    /**
     * tests simple or conjunction.
     */
    public function testExtractToWhereOr()
    {
        $this->assertEqual(' WHERE  test = "1" OR  test2 = "2"', SQL::extractToWhere(array("test" => "1", "OR", "test2" => "2")));
    }

    /**
     * tests extract to where with combined SQL + Array-Notation.
     */
    public function testExtractToWhereWithInlineSQL() {
        $this->assertEqual(' WHERE  ( test = \'blub\' )  AND ( id = "5")', SQL::extractToWhere(array("test = 'blub'", array("id" => 5))));
    }

    /**
     * tests extract to where with combined SQL + OR + Array-Notation.
     */
    public function testExtractToWhereWithInlineSQLOr() {
        $this->assertEqual(' WHERE  ( test = \'blub\' )  OR ( id = "5")', SQL::extractToWhere(array("test = 'blub'", "OR", array("id" => 5))));
    }


    /**
     * tests or conjunction with sub queries.
     */
    public function testExtractToWhereSubqueryOr()
    {
        $this->assertEqual(' WHERE ( test = "1" AND  test3 = "3") OR ( test2 = "2")', SQL::extractToWhere(array(array("test" => "1", "test3" => 3), "OR", array("test2" => "2"))));
    }
}
