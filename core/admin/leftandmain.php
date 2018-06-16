<?php use Goma\Controller\Versions\VersionController;

defined("IN_GOMA") OR die();

/**
 * A simple two column admin-panel.
 *
 * @package     Goma\Admin\LeftAndMain
 *
 * @license     GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author      Goma-Team
 *
 * @version     2.2.8
 */
class LeftAndMain extends AdminItem {
    /**
     * used to identify correct sort request.
     */
    const SESSION_KEY_SORT = "lam_sort_session";

    /**
	 * the base template of the view
	*/
	public $baseTemplate = "admin/leftandmain/leftandmain.html";

	/**
	 * defines the url-handlers
	*/
	static $url_handlers = array(
		"updateTree/\$search"			=> "updateTree",
		"add/\$model"					=> "cms_add",
        "versions"                      => "versions",
        "savesort"                      => "savesort"
	);

	/**
	 * this var defines the tree-class
	*/
	public $tree_class = "";

	/**
	 * sort-field
	*/
	protected $sort_field;

	/**
	 * render-class.
	*/
	static $render_class = "LeftAndMain_TreeRenderer";

	/**
	 * @var bool
	 */
	static $useStateData = true;

	/**
	 * gets the title of the root node
	 *
	 * @return string
	 */
	protected function getRootNode() {
		return "";
	}

	/**
	 * generates the options for the create-select-field
	 *
	 * @return array
	 */
	public function createOptions() {
		$options = array();
		foreach($this->models as $model) {
			if($title = ClassInfo::getClassTitle($model)) {
				$options[$model] = $title;
			}
		}
		return $options;
	}

	/**
	 * @param null|ViewAccessableData $model
	 * @return \Goma\Service\DefaultControllerService
	 */
	protected function defaultService($model = null)
	{
		return static::$useStateData ?
		new \Goma\Service\StateControllerService(
			$this->guessModel($model)
		) : parent::defaultService($model);
	}

	/**
	 * @param GomaResponse|string $content
	 * @return GomaResponse|string
	 */
	public function __output($content)
	{
		if(!$this->isManagingController($content) || $this->getRequest()->is_ajax()) {
			return parent::__output($content);
		}

		// add resources
		Resources::add("system/core/admin/leftandmain.js", "js", "tpl");

		if(isset($this->sort_field)) {
			Resources::addData("var LaMsort = true;");
		} else {
			Resources::addData("var LaMsort = false;");
		}

		Resources::addData("var adminURI = '".$this->adminURI()."';");

		$data = $this->ModelInst();

		$output = $data->customise(
			array(
				"CONTENT"	=> Director::getStringFromResponse($content),
				"activeAdd" => $this->getParam("model"),
				"SITETREE" => $this->createTree($this->getParam("searchtree")),
				"searchtree" => $this->getParam("searchtree"),
				"ROOT_NODE" => $this->getRootNode(),
				"TREEOPTIONS" => $this->generateTreeOptions(),
				"adminURI"	=> $this->adminURI()
			)
		)->renderWith($this->baseTemplate);

		return parent::__output(
			Director::setStringToResponse($content, $output)
		);
	}

	/**
	 * generates a set of options as HTML, that can be used to have more than just a search
	 * to customise the tree. For example a multilingual-plugin should add a select-option
	 * to filter by language.
	*/
	public function generateTreeOptions() {
		$tree_class = $this->tree_class;
		if($tree_class == "") {
			throw new LogicException("Failed to load Tree-Class. Please define \$tree_class in ".$this->classname);
		}

		$html = new HTMLNode("div");

		if(gObject::method_exists($tree_class, "generateTreeOptions")) {
			call_user_func_array(array($tree_class, "generateTreeOptions"), array($html, $this));
		}

		/** @var gObject $treeInstance */
		$treeInstance = new $tree_class;
		$treeInstance->callExtending("generateTreeOptions", $html, $this);

		if($html->children()) {
			return $html->render();
		}

		return "";
	}

