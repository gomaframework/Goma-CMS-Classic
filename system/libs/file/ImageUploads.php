<?php use Goma\GD\GD;
use Goma\GD\GDException;
use Goma\GD\GDImageSizeException;
use Goma\GD\ROOTImage;

defined('IN_GOMA') OR die();

/**
 *
 * @package 	goma framework
 * @link 		http://goma-cms.org
 * @license: 	LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 * @author 	Goma-Team
 * @Version 	1.5
 *
 * @property int width
 * @property int height
 * @property float thumbWidth
 * @property float thumbHeight
 * @property float thumbLeft
 * @property float thumbTop
 * @property ImageUploads|null sourceImage
 * @property int sourceImageId
 * @property bool realizedSize
 *
 * @method HasMany_DataObjectSet imageVersions($filter = null, $sort = null, $limit = null)
 *
 * last modified: 07.06.2015
 */
class ImageUploads extends Uploads {
    /**
     * add some db-fields
     * inherits fields from Uploads
     */
    static $db = array(
        "width"				=> "int(5)",
        "height"			=> "int(5)",
        "thumbLeft"			=> "float(8, 5)",
        "thumbTop"			=> "float(8, 5)",
        "thumbWidth"		=> "float(8, 5)",
        "thumbHeight"		=> "float(8, 5)",
        "realizedSize"      => "int(1)"
    );

    /**
     * add index for aspect-query.
     *
     * @var array
     */
    static $index = array(
        "aspectQuery" => array(
            "name"      => "aspect",
            "fields"    => "thumbWidth,thumbHeight,width,height",
            "type"      => "INDEX"
        )
    );

    /**
     * extensions in this files are by default handled by this class
     *
     *@name file_extensions
     *@access public
     */
    static $file_extensions = array(
        "png",
        "jpeg",
        "jpg",
        "gif",
        "bmp"
    );

    /**
     * some defaults
     */
    static $default = array(
        "thumbLeft"		=> 50,
        "thumbTop"		=> 50,
        "thumbWidth"	=> 100,
        "thumbHeight"	=> 100
    );

    /**
     * @var array
     */
    static $has_many = array(
        "imageVersions" => array(
            DataObject::RELATION_TARGET => ImageUploads::class,
            DataObject::RELATION_INVERSE => "sourceImage"
        )
    );

    /**
     * @var array
     */
    static $has_one = array(
        "sourceImage"   => ImageUploads::class
    );

    /**
     * @var bool
     */
    static $autoImageResize = true;

    /**
     * @var bool
     */
    static $useCommandLineforAutoResize = true;

    /**
     * @var int
     */
    static $destinationSize = 3000;

    /**
     * returns the raw-path
     *
     * @name raw
     * @access public
     * @return string
     */
    public function raw() {
        return $this->path;
    }

    /**
     * to string
     *
     * @name __toString
     * @access public
     * @return null|string
     */
    public function __toString() {
        if(preg_match("/\.(jpg|jpeg|png|gif|bmp)$/i", $this->filename)) {
            $file = $this->raw().'/index'.substr($this->filename, strrpos($this->filename, "."));


            if(substr($file, 0, strlen("index.php/")) != "index.php/") {
                if(!file_exists($file)) {
                    FileSystem::requireDir(dirname($file));
                    FileSystem::write(ImageUploadsController::calculatePermitFile($file), 1);
                }
            } else {
                if(file_exists(substr($file, strlen("index.php/")))) {
                    $file = substr($file, strlen("index.php/"));
                } else {
                    FileSystem::requireDir(substr(dirname($file), strlen("index.php/")));
                    FileSystem::write(ImageUploadsController::calculatePermitFile(substr(dirname($file), strlen("index.php/"))), 1);
                }
            }

            return '<img src="'.$file.'" height="'.$this->height.'" width="'.$this->width.'" alt="'.$this->filename.'" />';
        } else
            return '<a href="'.$this->raw().'">' . $this->filename . '</a>';
    }

    /**
     * returns the path to the icon of the file
     *
     * @param int $size
     * @param bool $retina
     * @return string
     */
    public function getIcon($size = 128, $retina = false) {
        $ext = substr($this->filename, strrpos($this->filename, "."));
        if ($this->width() >= $size) {
            $realSize = $retina ? $size * 2 : $size;
            $icon = $this->path . "/setSize/" . $realSize . "/" . $realSize . $ext;
        } else {
            switch ($size) {
                case 16:
                case 32:
                case 64:
                    if ($retina) {
                        return "system/images/icons/goma" . $size . "/image@2x.png";
                    }
                    return "system/images/icons/goma" . $size . "/image.png";
                    break;
            }
        }

        if (isset($icon)) {
            $this->manageURL($icon);
            return $icon;
        }

        return "system/images/icons/goma/128x128/image.png";
    }

