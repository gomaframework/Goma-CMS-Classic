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
	 * parse link unit-tests.
	*/
	public function testParseLink() {
		$this->unitParseLink("ftp://192.168.2.1", false, "index.php/");
		$this->unitParseLink("http://192.168.2.1", false, "index.php/");
		$this->unitParseLink("https://192.168.2.1", false, "index.php/");
		$this->unitParseLink("javascript:192.168.2.1", false, "index.php/");
		$this->unitParseLink("mailto:daniel@ibpg.eu", false, "index.php/");

		$this->unitParseLink("FTP://192.168.2.1", false, "index.php/");
		$this->unitParseLink("HtTP://192.168.2.1", false, "index.php/");
		$this->unitParseLink("hTTPs://192.168.2.1", false, "index.php/");
		$this->unitParseLink("JaVaScRiPt:192.168.2.1", false, "index.php/");
		$this->unitParseLink("MaIlTo:daniel@ibpg.eu", false, "index.php/");

		$this->unitParseLink("index.php", '"index.php"');

		$this->unitParseLink("blah/test/notexisting", '"index.php/blah/test/notexisting"', "index.php/");
		$this->unitParseLink("./blah/test/notexisting", '"./blah/test/notexisting"', "index.php/");
		$this->unitParseLink("blah/test/notexisting", '"base.php/blah/test/notexisting"', "base.php/");
		$this->unitParseLink("base.php/blah/test/notexisting", '"base.php/blah/test/notexisting"', "base.php/");

		$this->unitParseLink("/MBG//#abc", '"#abc" data-anchor="abc"', "", "/MBG/");
	}

	public function unitParseLink($url, $expected, $base = BASE_SCRIPT, $root = ROOT_PATH) {
		$this->assertEqual($expected, trim(HTMLParser::parseLink($url, "", "", $base, $root)), $url);
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

	public function testCSS() {
		$css = ''.randomString(30).' {text-align: center;}';
		$link = HTMLParser::css($css);

		if(preg_match('/\<link\s+rel="stylesheet"\s+href="(.*)"\s+type="text\/css"\s+\/>/', $link, $matches)) {
			$this->assertTrue(file_exists($matches[1]));
			$this->assertEqual(file_get_contents($matches[1]), $css);

			unlink($matches[1]);
			$this->assertFalse(file_exists($matches[1]));
		} else {
			$this->fail("Did not get valid HTML from HTMLParser::css.");
		}
	}

    /**
     * tests if HTMLParser is preserving scripts when no body exists.
     */
	public function testScriptRemovingWithoutBody() {
	    $html = "<script type=\"text/javascript\">var a = b;</script>";
	    $this->assertEqual($html, HTMLParser::parseHTML($html));
    }

    /**
     * tests if HTMLParser is removing scripts from body when it exists.
     */
    public function testScriptRemovingWithBody() {
        $html = "<html><head><title></title></head><body><script type=\"text/javascript\">var a = b;</script></body></html>";
        $this->assertFalse(strpos(HTMLParser::parseHTML($html), "var a = b;"));
    }

    /**
     * tests if HTMLParser is not removing scripts from body when it exists and $includeResourcesInBody = false;
     */
    public function testScriptRemovingWithBodyIncludeInBodyIsFalse() {
        $html = "<html><head><title></title></head><body><script type=\"text/javascript\">var a = b;</script></body></html>";
        $this->assertTrue(!!strpos(HTMLParser::parseHTML($html, true, false), "var a = b;"));
    }
}