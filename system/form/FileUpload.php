<?php defined("IN_GOMA") OR die();

/**
 * a simple Upload form-field which supports Ajax-Upload + normal Framed upload.
 *
 * @author 	Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @package Goma\Form
 * @version 1.2
 */
class FileUpload extends FormField {
    /**
     * url-handlers. used for controller.
     */
    static $url_handlers = array(
        "ajaxUpload" => "ajaxUpload",
        "frameUpload" => "frameUpload"
    );

    /**
     * all allowed file-extensions for this field.
     */
    public $allowed_file_types = array(
        "jpg",
        "png",
        "bmp",
        "jpeg",
        "zip",
        "rar",
        "doc",
        "txt",
        "text",
        "pdf",
        "dmg",
        "7z",
        "gif",
        "mp3",
        "xls"
    );

    /**
     * max filesize
     * @name 	max_filesize
     */
    public $max_filesize = 5242880;
    // 5m = 2097152 = 1024 * 1024 * 2

    /**
     * collection
     */
    public $collection = "FormUpload";

    /**
     * upload-class
     */
    protected $uploadClass = "Uploads";

    /**
     * this field needs to have the full width
     *
     * @name fullSizedField
     */
    protected $fullSizedField = true;

    /**
     * default-icon.
     */
    protected $defaultIcon = "system/images/icons/goma/128x128/file.png";

    /**
     * template.
     */
    public $template = "form/FileUpload.html";

    /**
     * template-view.
     */
    protected $templateView;

    /**
     * @var string
     */
    protected $chunkKey;

    /**
     * @var bool
     */
    protected $showDeleteButton = true;

    /**
     * creates field.
     * @param string $name
     * @param string $title
     * @param null|array $file_types
     * @param Uploads $value
     * @param null $parent
     * @return static
     */
    public static function create($name, $title, $file_types = null, $value = null, $parent = null) {
        return new static($name, $title, $file_types, $value, $parent);
    }

    /**
     * @param string $name
     * @param string $title
     * @param array $file_types
     * @param string $value
     * @param string $collection
     * @param Form $form
     */
    public function __construct($name = null, $title = null, $file_types = null, $value = null, $collection = null, &$form = null) {
        parent::__construct($name, $title, $value, $form);
        if($file_types !== null && (is_array($file_types) || $file_types == "*"))
            $this->allowed_file_types = $file_types;

        if(isset($collection))
            $this->collection = $collection;

        $this->templateView = new ViewAccessableData();
    }

    /**
     * @return array|mixed|null|string|Uploads|ViewAccessableData
     * @throws FileCopyException
     */
    public function getModel()
    {
        $model = parent::getModel();

        if(!$this->isDisabled()) {
            if (is_array($model) && !empty($model["name"])) {
                try {
                    $this->model = $model = $this->handleUpload($model);
                } catch (Exception $e) {
                    AddContent::addNotice($e->getCode() . ": " . $e->getMessage());
                }
            } else if ($this->POST) {
                if (isset($this->getRequest()->post_params[$this->PostName() . "__deletefile"])) {
                    $this->model = $model = "";
                } else if (isset($this->getRequest()->post_params[$this->PostName() . "_file"])) {
                    $this->model = $model = $this->getRequest()->post_params[$this->PostName() . "_file"];
                }
            }
        }

        if(!is_a($model, "Uploads")) {
            if (!empty($model) && ($data = Uploads::getFile($model)) !== false) {
                return $this->model = $data;
            } else {
                if (!empty($model)) {
                    if ($data = Uploads::addFile(basename($model), $model, $this->collection)) {
                        return $this->model = $data;
                    }
                }

                return $this->model = null;
            }
        }

        return $model;
    }

