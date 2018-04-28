<?php
session_name(getenv("LEAVENSKY_SESSION_NAME"));
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
<script type='text/javascript' src='js/taffy-min.js'></script>
<script type='text/javascript' src='js/moment.min.js'></script>
<script type='text/javascript' src='js/datepicker.js'></script>
<script type='text/javascript' src='js/script.js'></script>
";
}
/*}}}*/
function make_year() {/*{{{*/
	if(isset($_REQUEST['change_year'])) { 
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
}
/*}}}*/
function make_user() {/*{{{*/
	if(isset($_GET['id'])) { 
		$_SESSION['user_id']=$_GET['id'];
		$_SESSION['user']=$_SESSION['ll']->query("SELECT name FROM people WHERE id=$1", array($_SESSION['user_id']))[0]['name'];
	}
	$_SESSION['creator_id']=666;
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
	$_SESSION['ll']->query("UPDATE leavensky SET leaves=$1, taken=$2, creator_id=$3 WHERE year=$4 AND user_id=$5", array(json_encode($collect['leaves']), json_encode($collect['taken']), $_SESSION['creator_id'], $_SESSION['year'],$_SESSION['user_id']));
	unset($_REQUEST);
}
/*}}}*/
function form_year() {/*{{{*/
	echo "
	<form method=post>
	<br> ".$_SESSION['user'].", year
	<input type=text name=change_year size=4 value=".$_SESSION['year'].">
	<input type=submit value='set'>
	</form>
	";

}
/*}}}*/
function db_read_disabled() {/*{{{*/
	// user_id == -1  is admin. Whatever he chooses as leaves will be disabled
	// for normal users to select. Good for Saturdays/Sundays/religious
	// holidays, etc.

	$disabled=[];
	$r=$_SESSION['ll']->query("SELECT leaves FROM leavensky WHERE user_id=-1 AND year=$1", array($_SESSION['year']));
	if(!empty($r)) { 
		$leaves=json_decode($r[0]['leaves'],1);
		foreach($leaves as $v) {
			$disabled[]=$v[0];
		}
	}
	return $disabled;
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

	$r=$_SESSION['ll']->query("SELECT taken,limits,leaves FROM v WHERE user_id=$1 AND year=$2", array($_SESSION['user_id'], $_SESSION['year']));
	if(empty($r)) { die("$i18n_year_not_prepared ".$_SESSION['year']); }
	$taken=json_decode($r[0]['taken'],1);
	$limits=json_decode($r[0]['limits'],1);
	$leaves=json_decode($r[0]['leaves'],1);
	$_SESSION['setup']["summary"]=array('taken'=>$taken, 'limits'=>$limits); 
	$_SESSION['setup']["leaves"]=$leaves;
	$_SESSION['setup']['disabled']=db_read_disabled();
	$_SESSION['setup']['user']='user';


	echo "
	<script type='text/javascript'>
		var setup=".json_encode($_SESSION['setup']).";
		var preview=1;
	</script>
	";

}
/*}}}*/
function calendar_submitter() {/*{{{*/
	extract($_SESSION['i18n']);

	$titles='';
	foreach($_SESSION['setup']['titles'] as $k=>$v) { 
		$titles.="<th><label class=lradio id='l$k' title='$v'>$v</label>";
	}

	$block=$_SESSION['ll']->query("SELECT block FROM v WHERE user_id=$1 AND year=$2", array($_SESSION['user_id'], $_SESSION['year']))[0]['block'];

	$submitter='';
	if(empty($_SESSION['leavensky_admin'])) { 
		$submitter="<table style='width:1px'> <tr> <th>$i18n_choose<th> $titles </table>";
		if($block==1) { 
			$submitter.="<div style='display:inline-block'>";
			$submitter.="&nbsp; Blocked";
			$submitter.="<help title='".$i18n_howto_unblock."'></help>";
			$submitter.="</div><br>";
		} else {
			$submitter.="<div style='display:inline-block'>";
			$submitter.="<input id=leavensky_submit type=submit>";
			$submitter.="</div><br>";
		}
	}
	return $submitter;

}
/*}}}*/
function form_calendar() { /*{{{*/
	$submitter=calendar_submitter();
	echo "
	<form method=post> 
	<input type=hidden name=collect id=collect>
	$submitter
	<div style='display:inline-block'>
		<div id='multi-calendar' style='float:left'></div>
		<div id=preview></div>
	</div>
	<br><br>
	</form>
	";

}
/*}}}*/

head();
make_year();
make_user();
submit_calendar();
form_year();
db_read();
form_calendar();

?>
