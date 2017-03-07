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
 *
 * @version 1.0
 */
class VersionController extends Controller {

    public $template = "versions/versionsOverview.html";

    public $allowed_actions = array(
        "preview", "restore"
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
     *
     */
    public function preview() {
        $model = $this->getSingleModel();
        if($version = $model->versions(array(
            "versionid" => $this->getParam("id")
        ))->first()) {
            $controller = \ControllerResolver::instanceForModel($version);
            $response = $controller->handleRequest($this->request);

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
                return \Director::setStringToResponse($response, $view->renderWith(
                    "versions/versionHeader.html"
                ));
            }

            return $response;
        }
    }
}
