<?php
session_start();

function dd() {/*{{{*/
	echo "<dd>";
	foreach(func_get_args() as $v) {
		echo "<pre>";
		$out=print_r($v,1);
		echo htmlspecialchars($out);
		echo "</pre>";
	}
	echo "<br><br><br><br>";
	echo "</dd>";
}/*}}}*/
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
<div id=t_preview></div>
";
}
/*}}}*/
function leave_types() {/*{{{*/
	#if(!empty($_SESSION['leave_types'])) {
		$conf=array();
		$conf=$_SESSION['leave_types']=array();

		$conf['shorcuts']=array(
			"zalegly"      => "ZAL",
			"wypoczynkowy" => "WYP",
			"dodatkowy"    => "DOD",
			"dwps"         => "DWPS",
			"zl"           => "ZL",
		);

		$conf['titles']=array(
			"zalegly"      => "zaległy",
			"wypoczynkowy" => "wypoczynkowy",
			"dodatkowy"    => "dodatkowy",
			"dwps"         => "dzień wolny po służbie",
			"zl"           => "zwolnienie lekarskie",
		);

		$conf['limits']=array(
			"zalegly"      => 2,
			"wypoczynkowy" => 15,
			"dodatkowy"    => 3,
			"dwps"         => 3,
			"zl"           => 0,
		);

		$_SESSION['leave_types']=$conf;
		$leave_types=implode(",", array_keys($conf['shorcuts']));
		echo "<div id=leave_types>$leave_types</div>";
	#}

}
/*}}}*/
function selected_dates() {/*{{{*/
	$dates=json_encode(array('Fri Jan 12 2018', 'Sat Jan 13 2018'));
	echo "
	<script type='text/javascript'>
	    var selectedDates=$dates;
	</script>
	";
}
/*}}}*/
function planner_form() { /*{{{*/
	$conf=$_SESSION['leave_types'];

	$labels='';
	foreach($conf['shorcuts'] as $k=>$v) { 
		$labels.="<th><label class=lradio id='l$k'  title='".$conf['titles'][$k]."'>$v</label>";

	}

	$left='';
	foreach($conf['limits'] as $k=>$v) { 
		$left.="<td><input id=$k type=text size=1 name=$k value='$v' disabled>";
	}

	$limits ='';
	foreach($conf['limits'] as $k=>$v) { 
		$limits.="<td><input type=text size=1 value='$v' disabled>";
	}

	echo "
	<form method=post> 
	<input type=hidden name=collect id=collect>
	<table style='width:1px'>
	<tr>
	<th>Wybierz
	$labels
	<tr>
	<td>Pozostało
	$left
	<tr>
	<td>Przydział
	$limits
	</table>
	<div id='multi-calendar'> </div>
	<br><br>
	<input id=timeoff_submit type=submit value='OK'>
	</form>
	";

}
/*}}}*/
function output() { /*{{{*/
	echo "\$_REQUEST";
	#$dates=array_map('strtotime', explode(",", $_REQUEST['collect']));
	dd(json_decode($_REQUEST['collect']));
}
/*}}}*/

head();
selected_dates();
leave_types();
planner_form();
output();

?>
