<?php
defined("IN_GOMA") OR die();

/**
 * tests tplCaller.
 *
 * @package Goma\Test
 *
 * @author Goma-Team
 * @copyright 2016 Goma-Team
 *
 * @version 1.0
 */
class tplCallerTest extends GomaUnitTest {
    /**
     * tests languages if it responds with IDataSet.
     */
    public function testLanguagesRespondsWithIDataSet() {
        $caller = new tplCaller(new ViewAccessableData());
        $this->assertIsA($caller->languages(), IDataSet::class);
    }

    /**
     * tests if languages fit pattern.
     */
    public function testEveryLanguageFitsPattern() {
        $caller = new tplCaller(new ViewAccessableData());

        foreach($caller->languages() as $lang) {
            $this->assertTrue(is_array($lang) || is_a($lang, ViewAccessableData::class));

            $this->assertTrue(isset($lang["code"]));
            $this->assertTrue(isset($lang["name"]));
            $this->assertTrue(isset($lang["active"]));
            $this->assertEqual($lang["active"], $lang["code"] == Core::$lang);
            $this->assertTrue(isset($lang["title"]));
        }
    }

    /**
     *
     */
    public function testTplCallerWindowsPath() {
        $tplCaller = new tplCaller(new ViewAccessableData());
        $tplCaller->setTplPath("C:\\xampp\\htdocs\\template\\view.html", "C:\\xampp\\htdocs\\");

        $property = new ReflectionProperty("tplCaller", "tplBase");
        $property->setAccessible(true);

        $this->assertEqual("template", $property->getValue($tplCaller));
    }

    /**
     *
     */
    public function testTplCallerWindowsPathWithWrongSlashes() {
        $tplCaller = new tplCaller(new ViewAccessableData());
        $tplCaller->setTplPath("C:\\xampp\\htdocs\\template\\view.html", "C:/xampp/htdocs/");

        $property = new ReflectionProperty("tplCaller", "tplBase");
        $property->setAccessible(true);

        $this->assertEqual("template", $property->getValue($tplCaller));
    }
}
