<?php
session_name(getenv("LEAVENSKY_SESSION_NAME"));
require_once("inc.php");

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
function form_limits() { /*{{{*/
	extract($_SESSION['i18n']);

	$conf=json_decode(file_get_contents("conf.json"),1)['leave_titles'];
	$titles=[];
	foreach($conf as $t) {
		$titles[$t[0]]=$t[1];
	}

	echo "
	<form method=post>
	<br><br>Year
	<input type=text name=change_year size=4 value=".$_SESSION['year'].">
	<input type=submit name='submit_year' value='set'>
	</form>

	<form method=post> 
	<table> 
	<tr><th>block <help title='".$i18n_meaning_of_block."'></help> <th>name<th colspan=2>".join("<th colspan=2>",$titles);

	foreach($_SESSION['ll']->query("SELECT * FROM v WHERE year=$1 ORDER BY name", array($_SESSION['year'])) as $r) { 
		$zeroes=array();
		foreach(array_keys($titles) as $k) { 
			$zeroes[$k]=0;
		}
		$limits=json_decode($r['limits'],1);
		$taken=json_decode($r['taken'],1);

		if(empty($limits))     { $limits=$zeroes; }
		if(empty($taken))      { $taken=$zeroes; }
		if(empty($r['block'])) { $r['block']=0; }

		echo "<tr><td><input autocomplete=off class=block_$r[block] type=text name=block[$r[user_id]] value='$r[block]' size=1>";
		echo "<td><a class=rlink target=_ href='leavensky.php?id=$r[user_id]'>$r[name]($r[user_id])</a>";
		$bg="";
		foreach($limits as $k=>$i) { 
			if($taken[$k] > $limits[$k]) { $bg="style='background-color: #a00'"; }
			if($taken[$k] < $limits[$k]) { $bg="style='background-color: #08a'"; }

			echo "<td><input autocomplete=off size=2 value=$i name=collect_limits[$r[user_id]][$k]><td $bg>".$taken[$k];
			$bg="";
		}
	}

	echo "
	</table>

	<div style='display:inline-block'>
		<input type=submit value='OK'>
		<help title='".$i18n_admin_submit_year."'></help>
		<br>
	</div>
	<br><br>
	</form>
	";

}
/*}}}*/
function submit_calendar() { /*{{{*/
	if(empty($_REQUEST['collect'])) { return; }
	$collect=json_decode($_REQUEST['collect'],1);

	foreach($collect['leaves'] as $key => $row) {
		$date[$key] = $row[0];
		$type[$key] = $row[1];
	}
	array_multisort($date, SORT_ASC,  $collect['leaves']);
	$_SESSION['ll']->query("UPDATE leavensky SET block=1, leaves=$1, taken=$2, creator_id=$3 WHERE year=$4 AND user_id=-1", array(json_encode($collect['leaves']), json_encode($collect['taken']), $_SESSION['creator_id'], $_SESSION['year']));
}
/*}}}*/
function submit_limits() { /*{{{*/
	if(empty($_REQUEST['collect_limits'])) { return; }
	foreach($_REQUEST['collect_limits'] as $k=>$v) {
		$_SESSION['ll']->query("UPDATE leavensky SET block=$1, limits=$2, creator_id=$3 WHERE user_id=$4 AND year=$5", array($_REQUEST['block'][$k], json_encode($v), $_SESSION['creator_id'], $k, $_SESSION['year']));
	}
}
/*}}}*/
function assert_years_ok() {/*{{{*/
	// Make sure that for a requested year each person from people 
	// has a record in leavensky table

	$year_entries=[];
	foreach($_SESSION['ll']->query("SELECT user_id FROM leavensky WHERE year=$1", array($_SESSION['year'])) as $r) { 
		$year_entries[]=$r['user_id'];
	}

	foreach($_SESSION['ll']->query("SELECT id FROM people ORDER BY name") as $r) {
		if(!in_array($r['id'], $year_entries)){ 
			$_SESSION['ll']->query("INSERT INTO leavensky(user_id, year) VALUES($1,$2)", array($r['id'], $_SESSION['year']));
		}
	}

}
/*}}}*/
function init_year() {/*{{{*/
	#$_SESSION['ll']->query("DELETE FROM leavensky WHERE user_id=-1");
	$r=$_SESSION['ll']->query("SELECT * FROM leavensky WHERE user_id=-1 AND year=$1", array($_SESSION['year']));
	if(!empty($r)) { return; }

	$conf=json_decode(file_get_contents("conf.json"),1)['leave_titles'];
	foreach($conf as $t) {
		$taken[$t[0]]=0;
	}

	$_SESSION['ll']->query("INSERT INTO leavensky(user_id,year,taken,limits) VALUES(-1,$1,$2,$2)", array($_SESSION['year'], json_encode($taken)));
	
}
/*}}}*/
function setup_year() {/*{{{*/
	if(isset($_REQUEST['submit_year'])) { 
		$_SESSION['year']=$_REQUEST['change_year'];
	} 
	if(empty($_SESSION['year'])) { 
		$_SESSION['year']=date('Y');
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
	$submitter.="<div style='display:inline-block'>";
	$submitter.="<input id=leavensky_submit type=submit>";
	$submitter.="<help title='".$i18n_howto_disabled_days."'></help>";

	$submitter.="</div><br>";
	return $submitter;
}
/*}}}*/
function form_calendar() { /*{{{*/
	$submitter=calendar_submitter();
	echo "
	<hr>
	<form method=post> 
	<input type=hidden name=collect id=collect>
	<div style='display:inline-block'>
		<div id='multi-calendar' style='float:left'></div>
	</div>
	<br><br>
	$submitter
	</form>
	";

}
/*}}}*/
function db() {/*{{{*/
	dd($_SESSION['ll']->query("SELECT * FROM leavensky WHERE year=$1", array($_SESSION['year'])));
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

	$r=$_SESSION['ll']->query("SELECT taken,limits,leaves FROM leavensky WHERE user_id=-1 AND year=$1", array($_SESSION['year']));
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

$_SESSION['creator_id']=666;
$_SESSION['leavensky_admin']=1;
#db();
head();
setup_year();
assert_years_ok();
submit_limits();
submit_calendar();
db_read();
form_limits();
form_calendar();

?>
