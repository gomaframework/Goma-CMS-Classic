<?php

defined("IN_GOMA") or die();

/**
 * Exception, which is thrown if an user-password combination is invalid.
 *
 * @package		Goma\Security\Users
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class LoginInvalidException extends LogicException {
    /**
     * constructor.
     */
    public function __construct($message = "", $code = ExceptionManager::LOGIN_INVALID, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * correct status.
     *
     * @return int
     */
    public function http_status() {
        return 403;
    }
}
