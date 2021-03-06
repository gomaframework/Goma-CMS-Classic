<?php defined("IN_GOMA") OR die();

/**
 * @package goma framework
 * @link http://goma-cms.org
 * @license: LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 * @author Goma-Team
 */

define("BACKUP_MODEL_BACKUP_DIR", CURRENT_PROJECT . "/backup/");

class Backup extends gObject {
    const BACKUP_PATH = BACKUP_MODEL_BACKUP_DIR;
    /**
     * list of excluded tables
     */
    public static $excludeList = array("statistics", "statistics_state");

    /**
     * excludes files
     */
    public static $fileExcludeList = array("/uploads/d05257d352046561b5bfa2650322d82d", "temp", "/backups", "/config.php", "/backup", "version.php");

    /**
     * generates a database-backup
     *
     * @param string $file
     * @param Request $request
     * @param string $prefix
     * @param array $excludeList
     * @param float $maxTime
     * @param bool $cli
     * @return string
     * @throws GFSDBException
     * @throws GFSException
     * @throws GFSFileException
     * @throws GFSReadonlyException
     * @throws GFSRealFilePermissionException
     * @throws GFSVersionException
     * @throws IOException
     * @throws PListException
     * @throws ReflectionException
     * @throws SQLException
     */
    public static function generateDBBackup($file, $request, $prefix = DB_PREFIX, $excludeList = array(), $maxTime = 1.0, $cli = false)
    {
        if($cli) {
            echo "Generate DB Backup\n";
        }

        $excludeList = array_merge(StaticsManager::getStatic("Backup", "excludeList"), $excludeList);
        // force GFS
        if (!preg_match('/\.sgfs$/i', $file))
            $file .= ".sgfs";

        $gfs = new GFS($file);

        $plist = new CFPropertyList();
        $plist->add($dict = new CFDictionary());
        $dict->add("type", new CFString("backup"));
        $dict->add("backuptype", new CFString("SQLBackup"));
        $dict->add("foldername", new CFString(APPLICATION));
        $dict->add("created", new CFDate(NOW));

        $td = new CFTypeDetector();
        $excludeListPlist = $td->toCFType($excludeList);
        $dict->add("excludedTables", $excludeListPlist);

        $dict->add("includedTables", $tables = new CFArray());

        $time = microtime(true);
        $i = 0;

        foreach (ClassInfo::$database as $table => $fields) {
            if ($gfs->exists("database/" . $table . ".sql"))
                continue;

            $tables->add(new CFString($table));
            $data = "-- Table " . $table . "\n\n";

            // exclude drop
            if (!in_array($table, $excludeList))
                $data .= "DROP TABLE IF EXISTS " . $prefix . $table . ";\n";

            // Create table
            $data .= "  -- Create \n";
            $sql = "DESCRIBE " . DB_PREFIX . "" . $table . "";
            if ($result = sql::query($sql)) {
                $num = sql::num_rows($result);
                $end = 0;
                $data .= "CREATE TABLE IF NOT EXISTS " . $prefix . $table . " (\n";

                // get all fields
                while ($array = sql::fetch_array($result)) {
                    $tab_name = $array["Field"];
                    $tab_type = $array["Type"];
                    $tab_null = " NOT NULL";
                    $tab_default = (empty($array["Default"])) ? "" : " DEFAULT '" . $array["Default"] . "'";
                    $tab_extra = (empty($array["Extra"])) ? "" : " " . $array["Extra"];
                    $end++;
                    $tab_komma = ($end < $num) ? ",\n" : "";
                    $data .= " " . $tab_name . " " . $tab_type . $tab_null . $tab_default . $tab_extra . $tab_komma;
                }
            }

            // indexes
            $keyarray = array();
            $sql = "SHOW KEYS FROM " . DB_PREFIX . "" . $table;
            if ($result = sql::query($sql)) {
                while ($info = sql::fetch_array($result)) {
                    $keyname = $info["Key_name"];
                    $comment = (isset($info["Comment"])) ? $info["Comment"] : "";
                    $sub_part = (isset($info["Sub_part"])) ? $info["Sub_part"] : "";
                    if ($keyname != "PRIMARY" && $info["Non_unique"] == 0) {
                        $keyname = "UNIQUE " . $keyname;
                    }
                    if ($comment == "FULLTEXT") {
                        $keyname = "FULLTEXT " . $keyname;
                    }
                    if (!isset($keyarray[$keyname])) {
                        $keyarray[$keyname] = array();
                    }
                    $keyarray[$keyname][] = ($sub_part > 1) ? $info["Column_name"] . "(" . $sub_part . ")" : $info["Column_name"];

                } // endwhile
                if (is_array($keyarray)) {
                    foreach ($keyarray as $keyname => $columns) {
                        $data .= ",\n";
                        if ($keyname == "PRIMARY") {
                            $data .= "PRIMARY KEY (";
                        } else if (substr($keyname, 0, 6) == "UNIQUE") {

                            $data .= "UNIQUE " . substr($keyname, 7) . " (";
                        } else if (substr($keyname, 0, 8) == "FULLTEXT") {
                            $data .= "FULLTEXT " . substr($keyname, 9) . " (";
                        } else {
                            $data .= "KEY " . $keyname . " (";
                        }
                        $data .= implode($columns, ", ") . ")";

                    }    // end foreach
                }   // end if

                $data .= ");\n";
                $data .= "\n";
            }

            if (!in_array($table, $excludeList)) {

                // values
                $sql = "SELECT * FROM " . DB_PREFIX . "" . $table . "";
                if ($result = sql::query($sql)) {
                    if (sql::num_rows($result) > 0) {

                        $i = 0;
                        while ($row = sql::fetch_assoc($result)) {
                            if ($i == 0) {
                                $data .= "-- INSERT \n INSERT INTO " . $prefix . "" . $table . " (" . implode(", ", array_keys($row)) . ") VALUES ";
                            }
                            foreach ($row as $key => $value) {
                                $row[$key] = str_replace(array("\n\r", "\n", "\r"), '\n', $value);
                                $row[$key] = addSlashes($row[$key]);
                                $row[$key] = str_replace(APPLICATION, '{!#CURRENT_PROJECT}', $row[$key]);
                            }
                            if ($i == 0) {
                                $i++;
                            } else {
                                $data .= ",";
                            }
                            $data .= " ( '" . implode("','", $row) . "' )\n";
                        }
                    }
                    $data .= "; \n\n\n\n\n\n";
                } else {
                    throw new SQLException();
                }

            }

            $gfs->addFile("database/" . $table . ".sql", $data);
            unset($data);

            $i++;

            $diff = microtime(true) - $time;
            if ($maxTime > 0 && $diff > $maxTime) {
                if(!isCommandLineInterface() && $request->canReplyJSON()) {
                    return GomaResponse::create(null, new JSONResponseBody(array(
                        "file" => $file,
                        "redirect" => $request->url,
                        "reload" => true,
                        "archive" =>  "SQL-Backup",
                        "progress" => ($i / count(ClassInfo::$database)) * 100,
                        "status" => "",
                        "current" => $table,
                        "remaining" => ""
                    )));
                }


                $template = new Template;
                $template->assign("destination", $request->url);
                $template->assign("reload", true);
                $template->assign("archive", "SQL-Backup");
                $template->assign("progress", ($i / count(ClassInfo::$database)) * 100);
                $template->assign("status", "");
                $template->assign("current", $table);
                $template->assign("remaining", "");
                return GomaResponse::create(null, $template->display("/system/templates/GFSUnpacker.html"))->setShouldServe(false);
            }
        }

        $gfs->write("info.plist", $plist->toXML());
        $gfs->close();

        return $file;
    }

