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
        "children"      => Uploads::PERMISSION_ADMIN,
        "deleteAll"     => Uploads::PERMISSION_ADMIN
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
        $this->addBreadcrumb();

        if($this->request->canReplyJavaScript()) {
            return GomaResponse::create(null,
                new JSONResponseBody(array_merge(
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
                ))
            )->setShouldServe(false);
        }

        return parent::index();
    }

    /**
     *
     */
    protected function addBreadcrumb() {
        $breads = array();
        $current = $this->modelInst();
        while($current->collection) {
            $breads[] = array($current->collection->filename, $current->collection->getManagePath());
            $current = $current->collection;
        }
        array_reverse($breads);
        Core::addBreadcrumb(lang("filemanager_collection"), "Uploads/manageCollection");
        foreach($breads as $bread) {
            Core::addBreadcrumb($bread[0], $bread[1]);
        }
        Core::addBreadcrumb($this->modelInst()->filename, URL);
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
                "id"        => $record->id,
                "representation"    => $record->generateRepresentation(true)
            );
        }
        return GomaResponse::create(null,
            new JSONResponseBody(
                array("data" => $data, "hasNextPage" => $set->isNextPage(), "wholeCount" => $set->countWholeSet())
            )
        )->setShouldServe(false);
    }

    public function backtrackAll() {
        $set = $this->modelInst()->getLinkingModels();
        $set->activatePagination($this->getParam("page"));
        $set->getDbDataSource()->setFetchMode(UploadsBackTrackDataSource::FETCH_MODE_GROUP);

        $data = array();
        /** @var DataObject $record */
        foreach($set as $record) {
            $data[] = array(
                "classname"         => $record->classname,
                "id"                => $record->id,
                "representation"    => $record->generateRepresentation(true)
            );
        }

        return GomaResponse::create(null,
            new JSONResponseBody(array("data" => $data, "hasNextPage" => $set->isNextPage(), "wholeCount" => $set->countWholeSet()))
        )->setShouldServe(false);
    }

    public function allVersions() {
        $data = array();
        $set = $this->modelInst()->getFileVersions()->activatePagination($this->getParam("page"));
        /** @var Uploads $upload */
        foreach($set as $upload) {
            $data[] = array(
                "id"       => $upload->id,
                "isThis"   => $this->modelInst()->id == $upload->id,
                "filename" => $upload->filename,
                "url"      => $upload->url,
                "links" => array(
                    "manage" => $upload->getManagePath()
                )
            );
        }

        return GomaResponse::create(null,
            new JSONResponseBody(array("data" => $data, "hasNextPage" => $set->isNextPage(), "wholeCount" => $set->countWholeSet()))
        )->setShouldServe(false);
    }

    public function children() {
        $data = array();
        $set = $this->modelInst()->children();

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

        return GomaResponse::create(null,
            new JSONResponseBody(array("data" => $data, "hasNextPage" => $set->isNextPage(), "wholeCount" => $set->countWholeSet()))
        )->setShouldServe(false);
    }

    public function deleteAll() {
        if($model = $this->getSingleModel()) {
            if(!$model->can("Delete")) {
                return $this->actionComplete("less_rights");
            }

            $description = $this->generateRepresentation($model);

            if ($this->confirm(lang("filemanager_deleteall_confirm", "Do you really want to delete this record?"), null, null, $description)) {
                $preservedModel = clone $model;
                /** @var Uploads $preservedModel */
                /** @var Uploads $version */
                foreach($preservedModel->getFileVersions() as $version) {
                    $version->remove();
                }
                if ($this->getRequest()->isJSResponse() || isset($this->getRequest()->get_params["dropdownDialog"])) {
                    $response = new AjaxResponse();
                    $data = $this->hideDeletedObject($response, $preservedModel);

                    return $data;
                } else {
                    return $this->actionComplete("delete_success", $preservedModel);
                }
            }
        }
    }

    /**
     * @param string $action
     * @param Uploads|null $record
     * @return string
     */
    public function actionComplete($action, $record = null)
    {
        switch ($action) {
            case "delete_success":
                return GomaResponse::redirect($record && $record->collection ? $record->collection->getManagePath() : BASE_URI . BASE_SCRIPT . "Uploads/manageCollections" . URLEND);
        }

       return parent::actionComplete($action, $record);
    }
}
