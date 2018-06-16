<?php defined("IN_GOMA") OR die();

/**
 * Class for parsing HTML for inline Scripts and styles and updates all links to correct values.
 *
 * @package     Goma\HTML-Processing
 *
 * @license     GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @author      Goma-Team
 *
 * @version     2.4
 */
class HTMLParser extends gObject
{

    /**
     * allowed prefixes for links in goma that never are checked for BASE_SCRIPT.
     */
    static $allowedPrefixes = array(
        "javascript:", "mailto:"
    );


    /**
     * adds Resources to HTML and replaces existing links and scripts with base-url if required.
     * into files and all links working.
     *
     * @param string $html
     * @param bool $parseLinksAndScripts
     * @param bool $includeResourcesInBody
     * @return string
     */
    public static function parseHTML($html, $parseLinksAndScripts = true, $includeResourcesInBody = true)
    {
        if (PROFILE) Profiler::mark("HTMLParser::parseHTML");

        if ($parseLinksAndScripts) {
            $html = self::replaceScripts($html);
            $html = self::process_links($html);
        }

        if($includeResourcesInBody) {
            // replace css resources
            $view = new ViewAccessableData();
            if (strpos($html, "<base") === false) {
                $view->base_uri = BASE_URI;
            }
            $view->resources = resources::get(true, false, true, false);

            if (strpos($html, "</title>") !== false) {
                $html = str_replace('</title>', '</title>'.$view->renderWith("framework/resources-header.html"), $html);
            } else {
                $html = $view->renderWith("framework/resources-header.html").$html;
            }

            $resources = resources::get(true, false, false, true);
            if (strpos($html, "</body>") !== false) {
                // replace js resources
                $html = str_replace('</body>', "\n".$resources."\n	</body>", $html);
            } else {
                $html = $html.$resources;
            }
        }

        if (PROFILE) Profiler::unmark("HTMLParser::parseHTML");

        return $html;
    }

    /**
     * finds all javascripts and packs them into its own files.
     *
     * @param string $html
     * @return string
     */
    public static function replaceScripts($html)
    {
        preg_match_all('/\<\!\-\-(.*)\-\-\>/Usi', $html, $comments);
        foreach ($comments[1] as $k => $v) {
            $html = str_replace($comments[0][$k], "<!--comment_" . $k . "-->", $html);
        }

        // find inline scripts.
        preg_match_all('/\<script[^\>]*type\=\"text\/javascript\"[^\>]*\>(.*)\<\/script\s*\>/Usi', $html, $no_tags);
        foreach ($no_tags[1] as $key => $js) {
            if (!empty($js)) {
                $html = str_replace($no_tags[0][$key], "", $html);
                Resources::addJS($js,  "scripts");
            }
        }

        // find scripts with src.
        preg_match_all('/\<script[^\>]*src="(.+)"[^>]*\>(.*)\<\/script\s*\>/Usi', $html, $no_tags);
        foreach ($no_tags[1] as $key => $js) {
            if (trim($js) != "" && file_exists(ROOT . $js)) {
                Resources::add(ROOT . $js, "js", "tpl");
                $html = str_replace($no_tags[0][$key], "", $html);
            }
        }

        foreach ($comments[1] as $k => $v) {
            $html = str_replace("<!--comment_" . $k . "-->", $comments[0][$k], $html);
        }

        return $html;
    }

    /**
     * processes links to work also on servers where Mod Rewrite is not enabled.
     * it adds the BASE_SCRIPT before all links. BASE_SCRIPT normally contains index.php/.
     *
     * @param string $html
     * @param string $base
     * @param string $root
     * @param string $prependBase - html
     * @return mixed
     */
    public static function process_links($html, $base = BASE_SCRIPT, $root = ROOT_PATH, $prependBase = "")
    {
        if (PROFILE) Profiler::mark("HTMLParser::process_links");
        preg_match_all('/<a([^>]*)\shref="([^">]+)"([^>]*)>/Usi', $html, $links);
        foreach ($links[2] as $key => $href) {
            $newlink = self::parseLink($href, '<a' . $links[1][$key] . ' href=', $links[3][$key] . '>', $base, $root, $prependBase);

            if ($newlink) {
                $html = str_replace($links[0][$key], $newlink, $html);
            }
        }

        preg_match_all('/<iframe([^>]*)\ssrc="([^">]+)"([^>]*)>/Usi', $html, $frames);
        foreach ($frames[2] as $key => $href) {
            $newlink = self::parseLink($href, '<iframe' . $frames[1][$key] . ' src=', $frames[3][$key] . '>', $base, $root, $prependBase);

            if ($newlink) {
                $html = str_replace($frames[0][$key], $newlink, $html);
            }
        }

        preg_match_all('/<img([^>]*)\ssrc="([^">]+)"([^>]*)>/Usi', $html, $images);
        foreach ($images[2] as $key => $href) {
            if (strtolower(substr($href, 0, 17)) == "system/images/resampled/") {
                $href = BASE_SCRIPT . $href;
            }

            $href = $prependBase . $href;
            $newframes = '<img' . $images[1][$key] . ' src="' . $href . '"' . $images[3][$key] . '>';
            $html = str_replace($images[0][$key], $newframes, $html);
        }

        if (PROFILE) Profiler::unmark("HTMLParser::process_links");

        return $html;
    }

    /**
     * parses an url and generates a new string with the link, some code before, then some maybe
     * generated attributes and some code after.
     * It basically is parsing the following:
     * - do not change anything if URL is fully qualified with known protocol or prefix.
     *
     * if not fully qualified:
     * - remove double slahes
     * - remove $root if existing
     * - fix for anchors since goma is using base uri
     * - prepend BASE_URI if it does not link to a specific existing file
     * 
     * @param string $href link
     * @param string $beforeHref
     * @param string $afterHref
     * @param string $base
     * @param mixed|string $root
     * @param string $prependBase
     *
     * @return null|string
     */
    public static function parseLink($href, $beforeHref, $afterHref, $base = BASE_SCRIPT, $root = ROOT_PATH, $prependBase = "")
    {
        $attrs = "";

        // check for url in format //www.google.de
        if(preg_match('/^[a-zA-Z0-9\-]*:?\/\//', $href)) {
            return null;
        }

        // check for prefixes.
        foreach (self::$allowedPrefixes as $prefix) {
            if (substr(strtolower($href), 0, strlen($prefix)) == $prefix) {
                return null;
            }
        }

        while(strpos($href, "//")) {
            $href = str_replace("//", "/", $href);
        }

        // check ROOT_PATH
        if (substr(strtolower($href), 0, strlen($root)) == strtolower($root)) {
            $href = substr($href, strlen($root));
        }

        // check anchor
        if (substr(strtolower($href), 0, 1) == "#") {
            $attrs = 'data-anchor="' . substr($href, 1) . '"';
            $href = ((URL . URLEND) != "/") ? (URL . URLEND . $href) : $href;
        }

        // check for existing files.
        if (!preg_match('/\.php\/(.*)/i', $href) && !strpos($href, "?") && file_exists(ROOT . $href)) {
            $href = $prependBase . $href;
        } else if (
            substr(strtolower($href), 0, strlen($base)) != strtolower($base) &&
            substr(strtolower($href), 0, strlen($root . $base)) != strtolower($root . $base) &&
            substr(strtolower($href), 0, 2) != "./"
        ) {
            $href = $prependBase . $base . $href;
        }

        $newlink = $beforeHref . trim('"' . $href . '" ' . $attrs) . $afterHref;

        return $newlink;
    }
}
