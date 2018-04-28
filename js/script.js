var leaves;
var summary;
var disabled;
var currentType;

function dbInit() {//{{{
	summary  = TAFFY(setup['summary']);
	leaves   = TAFFY();
	disabled = TAFFY();
	for(var i in setup.leaves) { 
		leaves.insert({"lday":setup.leaves[i][0], "ltype":setup.leaves[i][1]});
	}

	for(var i in setup.disabled) { 
		disabled.insert({"disabled":setup.disabled[i]});
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
	if(preview==0) { return; }
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

	if(preview==1) { 
		$('#preview').slideDown();
	}

});
//}}}
function displayCalendar() {//{{{
	// console.log(disabled().select("disabled"));
	$('#multi-calendar').DatePicker({
		mode: 'multiple',
		inline: true,
		date: leaves().select("lday"),
		current: year+"-01",
		starts: 1,
		calendars: 4 ,
		onRenderCell: function(el,date) {
			//var z=moment(new Date(date)).format("YYYY-MM-DD");
			console.log("dis", disabled({disabled:"2018-01-02"}).first());
			return {'disabled': 1};
		},
		onChange: function(data){
			updateDB(data);
			updatePreview();
		}
	});
}
//}}}


