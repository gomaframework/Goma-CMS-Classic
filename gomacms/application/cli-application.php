<?php
/**
 *@package goma cms
 *@link http://goma-cms.org
 *@license: LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 *@author Goma-Team
 * last modified: 11.04.2013
 * $Version 1.1.7
 */

defined('IN_GOMA') OR die('<!-- restricted access -->'); // silence is golden ;)

/**
 * here you can define the seperator for the dynamic title in the <title></title>-Tag
 */
define('TITLE_SEPERATOR',' - ');

SQL::Init();

loadFramework();

require("loadSettings.php");

if(PROFILE) Profiler::unmark("settings");

echo "Goma Framework " . GOMA_VERSION . " - " . BUILD_VERSION . "\n";
echo "Goma CMS CLI started.\n";

$core = new Core();
define("CLI_LAUNCH_FINISHED", true);
