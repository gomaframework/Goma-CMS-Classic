<?php defined('IN_GOMA') OR die();


/**
 * @package		Goma\Model
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @version     5.0.2
 */
class CheckBoxQueryResultParser extends Extension {
    /**
     * converts ints to boolean values.
     *
     * @param array $data
     */
    public function argumentQueryResult(&$data) {
        if(isset(ClassInfo::$class_info[$this->getOwner()->classname]["bs"])) {
            foreach($data as $rowKey => $row) {
                foreach(ClassInfo::$class_info[$this->getOwner()->classname]["bs"] as $field) {
                    if(isset($row[$field])) {
                        $data[$rowKey][$field] = !!$row[$field];
                    }
                }
            }
        }
    }
}

gObject::extend(DataObject::class, CheckBoxQueryResultParser::class);
