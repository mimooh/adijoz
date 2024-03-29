<?php
session_start();
ini_set('error_reporting', E_ALL);
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
$_SESSION['aa']=new adijoz();

class adijoz{/*{{{*/
	// On init we load all messages from messages/en.csv. This way we don't have missing texts in case translations are not complete.
	// Then messages/$language.csv is loaded to replace some/all en strings.
	public function __construct(){
		$language=getenv("ADIJOZ_LANG");
		if(empty($language)) { $language='en'; }
		$_SESSION['i18n']=array();
		$_SESSION['console']=0;

		foreach (file("messages/en.csv") as $row) {                                                                                   
			$x=explode(";", $row);
			if(count($x)!=2){
				$this->fatal("Something wrong with messages/$language.csv file - each line must have a single semicolon");
			}
			$_SESSION['i18n'][trim($x[0])]=trim($x[1]);
		}
		foreach (file("messages/$language.csv") as $row) {                                                                                   
			$x=explode(";", $row);
			if(count($x)!=2){
				$this->fatal("Something wrong with messages/$language.csv file - each line must have a single semicolon");
			}
			$_SESSION['i18n'][trim($x[0])]=trim($x[1]);
			
		}   
	}

/*}}}*/
	private function reportbug($arr) {/*{{{*/
		$reportquery=join("\n" , array('--------' , date("G:i:s"), $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $_SERVER['REQUEST_URI'], $arr[0] , $arr[1] , $arr[2] , "\n\n"));
		mail(getenv("ADIJOZ_NOTIFY"), 'Adijoz bug!', "$reportquery", "from: adijoz"); 
		echo "<fatal>".$arr[0]."</fatal>"; 
		die();
}
/*}}}*/
	public function logout_button() {/*{{{*/
		if(!empty(getenv("ADIJOZ_LOGOUT_BUTTON"))) { 
			if(isset($_SESSION['setup']['summary']['limits'])) { 
				$limits=$_SESSION['setup']['summary']['limits'];
				$sum="<green>".implode("+", $limits)."=". array_sum($limits)."</green>";
			} else {
				$sum='';
			}
			echo "<div style='float:right'>$_SESSION[user] $sum".getenv("ADIJOZ_LOGOUT_BUTTON")."</div>"; 
			return;
		}
		if(!isset($_GET['logout'])) { 
			echo "<div style='float:right'><a href=?logout class=rlink>Logout: $_SESSION[user]</a></div>"; 
		} else {
			$home=$_SESSION['home_url'];
			$_SESSION=array();
			header("Location: $home");
		}
	}
/*}}}*/
	public function fatal($msg) {/*{{{*/
		echo "<fatal> $msg </fatal>";
		die();
	}
/*}}}*/
	public function extractDate($date_str) {/*{{{*/
		return substr($date_str, 0, 10);
	}
/*}}}*/
	public function extractTime($date_str) {/*{{{*/
		return substr($date_str, 11, 8);
	}
/*}}}*/
	public function extractDateAndTime($date_str) {/*{{{*/
		return substr($date_str, 0, 19);
	}
/*}}}*/
	public function msg($msg) {/*{{{*/
		echo "<msg>$msg</msg>";
	}
/*}}}*/
	public function cannot($msg) {/*{{{*/
		echo "<cannot>$msg</cannot>";
	}
/*}}}*/
	public function query($qq,$arr=[],$success=0) { /*{{{*/
		// You only need to tweak pg_* functions to switch from postgres to sqlite/mysql/anything.
		
        extract($_SESSION);
		$caller=debug_backtrace()[1]['function'];

		// In order to have two parallel instances of adijoz (e.g. adijoz_plan vs adijoz_fulfill) we check against the existance of dbname.adijoz file containing the name of the database.
		if(is_file("dbname.adijoz")) { $db=trim(file_get_contents("dbname.adijoz")); } else { $db="adijoz"; }
		$connect=pg_connect("dbname=$db host=localhost user=".getenv("ADIJOZ_DB_USER")." password=".getenv("ADIJOZ_DB_PASS"));
		($result=pg_query_params($connect, $qq, $arr)) || $this->reportBug(array("db error\n\ncaller: $caller()\n\n", "$qq", pg_last_error($connect)));
		$k=pg_fetch_all($result);
		if($success==1) { echo "<msg>OK</msg>"; }
		if(is_array($k)) { 
			return $k;
		} else {
			return array();
		}

    }
/*}}}*/
	public function querydd($qq,$arr=[]){ /*{{{*/
		# query debugger
		echo "$qq ";
		print_r($arr);
		echo "<br>";
		return array();
    }
	/*}}}*/

	public function db_read_holidays() {/*{{{*/
		# psql adijoz -c "SELECT name FROM people ORDER BY name";
		# psql adijoz -c "DELETE from adijoz";
		# psql adijoz -c "SELECT * from adijoz";
		# psql adijoz -c "insert into people(name) values('antonio')";
		# user_id == -1  is admin. Whatever he had chosen as leaves will mark holidays
		# Good for Saturdays/Sundays/religious holidays, etc.

		$holidays=[];
		$r=$this->query("SELECT leaves FROM adijoz WHERE user_id=-1 AND year=$1", array($_SESSION['year']));
		if(!empty($r)) { 
			$leaves=json_decode($r[0]['leaves'],1);
			if(!empty($leaves)) { 
				foreach($leaves as $v) {
					$holidays[]=$v[0];
				}
			}
		}
		return $holidays;
	}
	/*}}}*/
	public function each_day_of_year() {/*{{{*/
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
	public function each_month_day_of_year() {/*{{{*/
		#if(isset($_SESSION['each_month_day'][$_SESSION['year']])) { return; }

		setlocale(LC_TIME, 'pl_PL.UTF-8');  
		$_SESSION['each_month_day'][$_SESSION['year']]=array();
		$day=strtotime($_SESSION['year']."-01-01");
		$end=strtotime($_SESSION['year']."-12-31");
		while($day <= $end) { 
			$_SESSION['each_month_day'][$_SESSION['year']][date("m", $day)][date("Y-m-d", $day)]=strftime('%a', $day);
			$day=strtotime("+1 Day", $day);
		}
	}
/*}}}*/

}

