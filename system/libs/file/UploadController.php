<?php defined('IN_GOMA') OR die();

/**
  * handles Uploads/-URL for files that were uploaded to goma.
  *
  *	@package 	goma framework
  *	@link 		http://goma-cms.org
  *	@license 	LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
  *	@author 	Goma-Team
  * @Version 	1.5
  *
  * last modified: 26.01.2015
*/
class UploadController extends FrontedController {
	/**
	 * handler
	*/
	public $url_handlers = array(
		"manage/\$collection/\$hash/\$filename" => "manageFile",
		"manageCollection/\$collection" => "manageCollection",
		"\$collection/\$hash/\$filename" => "handleFile"
	);
	
	/**
	 * allow action
	*/
	public $allowed_actions = array(
		"handleFile",
		"manageFile",
		"manageCollection"
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
     * @return mixed
     */
	public function handleFile() {
		$upload = Uploads::getFile($this->getParam("collection") . "/" . $this->getParam("hash") . "/" . $this->getParam("filename"));

		if(!$upload) {
			return false;
		}
		
		if(!file_exists($upload->realfile)) {
			$upload->remove(true);
			return false;
		}

		if($upload->deletable) {
			$upload->deletable = false;
			$upload->writeToDB(false, true);
		}

		GlobalSessionManager::globalSession()->stopSession();

		return ControllerResolver::instanceForModel($upload)->handleRequest($this->request);
	}

	/**
	 * @return string
	 * @throws Exception
	 * @throws MySQLException
	 */
	public function manageFile() {
		$upload = Uploads::getFile($this->getParam("collection") . "/" . $this->getParam("hash") . "/" . $this->getParam("filename"));

		if(!$upload) {
			return false;
		}

		if(!file_exists($upload->realfile)) {
			$upload->remove(true);
			return false;
		}

		$controller = new ManageUploadController();
		return $controller->getWithModel($upload)->handleRequest($this->request);
	}

	/**
	 * @return string
	 * @throws Exception
	 * @throws MySQLException
	 */
	public function manageCollection() {
		$upload = DataObject::get_one(Uploads::class, array(
			"id" => $this->getParam("collection")
		));

		if(!$upload) {
			return DataObject::get(Uploads::class, array(
				"type" => "collection",
				"collectionid" => 0
			))->customise(array(
				"namespace" => $this->namespace
			))->renderWith("uploads/collections.html");
		}

		$controller = new ManageUploadController();
		return $controller->getWithModel($upload)->handleRequest($this->request);
	}
}