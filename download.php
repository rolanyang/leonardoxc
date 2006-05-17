<? 
/************************************************************************/
/* Leonardo: Gliding XC Server					            			*/
/* ============================================                         */
/*                                                                      */
/* Copyright (c) 2004-5 by Andreadakis Manolis                          */
/* http://sourceforge.net/projects/leonardoserver                       */
/*                                                                      */
/* This program is free software. You can redistribute it and/or modify */
/* it under the terms of the GNU General Public License as published by */
/* the Free Software Foundation; either version 2 of the License.       */
/************************************************************************/
	
 	require_once "EXT_config_pre.php";
	require_once "config.php";
 	require_once "EXT_config.php";

	require_once "FN_functions.php";	
	require_once "FN_UTM.php";
	require_once "FN_pilot.php";
	require_once "FN_waypoint.php";	
	require_once "FN_output.php";
	require_once "CL_flightData.php";
	require_once "language/lang-".$currentlang.".php";
	require_once "language/countries-".$currentlang.".php";
	setDEBUGfromGET();

	$type=$_REQUEST['type'];
	if (!in_array($type,array("kml_trk","kml_wpt")) ) return;

	if ($type=="kml_trk") {

		$flightID=$_REQUEST['flightID'];
		$flightID=$flightID+0;

		DEBUG("DL",1,"Will serve flight $flightID<BR>");

		$flight=new flight();
		$flight->getFlightFromDB($flightID);
	//	if ( $flight->userID!=$userID && ! in_array($userID,$admin_users) && $flight->private) {
	//		echo _FLIGHT_IS_PRIVATE;
	//		return;
	//	}
		$xml=$flight->createKMLfile();

		$file_name=$flight->filename.".kml";	
		DEBUG("DL",1,"KML Filepath= $file_path<BR>");
	} else 	if ($type=="kml_wpt") {		
		$waypointID=$_REQUEST['wptID'];
		$waypointID=$waypointID+0;
		
		$xml=makeKMLwaypoint($waypointID);
		$file_name=$waypointID.'.kml';
	}


		list($browser_agent,$browser_version)=getBrowser();

		if ($browser_agent == 'opera') $attachmentMIME = 'application/kml';
		else if ($browser_agent == 'ie'  || $browser_agent == 'netscape'   || $browser_agent == 'mozilla'  ) 
			$attachmentMIME ="application/vnd.google-earth.kml+xml";
		else $attachmentMIME ='application/octet-stream';

		DEBUG("DL",1,"browser_agent=$browser_agent, browser version=$browser_version<BR>");

		header("Pragma: public"); // required
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private",false); // required for certain browsers 

		header("Content-type: $attachmentMIME");
		//header("Content-Disposition: attachment; filename=\"$kml_file_name\"", true);
		header('Content-Disposition: inline; filename="' . htmlspecialchars($file_name) . '"');
		header("Content-Transfer-Encoding: binary");

		$size = strlen($xml);
		header("Content-length: $size");
		echo $xml;

?>