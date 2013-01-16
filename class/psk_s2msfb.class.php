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


class PSK_S2MSFB
{
	private static $is_admin                     = false;
	private static $shortcode_instance           = 0;
	private static $directory_s2_level_friendly  = array();
	private static $directory_s2_level           = array(
		PSK_S2MSFB_S2MEMBER_LEVEL0_FOLDER,
		PSK_S2MSFB_S2MEMBER_LEVEL1_FOLDER,
		PSK_S2MSFB_S2MEMBER_LEVEL2_FOLDER,
		PSK_S2MSFB_S2MEMBER_LEVEL3_FOLDER,
		PSK_S2MSFB_S2MEMBER_LEVEL4_FOLDER,
	);
	private static $directory_s2_level_to_rights = array(
		PSK_S2MSFB_S2MEMBER_LEVEL0_FOLDER => PSK_S2MSFB_S2MEMBER_LEVEL0_RIGHTS,
		PSK_S2MSFB_S2MEMBER_LEVEL1_FOLDER => PSK_S2MSFB_S2MEMBER_LEVEL1_RIGHTS,
		PSK_S2MSFB_S2MEMBER_LEVEL2_FOLDER => PSK_S2MSFB_S2MEMBER_LEVEL2_RIGHTS,
		PSK_S2MSFB_S2MEMBER_LEVEL3_FOLDER => PSK_S2MSFB_S2MEMBER_LEVEL3_RIGHTS,
		PSK_S2MSFB_S2MEMBER_LEVEL4_FOLDER => PSK_S2MSFB_S2MEMBER_LEVEL4_RIGHTS,
	);

	// POST values which can be called in several methods
	private static $openrecursive                = false;
	private static $display_hidden_files         = false;
	private static $display_directory_first      = true;
	private static $displayed_directory_names    = array();
	private static $filterfile                   = '';
	private static $filterdir                    = '';

	/**
	* Initialization
	*
	* @return void
	*/
	public static function init()
	{
		// Deal with activation and deactivation
		//
		register_activation_hook(	PSK_S2MSFB_PLUGIN_FILE			, array( __CLASS__ , 'activate' ) );
		register_deactivation_hook( PSK_S2MSFB_PLUGIN_FILE			, array( __CLASS__ , 'deactive' ) );

		// Create filters
		//
		//add_filter( 'cron_schedules'                                , array( __CLASS__ , 'set_cron_interval' ) );

		// Create and/or setup actions
		//
		add_action( PSK_S2MSFB_ID.'_enable_wp_cron_hook'			, array( __CLASS__ , 'enable_cron'       ) ); // Create a hook to enable cron
		add_action( PSK_S2MSFB_ID.'_disable_wp_cron_hook'			, array( __CLASS__ , 'disable_cron'      ) ); // Create a hook to disable cron
		add_action( PSK_S2MSFB_ID . '_cron_db_clean_download_hook'  , array( __CLASS__ , 'db_clean_download' ) ); // Create a hook to delete old logs

		add_action('init'									        , array( __CLASS__ , 'plugin_init'       ) );
		add_action('plugins_loaded'									, array( __CLASS__ , 'plugins_loaded'    ) );
 		add_action('wp_enqueue_scripts'    							, array( __CLASS__ , 'init_assets'       ) );
		add_action('ws_plugin__s2member_during_file_download_access', array( __CLASS__ , 'notify_download'   ) );

		add_action('wp_ajax_'.PSK_S2MSFB_ID.'_get_dir'        		, array( __CLASS__ , 'ajax_get_directory') ); // theme logged in
		add_action('wp_ajax_nopriv_'.PSK_S2MSFB_ID.'_get_dir' 		, array( __CLASS__ , 'ajax_get_directory') ); // theme not logged in
		add_action('wp_ajax_admin_'.PSK_S2MSFB_ID.'_get_dir'  		, array( __CLASS__ , 'ajax_admin_get_directory' ) );  // dashboard
		add_action('wp_ajax_admin_'.PSK_S2MSFB_ID.'_df'       		, array( __CLASS__ , 'ajax_admin_delete_file'   ) );  // dashboard
		add_action('wp_ajax_admin_'.PSK_S2MSFB_ID.'_rf'       		, array( __CLASS__ , 'ajax_admin_rename_file'   ) );  // dashboard

		// Create shortcodes
		//
		$i = 0;
		while (true) {
			if (defined( 'PSK_S2MSFB_SHORTCODE_NAME_'.$i )) {
				add_shortcode(
					constant ( 'PSK_S2MSFB_SHORTCODE_NAME_'.$i )	, array( __CLASS__ , 'shortcode_s2member_secure_files_browser' )
				);
				$i++;
			} else {
				break;
			}
		}
	}


