<?php
namespace GomaCMS\Service;
use Goma\Service\DefaultControllerService;

defined("IN_GOMA") OR die();

/**
 * Boxes-Service.
 *
 * @package Goma CMS
 *
 * @author Goma-Team
 * @copyright 2017 Goma-Team
 * @link http://goma-cms.org
 * @license: LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 *
 * @version 1.0
 */
class BoxesService extends DefaultControllerService {
    /**
     * @param \ViewAccessableData $model
     * @param array $data
     * @param int $priority
     * @param bool $forceInsert
     * @param bool $forceWrite
     * @param bool $overrideCreated
     * @return \DataObject
     */
    public function saveModel($model, $data, $priority = 1, $forceInsert = false, $forceWrite = false, $overrideCreated = false)
    {
        if(isset($data["class_name"])) {
            $model = $model->getClassAs($data["class_name"]);
        }
        return parent::saveModel($model, $data, $priority, $forceInsert, $forceWrite, $overrideCreated);
    }
}
