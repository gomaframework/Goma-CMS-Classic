<?php
namespace Goma\Controller\Versions;
use Controller;
use DataObject;
use Resources;

defined("IN_GOMA") OR die();

/**
 * Version-Controller.
 *
 * @package Goma
 *
 * @author Goma Team
 * @copyright 2017 Goma Team
 * @license: LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 *
 * @version 1.0
 */
class VersionController extends Controller {

    public $template = "versions/versionsOverview.html";

    static $url_handlers = array(
        "preview/\$id!" => "preview",
        "restore/\$id!" => "restore"
    );

    /**
     * index.
     */
    public function index() {
        if(!$this->getSingleModel()) {
            throw new \InvalidArgumentException();
        }

        $this->tplVars["versioncount"] = $this->modelInst()->versions()->count();

        return parent::index();
    }

    /**
     * @return \GomaResponse
     */
    public function restore() {
        $model = $this->getSingleModel();
        if($version = $model->versions(array(
            "versionid" => $this->getParam("id")
        ))->first()) {
            $version->writeToDB(false, false, 1);
            return \GomaResponse::redirect(
                $this->parentController()->namespace
            );
        }
    }

    /**
     * @throws \Exception
     */
    public function preview() {
        $model = $this->getSingleModel();
        if($version = $model->versions(array(
            "versionid" => $this->getParam("id")
        ))->first()) {
            $controller = \ControllerResolver::instanceForModel($version);
            return $this->serveControllerAndAddVersionInfo($controller, $model, $version);
        }
    }

    /**
     * @param Controller $controller
     * @param DataObject $model
     * @param DataObject $version
     * @return \GomaResponse|\GomaResponseBody|mixed|string
     * @throws \Exception
     */
    protected function serveControllerAndAddVersionInfo($controller, $model, $version) {
        $response = $controller->handleRequest($this->request, true);

        if(!\Director::isResponseFullPage($response)) {
            $view = new \ViewAccessableData(array(
                "content"   => \Director::getStringFromResponse($response),
                "namespace" => $this->namespace,
                "model"     => $model,
                "version"   => $version,
                "number"    => $model->versions(array(
                        "versionid" => array("<", $this->getParam("id"))
                    ))->count() + 1
            ));
            $response = \Director::setStringToResponse($response, $view->renderWith(
                "versions/versionHeader.html"
            ));
            $controller->subController = false;
            return $controller->__output($response);
        }

        return $response;
    }
}
