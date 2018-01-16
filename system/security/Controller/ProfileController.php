<?php use Goma\Security\Controller\EditProfileController;

defined("IN_GOMA") OR die();

i18n::AddLang("/members");

/**
 * this class provides Profile-Views for User.
 *
 * @package     goma framework
 * @link        http://goma-cms.org
 * @license:    LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 * @author      Goma-Team
 * @version     1.0
 *
 * last modified: 08.05.2016
 */
class ProfileController extends FrontedController {

	/**
	 * @var array
	 */
	public $url_handlers = array(
		"edit" 		=> "edit",
		"login" 	=> "login",
		"logout" 	=> "logout",
		"switchlang"=> "switchlang"
	);

	/**
	 * allowed actions
	 */
	public $allowed_actions = array("edit", "login", "logout", "switchlang");

	/**
	 * profile actions
	 */
	public $profile_actions;

	/**
	 * tabs
	 */
	protected $tabs;

	/**
	 * define right model.
	 */
	public $model = "user";

    /**
     * @var array
     */
	public static $ssoDomains = array();

	/**
	 * shows the edit-screen
	 * @return string
	 */
	public function edit() {
		if(!isset(Member::$loggedIn)) {
			return GomaResponse::redirect(BASE_URI . "profile/login/?redirect=".urlencode(ROOT_PATH . BASE_SCRIPT . "profile/edit/")."");
		}

		Core::addBreadCrumb(lang("profile"), "profile/");
		Core::addBreadCrumb(lang("edit_profile"), "profile/edit/");
		Core::setTitle(lang("edit_profile"));

		$controller = new EditProfileController();
		$controller->setModelInst(Member::$loggedIn);
		return $controller->handleRequest($this->request, true);
	}

	/**
	 * default screen
	 *
	 * @param string|null $id
	 * @return bool|string
	 */
	public function index($id = null) {
		$id = ($id == null) ? $this->getParam("action") : $id;
		if($id == null) {
			if($id = member::$id) {
				return GomaResponse::redirect(BASE_URI . BASE_SCRIPT . "profile/" . $id . URLEND);
			} else {
				return GomaResponse::redirect(BASE_URI);
			}
		}

		$this->tabs = new DataSet();
		$this->profile_actions = new HTMLNode("ul");

		if((isset(member::$id) && $id == member::$id)) {
			$this->profile_actions->append(new HTMLNode("li", array(), new HTMLNode("a", array("href" => "profile/edit/", "class" => "noAutoHide"), lang("edit_profile"))));
		}

		// get info-tab
		$userdata = DataObject::get_one(User::class, array("id" => $id));
		if(!$userdata || $userdata->status != 1) {
			return null;
		}

		$userdata->editable = ((isset(member::$id) && $id == member::$id)) ? true : false;
		$info = $userdata->renderWith("profile/info.html");
		$this->tabs->add(array(
			"name"		=> "info",
			"title" 	=> lang("general", "General Information"),
			"content"	=> $info
		));

		Core::addBreadcrumb($userdata->title, URL . URLEND);
		Core::setTitle($userdata->title);

		$this->callExtending("beforeRender", $userdata);

		return $userdata->customise(array("tabs" => $this->tabs, "profile_actions" => $this->profile_actions->render()))->renderWith("profile/profile.html");
	}

	/**
	 * login-method
	 */
	public function login() {
		Core::addBreadCrumb(lang("login"), "profile/login/");
		Core::setTitle(lang("login"), "profile/login/");

		// if login and a user want's to login as someone else, we should log him out
		if(isset(Member::$loggedIn) && isset($this->getRequest()->post_params["pwd"]))
		{
			AuthenticationService::sharedInstance()->doLogout();
			// if a user goes to login and is logged in, we redirect him home
		} else if(isset(Member::$loggedIn)) {
			return GomaResponse::redirect($this->getLoginRedirect());
		}

		// if no login and pwd and username isset, we login
		if(isset($this->getRequest()->post_params["user"], $this->getRequest()->post_params["pwd"]))
		{
			if(member::doLogin($this->getRequest()->post_params["user"], $this->getRequest()->post_params["pwd"]))
			{
				return GomaResponse::redirect($this->getLoginRedirect());
			}
		}

		// else we show template

		return tpl::render("profile/login.html");
	}

    /**
     * gets login redirect.
     */
	protected function getLoginRedirect() {
        if(isset($this->request->get_params["redirect"])) {
            $domains = self::$ssoDomains;
            $domains[] = $this->request->getServerName();
            foreach($domains as $domain) {
                if(isURLFromServer($this->request->get_params["redirect"], $domain)) {
                    if(isset($this->request->get_params["sessionparam"]) && is_string($this->request->get_params["sessionparam"])) {
                        $redirect = self::addParamToUrl($this->request->get_params["redirect"], $this->request->get_params["sessionparam"], livecounter::getUserIdentifier());
                    } else {
                        $redirect = $this->request->get_params["redirect"];
                    }

                    return htmlentities($redirect, ENT_COMPAT, "UTF-8", false);
                }
            }
        }

        return ROOT_PATH;
    }

	/**
	 * switch-lang view
	 *
	 * @return string
	 */
	public function switchlang() {
		return tpl::render("switchlang.html");
	}

	/**
	 * logout-method
	 */
	public function	logout()
	{
		if(isset($this->getRequest()->post_params["logout"])) {
			AuthenticationService::sharedInstance()->doLogout();
		}

		return GomaResponse::redirect($this->getRedirect($this));
	}
}
