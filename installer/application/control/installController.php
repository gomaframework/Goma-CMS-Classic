<?php defined("IN_GOMA") OR die();

/**
  * @package goma framework
  * @link http://goma-cms.org
  * @license: LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
  * @author Goma-Team
  * last modified: 15.09.2012
  * $Version 2.1.1
*/
class InstallController extends FrontedController {
	/**
	 * url_handlers
	*/
	public $url_handlers = array(
		"installapp/\$app!" 		=> "installApp",
		"execInstall/\$rand!"		=> "execInstall",
		"restore"					=> "selectRestore",
        "restoreFolder/\$app!"      => "restoreFolder"
	);
	
	/**
	 * actions
	*/
	public $allowed_actions = array(
		"install", "installApp", "langselect", "execInstall", "selectRestore",
        "showRestore", "installBackup", "installFormBackup", "restoreFolder"
	);
	
	/**
	 * shows install fronted if language is already selected, else shows lang-select
	*/
	public function index() {
		if(GlobalSessionManager::globalSession()->hasKey("lang")) {
			$folders = array();
			foreach(scandir(ROOT) as $directory) {
				if($directory != "system" && file_exists($directory . "/info.plist") &&
					is_dir($directory . "/application") && file_exists($directory . "/application/application.php")) {
					$info = $this->getFolderInfo($directory);
					$folders[$directory] = array_merge($info, array(
						"directory" => $directory,
						"working"   => file_exists($directory . "/config.php") &&
							$this->testConfig($directory . "/config.php")
					));
				}
			}

			$view = new ViewAccessableData();
			return $view->customise(array(
				"folders" => new DataSet($folders)
			))->renderWith("install/index.html");
		} else {
			return GomaResponse::Redirect(BASE_URI . BASE_SCRIPT . "/install/langselect/");
		}
	}

    /**
     * shows lang-select
     *
     * @return string
     */
	public function langSelect() {
		$data = new ViewAccessAbleData();
		return $data->renderWith("install/langselect.html");
	}

    /**
     * lists apps to select
     *
     * @return string
     */
	public function install() {
		G_SoftwareType::forceLiveDB();
		
		$data = unserialize(file_get_contents(FRAMEWORK_ROOT . "installer/data/apps/.index-db"));
		if(!$data)
			Dev::RedirectToDev();
		
		$apps = G_SoftwareType::listInstallPackages();
		foreach($apps as $key => $val) {
			$apps[$key]["app"] = $key;
			if($val["plist_type"] != "backup") {
				unset($apps[$key]);
			}
		}

		$data = new ViewAccessableData();
		return $data->customise(array(
            "apps" => new DataSet($apps)
        ))->renderWith("install/selectApp.html");
	}

    /**
     * @param string $directory
     * @return array
     * @throws DOMException
     * @throws FileNotFoundException
     * @throws IOException
     * @throws PListException
     */
    protected function getFolderInfo($directory) {
        if(!file_exists($directory . "/info.plist")) {
            throw new FileNotFoundException("File {$directory}/info.plist not found.");
        }

        $plist = new CFPropertyList();
        $plist->parse(file_get_contents($directory . "/info.plist"));
        $info = $plist->ToArray();
        return array(
            "name" => $info["name"],
            "title" => $info["title"],
            "version" => $info["version"] . "-" . $info["build"],
            "icon" => isset($info["icon"]) && file_exists($directory . "/" . $info["icon"]) ?
                $directory . "/" . $info["icon"] : null
        );
    }

    /**
     * @param $configFile
     * @return bool|mixed
     */
    protected function testConfig($configFile) {
        include $configFile;
        /** @var array $domaininfo */
        if(isset($domaininfo)) {
            if (!isset($domaininfo["sql_driver"]) || ClassInfo::exists($domaininfo["sql_driver"])) {
                $driver = isset($domaininfo["sql_driver"]) ? $domaininfo["sql_driver"] : "mysqli";
                if(isset($domaininfo["db"])) {
                    /** @var SQLDriver $mysqli */
                    return SQL::test($driver, $domaininfo["db"]["user"], $domaininfo["db"]["db"],
                        $domaininfo["db"]["pass"], $domaininfo["db"]["host"]);
                }
            } else if(isset($domaininfo["sql_driver"]) && $domaininfo["sql_driver"] == "") {
                return true;
            }
        }

        return false;
    }

    /**
     *
     */
    public function restoreFolder() {
        if(strpos($this->getParam("app"), "/") !== false) {
            return false;
        }

        $form = new Form($this, "restoreFolder", array(
            TextField::create("folder", lang("install.folder"), $this->getParam("app"))->disable()
        ), array(
            new CancelButton("cancel", lang("cancel")),
            new FormAction("save", lang("restore"), "restoreFolderExec")
        ));

        $info = $this->getFolderInfo($this->getParam("app"));

        if(!$this->testConfig($this->getParam("app") . "/config.php")) {
            foreach(array(
                        InfoTextField::createFieldWithInfo(
                            new TextField("dbhost", lang("install.db_host"), "localhost"),
                            lang("install.db_host_info")
                        ),
                        new TextField("dbuser", lang("install.db_user")),
                        new PasswordField("dbpwd", lang("install.db_password")),
                        new TextField("dbname", lang("install.db_name")),
                        new TextField("tableprefix", lang("install.table_prefix"), "".$info["name"]."_")
                    ) as $field) {
                $form->add($field);
            }
        }

        return $form->render();
    }

