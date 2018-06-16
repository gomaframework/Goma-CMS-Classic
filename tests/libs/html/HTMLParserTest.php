<?php defined("IN_GOMA") OR die();
/**
 * Unit-Tests for HTMLParser-Class.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */


class HTMLParserTests extends GomaUnitTest {
	/**
	 * area
	*/
	static $area = "HTML";

	/**
	 * internal name.
	*/
	public $name = "HTMLParser";

    /**
     * tests if process_links ignores link with //domain
     */
	public function testParseDoubleSlashOmitProtocol() {
	    $this->assertNull(
            HTMLParser::parseLink("//192.168.2.1", "", "", "index.php/", ROOT_PATH)
        );
    }

    /**
     * tests if process_links ignores link with custom url scheme
     */
    public function testCustomURLScheme() {
        $this->assertNull(
            HTMLParser::parseLink("paperscan://192.168.2.1", "", "", "index.php/", ROOT_PATH)
        );
    }

	/**
	 * parse link unit-tests.
	*/
	public function testParseLink() {
        $this->unitParseLink("//192.168.2.1", null, "index.php/");
        $this->unitParseLink("paperscan://192.168.2.1", null, "index.php/");

		$this->unitParseLink("ftp://192.168.2.1", null, "index.php/");
		$this->unitParseLink("http://192.168.2.1", null, "index.php/");
		$this->unitParseLink("https://192.168.2.1", null, "index.php/");
		$this->unitParseLink("javascript:192.168.2.1", null, "index.php/");
		$this->unitParseLink("mailto:daniel@ibpg.eu", null, "index.php/");

		$this->unitParseLink("FTP://192.168.2.1", null, "index.php/");
		$this->unitParseLink("HtTP://192.168.2.1", null, "index.php/");
		$this->unitParseLink("hTTPs://192.168.2.1", null, "index.php/");
		$this->unitParseLink("JaVaScRiPt:192.168.2.1", null, "index.php/");
		$this->unitParseLink("MaIlTo:daniel@ibpg.eu", null, "index.php/");

		$this->unitParseLink("index.php", '"index.php"');

		$this->unitParseLink("blah/test/notexisting", '"index.php/blah/test/notexisting"', "index.php/");
		$this->unitParseLink("./blah/test/notexisting", '"./blah/test/notexisting"', "index.php/");
		$this->unitParseLink("blah/test/notexisting", '"base.php/blah/test/notexisting"', "base.php/");
		$this->unitParseLink("base.php/blah/test/notexisting", '"base.php/blah/test/notexisting"', "base.php/");

		$this->unitParseLink("/MBG//#abc", '"#abc" data-anchor="abc"', "", "/MBG/");
	}

	public function unitParseLink($url, $expected, $base = BASE_SCRIPT, $root = ROOT_PATH) {
		$this->assertEqual($expected, HTMLParser::parseLink($url, "", "", $base, $root), $url);
	}

	public function testProcessLinks() {

		$url = ((URL . URLEND) == "/") ? "" : URL . URLEND;
		$this->unitProcessLinks('<a href="blah/test/notexisting">Test</a>', '<a href="index.php/blah/test/notexisting">Test</a>', "index.php/");

		$this->unitProcessLinks('<a href="#test">Test</a>', '<a href="index.php/'.$url.'#test" data-anchor="test">Test</a>', "index.php/");
		$this->unitProcessLinks('<a href="http://192.168.2.1">Test</a>', '<a href="http://192.168.2.1">Test</a>', "index.php/");
		$this->unitProcessLinks('<a HREF="http://192.168.2.1">Test</a>', '<a HREF="http://192.168.2.1">Test</a>', "index.php/");
		$this->unitProcessLinks('<a title="blah" href="http://192.168.2.1">Test</a>', '<a title="blah" href="http://192.168.2.1">Test</a>', "index.php/");
		$this->unitProcessLinks('<a alt="blah" HREF="http://192.168.2.1" myprop="2">Test</a>', '<a alt="blah" HREF="http://192.168.2.1" myprop="2">Test</a>', "index.php/");
		$this->unitProcessLinks('<a alt="blah" HREF="#b123" myprop="2">Test</a>', '<a alt="blah" href="index.php/'.$url.'#b123" data-anchor="b123" myprop="2">Test</a>', "index.php/");
		

	}

	public function unitProcessLinks($html, $expected, $base = BASE_SCRIPT, $root = ROOT_PATH) {
		$this->assertEqual(trim(HTMLParser::process_links($html, $base, $root)), $expected, $html . " %s");
	}

    /**
     * tests if HTMLParser is adding script tag even not body exists.
     */
	public function testScriptRemovingWithoutBody() {
	    $html = "<script type=\"text/javascript\">var a = b;</script>";
	    $this->assertRegExp('/<script/', HTMLParser::parseHTML($html));
    }

    /**
     * tests if HTMLParser is non adding script tag if includeResourcesInBody = false
     */
    public function testScriptIncludeResourcesFalse() {
        $html = "<script type=\"text/javascript\">var a = b;</script>";
        $this->assertNoPattern('/<script/', HTMLParser::parseHTML($html, true, false));
    }

    /**
     * tests if HTMLParser is removing scripts from body when it exists.
     */
    public function testScriptRemovingWithBody() {
        $html = "<html><head><title></title></head><body><script type=\"text/javascript\">var a = b;</script></body></html>";
        $this->assertFalse(strpos(HTMLParser::parseHTML($html), "var a = b;"));
    }

    /**
     * tests if HTMLParser is not removing scripts from body when it exists and $includeResourcesInBody = false and parseLinksAndScripts = false
     */
    public function testScriptRemovingWithBodyIncludeInBodyIsFalse() {
        $html = "<html><head><title></title></head><body><script type=\"text/javascript\">var a = b;</script></body></html>";
        $this->assertTrue(!!strpos(HTMLParser::parseHTML($html, false, false), "var a = b;"));
    }
}