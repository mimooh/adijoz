<?php
session_name(getenv("ADIJOZ_SESSION_NAME"));
require_once("inc.php");

# Init year for the users. Leave just limits that were set by the admin:
# psql adijoz -c "UPDATE adijoz SET taken=null, leaves=null, block=0 WHERE year=2018";
# psql adijoz -c "SELECT * FROM adijoz WHERE user_id=25 AND year=2018";

function head() { /*{{{*/
	echo "
<HTML><HEAD>
<META http-equiv=Content-Type content='text/html; charset=utf-8' />
<title>admin</title>
</HEAD>
<link rel='stylesheet' type='text/css' href='css/css.css'>
<link rel='stylesheet' type='text/css' href='css/datepicker.css' />
<script type='text/javascript' src='js/jquery.js'></script>
<script type='text/javascript' src='js/taffy-min.js'></script>
<script type='text/javascript' src='js/moment.min.js'></script>
<script type='text/javascript' src='js/datepicker.js'></script>
<script type='text/javascript' src='js/script.js'></script>
";
}
/*}}}*/

function menu() {/*{{{*/
	echo "
	<form method=post>
	Year <input type=text name=change_year size=4 value=".$_SESSION['year'].">
	<input type=submit name='submit_year' value='set'>
	</form>
	<a class=blink href=?limits_view>Limits View</a> 
	<a class=blink href=?department>Departments View</a> 
	<a class=blink href=?summary>Summary</a> 
	";
}
/*}}}*/
function form_limits() { /*{{{*/
	extract($_SESSION['i18n']);

	$conf=json_decode(file_get_contents("conf.json"),1)['leave_titles'];
	$titles=[];
	foreach($conf as $t) {
		$titles[$t[0]]=$t[1];
	}

	echo "
	<form method=post> 
	<table> 
	<tr><th>nr<th>block<help title='".$i18n_meaning_of_block."'></help><th>department<th>name<th colspan=3>".join("<th colspan=3>",$titles);

	$ii=1;
	foreach($_SESSION['aa']->query("SELECT * FROM v WHERE year=$1 ORDER BY department,name", array($_SESSION['year'])) as $r) { 
		$limits=json_decode($r['limits'],1);
		$taken=json_decode($r['taken'],1);

		foreach($titles as $k=>$v) { 
			if(empty($limits[$k])) { $limits[$k]=0; }
			if(empty($taken[$k]))  { $taken[$k]=0; }
		}

		if(empty($r['block'])) { $r['block']=0; }
		if(isset($_GET['block_all']) && $_GET['block_all']==1) { $r['block']=1; }
		if(isset($_GET['block_all']) && $_GET['block_all']==0) { $r['block']=0; }

		echo "<tr><td>$ii";
		echo "<td><input autocomplete=off class=block_$r[block] type=text name=block[$r[user_id]] value='$r[block]' size=1>";
		echo "<td>$r[department]";
		echo "<td><span style='white-space:nowrap'><a class=rlink target=_ href='adijoz.php?id=$r[user_id]'>$r[name] ($r[user_id])</a></span>";
		$bg="";

		foreach(array_keys($titles) as $k) { 
			if($taken[$k] > $limits[$k]) { $bg="style='background-color: #a00'"; }
			if($taken[$k] < $limits[$k]) { $bg="style='background-color: #08a'"; }

			echo "<td style='padding-left:40px; opacity:0.3'>$k<td><input autocomplete=off size=2 value=".$limits[$k]." name=collect_limits[$r[user_id]][$k]><td $bg>".$taken[$k];
			$bg="";
		}
		$ii++;
	}

	echo "
	</table>
		<input type=submit value='Save'>
		<a class=blink href=?limits_view&block_all=1>BlockAll 1</a> 
		<a class=blink href=?limits_view&block_all=0>BlockAll 0</a> 

		<help title='".$i18n_admin_submit_year."'></help>
	<br><br>
	</form>
	";

}
/*}}}*/
function summary() { /*{{{*/
	if(!isset($_GET['summary'])) { return; }
	$conf=json_decode(file_get_contents("conf.json"),1)['leave_titles'];
	$titles=[];
	foreach($conf as $t) {
		$titles[$t[0]]=$t[1];
	}

	$stats=['err'=>[], 'ok'=>[] ];
	foreach($_SESSION['aa']->query("SELECT * FROM v WHERE year=$1 ORDER BY department,name", array($_SESSION['year'])) as $r) { 
		$limits=json_decode($r['limits'],1);
		$taken=json_decode($r['taken'],1);

		foreach($titles as $k=>$v) { 
			if(empty($limits[$k])) { $limits[$k]=0; }
			if(empty($taken[$k]))  { $taken[$k]=0; }
		}
		$err=0;
		foreach($limits as $k=>$i) { 
			if($k != 'nz') { // yeah, some special case in our institution only (hopefully)
				if($taken[$k] != $limits[$k]) { $err=1; }
			}
		}
		if($err==1) {
			$stats['err'][]="$r[department]<td>$r[name]";
		} else {
			$stats['ok'][]="$r[department]<td>$r[name]";
		}
	}
	echo "<table><tr><th>nr<th>department<th>name<th>err";
	$ii=1;
	foreach($stats['err'] as $v) {
		echo "<tr><td>$ii<td>$v<td>1";
		$ii++;
	}
	echo "</table>";

	echo "<table><tr><th>nr<th>department<th>name<th>err";
	$ii=1;
	foreach($stats['ok'] as $v) {
		echo "<tr><td>$ii<td>$v<td>0";
		$ii++;
	}
	echo "</table>";

	exit();
}
/*}}}*/
function submit_calendar() { /*{{{*/
	if(empty($_REQUEST['collect'])) { return; }
	$collect=json_decode($_REQUEST['collect'],1);

	foreach($collect['leaves'] as $key => $row) {
		$date[$key] = $row[0];
		$type[$key] = $row[1];
	}
	if(!empty($collect['leaves'])) {  
		array_multisort($date, SORT_ASC,  $collect['leaves']);
	}
	$_SESSION['aa']->query("UPDATE adijoz SET block=1, leaves=$1, taken=$2 WHERE year=$3 AND user_id=-1", array(json_encode($collect['leaves']), json_encode($collect['taken']), $_SESSION['year']));
}
/*}}}*/
function submit_limits() { /*{{{*/
	if(empty($_REQUEST['collect_limits'])) { return; }
	foreach($_REQUEST['collect_limits'] as $k=>$v) {
		$_SESSION['aa']->query("UPDATE adijoz SET block=$1, limits=$2 WHERE user_id=$3 AND year=$4", array($_REQUEST['block'][$k], json_encode($v), $k, $_SESSION['year']));
	}
}
/*}}}*/
function assert_years_ok() {/*{{{*/
	// Make sure that for a requested year each person from people 
	// has a record in adijoz table

	$year_entries=[];
	foreach($_SESSION['aa']->query("SELECT user_id FROM adijoz WHERE year=$1", array($_SESSION['year'])) as $r) { 
		$year_entries[]=$r['user_id'];
	}

	foreach($_SESSION['aa']->query("SELECT id FROM people ORDER BY name") as $r) {
		if(!in_array($r['id'], $year_entries)){ 
			$_SESSION['aa']->query("INSERT INTO adijoz(user_id, year) VALUES($1,$2)", array($r['id'], $_SESSION['year']));
		}
	}

}
/*}}}*/
function init_year() {/*{{{*/
	#$_SESSION['aa']->query("DELETE FROM adijoz WHERE user_id=-1");
	$r=$_SESSION['aa']->query("SELECT * FROM adijoz WHERE user_id=-1 AND year=$1", array($_SESSION['year']));
	if(!empty($r)) { return; }

	$conf=json_decode(file_get_contents("conf.json"),1)['leave_titles'];
	foreach($conf as $t) {
		$taken[$t[0]]=0;
	}

	$_SESSION['aa']->query("INSERT INTO adijoz(user_id,year,taken,limits) VALUES(-1,$1,$2,$2)", array($_SESSION['year'], json_encode($taken)));
	
}
/*}}}*/
function setup_year() {/*{{{*/
	if(isset($_REQUEST['submit_year'])) { 
		$_SESSION['year']=$_REQUEST['change_year'];
	} 
	if(empty($_SESSION['year'])) { 
		$_SESSION['year']=date('Y');
		#$_SESSION['year']=2019;
	}

	echo "
	<script type='text/javascript'>
		var year=".$_SESSION['year'].";
	</script>
	";
	init_year();
}
/*}}}*/
function calendar_submitter() { /*{{{*/
	extract($_SESSION['i18n']);
	$submitter='';
	$submitter.="<input id=adijoz_submit type=submit>";
	$submitter.="<help title='".$i18n_howto_holidays."'></help>";
	$submitter.="<br>";
	return $submitter;
}
/*}}}*/
function form_calendar() { /*{{{*/
	$submitter=calendar_submitter();
	echo "
	<br><br>
	<form method=post> 
	".$_SESSION['i18n']['i18n_disabled_from_planning']."
	$submitter
	<input type=hidden name=collect id=collect>
	<div style='display:inline-block'>
		<div id='multi-calendar' style='float:left'></div>
	</div>
	<br><br>
	</form>
	";

}
/*}}}*/
function db_read() {/*{{{*/
	extract($_SESSION['i18n']);
	$_SESSION['setup']=[];
	$_SESSION['setup']['titles']=[];
	$conf=json_decode(file_get_contents("conf.json"),1)['leave_titles'];
	foreach($conf as $t) {
		$_SESSION['setup']['titles'][$t[0]]=$t[1];
	}

	$r=$_SESSION['aa']->query("SELECT taken,limits,leaves FROM adijoz WHERE user_id=-1 AND year=$1", array($_SESSION['year']));
	if(empty($r)) { die("$i18n_year_not_prepared ".$_SESSION['year']); }
	$taken=json_decode($r[0]['taken'],1);
	$limits=json_decode($r[0]['limits'],1);
	$leaves=json_decode($r[0]['leaves'],1);
	$_SESSION['setup']["summary"]=array('taken'=>$taken, 'limits'=>$limits); 
	$_SESSION['setup']["leaves"]=$leaves;
	$_SESSION['setup']["user"]="admin";
	if(empty($leaves)) { 
		$leaves=[];
	}
	foreach($leaves as $v) {
		$_SESSION['setup']['disabled'][]=$v[0];
	}

	echo "
	<script type='text/javascript'>
		var setup=".json_encode($_SESSION['setup']).";
		var preview=0;
	</script>
	";

}
/*}}}*/

