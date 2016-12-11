<?php
defined("IN_GOMA") OR die();

/**
 * When controller does not find suitable data in model, this is thrown.
 *
 * @package Goma
 *
 * @author Goma-Team
 * @copyright 2016 Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 *
 * @version 1.0
 */
class DataNotFoundException extends GomaException {
    /**
     * @var int
     */
    protected $standardCode = ExceptionManager::DATA_NOT_FOUND;

    /**
     * FormInvalidDataException constructor.
     *
     * @param string $message
     * @param null|int $code
     * @param Exception|null $previous
     */
    public function __construct($message = "Data not found.", $code = null, $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    public function http_status() {
        return 404;
    }
}
