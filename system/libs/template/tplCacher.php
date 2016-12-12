<?php namespace Goma\Template;
defined("IN_GOMA") OR die();

/**
 * tpl-cacher
 * caches compiled files
 *
 * @link            http://goma-cms.org
 * @license:        LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 * @author          Goma-Team
 * @package goma\template
 */
class tplcacher extends \gObject
{
    /**
     * @var string - the filename of the cachefile
     */
    private $filename;

    /**
     * @var int - last modfied of the template
     */
    private $lastmodify;

    /**
     * @var int - last modified of the cachefile
     */
    private $clastmodify;

    /**
     * @var bool - whether cache is valid
     */
    private $valid;

    /**
     * @param string $name
     * @param int $lastmodify
     */
    public function __construct($name, $lastmodify)
    {
        if (PROFILE) \Profiler::mark("tplcache");

        parent::__construct();

        $name = preg_replace("/[^a-zA-Z0-9_\-]/", "_", $name);
        $this->filename = ROOT . CACHE_DIRECTORY . "/tpl." . $name . ".php";
        $this->lastmodify = $lastmodify;
        if (!isset($_GET["flush"]) && is_file($this->filename)) {
            $this->clastmodify = filemtime($this->filename);
            if ($this->clastmodify > $this->lastmodify) {
                $this->valid = true;
            } else {
                $this->valid = false;
            }
        } else {
            $this->valid = false;
        }

        if (PROFILE) \Profiler::unmark("tplcache");
    }

    /**
     * gets the filename
     */
    public function filename()
    {
        return $this->filename;
    }

    /**
     * @return bool - whether valid or not
     */
    public function checkvalid()
    {
        return $this->valid;
    }

    /**
     * @param string $data
     * @return null
     */
    public function write($data)
    {
        if (PROFILE) \Profiler::mark("tplcache::write");
        $d = '<?php
defined(\'IN_GOMA\') OR die(\'<!-- restricted access -->\'); // silence is golden ;)
if(!isset($data))
	return false;
?>
';

        if (\FileSystem::write($this->filename, $d . $data)) {
            if (PROFILE) \Profiler::unmark("tplcache::write");

            return true;
        } else {
            if (PROFILE) \Profiler::unmark("tplcache::write");

            return false;
        }
    }
}
