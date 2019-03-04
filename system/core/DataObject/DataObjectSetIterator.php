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
class DataObjectSetIterator extends ViewAccessableDataIterator
{
    /**
     * @var DataObjectSet
     */
    protected $dataSet;

    /**
     * @var array
     */
    protected $itemsRef;

    /**
     * @var null|object
     */
    protected $firstCacheRef;

    /**
     * @var null|object
     */
    protected $lastCacheRef;

    /**
     * DataSetIterator constructor.
     * @param array $data
     * @param array $itemsRef
     * @param null|object $firstCacheRef
     * @param null|object $lastCacheRef
     * @param DataObjectSet $dataSet
     */
    public function __construct(array $data, &$itemsRef, &$firstCacheRef, &$lastCacheRef, DataObjectSet $dataSet)
    {
        parent::__construct($data);

        $this->itemsRef = &$itemsRef;
        $this->lastCacheRef = &$lastCacheRef;
        $this->firstCacheRef = &$firstCacheRef;
        $this->dataSet = $dataSet;
    }

    /**
     * gets the current value
     *
     * @return DataObject
     */
    public function current()
    {
        $position = $this->position;

        if(isset($this->itemsRef[$position]) && $this->itemsRef[$position] === $this->data[$position]) {
            $this->data[$position] = $this->itemsRef[$position] = $this->dataSet->getConverted($this->data[$position]);

            if($position == 0) {
                $this->firstCacheRef = $this->itemsRef[$position];
            }

            if($position == count($this->itemsRef) - 1) {
                $this->lastCacheRef = $this->itemsRef[$position];
            }
        } else {
            $this->data[$position] = $this->dataSet->getConverted($this->data[$position]);
        }

        return $this->data[$position];
    }
}
