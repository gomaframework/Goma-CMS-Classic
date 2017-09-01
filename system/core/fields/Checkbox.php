<?php defined("IN_GOMA") OR die();

/**
 * Basically a boolean type.
 */
class CheckBoxSQLField extends DBField {
    /**
     * converts every type of value into bool.
     * @param string $name
     * @param bool $value
     * @param array $args
     */
    public function __construct($name, $value, $args = array())
    {
        if(strtolower($value) == strtolower(lang("no")) || strtolower($value) == "no") {
            $value = false;
        }

        $value = (bool) $value;

        parent::__construct($name, $value, $args);
    }

    /**
     * gets the field-type
     *
     * @return string
     */
    static public function getFieldType($args = array()) {
        return 'enum("0","1")';
    }

    /**
     * generatesa a switch.
     *
     * @param string $title
     * @return Checkbox|FormField
     */
    public function formfield($title = null)
    {
        return new Checkbox($this->name, $title, $this->value);
    }

    /**
     * default convert
     */
    public function forTemplate() {
        if($this->value) {
            return lang("yes");
        } else {
            return lang("no");
        }
    }

    /**
     * @internal
     * @param DataObject $class
     * @param string $fieldName
     * @param array $args
     * @param string $fieldType
     */
    public static function argumentClassInfo($class, $fieldName, $args, $fieldType) {
        if(!isset(ClassInfo::$class_info[$class->classname]["defaults"][$fieldName])) {
            ClassInfo::$class_info[$class->classname]["defaults"][$fieldName] = false;
        }

        $classname = $class->classname;
        do {
            if(!isset(ClassInfo::$class_info[$classname]["bs"])) {
                ClassInfo::$class_info[$classname]["bs"] = array();
            }

            ClassInfo::$class_info[$classname]["bs"][] = $fieldName;
            $classname = get_parent_class($classname);
        } while(is_subclass_of($classname, DataObject::class));
    }

    /**
     * @param string $fieldName
     * @param mixed $default
     * @param array $args
     * @return string
     */
    public static function getSQLDefault($fieldName, $default, $args)
    {
        return $default ? "1" : "0";
    }

    /**
     * @return int
     */
    public function forDB() {
        return $this->value ? 1 : 0;
    }
}

class SwitchSQLField extends CheckBoxSQLField {}
