<?php defined("IN_GOMA") OR die();
/**
 * Unit-Tests for HTMLNode-Class.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class HTMLNodeTests extends GomaUnitTest {
    /**
     * tests img tag
     */
    public function testImgNodeAttr() {
        $node = new HTMLNode("IMG", array(
            "src" => "test.png",
            "alt" => "blah"
        ));
        $this->assertEqual($node->getTag(), "img");
        $this->assertEqual($node->src, "test.png");
        $this->assertEqual($node->render(), '<img src="test.png" alt="blah" />');
        $this->assertEqual((string) $node, '<img src="test.png" alt="blah" />');

        $node->alt = "blub";
        $this->assertEqual((string) $node, '<img src="test.png" alt="blub" />');

        $node->title = "test";
        $this->assertEqual((string) $node, '<img src="test.png" alt="blub" title="test" />');

        unset($node->alt);
        $this->assertEqual((string) $node, '<img src="test.png" title="test" />');
    }

    /**
     * tests setting css attribute.
     */
    public function testSetCSSAttribute() {
        $node = new HTMLNode("div");
        $node->css = "color:red;background-color: green;";
        $this->assertEqual("red", $node->css("color"));
        $this->assertEqual("green", $node->css("background-color"));
    }

    /**
     * tests setting css attribute via constructor.
     */
    public function testSetCSSAttributeConstructor() {
        $node = new HTMLNode("div", array("css" => "color:red;background-color: green;"));
        $this->assertEqual("red", $node->css("color"));
        $this->assertEqual("green", $node->css("background-color"));
    }

    /**
     * tests if addClass is throwing invalid argument exception if classname contains a backslash.
     */
    public function testInvalidArgAddClassBackslash() {
        $node = new HTMLNode("div");
        try {
            $node->addClass("div\\a");
            $this->assertTrue(false, "Exception not thrown.");
        } catch (Exception $e) {
            $this->assertInstanceOf(InvalidArgumentException::class, $e);
        }
    }

    /**
     * tests if setting class attribute is throwing invalid argument exception if classname contains a backslash.
     */
    public function testInvalidArgSetClassBackslash() {
        $node = new HTMLNode("div");
        try {
            $node->class = "div\\a blub";
            $this->assertTrue(false, "Exception not thrown.");
        } catch (Exception $e) {
            $this->assertInstanceOf(InvalidArgumentException::class, $e);
        }
    }

    /**
     * tests if attribute values are escaped.
     */
    public function testAttributeEscape() {
        $node = new HTMLNode("div", array("data-name" => 'blah"blub'));
        $this->assertEqual('<div data-name="blah&quot;blub" ></div>', $node->render());
    }

    /**
     * tests if val method escapes correctly for input.
     */
    public function testValEscape() {
        $htmlNode = new HTMLNode("input", array("type" => "text"));
        $htmlNode->val("Mühlenßein");
        $this->assertEqual('<input type="text" value="M&uuml;hlen&szlig;ein" />', $htmlNode->render());
    }
}
