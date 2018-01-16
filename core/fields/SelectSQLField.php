<?php
defined("IN_GOMA") or die();

/**
 * SelectSQLField allows to provide selects on DB and Goma Form side.
 * Supports complex arguments:
 * for example to define key and titles:
 * Select({
 *  "value1": "Title 1",
 *  "value2": "Title 2"
 * })
 *
 * @author Goma-Team
 * @copyright 2017 Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @package		Goma\Core
*/

class SelectSQLField extends DBField {
    /**
     * gets the field-type
     *
     * @param array $args
     * @param null $allowNull
     * @return string
     */
	static public function getFieldType($args = array(), $allowNull = null) {
	    if(count($args) == 1 && is_array($args[0])) {
	        $args = $args[0];
        }

	    if(ArrayLib::isAssocArray($args)) {
            return 'enum("'.implode('", "', array_map(array(self::class, "maskSlashes"), array_keys($args))).'")';
        } else {
            return 'enum("'.implode('", "', array_map(array(self::class, "maskSlashes"), $args)).'")';
        }
	}

    /**
     * adds backslash before quote
     * @param string $key
     * @return mixed
     */
	public static function maskSlashes($key) {
	    return str_replace("\"", "\\\"", $key);
    }

    /**
     * generates the default form-field for this field
     *
     * @param string $title
     * @param array $args, optional
     * @return FormField|Select
     */
	public function formfield($title = null, $args = array())
	{
        if(count($args) == 1 && is_array($args[0])) {
            $args = $args[0];
        }

        if(ArrayLib::isAssocArray($args)) {
            $field = new Select($this->name, $title, $args, $this->value);
        } else {
            $field = new Select($this->name, $title, ArrayLib::key_value($args), $this->value);
        }

        return $field;
	}
}
