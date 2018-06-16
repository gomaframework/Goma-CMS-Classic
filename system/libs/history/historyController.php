<?php defined("IN_GOMA") OR die();

/**
 * gives Controls to get history-information and compare two versions.
 *
 * @package        Goma\libs\History
 *
 * @author        Goma-Team
 * @license        GNU Lesser General Public License, version 3; see "LICENSE.txt"
 * @version        1.1.1
 */
class HistoryController extends Controller
{
    /**
     * url-handlers
     *
     * @name url_handlers
     * @access public
     */
    static $url_handlers = array(
        'compareVersion/$class!/$id!/$nid!' => "compareVersion",
        'restoreVersion/$class!/$id!' => "restoreVersion",
        '$c/$i' => "index"
    );

    /**
     * allowed actions
     *
     * @name allowed_actions
     */
    static $allowed_actions = array(
        "compareVersion" => "->canCompareVersion",
        "restoreVersion" => "->canRestoreVersion"
    );

    /**
     * renders the history for given filter
     *
     * @param DataObjectSet|array $filter
     * @param string $namespace
     * @return bool|string
     */
    public static function renderHistory($filter, $namespace = null)
    {
        if (is_a($filter, "DataObjectSet")) {
            $data = $filter;
        } else {
            if (isset($filter["dbobject"])) {
                $dbObjectFilter = array();
                foreach ((array)$filter["dbobject"] as $class) {
                    $dbObjectFilter = array_merge($dbObjectFilter, array($class), ClassInfo::getChildren($class));
                }
                $filter["dbobject"] = array_intersect(ArrayLib::key_value($dbObjectFilter), array_keys(History::supportHistoryView()));
                if (count($filter["dbobject"]) == 0) {
                    return false;
                }
            } else {
                $filter["dbobject"] = array_keys(History::supportHistoryView());
            }

            $filter["dbobject"] = array_map("strtolower", $filter["dbobject"]);

            $data = DataObject::get("History", $filter);
        }

        $id = "history_" . md5(var_export($filter, true));

        $dbfilter = is_array($filter["dbobject"]) ? $filter["dbobject"] : array();
        return $data->customise(array("id" => $id, "namespace" => $namespace, "filter" => json_encode($dbfilter)))->renderWith("history/history.html");
    }

    /**
     * name of this controller
     *
     * @return null|string
     */
    public function PageTitle()
    {
        return lang("history");
    }

    /**
     * index-method
     *
     * @return bool|string
     */
    public function index()
    {
        $filter = array();
        $class = $this->getParam("c");
        if (isset($class))
            $filter["dbobject"] = $class;

        $item = $this->getParam("i");
        if (isset($item))
            $filter["recordid"] = $item;

        // render the tabset
        $tabs = new DataSet();
        if (isset($filter["dbobject"]) && ClassInfo::exists($filter["dbobject"])) {
            $content = HistoryController::renderHistory($filter, $this->namespace);
            if ($content) {
                $tabs->add(array(
                    "title" => ClassInfo::getClassTitle($filter["dbobject"]),
                    "name" => $filter["dbobject"],
                    "content" => $content
                ));
            }
        }

        $tabs->add(array(
            "name" => "h_all_events",
            "title" => lang("h_all_events"),
            "content" => HistoryController::renderHistory(array(), $this->namespace)
        ));
        return GomaResponseBody::create($tabs->renderWith("tabs/tabs.html"))->setIsFullPage($this->getRequest()->is_ajax());
    }

    /**
     * you can restore a version if you are author or publisher
     */
    public function canRestoreVersion()
    {
        if (ClassInfo::exists($this->getParam("class"))) {
            if ($data = DataObject::get_one($this->getParam("class"), array("versionid" => $this->getParam("id")))) {
                if ($data->canWrite() || $data->canPublish()) {
                    return true;
                }
            } else {
                return false;
            }
        }

        return false;
    }

    /**
     * you can compare a version if you are author or publisher
     *
     * @name canCompareVersion
     * @return bool
     */
    public function canCompareVersion()
    {
        if (ClassInfo::exists($this->getParam("class"))) {
            if ($data = DataObject::get_one($this->getParam("class"), array("versionid" => $this->getParam("nid")))) {
                if ($data->canWrite() || $data->canPublish()) {
                    return true;
                }
            } else {
                return false;
            }
        }

        return false;
    }

    /**
     * restores a version
     *
     * @name restoreVersion
     * @return ControllerRedirectResponse|string
     */
    public function restoreVersion()
    {
        $version = DataObject::get_one($this->getParam("class"), array("versionid" => $this->getParam("id")));
        if ($version->canWrite() || $version->canPublish()) {

            $description = $version->generateRepresentation(true);
            if (isset($description)) {
                $description .= " " . lang("version_by") . " ";
                if ($version->editor) {
                    $description .= '<a href="member/' . $version->editor->ID . URLEND . '" class="user">' . convert::Raw2xml($version->editor->title) . '</a>';
                } else {
                    $description .= '<span style="font-style: italic;">System</span>';
                }
                $description .= " " . $version->last_modified()->ago();
            }

            return $this->confirmByForm(lang("restore_confirm"), function() use($version) {
                if ($version->canWrite()) {
                    $version->writeToDB(false, true, 1);
                } else {
                    $version->writeToDB(false, true, 2);
                }

                return $this->redirectBack();
            }, null, null, $description);
        } else {
            return lang("less_rights");
        }
    }

