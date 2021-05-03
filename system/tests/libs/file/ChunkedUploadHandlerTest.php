<?php

namespace Goma\File\Test;

defined("IN_GOMA") OR die();

/**
 * Tests chunked-upload-handler.
 *
 * @package Goma
 *
 * @author Goma-Team
 * @copyright 2017 Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class ChunkedUploadHandlerTest extends \GomaUnitTest
{
    static $default_headers = array(
        "x-file-size"   => "10",
        "x-file-name"   => "test",
        "content-range" => "bytes 0 - 10"
    );

    static $default_upload = array(
        "name" => "test",
        "type"  => "text/plain",
        "tmp_name" => "/tmp/php/abc",
        "error" => UPLOAD_ERR_OK,
        "size" => 10
    );

    /**
     * tests if chunked upload handler supports files with 0 bytes in size.
     */
    public function testUpload0Bytes() {
        $request = new \Request("post", "", array(), array("file" => self::$default_upload), array_merge(self::$default_headers, array(
            "x-file-size" => 0,
            "content-range" => "bytes 0 - 0"
        )));
        $chunkedUpload = new \ChunkedUploadHandler($request, "file", "test");
        $this->assertInstanceOf(\ChunkedUploadHandler::class, $chunkedUpload);
    }
}