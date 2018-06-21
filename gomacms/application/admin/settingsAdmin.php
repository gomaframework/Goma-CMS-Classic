<?php
use Goma\CMS\admin\SettingsAdminCategories;

defined("IN_GOMA") OR die();

/**
 * Settings.
 *
 * @author Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @package Goma-CMS/Admin
 * @version 1.0.5
 */
class settingsAdmin extends adminItem
{
	// config
    static $text = '{$_lang_settings}';

    static $sort = 980;

	static $rights = "SETTINGS_ADMIN";

	public $model = "newsettings";

	public $template = "admin/settings.html";

	static $icon = "templates/images/settings.png";

	static $less_vars = "tint-blue.less";

	/**
	 * history-url
	 */
	public function historyURL() {
		return "admin/history/newsettings";
	}

	/**
	 * @param Request $request
	 * @param bool $subController
	 * @return false|mixed|null|string
	 * @throws Exception
	 */
	public function handleRequest($request, $subController = false) {
		$this->Init($request);

		$controller = new SettingsAdminCategories();
		return $controller->handleRequest($request, true);
	}

	/**
	 * upgrades data regarding safe-mode.
	 */
	public static function upgradeSafeMode() {
		GlobalSessionManager::globalSession()->stopSession();
		FileSystem::applySafeMode(null, null, true);
	}

	/**
	 * returns an array of the wiki-article and youtube-video for this controller
	 *
	 * @name helpArticle
	 * @access public
	 * @return array
	 */
	public function helpArticle() {
		return array("wiki" => "Einstellungen");
	}
}
