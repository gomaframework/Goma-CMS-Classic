<?php defined("IN_GOMA") OR die();

// load language for stats
loadlang('st');

/**
 * in this file are all classes that handle statistics:
 *
 * - livecounter: statistic table, which is used to long-term store the stat-data
 * - livecounter_live: statistic table for use in real-time and in high-performance-context
 * - statController: used to generate stats
 * - liveCounterController: used to call migrate from an ajax-context
 */

/**
 * This class handles everything about statistics and session management for login like goma_sessid.
 *
 * @package     Goma\Statistics
 *
 * @license     GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author      Goma-Team
 *
 * @version     2.2.13
 */
class livecounter extends DataObject
{
    /**
     * sessionid for user-counted.
     */
    const SESSION_USER_COUNTED = "user_counted";

    /**
     * disable history for this DataObject, because it would be a big lag of performance
     */
    static $history = false;

    /**
     * database-fields
     */
    static $db = array(
        'user' 			=> 'varchar(200)',
        'phpsessid' 	=> 'varchar(800)',
        "browser"		=> "varchar(200)",
        "referer"		=> "varchar(400)",
        "ip"			=> "varchar(200)",
        "isbot"			=> "int(1)",
        "hitcount"		=> "int(10)",
        "recurring"		=> "int(1)"
    );

    /**
     * the name of the table isn't livecounter, it's statistics
     */
    static $table = "statistics";

    /**
     * indexes
     */
    static $index = array(
        "autorid" 		=> false,
        "editorid"		=> false,
        "phpsessid"		=> "INDEX"
    );

    static $search_fields = false;

    /**
     * a regexp to use to intentify browsers, which support cookies
     */
    public static $cookie_support = "(firefox|msie|AppleWebKit|opera|khtml|icab|irdier|teleca|webfront|iemobile|playstation)";

    /**
     * a regexp to use to intentify browsers, which support cookies
     */
    public static $no_cookie_support = "(hotbar|Mozilla\/1.22)";

    /**
     * bot-list
     */
    public static $bot_list = "(googlebot|seoscanners|Qwantify|curl|wget|CloudFlare|truebot|msnbot|CareerBot|nagios|SISTRIX|Coda|SeznamBot|AdvBot|crawl|MirrorDetector|AhrefsBot|MJ12bot|lb-spider|exabot|bingbot|yahoo|baiduspider|Ezooms|facebookexternalhit|360spider|80legs\.com|UptimeRobot|YandexBot|unknown|python\-urllib|applebot|jobboerse\.com|DotBot)";

    /**
     * some bots use the referer.
     */
    public static $bot_referer_list = "(baidu|semalt|anticrawler\.org)";

    /**
     * counts how much users are online
     */
    static private $useronline = 0;

    /**
     * just run once per request
     */
    static public $alreadyRun = false;

    static $userCounted = null;

    /**
     * @var string|null
     */
    static $user_identifier = null;

    /**
     * @var bool
     */
    static $ssoCookie = false;

    /**
     * allow writing
     * @return bool
     */
    public function canWrite() {
        return true;
    }

    /**
     * allow insert
     * @return bool
     */
    public function canInsert() {
        return true;
    }

    public static function run() {
        if(self::$alreadyRun) {
            return true;
        }

        self::$alreadyRun = true;

        // set correct host, avoid problems with localhost
        $host = GlobalSessionManager::getCookieHost();

        // user identifier
        $user_identifier = self::getUserIdentifier();

        self::$userCounted = GlobalSessionManager::globalSession()->get(self::SESSION_USER_COUNTED);

        GlobalSessionManager::globalSession()->set(self::SESSION_USER_COUNTED, time());
        // just rewirte cookie
        self::setGomaCookies($user_identifier, $host);

        //self::onBeforeShutdownUsingLife();
        Core::addToHook("onBeforeShutdown", array("livecounter", "onBeforeShutdownUsingLife"));

        self::checkForMigrationScript();
    }