    /**
     * ajax upload
     *
     * @return string
     *
     */
    public function ajaxUpload() {
        try {
            if(!$this->chunkKey) {
                $this->chunkKey = randomString(10);
            }

            if($this->max_filesize != -1 && $this->request->getHeader("x-file-size") > $this->max_filesize) {
                throw new FileUploadException(lang('files.filesize_failure', "The file is too large."), ExceptionManager::FILEUPLOAD_SIZE_FAIL);
            }

            $chunkedUpload = new ChunkedUploadHandler($this->request, "file", $this->chunkKey);
            if(!$chunkedUpload->isFinished()) {
                return new JSONResponseBody(array(
                    "status" => 2,
                    "wait" 	 => true,
                    "done"	 => $chunkedUpload->getStatus()
                ));
            }

            $response = $this->handleUpload($chunkedUpload->getFileArray());
            $chunkedUpload->delete();
            /** @var Uploads $response */
            if (is_object($response)) {
                return new JSONResponseBody(array(
                    "status" => 1,
                    "file" => $this->getFileResponse($response)
                ));
            } else if (is_string($response)) {
                return $this->sendFailureJSON($response);
            } else {
                return $this->sendFailureJSON();
            }
        } catch(Exception $e) {
            return $this->sendFailureJSON($e->getMessage());
        }
    }

    /**
     * renders response.
     *
     * @param Uploads $response
     * @return array
     */
    protected function getFileResponse($response) {
        /** @var FileUploadRenderData $info */
        $info = $this->exportBasicInfo();

        $info->setUpload($response);

        $data = $info->ToRestArray(false, false);

        return $data["upload"];
    }

    /**
     * sends error with optional status-message in JSON-Format and sets JSON-Header.
     * @param null|string $error
     * @return GomaResponse
     */
    public function sendFailureJSON($error = null) {
        $error = isset($error) ? $error : lang("files.upload_failure");

        return new JSONResponseBody(array(
            "status" => 0,
            "error" => $error
        ));
    }

    /**
     * prints failure as JSON without JSON-Header.
     * @param null|string $error
     * @return GomaResponse
     */
    public function printFailureJSON($error = null) {
        $error = isset($error) ? $error : lang("files.upload_failure");

        return new GomaResponse(array(
            "content-type" => "text/plain"
        ), json_encode(array(
            "status" => 0,
            "errstring" => $error
        )));
    }

    /**
     * frame upload
     *
     * @return string
     */
    public function frameUpload() {
        if(isset($this->request->post_params["file"])) {
            try {
                $response = $this->handleUpload($this->request->post_params["file"]);
                /** @var Uploads $response */
                if (is_object($response)) {
                    return json_encode(array(
                        "status" => 1,
                        "file" => $this->getFileResponse($response)
                    ));
                } else if (is_string($response)) {
                    $this->printFailureJSON($response);
                } else {
                    $this->printFailureJSON();
                }
            } catch(Exception $e) {
                $this->printFailureJSON($e->getMessage());
            }
        } else {
            $this->printFailureJSON();
        }
    }

    /**
     * this shouldn't do anything
     */
    public function setValue() {
    }

    /**
     * creates the file-upload-node
     */
    public function createNode() {
        $node = parent::createNode();
        $node->type = "file";
        return $node;
    }

    /**
     * javascript-variable.
     */
    protected function jsVar() {
        return "fileupload_" . $this->ID();
    }

    /**
     * @param FormFieldRenderData $info
     * @param bool $notifyField
     */
    public function addRenderData($info, $notifyField = true)
    {
        $info->addCSSFile("font-awsome/font-awesome.css");
        $info->addJSFile("system/form/FileUpload.js");
        $info->addCSSFile("FileUpload.less");
        gloader::load("ajaxupload");

        parent::addRenderData($info, $notifyField);
    }

