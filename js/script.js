var collect={};
var collect2={};
var selectedDates=[];

function dbInit() {//{{{
	console.log(setup);
	var leaves = TAFFY(setup['leaves']);
	var summary = TAFFY(setup['summary']);

	//friends({gender:"M"}).each(function (r) {
	//	friends({id:r.id}).update({name:"ZZ"});
	//});
	console.log(leaves().select("leave_day"));
}
//}}}
$(function() {//{{{
	dbInit();
	$("body").on("click", "#msg", function(){
		$('#msg').slideUp();
	});
	dbInit();

	$("#leavensky_submit").click(function(){
		$("#collect").val(JSON.stringify(collect));
	});
	console.log("LV", leaveSummary);

	for(var k in leaveSummary) { 
		selectedDates=selectedDates.concat(leaveSummary[k]['dates']);
	}

	$("#preview").html("");
	for(var k in leaveSummary) { 
		$("#preview").append("<br><br><b>"+leaveSummary[k]['full']+"("+leaveSummary[k]['taken']+")</b><br>");
		$("#preview").append(leaveSummary[k]['dates'].join("<br>"));
	}
	$('#preview').slideDown();

	displayCalendar();

});
//}}}
function collectAlt() {//{{{
	// collect and collect2 contain the same data, just organized differently
	var date;
	for(var k in leaveSummary) {
		collect2[k]=[];
	}

	for(var k in collect) {
		collect2[collect[k]].push(moment(new Date(k)).format("YYYY-MM-DD"));
	}

	for(var k in collect) {
		date=new Date(k);
		collect2[collect[k]].sort();
	}

	$("#preview").html("");
	for(var k in collect2) { 
		$("#preview").append("<br><br><b>"+leaveSummary[k]['full']+"("+collect2[k].length+")</b><br>");
		$("#preview").append(collect2[k].join("<br>"));
	}
}
//}}}
function displayCalendar() {//{{{
	var prev=0;
	$("#l"+lType).css("background-color", "#800");
	$(".lradio").click(function(){
		lType=$(this).attr('id').substr(1);
		$(".lradio").css("background-color", "transparent");
		$(this).css("background-color", "#800");
	});

	$('#multi-calendar').DatePicker({
		mode: 'multiple',
		inline: true,
		date: selectedDates,
		starts: 1,
		calendars: 8 ,
		onChange: function(data){
			if(data.length >= prev) { 
				collect[data[data.length-1]]=lType;
				counter=parseInt($("#"+lType).val());
				counter--;
			} else {
				for(var k in collect) { 
					if (!(data.includes(k))) { 
						lType=collect[k];
						delete collect[k];
					}
				}
				counter=parseInt($("#"+lType).val());
				counter++;
			}
			collectAlt();
			$(".lradio").css("background-color", "transparent");
			$("#l"+lType).css("background-color", "#800");

			if(counter < 0) {
				//$("#msg").html(lType+": Exceeded the limit");
				$("#msg").html(lType+": Przesadziłeś z urlopem<br>Musisz wykasować nadmiarowe dni.<br>[Zamknij]");
				$("#msg").slideDown();
			}
			prev=data.length;
			$('#'+lType).val(counter);
			console.log(collect);
			console.log("c2", collect2);
			console.log("data", data);
	}
  });
}
//}}}


