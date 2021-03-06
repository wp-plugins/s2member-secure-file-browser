function psk_sfb_rename_file( f ) {
	psk_sfb_rename_df( f , false );
}

function psk_sfb_comment_file( f , c ) {
	psk_sfb_comment_df( f , false , c );
}

function psk_sfb_displayname_file( f , c ) {
	psk_sfb_displayname_df( f , false , c );
}

function psk_sfb_remove_file( f ) {
	psk_sfb_remove_df( f , false );
}

function psk_sfb_rename_dir( f ) {
	psk_sfb_rename_df( f , true );
}

function psk_sfb_displayname_dir( f , c ) {
	psk_sfb_displayname_df( f+"/." , true , c );
}

function psk_sfb_remove_dir( f ) {
	psk_sfb_remove_df( f , true );
}


var sk_sfb_rename_df_lock = false;
function psk_sfb_rename_df( f , d ) {
	var title = (d === true) ? objectL10n.renamedirectory : objectL10n.renamefile;
	jQuery( "#pskModalLabel" ).html( title );

	var s = psk_sfb_basename( f );
	var text = objectL10n.rename + " <code>" + new psk_sfb_html( f ) + "</code><br/><br/><input id=\"pskModalInput\" type=\"text\" value=\"" + new psk_sfb_html( s ) + "\"/><br/><br/>";
	jQuery( "#pskModalBody" ).html( text );
	jQuery( "#pskModalSave" ).html( objectL10n.rename );
	jQuery( "#pskModalSave" ).removeClass( "btn-danger" );
	jQuery( "#pskModalSave" ).addClass( "btn-success" );

	jQuery( "#pskModal" ).modal( "show" );

	jQuery( '#pskModalInput' ).keypress( function ( event ) {
		var keycode = (event.keyCode ? event.keyCode : event.which);
		if ( keycode == '13' ) jQuery( '#pskModalSave' ).trigger( 'click' );
	} );

	jQuery( "#pskModalSave" ).click( function () {
		if ( ! sk_sfb_rename_df_lock ) {
			sk_sfb_rename_df_lock = true;
			jQuery( "#pskModalSave" ).addClass( "disabled" );

			var d = encodeURIComponent( jQuery( '#pskModalInput' ).attr( 'value' ) );
			jQuery.post( PSK_S2MSFB.ajaxurl , {action: PSK_S2MSFB.action_rf , dir: '' , dirbase: '' , s: f , d: d , nonce: PSK_S2MSFB.nonce } , function ( data ) {
				if ( data != "1" )
					psk_sfb_alert( objectL10n.error , objectL10n.erroroccurs + "<br/>" + data , 'error' , 120000 );
				else
					psk_sfb_alert( objectL10n.success , (d === true) ? objectL10n.renamedirectoryok : objectL10n.renamefileok , 'success' );
				jQuery( "#pskModalSave" ).unbind();
				jQuery( '#pskModalInput' ).unbind();
				jQuery( ".psk_jfiletree" ).each( function () {
					jQuery( this ).fileTreeReload();
				} );
				jQuery( "#pskModal" ).modal( "hide" );
				jQuery( "#pskModalSave" ).removeClass( "disabled" );
				sk_sfb_rename_df_lock = false;
			} );
		}
		else {
			alert( objectL10n.pleasewait );
		}
	} );
}


var sk_sfb_comment_df_lock = false;
function psk_sfb_comment_df( f , d , c) {
	var title = (d === true) ? objectL10n.commentdirectory : objectL10n.commentfile;
	jQuery( "#pskModalLabel" ).html( title );
	c = c.replace(/\[\[\[BR\]\]\]/g, "\n");
	var s = psk_sfb_basename( f );
	var text = objectL10n.comment + " <code>" + new psk_sfb_html( f ) + "</code><br/><br/><textarea class=\"span6\" placeholder=\"" + new psk_sfb_html( objectL10n.commentplaceholder ) + "\" id=\"pskModalInput\" rows=\"5\" cols=\"60\">" + new psk_sfb_html( c ) + "</textarea><br/><br/>";
	jQuery( "#pskModalBody" ).html( text );
	jQuery( "#pskModalSave" ).html( objectL10n.comment );
	jQuery( "#pskModalSave" ).removeClass( "btn-danger" );
	jQuery( "#pskModalSave" ).addClass( "btn-success" );

	jQuery( "#pskModal" ).modal( "show" );

	jQuery( "#pskModalSave" ).click( function () {
		if ( ! sk_sfb_comment_df_lock ) {
			sk_sfb_comment_df_lock = true;
			jQuery( "#pskModalSave" ).addClass( "disabled" );

			//			var d = encodeURIComponent( jQuery( '#pskModalInput' ).attr( 'value' ) );
			var c = jQuery( '#pskModalInput' ).attr( 'value' );
			jQuery.post( PSK_S2MSFB.ajaxurl , { action: PSK_S2MSFB.action_cf , dir: '' , dirbase: '' , s: f , c: c , nonce: PSK_S2MSFB.nonce } , function ( data ) {
				if ( data != "1" )
					psk_sfb_alert( objectL10n.error , objectL10n.erroroccurs + "<br/>" + data , 'error' , 120000 );
				else
					psk_sfb_alert( objectL10n.success , (d === true) ? objectL10n.commentdirectoryok : objectL10n.commentfileok , 'success' );
				jQuery( "#pskModalSave" ).unbind();
				jQuery( '#pskModalInput' ).unbind();
				jQuery( ".psk_jfiletree" ).each( function () {
					jQuery( this ).fileTreeReload();
				} );
				jQuery( "#pskModal" ).modal( "hide" );
				jQuery( "#pskModalSave" ).removeClass( "disabled" );
				sk_sfb_comment_df_lock = false;
			} );
		}
		else {
			alert( objectL10n.pleasewait );
		}
	} );
}


