<?php
define(	'PSK_S2MSFB_VERSION'  							, '0.3');


/*
 *	Global constants
 */
define(	'PSK_S2MSFB_NAME'     							, 's2member Secure File Browser');
define(	'PSK_S2MSFB_MENUNAME' 							, 'Secure File Browser');
define(	'PSK_S2MSFB_ID'       							, 'psk_s2msfb');
define(	'PSK_S2MSFB_SHORTCODE_NAME_0'					, 's2member_secure_files_browser'	);
define(	'PSK_S2MSFB_SHORTCODE_NAME_1'					, 's2membersecurefilesbrowser'		);
define(	'PSK_S2MSFB_SHORTCODE_NAME_2'					, 's2member_files_browser'			);
define(	'PSK_S2MSFB_SHORTCODE_NAME_3'					, 's2memberfilesbrowser'			);
define(	'PSK_S2MSFB_SHORTCODE_NAME_4'					, 's2member_secure_file_browser'	);
define(	'PSK_S2MSFB_SHORTCODE_NAME_5'					, 's2membersecurefilebrowser'		);
define(	'PSK_S2MSFB_SHORTCODE_NAME_6'					, 's2member_file_browser'			);
define(	'PSK_S2MSFB_SHORTCODE_NAME_7'					, 's2memberfilebrowser'				);
define(	'PSK_S2MSFB_SHORTCODE_NAME_8'					, 's2msfb'							);


/*
 *	s2Member constants
 */
define( 'PSK_S2MSFB_S2MEMBER_CCAP_FOLDER'   			, 'access-s2member-ccap-' );
define( 'PSK_S2MSFB_S2MEMBER_CCAP_RIGHTS'   			, 'access_s2member_ccap_' );
define( 'PSK_S2MSFB_S2MEMBER_LEVEL0_FOLDER' 			, 'access-s2member-level0' );
define( 'PSK_S2MSFB_S2MEMBER_LEVEL1_FOLDER' 			, 'access-s2member-level1' );
define( 'PSK_S2MSFB_S2MEMBER_LEVEL2_FOLDER' 			, 'access-s2member-level2' );
define( 'PSK_S2MSFB_S2MEMBER_LEVEL3_FOLDER' 			, 'access-s2member-level3' );
define( 'PSK_S2MSFB_S2MEMBER_LEVEL4_FOLDER' 			, 'access-s2member-level4' );
define( 'PSK_S2MSFB_S2MEMBER_LEVEL0_RIGHTS'				, 'access_s2member_level0' );
define( 'PSK_S2MSFB_S2MEMBER_LEVEL1_RIGHTS'				, 'access_s2member_level1' );
define( 'PSK_S2MSFB_S2MEMBER_LEVEL2_RIGHTS'				, 'access_s2member_level2' );
define( 'PSK_S2MSFB_S2MEMBER_LEVEL3_RIGHTS'				, 'access_s2member_level3' );
define( 'PSK_S2MSFB_S2MEMBER_LEVEL4_RIGHTS'				, 'access_s2member_level4' );


/*
 *	File paths
 */
define(	'PSK_S2MSFB_ADMIN_CLASS_FILE_BASE'  			, 'psk_s2msfb.admin');
define( 'PSK_S2MSFB_S2MEMBER_FILES_FOLDER'				, WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 's2member-files' . DIRECTORY_SEPARATOR );
define( 'PSK_S2MSFB_PLUGIN_FOLDER'						, dirname(PSK_S2MSFB_PLUGIN_FILE) . DIRECTORY_SEPARATOR);
define( 'PSK_S2MSFB_CLASSES_FOLDER'						, PSK_S2MSFB_PLUGIN_FOLDER . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR );
define( 'PSK_S2MSFB_INCLUDES_FOLDER'					, PSK_S2MSFB_PLUGIN_FOLDER . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR );


/*
 *	Url paths
 */
define( 'PSK_S2MSFB_PLUGIN_URL'			    			, plugin_dir_url(PSK_S2MSFB_PLUGIN_FILE));
define( 'PSK_S2MSFB_CSS_URL'							, PSK_S2MSFB_PLUGIN_URL . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR );
define( 'PSK_S2MSFB_JS_URL'				  				, PSK_S2MSFB_PLUGIN_URL . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR );


/*
 *	Default rights
 */
if( is_multisite() )
	define( 'PSK_S2MSFB_ADMIN_HOME_ACCESS'				, 'manage_options' );
else
	define( 'PSK_S2MSFB_ADMIN_HOME_ACCESS'				, 'manage_options' );


/*
 *	Options
 */
define( 'PSK_S2MSFB_OPT_SETTINGS_GENERAL'				, PSK_S2MSFB_ID.'_general' );
define( 'PSK_S2MSFB_OPT_SETTINGS_NOTIFY'				, PSK_S2MSFB_ID.'_notification' );


/*
 *	Options default values
 */
define( 'PSK_S2MSFB_DEFAULT_EMAIL_DOWNLOAD_SUBJECT' 	, __('%blogname% : file downloaded',PSK_S2MSFB_ID) );
define( 'PSK_S2MSFB_DEFAULT_EMAIL_DOWNLOAD_FROM' 		, get_option('admin_email') );
define( 'PSK_S2MSFB_DEFAULT_EMAIL_DOWNLOAD_TO'   		, get_option('admin_email') );


/*
 *	Database
 */
define( 'PSK_S2MSFB_DB_DOWNLOAD_TABLE_NAME'   			, PSK_S2MSFB_ID.'_downloads' );
define( 'PSK_S2MSFB_DB_DOWNLOAD_TABLE_VERSION_OPT'		, PSK_S2MSFB_DB_DOWNLOAD_TABLE_NAME.'_version' );
define( 'PSK_S2MSFB_DB_DOWNLOAD_TABLE_VERSION'  		, 1 );









