<?php
namespace Goma\Test;
use AbstractFormComponent;
use ClassInfo;
use Controller;
use Exception;
use FieldSet;
use Form;
use FormAction;
use FormField;
use FormNotValidException;
use FormValidator;
use GlobalSessionManager;
use Goma\Form\Exception\DuplicateActionException;
use GomaUnitTest;
use MockSessionManager;
use RadioButton;
use ReflectionClass;
use Request;
use RequestHandler;
use stdClass;
use TestAble;
use TextField;
use tpl;

defined("IN_GOMA") OR die();
/**
 * Unit-Tests for Form.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */

class FormTest extends GomaUnitTest implements TestAble {
	/**
	 * area
	 */
	static $area = "Form";

	/**
	 * internal name.
	 */
	public $name = "Form";

	/**
	 * tests form RequestHandler connection.
	 */
	public function testFormRequestHandler() {
		$form = new Form($c = new Controller(), "test");

		$this->assertEqual($form->name(), "test");
		$this->assertEqual($form->controller, $c);
		$this->assertTrue((boolean) $form->render());
	}

	/**
	 * tests form RequestHandler connection.
	 */
	public function testFormWithSimpleFieldRequestHandler() {
		$form = new Form($c = new Controller(), "test");
        $form->add(new TextField("name", "Name"));

		$this->assertEqual($form->name(), "test");
        $this->assertIsA($form->name, TextField::class);
        $this->assertNull($form->name->getModel());
		$this->assertEqual($form->controller, $c);
		$this->assertTrue((boolean) $form->render());
	}

	/**
	 *
	 */
	public function testFormUrl() {
		$request = new Request("get", "url");

		$requestHandler = new RequestHandler();
		$requestHandler->Init($request);
		$form = new Form($requestHandler, "blub");

		$this->assertEqual($form->url, ROOT_PATH . BASE_SCRIPT . "url" . URLEND);

		$request = new Request("get", "");

		$requestHandler = new RequestHandler();
		$requestHandler->Init($request);
		$form = new Form($requestHandler, "blub");

		$url = ROOT_PATH . BASE_SCRIPT . $request->url . URLEND;
		$url = $url == "//" ? "/" : $url;
		$this->assertEqual($form->url, $url);
	}

	/**
	 * tests if fields are accessable by name.
	 */
	public function testFieldAccessable() {
		$this->caseFieldAccessable("name", new TextField("name", "name"));
		$this->caseFieldAccessable("surname", new TextField("surname", "name"));
		$this->caseFieldAccessable("address1", new TextField("address1", "name"));
		$this->caseFieldAccessable("_1", new TextField("_1", "name"));
		$this->caseFieldAccessable("test", new TextField("test", "blub"));

		$this->caseFieldAccessable("test", new FieldSet("test", array(), "blub"));
		$this->caseFieldAccessable("blah", new FieldSet("BLAH", array(), "blub"));

		$this->caseFieldAccessable("TEST", new FieldSet("test", array(), "blub"));
		$this->caseFieldAccessable("Blah", new FieldSet("BLAH", array(), "blub"));

		$this->caseFieldAccessable("TEST", new RadioButton("test", "blub"));
		$this->caseFieldAccessable("Blah", new RadioButton("BLAH", "blub"));

		$this->caseFieldAccessable("TEST", new FormAction("test", "blub"));
		$this->caseFieldAccessable("Blah", new FormAction("BLAH", "blub"));

		$form = new Form(new Controller(), "test", array(
			$set = new FieldSet("BLAH", array(
				$t = new FormField("test"),
				$b = new FormField("Blub")
			))
		));

		$this->assertEqual($form->blah, $set);
		$this->assertEqual($form->Test, $t);
		$this->assertEqual($form->BLub, $b);
	}

	/**
	 * case.
	 * @param string $name
	 * @param AbstractFormComponent $field
	 */
	public function caseFieldAccessable($name, $field) {
		$this->assertEqual(strtolower($field->getName()), strtolower($name));

		$form = new Form(new Controller(), "test", array(
			$field
		));

		$this->assertEqual($form->$name, $field, "Check if field with name $name is accessable. %s");
	}

