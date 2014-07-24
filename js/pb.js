function getPbPercentDone(){
	var n = parseInt($("pb").last().text());
	if(n!=100){
		$("pb").remove();
	}
	return n;
}

function setProgressBarProgress(percentDone){
	var pb_before = $(".pb_bar .pb_before");
	var pb_after = $(".pb_bar .pb_after");
	var txt = $(".pb_text");
	if(isNaN(percentDone) || pb_before.length==0 || txt.length==0){
		return;
	}
	pb_before.width(percentDone+"%");
	txt.html(percentDone+"%");
}

// Return false in case of error - triggers stop timer.
function detectMsg(){
	var err = $("err");
	if(err.length!=0){
		//$(".pb_bar").remove();
		$(".pb_text").html("<error>"+err.last().text()+"</error>");
		return false;
	}
	var msg = $("msg");
	if(msg.length!=0){
		$(".pb_text").html(msg.last().text());
		msg.remove();
	}
	return true;
}

var timer = 0;

timer = setInterval(function(){
	percentDone = getPbPercentDone();
	setProgressBarProgress(percentDone);
	if(!detectMsg()) {
		clearInterval(timer);
		return;
	}
	if(percentDone==100) {
		clearInterval(timer);
		$(".pb_text").html("100%");
		return;
	}
}, 1000);