	/**
	 * generates the tree-links.
	*/
	public function generateTreeLink($child, $bubbles) {
		return new HTMLNode("a", array("href" => $this->originalNamespace . "/record/" . $child->recordid . URLEND, "class" => "node-area"), array(
			new HTMLNode("span", array("class" => "img-holder"), new HTMLNode("img", array("src" => $child->icon))),
			new HTMLNode("span", array("class" => "text-holder"), $child->title),
			$bubbles
		));
	}

	/**
	 * generates the context-menu.
	 *
	 * @param DataObject $child
	 * @return array
	 */
	public function generateContextMenu($child) {
		$data = array();
		if($child->treeclass) {

			$data = array(
				array(
					"icon"		=> "system/images/16x16/edit.png",
					"label" 	=> lang("edit"),
					"onclick"	=> "LoadTreeItem(".$child->recordid.");"
				),
				array(
					"icon"		=> "system/images/16x16/del.png",
					"label" 	=> lang("delete"),
					"ajaxhref"	=> $this->originalNamespace . "/record/" . $child->recordid . "/delete" . URLEND
				)
			);
		}

		$this->callExtending("generateContextMenu", $data);

		return $data;
	}

	/**
	 * @param gObject $instance
	 * @param array $options
	 * @return array|mixed
	 */
	protected function callArgumentTree($instance, $options) {
		$newParams = call_user_func_array(array($instance, "argumentTree"), array($this, $options));
		if(is_array($newParams) && isset($newParams["version"]) && isset($newParams["filter"])) {
			$options = $newParams;
		}
		return $options;
	}

	/**
	 * @param TreeRenderer $treeRenderer
	 * @param string $id
     */
	protected function setExpanded($treeRenderer, $id) {
		// here we check for Ajax-Opening. It is given to the leftandmain-js-api.
		/** @var DataObject $current */
		if($current = DataObject::get_versioned("pages", "state", array("id" => $id))->first()) {
			$treeRenderer->setExpanded($current->id);
			while($current->parent) {
				$current = $current->parent;
				$treeRenderer->setExpanded($current->id);
			}
		}
	}

	/**
	 * creates the Tree
	 *
	 * @param string $search
	 * @return String
	 */
	public function createTree($search = "") {
		$tree_class = $this->tree_class;
		if($tree_class == "") {
			throw new LogicException("Failed to load Tree-Class. Please define \$tree_class in ".$this->classname);
		}

		if(!gObject::method_exists($tree_class, "build_tree")) {
			throw new LogicException("Tree-Class does not have a method build_tree. Maybe you have to update your version of goma?");
		}

		$options = array("version" => "state", "search" => $search, "filter" => array());

		// give the tree-class the ability to modify the options.
		if(gObject::method_exists($tree_class, "argumentTree")) {
			$options = $this->callArgumentTree($tree_class, $options);
		}

		// iterate through extensions to give them the ability to change the options.
        /** @var gObject $treeClassInstance */
        $treeClassInstance = new $tree_class;
		foreach($treeClassInstance->getextensions() as $ext)
		{
			if (ClassInfo::hasInterface($ext, "TreeArgumenter")) {
                $treeClassInstance->workWithExtensionInstance($ext, function($instance) use(&$options) {
                    $options = $this->callArgumentTree($instance, $options);
                });
			}
		}
		unset($treeClassInstance);

		// generate tree
		$tree = call_user_func_array(array($tree_class, "build_tree"), array(0, $options));
		/** @var TreeRenderer $treeRenderer */
		$treeRenderer = new self::$render_class($tree, null, null, $this->originalNamespace, $this);
		$treeRenderer->setLinkCallback(array($this, "generateTreeLink"));
		$treeRenderer->setActionCallback(array($this, "generateContextMenu"));

		// check for logical opened tree-items.
		if(isset($this->getRequest()->get_params["edit_id"])) {
			$this->setExpanded($treeRenderer, $this->getRequest()->get_params["edit_id"]);
		} else if($this->getParam("id")) {
			$this->setExpanded($treeRenderer, $this->getParam("id"));
		}

		return $treeRenderer->render(true);
	}
	/**
	 * gets updated data of tree for searching or normal things
	*/
	public function updateTree() {
		$search = $this->getParam("search");

		return GomaResponse::create()->setShouldServe(false)->setBody(
			GomaResponseBody::create($this->createTree($search))->setParseHTML(false)
		);
	}

