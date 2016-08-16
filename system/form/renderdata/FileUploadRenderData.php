<?php
defined("IN_GOMA") OR die();

/**
 * Render-Info for Fileupload-Field.
 *
 * @package Goma\Form
 *
 * @author 	Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @version 1.0
 */
class FileUploadRenderData extends FormFieldRenderData {
    /**
     * uploads object.
     *
     * @var Uploads|null
     */
    protected $upload;

    /**
     * default icon.
     *
     * @var string
     */
    protected $defaultIcon;

    /**
     * @var bool
     */
    protected $showDeleteButton;

    /**
     * @var bool
     */
    protected $showManagePath;

    /**
     * @return null|Uploads
     */
    public function getUpload()
    {
        return $this->upload;
    }

    /**
     * @param null|Uploads $upload
     * @return $this
     */
    public function setUpload($upload)
    {
        $this->upload = $upload;
        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultIcon()
    {
        return $this->defaultIcon;
    }

    /**
     * @param string $defaultIcon
     * @return $this
     */
    public function setDefaultIcon($defaultIcon)
    {
        $this->defaultIcon = $defaultIcon;
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

    /**
     * @return boolean
     */
    public function isShowManagePath()
    {
        return $this->showManagePath;
    }

    /**
     * @param boolean $showManagePath
     * @return $this
     */
    public function setShowManagePath($showManagePath)
    {
        $this->showManagePath = $showManagePath;
        return $this;
    }

    /**
     * @param bool $includeRendered
     * @param bool $includeChildren
     * @return array
     */
    public function ToRestArray($includeRendered = false, $includeChildren = true)
    {
        $data = parent::ToRestArray($includeRendered, $includeChildren);

        if(isset($this->upload)) {
            $data["upload"] = array(
                "name" => $this->upload->filename,
                "realpath" => $this->upload->fieldGet("path"),
                "icon16" => $this->upload->getIcon(16),
                "icon16_2x" => $this->upload->getIcon(16, true),
                "path" => $this->upload->path,
                "id" => $this->upload->id,
                "icon128" => $this->upload->getIcon(128),
                "icon128_2x" => $this->upload->getIcon(128, true),
                "icon" => $this->upload->getIcon(),
                "hash"  => $this->upload->md5
            );
        } else {
            $data["upload"] = null;
        }

        if($this->showManagePath) {
            $data["upload"]["managePath"] = isset($this->upload) ? $this->upload->getManagePath() : null;
        }

        $data["showDeleteButton"] = $this->showDeleteButton;
        $data["defaultIcon"] = $this->defaultIcon;

        return $data;
    }
}
