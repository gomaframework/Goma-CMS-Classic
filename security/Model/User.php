<?php defined("IN_GOMA") OR die();

/**
 * Base-Model of every User.
 *
 * @package        Goma\Security\Users
 *
 * @author        Goma-Team
 * @license        GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @version        1.3.2
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
 * @property ImageUploads avatar
 * @property bool groupadmin
 *
 * @method ManyMany_DataObjectSet groups($filter = null, $sort = null)
 */
class User extends DataObject implements PermProvider
{
    const USERS_PERMISSION = "USERS_MANAGE";

    /**
     * the name of this dataobject
     */
    public static $cname = '{$_lang_user}';

    /**
     * the database fields of a user
     */
    static $db = array(
        'nickname' => 'varchar(200)',
        'name' => 'varchar(200)',
        'email' => 'varchar(200)',
        'password' => 'varchar(1000)',
        'signatur' => 'text',
        'status' => 'int(2)',
        'phpsess' => 'varchar(200)',
        "code" => "varchar(200)",
        "code_has_sent" => "Switch",
        "timezone" => "timezone",
        "custom_lang" => "varchar(10)",
        "groupAdmin" => "Switch",
    );


    /**
     * we add an index to username and password, because of logins
     */
    static $index = array(
        "login" => array("type" => "INDEX", "fields" => 'nickname, password'),
        "name" => true
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
     * @name versions
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

    static $many_many_sort = array(
        "groups" => "type DESC"
    );

    /**
     * sort by name
     */
    static $default_sort = array("name", "ASC");

    /**
     * users are activated by default
     */
    static $default = array(
        'status' => '1'
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
        if ($data["id"] == member::$id)
            return true;

        if (member::$loggedIn->groupadmin) {
            if ($data->groups()->count() == 0) {
                return false;
            }

            foreach ($data->groups() as $group) {
                if (member::$loggedIn->groups(array("id" => $group->id))->Count() == 0) {
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
            $general = new Tab("general", array(
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

        if (self::$useEmailAsNickname) {
            $form->remove("nickname");
            $form->addDataHandler(array($this, "generateNickNameFromEmail"));
        }

        if (Permission::check(self::USERS_PERMISSION) || (member::$loggedIn && member::$loggedIn->groupadmin)) {
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

        if (!member::login()) {
            $code = RegisterExtension::$registerCode;
            if ($code != "") {
                $general->add(new TextField("code", lang("register_code", "Code")));
                $form->addValidator(new FormValidator(array("User", '_validatecode')), "validatecode");
            }
        }

        if (Permission::check("USERS_MANAGE")) {
            $form->addValidator(new RequiredFields(array("nickname", "password", "groups", "repeat", "email")), "required_users");
        } else {
            $form->addValidator(new RequiredFields(array("nickname", "password", "repeat", "email")), "required_users");
        }
        $form->addValidator(new FormValidator(array($this, '_validateuser')), "validate_user");

        $form->addAction(new CancelButton("cancel", lang("cancel")));
        $form->addAction(new FormAction("submit", lang("register"), null, array("green")));
    }

    /**
     * gets the edit-form for profile-edit or admin-edit
     *
     * @param Form $form
     */
    public function getEditForm(&$form)
    {
        unset($form->result["password"]);

        // if a user is not activated by mail, admin should have a option to activate him manually
        if ($this->status == 0) {
            $status = new radiobutton("status", lang("ACCESS", "Access"), array(0 => lang("login_not_unlocked_by_mail", "Not activated by mail yet."), 1 => lang("not_locked", "Unlocked"), 2 => lang("locked", "Locked")));
        } else if ($this->status == 3) {
            $status = new radiobutton("status", lang("ACCESS", "Access"), array(3 => lang("not_unlocked", "Not activated yet"), 1 => lang("not_locked", "Unlocked"), 2 => lang("locked", "Locked")));
        } else {
            $status = new radiobutton("status", lang("ACCESS", "Access"), array(1 => lang("not_locked", "Unlocked"), 2 => lang("locked", "Locked")));
        }

        $form->add(TabSet::create("tabs", array(
            new Tab("general", array(
                new TextField("nickname", lang("username")),
                new TextField("name", lang("name", "name")),
                new TextField("email", lang("email", "email")),
                new ManyManyDropdown("groups", lang("groups", "Groups"), "name"),
                new CheckBox("groupAdmin", lang("groupAdmin")),
                $status,
                $this->doObject("timezone")->formfield(lang("timezone")),
                new LangSelect("custom_lang", lang("lang")),
                new ImageUploadField("avatar", lang("pic", "image")),
                new TextArea("signatur", lang("signatur", "signature"), null, "100px")
            ), lang("general"))
        ))->setHideTabsIfOnlyOne(true));

        $form->email->info = lang("email_correct_info");
        $form->nickname->disable();
        $form->addValidator(new RequiredFields(array("nickname", "groupid", "email")), "requirefields");

        // group selection for admin
        if ($this["id"] == member::$id || (!Permission::check("USERS_MANAGE") && !member::$loggedIn->groupadmin)) {
            $form->remove("groups");
            $form->remove("status");
            $form->remove("groupadmin");
        }

        $form->addAction(new CancelButton("cancel", lang("cancel")));
        $form->addAction(new FormAction("submit", lang("save"), "publish", array("green")));
    }

    /**
     * nickname is always lowercase
     * @param ModelWriter $modelWriter
     */
    public function onBeforeWrite($modelWriter)
    {
        parent::onBeforeWrite($modelWriter);

        $this->nickname = strtolower($this->nickname);
    }

    /**
     * clear caches before writing manymany.
     *
     * @param array $manipulation
     * @param ManyMany_DataObjectSet $dataset
     * @param array $writeData
     * @return void
     */
    public function onBeforeManipulateManyMany(&$manipulation, $dataset, $writeData)
    {
        parent::onBeforeManipulateManyMany($manipulation, $dataset, $writeData);

        self::$permissionCache[$this->id] = array();
        $this->groupIds = null;
        $this->groupType = null;
    }

    /**
     * generates nickname -> uses mail as nickname.
     * @param array $result
     * @return array
     */
    public function generateNickNameFromEmail($result)
    {
        $result["nickname"] = $result["email"];
        return $result;
    }

    /**
     * @param History $history
     * @param ModelWriter $modelWriter
     */
    public function historyCreated($history, $modelWriter)
    {
        if ($history != null) {
            $recordInfo = $history->historyData();
            if ($recordInfo && isset($recordInfo["relevant"]) && $recordInfo["relevant"]) {
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
        if (is_string($value) && RegisterExtension::$registerCode != "" && RegisterExtension::$registerCode != $value) {
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
        if (DataObject::count("user", array("nickname" => $obj->getForm()->result["nickname"])) > 0) {
            $problems["nickname"] = lang("register_username_bad", "The username is already taken.");
        }

        if ($obj->getForm()->result["password"] != $obj->getForm()->result["repeat"] || $obj->getForm()->result["repeat"] == "") {
            $problems["password"] = lang("passwords_not_match");
            $problems["repeat"] = "";
        }

        if ($problems) {
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
    public function getPassword()
    {
        return "";
    }

    /**
     * returns the title of the person
     *
     * @return string
     */
    public function title()
    {
        if ($this->fieldGet("name")) {
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
    public function generateRepresentation($link = false)
    {
        $title = $this->title;

        $title = $this->image()->setSize(20, 20) . " " . $title;

        if ($link)
            $title = '<a href="member/' . $this->id . '" target="_blank">' . $title . '</a>';

        return $title;
    }

    /**
     * performs a login
     */
    public function performLogin()
    {
        if ($this->custom_lang != Core::$lang && $this->custom_lang) {
            i18n::Init(i18n::SetSessionLang($this->custom_lang));
        }

        // now write login to database
        if ($this->code_has_sent == 1) {
            $this->generateCode();
        }

        $this->callExtending("performLogin");

        if ($this->wasChanged()) {
            $this->writeToDB(false, true);
        }
    }

    /**
     * regenerates and gives back code.
     *
     * @param bool if code should has been sent to user
     * @param bool if write Entity.
     * @return string
     */
    public function generateCode($send = false, $write = false)
    {
        $this->code = randomString(20);
        $this->code_has_sent = $send;

        if ($write) {
            $this->writeToDB(false, true);
        }

        return $this->code;
    }

    /**
     * performs a logout
     */
    public function performLogout()
    {
        $this->callExtending("performLogout");

        if ($this->wasChanged()) {
            $this->writeToDB(false, true);
        }
    }

    /**
     * returns a comma-seperated list of all groups
     *
     * @name getGroupList
     * @access public
     * @return string
     */
    public function getGroupList()
    {
        $str = "";
        $i = 0;
        foreach ($this->groups() as $group) {
            if ($i == 0) {
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
    public function providePerms()
    {
        return array(
            self::USERS_PERMISSION => array(
                "title" => '{$_lang_administration}: {$_lang_user}',
                "default" => array(
                    "type" => "admins",
                    "inherit" => "superadmin"
                ),
                "category" => "superadmin"
            )
        );
    }

    /**
     * gets the avatar
     *
     * @return ImageUploads
     */
    public function getImage()
    {
        if ($this->avatar && $this->avatar->realfile) {
            if ((ClassInfo::exists("gravatarimagehandler") && $this->avatar->filename == "no_avatar.png" && $this->avatar->classname != "gravatarimagehandler") || $this->avatar->classname == "gravatarimagehandler") {
                $this->avatarid = 0;
                return new GravatarImageHandler(array("email" => $this->email));
            }

            return $this->avatar;
        } else {
            return new GravatarImageHandler(array("email" => $this->email));
        }
    }

    /**
     * unique identifier of this user.
     */
    public function uniqueID()
    {
        return md5($this->id . "_" . $this->nickname . "_" . $this->password . "_" . $this->last_modified);
    }

    /**
     * cache for permissions.
     *
     * @var array
     */
    private static $permissionCache = array();

    /**
     * @var int
     */
    private $groupType = null;

    /**
     * @var null{array
     */
    private $groupIds;

    /**
     * @return array
     */
    public function groupIds() {
        if(isset($this->groupIds)) {
            return $this->groupIds;
        }

        return $this->groups()->fieldToArray("id");
    }

    /**
     * returns group type of user.
     * return value is either 2, 1 or 0
     * it is null if no group is assigned to user.
     *
     * @return int|null
     */
    public function getGroupType() {
        if(!isset($this->groupType)) {
            $this->groupType = $this->groups()->first() != null ? $this->groups()->first()->type : null;
        }

        return $this->groupType;
    }

    /**
     * @param $permissionCode
     * @return bool
     */
    public function hasPermissions($permissionCode) {
        $permissionCode = strtolower($permissionCode);

        if(isset(self::$permissionCache[$this->id][$permissionCode])) {
            return self::$permissionCache[$this->id][$permissionCode];
        }

        if ($permissionCode != "superadmin" && $this->hasPermissions("superadmin")) {
            return true;
        }

        if (RegexpUtil::isNumber($permissionCode)) {
            return $this->intRight($permissionCode);
        } else {
            if (isset(Permission::$providedPermissions[$permissionCode])) {
                /** @var Permission $data */
                if ($data = DataObject::get_one("Permission", array("name" => array("LIKE", $permissionCode)))) {
                    self::$permissionCache[$this->id][$permissionCode] = $data->hasPermission($this);
                    $data->forModel = "permission";
                    if ($data->type != "groups") {
                        $data->writeToDB(false, true, 2, false, false);
                    }
                    return self::$permissionCache[$this->id][$permissionCode];
                } else {

                    if (isset(Permission::$providedPermissions[$permissionCode]["default"]["inherit"]) &&
                        strtolower(Permission::$providedPermissions[$permissionCode]["default"]["inherit"]) != $permissionCode) {
                        if ($data = Permission::forceExisting(Permission::$providedPermissions[$permissionCode]["default"]["inherit"])) {
                            $perm = clone $data;
                            $perm->consolidate();
                            $perm->id = 0;
                            $perm->parentid = 0;
                            $perm->name = $permissionCode;
                            $data->forModel = "permission";
                            self::$permissionCache[$this->id][$permissionCode] = $perm->hasPermission($this);
                            $perm->writeToDB(true, true, 2);
                            return self::$permissionCache[$this->id][$permissionCode];
                        }
                    }
                    $perm = new Permission(array_merge(Permission::$providedPermissions[$permissionCode]["default"], array("name" => $permissionCode)));

                    if (isset(Permission::$providedPermissions[$permissionCode]["default"]["type"]))
                        $perm->setType(Permission::$providedPermissions[$permissionCode]["default"]["type"]);

                    self::$permissionCache[$this->id][$permissionCode] = $perm->hasPermission($this);
                    $perm->writeToDB(true, true, 2, false, false);
                    return self::$permissionCache[$this->id][$permissionCode];;
                }
            } else {
                if ($this->intRight(7)) {
                    return true; // soft allow
                }

                return false; // soft deny
            }
        }
    }

    /**
     * returns integer based permission system.
     *
     * @param int $needed
     * @return bool
     */
    protected function intRight($needed) {
        if (!defined("SQL_INIT"))
            return true;

        if ($needed < 7) {
            return true;
        }

        if ($needed < 10) {
            return ($this->getGroupType() > 1);
        }

        if ($needed == 10) {
            return $this->hasPermissions("superadmin");
        }
    }
}

StaticsManager::AddSaveVar(User::class, "useEmailAsNickname");
