<?php

defined("IN_GOMA") or die();

/**
 * Exception, which is thrown if user is locked by an admin.
 *
 * @package
 * @author Daniel Gruber
 * @copyright Ingenieurbüro Peter Gruber
 */
class LoginUserLockedException extends LoginInvalidException {

    protected $user;

    /**
     * constructor.
     * @param string $message
     * @param User|null $user
     * @param Exception|int $code
     * @param Exception $previous
     */
    public function __construct($message = "", $user = null, $code = ExceptionManager::LOGIN_USER_LOCKED, Exception $previous = null) {
        parent::__construct($message, $code, $previous);

        $this->user = $user;
    }

    /**
     * correct status.
     *
     * @return int
     */
    public function http_status() {
        return 403;
    }

    /**
     * @return null|User
     */
    public function getUser()
    {
        return $this->user;
    }
}
