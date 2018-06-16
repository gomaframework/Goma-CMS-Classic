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
	 * sort for admin
	*/
	static $sort = 0;

    /**
     * required rights to access this.
     *
     * @var string
     */
    static $rights = "admin";

	/**
	 * allowed_actions
	*/
	static $url_handlers = array
	(
		"cms_add" => "cms_add"
	);

	/**
	 * the template
	 * @var string
	*/
	public $template = "";

    /**
     * @param null|string $model
     * @return IDataSet|ViewAccessableData
     * @throws ReflectionException
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
     * if is visible to a specific user.
     *
     * @param User $user
     * @return bool
     * @throws PermissionException
     * @throws SQLException
     */
	public static function visible($user)
	{
		return $user->hasPermissions(static::$rights);
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
}
