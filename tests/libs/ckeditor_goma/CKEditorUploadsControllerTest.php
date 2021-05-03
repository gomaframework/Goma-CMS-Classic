<?php

namespace Goma\Test;

use Director;

defined("IN_GOMA") or die();

/**
 * Tests class CKEditorUploadsController.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class CKEditorUploadsControllerTest extends \GomaUnitTest
{
    /**
     * tests CKEditorUploads Controller if it responds with script if upload fails.
     *
     * 1. Call CKEditorUploadsController::getUploadToken, set to $accessToken
     * 2. Create Request $request, url is "ck_uploader", method is post
     * 3. Set $request->get_params["accesstoken"] to $accessToken
     * 4. Set $request->get_params["CKEditorFuncNum"] to 1
     *
     * 5. Create CKEditorUploadsController $controller
     * 6. Call $controller->handleRequest($request), set result to $response
     * 7. Assert that string response contains <script
     */
    public function testCKEditorCKFileUploadWithoutFileShowError() {
        $accessToken = \CKEditorUploadsController::getUploadToken();
        $request = new \Request("post", "ck_uploader");
        $request->get_params["accesstoken"] = $accessToken;
        $request->get_params["ckeditorfuncnum"] = 1;

        $controller = new \CKEditorUploadsController();
        $response = $controller->handleRequest($request);
        $this->assertRegExp("/\<script/", \Director::getStringFromResponse($response));

    }

    /**
     * tests CKEditorUploads Controller if it responds with script if upload fails if request directed via Director.
     *
     * 1. Call CKEditorUploadsController::getUploadToken, set to $accessToken
     * 2. Create Request $request, url is "ck_uploader", method is post
     * 3. Set $request->get_params["accesstoken"] to $accessToken
     * 4. Set $request->get_params["CKEditorFuncNum"] to 1
     *
     * 5. Call Director::directRequest($request, $serve = false), set result to $response
     * 6. Assert that string response contains <script
     */
    public function testCKEditorCKFileUploadWithoutFileShowErrorViaDirector() {
        $accessToken = \CKEditorUploadsController::getUploadToken();
        $request = new \Request("post", "system/ck_uploader", array(), array(), array(), "goma-cms.org", 80);
        $request->get_params["accesstoken"] = $accessToken;
        $request->get_params["ckeditorfuncnum"] = 1;

        $response = Director::directRequest($request, false);
        $this->assertRegExp("/\<script/", $response->render());

    }
}
