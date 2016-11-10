/**
* ownCloud importer app
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
var elt_num = 0;
var importer_pw = "";
var importer_pw_ok = false;
var max_failed_pw_attemts = 3;
var pw_attempts = 0;
var decrypting = false;
var decrypt_error = false;
var mydialog1;
var folder_prov = '';
var get_el = null;

function get_first_elt(){
	return $('#dllist .elts').first();
}

function get_first_id(){
	var first_elt = get_first_elt();
	return first_elt.attr('id');
}

function get_first_n(){
	var first_id = get_first_id();
	var n_str = first_id.replace('elt_','');
	return parseInt(n_str);
}


function remove_eltdelete(my_elt){
	var len = $('#dllist div.elts').length;
	var b = my_elt.prev();
	if(my_elt.find('.addelt').length>0){
		b.find('.dling').before('<button class="addelt" title="Add another download">+</button>');
		b.find('.addelt').bind('click',function(){
			addDownload(true);
			var first_elt = get_first_elt();
			if($(this).parent().attr('id')==first_elt.attr('id')){
				first_elt.find('.dling').first().before('<button class="eltdelete" title="Remove this download">-</button>');
				first_elt.find('button.eltdelete').first().bind('click',function(el){
					remove_eltdelete($(this).parent());
				});
			}
			if($('#dllist div.elts').length>1){
				$(this).remove();
			}
		});
	}
	my_elt.remove();
	if(len==2){
		var first_elt = get_first_elt();
		first_elt.find('.eltdelete').first().remove();
	}
	$('.tipsy').remove();
}

function addDownload(d, newurl, newprov, newpreserve){
	newurl = newurl || "";
	newprov = newprov || "";
	newpreserve = newpreserve || "0";
	//var a=$('#dllist div.elts').size();
	++elt_num;
	$('#dllist').append('<div id="elt_'+parseInt(elt_num+1)+'" class="elts new">'+$('#hiddentpl').html()+'</div>');

	var myinp = $("#elt_"+parseInt(elt_num+1)+" .urlc input.url");
	if(newurl!="" && (myinp.val()==undefined || myinp.val()=="")){
	  myinp.val(newurl);
	}
	$("#elt_"+parseInt(elt_num+1)+" select").val(newprov);
	
	if(newpreserve=="1"){
		$("#elt_"+parseInt(elt_num+1)+" input.slider-check").attr("value", "1");
		$("#elt_"+parseInt(elt_num+1)+" input.slider-check").attr("checked", "checked");
		$("#elt_"+parseInt(elt_num+1)+" .slider-frame .slider-button").addClass("on");
		$("#elt_"+parseInt(elt_num+1)+" .slider-frame .slider-button").text("nested");
	}
	
	$("#elt_"+parseInt(elt_num+1)+" .addelt").bind('click',function(){
		addDownload(true);
		var first_elt = get_first_elt();
		var first_id = get_first_id();
		if($(this).parent().attr('id')==first_id){
			first_elt.find('.dling').first().before('<button class="eltdelete" title="Remove this download">-</button>');
			first_elt.find('button.eltdelete').first().bind('click',function(){
				remove_eltdelete(first_elt);
			});
		}
		$(this).remove();
	});
	var aa = parseInt(elt_num+1);
	$('#elt_'+aa+' select').chosen({disable_search_threshold: 10, placeholder_text_single: "Data source", allow_single_deselect: true});
	if(d){
		$('#elt_'+aa+' button.eltdelete').bind('click',function(){
			remove_eltdelete($('#elt_'+aa));
		});
	}
	else{
		$('#elt_'+aa+' button.eltdelete').remove();
	}
	$('.tipsy').remove();
	setProvidertitles('#elt_'+aa);
}

function setProvidertitles(e){
	$(e+' span.urlc').tipsy({gravity:'s',fade:true});
	$(e+' span.load').tipsy({gravity:'s',fade:true});
	$(e+' button.addelt').tipsy({gravity:'s',fade:true});
	$(e+' button.eltdelete').tipsy({gravity:'s',fade:true});
	$(e+' div.chzn-container').tipsy({gravity:'s',fade:true});
	$(e+' select.chzen-select').change(function(){
		$(e+' span.urlc').attr('title',t('importer','Type URL'));
	});
	$('a.chzn-single').bind('click',function(){
		$('div.chzn-drop').css('top', '29px');
	});
	$(e+' .slider-frame').tipsy({gravity:'s',fade:true});
	$(e+' .slider-frame .slider-button').toggle(
		function(){
			$(this).addClass('on').html('nested').parent().next('input[type="checkbox"]').attr('checked', 'checked');
			$(this).parent().next('input[type="checkbox"]').attr('value', '1');
		},
		function(){
			$(this).removeClass('on').html('flat').parent().next('input[type="checkbox"]').removeAttr('checked');
			$(this).parent().next('input[type="checkbox"]').attr('value', '0');
	});
}

function getProvider(el){
	get_el = null;
	$(window).bind('beforeunload', function(){
		return false;
	});

	if(decrypting){
		alert("decrypting");
		return;
	};

	var p = el.find('select.chzen-select').val();
	var u = el.find('input.url').val();
	if(p==0){
		el.find('select.chzen-select option').each(function(el){
	    if($(this).text().toLowerCase()==u.replace(/^(\w+):\/\/.*$/, "$1").toLowerCase() ||
				$(this).text().toLowerCase()==u.replace(/https:\/\//, "http://").replace(/^(\w+):\/\/.*$/, "$1").toLowerCase()
			){
	      p = $(this).val();
	    }
	  });
	}
	if(p==0){
		el.find('span.dling').html('<img src="'+OC.imagePath('importer','warning.png')+'" />&nbsp;'+t('importer','Select a provider!'));
	}
	else{
		if(u.length==0){
			el.find('span.dling').html('<img src="'+OC.imagePath('importer','warning.png')+'" />&nbsp;'+t('importer','Provide a file URL!'));
		}
		else{
			$.ajax({
				type:'POST',
				url:OC.linkTo('importer','ajax/getProvider.php'),
				dataType:'json',
				data:{p:p},
				async:false,
				success:function(s){
					if(s.e){
						el.find('span.dling').html('<img src="'+OC.imagePath('importer','warning.png')+'" />&nbsp;'+t('importer', 'Provider does not exist!'));
					}
					else{
						if(s.a && !importer_pw_ok){
							decrypting = true;
							// Get username/password for the provider
							var checkUrl = el.find('input.url').val();
							var checkProvider = el.find('select').val();
							if(checkProvider==0){
								checkProvider = '';
							}
							checkMasterPw(checkUrl, checkProvider);
							if(!importer_pw_ok){
								folder_prov = '';
								get_el = el;
								$("#oc_pw_dialog").dialog('open');
								return;
							}
						}
						el.removeClass('new');
						el.find('span.dling').html('<iframe></iframe>');
						var iframe = el.find('iframe');
						var dlFolder = $('input[name=importer_download_folder]').val();
						var group = $('#user_groups_move_select').val();
						if(group=='home'){
							group = '';
						}
						// Do the actual download
						var iframeUri = OC.linkTo('importer','providers/'+s.n+'.php?u='+u+'&p='+p+'&k='+(el.find('input.slider-check').attr("checked")?1:0)+
								'&o=1'+'&d='+dlFolder+'&g='+group);
						iframe.load(function(){
							var n_str = el.attr('id').replace('elt_','');
							var new_n = parseInt(n_str)+1;
							var new_n_str = '#elt_'+new_n;
							if($(new_n_str).length!=0){
								getProvider($(new_n_str));
							}
							else{
								$(window).unbind('beforeunload');
							}
							if(iframe[0].contentWindow.getPbPercentDone()==100){
								var first_id = get_first_id();
								if(el.attr('id')==first_id){
									el.find('span.urlc input.url').val('');
									el.find('span.dling').html('');
									el.addClass('new');
								}
								else{
									el.remove();
								}
								updateHistory();
							}
						});
						iframe.attr('src', iframeUri);
					}
				}
			});
		}
	}
}

function checkMasterPw(url, provider){
	$.ajax({
		type:'POST',
		url:OC.linkTo('importer','ajax/getUserProviderInfoRaw.php'),
				dataType:'json',
				data:{url:url, provider, provider:provider},
				async:false,
				success:function(s){
					decrypting = false;
					if(typeof s.us_password === 'undefined' || s.us_password.trim()==''){
						importer_pw_ok = true;
					}
					if(s.error){
						alert(s.error);
					}
				},
				error:function(){
					decrypting = false;
				}
	});
	
	if(importer_pw_ok){
		return;
	}
	
	$.ajax({
		type:'POST',
		url:OC.linkTo('importer','ajax/checkMasterPw.php'),
		dataType:'json',
		data:{},
		async:false,
		success:function(s){
			decrypting = false;
			if(s.us_password=='1'){
				importer_pw_ok = true;
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
		url:OC.linkTo('importer','ajax/updateHistory.php'),
		dataType:'json',
		data:{clear:clear},
		async:true,
		success:function(s){
			$('#tbhisto').html('');
			if(!clear){
				$.each(s.h, function(k,v){
					$('#tbhisto').append('<tr><td class="col1">'+v.dl_file+'</td><td class="col2">'+v.dl_ts+'</td><td class="col3">'+v.dl_status+'</td></tr>');
				});
			}
		},
		error:function(){
			alert("Update history failed");
		}
	});
}

function lsDir(url, provider){
	var xhr = $.ajax({
		type:'POST',
		url:OC.linkTo('importer','ajax/lsDir.php'),
		dataType:'json',
		data:{url:url, provider:provider},
		async:true,
		success:function(urls){
			if(urls.error){
				$("#folder_pop .elts span.dling").html('<img src="'+OC.imagePath('importer','warning.png')+'" />&nbsp;'+urls.error);
				return false;
			}
		  $("#folder_pop .elts span.dling").html('');
			var mypreserve = $('#folder_pop .elts input.slider-check').is(':checked');
		  $.each(urls, function(k, v){
		  	if($('#dllist div.elts').filter(':visible').size()==1 && addFirstDownload(v, provider, mypreserve)){
					return true;
		  	}
				$('#dllist div button.addelt').remove();
				addDownload(true, v, provider, mypreserve);
		  });
		},
		error:function(error){
		  $("folder_pop .elts span.dling").html('<img src="'+OC.imagePath('importer','warning.png')+'" />&nbsp;'+t('importer',error));
		}
	});
	if($('.elts .urlc input.url').length>0 && $('.elts .urlc input.url').first().val().trim()!=""){
	  if($("#geturl").attr("disabled")=="disabled"){
	    $("#geturl").removeAttr("disabled");
	  }
	  if($("#savelist").attr("disabled")=="disabled"){
	    $("#savelist").removeAttr("disabled");
	  }
	}
	return xhr;
}

function addFirstDownload(v, myprov, mypreserve){
	var mysel = $('#dllist div.elts').filter(':visible').first();
	var myinp = mysel.find('.urlc input.url').first();
	if(myinp.val()==undefined || myinp.val()==""){
		  myinp.val(v);
		  mysel.find('div.chzn-container').remove();
		  mysel.find('select').toggle(true);
		  mysel.find('select').removeClass('chzn-done');
		  mysel.find('select').val(myprov);
			mysel.find('select').chosen({disable_search_threshold: 10, placeholder_text_single: "Data source", allow_single_deselect: true});
		  mysel.find("input.slider-check").attr("value", mypreserve?"1":"0");
			mysel.find("input.slider-check").attr("checked", mypreserve);
			var aa = parseInt(elt_num+1);
			if(mypreserve=="1"){
				$("#elt_"+aa+" .slider-frame .slider-button").addClass("on");
				$("#elt_"+aa+" .slider-frame .slider-button").text("nested");
			}
			else{
				$("#elt_"+aa+" .slider-frame .slider-button").removeClass("on");
				$("#elt_"+aa+" .slider-frame .slider-button").text("flat");
			}
			mysel.find('button.eltdelete').remove();
			return true;
	}
  return false;
}

function doSaveList(file_name, urls){
	$.ajax({
		type:'POST',
		url:OC.linkTo('importer','ajax/saveList.php'),
		dataType:'json',
		data:{file_name:file_name, list:urls},
		async:false,
		success:function(data, textStatus, jqXHR){
			console.log(jqXHR);
		  if(data==null){
		  	$("#save_pop .elts span.dling").html('<img src="'+OC.imagePath('importer','warning.png')+'" />&nbsp;Nothing returned.');
			}
			else if(data.error){
				$("#save_pop .elts span.dling").html('<img src="'+OC.imagePath('importer','warning.png')+'" />&nbsp;'+data.error);
			}
			else{
		  	$("#save_pop .elts span.dling").html('');
			}
		},
		error:function(jqXHR, textStatus, errorThrown){
		  $("#save_pop .elts span.dling").html('<img src="'+OC.imagePath('importer','warning.png')+'" />&nbsp;'+t('importer', textStatus));
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
		url:OC.linkTo('importer','ajax/readList.php'),
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
				if($('#dllist div.elts').filter(':visible').size()==1 && addFirstDownload(v.url, v.provider, v.preserve)){
					return true;
				}
				$('#dllist div button.addelt').remove();
				addDownload(true, v.url, v.provider, v.preserve);
			});
			$('.elts .urlc input.url').each(function(el){
				if($(this). val().trim()!=""){
					if($("#geturl").attr("disabled")=="disabled"){
						$("#geturl").removeAttr("disabled");
					}
					if($("#savelist").attr("disabled")=="disabled"){
						$("#savelist").removeAttr("disabled");
					}
				}
			});
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
				importer_pw_ok = true;
			}
		},
		error:function(s){
			alert("Unexpected error!");
			importer_pw_ok =  false;
		}
	});
}

function pw_ok_func(){
	importer_pw = $('#importer_pw').val();
	store_master_pw();
	importer_pw = "";
	mydialog1.dialog("close");
	if(importer_pw_ok){
		decrypting = false;
		if(folder_prov!=''){
			lsDir($("#folderurl").val(), folder_prov);
			folder_prov = '';
		}
		else if(get_el!=null){
			getProvider(get_el);
		}
	}
	else{
		decrypt_error = true;
		alert("ERROR: failed to decrypt master password.");
	}
}

function checkProviderAuthForLs(provider){
	$.ajax({
		type:'POST',
		url:OC.linkTo('importer','ajax/getProvider.php'),
		dataType:'json',
		data:{p:provider},
		async:false,
		success:function(s){
			if(s.e){
				return;
			}
			else{
				if(s.a && !importer_pw_ok){
					decrypting = true;
					// Get username/password for the provider
					checkMasterPw($("#folderurl").val(), folder_prov);
					if(!importer_pw_ok){
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

function loadFolderUrl(){
	$("#folder_pop .elts span.dling").html('<img id="folder_loading" src="'+OC.imagePath('importer','loader.gif')+'" />');
	myurl = $("#folderurl").val();
	var myprov = $("#elt_0 select").val();
	var xhr;
	if(myprov==0){
		$('#elt_0 select.chzen-select option').each(function(el){
			if($(this).text().toLowerCase()==myurl.replace(/^(\w+):\/\/.*$/, "$1").toLowerCase() ||
				$(this).text().toLowerCase()==myurl.replace(/https:\/\//, "http://").replace(/^(\w+):\/\/.*$/, "$1").toLowerCase()){
					myprov = $(this).val();
					folder_prov = myprov;
					if(!importer_pw_ok){
						checkProviderAuthForLs(myprov);
					}
				}
		});
		if(importer_pw_ok || myprov===0){
			xhr = lsDir(myurl, myprov);
		}
	}
	else{
		if(!importer_pw_ok){
			folder_prov = myprov;
			checkProviderAuthForLs(myprov);
		}
		else{
			xhr = lsDir(myurl, myprov);
		}
	}
	$('#folder_loading').click(function(el){
		xhr.abort();
		$("#folder_pop .elts span.dling").html('');
	});
}

function saveList(){
	var file_name = $("#save_pop .elts .urlc input").val().trim();
	if( file_name==""){
		$("#save_pop .elts span.dling").html('<img src="'+OC.imagePath('importer','warning.png')+'" />&nbsp;'+t('importer','Provide a file name!'));
		return;
	}
	var urlList = {};
	var i = 0;
	$("#dllist div.elts").filter(':visible').each(function(el){
		var urlLine = {};
		urlLine['url'] = $(this).find('.urlc input.url').val().trim();
		urlLine['preserve'] = $(this).find('input.slider-check').is(':checked');
		urlLine['provider'] = $(this).find('select').val().trim();
		if(urlLine['url']!=''){
			urlList[i] = urlLine;
		}
		++i;
	});
	$("#save_pop .elts span.dling").html('<img src="'+OC.imagePath('importer','loader.gif')+'" />');
	doSaveList(file_name, JSON.stringify(urlList));
	$('#chosen_file').text(file_name)

}

$(document).ready(function(){
	
	$('.btn').tipsy({gravity:'s', fade:true});
	$('.urlc').tipsy({gravity:'s', fade:true});
	$('#toggle_history').tipsy({gravity:'s', fade:true});

	$('#elt_'+$('#dllist div.elts').size()+' select').chosen({disable_search_threshold: 10, placeholder_text_single: "Data source", allow_single_deselect: true});
	setProvidertitles('#elt_'+$('#dllist div.elts').size());

	$("#geturl").button({text:true}).bind('click',function(){
		$('.elts.new span.dling').html('<img src="'+OC.imagePath('importer','loader.gif')+'" />');
		getProvider(get_first_elt());
	});

	$("#loadFolder").button({text:true}).bind('click',function(){
		loadFolderUrl();
	});
	
	$("#clearList").button({text:true}).bind('click',function(){
		var first_elt = $('.elts.new').first();
		var first_id = first_elt.attr("id");
		$('.elts.new').each(function(el){
			if($(this).attr('id')!=first_id){
				$(this).remove();
			}
		});
		first_elt.find('input.url').val('');
		if(first_elt.find('.addelt').length==0){
			var add_elt = first_elt.find('.dling').first().before('<button class="addelt" title="Add another download">+</button>');
			add_elt.bind('click',function(){
				addDownload(true);
				$(this).remove();
			});
		}
		elt_num = 0;
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
	  $("#save_list .slider-frame").tipsy({gravity:'s',fade:true});
		$('#save_list input.slider-check').unbind().click(function(){});
		$('#save_list input.slider-check').bind('click',function(){
	  	if($(this).val() == '1'){
	  		$(this).val('0');
	  	}
	  	else{
	  		$(this).val('1');
	  	}
	  });
	  var chosen_file = $('#chosen_file').text().replace(/.*\/([^\/]+)$/,"$1").replace(/^[^_]+_([^_]+)$/,"$1");
	  $('#urllist').val(chosen_file);
	});

	$("#save_pop .elts .urlc input").keypress(function(e) {
		if(e.which==13){
			saveList();
		}
	});
	
	$("#save_list").bind('click', function(){
		saveList();
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

	$("#clear_history").button({text:true}).bind('click',function(){
	  updateHistory(1);
	});
	
	$('#toggle_history').bind('click',function(){
		if($('#toggle_history').hasClass('icon-angle-right')){
			$('#toggle_history').removeClass('icon-angle-right').addClass('icon-angle-down');
			$('#importer_history').show();
		}
		else{
			$('#toggle_history').removeClass('icon-angle-down').addClass('icon-angle-right');
			$('#importer_history').hide();
		}
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
	  $("#folder_pop select").chosen({disable_search_threshold: 10, placeholder_text_single: "Data source", allow_single_deselect: true});
	  setProvidertitles("#folder_pop");
	});

	$(".addelt").bind('click',function(){
		addDownload(true);
		var first_elt = get_first_elt();
		var first_id = get_first_id();
		if($(this).parent().attr('id')==first_id){
			first_elt.find('.dling').first().before('<button class="eltdelete" title="Remove this download">-</button>');
			first_elt.find('button.eltdelete').first().bind('click',function(){
				remove_eltdelete(first_elt);
			});
		}
		setProvidertitles("#"+$(this).parent().attr('id'));
		$(this).remove();
	});

	$("#folder_pop .elts .urlc input").keypress(function (e) {
		if(e.which!=13){
			return;
		}
		loadFolderUrl();
	});
	
	$("#geturl").attr("disabled", "disabled");
	$('.elts.new .urlc input.url').each(function(el){
		$(this).on('input', function(){
			if($("#geturl").attr("disabled")=="disabled"){
				$("#geturl").removeAttr("disabled");
			}
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
				importer_pw = "";
				importer_pw_ok = false;
				mydialog1.dialog("close");
			}
		}
	});

 $("#oc_pw_dialog input#importer_pw").keypress(function (e) {
 	if(e.which==13){
 		pw_ok_func();
 	}
 });
 
 $('.slider-button').toggle(
	function(){
		$(this).addClass('on').html('nested').parent().next('input[type="checkbox"]').attr('checked', 'checked');
		$(this).parent().next('input[type="checkbox"]').attr('value', '1');
	},
	function(){
	 $(this).removeClass('on').html('flat').parent().next('input[type="checkbox"]').removeAttr('checked');
	 $(this).parent().next('input[type="checkbox"]').attr('value', '0');
	});
 
 // Fill group dropdown
	var group = null;
	if(typeof OCA.Files!== 'undefined' && typeof OCA.Files.App.fileList.getGroup!=='undefined'){
		group = OCA.Files.App.fileList.getGroup();
	}

	$.ajax({
		url: OC.filePath('user_group_admin', 'ajax', 'groups.php'),
		async: false,
		success: function(response) {
			if(response){
				var bookmarks = '';
				$.each( response, function(key,value) {
					$('#user_groups_move_select').append('<option value="'+value.gid+'">'+value.gid+'</option>');
				});
				if(group && group!=null){
					$('#user_groups_move_select').val(group);
				}
				else{
					$('#user_groups_move_select option').filter(function() { 
						return ($(this).text() == 'Home' && $(this).val() == 'home');
				}).prop('selected', true); 
				}
			}
			$('#user_groups_move_select').chosen({disable_search_threshold: 10, placeholder_text_single: "Data source", allow_single_deselect: true});
			$('#user_groups_move_select').change(function(ev){
				$('input[name=importer_download_folder]').val('/');
			});
		}
	});

});