    /**
     * generates a file-backup
     * @param string $file
     * @param $request
     * @param array $excludeList
     * @param bool $includeTPL
     * @param bool $cli
     * @return bool
     * @throws DOMException
     * @throws GFSRealFilePermissionException
     * @throws IOException
     * @throws PListException
     */
    public static function generateFileBackup($file, $request, $excludeList = array(), $includeTPL = true, $cli = false)
    {
        $backup = GFS_Package_Creator::createWithRequest($file, $request);

        // for converting the PHP-Array to a plist-structure
        $detector = new CFTypeDetector();

        $plist = new CFPropertyList();
        $plist->add($dict = new CFDictionary());
        $dict->add("type", new CFString("backup"));
        $dict->add("name", new CFString(ClassInfo::$appENV["app"]["name"]));
        $dict->add("created", new CFDate(NOW));
        $dict->add("backuptype", new CFString("files"));
        $dict->add("templates", $templates = new CFArray());
        $dict->add("framework_version", new CFString(GOMA_VERSION . "-" . BUILD_VERSION));
        $dict->add("appENV", $detector->toCFType(ClassInfo::$appENV["app"]));

        foreach (scandir(ROOT . "tpl/") as $template) {
            if ($template != "." && $template != ".." && is_dir(ROOT . "tpl/" . $template)) {
                $templates->add(new CFString($template));
            }
        }

        $td = new CFTypeDetector();
        $excludeListPlist = $td->toCFType($excludeList);
        $dict->add("excludedFiles", $excludeListPlist);


        $backup->write("info.plist", $plist->toXML());
        $backup->setAutoCommit(false);

        if ($includeTPL) {
            $plist = new CFPropertyList();
            foreach (scandir(ROOT . "tpl/") as $file) {

                // first validate if it looks good
                if ($file != "." && $file != ".." && file_exists(ROOT . "tpl/" . $file . "/info.plist")) {

                    // then validate properties
                    $plist->load(ROOT . "tpl/" . $file . "/info.plist");
                    $info = $plist->ToArray();

                    if (isset($info["type"]) && $info["type"] == "Template") {
                        if (!isset($info["requireApp"]) || $info["requireApp"] == ClassInfo::$appENV["app"]["name"]) {
                            if (!isset($info["requireAppVersion"]) || version_compare($info["requireAppVersion"], ClassInfo::appVersion(), "<=")) {
                                if (!isset($info["requireFrameworkVersion"]) || version_compare($info["requireFrameworkVersion"], GOMA_VERSION . "-" . BUILD_VERSION, "<=")) {
                                    $backup->add(ROOT . "tpl/" . $file . "/", "/templates/" . $file, $excludeList);
                                }
                            }
                        }
                    }
                }
            }
        }

        if (defined("LOG_FOLDER")) {
            self::$fileExcludeList[] = "/" . LOG_FOLDER;
        }

        $backup->add(ROOT . APPLICATION, "/backup/", array_merge(StaticsManager::getStatic("Backup", "fileExcludeList"), $excludeList));
        $out = $backup->commitReply(null, null, $cli ? -1 : 2.0, $cli);
        if(is_a($out, GomaResponse::class)) {
            return $out;
        }

        $backup->close();

        return true;
    }

