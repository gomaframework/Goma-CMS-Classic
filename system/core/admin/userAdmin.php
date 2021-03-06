<?php defined("IN_GOMA") OR die();

/**
 * User-Admin-Panel
 *
 * @package goma framework
 * @link http://goma-cms.org
 * @license: LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 * @author Goma-Team
 * @version 1.1
*/
class userAdmin extends adminItem {
	/**
	 * text
	*/
    static $text = '{$_lang_users}';
	
	/**
	 * permissions
	*/
    static $rights = "USERS_MANAGE";
	
	/**
	 * sort
	*/
    static $sort = "700";
	
	/**
	 * models
	*/
	public $model = "user";
	
	static $icon = "system/templates/admin/images/user.png";

	/**
	 * history-url
	 *
	 * @name historyURL
	 * @access public
	 * @return string
	 */
	public function historyURL() {
		return "admin/history/user";
	}
	

	/**
	 * extend actions
	*/
	static $url_handlers = array(
		"toggleLock/\$id!" => "toggleLock"
	);

	/**
	 * logic
	*/
	public function index() {
		$config = TableFieldConfig_Editable::create();
		$config->getComponentByType("TableFieldDataColumns")->setDisplayFields(array(
			"id"		=> "ID",
			"image"		=> lang("pic"),
			"nickname" 	=> lang("username"),
			"name"		=> lang("name"),
			"email"		=> lang("email"),
			"groupList"	=> lang("groups")
		))->setFieldFormatting(array(
			"image" => '$image.setSize(50, 50)'
		));
		$config->removeComponent($config->getComponentByType("TableFieldToolbarHeader"));
		$config->addComponent(new TableFieldActionLink(	$this->namespace . '/toggleLock/$id' . URLEND . '?redirect=' . urlencode(ROOT_PATH . $this->namespace . URLEND),
														'<i class="fa fa-lock"></i>',
														lang("lock"), 
														array($this, "checkForUnlock"),
			array("button button-clear yellow")));
		$config->addComponent(new TableFieldActionLink(	$this->namespace . '/toggleLock/$id' . URLEND . '?redirect=' . urlencode(ROOT_PATH . $this->namespace . URLEND),
														'<i class="fa fa-unlock"></i>',
														lang("unlock"), 
														array($this, "checkForLock"),
														array("button button-clear yellow")));

		if(isset($this->getRequest()->get_params["groupid"]) &&
            $group = DataObject::get_by_id(Group::class, $this->getRequest()->get_params["groupid"])) {
			$this->modelInst()->addFilter(array(
				"groups" => array(
                    "id" => $this->getRequest()->get_params["groupid"]
                )
			));
            $view = new ViewAccessableData();
            $groupField = new HTMLField("groupfield", $view->customise(array(
                "backurl" => isset($this->getRequest()->get_params["redirect"]) ?
                    $this->getRequest()->get_params["redirect"] : ROOT_PATH . $this->namespace . URLEND,
                "title" => $group->name()->text()
            ))->renderWith("admin/subview-header.html"));
		}

		$this->callExtending("extendUserAdmin", $config);

		$form = new Form($this, "form_useradmin", array(
			new TableField("userTable", lang("users"), $this->modelInst(), $config)
		));

        if(isset($groupField)) {
            $form->add($groupField, 0);
        }
		
		return $form->render();
	}

	/**
	 * helper for tableField.
	*/
	public function checkForLock($tableField, $record) {
		return ($record->status != 1 && Permission::check("USERS_MANAGE") && $record->id != member::$id);
	}

	/**
	 * helper for tableField.
	*/
	public function checkForUnlock($tableField, $record) {
		return ($record->status == 1 && Permission::check("USERS_MANAGE") && $record->id != member::$id);
	}


	/**
	 * switches the lock-state of an user.
	 *
	 * @name toggleLock
	 * @return bool|string
	 */
	public function toggleLock() {
		if($this->getParam("id") && Permission::check("USERS_MANAGE") && $this->getParam("id") != member::$id) {
			/** @var User $user */
			if($user = DataObject::get_by_id("user", $this->getParam("id"))) {
				if($user->status == 1) {
				    return $this->confirmByForm(lang("user_lock_q"), function() use($user) {
                        $user->status = 2;
                        $user->writeToDB();
                        return $this->actionComplete("lock_user", $user);
                    }, null, null, $user->generateRepresentation(true));
				} else {
                    return $this->confirmByForm(lang("user_unlock_q"), function() use($user) {
                        $user->status = 1;
                        $user->writeToDB();
                        return $this->actionComplete("lock_user", $user);
                    }, null, null, $user->generateRepresentation(true));
				}
			}
		}

		return $this->redirectBack();
	}

	/**
	 * this is the method, which is called when a action was completed successfully or not.
	 *
	 * it is called when actions of this controller are completed and the user should be notified. For example if the
	 * user saves data and it was successfully saved, this method is called with the param save_success. It is also
	 * called if an error occurs.
	 *
	 * @param    string $action the action called
	 * @param    gObject $record optional: record if available
	 * @access    public
	 * @return bool|string
	 */
	public function actionComplete($action, $record = null) {
		if($action == "publish_success") {
			AddContent::addSuccess(lang("successful_saved", "The data was successfully saved."));
			return $this->redirectback();
		}

		if($action == "unlock_user") {
			AddContent::addSuccess(lang("user_unlocked", "The account has been unlocked."));
			return $this->redirectback();
		}

		if($action == "lock_user") {
			AddContent::addSuccess(lang("user_locked", "The user has been locked."));
			return $this->redirectback();
		}
		
		return parent::actionComplete($action, $record);
	}

	/**
	 * @return false|mixed|null|string
	 * @throws Exception
	 */
	public function edit()
	{
		/** @var DataObject $user */
		if($user = $this->getSingleModel()) {
			Core::setTitle($user->title);

			$editController = new \Goma\Security\Controller\EditProfileController();
			$editController->setModelInst($user);
			$response = $editController->handleRequest($this->request, true);

			if(!Director::isResponseFullPage($response)) {
				$user->customise(array(
					"content"   => $response,
					"namespace" => $this->namespace
				));
				return \Director::setStringToResponse($response, $user->renderWith("admin/user-header.html", $this->inExpansion));
			}

			return $response;
		}
	}
}
