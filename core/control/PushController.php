<?php defined("IN_GOMA") OR die();

/**
 * Push-Controller.
 *
 * @package     Goma\Push
 *
 * @license     GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author      Goma-Team
 *
 * @version     2.0.10
 */
class PushController extends Controller {
	/**
	 * pusher
	*/
	static $pusher;
	
	/**
	 * url handlers
	*/
	static $url_handlers = array(
		"auth" => "auth"
	);

	protected static $key;
	private static $hasBeenInited;

	/**
	 * inits the push-controller
	 * @param string $key
	 * @param string $secret
	 * @param string $app_id
	 */
	static function initPush($key, $secret, $app_id) {
		self::$key = $key;
		self::$pusher = new Pusher($key, $secret, $app_id);
		if(Core::globalSession()->get("pushActive") && !self::$hasBeenInited) {
			self::initJS();
		}
	}

	static function enablePush() {
		if(!self::$hasBeenInited) {
			self::initJS();
		}

		GlobalSessionManager::globalSession()->set("pushActive", true);
	}

	static function disablePush() {
		GlobalSessionManager::globalSession()->set("pushActive", false);
	}

	protected static function initJS() {
		self::$hasBeenInited = true;
		Resources::addData("goma.Pusher.init('" . self::$key . "');var uniqueID = " . var_export(self::uniqueUserSessionID(), true) . ";");

		Resources::add("notifications.css", "css");
		gloader::load("pusher");
		gloader::load("notifications");
	}

	/**
	 * triggers event
	 *
	 * @name trigger
	 * @access public
	 * @return bool
	 */
	static function trigger($event, $data) {
		if(isset(self::$pusher)) {
			return self::$pusher->trigger("presence-goma", $event, $data);
		} else {
			return false;
		}
	}
	
	/**
	 * triggers a event to the currently logged-in user.
	*/
	static function triggerToUser($event, $data) {
		if(isset(self::$pusher)) {
			return self::$pusher->trigger("private-" . self::uniqueUserSessionID(), $event, $data);
		} else {
			return false;
		}
	}

    /**
	 * make auth
     *
	 * @return string
	 */
	public function auth() {
		if(isset($this->request->post_params['channel_name']) && preg_match('/^presence\-/', $this->request->post_params['channel_name']) && isset(Member::$loggedIn)) {
			if(self::$pusher && isset($this->request->post_params['socket_id'])) {
				echo self::$pusher->presence_auth($this->request->post_params['channel_name'], $this->request->post_params['socket_id'], member::$loggedIn->id, member::$loggedIn->toArray());
				exit;
			}
		} else if(isset($this->request->post_params['channel_name']) && preg_match('/^private\-/', $this->request->post_params['channel_name'])) {
			if(self::$pusher && isset($this->request->post_params['socket_id']) && $this->request->post_params["channel_name"] == "private-" . self::uniqueUserSessionID()) {
				echo self::$pusher->socket_auth($this->request->post_params['channel_name'], $this->request->post_params['socket_id']);
				exit;
			}
		}
		
		header('', true, 403);
		return "Forbidden";
	}

    /**
     * unique identifier of this user.
     */
    public static function uniqueUserSessionID()
    {
        if (GlobalSessionManager::globalSession()->hasKey("uniqueID")) {
            return GlobalSessionManager::globalSession()->get("uniqueID");
        } else {
            if (Member::$loggedIn) {
                GlobalSessionManager::globalSession()->set("uniqueID", Member::$loggedIn->uniqueID());
            } else {
                GlobalSessionManager::globalSession()->set("uniqueID", md5(randomString(20)));
            }
            return GlobalSessionManager::globalSession()->get("uniqueID");
        }
    }
}
