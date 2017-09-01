<?php defined("IN_GOMA") OR die();

/**
 * Base-Class for saving Dates with times as a timestamp.
 *
 * @package		Goma\Core\Model
 * @version		1.5.3
 */
class DateTimeSQLField extends DBField {

	/**
	 * gets the field-type
	 *
	 * @param array $args
	 * @return string
	 */
	static public function getFieldType($args = array()) {
		return "int(30) NULL";
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @param string $format
	 * @return static
	 */
	public static function createFromFormat($name, $value, $format) {
		return new static($name, $value, array($format));
	}

	/**
	 * converts every type of time to a date fitting in this object.
	 * @param string $name
	 * @param string $value
	 * @param array $args
	 */
	public function __construct($name, $value, $args = array())
	{
		$parsedValue = $this->parseDateTime($value, $args);
		parent::__construct($name, $parsedValue, $args);
	}

	/**
	 * @param $value
	 * @param $args
	 * @param bool $useTime
	 * @return int
	 */
	protected function parseDateTime($value, $args, $useTime = true) {
		if($value === null || (is_string($value) && trim($value) == "")) {
			return null;
		}

		if(!is_int($value)) {
			if(preg_match('/^\-?[0-9]+$/', trim($value))) {
				return (int) trim($value);
			} else if(isset($args[0]) && is_string($args[0]) && ($datetime = DateTime::createFromFormat($args[0], $value, new DateTimeZone(date_default_timezone_get())))) {
				if($useTime) {
					return $datetime->getTimestamp();
				}

				return mktime(0, 0, 0, date("m", $datetime->getTimestamp()), date("d", $datetime->getTimestamp()), date("Y", $datetime->getTimestamp()));
			} else if($useTime && preg_match('/^([0-9]{4})\-([0-9]{2})\-([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})/', $value, $matches)) {
				return mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
			} else if((!$useTime || strtotime($value) === false) && preg_match('/^([0-9]{4})\-([0-9]{2})\-([0-9]{2})/', $value, $matches)) {
				return mktime(0, 0, 0, $matches[2], $matches[3], $matches[1]);
			} else {
				if(!($timestamp = strtotime($value))) {
					throw new InvalidArgumentException("Argument is not any kind of date. " . gettype($value) . var_export($value, true));
				}

				if($useTime) {
					return $timestamp;
				}
				return mktime(0, 0, 0, date("m", $timestamp), date("d", $timestamp), date("Y", $timestamp));
			}
		}

		return $value;
	}

	/**
	 * default convert
	 */
	public function forTemplate() {
		if(isset($this->args[0]))
			return $this->date($this->args[0]);
		else
			return $this->date();
	}

	/**
	 * converts this with date
	 * @param string $format
	 * @return bool|mixed|null|string
	 */
	public function date($format = DATE_FORMAT)
	{
		if($this->value === null)
			return null;

		return goma_date($format, $this->value);
	}

	/**
	 * returns date with format given.
	 *
	 * @param String $format
	 * @return bool|mixed|null|string
	 */
	public function dateWithFormat($format) {
		return $this->date($format);
	}

	/**
	 * returns raw-data.
	 */
	public function raw() {
		if($this->value === null)
			return null;

		return date(DATE_FORMAT, $this->value);
	}

	/**
	 * @return int
	 */
	public function toTimestamp() {
		return (int) $this->value;
	}

	/**
	 * for db.
	 */
	public function forDB() {
		return $this->value;
	}

	/**
	 * returns date as ago
	 * @param bool $fullSentence
	 * @return string
	 */
	public function ago($fullSentence = true) {
		if(NOW - $this->value < 60) {
			return '<span title="'.$this->forTemplate().'" class="ago-date" data-date="'.$this->value.'">' . sprintf(lang("ago.seconds", "about %d seconds ago"), round(NOW - $this->value)) . '</span>';
		} else if(NOW - $this->value < 90) {
			return '<span title="'.$this->forTemplate().'" class="ago-date" data-date="'.$this->value.'">' . lang("ago.minute", "about one minute ago") . '</span>';
		} else {
			$diff = NOW - $this->value;
			$diff = $diff / 60;
			if($diff < 60) {
				return '<span title="'.$this->forTemplate().'" class="ago-date" data-date="'.$this->value.'">' . sprintf(lang("ago.minutes", "%d minutes ago"), round($diff)) . '</span>';
			} else {
				$diff = round($diff / 60);
				if($diff == 1) {
					return '<span title="'.$this->forTemplate().'" class="ago-date" data-date="'.$this->value.'">' . lang("ago.hour", "about one hour ago") . '</span>';
				} else {
					if($diff < 24) {
						return '<span title="'.$this->forTemplate().'" class="ago-date" data-date="'.$this->value.'">' . sprintf(lang("ago.hours", "%d hours ago"), round($diff)) . '</span>';
					} else {
						$diff = $diff / 24;
						if($diff <= 1.1) {
							return '<span title="'.$this->forTemplate().'" class="ago-date" data-date="'.$this->value.'">' . lang("ago.day", "about one day ago") . '</span>';
						} else if($diff <= 7) {
							$pre = ($fullSentence) ? lang("version_at") . " " : "";
							return '<span title="'.$this->forTemplate().'" data-date="'.$this->value.'">' . $pre . sprintf(lang("ago.weekday", "%s at %s"), $this->date("l"), $this->date("H:i")) . '</span>';
						} else {
							if($fullSentence)
								return lang("version_at") . " " . $this->forTemplate();
							else
								return $this->forTemplate();
						}
					}
				}
			}
		}
	}

	/**
	 * form
	 */
	public function form() {

	}

	/**
	 * @return int|null
	 */
	public function getTimestamp() {
		return $this->value;
	}
}
