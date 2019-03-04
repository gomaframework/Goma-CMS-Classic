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
class InvalidSortArgumentException extends InvalidArgumentException
{
    /**
     * @var string
     */
    protected $sortField;

    /**
     * InvalidSortArgumentException constructor.
     * @param string $field
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($field, $message = "", $code = 0, Throwable $previous = null)
    {
        $this->sortField = $field;
        parent::__construct($message, $code, $previous);
    }

    public function http_status() {
        return 500;
    }

    public function getDeveloperMessage() {
        return "Sort-Field not possible: $this->sortField \n" . $this->http_status() != 200 ? " Status: " . $this->http_status() : "";
    }
}
