<?php
/*
Plugin Name: s2member Secure File Browser
Plugin URI: https://www.potsky.com/code/wordpress-plugins/s2member-secure-file-browser/
Description: A plugin for browsing files from the secure-files location of the s2member WordPress Membership plugin. You can display the file browser via the shortcode [s2member_secure_files_browser /].
             Options are :
			 - dirbase        - initial directory
             - hidden         - show hiddent files or not  - default is no "0"  ; "1" to display
             - dirfirst       - show directory above files - default is yes "1" ; "0" to display directories with files
             - names          - replace file name, seperate files by a pipe  - eg : "access-s2member-level0:Free|access-s2member-level1:My Users|access-s2member-level2:Developers"
			 - folderevent    - event to trigger expand/collapse; default is "click" (can be "mouseover", ...)
			 - expandspeed    - default = 500 (ms); use -1 for no animatio
             - expandeasing   - easing function to use on expand - default is "swing" or "linear"
			 - collapsespeed  - default = 500 (ms); use -1 for no animation
             - collapseeasing - easing function to use on collapse - default is "swing" or "linear"
			 - multifolder    - whether or not to limit the browser to one subfolder at a time - default yes "1" ; "0" to display only one open directory at a time
			eg: [s2member_secure_files_browser folderevent="click" expandeasing="linear" expandspeed="200" collapseeasing="swing" collapsespeed="200" multifolder="1" dirbase="/" hidden="1" dirfirst="0" names="access-s2member-level0:General|access-s2member-level1:User files|access-s2member-level2:Developer files|access-s2member-level3:Partner files|access-s2member-level4:Platinium files|access-s2member-level2:Developers|access-s2member-ccap-video:Videos" /]
Version: 0.1
Date: 2012-12-25
Author: potsky
Author URI: http://www.potsky.com/about/

Copyright © 2012 Raphael Barbate (potsky) <potsky@me.com> [http://www.potsky.com]
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

class s2memberFilesBrowser
{
	private static $instance             = 0;
	private $root_directory              = '';
	private $display_hidden_files        = false;
	private $display_directory_first     = true;
	private $display_directory_names     = '';
	private $displayed_directory_names   = array();
	private $directory_s2_ccap           = 'access-s2member-ccap-';
	private $directory_s2_ccap_to_rights = 'access_s2member_ccap_';

	private $directory_s2_level = array(
		'access-s2member-level0',
		'access-s2member-level1',
		'access-s2member-level2',
		'access-s2member-level3',
		'access-s2member-level4',
	);

	private $directory_s2_level_to_rights = array(
		'access-s2member-level0' => 'access_s2member_level0',
		'access-s2member-level1' => 'access_s2member_level1',
		'access-s2member-level2' => 'access_s2member_level2',
		'access-s2member-level3' => 'access_s2member_level3',
		'access-s2member-level4' => 'access_s2member_level4',
	);

	public function __construct()
	{
		if ( is_admin() ) {
			add_action( 'wp_ajax_nopriv_s2member-files-browser', array( &$this, 'ajax_call' ) );
			add_action( 'wp_ajax_s2member-files-browser', array( &$this, 'ajax_call' ) );
		}
		add_action( 'init', array( &$this, 'init' ) );
 		add_action( 'wp_enqueue_scripts', array( &$this, 'init_css' ) );

		$this->root_directory = WP_PLUGIN_DIR.'/s2member-files';
	}


	public function init()
	{
		wp_enqueue_script( 's2member-files-browser', plugin_dir_url( __FILE__ ) . 'jqueryFileTree/jqueryFileTree.js', array( 'jquery' ), false, true );
		wp_localize_script( 's2member-files-browser', 's2memberFilesBrowser', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce('s2member-files-browser-nonce'),
		) );
	}


	public static function get_instance()
	{
		self::$instance++;
		return self::$instance;
	}


	public function init_css()
	{
        wp_register_style( 'prefix-style', plugin_dir_url( __FILE__ ) . 'jqueryFileTree/jqueryFileTree.css' );
        wp_enqueue_style( 'prefix-style' );
	}


	public function ajax_call()
	{
		if (!isset($_POST['nonce']) || !check_ajax_referer('s2member-files-browser-nonce','nonce',false)) die ('Invalid nonce');
		if (!isset($_POST['dir'])) die ('Invalid parameters');


		// Retrieve shortcode parameters and overwrite defaults
		//
		$this->root_directory         .= strpos(@$_POST['dirbase'],'..') ? '' : '/'.@$_POST['dirbase'];
		$this->display_directory_names = @$_POST['names'];
		if (@$_POST['hidden']=='1')		$this->display_hidden_files = true;
		if (@$_POST['dirfirst']=='0')	$this->display_directory_first = false;


		// Retrieve current directory
		//
		$dir_rel      = urldecode($_POST['dir']);
		$dir          = strpos($dir_rel,'..') ? $this->root_directory : $this->root_directory . $dir_rel;


		// Lets go
		//
		if( file_exists($dir) ) {

			$result  = array();
			$resultf = array();
			$resultd = array();

			// Retrieve default s2member level names set in Admin Panel prefs
			//
			$directory_s2_level_friendly = array(
				'access-s2member-level0' => S2MEMBER_LEVEL0_LABEL,
				'access-s2member-level1' => S2MEMBER_LEVEL1_LABEL,
				'access-s2member-level2' => S2MEMBER_LEVEL2_LABEL,
				'access-s2member-level3' => S2MEMBER_LEVEL3_LABEL,
				'access-s2member-level4' => S2MEMBER_LEVEL4_LABEL,
			);


			// Compute the user associative file names array
			//
			$this->displayed_directory_names = array();
			$tmp = explode('|',$this->display_directory_names);
			foreach ($tmp as $keyval) {
				list($key,$val) = explode(':',$keyval);
				$this->displayed_directory_names[$key] = $val;
			}


			// Retrieve default s2member level names set in Admin Panel prefs
			//
			$files = scandir($dir);


			// Browse all dirs and files
			//
			foreach( $files as $file ) {

				// Remove all . .. and hidden files if option is not set
				//
				if (!file_exists($dir . $file)) continue;
				if ($file=='.') continue;
				if ($file=='..') continue;
				if ($file=='.htaccess') continue;
				if (!$this->display_hidden_files && (substr($file,0,1)=='.')) continue;

				// Check if the file is allowed by s2member level
				//
				if (in_array($file,$this->directory_s2_level)) {
					if (current_user_cannot($this->directory_s2_level_to_rights[$file])) continue;
				}

				// Check if the file is allowed by s2member custom capability
				//
				if (substr($file,0,strlen($this->directory_s2_ccap))==$this->directory_s2_ccap) {
					if (current_user_cannot(str_replace($this->directory_s2_ccap,$this->directory_s2_ccap_to_rights,$file))) continue;
				}

				// Set the displayed name acoording to user shortcode parameters and next s2level names
				//
				if (isset($this->displayed_directory_names[$file])) {
					$d = $this->displayed_directory_names[$file];
				}
				else if (isset($directory_s2_level_friendly[$file])) {
					$d = $directory_s2_level_friendly[$file];
				}
				else {
					$d = $file;
				}
				$d = htmlentities($d);


				// Return html
				//
				if(is_dir($dir . $file)) {
					if ($this->display_directory_first) {
						$resultd[$d] = "<li class=\"directory collapsed\"><a href=\"#\" rel=\"" . htmlentities($dir_rel . $file) . "/\">" . $d . "</a></li>";
					}
					else {
						$result[$d] = "<li class=\"directory collapsed\"><a href=\"#\" rel=\"" . htmlentities($dir_rel . $file) . "/\">" . $d . "</a></li>";
					}
				}
				else {
					$ext  = preg_replace('/^.*\./', '', $file);
					$link = s2member_file_download_url(array("file_download" => $dir_rel.$file));

					if ($this->display_directory_first) {
						$resultf[$d] = "<li class=\"file ext_$ext\"><a href=\"#\" rel=\"" . htmlentities($link) . "\">" . $d . "</a></li>";
					}
					else {
						$result[$d] = "<li class=\"file ext_$ext\"><a href=\"#\" rel=\"" . htmlentities($link) . "\">" . $d . "</a></li>";
					}
				}

			}

			// Sort arrays according to displayed file names and not to system real file names
			//
			if ($this->display_directory_first) {
				uksort($resultd, "strnatcasecmp");
				uksort($resultf, "strnatcasecmp");
				$ar = array_merge($resultd,$resultf);
			}
			else {
				uksort($result, "strnatcasecmp");
				$ar = $result;
			}

			echo "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
			foreach ($ar as $html) echo $html;
			echo "</ul>";

		}
		die();
	}


	function shortcode_func($atts) {
		$i  = self::get_instance();
		$rt = '<div id="s2memberFilesBrowser'.$i.'"></div><script type="text/javascript">
			jQuery(document).ready(function(){
			    jQuery("#s2memberFilesBrowser'.$i.'").fileTree({root:"/"';

		foreach ($atts as $param=>$value) {
			$rt.= ','.$param.':"'.str_replace('"','\"',$value).'" ';
		}

		$rt.= '}, function(link) {
			        document.location.href=link;
			    });
			});
			</script>
		';
		return $rt;
	}
}

$s2memberFilesBrowser = new s2memberFilesBrowser();

add_shortcode('s2member_secure_files_browser', array('s2memberFilesBrowser','shortcode_func') );



