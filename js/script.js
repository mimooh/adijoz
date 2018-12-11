var leaves;
var summary;
var currentType;

function dbInit() {//{{{
	summary  = TAFFY(setup['summary']);
	leaves   = TAFFY();
	for(var i in setup.leaves) { 
		leaves.insert({"lday":setup.leaves[i][0], "ltype":setup.leaves[i][1]});
	}
}
//}}}
function setCurrentType(e) {//{{{
	currentType=e.target.id.substr(1);
	$(".lradio").css("opacity", 0.2);
	e.target.style.opacity=1;
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
	$("#l"+currentType).css("opacity", 1);

	$(".lradio").on("click", function(e) {
		setCurrentType(e);
	});

	$("body").on("click", "#msg", function(){
		$('#msg').slideUp();
	});

	$("#adijoz_submit").click(function(){
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
	if(setup.user == "admin") {
		displayCalendarAdmin();
	} else {
		displayCalendarUser();
	}

	if(preview==1) { 
		$('#preview').slideDown();
	}

});
//}}}
function displayCalendarUser() {//{{{
	// Calendar for the user
	$('#multi-calendar').DatePicker({
		mode: 'multiple',
		inline: true,
		current: year+"-01",
		date: leaves().select("lday"),
		starts: 1,
		calendars: 12 ,
		onRenderCell: function(el,date) {
			if(setup.holidays.includes(moment(new Date(date)).format("YYYY-MM-DD"))) { 
				return {'holiday': 1};
			}
			return {'holiday': 0};
		},
		onChange: function(data){
			updateDB(data);
			updatePreview();
		}
	});
}
//}}}
function displayCalendarAdmin() {//{{{
	// Calendar for the admin. Admin configures holidays -- the days users should avoid for leaves.
	$('#multi-calendar').DatePicker({
		mode: 'multiple',
		inline: true,
		date: leaves().select("lday"),
		current: year+"-01",
		starts: 1,
		calendars: 12 ,
		onChange: function(data){
			updateDB(data);
			updatePreview();
		}
	});
}
//}}}


