<?php defined('IN_GOMA') OR die();

/**
 * manages uploads.
 *
 *	@package 	goma framework
 *	@link 		http://goma-cms.org
 *	@license 	LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 *	@author 	Goma-Team
 *  @version 	1.5
 * @method Uploads modelInst()
 *
 * last modified: 31.07.2016
 */
class ManageUploadController extends FrontedController {
    /**
     * template
     */
    public $template = "uploads/manageFile.html";

    /**
     * allowed actions.
     */
    public $allowed_actions = array(
        "allVersions"   => Uploads::PERMISSION_ADMIN,
        "backtrack"     => Uploads::PERMISSION_ADMIN,
        "backtrackAll"  => Uploads::PERMISSION_ADMIN,
        "children"      => Uploads::PERMISSION_ADMIN
    );

    /**
     * @return bool|JSONResponseBody|string
     * @throws PermissionException
     */
    public function index()
    {
        if(!Permission::check(Uploads::PERMISSION_ADMIN)) {
            throw new PermissionException();
        }

        Core::setTitle($this->modelInst()->filename);
        Core::addBreadcrumb($this->modelInst()->filename, URL);
        if($this->request->canReplyJavaScript()) {
            return new JSONResponseBody(array_merge(
                array(
                    "path"      => BASE_URI . BASE_SCRIPT . $this->modelInst()->path,
                    "filename"  => $this->modelInst()->filename,
                    "icon"      => $this->modelInst()->icon,
                    "url"       => $this->modelInst()->url
                ),
                array(
                    "links" => array(
                        "backtrack" => BASE_URI . BASE_SCRIPT . $this->namespace . "/backtrack/\$page" . URLEND,
                        "backtrackAll"  => BASE_URI . BASE_SCRIPT . $this->namespace . "/backtrackAll/\$page" . URLEND,
                        "allVersions"   => BASE_URI . BASE_SCRIPT . $this->namespace . "/allVersions/\$page" . URLEND,
                        "children"      => BASE_URI . BASE_SCRIPT . $this->namespace . "/children/\$page" . URLEND
                    )
                )
            ));
        }

        return parent::index();
    }

    /**
     * @return JSONResponseBody
     */
    public function backtrack() {
        $set = $this->modelInst()->getLinkingModels();
        $set->activatePagination($this->getParam("page"));

        $data = array();
        /** @var DataObject $record */
        foreach($set as $record) {
            $data[] = array(
                "classname" => $record->classname,
                "id"        => $record->id
            );
        }
        return new JSONResponseBody(array("data" => $data, "hasNextPage" => $set->isNextPage(), "wholeCount" => $set->countWholeSet()));
    }

    public function backtrackAll() {
        $set = $this->modelInst()->getLinkingModels();
        $set->activatePagination($this->getParam("page"));
        $set->getDbDataSource()->setFetchMode(UploadsBackTrackDataSource::FETCH_MODE_GROUP);

        $data = array();
        /** @var DataObject $record */
        foreach($set as $record) {
            $data[] = array(
                "classname" => $record->classname,
                "id"        => $record->id
            );
        }
        return new JSONResponseBody(array("data" => $data, "hasNextPage" => $set->isNextPage(), "wholeCount" => $set->countWholeSet()));
    }

    public function allVersions() {
        $data = array();
        $set = DataObject::get(Uploads::class, array(
            "md5" => $this->modelInst()->md5
        ))->activatePagination($this->getParam("page"));
        /** @var Uploads $upload */
        foreach($set as $upload) {
            $data[] = array(
                "filename" => $upload->filename,
                "url"      => $upload->url,
                "links" => array(
                    "manage" => $upload->getManagePath()
                )
            );
        }
        return new JSONResponseBody(array("data" => $data, "hasNextPage" => $set->isNextPage(), "wholeCount" => $set->countWholeSet()));
    }

    public function children() {

    }
}
