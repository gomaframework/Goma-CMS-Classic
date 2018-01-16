<?php defined("IN_GOMA") OR die();
/**
 * timezone-field
 */
class TimeZone extends DBField {

    /**
     * @param array $args
     * @param null $allowNull
     * @return string
     */
    static public function getFieldType($args = array(), $allowNull = null) {
        return 'enum("'.implode('","', i18n::$timezones).'")';
    }

    /**
     * @param null $title
     * @return Select
     */
    public function formfield($title = null)
    {
        return new Select($this->name, $title, ArrayLib::key_value(i18n::$timezones), $this->value);
    }
}