	/**
	 * WP init
	 *
	 * @wp_action 		init
	 * @return          void
	 */
	public static function plugin_init()
	{
		// Retrieve default s2member level names set in Admin Panel prefs
		self::$directory_s2_level_friendly = array(
			'access-s2member-level0' => S2MEMBER_LEVEL0_LABEL,
			'access-s2member-level1' => S2MEMBER_LEVEL1_LABEL,
			'access-s2member-level2' => S2MEMBER_LEVEL2_LABEL,
			'access-s2member-level3' => S2MEMBER_LEVEL3_LABEL,
			'access-s2member-level4' => S2MEMBER_LEVEL4_LABEL,
		);
	}


	/**
	 * WP plugins_loaded
	 *
	 * @wp_action 		plugins_loaded
	 * @return          void
	 */
	public static function plugins_loaded()
	{
		// Set up language
		load_plugin_textdomain(	PSK_S2MSFB_ID , false , dirname( plugin_basename( PSK_S2MSFB_PLUGIN_FILE ) ) . '/languages/' );

		// Load activate method because plugin do not launch activate on upgrade and we have things to do
		self::activate();
	}


	/**
	 * What to do when plugin is activated
	 * This method is also called when plugins_loaded action so we have to manage light actions here
	 * (manage a version number for all database structure changes, check cron before loading it, etc...)
	 *
	 * @wp_action 		plugins_loaded
	 * @wp_action 		register_activation_hook
	 * @return void
	 */
	static public function activate()
	{
	    do_action( PSK_S2MSFB_ID . '_enable_wp_cron_hook' ); // Go enable cron
		self::db_check_install();
	}


	/**
	 * What to do when plugin is deactivated
	 *
	 * @wp_action 		register_deactivation_hook
	 * @return void
	 */
	static public function deactive()
	{
	    do_action( PSK_S2MSFB_ID . '_disable_wp_cron_hook' ); // Go disable cron
	}



	/**
 	 * Own cron interval
	 * for debug purpose...
	 * @return void
	 */
	static public function set_cron_interval($schedules)
	{
		$schedules['every1mn'] = array(
			'interval' => 60,
			'display'  => 'Every minute'
		);
		return $schedules;
	}


	/**
 	 * Enable crontab
	 *
	 * @wp_action 		PSK_S2MSFB_ID.'_enable_wp_cron'
	 * @return void
	 */
	static public function enable_cron()
	{
		if ( ! wp_next_scheduled( PSK_S2MSFB_ID . '_cron_db_clean_download_hook' ) ) {
			wp_schedule_event( time(), 'hourly', PSK_S2MSFB_ID . '_cron_db_clean_download_hook');
		}
	}


	/**
 	 * Enable crontab
	 *
	 * @wp_action 		PSK_S2MSFB_ID.'_disable_wp_cron'
	 * @return void
	 */
	static public function disable_cron()
	{
		$timestamp = wp_next_scheduled(  PSK_S2MSFB_ID . '_cron_db_clean_download_hook' );
		wp_unschedule_event( $timestamp, PSK_S2MSFB_ID . '_cron_db_clean_download_hook' );
	}


	/*
	 * Load javascript and css for Public and Admin part
	 * Called from the shortcode only !
	 *
	 * @return          void
	 */
	public static function init_assets()
	{
		wp_deregister_script('jquery');
		wp_register_script('jquery', PSK_S2MSFB_JS_URL . 'jquery-1.8.3.min.js', false);
		wp_enqueue_script('jquery');
	}


