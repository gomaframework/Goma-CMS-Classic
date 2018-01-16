<?php

defined("IN_GOMA") or die();

/**
 * Exception, which is thrown if an user has not been activated by email, yet.
 *
 * @package		Goma\Security\Users
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class LoginUserMustUnlockException extends LoginInvalidException {

    protected $user;

    /**
     * constructor.
     * @param string $message
     * @param User|null $user
     * @param Exception|int $code
     * @param Exception $previous
     */
    public function __construct($message = "", $user = null, $code = ExceptionManager::LOGIN_USER_MUST_UNLOCK, Exception $previous = null) {
        $this->user = $user;
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

    /**
     * @return null|User
     */
    public function getUser()
    {
        return $this->user;
    }
}
