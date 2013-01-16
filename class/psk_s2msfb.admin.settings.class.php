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


class PSK_S2MSFBAdminSettings
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
		wp_enqueue_script(PSK_S2MSFB_ID.'.admin.settings', PSK_S2MSFB_JS_URL . 'admin.settings.js', array('jquery','jquery.tablesorter'), false,true);
		wp_localize_script(PSK_S2MSFB_ID.'.admin.settings', 'objectL10n', array(
			'erroroccurs'            	=> __('An error occurs',PSK_S2MSFB_ID),
			'error'						=> _x('Error!','alertbox',PSK_S2MSFB_ID),
			'success'					=> _x('Success!','alertbox',PSK_S2MSFB_ID),
			'info'						=> _x('Info!','alertbox',PSK_S2MSFB_ID),
			'warning'					=> _x('Warning!','alertbox',PSK_S2MSFB_ID),
		));
	}

	/**
	 * Admin Screen : Stats > General
	 *
	 * @return 			void
	 */
	public static function admin_screen_settings_main() {

		$days = array(
			0	=>	__('Do not delete'	, PSK_S2MSFB_ID ),
			7	=>	__('Keep 1 week'	, PSK_S2MSFB_ID ),
			31	=>	__('Keep 1 month'	, PSK_S2MSFB_ID ),
			93	=>	__('Keep 3 months'	, PSK_S2MSFB_ID ),
			186	=>	__('Keep 6 months'	, PSK_S2MSFB_ID ),
			365	=>	__('Keep 1 year'	, PSK_S2MSFB_ID ),
			730	=>	__('Keep 2 years'	, PSK_S2MSFB_ID ),
		);

		echo PSK_S2MSFBAdmin::get_admin_header(__METHOD__);

		$settings   = get_option( PSK_S2MSFB_OPT_SETTINGS_GENERAL );
		$maxcount   = (int)$settings['maxcount'];
		$retention  = (int)$settings['retention'];

		if (isset($_POST['action'])) {

			check_admin_referer( __CLASS__.__METHOD__ );

			$action     = $_POST['action'];
			$maxcount   = (int)$_POST['maxcount'];
			$retention  = (int)$_POST['retention'];

			switch ( $action ) {

				case 'update':
					$form_is_valid = true;
					if ($form_is_valid===true) {
						update_option( PSK_S2MSFB_OPT_SETTINGS_GENERAL , array(
							'maxcount'		=> $maxcount,
							'retention'		=> $retention,
						) );
						echo PSK_Tools::get_js_alert( __('Success!',PSK_S2MSFB_ID) , __('General settings saved',PSK_S2MSFB_ID), 'success');
					}
					break;
			}

		}

		if ( ( $maxcount == 0 ) && ( $retention == 0 ) ) {
			echo PSK_Tools::get_js_alert( __('Warning!',PSK_S2MSFB_ID) , __('Download logs limit and retention disabled',PSK_S2MSFB_ID), 'warning' , 60000 );
		}
		else {
			if ( $maxcount == 0 ) {
				echo PSK_Tools::get_js_alert( __('Info!',PSK_S2MSFB_ID) , __('Download logs limit disabled',PSK_S2MSFB_ID), 'info' , 60000 );
			} else if ( $retention == 0 ) {
				echo PSK_Tools::get_js_alert( __('Info!',PSK_S2MSFB_ID) , __('Download logs retention disabled',PSK_S2MSFB_ID), 'info' , 60000 );
			}
		}


		echo '<form class="form-horizontal" action="" method="post">';
		echo '  <input type="hidden" name="action" value="update"/>';
		wp_nonce_field( __CLASS__.__METHOD__ );

		echo '  <fieldset>';
		echo '    <legend>'.__('Main settings',PSK_S2MSFB_ID).'</legend>';

		echo '    <div class="control-group">';
		echo '      <label class="control-label" for="maxcount">'.__('Logs limit',PSK_S2MSFB_ID).'</label>';
		echo '      <div class="controls">';
		echo '        <input type="text"  name="maxcount" id="maxcount" value="'.esc_attr($maxcount).'" required="required" />';
		echo '        <span class="help-inline"><em>' . __('When download records count has reach this limit, older records are deleted',PSK_S2MSFB_ID) . '</em></span>';
		echo '      </div>';
		echo '    </div>';

		echo '    <div class="control-group">';
		echo '      <label class="control-label" for="retention">'.__('Logs retention',PSK_S2MSFB_ID).'</label>';
		echo '      <div class="controls">';
		echo '        <select id="retention" name="retention">';
		foreach ($days as $day=>$val) {
			$sel = ($retention==$day) ? ' selected="selected"' : "";
			echo '			<option value="' . $day . '"' . $sel . '>' . $val . '</option>';
		}
		echo '        </select>';
		echo '        <span class="help-inline"><em>' . __('Older download records are deleted',PSK_S2MSFB_ID) . '</em></span>';
		echo '      </div>';
		echo '    </div>';

  		echo '  </fieldset>';

  		echo '  <br/>';
		echo '  <button type="submit" class="btn btn-primary">'.__('Save Changes',PSK_S2MSFB_ID).'</button>';
		echo '</form>';

		echo PSK_S2MSFBAdmin::get_admin_footer();
	}


	/**
	 * Admin Screen : Stats > Notification
	 *
	 * @return 			void
	 */
	public static function admin_screen_settings_notification() {

		echo PSK_S2MSFBAdmin::get_admin_header(__METHOD__);

		$settings    = get_option( PSK_S2MSFB_OPT_SETTINGS_NOTIFY );
		$emailfrom   = ($settings['emailfrom']=='') ? get_option('admin_email') : $settings['emailfrom'];
		$subject     = ($settings['subject']=='') ? PSK_S2MSFB_DEFAULT_EMAIL_DOWNLOAD_SUBJECT : $settings['subject'];
		$emailto     = ($settings['emailto']=='') ? get_option('admin_email') : $settings['emailto'];
		$emailnotify = ($settings['emailnotify']!='1') ? '0' : '1' ;

		if (isset($_POST['action'])) {

			check_admin_referer( __CLASS__.__METHOD__ );

			$action      = $_POST['action'];
			$emailfrom   = trim($_POST['emailfrom']);
			$emailto     = trim($_POST['emailto']);
			$emailnotify = $_POST['emailnotify'];
			$subject     = $_POST['subject'];

			switch ( $action ) {

				case 'update':

					$form_is_valid = true;

					if ( is_email($emailfrom) != $emailfrom ) {
						echo PSK_Tools::get_js_alert( __('Error!',PSK_S2MSFB_ID) , sprintf( __('From email address %s is invalid',PSK_S2MSFB_ID) , $emailfrom ) , 'error' , 60000 );
						$form_is_valid = false;
					}

					$addresses = explode( ',' , $emailto );
					$cleanaddr = array();
					foreach ($addresses as $address) {
						$address = trim($address);
						if ( is_email( $address ) == $address ) {
							$cleanaddr[] = $address;
						}
						else {
							echo PSK_Tools::get_js_alert( __('Error!',PSK_S2MSFB_ID) , sprintf( __('Notify email address %s is invalid',PSK_S2MSFB_ID) , $address ) , 'error' , 60000 );
							$form_is_valid = false;
						}
					}

					if ($form_is_valid===true) {
						$emailto = implode(',',$cleanaddr);
						update_option( PSK_S2MSFB_OPT_SETTINGS_NOTIFY , array(
							'subject'		=> $subject,
							'emailfrom'		=> $emailfrom,
							'emailto'		=> $emailto,
							'emailnotify'	=> $emailnotify,
						) );
						echo PSK_Tools::get_js_alert( __('Success!',PSK_S2MSFB_ID) , __('Notification settings saved',PSK_S2MSFB_ID), 'success');
					}
					break;
			}
		}

		$emailnotify = ($emailnotify=='1') ? ' checked="checked"' : "";
//;

		echo '<form class="form-horizontal" action="" method="post">';
		echo '  <input type="hidden" name="action" value="update"/>';
		wp_nonce_field( __CLASS__.__METHOD__ );

		echo '  <fieldset>';
		echo '    <legend>' . __('Real-time notification',PSK_S2MSFB_ID) . '</legend>';
		echo '    <div class="control-group">';
		echo '      <label class="control-label" for="emailFrom">' . __('Notify by email',PSK_S2MSFB_ID) . '</label>';
		echo '      <div class="controls">';
		echo '          <input type="checkbox" value="1" ' . $emailnotify . ' name="emailnotify" />';
		echo '      </div>';
		echo '    </div>';
		echo '    <div class="control-group">';
		echo '      <label class="control-label" for="emailFrom">' . __('From email address',PSK_S2MSFB_ID) . '</label>';
		echo '      <div class="controls">';
		echo '        <input type="email" name="emailfrom" id="emailFrom" value="' .  esc_attr($emailfrom) . '" placeholder="' . esc_attr(PSK_S2MSFB_DEFAULT_EMAIL_DOWNLOAD_FROM) . '" required="required" />';
		echo '      </div>';
		echo '    </div>';
		echo '    <div class="control-group">';
		echo '      <label class="control-label" for="emailTo">' . __('Notify email address',PSK_S2MSFB_ID) . '</label>';
		echo '      <div class="controls">';
		echo '        <input type="text"  name="emailto" id="emailTo" value="' . esc_attr($emailto) . '" placeholder="' . esc_attr(PSK_S2MSFB_DEFAULT_EMAIL_DOWNLOAD_TO) . '" required="required" />';
		echo '        <span class="help-inline"><em>' . __('Separate multiple email address with a comma (,)') . '</em></span>';
		echo '      </div>';
		echo '    </div>';
		echo '    <div class="control-group">';
		echo '      <label class="control-label" for="subject">' . __('Email subject',PSK_S2MSFB_ID) . '</label>';
		echo '      <div class="controls">';
		echo '        <input type="text"  name="subject" id="subject" value="' . esc_attr($subject) . '" placeholder="' . esc_attr(PSK_S2MSFB_DEFAULT_EMAIL_DOWNLOAD_SUBJECT) . '" />';
		echo '        <span class="help-inline"><em>' . __('You can use variable %blogname%') . '</em></span>';
		echo '      </div>';
		echo '    </div>';
  		echo '  </fieldset>';

		echo '  <fieldset>';
		echo '    <legend>' . __('Notification reports',PSK_S2MSFB_ID) . '</legend>';
		echo '    <div class="control-group">';
		echo '      <div class="controls">';
		echo __('Soon available',PSK_S2MSFB_ID);
		echo '      </div>';
		echo '    </div>';
  		echo '  </fieldset>';

  		echo '  <br/>';
		echo '  <button type="submit" class="btn btn-primary">'.__('Save Changes',PSK_S2MSFB_ID).'</button>';
		echo '</form>';

		echo PSK_S2MSFBAdmin::get_admin_footer();
	}



}

PSK_S2MSFBAdminSettings::init();

