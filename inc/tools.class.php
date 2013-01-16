<?php
/*
	Copyright © 2013 Raphael Barbate (potsky) <potsky@me.com> [http://www.potsky.com]
	This file is part of s2member Secure File Browser.

	s2member Secure File Browser is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License.

	s2member Secure File Browser is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with s2member Secure File Browser.  If not, see <http://www.gnu.org/licenses/>.
*/
if (( realpath (__FILE__) === realpath( $_SERVER["SCRIPT_FILENAME"] ) ) || ( !defined( 'ABSPATH' ) ) ) {
	status_header( 404 );
	exit;
}


class PSK_Tools
{

	/**
	 * return true if a $string starts with $start
	 *
	 * @param       string  $string      the haystack
	 * @param       string  $start       the start needle
	 * @return      boolean
	 */
	public function starts_with($string,$start) {
		return ( substr($string , 0 , strlen($start) ) == $start );
	}


	/**
	 * Remove all trailing [anti]slashes at the end of a directory
	 *
	 * @param       string  $directory   the dirctory to check
	 * @return      boolean
	 */
	public static function sanitize_directory_path($directory)
	{
		while (substr($directory,-1,1)==DIRECTORY_SEPARATOR) $directory = substr($directory,0,-1);
		return $directory;
	}


	/**
	 * Check if the specified directory is in s2member_files_path directory
	 *
	 * @param       string  $directory   the dirctory to check
	 * @return      boolean
	 */
	public static function is_directory_allowed($directory)
	{
		$child  = realpath($directory);
		$parent = realpath(PSK_S2MSFB_S2MEMBER_FILES_FOLDER);
		return self::starts_with($child,$parent);
	}


	/**
	 * Remove recursively a direcory or a file with check if the specified file/dir is in s2member_files_path directory
	 *
	 * @param       string  $filepath   the directory or file to delete
	 * @return      boolean
	 */
	public static function rm_secure_recursive($filepath)
	{
		if ( is_dir($filepath) && !is_link($filepath) ) {

			if ( $dh = opendir($filepath) ) {

				while (($sf = readdir($dh)) !== false) {

					if ($sf == '.' || $sf == '..') {
						continue;
					}

					if ( ! self::rm_secure_recursive( $filepath . DIRECTORY_SEPARATOR . $sf ) ) {
						throw new Exception( $filepath . DIRECTORY_SEPARATOR . $sf . ' could not be deleted.');
					}
				}
				closedir($dh);
			}

			if (!self::is_directory_allowed($filepath))
				throw new Exception( $filepath . DIRECTORY_SEPARATOR . $sf . ' could not be deleted.');

			return rmdir($filepath);
		}

		if (!self::is_directory_allowed($filepath))
			throw new Exception( $filepath . DIRECTORY_SEPARATOR . $sf . ' could not be deleted.');

		return unlink($filepath);
	}


	/**
	 * Return human readable sizes
	 *
	 * @author      Aidan Lister <aidan@php.net>
	 * @version     1.3.0
	 * @link        http://aidanlister.com/2004/04/human-readable-file-sizes/
	 * @param       int     $size        size in bytes
	 * @param       string  $max         maximum unit
	 * @param       string  $system      'si' for SI, 'bi' for binary prefixes
	 * @param       string  $retstring   return string format
	 * @return      string               readable sizes
	 */
	public static function size_readable($size, $max = null, $system = 'si', $retstring = '%01.2f %s')
	{
	    $sys['si']['p'] = array(_x('B','Bytes abbr',PSK_S2MSFB_ID), _x('KB','Kilobytes abbr',PSK_S2MSFB_ID), _x('MB','Megabytes abbr',PSK_S2MSFB_ID), _x('GB','Gigabytes abbr',PSK_S2MSFB_ID), _x('TB','Terabytes abbr',PSK_S2MSFB_ID), _x('PB','Petabytes abbr',PSK_S2MSFB_ID));
	    $sys['si']['s'] = 1000;
	    $sys['bi']['p'] = array(_x('B','Bytes abbr',PSK_S2MSFB_ID), _x('KiB','Kibibytes abbr',PSK_S2MSFB_ID), _x('MiB','Mebibytes abbr',PSK_S2MSFB_ID), _x('GiB','Gibibytes abbr',PSK_S2MSFB_ID), _x('TiB','Tebibytes abbr',PSK_S2MSFB_ID), _x('PiB','Pebibytes abbr',PSK_S2MSFB_ID));
	    $sys['bi']['s'] = 1024;
	    $sys = isset($sys[$system]) ? $sys[$system] : $sys['si'];

	    $depth = count($sys['p']) - 1;
	    if ($max && false !== $d = array_search($max, $sys['p'])) $depth = $d;
	    $i = 0;
	    while ($size >= $sys['s'] && $i < $depth) {
	        $size /= $sys['s'];
	        $i++;
	    }
	    return sprintf($retstring, $size, $sys['p'][$i]);
	}


	/**
	 * Return an escaped value for a html attribute
	 *
	 * @param       string  $str         the value to escape
	 * @return      string               the escaped value
	 */
	function rel_literal($str) {
		//return htmlspecialchars($str,ENT_COMPAT|ENT_HTML401,'UTF-8'|); // Only for PHP >= 5.4
		return htmlspecialchars($str,ENT_COMPAT,'UTF-8');
	}


	/**
	 * Return an utf8 htmlentities value
	 *
	 * @param       string  $str         the value to escape
	 * @return      string               the escaped value
	 */
	function html_entities($str) {
		//return htmlentities($str,ENT_COMPAT|ENT_HTML401,'UTF-8'|); // Only for PHP >= 5.4
		return htmlentities($str,ENT_COMPAT,'UTF-8');
	}

	/**
	 * Return a javascript literal
	 *
	 * @param       string  $str         the value
	 * @return      string               the literalized value
	 */
	function js_literal($str) {
		//return htmlentities('\''.str_replace('\'','\\\'',str_replace('\\','\\\\',$str)).'\'',ENT_COMPAT|ENT_HTML401,'UTF-8'); // Only for PHP >= 5.4
		return htmlentities('\''.str_replace('\'','\\\'',str_replace('\\','\\\\',$str)).'\'',ENT_COMPAT,'UTF-8');
	}


	/*
	 * Display alert
	 * psk_sfb_alert('Error!','File has been deleted','error');
	 * psk_sfb_alert('Info!','File has been deleted','info',4000);
	 * psk_sfb_alert('Success!','File has been deleted','success');
	 * psk_sfb_alert('Warning!','File has been deleted');
	 */
	function get_js_alert($title, $message, $alert='info', $time=5000) {
		$time = (int)$time;
		$ret = '<script>psk_sfb_alert('.self::js_literal($title).', '.self::js_literal($message).', '.self::js_literal($alert).', '.self::js_literal($time).');</script>';
		return $ret;
	}

}


