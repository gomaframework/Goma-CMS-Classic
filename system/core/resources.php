<?php
/**
  * this class provides the javascript and css-files in a html-file
  *@package goma
  *@link http://goma-cms.org
  *@license: http://www.gnu.org/licenses/gpl-3.0.html see 'license.txt'
  *@Copyright (C) 2009 - 2011  Goma-Team
  * last modified: 03.11.2011
  * Version: 003
*/

defined('IN_GOMA') OR die('<!-- restricted access -->'); // silence is golden ;)

ClassInfo::AddSaveVar("Resources", "names");

/**
 * new resources class
*/

class Resources extends Object {
	const VERSION = "1.0";
    /**
     * defines if gzip is enabled
     *
     *@name gzip
     *@access public
    */
    public static $gzip = false;
    /**
     * this var defines if combining is enabled
     *
     *@name combine
     *@access private
     *@var bool
    */
    private static $combine = true;
    /**
     * enables conbining
     *
     *@name enableCombine
     *@access public
    */
    public static function enableCombine() {
        self::$combine = true;
    }
    /**
     * disables combining
     *
     *@name disableCombine
     *@access public
    */
    public static function disableCombine() {
        self::$combine = false;
    }
    /**
     * this var contains all javascript-resources
     *
     *@name resources_js
     *@access private
     *@var array
    */
    private static $resources_js = array();
    /**
     * this var contains all css-resources
     *
     *@name resources_css
     *@access private
     *@var array
    */
    private static $resources_css = array();
    /**
     * this var contains names for special resources
     *
     *@name names
     *@access public
     *@var array
    */
    public static $names = array();
    /**
     * raw data
     *
     *@name resources_data
     *@access private
     *@var array
    */
    private static $resources_data = array();
    /**
     * raw js code
     *
     *@name rawjs
     *@access private
     *@var array
    */
    private static $raw_js = array();
    /**
     * if cache was updates this request
     *
     *@name cacheUpdated
     *@access public
    */
    public static $cacheUpdated = false;
    /**
     * adds a special name
     *@name addName
     *@access public
     *@param string - name
     *@param string - file
    */
    public static function addName($name, $file)
    {
            self::$names[$name] = $file;
    }
    
    /**
     * cache for css-default-diretory
    */
    private static $default_directory_contents = false;
    