    /**
     * authenticates a specific url and removes cache-files if necessary
     *
     * @name manageURL
     * @return string
     */
    public function manageURL($file) {
        $file = $this->removePrefix($file, "index.php/");
        $file = $this->removePrefix($file, "./index.php/");

        FileSystem::requireDir(dirname($file));
        FileSystem::write(ImageUploadsController::calculatePermitFile($file), 1);
        if(file_exists($file) && filemtime($file) < NOW - Uploads::$cache_life_time) {
            @unlink($file);
        }
        return $file;
    }

    /**
     * remove prefixes from a path.
     */
    protected function removePrefix($file, $prefix) {
        if(substr($file, 0, strlen($prefix)) == $prefix) {
            return substr($file, strlen($prefix));
        }

        return $file;
    }

    /**
     * remove cache files after remove.
     */
    public function onAfterRemove()
    {
        parent::onAfterRemove();

        if(is_dir(ROOT . $this->path)) {
            FileSystem::delete(ROOT . $this->path);
        }

        foreach($this->imageVersions() as $childImage) {
            $this->imageVersions()->removeFromSet($childImage);
        }

        $this->imageVersions()->commitStaging(false, true);
    }

    /**
     * returns url for specific scenario.
     *
     * @param int $desiredWidth -1 for no desired with
     * @param int $desiredHeight -1 for no desired height
     * @param bool $noCrop
     * @return string
     */
    public function getResizeUrl($desiredWidth, $desiredHeight, $noCrop = false) {
        if(!$this->path) {
            return "";
        }

        // get action
        $action = ($noCrop === true) ? "NoCrop" : "";
        if((!isset($desiredWidth) || $desiredWidth == -1) && ($desiredHeight == -1 || !isset($desiredHeight))) {
            throw new InvalidArgumentException("At least one of the size-parameters should be set.");
        } else if(!isset($desiredHeight) || $desiredHeight == -1) {
            $action .= "SetWidth";
        } else if(!isset($desiredWidth) || $desiredWidth == -1) {
            $action .= "SetHeight";
        } else {
            $action .= "SetSize";
        }

        // get appendix
        $file = $this->path . "/" . $action . "/";
        if($desiredWidth != -1) {
            $file .= $desiredWidth;
        }

        if($desiredHeight != -1) {
            if($desiredWidth != -1) {
                $file .= "/";
            }

            $file .= $desiredHeight;
        }

        // add extension
        $file .= substr($this->filename, strrpos($this->filename, "."));

        // enable it
        $this->manageURL($file);

        return $this->checkForBase($file);
    }

    /**
     * sets the height
     *
     * @param int $height
     * @param bool $absolute
     * @param string $html
     * @param string $style
     * @return string
     * @internal param $setHeight
     * @access public
     */
    public function setHeight($height, $absolute = false, $html = "", $style = "") {
        if(!$this->path)
            return "";

        $file = $this->getResizeUrl(-1, $height, false);
        $fileRetina = $this->getResizeUrl(-1, $height * 2, false);

        if($absolute === true) {
            $file = BASE_URI . $file;
            $fileRetina = BASE_URI . $fileRetina;
        }

        return '<img src="' . $file . '" height="'.$height.'" data-retina="' . $fileRetina . '" alt="'.$this->filename.'" style="'.$style.'" '.$html.' />';
    }

    /**
     * sets the width
     *
     * @param $width
     * @param bool $absolute
     * @param string $html
     * @param string $style
     * @return string
     * @internal param $setWidth
     * @access public
     */
    public function setWidth($width, $absolute = false, $html = "", $style = "") {
        if(!$this->path)
            return "";

        $file = $this->getResizeUrl($width, -1, false);
        $fileRetina = $this->getResizeUrl($width * 2, -1, false);

        if($absolute === true) {
            $file = BASE_URI . $file;
            $fileRetina = BASE_URI . $fileRetina;
        }

        return '<img src="' . $file . '" width="'.$width.'" data-retina="' . $fileRetina . '" alt="'.$this->filename.'" style="'.$style.'" '.$html.' />';
    }

    /**
     * sets the Size
     *
     * @param $width
     * @param $height
     * @param bool $absolute
     * @param string $html
     * @param string $style
     * @return string
     * @internal param $setSize
     * @access public
     */
    public function setSize($width, $height, $absolute = false, $html = "", $style = "") {
        if(!$this->path)
            return "";

        $file = $this->getResizeUrl($width, $height, false);
        $fileRetina = $this->getResizeUrl($width * 2, $height * 2, false);

        if($absolute === true) {
            $file = BASE_URI . $file;
            $fileRetina = BASE_URI . $fileRetina;
        }

        return '<img src="' . $file .'" height="'.$height.'" width="'.$width.'" data-retina="' . $fileRetina .'" alt="'.$this->filename.'" style="'.$style.'" '.$html.' />';
    }

