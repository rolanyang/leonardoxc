<?
/************************************************************************/
/* Leonardo: Gliding XC Server					                        */
/* ============================================                         */
/*                                                                      */
/* Copyright (c) 2004-5 by Andreadakis Manolis                          */
/* http://leonardo.thenet.gr                                            */
/*                                                                      */
/* This program is free software. You can redistribute it and/or modify */
/* it under the terms of the GNU General Public License as published by */
/* the Free Software Foundation; either version 2 of the License.       */
/************************************************************************/

function sec2Time($secs,$no_seconds=false) {
  if ($no_seconds)
    return sprintf("%d:%02d",$secs/3600,($secs%3600)/60);
  else 
    return '<font color=#702440>'.sprintf("%d:%02d:%02d",$secs/3600,($secs%3600)/60,$secs%60).'</font>';
}

function days2YearsMonths($days) {
   $years=floor($days/365);
   $months=ceil( ($days%365) / 30 );
   return array($years,$months);
}

function formatURL($linkURL) {
	if ( substr($linkURL,0,7) == "http://" ) return $linkURL;
	else return "http://".$linkURL;
}

function formatDate($date,$html_output=true) {
  // from 2002-07-14 -> 14/07/2004
  $dt_str=sprintf("%02d/%02d/%4d",substr($date,8,2),substr($date,5,2),substr($date,0,4));
  if ($html_output)
    return '<font color=#224488>'.$dt_str.'</font>';
  else return $dt_str;
}

function formatOLCScoreType($type,$html_output=true) {
	$ret="#".$type."#";
	if ($type=="FREE_FLIGHT") $ret=_FREE_FLIGHT;
	else if ($type=="FREE_TRIANGLE") $ret=_FREE_TRIANGLE;
	else if ($type=="FAI_TRIANGLE") $ret=_FAI_TRIANGLE;
	if ($html_output)
		return '<font color=#004466>'.$ret.'</font>';
	else return $ret;
}

function formatDistance($distance,$showUnits=false) { // in meters
	global $PREFS;
	// 1 kilometer = 0.62 mil
	// 1 meter  =  3.28 feet
	if ($PREFS->metricSystem==2) { 
		$dis=($distance*0.62)/1000; 
		$units=_MI;
	} else { // km
		$dis=$distance/1000;
		$units=_KM;
	}
	// return sprintf("%.2f km",$distance/1000);
	return sprintf("%.2f %s",$dis,($showUnits)?$units:"");
}

function formatDistanceOpen($distance,$showKm=true) { // in meters
	return '<font color=#4400aa>'.formatDistance($distance,$showKm).'</font>';
}

function formatDistanceOLC($distance,$showKm=true) { // in meters
	return '<font color=#ff0000><strong>'.formatDistance($distance,$showKm).'</strong></font>';
}



function formatOLCScore($score,$html_output=true) { 
	if ($html_output) return '<font color=#0000ff>'.sprintf("%.1f",$score).'</font>';
	else return sprintf("%.1f",$score);
}

function formatAltitude($alt) { 
	global $PREFS;
	// 1 kilometer = 0.62 mil
	// 1 meter  =  3.28 feet
	if ($PREFS->metricSystem==2) { 
		$alt=$alt*3.28; //feet
		$units=_FT;
	} else { 
		$units=_M;
	}
	return '<font color=#008800>'.sprintf("%d %s",$alt,$units).'</font>';
}

function formatSpeed($speed) { // in km/h
	global $PREFS;
	// 1 kilometer = 0.62 mil
	// 1 meter  =  3.28 feet
	if ($PREFS->metricSystem==2) { 
		$speed=$speed*0.62; // ml/h
		$units=_MPH;
	} else { 
		$units=_KM_PER_HR;
	}
	return '<font color=#880000>'.sprintf("%.2f %s",$speed,$units).'</font>';
}

