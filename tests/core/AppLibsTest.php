<?php defined("IN_GOMA") OR die();
/**
 * @package		Goma\Test\System\Core
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class AppLibsTest extends GomaUnitTest {
    /**
     *
     */
    public function testGetRootPathStandardLinux() {
        $this->assertEqual("/test/", getRootPath("/var/www/test/system/core/applibs.php", "/var/www"));
        $this->assertEqual("/test/", getRootPath("/var/www/test/system/core/applibs.php", "/var/www/"));
    }

    /**
     *
     */
    public function testGetRootPathStandardLinuxRoot() {
        $this->assertEqual("/", getRootPath("/var/www/system/core/applibs.php", "/var/www"));
        $this->assertEqual("/", getRootPath("/var/www/system/core/applibs.php", "/var/www/"));
    }

    /**
     *
     */
    public function testGetRootPathStandardPlesk() {
        $this->assertEqual("/test/", getRootPath("/var/www/vhosts/lala/test/system/core/applibs.php", "/var/www/vhosts/lala"));
        $this->assertEqual("/test/", getRootPath("/var/www/vhosts/lala/test/system/core/applibs.php", "/var/www/vhosts/lala/"));
    }

    /**
     *
     */
    public function testGetRootPathStandardPleskRoot() {
        $this->assertEqual("/", getRootPath("/var/www/vhosts/lala/system/core/applibs.php", "/var/www/vhosts/lala"));
        $this->assertEqual("/", getRootPath("/var/www/vhosts/lala/system/core/applibs.php", "/var/www/vhosts/lala/"));
    }

    /**
     *
     */
    public function testGetRootPathStandardWindows() {
        $this->assertEqual("/test/", getRootPath("C:\\xampp\\htdocs\\test\\system\\core\\applibs.php", "C:\\xampp\\htdocs"));
        $this->assertEqual("/test/", getRootPath("C:\\xampp\\htdocs\\test\\system\\core\\applibs.php", "C:\\xampp\\htdocs\\"));
    }

    /**
     *
     */
    public function testGetRootPathStandardWindowsRoot() {
        $this->assertEqual("/", getRootPath("C:\\xampp\\htdocs\\system\\core\\applibs.php", "C:\\xampp\\htdocs"));
        $this->assertEqual("/", getRootPath("C:\\xampp\\htdocs\\system\\core\\applibs.php", "C:\\xampp\\htdocs\\"));
    }
}
