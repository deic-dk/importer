function enable(provider, active){
	$.ajax(OC.linkTo('ocdownloader','ajax/enableProvider.php'), {
		 type:'POST',
		  data:{
		   provider: provider,
		   active: active,
		 },
		 dataType:'json',
		 success: function(s){
		 }
	});
}

$(document).ready(function(){
	$('.ocdownloader_provider').each(function(){
	  $(this).change(function(){
	    enable($(this).attr('name'), $(this).is(':checked')?1:0);
	  });
	});
});