	/*
	 * WP wp_enqueue_scripts
	 * Load javascript and css for Public and Admin part
	 *
	 * @wp_action 		wp_enqueue_scripts
	 * @return          void
	 */
	public static function init_shortcode_assets()
	{
		wp_enqueue_script(PSK_S2MSFB_ID, PSK_S2MSFB_JS_URL . 'jqueryFileTree.js', array('jquery'), false, true);
        wp_register_style(PSK_S2MSFB_ID, PSK_S2MSFB_CSS_URL . 'jqueryFileTree.css' );
        wp_enqueue_style(PSK_S2MSFB_ID);

		// Set localize javascript
        $prefix = (is_admin()) ? 'admin_' : '';
		wp_localize_script(PSK_S2MSFB_ID, __CLASS__, array(
			'ajaxurl'         => admin_url( 'admin-ajax.php' ),
			'nonce'           => wp_create_nonce(PSK_S2MSFB_ID.'-nonce'),
			'action_get_dir'  => $prefix.PSK_S2MSFB_ID.'_get_dir',
			'action_df'       => $prefix.PSK_S2MSFB_ID.'_df',
			'action_rf'       => $prefix.PSK_S2MSFB_ID.'_rf',
		));

	}


	/**
	 * Set the private value to true if the call is an admin call and false if the call comes from the public
	 * We do this because is_admin() in 'admin_ajax.php' is always true so we set the value $is_admin here to false
	 *
	 * @param 			boolean		$bool 	is admin or not
 	 * @return          void
	 */
	public static function set_is_admin($bool)
	{
		self::$is_admin = ($bool===true) ? true : false;
	}


	/**
	 * Ajax call - Admin wrapper
	 *
	 * @return          die
	 */
	public static function ajax_admin_delete_file()
	{
		if (PSK_S2MSFBAdmin::load_admin_class_file( 'manager_browser' ))
			PSK_S2MSFBAdminManager::ajax_admin_delete_file();
		die('action not found');
	}


	/**
	 * Ajax call - Admin wrapper
	 *
	 * @return          die
	 */
	public static function ajax_admin_rename_file()
	{
		if (PSK_S2MSFBAdmin::load_admin_class_file( 'manager_browser' ))
			PSK_S2MSFBAdminManager::ajax_admin_rename_file();
		die('action not found');
	}



	/**
	 * Ajax call - Returns a directory as a html structure
	 * Admin_ajax.php is always true so we set the value $is_admin here to true
	 * This method is the action for the admin part
	 *
	 * @return          die
	 */
	public static function ajax_admin_get_directory()
	{
		if (PSK_S2MSFBAdmin::load_admin_class_file( 'manager_browser' ))
			PSK_S2MSFBAdminManager::ajax_admin_get_directory();
		die('action not found');
	}


	/**
	 * Ajax call - Returns a directory as a html structure
	 * is_admin() in 'admin_ajax.php' is always true so we set the value $is_admin here to false
	 * This method is the action for the public part
	 *
	 * @return          die
	 */
	public static function ajax_get_directory()
	{
		self::set_is_admin(false);
		self::ajax_do_get_directory();
		die();
	}