    /**
     * sets the size on the original,  so not the thumbnail we saved
     *
     * @param $width
     * @param $height
     * @param bool $absolute
     * @param string $html
     * @param string $style
     * @return string
     * @internal param $noCropSetSize
     * @access public
     */
    public function noCropSetSize($width, $height, $absolute = false, $html = "", $style = "") {
        if(!$this->path)
            return "";

        $file = $this->getResizeUrl($width, $height, true);
        $fileRetina = $this->getResizeUrl($width * 2, $height * 2, true);

        if($absolute === true) {
            $file = BASE_URI . $file;
            $fileRetina = BASE_URI . $fileRetina;
        }

        return '<img src="' . $file .'" height="'.$height.'" width="'.$width.'" data-retina="' . $fileRetina .'" alt="'.$this->filename.'" style="'.$style.'" '.$html.' />';
    }

    /**
     * sets the width on the original, so not the thumbnail we saved
     *
     * @param $width
     * @param bool $absolute
     * @param string $html
     * @param string $style
     * @return string
     * @internal param $noCropSetWidth
     * @access public
     */
    public function noCropSetWidth($width, $absolute = false, $html = "", $style = "") {
        if(!$this->path)
            return "";

        $file = $this->getResizeUrl($width, -1, true);
        $fileRetina = $this->getResizeUrl($width * 2, -1, true);

        if($absolute === true) {
            $file = BASE_URI . $file;
            $fileRetina = BASE_URI . $fileRetina;
        }

        return '<img src="' . $file . '" width="'.$width.'" data-retina="' . $fileRetina . '" alt="'.$this->filename.'" style="'.$style.'" '.$html.' />';
    }

    /**
     * sets the height on the original, so not the thumbnail we saved
     *
     * @param $height
     * @param bool $absolute
     * @param string $html
     * @param string $style
     * @return string
     */
    public function noCropSetHeight($height, $absolute = false, $html = "", $style = "") {
        if(!$this->path)
            return "";

        $file = $this->getResizeUrl(-1, $height, true);
        $fileRetina = $this->getResizeUrl(-1, $height * 2, true);

        if($absolute === true) {
            $file = BASE_URI . $file;
            $fileRetina = BASE_URI . $fileRetina;
        }

        return '<img src="' . $file . '" height="'.$height.'" data-retina="' . $fileRetina . '" alt="'.$this->filename.'" style="'.$style.'" '.$html.' />';
    }

    /**
     * helper for width() and height()
     *
     * @param String $size
     * @return int
     * @throws FileNotFoundException
     */
    protected function getSize($size) {
        if(preg_match('/^[0-9]+$/', $this->fieldGET($size)) && $this->fieldGET($size) != 0) {
            return $this->fieldGet($size);
        }

        if(!$this->realfile) {
            throw new FileNotFoundException("File for ImageUploads was not found.");
        }

        $image = new RootImage($this->realfile);
        $this->setField($size, $image->$size);
        return $image->$size;
    }

    /**
     * adds an image-version by size in pixels.
     * @param int $left
     * @param int $top
     * @param int $width
     * @param int $height
     * @param bool $write
     * @return ImageUploads
     */
    public function addImageVersionBySizeInPx($left, $top, $width, $height, $write = true) {
        if($this->sourceImage) {
            throw new InvalidArgumentException("Transitive source-images are not allowed.");
        }

        $imageUploads = $this->duplicate();
        $imageUploads->thumbHeight = min($height / $imageUploads->height * 100, 100);
        $imageUploads->thumbWidth = min($width / $imageUploads->width * 100, 100);

        $leftPercentage = ($this->width - $width) > 1 ? min($left / ($this->width - $width) * 100, 100) : 50;
        $imageUploads->thumbLeft = $leftPercentage;

        $topPercentage = ($this->height - $height) > 1 ? min($top / ($this->height - $height) * 100, 100) : 50;
        $imageUploads->thumbTop = $topPercentage;

        $imageUploads->sourceImage = $this;

        // check for existing
        if($file = DataObject::get_one(ImageUploads::class, array(
            "thumbHeight" => $imageUploads->thumbHeight,
            "thumbWidth" => $imageUploads->thumbWidth,
            "thumbTop"  => $imageUploads->thumbTop,
            "thumbLeft" => $imageUploads->thumbLeft,
            "sourceImageId" => $this->id
        ))) {
            return $file;
        }
        $imageUploads->path = $imageUploads->buildPath($imageUploads->collection, $imageUploads->filename);

        if($this->id != 0 && $write) {
            $imageUploads->writeToDB(true, true);
        }

        $this->data["imageversions"] = null;

        return $imageUploads;
    }

