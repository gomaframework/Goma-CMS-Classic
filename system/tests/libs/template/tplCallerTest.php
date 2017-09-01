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

    protected static $tplCallerWindowsPath = "C:\\xampp\\htdocs\\template\\view.html";
    protected static $tplCallerRootWindowsPath = "C:\\xampp\\htdocs\\";
    protected static $tplCallerRootLinuxPath = "C:/xampp/htdocs/";
    protected static $tplCallerLinuxPath = "C:/xampp/htdocs/template/view.html";
    protected static $tplCallerBasePath = "template";

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
     * tests if tplCaller is correctly setting tplBase for Windows Path if root-path is in Windows-Style.
     *
     * 1. Create tplCaller with ViewAccessableData, set to tplCaller
     * 2. call setTplPath($tplCallerWindowsPath, $tplCallerRootWindowsPath) (defined above)
     * 3. Assert that tplCaller->tplBase is equal to $tplCallerBasePath
     */
    public function testTplCallerWindowsPath() {
        $tplCaller = new tplCaller(new ViewAccessableData());
        $tplCaller->setTplPath(self::$tplCallerWindowsPath, self::$tplCallerRootWindowsPath);

        $property = new ReflectionProperty("tplCaller", "tplBase");
        $property->setAccessible(true);

        $this->assertEqual(self::$tplCallerBasePath, $property->getValue($tplCaller));
    }

    /**
     * tests if tplCaller is correctly setting tplBase for Windows Path if root-path is in Linux-Style.
     *
     * 1. Create tplCaller with ViewAccessableData, set to tplCaller
     * 2. call setTplPath($tplCallerWindowsPath, $tplCallerRootLinuxPath) (defined above)
     * 3. Assert that tplCaller->tplBase is equal to $tplCallerBasePath
     */
    public function testTplCallerWindowsPathWithWrongSlashes() {
        $tplCaller = new tplCaller(new ViewAccessableData());
        $tplCaller->setTplPath(self::$tplCallerWindowsPath, self::$tplCallerRootLinuxPath);

        $property = new ReflectionProperty("tplCaller", "tplBase");
        $property->setAccessible(true);

        $this->assertEqual(self::$tplCallerBasePath, $property->getValue($tplCaller));
    }

    /**
     * tests tplCaller::sum
     */
    public function testSum() {
        $caller = new tplCaller(new ViewAccessableData());
        $this->assertEqual(10, $caller->sum(5, 5));
        $this->assertEqual(10, $caller->sum(3, 7));
        $this->assertEqual(10, $caller->sum(5, 2, 3));
    }
}
