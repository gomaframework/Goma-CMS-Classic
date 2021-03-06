<?php defined("IN_GOMA") OR die();
/**
  * SettingsController handles a simple local cache that has all settings in it.
  *
  *	@package 	goma cms
  *	@link 		http://goma-cms.org
  *	@license 	LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
  *	@author 	Goma-Team
  * @Version 	1.2.9
*/

Core::addToHook("loadedClassRegisterExtension", array(SettingsController::class, "setRegisterVars"));
Core::addCMSVarCallback(array(SettingsController::class, "get"));

class SettingsController extends gObject {
	const ID = "SettingsController";

	/**
	 * this is a cache of the dataobject of settings
	 *
	 * @name 	settingsCache
	 * @access 	public
	*/
	public static $settingsCache;

	/**
	 * gets the cache
	 *
	 * @name 	preInit
	 * @access 	public
	*/
	public static function PreInit() {
		if(PROFILE) Profiler::mark("settings");

		$cacher = new Cacher("settings");
		if($cacher->checkValid()) {
			self::$settingsCache = new newSettings($cacher->getData());
		} else {
			self::$settingsCache = DataObject::get("newsettings", array("id" => 1))->first();
			if(!self::$settingsCache) {
				self::$settingsCache = new Newsettings(array(

				));
				if(!file_exists("tpl/" . self::$settingsCache->stpl)) {
					$templates = TemplateInfo::get_available_templates(ClassInfo::$appENV["app"]["name"], ClassInfo::appVersion(), GOMA_VERSION . "-" . BUILD_VERSION);
					if(isset($templates[0])) {
						self::$settingsCache->stpl = $templates[0];
					}
				}
			}
			$cacher->write(self::$settingsCache->toArray(), 3600);
		}

		if(PROFILE) Profiler::unmark("settings");
	}

	/**
	 * gets a value for settings-key.
	 *
	 * @name   get
	 * @access public
	 * @param  string $name
	 * @return null
	 */
	public static function get($name)
	{
		if(!isset(self::$settingsCache)) {
			self::PreInit();
		}

		return self::$settingsCache->offsetExists($name) ? self::$settingsCache->$name : null;
	}
	
	/**
	 * sets register-vars only when RegisterClass is loaded.
	*/
	public static function setRegisterVars() {
		RegisterExtension::$enabled = settingsController::get("register_enabled");
		RegisterExtension::$validateMail = settingsController::get("register_email");
		RegisterExtension::$registerCode = settingsController::get("register");
	}
}