    /**
     * compares two versions
     *
     * @return string
     */
    public function compareVersion()
    {
        $oldversion = DataObject::get_one($this->getParam("class"), array("versionid" => $this->getParam("id")));
        $newversion = DataObject::get_one($this->getParam("class"), array("versionid" => $this->getParam("nid")));

        $casting = $oldversion->casting();

        // get all fields for compare-view
        $compareFields = $oldversion->getVersionedFields();
        if ($compareFields) {
            $view = new ViewAccessableData();
            $fieldset = new DataSet();
            foreach ($compareFields as $field => $title) {
                // get data
                if (isset($oldversion[$field]) && isset($newversion[$field])) {
                    $oldversiondata = $this->getDataFromVersion($field, $oldversion);
                    $newversiondata = $this->getDataFromVersion($field, $newversion);

                    if ($casting[strtolower($field)] = DBField::parseCasting($casting[strtolower($field)])) {
                        $compareContent = call_user_func_array(
                            array($casting[strtolower($field)]["class"], "getDiffOfContents"),
                            array(
                                $field,
                                (isset($casting[strtolower($field)]["args"])) ? $casting[strtolower($field)]["args"] : array(),
                                $oldversiondata,
                                $newversiondata
                            )
                        );
                        $fieldset->push(array("title" => $title, "content" => $compareContent));
                    } else {
                        $fieldset->push(array("title" => $title, "content" =>
                            '<del>' . convert::raw2text($oldversiondata) . '</del>' .
                            '<ins>' . convert::raw2text($newversiondata) . '</ins>'));
                    }
                }
            }

            return $view->customise(array("fields" => $fieldset, "css" => $this->buildEditorCSS()))->renderWith("history/compare.html");
        } else {
            throw new LogicException("No fields for version-comparing for class " . $oldversion->classname . ". Please create method " . $oldversion->classname . "::getVersionedFields with array as return-value.");
        }
    }

    /**
     * gets correct data from versions
     *
     * @name getDataFromVersion
     * @param string $field
     * @param object $version
     * @return null|string
     */
    public function getDataFromVersion($field, $version)
    {
        if (strpos($field, ".")) {
            $tmpItem = clone $version;
            $fieldNameParts = explode(".", $field);

            for ($idx = 0; $idx < sizeof($fieldNameParts); $idx++) {
                $methodName = $fieldNameParts[$idx];
                // Last mmethod call from $columnName return what that method is returning
                if ($idx == sizeof($fieldNameParts) - 1) {
                    return (string)$tmpItem;
                }
                // else get the object from this $methodName
                $tmpItem = $tmpItem->$methodName();
            }
            return null;
        }

        if (isset($version[$field])) {
            return $version[$field];
        }

        throw new InvalidArgumentException("$field doesn't exist on version of type " . $version->classname . " with id " . $version->versionid);
    }

