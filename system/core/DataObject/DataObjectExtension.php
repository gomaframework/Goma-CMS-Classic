<?php defined("IN_GOMA") OR die();

/**
 * abstract class to extend DataObjects.
 *
 * @package		Goma\Model
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
abstract class DataObjectExtension extends Extension
{
    static $db = null;
    static $has_one = null;
    static $has_many = null;
    static $many_many = null;
    static $belongs_many_many = null;
    static $default = null;
    static $index = null;
    static $many_many_extra_fields = null;
    static $search_fields = null;

    /**
     * @param string $class
     * @return array
     */
    public static function DBFields($class) {
        return isset(static::$db) ? static::$db : array();
    }

    /**
     * @param string $class
     * @return array
     */
    public static function has_one($class) {
        return isset(static::$has_one) ? static::$has_one : array();
    }

    /**
     * @param string $class
     * @return array
     */
    public static function has_many($class) {
        return isset(static::$has_many) ? static::$has_many : array();
    }

    /**
     * @param string $class
     * @return array
     */
    public static function many_many($class) {
        return isset(static::$many_many) ? static::$many_many : array();
    }

    /**
     * @param string $class
     * @return array
     */
    public static function belongs_many_many($class) {
        return isset(static::$belongs_many_many) ? static::$belongs_many_many : array();
    }

    /**
     * @param string $class
     * @return array
     */
    public static function defaults($class) {
        return isset(static::$default) ? static::$default : array();
    }

    /**
     * @param string $class
     * @return array
     */
    public static function index($class) {
        return isset(static::$index) ? static::$index : array();
    }

    /**
     * @param string $class
     * @return array
     */
    public static function many_many_extra_fields($class) {
        return isset(static::$many_many_extra_fields) ? static::$many_many_extra_fields : array();
    }

    /**
     * @param string $class
     * @return array
     */
    public static function search_fields($class) {
        return isset(static::$search_fields) ? static::$search_fields : array();
    }

    /**
     * it does check if owner is a kind of DataObject.
     *
     * @param gObject $object
     * @return $this
     */
    public function setOwner($object)
    {
        if (!is_a($object, 'DataObject') && !is_null($object))
        {
            $className = get_class($object);
            throw new InvalidArgumentException("Object must be subclass of DataObject, but is {$className}.");
        }

        parent::setOwner($object);
        return $this;
    }
}