    /**
     * @return string
     */
    public function js()
    {
        if($this->isDisabled()) {
            return parent::js();
        }

        return "$(function(){ new FileUpload(
		form, field,
		$('#" . $this->divID() . "'), '" . $this->externalURL() . "', " . var_export($this->max_filesize, true) . ", ".json_encode($this->allowed_file_types).");});" .
            parent::js();
    }

    /**
     * sets the right enctype for the form.
     * renders div.
     * @param FileUploadRenderData|null $info
     * @return HTMLNode
     */
    public function field($info) {
        if(PROFILE)
            Profiler::mark("FormField::field");

        // modify form for right datatype
        $this->form()->form->enctype = "multipart/form-data";

        $this->callExtending("beforeField");

        $this->setValue();

        $this->container->append(
            $this->templateView
                ->customise(
                    $info->setDefaultIcon($this->defaultIcon)
                        ->setUpload($this->getModel())
                        ->ToRestArray(false, false)
                )->customise(
                    array(
                        "postname" => $this->PostName()
                    )
                )
                ->renderWith($this->template)
        );

        $this->container->addClass("fileupload-formfield");
        if($this->errors) {
            $this->container->addClass("form-field-has-error");
        }

        if(PROFILE)
            Profiler::unmark("FormField::field");

        return $this->container;
    }

    /**
     * @param null $fieldErrors
     * @return FileUploadRenderData|FormFieldRenderData
     */
    public function exportBasicInfo($fieldErrors = null)
    {
        /** @var FileUploadRenderData $info */
        $info = parent::exportBasicInfo($fieldErrors);

        $info->setShowDeleteButton($this->showDeleteButton);
        $info->setShowManagePath(Permission::check(Uploads::PERMISSION_ADMIN));

        return $info;
    }

    /**
     * @return TabRenderData
     */
    protected function createsRenderDataClass() {
        return FileUploadRenderData::create($this->name, $this->classname, $this->ID(), $this->divID());
    }

    /**
     * handles the upload
     *
     * @param array $upload
     * @return mixed
     * @throws FileUploadException
     */
    public function handleUpload($upload) {
        if(!isset($upload["name"], $upload["size"], $upload["tmp_name"])) {
            throw new InvalidArgumentException("Upload-Object requires name, size, tmp_name.");
        }

        if($upload["error"] != UPLOAD_ERR_INI_SIZE && $upload["error"] != UPLOAD_ERR_FORM_SIZE &&
            ($upload["size"] <= $this->max_filesize || $this->max_filesize == -1)) {
            $name = $upload["name"];
            $ext = strtolower(substr($name, strrpos($name, ".") + 1));
            if($upload["error"] != UPLOAD_ERR_EXTENSION &&
                ($this->allowed_file_types == "*" || in_array($ext, $this->allowed_file_types))) {
                $name = preg_replace('/[^a-zA-Z0-9_\-\.]/i', '_', $name);
                $data = call_user_func_array(array(
                    $this->uploadClass,
                    "addFile"
                ), array(
                    $name,
                    $upload["tmp_name"],
                    $this->collection,
                    $this->uploadClass
                ));

                $this->model = $data;

                return $data;
            } else {
                // not right filetype
                throw new FileUploadException(lang("files.filetype_failure", "The filetype isn't allowed."), ExceptionManager::FILEUPLOAD_TYPE_FAIL);
            }
        } else {
            // file is too big
            throw new FileUploadException(lang('files.filesize_failure', "The file is too large."), ExceptionManager::FILEUPLOAD_SIZE_FAIL);
        }
    }

    /**
     * @return ViewAccessableData
     */
    public function getTemplateView()
    {
        return $this->templateView;
    }

    /**
     * @param ViewAccessableData $templateView
     * @return $this
     */
    public function setTemplateView($templateView)
    {
        $this->templateView = $templateView;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isShowDeleteButton()
    {
        return $this->showDeleteButton;
    }

    /**
     * @param boolean $showDeleteButton
     * @return $this
     */
    public function setShowDeleteButton($showDeleteButton)
    {
        $this->showDeleteButton = $showDeleteButton;
        return $this;
    }
}
