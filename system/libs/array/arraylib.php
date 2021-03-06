<?php defined("IN_GOMA") OR die();

/**
 * some basic functions that are used for arrays.
 *
 * @package    goma framework
 * @link    http://goma-cms.org
 * @license LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 * @author    Goma-Team
 * @version 1.3
 *
 * last modified: 13.04.2015
 */
class ArrayLib
{
    /**
     * merges two array.
     *
     * @param array $array1
     * @param array $array2
     * @return    array
     */
    public static function merge(array $array1, array $array2)
    {
        return array_merge($array1, $array2);
    }

    /**
     * merges two arrays as sets, so every value will only exist once.
     * keys will be removed. does not support objects or arrays in arrays.
     *
     * @name    mergeSets
     * @param    array - set 1
     * @param    array - set 2
     * @return    array
     */
    public static function mergeSets(array $set1, array $set2)
    {
        return array_values(self::key_value(self::merge($set1, $set2)));
    }

    /**
     * gets the first value of an array
     * @name first
     * @param array - array
     * @access public
     * @return mixed  - value
     */
    public static function first($arr)
    {
        if (!is_array($arr))
            return false;

        if ($arr) {
            $data = array_values($arr);
            return $data[0];
        }

        return false;
    }

    /**
     * gets the first key of an array
     * @name firstkey
     * @param array - array
     * @access public
     * @return mixed  - key
     */
    public static function firstkey($arr)
    {
        if (!is_array($arr))
            return false;

        if ($arr) {
            $data = array_keys($arr);
            return $data[0];
        }

        return false;
    }

    /**
     * sets key and value to value.
     *
     * @param    array
     * @return array
     */
    public static function key_value($arr)
    {
        $array = array();

        if ($arr) {
            foreach ($arr as $value) {
                if (is_array($value)) {
                    // arrays in arrays are unsupported in this funtion.
                    throw new LogicException("ArrayLib::key_value does not support arrays in arrays.");
                } else {
                    $array[$value] = $value;
                }
            }
        }
        return $array;
    }

    /**
     * sets key and value from value where key is numeric
     *
     * @param    array
     * @return array
     */
    public static function key_value_for_id($arr)
    {
        $array = array();
        if ($arr) {
            foreach ($arr as $key => $value) {
                if (RegexpUtil::isNumber($key)) {
                    $array[$value] = $value;
                } else {
                    $array[$key] = $value;
                }
            }
        }
        return $array;
    }

    /**
     * array_map for keys
     *
     * @param callback|closure $callback
     * @param array $array
     * @param bool $unique
     * @return array
     */
    public static function map_key($callback, $array, $unique = true)
    {
        if (!is_array($array)) {
            throw new InvalidArgumentException("ArrayLib::map_key(callback, array) requires an array as second parameter.");
        }

        $arr = array();
        foreach ($array as $key => $value) {
            $newKey = call_user_func_array($callback, array($key));
            if (isset($arr[$newKey]) && $unique) {
                throw new LogicException("ArrayLib::map_key. Keys must be unique after mapping. Key $newKey duplicated.");
            }
            $arr[$newKey] = $value;
        }
        return $arr;
    }

    /**
     * @param array $arr
     * @return bool
     */
    public static function isAssocArray($arr) {
        return array_keys($arr) !== array_keys(array_values($arr));
    }

    /**
     * @param $array
     * @param $old_key
     * @param $new_key
     * @return array
     */
    public static function change_key( $array, $old_key, $new_key) {
        if( ! array_key_exists( $old_key, $array ) )
            return $array;

        $keys = array_keys( $array );
        $keys[ self::array_search_without_zero( $old_key, $keys ) ] = $new_key;

        return array_combine( $keys, $array );
    }

    public static function array_search_without_zero($needle, $haystack) {
        foreach($haystack as $key => $value) {
            if($value == $needle) {
                if($value !== "0" && $value !== 0 && $needle !== 0 && $needle !== "0") {
                    return $key;
                } else {
                    if(($value === 0 || $value === "0") && ($needle === 0 || $needle === "0")) {
                        return $key;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param array $array
     * @return int
     */
    public static function findFreeInt($array) {
        $i = 0;
        while(isset($array[$i])) {
            $i++;
        }
        return $i;
    }
} 