	/**
	 * Actions of editing
	*/

	/**
	 * saves data for editing a site via ajax
	 *
	 * @param array $data
	 * @param FormAjaxResponse $response
	 * @param Form $form
	 * @param null $controller
	 * @param bool $forceInsert
	 * @param bool $forceWrite
	 * @param bool $overrideCreated
	 * @return FormAjaxResponse
	 */
	public function ajaxSave($data, $response, $form, $controller, $forceInsert = false, $forceWrite = false, $overrideCreated = false) {
		try {
			$model = $this->service()->saveModel($form->getModel(), $data, 1, $forceInsert, $forceWrite, $overrideCreated);
			// notify the user
			Notification::notify($model->classname, lang("SUCCESSFUL_SAVED", "The data was successfully written!"), lang("SAVED"));

			$response->exec("var href = '" . BASE_URI . $this->adminURI() . "record/" . $model->id . "/edit" . URLEND . "'; if(getInternetExplorerVersion() <= 7 && getInternetExplorerVersion() != -1) { if(location.href == href) location.reload(); else location.href = href; } else { reloadTree(function(){ goma.ui.ajax(undefined, {url: href, showLoading: true, pushToHistory: true}); }, " . var_export($model["id"], true) . "); }");

			return $response;
		} catch(Exception $e) {
			$response->exec('alert('.var_export($e->getMessage(), true).');');
			return $response;
		}
	}


	/**
	 * saves sort
	*/
	public function savesort() {
		if(isset($this->request->post_params["treenode"])) {
			if(isset($this->request->get_params["t"])) {
                if (Core::globalSession()->hasKey(self::SESSION_KEY_SORT)) {
                    $lastSession = Core::globalSession()->get(self::SESSION_KEY_SORT);
                    if ((int)$lastSession > (int)$this->request->get_params["t"]) {
                        return GomaResponse::create()->setShouldServe(false)->setBody(
                            GomaResponseBody::create($this->createTree())->setParseHTML(false)
                        );
                    }
                }

                Core::globalSession()->set(self::SESSION_KEY_SORT, $this->request->get_params["t"]);

                $field = $this->sort_field;
                foreach ($this->request->post_params["treenode"] as $key => $value) {
                    DataObject::update($this->tree_class, array($field => $key), array("recordid" => $value), "");
                }

                return GomaResponse::create()->setIsFullPage(true)->setBody(
                    GomaResponseBody::create($this->createTree())->setParseHTML(false)
                );
            }
		}

		throw new BadRequestException();
	}

	/**
	 * hides the deleted object
	*/
	public function hideDeletedObject($response, $data) {
		$response->exec("reloadTree(function(){ goma.ui.ajax(undefined, {url: '".$this->originalNamespace."'}); });");
		return $response;
	}

	/**
	 * publishes data for editing a site via ajax
	 * @param array $data
	 * @param AjaxResponse $response
	 * @param Form $form
	 * @param null $controller
	 * @param bool $overrideCreated
	 * @return AjaxResponse
	 */
	public function ajaxPublish($data, $response, $form = null, $controller = null, $overrideCreated = false) {
		if($model = $this->service()->saveModel($form->getModel(), $data, 2, false, false, $overrideCreated)) {
			// notify the user
			Notification::notify($model->classname, lang("successful_published", "The data was successfully published!"), lang("published"));

			$response->exec("var href = '".BASE_URI . $this->adminURI()."record/".$model->id."/edit".URLEND."'; if(getInternetExplorerVersion() <= 9 && getInternetExplorerVersion() != -1) { if(location.href == href) location.reload(); else location.href = href; } else {reloadTree(function(){ goma.ui.ajax(undefined, {url: href, showLoading: true, pushToHistory: true});}, ".$model->id."); }");

			return $response;
		} else {
			$response->exec('alert('.var_export(lang("less_rights"), true).');');
			return $response;
		}
	}

