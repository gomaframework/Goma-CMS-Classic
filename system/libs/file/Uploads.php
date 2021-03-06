<?php defined('IN_GOMA') OR die();

defined("UPLOAD_DIR") OR die('Constant UPLOAD_DIR not defined, Please define UPLOAD_DIR to proceed.');

loadlang("files");
loadlang("filemanager");

/**
 *
 * @package    goma framework
 * @link        http://goma-cms.org
 * @license    LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 * @author        Goma-Team
 * @version    1.5.13
 *
 * @property string realfile
 * @property string filename
 * @property string path
 * @property string type
 * @property string md5
 * @property string url
 * @property string collectionid
 * @property Uploads|null collection
 * @property int propLinks
 *
 * last modified: 25.08.2015
 */
class Uploads extends DataObject
{
    /**
     * max-filesize for md5
     *
     * @var int
     */
    const FILESIZE_MD5 = 52428800; // 50 MB

    /**
     * @var string
     */
    const PERMISSION_ADMIN = "UPLOADS_MANAGE";

    /**
     * max cache lifetime
     *
     * @var int
     */
    static $cache_life_time = 5356800; // 62 days = 5356800

    /**
     * database-table
     *
     * @var array
     */
    static $db = array(
        "filename"  => "varchar(300)",
        "realfile"  => "varchar(300)",
        "path"      => "varchar(400)",
        "type"      => "enum('collection','file')",
        "md5"       => "varchar(100)",
        "propLinks" => "int(10)",
    );

    /**
     * extensions in this files are by default handled by this class
     *
     * @var array
     */
    static $file_extensions = array();

    /**
     * relations
     */
    static $has_one = array(
        "collection" => "Uploads",
    );

    static $has_many = array(
        "children" => "Uploads",
    );

    /**
     * indexes
     */
    static $index = array(
        array(
            "name"   => "pathlookup",
            "fields" => "path,class_name",
            "type"   => "INDEX",
        ),
        array(
            "name"   => "md5",
            "fields" => "md5",
            "type"   => "INDEX",
        ),
        array(
            "name"   => "realfile_delete",
            "fields" => "realfile",
            "type"   => "INDEX",
        ),
    );

    static $search_fields = array(
        "filename",
        "path",
    );

    /**
     * adds a file to the upload-folder
     *
     * @param $filename
     * @param string $realfile
     * @param string $collectionPath
     * @param string $class_name
     * @param bool $rename
     * @return Uploads
     * @throws Exception
     * @throws FileCopyException
     * @throws PermissionException
     * @throws SQLException
     */
    public static function addFile($filename, $realfile, $collectionPath, $class_name = null, $rename = true)
    {
        if (!file_exists($realfile)) {
            throw new InvalidArgumentException(
                "Realfile ".convert::raw2text($realfile)." not found for Uploads::addFile"
            );
        }

        if (!$collectionPath) {
            throw new InvalidArgumentException("Collection is Required for Uploads::addFile");
        }

        // get collection info
        $collection = self::getCollection($collectionPath);

        // we need a collection, without SQL-DB this does not work,
        // but you can always create Uploads-Object by your own.
        if ($collection === null) {
            throw new LogicException("Collection must be set. A Database-Connection is required for Uploads::addFile.");
        }
        $collectionPath = $collection->hash();

        // determine file-position
        FileSystem::requireFolder(UPLOAD_DIR.md5($collectionPath));

        // generate instance of file.
        $file = self::getFileInstance($realfile, $collection, $filename);

        // now reinit the file-object with maybe guessed class-name.
        /** @var Uploads $file */
        $file = $file->getClassAs(self::getFileClass($class_name, $filename));

        if (file_exists($file->realfile) ||
            ($rename && rename($realfile, $file->realfile)) ||
            (!$rename && copy($realfile, $file->realfile))) {
            FileSystem::chmod($file->realfile, FileSystem::getMode());
            $file->writeToDB(true, true);

            return $file;
        }

        throw new FileCopyException("Cannot copy {$realfile} to {$file->realfile}.");
    }

