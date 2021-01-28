<?php

# This script is probably useless outside of my institution
require_once("/home/svn/svn_mimooh/systems/xlsphp/xlsphp.php");
if(getenv("ADIJOZ_ALLOW_REPORT_R1")!=1) { die("Reporting without passwords needs to be enabled by adding 'export ADIJOZ_ALLOW_REPORT_R1=1' to /etc/apache2/envvars and then restarting apache"); }
session_name(getenv("ADIJOZ_SESSION_NAME"));
require_once("inc.php");
if(!isset($_SERVER['SERVER_NAME'])) { $_SESSION['console']=1; } else { $_SESSION['console']=0; }
require_once("r2.php");
$_SESSION['year']=date('2021');

function head() { /*{{{*/
	if($_SESSION['console']==1) { return; }
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
function makepdf($arr) {
	$css=pdf_style();
	$mpdf_setup=array('default_font_size'=>10, 'orientation'=>'P', 'margin_left'=> 10, 'margin_right'=> 10, 'margin_top'=> 15, 'margin_bottom'=> 15, 'margin_header'=> 10, 'margin_footer'=> 10);
	pdf(array('pages'=>$arr, 'filename'=>"sonda.pdf", 'mpdf_setup'=> $mpdf_setup, 'css'=>$css));
	exit();
}
function by_departments() { /*{{{*/
	if(empty($_GET['department'])) { return; }
	if(isset($_GET['pdf'])) { $pdf=1; } else { $pdf=0; }
	$map=array( '01' =>	'styczeń', '02' =>	'luty', '03' =>	'marzec', '04' =>	'kwiecień', '05' =>	'maj', '06' =>	'czerwiec', '07' =>	'lipiec', '08' =>	'sierpień', '09' =>	'wrzesień', '10' =>	'październik', '11' =>	'listopad', '12' =>	'grudzień');
	$_SESSION['aa']->each_day_of_year();
	$_SESSION['aa']->each_month_day_of_year();
	$ddepartment=[];
	foreach($_SESSION['aa']->query("SELECT name,leaves FROM v WHERE department~$1 AND year=$2 ORDER BY name", array($_GET['department'], $_SESSION['year'])) as $r) { 
		$leaves=[];
		$ddepartment[$r['name']]=$_SESSION['each_day'][$_SESSION['year']];
		$leaves=json_decode($r['leaves'],1);
		if(!empty($leaves)) { 
			foreach($leaves as $v) {
				$ddepartment[$r['name']][$v[0]]=$v[1];
			}
		}
	}
	$wolne=array_flip($_SESSION['aa']->db_read_holidays());
	$arr=[];
	if($pdf==1) {
		$dark_sty="style='background-color: #bbb;'";
	} else {
		$dark_sty="style='background-color: #333;'";
	}
	foreach($_SESSION['each_month_day'][$_SESSION['year']] as $month=>$days) {
		if(isset($_GET['month']) && $_GET['month']!=$month) { continue; }
		if($pdf==1) {
			$names_chunks=array_chunk(array_keys($ddepartment), 9);
		} else {
			$names_chunks=[array_keys($ddepartment)];
		}
		foreach($names_chunks as $names) {
			$html_month="";
			$html_month.="$_GET[department]";
			$html_month.="<table>";
			$html_month.="\n<tr><td text-rotate='90'><div style='padding-top:5px'>".$map[$month]."</div>";
			foreach($names as $name) { 
				$n=explode(" ", $name);
				$n[0]=preg_replace("/-.*/", "", $n[0]);
				if($pdf==1) {
					$html_month.="<td style='width:70px'> <table style='border: 0px solid #fff;'> <tr style='border: 0px solid #fff;' text-rotate='90'> <td style='border: 0px solid #fff;'>$n[0]</td> <td style='border: 0px solid #fff;'>$n[1]</td> </tr> </table>";
				} else {
					$html_month.="<td> $name";
				}
			}
			foreach($days as $day=>$dayname) { 
				$sty="";
				if(isset($wolne[$day])) { $sty=$dark_sty; }
				$html_month.="\n<tr><td $sty><div style='white-space:nowrap; text-align: left'>".substr($day,8)."&nbsp;$dayname</div>";
				foreach($names as $name) { 
					if(!empty($ddepartment[$name][$day])) { 
						if($pdf==1) {
							$html_month.="<td style='background-color: #ddd'>";
						} else {
							$html_month.="<td style='color: #888'>".$ddepartment[$name][$day];
						}
					} else {
						$html_month.="<td $sty>";
					}
				}
			}
			$html_month.="</table><br><br>";
			$arr[]=$html_month;
		}
	}
	if(isset($_GET['all_departments'])) { return $arr; }
	
	if($pdf==0) { echo implode("<br><br><br><br>", $arr); } else { makepdf($arr); }
	exit();
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
			$arr['time_off'][$date[1]][$title][]=$date[2];
		}
		$collect[$v['user_id']]=$arr;
	}
	$_SESSION['collect']=$collect;
}
/*}}}*/
function r2($xls=0) { #{{{
	setlocale(LC_TIME, 'pl_PL.UTF-8');  
	read_time_off();
	$new=[];
	foreach($_SESSION['collect'] as $id=>$data) { 
		$new[$id]=$data;
		$new[$id]['time_off']=array();
		foreach($data['time_off'] as $k=>$v) {
			$mm = strftime('%b', mktime(0, 0, 0, $k));
			$new[$id]['time_off'][$k]=[];
			foreach($v as $kk=>$vv) {
				if(!empty($vv)) { 
					$count=count($vv);
					$new[$id]['time_off'][$k][]="$mm: ".implode(",",$vv). "  ($count $kk) ";
				} 
			}
		}
	}
	if($xls==1) { 
		return r2_to_xls($new);
	} else { 
		return r2_to_html($new);
	}

}
/*}}}*/
function r2_to_html($collect) { #{{{
	$lp=1;
	$html='';
	$html.="<table>";
	$html.="\n<tr><th>lp<th>komórka<th>nazwisko<th>zaplanował<th>zal.wyp+zal.dod+wyp+dod+nz<th>widok1<th>widok2";
	$faulty=[];
	foreach($collect as $k=>$v) {
		if($v['sum_user_planned_leaves'] != $v['sum_admin_planned_leaves']) { 
			$v['sum_user_planned_leaves']="<div style='background-color:red'>$v[sum_user_planned_leaves]</div>"; 
			$faulty[]=$v['email'];
		}
		$html.="\n<tr><td>$lp<td>$v[department]<td style='text-align:left; white-space: nowrap'>$v[name]<td>$v[sum_user_planned_leaves]<td style='text-align:left; white-space: nowrap'>".
			$v['admin_planned_leaves']['zal.wyp'].
		"+".$v['admin_planned_leaves']['zal.dod'].
		"+".$v['admin_planned_leaves']['wyp'].
		"+".$v['admin_planned_leaves']['dod'].
		"+".$v['admin_planned_leaves']['nz'].
		"=".$v['sum_admin_planned_leaves']."<td style='text-align:left; white-space: nowrap'>";
		foreach(array_filter($v['time_off']) as $mc=>$formy) {
			$html.="<br>".implode("<br>",$formy);
		}
		$html.="<td style='text-align:left; white-space: nowrap'>";
		$stanley=stanley_liczy($k);
		$html.=$stanley['html'];
		#dd($stanley['raw']);
		$lp++;
	}
	$html.="</table><br><br>";
	$html.="Wypełnili błędnie:<br><br>";
	$html.=implode(",<br>", array_filter($faulty));
	$html.="<br><br><br>Nie wypełnili:<br><br>";
	$r=$_SESSION['aa']->query("select department,email from v where year=$1 and taken is null order by department", array($_SESSION['year']));
	$html.="<table>";
	$i=0;
	foreach($r as $v) {
		$html.="<tr><td>$i<td>$v[department]<td>$v[email]";
		$i++;
	}
	$html.="</table><br> ";
	return $html;

}
/*}}}*/
function r2_to_xls($collect) { #{{{
	$lp=1;
	$data=[];
	$data[]=array('komórka','mundur','nazwisko i imię','zaplanował','zal.wyp', 'zal.dod', 'wyp', 'dod', 'nz', 'powinien', 'widok2');
	foreach($collect as $k=>$v) {
		if(!empty($v['stopien'])) { $funkcjonariusz=1; } else { $funkcjonariusz=0; }
		$out=array($v['department'], $funkcjonariusz, $v['name'], $v['sum_user_planned_leaves'], $v['admin_planned_leaves']['zal.wyp'], $v['admin_planned_leaves']['zal.dod'], $v['admin_planned_leaves']['wyp'], $v['admin_planned_leaves']['dod'], $v['admin_planned_leaves']['nz'], $v['sum_admin_planned_leaves']);
		$out[]=stanley_liczy($k, $podsumowanie=0)['xls'];
		$data[]=$out;
	}
	return $data;
}
/*}}}*/
function menu() {/*{{{*/
	$map=array( '01' =>	'styczeń', '02' =>	'luty', '03' =>	'marzec', '04' =>	'kwiecień', '05' =>	'maj', '06' =>	'czerwiec', '07' =>	'lipiec', '08' =>	'sierpień', '09' =>	'wrzesień', '10' =>	'październik', '11' =>	'listopad', '12' =>	'grudzień');
	echo "<br><a class=blink href=?r2>Raport</a> ";
	echo "Listy: ";
	foreach($map as $k=>$v) {
		echo "<a class=blink href=?all_departments&month=$k&pdf=1>$v</a>";
	}
}
/*}}}*/
function all_departments() {/*{{{*/
	if(!isset($_GET['all_departments'])) { return; }
	$arr=array('RA-1/1', 'RA-1/2', 'RA-2', 'RA-3', 'RA-4', 'RA-5', 'RA-6', 'RA-7', 'RK-1', 'RK-2', 'RK-3', 'RK-4', 'RK-5', 'RN-1', 'RN-2', 'RN-3', 'RN-4', 'RN-5', 'RO-1', 'RO-3', 'RO-4', 'RO-5', 'RO-6', 'RR-1', 'RR-2', 'RR-3', 'RR-4', 'RR-5', 'RR-6', 'RR-7', 'RR-8', 'RR-9', 'RW-1/1', 'RW-1/2', 'RW-1/3', 'RW-1/4', 'RW-2/1', 'RW-2/2', 'RW-2/3', 'RW-2/4', 'RW-3/1', 'RW-3/2', 'RW-3/3', 'RW-4/1', 'RW-4/2', 'RW-5/1', 'RW-5/2', 'RW-5/3', 'RW-6/1', 'RW-6/2', 'RW-6/3', 'RW-7/1', 'RW-7/2', 'RW-7/3', 'RW-10', 'RW-11', 'RW-12');
	#$arr=array('RA-1/1', 'RA-1/2', 'RA-2');
	$pages=[];
	foreach($arr as $k=>$v) {
		$_GET['department']=$v;
		$pages=array_merge($pages,by_departments());
	}
	makepdf($pages);
	exit();
}
/*}}}*/
function main() { /*{{{*/
	# echo "select leaves from adijoz where user_id=716" | psql adijoz
	leave_titles();
	if(isset($_GET['xls'])) { $data=r2($xls=1); xls($data, "sonda.xlsx"); exit(); }
	head();
	all_departments();
	by_departments();
	menu();
	if(isset($_GET['r2'])) { 
		echo r2();
		dd("Błędy pod gruszą", $_SESSION['grusza_errors']);
	}
}
/*}}}*/
main();

?>
