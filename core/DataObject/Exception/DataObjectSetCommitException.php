<?php defined("IN_GOMA") OR die();

/**
 * Thrown when commit of DataObjectSet fails. Includes records which have failed and exceptions for those.
 *
 * @package     Goma\Model
 *
 * @license     GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author      Goma-Team
 *
 * @version     1.5.2
 */
class DataObjectSetCommitException extends GomaException
{
    /**
     * exceptions.
     *
     * @var Exception[]
     */
    public $exceptions;

    /**
     * @var DataObject[]
     */
    public $records;

    protected $standardCode = ExceptionManager::DATAOBJECTSET_COMMIT;

    /**
     * DataObjectSetCommitException constructor.
     * @param Exception[] $exceptions
     * @param DataObject[] $records
     * @param string $message
     * @param null|int $code
     * @param null|Exception $previous
     */
    public function __construct($exceptions, $records, $message = "", $code = null, $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->exceptions = $exceptions;
        $this->records = $records;
    }

    public function getDeveloperMessage()
    {
        $message = parent::getDeveloperMessage();

        foreach ($this->exceptions as $exception) {
            $message .= get_class($exception).": ".$exception->getCode().": ".$exception->getMessage()." in ".
                $exception->getFile()." on line ".$exception->getLine()."\n".
                exception_get_dev_message($exception)."\n".$exception->getTraceAsString()."\n\n";
        }

        return $message;
    }
}
