<?php

defined("IN_GOMA") or die();

/**
 * Iterator for ArrayList.
 *
 * @package		Goma\Core\ViewModel
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class DataSetIterator extends ArrayListIterator
{
    /**
     * @var DataSet
     */
    protected $dataSet;

    /**
     * @var array
     */
    protected $currentItemsRef;

    /**
     * DataSetIterator constructor.
     * @param array $data
     * @param array $currentItemsRef
     * @param DataSet $dataSet
     */
    public function __construct(array $data, &$currentItemsRef, DataSet $dataSet)
    {
        parent::__construct($data);

        $this->currentItemsRef = &$currentItemsRef;
        $this->dataSet = $dataSet;
    }

    /**
     * gets the value of the current item.
     */
    public function current() {
        if(isset($this->currentItemsRef[$this->position]) &&
            $this->currentItemsRef[$this->position] === $this->data[$this->position]) {
            $this->data[$this->position] = $this->currentItemsRef[$this->position] =
                $this->dataSet->getConverted(parent::current());
        } else {
            $this->data[$this->position] = $this->dataSet->getConverted(parent::current());
        }

        return $this->data[$this->position];
    }
}
