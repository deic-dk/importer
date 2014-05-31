var ocdownloader_pw = "";
var ocdownloader_pw_ok = false;
var input_field;
var max_failed_pw_attemts = 3;
var pw_attempts = 0;
var submitting = false;
var mydialog1;

function decrypt_pw(enc_pw){
	$.ajax({
		type:'POST',
		url:OC.linkTo('ocdownloader','ajax/decryptPw.php'),
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
				ocdownloader_pw_ok = false;
				unlock_pw();
			}
		}
	});
}

function store_master_pw(){
	$.ajax({
		type:'POST',
		url:OC.linkTo('ocdownloader','ajax/storeMasterPw.php'),
		dataType:'json',
		data:{master_pw:ocdownloader_pw},
		async:false,
		success:function(s){
			if(s.error){
				return false;
			}
			else{
				ocdownloader_pw_ok =  true;
			}
		},
		error:function(s){
			alert("Unexpected error!");
			ocdownloader_pw_ok =  false;
		}
	});
}

function unlock_pw(){
	var enc = input_field.parent().find(".enc").first().val();
	if(enc=="0"){
		return;
	}
	if(pw_attempts>max_failed_pw_attemts){
		$("form#ocdownloader :input").attr("disabled", true);
		$("form#ocdownloader fieldset.personalblock #ocdownloader_settings_submit").unbind('click');
		$("form#ocdownloader fieldset.personalblock #ocdownloader_settings_submit").css('cursor', 'default');
		$('.ocdownloader-delete').unbind('click');
		$('.ocdownloader-delete').css('cursor', 'default');
		alert("ERROR: could not unlock password.");
		return;
	}
	if(!submitting){
		input_field.parent().find(".personal-show + label").css('background-image', 'url("../../../apps/ocdownloader/img/loader.gif")');
	}
	if(ocdownloader_pw_ok){
		var enc_pw = input_field.parent().find(".orig_enc_pw").first().val();
		decrypt_pw(enc_pw);
	}
	else if(!$("#oc_pw_dialog").dialog("isOpen")){
		$("#oc_pw_dialog").dialog('open');
		$("span.ui-button-text:contains('OK')").css("background-color", "#E6E6E6");
	}
}

function pw_ok_func(){
	ocdownloader_pw = $('#ocdownloader_pw').val();
	store_master_pw();
	ocdownloader_pw = "";
	mydialog1.dialog("close");
	if(submitting){
		return;
	}
	unlock_pw();
}

$(document).ready(function(){
	
	$('.ocdownloader-delete').bind('click', function(){
		$('#ocdownloader_pr_un_' + $(this).attr('rel')).val('');
		$('#ocdownloader_pr_pw_' + $(this).attr('rel')).val('');
	});
	$('.ocdownloader-delete').tipsy({gravity:'s',fade:true});

	$("form#ocdownloader fieldset.personalblock div.ocdownloader_pr").each(function(el){
		var encVal;
		$(this).find("input[type='password']").each(function(el){
			$(this).showPassword();
			encVal = $(this).val();
			$(this).on('input', function() {
				input_field = $(this);
				if(!ocdownloader_pw_ok || encVal!=''){
					unlock_pw();
				}
			});
			$(this).parent().find("input.personal-show[type='checkbox']").first().bind('click', function() {
				input_field = $(this).parent().find("input.password[type='text']").first();
				if(!ocdownloader_pw_ok || encVal!=''){
					unlock_pw();
				}
			});
		});
	});

 mydialog1 = $("#oc_pw_dialog").dialog({//create dialog, but keep it closed
		title: "Enter master password",
		autoOpen: false,
		width: $("label.nowrap").width()+64,
		modal: true,
		dialogClass: "no-close my-dialog",
		buttons: {
			"OK": function() {
				pw_ok_func();
			},
			"Cancel": function() {
				pw_attempts = 0;
				ocdownloader_pw = "";
				ocdownloader_pw_ok = false;
				mydialog1.dialog("close");
				if(!submitting){
					input_field.parent().find(".personal-show + label").css('background-image', 'url("../../../core/img/actions/toggle.png")');
					var orig_val = input_field.parent().find(".orig_enc_pw").first().val();
					input_field.val(orig_val);
				}
			}
		}
	});
	
	$("#oc_pw_dialog input#ocdownloader_pw").keypress(function (e) {
		if(e.which==13){
			pw_ok_func();
		}
	});

	$("form#ocdownloader fieldset.personalblock #ocdownloader_settings_submit").bind('click', function(){
		// Get the clear-text password cloned into the password field which is actually submmitted.
		$("input.personal-show[type='checkbox']").each(function(el){
			if($(this).is(':checked')){
				$(this).parent().find("input.password[type='password']").first().val($(this).parent().find("input.password[type='text']").first().val());
			}
		});
		if(ocdownloader_pw_ok){
			$("form#ocdownloader").submit();
		}
		else{
			submitting = true;
			$("#oc_pw_dialog").dialog('open');
			$("span.ui-button-text:contains('OK')").css("background-color", "#E6E6E6");
			$("#oc_pw_dialog").on( "dialogclose", function( event, ui ) {
				if(ocdownloader_pw_ok){
					$("form#ocdownloader").submit();
				}
			});
		}
	});

});
