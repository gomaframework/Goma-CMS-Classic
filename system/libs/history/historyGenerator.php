<?php
defined("IN_GOMA") or die();

/**
 * --------------------------------------------------------------------------------
 * Abstract class historyGenerator for making logged records visible in history by
 * checking DB-table
 * Class extends gObject. Extensions might use generateHistoryDataExtended-function
 * for editing data-Element and therefore the displayed record.
 *
 * A class for a model logging events as a record and therefore models that should
 * display the events in the history needs to extend historyGenerator. Classes for
 * framework models are located in "system\core\history", those for system specific
 * models in "<application-path>\application\models\history".
 * The prefix is always "historyData_" followed by the model's name. E.g. the
 * historyGenerator-Class for model class "user" should be called "history_data_user".
 * The only function that needs to be implemented is generateHistoryData.
 * The function needs to return an array.
 *
 * One dimensional arrays if only one event needs to be returned.
 *      array("text" => 'foo', "icon" => 'bar')
 * Multi dimensional arrays if multiple events needs to be returned on one record.
 *      $array(
 *          [0] => array("text" => 'foo', "icon" => 'bar'),
 *          [1] => array("text" => 'foo', "icon" => 'bar'),
 *      ...)
 * where foo is the text that should be displayed for specific record and bar is
 * the path where the icon is located.
 *
 * Switch-case or if-else on $this->getHistoryRecord()->action
 * (see IModelRepository for more information) can be used to get to know about
 * current record
 * Fallback/default text should be used so uncatched events are displayed as well.
 * You can use parameters in text-variable or therefore lang-vars. Please
 * remember to replace those variables before returning the data.
 *
 * See historyData-models for inspiration.
 *
 *
 * @package Goma
 * @author Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
abstract class historyGenerator extends gObject
{
    /**
     * @var History
     */
    private $record;

    /**
     * historyGenerator constructor.
     * @param History $record
     */
    public function __construct($record)
    {
        parent::__construct();

        $this->record = $record;
    }

    /**
     * enables extension possibilities for generateHistoryData.
     *
     * @return array
     */
    public function generateHistoryDataExtended()
    {
        $data = $this->generateHistoryData();
        $this->callExtending("generateHistoryData", $data);
        return $data;
    }

    /**
     * @return array
     */
    abstract public function generateHistoryData();

    /**
     * @return History
     */
    public function getHistoryRecord()
    {
        return $this->record;
    }
}