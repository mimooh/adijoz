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
		conf_leave_limits();

		echo "
		<script type='text/javascript'>
			var leaveTypes=".json_encode(array_keys($_SESSION['leave_types'])).";
			var lType=".json_encode($conf[0][0]).";
		</script>
		";
	#}

}
/*}}}*/
function conf_leave_limits() {/*{{{*/
	// Fetch from DB
	foreach($_SESSION['leave_types'] as $k=>$v) { 
		$_SESSION['leave_types'][$k]['limit']=7;
	}	
}
/*}}}*/
function selected_dates() {/*{{{*/
	$leaves=[];
	foreach($_SESSION['ll']->query("SELECT * FROM leavensky") as $v) { 
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
		$left.="<td><input id=$k type=text size=1 name=$k value='$v[limit]' disabled>";
	}

	$limits ='';
	foreach($leave_types as $k=>$v) { 
		$limits.="<td><input type=text size=1 value='$v[limit]' disabled>";
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
	<td>$i18n_days_allocated
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
	foreach(json_decode($_REQUEST['collect'],1) as $k=>$v) {
		$date=date('Y-m-d', strtotime($k));
		$_SESSION['ll']->query("INSERT INTO leavensky(leave_user,leave_type,leave_day,creator) values(1,$1,$2,1)", array($v,$date));
	}
}
/*}}}*/

head();
submit();
selected_dates();
conf_leave_types();
planner_form();

?>
