<?php defined("IN_GOMA") OR die();

/**
 * Base-Class for GFS Archive-Creation with Page which is reloading sometimes.
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @package		Goma\Framework
 * @version		2.7.2
 */
class GFS_Package_Creator extends GFS {
	public $status;
	public $current;
	public $progress;
	public $remaining;
	
	// packed files for probable later reload
	static public $packed = array();
	
	/**
	 * defines if we commit changes after adding files
	*/
	public $autoCommit = true;
	
	/**
	 * index of files of the next operation
	*/
	protected $fileIndex = array();

    /**
     * @var Request
     */
    protected $request;

    /**
     * @param string $filename
     * @param Request $request
     * @param int|null $flag
     * @param int|null $writeMode
     * @return static
     */
    public static function createWithRequest($filename, $request, $flag = null, $writeMode = null) {
        return new static($filename, $flag, $writeMode, $request);
    }

    /**
     * construct with read-only
     * @param string $filename
     * @param int|null $flag
     * @param int|null $writeMode
     * @param Request|null $request
     * @throws GFSDBException
     * @throws GFSFileException
     * @throws GFSVersionException
     */
    public function __construct($filename, $flag = null, $writeMode = null, $request = null) {
        parent::__construct($filename, isset($flag) ? $flag : GFS_READWRITE, $writeMode);

        $this->request = isCommandLineInterface() || isset($request) ? $request : Director::createRequestFromEnvironment(URL);
    }

    /**
     * @return null|Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param null|Request $request
     * @return $this
     */
    public function setRequest($request)
    {
        $this->request = $request;
        return $this;
    }
    /**
     * adds a folder
     *
     * @param $file
     * @param string $path - directory which we add
     * @param array $excludeList - subfolder, we want to exclude
     * @return bool
     */
	public function add($file, $path = "", $excludeList = array()){
		$this->indexHelper($file, $this->fileIndex, $path, $excludeList);
		
		if($this->autoCommit) {
			$this->commit();
		}
		
		return true;
	}

    /**
     * sets the value of auto-commit
     *
     * @param bool $commit
     * @return $this
     */
	public function setAutoCommit($commit) {
		$this->autoCommit = $commit;
        return $this;
	}

    /**
     * @param null $inFile
     * @param null $index
     * @param float $maxTime
     * @param bool $cli
     * @return GomaResponse
     */
    public function commit($inFile = null, $index = null, $maxTime = 2.0, $cli = false) {
        $out = $this->commitReply($inFile, $index, $maxTime, $cli);
        if(is_a($out, GomaResponse::class)) {
            $out->output();
            exit;
        }

        return $out;
    }

    /**
     * commits the changes
     *
     * @param null $inFile
     * @param null $index
     * @param float $maxTime
     * @param bool $cli
     * @return GomaResponse
     * @throws GFSFileExistsException
     * @throws GFSFileNotFoundException
     * @throws GFSFileNotValidException
     * @throws GFSRealFileNotFoundException
     * @throws GFSRealFilePermissionException
     */
	public function commitReply($inFile = null, $index = null, $maxTime = 2.0, $cli = false) {
		if(isset($index)) {
			$this->fileIndex = $index;
		}

        if($cli) {
            echo "Creating Archive... {$this->file}\n";
        }
		
		// Adding files...
		$this->status = "Adding files...";
		$this->current = "";
		
		// for reloading early enough
		$start = microtime(true);
		if($start - EXEC_START_TIME > 5) {
			$start += 0.9;
		}
		
		// create index-progress-file
		if($this->exists("/gfsprogress" . count($this->fileIndex))) {
			$data = $this->getFileContents("/gfsprogress" . count($this->fileIndex));
			$data = unserialize($data);
			$i = $data["i"];
			$count = $data["count"];
		} else {
			$count = 1;
			$i = 0;
			$this->addFile("/gfsprogress" . count($this->fileIndex), serialize(array("i" => $i, "count" => $count)));
		}

		$realfiles = array_keys($this->fileIndex);
		$paths = array_values($this->fileIndex);

		$currentProgress = round($i / count($this->fileIndex) * 100);
		// iterate through the index
		while($i < count($this->fileIndex)){
			// maximum of 2.0 seconds
			if($maxTime < 0 || microtime(true) - $start < $maxTime) {
				if(!$this->exists($paths[$i])) {
					$this->addFromFile($realfiles[$i], $paths[$i]);
				}

				if(round($i / count($this->fileIndex) * 100) != $currentProgress) {
					if($cli) {
						$currentProgress = round($i / count($this->fileIndex) * 100);
                        echo "\033[5D";
						echo str_pad($currentProgress, 3, " ", STR_PAD_LEFT) . " %";
					}
				}
			} else {
				$count++;
				$this->write("/gfsprogress" . count($this->fileIndex), serialize(array("i" => $i, "count" => $count)));
				$this->close();
				$this->progress = round($i / count($this->fileIndex) * 100);
				$perhit = $i / $count;
				$remaining = (round((count($index) - $i) / $perhit * 3) + 3);
				$this->current = $paths[$i];
				if($remaining > 60) {
					$remaining = round($remaining / 60);
					if($remaining > 60) {
						$remaining = round($remaining / 60);
						$this->remaining = "More than ".$remaining." hours remaining";
					} else {
						$this->remaining = "More than ".$remaining." minutes remaining";
					}
				} else {
					$this->remaining = "More than ".$remaining." seconds remaining";
				}
				
				if(!isset($inFile)) {
					// build the external file and redirect-uri
					$file = $this->buildFile($this->fileIndex);
					$uri = strpos($_SERVER["REQUEST_URI"], "?") ? $_SERVER["REQUEST_URI"] . "&pack[]=".urlencode($this->file)."" : $_SERVER["REQUEST_URI"] . "?pack[]=".urlencode($this->file)."";
					if(count(self::$packed)) {
						foreach(self::$packed as $file) {
							$uri .= "&pack[]=" . urlencode($file);
						}
					}
					return $this->showUI($file . "?redirect=" . urlencode($uri));
				} else {
					// if we are in the external file
				 	return $this->showUI($_SERVER["REQUEST_URI"]);
				}
			}
			$i++;
		}
		
		self::$packed[$this->file] = $this->file;
		$this->unlink("/gfsprogress" . count($this->fileIndex));
		//$this->fileIndex = array();

        if($cli) {
            echo "\033[5D";
            echo "Done Creating Archive.\n";
        }

		// if we are in the external file
		if(isset($inFile)) {
			@unlink($inFile);
            if($this->request->canReplyJSON()) {
                return new GomaResponse(null,
                    new JSONResponseBody(
                        array("success" => true, "redirect" => isset($_GET["redirect"]) ? $_GET["redirect"] : ROOT_PATH)
                    )
                );
            } else {
                return GomaResponse::redirect(isset($this->request->get_params["redirect"]) ? $this->request->get_params["redirect"] : ROOT_PATH);
            }
		}
	}

