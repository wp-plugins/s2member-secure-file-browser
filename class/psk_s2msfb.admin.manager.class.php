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


class PSK_S2MSFBAdminManager
{

	public static $shortcode_options = array();


	/**
	* Initialization
	*
	* @return void
	*/
	public static function init()
	{
 		add_action('admin_enqueue_scripts' 				  							, array(__CLASS__,'init_assets'));
 		add_action('admin_init' 													, array(__CLASS__,'admin_init'));
	}


	/**
	 * Initialization
	 * @wp_action 		init
	 */
	public static function admin_init()
	{
	}


	/**
	 * Load javascript and css for Public and Admin part
	 * @wp_action 		admin_enqueue_scripts
	 * @wp_action 		wp_enqueue_scripts
	 */
	public static function init_assets()
	{
		wp_enqueue_script('jquery.tablesorter', PSK_S2MSFB_JS_URL . 'jquery.tablesorter.min.js', array('jquery'), false,true);
		wp_enqueue_script('jquery.tablesorter.widgets', PSK_S2MSFB_JS_URL . 'jquery.tablesorter.widgets.min.js', array('jquery','jquery.tablesorter'), false,true);

        wp_enqueue_style('jquery.tablesorter.pager', PSK_S2MSFB_CSS_URL . 'jquery.tablesorter.pager.css' );
		wp_enqueue_script('jquery.tablesorter.pager', PSK_S2MSFB_JS_URL . 'jquery.tablesorter.pager.js', array('jquery','jquery.tablesorter'), false,true);

        wp_enqueue_style('theme.bootstrap', PSK_S2MSFB_CSS_URL . 'theme.bootstrap.css' );

		wp_enqueue_script(PSK_S2MSFB_ID.'.admin.manager', PSK_S2MSFB_JS_URL . 'admin.manager.js', array('jquery','jquery.tablesorter'), false,true);
	}



	public static function init_shortcode_options() {
		self::$shortcode_options = array(
			array(
				'name'     => 'dirbase',
				'desc'     => __('initial directory from the s2member-files directory',PSK_S2MSFB_ID),
				'descm'    => '',
				'default'  => '/',
				'defaultm' => '',
				'more'     => '',
			),
			array(
				'name'     => 'hidden',
				'desc'     => __('show hidden files or not',PSK_S2MSFB_ID),
				'descm'    => '',
				'default'  => '0',
				'defaultm' => __('do not show hidden files',PSK_S2MSFB_ID),
				'more'     => __('set to <code>1</code> to display',PSK_S2MSFB_ID),
			),
			array(
				'name'     => 'dirfirst',
				'desc'     => __('show directories above files',PSK_S2MSFB_ID),
				'descm'    => '',
				'default'  => '1',
				'defaultm' => __('show directories first',PSK_S2MSFB_ID),
				'more'     => __('set to <code>0</code> to display directories with files',PSK_S2MSFB_ID),
			),
			array(
				'name'     => 'names',
				'desc'     => __('replace files name with custom values',PSK_S2MSFB_ID),
				'descm'    => __('Syntax : <code>realfilename_1:Custom File Name #1|...|realfilename_n:Custom File Name #n</code>',PSK_S2MSFB_ID),
				'default'  => '',
				'defaultm' => '',
				'more'     => __('<code>access-s2member-level#</code> will be automatically renamed with your s2member level custom labels.',PSK_S2MSFB_ID),
			),
			array(
				'name'     => 'folderevent',
				'desc'     => __('event to trigger expand/collapse',PSK_S2MSFB_ID),
				'descm'    => '',
				'default'  => 'click',
				'defaultm' => __('user has to click to toggle directories, download files, ...',PSK_S2MSFB_ID),
				'more'     => __('can be any javascript event like <code>mouseover</code>, ...',PSK_S2MSFB_ID),
			),
			array(
				'name'     => 'expandspeed',
				'desc'     => __('speed of the expand folder action in ms',PSK_S2MSFB_ID),
				'descm'    => '',
				'default'  => '500',
				'defaultm' => '',
				'more'     => __('use <code>-1</code> for no animation',PSK_S2MSFB_ID),
			),
			array(
				'name'     => 'expandeasing',
				'desc'     => __('easing function to use on expand',PSK_S2MSFB_ID),
				'descm'    => '',
				'default'  => 'swing',
				'defaultm' => '',
				'more'     => __('can be set to <code>linear</code>',PSK_S2MSFB_ID),
			),
			array(
				'name'     => 'collapsespeed',
				'desc'     => __('speed of the collapse folder action in ms',PSK_S2MSFB_ID),
				'descm'    => '',
				'default'  => '500',
				'defaultm' => '',
				'more'     => __('use <code>-1</code> for no animation',PSK_S2MSFB_ID),
			),
			array(
				'name'     => 'collapseeasing',
				'desc'     => __('easing function to use on collapse',PSK_S2MSFB_ID),
				'descm'    => '',
				'default'  => 'swing',
				'defaultm' => '',
				'more'     => __('can be set to <code>linear</code>',PSK_S2MSFB_ID),
			),
			array(
				'name'     => 'multifolder',
				'desc'     => __('whether or not to limit the browser to one subfolder at a time',PSK_S2MSFB_ID),
				'descm'    => '',
				'default'  => '1',
				'defaultm' => '',
				'more'     => __('set to <code>0</code> to display only one open directory at a time',PSK_S2MSFB_ID),
			),
			array(
				'name'     => 'openrecursive',
				'desc'     => __('whether or not to open all subdirectories when opening a directory',PSK_S2MSFB_ID),
				'descm'    => '',
				'default'  => '0',
				'defaultm' => __('user has to open directories himself',PSK_S2MSFB_ID),
				'more'     => __('set to <code>1</code> to open recursively subdirectories when opening a directory (then all directories will be open at initialization)',PSK_S2MSFB_ID),
			),
			array(
				'name'     => 'filterdir',
				'desc'     => __('a full regexp directories have to match to be displayed',PSK_S2MSFB_ID),
				'descm'    => __('Syntax available here',PSK_S2MSFB_ID) . ' <a href="http://www.php.net/manual/en/pcre.pattern.php">http://www.php.net/manual/en/pcre.pattern.php</a>' . '<br/>' . __('<code>preg_match</code> PHP function is used',PSK_S2MSFB_ID),
				'default'  => '',
				'defaultm' => '',
				'more'     => __('eg: <code>/(access|user)/i</code>',PSK_S2MSFB_ID),
			),
			array(
				'name'     => 'filterfile',
				'desc'     => __('a full regexp files have to match to be displayed',PSK_S2MSFB_ID),
				'descm'    => __('Syntax available here',PSK_S2MSFB_ID) . ' <a href="http://www.php.net/manual/en/pcre.pattern.php">http://www.php.net/manual/en/pcre.pattern.php</a>' . '<br/>' . __('<code>preg_match</code> PHP function is used',PSK_S2MSFB_ID),
				'default'  => '',
				'defaultm' => '',
				'more'     => __('eg: <code>/\.(png|jpe?g|gif|zip)$/i</code>',PSK_S2MSFB_ID),
			),
		);
	}