	/**
	 * Returns a directory as a html structure
	 *
	 * @return          die
	 */
	public static function ajax_do_get_directory()
	{
		if (!isset($_POST['nonce']) || !check_ajax_referer(PSK_S2MSFB_ID.'-nonce','nonce',false)) die ('Invalid nonce');
		if (!isset($_POST['dir'])) die ('invalid parameters');

		// Retrieve shortcode parameters and overwrite defaults
		//
		$dirbase = PSK_Tools::sanitize_directory_path(stripslashes(rawurldecode(@$_POST['dirbase'])));
		$current = PSK_S2MSFB_S2MEMBER_FILES_FOLDER . $dirbase;
		if (!PSK_Tools::is_directory_allowed($current)) {
			$dirbase = '';
			$current = PSK_S2MSFB_S2MEMBER_FILES_FOLDER;
		}

		self::$display_hidden_files      = (@$_POST['hidden']=='1') ? true : false;
		self::$display_directory_first   = (@$_POST['dirfirst']=='0') ? false : true;
		self::$openrecursive             = (@$_POST['openrecursive']=='1') ? true : false;
		self::$filterfile                = stripslashes(rawurldecode(@$_POST['filterfile']));
		self::$filterdir                 = stripslashes(rawurldecode(@$_POST['filterdir']));
		$display_directory_names         = stripslashes(@$_POST['names']);
		self::$displayed_directory_names = array();
		$tmp = explode('|',$display_directory_names);
		foreach ($tmp as $keyval) {
			@list($key,$val) = @explode(':',$keyval,2);
			self::$displayed_directory_names[rawurldecode($key)] = rawurldecode($val);
		}

		// Retrieve current directory
		//
		$dir_rel = stripslashes(rawurldecode($_POST['dir']));
		echo self::recursive_directory( $current , $dirbase , $dir_rel );
		die();
	}


