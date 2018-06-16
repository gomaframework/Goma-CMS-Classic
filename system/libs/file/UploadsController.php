<?php defined('IN_GOMA') OR die();

/**
  *	@package 	goma framework
  *	@link 		http://goma-cms.org
  *	@license: 	LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
  *	@author 	Goma-Team
  * @Version 	1.5
  *
  * last modified: 26.01.2015
*/
class UploadsController extends Controller {
    
	/**
	 * index
	*/
	public function index() {
		if($this->modelInst()->checkPermission()) {
			if(preg_match('/\.(pdf)$/i', $this->modelInst()->filename)) {
                GomaResponse::create()->setHeader("content-type", "application/pdf")->sendHeader();
				readfile($this->modelInst()->realfile);
				exit;
			}

			return FileSystem::sendFile($this->modelInst()->realfile, $this->modelInst()->filename);
		}
	}

    /**
     * checks for the permission to do anything
     *
     * @param string $action
     * @param string $classWithActionDefined
     * @return bool
     */
	public function checkPermission($action, $classWithActionDefined) {
		return (parent::checkPermission($action, $classWithActionDefined) && $this->modelInst()->checkPermission());
	}
}