    /**
     * converts diff to HTML
     *
     * @name diffToHTML
     * @return mixed|string
     */
    public function diffToHTML($diffs)
    {
        $html = array();
        $blockElements = "p|h1|h2|h3|h4|h5|h6|div|blockquote|noscript|form|fieldset|adress|li|ul";
        $i = 0;
        for ($x = 0; $x < count($diffs); $x++) {
            $html[$x] = "";
            $add = "";
            $op = $diffs[$x][0]; // Operation (insert, delete, equal)
            $data = $diffs[$x][1]; // Text of change.
            /*$text = preg_replace(array (
                '/&/',
                '/</',
                '/>/',
                "/\n/"
            ), array (
                '&amp;',
                '&lt;',
                '&gt;',
                '&para;<BR>'
            ), $data);*/
            $text = trim($data);

            if (trim($text) == "") {
                continue;
            }

            if (preg_match('/^(\<(' . $blockElements . ')[^\>]*\>)(.*)\<\/\2\>$/si', $text, $m)) {
                $html[$x] = $m[1];
                $text = $m[3];
                $add = "</" . $m[2] . ">";
            }

            switch ($op) {
                case DIFF_INSERT :
                    $html[$x] .= '<ins>' . $text . '</ins>';
                    break;
                case DIFF_DELETE :
                    $html[$x] .= '<del>' . $text . '</del>';
                    break;
                case DIFF_EQUAL :
                    $html[$x] .= $text;
                    break;
            }

            $html[$x] = preg_replace('/^\s*\<(ins|del)\>\s*\<\/(' . $blockElements . ')\>\s*\<(' . $blockElements . ')\>/Usi', "</$2><$3><$1>", $html[$x]);

            if (isset($add)) {
                $html[$x] .= $add;
            }

            if ($op !== DIFF_DELETE) {
                $i += mb_strlen($data);
            }
        }
        $output = implode('', $html);


        // run output fixes here

        // img-fixes
        preg_match_all('/\<img(.*)\/\>/Usi', $output, $matches);
        foreach ($matches[0] as $key => $tag) {
            if (preg_match('/float\:\s*(left|right)/i', $tag, $match)) {
                $floating = 'float: ' . $match[1];
            } else {
                $floating = "";
            }

            if (strpos($tag, "<ins>") && strpos($tag, "<del>")) {
                $delTag = $tag;
                $delTag = str_replace('<del>', '', $delTag);
                $delTag = str_replace('</del>', '', $delTag);
                $delTag = preg_replace('/\<ins>(.*)\<\/ins\>/Usi', "", $delTag);

                $insTag = $tag;
                $insTag = str_replace('<ins>', '', $insTag);
                $insTag = str_replace('</ins>', '', $insTag);
                $insTag = preg_replace('/\<del>(.*)\<\/del\>/Usi', "", $insTag);

                $tag = "<del style=\"display: block;$floating\">" . $delTag . "</del><ins style=\"display: block;$floating\">" . $insTag . "</ins>";

            } else if (strpos($tag, "<ins>")) {

                $tag = str_replace('<ins>', '', $tag);
                $tag = str_replace('</ins>', '', $tag);
                $tag = "<ins style='$floating'>" . $tag . "</ins>";
            } else if (strpos($tag, "<del>")) {
                $tag = str_replace('<del>', '', $tag);
                $tag = str_replace('</del>', '', $tag);
                $tag = "<del style='$floating'>" . $tag . "</del>";
            }

            $output = str_replace($matches[0][$key], $tag, $output);
        }

        // a-fixes
        preg_match_all('/\<a(.*)\>(.*)\<\/a\>/Usi', $output, $matches);
        foreach ($matches[0] as $key => $tag) {
            if (strpos($tag, "<ins>") && strpos($tag, "<del>")) {
                $delTag = $tag;
                $delTag = str_replace('<del>', '', $delTag);
                $delTag = str_replace('</del>', '', $delTag);
                $delTag = preg_replace('/\<ins>(.*)\<\/ins\>/Usi', "", $delTag);

                $insTag = $tag;
                $insTag = str_replace('<ins>', '', $insTag);
                $insTag = str_replace('</ins>', '', $insTag);
                $insTag = preg_replace('/\<del>(.*)\<\/del\>/Usi', "", $insTag);

                $tag = "<del style=\"display: block;\">" . $delTag . "</del><ins style=\"display: block;\">" . $insTag . "</ins>";

            } else if (strpos($tag, "<ins>")) {
                $tag = str_replace('<ins>', '', $tag);
                $tag = str_replace('</ins>', '', $tag);
                $tag = "<ins>" . $tag . "</ins>";
            } else if (strpos($tag, "<del>")) {
                $tag = str_replace('<del>', '', $tag);
                $tag = str_replace('</del>', '', $tag);
                $tag = "<del>" . $tag . "</del>";
            }

            $output = str_replace($matches[0][$key], $tag, $output);
        }

        // script-tags - we remove them
        $output = preg_replace('/\<script(.*)\>(.*)\<\/script\>/Usi', '', $output);

        return $output;
    }

    /**
     * builds editor.css
     *
     * @name buildEditorCSS
     * @return bool|string
     */
    public function buildEditorCSS()
    {
        $cache = ROOT . CACHE_DIRECTORY . "/editor_compare_" . Core::GetTheme() . ".css";
        if ((!file_exists($cache) || filemtime($cache) < TIME + 300) && file_exists("tpl/" . Core::getTheme() . "/editor.css")) {
            $css = self::importCSS("system/templates/css/default.css") . "\n" . self::importCSS("tpl/" . Core::getTheme() . "/editor.css");

            // parse CSS
            $css = preg_replace_callback('/([\.a-zA-Z0-9_\-,#\>\s\:\[\]\=]+)\s*{/Usi', array("historyController", "interpretCSS"), $css);
            FileSystem::write($cache, $css);

            return $cache;
        } else {
            return false;
        }
    }

    /**
     * interprets the CSS
     *
     * @name interpretCSS
     * @return string
     */
    public static function interpretCSS($matches)
    {
        if (preg_match('/^(body|html)?,?\s*(html|body)?$/i', trim($matches[1]))) {
            return "\n.compareView .content {";
        } else {
            $exps = explode(",", trim($matches[1]));
            $out = "\n";
            foreach ($exps as $exp) {
                $out .= ".compareView .content " . trim($exp) . ", ";
            }
            return $out . " { ";
        }
    }

    /**
     * gets a consolidated CSS-File, where imports are merged with original file
     *
     * @name importCSS
     * @param string - file
     * @return mixed|string
     */
    public static function importCSS($file)
    {
        if (file_exists($file)) {
            $css = file_get_contents($file);
            // import imports
            preg_match_all('/\@import\s*url\(("|\')([^"\']+)("|\')\)\;/Usi', $css, $m);
            foreach ($m[2] as $key => $_file) {
                $css = str_replace($m[0][$key], self::importCSS(dirname($file) . "/" . $_file), $css);
            }

            return $css;
        }

        return "";
    }
}
