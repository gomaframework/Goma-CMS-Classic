<?php defined("IN_GOMA") OR die();
/**
 * File-System class to map all FileSystem calls with Goma-Specific updates.
 *
 * @package	goma framework
 * @link 	http://goma-cms.org
 * @license LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 * @author 	Goma-Team
 * @version 1.7.1
 *
 * last modified: 03.03.2015
*/

define("LANGUAGE_ROOT", ROOT . "/languages/");
define("IMAGE_ROOT", ROOT . "/images/");
define("UPLOADS_ROOT", ROOT . "/uploads/");
define("HTACCESS_FILE", ROOT . ".htaccess");

class FileSystem extends Object {
	/**
	 * this is the last file which causes an error
	 *
	 * @name errFile
	 * @access public
	*/
	protected static $errFile;

	/**
	 * safe-mode.
	 * When enabled all files and folders are created with 0755.
	 * When disabled all files and folders are created with 0777.
	 * you can call applySafeMode() to update all existing files.
	 *
	 * @param boolean
	*/
	public static $safe_mode = false;

	/**
	 * folders on which safe-mode is applied.
	 *
	 * applySafeModeFolders
	*/
	public static $applySafeModeFolders = array(
		FRAMEWORK_ROOT,
		APP_FOLDER,
		LANGUAGE_ROOT,
		IMAGE_ROOT,
		UPLOADS_ROOT,
		HTACCESS_FILE
	);
	
	/**
	 * this is the last file which causes an error
	 *
	 *@name errFile
	 *@access public
	*/
	public static function errFile() {
		$file = self::$errFile;
		if(substr($file, 0, strlen(ROOT)) == ROOT) {
			return substr($file, strlen(ROOT));
		} else {
			return $file;
		}
	}
	
	/**
	 * get mode from safe-mode.
	*/
	public static function getMode($mode = null) {
		if($mode === null) {
			$mode = (!self::$safe_mode) ? 0777 : 0755;
		}

		return $mode;
	}

	/**
	 * creates a directory and forces chmod safe-mode-specific or given mode
	 *
	 *@name mkdir
	 *@access public
	*/
	public static function requireDir($dir, $mode = null, $throwOnFail = true) {

		$mode = self::getMode($mode);

		clearstatcache();
		if(!file_exists($dir)) {
			if(mkdir($dir, $mode, true)) {
				@chmod($dir, $mode);
				return true;
			} else {
				if($throwOnFail) {
					throw new LogicException("Could not create folder '" . $dir . "'.");
				}
				self::$errFile = $dir;
				return false;
			}
		} else {
			if(fileperms($dir) == $mode)
				return true;
			
			@chmod($dir, $mode);
			return true;
		}
	}
	
	/**
	 * alias for requireDir
	 *
	 *@name requireFolder
	 *@access public
	*/
	public static function requireFolder($dir, $mode = null) {
		return self::requireDir($dir, $mode);
	}
	
	/**
	 * createFile
	 *
	 *@name createFile
	 *@access public
	*/
	public static function createFile($file) {
		if(!file_exists($file)) {
			if($handle = @fopen($file, "w")) {
				fclose($handle);
				chmod($file, self::getMode());
				return true;
			} else {
				self::$errFile = $file;
				return false;
			}
		} else {
			return -1;
		}
	}
	
