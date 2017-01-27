<?php
defined("IN_GOMA") OR die();

/**
 * Combines multiple forms to a combined field.
 * It requires a Set of DataObjects which have getForm or getEditForm.
 *
 * @package Goma
 *
 * @author Goma-Team
 * @copyright 2016 Goma-Team
 *
 * @version 1.0
 */
class MultiFormFormField extends ClusterFormField {

    /**
     * @param
     */
    const SESSION_PREFIX = "MultiFormField";

    /**
     * @var bool
     */
    protected $useEditFormMethod = false;

    /**
     * @var bool|string
     */
    protected $allowAddOfKind = false;

    /**
     * @var bool|string
     */
    protected $disallowAddOfKind = false;

    /**
     * @var string
     */
    protected $template = "form/MultiFormFormField.html";

    /**
     * @var string
     */
    protected $modelKeyField = "__MULTI__KEY__";

    /**
     * @var string
     */
    protected $secret;

    /**
     * @var bool
     */
    protected $loadedFromSession = false;

    /**
     * @var bool
     */
    protected $addedNewField = false;

    /**
     * @return DataObjectSet|IDataSet|RemoveStagingDataObjectSet|ISortableDataObjectSet
     */
    public function getModel() {
        if (!isset($this->hasNoValue) || !$this->hasNoValue) {
            if($this->POST) {
                if(!$this->secret && $this->parent && !$this->isDisabled()) {
                    $this->secret = randomString(10);
                    $this->add($hidden = new HiddenField("secret", $this->secret));

                    if(!$this->loadedFromSession && ($oldSecret = $this->parent->getFieldPost($hidden->PostName())) !== null) {
                        if($model = Core::globalSession()->get(self::SESSION_PREFIX . $this->PostName() . $oldSecret)) {
                            $this->loadedFromSession = true;
                            return $this->model = $model;
                        }
                    }
                }

                if ($this->model == null) {
                    return $this->parent ? $this->parent->getFieldValue($this->dbname) : null;
                }
            }
        }

        return $this->model;
    }

    /**
     * @return FormFieldRenderData
     */
    public function createsRenderDataClass()
    {
        return MultiFormRenderData::create($this->getName(), $this->classname, $this->ID(), $this->divID());
    }

    /**
     * saves to session.
     */
    protected function saveToSession() {
        Core::globalSession()->set(self::SESSION_PREFIX . $this->PostName() . $this->secret, $this->getModel());
    }

    /**
     * modify add and sort.
     *
     * @return bool
     */
    protected function modifyAddAndSort() {
        $hasBeenAddedNewField = false;

        foreach($this->getAddableClasses() as $class) {
            if($this->parent->getFieldPost($this->PostName() . "_add_" . $class)) {
                $this->getModel()->add(
                    $this->getModel()->createNew(array(
                        "class_name" => $class
                    ))
                );
                $hasBeenAddedNewField = true;
            }
        }

        if(is_a($this->getModel(), ISortableDataObjectSet::class) && $this->getRequest()->post_params) {
            $keyField = $this->modelKeyField;
            $postData = $this->getRequest()->post_params;
            $parent = $this->getParent();
            $this->getModel()->sortCallback(function($a, $b) use($keyField, $postData, $parent) {
                if(isset($a->{$keyField}) && isset($b->{$keyField}) &&
                    ($postA = $parent->getFieldPost($a->{$keyField} . "___sortpart")) !== null &&
                    ($postB = $parent->getFieldPost($b->{$keyField} . "___sortpart")) !== null) {
                    if($postA == $postB) {
                        return 0;
                    }

                    return ((int) $postA) < ((int) $postB) ? -1 : 1;
                } else if(isset($a->{$keyField}) && $parent->getFieldPost($a->{$keyField} . "___sortpart") !== null) {
                    return -1;
                } else if(isset($b->{$keyField}) && $parent->getFieldPost($b->{$keyField} . "___sortpart") !== null) {
                    return 1;
                } else {
                    return 0;
                }
            });
        }

        return $hasBeenAddedNewField;
    }

    /**
     *
     */
    protected function defineFields()
    {
        if(!is_a($this->getModel(), "DataSet") && !is_a($this->getModel(), "DataObjectSet")) {
            throw new InvalidArgumentException("Value for MultiFormFormField must be DataSet or DataObjectSet.");
        }

        if(is_a($this->getModel(), "DataObjectSet")) {
            $this->getModel()->setModifyAllMode();
        }

        $this->addedNewField = $this->modifyAddAndSort();

        /** @var DataObject $record */
        $i = 0;
        foreach($this->getModel() as $record) {
            if(!isset($record->{$this->modelKeyField})) {
                $record->{$this->modelKeyField} =  $record->versionid != 0 ? $this->name . "_" . $record->versionid :
                    $this->name . "_a" . $i;
            }
            $field = new ClusterFormField(
                $record->{$this->modelKeyField},
                "",
                array(
                    $hiddenDelete = new HiddenField("__shouldDeletePart", 0),
                    $hiddenSort = new HiddenField("__sortPart", $i)
                ),
                $record
            );

            $hiddenDelete->POST = $hiddenSort->POST = true;

            $field->container->addClass($record->classname);
            $field->setTemplate("form/MultiFormComponent.html");

            if($this->useEditFormMethod) {
                $record->getEditForm($field);
            } else {
                $record->getForm($field);
            }

            $this->add($field);
            $i++;
        }

        $this->saveToSession();
    }

