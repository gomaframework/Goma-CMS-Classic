<?php defined("IN_GOMA") OR die();
/**
 * Unit-Tests for Uploads-Class.
 *
 * @package		Goma\Test
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */


class UploadsTest extends GomaUnitTest {
	protected $filename;
	protected $testfile;
	protected $testTextFile;

	/**
	 * setup.
	*/
	public function setUp() {
		$this->testfile = "./system/tests/resources/IMG_2008.jpg";
		$this->filename = "uploads_testimg.jpg";

		// force no old versions of file.
		$data = DataObject::get("uploads", array("md5" => md5_file($this->testfile)));

		if($data->count() > 0) {
			foreach($data as $record) {
				$record->remove(true);
			}
		}

		$this->testTextFile = FRAMEWORK_ROOT . "temp/test.txt";
		file_put_contents($this->testTextFile, randomString(100));
	}

	public function tearDown() {
		@unlink($this->testTextFile);
	}

	public function testAddExistsAndRemove() {
	    try {
            // store first file.
            $file = Uploads::addFile("1".$this->filename, $this->testfile, "FormUpload", null, false);

            $this->assertEqual("1".$this->filename, $file->filename);
            $this->assertEqual(strtolower(ImageUploads::class), $file->classname);
            $this->assertTrue(file_exists($file->realfile));
            $this->assertEqual(md5_file($file->realfile), md5_file($this->testfile));
            $this->assertEqual(md5_file($this->testfile), $file->md5);
            $this->assertEqual($file->collection->filename, "FormUpload");


            // file2 test: Tests deletable for same file and some tests with same file/collection
            // check for second file, which should be stored.
            $file2 = Uploads::addFile($this->filename.".jpg", $this->testfile, "FormUpload", null, false);

            $this->assertTrue(file_exists($file2->realfile));
            $this->assertEqual($file->realfile, $file2->realfile);
            $this->assertNotEqual($file->filename, $file2->filename);
            $this->assertEqual($file->md5, $file2->md5);

            // file3: tests stuff with different collection but same file
            $file3 = Uploads::addFile($this->filename, $this->testfile, "TestUpload", null, false);
            $this->assertTrue(file_exists($file3->realfile));
            $this->assertNotEqual($file->realfile, $file3->realfile);
            $this->assertEqual($file->filename, "1".$file3->filename);
            $this->assertEqual($file->md5, $file3->md5);

            $this->assertTrue($file->bool());

            $this->assertNotNull($file2);

            if (isset($file2)) {
                $this->assertTrue($file2->bool());

                // check for file if we delete one.
                $file2->remove(true);
                $this->assertFalse($file2->bool());
            }

            $this->assertTrue(file_exists($file->realfile));

            $this->textFileTests();

            // try to get file.
            $path = $file->path;

            $this->match($path, $file);
            $this->match(BASE_URI.$path, $file);
            $this->match(BASE_URI.$path."/orgSetSize/20/20/", $file);
            $this->match("./".$path."/orgSetSize/20/20/", $file);
            $this->match("./".$path, $file);

            // test deletes#
            // $img is now $file here!!
            if ($img = Uploads::getFile($path)) {
                $this->assertEqual($img->md5, $file->md5);
                $this->assertEqual($img->md5, md5_file($this->testfile));

                FileSystem::requireDir($img->path);
                $this->assertTrue(file_exists($this->getFileWithoutBase($img->path)));

                $realfile = $img->realfile;
                $img->remove(true);

                $this->assertFalse(file_exists($this->getFileWithoutBase($img->path)));
                $this->assertFalse($img->bool());
                $this->assertFalse(file_exists($realfile));
            } else {
                $this->assertTrue(false);
            }

            $textfile = Uploads::getFile($this->textfile->fieldGet("path"));
            if (isset($textfile)) {
                $this->assertEqual($textfile->md5, md5_file($this->testTextFile));
                $textfile->remove(true);
                $this->assertFalse(file_exists($textfile->realfile));
            }
        } finally {
	        if($file) {
	            $file->remove(true);
            }

            if($file2) {
	            $file2->remove(true);
            }

            if($file3) {
	            $file3->remove(true);
            }
        }
	}

