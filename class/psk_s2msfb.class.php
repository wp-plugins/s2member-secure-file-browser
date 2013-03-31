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
if ( ( realpath( __FILE__ ) === realpath( $_SERVER["SCRIPT_FILENAME"] ) ) || ( ! defined( 'ABSPATH' ) ) ) {
	status_header( 404 );
	exit;
}


/**
 * Class PSK_S2MSFB
 */
class PSK_S2MSFB {
	private static $is_admin = false;
	private static $shortcode_instance = 0;
	private static $directory_s2_level_friendly = array();
	private static $directory_s2_level = array(
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
	private static $openrecursive = false;
	private static $display_hidden_files = false;
	private static $display_directory_first = true;
	private static $displayed_directory_names = array();
	private static $filterfile = '';
	private static $filterdir = '';
	private static $display_all_levels = '';
	private static $displaysize = true;
	private static $displaydownloaded = 0;
	private static $search = 0;
	private static $searchdisplay = 0;
	private static $dirzip = false;
	private static $cutdirnames = 0;
	private static $cutfilenames = 0;
	private static $previewext = array();
	private static $previewext_available = array( 'mp3' );


	/**
	 * Initialization
	 *
	 * @return void
	 */
	public static function init() {
		// Deal with activation and deactivation
		//
		register_activation_hook( PSK_S2MSFB_PLUGIN_FILE, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( PSK_S2MSFB_PLUGIN_FILE, array( __CLASS__, 'deactive' ) );

		// Create filters
		//
		//add_filter( 'cron_schedules'                                , array( __CLASS__ , 'set_cron_interval' ) );

		// Create and/or setup actions
		//
		add_action( PSK_S2MSFB_ID . '_enable_wp_cron_hook', array( __CLASS__, 'enable_cron' ) ); // Create a hook to enable cron
		add_action( PSK_S2MSFB_ID . '_disable_wp_cron_hook', array( __CLASS__, 'disable_cron' ) ); // Create a hook to disable cron
		add_action( PSK_S2MSFB_ID . '_cron_db_clean_download_hook', array( __CLASS__, 'db_clean_download' ) ); // Create a hook to delete old logs
		add_action( PSK_S2MSFB_ID . '_cron_db_clean_files_hook', array( __CLASS__, 'db_clean_files' ) ); // Create a hook to delete old logs
		add_action( PSK_S2MSFB_ID . '_cron_report', array( __CLASS__, 'notify_report' ) ); // Create a hook to send a report by email

		add_action( 'init', array( __CLASS__, 'plugin_init' ) );
		add_action( 'plugins_loaded', array( __CLASS__, 'plugins_loaded' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'init_assets' ) );
		add_action( 'ws_plugin__s2member_during_file_download_access', array( __CLASS__, 'notify_download' ) );

		add_action( 'wp_ajax_' . PSK_S2MSFB_ID . '_get_dir', array( __CLASS__, 'ajax_get_directory' ) ); // theme logged in
		add_action( 'wp_ajax_nopriv_' . PSK_S2MSFB_ID . '_get_dir', array( __CLASS__, 'ajax_get_directory' ) ); // theme not logged in
		add_action( 'wp_ajax_admin_' . PSK_S2MSFB_ID . '_get_dir', array( __CLASS__, 'ajax_admin_get_directory' ) ); // dashboard
		add_action( 'wp_ajax_admin_' . PSK_S2MSFB_ID . '_df', array( __CLASS__, 'ajax_admin_delete_file' ) ); // dashboard
		add_action( 'wp_ajax_admin_' . PSK_S2MSFB_ID . '_rf', array( __CLASS__, 'ajax_admin_rename_file' ) ); // dashboard

		add_action( 'widgets_init', create_function( '', 'register_widget( "' . PSK_S2MSFB_WIDGET_DOWNLOAD_ID . '" );' ) );
		add_action( 'widgets_init', create_function( '', 'register_widget( "' . PSK_S2MSFB_WIDGET_FILES_ID . '" );' ) );

		// Create shortcodes
		//
		$i = 0;
		while ( true ) {
			if ( defined( 'PSK_S2MSFB_SHORTCODE_NAME_' . $i ) ) {
				add_shortcode(
					constant( 'PSK_S2MSFB_SHORTCODE_NAME_' . $i ), array( __CLASS__, 'shortcode_s2member_secure_files_browser' )
				);
				$i ++;
			} else {
				break;
			}
		}
	}


	/**
	 * WP init
	 *
	 * @wp_action    init
	 * @return          void
	 */
	public static function plugin_init() {
		// Retrieve default s2member level names set in Admin Panel prefs
		/** @noinspection PhpUndefinedConstantInspection */
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
	 * @wp_action    plugins_loaded
	 * @return          void
	 */
	public static function plugins_loaded() {
		// Set up language
		load_plugin_textdomain( PSK_S2MSFB_ID, false, dirname( plugin_basename( PSK_S2MSFB_PLUGIN_FILE ) ) . '/languages/' );

		// Load activate method because plugin do not launch activate on upgrade and we have things to do
		self::activate();
	}


	/**
	 * What to do when plugin is activated
	 * This method is also called when plugins_loaded action so we have to manage light actions here
	 * (manage a version number for all database structure changes, check cron before loading it, etc...)
	 *
	 * @wp_action    plugins_loaded
	 * @wp_action    register_activation_hook
	 * @return void
	 */
	static public function activate() {
		do_action( PSK_S2MSFB_ID . '_enable_wp_cron_hook' ); // Go enable cron
		self::db_check_install();
	}


	/**
	 * What to do when plugin is deactivated
	 *
	 * @wp_action    register_deactivation_hook
	 * @return void
	 */
	static public function deactive() {
		do_action( PSK_S2MSFB_ID . '_disable_wp_cron_hook' ); // Go disable cron
	}


	/**
	 * Own cron interval
	 * for debug purpose...
	 *
	 * @param $schedules
	 *
	 * @return array
	 */
	static public function set_cron_interval( $schedules ) {
		$schedules['every1mn'] = array(
			'interval' => 60,
			'display'  => 'Every minute'
		);
		return $schedules;
	}


	/**
	 * Enable crontab
	 *
	 * @wp_action    PSK_S2MSFB_ID.'_enable_wp_cron'
	 * @return void
	 */
	static public function enable_cron() {
		// Report : send email
		$settings = get_option( PSK_S2MSFB_OPT_SETTINGS_NOTIFY );
		if ( @$settings['reportfrequency'] != '' ) {
			if ( ! wp_next_scheduled( PSK_S2MSFB_ID . '_cron_report' ) ) {
				$report_hour = (int) @$settings['reporthour'];
				switch ( $settings['reportfrequency'] ) {
					case 'm':
						$when = mktime( $report_hour, 0, 0, date( "m" ) + 1, 1 );
						break;
					case 'w':
						$when = strtotime( 'next monday ' . $report_hour . ' hour' );
						break;
					default:
						$report_today = mktime( $report_hour, 0, 0, date( "m" ), date( "d" ), date( "Y" ) );
						$now          = mktime() + get_option( 'gmt_offset' ) * 3600;
						$when         = ( $now < $report_today ) ? $report_today : mktime( $report_hour, 0, 0, date( "m" ), date( "d" ) + 1, date( "Y" ) );
						break;
				}

				//error_log("Report scheduled on ".date('r', $when));
				$when -= get_option( 'gmt_offset' ) * 3600;
				//error_log("Report scheduled on ".date('r', $when) . 'GMT');
				wp_schedule_single_event( $when, PSK_S2MSFB_ID . '_cron_report' );
			} else {
				//$when = wp_next_scheduled( PSK_S2MSFB_ID . '_cron_report' );
				//error_log("Report already scheduled on ".date('r',$when).' GMT');
				//$when += get_option('gmt_offset') * 3600;
				//error_log("Report already scheduled on ".date('r',$when));
			}
		} else {
			//error_log("Report scheduled deactivated");
		}

		// DB : Clean downloads
		if ( ! wp_next_scheduled( PSK_S2MSFB_ID . '_cron_db_clean_download_hook' ) )
			wp_schedule_event( time(), 'hourly', PSK_S2MSFB_ID . '_cron_db_clean_download_hook' );

		// DB : Clean files
		if ( ! wp_next_scheduled( PSK_S2MSFB_ID . '_cron_db_clean_files_hook' ) )
			wp_schedule_event( time(), 'hourly', PSK_S2MSFB_ID . '_cron_db_clean_files_hook' );

	}


	/**
	 * Enable crontab
	 *
	 * @wp_action    PSK_S2MSFB_ID.'_disable_wp_cron'
	 * @return void
	 */
	static public function disable_cron() {
		$timestamp = wp_next_scheduled( PSK_S2MSFB_ID . '_cron_db_clean_download_hook' );
		wp_unschedule_event( $timestamp, PSK_S2MSFB_ID . '_cron_db_clean_download_hook' );
		$timestamp = wp_next_scheduled( PSK_S2MSFB_ID . '_cron_db_clean_files_hook' );
		wp_unschedule_event( $timestamp, PSK_S2MSFB_ID . '_cron_db_clean_files_hook' );
		$timestamp = wp_next_scheduled( PSK_S2MSFB_ID . '_cron_report' );
		wp_unschedule_event( $timestamp, PSK_S2MSFB_ID . '_cron_report' );
	}


	/*
	 * Load javascript and css for Public and Admin part
	 * Called from the shortcode only !
	 *
	 * @return          void
	 */
	/**
	 *
	 */
	public static function init_assets() {
		wp_deregister_script( 'jquery' );
		wp_register_script( 'jquery', PSK_S2MSFB_JS_URL . 'jquery-1.8.3.min.js', false );
		wp_enqueue_script( 'jquery' );
	}


	/*
	 * WP wp_enqueue_scripts
	 * Load javascript and css for Public and Admin part
	 *
	 * @param       array $atts        the arguments from the editor
	 * @wp_action 		wp_enqueue_scripts
	 * @return          void
	 */
	/**
	 * @param $atts
	 */
	public static function init_shortcode_assets( $atts ) {

		if ( isset( $atts['previewext'] ) ) {
			foreach ( explode( ',', $atts['previewext'] ) as $ext ) {
				/** @var $ext string */
				if ( 'mp3' == trim( strtolower( $ext ) ) )
					wp_register_script( 'jquery.jplayer', PSK_S2MSFB_JS_URL . 'jquery.jplayer.min.js', array( 'jquery' ), '2.2.0', true );
				@
				wp_enqueue_script( 'jquery.jplayer' );
			}
		}

		wp_enqueue_script( PSK_S2MSFB_ID, PSK_S2MSFB_JS_URL . 'jqueryFileTree.' . PSK_S2MSFB_EXT_JS, array( 'jquery' ), false, true );
		wp_register_style( PSK_S2MSFB_ID, PSK_S2MSFB_CSS_URL . 'jqueryFileTree.' . PSK_S2MSFB_EXT_CSS );
		wp_enqueue_style( PSK_S2MSFB_ID );

		// Set localize javascript
		$prefix = ( is_admin() ) ? 'admin_' : '';
		wp_localize_script( PSK_S2MSFB_ID, __CLASS__, array(
			'ajaxurl'        => admin_url( 'admin-ajax.php' ),
			'nonce'          => wp_create_nonce( PSK_S2MSFB_ID . '-nonce' ),
			'errorsearch'    => __( 'Please type some words!', PSK_S2MSFB_ID ),
			'action_get_dir' => $prefix . PSK_S2MSFB_ID . '_get_dir',
			'action_df'      => $prefix . PSK_S2MSFB_ID . '_df',
			'action_rf'      => $prefix . PSK_S2MSFB_ID . '_rf',
		) );

	}


	/**
	 * Set the private value to true if the call is an admin call and false if the call comes from the public
	 * We do this because is_admin() in 'admin_ajax.php' is always true so we set the value $is_admin here to false
	 *
	 * @param      boolean $bool  is admin or not
	 *
	 * @return          void
	 */
	public static function set_is_admin( $bool ) {
		self::$is_admin = ( $bool === true ) ? true : false;
	}


	/**
	 * Ajax call - Admin wrapper
	 */
	public static function ajax_admin_delete_file() {
		if ( PSK_S2MSFBAdmin::load_admin_class_file( 'manager_browser' ) )
			PSK_S2MSFBAdminManager::ajax_admin_delete_file();
		die( 'action not found' );
	}


	/**
	 * Ajax call - Admin wrapper
	 */
	public static function ajax_admin_rename_file() {
		if ( PSK_S2MSFBAdmin::load_admin_class_file( 'manager_browser' ) )
			PSK_S2MSFBAdminManager::ajax_admin_rename_file();
		die( 'action not found' );
	}


	/**
	 * Ajax call - Returns a directory as a html structure
	 * Admin_ajax.php is always true so we set the value $is_admin here to true
	 * This method is the action for the admin part
	 */
	public static function ajax_admin_get_directory() {
		if ( PSK_S2MSFBAdmin::load_admin_class_file( 'manager_browser' ) )
			PSK_S2MSFBAdminManager::ajax_admin_get_directory();
		die( 'action not found' );
	}


	/**
	 * Ajax call - Returns a directory as a html structure
	 * is_admin() in 'admin_ajax.php' is always true so we set the value $is_admin here to false
	 * This method is the action for the public part
	 */
	public static function ajax_get_directory() {
		self::set_is_admin( false );
		self::ajax_do_get_directory();
		die();
	}


	/**
	 * Returns a directory as a html structure
	 */
	public static function ajax_do_get_directory() {
		if ( ! isset( $_POST['nonce'] ) || ! check_ajax_referer( PSK_S2MSFB_ID . '-nonce', 'nonce', false ) ) die ( 'Invalid nonce' );
		if ( ! isset( $_POST['dir'] ) ) die ( 'invalid parameters' );

		// Retrieve shortcode parameters and overwrite defaults
		$dirbase = PSK_Tools::sanitize_directory_path( stripslashes( rawurldecode( @$_POST['dirbase'] ) ) );
		$current = PSK_S2MSFB_S2MEMBER_FILES_FOLDER . $dirbase;
		if ( ! PSK_Tools::is_directory_allowed( $current ) ) {
			$dirbase = '';
			$current = PSK_S2MSFB_S2MEMBER_FILES_FOLDER;
		}

		self::$display_all_levels      = ( @$_POST['displayall'] == '1' ) ? true : false;
		self::$display_hidden_files    = ( @$_POST['hidden'] == '1' ) ? true : false;
		self::$display_directory_first = ( @$_POST['dirfirst'] == '0' ) ? false : true;
		self::$openrecursive           = ( @$_POST['openrecursive'] == '1' ) ? true : false;
		self::$displaysize             = ( @$_POST['displaysize'] == '0' ) ? false : true;
		self::$dirzip                  = ( @$_POST['dirzip'] == '1' ) ? true : false;
		self::$displaydownloaded       = (int) @$_POST['displaydownloaded'];
		self::$search                  = (int) @$_POST['search'];
		self::$searchdisplay           = (int) @$_POST['searchdisplay'];
		self::$cutdirnames             = (int) @$_POST['cutdirnames'];
		self::$cutfilenames            = (int) @$_POST['cutfilenames'];
		self::$filterfile              = stripslashes( rawurldecode( @$_POST['filterfile'] ) );
		self::$filterdir               = stripslashes( rawurldecode( @$_POST['filterdir'] ) );
		$display_directory_names       = stripslashes( @$_POST['names'] );

		self::$displayed_directory_names = array();
		$tmp                             = explode( '|', $display_directory_names );
		foreach ( $tmp as $keyval ) {
			@list( $key, $val ) = @explode( ':', $keyval, 2 );
			self::$displayed_directory_names[rawurldecode( $key )] = rawurldecode( $val );
		}
		self::$previewext = array();
		/** @var $ext string */
		foreach ( explode( ',', @$_POST['previewext'] ) as $ext ) {
			$ext = trim( strtolower( $ext ) );
			if ( in_array( $ext, self::$previewext_available ) )
				self::$previewext[] = $ext;
		}

		// Retrieve current directory
		//
		$dir_rel = stripslashes( rawurldecode( $_POST['dir'] ) );
		$token   = ( isset( $_POST['token'] ) ) ? stripslashes( rawurldecode( $_POST['token'] ) ) : '';
		echo self::recursive_directory( $current, $dirbase, $dir_rel, $token );
		die();
	}


	/**
	 * Returns a directory as a html structure (recursive method)
	 *
	 * @param       string $current     the root directory ( eg: /HD/wp/wp-content/plugins/s2member-files/ahah )
	 * @param       string $dirbase     the shortcode dirbase directory append to $current ( eg: ahah )
	 * @param       string $dir_rel     the inloop directory append to $dirbase ( eg: /Music/ )
	 * @param       string $token       the token to search in current directory
	 *
	 * @return      string               directory as a html structure
	 */
	private static function recursive_directory( $current, $dirbase, $dir_rel, $token = '' ) {
//		error_log( $current );
//		error_log( $dirbase );
//		error_log( $dir_rel );

		$dir     = $current . $dir_rel;
		$dirfile = PSK_Tools::sanitize_directory_path( $dirbase . $dir_rel, true, true ); // eg: /ahah/Music/
		$return  = '';

		if ( file_exists( $dir ) ) {

			// Check if this directory is below PSK_S2MSFB_S2MEMBER_FILES_FOLDER
			if ( ! PSK_Tools::is_directory_allowed( $dir ) ) return __( 'Permission denied', PSK_S2MSFB_ID );

			$hashes   = array();
			$alreadyd = array();

			// Get hashes and already downloaded files
			if ( self::$displaydownloaded > 0 ) {

				/** @var $wpdb WPDB */
				global $wpdb;
				$tablename = $wpdb->prefix . PSK_S2MSFB_DB_FILES_TABLE_NAME;
				$sql       = "SELECT filepath, filemd5 FROM " . $tablename . " WHERE filepath LIKE '" . mysql_real_escape_string( $dirfile ) . "%'";
				$result    = $wpdb->get_results( $sql, ARRAY_A );
				foreach ( $result as $row ) {
					$hashes[$row['filepath']] = $row['filemd5'];
				}

				$cuser     = wp_get_current_user();
				$tablename = $wpdb->prefix . PSK_S2MSFB_DB_DOWNLOAD_TABLE_NAME;
				$sql       = $wpdb->prepare( "SELECT filepath, filemd5 FROM " . $tablename . " WHERE userid = %s ORDER BY created ASC", $cuser->ID );
				$result    = $wpdb->get_results( $sql, ARRAY_A );
				foreach ( $result as $row ) {
					$alreadyd[$row['filepath']] = $row['filemd5'];
				}
			}

			$result  = array();
			$resultf = array();
			$resultd = array();

			// Part : listing
			if ( $token == '' ) {

				// Browse all dirs and files
				$files = scandir( $dir );
				foreach ( $files as $file ) {

					$filepath        = $dir . $file;
					$filepathrel     = $dir_rel . $file;
					$filepathrelbase = PSK_Tools::sanitize_directory_path( $dirbase, true, false ) . PSK_Tools::sanitize_directory_path( $filepathrel, true, false );

					// Remove all . .. and hidden files if option is not set
					if ( ! file_exists( $filepath ) )
						continue;
					if ( $file == '.' )
						continue;
					if ( $file == '..' )
						continue;
					if ( $file == '.htaccess' )
						continue;
					if ( ! self::$display_hidden_files && ( mb_substr( $file, 0, 1 ) == '.' ) )
						continue;

					// Check for filter
					$isdir = is_dir( $filepath );
					if ( $isdir ) {
						if ( self::$filterdir != '' )
							if ( ! preg_match( self::$filterdir, $file ) )
								continue;
							else
								if ( self::$filterfile != '' )
									if ( ! preg_match( self::$filterfile, $file ) )
										continue;
					}


					// Check for zip file corresponding to a directory
					if ( self::$dirzip )
						if ( ! $isdir )
							if ( self::$dirzip && ( 'zip' == mb_strtolower( preg_replace( '/^.*\./', '', $file ) ) ) )
								if ( is_dir( mb_substr( $filepath, 0, - 4 ) ) )
									continue;

					// Check for granted access only if with have to display all informations
					if ( ! self::$display_all_levels ) {

						// Check if the file is allowed by s2member level
						if ( in_array( $file, self::$directory_s2_level ) )
							if ( current_user_cannot( self::$directory_s2_level_to_rights[$file] ) )
								continue;

						// Check if the file is allowed by s2member custom capability
						if ( PSK_Tools::starts_with( $file, PSK_S2MSFB_S2MEMBER_CCAP_FOLDER ) )
							if ( current_user_cannot( str_replace( PSK_S2MSFB_S2MEMBER_CCAP_FOLDER, PSK_S2MSFB_S2MEMBER_CCAP_RIGHTS, $file ) ) )
								continue;
					}

					// Get the html fragment
					list( $display_name, $li ) = self::get_html_li_token( $isdir, $file, $filepathrelbase, $filepathrel, $filepath, $current, $dirbase, $token, $alreadyd, $hashes );
					if ( self::$display_directory_first )
						if ( $isdir )
							$resultd[$display_name] = $li;
						else
							$resultf[$display_name] = $li;
					else
						$result[$display_name] = $li;

				}

				// Sort arrays according to displayed file names and not to system real file names
				if ( self::$display_directory_first ) {
					uksort( $resultd, "strnatcasecmp" );
					uksort( $resultf, "strnatcasecmp" );
					$result = array_merge( $resultd, $resultf );
				} else {
					uksort( $result, "strnatcasecmp" );
				}

				$search_inp_value = __( 'Search...', PSK_S2MSFB_ID );
				$reset_btn_hidden = ' style="display:none;" ';
				$reset_class      = 'reset';
			} // Part search
			else {
				/** @var $wpdb WPDB */
				global $wpdb;
				$tablename        = $wpdb->prefix . PSK_S2MSFB_DB_FILES_TABLE_NAME;
				$search_inp_value = $token;
				$reset_btn_hidden = '';
				$reset_class      = 'reload';
				$extended         = '';

				// Search group by path
				if ( self::$searchdisplay == '2' ) {

					$group   = array();
					$words   = explode( ' ', trim( $token ) );
					$wordz   = array();
					$clauses = array();
					foreach ( $words as $word ) {
						if ( $word == '' )
							continue;
						$wordz[]   = $word;
						$clauses[] = "filepath LIKE '%" . mysql_real_escape_string( $word ) . "%' ";
					}
					$clause = ( count( $clauses ) > 0 ) ? ' AND ( ' . implode( ' OR ', $clauses ) . ' ) ' : '';
					$worda  = implode( ' ', $wordz );
					$sql    = "SELECT filename, filepath, MATCH(filename) AGAINST('" . mysql_real_escape_string( $worda ) . ' ' . mysql_real_escape_string( str_replace( ' ', '* ', $worda . '*' ) ) . "' IN BOOLEAN MODE) AS score ";
					$sql .= "FROM " . $tablename . " ";
					$sql .= "WHERE filepath LIKE '" . mysql_real_escape_string( $dirfile ) . "%' " . $clause;
					$sql .= "ORDER BY score DESC, filename";
					$sqlres = $wpdb->get_results( $sql, ARRAY_A );

					foreach ( $sqlres as $row ) {
						$filepathrelbase = $row['filepath'];
						$filepath        = PSK_S2MSFB_S2MEMBER_FILES_FOLDER . $filepathrelbase;
						if ( ! file_exists( $filepath ) )
							continue;
						$file                               = $row['filename'];
						$dirbase                            = PSK_Tools::sanitize_directory_path( $dirbase, true, false );
						$filepathrel                        = mb_substr( $filepathrelbase, mb_strlen( $dirfile ) );
						$subdirectory                       = mb_substr( $filepathrel, 0, - mb_strlen( $file ) );
						$subdirectory                       = ( $subdirectory == '' ) ? '/' : $subdirectory;
						$a                                  = self::get_html_li_token( false, $file, $filepathrelbase, $filepathrel, $filepath, $current, $dirbase, $token, $alreadyd, $hashes, $extended );
						$group[$subdirectory][$filepathrel] = $a[1];
					}

					uksort( $group, "strnatcasecmp" );

					foreach ( $group as $groupby => $v ) {
						$result[] = '<li class="directory expanded" data-s="-1"><div class="jftctn">';
						$result[] = '<a href="#" class="link" rel="">' . sprintf( __( 'Path <strong>%s</strong>', PSK_S2MSFB_ID ), $groupby ) . '</a>';
						$result[] = '</div>';
						$result[] = '<div style="clear:both"></div>';
						$result[] = '<ul class="jqueryFileTree">';
						uksort( $v, "strnatcasecmp" );
						foreach ( $v as $li ) {
							$result[] = $li;
						}
						$result[] = '</ul>';
						$result[] = '</li>';
					}

					// Search group by extension
				} else if ( ( self::$searchdisplay == '3' ) || ( self::$searchdisplay == '4' ) ) {

					$group   = array();
					$words   = explode( ' ', trim( $token ) );
					$wordz   = array();
					$clauses = array();
					foreach ( $words as $word ) {
						if ( $word == '' )
							continue;
						$wordz[]   = $word;
						$clauses[] = "filepath LIKE '%" . mysql_real_escape_string( $word ) . "%' ";
					}
					$clause = ( count( $clauses ) > 0 ) ? ' AND ( ' . implode( ' OR ', $clauses ) . ' ) ' : '';
					$worda  = implode( ' ', $wordz );
					$sql    = "SELECT filename, filepath, fileext, MATCH(filename) AGAINST('" . mysql_real_escape_string( $worda ) . ' ' . mysql_real_escape_string( str_replace( ' ', '* ', $worda . '*' ) ) . "' IN BOOLEAN MODE) AS score ";
					$sql .= "FROM " . $tablename . " ";
					$sql .= "WHERE filepath LIKE '" . mysql_real_escape_string( $dirfile ) . "%' " . $clause;
					$sql .= "ORDER BY score DESC, filename";
					$sqlres = $wpdb->get_results( $sql, ARRAY_A );

					foreach ( $sqlres as $row ) {
						$filepathrelbase = $row['filepath'];
						$filepath        = PSK_S2MSFB_S2MEMBER_FILES_FOLDER . $filepathrelbase;
						if ( ! file_exists( $filepath ) )
							continue;
						$file                            = $row['filename'];
						$dirbase                         = PSK_Tools::sanitize_directory_path( $dirbase, true, false );
						$filepathrel                     = mb_substr( $filepathrelbase, mb_strlen( $dirfile ) );
						$subdirectory                    = mb_substr( $filepathrel, 0, - mb_strlen( $file ) );
						$subdirectory                    = ( $subdirectory == '' ) ? '/' : $subdirectory;
						$extension                       = mb_strtolower( $row['fileext'] );
						$extended                        = ( self::$searchdisplay == '4' ) ? ' <small><em>(' . sprintf( __( 'in %s', PSK_S2MSFB_ID ), self::get_directories_name( $subdirectory ) ) . ')</em></small>' : '';
						$a                               = self::get_html_li_token( false, $file, $filepathrelbase, $filepathrel, $filepath, $current, $dirbase, $token, $alreadyd, $hashes, $extended );
						$group[$extension][$filepathrel] = $a[1];
					}

					uksort( $group, "strnatcasecmp" );

					foreach ( $group as $groupby => $v ) {
						$result[] = '<li class="directory expanded ' . $groupby . '" data-s="-1"><div class="jftctn">';
						$result[] = '<a href="#" class="link" rel="">' . sprintf( __( 'Extension <strong>%s</strong>', PSK_S2MSFB_ID ), $groupby ) . '</a>';
						$result[] = '</div>';
						$result[] = '<div style="clear:both"></div>';
						$result[] = '<ul class="jqueryFileTree">';
						uksort( $v, "strnatcasecmp" );
						foreach ( $v as $li ) {
							$result[] = $li;
						}
						$result[] = '</ul>';
						$result[] = '</li>';
					}

					// Search flat
				} else {
					$words   = explode( ' ', trim( $token ) );
					$wordz   = array();
					$clauses = array();
					foreach ( $words as $word ) {
						if ( $word == '' )
							continue;
						$wordz[]   = $word;
						$clauses[] = "filepath LIKE '%" . mysql_real_escape_string( $word ) . "%' ";
					}
					$clause = ( count( $clauses ) > 0 ) ? ' AND ( ' . implode( ' OR ', $clauses ) . ' ) ' : '';
					$worda  = implode( ' ', $wordz );
					$sql    = "SELECT filename, filepath, MATCH(filename) AGAINST('" . mysql_real_escape_string( $worda ) . ' ' . mysql_real_escape_string( str_replace( ' ', '* ', $worda . '*' ) ) . "' IN BOOLEAN MODE) AS score ";
					$sql .= "FROM " . $tablename . " ";
					$sql .= "WHERE filepath LIKE '" . mysql_real_escape_string( $dirfile ) . "%' " . $clause;
					$sql .= "ORDER BY score DESC, filename";
					$sqlres = $wpdb->get_results( $sql, ARRAY_A );

					foreach ( $sqlres as $row ) {
						$filepathrelbase = $row['filepath'];
						$filepath        = PSK_S2MSFB_S2MEMBER_FILES_FOLDER . $filepathrelbase;
						if ( ! file_exists( $filepath ) )
							continue;
						$file                 = $row['filename'];
						$dirbase              = PSK_Tools::sanitize_directory_path( $dirbase, true, false );
						$filepathrel          = mb_substr( $filepathrelbase, mb_strlen( $dirfile ) );
						$subdirectory         = mb_substr( $filepathrel, 0, - mb_strlen( $file ) );
						$subdirectory         = ( $subdirectory == '' ) ? '/' : $subdirectory;
						$extended             = ( self::$searchdisplay == '0' ) ? ' <small><em>(' . sprintf( __( 'in %s', PSK_S2MSFB_ID ), self::get_directories_name( $subdirectory ) ) . ')</em></small>' : '';
						$a                    = self::get_html_li_token( false, $file, $filepathrelbase, $filepathrel, $filepath, $current, $dirbase, $token, $alreadyd, $hashes, $extended );
						$result[$filepathrel] = $a[1];
					}
					//uksort( $result, "strnatcasecmp" );
				}
			}

			$return = '<ul class="jqueryFileTree" data-token="' . $token . '" style="display: none;">';

			if ( ( ( ( count( $result ) > 0 ) && ( $token == '' ) && ( ( ( self::$search == 1 ) && ( ( $dir_rel == '/' ) || ( $dir_rel == '' ) ) ) || ( self::$search > 1 ) ) ) || ( $token != '' ) ) ) {
				$reset_btn_value  = __( 'Click to reset', PSK_S2MSFB_ID );
				$search_btn_value = __( 'Click to search', PSK_S2MSFB_ID );
				$search_inp_title = __( 'Search...', PSK_S2MSFB_ID );

				$return .= '<li>';
				$return .= ' <div class="PSK_S2MSFB_search">';
				$return .= '  <button value="reset"' . $reset_btn_hidden . 'class="PSK_S2MSFB_' . $reset_class . 'btn" title="' . PSK_Tools::rel_literal( $reset_btn_value ) . '"></button>';
				$return .= '  <button value="submit" class="PSK_S2MSFB_searchbtn" title="' . PSK_Tools::rel_literal( $search_btn_value ) . '"></button>';
				$return .= '  <input type="text" name="search" class="PSK_S2MSFB_searchinp" value="' . PSK_Tools::rel_literal( $search_inp_value ) . '" title="' . PSK_Tools::rel_literal( $search_inp_title ) . '"/>';
				$return .= ' </div>';
				$return .= ' <div style="clear:both"></div>';
				$return .= '</li>';
			}

			foreach ( $result as $html ) {
				$return .= $html;
			}

			if ( ( count( $result ) == 0 ) && ( $token != '' ) )
				$return .= '<li>' . __( 'No result', PSK_S2MSFB_ID ) . '</li>';

			$return .= '</ul>';
		}
		return $return;
	}


	/**
	 * Return the display name of a path (several directories) according to user parameters
	 *
	 * @param $path
	 *
	 * @return string
	 */
	private static function get_directories_name( $path ) {
		$directories = explode( '/', $path );
		$result      = array();
		foreach ( $directories as $directory ) {
			$result[] = ( $directory == '' ) ? '' : self::get_display_name( true, $directory );
		}
		return implode( '/', $result );
	}


	/**
	 * Return the display name of a directory of file according to user parameters
	 *
	 * @param $isdir
	 * @param $file
	 *
	 * @return string
	 */
	private static function get_display_name( $isdir, $file ) {
		// Prepare dir/file name if cut
		if ( $isdir )
			if ( self::$cutdirnames > 0 )
				$cut_file = ( mb_strlen( $file ) > self::$cutdirnames ) ? PSK_Tools::html_entities( trim( mb_substr( $file, 0, self::$cutdirnames ) ) ) . '&hellip;' : PSK_Tools::html_entities( $file );
			else
				$cut_file = PSK_Tools::html_entities( $file );
		else
			if ( self::$cutfilenames > 0 ) {
				$en       = preg_replace( '/^.*\./', '', $file );
				$bn       = basename( $file, '.' . $en );
				$cut_file = ( mb_strlen( $bn ) > self::$cutfilenames ) ? PSK_Tools::html_entities( trim( mb_substr( $bn, 0, self::$cutfilenames ) ) ) . '&hellip;.' . $en : PSK_Tools::html_entities( $file );
			} else {
				$cut_file = PSK_Tools::html_entities( $file );
			}

		// Set the displayed name according to user shortcode parameters and next s2level names
		if ( isset( self::$displayed_directory_names[$file] ) )
			if ( self::$is_admin === true )
				$display_name = $cut_file . ' <span class="fn">(' . PSK_Tools::html_entities( self::$displayed_directory_names[$file] ) . ')</span>';
			else
				$display_name = PSK_Tools::html_entities( self::$displayed_directory_names[$file] );
		else if ( isset( self::$directory_s2_level_friendly[$file] ) )
			if ( self::$is_admin === true )
				$display_name = $cut_file . ' <span class="fn">(' . PSK_Tools::html_entities( self::$directory_s2_level_friendly[$file] ) . ')</span>';
			else
				$display_name = PSK_Tools::html_entities( self::$directory_s2_level_friendly[$file] );
		else
			$display_name = $cut_file;

		return $display_name;
	}


	/**
	 * Return a LI HTML fragment
	 *
	 * @param        $isdir
	 * @param        $file
	 * @param        $filepathrelbase
	 * @param        $filepathrel
	 * @param        $filepath
	 * @param        $current
	 * @param        $dirbase
	 * @param        $token
	 * @param        $alreadyd
	 * @param        $hashes
	 * @param string $extended
	 *
	 * @return array
	 */
	private static function get_html_li_token( $isdir, $file, $filepathrelbase, $filepathrel, $filepath, $current, $dirbase, $token, $alreadyd, $hashes, $extended = '' ) {

		$display_name = self::get_display_name( $isdir, $file );

		if ( $isdir ) {
			$lizip    = '';
			$already  = ' style="display:none;"';
			$alreadys = '';
			$alreadya = '0';

			if ( file_exists( $filepath . '.zip' ) ) {
				$filepathrelbasezip = $filepathrelbase . '.zip';
				$link               = PSK_Tools::rel_literal( s2member_file_download_url( array( 'file_download' => $filepathrelbasezip ) ) );
				if ( self::$displaydownloaded > 0 ) {
					if ( isset( $alreadyd[$filepathrelbasezip] ) ) {
						if ( ! isset( $hashes[$filepathrelbasezip] ) ) {
							self::db_clean_files();
						} else {
							if ( $hashes[$filepathrelbasezip] == $alreadyd[$filepathrelbasezip] ) {
								if ( self::$displaydownloaded == 2 ) {
									$already  = '';
									$alreadys = ' already';
								}
								$alreadya = '1';
							}
						}
					}
				}
				$lizip = '<span class="d dwnl display_name"><a href="#" class="link" rel="' . $link . '">' . __( 'Download zip', PSK_S2MSFB_ID ) . '</a></span>';
			}

			$li = '<li class="directory ';
			$li .= ( self::$openrecursive ) ? "expanded" : "collapsed";
			$li .= $alreadys;
			$li .= '" data-s="-1" data-already="' . $alreadya . '"><div class="jftctn">';
			$li .= '<a href="#" class="link" rel="' . PSK_Tools::rel_literal( $filepathrel ) . '/">' . $display_name . $extended . '</a>';
			$li .= $lizip;
			$li .= '<span class="d size">&nbsp;</span>';
			$li .= '<span class="d already"' . $already . '>' . __( 'You already have downloaded this directory', PSK_S2MSFB_ID ) . '&nbsp;&nbsp;&nbsp;</span>';
			$li .= ( self::$is_admin === true ) ? '<a href="javascript:psk_sfb_rename_dir(' . PSK_Tools::js_literal( $filepathrelbase ) . ')" class="d"><i class="icon-pencil"></i></a>&nbsp;&nbsp;' : '';
			$li .= ( self::$is_admin === true ) ? '<a href="javascript:psk_sfb_remove_dir(' . PSK_Tools::js_literal( $filepathrelbase ) . ')" class="d"><i class="icon-remove-sign"></i></a>&nbsp;&nbsp;' : '';
			$li .= '</div>';
			$li .= '<div style="clear:both"></div>';
			$li .= ( self::$openrecursive ) ? self::recursive_directory( $current, $dirbase, $filepathrel . DIRECTORY_SEPARATOR, $token ) : '';
			$li .= '</li>';

		} else {
			$ext      = mb_strtolower( preg_replace( '/^.*\./', '', $file ) );
			$link     = PSK_Tools::rel_literal( s2member_file_download_url( array( 'file_download' => $filepathrelbase ) ) );
			$prev     = PSK_Tools::rel_literal( s2member_file_download_url( array( 'file_download' => $filepathrelbase, 'file_inline' => true ) ) );
			$size     = filesize( $filepath );
			$hsize    = PSK_Tools::size_readable( $size );
			$size     = PSK_Tools::rel_literal( $size );
			$already  = ' style="display:none;"';
			$alreadys = '';
			$alreadya = '0';
			if ( self::$displaydownloaded > 0 ) {
				if ( isset( $alreadyd[$filepathrelbase] ) ) {
					if ( ! isset( $hashes[$filepathrelbase] ) ) {
						self::db_clean_files();
					} else {
						if ( $hashes[$filepathrelbase] == $alreadyd[$filepathrelbase] ) {
							if ( self::$displaydownloaded == 2 ) {
								$already  = '';
								$alreadys = ' already';
							}
							$alreadya = '1';
						}
					}
				}
			}

			/** @var $ext string */
			$li = '<li class="file' . $alreadys . ' ext_' . PSK_Tools::rel_literal( $ext ) . '" data-s="' . $size . '" data-already="' . $alreadya . '">';
			$li .= '<div class="jftctn"><a href="#" class="link" rel="' . $link . '">' . $display_name . $extended . '</a>';
			$li .= '<span class="size d" title="' . $size . ' ' . _x( 'B', 'Bytes abbr', PSK_S2MSFB_ID ) . '">';
			$li .= ( self::$displaysize ) ? $hsize : '';
			$li .= '</span>';

			if ( in_array( $ext, self::$previewext ) )
				$li .= '<span class="prev d" data-e="' . $ext . '" rel="' . $prev . '"></span>';

			$li .= '<span class="already d"' . $already . '>' . __( 'You already have downloaded this file', PSK_S2MSFB_ID ) . '&nbsp;&nbsp;&nbsp;</span>';

			$li .= ( self::$is_admin ) ? '<a href="javascript:psk_sfb_rename_file(' . PSK_Tools::js_literal( $filepathrelbase ) . ')" class="d"><i class="icon-pencil"></i></a>&nbsp;&nbsp;' : '';
			$li .= ( self::$is_admin ) ? '<a href="javascript:psk_sfb_remove_file(' . PSK_Tools::js_literal( $filepathrelbase ) . ')" class="d"><i class="icon-remove"></i></a>&nbsp;&nbsp;' : '';
			$li .= '<div style="clear:both"></div></div></li>';
		}

		return array( $display_name, $li );

	}


	/**
	 * Returns a shortcode
	 *
	 * @param       array $atts        the arguments from the editor
	 *
	 * @return      string               the shortcode html code
	 */
	public static function shortcode_s2member_secure_files_browser( $atts ) {
		self::init_shortcode_assets( $atts );

		$i = self::$shortcode_instance;
		self::$shortcode_instance ++;

		$rt = '<div id="' . PSK_S2MSFB_ID . $i . '" class="psk_jfiletree"></div>';
		$rt .= '<script type="text/javascript">';
		$rt .= 'jQuery(document).ready(function($){$("#' . PSK_S2MSFB_ID . $i . '").fileTree({';
		$rt .= '	root:"' . DIRECTORY_SEPARATOR . '",';
		$rt .= '	swfurl:"' . PSK_S2MSFB_SWF_URL . '",';
		$rt .= '	loadmessage:"' . esc_attr__( "Please wait while loading...", PSK_S2MSFB_ID ) . '"';

		if ( is_array( $atts ) ) {
			foreach ( $atts as $param => $value ) {
				$rt .= ',' . $param . ':"' . str_replace( '"', '\"', $value ) . '" ';
			}
		}

		$rt .= '}, function( obj ) {';

		$rt .= 'var download = false;';
		$rt .= 'var a = $(obj).parent().parent().attr( "data-already" );';
		$rt .= 'var f = (typeof a !== "undefined" && a !== false) ? $(obj).parent().parent() : $(obj).parent().parent().parent();'; // file or directory

		if ( @$atts['displaydownloaded'] == '1' ) {
			$rt .= 'if ( $(f).attr( "data-already" ) == "1" ) {';
			$rt .= '	if (!confirm("' . PSK_Tools::js_esc_string( __( 'You already have downloaded this file.\nAre you sure you want to download it again ?', PSK_S2MSFB_ID ) ) . '")) {';
			$rt .= '		return;';
			$rt .= '	}';
			$rt .= '}';
		}

		if ( @$atts['s2alertbox'] == '1' ) {
			$rt .= 'var skipAllFileConfirmations = ( typeof ws_plugin__s2member_skip_all_file_confirmations !== "undefined" && ws_plugin__s2member_skip_all_file_confirmations) ? true : false;';
			$rt .= 'var uniqueFilesDownloadedInPage = [];';
			$rt .= 'if (S2MEMBER_CURRENT_USER_IS_LOGGED_IN && S2MEMBER_CURRENT_USER_DOWNLOADS_CURRENTLY < S2MEMBER_CURRENT_USER_DOWNLOADS_ALLOWED && !skipAllFileConfirmations) {';
			$rt .= '		var c = "' . c_ws_plugin__s2member_utils_strings::esc_js_sq( _x( "— Confirm File Download —", "s2member-front", "s2member" ) ) . '" + "\n\n";';
			$rt .= '		c += $.sprintf ("' . c_ws_plugin__s2member_utils_strings::esc_js_sq( _x( "You`ve downloaded %s protected %s in the last %s.", "s2member-front", "s2member" ) ) . '", S2MEMBER_CURRENT_USER_DOWNLOADS_CURRENTLY, (S2MEMBER_CURRENT_USER_DOWNLOADS_CURRENTLY === 1) ? "' . c_ws_plugin__s2member_utils_strings::esc_js_sq( _x( "file", "s2member-front", "s2member" ) ) . '" : "' . c_ws_plugin__s2member_utils_strings::esc_js_sq( _x( "files", "s2member-front", "s2member" ) ) . '", ((S2MEMBER_CURRENT_USER_DOWNLOADS_ALLOWED_DAYS === 1) ? "' . c_ws_plugin__s2member_utils_strings::esc_js_sq( _x( "24 hours", "s2member-front", "s2member" ) ) . '" : $.sprintf ("' . c_ws_plugin__s2member_utils_strings::esc_js_sq( _x( "%s days", "s2member-front", "s2member" ) ) . '", S2MEMBER_CURRENT_USER_DOWNLOADS_ALLOWED_DAYS))) + "\n\n";';
			$rt .= '		c += (S2MEMBER_CURRENT_USER_DOWNLOADS_ALLOWED_IS_UNLIMITED) ? "' . c_ws_plugin__s2member_utils_strings::esc_js_sq( _x( "You`re entitled to UNLIMITED downloads though ( so, no worries ).", "s2member-front", "s2member" ) ) . '" : $.sprintf ("' . c_ws_plugin__s2member_utils_strings::esc_js_sq( _x( "You`re entitled to %s unique %s %s.", "s2member-front", "s2member" ) ) . '", S2MEMBER_CURRENT_USER_DOWNLOADS_ALLOWED, ((S2MEMBER_CURRENT_USER_DOWNLOADS_ALLOWED === 1) ? "' . c_ws_plugin__s2member_utils_strings::esc_js_sq( _x( "download", "s2member-front", "s2member" ) ) . '" : "' . c_ws_plugin__s2member_utils_strings::esc_js_sq( _x( "downloads", "s2member-front", "s2member" ) ) . '"), ((S2MEMBER_CURRENT_USER_DOWNLOADS_ALLOWED_DAYS === 1) ? "' . c_ws_plugin__s2member_utils_strings::esc_js_sq( _x( "each day", "s2member-front", "s2member" ) ) . '" : $.sprintf ("' . c_ws_plugin__s2member_utils_strings::esc_js_sq( _x( "every %s-day period", "s2member-front", "s2member" ) ) . '", S2MEMBER_CURRENT_USER_DOWNLOADS_ALLOWED_DAYS)));';
			$rt .= '		if (confirm(c)) {';
			$rt .= '			if ($.inArray (this.href, uniqueFilesDownloadedInPage) === -1) {';
			$rt .= '				S2MEMBER_CURRENT_USER_DOWNLOADS_CURRENTLY++, uniqueFilesDownloadedInPage.push (this.href);';
			$rt .= '			}';
			$rt .= '			download = true;';
			$rt .= '		}';
			$rt .= '} else {';
			$rt .= '	download = true;';
			$rt .= '}';
		} else {
			$rt .= 'download = true;';
		}

		$rt .= 'if ( download === true ) {';
		if ( @$atts['displaydownloaded'] == '2' ) {
			$rt .= '	$(f).addClass( "already" );';
			$rt .= '	$(f).attr( "data-already" , "1" );';
			$rt .= '	$(f).find( ".already" ).show();';
		} else if ( @$atts['displaydownloaded'] == '1' ) {
			$rt .= '	$(f).attr( "data-already" , "1" );';
		}
		$rt .= '	document.location.href = $(obj).attr("rel");';
		$rt .= '}';

		$rt .= '}); });';
		$rt .= '</script>';

		if ( is_admin() ) {
			$rt .= '<div id="pskModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="pskModalLabel" aria-hidden="true">';
			$rt .= ' <div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button><h3 id="pskModalLabel"></h3></div>';
			$rt .= ' <div class="modal-body" id="pskModalBody"></div>';
			$rt .= ' <div class="modal-footer"><button class="btn" data-dismiss="modal" aria-hidden="true" id="pskModalCancel">Cancel</button><button class="btn btn-primary" id="pskModalSave"></button></div>';
			$rt .= '</div>';
		}

		return $rt;
	}


	/**
	 * This method is called when a report is sent by cron
	 *
	 * @return      void
	 */
	public static function notify_report() {
		/** @var $wpdb WPDB */
		global $wpdb;

		$settings = get_option( PSK_S2MSFB_OPT_SETTINGS_NOTIFY );

		if ( @$settings['reportfrequency'] != '' ) {

			$emailfrom = ( $settings['reportemailfrom'] == '' ) ? PSK_S2MSFB_DEFAULT_EMAIL_REPORT_FROM : $settings['emailfrom'];
			$subject   = ( $settings['reportsubject'] == '' ) ? PSK_S2MSFB_DEFAULT_EMAIL_REPORT_SUBJECT : $settings['subject'];
			$emailto   = ( $settings['reportemailto'] == '' ) ? PSK_S2MSFB_DEFAULT_EMAIL_REPORT_TO : $settings['emailto'];

			$subject = str_replace( '%blogname%', get_bloginfo( 'name' ), $subject );
			$subject = '=?UTF-8?B?' . base64_encode( $subject ) . '?=';

			$msg = '';

			foreach ( get_users() as $user ) {
				$users[$user->ID] = $user->display_name;
			}


			// Block unnotified rows now
			//
			$now = date( 'Y-m-d H:i:s' );
			$how = $wpdb->update(
				$wpdb->prefix . PSK_S2MSFB_DB_DOWNLOAD_TABLE_NAME,
				array( 'notified' => $now ),
				array( 'notified' => 0 ),
				array( '%s' ),
				array( '%d' )
			);

			if ( $how > 0 ) {

				// Dates
				//
				$tablename = $wpdb->prefix . PSK_S2MSFB_DB_DOWNLOAD_TABLE_NAME;
				$sql       = "SELECT timestamp(MIN(created)) A, timestamp(MAX(created)) B FROM " . $tablename . " WHERE notified='" . $now . "'";
				$result    = $wpdb->get_row( $sql, ARRAY_N );

				if ( $result != null ) {

					// From To
					//
					$msg .= '<h2>' . sprintf( __( 'Stats from %s to %s', PSK_S2MSFB_ID ), $result[0], $result[1] ) . '</h2>';

					// Top files
					//
					$msg .= '<h3>' . __( 'Top files', PSK_S2MSFB_ID ) . '</h3>';
					$tablename = $wpdb->prefix . PSK_S2MSFB_DB_DOWNLOAD_TABLE_NAME;
					$sql       = "SELECT filepath, COUNT(*) A FROM " . $tablename . " WHERE notified='" . $now . "' GROUP BY filepath ORDER BY A DESC";
					$result    = $wpdb->get_results( $sql, ARRAY_A );
					if ( count( $result ) == 0 ) {
						$msg .= __( "No download", PSK_S2MSFB_ID );
					} else {
						$msg .= '<table border="1" cellpadding="2" cellspacing="0">';
						$msg .= '<tr>';
						$msg .= '  <th>' . __( 'File', PSK_S2MSFB_ID ) . '</th>';
						$msg .= '  <th>' . __( 'Count', PSK_S2MSFB_ID ) . '</th>';
						$msg .= '</tr>';
						foreach ( $result as $row ) {
							$msg .= '<tr>';
							$msg .= '  <td>' . PSK_Tools::mb_html_entities( $row['filepath'] ) . '</td>';
							$msg .= '  <td>' . $row['A'] . '</td>';
							$msg .= '</tr>';
						}
						$msg .= '</table>';
					}


					// Top downloaders
					//
					$msg .= '<h3>' . __( 'Top downloaders', PSK_S2MSFB_ID ) . '</h3>';
					$tablename = $wpdb->prefix . PSK_S2MSFB_DB_DOWNLOAD_TABLE_NAME;
					$sql       = "SELECT userid, COUNT(*) A FROM " . $tablename . "WHERE notified='" . $now . " GROUP BY userid ORDER BY A DESC";
					$result    = $wpdb->get_results( $sql, ARRAY_A );
					if ( count( $result ) == 0 ) {
						$msg .= __( "No download", PSK_S2MSFB_ID );
					} else {
						$msg .= '<table border="1" cellpadding="2" cellspacing="0">';
						$msg .= '<tr>';
						$msg .= '  <th>' . __( 'User', PSK_S2MSFB_ID ) . '</th>';
						$msg .= '  <th>' . __( 'Count', PSK_S2MSFB_ID ) . '</th>';
						$msg .= '</tr>';
						foreach ( $result as $row ) {
							if ( isset( $users[$row['userid']] ) ) {
								$user = $users[$row['userid']];
							} else {
								$user = $row['useremail'] . ' - #' . $row['userid'];
							}
							$msg .= '<tr>';
							$msg .= '  <td>' . $user . '</td>';
							$msg .= '  <td>' . $row['A'] . '</td>';
							$msg .= '</tr>';
						}
						$msg .= '</table>';
					}
				} else {
					$msg .= __( "No download", PSK_S2MSFB_ID );
				}
			}

			if ( $msg == '' ) {
				$msg = __( 'No data to report', PSK_S2MSFB_ID );
			}

			$headers = 'From: ' . $emailfrom . ' <' . $emailfrom . '>' . "\r\n";
			$headers .= 'Sender: ' . $emailfrom . ' <' . $emailfrom . '>' . "\r\n";
			$headers .= "Content-type: text/html; charset=UTF-8;" . "\r\n";

			$tos = explode( ',', $emailto );
			foreach ( $tos as $to ) {
				//error_log("Send email to ".$to);
				wp_mail( $to, $subject, $msg, $headers );
			}
		}
	}


	/**
	 * This method is called when a s2member file is downloaded
	 *
	 * @param       array $vars        the s2member context
	 *
	 * @return      void
	 */
	public static function notify_download( $vars = array() ) {
		/** @var $wpdb WPDB */
		global $wpdb;

		/* It seems to be a preview...
		 * Do not record anything
		 */
		if ( isset( $vars['serving_range'] ) ) {
			if ( $vars['serving_range'] === true ) {
				return;
			}
		}

		if ( isset( $_GET["s2member_file_download"] ) ) {
			delete_transient( PSK_S2MSFB_WIDGET_DOWNLOAD_LATEST_ID );
			delete_transient( PSK_S2MSFB_WIDGET_DOWNLOAD_TOP0_ID );
			delete_transient( PSK_S2MSFB_WIDGET_DOWNLOAD_TOP1_ID );
			delete_transient( PSK_S2MSFB_WIDGET_DOWNLOAD_TOP7_ID );
			delete_transient( PSK_S2MSFB_WIDGET_DOWNLOAD_TOP31_ID );
			delete_transient( PSK_S2MSFB_WIDGET_DOWNLOAD_TOP365_ID );

			$file    = stripslashes( $_GET["s2member_file_download"] );
			$user_id = $vars["user_id"];
			$user    = new WP_User( $user_id );
			$ip      = $_SERVER['REMOTE_ADDR'];

			// Get MD5 of the downloaded file
			//
			$tablename = $wpdb->prefix . PSK_S2MSFB_DB_FILES_TABLE_NAME;
			$filemd5   = $wpdb->get_var( $wpdb->prepare( "SELECT filemd5 FROM " . $tablename . " WHERE filepath = %s", $file ) );
			if ( $filemd5 == '' ) { // file is not found in cache so refresh cache and get it
				self::db_clean_files();
				$filemd5 = $wpdb->get_var( $wpdb->prepare( "SELECT filemd5 FROM " . $tablename . " WHERE filepath = %s", $file ) );
			}

			// Insert record in table
			//
			self::db_install_download();
			$tablename = $wpdb->prefix . PSK_S2MSFB_DB_DOWNLOAD_TABLE_NAME;
			$newdata   = array(
				'userid'    => $user_id,
				'useremail' => $user->user_email,
				'ip'        => $ip,
				'filepath'  => $file,
				'filemd5'   => $filemd5,
			);
			$wpdb->insert( $tablename, $newdata );


			// Send email if necessary
			//
			$settings = get_option( PSK_S2MSFB_OPT_SETTINGS_NOTIFY );
			if ( $settings['emailnotify'] == '1' ) {
				$emailfrom = ( $settings['emailfrom'] == '' ) ? PSK_S2MSFB_DEFAULT_EMAIL_DOWNLOAD_FROM : $settings['emailfrom'];
				$emailto   = ( $settings['emailto'] == '' ) ? PSK_S2MSFB_DEFAULT_EMAIL_DOWNLOAD_TO : $settings['emailto'];
				$subject   = ( $settings['subject'] == '' ) ? PSK_S2MSFB_DEFAULT_EMAIL_DOWNLOAD_SUBJECT : $settings['subject'];

				$subject = str_replace( '%blogname%', get_bloginfo( 'name' ), $subject );
				$subject = '=?UTF-8?B?' . base64_encode( $subject ) . '?=';

				$dt = date_i18n( sprintf( '%1$s - %2$s', get_option( 'date_format' ), get_option( 'time_format' ) ) );

				$msg = __( 'A file has been downloaded', PSK_S2MSFB_ID );
				$msg .= '<table>';
				$msg .= '<tr><th align="right">' . __( 'Download Time', PSK_S2MSFB_ID ) . ' : </th><td>' . $dt . '</td></tr>';
				$msg .= '<tr><th align="right">' . __( 'File downloaded', PSK_S2MSFB_ID ) . ' : </th><td>' . htmlentities( $file ) . '</td></tr>';
				$msg .= '<tr><th align="right">' . __( 'User ID', PSK_S2MSFB_ID ) . ' : </th><td>' . $user->ID . '</td></tr>';
				$msg .= '<tr><th align="right">' . __( 'User Login', PSK_S2MSFB_ID ) . ' : </th><td>' . $user->user_login . '</td></tr>';
				$msg .= '<tr><th align="right">' . __( 'User Email', PSK_S2MSFB_ID ) . ' : </th><td>' . htmlentities( $user->user_email ) . '</td></tr>';
				$msg .= '<tr><th align="right">' . __( 'User Nice name', PSK_S2MSFB_ID ) . ' : </th><td>' . htmlentities( $user->user_nicename ) . '</td></tr>';
				$msg .= '<tr><th align="right">' . __( 'User Display name', PSK_S2MSFB_ID ) . ' : </th><td>' . htmlentities( $user->display_name ) . '</td></tr>';
				$msg .= '<tr><th align="right">' . __( 'User IP', PSK_S2MSFB_ID ) . ' : </th><td>' . $ip . '</td></tr>';
				$msg .= '</table>';

				$headers = 'From: ' . $emailfrom . ' <' . $emailfrom . '>' . "\r\n";
				$headers .= 'Sender: ' . $emailfrom . ' <' . $emailfrom . '>' . "\r\n";
				$headers .= "Content-type: text/html; charset=UTF-8;" . "\r\n";

				$tos = explode( ',', $emailto );
				foreach ( $tos as $to ) {
					wp_mail( $to, $subject, $msg, $headers );
				}
			}
		}
	}


	/**
	 * This method install/update the DB Table for downloaded stats
	 *
	 * @return      void
	 */
	public static function db_install_download() {
		/** @var $wpdb WPDB */
		global $wpdb;
		//self::db_uninstall_download();

		$installed_version = get_option( $wpdb->prefix . PSK_S2MSFB_DB_DOWNLOAD_TABLE_VERSION_OPT );
		if ( $installed_version != PSK_S2MSFB_DB_DOWNLOAD_TABLE_VERSION ) {

			$tablename = $wpdb->prefix . PSK_S2MSFB_DB_DOWNLOAD_TABLE_NAME;

			$sql = "CREATE TABLE $tablename (
				id INT(11) NOT NULL AUTO_INCREMENT,
				created TIMESTAMP NOT NULL,
				userid BIGINT(20) NOT NULL,
				useremail VARCHAR(100) NOT NULL,
				ip VARCHAR(100) NOT NULL,
				filepath VARCHAR(4000) NOT NULL,
				filemd5 VARCHAR(32) NOT NULL,
				notified TIMESTAMP,
				PRIMARY KEY  (id)
			) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";

			require( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );

			update_option( $wpdb->prefix . PSK_S2MSFB_DB_DOWNLOAD_TABLE_VERSION_OPT, PSK_S2MSFB_DB_DOWNLOAD_TABLE_VERSION );
		}
	}


	/**
	 * This method is called by cron and will delete all records older than retention setting and will keep only maxcount setting records
	 *
	 * @return      void
	 */
	public static function db_clean_download() {
		/** @var $wpdb WPDB */
		global $wpdb;

		/** @var $tablename $string */
		$tablename = $wpdb->prefix . PSK_S2MSFB_DB_DOWNLOAD_TABLE_NAME;

		$settings  = get_option( PSK_S2MSFB_OPT_SETTINGS_GENERAL );
		$maxcount  = (int) $settings['maxcount'];
		$retention = (int) $settings['retention'];

		if ( $maxcount > 0 ) {
			$sql   = 'SELECT COUNT(*) FROM ' . $tablename;
			$count = $wpdb->get_col( $sql );
			$count = (int) $count[0];
			if ( $count > $maxcount ) {
				$delete = $count - $maxcount;
				$sql    = 'DELETE FROM ' . $tablename . ' ORDER BY created ASC LIMIT ' . $delete;
				$wpdb->query( $sql );
			}
		}

		if ( $retention > 0 ) {
			$sql = 'DELETE FROM ' . $tablename . ' WHERE created < DATE_SUB( NOW(), INTERVAL ' . $retention . ' DAY )';
			$wpdb->query( $sql );
		}

	}


	/**
	 * This method install/update the DB Table for files
	 *
	 * @return      void
	 */
	public static function db_install_files() {
		/** @var $wpdb WPDB */
		global $wpdb;

		//self::db_uninstall_files();

		$installed_version = get_option( $wpdb->prefix . PSK_S2MSFB_DB_FILES_TABLE_VERSION_OPT );
		if ( $installed_version != PSK_S2MSFB_DB_FILES_TABLE_VERSION ) {

			/** @var $tablename $string */
			$tablename = $wpdb->prefix . PSK_S2MSFB_DB_FILES_TABLE_NAME;

			$sql = "CREATE TABLE $tablename (
				id INT(11) NOT NULL AUTO_INCREMENT,
				filepath VARCHAR(4000) NOT NULL,
				filename VARCHAR(4000) NOT NULL,
				filedir VARCHAR(4000) NOT NULL,
				fileext VARCHAR(100) NOT NULL,
				filesize BIGINT(20) NOT NULL,
				filemd5 VARCHAR(32) NOT NULL,
				filemodificationdate TIMESTAMP NOT NULL,
				creationdate TIMESTAMP NOT NULL,
				modificationdate TIMESTAMP NOT NULL,
				lastdate TIMESTAMP NOT NULL,
				PRIMARY KEY  (id),
				FULLTEXT (filepath)
			) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );

			update_option( $wpdb->prefix . PSK_S2MSFB_DB_FILES_TABLE_VERSION_OPT, PSK_S2MSFB_DB_FILES_TABLE_VERSION );
			self::db_clean_files();
		}
	}