	protected static $testCalled = false;

	public function testNullResult() {
		$form = new Form(new Controller(), "test" ,array(
			new TextField("test", "test")
		), array(
			$action = new FormAction("save", "save")
		));

		$form->setSubmission(array($this, "_testNull"));

		$form->saveToSession();

		self::$testCalled = false;
		$this->assertFalse(self::$testCalled);

		$form->getRequest()->post_params = array(
			"test" => null,
			$action->PostName() => 1
		);
		$form->trySubmit();
		$this->assertTrue(self::$testCalled);

		self::$testCalled = false;
		$this->assertFalse(self::$testCalled);
		$form->setSubmission(array($this, "_exceptionSubmit"));

		$form->saveToSession();

		$this->assertNotEqual($form->trySubmit(), "");
		$this->assertEqual(self::$testCalled, 2);
	}

	protected $fieldValue1;
	protected $fieldValue2;
	protected $handlerCalled;
	protected $validationCalled;

	public function testDataHandlerAndValidation() {
		$this->unittestDataHandlerAndValidation("123", "456");
		$this->unittestDataHandlerAndValidation(null, null);
		$this->unittestDataHandlerAndValidation("abc", "efg");
	}

	public function unittestDataHandlerAndValidation($fieldValue1, $fieldValue2) {
		$this->fieldValue1 = $fieldValue1;
		$this->fieldValue2 = $fieldValue2;

		$form = new Form(new Controller(), "testData", array(
			new TextField("field1", "123"),
			new TextField("field2", "123")
		));

		$form->addDataHandler(array($this, "transformFields"));
		$form->addValidator(new FormValidator(array($this, "validateFieldsActive")), "validate");

		$this->validationCalled = $this->handlerCalled = false;

		$form->getRequest()->post_params = array(
			"field1" => $this->fieldValue1,
			"field2" => $this->fieldValue2
		);

		$this->assertEqual($form->gatherResultForSubmit(), array(
			"field1" => $this->fieldValue2,
			"field2" => $this->fieldValue2,
			"field3" => $this->fieldValue1
		));

		$this->assertTrue($this->handlerCalled);
		$this->assertTrue($this->validationCalled);
	}

	/**
	 * @param FormValidator $obj
	 */
	public function validateFieldsActive($obj) {
		$this->validationCalled = true;
		$result = $obj->getForm()->result;

		$this->assertEqual($result["field1"], $this->fieldValue2);
		$this->assertEqual($result["field2"], $this->fieldValue2);
		$this->assertEqual($result["field3"], $this->fieldValue1);
	}

	public function transformFields($result) {
		$this->handlerCalled = true;

		$data = $result["field1"];
		$result["field1"] = $result["field2"];
		$result["field3"] = $data;
		return $result;
	}

	public function testValidationError() {
		$form = new Form(new Controller(), "testData", array(
			new TextField("field1", "123")
		));

		$form->addValidator(new FormValidator(array($this, "validateAndThrow")), "validate");

		$form->post = array(
			"field1" => "123"
		);

		/** @var FormNotValidException $e */
		try {
			$form->gatherResultForSubmit();

			$this->assertFalse(true);
		} catch(Exception $e) {
			$this->assertIsA($e, "FormNotValidException");

			$errors = $e->getErrors();
			$this->assertIsA($errors[0], "Exception");
			$this->assertEqual($errors[0]->getMessage(), "problem");
		}
	}

	public function validateAndThrow() {
		throw new Exception("problem");
	}

	/**
	 * @param $data
	 */
	public function _testNull($data) {
		self::$testCalled = true;
		$this->assertNull($data["test"]);
	}

	public function _exceptionSubmit() {
		self::$testCalled = 2;
		throw new Exception("Problem");
	}

