<?php defined('IN_GOMA') OR die();

/**
  * handles Uploads/-URL for files that were uploaded to goma.
  *
  *	@package 	goma framework
  *	@link 		http://goma-cms.org
  *	@license: 	LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
  *	@author 	Goma-Team
  * @Version 	1.5
  *
  * last modified: 26.01.2015
*/
class UploadController extends Controller {
	/**
	 * handler
	 *
	 *@name url_handlers
	 *@access public
	*/
	public $url_handlers = array(
		"\$collection/\$hash/\$filename" => "handleFile"
	);
	
	/**
	 * allow action
	 *
	 *@name allowed_actions
	 *@access public
	*/
	public $allowed_actions = array(
		"handleFile"
	);
	
	/**
	 * index
	*/
	public function index() {
		return false;
	}
	
	/**
	 * handles a file
	 *
	 *@name handleFile
	 *@access public
	*/
	public function handleFile() {
		$data = DataObject::Get("Uploads", array("path" => $this->getParam("collection") . "/" . $this->getParam("hash") . "/" . $this->getParam("filename")));
		
		if($data->count() == 0) {
			return false;
		}
		
		if(!file_exists($data->first()->realfile)) {
			$data->first()->remove(true);
			return false;
		}
		
		session_write_close();
		
		return $data->first()->controller()->handleRequest($this->request);
	}	
}