	/**
	 * decorate model
	 *
	 * @name decorateModel
	 * @access public
	 * @param object $model
	 * @param array $add
	 * @return DataObject
	 */
	public function decorateModel($model, $add = array()) {
		$add["types"] = $this->Types();

		return parent::decorateModel($model, $add);
	}

	/**
	 * gets the options for add
	 *
	 * @return DataSet
	 */
	public function Types() {
		$data = $this->createOptions();
		$arr = new DataSet();
		foreach($data as $class => $title) {
			$arr->push(array("value" => str_replace("\\", "-", $class), "title" => $title, "icon" => ClassInfo::getClassIcon($class)));
		}
		return $arr;
	}

	/**
	 * adds content-class left-and-main to content-div
	 *
	 * @return string
	 */
	public function contentClass() {
		return parent::contentclass() . " left-and-main";
	}

	/**
	 * add-form
	 *
	 * @return string
	 */
	public function cms_add() {
		$model = clone $this->modelInst();

		if($this->getParam("model")) {
			if($selectedModel = $this->getModelByName($this->getParam("model"))) {
				$model = $selectedModel;
			}
		} else {
			Resources::addJS('$(function(){$(".leftbar_toggle, .leftandmaintable tr > .left").addClass("active");$(".leftbar_toggle, .leftandmaintable tr > .left").removeClass("not_active");$(".leftbar_toggle").addClass("index");});');

			$model = new ViewAccessableData();
			return $model->customise(array("adminuri" => $this->adminURI(), "types" => $this->types()))->renderWith("admin/leftandmain/leftandmain_add.html");
		}

		if(DataObject::Versioned($model->dataClass) && $model->canWrite($model)) {
			$model->queryVersion = "state";
		}

		return $this->form(null, $model);
	}

	/**
	 * index-method
	 *
	 * @return string
	 */
	public function index() {
		if($this->getSingleModel()) {
			return $this->edit();
		}

		Resources::addJS('$(function(){$(".leftbar_toggle, .leftandmaintable tr > .left").addClass("active");$(".leftbar_toggle, .leftandmaintable tr > .left").removeClass("not_active");$(".leftbar_toggle").addClass("index");});');

		if(!$this->template)
			return "";

		return parent::index();
	}

	/**
	 * @return null|mixed
	 * @throws Exception
	 */
	public function versions()
	{
		/** @var Pages $model */
		if($model = $this->getSingleModel()) {
			$controller = new VersionController();
			$controller->setModelInst($model);
            $remaining = $this->request->remaining();
            $response = $controller->handleRequest($this->request, true);
			return $remaining ? $response : $this->putHeaderToResponse(
                $response, $model->customise(array(
                    "inVersionView" => true
                )
			));
		}

		return null;
	}

	/**
	 * @return GomaFormResponse|string
	 */
	public function edit()
	{
		return $this->putHeaderToResponse(parent::edit(), $this->getSingleModel());
	}

	/**
	 * @param GomaResponse $response
	 * @param DataObject $model
	 * @return GomaFormResponse|GomaResponse|GomaResponseBody|mixed|string
	 */
	protected function putHeaderToResponse($response, $model) {
		if(Director::isResponseFullPage($response)) {
			return $response;
		}

		return Director::setStringToResponse($response, $model->customise(array(
			"content" 		=> Director::getStringFromResponse($response),
			"icon"	  		=> ClassInfo::getClassIcon($model->class_name),
			"classtitle"	=> ClassInfo::getClassTitle($model->class_name),
			"isVersioned"	=> DataObject::Versioned($model->class_name),
			"versionsLink"	=> $this->buildUrlForActionAndModel("versions", $model->id),
			"namespace"		=> $this->namespace
		))->renderWith("admin/leftandmain/edit_with_header.html"));
	}
}
