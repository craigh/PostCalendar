ical.php	- Add on module to extract PostCalendar 4.0.x data
		  and render it as iCalendar (RFC 2445) data streams.


	Author:		Eric Germann
	Date:		January 15, 2004
	Version:	0.1
	Support:	postical@cctec.com




ical.php is an addon module for the PostNuke CMS system and specifically, 
the PostCalendar Event Calendar module.  The script is self contained 
and requires no modifications to any of the PostNuke or PostCalendar
files.  It is written in PHP, but support export of the data to other
programming languages via the iCalendar specification and also via
an 'extended info' field in the COMMENT field of a VEVENT record.  This
extended info is a serialized PHP data structure, so any language 
capable of reading and processing this structure can have full access
to the fields from PostCalendar.

Examples of consumers of this extended info include:

  PHP:	via unserialize
  Perl:	via PHP::Serialization

To use this module, unpack it in your main HTML directory where 
Postnuke is located.  It will add ical.php to this directory, and
also create a new template for PostCalendar called 'icalenabled'.
The template is not required, but can be used to show you how 
you can link events, weeks, days, months and years to PostCalendar
views.

To use this module, you can do the following:

http://www.yoursite.ex/ical.php

This is the default view which is all events for "today".

URL parameters are as follows:

date=YYYY-MM-DD		filter to a specific date

Use the following two together for a date range:

start=YYYY-MM-DD	filter to a specific start date
end=YYYY-MM-DD		filter to a specific end date

To get a specific category:

category=name		name is URL encoded Postnuke category name

To get extended information:

extendedinfo=1		Return extended info (experimental)
  This adds a COMMENT field to the VEVENT record.  The comment field
  is the PHP array of data associated with this event (ALL of it)
  persisted using serialize.  You can reconstitute it via
  unserialize in PHP or via the PHP::Serialization module in Perl.

debug=1			Turn on debugging
  This returns the data as an HTML page with the VCALENDAR data
  as text.  It also appends the unsorted and sorted arrays dumped
  via print_r to the end of it.  Useful to see what we're getting
  out vs. what is in the database.

type=attach		Send as an attachment
  This changes the content dispostition header to turn off inline.
  In laymens terms, it pops up the "Save" dialog in a browser vs. 
  trying to launch the program associated with .ics file extensions.

  This exists because every version of Outlook I tested only 
  imports the first event in the file if there are multiple.  If you
  import a file from disk, though, it will import them all.  So, 
  this allows you to save the file, then import it into your calendar.

eid=x			Refer to a specific event ID (Postcalendar generated).


In the ical.php file, two variables at the top control behavior:

  $debugallowed = 1|0 controls whether dumping of the array is done when
  debug flag is sent via the URL.

  $extendedinfoallowed = 1|0 controls whether extended info is sent when
  extendedinfo flag is sent via the URL.


Parameters can be stacked to do cool things.

Show me all events for PostNuke catagory "My Sport" for this year.

  http://localhost/ical.php?start=2004-01-01&end=2004-01-31&category=My+Sport&type=attach

Give me todays events for category "My Sport"

  http://localhost/ical.php?category=My+Sport

Give me extendeded info for this months events for "My Sport"

  http://localhost/ical.php?start=2004-01-01&end=2004-01-31&category=My+Sport&extendedinfo=1

Give me the entire calendar for the year (may be huge)

  http://localhost/ical.php?start=2004-01-01&end=2004-12-31

Some Notes about the templates

  A new set of templates have been created for PostCalendar called 'icalenabled'.  
  These add links at the top of each view to download that day|week|month|year's 
  events.  This is category enabled, so if you filtered the view in the displayed
  calendar on the web, you'll get a filtered view for that category also.

  To use the new template, simply go to PostCalendar in the Administration section
  and change the default template to 'icalenabled'.  Then the links will show up.
  In detailed view or daily views, a little bell will be added, which will link to 
  the specific event and import it inline (add it to Outlook).
