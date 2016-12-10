<?php defined('IN_GOMA') OR die();


/**
 * Wrapper-Class to reflect some data of the logged-in user.
 *
 * @package		Goma\Security\Users
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 *
 * @version		1.4
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
	 * nickname of the user logged in
	 */
	public static $nickname;

	/**
	 * this var reflects the status of the highest group in which the user is
	 *@var int
	 */
	public static $groupType = 0;

	/**
	 * set of groups of this user
	 */
	public static $groups = array();

	/**
	 * default-admin
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
			$user = $auth->user;

			if($user) {
				if ($user["timezone"]) {
					Core::setCMSVar("TIMEZONE", $user["timezone"]);
					date_default_timezone_set(Core::getCMSVar("TIMEZONE"));
				}

				self::$id = $user->id;
				self::$nickname = $user->nickname;

				self::$groups = DefaultPermission::forceGroups($user);

				self::$groupType = self::$groups->first()->type;

				// every group has at least the type 1, 0 is just for guests
				if (self::$groupType == 0) {
					self::$groupType = 1;
					self::$groups->first()->type = 1;
					self::$groups->first()->write(false, true, 2, false, false);
				}

				self::$loggedIn = $user;
				if (PROFILE) Profiler::unmark("member::Init");

				return true;
			}
		}

		if(PROFILE) Profiler::unmark("member::Init");
		return false;
	}

	/**
	 * returns the groupids of the groups of the user
	 *
	 * @return array
	 */
	public static function groupIDs() {
		if(is_array(self::$groups)) {
			return self::$groups;
		}
		return self::$groups->fieldToArray("id");
	}

	/**
	 * returns if the user is logged in
	 *
	 * @return bool
	 */
	public static function login() {
		return (self::$groupType > 0);
	}

	/**
	 * returns if the user is an admin
	 *
	 * @return bool
	 */
	public static function admin() {
		return (self::$groupType == 2);
	}

	/**
	 * checks if an user have the rights
	 *
	 *@param string|number $name if numeric: the rights from 1 - 10, if string: the advanced rights
	 *@return bool
	 */
	static function right($name)
	{
		return Permission::check($name);
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
		} catch(LoginInvalidException $e) {

			// credentials wrong
			logging("Login with wrong Username/Password with IP: ".$_SERVER["REMOTE_ADDR"].""); // just for security
			AddContent::addError(lang("wrong_login"));
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
		}

		return false;
	}

	/**
	 * require login
	 * @deprecated
	 * @param string|null $lang
	 * @return bool
	 */
	public static function require_login($lang = null) {
		if(!self::login()) {
			AddContent::addNotice(isset($lang) ? $lang : lang("require_login"));
			self::redirectToLogin();
		}
		return true;
	}

	public static function redirectToLogin() {
		HTTPResponse::redirect(ROOT_PATH . BASE_SCRIPT . "profile/login/?redirect=" . $_SERVER["REQUEST_URI"]);
		exit;
	}

	/**
	 * unique identifier of this user.
	 */
	public static function uniqueID() {
		if(GlobalSessionManager::globalSession()->hasKey("uniqueID")) {
			return GlobalSessionManager::globalSession()->get("uniqueID");
		} else {
			if(self::$loggedIn) {
				GlobalSessionManager::globalSession()->set("uniqueID", self::$loggedIn->uniqueID());
			} else {
				GlobalSessionManager::globalSession()->set("uniqueID", md5(randomString(20)));
			}
			return GlobalSessionManager::globalSession()->get("uniqueID");
		}
	}
}

class LoginInvalidException extends LogicException {
	/**
	 * constructor.
	 */
	public function __construct($m = "", $code = ExceptionManager::LOGIN_INVALID, Exception $previous = null) {
		parent::__construct($m, $code, $previous);
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

class LoginUserLockedException extends LogicException {

	protected $user;

	/**
	 * constructor.
	 * @param string $m
	 * @param User|null $user
	 * @param Exception|int $code
	 * @param Exception $previous
	 */
	public function __construct($m = "", $user = null, $code = ExceptionManager::LOGIN_USER_LOCKED, Exception $previous = null) {
		parent::__construct($m, $code, $previous);

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

class LoginUserMustUnlockException extends LogicException {

	protected $user;

	/**
	 * constructor.
	 * @param string $m
	 * @param User|null $user
	 * @param Exception|int $code
	 * @param Exception $previous
	 */
	public function __construct($m = "", $user = null, $code = ExceptionManager::LOGIN_USER_MUST_UNLOCK, Exception $previous = null) {
		$this->user = $user;
		parent::__construct($m, $code, $previous);
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