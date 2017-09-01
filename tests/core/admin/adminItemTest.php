<?php
namespace Goma\Test\Admin;

use adminItem;
use GomaUnitTest;

defined("IN_GOMA") OR die();

/**
 * Unit-Tests for AdminItem.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */

class AdminItemTest extends GomaUnitTest {
	/**
	 * test init.
	 */
	public function testInitNull() {
		$item = new adminItem();
		$this->assertInstanceOf(AdminItem::class, $item);
	}

	/**
	 * test init.
	 */
	public function testInitsetModel() {
		$item = new adminItem();
		$item->setModelInst($user = new \User());
		$this->assertInstanceOf(AdminItem::class, $item);
		$this->assertEqual(strtolower(\User::class), $item->model());
		$this->assertEqual($user, $item->modelInst());
	}
}