var sk_sfb_displayname_df_lock = false;
function psk_sfb_displayname_df( f , d , c) {
	var title = (d === true) ? objectL10n.displaynamedirectory : objectL10n.displaynamefile;
	jQuery( "#pskModalLabel" ).html( title );
	var s = psk_sfb_basename( f );
	var text = objectL10n.displayname + " <code>" + new psk_sfb_html( f ) + "</code><br/><br/><input class=\"span7\" placeholder=\"" + new psk_sfb_html( objectL10n.displaynameplaceholder ) +"\" id=\"pskModalInput\" type=\"text\" value=\"" + new psk_sfb_html( c ) + "\"/><br/><small>" + objectL10n.displaynameplacemore + "</small><br/>";
	jQuery( "#pskModalBody" ).html( text );
	jQuery( "#pskModalSave" ).html( objectL10n.displayname );
	jQuery( "#pskModalSave" ).removeClass( "btn-danger" );
	jQuery( "#pskModalSave" ).addClass( "btn-success" );

	jQuery( "#pskModal" ).modal( "show" );

	jQuery( '#pskModalInput' ).keypress( function ( event ) {
		var keycode = (event.keyCode ? event.keyCode : event.which);
		if ( keycode == '13' ) jQuery( '#pskModalSave' ).trigger( 'click' );
	} );

	jQuery( "#pskModalSave" ).click( function () {
		if ( ! sk_sfb_displayname_df_lock ) {
			sk_sfb_displayname_df_lock = true;
			jQuery( "#pskModalSave" ).addClass( "disabled" );

			//			var d = encodeURIComponent( jQuery( '#pskModalInput' ).attr( 'value' ) );
			var c = jQuery( '#pskModalInput' ).attr( 'value' );
			jQuery.post( PSK_S2MSFB.ajaxurl , { action: PSK_S2MSFB.action_nf , dir: '' , dirbase: '' , s: f , c: c , nonce: PSK_S2MSFB.nonce } , function ( data ) {
				if ( data != "1" )
					psk_sfb_alert( objectL10n.error , objectL10n.erroroccurs + "<br/>" + data , 'error' , 120000 );
				else
					psk_sfb_alert( objectL10n.success , (d === true) ? objectL10n.displaynamedirectoryok : objectL10n.displaynamefileok , 'success' );
				jQuery( "#pskModalSave" ).unbind();
				jQuery( '#pskModalInput' ).unbind();
				jQuery( ".psk_jfiletree" ).each( function () {
					jQuery( this ).fileTreeReload();
				} );
				jQuery( "#pskModal" ).modal( "hide" );
				jQuery( "#pskModalSave" ).removeClass( "disabled" );
				sk_sfb_displayname_df_lock = false;
			} );
		}
		else {
			alert( objectL10n.pleasewait );
		}
	} );
}

