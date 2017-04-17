<?php
namespace Goma\Service;

use DataObject;
use gObject;
use IDataSet;
use InvalidArgumentException;
use ViewAccessableData;

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
class DefaultControllerService extends gObject {
    /**
     * repository.
     *
     * @var \IModelRepository
     */
    protected $repository;

    /**
     * @var \IDataSet|\ViewAccessableData
     */
    protected $model;

    /**
     * @var array
     */
    protected $singleModelCache = array();

    /**
     * if to validate model class.
     * TODO: Decide if this should be true.
     *
     * @var bool
     */
    protected $validateModelClass = false;

    /**
     * @param \IDataSet|\ViewAccessableData $model
     * @param null|\IModelRepository $repository
     */
    public function __construct($model = null, $repository = null)
    {
        parent::__construct();

        $this->repository = $repository;
        if(isset($model)) {
            $this->setModel($model);
        }
    }

    /**
     * @return \IModelRepository|null
     */
    public function repository() {
        return $this->repository ? $this->repository : \Core::repository();
    }

    /**
     * @param \IModelRepository $repository
     * @return $this
     */
    public function setRepository($repository)
    {
        if(isset($repository) && !is_a($repository, \IModelRepository::class)) {
            throw new InvalidArgumentException();
        }

        $this->repository = $repository;
        return $this;
    }

    /**
     * @param ViewAccessableData|DataObject $model
     * @param bool $force
     * @param bool $history
     * @throws \MySQLException
     */
    public function remove($model, $force = false, $history = true)
    {
        if(is_a($model, DataObject::class)) {
            $model->remove($force, false, $history);
        } else {
            throw new \LogicException("Removing of object of type ".gettype($model)." not supported.");
        }
    }

    /**
     * @return \IDataSet|\ViewAccessableData
     */
    public function & getModel()
    {
        return $this->model;
    }

    /**
     * @param \IDataSet|\ViewAccessableData $model
     * @return $this
     */
    public function setModel($model)
    {
        if(!is_a($model, \ViewAccessableData::class)) {
            throw new InvalidArgumentException("Argument must be type of ViewAccessableData.");
        }

        $this->model = $model;
        $this->singleModelCache = array();
        return $this;
    }

    /**
     * finds single model if set or by id.
     *
     * @param null|string $id
     * @return \DataObject|\ViewAccessableData|null
     */
    public function getSingleModel($id = null) {
        if(is_a($this->model, IDataSet::class)) {
            if(isset($id)) {
                if(!isset($this->singleModelCache["record"][$id])) {
                    $data = clone $this->model;
                    $data->addFilter(array("id" => $id));
                    $this->callExtending("decorateRecord", $model);
                    $this->decorateRecord($data);
                    $this->singleModelCache["record"][$id] = $data->first();
                }

                return $this->singleModelCache["record"][$id];
            }

            return null;
        } else {
            return $this->model;
        }
    }

    /**
     * @param int $versionid
     * @return DataObject|ViewAccessableData|null
     */
    public function getSingleVersion($versionid) {
        if(is_a($this->model, IDataSet::class)) {
            if(!isset($this->singleModelCache["record"][$versionid])) {
                /** @var IDataSet $data */
                $data = clone $this->model;
                $data->addFilter(array("versionid" => $versionid));
                $this->callExtending("decorateRecord", $model);
                $this->decorateRecord($data);

                $this->singleModelCache["record"][$versionid] = $data->first();
            }

            return $this->singleModelCache["record"][$versionid];
        } else if($this->model->versionid == $versionid) {
            return $this->model;
        }

        return null;
    }

    /**
     * @return \ViewAccessableData
     */
    protected function getModelToWrite() {
        if(is_a($this->getModel(), IDataSet::class)) {
            return $this->getModel()->createNew();
        }

        return $this->getModel();
    }

    /**
     * deprecated global save method for the database.
     *
     * it saves data to the database. you can define which priority should be selected and if permissions are relevant.
     *
     * @param    array $data data
     * @param    integer $priority Defines what type of save it is: 0 = autosave, 1 = save, 2 = publish
     * @param    boolean $forceInsert forces the database to insert a new record of this data and neglect permissions
     * @param    boolean $forceWrite forces the database to write without involving permissions
     * @param bool $overrideCreated
     * @param null|\DataObject $givenModel
     * @return bool|\DataObject
     * @deprecated
     */
    public function saveData($data, $priority = 1, $forceInsert = false, $forceWrite = false, $overrideCreated = false, $givenModel = null)
    {
        return $this->saveModel(
            $this->getSafableModel($data, $givenModel),
            $data, $priority, $forceInsert, $forceWrite, $overrideCreated
        );
    }

    /**
     * returns model out of given data and model.
     * it does not append the data onto the model, it only uses it if no model is given.
     *
     * @param array $data
     * @param null $givenModel
     * @return DataObject|null
     */
    public function getSafableModel($data, $givenModel = null) {
        if(isset($givenModel)) {
            $model = $givenModel;
            unset($data["class_name"], $data["id"], $data["versionid"]);
        } else {
            if(isset($data["class_name"]) && !\ClassManifest::isOfType($data["class_name"], $this->getModel()->DataClass())) {
                throw new \LogicException("Class_name is not of type of this service-data-class.");
            }

            if (isset($data["versionid"])) {
                $model = clone $this->getSingleVersion($data["versionid"]);
            } else if (isset($data["id"])) {
                $model = clone $this->getSingleModel($data["id"]);
            } else {
                throw new \LogicException("Model is not given, nor id nor versionid");
            }
        }

        return $model;
    }

