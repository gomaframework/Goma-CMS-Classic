<?php
namespace Goma\Libs\CKEditor;
use Convert;
use Core;
use DataObject;
use Director;
use GomaResponse;
use GomaResponseBody;
use JSONResponseBody;
use RequestHandler;

defined("IN_GOMA") OR die();

/**
  * @package goma framework
  * @link http://goma-cms.org
  * @license: LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
  * @author Goma-Team
  * last modified: 22.12.2012
  * $Version 1.0.3
*/

class PageLinksController extends RequestHandler {
	/**
	 * limit for the list
	*/
	public $limit = 15;
	/**
	 * urls
	*/
	public $url_handlers = array(
		"search/\$search" => "search"
	);
	/**
	 * actions
	*/
	public $allowed_actions = array(
		"search"
	);
	/**
	 * index
	*/
	public function search() {
		if($this->getParam("search")) {
			$search = $this->getParam("search");
		} else {
			$search = "";
		}
		$data = DataObject::search_object("pages", array($search), array(), $this->limit);
		$output = array("count" => $data->count, "nodes" => array());
		foreach($data as $record) {
			$output["nodes"][$record["id"]] = array(
				"id" 	=> $record["id"],
				"title"	=> convert::raw2xml($record["title"]),
				"url"	=> "./?r=" . $record->id
			);
		}
		return new JSONResponseBody($output);;
	}
	/**
	 * index
	*/
	public function index() {
		return GomaResponse::create(null, new GomaResponseBody(array("error" => "Bad Request", "errno" => 400)))->setStatus(400);
	}


	public static function ckhook(&$start) {
		$start .= "if(window['__pagelinksloaded'] == null) {
		$.getScript(\"".APPLICATION."/application/model/pages/CkEditor/pagelinks.js\"); window['__pagelinksloaded'] = true; }";
	}
}

Core::addToHook("ckeditorAddJS", array(PageLinksController::class, "ckhook"));

Director::addRules(array(
	"api/pagelinks/" => PageLinksController::class
));