    /**
     * get file class by class or filename.
     *
     * @param string $class_name
     * @param string $filename
     * @return string
     * @internal param $getFileClass
     */
    public static function getFileClass($class_name, $filename)
    {
        // make it a valid class-name
        if (isset($class_name)) {
            $class_name = trim(strtolower($class_name));
        }

        // guess class-name
        $guessed_class_name = self::guessFileClass($filename);

        // if we dont have a given class-name, use guessed one.
        if (!isset($class_name)) {
            $class_name = $guessed_class_name;

            // if guessed classname is a specialisation of class-name, use guessed one.
        } else if (is_subclass_of($guessed_class_name, $class_name)) {
            $class_name = $guessed_class_name;
        }

        return $class_name;
    }

    /**
     * gets the object for the given file-path
     *
     * @param string $path
     * @return null|Uploads
     * @throws Exception
     */
    public static function getFile($path)
    {
        if (!is_string($path)) {
            return null;
        }

        if (preg_match('/Uploads\/([^\/]+)\/([a-zA-Z0-9]+)\/([^\/]+)/', $path, $match)) {
            $path = $match[1]."/".$match[2]."/".$match[3];
        }

        $cacher = new Cacher("file_".$path);
        if ($cacher->checkValid()) {
            $data = $cacher->getData();

            return new $data["class_name"]($data);
        } else {
            if (($data = DataObject::get_one(Uploads::class, array("path" => $path))) !== null) {
                $cacher->write($data->toArray(), 86400);

                return $data;
            } else if (($data = DataObject::get_one(Uploads::class, array("realfile" => $path))) !== null) {
                $cacher->write($data->toArray(), 86400);

                return $data;
            } else {
                return null;
            }
        }
    }

    /**
     * guesses the file-class
     *
     * @return string
     */
    public static function guessFileClass($filename)
    {
        $ext = strtolower(substr($filename, strrpos($filename, ".") + 1));
        foreach (ClassInfo::getChildren(Uploads::class) as $child) {
            if (in_array($ext, StaticsManager::getStatic($child, "file_extensions"))) {
                return $child;
            }
        }

        return Uploads::class;
    }

    /**
     * builds an instance of file.
     * it checks if file with md5 already exists and creates it if required.
     *
     * @param    string $realfile
     * @param    Uploads $collection
     * @param    string $filename
     * @return   Uploads
     */
    public static function getFileInstance($realfile, $collection, $filename)
    {
        // check for already existing file.
        if (filesize($realfile) < self::FILESIZE_MD5) {
            $md5 = md5_file($realfile);

            /** @var Uploads $uploadObject */
            $uploadObject = DataObject::get_one(static::class, array("md5" => $md5));
            if($uploadObject && file_exists($uploadObject->realfile)) {
                if(md5_file($uploadObject->realfile) == $md5 && $uploadObject->collectionid == $collection->id) {

                    // we found the same file, just create a new DB-Entry, cause we
                    // don't track where db-entry is used. one db entry is for one
                    // connection to another model.
                    $file = clone $uploadObject;
                    $file->collectionid = $collection->id;
                    $file->path = self::buildPath($collection, $filename);
                    $file->filename = $filename;

                    return $file;
                } else {
                    // maybe file of object has changed and md5 is not valid anymore
                    // so rewrite md5-hash of object
                    $uploadObject->md5 = md5_file($uploadObject->realfile);
                    $uploadObject->writeToDB(false, true);
                }
            }
        }

        // generate Uploads-Object.
        return new Uploads(
            array(
                "filename"     => $filename,
                "type"         => "file",
                "realfile"     => UPLOAD_DIR.md5($collection->hash())."/".randomString(8).self::cleanUpURL($filename),
                "path"         => self::buildPath($collection, $filename),
                "collectionid" => $collection->id,
                "md5"          => isset($md5) ? $md5 : null,
            )
        );
    }