    /**
     * generates a backup
     *
     * @param $file
     * @param Request $request
     * @param array $excludeList
     * @param array $excludeSQLList
     * @param $SQLprefix
     * @param bool $includeTPL
     * @param null $framework
     * @param null $changelog
     * @param float $maxTime
     * @param bool $cli
     * @return bool|GomaResponse
     * @throws GFSFileExistsException
     * @throws GFSRealFileNotFoundException
     * @throws GFSRealFilePermissionException
     * @throws PListException
     * @throws SQLException
     */
    public static function generateBackup($file, $request, $excludeList = array(), $excludeSQLList = array(), $SQLprefix = DB_PREFIX,
                                          $includeTPL = true, $framework = null, $changelog = null, $maxTime = 1.0, $cli = false)
    {
        if (!isCommandLineInterface() &&
            GFS_Package_Creator::wasPacked(null, $request) && GlobalSessionManager::globalSession()->hasKey("backup") &&
            GFS_Package_Creator::wasPacked(GlobalSessionManager::globalSession()->get("backup"), $request)
        ) {
            $file = GlobalSessionManager::globalSession()->get("backup");
        } else {
            GlobalSessionManager::globalSession()->set("backup", $file);
            $out = self::generateFileBackup($file, $request, $excludeList, $includeTPL, $cli);
            if (is_a($out, GomaResponse::class)) {
                return $out;
            }

        }
        $DBfile = self::generateDBBackup(ROOT . CACHE_DIRECTORY . "/database.sgfs", $request, $SQLprefix, $excludeSQLList, $maxTime, $cli);
        if (is_a($DBfile, GomaResponse::class)) {
            return $DBfile;
        }

        $backup = new GFS($file);
        $backup->addFromFile($DBfile, basename($DBfile));
        @unlink($DBfile);
        unset($sql);

        // for converting the PHP-Array to a plist-structure
        $td = new CFTypeDetector();

        $plist = new CFPropertyList();
        $plist->add($dict = new CFDictionary());
        $dict->add("type", new CFString("backup"));
        $dict->add("created", new CFDate(NOW));
        $dict->add("backuptype", new CFString("full"));
        $dict->add("name", new CFString(ClassInfo::$appENV["app"]["name"]));
        $dict->add("version", new CFString(ClassInfo::appVersion()));

        // append changelog
        if (isset($changelog))
            $dict->add("changelog", new CFString($changelog));

        // append framework-version we need

        // append framework-version we need
        if (!isset($framework))
            $dict->add("framework_version", new CFString(GOMA_VERSION . "-" . BUILD_VERSION));
        else
            $dict->add("framework_version", new CFString($framework));

        // append current appENV
        $dict->add("appENV", $td->toCFType(ClassInfo::$appENV["app"]));

        $excludeListPlist = $td->toCFType($excludeList);
        $dict->add("excludedFiles", $excludeListPlist);

        $td = new CFTypeDetector();
        $excludeSQLListPlist = $td->toCFType(array_merge($excludeSQLList, StaticsManager::getStatic("Backup", "excludeList")));
        $dict->add("excludedTables", $excludeSQLListPlist);

        $dict->add("DB_PREFIX", new CFString($SQLprefix));

        $backup->write("info.plist", $plist->toXML());
        $backup->close();
        unset($plist);

        return true;
    }

