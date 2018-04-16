<?php
session_start();
ini_set('error_reporting', E_ALL);
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
$_SESSION['ll']=new leavensky();

# debug/*{{{*/

function dd() {
	// Just a debugging function
	echo "<dd>";
	foreach(func_get_args() as $v) {
		echo "<pre>";
		$out=print_r($v,1);
		echo htmlspecialchars($out);
		echo "</pre>";
	}
	echo "<br><br><br><br>";
	echo "</dd>";
}
function dd2($arr) {
	$out=print_r($arr,1);
	echo $out;
}

/*}}}*/
class leavensky{/*{{{*/
	// On init we load all messages from messages/en.csv. This way we don't have missing texts in case translations are not complete.
	// Then messages/$language.csv is loaded to replace some/all en strings.
	public function __construct(){
		$conf=json_decode(file_get_contents("conf.json"),1);
		$language=$conf['lang'];
		$_SESSION['i18n']=array();

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
		mail('mimoohowy@gmail.com', 'Leavensky bug!', "$reportquery", "from: mimooh@inf.sgsp.edu.pl"); 
		echo "<fatal>".$arr[0]."</fatal>"; 
		die();
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

		$connect=pg_connect("dbname=leavensky host=localhost user=".getenv("LEAVENSKY_DB_USER")." password=".getenv("LEAVENSKY_DB_PASS"));
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
}

