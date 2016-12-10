<?php defined("IN_GOMA") OR die();

/**
 * Base-Model of every User.
 *
 * @package		Goma\Security\Users
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @version		1.3.2
 *
 * @property string nickname
 * @property string email
 * @property string code
 * @property string password
 * @property string title
 * @property string name
 * @property int avatarid
 * @property int code_has_sent
 * @property int status
 * @property Uploads avatar
 * @property bool groupadmin
 *
 * @method ManyMany_DataObjectSet groups($filter = null, $sort = null)
 */
class User extends DataObject implements HistoryData, PermProvider, Notifier
{
	const USERS_PERMISSION = "USERS_MANAGE";
	const ID = "User";

	/**
	 * the name of this dataobject
	 */
	public static $cname = '{$_lang_user}';

	/**
	 * the database fields of a user
	 */
	static $db = array(
		'nickname'		=> 'varchar(200)',
		'name'			=> 'varchar(200)',
		'email'			=> 'varchar(200)',
		'password'		=> 'varchar(1000)',
		'signatur'		=> 'text',
		'status'		=> 'int(2)',
		'phpsess'		=> 'varchar(200)',
		"code"			=> "varchar(200)",
		"code_has_sent" => "Switch",
		"timezone"		=> "timezone",
		"custom_lang"	=> "varchar(10)",
		"groupAdmin"	=> "Switch",
	);


	/**
	 * we add an index to username and password, because of logins
	 */
	static $index = array(
		"login"	=> array("type"	=> "INDEX", "fields" => 'nickname, password')
	);

	/**
	 * fields which are searchable
	 */
	static $search_fields = array(
		"nickname", "name", "email", "signatur"
	);

	/**
	 * the table is users not user
	 */
	static $table = "users";

	/**
	 * use versions here
	 *
	 *@name versions
	 */
	static $versions = true;

	/**
	 * authentications.
	 */
	static $has_many = array("authentications" => array(
		DataObject::RELATION_TARGET => "UserAuthentication",
		DataObject::RELATION_INVERSE => "user"
	));

	/**
	 * every user has one group and an avatar-picture, which is reflected in this relation
	 */
	static $has_one = array(
		"avatar" => array(
			DataObject::RELATION_TARGET => "Uploads",
			DataObject::FETCH_TYPE => DataObject::FETCH_TYPE_EAGER
		)
	);

	/**
	 * every user has additional groups
	 */
	static $many_many = array("groups" => "group");

	/**
	 * sort by name
	 */
	static $default_sort = array("name", "ASC");

	/**
	 * users are activated by default
	 *
	 *@name defaults
	 *@access public
	 */
	static $default = array(
		'status'	=> '1'
	);

	/**
	 * if to automatically use email as nickname.
	 *
	 * @var bool
	 */
	static $useEmailAsNickname = false;

	/**
	 * returns true if you can write
	 * @param User $data
	 * @return bool
	 */

	public function canWrite($data)
	{
		if($data["id"] == member::$id)
			return true;

		if(member::$loggedIn->groupadmin) {
			if($data->groups()->count() == 0) {
				return false;
			}

			foreach($data->groups() as $group) {
				if(member::$loggedIn->groups(array("id" => $group->id))->Count() == 0) {
					return false;
				}
			}

			return true;
		}

		return Permission::check("USERS_MANAGE");
	}

	/**
	 * @param User $record
	 * @return bool
	 */
	public function canPublish($record)
	{
		return $this->canWrite($record);
	}

	/**
	 * @param DataObject $data
	 * @return bool
	 */
	public function canDelete($data)
	{
		return Permission::check("USERS_MANAGE");
	}

	/**
	 * @param DataObject $data
	 * @return bool
	 */
	public function canInsert($data)
	{
		return true;
	}

