function chooseDownloadFolder(folder){
  $('[name=importer_download_folder]').val(folder)
}

function stripTrailingSlash(str) {
  if(str.substr(-1)=='/') {
	str = str.substr(0, str.length - 1);
  }
  if(str.substr(1)!='/') {
	str = '/'+str;
  }
  return str;
}

function stripLeadingSlash(str) {
  if(str.substr(0,1)=='/') {
	str = str.substr(1, str.length-1);
  }
  return str;
}

$(document).ready(function(){

  choose_download_folder_dialog = $("div.importer_folder_dialog").dialog({//create dialog, but keep it closed
	title: "Choose download folder",
	// autoOpen: false,
	// height: 440,
	// width: 620,
	// modal: true,
    dialogClass: "oc-dialog",
    autoOpen: false,
    resizeable: false,
    draggable: false,
    height: 600,
    width: 720,
	buttons: {
	  "Choose": function() {
		folder = stripTrailingSlash($('#download_folder').text());
		chooseDownloadFolder(folder);
		choose_download_folder_dialog.dialog("close");
	  },
	  "Cancel": function() {
		choose_download_folder_dialog.dialog("close");
	  }
	}
  });

  $('.importer_choose_download_folder').live('click', function(){
	choose_download_folder_dialog.dialog('open');
	choose_download_folder_dialog.show();
	folder = stripLeadingSlash($('[name=importer_download_folder]').val());
	$('.importer_folder_dialog div.loadFolderTree').fileTree({
	  //root: '/',
	  script: '../../apps/chooser/jqueryFileTree.php',
	  multiFolder: false,
	  selectFile: false,
	  selectFolder: true,
	  folder: folder,
	  file: ''
	},
	// single-click
	function(file) {
	  $('#download_folder').text(file);
	},
	// double-click
	function(file) {
	  if(file.indexOf("/", file.length-1)!=-1){// folder double-clicked
		chooseDownloadFolder(file);
		choose_download_folder_dialog.dialog("close");
	  }
	});
  });

});