var sk_sfb_remove_df_lock = false;
function psk_sfb_remove_df( f , d ) {
	var title = (d === true) ? objectL10n.removedirectory : objectL10n.removefile;
	jQuery( "#pskModalLabel" ).html( title );

	var text = (d === true) ? objectL10n.removedirectory + "<br/><code>" + new psk_sfb_html( f ) + "</code><br/><br/>" + objectL10n.removedirectorywarning : objectL10n.removefile + "<br/><code>" + f + "</code><br/><br/>" + objectL10n.removefilewarning
	jQuery( "#pskModalBody" ).html( text );
	jQuery( "#pskModalSave" ).html( objectL10n.remove );
	jQuery( "#pskModalSave" ).removeClass( "btn-success" );
	jQuery( "#pskModalSave" ).addClass( "btn-danger" );
	jQuery( "#pskModal" ).modal( "show" );

	jQuery( "#pskModalSave" ).click( function () {
		if ( ! sk_sfb_remove_df_lock ) {
			sk_sfb_remove_df_lock = true;
			jQuery( "#pskModalSave" ).addClass( "disabled" );

			jQuery.post( PSK_S2MSFB.ajaxurl , {action: PSK_S2MSFB.action_df , dir: '' , dirbase: '' , s: f , nonce: PSK_S2MSFB.nonce } , function ( data ) {
				if ( data != "1" ) {
					if ( data.indexOf( 'Maximum function nesting level' ) != - 1 ) data = objectL10n.xdebugerror;
					psk_sfb_alert( objectL10n.error , objectL10n.erroroccurs + "<br/>" + data , 'error' , 120000 );
				}
				else {
					psk_sfb_alert( objectL10n.success , (d === true) ? objectL10n.removedirectoryok : objectL10n.removefileok , 'success' );
				}
				jQuery( "#pskModalSave" ).unbind();
				jQuery( ".psk_jfiletree" ).each( function () {
					jQuery( this ).fileTreeReload();
				} );
				jQuery( "#pskModal" ).modal( "hide" );
				jQuery( "#pskModalSave" ).removeClass( "disabled" );
				sk_sfb_remove_df_lock = false;
			} );
		}
		else {
			alert( objectL10n.pleasewait );
		}
	} );
}


/*
 * Generate the shortcode
 */
function generate_shortcode() {
	var str = '[' + objectL10n.shortcode;
	var taa = objectL10n.shortcodetags.split( ',' );

	jQuery.each( taa , function ( i ) {
		var tag = taa[i];
		var val = get_shortcode_val( tag );
		if ( (val != '') && (val != undefined) ) {
			str += ' ' + tag + '="' + val + '"';
		}
	} );

	str += " /]";
	jQuery( "#shortcode_preview" ).html( str );
}


/*
 * Return the value of a given shortcode tag
 */
function get_shortcode_val( tag ) {
	var val = "";

	switch ( tag ) {
		case 'displayall'              :
		case 'displaydownloaded'       :
		case 'displaybirthdate'        :
		case 'displaycomment'          :
		case 'displaydisplayname'          :
		case 'displaymodificationdate' :
		case 'sortby'                  :
		case 'search'                  :
		case 'searchdisplay'           :
		case 'displaysize'             :
		case 'dirzip'                  :
		case 's2alertbox'              :
		case 'dirfirst'                :
		case 'hidden'                  :
		case 'multifolder'             :
		case 'openrecursive'           :
			val = jQuery( 'input[type=radio][name=' + tag + ']:checked' ).val();
			break;

		case 'collapsespeed' :
		case 'expandspeed'   :
			val = jQuery( '#' + tag ).val();
			if ( val != '' ) {
				val = parseInt( jQuery( '#' + tag ).val() , 10 );
				if ( isNaN( val ) ) {
					val = '';
					jQuery( '#cg' + tag ).addClass( 'error' );
				} else {
					jQuery( '#cg' + tag ).removeClass( 'error' );
				}
				val = val.toString();
			}
			else {
				jQuery( '#cg' + tag ).removeClass( 'error' );
			}
			break;

		case 'filterdir'  :
		case 'filterfile' :
		case 'dirbase'    :
			val = encodeURIComponent( jQuery( '#' + tag ).val() );
			break;

		case 'names':
			for ( var i = 0 ; i < 6 ; i ++ ) {
				var k = jQuery( '#h' + tag + i ).val();
				var v = jQuery( '#' + tag + i ).val();
				if ( v != '' )
					val = val + encodeURIComponent( k ) + ':' + encodeURIComponent( v ) + '|';
			}
			val = (val.substr( - 1 , 1 ) == '|') ? val.slice( 0 , - 1 ) : val;
			break;

		case 'folderevent'    :
		case 'collapseeasing' :
		case 'expandeasing'   :
		default:
			val = jQuery( '#' + tag ).val();
			break;
	}
	return val;
}


jQuery( document ).ready( function () {
	if ( jQuery( "#shortcode_preview" ).length != 0 ) {
		jQuery( '.generator' ).on( 'change' , function () {
			generate_shortcode();
		} );
		generate_shortcode();
	}

	jQuery( '#pskModal' ).on( 'hidden' , function () {
		jQuery( "#pskModalSave" ).unbind();
		jQuery( '#pskModalInput' ).unbind();
	} );

} );