    /**
     * add-functionality
     *
     *@name add
     *@access public
     *@param string - name: special name; name of gloader-resource @see gloader; filename
     *@param resource-type
     *@param combine-name
    */
    public static function add($content, $type = false, $combine_name = "") {
        if(PROFILE) Profiler::mark("resources::Add");
        
        if(Core::is_ajax() || isset($_GET["debug"])) {
            self::disableCombine();
        }
        // special names
        if(isset(self::$names[$content])) {
            $content = self::$names[$content];
        }
        
        if(isset(gloader::$resources[$content])) {
            $content = gloader::$resources[$content]["file"];
        }
        
        // find out type if not set
        if($type === false) {
            if(_eregi("\.css$", $content)) {
                $type = "css";
            } else {
                $type = "js";
            }
        }
        
        $content = str_replace("//", "/", $content);
        
        $type = strtolower($type);
        
        if($path = self::getFilePath($content)) {
            $content = $path;
            $path = true;
        }
        
        switch($type) {
            case "css":
            case "style":
            case "stylesheet":
                if(self::$combine && !_ereg("\.php/", $content) && is_file(ROOT . $content)) {
                    if(!isset(self::$resources_css["combine"]["mtime"])) {
                        self::$resources_css["combine"]["mtime"] = filemtime(ROOT . $content);
                    } else {
                        $mtime = filemtime(ROOT . $content);
                        if(self::$resources_css["combine"]["mtime"] < $mtime) {
                            self::$resources_css["combine"]["mtime"] = $mtime;
                        }
                        unset($mtime);
                    }
                    self::addData("self.CSSLoadedResources['".$content."'] = '';self.CSSIncludedResources['".$content."'] = true;");
                  	
                    self::$resources_css["combine"]["files"][$content] = $content;
                } else {
                    if(!$path && self::file_exists(SYSTEM_TPL_PATH . "/css/" . $content)) {
                        $content = SYSTEM_TPL_PATH . "/css/" . $content;
                    } else if(!$path) {
                    	self::addData("self.CSSLoadedResources['".$content."'] = '';self.CSSIncludedResources['".$content."'] = true;");
                        self::$resources_css["default"]["files"][$content] = $content;
                        break;
                    }
                    
                    if(self::$combine) {
                        if(!isset(self::$resources_css["combine"]["mtime"])) {
                            self::$resources_css["combine"]["mtime"] = filemtime(ROOT . $content);
                        } else {
                            $mtime = filemtime(ROOT . $content);
                            if(self::$resources_css["combine"]["mtime"] < $mtime) {
                                self::$resources_css["combine"]["mtime"] = $mtime;
                            }
                            
                        }
                        self::$resources_css["combine"]["files"][$content] = $content;
                        self::addData("self.CSSLoadedResources['".$content."'] = '';self.CSSIncludedResources['".$content."'] = true;");
                        break;
                    } else {
                    	self::addData("self.CSSLoadedResources['".$content."'] = '';self.CSSIncludedResources['".$content."'] = true;");
                        self::$resources_css["default"]["files"][$content] = $content;
                    }
                
                }
                
            break;
            case "script":
            case "js":
            case "javascript":
                if(self::$combine && $combine_name != "" && !_ereg("\.php/", $content) && $path === true /* file exists */) {
                    // last modfied of the whole block
                    if(!isset(self::$resources_js[$combine_name])) {
                        self::$resources_js[$combine_name] = array(
                            "files"     	=> array(),
                            "mtime"        	=> filemtime(ROOT . $content),
                            "raw"        	=> array(),
                            "name"        	=> $combine_name
                        );
                    } else {
                        $mtime = filemtime(ROOT . $content);
                        if(self::$resources_js[$combine_name]["mtime"] < $mtime) {
                            self::$resources_js[$combine_name]["mtime"] = $mtime;
                        }
                    }
                    self::$resources_js[$combine_name]["files"][$content] = $content;
                } else {
                	
                    if($combine_name == "main") {
                    	if(!isset(self::$resources_js["main"])) {
                      	  self::$resources_js["main"] = array("files" => array());
                    	}
                    	self::$resources_js["main"]["files"][$content] = $content;
                    } else {
                    	if(!isset(self::$resources_js["default"])) {
                     	   self::$resources_js["default"] = array();
                  	  	}
                    	self::$resources_js["default"]["files"][$content] = $content;
                    }
                    
                   
                }
            break;
        }
    
        if(PROFILE) Profiler::unmark("resources::Add");
    }
    /**
     * checks the file-path
     *
     *@name getFilePath
     *@access public
    */
    public static function getFilePath($path) {
        if(self::file_exists($path))
            return $path;
        
        if(self::file_exists(tpl::$tplpath . Core::getTheme() . "/" . $path)) {
            $content = tpl::$tplpath . Core::getTheme() . "/" . $path;
        } else if(self::file_exists(APPLICATION_TPL_PATH . "/" . $path)) {
            $content = APPLICATION_TPL_PATH  . "/".  $path;
        } else if(self::file_exists(SYSTEM_TPL_PATH . "/" . $path)) {
            $content = SYSTEM_TPL_PATH . "/" . $path;
        } else {
            $content = false;
        }
        return $content;
    }
    
