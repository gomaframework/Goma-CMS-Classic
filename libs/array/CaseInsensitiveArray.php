<?php

namespace Goma\ArrayLib;

use InvalidArgumentException;

defined("IN_GOMA") or die();

/**
 * This class provides an array object, which maps all keys to lowercase and all keys are accessible also via uppercase
 * notation.
 *
 *  @package Goma\ArrayLib
 * @link    http://goma-cms.org
 * @license LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 * @author    Goma-Team
 */
class CaseInsensitiveArray extends \ArrayIterator
{
    /**
     * construct this from given array.
     * @param array|CaseInsensitiveArray $data
     */
    public function __construct($data = array())
    {
        if(!is_array($data)) {
            if (method_exists($data, "getArrayCopy")) {
                $data = $data->getArrayCopy();
            } else if(method_exists($data, "ToArray")) {
                $data = $data->ToArray();
            }
        }

        if(!is_array($data)) {
            throw new InvalidArgumentException("\$data must be array or implement getArrayCopy() or ToArray()");
        }

        parent::__construct(\ArrayLib::map_key("strtolower", $data));
    }


    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return parent::offsetExists(strtolower($offset));
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return parent::offsetGet(strtolower($offset));
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        parent::offsetSet(strtolower($offset), $value);
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        parent::offsetUnset(strtolower($offset));
    }

    /**
     * merges an array or CaseInsensitiveArray into this.
     * @param array|CaseInsensitiveArray $toMerge
     * @return static
     */
    public function merge($toMerge) {
        $arrays = func_get_args();
        $data = $this->getArrayCopy();
        foreach($arrays as $array) {
            if(is_array($array)) {
                $data = array_merge($data, \ArrayLib::map_key("strtolower", $array));
            } else {
                foreach($array as $key => $item) {
                    $data[strtolower($key)] = $item;
                }
            }
        }

        return new static($data);
    }
}
