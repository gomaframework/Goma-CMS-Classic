<?php defined("IN_GOMA") OR die();

/**
 * Stat Migration.
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @package		Goma\Framework
 * @version		1.1.1
 */
class livecounterController extends Controller {
    /**
     * allowed actions.
     */
    static $url_handlers = array(
        "migrateStats" => "migrateStats"
    );

    public function migrateStats() {
        if(!$this->request->is_ajax()) {
            exit;
        }

        GlobalSessionManager::globalSession()->stopSession();
        ignore_user_abort(true);

        livecounter::migrateStats();
    }
}
