<?php defined("IN_GOMA") OR die();

/**
 * compares a model with an array.
 *
 * @package     Goma\Model
 *
 * @license     GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author      Goma-Team
 *
 * @version     1.0
 */
class DataObjectCompare {
    /**
     * gets array of changed fields from model.
     *
     * @param DataObject $model
     * @param array $newdata
     * @param array $additionalFields
     * @return array
     */
    public static function getChanges($model, $newdata, $additionalFields = array())
    {
        $changed = array();

        if (is_object($newdata) && gObject::method_exists($newdata, "toArray")) {
            /** @var ViewAccessableData $newdata */
            $newdata = ArrayLib::map_key("strtolower", $newdata->ToArray());
        }

        // first calculate change-count
        $data = ArrayLib::map_key("strtolower", $model->ToArray());
        $keys = array_merge(array_keys($data), $additionalFields);
        foreach ($keys as $key) {
            $val = isset($data[$key]) ? $data[$key] : null;
            if (isset($newdata[$key])) {
                if(!self::isEqual($newdata[$key], $val)) {
                    $changed[] = strtolower(trim($key));
                }
            }
        }

        return $changed;
    }

    /**
     * @param $var1
     * @param $var2
     * @return bool
     */
    public static function isEqual($var1, $var2) {
        $comparableTypes = array("boolean", "integer", "string", "double");
        if (gettype($var1) != gettype($var2) &&
            !in_array(gettype($var1), $comparableTypes) &&
            !in_array(gettype($var2), $comparableTypes)
        ) {
            return false;
        } else if ($var1 != $var2) {
            return false;
        }

        return true;
    }
}
