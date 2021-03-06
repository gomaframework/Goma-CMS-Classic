<?php defined("IN_GOMA") OR die();


// TODO: Parsing should support Escape method

/**
 * this is a simple CSV-Parser and generator-class.
 *
 * @package        Goma\libs\CSV
 *
 * @author        Goma-Team
 * @license        GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @version        1.1
 */
class CSV extends gObject implements Iterator {
	/**
	 * this var contains the raw csv-string.
	 */
	protected $csv;

	/**
	 * this var contains the data as an array.
	 */
	protected $csvarr = array();

    /**
     * @var bool
     */
	protected $useWindowsLineEnding = false;

	/**
	 * here you should give the object your raw csv-string.
	 *
	 * @param    string $str CSV
	 */
	public function __construct($str)
	{
		parent::__construct();

		/* --- */

		$this->csv = trim($str);
		$this->parse();
	}

	/**
	 * parses the csv-string.
	 */
	protected function parse()
	{
		$str = $this->csv;
		// we do not need \r
        $this->useWindowsLineEnding = strpos($str, "\r\n") !== false;
		$str = str_replace("\r\n", "\n", $str);
		$rows = explode("\n", $str);
		$i = 1;
		foreach ($rows as $row) {
			if (substr($row, -1) == ";") {
				$row = substr($row, 0, -1);
			}
			$arr = preg_split("#(?<!\\\)\;#", $row);

			// validate
			$fields = array();
			$a = 0;
			$b = 1; // counter for field-names

			while ($a < count($arr)) {
				$fields[$b] = self::unescape($arr[$a]);

				$a++;
				$b++;
			}
			$this->csvarr[$i] = $fields;
			$i++;
		}
	}

	/**
	 * gets the field with index $field from the row with index $row.
	 *
	 * @param    int $row row is starting at 1.
	 * @param    int $field field
	 * @return bool
	 */
	public function get($row, $field)
	{
		return isset($this->csvarr[$row][$field]) ? $this->csvarr[$row][$field] : false;
	}

	/**
	 * gets the array of a complete row of data.
	 *
	 * @param    int $row row
	 * @return   array|null
	 */
	public function getRow($row)
	{
        return isset($this->csvarr[$row]) ? $this->csvarr[$row] : null;
	}

    /**
     * returns row-count of CSV.
     */
	public function count() {
	    return count($this->csvarr);
    }

	/**
	 * generates RAW-csv.
     * To create windows line endings, set csv::useWindowsLineEndings to true.
	 */
	public function csv()
	{
		$str = "";
		foreach ($this->csvarr as $row) {
			$i = 0;
			foreach ($row as $val) {
				if ($i == 0) {
					$i++;
				} else {
					$str .= ";";
				}
				$str .= CSV::escape($val);
			}

            if($this->useWindowsLineEnding) {
                $str .= "\r\n";
            } else {
                $str .= "\n";
            }
		}

		return $str;
	}

	/**
	 * generates excel version. The cells are then formatted in excel-way.
     * To create windows line endings, set csv::useWindowsLineEndings to true.
	 */
	public function toExcel() {
		$str = "";
		foreach ($this->csvarr as $row) {
			$i = 0;
			foreach ($row as $val) {
				if ($i == 0) {
					$i++;
				} else {
					$str .= ";";
				}
				$str .= CSV::escape($val, true);
			}

			if($this->useWindowsLineEnding) {
                $str .= "\r\n";
            } else {
                $str .= "\n";
            }
		}

		return $str;
	}

    /**
     * gets an escapes string and converts it to an normal one.
     * @param string $str
     * @param null $isExcel
     * @return string
     */
	public static function unescape($str, $isExcel = null) {
        $str = str_replace("\\;", ";", $str);

        // unescape tab characters
        $str = preg_replace("/\\t/", "\t", $str);

        // unescape new lines
        $str = str_replace("\\r\\n", "\r\n", $str);
        $str = str_replace("\\n", "\n", $str);

        return $str;
    }