    /**
     * @param ModelWriter $modelWriter
     * @throws GDImageSizeException
     * @throws MySQLException
     */
    public function onBeforeWrite($modelWriter)
    {
        parent::onBeforeWrite($modelWriter);

        // assign $this->width and $this->height
        $this->width = $this->getSize("width");
        $this->height = $this->getSize("height");

        $gd = new GD($this->realfile);
        try {
            $tmpFile = ROOT . CACHE_DIRECTORY . "/" . md5($this->realfile). "_fixed2";
            $usage = memory_get_usage();
            $gd->fixRotationInPlace()->toFile($tmpFile);

            if (self::$autoImageResize && self::$destinationSize < max($gd->width, $gd->height)) {
                $gd->autoImageResizeInPlace(self::$destinationSize, self::$useCommandLineforAutoResize)->toFile($tmpFile);
            }

            $gd->gd();
            if (memory_get_usage() - $usage > 0.5 * getMemoryLimit()) {
                if (DataObject::count(Uploads::class, array("realfile" => $this->realfile)) == 0) {
                    unlink($this->realfile);
                    unlink($tmpFile);
                }

                throw new GDImageSizeException(lang("imageTooBig"));
            }
            $gd->destroy();

            if(md5_file($tmpFile) != $this->md5) {
                /** @var Uploads $upload */
                $upload = DataObject::get_one(Uploads::class, array(
                    "md5" => md5_file($tmpFile)
                ));
                if ($upload && file_exists($upload->realfile)
                ) {
                    $this->realfile = $upload->realfile;
                } else {
                    $i = 0;
                    while (file_exists($this->realfile)) {
                        $this->realfile = substr($this->realfile, 0, strrpos($this->realfile, ".")) . "_" . $i .
                            substr($this->realfile, strrpos($this->realfile, "."));

                    }
                    copy($tmpFile, $this->realfile);
                }
                unlink($tmpFile);

                $this->width = $gd->width;
                $this->height = $gd->height;
                $this->md5 = md5_file($this->realfile);
            }
        } finally {
            $gd->destroy();
        }
    }

    /**
     * gets best version of file for given aspect-ratio.
     * aspect is width / height.
     *
     * @param float $aspect
     * @return ImageUploads
     */
    public function getBestVersionForAspect($aspect) {
        // if aspect is on a precision of 5% correct we use this.
        if(round($aspect * 20) / 20 == round($this->getAspect() * 20) / 20) {
            return $this;
        }

        // query for best fitting aspect.
        /** @var ImageUploads|null $aspectVersion */
        $aspectVersion = $this->imageVersions(
            null, " ABS((width * thumbWidth / (height * thumbHeight)) - $aspect) ASC ", 1
        )->first();

        // check if is really the better version.
        if(isset($aspectVersion) && abs($aspectVersion->getAspect() - $aspect) < ($this->getAspect() - $aspect)) {
            return $aspectVersion;
        }

        return $this;
    }

    /**
     * calculates aspect for current ImageUploads instance.
     */
    public function getAspect() {
        return ($this->width() * $this->thumbWidth) / ($this->height() * $this->thumbHeight);
    }

    /**
     * returns width
     * @return int
     * @throws Exception
     */
    public function width() {
        try {
            return $this->getSize("width") * $this->thumbWidth / 100;
        } catch(Exception $e) {
            if ($e instanceof FileException OR $e instanceof GDException) {
                return -1;
            } else {
                // Keep throwing it.
                throw $e;
            }
        }
    }

    /**
     * returns height
     * @return int
     * @throws Exception
     */
    public function height() {
        try {
            return $this->getSize("height") * $this->thumbHeight / 100;
        } catch(Exception $e) {
            if ($e instanceof FileException OR $e instanceof GDException) {
                return -1;
            } else {
                // Keep throwing it.
                throw $e;
            }
        }
    }

    /**
     * calculates if image in given format can be generated and this image
     * has enough pixels to generate that format without upscaling.
     * Examples:
     *
     * Original image: 1000 * 1000px, requirement is 1000 * 50 -> true
     * Original image: 1000 * 200px, requirement is 1000 * 300 -> false
     * Original image: 1000 * 200px, requirement is 500 * 150 -> true
     * Original image: 200 * 1000px, requirement is 150 * 500 -> true
     * Original image: 200 * 1000px, requirement is 201 * 500 -> false
     *
     * @param int $width
     * @param int $height
     * @return bool
     */
    public function hasMinimumImageSizeWithExactFormat($width, $height)
    {
        if($width > $this->width() || $height > $this->height()) {
            return false;
        }

        $pot = $width / $this->width();
        $heightAfterCalculate = $this->height() * $pot;

        $potHeight = $height / $this->height();
        $widthAfterCalculate = $this->width() * $potHeight;

        return ($heightAfterCalculate >= $height || $widthAfterCalculate >= $width);
    }
}
