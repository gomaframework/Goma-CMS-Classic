<?php
defined("IN_GOMA") OR die();

/**
 * Homepage-Controller.
 *
 * @package Goma CMS
 *
 * @author Goma-Team
 * @copyright 2016 Goma-Team
 * @license: LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 *
 * @version 1.0
 */
class HomePageController extends SiteController
{
    /**
     * shows the homepage of this page
     *
     * @return false|string
     */
    public function index() {
        defined("HOMEPAGE") OR define("HOMEPAGE", true);

        if (isset($this->getRequest()->get_params["r"])) {
            $redirect = $this->pageService->getPageWithState(
                array("id" => $this->getRequest()->get_params["r"]),
                isset($this->request->get_params["pages_state"])
            );
            if ($redirect) {
                $query = preg_replace('/\&?r\=' . preg_quote($this->getRequest()->get_params["r"], "/") . '/', '', $_SERVER["QUERY_STRING"]);
                return GomaResponse::redirect($redirect->getURL() . "?" . $query);
            }
        }

        /** @var Page $data */
        if ($data = $this->pageService->getPageWithState(
            array("parentid" => 0),
            isset($this->request->get_params["pages_state"])
        )) {
            // fix request
            if(!$this->request->getUrlParts()) {
                $this->request->setUrlParts(array(
                    $data->path
                ));
                $this->request->shift(1);
            }

            return ControllerResolver::instanceForModel($data)->handleRequest($this->request, $this->isSubController());
        } else {
            return false;
        }
    }
}
