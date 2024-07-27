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
				alert('Could not decrypt password. '+s.error);
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

/**
 * Unencrypt a single password input field (the one currently held in input_field).
 */
function unlock_pw(){
	var enc = input_field.parent().find(".enc").first().val();
	if(pw_attempts>max_failed_pw_attemts){
		$("fieldset#importerPersonalSettings :input").attr("disabled", true);
		$("fieldset#importerPersonalSettings #importer_settings_submit").unbind('click');
		$("fieldset#importerPersonalSettings #importer_settings_submit").css('cursor', 'default');
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

function submit_importer_form(){
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
	
	$("#importerPersonalSettings .importer_pr").each(function(ev){
		var encPass = $(this).find("input.password").val();
		if(encPass===""){
			$(this).find("input.enc[type='hidden']").val("0");
		}
		else{
			// Browsers no longer honor autocomplete="off" for password fields and FF offers
			// to remember passwords even when not clicking save. 
			// TODO: find solution.
			$(this).find("input.enc[type='hidden']").val("1");
		}	
		// FF remembers if checkboxes are checked on reload. Just clear them all.
		$(this).find("input.personal-show[type='checkbox']").first().attr("checked", false);
		});

	$('.importer-delete').bind('click', function(){
		$('#importer_pr_un_' + $(this).attr('rel')).val('');
		$('#importer_pr_pw_' + $(this).attr('rel')).val('');
		$('input[name="importer_pr_pw_' + $(this).attr('rel')+'-clone"]').val('');
		 $(this).parent().find(".personal-show + label").css('background-image', 'url("../../../core/img/actions/toggle.png")');
	});
	$('.importer-delete').tipsy({gravity:'s',fade:true});

	$("fieldset#importerPersonalSettings div.importer_pr").each(function(el){
		var encVal;
		$(this).find("input.password").each(function(el){
			encVal = $(this).val();
			$(this).on('input', function() {
				input_field = $(this);
				if(!importer_pw_ok || encVal!=''){
					unlock_pw();
				}
			});
			$(this).parent().find("input.personal-show[type='checkbox']").first().bind('click', function() {
				input_field = $(this).parent().find("input.password").first();
				//var inputType = input_field.attr('type');
				//input_field.attr('type', inputType=='password'?'text':'password');
				if(input_field.hasClass('numeric-password')){
					input_field.removeClass('numeric-password');
				}
				else{
					input_field.addClass('numeric-password');
				}
				if(!importer_pw_ok || encVal!=''){
					unlock_pw();
				}
			});
		});
	});

	mydialog1  = $("#oc_pw_dialog").dialog({//create dialog, but keep it closed
		title: t("importer", "Enter master password"),
		autoOpen: false,
		modal: true,
		dialogClass: "no-close my-dialog",
		buttons: buttons1
	});
	
	var buttons1 = {};
	buttons1[t("importer", "OK")] = function() {
		pw_ok_func();
 	};
 	buttons1[t("importer", "Cancel")] = function() {
		pw_attempts = 0;
		importer_pw = "";
		importer_pw_ok = false;
		mydialog1.dialog('close');
		if(!submitting){
			input_field.parent().find(".personal-show + label").css('background-image', 'url("../../../core/img/actions/toggle.png")');
			var orig_val = input_field.parent().find(".orig_enc_pw").first().val();
			input_field.val(orig_val);
		}
 	};

	$("#oc_pw_dialog input#importer_pw").keypress(function (e) {
		//alert(e.which);
		if(e.which==13){
			pw_ok_func();
		}
		else{
			$("body").dialog('close');
		}
	});

	$("fieldset#importerPersonalSettings #importer_settings_submit").bind('click', function(){
		
		
		
		if(importer_pw_ok || $('.importer_pr .password:empty').length==$('.importer_pr .password').length){
			submit_importer_form();
		}
		else{
			submitting = true;
			$("#oc_pw_dialog").dialog('open');
			$("span.ui-button-text:contains('OK')").css("background-color", "#E6E6E6");
			$("#oc_pw_dialog").on( "dialogclose", function( event, ui ) {
				if(importer_pw_ok){
					submit_importer_form();
				}
			});
		}
	});

	$('.add_importer_url').click(function(ev){
		$(this).addClass('hidden');
		$('#importer_pr_0_').removeClass('hidden');
	});
	
});

