<?php
defined("IN_GOMA") OR die();

/**
 * A single HasMany-Relationship.
 *
 * @package Goma
 *
 * @author Goma Team
 * @copyright 2016 Goma Team
 *
 * @version 1.0
 */
class ModelHasOneRelationshipInfo extends ModelRelationShipInfo {
    /**
     * @var string
     */
    protected static $modelInfoGeneratorFunction = "generateHas_one";

    /**
     * forces inverse.
     */
    protected function validateAndForceInverse() {
        if(isset($this->inverse)) {
            $relationShips = ModelInfoGenerator::generateHas_many($this->targetClass);
            if(!isset($relationShips[$this->inverse])) {
                throw new InvalidArgumentException("Inverse {$this->inverse} not found on class {$this->targetClass}.");
            }
        }

        if(DataObject::Versioned($this->owner) && !DataObject::Versioned($this->targetClass)) {
            if( $this->cascade == DataObject::CASCADE_TYPE_ALL ||
                $this->cascade == DataObject::CASCADE_TYPE_REMOVE ||
                $this->cascade == DataObject::CASCADE_TYPE_UNIQUE) {
                throw new InvalidArgumentException("When using Remove-Cascade Versioning must be equal on both objects.");
            }
        }

        if($this->cascade == DataObject::CASCADE_UNIQUE_LIKE) {
            throw new InvalidArgumentException("CASCADE_UNIQUE_LIKE is an option not a value for cascade.");
        }

        if($this->cascade == DataObject::CASCADE_TYPE_UNIQUE &&
            (!StaticsManager::hasStatic($this->targetClass, "unique_fields") || !is_array(StaticsManager::getStatic($this->targetClass, "unique_fields")))) {
            throw new InvalidArgumentException("When using UNIQUE-Cascade, Target-Class must define unique_fields.");
        }
    }

    /**
     * generates information for ClassInfo.
     *
     * @return array
     */
    public function toClassInfo() {
        $info = array(
            DataObject::RELATION_TARGET => $this->targetClass,
            DataObject::RELATION_INVERSE => $this->inverse
        );

        if($this->cascade != DataObject::CASCADE_TYPE_UPDATE) {
            $info[DataObject::CASCADE_TYPE] = $this->cascade;
        }

        if($this->fetchType != DataObject::FETCH_TYPE_LAZY) {
            $info[DataObject::FETCH_TYPE] = $this->fetchType;
        }

        if($this->uniqueLike) {
            $info[DataObject::CASCADE_UNIQUE_LIKE] = true;
        }

        return $info;
    }
}
