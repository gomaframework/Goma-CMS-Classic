<?php defined("IN_GOMA") OR die();

/**
 * Every value of an field can used as object if you call doObject($offset)
 * This Object has some very cool methods to convert the field
 *
 * @author Goma-Team
 * @copyright 2017 Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @package		Goma\Core
 */
class DBField extends gObject implements IDataBaseField
{
    /**
     * this var contains the value
     * @var mixed
     */
    protected $value;

    /**
     * this field contains the field-name of this object
     * @var string
     */
    protected $name;

    /**
     * args
     */
    public $args = array();

    /**
     * cache for casting
     */
    private static $castingCache = array();

    /**
     * @param string $name
     * @param mixed $value
     * @param array $args
     */
    public function __construct($name, $value, $args = array())
    {
        parent::__construct();

        if(!is_string($name)) {
            throw new InvalidArgumentException("\$name must be a string. " . gettype($args));
        }

        if(isset($value) && !is_string($value) && !is_bool($value) && !is_numeric($value)) {
            throw new InvalidArgumentException("DBField \$value of field {$name} must be either null, string, bool or numeric. Type given: " . gettype($args));
        }

        if(isset($args) && !is_array($args)) {
            throw new InvalidArgumentException("DBField \$args of field {$name} must be either array or null. Type given: " . gettype($args));
        }

        $this->name = $name;
        $this->value = $value;
        $this->args = (array) $args;
    }

    /**
     * sets the value
     * @param mixed $value
     * @return $this
     */
    public function setValue($value) {
        $this->value = $value;
        return $this;
    }
    /**
     * gets the value
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * sets the name
     * @param string $name
     * @return $this
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }
    /**
     * gets the anme
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function raw()
    {
        return $this->value;
    }

    /**
     * get it as text
     * @return string
     */
    public function text()
    {
        return convert::raw2xml($this->value);
    }

    /**
     * get it as text and correct  linebreaks
     * @return string
     */
    public function textLines()
    {
        return convert::raw2xmlLines($this->value);
    }

    /**
     * get this as url
     * @return string
     */
    public function url()
    {
        return convert::raw2text(urlencode($this->value));
    }
    /**
     * for js
     * @return string
     */
    public function js()
    {
        return str_replace(array("\n","\t","\r","\"","'"), array('\n', '\t', '\r', '\\"', '\\\''), $this->value);
    }
    /**
     * converts string to uppercase
     * @return string
     */
    public function UpperCase()
    {
        return strtoupper($this->value);
    }

    /**
     * converts string to lowerase
     * @return string
     */
    public function LowerCase()
    {
        return strtolower($this->value);
    }


    /**
     * Layer for form-fields
     */

    /**
     * generates the default form-field for this field
     * @param string $title
     * @return FormField
     */
    public function formfield($title = null)
    {
        $field = new TextField($this->name, $title, $this->value);

        return $field;
    }

    /**
     * search-field for searching
     * @param string $title
     * @return FormField
     */
    public function searchfield($title = null)
    {
        return $this->formfield($title);
    }

    /**
     * gets the field-type
     *
     * @param array $args
     * @param bool|null defined if null type is defined in original type definition
     * @return string|null
     */
    static public function getFieldType($args = array(), $allowNull = null) {
        return null;
    }

    /**
     * toString-Method
     * @return string
     */
    public function __toString()
    {
        return $this->forTemplate();
    }

    /**
     * gets Data Converted for Template
     *
     * @return string
     */
    public function forTemplate() {
        return (string) $this->value;
    }

    /**
     * bool - for IF in template
     *
     * @return bool
     */
    public function toBool() {
        return (bool) $this->value;
    }

    /**
     * returns true when value is string and starts with given value.
     *
     * @param string $compare
     * @return bool
     */
    public function startsWith($compare) {
        $str = (string) $this->value;

        if(substr($str, 0, strlen($compare)) == $compare) {
            return true;
        }

        return false;
    }

    /**
     * returns true when value is string and ends with given value.
     *
     * @param string $compare
     * @return bool
     */
    public function endsWith($compare) {
        $str = (string) $this->value;

        if(substr($str, 0 - strlen($compare)) == $compare) {
            return true;
        }

        return false;
    }


    /**
     * calls
     *
     * @param string $methodName
     * @param array $args
     * @return mixed|string
     */
    public function __call($methodName, $args) {
        if(isPHPUnit()) {
            $trace = debug_backtrace();
            if(isset($trace[0]['file'])) {
                throw new LogicException('Call to undefined method ' . $this->classname . '::' . $methodName . ' in '.$trace[0]['file'].' on line '.$trace[0]['line']);
            } else {
                throw new LogicException('Call to undefined method ' . $this->classname . '::' . $methodName);
            }
        }

        if(DEV_MODE) {
            $trace = debug_backtrace();
            if(isset($trace[0]['file']))
                log_error('Warning: Call to undefined method ' . $this->classname . '::' . $methodName . ' in '.$trace[0]['file'].' on line '.$trace[0]['line']);
            else
                log_error('Warning: Call to undefined method ' . $this->classname . '::' . $methodName);

            if(DEV_MODE)
                AddContent::add('<div class="error"><b>Warning</b> Call to undefined method ' . $this->classname . '::' . $methodName . '</div>');
        }


        return $this->__toString();
    }

