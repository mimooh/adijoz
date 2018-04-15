var collect={};
var collect_alt={};
var leave_types=[];

$(function() {//{{{
	$("body").on("click", "#msg", function(){
		$('#msg').slideUp();
	});
	leave_types=$("#leave_types").text().split(",");
	$("#timeoff_submit").click(function(){
		$("#collect").val(JSON.stringify(collect));
	});
	displayCalendar();
});
//}}}
function collectAlt() {//{{{
	for(var k in leave_types) {
		collect_alt[leave_types[k]]=[];
	}

	console.log(collect_alt);
	for(var k in collect) {
		collect_alt[collect[k]].push("&nbsp;&nbsp;"+k);
	}

	$("#t_preview").html("");
	for(var k in collect_alt) { 
		$("#t_preview").append("<br><br><b>"+k+"("+collect_alt[k].length+")</b><br>");
		$("#t_preview").append(collect_alt[k].join("<br>"));
	}
	$('#t_preview').slideDown();
}
//}}}
function displayCalendar() {//{{{
	var typ_urlopu='zalegly';
	var prev_zaznaczonych=0;
	$("#lzalegly").css("background-color", "#800");
	$(".lradio").click(function(){
		typ_urlopu=$(this).attr('id').substr(1);
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
			if(data.length >= prev_zaznaczonych) { 
				collect[data[data.length-1]]=typ_urlopu;
				licznik=parseInt($("#"+typ_urlopu).val());
				licznik--;
			} else {
				for(var k in collect) { 
					if (!(data.includes(k))) { 
						typ_urlopu=collect[k];
						delete collect[k];
					}
				}
				licznik=parseInt($("#"+typ_urlopu).val());
				licznik++;
			}
			collectAlt();
			$(".lradio").css("background-color", "transparent");
			$("#l"+typ_urlopu).css("background-color", "#800");

			if(licznik < 0) {
				$("#msg").html("Przesadziłeś z urlopem: "+typ_urlopu+".<br>Musisz wykasować nadmiarowe dni.<br>[Zamknij]");
				$("#msg").slideDown();
			}
			prev_zaznaczonych=data.length;
			$('#'+typ_urlopu).val(licznik);
	}
  });
}
//}}}
