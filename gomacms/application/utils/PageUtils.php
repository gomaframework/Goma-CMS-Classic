<?php defined("IN_GOMA") OR die();
/**
 * Utils for Pages.
 *
 * @package		Goma\Utilites
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class PageUtils {
    public static function cleanPath($path) {
        $path = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', umlautMap($path));
        $path = trim($path);
        $path = strtolower($path);

        $path = str_replace(" ",  "-", $path);
        // normal chars
        $path = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $path);
        $path = str_replace('--', '-', $path);

        return $path;
    }
}