    /**
     * if a specific file was packed
     *
     * @param null|string $file
     * @param null|Request $request
     * @return bool
     */
	public static function wasPacked($file = null, $request = null) {
        $request = isset($request) ? $request : Director::createRequestFromEnvironment(URL);
		if(isset($file)) {
			$file = str_replace('\\\\', '\\', realpath($file));
			$file = str_replace('\\', '/', realpath($file));
			$pack = isset($request->get_params["pack"]) ? str_replace('\\', '/', str_replace('\\\\', '\\', $_GET["pack"])) : array();
			
			if(isset($request->get_params["pack"])) {
				$file = realpath($file);
				return in_array($file, $pack);
			} else {
				return false;
			}
		} else {
			if(isset($request->get_params["pack"]))
				return true;
			else
				return false;
		}
	}
	
	/**
	 * builds the Code for the external file
	*/
	public function buildFile($index) {
		$goma = new GomaSeperatedEnvironment();
		$goma->addClasses(array("gfs", "GFS_Package_Creator"));

		$code = 'try { 
					$gfs = new GFS_Package_Creator('.var_export($this->file, true).');
					$gfs->commit(__FILE__, '.var_export($index, true).');
				} catch(Exception $e) { 
					echo "<script type=\"text/javascript\">setTimeout(location.reload, 1000);</script> An Error occurred. Please <a href=\"\">Reload</a>"; exit; 
				}';

		$file = $goma->build($code);


		return $file;

	}
	
	/**
	 * creates the index
	*/ 
	public function indexHelper($folder, &$index, $path, $excludeList = array(), $internalPath = "") {
	    if(file_exists($folder)) {
            foreach (scandir($folder) as $file) {
                if ($file != "." && $file != "..") {
                    if (in_array($file, $excludeList) || in_array($internalPath."/".$file, $excludeList)) {
                        continue;
                    }
                    if (is_dir($folder."/".$file)) {
                        $this->indexHelper(
                            $folder."/".$file,
                            $index,
                            $path."/".$file,
                            $excludeList,
                            $internalPath."/".$file
                        );
                    } else {
                        $index[$folder."/".$file] = $path."/".$file;
                    }
                }
            }
        } else {
	        throw new InvalidArgumentException("Folder $folder does not exist.");
        }
	}

    /**
     * shows the ui
     * @param null $file
     * @param bool $reload
     * @return GomaResponse
     */
	public function showUI($file = null, $reload = true) {
		if(!defined("BASE_URI")) define("BASE_URI", "./"); // most of the users use this path ;)

		if(!isCommandLineInterface() && $this->request->canReplyJSON()) {
			return GomaResponse::create(null, new JSONResponseBody(array(
				"redirect" => $file,
				"reload" => $reload,
				"archive" => basename($this->file),
				"progress" => $this->progress,
				"status" => $this->status,
				"current" => $this->current,
				"remaining" => $this->remaining
			)))->setShouldServe(false);
		} else {
			$template = new Template();
			$template->assign("destination", $file);
			$template->assign("reload", $reload);
			$template->assign("archive", basename($this->file));
			$template->assign("progress", $this->progress);
			$template->assign("status", $this->status);
			$template->assign("current", $this->current);
			$template->assign("remaining", $this->remaining);
            return GomaResponse::create(null,
                GomaResponseBody::create(
                    $template->display("/system/templates/GFSUnpacker.html")
                )->setParseHTML(false)
            )->setShouldServe(false);
		}
	}
}