	/**
	 * gets file without base.
	 *
	 * @param string $file
	 * @return string
	 */
	protected function getFileWithoutBase($file) {
		if(substr($file, 0, strlen(BASE_SCRIPT)) == BASE_SCRIPT) {
			return substr($file, strlen(BASE_SCRIPT));
		}

		return $file;
	}

	/**
	 * checks for hash-method.
	 */
	public function testNoDBInterface() {
		/** @var Uploads $file */
		$file = new Uploads(array(
			"filename" 		=> "test.txt",
			"type"			=> "file",
			"realfile"		=> $this->testTextFile,
			"path"			=> "",
			"collectionid" 	=> 0,
			"md5"			=> null
		));

		$this->assertTrue(file_exists($file->realfile));
		$this->assertNull($file->collection);
		$this->assertEqual($file->hash(), $file->realfile, "hash()-method should return md5 of filename.");
	}

	public function textFileTests() {
		$textfilename = basename($this->testTextFile);
		$textfile = Uploads::addFile(basename($this->testTextFile), $this->testTextFile, "FormUpload", null, false);
		$this->assertEqual($textfile->filename, $textfilename);
		$this->assertEqual($textfile->classname, "uploads");
		$this->assertTrue(file_exists($textfile->realfile));
		$this->assertEqual(md5_file($textfile->realfile), md5_file($this->testTextFile));
		$this->assertEqual(md5_file($this->testTextFile), $textfile->md5);
		$this->assertEqual($textfile->collection->filename, "FormUpload");

		$this->textfile = $textfile;
	}

	public function match($path, $file) {
		$match = Uploads::getFile($path);
		$this->assertEqual($match->md5, $file->md5);
		$this->assertEqual($match->filename, $file->filename);
	}

	/**
	 * tests collections.
	 */
	public function testCollection() {
	    try {
            $collection = "test.c.t.b.a.d.t.d.e.d";
            $file = Uploads::addFile($this->filename, $this->testfile, $collection, null, false);

            $this->assertEqual($file->collection->collection->collection->collection->filename, "t");
            $this->assertEqual($file->collection->collection->collection->collection->collection->filename, "d");
            $this->assertEqual(
                $file->collection->collection->collection->collection->getSubCollection("d")->filename,
                "d"
            );

            $file->remove(true);

            $this->assertNull(Uploads::getCollection($collection, true, false));
        } finally {
	        if($file) {
	            $file->remove(true);
            }
        }
	}

	/**
	 * tests for filenames which are not normal.
	 */
	public function testStrangeFilenames() {

		$collection1 = "FormUpload";
		$collection2 = "FormUpload.Blub";
		$collection3 = "t.b.a.d.t.d.e.d";

		$this->assertPattern(
			"/^Uploads\/".preg_quote(md5($collection1), "/")."\/[a-zA-Z0-9]+\/file_123_.jpg$/",
			$this->unitTestStrangeFilename("file+123 .jpg", $this->testfile, $collection1)
		);

		$this->assertPattern(
			"/^Uploads\/".preg_quote(md5($collection2), "/")."\/[a-zA-Z0-9]+\/file_123_.jpg$/",
			$this->unitTestStrangeFilename("file+123 .jpg", $this->testfile, $collection2)
		);

		$this->assertPattern(
			"/^Uploads\/".preg_quote(md5($collection2), "/")."\/[a-zA-Z0-9]+\/file-123_.jpg$/",
			$this->unitTestStrangeFilename("file-123 .jpg", $this->testfile, $collection2)
		);
		$this->assertPattern(
			"/^Uploads\/".preg_quote(md5($collection3), "/")."\/[a-zA-Z0-9]+\/file-123_.jpg$/",
			$this->unitTestStrangeFilename("file-123 .jpg", $this->testfile, $collection3)
		);
	}

	public function unitTestStrangeFilename($filename, $testfile, $collection) {
		// store first file.
		if($file = Uploads::addFile($filename, $testfile, $collection, null, false)) {
			$path = $file->path;

			$file->remove(true);

			return $path;
		} else {
			return null;
		}
	}