    /**
     * adds some javascript code
     *@name addJS
     *@access public
     *@param string - js
    */
    public function addJS($js, $combine_name = "scripts") {
        if(self::$combine && $combine_name != "") {
            if(!isset(self::$resources_js[$combine_name])) {
                self::$resources_js[$combine_name] = array("files" => array(), "raw" => array(), "mtime"    => 1, "name"    => $combine_name);
            }
            self::$resources_js[$combine_name]["raw"][] = $js;
        } else {
            self::$raw_js[] = $js;
        }
    }
    /**
     * adds some css code
     *@name addCSS
     *@access public
     *@param string - js
    */
    public function addCSS($css) {
        self::$resources_css["raw"]["data"][] = $css;
    }
    /**
     * if you want to use some data in your scripts, which is from the database you can add it here
     *@name addData
     *@access public
     *@param string - javascript-code
    */
    public function addData($js) {
    
        self::$resources_data[md5($js)] = $js;
    }
    /**
     * gets the resources
     *
     *@name get
     *@access public
    */
    public static function get() {
        if(PROFILE) Profiler::mark("Resources::get");
        
        // if ajax, no combine
        if(Core::is_ajax()) {
            self::disableCombine();
        }
        
        
        
        // generate files
        $files = self::generateFiles();
        $js = $files[1];
        $css = $files[0];
        
        
        if(Core::is_ajax()) {
            // write data to file
            $datajs = implode("\n", self::$resources_data);
            FileSystem::Write(ROOT . CACHE_DIRECTORY . "/data.".md5($datajs).".js",$datajs);
            $js = array_merge(array(ROOT_PATH . CACHE_DIRECTORY . "/data.".md5($datajs).".js"), $js);
            return array("css"    => $css, "js"    => $js);
        } else {
            // generate data
            $datajs = implode("\n			", self::$resources_data);
            // now render
            $html = "";
            if(isset($css["files"])) {
                foreach($css["files"] as $file) {
                    $html .= "        	<link rel=\"stylesheet\" type=\"text/css\" href=\"".ROOT_PATH . $file."\" />\n";
                }
                unset($css["files"]);
            }
            foreach($css as $key => $file) {
                $html .= "        	<link rel=\"stylesheet\" type=\"text/css\" href=\"".ROOT_PATH . $file."\" />\n";
            }
            
            
            foreach($js as $file) {
                $html .= "        	<script type=\"text/javascript\" src=\"".$file."\"></script>\n";
            }
            
            $html .= "\n\n
        	<script type=\"text/javascript\">
            	// <![CDATA[
                	".$datajs."
            	// ]]>
        	</script>\n";
            
            
            
            if(PROFILE) Profiler::unmark("Resources::get");
            return $html;
        }
        
        
    }
    
