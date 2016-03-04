var importer_pw = "";
var importer_pw_ok = false;
var input_field;
var max_failed_pw_attemts = 3;
var pw_attempts = 0;
var submitting = false;
var mydialog1;

function decrypt_pw(enc_pw){
	$.ajax({
		type:'POST',
		url:OC.linkTo('importer','ajax/decryptPw.php'),
		dataType:'json',
		data:{enc_pw:enc_pw},
		async:false,
		success:function(s){
			if(!s.error){
				if(enc_pw!=''){
					input_field.val(s.pw);
				}
				input_field.parent().find("input.enc[type='hidden']").first().val("0");
				input_field.off('input');
				$(".personal-show + label").css('background-image', 'url("../../../core/img/actions/toggle.png")');
			}
			else{
				alert(s.error);
				++pw_attempts;
				importer_pw_ok = false;
				unlock_pw();
			}
		}
	});
}

function store_master_pw(){
	$.ajax({
		type:'POST',
		url:OC.linkTo('importer','ajax/storeMasterPw.php'),
		dataType:'json',
		data:{master_pw:importer_pw},
		async:false,
		success:function(s){
			if(s.error){
				return false;
			}
			else{
				importer_pw_ok =  true;
			}
		},
		error:function(s){
			alert("Unexpected error!");
			importer_pw_ok =  false;
		}
	});
}

function unlock_pw(){
	var enc = input_field.parent().find(".enc").first().val();
	//fix for auto popup open on some screens asking for Master password
	//Ashokaditya
	if(enc=="0" || enc == "1"){
		return;
	}
	if(pw_attempts>max_failed_pw_attemts){
		$("form#importer :input").attr("disabled", true);
		$("form#importer fieldset.personalblock #importer_settings_submit").unbind('click');
		$("form#importer fieldset.personalblock #importer_settings_submit").css('cursor', 'default');
		$('.importer-delete').unbind('click');
		$('.importer-delete').css('cursor', 'default');
		alert("ERROR: could not unlock password.");
		return;
	}
	if(!submitting){
		input_field.parent().find(".personal-show + label").css('background-image', 'url("../../../apps/importer/img/loader.gif")');
	}
	if(importer_pw_ok){
		var enc_pw = input_field.parent().find(".orig_enc_pw").first().val();
		decrypt_pw(enc_pw);
	}
	else if(!$("#oc_pw_dialog").dialog("isOpen")){
		$("#oc_pw_dialog").dialog('open');
		$("span.ui-button-text:contains('OK')").css("background-color", "#E6E6E6");
	}
}

function pw_ok_func(){
	importer_pw = $('#importer_pw').val();
	store_master_pw();
	importer_pw = "";
	mydialog1.dialog('close');
	if(submitting){
		return;
	}
	unlock_pw();
}

function submit_form(){
	$.ajax({
		type:'POST',
		url:OC.linkTo('importer','ajax/updatePersonalSettings.php'),
				dataType:'json',
				data:$('#importerPersonalSettings').serialize(),
				async:false,
				success:function(s){
					if(s.length!=0){
						$("#importer_msg").html(s);
					}
					else{
						$("#importer_msg").html("Settings saved");
					}
				},
				error:function(s){
					$("#importer_msg").html("Unexpected error!");
				}
	});
}

$(document).ready(function(){

	$('.importer-delete').bind('click', function(){
		$('#importer_pr_un_' + $(this).attr('rel')).val('');
		$('#importer_pr_pw_' + $(this).attr('rel')).val('');
		$('input[name="importer_pr_pw_' + $(this).attr('rel')+'-clone"]').val('');
	});
	$('.importer-delete').tipsy({gravity:'s',fade:true});

	$("fieldset#importerPersonalSettings div.importer_pr").each(function(el){
		var encVal;
		$(this).find("input[type='password']").each(function(el){
			$(this).showPassword();
			encVal = $(this).val();
			$(this).on('input', function() {
				input_field = $(this);
				if(!importer_pw_ok || encVal!=''){
					unlock_pw();
				}
			});
			$(this).parent().find("input.personal-show[type='checkbox']").first().bind('click', function() {
				input_field = $(this).parent().find("input.password[type='text']").first();
				if(!importer_pw_ok || encVal!=''){
					unlock_pw();
				}
			});
		});
	});

 mydialog1 = $("#oc_pw_dialog").dialog({//create dialog, but keep it closed
		title: "Enter master password",
		autoOpen: false,
		modal: true,
		dialogClass: "no-close my-dialog",
		buttons: {
			"OK": function() {
				pw_ok_func();
			},
			"Cancel": function() {
				pw_attempts = 0;
				importer_pw = "";
				importer_pw_ok = false;
				mydialog1.dialog('close');
				if(!submitting){
					input_field.parent().find(".personal-show + label").css('background-image', 'url("../../../core/img/actions/toggle.png")');
					var orig_val = input_field.parent().find(".orig_enc_pw").first().val();
					input_field.val(orig_val);
				}
			}
		}
	});

	$("#oc_pw_dialog input#importer_pw").keypress(function (e) {
		//alert(e.which);
		if(e.which==13){
			pw_ok_func();
		}
		else{
            $("body").dialog("close');
        }
	});

	$("fieldset#importerPersonalSettings #importer_settings_submit").bind('click', function(){
		// Get the clear-text password cloned into the password field which is actually submmitted.
		$("input.personal-show[type='checkbox']").each(function(el){
			if($(this).is(':checked')){
				$(this).parent().find("input.password[type='password']").first().val($(this).parent().find("input.password[type='text']").first().val());
			}
		});
		var ok = true;
		$("form#importer fieldset.personalblock div.importer_pr").each(function(el){
			var encVal;
			$(this).find("input[type='password']").each(function(el){
				encVal = $(this).val();
				if(typeof encVal != 'undefined' && encVal.trim()!=""){
					ok = false;
					return;
				}
			});
		});
		if(ok || importer_pw_ok){
			submit_form();
		}
		else{
			submitting = true;
			$("#oc_pw_dialog").dialog('open');
			$("span.ui-button-text:contains('OK')").css("background-color", "#E6E6E6");
			$("#oc_pw_dialog").on( "dialogclose", function( event, ui ) {
				if(importer_pw_ok){
					submit_form();
				}
			});
		}
	});

	// Apparently none of this is working. Chrome autofills if one has a remembered password on the login page
	/*if(navigator.userAgent.toLowerCase().indexOf('chrome') >= 0) {
		setTimeout(function () {
			document.getElementById('importer_pr_pw_1').autocomplete = 'off';
		}, 1);
	}

	$(':input').live('focus',function(){
		$(this).attr('autocomplete', 'off');
	});

	$('form#importer').attr('autocomplete', 'off');
	$('.importer_pr input').attr('autocomplete', 'off');
	$('.importer_pr input.username, .importer_pr input.password').each(function(el){
		if($(this).attr('type')=='password' && (typeof $(this).val() == typeof undefined || $(this).val() == false || $(this).val()=='')){
			//alert($(this).attr('autocomplete'));
			$(this).attr('autocomplete','off');
			$(this).attr('type', 'text');
			$(this).val('');
			$(this).attr('type', 'password');
			$(this).val('');
		}
	});*/

});