	public function testTemplateExists() {
		foreach(ClassInfo::getChildren("FormField") as $field) {
			if(!ClassInfo::isAbstract($field)) {
				$reflectionClass = new ReflectionClass($field);
				$inst = $reflectionClass->newInstance();

				if($reflectionClass->hasProperty("template")) {
					$reflectionProp = $reflectionClass->getProperty("template");
					$reflectionProp->setAccessible(true);
					$tpl = $reflectionProp->getValue($inst);

					$expansion = isset(ClassInfo::$class_info[$field]["inExpansion"]) ?
						ClassInfo::$class_info[$field]["inExpansion"] : null;

					if ($tpl) {
						$this->assertTrue(!!tpl::getFilename($tpl, "", false, $expansion), "Template $tpl for class $field %s");
						$this->assertTrue(file_exists(tpl::getFilename($tpl, "", false, $expansion)), "Template $tpl exists for class $field %s");
					}
				}
			}
		}
	}

	public function testcheckForRestore() {
		$session = GlobalSessionManager::globalSession();
		$mock = new MockSessionManager();
		GlobalSessionManager::__setSession($mock);

		$form = new Form(new Controller(), "blub");
		$this->assertEqual($mock->functionCalls, array(
			array("hasKey", array("form_restore.blub")),
			array("hasKey", array("form_state_blub"))
		));

		$this->assertIsA($form->state, "FormState");

		$mock->functionCalls = array();
		$mock->session["form_state_blah"] = array(
			"test" => 1
		);
		$form = new Form(new Controller(), "blah");
		$this->assertEqual($mock->functionCalls, array(
			array("hasKey", array("form_restore.blah")),
			array("hasKey", array("form_state_blah")),
			array("get", array("form_state_blah"))
		));
		$this->assertIsA($form->state, "FormState");
		$this->assertEqual($form->state->test, 1);

		GlobalSessionManager::__setSession($session);
	}

	public function testName() {
		$form = new Form(new Controller(), "BLAH");
		$this->assertEqual($form->name(), "blah");
	}

    /**
     * tests if removing an action works.
     */
	public function testAddRemoveAction() {
		$action1 = new FormAction("action1");
		$action2 = new FormAction("action2");

		$action1->action1 = 1;
		$action2->action2 = 1;

		$form = new Form(new Controller(), "BLAH", array(), array(
			$action2
		));
		$form->addAction($action1);

		$this->assertEqual($form->action1, $action1);
		$this->assertEqual($form->action2, $action2);

		$form->removeAction("action1");
		$this->assertEqual($form->action1, null);
	}

    /**
     * tests if adding duplicate actions is throwing an exception.
     *
     * 1. Create action1 with name action1
     * 2. Create action2 with name action1
     * 3. Create form with action2
     * 4. Try to add action1 to form. Exception should be DuplicateActionException
     * 5. Check if $form->action1 is equal to action2
     */
	public function testAddDuplicateAction() {
		$action1 = new FormAction("action1");
		$action2 = new FormAction("action1");

		$action1->action1 = 1;
		$action2->action2 = 1;

		$form = new Form(new Controller(), "BLAH", array(), array(
			$action2
		));

		$this->assertThrows(function() use ($action1, $form) {
            $form->addAction($action1);
        }, DuplicateActionException::class);

		$this->assertEqual($form->action1, $action2);
	}

    /**
     * tests if form is preserving state-changed, which are made in submit functions.
     */
	public function testSwitchState() {
		$form = new Form(new Controller(), "blub", array(), array(
			new FormAction("test", "test", array($this, "manipulateState"))
		));
		$form->state->blah = 123;
		$form->render()->render();

		$this->assertEqual($form->state->blah, 123);

		$form->setRequest(new Request("post", "test", array(), array(
			"test" => "test"
		)));
		$form->trySubmit();
		$this->assertEqual($form->state->blah, 321);
	}

	public function manipulateState($data, $form) {
		$this->assertEqual($form->state->blah, 123);
		$form->state->blah = 321;
	}

