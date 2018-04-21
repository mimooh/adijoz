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
";
}
/*}}}*/
function form() { /*{{{*/
	extract($_SESSION['i18n']);

	$conf=json_decode(file_get_contents("conf.json"),1)['leave_titles'];
	$titles=[];
	foreach($conf as $t) {
		$titles[$t[0]]=$t[1];
	}

	echo "
	<form method=post> 
	<br><br>Configuration for 
	<input type=year name=year size=4 value=".$_SESSION['year'].">.
	Once the users started to fill the data for the year that you set now, you cannot not modify the table below (DELETE/INSERT in db).<br><br> 
	<table> 
	<tr><th>Name<th colspan=2>".join("<th colspan=2>",$titles);

	foreach($_SESSION['ll']->query("SELECT * FROM v WHERE year=$1 OR year IS NULL", array($_SESSION['year'])) as $r) { 
		$zeroes=array();
		foreach(array_keys($titles) as $k) { 
			$zeroes[$k]=0;
		}
		$limits=json_decode($r['limits'],1);
		$taken=json_decode($r['taken'],1);
		if(empty($limits)) { $limits=$zeroes; }
		if(empty($taken))  { $taken=$zeroes; }
		echo "<tr><td>$r[name]($r[id])";
		$bg="";
		foreach($limits as $k=>$i) { 
			if($taken[$k] > $limits[$k]) { $bg="style='background-color: #a00'"; }
			if($taken[$k] < $limits[$k]) { $bg="style='background-color: #08a'"; }

			echo "<td><input size=2 value=$i name=collect[$r[id]][$k]><td $bg>".$taken[$k];
			$bg="";
		}
	}

	echo "
	</table>
	<input type=submit value='OK'><br>
	<br><br>
	</form>
	";

}
/*}}}*/
function submit() { /*{{{*/
	if(empty($_REQUEST['collect'])) { return; }
	$_SESSION['year']=$_REQUEST['year'];
	$_SESSION['ll']->query("DELETE FROM leavensky WHERE year=$1", array($_SESSION['year']));
	foreach($_REQUEST['collect'] as $k=>$v) {
		$_SESSION['ll']->query("INSERT INTO leavensky (limits,creator_id,user_id,year) VALUES($1,$2,$3,$4)", array(json_encode($v), $_SESSION['creator_id'], $k, $_SESSION['year']));
	}
}
/*}}}*/

$_SESSION['creator_id']=666;
$_SESSION['year']=date('Y');
head();
submit();
form();

?>
