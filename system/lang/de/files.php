<?php
defined('IN_GOMA') OR die('');
/**
 * @package goma framework
 * @link http://goma-cms.org
 * @license: LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 * @Copyright (C) 2009 - 2018 Goma-Team
 */

$files_lang = array(
    "filetype_failure" => "Dieser Dateityp ist an dieser Stelle nicht erlaubt!",
    "filesize_failure" => "Diese Datei ist für diesen Ort zu groß.",
    "upload_failure"   => "Die Datei konnte nicht auf den Server geladen werden.",
    "browse"           => "Datei Hochladen",
    "replace"          => "Datei ersetzen",
    "delete"           => "Datei löschen",
    "filename"         => "Dateiname",
    "upload"           => "Hochladen",
    "no_file"          => "Keine Datei vorhanden",
    "upload_success"   => "Die Datei wurde erfolgreich hochgeladen!",
    "size"             => "Dateigröße",
    "backtrack"        => "Verwendung",
    "manage_file"      => "Datei administrieren",
);

foreach ($files_lang as $key => $value) {
    $GLOBALS['lang']['files.'.$key] = $value;
}

