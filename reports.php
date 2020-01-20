<?php
require_once("inc.php");
if(isset($_SERVER['SERVER_ADDR'])) { die("this script is meant to be run from the console by the admin"); }

$_SESSION['console']=1;
$_SESSION['year']=date('Y');
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
	$r=$_SESSION['aa']->query("select user_id,name,leaves from v where leaves is not null and year=$1", array($_SESSION['year']));
	$collect=[];
	foreach($r as $k=>$v) { 
		$arr=array();
		$arr['name']=$v['name'];
		$arr['time_off']=array();
		foreach(array('01' ,'02' ,'03', '04' ,'04' ,'05' ,'06' ,'07' ,'08' ,'09' ,'10' ,'11' ,'12' ) as $mc) { 
			$arr['time_off'][$mc]=array();
			foreach($_SESSION['leave_titles'] as $title) {
				$arr['time_off'][$mc][$title]=array();
			}
		}
		$leaves=json_decode($v['leaves'],1);
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
function report() { #{{{
	$new=[];
	foreach($_SESSION['collect'][25]['time_off'] as $k=>$v) {
		$new[$k]=array();
		foreach($v as $kk=>$vv) {
			if(!empty($vv)) { 
				$count=count($vv);
				$new[$k][]="$count $kk: ".implode(" ",$vv);
			}
		}
	}
	dd($new);

}
/*}}}*/
function main() { /*{{{*/
	#conf_time_off();
	leave_titles();
	read_time_off();
	report();
}
/*}}}*/
main();

?>
