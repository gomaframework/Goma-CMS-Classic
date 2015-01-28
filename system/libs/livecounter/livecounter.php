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
 * session-timeout for goma-cookie. this also lives after browser has closed.
*/
define("SESSION_TIMEOUT", 24*3600);

/**
 * This class handles everything about statistics.
 *
 * @package     Goma\Statistics
 *
 * @license     GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author      Goma-Team
 *
 * @version     2.2.10
 */
class livecounter extends DataObject
{
	/**
	 * disable history for this DataObject, because it would be a big lag of performance
	*/
	static $history = false;
	
	/**
	 * database-fields
	 *
	 *@name db
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
	 *
	 *@name table
	*/
	static $table = "statistics";
	
	/**
	 * indexes
	 *
	 *@name index
	*/
	static $index = array(
		"autorid" 		=> false,
		"editorid"		=> false,
        "phpsessid"		=> "INDEX"
	);
	
	/**
	 * a regexp to use to intentify browsers, which support cookies
	 *
	 *@name cookie_support
	 *@access public
	*/
	public static $cookie_support = "(firefox|msie|AppleWebKit|opera|khtml|icab|irdier|teleca|webfront|iemobile|playstation)";
	
	/**
	 * a regexp to use to intentify browsers, which support cookies
	 *
	 *@name no_cookie_support
	 *@access public
	*/
	public static $no_cookie_support = "(hotbar|Mozilla\/1.22)";
	
	/**
	 * bot-list
	*/
	public static $bot_list = "(googlebot|truebot|msnbot|CareerBot|nagios|SISTRIX|Coda|SeznamBot|AdvBot|crawl|MirrorDetector|AhrefsBot|MJ12bot|lb-spider|exabot|bingbot|yahoo|baiduspider|Ezooms|facebookexternalhit|360spider|80legs\.com|UptimeRobot|YandexBot|unknown|python\-urllib)";
	
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
	 * allow writing
	*/
	public function canWrite($data = null) {
		return true;
	}
	/**
	 * allow insert
	*/
	public function canInsert($data = null) {
		return true;
	}
	
	public static function run() {
		
		if(self::$alreadyRun) {
			return true;
		}
		
		self::$alreadyRun = true;
		
		// set correct host, avoid problems with localhost
		$host = self::getHost();
	
		// user identifier
		$user_identifier = self::getUserIdentifier();
		
		self::$userCounted = isset($_SESSION["user_counted"]) ? $_SESSION["user_counted"] : null;

		$_SESSION["user_counted"] = TIME;
		// just rewirte cookie
		self::setGomaCookies($user_identifier, $host);
				
		//self::onBeforeShutdownUsingLife();
		register_shutdown_function(array("livecounter", "onBeforeShutdownUsingLife"));
		
		self::checkForMigrationScript();
	}

