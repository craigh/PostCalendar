<?php
/*
$Id$
This api utilizes iCalcreator class to manipulate uploaded files
and create downloadable event files
*/
Loader::requireOnce('modules/PostCalendar/pnincludes/iCalcreator.class.php');

// process uploaded .ics file
// store to PC database
function postcalendar_icalapi_processupload($args)
{
	extract($args);


	$vevent  = array();
	$vevent_save_data  = array();
	$counter = 0;
	
	//$fp = fopen($_FILES['icsupload']['tmp_name'], "r");
	$fp = fopen($icsupload['tmp_name'], "r");
	while(!feof($fp))
	{
		$fileline = fgets($fp);
		if(preg_match('(BEGIN:VCALENDAR)', $fileline, $result))
	        {
			$write = 1;
		}

		if((preg_match('(BEGIN:VEVENT)', $fileline, $result))&&($write==1))
		{
			$write=2;
		}
		
		if((preg_match('(SUMMARY:)', $fileline, $result))&&($write == 2))
		{
			$start					=  strpos($fileline, ":")+1;
			$vevent[$counter]['title']  		=  substr($fileline, $start);
		}
		if((preg_match('(DESCRIPTION:)', $fileline, $result))&&($write == 2))
		{
			$start					=  strpos($fileline, ":")+1;
			$vevent[$counter]['description']	=  trim(substr($fileline, $start, -5));
		}
		if((preg_match('(\s+Contact:)', $fileline, $result))&&($write == 2))
		{
			$start					=  strpos($fileline, ":")+2;
			$vevent[$counter]['contact']   		=  substr($fileline, $start, -3);
		}
		if((preg_match('(\s+Phone:)', $fileline, $result))&&($write == 2))
		{
			$start					=  strpos($fileline, ":")+2;
			$vevent[$counter]['phone']   		=  substr($fileline, $start, -3);
		}
		if((preg_match('(\s+Email:)', $fileline, $result))&&($write == 2))
		{
			$start					=  strpos($fileline, ":")+2;
			$vevent[$counter]['email']   		=  substr($fileline, $start, -3);
		}
		if((preg_match('(\s+URL:)', $fileline, $result))&&($write == 2))
		{
			$start					=  strpos($fileline, ":")+9;
			$vevent[$counter]['url']   		=  substr($fileline, $start, -3);
		}
		if((preg_match('(ALLDAY:)', $fileline, $result))&&($write == 2))
		{
			$start					=  strpos($fileline, ":")+1;
			$vevent[$counter]['allday']  		=  substr($fileline, $start);
		}
		if((preg_match('(TOPIC:)', $fileline, $result))&&($write == 2))
		{
			$start					=  strpos($fileline, ":")+1;
			$vevent[$counter]['topic']  		=  substr($fileline, $start);
		}
		if((preg_match('(FEE:)', $fileline, $result))&&($write == 2))
		{
			$start					=  strpos($fileline, ":")+1;
			$vevent[$counter]['fee']  		=  substr($fileline, $start);
		}
		if((preg_match('(\s+Location:)', $fileline, $result))&&($write == 2))
		{
			$start					=  strpos($fileline, ":")+2;
			$vevent[$counter]['location']  		=  substr($fileline, $start, -3);
		}
		if((preg_match('(\s+City,\s+ST\s+ZIP:)', $fileline, $result))&&($write == 2))
		{
			$start					=  strpos($fileline, ":")+2;
			$help			 		=  substr($fileline, $start, -3);
			$citystop				=  strpos($help, ",");
			$vevent[$counter]['city']  		=  substr($help, 0, $citystop);
			$statezip				=  trim(substr($help, $citystop+1));
			$statestop				=  strpos($statezip, " ");
			$vevent[$counter]['state']		=  substr($statezip, 0, $statestop); 
			$vevent[$counter]['zip']		=  substr($statezip, $statestop+1);
		}
		if((preg_match('(CATEGORIES:)', $fileline, $result))&&($write == 2))
		{
			$start					=  strpos($fileline, ":")+1;
			$category		  		=  trim(substr($fileline, $start));
			$cat_id = DBUtil::selectFieldByID ('postcalendar_categories', 'catid', $category, 'catname');
			if(!$cat_id)
				$cat_id = 1;
			$vevent[$counter]['cat_id'] = $cat_id;
		}
		if(preg_match('(DTSTAMP:)', $fileline, $result))
	        {
			$stampstart			= strpos($fileline, ":")+1;
			$vevent[$counter]['stdate']	= postcalendar_user_splitdate(substr($fileline, $stampstart, 8));
			$vevent[$counter]['sttime']	= postcalendar_user_splittime(substr($fileline, $stampstart+9, 6));
		}
		if(preg_match('(DTSTART:)', $fileline, $result))
	        {
			$datestart			  = strpos($fileline, ":")+1;
			$vevent[$counter]['startdate'] = postcalendar_user_splitdate(substr($fileline, $datestart, 8));
			$vevent[$counter]['starttime'] = postcalendar_user_splittime(substr($fileline, $datestart+9, 6));

		}
		if(preg_match('(DTEND:)', $fileline, $result))
	        {
			$dateend			= strpos($fileline, ":")+1;
			$vevent[$counter]['enddate']	= postcalendar_user_splitdate(substr($fileline, $dateend, 8));
			$vevent[$counter]['endtime']	= postcalendar_user_splittime(substr($fileline, $dateend+9, 6));
		}

		$event_repeat_data = array();
		$event_repeat_data['event_reqeat_freq'] 	= "1";
		$event_repeat_data['event_reqeat_freq_type'] 	= "0";
		$event_repeat_data['event_reqeat_on_num'] 	= "1";
		$event_repeat_data['event_reqeat_on_day'] 	= "0";
		$event_repeat_data['event_reqeat_on_freq'] 	= "1";
		
		$event_location_data = array();
		$event_location_data['event_location']  = $vevent[$counter]['location'];
		$event_location_data['event_street1']   = $vevent[$counter]['street1'];
		$event_location_data['event_street2']   = $vevent[$counter]['street2'];
		$event_location_data['event_city']	= $vevent[$counter]['city'];
		$event_location_data['event_state']	= $vevent[$counter]['state'];
		$event_location_data['event_postal']	= $vevent[$counter]['zip'];
		
		if(preg_match('(END:VEVENT)', $fileline, $result))
	        {
			$vevent[$counter]['pc_recurrspec']	= serialize($event_repeat_data);
			$vevent[$counter]['pc_location']	= serialize($event_location_data);
			$write = 1;
			$counter++;
		}
		if(preg_match('(END:VCALENDAR)', $fileline, $result))
	        {
			$write = 0;
		}
	}
	foreach($vevent as $ve)
	{
		$duration = NULL;
		$ve['endtime']['second'] < $ve['starttime']['second'] ? $ve['endtime']['minute']++ : '';
		$ve['endtime']['minute'] < $ve['starttime']['minute'] ? $ve['endtime']['hour']++ : '';
		$duration = 3600*($ve['endtime']['hour']-$ve['starttime']['hour'])
	       		    + 60*($ve['endtime']['minute']-$ve['starttime']['minute'])
			    +    ($ve['endtime']['second']-$ve['starttime']['second']);
		$pc_aid = pnSessionGetVar('uid');
		$pc_informant = pnUserGetVar('name', $pc_aid);
		
		$sql  = "SELECT pc_meeting_id FROM $event_table ORDER BY pc_meeting_id DESC LIMIT 1";
		$res  = DBUtil::executeSQL ($sql, false, false);
		$emid = $res->fields[0];
		
		$pc_endDate   = $ve['enddate']['year']."-".$ve['enddate']['month']."-".$ve['enddate']['day']; 
		$pc_endTime   = $ve['endtime']['hour'].":".$ve['endtime']['minute'].":".$ve['endtime']['second'];
		$pc_eventDate = $ve['startdate']['year']."-".$ve['startdate']['month']."-".$ve['startdate']['day']; 
		$pc_startTime = $ve['starttime']['hour'].":".$ve['starttime']['minute'].":".$ve['starttime']['second'];
		$pc_timestamp = $ve['stdate']['year']."-".$ve['stdate']['month']."-".$ve['stdate']['day']." ".$ve['sttime']['hour'].":".$ve['sttime']['minute'].":".$ve['sttime']['second'];


		
		$where = " WHERE pc_catid     = $ve[cat_id] 
			   AND   pc_aid       = '$pc_aid'
			   AND   pc_title     = '$ve[title]'
			   AND   pc_hometext  = ':text:$ve[description]'
			   AND   pc_eventDate = '$pc_eventDate' 
			   AND   pc_duration  = $duration
			   AND   pc_startTime = '$pc_startTime'";
		$event = DBUtil::selectObject ('postcalendar_events', $where);
		if (!$event)
		{
			$obj = array ();
			$obj['catid']      = $ve['cat_id'];
			$obj['aid']         = $pc_aid;
			$obj['title']       = $ve['title'];
			$obj['time']        = $pc_timestamp;
			$obj['hometext']    = ":text:$ve[description]";
			$obj['topic']       = $ve['topic'];
			$obj['informant']   = $pc_informant;
			$obj['eventDate']   = $pc_eventDate;
			$obj['endDate']     = $pc_endDate;
			$obj['duration']    = $duration;
			$obj['recurrtype']  = 0;
			$obj['recurrspec']  = $ve['pc_recurrspec'];
			$obj['recurrfreq']  = 0;
			$obj['startTime']   = $pc_eventDate;
			$obj['endTime']     = $pc_endDate;
			$obj['alldayevent'] = $ve['allday'];
			$obj['location']    = $ve['pc_location'];
			$obj['conttel']     = $ve['phone'];
			$obj['contname']    = $ve['contact'];
			$obj['contemail']   = $ve['email'];
			$obj['website']     = $ve['url'];
			$obj['fee']         =  $ve['fee'];
			$obj['eventstatus'] = 1;
			$obj['sharing']     = 1;
			$obj['language']    = NULL;
			$obj['meeting_id']  = $emid;
			$result = DBUtil::insertObject ($obj, 'postcalendar_events');
		}
	}

	pnModAPIFunc('PostCalendar','admin','clearCache');
	if ($result==true) {
		return true;
	} else {
		return false;
	}
}

