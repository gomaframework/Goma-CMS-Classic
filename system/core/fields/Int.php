<?php defined("IN_GOMA") OR die();

/**
 * Every value of an field can used as object if you call doObject($offset) for Int-fields
 * This Object has some very cool methods to convert the field
 */
class intSQLField extends Varchar
{
    /**
     * generatesa a numeric field
     *
     * @param string $title
     * @return FormField|NumberField|TextArea
     */
    public function formfield($title = null)
    {
        return new NumberField($this->name, $title);
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
            if(!self::isNullType($fieldType)) {
                ClassInfo::$class_info[$class->classname]["defaults"][$fieldName] = 0;
            }
        }
    }

    /**
     * @param string $fieldName
     * @param mixed $default
     * @param array $args
     * @return mixed|null
     */
    public static function getSQLDefault($fieldName, $default, $args)
    {
        return $default != 0 ? $default : null;
    }
}
