<?php
defined("IN_GOMA") OR die();

/**
 * Render-Info for ImageUpload-Field.
 *
 * @package Goma\Form
 *
 * @author 	Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @version 1.0
 *
 * @property ImageUploads $upload
 */
class ImageFileUploadRenderData extends FileUploadRenderData {
    /**
     * @var double|null
     */
    protected $aspect = null;

    /**
     * @param null|ImageUploads $upload
     * @return $this
     */
    public function setUpload($upload)
    {
        if(is_a($upload, "ImageUploads") || $upload == null) {
            return parent::setUpload($upload);
        } else {
            throw new InvalidArgumentException("\$upload must be typeof ImageUploads.");
        }
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
        $this->aspect = $aspect;
        return $this;
    }

    /**
     * @param bool $includeRendered
     * @param bool $includeChildren
     * @return array
     */
    public function ToRestArray($includeRendered = false, $includeChildren = true) {
        $data = parent::ToRestArray($includeRendered, $includeChildren);

        if($this->upload) {
            $data["upload"]["thumbLeft"] = $this->upload->thumbLeft;
            $data["upload"]["thumbTop"] = $this->upload->thumbTop;
            $data["upload"]["thumbWidth"] = $this->upload->thumbWidth;
            $data["upload"]["thumbHeight"] = $this->upload->thumbHeight;
            $data["upload"]["orgImageSize"] = array("width" => $this->upload->width, "height" => $this->upload->height);

            if ($this->upload->sourceImage) {
                $data["upload"]["sourceImage"] = $this->upload->sourceImage->path;
                $data["upload"]["sourceImageRP"] = $this->upload->sourceImage->fieldGet("path");
                $data["upload"]["sourceImageHeight400"] = $this->upload->sourceImage->getResizeUrl(null, 400);
                $data["upload"]["orgImageSize"] = array("width" => $this->upload->sourceImage->width, "height" => $this->upload->sourceImage->height);
            } else {
                $data["upload"]["sourceImageHeight400"] = $this->upload->getResizeUrl(null, 400);
            }

            $width = $this->aspect !== null ? 400 * $this->aspect : null;
            $data["upload"]["imageHeight400"] = $this->upload->getResizeUrl($width, 400);
        }

        $data["aspect"] = $this->aspect;

        return $data;
    }
}
