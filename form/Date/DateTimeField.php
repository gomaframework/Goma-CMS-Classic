<?php defined("IN_GOMA") OR die();

/**
 * DateTime-Field for SQL-DateTime.
 *
 * @package	Goma\Forms
 * @link	http://goma-cms.org
 * @license LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 * @author 	Goma-Team
 * @version 2.0
 */
class DateTimeField extends DateField
{
    /**
     * @var string
     */
    protected $format = DATE_FORMAT;

    /**
     * @return int
     */
    public function result()
    {
        $datetime = new DateTimeSQLField("parse", parent::result(), array($this->format));
        return $datetime->getTimestamp();
    }

    /**
     * @return array
     */
    protected function getDatePickerOptions()
    {
        $options = parent::getDatePickerOptions();

        $options["timePicker24Hour"] = true;
        $options["timePicker"] = true;
        $options["seperator"] = "  -  ";

        return $options;
    }
}