    /**
     * returns object of file-collection by given collection-data. (string or object)
     *
     * @param mixed $collectionPath collection as string or object
     * @param bool $useCache use cache to cache results.
     * @param bool $create
     * @return Uploads null if SQL not loaded up, else object of type Uploads or null when create is false and nothing found.
     * @throws Exception
     */
    public static function getCollection($collectionPath, $useCache = true, $create = true)
    {
        if (!is_object($collectionPath)) {
            if (defined("SQL_LOADUP")) {
                $cacher = new Cacher("uploads_collection_".$collectionPath);
                if ($useCache && $cacher->checkValid() && $collectionObject = DataObject::get_by_id(
                        Uploads::class,
                        $cacher->getData()
                    )) {
                    return $collectionObject;
                } else {
                    $collection = self::generateCollectionTree($collectionPath, $create);

                    if ($collection) {
                        $cacher->write($collection->id, 86400);
                    }
                }
            } else {
                return null;
            }
        } else {
            $collection = $collectionPath;
        }

        return $collection;
    }

    /**
     * checks if collection-tree exists and gets last generated or found location.
     *
     * @param string $collectionPath
     * @param bool $create
     * @return Uploads
     * @internal param $generateCollectionTree
     */
    public static function generateCollectionTree($collectionPath, $create)
    {
        $collectionObject = null;
        // determine id of collection
        $collectionTree = explode(".", $collectionPath);

        // check for each level of collection if it is existing.
        foreach ($collectionTree as $collection) {
            /** @var Uploads $collectionObject */
            // find parent collection
            if ($data = DataObject::get_one(
                Uploads::class,
                array(
                    "filename"     => $collection,
                    "collectionid" => isset($collectionObject) ? $collectionObject->id : 0,
                    "type"         => "collection",
                )
            )) {
                $collectionObject = $data;
            } else if ($create) {
                $collectionObject = self::createCollection(
                    $collection,
                    isset($collectionObject) ? $collectionObject->id : 0
                );
            } else {
                return null;
            }
        }

        return $collectionObject;
    }

    /**
     * builds path out of data.
     *
     * @param Uploads $collection
     * @param string $filename
     * @return string
     */
    protected static function buildPath($collection, $filename)
    {
        return strtolower(self::cleanUpURL($collection->hash()))."/".randomString(6)."/".self::cleanUpURL($filename);
    }