	/**
	 * This method parses all files on storage and update files in DB.
	 * It is called by cron and by user when changing files in admin dashboard or when clicking on the button !
	 *
	 * @param bool $return
	 *
	 * @return array
	 */
	/** @noinspection PhpInconsistentReturnPointsInspection */
	public static function db_clean_files( $return = false, $check_md5 = false ) {
		/** @var $wpdb WPDB */
		global $wpdb;

		$start = microtime( true );

		$delete   = array();
		$update   = array();
		$hd_files = self::scan_directory( '/', array(), $check_md5 );
		$count    = count( $hd_files );

		/** @var $tablename $string */
		$tablename = $wpdb->prefix . PSK_S2MSFB_DB_FILES_TABLE_NAME;
		$sql       = 'SELECT id,filepath,filesize,filemd5,filemodificationdate FROM  ' . $tablename;
		$result    = $wpdb->get_results( $sql, ARRAY_A );

		// Find deleted and modified files
		if ( count( $result ) > 0 ) {
			foreach ( $result as $row ) {
				$db_filepath = $row['filepath'];
				if ( array_key_exists( $db_filepath, $hd_files ) ) { // modified
					if ( ( $row['filesize'] != $hd_files[$db_filepath]['s'] ) || ( strtotime( $row['filemodificationdate'] ) != $hd_files[$db_filepath]['m'] ) ) {
						$update[$row['filepath']] = array(
							's' => $hd_files[$db_filepath]['s'],
							'm' => $hd_files[$db_filepath]['m'],
							'h' => md5_file( PSK_S2MSFB_S2MEMBER_FILES_FOLDER . $db_filepath ),
							'i' => (int) $row['id'],
						);
						if ( $check_md5 ) {
							$update[$db_filepath]['h'] = $hd_files[$db_filepath]['h'];
						} else {
							$update[$db_filepath]['h'] = md5_file( PSK_S2MSFB_S2MEMBER_FILES_FOLDER . $db_filepath );
						}

					} else if ( ( $check_md5 ) && ( ( $row['filemd5'] != $hd_files[$db_filepath]['h'] ) ) ) {
						$update[$row['filepath']] = array(
							's' => $hd_files[$db_filepath]['s'],
							'm' => $hd_files[$db_filepath]['m'],
							'h' => $hd_files[$db_filepath]['h'],
							'i' => (int) $row['id'],
						);
					}
					unset( $hd_files[$db_filepath] );
				} else { // deleted
					$delete[$row['filepath']] = array(
						'i' => $row['id'],
					);

				}
			}
		}
		$now = date( 'Y-m-d H:i:s' );

		// Insert files
		foreach ( $hd_files as $filepath => $u ) {
			$wpdb->insert(
				$tablename,
				array(
					'filepath'             => $filepath,
					'filename'             => $u['n'],
					'filedir'              => $u['p'],
					'fileext'              => $u['e'],
					'filesize'             => $u['s'],
					'filemd5'              => md5_file( PSK_S2MSFB_S2MEMBER_FILES_FOLDER . $filepath ),
					'filemodificationdate' => date( 'Y-m-d H:i:s', $u['m'] ),
					'creationdate'         => $now,
					'lastdate'             => $now,
				),
				array( '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
			);
		}

		// Update files
		foreach ( $update as $u ) {
			$wpdb->update(
				$tablename,
				array(
					'filesize'             => $u['s'],
					'filemodificationdate' => date( 'Y-m-d H:i:s', $u['m'] ),
					'filemd5'              => $u['h'],
					'modificationdate'     => $now,
					'lastdate'             => $now,
				),
				array( 'id' => $u['i'] ),
				array( '%s' ),
				array( '%d' )
			);
		}

		// Delete files
		foreach ( $delete as $u ) {
			$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $tablename . ' WHERE id=%s', $u['i'] ) );
		}

