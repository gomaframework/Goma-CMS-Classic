<?php

namespace Goma\Test;

use Goma\Controller\Versions\VersionController;

defined("IN_GOMA") or die();

/**
 * Tests VersionController class.
 *
 * @package Goma
 *
 * @author Goma Team
 * @copyright 2017 Goma Team
 * @license: LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 *
 * @version 1.0
 */
class VersionControllerTest extends GomaUnitTestWithAdmin
{
    /**
     * tests if output has information about version but preserves __output of Controller.
     */
    public function testOutputPreservesController__output() {
        $request = new \Request("get", "");
        $versionController = new VersionController();
        $versionController->setRequest($request);
        $reflectionMethod = new \ReflectionMethod(VersionController::class, "serveControllerAndAddVersionInfo");
        $reflectionMethod->setAccessible(true);
        $content = $reflectionMethod->invoke($versionController, new TestVersionController(), $this->adminUser, $this->adminUser);

        $this->assertRegExp('/^\<div class="lala"\>.*\<div class="version-header"\>.*blub/s', $content);
    }
}

class TestVersionController extends \Controller {
    /**
     * @param \GomaResponse|string $content
     * @return \GomaResponse|\GomaResponseBody|mixed|string
     */
    public function __output($content)
    {
        if($this->isManagingController($content)) {
            return \Director::setStringToResponse($content, '<div class="lala">'.\Director::getStringFromResponse($content).'</div>');
        }

        return $content;
    }

    public function index()
    {
        return "blub";
    }
}