	/**
	 * forms
	 *
	 * @param Form $form
	 */
	public function getForm(&$form)
	{
		// add default tab
		$form->add(TabSet::create("tabs", array(
			$general = new Tab("general",array(
				new TextField("nickname", lang("USERNAME")),
				new TextField("name", lang("NAME")),
				InfoTextField::createFieldWithInfo(
					new EMail("email", lang("EMAIL")),
					lang("email_correct_info")
				),
				new PasswordField("password", lang("PASSWORD"), ""),
				new PasswordField("repeat", lang("REPEAT_PASSWORD"), ""),
				new langSelect("custom_lang", lang("lang"), Core::$lang)
			), lang("GENERAL"))
		))->setHideTabsIfOnlyOne(true));

		if(self::$useEmailAsNickname) {
			$form->remove("nickname");
			$form->addDataHandler(array($this, "generateNickNameFromEmail"));
		}

		if(Permission::check(self::USERS_PERMISSION) || (member::$loggedIn && member::$loggedIn->groupadmin))
		{
			$groupFilter = Permission::check(self::USERS_PERMISSION) ?
				array() :
				array(
					"id" => member::$loggedIn->groups()->fieldToArray("id")
				);
			$form->add(
				new Manymanydropdown("groups", lang("groups", "Groups"), "name", $groupFilter),
				null,
				"general"
			);
			$form->add(new CheckBox("groupAdmin", lang("groupAdmin")));
		}

		if(!member::login())
		{
			$code = RegisterExtension::$registerCode;
			if($code != "")
			{
				$general->add(new TextField("code", lang("register_code", "Code")));
				$form->addValidator(new FormValidator(array("User", '_validatecode')), "validatecode");
			}
		}

		if(Permission::check("USERS_MANAGE"))
		{
			$form->addValidator(new RequiredFields(array("nickname", "password", "groups", "repeat", "email")), "required_users");
		} else {
			$form->addValidator(new RequiredFields(array("nickname", "password", "repeat", "email")), "required_users");
		}
		$form->addValidator(new FormValidator(array($this, '_validateuser')), "validate_user");

		$form->addAction(new CancelButton("cancel", lang("cancel")));
		$form->addAction(new FormAction("submit", lang("save"), null, array("green")));
	}

	/**
	 * gets the edit-form for profile-edit or admin-edit
	 *
	 * @param Form $form
	 */
	public function getEditForm(&$form) {
		unset($form->result["password"]);

		// if a user is not activated by mail, admin should have a option to activate him manually
		if($this->status == 0) {
			$status = new radiobutton("status", lang("ACCESS", "Access"), array(0 => lang("login_not_unlocked_by_mail", "Not activated by mail yet."),1 => lang("not_locked", "Unlocked"), 2 => lang("locked", "Locked")));
		} else if($this->status == 3) {
			$status = new radiobutton("status", lang("ACCESS", "Access"), array(3 => lang("not_unlocked", "Not activated yet"),1 => lang("not_locked", "Unlocked"), 2 => lang("locked", "Locked")));
		} else {
			$status = new radiobutton("status", lang("ACCESS", "Access"), array(1 => lang("not_locked", "Unlocked"), 2 => lang("locked", "Locked")));
		}

		$form->add(TabSet::create("tabs", array(
			new Tab("general",array(
				new TextField("nickname", lang("username")),
				new TextField("name",  lang("name", "name")),
				new TextField("email", lang("email", "email")),
				new ManyManyDropdown("groups", lang("groups", "Groups"), "name"),
				new CheckBox("groupAdmin", lang("groupAdmin")),
				$status,
				$this->doObject("timezone")->formfield(lang("timezone")),
				new LangSelect("custom_lang", lang("lang")),
				// password management in external window
				new ExternalForm("passwort", lang("password", "password"), lang("edit_password", "change password"), '**********', array($this, "pwdform")),
				new ImageUploadField("avatar", lang("pic", "image")),
				new TextArea("signatur", lang("signatur", "signature"), null, "100px")
			), lang("general"))
		))->setHideTabsIfOnlyOne(true));

		$form->email->info = lang("email_correct_info");
		$form->nickname->disable();
		$form->addValidator(new RequiredFields(array("nickname", "groupid", "email")), "requirefields");

		// group selection for admin
		if($this["id"] == member::$id || (!Permission::check("USERS_MANAGE") && !member::$loggedIn->groupadmin))
		{
			$form->remove("groups");
			$form->remove("status");
			$form->remove("groupadmin");
		}

		$form->addAction(new CancelButton("cancel", lang("cancel")));
		$form->addAction(new FormAction("submit", lang("save"), "publish", array("green")));
	}