		update_option( PSK_S2MSFB_DB_FILES_CLEAN_OPT, time() );
		update_option( PSK_S2MSFB_DB_FILES_CLEAN_COUNT_OPT, $count );
		update_option( PSK_S2MSFB_DB_FILES_CLEAN_DURATION_OPT, (int) ( microtime( true ) - $start ) );

		if ( $return )
			return array( $hd_files, $update, $delete );
	}


	/**
	 * Scan files and directories in the file system
	 *
	 * @param       $path
	 * @param array $result
	 * @param       $check_md5
	 *
	 * @return array
	 */
	private static function scan_directory( $path, $result = array(), $check_md5 ) {

		$files = scandir( PSK_S2MSFB_S2MEMBER_FILES_FOLDER . $path );

		foreach ( $files as $file ) {

			$filepath = $path . $file;
			$fullpath = PSK_S2MSFB_S2MEMBER_FILES_FOLDER . $filepath;

			if ( in_array( $file, array( '.', '..', '.DS_Store', 'Thumb.db' ) ) )
				continue;

			// Remove all . .. and hidden files if option is not set
			if ( ! file_exists( $fullpath ) )
				continue;

			if ( is_dir( $fullpath ) ) {
				$result = self::scan_directory( $filepath . '/', $result, $check_md5 );
			} else {
				$result[$filepath] = array(
					'p' => $path,
					'n' => $file,
					's' => filesize( $fullpath ),
					'm' => filemtime( $fullpath ),
					'e' => mb_strtolower( preg_replace( '/^.*\./', '', $file ) ),
				);
				if ( $check_md5 ) {
					$result[$filepath]['h'] = md5_file( $fullpath );
				}
			}
		}

		return $result;
	}


	/**
	 * This method uninstall the DB Table for downloaded stats
	 *
	 * @return      void
	 */
	public static function db_uninstall_download() {
		/** @var $wpdb WPDB */
		global $wpdb;

		delete_option( $wpdb->prefix . PSK_S2MSFB_DB_DOWNLOAD_TABLE_VERSION_OPT );

		/** @var $tablename $string */
		$tablename = $wpdb->prefix . PSK_S2MSFB_DB_DOWNLOAD_TABLE_NAME;
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $tablename );
	}


	/**
	 * This method uninstall the DB Table for downloaded stats
	 *
	 * @return      void
	 */
	public static function db_uninstall_files() {
		/** @var $wpdb WPDB */
		global $wpdb;

		delete_option( $wpdb->prefix . PSK_S2MSFB_DB_FILES_TABLE_VERSION_OPT );

		$tablename = $wpdb->prefix . PSK_S2MSFB_DB_FILES_TABLE_NAME;
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $tablename );
	}


	/**
	 * This method calls all database installation methods
	 *
	 * @return      void
	 */
	public static function db_check_install() {
		self::db_install_download();
		self::db_install_files();
	}


	/**
	 * This method calls all database uninstallation methods
	 *
	 * @return      void
	 */
	public static function db_uninstall() {
		self::db_uninstall_download();
		self::db_uninstall_files();
	}


}

PSK_S2MSFB::init();

