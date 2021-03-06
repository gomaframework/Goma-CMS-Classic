<?php defined('IN_GOMA') OR die();

/**
 * Wrapper-Class to reflect login-process.
 *
 * @package		Goma\Security\Users
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 *
 * @version		1.0
 */
class AuthenticationService extends gObject {

    /**
     * limit of time when session expires.
     */
    public static $expirationLimit = 604800;

    /**
     * @var null|AuthenticationService
     */
    private static $shared;

    /**
     * @return AuthenticationService
     */
    public static function sharedInstance() {
        if(!isset(self::$shared)) {
            self::$shared = new AuthenticationService();
        }

        return self::$shared;
    }

    /**
     * gets auth-record for current authentification.
     *
     * @param string $sessionid
     * @return UserAuthentication
     * @throws MySQLException
     */
    public static function getAuthRecord($sessionid) {
        if(PROFILE) Profiler::mark("AuthService getAuthRecord");

        $record = DataObject::get_one("UserAuthentication", array(
            "token" => $sessionid
        ));

        if($record && $record->last_modified < time() - self::$expirationLimit) {
            $record->remove(true);

            if(PROFILE) Profiler::unmark("AuthService getAuthRecord");
            return null;
        }

        if(PROFILE) Profiler::unmark("AuthService getAuthRecord");

        return $record;
    }

    /**
     * returns User-Object by given Authentification-id.
     *
     * @param int $id
     * @param string|null $sessionId
     * @return User
     */
    public static function getUserObject($id, $sessionId = null) {
        if($data = DataObject::get_one("user", array("id" => $id))) {
            $currsess = isset($sessionId) ? $sessionId : GlobalSessionManager::globalSession()->getId();

            if($data->authentications(
                    array(
                        "token" => $currsess,
                        "last_modified" => array(">", time() - self::$expirationLimit)
                    )
                )->count() > 0) {
                return $data;
            }
        }

        return null;
    }

    /**
     * regenerates token.
     *
     * @param ISessionManager|null $session
     */
    public static function regenerateToken($session = null) {
        if(!isset($session)) {
            $session = GlobalSessionManager::globalSession();
        }
        $oldToken = $session->getId();

        $session->regenerateId();

        /** @var UserAuthentication $auth */
        if($auth = DataObject::get_one("UserAuthentication", array(
            "token" => $oldToken,
            "last_modified" => array(">", time() - self::$expirationLimit)
        ))) {
            $auth->token = $session->getId();
            Core::repository()->write($auth, true);
        }

        return $session->getId();
    }

    /**
     * forces a logout
     * @param string|null $sessionId if to logout specific id
     * @throws MySQLException
     * @throws SQLException
     */
    public function doLogout($sessionId = null) {
        if(!isset($sessionId)) {
            $sessionId = GlobalSessionManager::globalSession()->getId();
        }

        $data = DataObject::get_one("UserAuthentication", array("token" => $sessionId));
        /** @var UserAuthentication $data */
        if($data) {
            $data->user()->performLogout();
            $data->remove(true);
        }
    }

    /**
     * performs a login and throws an exception if login cannot be validates.
     *
     * @param string $user
     * @param string $pwd
     * @param string|null $sessionId
     * @param int $allowStatus which status should be additionally allowed?
     * @return User
     * @throws PermissionException
     * @throws SQLException
     */
    public function checkLogin($user, $pwd, $sessionId = null, $allowStatus = -1) {
        if($userObject = $this->resolveUser($user, $pwd)) {
            if ($userObject->status == 1 || $userObject->status == $allowStatus) {

                DefaultPermission::forceGroupType($userObject);

                $authentication = new UserAuthentication(array(
                    "token"  => isset($sessionId) ? $sessionId : GlobalSessionManager::globalSession()->getId(),
                    "userid" => $userObject->id
                ));
                $authentication->writeToDB(true, true);

                $userObject->performLogin();

                return $userObject;
            } else if ($userObject->status == 0) {
                throw new LoginUserMustUnlockException("User must validate email-address.", $userObject);
            } else {
                throw new LoginUserLockedException("User was locked by administrator.", $userObject);
            }
        }

        throw new LoginInvalidException("Login was not found.");
    }

    /**
     * @param $user
     * @param $pwd
     * @return User|null
     */
    protected function resolveUser($user, $pwd) {
        DefaultPermission::checkDefaults();

        $currentUserObject = null;

        $users = DataObject::get("user", array("nickname" => trim(strtolower($user)), "OR", "email" => array("LIKE", $user)));

        /** @var User $userObject */
        if($users->count() > 0) {
            foreach ($users as $userObject) {
                // check password
                if (Hash::checkHashMatches($pwd, $userObject->fieldGet("password"))) {
                    $currentUserObject = $userObject;
                    break;
                }
            }
        }

        $this->callExtending("resolveUser", $currentUserObject, $user, $pwd);

        return $currentUserObject;
    }
}