    /**
     * this method generates all filename and gives them back
     *
     *@name generateFiles
     *@access public
    */
    public static function generateFiles() {
        
        Profiler::mark("Resources::generateFiles");
        $css_files = array();
        $js_files = array();
        if(self::$combine) {
            
            // css
            if(isset(self::$resources_css["combine"])) {
                $combine_css = self::$resources_css["combine"];
                $file = self::getFileName(CACHE_DIRECTORY . "css.combined.".md5(implode(".", $combine_css["files"])).".".$combine_css["mtime"].".".self::VERSION.".css");
                
                if(self::file_exists($file)) {
                    $css_files[] = $file;
                } else {
                    // generate css-file
                    $css = "/**
 *@builder goma resources ".self::VERSION."
 *@license GPL V3.0 <http://www.gnu.org/licenses/gpl-3.0.html>        
*/\n\n";
                    foreach($combine_css["files"] as $cssfile) {
                        
                        $cachefile = ROOT . CACHE_DIRECTORY  . ".cache." . md5($cssfile) . ".css";
                        if(self::file_exists($cachefile) && filemtime($cachefile) > filemtime(ROOT . $cssfile)) {
                            $css .= file_get_contents($cachefile);
                        } else {
                            $data = "/* file ". $cssfile ." */\n\n";
                            $data .= trim(self::parseCSSURLs(cssmin::minify(file_get_contents(ROOT . $cssfile)), $cssfile, ROOT_PATH)) . "\n\n";
                            $css .= $data;
                            FileSystem::Write($cachefile, $data);
                        }
                        unset($cfile, $data, $cachefile);
                    }
                    FileSystem::Write($file,self::getEncodedString($css));
                    $css_files[] = $file;
                    unset($filepointer, $css);
                }
                unset($combine_css, $file, $css_mtime);
            }
            if(isset(self::$resources_css["default"])) {
                $css_files = array_merge($css_files, self::$resources_css["default"]);
            }
            
            
            
            // javascript
            $resources_js = self::$resources_js;
            // main
            if(isset($resources_js["main"])) {
                    $js_files[] = self::makeCombiedJS($resources_js["main"]);
                    unset($resources_js["main"]);
            }
            // default
            if(isset($resources_js["default"])) {
                    foreach($resources_js["default"]["files"] as $jsfile) {
                        $js_files[] = $jsfile;
                        Resources::addData("self.JSLoadedResources[\"".$jsfile."\"] = true;");
                    }
                    unset($resources_js["default"], $jsfile);
            }
            
            // all others
            foreach($resources_js as $combine_name) {
                $js_files[] = self::makeCombiedJS($combine_name);
            }
            // we have to make raw-file
            if(count(self::$raw_js) > 0) {
                $file = self::getFileName(ROOT . CACHE_DIRECTORY . "raw.".md5(implode("", self::$raw_js)).".".self::VERSION.".js");
                if(!is_file($file)) {
                        $js = "";
                        foreach(self::$raw_js as $code) {
                            $js .= "/* RAW */\n\n";
                            $js .= jsmin::minify($code) . "\n\n";
                        }
                        FileSystem::Write($file,self::getEncodedString($js));
                        $js_files[] = $file;
                }
            }
            
            if(isset(self::$resources_css["raw"]["data"]) && count(self::$resources_css["raw"]["data"]) > 0) {
                $css = implode("\n\n", self::$resources_css["raw"]["data"]);
                $filename = self::getFileName(CACHE_DIRECTORY . "/raw." . md5($css) . ".css");
                if(!is_file(ROOT . $filename)) {
                    FileSystem::Write($filename,self::getEncodedString($css));
                    $css_files[] = $filename;
                } else {
                    $css_files[] = $filename;
                }
            }
            usort($js_files, array("Resources", "sortjs"));
        } else {
            
            $css_files = isset(self::$resources_css["default"]["files"]) ? array_values(self::$resources_css["default"]["files"]) : array();
            $js_files = isset(self::$resources_js["default"]["files"]) ? array_values(self::$resources_js["default"]["files"]) : array();
            
            if(isset(self::$resources_js["main"]["files"])) {
                $js_files = array_merge(array_values(self::$resources_js["main"]["files"]), $js_files);
            }
            

            // raw
            if(isset(self::$resources_js["default"]["raw"])) {
                self::$raw_js = array_merge(self::$raw_js, self::$resources_js["default"]["raw"]);
            }
            if(isset(self::$resources_js["main"]["raw"])) {
                self::$raw_js = array_merge(self::$raw_js, self::$resources_js["main"]["raw"]);
            }
            
            
            Profiler::mark("Resources::::get");
            // we have to make raw-file
            if(count(self::$raw_js) > 0) {
                $file = self::getFilename(CACHE_DIRECTORY . "/raw.".md5(implode("", self::$raw_js)).".js");
                if(!is_file(ROOT . $file)) {
                        $js = "";
                        foreach(self::$raw_js as $code) {
                            $js .= "/* RAW */\n\n";
                            $js .= jsmin::minify($code) . "\n\n";
                        }
                        FileSystem::Write($file,self::getEncodedString($js));
                        $js_files[] = $file;
                } else {
                        $js_files[] = $file;
                }
            }
            
            Profiler::unmark("Resources::::get");
            
            if(isset(self::$resources_css["raw"]["data"]) && count(self::$resources_css["raw"]["data"]) > 0) {
                $css = implode("\n\n", self::$resources_css["raw"]["data"]);
                $filename = self::getFileName(CACHE_DIRECTORY . "/raw." . md5($css) . ".css");
                if(!is_file(ROOT . $filename)) {
                    FileSystem::Write($filename,self::getEncodedString($css));
                    $css_files[] = $filename;
                } else {
                    $css_files[] = $filename;
                }
            }
        }
        // reorder js-files
        
        Profiler::unmark("Resources::generateFiles");
        return array($css_files, $js_files);
        
    }
    /**
     * sorts js files, main at first and scripts at last
    */
    public function sortJS($a, $b) {
        if(_ereg("main", $a)) 
            return -1;
            
        if(_ereg("main", $b)) 
            return 1;
        
        if(_ereg("data", $a)) 
            return -1;
            
        if(_ereg("data", $b)) 
            return 1;
        
        if(_ereg("scripts", $a)) 
            return 1;
            
        if(_ereg("scripts", $b)) 
            return -1;
            
        if(_ereg("raw", $a)) 
            return 1;
            
        if(_ereg("raw", $b)) 
            return -1;
            
        return 0;
    }
    /**
     * makes a combined javascript-file
     *
     *@name makeCombinedJS
     *@access public
     *@param data-array
    */
    public static function makeCombiedJS($data) {
        if(isset($data["raw"])) {
            $hash = md5(implode("", $data["files"])) . md5(implode("", $data["raw"]));
        } else {
            $hash = md5(implode("", $data["files"]));
        }
        $file = self::getFileName(CACHE_DIRECTORY . "js.combined.".$data["name"].".".$hash.".".$data["mtime"].".".self::VERSION.".js");
        if(self::file_exists($file)) {
            return $file;
        } else {
            // remake file
            $js = "/**
 *@builder goma resources ".self::VERSION."
 *@license GPL V3.0 <http://www.gnu.org/licenses/gpl-3.0.html>        
*/\n\n";
            $i = 0;
            foreach($data["files"] as $jsfile) {
                $cachefile = ROOT . CACHE_DIRECTORY . ".cache.".md5($jsfile).".js";
                if(self::file_exists($cachefile) && filemtime($cachefile) > filemtime(ROOT . $jsfile)) {
                    $js .= file_get_contents($cachefile);
                } else {
                    $data = "/* File ".$jsfile." */\n\n";
                    if($i == 0) {
                        $i++;
                        $data .= "if(self.JSLoadedResources == null) self.JSLoadedResources = [];";
                    }
                    $data .= "self.JSLoadedResources[\"".$jsfile."\"] = true;\n";
                    $data .= jsmin::minify(file_get_contents(ROOT . $jsfile)) . "\n\n";
                    $js .= $data;
                   	FileSystem::Write($cachefile,$data);
                }
                unset($cfile, $data, $cachefile);
            }
            
            if(isset($data["raw"])) {
                foreach($data["raw"] as $code) {
                    if(strlen($code) > 4000) {
                        $cachefile = ROOT . CACHE_DIRECTORY . ".cache.".md5($code).".js";
                        if(self::file_exists($cachefile)) {
                            $js .= file_get_contents($cachefile);
                        } else {
                            $data = "/* RAW */\n\n";
                            $data .= jsmin::minify($code) . "\n\n";
                            $js .= $data;
                            FileSystem::Write($cachefile,$data);
                        }
                        unset($cfile, $data, $cachefile);
                    } else {
                        $js .= "/* RAW */\n\n";
                        $js .= jsmin::minify($code) . "\n\n";
                    }
                }
            }
            
            FileSystem::Write($file,self::getEncodedString($js));
            unset($filepointer, $js);
            return $file;
        }
    }
    /**
     * cache for following functions
     *
     *@name extCache
     *@access private
    */
    private static $extCache = false;
    
