<?php
session_name(getenv("ADIJOZ_SESSION_NAME"));
require_once("inc.php");

# echo "select * from v where year=2021" | psql adijoz

function head() { /*{{{*/
	echo "
<HTML><HEAD>
<META http-equiv=Content-Type content='text/html; charset=utf-8' />
<title>adijoz</title>
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
		#$_SESSION['year']=date('Y');
		$_SESSION['year']=2021;
	}

	echo "
	<script type='text/javascript'>
		var year=".$_SESSION['year'].";
	</script>
	";
}
/*}}}*/
function submit_calendar() { /*{{{*/
	if(empty($_REQUEST['collect'])) { return; }
	$collect=json_decode($_REQUEST['collect'],1);

	foreach($collect['leaves'] as $key => $row) {
		$date[$key] = $row[0];
		$type[$key] = $row[1];
	}
	if(!empty($collect['leaves'])) {  
		array_multisort($date, SORT_ASC,  $collect['leaves']);
	}
	$_SESSION['aa']->query("UPDATE adijoz SET leaves=$1, taken=$2 WHERE year=$3 AND user_id=$4", array(json_encode($collect['leaves']), json_encode($collect['taken']), $_SESSION['year'],$_SESSION['user_id']));
	$_SESSION['aa']->msg("OK!");
	unset($_REQUEST);
}
/*}}}*/
function form_year() {/*{{{*/
	echo "
	<form method=post>
	<br>Year
	<input type=text name=change_year size=4 value=".$_SESSION['year'].">
	<input type=submit value='set'>
	</form>
	";

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

	$r=$_SESSION['aa']->query("SELECT taken,limits,leaves FROM v WHERE user_id=$1 AND year=$2", array($_SESSION['user_id'], $_SESSION['year']));
	if(empty($r)) { die("$i18n_year_not_prepared ".$_SESSION['year']); }
	$taken=json_decode($r[0]['taken'],1);
	$limits=json_decode($r[0]['limits'],1);
	$leaves=json_decode($r[0]['leaves'],1);
	$_SESSION['setup']["summary"]=array('taken'=>$taken, 'limits'=>$limits); 
	$_SESSION['setup']["leaves"]=$leaves;
	$_SESSION['setup']['holidays']=$_SESSION['aa']->db_read_holidays();
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
	// $_GET['adijoz_plain_user'] allows an admin user in the institution to act as a plain user and plan his own vacation.
	extract($_SESSION['i18n']);

	$titles='';
	foreach($_SESSION['setup']['titles'] as $k=>$v) { 
		$titles.="<th><label class=lradio id='l$k' title='$v'>$v</label>";
	}

	$block=$_SESSION['aa']->query("SELECT block FROM v WHERE user_id=$1 AND year=$2", array($_SESSION['user_id'], $_SESSION['year']))[0]['block'];

	$submitter='';

	if(isset($_GET['adijoz_plain_user'])) { 
		$submitter="<table style='width:1px'> <tr> <th>$i18n_I_am_planning<th> $titles </table>";
		if($block==1) { 
			$submitter.="Blocked";
			$submitter.="<help title='".$i18n_howto_unblock."'></help>";
			$submitter.="<br><br>";
		} else {
			$submitter.="<div style='display:inline-block'>";
			$submitter.="<input id=adijoz_submit value=$i18n_submit type=submit>";
			$submitter.="</div><br>";
		}
	}
	if(!empty($_SESSION['adijoz_admin'])) { 
		$submitter="<table style='width:1px'> <tr> <th>$i18n_I_am_planning<th> $titles </table>";
		$submitter.="<div style='display:inline-block'>";
		$submitter.="<input id=adijoz_submit value=$i18n_submit type=submit>";
		$submitter.="</div><br>";
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
function admin_change_user() {/*{{{*/
	// Admin may want to inspect any user's leave calendar
	if(!empty($_SESSION['adijoz_admin']) and isset($_GET['id'])) { 
		$_SESSION['user_id']=$_GET['id'];
		$_SESSION['user']=$_SESSION['aa']->query("SELECT name FROM people WHERE id=$1", array($_GET['id']))[0]['name'];
	}
}
/*}}}*/

head();

if(getenv("ADIJOZ_DISABLE_AUTH")==1) { 
	$_SESSION['home_url']=$_SERVER['SCRIPT_NAME'];
	$_SESSION['user_id']=1; 
	$_SESSION['user']='Lannister Jaimie';
	echo "<a class=blink href=admin.php>admin.php</a>";

}
if(empty($_SESSION['user_id'])) { $_SESSION['aa']->fatal("Not allowed"); }
make_year();
admin_change_user();
submit_calendar();
form_year();
db_read();
$_SESSION['aa']->logout_button();
form_calendar();

?>
