<?php
namespace Goma\Test;

defined("IN_GOMA") OR die();
/**
 * Unit-Tests for ImageUploadField.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class ImageUploadFieldTest extends \GomaUnitTest
{
    /**
     * tests init with form.
     */
    public function testInit() {
        $field = new \ImageUploadField("image", "Bild");
        $this->assertInstanceOf(\ImageUploadField::class, $field);
    }

    /**
     * tests image upload field with model.
     */
    public function testinitWithModel() {
        try {
            $imageModel = \Uploads::addFile("img_1000_480.png", "system/tests/resources/img_1000_480.png", "test", null, false);
            $this->assertInstanceOf(\ImageUploads::class, $imageModel);

            $field = new \ImageUploadField("image", "Bild", null, $imageModel);
            $this->assertInstanceOf(\ImageUploads::class, $field->getModel());
        } finally {
            if($imageModel) {
                $imageModel->remove(true);
            }
        }
    }

    /**
     * tests image upload field with model.
     * tests if creating new version is working.
     */
    public function testsetCropInfoWithoutSource() {
        try {
            $imageModel = \Uploads::addFile("img_1000_480.png", "system/tests/resources/img_1000_480.png", "test", null, false);

            $field = new \ImageUploadField("image", "Bild", null, $imageModel);
            $request = new \Request("post", "setCropInfo", array(), array("thumbleft" => 100, "thumbtop" => 100, "thumbheight" => 100, "thumbwidth" => 100, "usesource" => "false"));
            /** @var \JSONResponseBody $response */
            $response = $field->handleRequest($request);

            $this->assertInstanceOf(\JSONResponseBody::class, $response);
            $this->assertEqual(1, $response->getBody()["status"]);
            $this->assertTrue(is_array($response->getBody()["file"]));
        } finally {
            if($imageModel) {
                $imageModel->remove(true);
            }
        }
    }

    /**
     * tests image upload field with transitive model, where usesource is set to false.
     * This should cause an InvalidArgumentException.
     */
    public function testsetCropInfoWithoutSourceTransitive() {
        try {
            /** @var \ImageUploads $imageModel */
            $imageModel = \Uploads::addFile("img_1000_480.png", "system/tests/resources/img_1000_480.png", "test", null, false);

            $version = $imageModel->addImageVersionBySizeInPx(100, 100, 100, 100);

            $field = new \ImageUploadField("image", "Bild", null, $version);
            $request = new \Request("post", "setCropInfo", array(), array("thumbleft" => 100, "thumbtop" => 100, "thumbheight" => 100, "thumbwidth" => 100, "usesource" => "false"));
            /** @var \JSONResponseBody $response */
            $response = $field->handleRequest($request);

            $this->assertInstanceOf(\JSONResponseBody::class, $response->getBody());
            $body = $response->getBody()->getBody();
            $this->assertTrue(isset($body["error"]));
            $this->assertEqual(\InvalidArgumentException::class, $response->getBody()->getBody()["class"]);
        } finally {
            if($imageModel) {
                $imageModel->remove(true);
            }
        }
    }

    /**
    * tests image upload field with transitive model, where usesource is set to true.
    * This should cause an InvalidArgumentException.
    */
    public function testsetCropInfoWithoutSourceUsingSource() {
        try {
            /** @var \ImageUploads $imageModel */
            $imageModel = \Uploads::addFile("img_1000_480.png", "system/tests/resources/img_1000_480.png", "test", null, false);

            $field = new \ImageUploadField("image", "Bild", null, $imageModel);
            $request = new \Request("post", "setCropInfo", array(), array("thumbleft" => 100, "thumbtop" => 100, "thumbheight" => 100, "thumbwidth" => 100, "usesource" => "true"));
            /** @var \GomaResponse $response */
            $response = $field->handleRequest($request);

            $this->assertInstanceOf(\JSONResponseBody::class, $response->getBody());
            $body = $response->getBody()->getBody();
            $this->assertTrue(isset($body["error"]));
            $this->assertEqual(\InvalidArgumentException::class, $response->getBody()->getBody()["class"]);
        } finally {
            if($imageModel) {
                $imageModel->remove(true);
            }
        }
    }

    /**
     * tests image upload field with transitive model, where usesource is set to may.
     * This should create a new version.
     */
    public function testsetCropInfoWithoutSourceUsingSourceMay() {
        try {
            /** @var \ImageUploads $imageModel */
            $imageModel = \Uploads::addFile("img_1000_480.png", "system/tests/resources/img_1000_480.png", "test", null, false);

            $field = new \ImageUploadField("image", "Bild", null, $imageModel);
            $request = new \Request("post", "setCropInfo", array(), array("thumbleft" => 100, "thumbtop" => 100, "thumbheight" => 100, "thumbwidth" => 100, "usesource" => "may"));
            /** @var \JSONResponseBody $response */
            $response = $field->handleRequest($request);

            $this->assertInstanceOf(\JSONResponseBody::class, $response);
            $this->assertEqual(1, $response->getBody()["status"]);
            $this->assertTrue(is_array($response->getBody()["file"]));
        } finally {
            if($imageModel) {
                $imageModel->remove(true);
            }
        }
    }

    /**
     * tests image upload field with transitive model, where usesource is set to true.
     * This should create a new version.
     */
    public function testsetCropInfoWithSource() {
        try {
            /** @var \ImageUploads $imageModel */
            $imageModel = \Uploads::addFile("img_1000_480.png", "system/tests/resources/img_1000_480.png", "test", null, false);

            $version = $imageModel->addImageVersionBySizeInPx(100, 100, 100, 100);

            $field = new \ImageUploadField("image", "Bild", null, $version);
            $request = new \Request("post", "setCropInfo", array(), array("thumbleft" => 200, "thumbtop" => 200, "thumbheight" => 100, "thumbwidth" => 100, "usesource" => "true"));
            /** @var \JSONResponseBody $response */
            $response = $field->handleRequest($request);

            $this->assertInstanceOf(\JSONResponseBody::class, $response);
            $this->assertEqual(1, $response->getBody()["status"]);
            $this->assertTrue(is_array($response->getBody()["file"]));
        } finally {
            if($imageModel) {
                $imageModel->remove(true);
            }
        }
    }

    /**
     * tests image upload field with transitive model, where usesource is set to true.
     * This should create a new version.
     */
    public function testsetCropInfoWithSourceHeightZero() {
        try {
            /** @var \ImageUploads $imageModel */
            $imageModel = \Uploads::addFile("img_1000_480.png", "system/tests/resources/img_1000_480.png", "test", null, false);

            $version = $imageModel->addImageVersionBySizeInPx(100, 100, 100, 100);

            $field = new \ImageUploadField("image", "Bild", null, $version);
            $request = new \Request("post", "setCropInfo", array(), array("thumbleft" => 200, "thumbtop" => 200, "thumbheight" => 0, "thumbwidth" => 100, "usesource" => "true"));
            /** @var \JSONResponseBody $response */
            $response = $field->handleRequest($request);

            $this->assertInstanceOf(\JSONResponseBody::class, $response);
            $this->assertEqual(1, $response->getBody()["status"]);
            $this->assertTrue(is_array($response->getBody()["file"]));
        } finally {
            if($imageModel) {
                $imageModel->remove(true);
            }
        }
    }

    /**
     * tests image upload field with transitive model, where usesource is set to true.
     * This should create a new version.
     */
    public function testsetCropInfoWithSourceWidthZero() {
        try {
            /** @var \ImageUploads $imageModel */
            $imageModel = \Uploads::addFile("img_1000_480.png", "system/tests/resources/img_1000_480.png", "test", null, false);

            $version = $imageModel->addImageVersionBySizeInPx(100, 100, 100, 100);

            $field = new \ImageUploadField("image", "Bild", null, $version);
            $request = new \Request("post", "setCropInfo", array(), array("thumbleft" => 200, "thumbtop" => 200, "thumbheight" => 10, "thumbwidth" => 0, "usesource" => "true"));
            /** @var \JSONResponseBody $response */
            $response = $field->handleRequest($request);

            $this->assertInstanceOf(\JSONResponseBody::class, $response);
            $this->assertEqual(1, $response->getBody()["status"]);
            $this->assertTrue(is_array($response->getBody()["file"]));
        } finally {
            if($imageModel) {
                $imageModel->remove(true);
            }
        }
    }

    /**
     * tests if setCrop is working via GET.
     */
    public function testSetCropInfoViaGet() {
        $field = new \ImageUploadField("image", "Bild", new \ImageUploads());
        $request = new \Request("get", "setCropInfo", array(), array("thumbleft" => 200, "thumbtop" => 200, "thumbheight" => 10, "thumbwidth" => 0, "usesource" => "true"));
        /** @var \GomaResponse $response */
        $response = $field->handleRequest($request);

        $this->assertInstanceOf(\JSONResponseBody::class, $response->getBody());
        $body = $response->getBody()->getBody();
        $this->assertTrue(isset($body["error"]));
        $this->assertEqual(\BadRequestException::class, $response->getBody()->getBody()["class"]);
    }

    /**
     * tests if setCrop is working without image.
     */
    public function testSetCropInfoWithoutImage() {
        $field = new \ImageUploadField("image", "Bild", null);
        $request = new \Request("post", "setCropInfo", array(), array("thumbleft" => 200, "thumbtop" => 200, "thumbheight" => 10, "thumbwidth" => 0, "usesource" => "true"));
        /** @var \GomaResponse $response */
        $response = $field->handleRequest($request);

        $this->assertInstanceOf(\JSONResponseBody::class, $response->getBody());
        $body = $response->getBody()->getBody();
        $this->assertTrue(isset($body["error"]));
        $this->assertEqual(\InvalidArgumentException::class, $response->getBody()->getBody()["class"]);
    }
}
