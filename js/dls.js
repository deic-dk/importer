/**
* ownCloud downloader app
*
* @author Xavier Beurois
* @copyright 2012 Xavier Beurois www.djazz-lab.net
* @extended Frederik Orellana, 2013
* 
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either 
* version 3 of the License, or any later version.
* 
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*  
* You should have received a copy of the GNU Lesser General Public 
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
*/

var mydialog0;
var a = 0;
var downloader_pw = "";
var downloader_pw_ok = false;
var max_failed_pw_attemts = 3;
var pw_attempts = 0;
var decrypting = false;
var decrypt_error = false;
var mydialog1;
var folder_prov = '';

function addDownload(d, newurl, newprov, newoverw){
	newurl = newurl || "";
	newprov = newprov || "";
	newoverw = newoverw || "0";
	//var a=$('#dllist div.elts').size();
	++a;
	$('#dllist').append('<div id="elt_'+parseInt(a+1)+'" class="elts new">'+$('#hiddentpl').html()+'</div>');

	var myinp = $("#elt_"+parseInt(a+1)+" .urlc input.url");
	if(newurl!="" && (myinp.val()==undefined || myinp.val()=="")){
	  myinp.val(newurl);
	}
	$("#elt_"+parseInt(a+1)+" div select").val(newprov);
	if(newoverw=="1"){
		$("#elt_"+parseInt(a+1)+" div span.overwrite input").attr("checked", "checked");
		$("#elt_"+parseInt(a+1)+" div span.overwrite input").attr("value", "1");
	}
	
	$("#elt_"+parseInt(a+1)+" .addelt").bind('click',function(){
		addDownload(true);
		$(this).remove();
	});
	$('#elt_'+parseInt(a+1)+' div select').chosen();
	setProvidertitles('#elt_'+parseInt(a+1));
	var aa = parseInt(a+1);
	if(d){
		$('#elt_'+aa+' button.eltdelete').bind('click',function(){
			var len = $('#dllist div.elts').size();
			var b = $('#elt_'+aa).prev();
			if($('#elt_'+aa+' .addelt').length>0 || len==2){
			  b.find('.dling').before('<button class="addelt">+</button>');
			  b.find('.addelt').bind('click',function(){
			    addDownload(true);
			    $(this).remove();
			  });
			}
		$(this).parent().remove();
		});
	}
	else{
		$('#elt_'+parseInt(a+1)+' button.eltdelete').remove();
	}
}

function setProvidertitles(e){
	$(e+' span.urlc').tipsy({gravity:'s',fade:true});
	$(e+' div select.chzen-select').change(function(){
		$(e+' span.urlc').attr('title',t('downloader','Type URL, then hit RETURN'));
	});
	$(e+' div span.overwrite').tipsy({gravity:'s',fade:true});
	$(e+' div span.overwrite input').bind('click',function(){
		if($(this).val() == '1'){
			$(this).val('0');
		}else{
			$(this).val('1');
		}
	});
}