	/**
	 * form for password-edit
	 *
	 * @param string $id
	 * @return Form
	 */
	public function pwdform($id = null)
	{
		if(!isset($id)) {
			$id = $this->id;
		}

		$pwdform = new Form($this->controller(), "editpwd", array(
			new HiddenField("id", $id),
			new PasswordField("password",lang("NEW_PASSWORD")),
			new PasswordField("repeat", lang("REPEAT"))
		));

		// check if user needs to give old password or permissions are enough to not adding old one.
		if(Permission::check("USERS_MANAGE") && $id != member::$id) {
			$pwdform->addValidator(new FormValidator(array("User", "validateNewAndRepeatPwd")), "pwdvalidator");
		} else {
			$pwdform->add(new PasswordField("oldpwd", lang("OLD_PASSWORD")), 0);
			$pwdform->addValidator(new FormValidator(array($this, "validatepwd")), "pwdvalidator");
		}

		$pwdform->addAction(new FormAction("submit", lang("save", "save"), "pwdsave"));

		return $pwdform;
	}

	/**
	 * nickname is always lowercase
	 * @param ModelWriter $modelWriter
	 */
	public function onBeforeWrite($modelWriter) {
		parent::onBeforeWrite($modelWriter);

		$this->nickname = strtolower($this->nickname);
	}

	/**
	 * generates nickname -> uses mail as nickname.
	 * @param array $result
	 * @return array
	 */
	public function generateNickNameFromEmail($result) {
		$result["nickname"] = $result["email"];
		return $result;
	}

	/**
	 * @param History $history
	 * @param ModelWriter $modelWriter
	 */
	public function historyCreated($history, $modelWriter)
	{
		$recordInfo = $this->generateHistoryData($history);
		if($recordInfo && $recordInfo["relevant"]) {
			if (DataObject::count("group", array("groupnotification" => array("!=", ""))) > 0) {
				/** @var Group $group */
				foreach ($this->groups() as $group) {
					if ($group->groupnotification) {
						$mail = new Mail("noreply@" . $_SERVER["SERVER_NAME"]);
						$mail->sendHTML($group->groupnotification, lang("group_user_changed"),
							$this->customise(array("history" => $history, "recordInfo" => $recordInfo))
								->renderWith("mail/userChanged.html")
						);
					}
				}
			}
		}
	}

	/**
	 * validates code for form.
	 *
	 * @param FormValidator $obj
	 * @throws FormInvalidDataException
	 */
	public static function _validateCode($obj)
	{
		$value = $obj->getForm()->result["code"];
		if(is_string($value) && RegisterExtension::$registerCode != "" && RegisterExtension::$registerCode != $value) {
			throw new FormInvalidDataException("code", lang("register_code_wrong", "The Code was wrong!"));
		}
	}

	/**
	 * validates an new user
	 * @param FormValidator $obj
	 * @throws FormInvalidDataException
	 * @throws FormMultiFieldInvalidDataException
	 */
	public function _validateuser($obj)
	{
		$problems = array();
		if(DataObject::count("user", array("nickname" => $obj->getForm()->result["nickname"])) > 0)
		{
			$problems["nickname"] = lang("register_username_bad", "The username is already taken.");
		}

		if($obj->getForm()->result["password"] != $obj->getForm()->result["repeat"] || $obj->getForm()->result["repeat"] == "")
		{
			$problems["password"] = lang("passwords_not_match");
			$problems["repeat"]	= "";
		}

		if($problems) {
			throw new FormMultiFieldInvalidDataException($problems);
		}
	}

	/**
	 * sets the password with md5
	 * @param string $value
	 */
	public function setPassword($value)
	{
		$this->setField("password", Hash::getHashFromDefaultFunction($value));
	}

