<?php
$_SESSION['grusza_errors']=[];
function rok(){/*{{{*/ //w tym roku przestepny 29 dni w lutym
	$start_date = strtotime("$_SESSION[year]-01-01"); 
	$days_in_a_year = date('z', strtotime("$_SESSION[year]-12-31")); // 31th of december
	$dates=[];
	for ($i = 0; $i <= $days_in_a_year; $i++) {
		$date = strtotime(date("$_SESSION[year]-m-d", $start_date) . " +$i day");
		$dates[date('m_d', $date)]=0;
	}
	return $dates;

}/*}}}*/
function fill_year($kto){//tworzy os czasu dni zaplanowane + dni wolne od pracy (sobota niedziela, swieta)/*{{{*/
	$wolne=$_SESSION['aa']->db_read_holidays();
	$kto=$_SESSION['collect'][$kto];
	$time_line=rok(); //wszystkie dni w roku
	foreach($kto['time_off'] as $k_mon=>$v){ //wstawienie zaplanowanych dni pracownika na os czasu
		foreach($v as $type=>$val){
			foreach ($val as $k_dom){
				$key=$k_mon."_".$k_dom;
				$time_line[$key]=$type; //dodaj dzien urlopu do timeline
			}
		}
	}
	foreach($wolne as $w){ //wpisywanie wolnego w timeline
		$data=explode("-",$w);
		$key=$data[1]."_".$data[2];
		$time_line[$key]='wol';
	}
	return($time_line);	
}/*}}}*/
function remove_holiday_at_end($temp_table){/*{{{*/
		while(end($temp_table)=='wol'){
			array_pop($temp_table);
		}
		return($temp_table);
}/*}}}*/
function make_con_table($new_temp, $count){ //dodaj temp table do duzej listy/*{{{*/
	$keys=array_keys($new_temp);
	$from_array=explode("_",reset($keys));
	$from=$from_array[1].".".$from_array[0];	
	$to_array=explode("_",end($keys));
	$to=$to_array[1].".".$to_array[0];	
	$ret=array('from'=>$from,'to'=>$to,'count'=>$count);
	return($ret);
}/*}}}*/
function find_continuity($time_line){/*{{{*/
	$con_table=[];//pusta tabela na znalezione zakresy dni
	$con_table['zakresy']=[];
	$temp_table=[];
	$found=0;
	$count=0;//liczba dni zaplanowanych w serii
	$limit_gruszy=10;
	$juz_zaplanowanych=0;
	foreach($time_line as $date=>$type){
		if(!empty($type)and $type!='wol' ){ //znalazlem poczatek serii - nie zaczynamy od dnia wolnego
			$found=1;
			$temp_table[$date]=$type;
			$count++;//liczba dni zaplanowanych w serii
			$juz_zaplanowanych++;
			if($juz_zaplanowanych==$limit_gruszy){
				$d=explode("_",$date);
				$con_table['grusza_limit']="Osiągnięto limit $limit_gruszy dni w ".$d[1].".".$d[0].".2020";
			}
		}
		if(!empty($type) and $found==1){ //znalazłem kolejny wpis w serii
			$temp_table[$date]=$type;
		}
		if(empty($type) and $found==1){ //ten wpis juz nie w serii
			$new_temp=remove_holiday_at_end($temp_table); //usun z konca dni swiateczne
			$con_table['zakresy'][]=make_con_table($new_temp, $count);//dodaj znaleziona serie do duzej listy
			$count=0;
			$found=0;
			$temp_table=[];
		}
	}
	return($con_table);	
}/*}}}*/
function find_first_long($con_table){/*{{{*/
	$long_limit=10;
	foreach ($con_table['zakresy'] as $k=>$v){
			if( $con_table['zakresy'][$k]['count']>=$long_limit){
				$dlugi_urlop="Długi urlop długości min $long_limit zaplanowano od ".$con_table['zakresy'][$k]['from']." do ".$con_table['zakresy'][$k]['to'];
				return($dlugi_urlop);
			}
	}
}/*}}}*/
function stanley_liczy($z_r1, $podsumowanie=1) { #{{{
	$time_line=fill_year($z_r1);
	$con_table=find_continuity($time_line);
	$con_table['dlugi_urlop']= find_first_long($con_table);
	$html='';
	$xls='';
	foreach($con_table['zakresy']  as $v) {
		$html.="$v[from] - $v[to] ($v[count])<br>";
		$xls.="$v[from] - $v[to] ($v[count])\n";
	}
	if(!empty($con_table['dlugi_urlop']) and $podsumowanie==1) { 
		$html.="$con_table[grusza_limit]<br>";  
		$html.="$con_table[dlugi_urlop]";  
		$xls.="$con_table[grusza_limit]\n";  
		$xls.="$con_table[dlugi_urlop]";  
	} else {
		$r=$_SESSION['aa']->query("select name from v where user_id=$1", array($z_r1));
		$_SESSION['grusza_errors'][]=$r[0]['name'];
	}

	return array('xls'=>$xls, 'html'=>$html);
}
/*}}}*/
?>
