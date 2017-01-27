<?php
namespace Goma\Model\Validation;
use ArrayLib;
use InvalidArgumentException;
use ViewAccessableData;

defined("IN_GOMA") OR die();

/**
 * Validator-Object for DataObject-Instances.
 *
 * @package		Goma\Validation
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class DataValidator
{
	/**
	 * @var ViewAccessableData
	 */
	protected $model;

	/**
	 * @var string[]
	 */
	protected $requiredFields;

	/**
	 * @param ViewAccessableData $model
	 * @param string[] $requiredFields
	 */
	public function __construct($model, $requiredFields = array())
	{
        if(!\ArrayLib::isAssocArray($requiredFields)) {
            throw new InvalidArgumentException("Required-Fields must be an assoc array. field => title");
        }
		if (!is_subclass_of($model, ViewAccessableData::class)) {
			throw new InvalidArgumentException('$model is not child of DataObject.');
		}
		$this->model = $model;
		$this->requiredFields = ArrayLib::map_key("strtolower", $requiredFields);
	}

	/**
	 * validates the data
	 */
	public function validate()
	{
		$requiredFields = array();
        $exceptions = array();
		foreach(
            array_map("strtolower", array_keys($this->model->ToArray())) as $field) {
            if(isset($this->requiredFields[$field]) && !$this->model->{$field}) {
                $requiredFields[] = $field;
            }

            if(\gObject::method_exists($this->model, "validate" . $field)) {
                try {
                    call_user_func_array(array($this->model, "validate" . $field), array());
                } catch(\Throwable $e) {
                    $exceptions[] = $e->getMessage();
                } catch(\Exception $e) {
                    $exceptions[] = $e->getMessage();
                }
            }
		}
	}

    /**
     * @param string[] $exceptions
     * @param string[] $requiredFields
     * @throws \FormMultiFieldInvalidDataException
     */
    protected function consolidateExceptions($exceptions, $requiredFields) {
        if(!$requiredFields) {
            throw new \FormMultiFieldInvalidDataException($exceptions);
        }

        $text = lang("form_required_fields", "Please fill out the oligatory fields");
        $i = 0;
        foreach ($requiredFields as $field) {
            if ($i == 0) {
                $i = 1;
            } else {
                $text .= ", ";
            }
            $text .= ' \'' . $this->requiredFields[$field] . '\'';
        }

        array_unshift($exceptions, $text);

        $exceptions = array_merge($exceptions,
            array_combine($requiredFields, array_fill(0, count($requiredFields), "")));

        throw new \FormMultiFieldInvalidDataException($exceptions);
    }
}
