<?php
defined('IN_GOMA') OR die();

/**
 * defines the basic group.
 *
 * @property    int type
 * @property    string name
 * @property    int usergroup
 * @property    string groupnotification
 *
 * @method ManyMany_DataObjectSet permissions($filter = null, $sort = null)
 * @method ManyMany_DataObjectSet users($filter = null, $sort = null)
 *
 * @package        Goma\Security\Users
 *
 * @author        Goma-Team
 * @license        GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @version        1.2.2
 */
class Group extends DataObject implements PermProvider
{
    /**
     * name of this model
     */
    public static $cname = '{$_lang_group}';

    /**
     * icon for this model
     */
    static public $icon = "system/images/icons/fatcow16/group.png";

    /**
     * database-fields
     *
     * @var array
     */
    static $db = array(
        "name" => 'varchar(100)',
        "type" => 'enum("0", "1", "2")',
        "usergroup" => "int(1)",
        "groupnotification" => "varchar(200)"
    );

    /**
     * fields, whch are searchable
     */
    static $search_fields = array(
        "name"
    );

    /**
     * belongs many-many
     */
    static $belongs_many_many = array(
        "users" => "user",
        "permissions" => "Permission"
    );

    static $index = array(
        "name" => true
    );

    static $default = array(
        "type" => 1
    );

    /**
     * sort by name
     */
    static $default_sort = array("name", "ASC");

    /**
     * the table_name
     */
    static $table = "groups";

    /**
     * generates the form to create a new group
     */
    public function getForm(&$form)
    {
        $form->add(new TabSet("tabs", array(
            new Tab("general", array(
                new TextField("name", lang("name", "Name")),
                new Select("type", lang("grouptype"), array(1 => lang("users"), 2 => lang("admins"))),
                new CheckBox("usergroup", lang("user_defaultgroup")),
                InfoTextField::createFieldWithInfo(
                    new Email("groupnotification", lang("group_notificationmail")),
                    lang("group_notificationmail_info")
                )
            ), lang("general", "general information"))
        )));

        $form->addValidator(new RequiredFields(array("name")), "valdiator");
        $form->addAction(new CancelButton("cancel", lang("cancel")));
        $form->addAction(new FormAction("savegroup", lang("save", "Save"), null, array("green")));

    }

    /**
     * generates the form to edit a group
     *
     * @name getEditForm
     * @access public
     */
    public function getEditForm(&$form)
    {
        // default form
        $form->add($tabs = new TabSet("tabs", array(
            new Tab("general", array(
                new TextField("name", lang("name", "Name")),
                InfoTextField::createFieldWithInfo(
                    new Email("groupnotification", lang("group_notificationmail")),
                    lang("group_notificationmail_info")
                ),
                new CheckBox("usergroup", lang("user_defaultgroup"))
            ), lang("general", "general information"))
        )));

        // permissions
        if (Permission::check("canManagePermissions")) {
            $form->general->add(new ClusterFormField("mypermissions", array(), lang("rights")));

            foreach (Permission::$providedPermissions as $name => $data) {
                $active = ($this->permissions(array("name" => $name))->count() > 0) ? 1 : 0;
                $form->mypermissions->add(new Checkbox($name, parse_lang($data["title"]), $active));
                if (isset($data["description"])) {
                    $form->mypermissions->{$name}->info = parse_lang($data["description"]);
                }
            }

            $form->addDataHandler(array($this, "handlePerms"));
        }

        $form->addValidator(new RequiredFields(array("name")), "validator");

        $form->addAction(new CancelButton("cancel", lang("cancel", "cancel")));
        $form->addAction(new FormAction("savegroup", lang("save", "Save"), null, array("green")));
    }

    /**
     * rewrites permissions to object
     * @param array $data
     * @return array
     */
    public function handlePerms($data)
    {
        $dataset = new ManyMany_DataObjectSet("permission");
        $dataset->setFetchMode(DataObjectSet::FETCH_MODE_CREATE_NEW);
        foreach ($data["mypermissions"] as $key => $val) {
            if ($val) {
                // check for created
                Permission::forceExisting($key);
                if ($record = DataObject::get_one("Permission", array("name" => $key)))
                    $dataset->add($record);
            }
        }

        // get all permissions not listed above and preserve them
        foreach ($this->Permissions(array("name" => "")) as $perm) {
            $dataset->add($perm);
        }

        $data["permissions"] = $dataset;

        return $data;
    }

    /**
     * unsets the default group if this is now default.
     *
     * @param ModelWriter $modelWriter
     * @throws MySQLException
     */
    public function onAfterWrite($modelWriter)
    {
        if ($this->usergroup == 1) {
            DataObject::update("group", array("usergroup" => 0), "recordid != " . $this->recordid . "");
        }

        parent::onAfterWrite($modelWriter);
    }

    /**
     * provide perms
     */
    public function providePerms()
    {
        return array(
            "canManagePermissions" => array(
                "title" => '{$_lang_rights_manage}',
                "default" => array(
                    "type" => "admins"
                ),
                "category" => "ADMIN"
            )
        );
    }
}
