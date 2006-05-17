<?
/************************************************************************/
/* Leonardo: Gliding XC Server					                        */
/* ============================================                         */
/*                                                                      */
/* Copyright (c) 2004-5 by Andreadakis Manolis                          */
/* http://sourceforge.net/projects/leonardoserver                       */
/*                                                                      */
/* This program is free software. You can redistribute it and/or modify */
/* it under the terms of the GNU General Public License as published by */
/* the Free Software Foundation; either version 2 of the License.       */
/************************************************************************/

class gpsPoint {
	var $waypointID;
	var $name;
	var $intName;
    var $gpsTime;
	var $lat;
    var $lon;  
    var $varioAlt;  
	var $gpsAlt;  
	var $timezone;
	function gpsPoint($line="",$timezone=0) {	
		$this->timezone=$timezone;
		//echo "@".$line."@<br>";
		if ($line{0}!='B') return;
		// if ( $line{15} != '0') return; // WHY was that here ??
        if ( ($line{14} != 'N') && ($line{14} != 'S')) return;
        if ( ($line{23} != 'E') && ($line{23} != 'W')) return;
        if ( strlen($line) < 23) return;
		 
        if ($line{14}=='N') $signlat = 1.0;
        if ($line{14}=='S') $signlat = -1.0;
        if ($line{23}=='W') $signlon = 1.0;
        if ($line{23}=='E') $signlon = -1.0;

//           1         2         3
// 01234567890123456789012345678901234
// B1153374029686N02313134EA0000000912
//        *     *  *     *  #####*****        

		// get lat lon
   		$this->lat = $signlat * $this->getlatlon(substr($line,7,7) );
		$this->lon = $signlon * $this->get_lon(substr($line,15,8)); // why 16,7 should be 15,8
               
		// echo "gpsPoint (lat,lon) : ".$this->lat .",".$this->lon."<BR>";

		// get time
        $seconds = substr($line,5,2);
        $minutes = substr($line,3,2);
		$hours   = substr($line,1,2);       
		$this->gpsTime = $seconds + 60*$minutes + 3600*$hours;

		$this->varioAlt=substr($line,25,5);
		$this->gpsAlt=substr($line,30,5);			
	}		

	function getlatlon($str) {
      $latlon = substr($str,2) / 60000.0;
      $latlon += substr($str,0,2);
      return $latlon;
   }

	function get_lon($str) {
      $lon = substr($str,3) / 60000.0;
      $lon += substr($str,0,3);
      return $lon;
   }

	function getAlt() {
	  if ($this->gpsAlt >0 ) return $this->gpsAlt+0;
	  else return $this->varioAlt+0;
	}

	function getTime() {
	  // compute Local Time
//	  $localTime= $this->gpsTime +  ($this->timezone*60*60);
//	  if (  $localTime<0)   $localTime+=24*60*60;

	  $localTime= ( 86400 + $this->gpsTime +  ($this->timezone*60*60) ) % 86400 ;
	  return $localTime;
	}

   function calc_distance($lat1, $lon1,$lat2, $lon2) { // in metern 
	  // echo "calc_distance : ".$lat1." ".$lon1." ".$lat2." ".$lon2."<br>";
  	  $pi_div_180 = M_PI/180.0;
      $d_fak = 6371000.0;  // FAI Erdradius in Metern 
	  //  Radius nautischen Meilen: ((double)1852.0)*((double)60.0)*((double)180.0)/((double)M_PI); 
      $d2    = 2.0;
      $latx = $lat1 * $pi_div_180; 
	  $lonx = $lon1 * $pi_div_180;
      $laty = $lat2 * $pi_div_180; 
	  $lony = $lon2 * $pi_div_180;
      $sinlat = sin(($latx-$laty)/$d2);
      $sinlon = sin(($lonx-$lony)/$d2);
      return $d2*asin(sqrt( $sinlat*$sinlat + $sinlon*$sinlon*cos($latx)*cos($laty)))*$d_fak;
   }

	function calcDistance($secondPoint) {
		return $this->calc_distance($this->lat,$this->lon,$secondPoint->lat,$secondPoint->lon);
	}

	function getUTM() {
		return	utm(-$this->lon,$this->lat);
	}

	function getLatMinDec() {
		 $coord_tmp =$this->lat; 		
		 if ($coord_tmp >=0) $i=floor($coord_tmp); 		
		 else $i=ceil($coord_tmp); 		

		 $f=abs(($coord_tmp-$i)*60); 
		 return sprintf("%s %d� %06.5f'\n", (($i>=0)?"N":"S"),abs($i), $f);
	}

	function getLonMinDec() {
		 $coord_tmp=-$this->lon;
		 if ($coord_tmp >=0) $i=floor($coord_tmp); 		
		 else $i=ceil($coord_tmp); 		

		 $f=abs(($coord_tmp-$i)*60); 
		 return sprintf("%s %d� %06.5f'\n", (($i>=0)?"E":"W"),abs($i), $f);
	}

