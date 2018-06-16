<?php defined('IN_GOMA') OR die();


/**
 * Wrapper-Class to reflect some data of the logged-in user.
 *
 * @package		Goma\Security\Users
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class Member extends gObject {
	/**
	 * user-login const
	 */
	const USER_LOGIN = "g_userlogin";

	/**
	 * id of the current logged in user
	 */
	public static $id;

	/**
	 * this var reflects the status of the highest group in which the user is
	 */
	public static $groupType = 0;

	/**
	 * default-admin can be used to force a user account with admin permissions and these credentials.
     * for example:
     *
     * <code>
     * array(
     *      "nickname" 	=> "admin",
     *      "password"	=> "1234"
     * );
     * </code>
     *
	 */
	public static $default_admin;

	/**
	 * object of logged in user
	 *
	 * @var User
	 */
	public static $loggedIn;

	/**
	 * checks the login and writes the types
	 *
	 * @return 	boolean	true if logged in
	 */
	public static function Init() {
		if(PROFILE) Profiler::mark("member::Init");

		DefaultPermission::checkDefaults();

		if($auth = AuthenticationService::getAuthRecord(GlobalSessionManager::globalSession()->getId())) {
			if(self::InitUser($auth->user)) {
				if(PROFILE) Profiler::unmark("member::Init");
				return true;
			}
		}

        self::InitUser(null);

		if(PROFILE) Profiler::unmark("member::Init");
		return false;
	}

    /**
     * @param User|null $user
     * @return bool
     */
	public static function InitUser($user) {
		if($user) {
            if(!$user->id) {
                throw new InvalidArgumentException("Parameter \$user of Member::InitUser must be a written user or null.");
            }

			if ($user["timezone"]) {
				Core::setCMSVar("TIMEZONE", $user["timezone"]);
				date_default_timezone_set(Core::getCMSVar("TIMEZONE"));
			}

			self::$id = $user->id;

			self::$groupType = DefaultPermission::forceGroupType($user);

			self::$loggedIn = $user;

			return true;
		} else {
            self::$loggedIn = self::$id = null;
            self::$groupType = 0;
		}

		return false;
	}

	/**
	 * login an user with the params
	 * if the params are incorrect, it returns false.
	 *
	 * @param 	string $user
	 * @param 	string $pwd
	 * @return 	bool
	 */
	public static function doLogin($user, $pwd)
	{
		try {
			AuthenticationService::sharedInstance()->checkLogin($user, $pwd);

			return true;
		} catch(LoginUserLockedException $e) {

            // user is locked
            AddContent::addError(lang("login_locked"));
        } catch(LoginUserMustUnlockException $e) {
            // user must activate account
            $add = "";
            if(ClassInfo::exists("registerExtension")) {
                $add = ' <a href="profile/resendActivation/?email=' . urlencode($e->getUser()->email) . '">'.lang("register_resend_title").'</a>';
            }
            AddContent::addError(lang("login_not_unlocked") . $add);
        } catch(LoginInvalidException $e) {

			// credentials wrong
			logging("Login with wrong Username/Password with IP: ".$_SERVER["REMOTE_ADDR"].""); // just for security
			AddContent::addError(lang("wrong_login"));
		}

		return false;
	}
}
