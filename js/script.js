var leaves;
var summary;
var currentType;

function dbInit() {//{{{
	leaves = TAFFY();
	summary = TAFFY(setup['summary']);
	for(var i in setup.leaves) { 
		leaves.insert({"lday":setup.leaves[i][0], "ltype":setup.leaves[i][1]});
	}
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
	$("#preview").css("background-color", "#064");
	var c;
	for(var k in setup['titles']) { 
		c=leaves({ltype:k}).select("lday");
		if(c.length > setup['summary']['limits'][k]) {
			$("#preview").css("background-color", "#a00");
		}
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
		var collect={};
		collect['leaves']=leaves().select("lday", "ltype");
		collect['taken']={};

		var c;
		for(var k in setup['titles']) { 
			c=leaves({ltype:k}).select("lday");
			collect['taken'][k]=c.length; 
		}
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
		calendars: 4 ,
		onChange: function(data){
			updateDB(data);
			updatePreview();
		}
	});
}
//}}}


