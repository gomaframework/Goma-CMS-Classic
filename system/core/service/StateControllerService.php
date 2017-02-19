<?php
namespace Goma\Service;

use DataObjectSet;

defined("IN_GOMA") OR die();

/**
 * Controller-Service.
 *
 * @package Goma\Service
 *
 * @license        GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author Goma-Team
 * @copyright 2017 Goma-Team
 *
 * @version 1.0
 */
class StateControllerService extends DefaultControllerService {
    /**
     * record.
     * @param \IDataSet|\ViewAccessableData $record
     */
    public function decorateRecord(&$record)
    {
        parent::decorateRecord($record);

        if(is_a($record, DataObjectSet::class)) {
            /** @var DataObjectSet $record */
            if (!$record->getVersion()) $record->setVersion(\DataObject::VERSION_STATE);
        }
    }
}
