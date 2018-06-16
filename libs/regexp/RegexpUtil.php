<?php defined("IN_GOMA") OR die();
/**
 * this class provides some methods to check validity of formats.
 *
 * @package     goma framework
 * @link        http://goma-cms.org
 * @license:    LGPL http://www.gnu.org/copyleft/lesser.html see 'license.txt'
 * @author      Goma-Team
 * @version     1.0
 *
 * last modified: 01.08.2015
 */

class RegexpUtil {
    const EMAIL_REGEXP = '/^([a-zA-Z0-9\-\._]+)@([a-zA-Z0-9_\-\.]+)\.([a-zA-Z0-9]{2,9})$/';
    const NUMBER_REGEXP = '/^\-?[0-9\.]+$/';
    const INT_REGEXP = '/^\-?[0-9]+$/';

    /**
     * returns if variable is a number. returns false for float values.
     *
     * @param string $string
     * @return bool
     */
    public static function isNumber($string) {
        return is_int($string) ||
            ((is_string($string) || is_numeric($string)) && preg_match(self::INT_REGEXP, (string) $string));
    }

    /**
     * returns if variable is a double. returns also true for integer values.
     *
     * @param string $string
     * @return bool
     */
    public static function isDouble($string) {
        return is_numeric($string) || (is_string($string) && preg_match(self::NUMBER_REGEXP, $string));
    }

    /**
     * returns if email is valid.
     *
     * @param string $email
     * @return bool
     */
    public static function isEmail($email) {
        return is_string($email) && preg_match(self::EMAIL_REGEXP, $email);
    }

    /**
     * returns if phone-number is valid.
     *
     * @param string $phone
     * @return bool
     */
    public static function isPhoneNumber($phone) {
        return is_string($phone) && preg_match('/^\+?[0-9\s]+$/', $phone);
    }

    /**
     * is website.
     *
     * @param string $website
     * @return bool
     */
    public static function isWebsite($website) {
        return is_string($website) && preg_match('/^(http\:\/\/|https\:\/\/)?([a-z0-9][a-z0-9\-]*\.)+[a-z0-9][a-z0-9\-]*/i', $website);
    }


    /**
     * checks of the file-extension
     *
     * @param string $filename
     * @param string $ext
     * @return bool
     */
    public static function checkFileExt($filename, $ext) {
        return is_string($filename) && (strtolower(substr($filename, 0 - strlen($ext) - 1)) == "." . $ext);
    }
}