    /**
     * @return array|string|ViewAccessableData
     */
    public function result()
    {
        $result = $this->getModel();
        if(!$result)
            throw new LogicException();

        $sortInfo = array();
        foreach($result as $record) {
            if(isset($record->{$this->modelKeyField})) {
                if($this->getField($record->{$this->modelKeyField})) {
                    $this->getField($record->{$this->modelKeyField})->argumentResult($record);
                    if ($record->__shouldDeletePart) {
                        /** @var RemoveStagingDataObjectSet $result */
                        $result->removeFromSet($record);
                    } else {
                        $sortInfo[$record->{$this->modelKeyField}] = $record->__sortPart;
                    }
                }
            }
        }

        if(is_a($result, ISortableDataObjectSet::class)) {
            /** @var ISortableDataObjectSet $result */
            $keyField = $this->modelKeyField;
            $result->sortCallback(function($a, $b) use($sortInfo, $keyField) {
                if(isset($sortInfo[$a->{$keyField}]) && isset($sortInfo[$b->{$keyField}])) {
                    if($sortInfo[$a->{$keyField}] == $sortInfo[$b->{$keyField}]) {
                        return 0;
                    }

                    return $sortInfo[$a->{$keyField}] < $sortInfo[$b->{$keyField}] ? -1 : 1;
                } else {
                    $fieldA = $this->getField($a->{$keyField});
                    $fieldB = $this->getField($b->{$keyField});
                    $hasFieldA = $fieldA != null;
                    $hasFieldB = $fieldB != null;
                    $infoA = array();
                    $infoB = array();
                    $fieldA != null && $fieldA->argumentResult($infoA);
                    $fieldB != null && $fieldB->argumentResult($infoB);
                    throw new LogicException("Sort-Information not available. Query for: {$a->{$keyField}}: $hasFieldA, " .
                    " {$b->{$keyField}}: $hasFieldB | Data for: " . print_r($sortInfo, true) . " A: " . print_r($infoA, true) .
                    "B: " . print_r($infoB, true));
                }
            });
        }

        return $result;
    }

    /**
     * @param null $fieldErrors
     * @return MultiFormRenderData
     */
    public function exportBasicInfo($fieldErrors = null)
    {
        /** @var MultiFormRenderData $data */
        $data = parent::exportBasicInfo($fieldErrors);

        return $data
            ->setSortable(is_a($this->getModel(), "ISortableDataObjectSet"))
            ->setDeletable(is_a($this->getModel(), "RemoveStagingDataObjectSet"))
            ->setAddAble($this->getAddableClasses())
            ->setAddedNewField($this->addedNewField);
    }

    public function addRenderData($info, $notifyField = true)
    {
        parent::addRenderData($info, $notifyField);

        $info->addJSFile("system/libs/javascript/jquery-touch-punch.js");
    }

    /**
     * @return string[]
     */
    protected function getAddableClasses() {
        return $this->getAllowAddOfKindClass() ?
            $this->filterAllowed(
                array_merge(array($this->getAllowAddOfKindClass()), ClassInfo::getChildren($this->getAllowAddOfKindClass()))
            ) :
            array();
    }

    /**
     * @param array $allowed
     * @return array
     */
    protected function filterAllowed($allowed) {
        if($this->disallowAddOfKind) {
            return array_filter($allowed, function($allow) {
                return !ClassManifest::isOfType($allow, $this->disallowAddOfKind);
            });
        }

        return $allowed;
    }

    /**
     * @return null|string
     */
    protected function getAllowAddOfKindClass() {
        return $this->allowAddOfKind ? (ClassInfo::exists($this->allowAddOfKind) ? $this->allowAddOfKind : $this->getModel()->DataClass()) : null;
    }

    /**
     * @return boolean
     */
    public function isUseEditFormMethod()
    {
        return $this->useEditFormMethod;
    }

    /**
     * @param boolean $useEditFormMethod
     * @return $this
     */
    public function setUseEditFormMethod($useEditFormMethod)
    {
        $this->useEditFormMethod = $useEditFormMethod;
        return $this;
    }

    /**
     * @return bool|string
     */
    public function getAllowAddOfKind()
    {
        return $this->allowAddOfKind;
    }

    /**
     * @param bool|string $allowAddOfKind
     * @return $this
     */
    public function setAllowAddOfKind($allowAddOfKind)
    {
        $this->allowAddOfKind = $allowAddOfKind;
        return $this;
    }

    /**
     * @return bool|string
     */
    public function getDisallowAddOfKind()
    {
        return $this->disallowAddOfKind;
    }

    /**
     * @param bool|string $disallowAddOfKind
     * @return $this
     */
    public function setDisallowAddOfKind($disallowAddOfKind)
    {
        $this->disallowAddOfKind = $disallowAddOfKind;
        return $this;
    }

    /**
     * @return string
     */
    public function getModelKeyField()
    {
        return $this->modelKeyField;
    }

    /**
     * @param string $modelKeyField
     * @return $this
     */
    public function setModelKeyField($modelKeyField)
    {
        if($modelKeyField) {
            $this->modelKeyField = $modelKeyField;
        }
        return $this;
    }
}
