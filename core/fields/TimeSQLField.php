<?php defined("IN_GOMA") OR die();
/**
 * Base-Class for saving times in db.
 *
 * @package		Goma\Core\Model
 * @version		1.0
 */
class TimeSQLField extends DBField {

    /**
     * gets the field-type
     * @param array $args
     * @param null $allowNull
     * @return string
     */
    static public function getFieldType($args = array(), $allowNull = null) {
        return "time";
    }

    /**
     * converts every type of time to a date fitting in this object.
     * @param string $name
     * @param mixed $value
     * @param array $args
     */
    public function __construct($name, $value, $args = array())
    {
        if($value !== null) {
            if(preg_match('/^\-?[0-9]+$/', trim($value))) {
                $value = (int) trim($value);
            } else {
                $value = strtotime(str_replace(".", ":", $value));
            }
            $value = date("H:i:s", $value);
        }

        parent::__construct($name, $value, $args);
    }

    /**
     * converts this with date
     *
     * @param String $format optional
     * @return string|null
     */
    public function date($format =	DATE_FORMAT)
    {
        if($this->value === null)
            return null;

        return goma_date($format, strtotime($this->value));
    }

    /**
     * returns time with format given.
     *
     * @return null|string
     */
    public function timeWithFormat($format) {
        return $this->date($format);
    }

    /**
     * default convert
     */
    public function forTemplate() {
        return $this->value;
    }

    /**
     * generatesa a date-field.
     *
     * @param string|null $title
     * @return FormField|TimeField
     */
    public function formfield($title = null)
    {
        return new TimeField($this->name, $title, $this->value);
    }
}
