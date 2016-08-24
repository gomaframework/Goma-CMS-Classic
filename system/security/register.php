<?php
/**
 * @package        Goma\Security\Users
 *
 * @author        Goma-Team
 * @license        GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */

defined('IN_GOMA') OR die();

/**
 * extends the user-class with a registration-form.
 *
 * @package        Goma\Security\Users
 *
 * @author        Goma-Team
 * @license        GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @version        2.3
 *
 * @method Controller getOwner
 */
class RegisterExtension extends ControllerExtension
{
	/**
	 * a bool which indicates whether registration is enabled or disabled
	 *
	 * @name enabled
	 * @access public
	 */
	public static $enabled = false;

	/**
	 * a bool which indicates whether a new user needs to validate his email-adresse or not
	 *
	 * @name validateMail
	 * @access public
	 */
	public static $validateMail = true;

	/**
	 * registration code, if set to null or "" no code is required
	 *
	 * @name registerCode
	 * @access public
	 */
	public static $registerCode;

	/**
	 * set to true when a new user must be validated by the administrator.
	 */
	public static $mustBeValidated = false;

	/**
	 * email to notify when a user registers that should be validated.
	 * also allowed is an array or commma-seperated value.
	 * if set to 0, every user with the permission to validate can validate users.
	 */
	public static $validationMail = null;

	/**
	 * add custom actions
	 *
	 * @name allowed_actions
	 * @access public
	 */
	public $allowed_actions = array(
		"register", "resendActivation", "activate"
	);

	/**
	 * register custom method
	 *
	 * @name extra_methods
	 */
	public static $extra_methods = array("register", "doRegister", "resendActivation", "activate");

	/**
	 * handles basic register stuff.
	 *
	 * @return string
	 * @throws Exception
	 */
	public function register()
	{
		// define title of this page
		Core::setTitle(lang("register"));
		Core::addBreadCrumb(lang("register"), "profile/register/");

		// check if logged in
		if (member::login()) {
			HTTPResponse::Redirect(BASE_URI);
			exit;

			// check if link from e-mail
		} else if (isset($this->getRequest()->get_params["activate"])) {
			/** @var User $data */
			$data = DataObject::get_one("user", array("code" => $this->getRequest()->get_params["activate"]));

			if ($data && $data->status != 2) {
				$data->code = randomString(10); // new code
				if (self::$mustBeValidated) {
					$data->status = 3; // activation
					$this->sendMailToAdmins($data);
				} else {
					$data->status = 1; // activation
				}

				$data->writeToDB(false, true);
				if (self::$mustBeValidated) {
					return $this->renderView($data, lang("register_require_acitvation"));
				} else {
					return $this->renderView($data, lang("register_ok"));
				}
			} else {
				return $this->renderView(isset($_GET["email"]) ? $_GET["email"] : "", lang("register_not_found"), isset($_GET["email"]));
			}

			// check if registering is not available on this page
		} else if (!self::$enabled) {
			return "<div class=\"notice\">" . lang("register_disabled", "You cannot register on this site!") . "</div>";

			// great, let's show a form
		} else {
			$user = new User();

			$this->callExtending("extendNewUserForRegistration", $user);

			return $user->controller($this->getOwner())->form(false, false, array(), false, "doregister");
		}
	}

	/**
	 * resends the activation mail.
	 */
	public function resendActivation()
	{
		if ($this->getParam("email") && !member::login()) {
			/** @var User $user */
			$user = DataObject::get_one("user", array("email" => $this->getParam("email")));
			if ($user && $user->status != 1) {
				$this->sendMail($user);
				return $this->renderView($user, lang("register_resend"), true);
			} else {
				return "";
			}
		} else {
			return $this->redirectBack();
		}
	}

	/**
	 * sends activation mail.
	 * @param User $data
	 * @return bool
	 * @throws Exception
	 */
	public function sendMail($data) {
		$email = $data->renderWith("mail/register.html");
		$mail = new Mail("noreply@" . $this->getOwner()->getRequest()->getServerName());
		if (!$mail->sendHTML($data["email"], lang("register"), $email)) {
			throw new Exception("Could not send mail.");
		}

		return true;
	}

