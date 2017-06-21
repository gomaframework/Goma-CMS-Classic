<?php defined("IN_GOMA") OR die();

/**
 * Used for Geo-Coordinates.
 * Class PointSQLField
 */
class PointSQLField extends DBField {

    /**
     * for db.
     */
    public function forDBQuery() {
        return "GeomFromText('POINT(".$this->value.")')";
    }
}