	public static function getUserAgent() {
		return $_SERVER['HTTP_USER_AGENT'] ? $_SERVER['HTTP_USER_AGENT'] : "unknown";
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

	public static function getUserIdentifier() {

		$userAgent = self::getUserAgent();

		// user identifier
		if((!isset($_COOKIE['goma_sessid']) && (!preg_match("/" . self::$cookie_support . "/i", $userAgent) || preg_match("/" . self::$no_cookie_support . "/i", $userAgent)  || preg_match("/" . self::$bot_list . "/i", $userAgent))) || $userAgent == "" || $_SERVER['HTTP_USER_AGENT'] == "-") {
			$user_identifier = md5($userAgent . $_SERVER["REMOTE_ADDR"]);
		} else if(isset($_COOKIE['goma_sessid'])) {
			$user_identifier = $_COOKIE['goma_sessid'];
		} else {
			$user_identifier = session_id();
		}

		return $user_identifier;
	}

	public static function getHost() {
		// set correct host, avoid problems with localhost
		$host = $_SERVER["HTTP_HOST"];
		if(!preg_match('/^[0-9]+/', $host) && $host != "localhost" && strpos($host, ".") !== false)
			$host = "." . $host;

		return $host;
	}

	public static function checkForAttacks() {
		/**
		 * for users without enabled cookies, this works!
		*/
		if(	!isset(self::$userCounted) && 
			!isset($_COOKIE["goma_sessid"]) && 
			!isset($_COOKIE["goma_lifeid"]) && 
			DataObject::count("livecounter_live", array("ip" => md5($_SERVER["REMOTE_ADDR"]), "browser" => self::getUserAgent(), "last_modified" => array(">", NOW - 60 * 60 * 1))) > 10) {


			// this could be a ddos-attack or hacking-attack, we should notify the system administrator
			Security::registerAttacker($_SERVER["REMOTE_ADDR"], self::getUserAgent());
			$user_identifier = $_SERVER["REMOTE_ADDR"];
			AddContent::addNotice("Please activate Cookies in your Browser.");
		}
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
		session_write_close();
		
		if(function_exists("fastcgi_finish_request")) {
			fastcgi_finish_request();
		}

		$start = microtime(true);

		$userAgent = self::getUserAgent();
		
		if(preg_match('/favicon\.ico/', $_SERVER["REQUEST_URI"]) || substr($_SERVER["REQUEST_URI"], 0, strlen(ROOT_PATH . "null")) == ROOT_PATH . "null") {
			return false;
		}

		$host = self::getHost();

		$user_identifier = self::getUserIdentifier();

		self::checkForAttacks();

		/**
		 * there's a mode that live-counter updates record by exact date, it's better, because the database can better use it's index.
		*/
		if(isset(self::$userCounted)) {
			$data = DataObject::get_one("livecounter_live", array("phpsessid" => $user_identifier, "last_modified" => self::$userCounted));
			if($data && date("d", $data->created) == date("d", NOW)) {
				DataObject::update("livecounter_live", array("hitcount" => $data->hitcount + 1), array("id" => $data->versionid));

				return true;
			} else if($data) {

				self::generateLiveCounterSession($userAgent, $user_identifier, self::userId(), 1);
				
				return;
			}
		}

		// now we are in normal not high-performance-optimized mode.
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
				if($data && date("d", $data->created) == date("d", NOW)) {
					$data->phpsessid = $user_identifier;
					$data->hitcount++;
					$data->write(false, true);
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
		if($data && date("d", $data->created) == date("d", NOW)) {
			DataObject::update("livecounter_live", array("user" => self::userId(), "hitcount" => $data->hitcount + 1), array("id" => $data->versionid));
		} else {
			if($data) {
				$lt = $data->longtermid;
				DataObject::update("livecounter", array("hitcount" => $data->hitcount, "phpsessid" => $data->phpsessid, "last_modified" => $data->last_modified), array("recordid" => $lt));
				$data->remove(true);
			}

			$recurring = (isset($_COOKIE["goma_lifeid"]) && DataObject::count("livecounter", array("phpsessid" => $_COOKIE["goma_lifeid"])) > 0);
			self::generateLiveCounterSession($userAgent, $user_identifier, self::userId(), $recurring);
		}

		
		
		/*$end = microtime(true);
		$diff = $end - $start;
		logging("time session: " . $diff . "/" . $user_identifier);*/

	}

	public static function setGomaCookies($user_identifier, $host) {
		setCookie('goma_sessid',$user_identifier, TIME + SESSION_TIMEOUT, '/', $host, false, true);
		setCookie('goma_lifeid',$user_identifier, TIME + 365 * 24 * 60 * 60, '/', $host);
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
		$dataLive->write(true, true);

		return $dataLive;
	}
		
	/**
	 * checks if a user is online by id
	 *@name checkUserOnline
	 *@param int - userid
	 *@access public
	*/
	public function checkUserOnline($userid)
	{
			
			$last = TIME - 300;
			$c = DataObject::count("livecounter", array("last_modified" => array(">", $last), "user" => $userid));
			return ($c > 0) ? true : false;
	}
	
	/**
	 * counts how much users are online
	 *@name countMembersOnline
	 *@access public
	*/
	public static function countMembersOnline()
	{
			$last = TIME - 300;
			return DataObject::count("livecounter", array('last_modified > '.$last.' AND user != "" AND isbot = 0'));
	}
	
	/**
	 * counts how much users AND guests online
	 *@name countUsersOnline
	 *@access public
	*/
	public function countUsersOnline()
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
	 *@name countUsersByLast
	 *@access public
	 *@param timestamp
	*/
	public function countUsersByLast($last)
	{

			return DataObject::count("livecounter", array("last_modified" => array(">", $last), "isbot" => 0));
	}
	
	/**
	 * counts user since and before..
	*/
	public function countUsersByLastFirst($last, $first)
	{

			return DataObject::count("livecounter", ' last_modified > "'.convert::raw2sql($last).'" AND last_modified < "'.convert::raw2sql($first).'" AND isbot = 0');
	}
	
	/**
	 * gets stats for the last x days
	 *
	 *@name statisticsByDay
	 *@access public
	 *@param numeric - number of periods
	 *@param numeric - length of a period in days
	 *@param start-period
	*/
	public static function statisticsByDay($showcount = 10, $days = 1, $page = 1) {
		if(!isset($page) || !preg_match('/^[0-9]+$/', $page) || $page < 1)
			$page = 1;
			
		$interval = $days * 24 * 60 * 60;
		$day = mktime(0, 0, 0, date("n", NOW), date("j", NOW), date("Y", NOW));
		
		$page--;
		$day = $day - ($interval * $page * $showcount);
		
		$max = 0;
		$data = array();
		for($i = 0; $i < $showcount; $i++) {
			
			// gets last day
			$last =$day + $interval;
			$data[$day] = DataObject::count("livecounter", "last_modified > ".$day." AND last_modified < ". $last . " AND isbot = 0");
			if($max < $data[$day]) {
				$max = $data[$day];
			}
			$day = $day - $interval;
		}
		ksort($data);
		$dataobject = new DataSet();
		foreach($data as $timestamp => $count) {
			$dataobject->push(array(
				"timestamp"	=> $timestamp,
				"count"		=> $count,
				"max"		=> $max,
				"percent"	=> ($max == 0) ? 0 : round($count / $max * 100),
				"day"		=> true
			));
		}
		
		return $dataobject;
	}
	
	/**
	 * gets stats for the last x month
	 *
	 *@name statisticsByMonth
	 *@access public
	 *@param numeric - number of periods
	 *@param numeric - length of a period in month
	*/
	public static function statisticsByMonth($showcount = 10, $page = 1) {
		if(!isset($page) || !preg_match('/^[0-9]+$/', $page) || $page < 1)
			$page = 1;
		
		$page--;
		
		// get last month
		$month = date("n", NOW);
		$year = date("Y", NOW);
		
		for($i = 0; $i < $page * $showcount; $i++) {
			if($month == 1) {
				$month = 12;
				$year--;
			} else {
				$month--;
			}
		}
		
		$start = mktime(0, 0, 0, $month, 1, $year); // get 1st of last month 00:00:00
		$endm = date("n", NOW);
		$endy = date("Y", NOW);
		if($endm == 12) {
			$endm = 1;
			$endy++;
		} else {
			$endm++;
		}
		
		for($i = 0; $i < $page * $showcount; $i++) {
			if($endm == 1) {
				$endm = 12;
				$endy--;
			} else {
				$endm--;
			}
		}
		
		$end = mktime(0, 0, 0, $endm, 1, $endy);
		$max = 0;
		$data = array();
		for($i = 0; $i < $showcount; $i++) {
			
			
			$data[$start] = DataObject::count("livecounter", "last_modified > ".$start." AND last_modified < ". $end . " AND isbot = 0");
			if($max < $data[$start]) {
				$max = $data[$start];
			}
			// recalculate the new start and end
			$end = $start;
			if($month == 1) {
				$month = 12;
				$year--;
			} else {
				$month--;
			}
			$start = mktime(0, 0, 0, $month, 1, $year); // get 1st of the month before month 00:00:00
		}
		unset($start, $end, $month, $year);
		ksort($data);
		
		$dataobject = new DataSet();
		foreach($data as $timestamp => $count) {
			$dataobject->push(array(
				"timestamp"	=> $timestamp,
				"count"		=> $count,
				"max"		=> $max,
				"percent"	=> ($max == 0) ? 0 : round($count / $max * 100),
				"month"		=> true
			));
		}
		
		return $dataobject;
	}
	
	/**
	 * gets data for statistics-graph.
	 *
	 * @access public
	 *
	 * @param int $start timestamp to start
	 * @param int $end timestamp to end
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
			$hitCount = -1;
			
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

		$cacher = new Cacher("cron_for_migratev2");
		$cacher->write("", 3600);

		$start = microtime(true);

		$migrateTimeout = NOW - 60 * 60;

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

		$deleteTimeout = NOW - SESSION_TIMEOUT;
		// remove old
		$sqlDeleteData = "DELETE FROM ".DB_PREFIX ."statistics_live WHERE last_modified < ".$deleteTimeout."";
			
		SQL::Query($sqlDeleteData);
		
		// TODO: Delete State data
		$e = microtime(true);
		$timeAfterDelete = $e - $start;
		logging("migration: delete done after " .  $timeAfterDelete . " seconds.");
		logging("migration done.");

		echo "ok";
		exit;
	}
}

class liveCounter_live extends DataObject {
	/**
	 * disable history for this DataObject, because it would be a big lag of performance
	*/
	static $history = false;

	/**
	 * database-fields
	 *
	 *@name db
	*/
	static $db = array(
		'phpsessid' 	=> 'varchar(800)', 
		"browser"		=> "varchar(200)",
		"ip"			=> "varchar(200)",
		"hitcount"		=> "int(10)",
	);
	
	/**
	 * has-one-relationship to statistics
	*/	
	static $has_one = array(
		"longterm"	=> "livecounter"
	);

	/**
	 * the name of the table isn't livecounter, it's statistics
	 *
	 *@name table
	*/
	static $table = "statistics_live";
	
	/**
	 * indexes
	 *
	 *@name index
	*/
	static $index = array(
		"recordid" 	    => false,
		"autorid" 		=> false,
		"editorid"		=> false,
        "phpsessid"		=> "INDEX"
	);
}

class livecounterController extends Controller {
	/**
	 * allowed actions.
	*/
	public $allowed_actions = array(
		"migrateStats"
	);

	public function migrateStats() {
		if(!Core::is_ajax()) {
			exit;
		}

		session_write_close();
		ignore_user_abort(true);

		livecounter::migrateStats();
	}
}

class StatController extends Controller {
	/**
	 * url-handlers
	 *
	 *@name url_handlers
	*/
	public $url_handlers = array(
		"lastWeek/\$page"		=> "lastWeek",
		"lastMonth/\$page"		=> "lastMonth",
		"lastYear/\$page"		=> "lastYear",
		"yesterday/\$page"		=> "yesterday",
		"\$start!/\$end/\$max"	=> "handleStats"
	);
	
	/**
	 * allow actions
	*/
	public $allowed_actions = array(
		"handleStats"	=> "ADMIN",
		"lastMonth"		=> "ADMIN",
		"lastWeek"		=> "ADMIN",
		"lastYear"		=> "ADMIN",
		"yesterday"		=> "ADMIN"
	);
	
	/**
	 * handles stats.
	*/
	public function handleStats() {
		$start = $this->getParam("start");
		$end = $this->getParam("end") ? $this->getParam("end") : $start + (60 * 60 * 24 * 7);
		$max = $this->getParam("max") ? $this->getParam("max") : 32;
		
		$data = LiveCounter::statisticsData($start, $end, $max);
		
		HTTPResponse::setHeader("content-type", "text/x-json");
		HTTPResponse::sendHeader();
		echo json_encode($data);
		exit;
	}
	
	/**
	 * handles stats for last week
	*/
	public function lastMonth() {
		$page = $this->getParam("page") ? $this->getParam("page") : 1;
		$showcount = 1;
		
		$last30Days = mktime(0, 0, 0, date("n"), date("d"), date("Y")) - 1 - (60 * 60 * 24 * 30) * $page;
		// get last month
		$month = date("n", $last30Days);
		$year = date("Y", $last30Days);
		$day = date("d", $last30Days);
		
		$start = mktime(0, 0, 0, $month, $day, $year); // get 1st of last month 00:00:00
		
		$endTime = $start + (60 * 60 * 24 * 30);
		$end = mktime(23, 59, 59, date("m", $endTime), date("d", $endTime), date("Y", $endTime));
		$max = 30;
		
		$data = LiveCounter::statisticsData($start, $end, $max);
		
		$data["timeFormat"] = "%d.%m";
		$data["timePositionMiddle"] = false;
		
		HTTPResponse::setHeader("content-type", "text/x-json");
		HTTPResponse::sendHeader();
		echo json_encode($data);
		exit;
	}
	
	/**
	 * last week-data.
	*/
	public function lastWeek() {
		$page = $this->getParam("page") ? $this->getParam("page") : 1;
		$showcount = 1;
		
		$last7Days = mktime(0, 0, 0, date("n"), date("d"), date("Y")) - 1 - (60 * 60 * 24 * 7) * $page;
		// get last month
		$month = date("n", $last7Days);
		$year = date("Y", $last7Days);
		$day = date("d", $last7Days);
		
		$start = mktime(0, 0, 0, $month, $day, $year); // get 1st of last month 00:00:00
		
		$endTime = $start + (60 * 60 * 24 * 7);
		$end = mktime(23, 59, 59, date("m", $endTime), date("d", $endTime), date("Y", $endTime));
		$max = 7;
		
		$data = LiveCounter::statisticsData($start, $end, $max);
		
		$data["timeFormat"] = "%d.%m";
		$data["timePositionMiddle"] = false;
		$data["minTickSize"] = array(24, "hour");
		
		HTTPResponse::setHeader("content-type", "text/x-json");
		HTTPResponse::sendHeader();
		echo json_encode($data);
		exit;
	}
	
	/**
	 * last year-data.
	*/
	public function lastYear() {
		$page = $this->getParam("page") ? $this->getParam("page") : 1;
		$showcount = 1;
		
		$lastYear = NOW - (60 * 60 * 24 * 365) * $page;
		// get last month
		$month = date("n", $lastYear);
		$year = date("Y", $lastYear);
		$day = date("d", $lastYear);
		
		$start = mktime(0, 0, 0, $month, $day, $year); // get 1st of last month 00:00:00
		
		$endTime = $start + (60 * 60 * 24 * 365);
		$end = mktime(23, 59, 59, date("m", $endTime), date("d", $endTime), date("Y", $endTime));
		$max = 36;
		
		$data = LiveCounter::statisticsData($start, $end, $max);
		
		$data["timeFormat"] = "%d.%m";
		$data["timePositionMiddle"] = false;
		
		HTTPResponse::setHeader("content-type", "text/x-json");
		HTTPResponse::sendHeader();
		echo json_encode($data);
		exit;
	}
	
	/**
	 * last day-data.
	*/
	public function yesterday() {
		$page = $this->getParam("page") ? $this->getParam("page") : 1;
		$showcount = 1;
		
		$yesterday = NOW - (60 * 60 * 24) * $page;
		// get last month
		$month = date("n", $yesterday);
		$year = date("Y", $yesterday);
		$day = date("d", $yesterday);
		
		$start = mktime(0, 0, 0, $month, $day, $year); // get 1st of last month 00:00:00
		
		$endTime = $start + (60 * 60 * 24);
		$end = mktime(date("H", $endTime), 0, 0, date("m", $endTime), date("d", $endTime), date("Y", $endTime));
		$max = 24;
		
		$data = LiveCounter::statisticsData($start, $end, $max);
		
		$data["timeFormat"] = "%H:%M";
		
		$data["title"] = goma_date(DATE_FORMAT_DATE . " (l)", $start);
		
		HTTPResponse::setHeader("content-type", "text/x-json");
		HTTPResponse::sendHeader();
		echo json_encode($data);
		exit;
	}
}