    /**
     * gets the filename
     *
     *@name getFileExt
     *@access public
    */
    public static function getFileExt() {
        if(self::$extCache === false) {
            $gzip = self::$gzip;
            // first check if defalte is available
            // defalte is 21% faster than normal gzip
            if($gzip != 2 && request::CheckBrowserDeflateSupport() && function_exists("gzdeflate")) {
                self::$extCache = ".gdf";            
            // if not, check if gzip
            } else if($gzip != 2 && request::CheckBrowserGZIPSupport() && function_exists("gzencode")) {
                self::$extCache = ".ggz";
            // else send normal file
            } else {
                self::$extCache = "";
            }
        }
        return self::$extCache;
    }
    /**
     * gets full file
     *
     *@name getFileName
     *@access public
     *@param string - file
    */
    public static function getFileName($file) {
        $ext = self::getFileExt();
        if(_ereg("\.js$", $file)) {
            return substr($file, 0, -3) . $ext . ".js";
        }
        
        if(_ereg("\.css$", $file)) {
            return substr($file, 0, -4) . $ext . ".css";
        }
        
        return $file . $ext;
    }
    /**
     * gets the string encoded
     *
     *@name getEncodedString
     *@access public
    */
    public static function getEncodedString($data) {
        $ext = self::getFileExt();
        if($ext == ".gdf") {
            return gzdeflate($data);
        } else if($ext == ".ggz") {
            return gzencode($data);
        } else {
            return $data;
        }
    }
    
