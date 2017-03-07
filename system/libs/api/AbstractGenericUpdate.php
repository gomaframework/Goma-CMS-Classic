<?php
namespace Goma\Rest;
use ArrayLib;

defined("IN_GOMA") OR die();
/**
 * Generic Update-Class.
 * Translates an assoc array to an object.
 *
 * @package Goma\Rest
 *
 * @license     GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author Goma-Team
 * @copyright 2017 Goma-Team
 *
 * @version 1.0
 */
abstract class AbstractGenericUpdate extends \gObject {

    /**
     * @var array
     */
    protected $data;

    /**
     * AbstractGenericUpdate constructor.
     * @param array $data
     */
    public function __construct($data)
    {
        parent::__construct();

        $this->data = $data;
    }

    /**
     * @return array
     */
    protected abstract function getTranslateDictionary();

    /**
     * @return \gObject
     */
    protected abstract function getBaseEntitiy();

    protected function getExtendedTranslateDictionary() {
        $dict = (array) $this->getTranslateDictionary();
        if(!ArrayLib::isAssocArray($dict)) {
            throw new \InvalidArgumentException();
        }

        $this->callExtending("getTranslateDictionary", $dict);
        if(!ArrayLib::isAssocArray($dict)) {
            throw new \InvalidArgumentException();
        }

        return ArrayLib::map_key("strtolower", $dict);
    }

    /**
     * @return \gObject
     */
    public function getEntity() {
        $entity = $this->getBaseEntitiy();

        $data = ArrayLib::map_key("strtolower", $this->data);

        foreach($this->getExtendedTranslateDictionary() as $key => $value) {
            if(isset($data[$key])) {
                $entity->{$value} = $data[$key];
            }
        }

        return $entity;
    }
}
