<?php
defined("IN_GOMA") OR die();

/**
 * a cron-job is one job executes from cron.
 *
 * @property int timeinms
 * @property bool finished
 * @property string log
 * @package Goma
 *
 * @author Goma-Team
 * @copyright 2016 Goma-Team
 * @license     GNU Lesser General Public License, version 3; see "LICENSE.txt"
 *
 * @version 1.0
 */
class CronJob extends DataObject {
    /**
     * @var array
     */
    static $db = array(
        "finished"  => "Switch",
        "timeinms"  => "int(20)",
        "log"       => "text"
    );

    /**
     * @var bool
     */
    static $search_fields = false;
}