	/**
	 * Admin Screen Manager > Browser
	 *
	 * @return 			void
	 */
	public static function admin_screen_manager_browse() {
		wp_localize_script(PSK_S2MSFB_ID.'.admin.manager', 'objectL10n', array(
			'xdebugerror'            	=> __('It seems you have xebug installed and try to delete a very deep directory.',PSK_S2MSFB_ID),
			'erroroccurs'            	=> __('An error occurs',PSK_S2MSFB_ID),
			'pleasewait'             	=> __('Please wait...',PSK_S2MSFB_ID),
			'renamedirectory'        	=> __('Rename Directory',PSK_S2MSFB_ID),
			'renamefile'             	=> __('Rename File',PSK_S2MSFB_ID),
			'rename'                 	=> __('Rename',PSK_S2MSFB_ID),
			'removedirectorywarning' 	=> __('Directory and all children will be deleted.<br/>You can not undo this action.',PSK_S2MSFB_ID),
			'removefilewarning'      	=> __('File will be deleted.<br/>You can not undo this action.',PSK_S2MSFB_ID),
			'remove'                 	=> __('Delete',PSK_S2MSFB_ID),
			'removedirectory'        	=> __('Delete Directory',PSK_S2MSFB_ID),
			'removefile'             	=> __('Delete File',PSK_S2MSFB_ID),
			'renamefileok'				=> __('File has been successfully renamed',PSK_S2MSFB_ID),
			'renamedirectoryok'			=> __('Directory has been successfully renamed',PSK_S2MSFB_ID),
			'removefileok'				=> __('File has been successfully deleted',PSK_S2MSFB_ID),
			'removedirectoryok'			=> __('Directory has been successfully deleted',PSK_S2MSFB_ID),
			'error'						=> _x('Error!','alertbox',PSK_S2MSFB_ID),
			'success'					=> _x('Success!','alertbox',PSK_S2MSFB_ID),
			'info'						=> _x('Info!','alertbox',PSK_S2MSFB_ID),
			'warning'					=> _x('Warning!','alertbox',PSK_S2MSFB_ID),
		));

		echo PSK_S2MSFBAdmin::get_admin_header(__METHOD__);
		echo PSK_S2MSFB::shortcode_s2member_secure_files_browser(array(
			"loadmessage"   => __("Please wait while loading...",PSK_S2MSFB_ID),
			"openrecursive" => "0",
			"hidden"        => "1"
		));
		echo PSK_S2MSFBAdmin::get_admin_footer();
	}


