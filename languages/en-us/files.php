<?php defined("IN_GOMA") OR die();

/**
  * @package goma
  * @link http://goma-cms.org
  * @license LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
  * @Copyright (C) 2009 - 2011 Goma-Team
  * last modified: 08.12.2011
  * 001
*/

$files_lang = array(
	"filetype_failure"	=> "This filetype isn't allowed here!",
	"filesize_failure"	=> "This file is to big for this place!",
	"upload_failure"	=> "The file couldn't uploaded to the server.",
	"browse"			=> "Upload",
	"replace"			=> "Replace file",
	"delete"			=> "Delete file",
	"filename"			=> "filename",
	"upload"			=> "upload",
	"no_file"			=> "There is not file visible here",
	"upload_success"	=> "The file was uploaded successfully!",
	"size"				=> "filesize",
	"backtrack"			=> "Backlinks"
);

foreach($files_lang as $key => $value)
{
	$GLOBALS['lang']['files.'.$key] = $value;
}