function formatVario($vario) { // in m/sec
	global $PREFS;
	// 1 kilometer = 0.62 mil
	// 1 meter  =  3.28 feet
	if ($PREFS->metricSystem==2) { 
		$vario=$vario*3.28*60; // feet /min
		$units=_FPM;
		return '<font color=#000844>'.sprintf("%.0f %s",$vario,$units).'</font>';
	} else { 
		$units=_M_PER_SEC;
		return '<font color=#000844>'.sprintf("%.1f %s",$vario,$units).'</font>';
	}

}

function formatLocation($name,$vinicity,$radious) {
	global $PREFS;
	if ($PREFS->metricSystem==2) $dis=($vinicity*0.62)/1000; 
	else $dis=$vinicity/1000;

 if ($vinicity > 300000 ) $res_name="UNKNOWN";
 else if ($vinicity > $radious ) 
		$res_name=$name."&nbsp;[~".sprintf("%.1f",$dis)."]"; 
//		$res_name=$name."&nbsp;[~".sprintf("%.1f",$vinicity/1000)."&nbsp;km]"; 
 else $res_name=$name;
 return '<font color=#884400>'.$res_name.'</font>';
}


function addFlightError($errMsg) {
	open_inner_table(_SUBMIT_FLIGHT_ERROR,600);
	open_tr();
		echo "<br><br><center>";
		echo $errMsg;
		echo "</center><br><br><br>";
	close_inner_table();
	exitPage();
}

function datetime2UnixTimestamp($datestamp) {
    if ($datestamp!=0) {
        list($date, $time)=split(" ", $datestamp);
        list($year, $month, $day)=split("-", $date);
        list($hour, $minute, $second)=split(":", $time);
        $stampeddate=mktime($hour,$minute,$second,$month,$day,$year);
     
        return   $stampeddate ;
    }
}

function getUTMzoneLocal($lon, $lat)  {
	if ($lon < 0.0)  $lonTmp = 180 - $lon ;
	else $lonTmp = 180 - $lon ;

	$UTMzone=ceil($lonTmp/6);
	return $UTMzone;
}

function processIGC($filePath) {
	global $takeoffRadious;
	//echo $filePath."<br>";
	//echo filesize( $filePath)."<br>";
	$lines = file ($filePath); 
	$points=0;
	foreach($lines as $line) {
		$line=trim($line);
		if  (strlen($line)==0) continue;
		
		if ($line{0}=='B') {
			if  ($points==0)  { // first point 
				$firstPoint=new gpsPoint($line);
				echo _TAKEOFF_COORDS." ";
				echo $firstPoint->lat." ";
				echo $firstPoint->lon."<br>";


			$zone= getUTMzoneLocal( $firstPoint->lon,$firstPoint->lat);
			$timezone= ceil(-$firstPoint->lon / (180/12) );
			echo "<b>UTM zone:</b> ".$zone." ";
			echo "<b>Timezone:</b> ".$timezone."<br>";
			} else  {
				$lastPoint=new gpsPoint($line);
			}
			$points++;		   
		}
	}
	// echo $points;
}

function getUTMtimeOffset($lat,$lon, $theDate ) {
// $lon is the X (negative is EAST positive is WEST
// for now we return a very rough calculation

	$timezone= ceil( -$lon / (180/12) );
	return $timezone;
}