function getProvider(msg){
	$(window).bind('beforeunload', function(){
		return false;
	});

	if(decrypting){
		alert("decrypting");
		return;
	};
	
	var p=msg.find('select.chzen-select').val();
	var u=msg.find('input.url').val();
	if(p==0){
	  msg.find('select.chzen-select option').each(function(el){
	    if($(this).text().toLowerCase()==u.replace(/^(\w+):\/\/.*$/, "$1").toLowerCase() ||
				$(this).text().toLowerCase()==u.replace(/https:\/\//, "http://").replace(/^(\w+):\/\/.*$/, "$1").toLowerCase()
			){
	      p = $(this).val();
	    }
	  });
	}
	if(p==0){
		msg.find('span.dling').html('<img src="'+OC.imagePath('downloader','warning.png')+'" />&nbsp;'+t('downloader','Select a provider!'));
	}
	else{
		if(u.length==0){
			msg.find('span.dling').html('<img src="'+OC.imagePath('downloader','warning.png')+'" />&nbsp;'+t('downloader','Provide a file URL!'));
		}
		else{
			$.ajax({
				type:'POST',
				url:OC.linkTo('downloader','ajax/getProvider.php'),
				dataType:'json',
				data:{p:p},
				async:false,
				success:function(s){
					if(s.e){
						msg.find('span.dling').html('<img src="'+OC.imagePath('downloader','warning.png')+'" />&nbsp;'+t('downloader', 'Provider does not exist!'));
					}
					else{
						if(s.a && !downloader_pw_ok){
							decrypting = true;
							// Get username/password for the provider
							checkMasterPw();
							if(!downloader_pw_ok){
								folder_prov = '';
								$("#oc_pw_dialog").dialog('open');
								return;
							}
						}
						msg.removeClass('new');
						msg.find('span.dling').html('<iframe></iframe>');
						// Notice: that the variable overwrite is actually used to indicate "preserve/keep directory structure"... TODO: rename
						msg.find('span.dling iframe').attr('src',OC.linkTo('downloader','providers/'+s.n+'.php?u='+u+'&p='+p+'&k='+(msg.find('span.overwrite input').attr("checked")?1:0)+'&o=1'));
						msg.find('span.dling iframe').load(function(){
							n = msg.attr('id').replace('elt_','');
							n = parseInt(parseInt(n)+1);
							if($('#elt_'+n).length != 0){
								getProvider($('#elt_'+n));
							}
							else{
								$(window).unbind('beforeunload');
								if($('#dllist div.elts.new').size() == 0 && msg.find('span.dling iframe').contents().find(".pb_text").text() == '100.0%' &&
									 msg.attr('id')!='elt_1'){
									//addDownload(false);
								}
							}
							if(msg.find('span.dling iframe').contents().find(".pb_text").text() == '100.0%'){
								//msg.css('display','none');
								if(msg.attr('id')=='elt_1'){
									msg.find('span.urlc input.url').val('');
									msg.find('span.dling').html('');
									msg.addClass('new');
								}
								else{
									msg.remove();
								}
								updateHistory();
							}
							else{
								msg.addClass('new');
							}
						});
					}
				}
			});
		}
	}
}

function checkMasterPw(){
	$.ajax({
		type:'POST',
		url:OC.linkTo('downloader','ajax/checkMasterPw.php'),
		dataType:'json',
		data:{},
		async:false,
		success:function(s){
			decrypting = false;
			if(s.pw=='1'){
				downloader_pw_ok = true;
			}
		},
		error:function(){
			decrypting = false;
		}
	});
}

function updateHistory(clear){
	clear = clear || 0;
	$.ajax({
		type:'POST',
		url:OC.linkTo('downloader','ajax/updateHistory.php'),
		dataType:'json',
		data:{clear:clear},
		async:true,
		success:function(s){
			$('#tbhisto').html('');
			$.each(s.h, function(k,v){
				$('#tbhisto').append('<tr><td class="col1">'+v.dl_file+'</td><td class="col2">'+v.dl_ts+'</td><td class="col3">'+v.dl_status+'</td></tr>');
			});
		}
	});
}

function lsDir(url, provider){
	$.ajax({
		type:'POST',
		url:OC.linkTo('downloader','ajax/lsDir.php'),
		dataType:'json',
		data:{url:url, provider:provider},
		async:false,
		success:function(urls){
			if(urls.error){
				$("#folder_pop .elts span.dling").html('<img src="'+OC.imagePath('downloader','warning.png')+'" />&nbsp;'+urls.error);
				return false;
			}
		  $("#folder_pop .elts span.dling").html('');
		  var myoverw = $('#folder_pop .elts span.overwrite input').is(':checked');
		  $.each(urls, function(k, v){
		  	if($('#dllist div.elts').filter(':visible').size()==1 && addFirstDownload(v, provider, myoverw)){
					return true;
		  	}
				$('#dllist div button.addelt').remove();
				addDownload(true, v, provider, myoverw);
		  });
		},
		error:function(error){
		  $("folder_pop .elts span.dling").html('<img src="'+OC.imagePath('downloader','warning.png')+'" />&nbsp;'+t('downloader',error));
		}
	});
	if($('#elt_1 .urlc input.url').val().trim()!=""){
	  if($("#geturl").attr("disabled")=="disabled"){
	    $("#geturl").removeAttr("disabled");
	  }
	  if($("#savelist").attr("disabled")=="disabled"){
	    $("#savelist").removeAttr("disabled");
	  }
	}
}

function addFirstDownload(v, myprov, myoverw){
	var mysel = $('#dllist div.elts').filter(':visible').first();
	var myinp = mysel.find('.urlc input.url').first();
	if(myinp.val()==undefined || myinp.val()==""){
		  myinp.val(v);
		  mysel.find('div div.chzn-container').remove();
		  mysel.find('div select').toggle(true);
		  mysel.find('div select').removeClass('chzn-done');
		  mysel.find('div select').val(myprov);
		  mysel.find('div select').chosen();
		  mysel.find("span.overwrite input").attr("value", myoverw?"1":"0");
		  mysel.find("span.overwrite input").attr("checked", myoverw);
			mysel.find('button.eltdelete').remove();
		  //setProvidertitles('#elt_1');
		  return true;
	}
  return false;
}

function saveList(file_name, urls, overwrite){
	overwrite = overwrite || false;
	$.ajax({
		type:'POST',
		url:OC.linkTo('downloader','ajax/saveList.php'),
		dataType:'json',
		data:{file_name:file_name, list:urls, overwrite:overwrite},
		async:false,
		success:function(data, textStatus, jqXHR){
			console.log(jqXHR);
		  if(data==null){
		  	$("#save_pop .elts span.dling").html('<img src="'+OC.imagePath('downloader','warning.png')+'" />&nbsp;Nothing returned.');
			}
			else if(data.error){
				$("#save_pop .elts span.dling").html('<img src="'+OC.imagePath('downloader','warning.png')+'" />&nbsp;'+data.error);
			}
			else{
		  	$("#save_pop .elts span.dling").html('');
			}
		},
		error:function(jqXHR, textStatus, errorThrown){
		  $("#save_pop .elts span.dling").html('<img src="'+OC.imagePath('downloader','warning.png')+'" />&nbsp;'+t('downloader', textStatus));
		}
	});
}

function readListFile(){
  var selected_file = $('#chosen_file').text();
  if(selected_file==""){
    return;
  }
  $.ajax({
		type:'POST',
		url:OC.linkTo('downloader','ajax/readList.php'),
		dataType:'json',
		data:{file_name:selected_file},
		async:true,
		success:function(s){
			$.each(s, function(k, v){
				if(k=="msg"){
					return true;
				}
				if(k=="error"){
					alert(v);
					return false;
				}
				if($('#dllist div.elts').filter(':visible').size()==1 && addFirstDownload(v.url, v.provider, v.overwrite)){
					return true;
				}
				$('#dllist div button.addelt').remove();
				addDownload(true, v.url, v.provider, v.overwrite);
			});
			if($('#elt_1 .urlc input.url').val() && $('#elt_1 .urlc input.url').val().trim()!=""){
			  if($("#geturl").attr("disabled")=="disabled"){
			    $("#geturl").removeAttr("disabled");
			  }
			  if($("#savelist").attr("disabled")=="disabled"){
			    $("#savelist").removeAttr("disabled");
			  }	
			}
		}
	});
}

function store_master_pw(){
	$.ajax({
		type:'POST',
		url:OC.linkTo('downloader','ajax/storeMasterPw.php'),
		dataType:'json',
		data:{master_pw:downloader_pw},
		async:false,
		success:function(s){
			if(s.error){
				return false;
			}
			else{
				downloader_pw_ok =  true;
			}
		},
		error:function(s){
			alert("Unexpected error!");
			downloader_pw_ok =  false;
		}
	});
}

function pw_ok_func(){
	downloader_pw = $('#downloader_pw').val();
	store_master_pw();
	downloader_pw = "";
	if(downloader_pw_ok){
		decrypting = false;
		if(folder_prov!=''){
			lsDir($("#folderurl").val(), folder_prov);
			folder_prov = '';
		}
	}
	else{
		decrypt_error = true;
		alert("ERROR: failed to decrypt master password.");
	}
	mydialog1.dialog("close");
}

function checkProviderAuth(provider){
	$.ajax({
		type:'POST',
		url:OC.linkTo('downloader','ajax/getProvider.php'),
		dataType:'json',
		data:{p:provider},
		async:false,
		success:function(s){
			if(s.e){
				return;
			}
			else{
				if(s.a && !downloader_pw_ok){
					decrypting = true;
					// Get username/password for the provider
					checkMasterPw();
					if(!downloader_pw_ok){
						$("#oc_pw_dialog").dialog('open');
					}
				}
				else{
					lsDir($("#folderurl").val(), folder_prov);
				}
			}
		}
	});
}

$(document).ready(function(){

	$('#elt_'+$('#dllist div.elts').size()+' div select').chosen();
	setProvidertitles('#elt_'+$('#dllist div.elts').size());

	$("#geturl").button({text:true}).bind('click',function(){
		var first='';
		$('.elts.new span.dling').html('<img src="'+OC.imagePath('downloader','loader.gif')+'" />');
		getProvider($('.elts.new').first());
	});

	$("#clearList").button({text:true}).bind('click',function(){
		$('.elts.new').each(function(el){
			if($(this).attr('id') != "elt_1"){
				$(this).remove();
			}
		});
		$('.elts.new#elt_1 span.urlc input.url').val('');
		if($('.elts.new#elt_1 .addelt').length==0){
			$('.elts.new#elt_1 .dling').before('<button class="addelt">+</button>');
			$('.elts.new#elt_1 .addelt').bind('click',function(){
				addDownload(true);
				$(this).remove();
			});
		}
		a = 0;
		$('#dllist div.elts span.dling').html('');
	});
	
	$("#savelist").button({text:true}).bind('click',function(){
	  if($("#folder_pop").is(':visible')){
	    $("#folder_pop").slideUp();
	    $("#folder_pop").hide();
	  }
	  if(!$("#save_pop").is(':visible')){
	    $("#save_pop").slideDown();
	  }
	  else{
	    $("#save_pop").slideUp();
	  }
	  $("#save_pop").position({
            of: $("#savelist"),
            my: "right top",
            at: "right bottom"
	  });
	  $("#save_list span.urlc").tipsy({gravity:'s',fade:true});
	  $("#save_list span.overwrite").tipsy({gravity:'s',fade:true});
	  $('#save_list span.overwrite input').unbind().click(function(){});
	  $('#save_list span.overwrite input').bind('click',function(){
	  	if($(this).val() == '1'){
	  		$(this).val('0');
	  	}else{
	  		$(this).val('1');
	  	}
	  });
	  var chosen_file = $('#chosen_file').text().replace(/.*\/([^\/]+)$/,"$1").replace(/^[^_]+_([^_]+)$/,"$1");
	  $('#urllist').val(chosen_file);
	});

	$("#save_pop .elts .urlc input").keypress(function(e) {
		var file_name = $("#save_pop .elts .urlc input").val().trim();
		if(e.which==13 && file_name!=""){
		  var urlList = {};
		  var i = 0;
		  $("#dllist div.elts").filter(':visible').each(function(el){
		    var urlLine = {};
		    urlLine['url'] = $(this).find('.urlc input.url').val().trim();
		    urlLine['overwrite'] = $(this).find('span.overwrite input').is(':checked');
		    urlLine['provider'] = $(this).find('div select').val().trim();
		    if(urlLine['url']!=''){
		      urlList[i] = urlLine;
		    }
		    ++i;
		 });
		  $("#save_pop .elts span.dling").html('<img src="'+OC.imagePath('downloader','loader.gif')+'" />');
		  var myoverw = $('#save_pop .elts span.overwrite input').is(':checked');
		  saveList(file_name, JSON.stringify(urlList), myoverw);
		  $('#chosen_file').text(file_name)
		}
	});

	mydialog0 = $("#dialog0").dialog({//create dialog, but keep it closed
	  title: "Choose file",
	  autoOpen: false,
	  height: 440,
	  width: 620,
	  modal: true,
	  dialogClass: "no-close",
	  buttons: {
	   	"Choose": function() {
	   		readListFile();
	   		mydialog0.dialog("close");
	   	},
	   	"Cancel": function() {
	   		mydialog0.dialog("close");
			}
	  }
	});
	
	$("#chooselist").button({text:true}).bind('click',function(){
	  mydialog0.load("/apps/chooser/").dialog("open");
	});

	$("#clearhistory").button({text:true}).bind('click',function(){
	  updateHistory(1);
	});

	$("#getfolderurl").button({text:true}).bind('click',function(){
	  //$("#folder_pop").toggle(!$('#folder_pop').is(':visible'));
	  if($("#save_pop").is(':visible')){
	    $("#save_pop").slideUp();
	    $("#save_pop").hide();
	  }
	  if(!$("#folder_pop").is(':visible')){
	    $("#folder_pop").slideDown();
	  }
	  else{
	    $("#folder_pop").slideUp();
	  }
	  $("#folder_pop").position({
            of: $("#getfolderurl"),
            my: "right top",
            at: "right bottom"
	  });
	  $("#folder_pop div select").chosen();
	  setProvidertitles("#folder_pop");
	});

	$(".addelt").bind('click',function(){
		addDownload(true);
		$(this).remove();
	});

	$("#folder_pop .elts .urlc input").keypress(function (e) {
		if(e.which!=13){
			return;
		}
		$("#folder_pop .elts span.dling").html('<img src="'+OC.imagePath('downloader','loader.gif')+'" />');
		myurl = $("#folderurl").val();
		var myprov = $("#elt_0 div select").val();
		if(myprov==0){
			$('#elt_0 div select.chzen-select option').each(function(el){
		  	if($(this).text().toLowerCase()==myurl.replace(/^(\w+):\/\/.*$/, "$1").toLowerCase() ||
					$(this).text().toLowerCase()==myurl.replace(/https:\/\//, "http://").replace(/^(\w+):\/\/.*$/, "$1").toLowerCase()){
		    	myprov = $(this).val();
		    	folder_prov = myprov;
		    	if(!downloader_pw_ok){
		    		checkProviderAuth(myprov);
		    	}
		   	}
			});
			if(downloader_pw_ok || myprov===0){
				lsDir(myurl, myprov);
			}
		}
		else{
			if(!downloader_pw_ok){
				folder_prov = myprov;
				checkProviderAuth(myprov);
			}
			else{
				lsDir(myurl, myprov);
			}
		}
	});
	
	$("#geturl").attr("disabled", "disabled");
	$('#elt_1 .urlc input.url').on('input', function(){
	  if($("#geturl").attr("disabled")=="disabled"){
	    $("#geturl").removeAttr("disabled");
	  }
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
				downloader_pw = "";
				downloader_pw_ok = false;
				mydialog1.dialog("close");
			}
		}
	});

 $("#oc_pw_dialog input#downloader_pw").keypress(function (e) {
 	if(e.which==13){
 		pw_ok_func();
 	}
 });

});
