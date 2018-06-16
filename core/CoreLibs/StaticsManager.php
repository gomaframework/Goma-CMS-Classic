<?php defined("IN_GOMA") OR die();
/**
 * provides some methods to use to get, set and call statics on classes.
 *
 * @package		Goma\Core
 * @version		1.0
 */

class StaticsManager {
    /**
     * this var saves for each class, which want to save vars in cache, the names
     *@var array
     */
    public static $save_vars;

    /**
     * array of classes, which we already called static hook.
     */
    public static $hook_called = array();

    /**
     * validates if class and variable/method-names are valid.
     * it throws an exception if not and returns correct class-name.
     *
     * @param string|gObject $class
     * @param string $var
     * @return string classname
     */
    public static function validate_static_call($class, $var)
    {
        $class = ClassInfo::find_class_name($class);

        if (empty($var)) {
            throw new InvalidArgumentException("Invalid name of variable $var for $class");
        }

        return $class;
    }

    /**
     * Gets the value of $class::$$var.
     *
     * @param string|gObject $class Name of the class.
     * @param string $var Name of the variable.
     *
     * @param bool $ignoreAccess
     * @return mixed Value of $var.
     * @throws ReflectionException
     */
    public static function getStatic($class, $var, $ignoreAccess = false)
    {
        $className = ClassManifest::resolveClassName($class);

        $reflectionClass = new ReflectionClass($className);

        if($reflectionClass->hasProperty($var)) {
            $property = $reflectionClass->getProperty($var);

            if ($ignoreAccess) {
                $property->setAccessible(true);
            }

            if(!$property->isStatic()) {
                if(!is_a($class, $className)) {
                    throw new InvalidArgumentException();
                }

                return $property->getValue($class);
            }

            return $property->getValue();
        }

        return null;
    }

    /**
     * Checks, if $class::$$var is set.
     *
     * @param string|gObject $class Name of the class.
     * @param string $var Name of the variable.
     *
     * @return boolean
     * @throws ReflectionException
     */
    public static function hasStatic($class, $var)
    {
        if(empty($var)) {
            throw new InvalidArgumentException("Variable might not be empty.");
        }

        $class = ClassManifest::resolveClassName($class);
        $reflectionClass = new ReflectionClass($class);

        return $reflectionClass->hasProperty($var);
    }

    /**
     * Sets $value for $class::$$var.
     *
     * @param string|gObject $class Name of the class.
     * @param string $var Name of the variable.
     * @param mixed $value
     * @param bool $ignoreAccess
     * @throws ReflectionException
     */
    public static function setStatic($class, $var, $value, $ignoreAccess = false)
    {
        $class = ClassManifest::resolveClassName($class);

        $reflectionClass = new ReflectionClass($class);
        if($reflectionClass->hasProperty($var)) {
            $property = $reflectionClass->getProperty($var);

            if ($ignoreAccess) {
                $property->setAccessible(true);
            }

            $property->setValue($value);
        } else {
            throw new ReflectionException("Variable $var not found on class $class.");
        }
    }

    /**
     * Calls $class::$$func.
     *
     * @param string|gObject $class Name of the class.
     * @param string $func Name of the function.
     * @param bool $ignoreAccess
     * @param array $args
     *
     * @return mixed return value of call
     * @throws ReflectionException
     */
    public static function callStatic($class, $func, $ignoreAccess = false, $args = null)
    {
        $class = self::validate_static_call($class, $func);

        $reflectionClass = new ReflectionClass($class);
        if($reflectionClass->hasMethod($func)) {
            $method = $reflectionClass->getMethod($func);

            if($ignoreAccess) {
                $method->setAccessible(true);
            } else if($method->isProtected()) {
                throw new BadMethodCallException('Call to protected method ' . $class . '::' . $func);
            } else if($method->isPrivate()) {
                throw new BadMethodCallException('Call to private method ' . $class . '::' . $func);
            }

            if($args) {
                return call_user_func_array(array($method, "invoke"), array_merge(array(null), $args));
            }

            return $method->invoke(null, array($class));
        } else {
            throw new BadMethodCallException('Call to unknown method ' . $class . '::' . $func);
        }
    }

    /**
     * adds a var to cache
     * @param string|gObject $class
     * @param string $variableName
     * @throws ReflectionException
     */
    public static function addSaveVar($class, $variableName)
    {
        if (class_exists("ClassManifest")) {
            $class = ClassManifest::resolveClassName($class);
        }

        if(class_exists($class, false)) {
            if(!defined("GENERATE_CLASS_INFO")) {
                if (isset(ClassInfo::$class_info[$class][$variableName])) {
                    self::setStatic($class, $variableName, ClassInfo::$class_info[$class][$variableName], true);
                }
            }
        } else {
            die("Class $class must be loaded before adding SaveVars.");
        }

        self::$save_vars[$class][] = $variableName;
    }

    /**
     * @param string $class
     * @throws ReflectionException
     * @internal
     */
    public static function setSaveVarsForClass($class) {
        $class = ClassManifest::resolveClassName($class);
        foreach(self::$save_vars[$class] as $variableName) {
            if (isset(ClassInfo::$class_info[$class][$variableName])) {
                self::setStatic($class, $variableName, ClassInfo::$class_info[$class][$variableName], true);
            }
        }
    }

    /**
     * gets for a specific class the save_vars
     * @param $class
     * @return array
     */
    public static function getSaveVars($class)
    {
        $class = ClassManifest::resolveClassName($class);

        if (isset(self::$save_vars[$class])) {
            return self::$save_vars[$class];
        }
        return array();
    }

    /**
     * gets static property while checking if it is only inherited property.
     *
     * @param string $class
     * @param string $staticProp
     * @param bool $filterParent
     * @return mixed
     */
    public static function getNotInheritedStatic($class, $staticProp, $filterParent = true)
    {
        if (StaticsManager::hasStatic($class, $staticProp)) {
            // validates that it is not just the extended property.
            $parent = get_parent_class($class);
            $fields = StaticsManager::getStatic($class, $staticProp);

            if ($filterParent && $parent && self::getNotInheritedStatic($parent, $staticProp, false) === $fields) {
                return null;
            }

            return $fields;
        }

        return null;
    }

    /**
     * Helper function to merge array return values from class specific methods with parent methods.
     *
     * @param string $startingClass
     * @param string $method
     * @param array $params
     * @return array
     */
    public static function getInheritedStaticArrayFromMethod($startingClass, $method, $params = array()) {
        $data = array();
        $current = $startingClass;
        while(gObject::method_exists($current, $method)) {
            $data = array_merge(
                (array) call_user_func_array(array($current, $method), $params),
                $data
            );

            $current = get_parent_class($current);
        }

        return $data;
    }
}
