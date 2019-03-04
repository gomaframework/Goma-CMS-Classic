<?php

defined("IN_GOMA") or die();

/**
 * Iterator for ArrayList.
 *
 * @package		Goma\Core\ViewModel
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class ArrayListIterator extends ViewAccessableDataIterator
{
    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return $this->data[$this->position];
    }

    /**
     * gets the value of the current item.
     */
    public function current() {
        return $this->data[$this->position];
    }
}