    public static function getUserAgent() {
        return isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] ? $_SERVER['HTTP_USER_AGENT'] : "unknown";
    }

    public static function checkForMigrationScript() {
        $userAgent = self::getUserAgent();

        if(!preg_match("/" . self::$no_cookie_support . "/i", $userAgent)  && !preg_match("/" . self::$bot_list . "/i", $userAgent)) {

            $cacher = new Cacher("cron_for_migratev2");
            if(!$cacher->checkValid()) {
                Resources::addJS("$(function(){ 
					setTimeout(function(){
						$.ajax({
							url: \"".BASE_URI . BASE_SCRIPT."system/livecounter/migrateStats".URLEND."\"
						});
					}, 300); 
				});");
                $cacher->write("", 10);
            }
        }
    }

    /**
     * @return null|string
     */
    public static function getUserIdentifier() {
        $userAgent = self::getUserAgent();

        // user identifier
        if((!isset($_COOKIE['goma_sessid']) &&
                (!preg_match("/" . self::$cookie_support . "/i", $userAgent) ||
                    preg_match("/" . self::$no_cookie_support . "/i", $userAgent)  ||
                    preg_match("/" . self::$bot_list . "/i", $userAgent))) ||
            $userAgent == "" || $_SERVER['HTTP_USER_AGENT'] == "-") {
            $user_identifier = isCommandLineInterface() ? "cli" : md5($userAgent . $_SERVER["REMOTE_ADDR"]);
        } else if(isset($_COOKIE['goma_sessid'])) {
            $user_identifier = $_COOKIE['goma_sessid'];
        } else if(isset(self::$user_identifier)) {
            $user_identifier = self::$user_identifier;
        } else {
            $user_identifier = session_id();
        }

        return $user_identifier;
    }

    public static function userId() {
        return member::$id;
    }

    public static function isBot($userAgent, $referer) {

        if(!isset($referer)) {
            $referer = "";
        }

        return preg_match("/" . self::$bot_list . "/i", $userAgent) || preg_match('/'.self::$bot_referer_list.'/i', $referer);
    }

    public static function onBeforeShutdownUsingLife() {
        if(function_exists("ignore_user_abort")) {
            ignore_user_abort(true);
        }

        GlobalSessionManager::globalSession()->stopSession();

        if(isCommandLineInterface()) return;

        if(function_exists("fastcgi_finish_request")) {
            fastcgi_finish_request();
        }

        $userAgent = self::getUserAgent();

        if(preg_match('/favicon\.ico/', $_SERVER["REQUEST_URI"]) || substr($_SERVER["REQUEST_URI"], 0, strlen(ROOT_PATH . "null")) == ROOT_PATH . "null" || URL == "null") {
            return false;
        }

        $user_identifier = self::getUserIdentifier();

        /**
         * there's a mode that live-counter updates record by exact date, it's better, because the database can better use it's index.
         */
        if(isset(self::$userCounted)) {
            $data = DataObject::get_one("livecounter_live", array("phpsessid" => $user_identifier, "last_modified" => self::$userCounted));
            if($data && date("d", $data->created) == date("d", time())) {
                DataObject::update("livecounter_live", array("hitcount" => $data->hitcount + 1), array("id" => $data->versionid));

                return true;
            } else if($data) {

                self::generateLiveCounterSession($userAgent, $user_identifier, self::userId(), 1);

                return;
            }
        }

        // time() we are in normal not high-performance-optimized mode.
        $timeout = TIME - SESSION_TIMEOUT;

        // check if a cookie exists, that means that the user was here in the last 16 hours.
        if(isset($_COOKIE['goma_sessid']))
        {
            $sessid = $_COOKIE['goma_sessid'];
            // if sessid is not the same the user has begun a new php-session, so we update our cookie
            if($user_identifier != $sessid)
            {
                $data = DataObject::get_one("livecounter_live", "phpsessid = '".convert::raw2sql($sessid)."' AND last_modified > ".convert::raw2sql($timeout)."");


                // check if we are on the same day or not.
                if($data && date("d", $data->created) == date("d", time())) {
                    $data->phpsessid = $user_identifier;
                    $data->hitcount++;
                    $data->writeToDB(false, true);
                } else if($data) {
                    // update longterm-entry and remove life entry
                    $lt = $data->longtermid;
                    DataObject::update("livecounter", array("hitcount" => $data->hitcount, "phpsessid" => $data->phpsessid, "last_modified" => $data->last_modified), array("recordid" => $lt));
                    $data->remove(true);

                    self::generateLiveCounterSession($userAgent, $user_identifier, self::userId(), 1);
                }

                // free memory
                unset($data);

                return true;
            }
        }

        /**
         * check for current sessid
         */
        $data = DataObject::get_one("livecounter_live", array("phpsessid" => $user_identifier, "last_modified" => array(">", $timeout)));
        if($data && date("d", $data->created) == date("d", time())) {
            DataObject::update(livecounter_live::class, array("user" => self::userId(), "hitcount" => $data->hitcount + 1), array("id" => $data->versionid));
        } else {
            if($data) {
                $lt = $data->longtermid;
                DataObject::update("livecounter", array("hitcount" => $data->hitcount, "phpsessid" => $data->phpsessid, "last_modified" => $data->last_modified), array("recordid" => $lt));
                $data->remove(true);
            }

            $recurring = (isset($_COOKIE["goma_lifeid"]) && DataObject::count("livecounter", array("phpsessid" => $_COOKIE["goma_lifeid"])) > 0);
            self::generateLiveCounterSession($userAgent, $user_identifier, self::userId(), $recurring);
        }

    }

    /**
     * sets goma cookies.
     * @param string $user_identifier
     * @param string $host
     */
    public static function setGomaCookies($user_identifier, $host) {
        if(!isCommandLineInterface()) {
            if(self::$ssoCookie && ($ssoHost = self::getSSOHost($host)))  {
                setCookie('goma_sessid', $user_identifier, TIME + SESSION_TIMEOUT, '/', $ssoHost, false, true);
                setCookie('goma_lifeid', $user_identifier, TIME + 365 * 24 * 60 * 60, '/', $ssoHost);
            } else {
                setCookie('goma_sessid', $user_identifier, TIME + SESSION_TIMEOUT, '/', $host, false, true);
                setCookie('goma_lifeid', $user_identifier, TIME + 365 * 24 * 60 * 60, '/', $host);
            }
        }
    }

    /**
     * @param string $host
     * @return null|string
     */
    protected static function getSSOHost($host) {
        $ssoHostEnd = substr($host, strrpos($host, ".") + 1);
        if(RegexpUtil::isNumber($ssoHostEnd)) {
            return null;
        }

        $ssoHostWithoutEnd = substr($host, 0, strrpos($host, "."));
        if(strrpos($ssoHostWithoutEnd, ".") !== false) {
            return substr($ssoHostWithoutEnd, strrpos($ssoHostWithoutEnd, ".")).".".$ssoHostEnd;
        } else {
            return "." . $ssoHostWithoutEnd . "." . $ssoHostEnd;
        }
    }

    public static function generateLiveCounterSession($userAgent, $user_identifier, $userid, $recurring) {

        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "";

        $data = new LiveCounter();
        $data->user = $userid;
        $data->phpsessid = $user_identifier;
        $data->browser = $userAgent;
        $data->referer = $referer;
        $data->ip = md5($_SERVER["REMOTE_ADDR"]);
        $data->isbot = self::isBot($userAgent, $referer);
        $data->hitcount = 1;
        $data->recurring = 1;

        $dataLive = new liveCounter_live();
        $dataLive->phpsessid = $user_identifier;
        $dataLive->ip = md5($_SERVER["REMOTE_ADDR"]);
        $dataLive->hitcount = 1;
        $dataLive->browser = $userAgent;
        $dataLive->longterm = $data;
        $dataLive->writeToDB(true, true);

        return $dataLive;
    }

    /**
     * counts how much users AND guests online
     * @return int
     */
    public static function countUsersOnline()
    {
        if(self::$useronline != 0)
        {
            return self::$useronline;
        }
        $last = time() - 60;
        $c = DataObject::count("livecounter",array("last_modified" => array(">", $last), "isbot" => 0));
        self::$useronline = $c;
        return $c;
    }

    /**
     * counts how much users where online since...
     * @param int $since
     * @return int
     */
    public static function countUsersByLast($since)
    {
        return DataObject::count("livecounter", array("last_modified" => array(">", $since), "isbot" => 0));
    }

    /**
     * counts user since and before..
     * @param int $from start timestamp
     * @param int $to stop timestamp
     * @return int
     */
    public static function countUsersByLastFirst($from, $to)
    {
        return DataObject::count("livecounter", ' last_modified > "'.convert::raw2sql($from).'" AND last_modified < "'.convert::raw2sql($to).'" AND isbot = 0');
    }

    /**
     * gets data for statistics-graph.
     *
     * @param int $start timestamp to start
     * @param int $end timestamp to end
     * @return array
     */
    static function statisticsData($start, $end, $maxPoints = 32) {

        $cacher = new Cacher("stat_data_" . $start . "_" . $end . "_" . $maxPoints);
        if($cacher->checkValid()) {
            return $cacher->getData();
        } else {

            // first calculate how many days we have
            $diff = $end - $start;
            if($diff < 0) {
                throw new LogicException('$start must be higher than $end');
            }

            $day = (24 * 60 * 60);

            $pointsPerDay = $maxPoints / ($diff / $day);
            if($pointsPerDay > 2) {
                $pointsPerDay = round($pointsPerDay);
                $timePerPoint = $day / $pointsPerDay;
            } else if(floor($pointsPerDay) == 1) {
                $pointsPerDay = 1;
                $timePerPoint = $day / $pointsPerDay;
            } else {
                $daysPerPoint = round(1 / $pointsPerDay);
                $pointsPerDay = 0;
                $timePerPoint = $day * $daysPerPoint;
            }

            $data = array();
            $current = $start;

            while($current <= $end) {
                $hitsQuery = new SelectQuery("statistics", "sum(hitcount) as hitcount", 'last_modified > ' . $current . ' AND last_modified < ' . ($current + $timePerPoint) . ' AND isbot = 0');
                if($hitsQuery->execute()) {
                    $record = $hitsQuery->fetch_assoc();
                    $hitCount = (int) $record["hitcount"];

                    array_push($data, array(
                        "start" 	=> $current,
                        "flotStart"	=> $current * 1000,
                        "end" 		=> $current + $timePerPoint,
                        "flotEnd"	=> ($current + $timePerPoint) * 1000,
                        "visitors"	=> DataObject::count("livecounter", 'last_modified > ' . $current . ' AND last_modified < ' . ($current + $timePerPoint) . ' AND isbot = 0'),
                        "hits"		=> $hitCount
                    ));
                }

                $current += $timePerPoint;
            }


            if($diff < 24 * 30 * 2 * 60 * 60) {
                $title = goma_date(DATE_FORMAT_DATE, $start) . " - " . goma_date(DATE_FORMAT_DATE, $end - 86400);
            } else {
                $title = goma_date("M Y", $start) . " - " . goma_date("M Y", $end);
            }

            $data = array("data" => $data, "title" => $title);
            $cacher->write($data, 300);
            return $data;
        }
    }

    public static function migrateStats() {
        if(function_exists("ignore_user_abort")) {
            ignore_user_abort(true);
        }

        $cacher = new Cacher("cron_for_migratev2");
        $cacher->write("", 3600);

        $start = microtime(true);

        $migrateTimeout = time() - 60 * 60;

        // migrate data from live table back to normal table
        $select = new SelectQuery("statistics_live", array("statistics_live.last_modified", "statistics_live.longtermid", "statistics_live.hitcount", "statistics_live.phpsessid"), array());
        $select->innerJoin("statistics", "statistics_live.longtermid = statistics.recordid", "statistics");
        $select->addFilter("statistics.last_modified != statistics_live.last_modified AND statistics_live.last_modified < ".$migrateTimeout."");

        if ($select->execute()) {
            while($data = $select->fetch_assoc()) {
                DataObject::update("livecounter", array("phpsessid" => $data["phpsessid"], "hitcount" => $data["hitcount"], "last_modified" => $data["last_modified"]), array("recordid" => $data["longtermid"]));
            }
        } else {
            throw new SQLException();
        }

        $e = microtime(true);
        $timeAfterCopy = $e - $start;
        logging("migration: copy done after " .  $timeAfterCopy . " seconds.");

        $deleteTimeout = time() - SESSION_TIMEOUT;
        // remove old
        $sqlDeleteData = "DELETE FROM ".DB_PREFIX ."statistics_live WHERE last_modified < ".$deleteTimeout;

        // DELETE t is correct
        $sqlDeleteStateData = "DELETE t FROM ".DB_PREFIX ."statistics_live_state t WHERE NOT EXISTS ( SELECT * FROM ".DB_PREFIX ."statistics_live l WHERE l.recordid = t.id)";

        SQL::Query($sqlDeleteData);
        SQL::Query($sqlDeleteStateData, true);

        $e = microtime(true);
        $timeAfterDelete = $e - $start;
        logging("migration: delete done after " .  $timeAfterDelete . " seconds.");
        logging("migration done.");

        echo "ok";
        exit;
    }
}