    /**
     * removes unwanted letters from string.
     *
     * @param string $path
     * @return string
     */
    protected static function cleanUpURL($path)
    {
        return preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $path);
    }

    /**
     * creates a new collection with given name and id.
     * @param string $name
     * @param int $parentId
     * @return Uploads
     */
    protected static function createCollection($name, $parentId)
    {
        $collection = new Uploads(
            array(
                "filename"     => $name,
                "collectionid" => $parentId,
                "type"         => "collection",
            )
        );
        $collection->writeToDB(true, true);

        return $collection;
    }

    /**
     * removes the file after remvoing from Database
     *
     * @return void
     */
    public function onAfterRemove()
    {
        parent::onAfterRemove();

        if (file_exists($this->realfile)) {
            $data = DataObject::get("Uploads", array("realfile" => $this->realfile));
            if ($data->Count() === 0) {
                FileSystem::delete($this->realfile);
            }
        }

        $cacher = new Cacher("file_".$this->fieldGet("path"));
        $cacher->delete();

        $cacher = new Cacher("file_".$this->fieldGet("realfile"));
        $cacher->delete();

        if (file_exists($this->path)) {
            FileSystem::delete($this->path);
        }

        if ($this->collection) {
            $collectionFiles = $this->collection->getCollectionFiles();
            if ($collectionFiles->count() === 0 ||
                ($collectionFiles->first()->id == $this->id && $collectionFiles->count() == 1)) {
                $this->collection->remove(true);
            }
        }
    }

    /**
     * event on before write
     */
    public function onBeforeWrite($modelWriter)
    {
        parent::onBeforeWrite($modelWriter);

        $CacheForPath = new Cacher("file_".$this->fieldGet("path"));
        $CacheForPath->delete();

        $CacheForRealfile = new Cacher("file_".$this->fieldGet("realfile"));
        $CacheForRealfile->delete();
    }

    /**
     * returns files in the collection
     *
     * @return DataObjectSet
     */
    public function getCollectionFiles()
    {
        if ($this->type == "file") {
            return DataObject::get("Uploads", array("collectionid" => $this->collectionid));
        } else {
            return DataObject::get("Uploads", array("collectionid" => $this->id));
        }
    }

    /**
     * gets a subcollection with given name
     *
     * @param string $name
     * @return Uploads
     */
    public function getSubCollection($name)
    {
        if ($this->type == "file") {
            if (!$this->collection) {
                $this->addToDefaultCollection();
            }

            return $this->collection->getSubCollection($name);
        } else {
            if ($collection = DataObject::get_one("Uploads", array("collectionid" => $this->id, "filename" => $name))) {
                return $collection;
            } else {
                return self::createCollection($name, $this->id);
            }
        }
    }

    /**
     * generates unique path for this collection
     * @return string
     */
    public function hash()
    {
        if ($this->realfile == "") {
            return md5($this->identifier());
        }

        return $this->realfile;
    }

    /**
     * @return null|string
     */
    public function forTemplate()
    {
        return $this->__toString();
    }

    /**
     * generates identifier for collection
     *
     * @return string
     */
    public function identifier()
    {
        if ($this->collection) {
            return $this->collection()->identifier().".".$this->filename;
        } else {
            return $this->filename;
        }
    }

    /**
     * returns the raw-path
     *
     * @return string
     */
    public function raw()
    {
        return $this->path;
    }

    /**
     * returns the path
     *
     * @return string
     */
    public function getPath()
    {
        if (!$this->fieldGET("path") || $this->fieldGet("path") == "Uploads/" || $this->fieldGet("path") == "Uploads") {
            return $this->fieldGET("path");
        }

        return BASE_SCRIPT.'Uploads/'.$this->fieldGET("path");
    }

    /**
     * checks if file has bas and returns without if having.
     *
     * @param string $file
     * @param string $base
     * @return string
     */
    public function checkForBase($file, $base = BASE_SCRIPT)
    {
        if ($existentFile = $this->checkForExistence($file, $base)) {
            return $existentFile;
        }

        if (substr($file, -1) != URLEND) {
            return $file.URLEND;
        }

        return $file;
    }

    /**
     * sets the path
     */
    public function setPath($path)
    {
        if (substr($path, 0, strlen(BASE_SCRIPT)) == BASE_SCRIPT) {
            $path = substr($path, strlen(BASE_SCRIPT));
        }

        if (substr($path, 0, strlen("index.php/")) == "index.php/") {
            $path = substr($path, strlen("index.php/"));
        }

        if (substr($path, 0, 8) == "Uploads/") {
            $this->setField("path", substr($path, 8));
        } else {
            $this->setField("path", $path);
        }
    }

    /**
     * to string
     *
     * @return null|string
     */
    public function __toString()
    {
        if ($this->bool()) {
            return '<a href="'.$this->raw().'">'.$this->filename.'</a>';
        } else {
            return "";
        }
    }

    /**
     * returns the path to the icon of the file
     *
     * @param int $size ; support for 16, 32, 64 and 128
     * @return string
     */
    public function getIcon($size = 128, $retina = false)
    {
        if ($this->type == "file") {
            switch ($size) {
                case 16:
                case 32:
                case 64:
                    if ($retina) {
                        return "system/images/icons/goma".$size."/file@2x.png";
                    } else {
                        return "system/images/icons/goma".$size."/file.png";
                    }
                    break;
            }

            return "system/images/icons/goma/128x128/file.png";
        } else {
            if ($size > 16 || $retina) {
                return "system/images/icons/fatcow16/folder@2x.png";
            }

            return "system/images/icons/fatcow16/folder.png";
        }
    }

    /**
     * local argument Query
     *
     * @name argumentQuery
     * @access public
     */

    public function argumentQuery(&$query)
    {
        parent::argumentQuery($query);

        if (isset($query->filter["path"])) {
            if (substr($query->filter["path"], 0, strlen(BASE_SCRIPT)) == BASE_SCRIPT) {
                $query->filter["path"] = substr($query->filter["path"], strlen(BASE_SCRIPT));
            }

            if (substr($query->filter["path"], 0, strlen("index.php/")) == "index.php/") {
                $query->filter["path"] = substr($query->filter["path"], strlen("index.php/"));
            }

            if (substr($query->filter["path"], 0, strlen("Uploads")) == "Uploads") {
                $query->filter["path"] = substr($query->filter["path"], strlen("Uploads") + 1);
            }
        }
    }

    /**
     * gets the formatted file-size
     *
     * @return string
     */
    public function formattedFileSize()
    {
        return FileSizeFormatter::format_nice(@filesize($this->realfile));
    }

    /**
     * gets the file-size
     *
     * @return int
     */
    public function filesize()
    {
        return @filesize($this->realfile);
    }

    /**
     * returns if this dataobject is valid
     * @return bool
     */
    public function bool()
    {
        if (parent::bool()) {
            return ($this->type == "collection" || ($this->realfile !== "" && is_file($this->realfile)));
        } else {
            return false;
        }
    }

    /**
     * checks for the permission to show this file
     *
     * @param DataObject|null $row
     * @param string|null $name
     * @return bool
     * @internal param $checkPermission
     */
    public function checkPermission($row = null, $name = null)
    {
        if (!isset($row) && !isset($name)) {
            $check = true;
            $this->callExtending("checkPermission", $check);

            return $check;
        }

        return parent::checkPermission($row, $name);
    }

    /**
     * returns url.
     */
    public function getUrl()
    {
        return BASE_URI.$this->checkForBase($this->getPath()."/".$this->filename);
    }

    /**
     * to array if we need data for REST-API.
     * @param array $additional_fields
     * @return array
     */
    public function ToRESTArray($additional_fields = array())
    {
        $arr = parent::ToRESTArray($additional_fields);
        $arr["path"] = $this->getPath();
        $arr["url"] = $this->url;

        unset($arr["realfile"]);

        return $arr;
    }

    /**
     * @return bool
     */
    public function hasNoLinks()
    {
        return $this->getLinkingModels()->first() == null;
    }

    /**
     * @return DataObjectSet
     */
    public function getLinkingModels()
    {
        return new DataObjectSet(new UploadsBackTrackDataSource($this));
    }

    public function getManagePath()
    {
        if ($this->type == "collection") {
            return "Uploads/manageCollection/".$this->id.URLEND;
        }

        return "Uploads/manage/".$this->fieldGet("path");
    }

    public function providePerms()
    {
        return array(
            self::PERMISSION_ADMIN => array(
                "title"   => '{$_lang_uploads_manage}',
                "default" => array(
                    "type" => "admins",
                ),
            ),
        );
    }

    public function getFileVersions()
    {
        return DataObject::get(
            Uploads::class,
            array(
                "md5" => $this->md5,
            )
        );
    }

    /**
     * @param string $file
     * @param string $base
     * @return bool
     */
    protected function checkForExistence($file, $base = BASE_SCRIPT)
    {
        if (substr($file, 0, strlen($base)) == $base) {
            $fileWithoutBase = substr($file, strlen($base));

            return (file_exists($fileWithoutBase) && !is_dir($fileWithoutBase)) ? $fileWithoutBase : null;
        }

        return file_exists($file) && !is_dir($file) ? $file : null;
    }

    /**
     * creates a default collection and adds this file to it.
     */
    protected function addToDefaultCollection()
    {
        $this->collection = self::getCollection("default");

        if ($this->id != 0) {
            $this->writeToDB(false, true);
        }
    }
}
