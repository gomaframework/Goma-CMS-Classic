<?php
namespace Goma\Controller\Exception;
use ExceptionManager;
use GomaException;

defined("IN_GOMA") OR die();

/**
 * When director does not find any suiting controller this is the exception.
 *
 * @package Goma
 *
 * @author Goma-Team
 * @copyright 2016 Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 *
 * @version 1.0
 */
class ResourceNotFoundException extends GomaException {
    /**
     * @var int
     */
    protected $standardCode = ExceptionManager::DATA_NOT_FOUND;

    /**
     * FormInvalidDataException constructor.
     *
     * @param string $message
     * @param null|int $code
     * @param \Throwable|null $previous
     */
    public function __construct($message = "Resource not found.", $code = null, $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    public function http_status() {
        return 404;
    }
}
