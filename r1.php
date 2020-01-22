<?php
session_name(getenv("ADIJOZ_SESSION_NAME"));
require_once("inc.php");

$_SESSION['year']=date('Y');

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

function by_departments() { /*{{{*/
	if(empty($_GET['department'])) { return; }
	each_day_of_year();
	$_SESSION['each_day_department']=[];
	foreach($_SESSION['aa']->query("SELECT name,leaves FROM v WHERE department~$1 AND year=$2 ORDER BY name", array($_GET['department'], $_SESSION['year'])) as $r) { 
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
function not_empty() { /*{{{*/
	if(empty($_GET['ne'])) { return; }
	$z=$_SESSION['aa']->query("SELECT department,name FROM v WHERE leaves is not null and year=$1 ORDER BY department,name", array($_SESSION['year'])); 
	echo "Lista osób które podały urlopy";
	echo "<table>";
	$i=1;
	foreach($z as $k=>$v) {
		echo "<tr><td>$i<td>$v[department]<td>$v[name]";
		$i++;
	}
	echo "</table>";
}

/*}}}*/
function each_day_of_year() {/*{{{*/
	if(isset($_SESSION['each_day'][$_SESSION['year']])) { return; }
	$_SESSION['each_day'][$_SESSION['year']]=array();
	$day=strtotime($_SESSION['year']."-01-01");
	$end=strtotime($_SESSION['year']."-12-31");
	while($day <= $end) { 
		$_SESSION['each_day'][$_SESSION['year']][date("Y-m-d", $day)]='';
		$day=strtotime("+1 Day", $day);
	}
}
/*}}}*/

function main() { /*{{{*/
	if(getenv("ADIJOZ_ALLOW_REPORT_R1")!=1) { die("Reporting without passwords needs to be enabled by adding 'export ADIJOZ_ALLOW_REPORT_R1=1' to /etc/apache2/envvars"); }

	head();
	by_departments();
	not_empty();

}
/*}}}*/
main();

?>