    /**
     * bool
     */
    public function bool() {
        return ($this->value);
    }

    /**
     * converts defined field type to DB field type to use in SQL class for requireTable.
     * @param string $fieldType
     * @return string
     */
    public static function getDBFieldTypeForCasting($fieldType) {
        if($parsedCasting = self::parseCasting($fieldType)) {
            $newType = call_user_func_array(
                array($parsedCasting["class"], "getFieldType"),
                (isset($parsedCasting["args"])) ?
                    array(
                        $parsedCasting["args"],
                        self::getNullDefinitionBoolNull($fieldType)
                    ) :
                    array(
                        array(),
                        self::getNullDefinitionBoolNull($fieldType)
                    )
            );

            if ($newType != "") {
                if (!self::definesNullInfo($newType)) {
                    return $newType . " " . self::getNullDefinitionString($fieldType);
                } else {
                    return $newType;
                }
            }
        }

        return $fieldType;
    }

    /**
     * parses casting-args and gives back the result.
     * returns null if casting can't be determined.
     *
     * returns array if casting has been determined
     * result array has the following keys:
     * * class - class name of casting class
     * * args - array of arguments for casting class
     * * method - method to be used to convert
     *
     * @param string $casting
     * @return array|null
     */
    public static function parseCasting($casting) {
        if(is_array($casting)) {
            return $casting;
        }

        if(isset(self::$castingCache[$casting]))
            return self::$castingCache[$casting];

        if(PROFILE) Profiler::mark("DBField::parseCasting");

        $method = self::parseCastingString($casting, $name, $args);

        if(ClassInfo::exists($name) && ClassInfo::hasInterface($name, "IDataBaseField")) {
            $valid = true;
        } else if (ClassInfo::exists($name . "SQLField") && ClassInfo::hasInterface($name . "SQLField", "IDataBaseField")) {
            $name = $name . "SQLField";
            $valid = true;
        }

        if(!isset($valid)) {
            self::$castingCache[$casting] = null;
            if(PROFILE) Profiler::unmark("DBField::parseCasting");
            return null;
        }

        $data = array(
            "class" => $name
        );

        if(!empty($args)) {
            $data["args"] = $args;
        }

        if(isset($method)) {
            $data["method"] = $method;
        }

        self::$castingCache[$casting] = $data;
        if(PROFILE) Profiler::unmark("DBField::parseCasting");

        return $data;
    }

    /**
     * parses casting and returns name of method or null if not set.
     *
     * @param string $casting
     * @param string $name variable to fill name in
     * @param array $args variable for args
     * @return string
     */
    protected static function parseCastingString($casting, &$name, &$args) {
        $method = null;
        $args = array();

        if(preg_match('/\-\>([a-zA-Z0-9_]+)\s*$/Usi', $casting, $matches)) {
            $method = $matches[1];
            $casting = substr($casting, 0, 0 - strlen($method) - 2);
            unset($matches);
        }

        if(strpos($casting, "(")) {
            $name = trim(substr($casting, 0, strpos($casting, "(")));
            if(preg_match('/\((.+)\)/', $casting, $matches)) {
                $argString = $matches[1];
                if($json = json_decode("[".fixJSON($argString)."]", true)) {
                    $args = $json;
                } else {
                    throw new InvalidArgumentException("Could not parse casting arguments for " . $casting . ". JSON Error: " . json_last_error());
                }
            }
            unset($matches);
        } else if(self::definesNullInfo($casting) && preg_match('/^\s*([0-9a-zA-Z_]+)\s*(not)?\s*null\s*$/i', $casting, $matches)) {
            $name = $matches[1];
        } else {
            $name = trim($casting);
        }

        $name = ClassManifest::resolveClassName($name);

        return $method;
    }

    /**
     * gets a var for template
     *
     * @param string $var
     * @return null|string
     */
    public function getTemplateVar($var) {
        if(strpos($var, ".")) {
            $var = substr($var, 0, strpos($var, "."));
        }

        // check for args
        if(strpos($var, "(") && substr($var, -1) == ")") {
            $args = eval("return array(" . substr($var, strpos($var, "(") + 1, -1) . ");");
            $var = substr($var, 0, strpos($var, "("));
        } else {
            $args = array();
        }

        if(gObject::method_exists($this, $var)) {
            return call_user_func_array(array($this, $var), $args);
        }

        return $this->forTemplate();
    }