	function getLatDMS() {
		 $coord_tmp=$this->lat;
		 if ($coord_tmp >=0) $i=floor($coord_tmp); 		
		 else $i=ceil($coord_tmp); 		

		 $minutes=abs(($coord_tmp-$i)*60); 
		 $seconds=($minutes-floor($minutes)) *60;
		 return sprintf("%s %d� %02d' %02.1f\"", (($i>=0)?"N":"S"),abs($i), floor($minutes),$seconds);

	}
	function getLonDMS() {
		 $coord_tmp=-$this->lon;
		 if ($coord_tmp >=0) $i=floor($coord_tmp); 		
		 else $i=ceil($coord_tmp); 		

		 $minutes=abs(($coord_tmp-$i)*60); 
		 $seconds=abs($minutes-floor($minutes)) *60;
		 return sprintf("%s %d� %02d' %02.1f\"", (($i>=0)?"E":"W"),abs($i), floor($minutes),$seconds);

	}


}	 // end class gpsPoint

class waypoint extends gpsPoint {
	var $type;
	var $countryCode;
	var $name;
	var $intName;
	var $location;
	var $intLocation;
	var $link;
	var $description;
	var $modifyDate;

/* should add

Nearest city 1
distance
time
Nearest city 2
Nearest village 1
How to get there 

type of flying: mountain desert flatland ridge coastal
Launch: easy normal hard
Landing: easy normal hard

Height Difference 
Wind direction SE, S, SW
Best flying season

Local club and/or pilot 
Remarks:

required rating
access to launch (by car, foot, 4x4)
Time to launch from landing

*/
	function waypoint($id="") {
		$this->gpsPoint(); 	  
		if ($id!="") {
			$this->waypointID=$id;
		}
	}

	function getFromDB() {
		global $db,$waypointsTable;
		$res= $db->sql_query("SELECT * FROM $waypointsTable WHERE ID=".$this->waypointID );
  		if($res <= 0){   
		     return;
	    }

	    $wpInfo = mysql_fetch_assoc($res);

		$this->lat =$wpInfo['lat'];
		$this->lon =$wpInfo['lon'];
		$this->name =$wpInfo['name'];
		$this->type =$wpInfo['type'];
		$this->intName =$wpInfo['intName'];
		$this->location =$wpInfo['location'];
		$this->intLocation =$wpInfo['intLocation'];
		$this->countryCode =$wpInfo['countryCode'];
		$this->link =$wpInfo['link'];
		$this->description =$wpInfo['description'];
		$this->modifyDate=$wpInfo['modifyDate'];
    }

	function exportXML() {
return "<waypoint>
<name>".htmlspecialchars($this->name)."</name>
<intName>".htmlspecialchars($this->intName)."</intName>
<location>".htmlspecialchars($this->location)."</location>
<intLocation>".htmlspecialchars($this->intLocation)."</intLocation>
<countryCode>".htmlspecialchars($this->countryCode)."</countryCode>
<type>".htmlspecialchars($this->type)."</type>
<lat>".htmlspecialchars($this->lat)."</lat>
<lon>".htmlspecialchars(-$this->lon)."</lon>
<link>".htmlspecialchars($this->link)."</link>
<description>".htmlspecialchars($this->description)."</description>
<modifyDate>".htmlspecialchars($this->modifyDate)."</modifyDate>
</waypoint>";
	
	}

	function putToDB($update=0) {
		global $db,$waypointsTable;

		if ($update) {
			$query="REPLACE INTO ";		
			$fl_id_1="ID, modifyDate,";
			$this->modifyDate= date("Y-m-d H:i:s"); 
			$fl_id_2=$this->waypointID.", now(), ";
		}else {
			$query="INSERT INTO ";		
			$fl_id_1="modifyDate,";
			$fl_id_2="now(),";
			$this->modifyDate= date("Y-m-d H:i:s"); 
		}


		$query.=" $waypointsTable 
					   ( $fl_id_1 name ,intName, lat,lon, type, location ,intLocation ,CountryCode , link, description ) 
				VALUES ( $fl_id_2 '".prep_for_DB($this->name)."', '".prep_for_DB($this->intName) ."', ".$this->lat .", ".$this->lon." , ".$this->type.", 
						'".prep_for_DB($this->location)."', '".prep_for_DB($this->intLocation )."', '".prep_for_DB($this->countryCode) .
						"' , '".prep_for_DB($this->link )."' , '".prep_for_DB($this->description) ."'  )";
		// echo $query;
	    $res= $db->sql_query($query);
	    if($res <= 0){
		  echo "Error putting waypount to DB<BR>";
		  return 0;
	    }					
		return 1;
    }

}

?>