	/**
	 * Admin Screen : Manager > Documentation > Shortcode
	 *
	 * @return 			void
	 */
	public static function admin_screen_manager_docshortcode() {
		echo PSK_S2MSFBAdmin::get_admin_header(__METHOD__);

		echo '<table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>' . __('Tag',PSK_S2MSFB_ID) . '</th>
                <th>' . __('Description',PSK_S2MSFB_ID) . '</th>
                <th>' . __('Default value',PSK_S2MSFB_ID) . '</th>
                <th>' . __('Comment',PSK_S2MSFB_ID) . '</th>
              </tr>
            </thead>
            <tbody>';

		self::init_shortcode_options();

		foreach (self::$shortcode_options as $option) {
            echo '<tr>
              <td><code>' . $option['name'] . '</code></td>
              <td>' . $option['desc'] . '<br/>' . $option['descm'] . '</td>
              <td><code>'.$option['default'] . '</code><br/><em class="muted">' . $option['defaultm'] . '</em></td>
              <td>' . $option['more'] . '</td>
            </tr>';
		}

		echo '</tbody></table>';

		echo PSK_S2MSFBAdmin::get_admin_footer();
	}



	/**
	 * Admin Screen : Manager > Tools > Shortcode generator
	 *
	 * @return 			void
	 */
	public static function admin_screen_manager_shortcodegenerator() {

		self::init_shortcode_options();
		$tags = array();
		foreach (self::$shortcode_options as $option) $tags[] = $option['name'];

		wp_localize_script(PSK_S2MSFB_ID.'.admin.manager', 'objectL10n', array(
			'shortcode'             => PSK_S2MSFB_SHORTCODE_NAME_0,
			'shortcodetags'        	=> implode(',',$tags),
			'error'					=> _x('Error!','alertbox',PSK_S2MSFB_ID),
			'success'				=> _x('Success!','alertbox',PSK_S2MSFB_ID),
			'info'					=> _x('Info!','alertbox',PSK_S2MSFB_ID),
			'warning'				=> _x('Warning!','alertbox',PSK_S2MSFB_ID),
		));

		echo PSK_S2MSFBAdmin::get_admin_header(__METHOD__);

		echo '<table class="table table-bordered table-striped table-condensed">
            <thead>
              <tr>
                <th>' . __('Tag',PSK_S2MSFB_ID) . '</th>
                <th>' . __('Description',PSK_S2MSFB_ID) . '</th>
                <th>' . __('Value',PSK_S2MSFB_ID) . '</th>
              </tr>
            </thead>
            <tbody>';

		foreach (self::$shortcode_options as $option) {
			$tagname 	= $option['name'];
			$default 	= $option['default'];
			$currentval = $default;
			$control    = '<div class="control-group" id="cg' . $tagname . '">';


			switch ($tagname) {

				case 'dirfirst':
				case 'hidden':
				case 'multifolder':
				case 'openrecursive':
					$checked1 = ($default=="1") ? ' checked="checked"' : '';
					$checked0 = ($default!="1") ? ' checked="checked"' : '';
					$control.= '
					<label class="radio inline">
					  <input class="generator" type="radio" name="' . $tagname . '" id="' . $tagname . '1" value="1"' . $checked1 . '/>' . __( 'Yes' , PSK_S2MSFB_ID ) . '
					</label>
					<label class="radio inline">
					  <input class="generator" type="radio" name="' . $tagname . '" id="' . $tagname . '2" value="0"' . $checked0 . '/>' . __( 'No' , PSK_S2MSFB_ID ) . '
					</label>';
					break;

				case 'folderevent':
					$values = array('blur','click','dblclick','focus','focusin','hover','keydown','keypress','keyup','mousedown','mouseenter','mouseleave','mousemove','mouseout','mouseover','mouseup');
					$control.= '<select class="generator" name="' . $tagname . '" id="' . $tagname . '">';
					foreach ($values as $value) {
						$control.= '<option value="' . $value . '"';
						if ($currentval==$value) $control.= ' selected="selected"';
						$control.= '>' . $value . '</option>';
					}
					$control.= '</select>';
					break;

				case 'collapseeasing':
				case 'expandeasing':
					$values = array('linear','swing');
					$control.= '<select class="generator" name="' . $tagname . '" id="' . $tagname . '">';
					foreach ($values as $value) {
						$control.= '<option value="' . $value . '"';
						if ($currentval==$value) $control.= ' selected="selected"';
						$control.= '>' . $value . '</option>';
					}
					$control.= '</select>';
					break;

				case 'names':
					for ($i=0; $i<5; $i++) {
						$control.= '<label class="control-label inline" for="' . $tagname . $i . '">' . constant('PSK_S2MSFB_S2MEMBER_LEVEL' . $i . '_FOLDER') . ' : ' ;
						$control.= '  <input id="h' . $tagname . $i . '" name="h' . $tagname . $i . '" type="hidden" value="' . esc_attr( constant('PSK_S2MSFB_S2MEMBER_LEVEL' . $i . '_FOLDER') ) . '" />';
						$control.= '  <input class="generator" id="' . $tagname . $i . '" name="' . $tagname . $i . '" type="text" value="' . esc_attr( $currentval ) . '" placeholder="' . esc_attr( constant('PSK_S2MSFB_S2MEMBER_LEVEL' . $i . '_FOLDER') ) . '" />';
						$control.= '</label>';
					}
					$control.= '<label class="control-label inline" for="' . $tagname . $i . '">' . PSK_S2MSFB_S2MEMBER_CCAP_FOLDER . 'videos' . ' : ' ;
					$control.= '  <input id="h' . $tagname . $i . '" name="h' . $tagname . $i . '" type="hidden" value="' . esc_attr( PSK_S2MSFB_S2MEMBER_CCAP_FOLDER . 'videos' ) . '" />';
					$control.= '  <input class="generator" id="' . $tagname . $i . '" name="' . $tagname . $i . '" type="text" value="' . esc_attr( $currentval ) . '" placeholder="' . esc_attr( PSK_S2MSFB_S2MEMBER_CCAP_FOLDER . 'videos' ) . '" />';
					$control.= '</label>';
					break;

				default:
					$control.= '<input class="generator" id="' . $tagname . '" name="' . $tagname . '" type="text" value="' . esc_attr( $currentval ) . '" placeholder="' . esc_attr( $default ) . '" />';
					break;
			}

			$control.= '</div>';

            echo '<tr>
              <td><code>' . $tagname . '</code></td>
              <td>' . $option['desc'] . '</td>
              <td>' . $control . '</td>
            </tr>';
		}

		echo '</tbody></table>';

		echo '<h5>' . __('Copy paste this shortcode in pages :',PSK_S2MSFB_ID) . '</h5>';
		echo '<pre id="shortcode_preview"></pre>';

		echo PSK_S2MSFBAdmin::get_admin_footer();
	}