    /**
     * tests if changing the model while throwing an exception is saved back to the form.
     */
	public function testChangeModel() {
		$form = new Form(new Controller(), "blub", array(), array(
			new FormAction("test", "test")
		));
        $form->setSubmission(array($this, "manipulateModel"));
		$form->setModel($model = new StdClass());
		$form->state->blah = 123;
		$form->render()->render();

		$this->assertEqual(123, $form->state->blah);
		$this->assertEqual($model, $form->getModel());

		$form->setRequest(new Request("post", "test", array(), array(
			"test" => "test"
		)));
		$form->trySubmit();
		$this->assertEqual(null, $form->getModel()->test);
		$this->assertEqual(321, $form->state->blah);

        $form->getModel()->test = 0;
        $form->state->blah = 123;
        $this->assertNotEqual(321, $form->state->blah);
        $this->assertEqual(0, $form->getModel()->test);
        $form->setSubmission(array($this, "manipulateModelException"));

        $form->setRequest(new Request("post", "test", array(), array(
            "test" => "test"
        )));
        $form->trySubmit();
        $this->assertEqual(0, $form->getModel()->test);
        $this->assertEqual(321, $form->state->blah);
	}

	/**
	 * @param $data
	 * @param Form $form
     */
	public function manipulateModel($data, $form) {
		$this->assertEqual($form->state->blah, 123);
		$form->state->blah = 321;

		$form->getModel()->test = 1;
        $this->assertEqual(1, $form->getModel()->test);
	}


    /**
     * @param $data
     * @param Form $form
     * @throws Exception
     */
    public function manipulateModelException($data, $form) {
        $this->assertEqual($form->state->blah, 123);
        $form->state->blah = 321;

        $form->getModel()->test = 1;
        $this->assertEqual(1, $form->getModel()->test);
        throw new Exception("blub");
    }

    /**
     * tests if submitting a form by calling submitForm is calling submit method.
     *
     * 1. Create form
     * 2. Add action with callback to callSave, which returns 2
     * 3. Assert that $form->submitWithPostParamsAndThrow(array("save" => "save")) is equal to 2
     */
    public function testSubmitFormIsCallingSubmit() {
        $form = new Form(new Controller(), "form", array(new \HiddenField("data", "blah")));
        $form->addAction(new FormAction("save", lang("save"), array($this, "callSave")));
        $this->assertEqual(2, $form->submitWithPostParamsAndThrow(array("save" => "save", "data" => "blah")));
    }

    protected $calledSave = false;

    /**
     * helper function for testSubmitFormIsCallingSubmit
     *
     * @return 2
     */
    public function callSave() {
        $this->calledSave = true;
        return 2;
    }

    /**
     * tests if form is throwing an exception with a message which contains "url", when url mismatch.
     *
     * 1. Create form $form
     * 2. Set url to "url"
     * 3. Render Form and response
     * 4. Set url to "url2"
     * 5. Call $form->submitForm(true) and catch exception
     * 6. Assert that exception is FormNotSubmittedException
     * 7. Assert that exception message contains url
     */
    public function testsubmitFormUrlMismatch() {
        $form = new Form(new Controller(), "name");
        $form->url = "url";
        $form->render()->render();

        $form->url = "url2";
        $exc = null;
        try {
            $method = new \ReflectionMethod(Form::class, "submitForm");
            $method->setAccessible(true);
            $method->invoke($form, true);
        } catch (Exception $e) {
            $exc = $e;
        }
        $this->assertInstanceOf(\FormNotSubmittedException::class, $exc);
        $this->assertRegExp("/url/", $exc->getMessage());
    }

    /**
     * tests if form is not throwing an exception with a message which contains "url", when url mismatch.
     *
     * 1. Create form $form
     * 2. Set url to "url"
     * 3. Render Form and response
     * 4. Set url to "url"
     * 5. Call $form->>submitForm(true) and catch exception
     * 6. Assert that exception is FormNotSubmittedException
     * 7. Assert that exception message *not* contains url
     */
    public function testsubmitFormUrlMatch() {
        $form = new Form(new Controller(), "name");
        $form->url = "url";
        $form->render()->render();

        $form->url = "url";
        $exc = null;
        try {
            $method = new \ReflectionMethod(Form::class, "submitForm");
            $method->setAccessible(true);
            $method->invoke($form, true);
        } catch (Exception $e) {
            $exc = $e;
        }
        $this->assertInstanceOf(\FormNotSubmittedException::class, $exc);
        $this->assertFalse(!!preg_match('/url/', $exc->getMessage()));
    }
}