	/**
	 * sends activation mail.
	 * @param User $user
	 * @return bool
	 * @throws Exception
	 */
	public function sendMailToAdmins($user)
	{
		// first step: get emails that we want to send to.

		if (self::$validationMail == null) {
			// get group ids that have the permission USERS_MANAGE
			$data = DataObject::get("group", array("permissions" => array("name" => "USERS_MANAGE")));
			$groupids = $data->fieldToArray("id");

			$users = DataObject::get("user", array("groups" => array("id" => $groupids)));

			$emails = implode(",", $users->fieldToArray("email"));
		} else {
			if (is_array(self::$validateMail)) {
				$emails = implode(",", self::$validateMail);
			} else {
				$emails = self::$validateMail;
			}
		}

		if (!is_object($user)) {
			$user = new User($user);
		}

		$view = $user->customise(array("activateLink" => BASE_URI . BASE_SCRIPT . "profile/activate" . URLEND . "?activate=" . $user["code"]))
			->renderWith("mail/activate_account_admin.html");

		$mail = new Mail("noreply@" . $this->getOwner()->getRequest()->getServerName());
		if (!$mail->sendHTML($emails, lang("user_activate"), $view)) {
			throw new Exception("Could not send mail.");
		}

		return true;
	}

	/**
	 * activation method for admins.
	 */
	public function activate()
	{
		if (!Permission::check("USERS_MANAGE")) {
			member::redirectToLogin();
		}

		if (isset($_GET["activate"]) && $data = DataObject::get_one("user", array("code" => $_GET["activate"]))) {
			if ($this->getOwner()->confirm(lang("user_activate_confirm"), lang("yes"), null, $data->generateRepresentation(true))) {
				$data->status = 1;
				$data->code = randomString(10);

				$view = $data->customise()
					->renderWith("mail/account_activated.html");

				$mail = new Mail("noreply@" . $this->getOwner()->getRequest()->getServerName());
				if (!$mail->sendHTML($data->email, lang("user_activated_subject"), $view)) {
					throw new Exception("Could not send mail.");
				}

				$data->writeToDB(false, true);
				AddContent::addSuccess(lang("user_activated_subject"));
				return $this->getOwner()->redirectBack();

			}
		} else {
			return $this->getOwner()->redirectBack();
		}
	}

	/**
	 * registers the user
	 * we don't use register, because of constructor
	 *
	 * @name doRegister
	 * @access public
	 * @return string
	 */
	public function doregister($data)
	{
		/** @var ProfileController $owner */
		$owner = $this->getOwner();
		if (self::$validateMail) {
			$data["status"] = 0;
			$data["code"] = randomString(10);

			// send mail
			$this->sendMail($data);

			if ($model = $owner->save($data, 2, true, true)) {
				return $this->renderView($model, lang('register_ok_activate', "User successful created. Please visit your e-mail-provider to check out the e-mail we sent to you."), true);
			}
		} else if (self::$mustBeValidated) {
			$data["status"] = 3;
			$data["code"] = randomString(10);
			// send mail
			$this->sendMailToAdmins($data);

			if ($model =  $owner->save($data, 2, true, true)) {
				return $this->renderView($model, lang('register_wait_for_activation', "The account was sucessfully registered, but an administrator needs to activate it. You'll be notified by email."));
			}
		} else {
			if ($model = $owner->save($data, 2, true, true)) {
				return $this->renderView($model, lang('register_ok', "Ready to login! Thanks for using this Site!"));
			}
		}
	}

	/**
	 * renders template.
	 *
	 * @param DataObject|string $model
	 * @param string $message
	 * @param bool $needsCode
	 * @return string
	 */
	protected function renderView($model, $message, $needsCode = false) {
		if(is_string($model)) {
			$model = new ViewAccessableData(array("email" => $model));
		}

		return $model->customise(array(
			"info" => $message,
			"codeNeeded" => $needsCode
		))->renderWith("profile/registerSuccess.html");
	}
}

gObject::extend("ProfileController", RegisterExtension::class);
StaticsManager::AddSaveVar(RegisterExtension::class, "enabled");
StaticsManager::AddSaveVar(RegisterExtension::class, "validateMail");
StaticsManager::AddSaveVar(RegisterExtension::class, "registerCode");