	/**
	 * Ajax call - Returns a directory as a html structure
	 * We do not call PSK_S2MSFB because we have to set before $is_admin to true
	 *
	 * @return          die
	 */
	public static function ajax_admin_get_directory() {
		PSK_S2MSFB::set_is_admin(true);
		PSK_S2MSFB::ajax_do_get_directory();
		die();
	}


	/**
	 * Ajax call - Delete a file or directory
	 *
	 * @return          die
	 */
	public static function ajax_admin_delete_file() {

		if (!isset($_POST['nonce']) || !check_ajax_referer(PSK_S2MSFB_ID.'-nonce','nonce',false))
			die ('Invalid nonce');

		if (!isset($_POST['s']))
			die ('invalid parameters');

		$current = PSK_S2MSFB_S2MEMBER_FILES_FOLDER . stripslashes( rawurldecode( @$_POST['s'] ) );
		if (!PSK_Tools::is_directory_allowed($current))
			die('Forbidden');

		PSK_Tools::rm_secure_recursive($current);
		die('1');
	}


	/**
	 * Ajax call - Rename a file or directory
	 *
	 * @return          die
	 */
	public static function ajax_admin_rename_file() {

		if (!isset($_POST['nonce']) || !check_ajax_referer(PSK_S2MSFB_ID.'-nonce','nonce',false))
			die ('Invalid nonce');

		if (!isset($_POST['s']))
			die ('Invalid parameters');

		if (!isset($_POST['d']))
			die ('Invalid parameters');

		$current     = PSK_S2MSFB_S2MEMBER_FILES_FOLDER . stripslashes( rawurldecode( @$_POST['s'] ) );
		if (!PSK_Tools::is_directory_allowed($current))
			die('Forbidden');

		$destination = dirname($current). DIRECTORY_SEPARATOR .str_replace(array('\\','/',':'),array('_','_','_'),stripslashes(rawurldecode($_POST['d'])));
		rename($current,$destination);
		die('1');
	}


}

PSK_S2MSFBAdminManager::init();