//export ical event from provided event info
function postcalendar_icalapi_export_ical ($sevents)
{
  $eid      = FormUtil::getPassedValue('eid');
  $category = FormUtil::getPassedValue('category');
  $sitename = getenv ('SERVER_NAME');

	$v = new vcalendar();
	$v->setConfig( 'unique_id', $sitename );

	$v->setProperty( 'method', 'PUBLISH' );
	$v->setProperty( "x-wr-calname", "Calendar from ".$sitename );
	$v->setProperty( "X-WR-CALDESC", "Calendar from ".$sitename );
	list($tzoffset, $tztext)= postcalendar_icalapi_getTZ();
	$v->setProperty( "X-WR-TIMEZONE", $tztext);

  foreach ($sevents as $cdate => $event)
  {
      # $cdate has the events actual date
      # $event has the event array for $cdate day
      foreach ($event as $item)
      {
          # Allow a selection by unique eventid and/or category
          if (($item['eid'] == $eid || $eid == "") &&
              ($item['catname'] == $category || $category == ""))
          {
              # slurp out the fields to make it more convenient
              $starttime        = $item['startTime'];
              $duration         = $item['duration'];
              $title            = $item['title'];
              $summary          = $item['title'];
              $description      = html_entity_decode(strip_tags(substr($item['hometext'],6)));
              $evcategory       = $item['catname'];
              $location         = $item['event_location'];
              $uid              = $item['eid'] . "--" .  strtotime ($item['time']) . "@$sitename";
              $url              = $item['website'];
              $peid             = $item['eid'];
              $allday           = $item['alldayevent'];
              $fee              = $item['fee'];
              $topic            = $item['topic'];
       
              # this block of code cleans up encodings such as &#113; in the
              # email addresses.  These were escaped on store by postcalendar
              # and I'm too lazy to figure out a regexp to fix it.
              # it builds two arrays with search and replace and then calls
              # str_replace once to translate everything over.
              $email = $item ['contemail'];
              for ($i=1; $i<=127; $i++)
              {
                  $srch [$i] = sprintf ("&#%03.3d;", $i);
                  $repl [$i] = chr ($i);
              }
              $item ['contemail'] = str_replace ($srch, $repl, $item ['contemail']);
              $email = str_replace ($srch, $repl, $email);
              $organizer = $email;
       
              # indent the original description so VEVENT doesn't blow up on DESCRIPTION
              $description = preg_replace ('!^!m', str_repeat (' ', 2), $description);

              # Build the event description text.
              $evtdesc = $description . "\N\n" .
                "  For more information:\N\n" .
                "  Contact: " . $item['contname'] . "\N\n" .
                "  Phone: " . $item['conttel'] . "\N\n" .
                "  Email: " . $email . "\N\n" .
                "  URL: " . $item['website'] . "\N\n";
              if ($item['event_location'])
                  $eventdesc .= "  Location: " . $item['event_location'] . "\N\n";
              if ($item['event_street1'])
                  $eventdesc .= "  Street Addr 1: " . $item['event_street1'] . "\N\n";
              if ($item['event_street2'])
                  $eventdesc .= "  Street Addr 2: " . $item['event_street2'] . "\N\n";
              if ($item['event_city'])
                  $eventdesc .= "  City, ST ZIP: " . $item['event_city'] . "," . $item['event_state'] . " " . $item['event_postal'] . "\N\n";

              # Build the ALTREP line as a link to the actual calendar
              $args = array();
              $args['Date'] = date ("Ymd", strtotime ($cdate));
              $args['viewtype'] = 'details';
              $args['eid'] = $peid;
              $url = pnModURL ('PostCalendar', 'user', 'view', $args);

              # output the vCard/iCal VEVENT object
              //echo "BEGIN:VEVENT\n";
							$vevent = new vevent(); 
              if ($organizer <> "")
              {
                  //echo "ORGANIZER:MAILTO:$organizer\n";
									$vevent->setProperty( 'ORGANIZER:MAILTO', $organizer );
                  //echo "CONTACT:MAILTO:$organizer\n";
									$vevent->setProperty( 'CONTACT:MAILTO', $organizer );
              }
              if ($url <> "") 
              {
                //echo "URL:$url\n"; 
 								$vevent->setProperty( 'URL', $url );
							}

/*              echo "SUMMARY:$summary\n";
              echo "DESCRIPTION:$evtdesc\n";
              echo "TZ:-5\r\n";
              echo "CATEGORIES:$evcategory\n";
              echo "LOCATION:$location\n";
              echo "TRANSP:OPAQUE\n";
              echo "CLASS:CONFIDENTIAL\n";
              echo "DTSTAMP:" . gmdate ("Ymd") . "T" . gmdate ("His") . "Z\n";
              echo "ALLDAY:" . $allday."\n";
              echo "FEE:" . $fee."\n";
              echo "TOPIC:" . $topic."\n"; */
								$vevent->setProperty( 'SUMMARY', $summary );
 								$vevent->setProperty( 'DESCRIPTION', $evtdesc );
 								$vevent->setProperty( 'TZ', $tzoffset );
								$vevent->setProperty( 'CATEGORIES', $evcategory );
 								$vevent->setProperty( 'LOCATION', $location );
 								$vevent->setProperty( 'TRANSP', 'OPAQUE' );
 								$vevent->setProperty( 'CLASS', 'CONFIDENTIAL' );
 								$vevent->setProperty( 'DTSTAMP', gmdate ("Ymd") . "T" . gmdate ("His") . "Z" );
 								$vevent->setProperty( 'ALLDAY', $allday );
 								$vevent->setProperty( 'FEE', $fee );
 								$vevent->setProperty( 'TOPIC', $topic );
             # format up the date/time into ical format for output
              # build the normal date/time string ...
              $evtstr = $cdate . " ". $item['startTime'];
              # convert it to unix time ...
              $evttime = strtotime ($evtstr);
              # add duration to get the end time ...
              $evtend = $evttime + $duration;

              # format it for output
              //$startdate = gmdate ("Ymd", $evttime) . "T" . gmdate ("His", $evttime) ."Z";
							//$startdate = gmdate("Y^m^d^H^i^s", $evttime);
							list($year,$month,$day,$hour,$min,$sec)=explode(gmdate("Y^m^d^H^i^s", $evttime));
              //echo "DTSTART:$startdate\n";
							$vevent->setProperty( 'dtstart', array( 'year'=>$year, 'month'=>$month, 'day'=>$day, 'hour'=>$hour, 'min'=>$min,  'sec'=>$sec ));

              //$enddate = gmdate ("Ymd", $evtend) . "T" . gmdate ("His", $evtend) . "Z";
              //echo "DTEND:$enddate\n";
							list($year,$month,$day,$hour,$min,$sec)=explode(gmdate("Y^m^d^H^i^s", $evtend));
							$vevent->setProperty( 'dtend', array( 'year'=>$year, 'month'=>$month, 'day'=>$day, 'hour'=>$hour, 'min'=>$min,  'sec'=>$sec ));

              # bury a serialized php structure in the COMMENT field.
              if (($extendedinfo == 1) && ($extendedinfoallowed == 1))
              {
                  $extinfo['url']               = $url;
                  $extinfo['date']              = gmdate ("Ymd", $evttime);
                  $extinfo['eid']               = $peid;
                  $extinfo['eventtime']         = $evttime;
                  $extinfo['icallink']          = "http://$sitename/modules/PostCalendar/ical.php?eid=$peid&date=" .  date ("Ymd", strtotime ($cdate));
                  $extinfo['evtstartunixtime']  = $evttime;
                  $extinfo['evtendunixtime']    = $evtend;

                  foreach ($item as $key => $data)
                  { 
                      $extinfo[$key] = $item[$key]; 
                  }

                  //echo "COMMENT:" . serialize ($extinfo) . "\n";
  								$vevent->setProperty( 'COMMENT', serialize ($extinfo) );
             }

              //echo "END:VEVENT\n";
							$v->setComponent ( $vevent ); 
          }
      }
  }
  //echo "END:VCALENDAR\n";
	$v->returnCalendar();
  return true;
}
function postcalendar_icalapi_getTZ()
{
	$tzinfo = pnConfigGetVar('timezone_info');
	$tzid = pnConfigGetVar('timezone_offset');
	$timezones = array();
	foreach ($tzinfo as $tzindex => $tzdata) {
		$timezones[$tzindex] = $tzdata;
	}
	return array($tzid, $timezones[$tzid]);
}
?>