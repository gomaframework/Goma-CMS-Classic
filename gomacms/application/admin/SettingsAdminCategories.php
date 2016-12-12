<?php
namespace Goma\CMS\admin;
use Cacher;
use CheckBox;
use DataObject;
use FileSystem;
use Goma\Controller\Category\AbstractCategoryController;
use LogicException;
use Newsettings;

defined("IN_GOMA") OR die();
/**
 * Settings category view.
 *
 * @author Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @package Goma-CMS/Admin
 * @version 1.0.5
 */
class SettingsAdminCategories extends AbstractCategoryController {

    /**
     * @var array
     */
    public $allowed_actions = array(
        "general", "security"
    );

    /**
     * returns categories in form method => category title
     * @return array
     */
    public function provideCategories()
    {
        $categories = array(
            "general"   => lang("general"),
            "security"  => lang("security")
        );

        foreach(\ClassInfo::getChildren(NewSettings::class) as $child) {
            if(\StaticsManager::getStatic($child, "tab", true)) {
                $categories[str_replace("\\", "_", $child)] = parse_lang(\StaticsManager::getStatic($child, "tab", true));
            } else {
                $instance = new $child();
                $categories[str_replace("\\", "_", $child)] = parse_lang($instance->tab);
            }
        }

        return $categories;
    }

    /**
     * @param null $request
     */
    public function Init($request = null)
    {
        parent::Init($request);

        $this->model_inst = DataObject::get("newsettings", array("id" => 1))->first();
    }

    /**
     * @param string $action
     * @return bool
     */
    public function hasAction($action)
    {
        $probClass = str_replace("_", "\\", $action);
        if(\ClassInfo::exists($probClass) && is_subclass_of($probClass, Newsettings::class)) {
            return true;
        }

        return parent::hasAction($action);
    }

    /**
     * @param string $action
     * @return false|mixed|null
     */
    public function handleAction($action)
    {
        $probClass = str_replace("_", "\\", $action);
        if(\ClassInfo::exists($probClass) && is_subclass_of($probClass, Newsettings::class)) {
            $this->currentAction = $action;

            if($action != "index" && $title = $this->getActiveActionTitle()) {
                \Core::setTitle($title);
                \Core::addBreadcrumb($title, $this->namespace . "/" . $action . URLEND);
            }

            $form = (new \Form($this, $action, array(

            ), array(
                new \CancelButton("cancel", lang("cancel")),
                new \FormAction("save", lang("save"), "submit_form", array("green"))
            )))->setModel(
                $this->model_inst
            );

            /** @var Newsettings $instance */
            $instance = new $probClass($this->model_inst->ToArray());
            $instance->getFormFromDB($form);

            return $form->render();
        }

        return parent::handleAction($action);
    }

    /**
     *
     */
    public function security() {
        return (new \Form($this, "security", array(
            \InfoTextField::createFieldWithInfo(
                new Checkbox("safe_mode", lang("safe_mode"), FileSystem::$safe_mode),
                lang("safe_mode_info")
            )
        ), array(
            new \CancelButton("cancel", lang("cancel")),
            new \FormAction("save", lang("save"), "submit_form", array("green"))
        )))->setModel(
            $this->model_inst
        )->render();
    }

    /**
     * @return string
     */
    public function general()
    {
        return parent::Form(null, $this->model_inst, array(), true, "submit_form");
    }

    /**
     * writes correct settings to correct location
     *
     * @param array $data
     * @param Form|null $form
     * @param null $model
     * @return string|void
     * @throws LogicException
     * @throws \Exception
     * @throws \ProjectConfigWriteException
     */
    public function submit_form($data, $form, $model = null) {
        $cacher = new Cacher("settings");
        $cacher->delete();

        if(isset($data["lang"], $data["status"], $data["timezone"], $data["date_format_date"])) {
            if(!file_exists(ROOT . LANGUAGE_DIRECTORY . $data["lang"])) {
                throw new LogicException("Selected language is not existing!");
            }

            $status = (SITE_MODE == STATUS_DISABLED) ? STATUS_DISABLED : $data["status"];
            writeProjectConfig(array(	'lang' => $data["lang"],
                                         "status" => $status,
                                         "safe_mode" => isset($data["safe_mode"]) ? $data["safe_mode"] : FileSystem::$safe_mode,
                                         "timezone" => $data["timezone"],
                                         "date_format_date" => $data["date_format_date"],
                                         "date_format_time" => $data["date_format_time"]));

            if(isset($data["safe_mode"]) && FileSystem::$safe_mode != $data["safe_mode"]) {
                FileSystem::$safe_mode = (bool) $data["safe_mode"];
                register_shutdown_function(array("settingsAdmin", "upgradeSafeMode"));
            }
        } else

        if(isset($data["safe_mode"])) {
            writeProjectConfig(array(	'lang' => defined("PROJECT_LANG") ? PROJECT_LANG : DEFAULT_LANG,
                                         "status" => SITE_MODE,
                                         "safe_mode" => $data["safe_mode"],
                                         "timezone" => PROJECT_TIMEZONE,
                                         "date_format_date" => DATE_FORMAT_DATE,
                                         "date_format_time" => DATE_FORMAT_TIME));
            FileSystem::$safe_mode = (bool) $data["safe_mode"];
            register_shutdown_function(array("settingsAdmin", "upgradeSafeMode"));
        }

        return parent::safe($data, $form, $model);
    }
}