    /**
     * converts by casting
     *
     * @param string|array $castingToParse
     * @param string $name
     * @param mixed $value
     * @return string
     */
    public static function convertByCasting($castingToParse, $name, $value) {
        if(!is_string($name)) {
            throw new InvalidArgumentException("Second argument (\$name) of DBField::convertByCastingIfDefault must be an string.");
        }
        $casting = self::parseCasting($castingToParse);
        if(isset($casting)) {
            $object = new $casting["class"]($name, $value, isset($casting["args"]) ? $casting["args"] : array());
            if(isset($casting["method"])) {
                return call_user_func_array(array($object, $casting["method"]), array());
            } else {
                return $object->__toString();
            }
        } else {
            throw new InvalidArgumentException("Invalid casting-Array given to DBField::convertByCasting(".var_export($castingToParse, true).", ".var_export($name, true).", ".var_export($value, true)."). ");
        }
    }

    /**
     * converts by casting if convertDefault
     *
     * @param string|array casting
     * @param string - name
     * @param mixed - value
     * @return mixed
     */
    public static function convertByCastingIfDefault($casting, $name, $value) {
        if(!is_string($name)) {
            throw new InvalidArgumentException("Second argument (\$name) of DBField::convertByCastingIfDefault must be an string.");
        }
        $casting = self::parseCasting($casting);
        if(isset($casting["convert"]) && $casting["convert"]) {
            return self::convertByCasting($casting, $name, $value);
        }

        return $value;
    }

    /**
     * gets an object by casting
     *
     * @param $casting
     * @param $name
     * @param $value
     * @param bool $throwErrorOnFail
     * @return DBField
     */
    public static function getObjectByCasting($casting, $name, $value, $throwErrorOnFail = false) {
        if(PROFILE) Profiler::mark("DBField::getObjectByCasting");

        if(!is_string($name)) {
            if(PROFILE) Profiler::unmark("DBField::getObjectByCasting");
            throw new InvalidArgumentException("Second argument (\$name) of DBField::convertByCastingIfDefault must be an string.");
        }
        $casting = self::parseCasting($casting);
        $object = null;
        if(isset($casting)) {
            $object = new $casting["class"]($name, $value, isset($casting["args"]) ? $casting["args"] : array());
        } else if($throwErrorOnFail) {
            if(PROFILE) Profiler::unmark("DBField::getObjectByCasting");
            throw new LogicException("Invalid casting given to DBField::getObjectByCasting '".$casting."'");
        } else {
            $object = new DBField($name, $value, array());
        }

        if(PROFILE) Profiler::unmark("DBField::getObjectByCasting");
        return $object;
    }

    /**
     * returns false because no object can be done
     *
     * @return bool
     */
    public function canDoObject() {
        return false;
    }

    /**
     * returns field-value for database.
     */
    public function forDBQuery()
    {
        return $this->value === null ? "NULL" : "'".convert::raw2sql($this->value)."'";
    }

    /**
     * @internal
     * @param DataObject $class
     * @param string $fieldName
     * @param array $args
     * @param string $fieldType
     */
    public static function argumentClassInfo($class, $fieldName, $args, $fieldType) {

    }

    /**
     * @param string $fieldName
     * @param mixed $default
     * @param array $args
     * @return null|string|mixed new default
     */
    public static function getSQLDefault($fieldName, $default, $args) {
        return $default;
    }

    /**
     * @param mixed $old
     * @param mixed $new
     * @return string
     */
    public static function getDiffOfContents($name, $args, $old, $new) {
        $class = static::class;
        /** @var DBField $newCasted */
        $newCasted =  new $class($name, $new, $args);
        if($old != $new) {
            /** @var DBField $oldCasted */
            $oldCasted = new $class($name, $old, $args);
            return
                '<del>' . $oldCasted->forTemplate() . '</del>' .
                '<ins>' . $newCasted->forTemplate() . '</ins>';
        } else {
            return $newCasted->forTemplate();
        }
    }

    /**
     * @param string $type
     * @return bool
     */
    public static function isNullType($type) {
        if(self::definesNullInfo($type)) {
            $trimmed = strtolower(trim($type));
            $lastInfo = trim(substr($trimmed, 0, strlen($trimmed) - 4));
            return substr(trim(strtolower($lastInfo)), -3) != "not";
        }

        return false;
    }

    /**
     * @param string $type
     * @return bool
     */
    public static function definesNullInfo($type) {
        return substr(trim(strtolower($type)), -4) == "null";
    }

    /**
     * returns null if no null definition.
     * bool if null is defined.
     *
     * @param $type
     * @return bool|null
     */
    static function getNullDefinitionBoolNull($type) {
        if(self::definesNullInfo($type)) {
            return self::isNullType($type);
        }

        return null;
    }

    /**
     * returns "" for no null definition.
     * NOT NULL or NULL for definition.
     *
     * @param $type
     * @return bool|null
     */
    static function getNullDefinitionString($type) {
        if(self::definesNullInfo($type)) {
            return self::isNullType($type) ? "NULL" : "NOT NULL";
        }

        return "";
    }
}
