<?php
defined("IN_GOMA") or die();

/**
 * RadiosSQLField works like SelectSQLField, but uses RadioButton instead of Select in Forms.
 *  Supports complex arguments:
 * for example to define key and titles:
 * Radios({
 *  "value1": "Title 1",
 *  "value2": "Title 2"
 * })
 *
 * @author Goma-Team
 * @copyright 2017 Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @package		Goma\Core
 */
class RadiosSQLField extends SelectSQLField {
    /**
     * generates the default form-field for this field
     *
     * @param string $title
     * @param array $args, optional
     * @return FormField|Select
     */
    public function formfield($title = null, $args = array())
    {
        if(ArrayLib::isAssocArray($args)) {
            $field = new RadioButton($this->name, $title, $args, $this->value);
        } else {
            $field = new RadioButton($this->name, $title, ArrayLib::key_value($args), $this->value);
        }

        return $field;
    }
}
