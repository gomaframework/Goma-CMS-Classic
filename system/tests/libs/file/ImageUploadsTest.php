<?php defined("IN_GOMA") OR die();
/**
 * Unit-Tests for ImageUploads-Class.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */


class ImageUploadsTest extends GomaUnitTest
{

    /**
     * area
     */
    static $area = "Files";

    /**
     * internal name.
     */
    public $name = "ImageUploads";

    /**
     * tests generating images.
     */
    public function testImageResizer() {

        $this->unitTestResizeCase("SetSize", 500, 375);

        /* no crop set size */
        $this->unitTestResizeCase("NoCropSetSize", 500, 375);

        /* set width */
        $this->unitTestResizeCase("SetWidth", 500, null);

        /* set height */
        $this->unitTestResizeCase("SetHeight", null, 375);

        /* no crop set width */
        $this->unitTestResizeCase("NoCropSetWidth", 500, null);

        /* no crop set height */
        $this->unitTestResizeCase("NoCropSetHeight", null, 375);

    }

    /**
     * unit test resizing for specific case.
     */
    protected function unitTestResizeCase($action, $width, $height) {

        $args = array();
        $html = "";
        $url = "";
        $urlRetina = "";
        if(isset($width)) {
            $args[] = $width;
            $html .= 'width="'.$width.'"';
            $url .= $width;
            $urlRetina .= ($width * 2);
            if(isset($height)) {
                $html = " " . $html;
                $url .= "/";
                $urlRetina .= "/";
            }
        }

        if(isset($height)) {
            $args[] = $height;
            $html = 'height="'.$height.'"' . $html;
            $url .= $height;
            $urlRetina .= ($height * 2);
        }

        $path = 'Uploads/test/img.jpg/'.$action.'/'.$url.'.jpg';
        $retinaPath = 'Uploads/test/img.jpg/'.$action.'/'.$urlRetina.'.jpg';

        /* set size */
        $this->unitTestResizer(
            "test/img.jpg",
            new Size(1000, 750),
            $action,
            $args,
            '<img src="'.$this->getExpectedPath($path).'" '.$html.' data-retina="'.$this->getExpectedPath($retinaPath).'" alt="img.jpg" style=""  />');

        $args[] = true;
        $this->unitTestResizer(
            "test/img.jpg",
            new Size(1000, 750),
            $action,
            $args,
            '<img src="'.BASE_URI . BASE_SCRIPT. $this->getExpectedPath($path) . '" '.$html.' data-retina="'.BASE_URI . BASE_SCRIPT.$this->getExpectedPath($retinaPath).'" alt="img.jpg" style=""  />');

        $this->assertTrue(file_exists(ImageUploadsController::calculatePermitFile($path)));
        $this->assertTrue(file_exists(ImageUploadsController::calculatePermitFile($retinaPath)));

        unlink(ImageUploadsController::calculatePermitFile($path));
        unlink(ImageUploadsController::calculatePermitFile($retinaPath));
    }

    protected function getExpectedPath($path) {
        if(!file_exists($path) || is_dir($path)) {
            return $path . URLEND;
        }

        return $path;
    }

    /**
     * @param string $path
     * @param Size $size
     * @param string $action
     * @param array $args
     * @param string $expected
     */
    public function unitTestResizer($path, $size, $action, $args, $expected) {
        $imageUpload = new ImageUploads(array(
            "path"      => $path,
            "width"     => $size->getWidth(),
            "height"    => $size->getHeight(),
            "filename"  => basename($path),
            "type"      => "file"
        ));

        $this->assertEqual(strtolower(call_user_func_array(array($imageUpload, $action), $args)), strtolower($expected), " Expected " . $expected . " %s");
    }

    /**
     * get best version for aspect.
     */
    public function testgetBestVersionForAspect() {
        /** @var ImageUploads $imageUpload */
        /*$imageUpload = Uploads::addFile("IMG_2008.jpg", "./system/tests/resources/IMG_2008.jpg", "test.image");

        $this->assertEqual($imageUpload->getAspect(), 4 / 3);
        $this->assertEqual($imageUpload->getBestVersionForAspect(4 / 3), $imageUpload);
        $this->assertEqual($imageUpload->getBestVersionForAspect(3 / 4), $imageUpload);

        $newUpload = $imageUpload->addImageVersionBySizeInPx(100, 100, 400, 300);
        $this->assertEqual($newUpload->thumbWidth, 50);
        $this->assertEqual($newUpload->thumbHeight, 50);

        $this->assertEqual($newUpload->thumbLeft, 25);
        $this->assertEqual($newUpload->thumbTop, 1 / 3 * 100);*/
    }

    /**
     * tests if hasMinimumImageSizeWithExactFormat is correctly determining if image has enough pixels for given format.
     *
     * Format options tested:
     * Original image: 1000 * 1000px, requirement is 1000 * 50 -> Expected: true
     * Original image: 1000 * 1000px, requirement is 1000 * 1000 -> Expected: true
     * Original image: 1000 * 1000px, requirement is 500 * 500 -> Expected: true
     * Original image: 1000 * 200px, requirement is 500 * 150 -> Expected: true
     * Original image: 1000 * 200px, requirement is 1000 * 300 -> Expected: false
     * Original image: 200 * 1000px, requirement is 150 * 500 -> Expected: true
     * Original image: 200 * 1000px, requirement is 201 * 500 -> Expected: false
     */
    public function testhasMinimumImageSizeWithExactFormat() {
        $imageUploads = new ImageUploads(array(
            "width" => 1000,
            "height" => 1000
        ));
        $this->assertTrue($imageUploads->hasMinimumImageSizeWithExactFormat(1000, 50));

        $imageUploads = new ImageUploads(array(
            "width" => 1000,
            "height" => 1000
        ));
        $this->assertTrue($imageUploads->hasMinimumImageSizeWithExactFormat(1000, 1000));

        $imageUploads = new ImageUploads(array(
            "width" => 1000,
            "height" => 1000
        ));
        $this->assertTrue($imageUploads->hasMinimumImageSizeWithExactFormat(500, 500));

        $imageUploads = new ImageUploads(array(
            "width" => 1000,
            "height" => 200
        ));
        $this->assertTrue($imageUploads->hasMinimumImageSizeWithExactFormat(500, 150));

        $imageUploads = new ImageUploads(array(
            "width" => 1000,
            "height" => 200
        ));
        $this->assertFalse($imageUploads->hasMinimumImageSizeWithExactFormat(1000, 300));

        $imageUploads = new ImageUploads(array(
            "width" => 200,
            "height" => 1000
        ));
        $this->assertTrue($imageUploads->hasMinimumImageSizeWithExactFormat(150, 500));

        $imageUploads = new ImageUploads(array(
            "width" => 200,
            "height" => 1000
        ));
        $this->assertFalse($imageUploads->hasMinimumImageSizeWithExactFormat(201, 500));
    }
}
