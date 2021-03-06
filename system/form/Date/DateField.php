<?php defined("IN_GOMA") OR die();

/**
 * Date-Field for SQL-Date.
 *
 * @package	Goma\Forms
 * @link	http://goma-cms.org
 * @license LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 * @author 	Goma-Team
 * @version 2.0
 */
class DateField extends FormField
{
	/**
	 * @var string
	 */
	protected $format = DATE_FORMAT_DATE;

	/**
	 * @var bool
	 */
	protected $showClear = true;

    /**
     * @var array
     */
    protected $between;

    /**
	 * generates this field.
	 *
	 * @name    __construct
	 * @param    string $name name
	 * @param    string $title title
	 * @param    string $value value
	 * @param    array $between key 0 for start and key 1 for end and key 2 indicates whether to allow the values given
	 * @param    object $form
	 */
	public function __construct($name = null, $title = null, $value = null, $between = null, $form = null)
	{
		$this->between = is_int($between) ? array($between, PHP_INT_MAX) : $between;
		parent::__construct($name, $title, $value, $form);
	}

	/**
	 * creates the field.
	 *
	 * @name createNode
	 * @access public
	 * @return HTMLNode
	 */
	public function createNode()
	{
		$node = parent::createNode();
		$node->type = "text";
		$node->addClass("datepicker");
		return $node;
	}

	/**
	 * validate
	 *
	 * @param string $value
	 * @return bool
	 * @throws FormInvalidDataException
	 */
	public function validate($value)
	{
        try {
            $datetime = new DateTimeSQLField("parse", parent::result(), array($this->format));
            if ($this->between && is_array($this->between)) {
                $this->validateTimestamp($datetime->getTimestamp());
            }
        } catch(InvalidArgumentException $e) {
            throw new FormInvalidDataException($this->name, lang("no_valid_date", "No valid date.") . " " . $this->getTitle(), null, $e);
        }

		return true;
	}

	/**
	 * @return string
	 */
	public function getModel()
	{
		$model = parent::getModel();
		if(RegexpUtil::isNumber($model)) {
			return date($this->format, $model);
		}

		return $model;
	}

	/**
	 * @param int $timestamp
	 * @throws FormInvalidDataException
	 */
	protected function validateTimestamp($timestamp) {
	    if(is_array($this->between)) {
            $between = array_values($this->between);

            if (!preg_match("/^[0-9]+$/", trim($between[0]))) {
                $start = strtotime($between[0]);
            } else {
                $start = $between[0];
            }

            if (!preg_match("/^[0-9]+$/", trim($between[1]))) {
                $end = strtotime($between[1]);
            } else {
                $end = $between[1];
            }

            if (((!isset($between[2]) || $between[2] === false) && ($start >= $timestamp || $timestamp >= $end)) || (isset($between[2]) && $between[2] === true && ($start > $timestamp && $timestamp > $end))) {
                $err = lang("date_not_in_range", "The given time is not between the range \$start and \$end.");
                $err = str_replace('$start', date(DATE_FORMAT_DATE, $start), $err);
                $err = str_replace('$end', date(DATE_FORMAT_DATE, $end), $err);
                throw new FormInvalidDataException($this->name, $err);
            }
        }
	}