    /**
     * @param array $data
     * @param Form $form
     * @return GomaResponse
     */
    public function restoreFolderExec($data, $form) {
        setProject($data["folder"]);

        if(isset($data["dbuser"])) {
            writeProjectConfig(array(
                "db" => array(
                    "user" => $data["dbuser"],
                    "pass" => $data["dbpwd"],
                    "db" => $data["dbname"],
                    "host" => $data["dbhost"],
                    "prefix" => $data["tableprefix"]
                )
            ), $data["folder"]);
            $_SESSION["reinstall"] = true;
        }

        if(is_dir($data["folder"] . "/temp")) {
            FileSystem::delete($data["folder"] . "/temp");
        }

        return GomaResponse::redirect(BASE_URI);
    }

    /**
     * starts an installation of an specific app
     *
     * @return array|mixed|string
     */
	public function installApp() {
		G_SoftwareType::forceLiveDB();
		
		$data = unserialize(file_get_contents(FRAMEWORK_ROOT . "installer/data/apps/.index-db"));
		if(!$data)
			return Dev::RedirectToDev();
		
		$apps = G_SoftwareType::listInstallPackages();
		
		$app = $this->getParam("app");
		
		if(isset($apps[$app])) {
			$softwareType = G_SoftwareType::getByType($apps[$app]["plist_type"], $apps[$app]["file"]);
			$data = $softwareType->getInstallInfo($this);
            if(is_a($data, GomaFormResponse::class)) {
                /** @var GomaFormResponse $data */
                if(is_array($data->getResult())) {
                    $data = $data->getResult();
                }
            }

			if(is_array($data)) {
				$rand = randomString(20);
				$data["rand"] = $rand;
				$_SESSION["install"] = array();
				$_SESSION["install"][$rand] = $data;
				
				$dataset = new ViewAccessableData($data);
				return $dataset->renderWith("install/showInfo.html");
			} else {
				return $data;
			}
		}
	}

	/**
	 * validates the installation
	 *
	 * @param FormValidator $obj
	 * @return bool|string
	 */
	public function validateInstall($obj) {
		$result = $obj->getForm()->result;
		$notAllowedFolders = array(
			"dev", "admin", "pm", "system", "__system_temp", "api"
		);
		if(file_exists(ROOT . $result["folder"]) || in_array($result["folder"], $notAllowedFolders) || !preg_match('/^[a-z0-9_]+$/', $result["folder"])) {
			return lang("install.folder_error");
		}
		
		if(isset($result["dbuser"])) {
			if(!SQL::test(SQL_DRIVER, $result["dbuser"], $result["dbname"], $result["dbpwd"], $result["dbhost"])) {
				return lang("install.sql_error");
			}
		}
		
		return true;
	}

    /**
     * executess the installation with a give file
     *
     * @return GomaResponse
     */
	public function execInstall() {
		$rand = $this->getParam("rand");
		if(isset($_SESSION["install"][$rand])) {
			$data = $_SESSION["install"][$rand];
			G_SoftwareType::install($data);
			return GomaResponse::redirect(BASE_URI);
		} else {
			return GomaResponse::redirect(BASE_URI);
		}
	}

    /**
     * shows a form to select a file to restore
     *
     * @name selectRestore
     * @access public
     * @return GomaFormResponse|string
     */
	public function selectRestore() {
		$backups = array();
		$files = scandir(APP_FOLDER . "data/restores/");
		foreach($files as $file) {
			if(preg_match('/\.gfs$/i', $file)) {
				$backups[$file] = $file;
			}
		}
		
		if(empty($backups))
			return '<div class="notice">' . lang("install.no_backup") . '</div>';
		
		$form = new Form($this, "selectRestore", array(
			new Select("backup", lang("install.backup"), $backups)
		), array(
			new FormAction("submit", lang("install.restore"), "submitSelectRestore")
		));
		
		$form->setSubmission("submitSelectRestore");
		
		return $form->render();
	}

    /**
     * submit-action for selectRestore-form
     *
     * @return GomaResponse
     */
	public function submitSelectRestore($data) {
		return GomaResponse::redirect(ROOT_PATH . BASE_SCRIPT . "install/showRestore".URLEND."?restore=" . $data["backup"]);
	}