	/**
	 * Returns a directory as a html structure (recursive method)
	 *
	 * @param       string  $current     the root directory
	 * @param       string  $dirbase     the shortcode dirbase directory append to $current
	 * @param       string  $dir_rel     the inloop directory append to $dirbase
	 * @return      string               directory as a html structure
	 */
	private static function recursive_directory( $current , $dirbase , $dir_rel )
	{
		$dir = $current . $dir_rel;

		if( file_exists($dir) ) {
			$result  = array();
			$resultf = array();
			$resultd = array();


			// Check if this directory is below PSK_S2MSFB_S2MEMBER_FILES_FOLDER
			//
			if (!PSK_Tools::is_directory_allowed($dir)) return __('Permission denied',PSK_S2MSFB_ID);


			// Retrieve default s2member level names set in Admin Panel prefs
			//
			$files = scandir($dir);


			// Browse all dirs and files
			//
			foreach( $files as $file ) {

				$filepath        = $dir.$file;
				$filepathrel     = $dir_rel.$file;
				$filepathrelbase = $dirbase.$filepathrel;

				// Remove all . .. and hidden files if option is not set
				if (!file_exists($filepath)) continue;
				if ($file=='.') continue;
				if ($file=='..') continue;
				if ($file=='.htaccess') continue;
				if (!self::$display_hidden_files && (substr($file,0,1)=='.')) continue;


				// Check for filter
				//
				$isdir = is_dir($filepath);
				if ($isdir) {
					if (self::$filterdir!='') {
						 if (!preg_match(self::$filterdir,$file)) continue;
					}
				}
				else {
					if (self::$filterfile!='') {
						 if (!preg_match(self::$filterfile,$file)) continue;
					}
				}


				// Check if the file is allowed by s2member level
				//
				if ( in_array( $file,self::$directory_s2_level ) ) {
					if ( current_user_cannot(self::$directory_s2_level_to_rights[$file]) ) continue;
				}

				// Check if the file is allowed by s2member custom capability
				//
				if ( PSK_Tools::starts_with( $file , PSK_S2MSFB_S2MEMBER_CCAP_FOLDER ) ) {
					if ( current_user_cannot( str_replace( PSK_S2MSFB_S2MEMBER_CCAP_FOLDER, PSK_S2MSFB_S2MEMBER_CCAP_RIGHTS , $file)) ) continue;
				}

				// Set the displayed name acoording to user shortcode parameters and next s2level names
				//
				if (isset(self::$displayed_directory_names[$file])) {
					if ( self::$is_admin === true )
						$d = PSK_Tools::html_entities($file).' <span class="fn">('.PSK_Tools::html_entities(self::$displayed_directory_names[$file]).')</span>';
					else
						$d = PSK_Tools::html_entities(self::$displayed_directory_names[$file]);
				}
				else if (isset(self::$directory_s2_level_friendly[$file])) {
					if ( self::$is_admin === true )
						$d = PSK_Tools::html_entities($file).' <span class="fn">('.PSK_Tools::html_entities(self::$directory_s2_level_friendly[$file]).')</span>';
					else
						$d = PSK_Tools::html_entities(self::$directory_s2_level_friendly[$file]);
				}
				else {
					$d = htmlentities($file,ENT_COMPAT,'UTF-8');
				}


				// Return html
				//
				if($isdir) {
					$li = '<li class="directory ';
					$li.= (self::$openrecursive) ? "expanded" : "collapsed";
					$li.= '" data-s="-1"><div class="jftctn">';
					$li.= '<a href="#" class="link" rel="' . PSK_Tools::rel_literal($filepathrel) . '">' . $d . '</a>';
					$li.= '<span class="size d">&nbsp;</span>';
					$li.= ( self::$is_admin === true ) ? '<a href="javascript:psk_sfb_rename_dir('. PSK_Tools::js_literal($filepathrelbase) .')" class="d"><i class="icon-pencil"></i></a>&nbsp;&nbsp;' : '';
					$li.= ( self::$is_admin === true ) ? '<a href="javascript:psk_sfb_remove_dir('. PSK_Tools::js_literal($filepathrelbase) .')" class="d"><i class="icon-remove-sign"></i></a>&nbsp;&nbsp;' : '';
					$li.= '</div>';
					$li.= '<div style="clear:both"></div>';
					$li.= (self::$openrecursive) ? self::recursive_directory( $current , $dirbase, $filepathrel. DIRECTORY_SEPARATOR ) : '';
					$li.= '</li>';

					if (self::$display_directory_first) $resultd[$d] = $li; else $result[$d] = $li;
				}

				else {
					$ext   = PSK_Tools::rel_literal( preg_replace('/^.*\./', '', $file) );
					$link  = PSK_Tools::rel_literal( s2member_file_download_url( array("file_download" => $filepathrelbase ) ) );
					$size  = filesize( $filepath );
					$hsize = PSK_Tools::size_readable( $size );
					$size  = PSK_Tools::rel_literal( $size );

					$li = '<li class="file ext_'.$ext.'" data-s="'.$size.'"><div class="jftctn"><a href="#" class="link" rel="' . $link . '">' . $d . '</a>';
					$li.= '<span class="size d" title="'.$size.' '._x('B','Bytes abbr',PSK_S2MSFB_ID).'">'.$hsize.'</span>';
					$li.= (self::$is_admin) ? '<a href="javascript:psk_sfb_rename_file('. PSK_Tools::js_literal($filepathrelbase) .')" class="d"><i class="icon-pencil"></i></a>&nbsp;&nbsp;' : '';
					$li.= (self::$is_admin) ? '<a href="javascript:psk_sfb_remove_file('. PSK_Tools::js_literal($filepathrelbase) .')" class="d"><i class="icon-remove"></i></a>&nbsp;&nbsp;' : '';
					$li.= '<div style="clear:both"></div></div></li>';

					if (self::$display_directory_first) $resultf[$d] = $li; else $result[$d] = $li;
				}

			}

			// Sort arrays according to displayed file names and not to system real file names
			//
			if (self::$display_directory_first) {
				uksort($resultd, "strnatcasecmp");
				uksort($resultf, "strnatcasecmp");
				$ar = array_merge($resultd,$resultf);
			}
			else {
				uksort($result, "strnatcasecmp");
				$ar = $result;
			}

			$return = "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
			foreach ($ar as $html) $return.=$html;
			$return.= '</ul>';
		}
		return $return;
	}


