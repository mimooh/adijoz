var collect={};
var collect_alt={};
// lType comes from php
// leaveTypes comes from php
// selectedDates comes from php

$(function() {//{{{
	$("body").on("click", "#msg", function(){
		$('#msg').slideUp();
	});

	$("#leavensky_submit").click(function(){
		$("#collect").val(JSON.stringify(collect));
	});


	$("#preview").html("");
	console.log(selectedDates);
	for(var k in selectedDates) { 
		console.log("k", k);
		// TODO
		$("#preview").append("<br><br><b>"+selectedDates[k]+"("+selectedDates[k].length+")</b><br>");
		//$("#preview").append(selectedDates[k].join("<br>"));
	}
	$('#preview').slideDown();

	displayCalendar();
});
//}}}
function collectAlt() {//{{{
	for(var k in leaveTypes) {
		collect_alt[leaveTypes[k]]=[];
	}

	var date;
	for(var k in collect) {
		collect_alt[collect[k]].push("&nbsp;&nbsp;"+moment(new Date(k)).format("YYYY-MM-DD"));
	}

	for(var k in collect) {
		date=new Date(k);
		collect_alt[collect[k]].sort();
	}

	$("#preview").html("");
	for(var k in collect_alt) { 
		$("#preview").append("<br><br><b>"+k+"("+collect_alt[k].length+")</b><br>");
		$("#preview").append(collect_alt[k].join("<br>"));
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
		date: Object.keys(selectedDates),
		starts: 1,
		calendars: 8 ,
		onChange: function(data){
			if(data.length >= prev) { 
				collect[data[data.length-1]]=lType;
				licznik=parseInt($("#"+lType).val());
				licznik--;
			} else {
				for(var k in collect) { 
					if (!(data.includes(k))) { 
						lType=collect[k];
						delete collect[k];
					}
				}
				licznik=parseInt($("#"+lType).val());
				licznik++;
			}
			collectAlt();
			$(".lradio").css("background-color", "transparent");
			$("#l"+lType).css("background-color", "#800");

			if(licznik < 0) {
				//$("#msg").html(lType+": Exceeded the limit");
				$("#msg").html(lType+": Przesadziłeś z urlopem<br>Musisz wykasować nadmiarowe dni.<br>[Zamknij]");
				$("#msg").slideDown();
			}
			prev=data.length;
			$('#'+lType).val(licznik);
	}
  });
}
//}}}


