<?php defined("IN_GOMA") OR die();
/**
 * base-class for every "tab" which is visible in the admin-panel.
 *
 * @package 	goma framework
 * @link 		http://goma-cms.org
 * @license 	LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 * @author 		Goma-Team
 * @version 	2.4
 *
 * last modified: 17.02.2017
*/
class adminItem extends AdminController implements PermProvider {
	/**
	 * sort
	*/
	public $sort = 0;

	/**
	 * allowed_actions
	*/
	public $allowed_actions = array
	(
		"cms_add"
	);

	/**
	 * controller inst of the model if set
	*/
	public $controllerInst;

	/**
	 * the template
	 * @var string
	*/
	public $template = "";

	/**
	 * @return bool
	 */
	public function userHasPermissions() {
		if(StaticsManager::hasStatic($this->classname, "permission")) {
			return Permission::check(StaticsManager::getStatic($this->classname, "permission"));
		}

		if(isset($this->rights)) {
			return Permission::check($this->rights);
		}

		return Permission::check("ADMIN");
	}

	/**
	 * @param null|string $model
	 * @return IDataSet|ViewAccessableData
	 */
	protected function guessModel($model = null)
	{
		if(!isset($model) && !StaticsManager::getStatic($this, "model", true)) {
			if (isset($this->models)) {
				if (count($this->models) == 1) {
					if($set = $this->createDefaultSetFromModel($this->models[0])) {
						return $set;
					}
				} else if (count($this->models) > 1) {
					throw new InvalidArgumentException("adminItem does not support more than 1 model.");
				}
			}
		}

		return parent::guessModel($model);
	}

    /**
     * @param null|Request $request
     * @return $this
     */
    public function Init($request = null)
    {
        $this->tplVars["admintitle"] = $this->adminTitle();
        $this->tplVars["adminURI"] = $this->adminURI();

        return parent::Init($request);
    }

    /**
	 * if is visible
	*/
	public function visible()
	{
		return true;
	}

	/**
	 * gives back the url of this admin-item
	 *
	 * @return string
	 */
	public function url() {
		return $this->originalNamespace . "/";
	}
	
	public function adminURI() {
		return $this->originalNamespace . "/";
	}
	
	/**
	 * gives back the title of this module
	*/
	public function Title() {
		return parse_lang(StaticsManager::getStatic($this, "text", true));
	}

	/**
	 * we provide all methods of the model-controller, too
	 *
	 * @param string $methodName
	 * @param array $args
	 * @return mixed
	 */
	public function __call($methodName, $args) {
		if(gObject::method_exists($this->getControllerInst(), $methodName)) {
			$this->getControllerInst()->request = $this->request;
			return call_user_func_array(array($this->getControllerInst(), $methodName), $args);
		}

		return parent::__call($methodName, $args);
	}

	/**
	 * we provide all methods of the model-controller, too
	 * method_exists-overloading-api of @see Object
	 *
	 * @param    string $methodName
	 * @return bool
	 */
	public function __cancall($methodName) {
		if($c = $this->getControllerInst()) {
			return gObject::method_exists($c, $methodName);
		} else {
			return false;
		}
	}

	/**
	 * add-form
	 *
	 * @return mixed|string
	 */
	public function cms_add() {
		$model = clone $this->modelInst();
		
		if($this->getParam("model")) {
			if($selectedModel = $this->getModelByName($this->getParam("model"))) {
				$model = $selectedModel;
			}
		}
		
		if(DataObject::versioned($model->dataClass) && $model->canWrite($model)) {
			/** @var DataObject $model */
			$model->queryVersion = "state";
		}
		
		$submit = DataObject::Versioned($model->classname) ? "publish" : null;

		return $this->form(null, $model, array(), false, $submit);
	}

	/**
	 * gets model by given name.
	 *
	 * @param string $name name of object.
	 * @return gObject|null
	 */
	protected function getModelByName($name) {
		$name = str_replace("-", "\\", $name);

		if (ClassManifest::isOfType($name, $this->model())) {
            return new $name;
        }

		return null;
	}

	/**
	 * alias for cms_add
	 *
	 * @return mixed|string
	 */
	public function add() {
		return $this->cms_add();	
	}

	/**
	 *  provides no perms
	 *
	 * @return array
	 */
	public function providePerms()
	{
		return array();
	}

	/**
	 * generates the normal controller for the model inst
	 *
	 * @return bool|Controller|null
	 */
	public function getControllerInst() {
		if(!isset($this->controllerInst)) {
			$this->controllerInst = ControllerResolver::instanceForModel($this->modelInst());
		}
		
		return $this->controllerInst;
	}
}
