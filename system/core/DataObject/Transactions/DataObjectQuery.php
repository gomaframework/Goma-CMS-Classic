<?php defined("IN_GOMA") OR die();

/**
 * implements reading + cache.
 *
 * @package		Goma\DB
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class DataObjectQuery {
    /**
     * cache
     */
    protected static $datacache = array();

    /**
     * @var array[]
     */
    protected static $cacheCallbacks = array();

    /**
     * registers cache callback. Returns closure to unregister.
     *
     * @param string $givenClass
     * @param Closure $callback
     * @return Closure
     */
    public static function registerCacheCallback($givenClass, $callback) {
        $class = self::getBaseClass($givenClass);

        if(!is_callable($callback)) {
            throw new InvalidArgumentException("Callback not callable");
        }

        if(!ClassInfo::exists($class)) {
            throw new InvalidArgumentException("Given DataObject cache class not found.");
        }

        do {
            $random = randomString(10);
        } while(isset(self::$cacheCallbacks[$class][$random]));

        self::$cacheCallbacks[$class][$random] = $callback;
        return function() use($class, $random) {
            unset(self::$cacheCallbacks[$class][$random]);
        };
    }

    /**
     * clears cache.
     *
     * @param string|object|null $class
     */
    public static function clearCache($class = null) {
        if(isset($class)) {
            self::$datacache[self::getBaseClass($class)] = array();

            if(isset(self::$cacheCallbacks[self::getBaseClass($class)])) {
                foreach (self::$cacheCallbacks[self::getBaseClass($class)] as $callback) {
                    call_user_func_array($callback, array());
                }
            }
        } else {
            self::$datacache = array();

            foreach(self::$cacheCallbacks as $class => $calls) {
                foreach ($calls as $callback) {
                    call_user_func_array($callback, array());
                }
            }
        }
    }

    /**
     * @param string|object $class
     * @param string $hash
     * @return null|array
     */
    public static function getCached($class, $hash) {
        if(!is_string($hash)) {
            throw new InvalidArgumentException();
        }

        return isset(self::$datacache[self::getBaseClass($class)][$hash]) ?
            self::$datacache[self::getBaseClass($class)][$hash] :
            null;
    }

    /**
     * @param string|object $class
     * @param string $hash
     * @param array $data
     */
    public static function addCached($class, $hash, $data) {
        if(!is_array($data) || !is_string($hash)) {
            throw new InvalidArgumentException();
        }

        self::$datacache[self::getBaseClass($class)][$hash] = $data;
    }

    /**
     * @param string|object $class
     * @return string
     */
    public static function getBaseClass($class) {
        $class = ClassManifest::resolveClassName($class);

        return isset(ClassInfo::$class_info[$class]["baseclass"]) ? ClassInfo::$class_info[$class]["baseclass"] : $class;
    }
}