	/**
	 * password should not be visible
	 *
	 * @return string
	 */
	public function getPassword() {
		return "";
	}

	/**
	 * validates new and old passwords and returns error string when error happened.
	 * @param FormValidator $obj
	 * @throws DataNotFoundException
	 * @throws FormInvalidDataException
	 */
	public function validatepwd($obj) {
		if(isset($obj->getForm()->result["oldpwd"]))
		{
			$data = DataObject::get_one("user", array("id" => $obj->getForm()->result["id"]));
			if($data) {
				// export data
				$data = $data->ToArray();
				$pwd = $data["password"];

				// check old password
				if(Hash::checkHashMatches($obj->getForm()->result["oldpwd"], $pwd))
				{
					self::validateNewAndRepeatPwd($obj);
				} else {
					throw new FormInvalidDataException("password", "password_wrong");
				}
			} else {
				throw new DataNotFoundException("error");
			}
		} else
		{
			throw new FormInvalidDataException("password", "password_wrong");
		}
	}

	/**
	 * validates new password and repeat matches.
	 *
	 * @param FormValidator $obj
	 * @throws FormInvalidDataException
	 */
	public static function validateNewAndRepeatPwd($obj) {
		if(isset($obj->getForm()->result["password"], $obj->getForm()->result["repeat"]) && $obj->getForm()->result["password"] != "")
		{
			if($obj->getForm()->result["password"] != $obj->getForm()->result["repeat"])
			{
				throw new FormInvalidDataException("password", "passwords_not_match");
			}
		} else {
			throw new FormInvalidDataException("password", "password_cannot_be_empty");
		}
	}

	/**
	 * returns the title of the person
	 *
	 * @return string
	 */
	public function title() {
		if($this->fieldGet("name")) {
			return $this->fieldGet("name");
		}

		return $this->nickname;
	}

	/**
	 * returns the representation of this record
	 *
	 * @name generateResprensentation
	 * @access public
	 * @return string
	 */
	public function generateRepresentation($link = false) {
		$title = $this->title;

		$title = $this->image()->setSize(20, 20) . " " . $title;

		if($link)
			$title = '<a href="member/'.$this->id.'" target="_blank">' . $title . '</a>';

		return $title;
	}

	/**
	 * performs a login
	 *
	 *@name performLogin
	 *@access public
	 */
	public function performLogin() {
		if($this->custom_lang != Core::$lang && $this->custom_lang) {
			i18n::Init(i18n::SetSessionLang($this->custom_lang));
		}

		// now write login to database
		if($this->code_has_sent == 1) {
			$this->generateCode();
		}

		$this->callExtending("performLogin");

		if($this->wasChanged()) {
			$this->writeToDB(false, true);
		}
	}

	/**
	 * regenerates and gives back code.
	 *
	 * @param bool if you want to send the code to a user
	 * @param bool if write Entity.
	 * @return string
	 */
	public function generateCode($send = false, $write = false) {
		$this->code = randomString(20);
		$this->code_has_sent = (int) $send;

		if($write) {
			$this->writeToDB(false, true);
		}

		return $this->code;
	}

	/**
	 * performs a logout
	 *
	 *@name performLogout
	 *@access public
	 */
	public function performLogout() {
		$this->callExtending("performLogout");

		if($this->wasChanged()) {
			$this->writeToDB(false, true);
		}
	}

	/**
	 * returns text what to show about the event
	 *
	 * @name generateHistoryData
	 * @access public
	 * @param History $record
	 * @return array|bool
	 */
	public static function generateHistoryData($record) {
		if(!$record->newversion()) {
			return false;
		}

		$lang = self::getHistoryLang($record);
		$lang = str_replace('$userUrl', BASE_URI . "member/" . $record->newversion()->id . URLEND, $lang);
		$lang = str_replace('$euser', convert::Raw2text($record->newversion()->title), $lang);

		return array(
			"icon" => self::getHistoryIcon($record),
			"text" => $lang,
			"relevant" => !!$record->autor
		);
	}