    /**
     * changes data on model by key-value.
     * @param ViewAccessableData $model
     * @param array|gObject $data Data or Object of Data
     * @return ViewAccessableData
     */
    public function applyDataToModel($model, $data)
    {
        if (is_object($data) && is_subclass_of($data, ViewaccessableData::class)) {
            /** @var ViewAccessableData $data */
            $data = $data->ToArray();
        }

        unset($data["class_name"], $data["id"], $data["versionid"]);

        foreach ($data as $key => $value) {
            $model->$key = $value;
        }

        return $model;
    }

    /**
     * @param ViewAccessableData $model
     * @param array $data
     * @param bool $force
     * @param bool $overrideCreated
     * @return DataObject
     */
    public function save($model, $data, $force = false, $overrideCreated = false) {
        return $this->saveModel($model, $data, 2, false, $force, $overrideCreated);
    }

    /**
     * @param ViewAccessableData $model
     * @param array $data
     * @param bool $force
     * @param bool $overrideCreated
     * @return DataObject
     */
    public function add($model, $data, $force = false, $overrideCreated = false) {
        return $this->saveModel($model, $data, 2, true, $force, $overrideCreated);
    }

    /**
     * @param ViewAccessableData $model
     * @param array $data
     * @param bool $force
     * @param bool $overrideCreated
     * @return DataObject
     */
    public function addDraft($model, $data, $force = false, $overrideCreated = false) {
        return $this->saveModel($model, $data, 1, true, $force, $overrideCreated);
    }

    /**
     * @param ViewAccessableData $model
     * @param array $data
     * @param bool $force
     * @param bool $overrideCreated
     * @return DataObject
     */
    public function saveDraft($model, $data, $force = false, $overrideCreated = false) {
        return $this->saveModel($model, $data, 1, false, $force, $overrideCreated);
    }

    /**
     * @param \ViewAccessableData $model
     * @param array $data
     * @param int $priority
     * @param bool $forceInsert
     * @param bool $forceWrite
     * @param bool $overrideCreated
     * @return \DataObject
     */
    public function saveModel($model, $data, $priority = 1, $forceInsert = false, $forceWrite = false, $overrideCreated = false) {
        if (PROFILE) \Profiler::mark("Controller::save");

        if(!isset($model)) {
            throw new InvalidArgumentException();
        }

        if(isset($data["id"], $data["versionid"])) {
            throw new InvalidArgumentException("Controller::saveModel does not use id and versionid.");
        }

        $this->applyDataToModel($model, $data);

        $this->onBeforeSave($model, $data, $priority, $forceInsert, $forceWrite, $overrideCreated);
        $this->callExtending("onBeforeSave", $model, $data, $priority);

        $this->storeModel($model, $priority, $forceInsert, $forceWrite, $overrideCreated);

        if (PROFILE) \Profiler::unmark("Controller::save");

        return $model;
    }

    /**
     * @param \ViewAccessableData $model
     * @param int $priority
     * @param bool $forceInsert
     * @param bool $forceWrite
     * @param bool $overrideCreated
     */
    protected function storeModel($model, $priority, $forceInsert, $forceWrite, $overrideCreated) {
        if($this->validateModelClass && !\ClassManifest::isOfType($model, $this->getModel()->DataClass())) {
            throw new \LogicException("Model is not of type {$this->getModel()->DataClass()}");
        }

        if(!gObject::method_exists($model, "writeToDBInRepo")) {
            throw new \LogicException("Model " . gettype($model) . " not supported for writing");
        }

        /** @var DataObject $model */
        $model->writeToDBInRepo($this->repository(), $forceInsert, $forceWrite, $priority, true, false, $overrideCreated);

        $this->onAfterSave($model, $priority, $forceInsert, $forceWrite, $overrideCreated);
        $this->callExtending("onAfterSave", $model, $priority, $forceInsert, $forceWrite, $overrideCreated);
    }

    /**
     * @param \ViewAccessableData $model
     * @param array $data
     * @param int $priority
     * @param bool $forceInsert
     * @param bool $forceWrite
     * @param bool $overrideCreated
     */
    protected function onBeforeSave($model, $data, $priority, $forceInsert, $forceWrite, $overrideCreated) {

    }

    /**
     * @param \ViewAccessableData $model
     * @param int $priority
     * @param bool $forceInsert
     * @param bool $forceWrite
     * @param bool $overrideCreated
     */
    protected function onAfterSave($model, $priority, $forceInsert, $forceWrite, $overrideCreated) {

    }

    /**
     * hook in this function to decorate a created record of record()-method
     * @param ViewAccessableData|IDataSet $record
     */
    protected function decorateRecord(&$record)
    {

    }
}
