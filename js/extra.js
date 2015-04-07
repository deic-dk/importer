$(document).ready(function(){
  /* 
   * Bind click to provider selector and drop down
   */
  $('tbody#fileList').on('click', 'tr a.dropdown-toggle', function(){
	$(this).siblings('ul').toggle();		
  });

 $('tbody#fileList').on('click', 'tr ul.dropdown-menu li a', function(){
 	var value=$(this).html();
	var id=$(this).parent('li').attr('data-id');

	$(this).parents('ul').siblings('a.pr-value').html(value);
	$(this).parents('ul').siblings('a.pr-value').attr('data-id',id);
	$(this).parents('ul').toggle();
 })

  


})

