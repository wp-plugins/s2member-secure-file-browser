<?php
/*
Plugin Name: s2member Secure File Browser
Plugin URI: http://www.potsky.com/code/wordpress-plugins/s2member-secure-file-browser/
Description:	A plugin for browsing files from the secure-files location of the s2member WordPress Membership plugin.  
				You can display the file browser via the shortcode [s2member_secure_files_browser /].  
				You can manage files and get statistics in the Dashboard > s2Member > Secure File Browser  
Version: 0.3.1
Date: 2013-01-04
Author: Potsky
Author URI: http://www.potsky.com/about/
Licence:
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

//--> Wordpress 3.3 is required because we load javascripts and css from the shortcode directly


if (( realpath (__FILE__) === realpath( $_SERVER["SCRIPT_FILENAME"] ) )
	||
	( !defined( 'ABSPATH' ) )
) {
	status_header( 404 );
	exit;
}

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if ( is_plugin_active( 's2member/s2member.php' ) ) {

	define( 'PSK_S2MSFB_PLUGIN_FILE' , __FILE__ );
	require( 'inc/define.php' );
	require( PSK_S2MSFB_INCLUDES_FOLDER	. 'tools.class.php' );

	require( PSK_S2MSFB_CLASSES_FOLDER	. 'psk_s2msfb.class.php' );

	if (is_admin()) {
		require( PSK_S2MSFB_CLASSES_FOLDER		. 'psk_s2msfb.admin.class.php' );
	}

}
