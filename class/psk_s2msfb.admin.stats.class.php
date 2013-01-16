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

class PSK_S2MSFBAdminStats
{
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
		wp_register_script('jquery.tablesorter', PSK_S2MSFB_JS_URL . 'jquery.tablesorter.min.js', array('jquery'), false,true);
		wp_enqueue_script('jquery.tablesorter');
		wp_enqueue_script('jquery.tablesorter.widgets', PSK_S2MSFB_JS_URL . 'jquery.tablesorter.widgets.min.js', array('jquery','jquery.tablesorter'), false,true);
		wp_enqueue_script('jquery.tablesorter.pager', PSK_S2MSFB_JS_URL . 'jquery.tablesorter.pager.js', array('jquery','jquery.tablesorter'), false,true);
        wp_enqueue_style('jquery.tablesorter.pager', PSK_S2MSFB_CSS_URL . 'jquery.tablesorter.pager.css' );
        wp_enqueue_style('theme.bootstrap', PSK_S2MSFB_CSS_URL . 'theme.bootstrap.css' );

		wp_enqueue_script(PSK_S2MSFB_ID.'.admin.stats', PSK_S2MSFB_JS_URL . 'admin.stats.js', array('jquery','jquery.tablesorter'), false,true);
		wp_localize_script(PSK_S2MSFB_ID.'.admin.stats', 'objectL10n', array(
			'erroroccurs'            	=> __('An error occurs',PSK_S2MSFB_ID),
			'error'						=> _x('Error!','alertbox',PSK_S2MSFB_ID),
			'success'					=> _x('Success!','alertbox',PSK_S2MSFB_ID),
			'info'						=> _x('Info!','alertbox',PSK_S2MSFB_ID),
			'warning'					=> _x('Warning!','alertbox',PSK_S2MSFB_ID),
		));
	}


	/**
	 * Admin Screen : Stats > All downloads
	 *
	 * @return 			void
	 */
	public static function admin_screen_stats_all() {
		echo PSK_S2MSFBAdmin::get_admin_header(__METHOD__);

		global $wpdb;
		$tablename = $wpdb->prefix . PSK_S2MSFB_DB_DOWNLOAD_TABLE_NAME;
		$sql       = "SELECT userid,useremail,ip,UNIX_TIMESTAMP(created),filepath FROM $tablename ORDER BY created DESC";
		$result    = $wpdb->get_results( $sql , ARRAY_A );

		foreach (get_users() as $user)
			$users[$user->ID] = $user->display_name;


		if (count($result)==0) {
			echo '<div class="alert alert-error">' . __("No download",S2MSFB_ID) . '</div>';
		}
		else {
			echo '<table class="table sort table-bordered table-hover table-condensed">';
			echo '<thead><tr>';
			echo '  <th>' . __('When',PSK_S2MSFB_ID) . '</th>';
			echo '  <th>' . __('File',PSK_S2MSFB_ID) . '</th>';
			echo '  <th class="filter-select filter-exact" data-placeholder="Select user">' . __('User',PSK_S2MSFB_ID) . '</th>';
			echo '  <th>' . __('IP Address',PSK_S2MSFB_ID) . '</th>';
			echo '</tr></thead>';
			echo '<tfoot>';
			echo '  <tr>';
			echo '    <th>' . __('When',PSK_S2MSFB_ID) . '</th>';
			echo '    <th>' . __('File',PSK_S2MSFB_ID) . '</th>';
			echo '    <th>' . __('User',PSK_S2MSFB_ID) . '</th>';
			echo '    <th>' . __('IP Address',PSK_S2MSFB_ID) . '</th>';
			echo '  </tr>';
			echo '  <tr><th colspan="4" class="pager form-horizontal">';
			echo '    <button class="reset btn btn-mini btn-primary" data-column="0" data-filter=""><i class="icon-white icon-refresh"></i> Reset filters</button>';
			echo '    <div class="pull-right">';
			echo '      <button class="btn btn-mini first"><i class="icon-step-backward"></i></button>';
			echo '      <button class="btn btn-mini prev"><i class="icon-arrow-left"></i></button>';
			echo '      <span class="pagedisplay"></span> <!-- this can be any element, including an input -->';
			echo '      <button class="btn btn-mini next"><i class="icon-arrow-right"></i></button>';
			echo '      <button class="btn btn-mini last"><i class="icon-step-forward"></i></button>';
			echo '      <select class="pagesize" title="Select page size">';
			echo '      	<option selected="selected" value="10">10</option>';
			echo '      	<option value="20">20</option>';
			echo '      	<option value="30">30</option>';
			echo '      	<option value="40">40</option>';
			echo '      </select>';
			echo '      <select class="pagenum input-mini" title="Select page number"></select>';
			echo '    </div>';
			echo '  </th></tr>';
			echo '</tfoot>';
			echo '<tbody>';
			foreach($result as $row) {
				$time = (int)$row['UNIX_TIMESTAMP(created)'];
				$dt   = date_i18n( sprintf( '%1$s - %2$s', get_option('date_format'), get_option('time_format') ) , $time);

				if (isset($users[$row['userid']])) {
					$user      = $users[$row['userid']];
					$userclass = '';
				} else {
					$user      = $row['useremail'].' - #'.$row['userid'];
					$userclass = ' class="deleted"';
				}

				echo '<tr>';
				echo '  <td data-t="' . $time . '">' 	. $dt 				. '</td>';
				echo '  <td>' 							. $row['filepath'] 	. '</td>';
				echo '  <td' . $userclass . '>' 		. $user 			. '</td>';
				echo '  <td>' 							. $row['ip'] 		. '</td>';
				echo '</tr>';
			}
			echo '</tbody>';
			echo '</table>';
		}

		echo PSK_S2MSFBAdmin::get_admin_footer();
	}



	/**
	 * Admin Screen : Stats > s2member current logs
	 *
	 * @return 			void
	 */
	public static function admin_screen_stats_log() {
		echo PSK_S2MSFBAdmin::get_admin_header(__METHOD__);

		$s2list     = array('log');	// log and/or arc
		$down['ti'] = array();
		$down['ui'] = array();
		$down['fn'] = array();
		$users      = array();

		foreach (get_users() as $user) {
            $user = new WP_User ($user->ID);
			$user_downloads = c_ws_plugin__s2member_files::user_downloads($user);
			foreach ($s2list as $type) {
				if (isset($user_downloads[$type])) {
					foreach ($user_downloads[$type] as $dl) {
						$down['ui'][] = $user->ID;
						$down['fn'][] = $dl['file'];
						$down['ti'][] = $dl['time'];
					}
				}
			}
        }

		if (count($down['ti'])==0) {
			echo '<div class="alert alert-error">' . __("No current download",S2MSFB_ID) . '</div>';
		}
		else {
			foreach (get_users() as $user)
				$users[$user->ID] = $user->display_name;

			array_multisort($down['ti'],SORT_DESC,$down['ui'],$down['fn']);

			echo '<table class="table sort table-bordered table-hover table-condensed">';
			echo '<thead><tr>';
			echo '  <th>' . __('When',PSK_S2MSFB_ID) . '</th>';
			echo '  <th>' . __('What',PSK_S2MSFB_ID) . '</th>';
			echo '  <th class="filter-select filter-exact" data-placeholder="Select user">' . __('Who',PSK_S2MSFB_ID) . '</th>';
			echo '</tr></thead>';
			echo '<tfoot>';
			echo '  <tr>';
			echo '    <th>' . __('When',PSK_S2MSFB_ID) . '</th>';
			echo '    <th>' . __('What',PSK_S2MSFB_ID) . '</th>';
			echo '    <th>' . __('Who',PSK_S2MSFB_ID) . '</th>';
			echo '  </tr>';
			echo '  <tr><th colspan="3" class="pager form-horizontal">';
			echo '    <button class="reset btn btn-mini btn-primary" data-column="0" data-filter=""><i class="icon-white icon-refresh"></i> Reset filters</button>';
			echo '    <div class="pull-right">';
			echo '      <button class="btn btn-mini first"><i class="icon-step-backward"></i></button>';
			echo '      <button class="btn btn-mini prev"><i class="icon-arrow-left"></i></button>';
			echo '      <span class="pagedisplay"></span> <!-- this can be any element, including an input -->';
			echo '      <button class="btn btn-mini next"><i class="icon-arrow-right"></i></button>';
			echo '      <button class="btn btn-mini last"><i class="icon-step-forward"></i></button>';
			echo '      <select class="pagesize" title="Select page size">';
			echo '      	<option selected="selected" value="10">10</option>';
			echo '      	<option value="20">20</option>';
			echo '      	<option value="30">30</option>';
			echo '      	<option value="40">40</option>';
			echo '      </select>';
			echo '      <select class="pagenum input-mini" title="Select page number"></select>';
			echo '    </div>';
			echo '  </th></tr>';
			echo '</tfoot>';
			echo '<tbody>';
			foreach($down['ti'] as $key=>$time) {
				$dt = date_i18n( sprintf( '%1$s - %2$s', get_option('date_format'), get_option('time_format') ) , (int)$time );
				$du = $users[$down['ui'][$key]];
				echo '<tr>';
				echo '  <td data-t="' . $time . '">' . $dt . '</td>';
				echo '  <td>' . $down['fn'][$key] . '</td>';
				echo '  <td>' . $du .'</td>';
				echo '</tr>';
			}
			echo '</tbody>';
			echo '</table>';
		}

		echo PSK_S2MSFBAdmin::get_admin_footer();
	}



}

PSK_S2MSFBAdminStats::init();

