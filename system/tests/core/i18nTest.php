<?php defined("IN_GOMA") OR die();
/**
 * @package		Goma\Test\System\Core
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class i18nTest extends GomaUnitTest {
    /**
     * tests if compilation worked.
     */
    public function testL() {
        $this->assertEqual("LOGIN", L::LOGIN);
    }

    /**
     * tests if all languages have the same keys.
     */
    public function testAllKeysEqual() {
        $lastLangKeys = null;
        $firstLang = null;
        foreach(i18n::listLangs() as $lang) {
            i18n::Init($lang["code"]);
            if(isset($lastLangKeys)) {
               $diff = array_diff($lastLangKeys, array_map("strtoupper", array_keys($GLOBALS["lang"])));
               $this->assertEqual(array(), $diff, "Testing Language {$lang["code"]} vs $firstLang");
            } else {
                $firstLang = $lang["code"];
                $lastLangKeys = array_map("strtoupper", array_keys($GLOBALS["lang"]));
            }
        }
    }
}