    /**
     * shows up the file to restore and some information
     *
     * @return array|mixed|string
     */
	public function showRestore() {
		if(!$this->getParam("restore")) {
			return GomaResponse::redirect(ROOT_PATH . BASE_SCRIPT . "install/selectRestore" . URLEND);
		}
			
		if(file_exists(APP_FOLDER . "data/restores/" . basename($this->getParam("restore")))) {
			$gfs = new GFS(APP_FOLDER . "data/restores/" . basename($this->getParam("restore")));
			$data = $gfs->parsePlist("info.plist");
			$t = G_SoftwareType::getByType($data["type"], APP_FOLDER . "data/restores/" . basename($this->getParam("restore")));
			
			$data = $t->getRestoreInfo();
            if(is_a($data, GomaFormResponse::class)) {
                /** @var GomaFormResponse $data */
                if(is_array($data->getResult())) {
                    $data = $data->getResult();
                }
            }

			if(is_array($data)) {
				$rand = randomString(20);
				$data["rand"] = $rand;
				$_SESSION["install"] = array();
				$_SESSION["install"][$rand] = $data;
				
				$dataset = new ViewAccessableData($data);
				return $dataset->renderWith("restore/showInfo.html");
			} else {
				return $data;
			}
		} else {
			return "file not found";
		}
	}
	
	/**
	 * shows the install form for the backup
	*/
	public function installFormBackup() {
		if(!$this->getParam("restore")) {
			return GomaResponse::redirect(ROOT_PATH . BASE_SCRIPT . "install/selectRestore" . URLEND);
		}
		
		if(file_exists(APP_FOLDER . "data/restores/" . basename($this->getParam("restore")))) {
			$gfs = new GFS(APP_FOLDER . "data/restores/" . basename($this->getParam("restore")));
			if(!$gfs->valid) {
				if($gfs->error == 1) {
					return '<div class="notice">' . lang("file_perm_error") . '</div>';
				}
				return "Package corrupded.";
			}
			$plist = new CFPropertyList();
			$plist->parse($gfs->getFileContents("info.plist"));
			
			$data = $plist->ToArray();
			
			if(!version_compare(GOMA_VERSION . "-" . BUILD_VERSION, $data["framework_version"], ">=") || $data["backuptype"] != "full") {
				return false;
			}
			
			// find a good folder-name :)
			if( defined("PROJECT_LOAD_DIRECTORY") && !file_exists(ROOT . PROJECT_LOAD_DIRECTORY)) {
				$default = PROJECT_LOAD_DIRECTORY;
			} else if(!file_exists(ROOT . "mysite")) {
				$default = "mysite";
			} else if(!file_exists(ROOT . "myproject")) {
				$default = "myproject";
			} else {
				$default = null;
			}
			
			$form = new Form($this, "installBackup", array(
				$restore_info = new TextField("restore_info", lang("install.backup"), $this->getParam("restore")),
				$folder = new TextField("folder", lang("install.folder"), $default),
				new HiddenField("restore", $this->getParam("restore")),
				$host = new TextField("dbhost", lang("install.db_host"), "localhost"),
				new TextField("dbuser", lang("install.db_user")),
				new PasswordField("dbpwd", lang("install.db_password")),
				new TextField("dbname", lang("install.db_name"), "goma"),
				$tableprefix = new TextField("tableprefix", lang("install.table_prefix"), "gf_"),
			), array(
				new FormAction("install", lang("restore"), "installBackup")
			));
			
			$restore_info->disable();
			
			if($data["DB_PREFIX"] != "{!#PREFIX}") {
				$tableprefix->value = $data["DB_PREFIX"];
				$tableprefix->disable();
			}
			
			$host->info = lang("install.db_host_info");
			
			$folder->info = lang("install.folder_info");
			$form->addValidator(new FormValidator(array($this, "validateInstall")), "validate");
			$form->addValidator(new RequiredFields(array("folder", "dbhost", "dbuser", "dbname")), "fields");
			
			return $form->render();
		} else {
			return "file not found";
		}
	}
	
	/**
	 * installs the backup
	*/
	public function installBackup($data) {
		$restore = basename($data["restore"]);
		if(file_exists(APP_FOLDER . "data/restores/" . $restore)) {
			$gfs = new GFS(APP_FOLDER . "data/restores/" . $restore);
			if(!$gfs->valid) {
				if($gfs->error == 1) {
					return lang("file_perm_error");
				}
				return "Package corrupded.";
			}
			$plist = new CFPropertyList();
			$plist->parse($gfs->getFileContents("info.plist"));
			
			$plist_data = $plist->ToArray();
			
			if(!version_compare(GOMA_VERSION . "-" . BUILD_VERSION, $plist_data["framework_version"], ">=")  || $plist_data["backuptype"] != "full") {
				return false;
			}
			
			return $this->execInstall(APP_FOLDER . "data/restores/" . $restore, $data["folder"], array(
				"user" 	=> $data["dbuser"],
				"db"	=> $data["dbname"],
				"pass"	=> $data["dbpwd"],
				"host"	=> $data["dbhost"],
				"prefix"=> $data["tableprefix"]
			), false);
		} else {
			return "file not found";
		}
	}
}