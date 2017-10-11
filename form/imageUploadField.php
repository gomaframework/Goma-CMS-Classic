<?php defined("IN_GOMA") OR die();

/**
 * a simple Upload form-field which supports Images with Ajax-Upload + cropping.
 * it will give back an ImageUploads-Class with parameters correctly filled out.
 *
 * @author 	Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @package Goma\Form
 * @version 1.2
 */
class ImageUploadField extends FileUpload
{
	/**
	 * all allowed file-extensions
	 */
	public $allowed_file_types = array(
		"jpg",
		"png",
		"bmp",
		"gif",
		"jpeg"
	);

	/**
	 * @var array
	 */
	public $allowed_actions = array(
		"setCropInfo"
	);

	/**
	 * upload-class
	 */
	protected $uploadClass = "ImageUploads";

	/**
	 * @var string
	 */
	protected $widgetTemplate = "form/ImageUploadWidget.html";

	/**
	 * @var double|null
	 */
	protected $aspect = null;

	/**
	 * advanced mode means, that aspect ratio is used.
	 *
	 * @param bool $mode
	 * @return $this
	 */
	public function setAdvancedMode($mode = true) {
		if($mode) {
			$this->template = "form/ImageUpload.html";
		} else {
			$this->template = "form/FileUpload.html";
		}

		return $this;
	}

	/**
	 * @param FormFieldRenderData $info
	 * @param bool $notifyField
	 */
	public function addRenderData($info, $notifyField = true)
	{
		parent::addRenderData($info, $notifyField);

		$info->addJSFile("system/libs/thirdparty/jcrop/jquery.Jcrop.js");
		$info->addJSFile("system/form/imageUpload.js");
		$info->addJSFile("system/libs/tabs/tabs.js");
		$info->addCSSFile("system/templates/css/tabs.less");
		$info->addCSSFile("system/libs/thirdparty/jcrop/jquery.Jcrop.css");

		$info->getRenderedField()->append(
			$this->templateView->renderWith($this->widgetTemplate)
		);
	}

	/**
	 * @param null $fieldErrors
	 * @return FileUploadRenderData
	 */
	public function exportBasicInfo($fieldErrors = null)
	{
		return parent::exportBasicInfo($fieldErrors)->setAspect($this->aspect);
	}

	/**
	 * @return string
	 */
	public function js()
	{
		if($this->isDisabled()) {
			return parent::js();
		}

		return parent::js() . '
			$(function(){
				new ImageUploadController(field, '.var_export($this->externalURL() . "/setCropInfo" . URLEND, true).')
			});
		';
	}

	/**
	 * sets crop-info.
	 */
	public function setCropInfo() {
        $microtime = microtime(true);

        if (!$this->request->isPOST()) {
            throw new BadRequestException("You need to use POST.");
        }

        if (!is_a($this->getModel(), "ImageUploads")) {
            throw new InvalidArgumentException("Value is not type of ImageUpload.");
        }

        $crop = true;
        foreach (array("thumbHeight", "thumbWidth", "thumbLeft", "thumbTop") as $key) {
            if (!RegexpUtil::isDouble($this->getParam($key))) {
                $crop = false;
            }
        }

        /** @var ImageUploads $image */
        $image = $this->getModel();

        if ($this->getParam("useSource") && $this->getParam("useSource") != "false") {
            if (!$image->sourceImage) {
                if($this->getParam("useSource") != "may") {
                    throw new InvalidArgumentException("Source Image not defined.");
                }
            } else {
                $image = $image->sourceImage;
            }
        }

        if ($this->getParam("thumbWidth") == 0 || $this->getParam("thumbHeight") == 0 || !$crop) {
            $upload = $image;
        } else {
            $upload = $image->addImageVersionBySizeInPx($this->getParam("thumbLeft"), $this->getParam("thumbTop"), $this->getParam("thumbWidth"), $this->getParam("thumbHeight"));
        }

        $end = microtime(true);

        // cleanup
        if ($this->getModel()->sourceImage && $this->getModel()->id != $upload->id) {
            if ($this->getModel()->hasNoLinks()) {
                $this->getModel()->remove(true);
            }
        }

        $this->model = $upload;

        $end2 = microtime(true);

        return new JSONResponseBody(array(
            "status" => 1,
            "file" => $this->getFileResponse($upload),
            "time" => $end - $microtime,
            "time2" => $end2 - $microtime
        ));
	}

    /**
     * @return ImageUploads|null
     */
    public function getModel()
    {
        $model = parent::getModel();

        if ($model &&
            isset($this->getRequest()->post_params[$this->PostName() . "_thumbheight"],
            $this->getRequest()->post_params[$this->PostName() . "_thumbwidth"],
            $this->getRequest()->post_params[$this->PostName() . "_thumbleft"],
            $this->getRequest()->post_params[$this->PostName() . "_thumbtop"])
        && $this->getRequest()->post_params[$this->PostName() . "_thumbheight"] != -1) {
            if($model->sourceImage) {
                $model = $model->sourceImage->addImageVersionBySizeInPx(
                    $this->getRequest()->post_params[$this->PostName() . "_thumbleft"],
                    $this->getRequest()->post_params[$this->PostName() . "_thumbtop"],
                    $this->getRequest()->post_params[$this->PostName() . "_thumbwidth"],
                    $this->getRequest()->post_params[$this->PostName() . "_thumbheight"]
                );
            }
        }

        return $model;
    }

    /**
	 * @param Exception $e
	 * @return string
	 * @throws Exception
	 */
	public function handleException($e) {
		if(in_array(strtolower($this->request->getParam("action")), $this->allowed_actions)) {
			return GomaResponse::create(null, JSONResponseBody::create(array(
				"class" => get_class($e),
				"errstring" => $e->getMessage(),
				"code" => $e->getCode(),
                "error" => get_class($e)
			)))->setStatus(
				method_exists($e, "http_status") ?
					$e->http_status() :
					500
			);
		}

		return parent::handleException($e);
	}

	/**
	 * @return FormFieldRenderData
	 */
	protected function createsRenderDataClass()
	{
		return ImageFileUploadRenderData::create($this->name, $this->classname, $this->ID(), $this->divID());
	}

	/**
	 * @return float|null
	 */
	public function getAspect()
	{
		return $this->aspect;
	}

	/**
	 * @param float|null $aspect
	 * @return $this
	 */
	public function setAspect($aspect)
	{
		if(!is_null($aspect) && !RegExpUtil::isDouble($aspect)) {
			throw new InvalidArgumentException("Double or null expected.");
		}

		$this->aspect = is_null($aspect) ? $aspect : (double) $aspect;
		return $this;
	}
}