    /**
     * bacause the background-image-locations arent't right anymore, we have to correct them
     *
     *@name parseCSSURLs
     *@access public
    */
    public static function parseCSSURLs($css, $file, $base) {
        $path = substr($file, 0, strrpos($file, '/'));
        if(_ereg('^' . preg_quote($base), $path)) {
                $path = substr($path, strlen($base));
        }
        
        preg_match_all('/url\(((\'|"|\s?)(.*)(\"|\'|\s?))\)/Usi', $css, $matches);
        foreach($matches[3] as $key => $url) {
            $css = str_replace($matches[0][$key], 'url("' . $base . $path . "/" .$url . '")', $css);
        }
        return $css;
    }
    /**
     * checks if a file exists
     * for optional further caching
     *
     *@name file_exists
     *@access public
    */ 
    public static function file_exists($file) {
       
        if(isset($_GET["flush"]) && self::$cacheUpdated === false) {
            Object::instance("Resources")->generateClassInfo();
            ClassInfo::write();
        }
        if(defined("CLASS_INFO_LOADED")) {
        	if(!strpos($file, "../") && _eregi('\.(js|css|html)$', $file) && substr($file, 0, strlen(SYSTEM_TPL_PATH)) == SYSTEM_TPL_PATH || substr($file, 0, strlen(APPLICATION_TPL_PATH)) == APPLICATION_TPL_PATH) {
         	   return isset(ClassInfo::$class_info["resources"]["files"][$file]);
       	 	}
       	} else {
       		logging("CLASS_INFO not loaded for file ".$file.". using filesystem. -> poor performance");
       	}
       	
        return file_exists($file);
    }
    /**
     * generates the Class-Info
     *
     *@name generateClassInfo
     *@access public
    */
    public function generateClassInfo() {
        if(PROFILE) Profiler::mark("Resources::GenerateClassInfo");
        
        // scan directories
        ClassInfo::$class_info[$this->class]["files"] = array();
        $this->scanToClassInfo(SYSTEM_TPL_PATH);
        $this->scanToClassInfo(APPLICATION_TPL_PATH);
        self::$cacheUpdated = true;
        if(PROFILE) Profiler::unmark("Resources::GenerateClassInfo");
    }
    /**
     * scan's directories to class-info
     *
     *@name scanToClassInfo
     *@access public
     *@param string - dir
    */
    public function scanToClassInfo($dir) {
        foreach(scandir($dir) as $file) {
            if(is_dir($dir . "/" . $file) && $file != "." && $file != "..") {
                $this->scanToClassInfo($dir . "/" . $file);
            } else { 
                if(_eregi('\.(js|css|html)$', $file)) {
                    ClassInfo::$class_info[$this->class]["files"][$dir . "/" . $file] = true;
                }
            }
            
        }
    }
}