	/**
	 * writes file contentss
	 *
	 *@name writeFileContents
	 *@access public
	*/
	public static function writeFileContents($file, $content, $modifier = null,$mode = null) {	
		if(@file_put_contents($file, $content, $modifier)) {
			@chmod($file, self::getMode($mode));
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * alias for writeFileContents
	 *
	 *@name write
	 *@access public
	*/
	public static function write($file, $content, $modifier = null, $mode = null) {
		return self::writeFileContents($file, $content, $modifier, $mode);
	}
	
	/**
	 * sets chmod recursivly
	 *
	 *@name chmod
	 *@access public
	 *@param string - path
	 *@param int - mode
	 *@param bool - if to break and return false on fail
	*/
	public static function chmod($file, $mode, $breakOnFail = true) {

		if(function_exists("get_current_user")) {
			@chown($file, get_current_user());
		}

		if(is_dir($file)) {
			if(!@chmod($file, $mode) && $breakOnFail) {
				self::$errFile = $file;
				return false;
			}	
			
			foreach(scandir($file) as $_file) {
				if($_file != "." && $_file != "..")
					if(!self::chmod($file . "/" . $_file, $mode, $breakOnFail) && $breakOnFail) {
						return false;
					}
			}
			return true;
		} else {
			return @chmod($file, $mode);
		}
	}
	
	/**
	 * removes recursivly
	 *
	 *@name delete
	 *@access public
	 *@param string - path
	 *@param bool - if to break and return false on fail
	*/
	public static function delete($file, $breakOnFail = true) {
		if(is_dir($file)) {
			foreach(scandir($file) as $_file) {
				if($_file != "." && $_file != "..")
					if(!self::delete($file . "/" . $_file, $breakOnFail) && $breakOnFail) {
						return false;
					}
			}
			return @rmdir($file);
		} else {
			return @unlink($file);
		}
	}
	
	/**
	 * copies recursivly
	 *
	 *@name copy
	 *@access public
	 *@param string - source
	 *@param string - destination
	 *@param null|int - mode, if you want to make a chmod to every destination file
	 *@param bool - if to break and return false on fail
	*/
	public static function copy($source, $destination, $mode = null, $breakOnFail = true) {
		if(!$source || !$destination) {
			throw new InvalidArgumentException("Source and Destination are required for FileSystem::copy.");
		}
		
		if(is_dir($source)) {
			if(!self::requireDir($destination) && $breakOnFail){
				return false;
			}
		
			
			foreach(scandir($source) as $file) {
				if($file != "." && $file != "..") {
					if(!self::copy($source . "/" . $file, $destination . "/" . $file, $mode, $breakOnFail) && $breakOnFail) {
						return false;
					}
				}
			}
			return true;
		} else {
			if(file_exists($destination) && !@unlink($destination)) {
				self::$errFile = $destination;
				return false;
			}
			
			if(copy($source, $destination)) {
				if($mode !== null) {
					chmod($destination, $mode);
				}
				return true;
			} else {
				self::$errFile = $source;
				return false;
			}
		}
	}
	
	/**
	 * moves recursivly
	 *
	 *@name move
	 *@access public
	 *@param string - source
	 *@param string - destination
	 *@param bool - if to break and return false on fail
	*/
	public static function move($source, $destination, $breakOnFail = true, $useLog = false) {
		if(!$source || !$destination) {
			throw new InvalidArgumentException("Source and Destination are required for FileSystem::move.");
		}
		
		if(is_dir($source)) {
			if(!self::requireDir($destination) && $breakOnFail){
				return false;
			}
			
			foreach(scandir($source) as $file) {
				if($file != "." && $file != "..") {
					if(!self::move($source . "/" . $file, $destination . "/" . $file, $breakOnFail) && $breakOnFail) {
						return false;
					}
				}
			}
			return true;
		} else {
			if(file_exists($destination) && !@unlink($destination)) {
				self::$errFile = $destination;
				return false;
			}
			
			if(rename($source, $destination)) {
				return true;
			} else {
				self::$errFile = $source;
				return false;
			}
		}
	}
	
	/**
	 * moves recursivly with logging
	 *
	 *@name moveLogged
	 *@access public
	 *@param string - source
	 *@param string - destination
	 *@param bool - if to break and return false on fail
	 *@param internal variable - for the log
	*/
	public static function moveLogged($source, $destination, $breakOnFail = true, $useLog = false) {
		$log = "#: ";
		
		if(!$source || !$destination) {
			throwError(6, "PHP-Error", "Source and Destination are required for FileSystem::moveLogged.");
		}
		
		if(is_dir($source)) {
			if(!self::requireDir($destination) && $breakOnFail){
				return false;
			}
			
			foreach(scandir($source) as $file) {
				if($file != "." && $file != "..") {
					if(($return = self::moveLogged($source . "/" . $file, $destination . "/" . $file, $breakOnFail)) === false && $breakOnFail) {
						return false;
					}
					
					if($return === false) {
						$log .= "Failed: {$source}/{$file} => {$destination}/{$file}\n";
					}
					
					if(is_string($return))
						$log .= $return;
				}
			}
			return $log;
		} else {
			if(file_exists($destination) && !@unlink($destination)) {
				self::$errFile = $destination;
				return false;
			}
			
			if(rename($source, $destination)) {
				$log .= "{$source} => {$destination} \n";
				return $log;
			} else {
				self::$errFile = $source;
				return false;
			}
		}
	}
	
	/**
	 * nice filezize of file or number
	 *
	 *@name filesize_nice
	 *@access public
	 *@param string|int - filesize or filename
	 *@param int - precision of rounding
	*/
	public static function filesize_nice($data, $prec = 1) {
		if(file_exists($data)) {
			$size = filesize($data);
		} else if(preg_match('/^[0-9]+$/', $data)) {
			$size = $data;
		} else {
			return false;
		}
		
		
		$ext = "B";
		if($size > 1300) {
			$size = round($size / 1024, $prec);
			$ext = "K";
			if($size > 1300) {
				$size = round($size / 1024, $prec);
				$ext = "M";
				if($size > 1300) {
					$size = round($size / 1024, $prec);
					$ext = "G";
				}
			}
		}
		
		return $size . $ext;
	}
	
	/**
	 * protects file-path
	 *
	 *@name protect
	 *@access public
	*/
	public static function protect($path) {
		return str_replace("../", "", $path);
	}
	
	/**
	 * sends a file to browser in chunks, because of less RAM-Usage
	 *
	 *@name readfile_chunked
	 *@access public
	*/
	public static function readfile_chunked($filename) {
		  
	
		$range = 0; 
		$size = filesize($filename); 
	
		if(isset($_SERVER['HTTP_RANGE'])) { 
			list($a, $range) = explode("=",$_SERVER['HTTP_RANGE']); 
			str_replace($range, "-", $range); 
			$size2 = $size - 1; 
			$new_length = $size - $range; 
			HTTPResponse::setResHeader(206);
			HTTPResponse::addHeader("content-length", $new_length);
			HTTPResponse::addHeader("content-range", "bytes " . $range . $size2 . "/" . $size);
		} else { 
			$size2 = $size-1; 
			HTTPResponse::addHeader("content-range", "bytes 0-".$size2 . "/" . $size."");
			HTTPResponse::addHeader("content-length", $size);
		} 
		HTTPResponse::addHeader("Accept-Ranges", "bytes");		
		// send headers now
		HTTPResponse::sendHeader();
	
		ini_set('max_execution_time', '0');  
		$chunksize = 1*(1024*1024); // how many bytes per chunk
		$handle = fopen($filename, 'rb');
		
		fseek($handle,$range);
		
		if ($handle === false) {
			return false;
		}
		while (!feof($handle)) {
			$buffer = fread($handle, $chunksize);
			print $buffer;
			ob_flush();
			flush();
		}
		return fclose($handle);
	}
	
	/**
	 * sends a specified file to the browser through the file-sender
	 *
	 *@name sendFile
	 *@access public
	 *@param string - file
	*/
	public function sendFile($file, $filename = null) {
		if(!file_exists($file))
			return false;
		
		$hash = randomString(20);
		FileSystem::write(FRAMEWORK_ROOT . "temp/download." . $hash . ".goma", serialize(array("file" => realpath($file), "filename" => $filename)));
		HTTPResponse::redirect(ROOT_PATH . "system/libs/file/Sender/FileSender.php?downloadID=" . $hash);
		exit;
	}
	
	/**
	 * compares two files
	 *
	 *@name compare
	 *@access public
	*/
	public function compare($file1, $file2) {
		$content1 = strtoupper(dechex(crc32(file_get_contents($file1))));
		$content2 = strtoupper(dechex(crc32(file_get_contents($file2))));

		if ($content1 != $content2) {
			return false;
		} else {
			return true;
		}
	}
	
	/**
	 * checks if we could copy/move and overwrite files from directory 1 in directory 2.
	 *
	 * @name 	checkMovePerms
	 * @access 	public
	 * @param 	string - directory 1
	 * @param 	string - directory 2
	*/
	public static function checkMovePerms($source, $dest) {
		if(!file_exists($dest) || !file_exists($source) || !is_writable($dest)) {
			if(!file_exists($source)) {
				self::$errFile = $source;
			} else {
				self::$errFile = $dest;
			}
			return false;
		}
		
		foreach(scandir($source) as $file) {
			if($file != "." && $file != "..") {
				if(is_dir($source . "/" . $file)) {
					if(file_exists($dest . "/" . $file)) {
						if(!self::checkMovePerms($source . "/" . $file, $dest . "/" . $file)) {
							return false;
						}
					}
				} else {
					if(file_exists($dest . "/" . $file)) {
						if(!is_writable($dest . "/" . $file)) {
							self::$errFile = $dest . "/" . $file;
							return false;
						}
					}
				}
			}
		}
		return true;
	}
	
	/**
	 * checks if we could copy/move and overwrite given files
	 *
	 * @name 	checkMovePermsByList
	 * @access 	public
	 * @param 	array - filelist
	 * @param 	string - destination
	*/
	public static function checkMovePermsByList($list, $dest) {
		if(!file_exists($dest) || !is_writable($dest)) {
			self::$errFile = $dest;
			return false;
		}
		
		foreach($list as $file) {
			if(!file_exists($dest . "/" . $file)) {
				$_file = substr($file, 0, strrpos($file, "/"));
				while(!file_exists($dest . "/ . $_file")) {
					if(!strpos($_file, "/")) {
						continue 2;
					}
					$_file = substr($_file, 0, strrpos($_file, "/"));
				}
				
				if(!is_dir($dest . "/" . $file) && !is_writable($dest . "/" . $_file)) {
					self::$errFile = $dest . "/" . $_file;
					return false;
				}
				
			} else if (!is_dir($dest . "/" . $file) && !is_writable($dest . "/" . $file)) {
				self::$errFile = $dest . "/" . $file;
				return false; 
			}
		}
		return true;
	}

	/**
	 * finds the first existing file in a path. it iterates upwards, so
	 * it first checks test/blah/blub.txt, then test/blah, and so on.
	 *
	 * @param 	getNearestFileInPath
	 * @return  string|false
	*/
	public function getNearestFileInPath($path, $root = "./") {
		if(file_exists($root . $path)) {
			return $path;
		}

		$file = substr($path, 0, strrpos($path, "/"));
		while(!file_exists($root . $file)) {
			if(!strpos($file, "/")) {
				return false;
			}
			$file = substr($file, 0, strrpos($file, "/"));
		}

		return $file;
	}
	
	/**
	 * returns an index of all files in a directory and every subdirectory
	 *
	 *@name index
	 *@access public
	 *@param string - directory
	 *@param array - index
	*/
	public static function index($dir, &$index) {
		if(is_dir($dir)) {
			$dir = realpath($dir);
			foreach(scandir($dir) as $file) {
				if($file == "." || $file == "..")
					continue;
				
				if(is_dir($dir . "/" . $file)) {
					self::index($dir . "/" . $file, $index);
				} else {
					self::$index[] = $dir . "/" . $file;
				}
			}
		} else if(file_exists($dir)) {
			$index[] = $dir;
		}
		
	}

	/**
	 * apply-safe-mode.
	*/
	public static function applySafeMode($folders = null, $configFiles = null) {
		if($folders === null) {
			$folders = self::$applySafeModeFolders;
		}

		foreach($folders as $folder) {
			if(file_exists($file)) {
				self::chmod($folder, self::getMode(), false);	
			}
		}
		
		chmod(ROOT, self::getMode());

		// reset config files
		if(self::$safe_mode) {
			$configFiles = $configFiles || array(ROOT . "_config.php", APP_FOLDER . "config.php");
			foreach($configFiles as $file) {
				self::chmod($folder, 0644);
			}
		}
	}

	/**
	 * generate code for external system to preserve state of safe-mode.
	*/
	public static function codeForExternalSystem() {
		return 'FileSystem::$safe_mode = '.var_export(FileSystem::$safe_mode, true).';';
	}
}