	/**
	 * returns language-string for current event.
	 *
	 * @param History $record
	 * @return string
	 */
	public static function getHistoryLang($record) {
		switch($record->action) {
			case IModelRepository::COMMAND_TYPE_UPDATE:
			case IModelRepository::COMMAND_TYPE_PUBLISH:
			case "update":
			case "publish":
				if($record->autorid == $record->newversion()->id) {
					return lang("h_profile_update", '$user updated the own profile');
				} else {
					if($record->newversion()->status != $record->oldversion()->status) {
						if($record->newversion()->status == 2) {
							return lang("h_user_locked", '$user locked the user <a href="$userUrl">$euser</a>');
						} else {
							return lang("h_user_unlocked", '$user unlocked the user <a href="$userUrl">$euser</a>');
						}
					}
					// admin changed profile
					return lang("h_user_update", '$user updated the user <a href="$userUrl">$euser</a>');
				}
				break;
			case IModelRepository::COMMAND_TYPE_INSERT:
			case "insert":
				return lang("h_user_create", '$user created the user <a href="$userUrl">$euser</a>');
				break;
			case IModelRepository::COMMAND_TYPE_DELETE:
			case "remove":
				return lang("h_user_remove", '$user removed the user $euser');
				break;
			default:
				return "Unknown event " . $record->action;
		}
	}

	/**
	 * returns icon for history-record.
	 *
	 * @param History record
	 * @return string path
	 */
	public static function getHistoryIcon($record) {
		$icon = array(
			"insert" => "images/icons/fatcow16/user_add.png",
			IModelRepository::COMMAND_TYPE_INSERT => "images/icons/fatcow16/user_add.png",
			"remove" => "images/icons/fatcow16/user_delete.png",
			IModelRepository::COMMAND_TYPE_DELETE => "images/icons/fatcow16/user_delete.png",
		);

		return isset($icon[$record->action]) ? $icon[$record->action] : "images/icons/fatcow16/user_edit.png";
	}

	/**
	 * returns a comma-seperated list of all groups
	 *
	 * @name getGroupList
	 * @access public
	 * @return string
	 */
	public function getGroupList() {
		$str = "";
		$i = 0;
		foreach($this->groups() as $group) {
			if($i == 0) {
				$i++;
			} else {
				$str .= ", ";
			}
			$str .= Convert::raw2text($group->name);
		}
		return $str;
	}

	/**
	 * provides some permissions
	 *
	 * @name providePerms
	 * @access public
	 * @return array
	 */
	public function providePerms() {
		return array(
			self::USERS_PERMISSION	=> array(
				"title"		=> '{$_lang_administration}: {$_lang_user}',
				"default"	=> array(
					"type"	 	=> "admins",
					"inherit"	=> "superadmin"
				),
				"category"	=> "superadmin"
			)
		);
	}

	/**
	 * gets the avatar
	 *
	 * @return Uploads
	 */
	public function getImage() {
		if($this->avatar && $this->avatar->realfile) {
			if((ClassInfo::exists("gravatarimagehandler") && $this->avatar->filename == "no_avatar.png" && $this->avatar->classname != "gravatarimagehandler") || $this->avatar->classname == "gravatarimagehandler") {
				$this->avatarid = 0;
				return new GravatarImageHandler(array("email" => $this->email));
			}
			return $this->avatar;
		} else {
			return new GravatarImageHandler(array("email" => $this->email));
		}
	}

	/**
	 * returns information about notification-settings of this class
	 * these are:
	 * - title
	 * - icon
	 * this API may extended with notification settings later
	 *
	 * @name NotifySettings
	 * @access public
	 * @return array
	 */
	public static function NotifySettings() {
		return array("title" => lang("user"), "icon" => "images/icons/fatcow16/user@2x.png");
	}

	/**
	 * unique identifier of this user.
	 */
	public function uniqueID() {
		return md5($this->id . "_" . $this->nickname . "_" . $this->password . "_" . $this->last_modified);
	}
}

StaticsManager::AddSaveVar(User::class, "useEmailAsNickname");