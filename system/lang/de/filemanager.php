<?php
/**
 * @package goma
 * @link http://goma-cms.org
 * @license: LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 * @author Goma-Team
 * last modified: 02.07.2011
 */

$filemanager_lang = array('new_directory'    => "Neuer Ordner",
                          'new_file'         => "Neue Datei",
                          'new_name'         => "Neuer Dateiname",
                          'current_dir'      => "Aktuelles Verzeichnis",
                          'actions'          => "Aktionen",
                          'flush'            => "leeren",
                          'upload'           => "Hochladen",
                          'unzip'            => "Archiv entpacken",
                          'edit_file'        => "Datei bearbeiten",
                          'parent_directory' => "&Uuml;bergeordnetes Verzeichnis",
                          'delete_dir'       => "Ordner l&ouml;schen",
                          'del_dir_rekursiv' => "Ordner rekursiv l&ouml;schen",
                          'rename_dir'       => "Ordner umbenennen",
                          'delete_file'      => "Datei l&ouml;schen",
                          'rename_file'      => "Datei umbenennen",
                          "rename"           => "umbenennen",
                          'fileinfos'        => "Dateiinformationen",
                          'upload_ok'        => "Datei %file% hochgeladen!",
                          'upload_not_ok'    => "Datei nicht hochgeladen",
                          'file_exist'       => 'Datei nicht hochgeladen! Datei existiert schon!',
                          'file_renamed'     => 'Datei %file% umbenannt!',
                          'file_not_renamed' => 'Datei %file% nicht umbenannt!',
                          'new_file_exist'   => 'Dieser Dateiname wird schon verwendet!',
                          'file_created'     => "Datei erstellt!",
                          'file_not_created' => "Datei konnte nicht erstellt werden!",
                          'file_delete_ok'   => "Datei %file% gel&ouml;scht!",
                          'file_delete_bad'  => "Datei %file% nicht gel&ouml;scht!",
                          'dir_creat_ok'     => "Ordner erstellt!",
                          'dir_creat_bad'    => "Konnte ordner nicht erstellen!",
                          'dir_rm_ok'        => "Ordner gel&ouml;scht!",
                          'dir_rm_bad'       => "Konnte ordner nicht l&ouml;schen!",
                          'savefile_ok'      => "Habe neuen Inhalt geschrieben.",
                          'unzip_ok'         => "Archiv entpackt!",
                          'unzip_bad'        => "Konnte Archiv nicht entpacken!",
                          'filesize'         => "Dateigr&ouml;&szlig;e",
                          'fileext'          => "Dateiendung",
                          'last_modified'    => "Letzte &Auml;nderung",
                          "created"          => "Erstellt",
                          'show_file'        => "Datei anzeigen",
                          'auswahl'          => "Datei w&auml;hlen",
                          'js_noauswahl'     => "Sie k??nnen leider keine Datei w??hlen!",
                          'filetype_bad'     => "Die Datei hat keine g??ltige Endung!",
                          "sites"            => "Seiten",
                          'files'            => "Dateien",
                          'directories'      => "Ordner",
                          'or'               => "oder",
                          "filename"         => "Dateiname",
                          'js_copy_dest'     => "Bitte Ziel eingeben",
                          "copy_bad"         => "Die Datei/ Der Ordner konnte nicht kopiert werden.",
                          'copy_ok'          => "Die Datei/ Der Ordner wurde erfolgreich kopiert!",
                          'copy'             => "Kopieren",
                          "too_big"          => "Die Datei ist zu gro??!",
                          'view'             => 'Datei aufrufen',
                          'resize'           => 'Bildgr????e ??ndern',
                          'height'           => 'H??he',
                          'width'            => 'Breite',
                          'original'         => 'Orginal',
                          '_new'             => 'Neu',
                          "collection"       => "Sammlung",
                          "delete_all"       => "Alle Versionen l??schen",
                          "deleteall_confirm"=> "Wollen Sie wirklich alle Versionen l??schen? Das kann nicht r??ckg??ngig gemacht werden.");


foreach ($filemanager_lang as $key => $value) {
    $GLOBALS['lang']['filemanager_' . $key] = $value;
}

