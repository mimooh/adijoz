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
	<br><br>Year
	<input type=text name=change_year size=4 value=".$_SESSION['year'].">
	<input type=submit name='submit_year' value='set'>
	</form>

	<form method=post> 
	<table> 
	<tr><th>Name<th colspan=2>".join("<th colspan=2>",$titles);

	foreach($_SESSION['ll']->query("SELECT * FROM v WHERE year=$1 ORDER BY name", array($_SESSION['year'])) as $r) { 
		$zeroes=array();
		foreach(array_keys($titles) as $k) { 
			$zeroes[$k]=0;
		}
		$limits=json_decode($r['limits'],1);
		$taken=json_decode($r['taken'],1);
		if(empty($limits)) { $limits=$zeroes; }
		if(empty($taken))  { $taken=$zeroes; }
		echo "<tr><td><a class=rlink href='?details=$r[user_id]'>$r[name]($r[user_id])</a>";
		$bg="";
		foreach($limits as $k=>$i) { 
			if($taken[$k] > $limits[$k]) { $bg="style='background-color: #a00'"; }
			if($taken[$k] < $limits[$k]) { $bg="style='background-color: #08a'"; }

			echo "<td><input size=2 value=$i name=collect[$r[user_id]][$k]><td $bg>".$taken[$k];
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
	foreach($_REQUEST['collect'] as $k=>$v) {
		$_SESSION['ll']->query("UPDATE leavensky SET limits=$1, creator_id=$2 WHERE user_id=$3 AND year=$4", array(json_encode($v), $_SESSION['creator_id'], $k, $_SESSION['year']));
		#$_SESSION['ll']->querydd("UPDATE leavensky SET limits=$1, creator_id=$2 WHERE user_id=$3 AND year=$4", array(json_encode($v), $_SESSION['creator_id'], $k, $_SESSION['year']));
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
function setup_year() {/*{{{*/
	if(isset($_REQUEST['submit_year'])) { 
		$_SESSION['year']=$_REQUEST['change_year'];
	} 
	if(empty($_SESSION['year'])) { 
		$_SESSION['year']=date('Y');
	}
}
/*}}}*/
function details() {/*{{{*/
	$r=$_SESSION['ll']->query("SELECT * FROM v WHERE user_id=$1 AND year=$2", array($_GET['details'], $_SESSION['year']))[0];
	$leaves=json_decode($r['leaves']);
	echo "<br><br>$r[name]<table style='width:0'>";
	$i=1;
	foreach($leaves as $d) { 
		echo "<tr><td>$i<td>$d[0]<td>$d[1]";
		$i++;
	}
	echo "</table>";

	$taken=json_decode($r['taken']);
	echo "<table style='width:0'><tr>";
	foreach($taken as $k=>$v) { 
		echo "<td>$k: $v";
	}
	echo "</table>";
	
}
/*}}}*/
$_SESSION['creator_id']=666;
head();
details();
setup_year();
assert_years_ok();
submit();
form();

?>
