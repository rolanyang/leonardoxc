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

showFlight($flightID);  

function showFlight($flightID) {
  global $Theme;
  global $module_name, $flightsAbsPath,$flightsWebPath, $takeoffRadious,$landingRadious;
  global $moduleRelPath;
  global $userID,$admin_users;
  global $enableOLCsubmission ,$scoringServerActive;
  global $gliderCatList;
  global $PREFS;
  global $CONF_show_DBG_XML;

  $flight=new flight();
  $flight->getFlightFromDB($flightID);
  if ( $flight->userID!=$userID && ! in_array($userID,$admin_users) && $flight->private) {
		echo "<TD align=center>"._FLIGHT_IS_PRIVATE."</td>";
		return;
  }

  $flight->incViews();
  $location=formatLocation(getWaypointName($flight->takeoffID),$flight->takeoffVinicity,$takeoffRadious);
  $opString="";
  if ( $flight->userID==$userID || in_array($userID,$admin_users) )
		$opString="<a href='?name=$module_name&op=delete_flight&flightID=".$flightID."'><img src='".$moduleRelPath."/img/x_icon.gif' border=0 align=bottom></a>
				   <a href='?name=$module_name&op=edit_flight&flightID=".$flightID."'><img src='".$moduleRelPath."/img/change_icon.png' border=0 align=bottom></a>"; 


  open_inner_table("<table class=main_text width=100% cellpadding=0 cellspacing=0><tr><td>"._PILOT.": <a href='?name=$module_name&op=list_flights&pilotID=".$flight->userID."'>".$flight->userName.
					"</a> 
					<a href='?name=$module_name&op=pilot_profile&pilotIDview=".$flight->userID."'><img src='".$moduleRelPath."/img/icon_magnify_small.gif' border=0></a>". 	   
			        "<a href='?name=$module_name&op=pilot_profile_stats&pilotIDview=".$flight->userID."'><img src='".$moduleRelPath."/img/icon_stats.gif' border=0></a>
					&nbsp;&nbsp; "._DATE_SORT.": ".formatDate($flight->DATE)."</td><td align=right width=50><div align=right>".$opString."</div></td></tr></table>",650,$flight->cat);

  if (!$flight->active &&  (mktime() - datetime2UnixTimestamp($flight->dateAdded) > 5 ) )  {  //  5 secs
		$flight->activateFlight();
  } else if (!$flight->active) {
		open_tr();
		echo "<TD align=center>"._FLIGHT_WILL_BE_ACTIVATED_SOON."<a href=''>"._TRY_AGAIN."</a></td>";
  		close_tr(); 
		close_inner_table();  
		return;
  }

  if ($_REQUEST['updateMap']) $flight->getMapFromServer();		
  if ($_REQUEST['updateMap3d']) $flight->getMapFromServer(1);		
  if ($_REQUEST['updateCharts']) $flight->updateCharts(1);		
  if ($_REQUEST['updateData'])  {
	$flight->getFlightFromIGC( $flight->getIGCFilename() );
	$flight->updateTakeoffLanding();
	$flight->putFlightToDB(1); // 1== UPDATE
  }

  if ($_REQUEST['updateScore'] || $flight->FLIGHT_POINTS==0) { 
		$flight->getOLCscore();
		$flight->putFlightToDB(1); // 1== UPDATE
  }

  $flight->updateAll(0);

  open_tr();
	   echo "<TD width=7>&nbsp</td>";
	   echo "<TD width=140 bgcolor=".$Theme->color2."><div align=".$Theme->table_cells_align.">"._TAKEOFF_LOCATION."</div></TD>";
   	   echo "<TD width=220><div align=".$Theme->table_cells_align.">".$location."&nbsp;
		<a href='?name=$module_name&op=show_waypoint&waypointIDview=".$flight->takeoffID."'><img src='".$moduleRelPath."/img/icon_magnify_small.gif' border=0></a>";
	   echo "<a href='".$moduleRelPath."/download.php?type=kml_wpt&wptID=".$flight->takeoffID."'><img src='".$moduleRelPath."/img/gearth_icon.png' border=0></a>";
  	   echo "</div></TD>";
	   echo "<TD width=10>&nbsp</td>";
	   echo "<TD width=140 bgcolor=".$Theme->color0."><div align=".$Theme->table_cells_align.">"._TAKEOFF_TIME."</div></TD>";
   	   echo "<TD width=120><div align=".$Theme->table_cells_align.">".sec2Time($flight->START_TIME)."</div></TD>";
   	   echo "<TD width=7>&nbsp</td>";
  close_tr(); 
  open_tr();
	   echo "<TD>&nbsp</td>";
	   echo "<TD bgcolor=".$Theme->color2."><div align=".$Theme->table_cells_align.">"._LANDING_LOCATION."</div></TD>";
   	   echo "<TD><div align=".$Theme->table_cells_align.">".formatLocation(getWaypointName($flight->landingID),$flight->landingVinicity,$landingRadious)."</div></TD>";
	   echo "<TD>&nbsp</td>";
	   echo "<TD bgcolor=".$Theme->color0."><div align=".$Theme->table_cells_align.">"._LANDING_TIME."</div></TD>";
   	   echo "<TD><div align=".$Theme->table_cells_align.">".sec2Time($flight->END_TIME)."</div></TD>";
   	   echo "<TD >&nbsp</td>";
  close_tr();
  open_tr();
  	   echo "<TD>&nbsp</td>";
	   echo "<TD bgcolor=".$Theme->color5."><div align=".$Theme->table_cells_align.">"._OPEN_DISTANCE."</div></TD>";
   	   echo "<TD ><div align=".$Theme->table_cells_align.">".formatDistanceOpen($flight->LINEAR_DISTANCE)."</div></TD>";
	   echo "<TD>&nbsp</td>";
	   echo "<TD  bgcolor=".$Theme->color5."><div align=".$Theme->table_cells_align.">"._DURATION."</div></TD>";
   	   echo "<TD><div align=".$Theme->table_cells_align.">".sec2Time($flight->DURATION)."</div></TD>";
	   echo "<TD>&nbsp</td>";
  close_tr();
  if ( $scoringServerActive ) {
	  open_tr();
		   echo "<TD>&nbsp</td>";
		   echo "<TD bgcolor=".$Theme->color5."><div align=".$Theme->table_cells_align.">"._MAX_DISTANCE."</div></TD>";
		   echo "<TD ><div align=".$Theme->table_cells_align.">".formatDistanceOpen($flight->MAX_LINEAR_DISTANCE)."</div></TD>";
		   echo "<TD>&nbsp</td>";
		   echo "<TD  bgcolor=".$Theme->color5."><div align=".$Theme->table_cells_align.">"._OLC_SCORE_TYPE."</div></TD>";
		   echo "<TD><div align=".$Theme->table_cells_align.">".formatOLCScoreType($flight->BEST_FLIGHT_TYPE)."</div></TD>";
		   echo "<TD>&nbsp</td>";
	  close_tr();
	  open_tr();
		   echo "<TD>&nbsp</td>";
		   echo "<TD bgcolor=".$Theme->color5."><div align=".$Theme->table_cells_align.">"._OLC_DISTANCE."</div></TD>";
		   echo "<TD ><div align=".$Theme->table_cells_align.">".formatDistanceOpen($flight->FLIGHT_KM)."</div></TD>";
		   echo "<TD>&nbsp</td>";
		   echo "<TD  bgcolor=".$Theme->color5."><div align=".$Theme->table_cells_align.">"._OLC_SCORING."</div></TD>";
		   echo "<TD><div align=".$Theme->table_cells_align.">".formatOLCScore($flight->FLIGHT_POINTS)."</div></TD>";
		   echo "<TD>&nbsp</td>";
	  close_tr();
  }
  open_tr();
  	   echo "<TD>&nbsp</td>";
	   echo "<TD bgcolor=#B6F4A8><div align=".$Theme->table_cells_align.">"._MAX_SPEED."</div></TD>";
   	   echo "<TD><div align=".$Theme->table_cells_align.">".formatSpeed($flight->MAX_SPEED)."</div></TD>";
	   echo "<TD>&nbsp</td>";
	   echo "<TD bgcolor=#B6F4A8><div align=".$Theme->table_cells_align.">"._MAX_VARIO."</div></TD>";
   	   echo "<TD><div align=".$Theme->table_cells_align.">".formatVario($flight->MAX_VARIO)."</div></TD>";
   	   echo "<TD>&nbsp</td>";
  close_tr();
  open_tr();
  	   echo "<TD>&nbsp</td>";
  	   echo "<TD bgcolor=#B6F4A8><div align=".$Theme->table_cells_align.">"._MEAN_SPEED."</div></TD>";
   	   echo "<TD><div align=".$Theme->table_cells_align.">".formatSpeed($flight->MEAN_SPEED)."</div></TD>";
	   echo "<TD>&nbsp</td>";
	   echo "<TD bgcolor=#B6F4A8><div align=".$Theme->table_cells_align.">"._MIN_VARIO."</div></TD>";
   	   echo "<TD><div align=".$Theme->table_cells_align.">".formatVario($flight->MIN_VARIO)."</div></TD>";
	   echo "<TD>&nbsp</td>";	  
  close_tr();
  if ($flight->is3D()) {
    open_tr();
	   echo "<TD>&nbsp</td>";
	   echo "<TD bgcolor=".$Theme->color3."><div align=".$Theme->table_cells_align.">"._MAX_ALTITUDE."</div></TD>";
   	   echo "<TD><div align=".$Theme->table_cells_align.">".formatAltitude($flight->MAX_ALT)."</div></TD>";
	   echo "<TD>&nbsp</td>";
	   echo "<TD bgcolor=".$Theme->color3."><div align=".$Theme->table_cells_align.">"._TAKEOFF_ALTITUDE."</div></TD>";
   	   echo "<TD><div align=".$Theme->table_cells_align.">".formatAltitude($flight->TAKEOFF_ALT)."</div></TD>";
  	   echo "<TD>&nbsp</td>";
	  close_tr();
	  open_tr();
		   echo "<TD>&nbsp</td>";
		   echo "<TD bgcolor=".$Theme->color3."><div align=".$Theme->table_cells_align.">"._MIN_ALTITUDE."</div></TD>";
		   echo "<TD><div align=".$Theme->table_cells_align.">".formatAltitude($flight->MIN_ALT)."</div></TD>";
		   echo "<TD>&nbsp</td>";
		   echo "<TD bgcolor=".$Theme->color3."><div align=".$Theme->table_cells_align.">"._ALTITUDE_GAIN."</div></TD>";
		   echo "<TD><div align=".$Theme->table_cells_align.">".formatAltitude($flight->MAX_ALT-$flight->TAKEOFF_ALT)."</div></TD>";
		   echo "<TD>&nbsp</td>";
	  close_tr();
  }
  open_tr();
   	   echo "<TD>&nbsp</td>";
	   echo "<TD bgcolor=".$Theme->color2."><div align=".$Theme->table_cells_align.">"._FLIGHT_FILE."</div></TD>";
   	   echo "<TD colspan=4><div align=left><a href='".$flight->getIGCRelPath()."'>".$flight->filename."</a>";
		echo " :: <a href='".$moduleRelPath."/download.php?type=kml_trk&flightID=".$flight->flightID."'>Google Earth</a>";
		echo "</div></TD>";
	   echo "<TD>&nbsp</td>";
  close_tr();
  if ( $flight->olcFilename  || ( $flight->insideOLCsubmitWindow() && $flight->FLIGHT_POINTS ) ) $showOLCsubmit=1;
  else  $showOLCsubmit=0;
  if ( $enableOLCsubmission && $showOLCsubmit ) {
	  open_tr();
		   echo "<TD>&nbsp</td>";
		   echo "<TD bgcolor=".$Theme->color2."><div align=".$Theme->table_cells_align.">OLC</div></TD>";
		   echo "<TD  colspan=4><div align=left>";
			if ($flight->olcFilename) {
			  $olc_url="http://www2.onlinecontest.org/holc/".$flight->getOLCYear();
			  $olcName=strtolower (substr($flight->olcFilename,0,-4) );
			  echo "[ <a href='$olc_url/map/".$olcName.".jpg' target='_blank'>"._OLC_MAP."</a> ] ";
			  echo "[ <a href='$olc_url/ENL/".$olcName.".png' target='_blank'>"._OLC_BARO."</a> ] ";
			  echo "[".substr($flight->olcDateSubmited,0,10)."] ";
			  if ( in_array($userID,$admin_users)  || $flight->userID==$userID  ) echo "(Ref: ".$flight->olcRefNum.") ";
			  echo "<img src='".$moduleRelPath."/img/olc_icon_submited.gif' border=0 align=bottom>";
			  // echo _SUBMITED_SUCCESSFULLY_ON." ".$flight->olcDateSubmited;
			  if ($flight->insideOLCsubmitWindow()  && ( in_array($userID,$admin_users)  || $flight->userID==$userID  )  ) {
				echo "<a href='?name=".$module_name."&op=olc_remove&flightID=".$flight->flightID."'>";	
				echo "<img src='".$moduleRelPath."/img/x_icon.gif' border=0 align=bottom></a>";
			  }
			}
			else if ($flight->insideOLCsubmitWindow() && $flight->FLIGHT_POINTS ) {
				echo _READY_FOR_SUBMISSION;
				if ( in_array($userID,$admin_users)  || $flight->userID==$userID  ) 
				echo " <a href='?name=".$module_name."&op=olc_submit&flightID=".$flight->flightID."'>"._SUBMIT_TO_OLC."</a>";
			}
			else  echo _CANNOT_BE_SUBMITTED;
		   echo "</div></TD>";
		   echo "<TD>&nbsp</td>";
	  close_tr();
  }
  if ($flight->comments) {
	  open_tr();
		   echo "<TD>&nbsp</td>";
		   echo "<TD bgcolor=".$Theme->color0."><div align=".$Theme->table_cells_align.">"._COMMENTS."</div></TD>";
		   echo "<TD colspan=4><div align=left>".$flight->comments."</div></TD>";
		   echo "<TD>&nbsp</td>";
	  close_tr();
  }
  if ($flight->linkURL) {
	  open_tr();
		   echo "<TD>&nbsp</td>";
		   echo "<TD bgcolor=".$Theme->color0."><div align=".$Theme->table_cells_align.">"._RELEVANT_PAGE."</div></TD>";
		   echo "<TD colspan=4><div align=left><a href='".formatURL($flight->linkURL)."' target=_blank>".$flight->linkURL."</a></div></TD>";
		   echo "<TD>&nbsp</td>";
	  close_tr();
  }
  if ($flight->glider) {
	  open_tr();
		   echo "<TD>&nbsp</td>";
		   echo "<TD bgcolor=".$Theme->color0."><div align=".$Theme->table_cells_align.">"._GLIDER."</div></TD>";
		   echo "<TD colspan=4><div align=left>".$flight->glider." [ <img src='".$moduleRelPath."/img/icon_cat_".$flight->cat.".png' align='absmiddle'> ".$gliderCatList[$flight->cat]."] </div></TD>";
		   echo "<TD>&nbsp</td>";
	  close_tr();
  }
  if ($flight->photo1Filename) {
  open_tr();
   	   echo "<TD>&nbsp</td>";
	   echo "<TD bgcolor=".$Theme->color0."><div align=".$Theme->table_cells_align.">"._PHOTOS."</div></TD>";	  	   
   	   echo "<TD colspan=4><div align=left>";
    	  if ($flight->photo1Filename) 	echo "<a href='".$flight->getPhotoRelPath(1)."' target=_blank><img src='".$flight->getPhotoRelPath(1).".icon.jpg' border=0></a>";
    	  if ($flight->photo2Filename) 	echo "<a href='".$flight->getPhotoRelPath(2)."' target=_blank><img src='".$flight->getPhotoRelPath(2).".icon.jpg' border=0></a>";
    	  if ($flight->photo3Filename) 	echo "<a href='".$flight->getPhotoRelPath(3)."' target=_blank><img src='".$flight->getPhotoRelPath(3).".icon.jpg' border=0></a>";
	   echo "</div></TD>";
   	   echo "<TD>&nbsp</td>";
  close_tr();
  }

  $firstPoint=new gpsPoint($flight->FIRST_POINT,$flight->timezone);						

	$getXMLurl="http://www.paragliding365.com/paragliding_sites_xml.html?longitude=".-$firstPoint->lon."&latitude=".$firstPoint->lat."&radius=50&type=mini";
	$xmlSitesLines=getHTTPpage($getXMLurl);
if ($xmlSitesLines) {
	$xmlSites=implode(" ",$xmlSitesLines);		
	require_once $moduleRelPath.'/miniXML/minixml.inc.php';
	$xmlDoc = new MiniXMLDoc();
	$xmlDoc->fromString($xmlSites);
	$xmlArray = $xmlDoc->toArray();
	$takeoffsNum=0;
	$takoffsList=array();

	if ($xmlArray['root']['flightareas']['flightarea']) {
		if ( is_array($xmlArray['root']['flightareas']['flightarea'][0] ) )
			$arrayToUse=$xmlArray['root']['flightareas']['flightarea'];
		else
			$arrayToUse=$xmlArray['root']['flightareas'];
	} else $arrayToUse=0;

	if ($arrayToUse)
		foreach ($arrayToUse as $flightareaNum=>$flightarea) {
			 if ( $flightareaNum!=="_num") {
					$distance=$flightarea['distance']+0; 
					if ($distance>50000) continue;
					$takoffsList[$takeoffsNum]= "<a href='".$flightarea['link']."' target=_blank>".$flightarea['name']." - ".$flightarea['location']." (".$flightarea['iso'].") [~".formatDistance($distance,1)."]</a>";
					$takeoffsNum++;
					if ($takeoffsNum==5) break;
			}
		}

  if ($takeoffsNum) {
  open_tr();
   	   echo "<TD>&nbsp</td>";
	   echo "<TD valign=top bgcolor=".$Theme->color0."><div align=".$Theme->table_cells_align.">";;
	   echo "<a href='http://www.paragliding365.com/paragliding_sites.html?longitude=".-$firstPoint->lon."&latitude=".$firstPoint->lat."&radius=50' target=_blank>";
	   echo "<img src='".$moduleRelPath."/img/paraglider365logo.gif' border=0><br>"._FLYING_AREA_INFO;
	   echo "</a>";
	   echo "</div></TD>";	  	   
   	   echo "<TD colspan=4><div align=left>";

	   	echo "<table width=100% class=main_text><tr><td valign=top align=left>";
		echo "<ul>";
			foreach ($takoffsList as $takeoffLink) echo "<li>$takeoffLink";
		echo "</ul>";
  	    //	  echo "<a href='http://www.paragliding365.com/paragliding_sites_kml.html?longitude=".-$firstPoint->lon."&latitude=".$firstPoint->lat."&radius=50' target=_blank>Google Earth Flying area 50km radius from paragliding365.com</a><br>";
		echo "</td></tr></table>";

	   echo "</div></TD>";
   	   echo "<TD>&nbsp</td>";
  close_tr();
  }

} // if we have content

  if (in_array($userID,$admin_users) ) {
	  open_tr();
		   echo "<TD>&nbsp</td>";
		   echo "<TD bgcolor=".$Theme->color0."><div align=".$Theme->table_cells_align.">"._MORE_INFO."</div></TD>";	  	   
		   echo "<TD colspan=4><div align=left>";

		   echo "<b>TIMES VIEWED:</b> ".$flight->timesViewed."  ";
		   echo "<b>DATE ADDED:</b> ".$flight->dateAdded."<br>";
		  	// DEBUG MANOLIS
			 processIGC($flight->getIGCFilename());
			// display the trunpoints
			//echo "<hr> ";
			//for($k=1;$k<=5;$k++) { $vn="turnpoint$k"; echo " ".$flight->$vn." <BR>"; }
			if ($CONF_show_DBG_XML) {
				echo "<hr>";
				echo "XML from paragliding365.com<br>";
				echo "<pre>$xmlSites</pre>";
			}

		   echo "</div></TD>";
		   echo "<TD>&nbsp</td>";
	  close_tr();
  }
  open_tr();
  echo "<td colspan=7><center>";

	  if (in_array($userID,$admin_users)) {
	  	echo "<a href='?name=".$module_name."&op=show_flight&flightID=".$flight->flightID."&updateData=1'>"._UPDATE_DATA."</a> | ";
	  	echo "<a href='?name=".$module_name."&op=show_flight&flightID=".$flight->flightID."&updateMap=1'>"._UPDATE_MAP."</a> | ";
	  	echo "<a href='?name=".$module_name."&op=show_flight&flightID=".$flight->flightID."&updateCharts=1'>"._UPDATE_GRAPHS."</a> | ";
		echo "<a href='?name=".$module_name."&op=show_flight&flightID=".$flight->flightID."&updateScore=1'>"._UPDATE_SCORE."</a> | ";
	

		echo "<a href='?name=".$module_name."&op=add_waypoint&lat=".$firstPoint->lat."&lon=".$firstPoint->lon."&takeoffID=".$flight->takeoffID."'>"._ADD_WAYPOINT."</a> <br> ";
		@include dirname(__FILE__)."/site/admin_takeoff_info.php";
	  }
	  
	  if ( is_file($flight->getMapFilename() ) )
	  	echo "<br><img src='".$flight->getMapRelPath()."' border=0>";	
  	
      if ($flight->is3D() &&  is_file($flight->getChartfilename("alt",$PREFS->metricSystem))) 
      	echo "<br><br><img src='".$flight->getChartRelPath("alt",$PREFS->metricSystem)."'>";
	  if ( is_file($flight->getChartfilename("takeoff_distance",$PREFS->metricSystem)) )
	  	echo "<br><br><img src='".$flight->getChartRelPath("takeoff_distance",$PREFS->metricSystem)."'>";
	  if ( is_file($flight->getChartfilename("speed",$PREFS->metricSystem)) )
	  	echo "<br><br><img src='".$flight->getChartRelPath("speed",$PREFS->metricSystem)."'>";
	  if ($flight->is3D() &&  is_file($flight->getChartfilename("vario",$PREFS->metricSystem))) 
	  	echo "<br><br><img src='".$flight->getChartRelPath("vario",$PREFS->metricSystem)."'>";
  echo "</center></td>";
  close_tr();
	
  close_inner_table();   
}

?>