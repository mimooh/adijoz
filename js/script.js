var leaves;
var summary;
var currentType;

function dbInit() {//{{{
	leaves = TAFFY(setup['leaves']);
	summary = TAFFY(setup['summary']);
	leaves.insert({"lday":"2018-01-05", "ltype":"zal"});
	leaves.insert({"lday":"2018-01-06", "ltype":"zal"});
	leaves.insert({"lday":"2018-02-06", "ltype":"wyp"});
	leaves.insert({"lday":"2018-02-07", "ltype":"wyp"});
	leaves.insert({"lday":"2018-02-08", "ltype":"wyp"});
}
//}}}
function setCurrentType(e) {//{{{
	currentType=e.target.id.substr(1);
	$(".lradio").css("background-color", "transparent");
	e.target.style.backgroundColor="#800";
}
//}}}

function updateDB(data) {//{{{
	var db=leaves().select("lday");
	for(var day in db) {
		if(! data.includes(db[day])) { 
			leaves({"lday":db[day]}).remove();
		}
	}

	for(var day in data) {
		if(! db.includes(data[day])) { 
			leaves.insert({"lday":data[day], "ltype":currentType});
		}
	}
}

//}}}
function updatePreview() {//{{{
	$("#preview").html("");
	var c;
	for(var k in setup['titles']) { 
		c=leaves({ltype:k}).select("lday");
		$("#preview").append("<br><br><b>"+setup['titles'][k]+"("+c.length+"/"+setup['summary']['limits'][k]+")</b><br>");
		$("#preview").append("&nbsp;&nbsp;"+c.join("<br>&nbsp;&nbsp;"));
	}
}

//}}}
$(function() {//{{{
	dbInit();
	for (currentType in setup['titles']) break; // init currentType to the first elem
	$("#l"+currentType).css("background-color", "#800");

	$(".lradio").on("click", function(e) {
		setCurrentType(e);
	});

	$("body").on("click", "#msg", function(){
		$('#msg').slideUp();
	});

	$("#leavensky_submit").click(function(){
		$("#collect").val(JSON.stringify(collect));
	});
	updatePreview();
	displayCalendar();
	$('#preview').slideDown();

});
//}}}
function displayCalendar() {//{{{
	$('#multi-calendar').DatePicker({
		mode: 'multiple',
		inline: true,
		date: leaves().select("lday"),
		starts: 1,
		calendars: 8 ,
		onChange: function(data){
			updateDB(data);
			updatePreview();
		}
	});
}
//}}}