function generate_flights_pagination($base_url, $num_items, $per_page, $start_item, $add_prevnext_text = TRUE)
{


/*
	By default, $begin_end is 3, and $from_middle is 1, so on page 6 in a 12 page view, it will look like this:

	a, d = $begin_end = 3
	b, c = $from_middle = 1

 "begin"        "middle"           "end"
    |              |                 |
    |     a     b  |  c     d        |
    |     |     |  |  |     |        |
    v     v     v  v  v     v        v
    1, 2, 3 ... 5, 6, 7 ... 10, 11, 12

	Change $begin_end and $from_middle to suit your needs appropriately
*/
	$begin_end = 4;
	$from_middle = 3;
	
	$total_pages = ceil($num_items/$per_page);

	if ( $total_pages == 1 )
	{
		return '';
	}

	$on_page = floor($start_item / $per_page) + 1;

	$page_string = '';
	if ( $total_pages > ((2*($begin_end + $from_middle)) + 2) )
	{
	//	$init_page_max = ( $total_pages > 3 ) ? 3 : $total_pages;
		$init_page_max = ( $total_pages > $begin_end ) ? $begin_end : $total_pages;


		for($i = 1; $i < $init_page_max + 1; $i++)
		{
			// $page_string .= ( $i == $on_page ) ? '<b>' . $i . '</b>' : '<a href="' . append_sid($base_url . "&amp;start=" . ( ( $i - 1 ) * $per_page ) ) . '">' . $i . '</a>';
			$page_string .= ( $i == $on_page ) ? '<b>' . $i . '</b>' : '<a href="' . append_sid($base_url . "&amp;page_num=".$i ) . '">' . $i . '</a>';
			if ( $i <  $init_page_max )
			{
				$page_string .= ", ";
			}
		}

		//if ( $total_pages > 3 )
		if ( $total_pages > $begin_end )
		{
			if ( $on_page > 1  && $on_page < $total_pages )	{
				//$page_string .= ( $on_page > 5 ) ? ' ... ' : ', ';
				$page_string .= ( $on_page > ($begin_end + $from_middle + 1) ) ? ' ... ' : ', ';


				//$init_page_min = ( $on_page > 4 ) ? $on_page : 5;
				$init_page_min = ( $on_page > ($begin_end + $from_middle) ) ? $on_page : ($begin_end+ $from_middle + 1);
				//$init_page_max = ( $on_page < $total_pages - 4 ) ? $on_page : $total_pages - 4;
				$init_page_max = ( $on_page < $total_pages - ($begin_end + $from_middle) ) ? $on_page : $total_pages - ($begin_end + $from_middle);

//echo $init_page_min."#".$init_page_max."#".$on_page;
				//for($i = $init_page_min - 1; $i < $init_page_max + 2; $i++)
				for($i = $init_page_min - $from_middle; $i < $init_page_max + ($from_middle + 1); $i++)
				{
					$page_string .= ($i == $on_page) ? '<b>' . $i . '</b>' : '<a href="' . append_sid($base_url . "&amp;page_num=$i").'">'.$i. '</a>';
					//if ( $i <  $init_page_max + 1 )
					if ( $i <  $init_page_max + $from_middle )
					{
						$page_string .= ', ';
					}
				}

				//$page_string .= ( $on_page < $total_pages - 4 ) ? ' ... ' : ', ';
				$page_string .= ( $on_page < $total_pages - ($begin_end + $from_middle) ) ? ' ... ' : ', ';


			} else {
				$page_string .= ' ... ';
			}

			//for($i = $total_pages - 2; $i < $total_pages + 1; $i++)
			for($i = $total_pages - ($begin_end - 1); $i < $total_pages + 1; $i++)
			{
				$page_string .= ( $i == $on_page ) ? '<b>' . $i . '</b>'  : '<a href="' . append_sid($base_url . "&amp;page_num=$i").'">' . $i . '</a>';
				if( $i <  $total_pages )
					$page_string .= ", ";
			}
		}
	} else {
		for($i = 1; $i < $total_pages + 1; $i++)
		{
			$page_string .= ( $i == $on_page ) ? '<b>' . $i . '</b>' : '<a href="' . append_sid($base_url . "&amp;page_num=$i").'">'.$i.'</a>';
			if ( $i <  $total_pages )
				$page_string .= ', ';
		}
	}

	if ( $add_prevnext_text )
	{
		if ( $on_page > 1 )
			$page_string = ' <a href="' . append_sid($base_url . "&amp;page_num=" . ( $on_page - 1 )  ) . '"><<</a>&nbsp;&nbsp;'.$page_string;

		if ( $on_page < $total_pages )
			$page_string .= '&nbsp;&nbsp;<a href="' . append_sid($base_url . "&amp;page_num=" . ($on_page+1)   ) . '">>></a>';
	}

	return $page_string;
}

?>