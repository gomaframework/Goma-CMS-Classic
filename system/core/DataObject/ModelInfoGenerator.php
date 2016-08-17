<?php defined("IN_GOMA") OR die();

/**
 * @package		Goma\Model
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class ModelInfoGenerator {
    /**
     * combines data from given class-attribute + given extension method
     * @param string|gObject $class
     * @param string $staticProp static property on class
     * @param string|null $extensionMethod static method on extension
     * @param bool $useParents
     * @internal
     * @return array
     */
    public static function generate_combined_array($class, $staticProp, $extensionMethod = null, $useParents = false) {
        $class = ClassManifest::resolveClassName($class);

        $fields = (array)StaticsManager::getNotExtendedStatic($class, $staticProp);

        // fields of extensions
        if($extensionMethod !== null) {
            foreach (gObject::getExtensionsForClass($class, false) as $extension) {
                if (gObject::method_exists($extension, $extensionMethod)) {
                    if ($extensionFields = call_user_func_array(array($extension, $extensionMethod), array($class))) {
                        $fields = array_merge($fields, (array)$extensionFields);
                    }
                }
            }
        }

        // if parents, include parents.
        $parent = get_parent_class($class);
        if ($useParents === true && $parent != "DataObject" && $parent !== false) {
            $fields = array_merge(self::generate_combined_array($parent, $staticProp, $extensionMethod, true), $fields);
        }

        $fields = ArrayLib::map_key("strtolower", $fields, false);

        return $fields;
    }

    /**
     * gets all dbfields
     *
     * @param string|gObject $class
     * @param bool $parents
     * @return array
     */
    public static function generateDBFields($class, $parents = false) {

        $fields = self::generate_combined_array($class, "db", "DBFields", $parents);

        $fields = array_merge(self::getHasOneArrayWithValue($class, "int(10)"), $fields);

        if (!empty($fields) && gObject::method_exists($class, "DefaultSQLFields")) {
            $fields = array_merge(call_user_func_array(array($class, "DefaultSQLFields"), array($class)), $fields);
        }

        if(DEV_MODE) {
            self::validateDBFields($class, $fields);
        }

        return $fields;
    }

    /**
     * validates db fields.
     *
     * @param $class
     * @param $fields
     * @throws DBFieldNotValidException
     */
    public static function validateDBFields($class, $fields) {
        foreach($fields as $name => $type) {
            // hack to not break current Goma-CMS Build
            if((
                    in_array($name, array("long", "order", "select", "where", "group")) ||
                    in_array($name, ViewAccessableData::$notViewableMethods) ||
                    !preg_match('/^[a-zA-Z_][a-zA-Z_0-9]+$/', $name)
                )
                &&
                (ClassInfo::$appENV["app"]["name"] != "gomacms" || goma_version_compare(ClassInfo::appVersion(), "2.0RC2-074", ">="))) {
                throw new DBFieldNotValidException($class . "." . $name);
            }
        }
    }

    /**
     * returns defaults
     *
     * @param string|gObject $class
     * @param bool $parents
     * @return array
     */
    public static function generateDefaults($class, $parents = true) {

        $defaults = self::generate_combined_array($class, "default", "defaults", $parents);

        return $defaults;
    }

    /**
     * returns casting
     *
     * @param string|gObject $class
     * @param bool $parents
     * @return array
     */
    public static function generateCasting($class, $parents = true) {

        $casting = self::generate_combined_array($class, "casting", "casting", $parents);

        return $casting;
    }

    /**
     * gets has_one
     *
     * @access public
     * @param string|gObject $class
     * @param bool $parents
     * @return array
     */
    public static function generateHas_one($class, $parents = true) {

        $has_one = self::generate_combined_array($class, "has_one", "has_one", $parents);

        if (ClassInfo::get_parent_class($class) == "dataobject") {
            $has_one["autor"] = "user";
            $has_one["editor"] = "user";
        }

        return $has_one;
    }

    /**
     * returns has-one array with given value.
     *
     * @param string|gObject $class
     * @param string $value
     * @return array
     */
    protected static function getHasOneArrayWithValue($class, $value) {
        $arr = array();
        foreach(self::generateHas_one($class, false) as $name => $v) {
            if (!isset($indexes[$name . "id"])) {
                $arr[$name . "id"] = $value;
            }
        }

        return $arr;
    }

    /**
     * gets search-fields.
     *
     * @access public
     * @param string|gObject $class
     * @param bool $parents
     * @return array
     */
    public static function generate_search_fields($class, $parents = false) {
        $searchFields = self::generate_combined_array($class, "search_fields", "search_fields", $parents);

        $searchFields = array_map("strtolower", $searchFields);

        return $searchFields;
    }

    /**
     * gets has_many
     *
     * @access public
     * @param string|gObject $class
     * @param bool $parents
     * @return array
     */
    public static function generateHas_many($class, $parents = true) {
        return self::generate_combined_array($class, "has_many", "has_many", $parents);
    }

    /**
     * gets many_many
     *
     * @param string|gObject $class
     * @param bool $parents
     * @return array
     */
    public static function generateMany_many($class, $parents = true) {
        $many_many = self::generate_combined_array($class, "many_many", "many_many", $parents);

        $many_many = self::convertManyManyToLowerCase($many_many, $class);

        return $many_many;
    }

    /**
     * gets belongs_many_many
     *
     * @param string|gObject $class
     * @param bool $parents
     * @return array
     */
    public static function generateBelongs_many_many($class, $parents = true) {
        $belongs_many_many = self::generate_combined_array($class, "belongs_many_many", "belongs_many_many", $parents);

        $belongs_many_many = self::convertManyManyToLowerCase($belongs_many_many, $class);

        return $belongs_many_many;
    }

    /**
     * converts many-many error to lower-case.
     *
     * @param array $many_many
     * @param string $class for exception
     * @return array
     */
    protected static function convertManyManyToLowerCase($many_many, $class) {
        // put everything in lowercase
        foreach($many_many as $k => $v) {
            if(is_string($v)) {
                $many_many[$k] = strtolower($v);
            } else if(isset($many_many[$k]["class"])) {
                $many_many[$k]["class"] = strtolower($v["class"]);
            }else if(isset($many_many[$k][DataObject::RELATION_TARGET])) {
                $many_many[$k][DataObject::RELATION_TARGET] = strtolower($v[DataObject::RELATION_TARGET]);
            } else {
                throw new LogicException("Information in Many-Many must be either array with key class or string. $k is $class wasn't.");
            }
        }

        return $many_many;
    }

    /**
     * gets extra-fields for given class and key.
     *
     * @param string|gObject $class
     * @param string $name of many-many-relationship
     * @return array
     */
    public static function get_many_many_extraFields($class, $name) {
        $name = strtolower($name);
        $fields = array();
        if(StaticsManager::hasStatic($class, "many_many_extra_fields")) {
            $extraFields = ArrayLib::map_key("strtolower", (array)StaticsManager::getStatic($class, "many_many_extra_fields"));
            if (isset($extraFields[$name])) {
                $fields = $extraFields[$name];
            }
        }

        foreach(gObject::getExtensionsForClass($class, false) as $extension) {
            if(gObject::method_exists($extension, "many_many_extra_fields")) {
                if($extensionFields = call_user_func_array(array($extension, "many_many_extra_fields"), array())) {
                    $extensionFields = ArrayLib::map_key("strtolower", $extensionFields);
                    if(isset($extensionFields[$name])) {
                        $fields = array_merge($fields, $extensionFields[$name]);
                    }
                }
            }
        }

        return $fields;
    }

    /**
     * indexes
     *
     * @param string|gObject $class
     * @return array
     */
    public static function generateIndexes($class) {
        $indexes = self::generate_combined_array($class, "index", "index", false);

        $indexes = array_merge(self::getHasOneArrayWithValue($class, "INDEX"), $indexes);

        $searchable_fields = StaticsManager::getNotExtendedStatic($class, "search_fields");
        if ($searchable_fields) {
            // we add an index for fast searching
            $indexes["searchable_fields"] = array("type" => "INDEX", "fields" => implode(",", $searchable_fields), "name" => "searchable_fields");
        }

        // validate
        self::validateIndexes($indexes);

        $db = self::generateDBFields($class, false);
        if (isset($db["last_modified"])) {
            $indexes["last_modified"] = "INDEX";
        }

        return $indexes;
    }

    /**
     * validates indexes.
     *
     * @name validateIndexes
     * @param indexes
     */
    protected static function validateIndexes($indexes) {
        foreach($indexes as $name => $type) {
            if (is_array($type)) {
                if (!isset($type["fields"])) {
                    throw new LogicException("Index $name in DataObject $class is invalid. Fields are required.", ExceptionManager::INDEX_INVALID);
                }
            }
        }
    }

    /**
     * gets a table_name for a given class
     * @param string|object $class
     * @return bool
     */
    public static function classTable($class) {
        $class = ClassManifest::resolveClassName($class);

        return isset(ClassInfo::$class_info[$class]["table"]) ? ClassInfo::$class_info[$class]["table"] : false;
    }
}
