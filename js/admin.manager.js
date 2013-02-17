function psk_sfb_rename_file(f) {
	psk_sfb_rename_df(f,false);
}


function psk_sfb_remove_file(f) {
	psk_sfb_remove_df(f,false);
}


function psk_sfb_rename_dir(f)  {
	psk_sfb_rename_df(f,true);
}


function psk_sfb_remove_dir(f)  {
	psk_sfb_remove_df(f,true);
}


var sk_sfb_rename_df_lock = false;
function psk_sfb_rename_df(f,d) {
	var title = (d===true) ? objectL10n.renamedirectory : objectL10n.renamefile;
	$("#pskModalLabel").html(title);

	var s    = psk_sfb_basename(f);
	var text = objectL10n.rename+" <code>"+new psk_sfb_html(f)+"</code><br/><br/><input id=\"pskModalInput\" type=\"text\" value=\""+new psk_sfb_html(s)+"\"/><br/><br/>";
	$("#pskModalBody").html(text);
	$("#pskModalSave").html(objectL10n.rename);
	$("#pskModalSave").removeClass("btn-danger");
	$("#pskModalSave").addClass("btn-success");

	$("#pskModal").modal("show");

	$('#pskModalInput').keypress(function(event){
		var keycode = (event.keyCode ? event.keyCode : event.which);
		if (keycode == '13') $('#pskModalSave').trigger('click');
 	});

	$("#pskModalSave").click(function() {
		if (!sk_sfb_rename_df_lock) {
			sk_sfb_rename_df_lock = true;
			$("#pskModalSave").addClass("disabled");

			var d = encodeURIComponent($('#pskModalInput').attr('value'));
			$.post(PSK_S2MSFB.ajaxurl,{action:PSK_S2MSFB.action_rf, dir:'', dirbase:'', s:f, d:d, nonce:PSK_S2MSFB.nonce, }, function(data) {
				if (data!="1") {
					psk_sfb_alert(objectL10n.error,objectL10n.erroroccurs+"<br/>"+data,'error',120000);
				}
				else {
					psk_sfb_alert(objectL10n.success,(d===true) ? objectL10n.renamedirectoryok : objectL10n.renamefileok,'success');
				}
				$("#pskModalSave").unbind();
				$('#pskModalInput').unbind();
				$(".psk_jfiletree").each(function() {$(this).fileTreeReload();});
				$("#pskModal").modal("hide");
				$("#pskModalSave").removeClass("disabled");
				sk_sfb_rename_df_lock = false;
			});
		}
		else {
			alert(objectL10n.pleasewait);
		}
	});
}


var sk_sfb_remove_df_lock = false;
function psk_sfb_remove_df(f,d) {
	var title = (d===true) ? objectL10n.removedirectory : objectL10n.removefile;
	$("#pskModalLabel").html(title);

	var text = (d===true) ? objectL10n.removedirectory+"<br/><code>"+new psk_sfb_html(f)+"</code><br/><br/>"+objectL10n.removedirectorywarning : objectL10n.removefile+"<br/><code>"+f+"</code><br/><br/>"+objectL10n.removefilewarning
	$("#pskModalBody").html(text);
	$("#pskModalSave").html(objectL10n.remove);
	$("#pskModalSave").removeClass("btn-success");
	$("#pskModalSave").addClass("btn-danger");
	$("#pskModal").modal("show");

	$("#pskModalSave").click(function() {
		if (!sk_sfb_remove_df_lock) {
			sk_sfb_remove_df_lock = true;
			$("#pskModalSave").addClass("disabled");

			$.post(PSK_S2MSFB.ajaxurl,{action:PSK_S2MSFB.action_df, dir:'', dirbase:'', s:f, nonce:PSK_S2MSFB.nonce, }, function(data) {
				if (data!="1") {
					if (data.indexOf('Maximum function nesting level')!=-1) data = objectL10n.xdebugerror;
					psk_sfb_alert(objectL10n.error,objectL10n.erroroccurs+"<br/>"+data,'error',120000);
				}
				else {
					psk_sfb_alert(objectL10n.success,(d===true) ? objectL10n.removedirectoryok : objectL10n.removefileok,'success');
				}
				$("#pskModalSave").unbind();
				$(".psk_jfiletree").each(function() {$(this).fileTreeReload();});
				$("#pskModal").modal("hide");
				$("#pskModalSave").removeClass("disabled");
				sk_sfb_remove_df_lock = false;
			});
		}
		else {
			alert(objectL10n.pleasewait);
		}
	});
}


$('#pskModal').on('hidden',function () {
	$("#pskModalSave").unbind();
	$('#pskModalInput').unbind();
});




/*
 * Generate the shortcode
 */
function generate_shortcode() {
	var str = '[' + objectL10n.shortcode;
    var taa = objectL10n.shortcodetags.split(',');

    $.each(taa,function(i) {
    	var tag = taa[i];
    	var val = get_shortcode_val(tag);
		if ((val!='') && (val != undefined)) {
			str += ' ' + tag + '="' + val + '"';
		}
    });

    str += " /]";
    $("#shortcode_preview").html(str);
}



/*
 * Return the value of a given shortcode tag
 */
function get_shortcode_val(tag) {
   	var val = "";

	switch (tag) {
		case 'displayall':
		case 's2alertbox':
		case 'dirfirst'      :
		case 'hidden'        :
		case 'multifolder'   :
		case 'openrecursive' :
			val = $('input[type=radio][name='+tag+']:checked').val();
			break;

		case 'collapsespeed' :
		case 'expandspeed'   :
			val = $('#'+tag).val();
			if (val!='') {
				val = parseInt($('#'+tag).val(),10);
				if ( isNaN( val ) ) {
					val = '';
					$('#cg'+tag).addClass('error');
				} else {
					$('#cg'+tag).removeClass('error');
				}
				val = val.toString();
			}
			else {
				$('#cg'+tag).removeClass('error');
			}
			break;

		case 'filterdir'  :
		case 'filterfile' :
		case 'dirbase'    :
			val = encodeURIComponent($('#'+tag).val());
			break;

		case 'names':
			for (var i=0; i<6; i++) {
				var k = $('#h'+tag+i).val();
				var v = $('#'+tag+i).val();
				if (v!='')
					val = val + encodeURIComponent(k) + ':' + encodeURIComponent(v) + '|';
			}
			val = (val.substr(-1,1)=='|') ? val.slice(0,-1) : val;
			break;

		case 'folderevent'    :
		case 'collapseeasing' :
		case 'expandeasing'   :
		default:
			val = $('#'+tag).val();
			break;
	}
	return val;
}


if ( $("#shortcode_preview").length != 0 ) {
	$('.generator').on('change',function () { generate_shortcode(); });
	$(document).ready(function(){ generate_shortcode(); });
}

