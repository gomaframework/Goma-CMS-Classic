<?php defined("IN_GOMA") OR die();

/**
 * Base-Interface for all DB-Fields.
 *
 * @package		Goma\Core\Model
 * @version		1.6
 */
interface IDataBaseField {
    /**
     * constructor of each IDataBaseField.
     * The constructor should validate the field and throw exception if not valid.
     * If name and value is null, no validation should be done since this is the standard indexer process of ClassInfo.
     *
     * @param string $name
     * @param mixed$value
     * @param array $args
     */
    public function __construct($name, $value, $args = array());

    /**
     * set the value of the field
     * @param mixed $value
     * @return
     */
    public function setValue($value);

    /**
     * gets the value of the field
     * @return mixed
     */
    public function getValue();

    /**
     * sets the name of the field
     * @param $name
     * @return
     */
    public function setName($name);

    /**
     * gets the name of the field
     * @return string
     */
    public function getName();

    /**
     * gets the raw-data of the field
     * should be give back the same as getValue
     *
     * @return mixed
     */
    public function raw();

    /**
     * generates the default form-field for this field
     *
     * @param string $title
     */
    public function formfield($title = null);

    /**
     * search-field for searching
     *
     * @param string $title
     */
    public function searchfield($title = null);

    /**
     * gets the field-type for the database, for example if you want to have the type varchar instead of the name of this class
     *
     * @param array $args
     * @param null|bool $allowNull bool if null type is defined (e.g. NOT NULL or NULL), otherwise null
     * @return string|null
     */
    static public function getFieldType($args = array(), $allowNull = null);

    /**
     * toString-Method
     * should call default-convert
     */
    public function __toString();

    /**
     * bool - for IF in template
     * should give back if the value of this field represents a false or true
     *
     * @return bool
     */
    public function toBool();

    /**
     * to don't give errors for unknown calls, should always give back raw-data
     * @param string $name
     * @param array $args
     * @return
     */
    public function __call($name, $args);

    /**
     * bool, like toBool
     * @return bool
     */
    public function bool();

    /**
     * returns string representation of this field for the view
     * @return string
     */
    public function forTemplate();
}