	public function testFileVersions() {
	    try {
            foreach (DataObject::get(
                Uploads::class,
                array(
                    "md5" => md5_file($this->testfile)
                )
            ) as $file) {
                $file->remove(true);
            }

            $this->assertEqual(
                0,
                DataObject::get(
                    Uploads::class,
                    array(
                        "md5" => md5_file($this->testfile)
                    )
                )->count()
            );

            if ($file1 = Uploads::addFile("blub.jpg", $this->testfile, "testCollection", null, false)) {
                if ($file2 = Uploads::addFile("blah.jpg", $this->testfile, "testCollection", null, false)) {

                    $this->assertEqual(2, $file1->getFileVersions()->count());
                    $this->assertEqual($file1->id, $file1->getFileVersions()->first()->id);
                    $this->assertEqual($file2->id, $file1->getFileVersions()->last()->id);
                    $file2->remove(true);
                } else {
                    $this->assertFalse(true, "Could not add file to collection.");
                }
                $file1->remove(true);
            } else {
                $this->assertFalse(true, "Could not add file to collection.");
            }
        } finally {
	        if($file1) {
	            $file1->remove(true);
            }

            if($file2) {
	            $file2->remove(true);
            }
        }
	}

    /**
     * tests if getLinkingModels returns linked models correctly.
     *
     * 1. Adds a file to file archive, set to $file
     * 2. Assert that $file->getLinkingModels->getDbDataSource() is of type UploadsBackTrackDataSource
     * 3. Assert that $file->getLinkingModels->count() is equal to 0
     * 4. Assert that $file->getLinkingModels()->getModelSource() is null
     *
     * 5. Create MockBackTrackModel with file $file, set to $model
     * 6. Write this to DB.
     *
     * 7. Assert that $file->getLinkingModels->count() is equal to 1
     * 8. Assert that $file->getLinkingModels()->first()->id is equal to $model->id
     * 9. Assert that $file->getLinkingModels()->first()->classname is equal to $model->classname
     *
     * 10. Cleanup model and file
     */
	public function testBacktrack() {
	    try {
            if ($file = Uploads::addFile("blub.jpg", $this->testfile, "testCollection", null, false)) {

                $this->assertIsA($file->getLinkingModels()->getDbDataSource(), UploadsBackTrackDataSource::class);
                $this->assertEqual($file->getLinkingModels()->count(), 0);
                $this->assertNull($file->getLinkingModels()->getModelSource());

                $model = new MockBacktrackModel(array(
                    "file" => $file
                ));
                $model->writeToDB(false, true);

                $this->assertEqual($file->getLinkingModels()->count(), 1);

                $linkingModel = $file->getLinkingModels()->first();
                $this->assertEqual($linkingModel->id, $model->id);
                $this->assertEqual($linkingModel->classname, $model->classname);
            } else {
                $this->assertFalse(true, "Could not add file to collection.");
            }
        } finally {
	        if($model) {
                $model->remove(true);
            }

            if($file) {
                $file->remove(true);
            }
        }
	}

    /**
     * tests if __toString returns empty string if Uploads is empty.
     *
     * 1. Create Uploads Object, set to $uploads
     * 2. Assert that (string) $uploads equals to ""
     */
	public function testToStringEmptyUploads() {
	    $uploads = new Uploads();
	    $this->assertEqual("", (string) $uploads);
    }

    /**
     * tests if __toString returns link to path with title as filename if realfile is existing.
     *
     * 1. Create Uploads()array('path' => 'abc', 'filename' => '123.pdf', 'realfile' => ROOT . 'index.php')), set to $uploads
     * 2. Assert that (string) $uploads equals to '<a href="Uploads/abc">123.pdf</a>'
     */
    public function testToString() {
        $uploads = new Uploads(array('path' => 'abc', 'filename' => '123.pdf', 'realfile' => ROOT . 'index.php'));
        $this->assertEqual("<a href=\"Uploads/abc\">123.pdf</a>", (string) $uploads);
    }
}

class MockBacktrackModel extends DataObject {
	static $has_one = array(
		"file" => "Uploads"
	);
}