function list_departments() {/*{{{*/
	echo "<br><br><br>By departments:<br>";
	foreach($_SESSION['aa']->query("SELECT DISTINCT department FROM people ORDER BY department") as $r) { 
		echo "<a class=blink href=?department=$r[department]>$r[department]</a>";
	}
}
/*}}}*/
function by_departments() { /*{{{*/
	if(empty($_GET['department'])) { return; }
	$_SESSION['aa']->each_day_of_year();
	$_SESSION['each_day_department']=[];
	foreach($_SESSION['aa']->query("SELECT name,leaves FROM v WHERE department=$1 AND year=$2 ORDER BY name", array($_GET['department'], $_SESSION['year'])) as $r) { 
		$leaves=[];
		$_SESSION['each_day_department'][$r['name']]=$_SESSION['each_day'][$_SESSION['year']];
		$leaves=json_decode($r['leaves'],1);
		if(!empty($leaves)) { 
			foreach($leaves as $v) {
				$_SESSION['each_day_department'][$r['name']][$v[0]]=$v[1];
			}
		}
	}
	echo "<table>";
	echo "<tr><td>date";
	$names=array_keys($_SESSION['each_day_department']);
	foreach($names as $name) { 
		echo "<td>$name";
	}
	foreach(array_keys($_SESSION['each_day'][$_SESSION['year']]) as $day) {
		echo "<tr><td><span style='white-space:nowrap'>$day</span>";
		 
		foreach($names as $name) { 
			echo "<td>".$_SESSION['each_day_department'][$name][$day];
		}
	}
}

/*}}}*/

function main() { /*{{{*/
	head();

	if(getenv("ADIJOZ_DISABLE_AUTH")==1) { 
		#$_SESSION['home_url']=$_SERVER['SCRIPT_NAME'];
		$_SESSION['user_id']=-1; 
		$_SESSION['user']='Admin';
		$_SESSION['adijoz_admin']=1;
	}
	if(empty($_SESSION['adijoz_admin'])) { $_SESSION['aa']->fatal("Not allowed"); }

	$_SESSION['aa']->logout_button();
	setup_year();
	assert_years_ok();
	submit_limits();
	submit_calendar();
	db_read();
	menu();
	summary();
	if(isset($_GET['limits_view'])) { 
		form_limits();
		form_calendar();
	}
	if(isset($_GET['department'])) { 
		list_departments();
		by_departments();
	}
}
/*}}}*/
main();

?>