    /**
     * @param array $args
     * @param int $code
     * @throws GFSDBException
     * @throws GFSException
     * @throws GFSFileException
     * @throws GFSFileExistsException
     * @throws GFSReadonlyException
     * @throws GFSRealFileNotFoundException
     * @throws GFSRealFilePermissionException
     * @throws GFSVersionException
     * @throws IOException
     * @throws PListException
     * @throws ReflectionException
     * @throws SQLException
     */
    public static function cli($args, &$code) {
        if(isset($args["-backup"])) {
            if($args["-backup"] == "sql") {
                $file = isset($args["backupfile"]) ? $args["backupfile"] : "sql" . "." . randomString(5) . "." . date("m-d-y_H-i-s", NOW) . ".sgfs";

                self::generateDBBackup(self::BACKUP_PATH . "/" . $file, new Request("get", ""),
                    DB_PREFIX, array(), -1, true);
            } else {
                $file = isset($args["backupfile"]) ? $args["backupfile"] : "full" . "." . randomString(5) . "." . date("m-d-y_H-i-s", NOW) . ".gfs";

                self::generateBackup(self::BACKUP_PATH . "/" . $file, new Request("get", ""), array(), array(), DB_PREFIX, true, null, null, -1, true);
            }
        }
    }
}

StaticsManager::addSaveVar("Backup", "excludeList");
StaticsManager::addSaveVar("Backup", "fileExcludeList");
Core::addToHook("cli", array(Backup::class, "cli"));
