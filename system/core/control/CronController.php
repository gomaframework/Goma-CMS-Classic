<?php defined("IN_GOMA") OR die();

/**
 * Basic Class some system behaviour.
 *
 * @package     Goma\Model
 *
 * @license     GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author      Goma-Team
 *
 * @version    1.0
 */
class CronController extends RequestHandler {
    /**
     * cron.
     */
    public function handleRequest() {
        self::handleCron();
    }

    /**
     *
     */
    public static function handleCron() {
        ini_set('max_execution_time', -1);

        $cronJob = new CronJob();
        $cronJob->log = "Started Cronjob\n\n";

        $count = DataObject::get(CronJob::class, array(
            "created" => array(">", time() - 300)
        ))->count();
        if($count > 0) {
            $cronJob->log .= "Found $count running jobs.\n";
        }

        $cronJob->writeToDB(false, true);

        Core::callHook("cron", $cronJob);

        $cronJob->finished = true;
        $endTime = microtime(true);
        $cronJob->timeinms = ($endTime - EXEC_START_TIME) * 1000;
        $cronJob->log .= "\nFinished Cronjob in ".$cronJob->timeinms."ms\n";
        $cronJob->writeToDB(false, true);
    }
}
