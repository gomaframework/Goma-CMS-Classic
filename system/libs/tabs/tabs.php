<?php defined("IN_GOMA") OR die();
/**
  * @package goma framework
  * @link http://goma-cms.org
  * @license: LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
  * @author Goma-Team
*/
class Tabs extends gObject {
    /**
     * name of this tabset
    */
    public $name;
    
    /**
     * array of tabs
    */
    public $tabs = array();
    
    /**
     * container of the tabset
     *
     *@name tabContainer
     *@access public
    */
    public $tabContainer;
    
    /**
     * contains the tab-navigation
    */
    public $tabNavi;
    
    /**
     *@param string $name
    */
    public function __construct($name) {
        parent::__construct();
        
        $this->name = $name;
        $this->tabContainer = new HTMLNode("div", array(
            "class"    	=> "tabs",
            "id"		=> "tabs_container_" . $name
        ), $this->tabNavi = new HTMLNode("ul"));
    }

    /**
     * adds a Tab
     *
     * @param string $title
     * @param string $content
     * @param string $name
     * @param numeric $sort : the tabs are sorted downward
     * @param bool $prepend if prepend or append to sort-group
     * @return bool
     */
    public function addTab($title, $content, $name = "", $sort = 0, $prepend = false) {
        if($prepend) {
            $this->tabs[$sort] = array_merge(array(array("title" => $title, "content" => $content, "name" => $name)), $this->tabs[$sort]);
        } else {
            $this->tabs[$sort][] = array("title" => $title, "content" => $content, "name" => $name);    
        }
        return true;
    }

    /**
     * adds an ajax-Tab
     *
     * @param $title
     * @param $content
     * @param string $name - title
     * @param int $sort
     * @param bool $prepend - if prepend or append to sort-group
     * @return bool
     */
    public function addAjaxTab($title, $content, $name = "", $sort = 0, $prepend = false) {
        if($prepend) {
            $this->tabs[$sort] = array_merge(array(array("title" => $title, "callback" => $content, "name" => $name)), $this->tabs[$sort]);
        } else {
            $this->tabs[$sort][] = array("title" => $title, "callback" => $content, "name" => $name);    
        }
        return true;
    }

    /**
     * renders the tabs
     *
     * @return string
     */
    public function render() {
        $this->callExtending("beforeRender");
        
        // ajax implementation
        if(isset($_GET[$this->name]) && Core::is_ajax()) {
            // find tab to show
            foreach($this->tabs as $tabs) {
                foreach($tabs as $data) {
                	$id = isset($data["name"]) ? "tab_" . $this->name . "_" . $data["name"] : md5($data["title"]);
                    if(isset($data["title"], $data["callback"]) && $id == $_GET[$this->name]) {
                        $_data = call_user_func_array($data["callback"], array());
                        if(is_array($_data) && isset($_data[0], $_data[1])) {
                        	$_data = array("content" => $_data[1], "title" => $_data[0]);
                        }
                        HTTPResponse::addHeader("content-type", "application/x-json");
                        HTTPResponse::setBody(json_encode($_data));
                        HTTPResponse::output();
                        exit;
                    }
                }
            }
            HTTPResponse::addHeader("content-type", "application/x-json");
            HTTPResponse::setBody(json_encode("tab not found"));
            HTTPResponse::output();
            exit;
        }
        
        Resources::add("tabs.css");
        gloader::load("gtabs");
        
        krsort($this->tabs);
        
        $activeFound = false;
        foreach($this->tabs as $tabs) {
            foreach($tabs as $data) {
                // default tabs, without ajax
                if(isset($data["title"], $data["content"])) {
                    $id = isset($data["name"]) ? "tab_" . $this->name . "_" . $data["name"] : md5($data["title"]);
                    
                    $this->tabNavi->append(new HTMLNode("li", array(), $point = new HTMLNode("a", array("href" => URL . URLEND . "?" . $this->name . "=" . $id, "name" => $id, "id" => $id . "_tab", "class" => "tab"), $data["title"])));
                    $this->tabContainer->append($content = new HTMLNode("div", array("id" => $id), $data["content"]));
                    if(!$activeFound && ((isset($_GET[$this->name]) && $_GET[$this->name] == $id) || (!isset($_GET[$this->name]) && isset($_COOKIE["tabs_" . $this->name]) && $_COOKIE["tabs_" . $this->name] = $id))) {
                        
                        setcookie("tabs_" . $this->name, $id, 0, "/");
                        $point->addClass("active");
                        $content->addClass("active");
                        $activeFound = true;
                    }
                    unset($point, $id, $content);
                    
                // ajax tabs
                } else if(isset($data["title"], $data["callback"])) {
                    $id = isset($data["name"]) ? "tab_" . $this->name . "_" . $data["name"] : md5($data["title"]);
                    // check if selected, so call callback
                    if(!$activeFound && ((isset($_GET[$this->name]) && $_GET[$this->name] == $id) || (!isset($_GET[$this->name]) && isset($_COOKIE["tabs_" . $this->name]) && $_COOKIE["tabs_" . $this->name] = $id))) {
                    
                    	$activeFound = true;
                        setcookie("tabs_" . $this->name, $id, 0, "/");
                        
                        // get data from callback
                        $_data = call_user_func_array($data["callback"], array());
                        
                        // if array, the programmer wants to overwrite the title-attribute
                        if(is_array($_data)) {
                            if(isset($_data["title"], $_data["content"])) {
                                $data = $_data;
                            } else {
                                $_data = array_values($_data);
                                $data["title"] = $_data[0];
                                $data["content"] = $_data[1];
                            }
                        // if not, the programmer just gives back the data
                        }  else {
                            $data["content"] = $_data;
                        }
                        
                        // render to tabs
                        $this->tabNavi->append(new HTMLNode("li", array(), new HTMLNode("a", array("class" => "active tab ajax", "name" => $id, "href" => URL . URLEND . "?" . $this->name . "=" . $id, "name" => $id, "id" => $id . "_tab"), $data["title"])));
                        $this->tabContainer->append(new HTMLNode("div", array("class" => "active", "id" => $id), $data["content"]));
                        unset($_data);
                    } else {
                        // in case the ajax-tab is not selected, we just draw the menu
                        $this->tabNavi->append(new HTMLNode("li", array(), new HTMLNode("a", array("class" => "ajax tab", "name" => $id, "href" => URL . URLEND . "?" . $this->name . "=" . $id, "id" => $id . "_tab"), $data["title"])));
                        $this->tabContainer->append(new HTMLNode("div", array("id" => $id), '<div style="text-align: center;"><img src="system/images/loading.gif" alt="loading..." /></div>'));
                    }
                }
            }
        }
        
        // in case no tab is preselected, select the first @todo add ajax support here
        if(!$activeFound && count($this->tabs) > 0) {
            $this->tabNavi->getNode(0)->getNode(0)->addClass("active");
            $this->tabContainer->getNode(1)->addClass("active");
        }

        $this->tabNavi->append(new HTMLNode("li", array("class" => "clear")));

        $this->callExtending("afterRender");
        
  		Resources::addJS('$(function(){ $("#'.$this->tabContainer->id.'").gtabs({"animation": true, "cookiename": "tabs_'.$this->name.'"}); });');
        
        return $this->tabContainer->render();
    }
}