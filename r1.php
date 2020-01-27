<?php

# This script is probably useless outside of my institution

if(getenv("ADIJOZ_ALLOW_REPORT_R1")!=1) { die("Reporting without passwords needs to be enabled by adding 'export ADIJOZ_ALLOW_REPORT_R1=1' to /etc/apache2/envvars and then restarting apache"); }
session_name(getenv("ADIJOZ_SESSION_NAME"));
require_once("inc.php");
if(!isset($_SERVER['SERVER_NAME'])) { $_SESSION['console']=1; }
require_once("r2.php");
$_SESSION['year']=date('Y');

function head() { /*{{{*/
	if(isset($_SESSION['console'])) { return; }
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
	echo "\n<tr><td>date";
	$names=array_keys($_SESSION['each_day_department']);
	foreach($names as $name) { 
		echo "<td>$name";
	}
	foreach(array_keys($_SESSION['each_day'][$_SESSION['year']]) as $day) {
		echo "\n<tr><td><span style='white-space:nowrap'>$day</span>";
		 
		foreach($names as $name) { 
			echo "<td>".$_SESSION['each_day_department'][$name][$day];
		}
	}
	echo "</table>";
	echo " <br> <br> <br> <br> <br> ";
	exit();
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

function leave_titles() { #{{{
	$f=json_decode(file_get_contents("conf.json"),1)['leave_titles'];
	$_SESSION['leave_titles']=array();
	foreach($f as $k=>$v)  {
		$_SESSION['leave_titles'][]=$v[0];
	}
}
/*}}}*/
function conf_time_off() { #{{{
	$r=$_SESSION['aa']->query("select * from adijoz where user_id=-1 and year=$1", array($_SESSION['year']));
	$offs=json_decode($r[0]['leaves'],1);
	dd($offs);
}
/*}}}*/
function read_time_off() { #{{{
	# echo "select * from v" | psql adijoz;
	$r=$_SESSION['aa']->query("select user_id,department,name,email,stopien,leaves,limits from v where leaves is not null and year=$1 order by department,name", array($_SESSION['year']));
	$collect=[];
	foreach($r as $k=>$v) { 
		$leaves=[];
		foreach(json_decode($v['leaves'],1) as $m=>$n) {
			if ($n[1] != 'nz') {
				$leaves[]=$n;
			}
		}

		$limits=json_decode($v['limits'],1);
		$arr=array();
		$arr['name']=$v['name'];
		$arr['email']=$v['email'];
		$arr['stopien']=$v['stopien'];
		$arr['department']=$v['department'];
		$arr['sum_user_planned_leaves']=count($leaves) + $limits['nz'];
		$arr['sum_admin_planned_leaves']=array_sum($limits); 
		$arr['admin_planned_leaves']=$limits;
		$arr['time_off']=array();
		foreach(array('01' ,'02' ,'03', '04' ,'04' ,'05' ,'06' ,'07' ,'08' ,'09' ,'10' ,'11' ,'12' ) as $mc) { 
			$arr['time_off'][$mc]=array();
			foreach($_SESSION['leave_titles'] as $title) {
				$arr['time_off'][$mc][$title]=array();
			}
		}
		foreach($leaves as $ll) {
			$date=explode('-', $ll[0]);
			$title=$ll[1];
			$arr['time_off'][$date[1]][$title][]=intval($date[2]);
		}
		$collect[$v['user_id']]=$arr;
	}
	$_SESSION['collect']=$collect;
}
/*}}}*/
function r2() { #{{{
# NAZWISKO          , IMIĘ      , dodatkowy , wypoczynkowy , zaległy , department , I , II , III , IV , V , VI , VII , VIII , IX , X , XI , XII
# FLISZKIEWICZ      , MATEUSZ   , 10        , 26           , 3       , RN-P/4/1   ,   ,    ,     ,    ,   ,    ,     ,      ,    ,   ,    ,
# KRAUZE            , ANDRZEJ   , 13        , 26           , 0       , RN-P/4/1   ,   ,    ,     ,    ,   ,    ,     ,      ,    ,   ,    ,
# KREŃSKI           , KAROL     , 13        , 26           , 0       , RN-P/4/1   ,   ,    ,     ,    ,   ,    ,     ,      ,    ,   ,    ,
# ŁAZOWY            , STANISŁAW , 13        , 26           , 6       , RN-P/4/1   ,   ,    ,     ,    ,   ,    ,     ,      ,    ,   ,    ,
# WĘSIERSKI         , TOMASZ    , 5         , 26           , 2       , RN-P/4/2   ,   ,    ,     ,    ,   ,    ,     ,      ,    ,   ,    ,

	read_time_off();
	$new=[];
	#dd($_SESSION['collect']);
	foreach($_SESSION['collect'] as $id=>$data) { 
		$new[$id]=$data;
		$new[$id]['time_off']=array();
		foreach($data['time_off'] as $k=>$v) {
			$new[$id]['time_off'][$k]=[];
			foreach($v as $kk=>$vv) {
				if(!empty($vv)) { 
					$count=count($vv);
					$new[$id]['time_off'][$k][]="($count $kk) ".implode(",",$vv);
				} 
			}
		}
	}
	r2_to_html($new);

}
/*}}}*/
function r2_to_html($collect) { #{{{
	$lp=1;
	echo "<table>";
	echo "\n<tr><th>lp<th>komórka<th>mundur<th>nazwisko i imię<th>zaplanował<th>zal+wyp+dod+nz<th>I<th>II<th>III<th> IV <th>V <th>VI <th>VII <th>VIII <th>IX <th>X <th>XI <th>XII";
	$faulty=[];
	foreach($collect as $k=>$v) {
		if($v['sum_user_planned_leaves'] != $v['sum_admin_planned_leaves']) { 
			$v['sum_user_planned_leaves']="<div style='background-color:red'>$v[sum_user_planned_leaves]</div>"; 
			$faulty[]=$v['email'];
		}
		if(!empty($v['stopien'])) { $funkcjonariusz=1; } else { $funkcjonariusz=0; }
		echo "\n<tr><td>$lp<td>$v[department]<td>$funkcjonariusz<td style='white-space: nowrap'>$v[name]<td>$v[sum_user_planned_leaves]<td>".
		$v['admin_planned_leaves']['zal'].
		"+".$v['admin_planned_leaves']['wyp'].
		"+".$v['admin_planned_leaves']['dod'].
		"+".$v['admin_planned_leaves']['nz'].
		"=".$v['sum_admin_planned_leaves'];
		foreach($v['time_off'] as $mc=>$formy) {
			echo "<td style='text-align:left; white-space: nowrap'>".implode("<br>",$formy);
		}
		$lp++;
	}
	echo "</table><br><br>";
	echo "Do poprawki:<br><br>";
	echo implode(", ", $faulty);
	echo " <br> <br> <br> <br> <br> <br>";

}
/*}}}*/
function main() { /*{{{*/

	head();
	by_departments();
	leave_titles();
	r2();

}
/*}}}*/

#stanley_liczy();
main();

?>
