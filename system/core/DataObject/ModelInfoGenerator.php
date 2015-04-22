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
     * @param string|object $class
     * @param string $staticProp static property on class
     * @param string $extensionMethod static method on extension
     * @param bool $useParents
     * @return array
     */
    protected static function generate_combined_array($class, $staticProp, $extensionMethod, $useParents = false) {
        $class = ClassManifest::resolveClassName($class);

        $fields = array();

        if (StaticsManager::hasStatic($class, $staticProp)) {
            $fields = (array)StaticsManager::getStatic($class, $staticProp);
        }

        // fields of extensions
        foreach(Object::getExtensionsForClass($class, false) as $extension) {
            if(Object::method_exists($extension, $extensionMethod)) {
                if($extensionFields = call_user_func_array(array($extension, $extensionMethod), array($class))) {
                    $fields = array_merge($fields, (array) $extensionFields);
                }
            }
        }

        // if parents, include parents.
        $parent = get_parent_class($class);
        if ($useParents == true && $parent != "DataObject" && $parent !== false) {
            $fields = array_merge(self::generate_combined_array($parent, $staticProp, $extensionMethod, true), $fields);
        }

        $fields = ArrayLib::map_key("strtolower", $fields, false);

        return $fields;
    }

    /**
     * gets all dbfields
     *
     * @param string|object $class
     * @param bool $parents
     * @return array
     */
    public static function generateDBFields($class, $parents = false) {

        $fields = self::generate_combined_array($class, "db", "DBFields", $parents);

        foreach(self::generateHas_one($class, false) as $key => $value) {
            if (!isset($fields[$key . "id"])) { // check if field already is existing.
                $fields[$key . "id"] = "int(10)";
            }

            unset($key, $value);
        }

        if ($fields && Object::method_exists($class, "DefaultSQLFields")) {
            $fields = array_merge(call_user_func_array(array($class, "DefaultSQLFields"), array($class)), $fields);
        }

        return $fields;
    }

    public static function validateDBFields($class, $fields) {
        foreach($fields as $name => $type) {
            // hack to not break current Goma-CMS Build
            if(in_array($name, ViewAccessableData::$notViewableMethods) && (ClassInfo::$appENV["app"]["name"] != "gomacms" || goma_version_compare(ClassInfo::appVersion(), "2.0RC2-074", ">="))) {
                throw new DBFieldNotValidException($class . "." . $name);
            }
        }
    }

    /**
     * returns defaults
     *
     * @param string|object $class
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
     * @param string|object $class
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
     * @param string|object $class
     * @param bool $parents
     * @return array
     */
    public static function generateHas_one($class, $parents = true) {

        $has_one = self::generate_combined_array($class, "has_one", "has_one", $parents);

        if (ClassInfo::get_parent_class($class) == "dataobject") {
            $has_one["autor"] = "user";
            $has_one["editor"] = "user";
        }

        $has_one = array_map("strtolower", $has_one);

        return $has_one;
    }

    /**
     * gets search-fields.
     *
     * @access public
     * @param string|object $class
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
     * @param string|object $class
     * @param bool $parents
     * @return array
     */
    public static function generateHas_many($class, $parents = true) {

        $has_many = self::generate_combined_array($class, "has_many", "has_many", $parents);

        $has_many = array_map("strtolower", $has_many);
        return $has_many;
    }

    /**
     * gets many_many
     *
     * @param string|object $class
     * @param bool $parents
     * @return array
     */
    public static function generateMany_many($class, $parents = true) {
        $many_many = self::generate_combined_array($class, "many_many", "belongs_many_many", $parents);

        $many_many = self::convertManyManyToLowerCase($many_many, $class);

        return $many_many;
    }

    /**
     * gets belongs_many_many
     *
     * @param string|object $class
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
            } else {
                throw new LogicException("Information in Many-Many must be either array with key class or string. $k is $class wasn't.");
            }
        }

        return $many_many;
    }

    /**
     * generates many-many-data for given key and value pair.
     * it also have to know if it is belonging or not.
     *
     * @param string $class
     * @param $key
     * @param $value
     * @param bool $belonging
     */
    protected static function generate_many_many_tableinfo($class, $key, $value, $belonging = false) {
        $key = trim(strtolower($key));
        $extraFields = self::get_many_many_extraFields($class, $key);

        $table = "many_many_".strtolower(trim($class))."_".  $key . '_' . $value;
        if (!SQL::getFieldsOfTable($table)) {
            $table = "many_".strtolower(trim($class))."_".  $key;
        }
    }


    /**
     * gets extra-fields for given class and key.
     *
     * @param string|object $class
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

        foreach(Object::getExtensionsForClass($class, false) as $extension) {
            if(Object::method_exists($extension, "many_many_extra_fields")) {
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
     * @param string|object $class
     * @return array
     */
    public static function generateIndexes($class) {
        $indexes = self::generate_combined_array($class, "index", "index", false);

        foreach(self::generateHas_one($class, false) as $key => $value) {
            if (!isset($indexes[$key . "id"])) {
                $indexes[$key . "id"] = "INDEX";
                unset($key, $value);
            }
        }

        $searchable_fields = StaticsManager::getStatic($class, "search_fields");
        if ($searchable_fields) {
            // we add an index for fast searching
            $indexes["searchable_fields"] = array("type" => "INDEX", "fields" => implode(",", $searchable_fields), "name" => "searchable_fields");
        }

        // validate
        foreach($indexes as $name => $type) {
            if (is_array($type)) {
                if (!isset($type["type"]) || !isset($type["fields"])) {
                    throw new LogicException("Index $name in DataObject $class is invalid. Type and Fields are required.", ExceptionManager::INDEX_INVALID);
                }
            }
        }

        $db = self::generateDBFields($class, false);
        if (isset($db["last_modified"])) {
            $indexes["last_modified"] = "INDEX";
        }

        return $indexes;

    }
}