	/**
	 * Returns a shortcode
	 *
	 * @param       array   $atts        the arguments from the editor
	 * @return      string               the shortcode html code
	 */
	public static function shortcode_s2member_secure_files_browser($atts)
	{
		self::init_shortcode_assets();

		$i = self::$shortcode_instance;
		self::$shortcode_instance++;

		$rt = '
		<div id="'.PSK_S2MSFB_ID.$i.'" class="psk_jfiletree"></div>
		<script type="text/javascript">
		jQuery(document).ready(function(){jQuery("#'.PSK_S2MSFB_ID.$i.'").fileTree({
			root:"'.DIRECTORY_SEPARATOR.'",
			loadmessage:"'.esc_attr__("Please wait while loading...",PSK_S2MSFB_ID).'"';

		if (is_array($atts)) {
			foreach ($atts as $param=>$value) {
				$rt.= ','.$param.':"'.str_replace('"','\"',$value).'" ';
			}
		}

		$rt.= '}, function(link) {document.location.href=link;});});
		</script>';

		if (is_admin()) {
			$rt.='<div id="pskModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="pskModalLabel" aria-hidden="true">
			  <div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button><h3 id="pskModalLabel"></h3></div>
			  <div class="modal-body" id="pskModalBody"></div>
			  <div class="modal-footer"><button class="btn" data-dismiss="modal" aria-hidden="true" id="pskModalCancel">Cancel</button><button class="btn btn-primary" id="pskModalSave"></button></div>
			</div>';
		}

		return $rt;
	}


	/**
	 * This method is called when a s2member file is downloaded
	 *
	 * @param       array   $vars        the s2member context
	 * @return      void
	 */
	public static function notify_download( $vars = array() )
	{
		global $wpdb;

		if (isset($_GET["s2member_file_download"])) {

			$file    = stripslashes($_GET["s2member_file_download"]);
			$user_id = $vars["user_id"];
			$user    = new WP_User($user_id);
			$ip      = $_SERVER['REMOTE_ADDR'];

			// Insert record in table
			//
			self::db_install_download();
			$tablename = $wpdb->prefix . PSK_S2MSFB_DB_DOWNLOAD_TABLE_NAME;
			$newdata   = array(
				'userid'    => $user_id,
				'useremail' => $user->user_email,
				'ip'        => $ip,
				'filepath'  => $file,
			);
			$wpdb->insert( $tablename , $newdata );


			// Send email if necessary
			//
			$settings = get_option( PSK_S2MSFB_OPT_SETTINGS_NOTIFY );
			if ($settings['emailnotify']=='1') {
				$emailfrom   = ($settings['emailfrom']=='') ? get_option('admin_email') : $settings['emailfrom'];
				$emailto     = ($settings['emailto']=='') ? get_option('admin_email') : $settings['emailto'];
				$subject     = ($settings['subject']=='') ? PSK_S2MSFB_DEFAULT_EMAIL_DOWNLOAD_SUBJECT : $settings['subject'];

				$subject = str_replace('%blogname%',get_bloginfo('name'),$subject);
				$subject = '=?UTF-8?B?'.base64_encode($subject).'?=';

				$dt   = date_i18n( sprintf( '%1$s - %2$s', get_option('date_format'), get_option('time_format') ) );

				$msg = __( 'A file has been downloaded',PSK_S2MSFB_ID);
				$msg.= '<table>';
		    	$msg.= '<tr><th align="right">' . __( 'Download Time'     , PSK_S2MSFB_ID ) . ' : </th><td>' . $dt . '</td></tr>';
		    	$msg.= '<tr><th align="right">' . __( 'File downloaded'   , PSK_S2MSFB_ID ) . ' : </th><td>' . $file . '</td></tr>';
		    	$msg.= '<tr><th align="right">' . __( 'User ID'           , PSK_S2MSFB_ID ) . ' : </th><td>' . $user->ID . '</td></tr>';
				$msg.= '<tr><th align="right">' . __( 'User Login'        , PSK_S2MSFB_ID ) . ' : </th><td>' . $user->user_login . '</td></tr>';
				$msg.= '<tr><th align="right">' . __( 'User Email'        , PSK_S2MSFB_ID ) . ' : </th><td>' . $user->user_email . '</td></tr>';
				$msg.= '<tr><th align="right">' . __( 'User Nice name'    , PSK_S2MSFB_ID ) . ' : </th><td>' . $user->user_nicename . '</td></tr>';
				$msg.= '<tr><th align="right">' . __( 'User Display name' , PSK_S2MSFB_ID ) . ' : </th><td>' . $user->display_name . '</td></tr>';
				$msg.= '<tr><th align="right">' . __( 'User IP'           , PSK_S2MSFB_ID ) . ' : </th><td>' . $ip . '</td></tr>';
				$msg.= '</table>';

				$headers = 'From: '.$emailfrom.' <'.$emailfrom.'>' . "\r\n";
				$headers.= 'Sender: '.$emailfrom.' <'.$emailfrom.'>' . "\r\n";
				$headers.= "Content-type: text/html; charset=UTF-8;"."\r\n";

				$tos = explode(',',$emailto);
				foreach ($tos as $to) {
			        wp_mail($to,$subject,$msg,$headers);
				}
			}
		}
	}


	/**
	 * This method install/update the DB Table for downloaded stats
	 *
	 * @return      void
	 */
	public static function db_install_download()
	{
		global $wpdb;
		//self::db_uninstall_download();

		$installed_version = get_option($wpdb->prefix . PSK_S2MSFB_DB_DOWNLOAD_TABLE_VERSION_OPT);
		if ($installed_version != PSK_S2MSFB_DB_DOWNLOAD_TABLE_VERSION) {

			$tablename = $wpdb->prefix . PSK_S2MSFB_DB_DOWNLOAD_TABLE_NAME;

			$sql = "CREATE TABLE $tablename (
				id INT(11) NOT NULL AUTO_INCREMENT,
				created TIMESTAMP NOT NULL,
				userid BIGINT(20) NOT NULL,
				useremail VARCHAR(100) NOT NULL,
				ip VARCHAR(100) NOT NULL,
				filepath VARCHAR(4000) NOT NULL,
				PRIMARY KEY  (id)
			) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";

			require( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta($sql);

			update_option($wpdb->prefix . PSK_S2MSFB_DB_DOWNLOAD_TABLE_VERSION_OPT, PSK_S2MSFB_DB_DOWNLOAD_TABLE_VERSION );
		}
	}


	/**
	 * This method is called by cron and will delete all records older than retention setting and will keep only maxcount setting records
	 *
	 * @return      void
	 */
	public static function db_clean_download()
	{
		global $wpdb;

		$settings   = get_option( PSK_S2MSFB_OPT_SETTINGS_GENERAL );
		$maxcount   = (int)$settings['maxcount'];
		$retention  = (int)$settings['retention'];
		$tablename  = $wpdb->prefix . PSK_S2MSFB_DB_DOWNLOAD_TABLE_NAME;

		if ($maxcount>0) {
			$sql = "SELECT COUNT(*) FROM $tablename";
			$count = $wpdb->get_col($sql);
			$count = (int)$count[0];
			if ( $count > $maxcount ) {
				$delete = $count - $maxcount;
				$sql = "DELETE FROM $tablename ORDER BY created ASC LIMIT $delete";
				$count = $wpdb->query($sql);
			}
		}

		if ($retention>0) {
			$sql = "DELETE FROM $tablename WHERE created < DATE_SUB( NOW(), INTERVAL $retention DAY )";
			$count = $wpdb->query($sql);
		}

	}


	/**
	 * This method uninstall the DB Table for downloaded stats
	 *
	 * @return      void
	 */
	public static function db_uninstall_download()
	{
		global $wpdb;

		delete_option($wpdb->prefix . PSK_S2MSFB_DB_DOWNLOAD_TABLE_VERSION_OPT);

		global $wpdb;
		$tablename = $wpdb->prefix . PSK_S2MSFB_DB_DOWNLOAD_TABLE_NAME;
		$wpdb->query('DROP TABLE IF EXISTS ' . $tablename);
	}


	/**
	 * This method calls all database installation methods
	 *
	 * @return      void
	 */
	public static function db_check_install()
	{
		self::db_install_download();
	}


	/**
	 * This method calls all database uninstallation methods
	 *
	 * @return      void
	 */
	public static function db_uninstall()
	{
		self::db_uninstall_download();
	}


}

PSK_S2MSFB::init();