	/**
	 * @param FormFieldRenderData $info
	 * @param bool $notifyField
	 */
	public function addRenderData($info, $notifyField = true)
	{
		parent::addRenderData($info, $notifyField);

		$info->addJSFile("system/libs/thirdparty/moment/moment.min.js");
		$info->addJSFile("system/libs/thirdparty/jquery-daterangepicker/daterangepicker.js");
		$info->addCSSFile("system/libs/thirdparty/jquery-daterangepicker/daterangepicker.css");
		$info->addJSFile("system/form/Date/DateField.js");
		$info->addCSSFile("font-awsome/font-awesome.css");

		$info->getRenderedField()->addClass("date-field");
		if($this->showClear) {
			$info->getRenderedField()->append(
				'<div class="clear-wrapper">
					<a class="clear-date" href="#"><i class="fa fa-times" aria-hidden="true"></i></a>
				</div>');
		}
	}

	/**
	 * @param string $php_format
	 * @return string
	 */
	public static function dateformat_PHP_to_DatePicker($php_format)
	{
		$SYMBOLS_MATCHING = array(
			// Day
			'd' => 'DD',
			'D' => 'D',
			'j' => 'D',
			'l' => 'DD',
			'N' => '',
			'S' => '',
			'w' => '',
			'z' => 'o',
			// Week
			'W' => '',
			// Month
			'F' => 'MM',
			'm' => 'MM',
			'M' => 'M',
			'n' => 'M',
			't' => '',
			// Year
			'L' => '',
			'o' => '',
			'Y' => 'YYYY',
			'y' => 'YY',
			// Time
			'a' => 'a',
			'A' => '',
			'B' => '',
			'g' => 'h',
			'G' => 'H',
			'h' => 'hh',
			'H' => 'HH',
			'i' => 'mm',
			's' => 'ss',
			'u' => ''
		);
		$jqueryui_format = "";
		$escaping = false;
		for($i = 0; $i < strlen($php_format); $i++)
		{
			$char = $php_format[$i];
			if($char === '\\') // PHP date format escaping character
			{
				$i++;
				if($escaping) $jqueryui_format .= $php_format[$i];
				else $jqueryui_format .= '\'' . $php_format[$i];
				$escaping = true;
			}
			else
			{
				if($escaping) { $jqueryui_format .= "'"; $escaping = false; }
				if(isset($SYMBOLS_MATCHING[$char]))
					$jqueryui_format .= $SYMBOLS_MATCHING[$char];
				else
					$jqueryui_format .= $char;
			}
		}
		return $jqueryui_format;
	}

	/**
	 * render JavaScript
	 */
	public function JS()
	{
		return 'new gDateField(field, '.json_encode($this->getDatePickerOptions()).', form);';
	}

	/**
	 * @return array
	 */
	protected function getDatePickerOptions() {
		/** @var string[] $calendar */
		require (ROOT . LANGUAGE_DIRECTORY . Core::$lang . "/calendar.php");

		return array(
			"autoUpdateInput"  => false,
			"singleDatePicker" => true,
			"showDropdowns"	=> true,
			"showWeekNumbers"	=> true,
			"autoApply"	=> true,
			"minDate"	=> isset($this->between[0]) ? date("d/m/Y", $this->between[0]) : null,
			"maxDate"	=> isset($this->between[1]) ? date("d/m/Y", $this->between[1]) : null,
			"applyClass"=> "btn-success button green",
			"locale"	=> array(
				"format"		=> self::dateformat_PHP_to_DatePicker($this->format),
				"seperator"		=> " - ",
				"applyLabel"	=> lang("save"),
				"cancelLabel"	=> lang("cancel"),
				"fromLabel"		=> lang("fromLabel"),
				"toLabel"		=> lang("toLabel"),
				"customRangeLabel"	=> lang("customLabel"),
				"daysOfWeek"	=> array(
					$calendar["Sun"],
					$calendar["Mon"],
					$calendar["Tue"],
					$calendar["Wed"],
					$calendar["Thu"],
					$calendar["Fri"],
					$calendar["Sat"]
				),
				"monthNames"	=> array(
					$calendar["January"],
					$calendar["February"],
					$calendar["March"],
					$calendar["April"],
					$calendar["May"],
					$calendar["June"],
					$calendar["July"],
					$calendar["August"],
					$calendar["September"],
					$calendar["October"],
					$calendar["November"],
					$calendar["December"]
				),
				"firstDay"	=> 1
			)
		);
	}

	/**
	 * @return string
	 */
	public function getFormat()
	{
		return $this->format;
	}

	/**
	 * @param string $format
	 * @return $this
	 */
	public function setFormat($format)
	{
		$this->format = $format;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function isShowClear()
	{
		return $this->showClear;
	}

	/**
	 * @param boolean $showClear
	 * @return $this
	 */
	public function setShowClear($showClear)
	{
		$this->showClear = $showClear;
		return $this;
	}

	/**
	 * @return int
	 */
	public function result()
	{
		$this->validate(parent::result());

		$datetime = new DateTimeSQLField("parse", parent::result(), array($this->format));
		return $datetime->getTimestamp() === null ? null : date($this->format, $datetime->getTimestamp());
	}
}
