<?php
session_name('leavensky');
require_once("inc.php");

function head() { /*{{{*/
	echo "
<HTML><HEAD>
<META http-equiv=Content-Type content='text/html; charset=utf-8' />
<title>leavensky</title>
</HEAD>
<link rel='stylesheet' type='text/css' href='css/css.css'>
<link rel='stylesheet' type='text/css' href='css/datepicker.css' />
<script type='text/javascript' src='js/jquery.js'></script>
<script type='text/javascript' src='js/datepicker.js'></script>
<script type='text/javascript' src='js/script.js'></script>
<div id=msg></div>
<div id=preview></div>
";
}
/*}}}*/
function conf_leave_types() {/*{{{*/
	#if(!empty($_SESSION['leave_types'])) {
		$conf=json_decode(file_get_contents("conf.json"),1)['leave_types'];
		$_SESSION['leave_types']=array();
		foreach(array_values($conf) as $v) { 
			$_SESSION['leave_types'][$v[0]]=array('full'=>$v[1]);
		}	
		conf_leave_summary();

		echo "
		<script type='text/javascript'>
			var leaveTypes=".json_encode(array_keys($_SESSION['leave_types'])).";
			var lType=".json_encode($conf[0][0]).";
		</script>
		";
	#}

}
/*}}}*/
function conf_leave_summary() {/*{{{*/
	// Fetch from DB
	$r=$_SESSION['ll']->query("SELECT taken,limits FROM leavensky_summary WHERE leave_user=$1 AND year=$2", array($_SESSION['user_id'], 2018)); 
	$taken=json_decode($r[0]['taken'],1);
	$limits=json_decode($r[0]['limits'],1);

	foreach($_SESSION['leave_types'] as $k=>$v) { 
		$_SESSION['leave_types'][$k]['limits']=$limits[$k];
		#$_SESSION['leave_types'][$k]['left']=$limits[$k] - $taken[$k];
		$_SESSION['leave_types'][$k]['left']=$limits[$k]; # TODO 
	}	
}
/*}}}*/
function selected_dates() {/*{{{*/
	$leaves=[];
	foreach($_SESSION['ll']->query("SELECT * FROM leavensky WHERE leave_user=$1", array($_SESSION['user_id'])) as $v) { 
		$leaves[]=$v['leave_day'];
	}
	$dates=json_encode($leaves);
	echo "
	<script type='text/javascript'>
	    var selectedDates=$dates;
	</script>
	";
}
/*}}}*/
function planner_form() { /*{{{*/
	extract($_SESSION['i18n']);
	$leave_types=$_SESSION['leave_types'];

	$labels='';
	foreach($leave_types as $k=>$v) { 
		$labels.="<th><label class=lradio id='l$k' title='".$v['full']."'>$k</label>";
	}

	$left='';
	foreach($leave_types as $k=>$v) { 
		$left.="<td><input id=$k type=text size=1 name=$k value='$v[left]' disabled>";
	}

	$limits ='';
	foreach($leave_types as $k=>$v) { 
		$limits.="<td><input type=text size=1 value='$v[limits]' disabled>";
	}

	echo "
	<form method=post> 
	<input type=hidden name=collect id=collect>
	<table style='width:1px'>
	<tr>
	<th>$i18n_choose
	$labels
	<tr>
	<td>$i18n_days_left
	$left
	<tr>
	<td>Limit
	$limits
	</table>
	<div id='multi-calendar'> </div>
	<br><br>
	<input id=leavensky_submit type=submit value='OK'>
	</form>
	";

}
/*}}}*/
function submit() { /*{{{*/
	if(empty($_REQUEST['collect'])) { return; }
	$collect=json_decode($_REQUEST['collect'],1); 
	$_SESSION['ll']->query("DELETE FROM leavensky WHERE year=$1 AND leave_user=$2", array($_SESSION['year'],$_SESSION['user_id']));
	foreach($collect as $k=>$v) {
		$date=date('Y-m-d', strtotime($k));
		$_SESSION['ll']->query("INSERT INTO leavensky(year,leave_user,leave_type,leave_day,creator) values($1,$2,$3,$4,666)", array($_SESSION['year'],$_SESSION['user_id'],$v,$date));
	}

	$taken=json_encode(array_count_values($collect));
	$_SESSION['ll']->query("UPDATE leavensky_summary SET taken=$1, creator=666 WHERE year=$2 AND leave_user=$3", array($taken, $_SESSION['year'],$_SESSION['user_id']));
}
/*}}}*/

$_SESSION['user_id']=2;
$_SESSION['year']=2018;
head();
submit();
selected_dates();
conf_leave_types();
planner_form();

?>