    /**
     * escapes a string for csv. this is important, because sometimes there are semicolons in values.
     *
     * 
     * @param    string $str
     * @param bool $toExcel
     * @return mixed|string
     */
	public static function escape($str, $toExcel = false)
	{
		$str = str_replace(";", "\\;", $str);
		// escape tab characters
		$str = preg_replace("/\t/", "\\t", $str);

		// escape new lines
		$str = str_replace("\n", "\\n", $str);
        $str = str_replace("\r\n", "\\r\n", $str);

		// convert 't' and 'f' to boolean values
		if($str == 't') $str = 'TRUE';
		if($str == 'f') $str = 'FALSE';

		if($toExcel) {
            // force certain number/date formats to be imported as strings
            if (preg_match("/^\+?\d{8,}$/", $str) || preg_match("/^\d{4}.\d{1,2}.\d{1,2}/", $str)) {
                $str = "'$str";
            }

            // escape fields that include double quotes
            if (strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"';
        }

		if($toExcel) {
            $str = mb_convert_encoding($str, 'UTF-16LE', 'UTF-8');
        }

		return $str;
	}

	/**
	 * adds a row to the csv
	 *
	 * @param    array $data fields of the row
	 * @return $this
	 */
	public function addRow($data = array())
	{
		$this->csvarr[] = $data;

		return $this;
	}

	/**
	 * sets the data of an specific $field in an specfic $row.
	 *
	 * @param int $row row
	 * @param int $field field
	 * @param string $data data to set
	 * @return $this
	 */
	public function set($row, $field, $data)
	{
		if (!isset($this->csvarr[$row])) {
			// generate rows
			$i = count($this->csvarr);
			while ($i <= $row) {
				$this->csvarr[$i] = array();
				$i++;
			}
		}

		$this->csvarr[$row][$field] = $data;

		return $this;
	}

	/**
	 * Magic Methods
	 * for handling csv like this: $csv->1_1 = "this is field 1 in row 1";
	 */
	/**
	 * for reading
	 * @name __get
	 * @access public
	 * @param string - var
	 * @return bool
	 */
	public function __get($var)
	{
		if (!strpos($var, "_")) {
            return false;
		}
		$arr = explode("_", $var);

		return $this->get($arr[0], $arr[1]);
	}

	/**
	 * for writing
	 * @name __set
	 * @access public
	 * @param string - var
	 * @param string - data
	 * @return bool|CSV
	 */
	public function __set($var, $data)
	{
		if (!strpos($var, "_")) {
			return false;
		}
		$arr = explode("_", $var);

		return $this->set($arr[0], $arr[1], $data);
	}

    /**
     * @return array
     */
	public function toArray() {
	    return $this->csvarr;
    }

    /**
     * @return bool
     */
    public function isUseWindowsLineEnding()
    {
        return $this->useWindowsLineEnding;
    }

    /**
     * @param bool $useWindowsLineEnding
     * @return $this
     */
    public function setUseWindowsLineEnding($useWindowsLineEnding)
    {
        $this->useWindowsLineEnding = $useWindowsLineEnding;
        return $this;
    }

	/**
	 * Iterator
	 * @link http://php.net/manual/en/class.iterator.php
	 */
	/**
	 * the position of the iterator
	 */
	protected $position = 1;
	/**
	 * this is the position set
	 * @name pos
	 */
	protected $pos = 1;

	/**
	 * checks if valid
	 * @return bool
	 */
	public function valid()
	{
		return isset($this->csvarr[$this->position]);
	}

	/**
	 * rewinds
	 * @name rewind
	 */
	public function rewind($pos = true)
	{
		if ($pos)
			$this->position = $this->pos;
		else
			$this->position = 1;
	}

	/**
	 * gets the current key
	 */
	public function key()
	{
		return $this->position;
	}

	/**
	 * gets the current value
	 */
	public function current()
	{
		return $this->csvarr[$this->position];
	}

	/**
	 * goes to the next position
	 * @name next
	 */
	public function next()
	{
		$this->position++;
	}

	/**
	 * sets the position of the iterator
	 * @name setPosition
	 * @param numeric - position
	 */
	public function setPosition($pos)
	{
		if (isset($this->csvarr[$pos])) {
			$this->pos = $pos;
		}
	}
}
