<?php
defined("IN_GOMA") OR die();

/**
 * An external AJAX form.
 *
 * @author Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @package Goma\Form
 * @version 1.1.1
 */
class AjaxExternalForm extends FormField {

	/**
	 * @var array
	 */
	public $allowed_actions = array("render");

	/**
	 * external form.
	 */
	public $external_form;

	/**
	 * @param string $name
	 * @param string|null $title
	 * @param mixed $value
	 * @param string $external
	 * @param Form|null $form
	 * @param string $code
	 */
	public function __construct($name = "", $title = null, $value = null, $external = "", &$form = null, $code = "") {
		if(is_object($external))
			$this->external_form = $external;

		parent::__construct($name, $title, $value, $form);
	}

	/**
	 *
	 */
	public function createNode() {
		$node = new HTMLNode("span", array("class" => "value"));

		return $node;
	}

	/**
	 * renders the field
	 * @param FormFieldRenderData $info
	 * @return HTMLNode
	 */
	public function field($info) {
		$this->callExtending("beforeField");

		$this->container->append(new HTMLNode("label", array(), $this->title));

		$this->container->append($this->input);
		$this->input->append(array(
			$this->getModel(),
			"&nbsp;&nbsp;&nbsp;&nbsp;",
			new HTMLNode("a", array(
				"href" => $this->externalURL() . "/render/?redirect=" . urlencode(getredirect()),
				"title" => convert::raw2text($this->title),
				"rel" => "dropdownDialog",
				"class" => "edit hideClose noAutoHide"
			), new HTMLNode("img", array(
				"alt" => lang("edit"),
				"src" => "images/icons/fatcow16/edit.png",
				"data-retina" => "images/icons/fatcow16/edit@2x.png"
			)))
		));

		$this->callExtending("afterField");

		return $this->container;
	}

	/**
	 * renders the bluebox
	 */
	public function render() {
		$form = unserialize(serialize($this->external_form));
		if($this->getRequest()->is_ajax()) {
			$form->addAction(new Button("cancel", lang("cancel", "Cancel"), "var id = $(this).parents('.bluebox').attr('id').replace('bluebox_','');getblueboxbyid(id).close();"));
			return $form->render();
		} else {
			$form->add(new HTMLField("head", "<h1>" . convert::raw2text($this->title) . "</h1>"), 1);
			$form->addAction(new LinkAction("cancel", lang("cancel"), $this->form()->url));

			return $form->render();
		}
	}

	/**
	 * we never have a result
	 */
	public function result() {
		return null;
	}
}
