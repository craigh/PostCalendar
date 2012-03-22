<?php
/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009-2012, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

use PostCalendar_Entity_Repository_CalendarEventRepository as EventRepo;
use PostCalendar_Entity_CalendarEvent as CalendarEvent;

/**
 * This is the event handler api
 **/

class PostCalendar_Api_Event extends Zikula_AbstractApi
{
    const ENDTYPE_ON = 1;
    const ENDTYPE_NONE = 0;

    const REPEAT_EVERY_DAY = 0;
    const REPEAT_EVERY_WEEK = 1;
    const REPEAT_EVERY_MONTH = 2;
    const REPEAT_EVERY_YEAR = 3;
    public $rTypes = array(self::REPEAT_EVERY_DAY => "day",
        self::REPEAT_EVERY_WEEK => "week",
        self::REPEAT_EVERY_MONTH => "month",
        self::REPEAT_EVERY_YEAR => "year",
    );
    
    const REPEAT_ON_1ST = 1;
    const REPEAT_ON_2ND = 2;
    const REPEAT_ON_3RD = 3;
    const REPEAT_ON_4TH = 4;
    const REPEAT_ON_LAST = 5;
    public $rWeeks = array(self::REPEAT_ON_1ST => "first",
        self::REPEAT_ON_2ND => "second",
        self::REPEAT_ON_3RD => "third",
        self::REPEAT_ON_4TH => "fourth",
        self::REPEAT_ON_LAST => "last",
    );

    const REPEAT_ON_SUN = 0;
    const REPEAT_ON_MON = 1;
    const REPEAT_ON_TUE = 2;
    const REPEAT_ON_WED = 3;
    const REPEAT_ON_THU = 4;
    const REPEAT_ON_FRI = 5;
    const REPEAT_ON_SAT = 6;
    public $rDays = array(self::REPEAT_ON_SUN => "Sunday",
        self::REPEAT_ON_MON => "Monday",
        self::REPEAT_ON_TUE => "Tuesday",
        self::REPEAT_ON_WED => "Wednesday",
        self::REPEAT_ON_THU => "Thursday",
        self::REPEAT_ON_FRI => "Friday",
        self::REPEAT_ON_SAT => "Saturday",
    );
    
    /**
     * This function returns an array of events sorted by date
     *    if either is present, both must be present. else uses today's/jumped date.
     * expected args (from search/postcalendar_search_options): s_keywords, filtercats, seachstart, searchend
     **/
    public function getEvents($args)
    {
        $startDate   = isset($args['start'])       ? $args['start']       : new DateTime();
        $endDate     = isset($args['end'])         ? $args['end']         : null;
        $filtercats  = isset($args['filtercats'])  ? $args['filtercats']  : '';
        $userFilter  = isset($args['pc_username']) ? $args['pc_username'] : '';
        $searchDql   = isset($args['s_keywords'])  ? $args['s_keywords']  : ''; // search WHERE dql
        $searchstart = isset($args['searchstart']) ? $args['searchstart'] : '';
        $searchend   = isset($args['searchend'])   ? $args['searchend']   : '';
        $requestedDate = isset($args['date'])      ? $args['date']        : new DateTime();
        $sort        = ((isset($args['sort'])) && ($args['sort'] == 'DESC')) ? 'DESC' : 'ASC';
        $eventstatus = (isset($args['eventstatus']) && (in_array($args['eventstatus'], array(CalendarEvent::APPROVED, CalendarEvent::QUEUED, CalendarEvent::HIDDEN)))) ? $args['eventstatus'] : CalendarEvent::APPROVED;

        // update news-hooked stories that have been published since last pageload
        $bindings = HookUtil::getBindingsBetweenOwners('News', 'PostCalendar');
        if (!empty($bindings)) {
            PostCalendar_PostCalendarEvent_News::scheduler();
        }
        
        if ($startDate > $requestedDate) {
            $requestedDate = clone $startDate;
        }
        
        if (!empty($searchDql)) {
            $startDate = clone $requestedDate;
            $endDate = clone $requestedDate;
            $startDate->modify("-$searchstart years");
            $endDate->modify("+$searchend years");
        }
            
        if (empty($userFilter)) {
            $userFilter = (_SETTING_ALLOW_USER_CAL) ? EventRepo::FILTER_ALL : EventRepo::FILTER_GLOBAL;
        }
        if (!UserUtil::isLoggedIn()) {
            $userFilter = EventRepo::FILTER_GLOBAL;
        }

        // convert $userFilter to useable information
        if ($userFilter > 0) {
            // possible values: a user id - only an admin can use this
            $userid = $userFilter; // keep the id
            $userFilter = EventRepo::FILTER_PRIVATE;
        } else {
            $userid = UserUtil::getVar('uid'); // use current user's ID
        }
        
        // get event collection
        $events = $this->entityManager->getRepository('PostCalendar_Entity_CalendarEvent')
                ->getEventCollection($eventstatus, $startDate, $endDate, $userFilter, $userid, self::formatCategoryFilter($filtercats), $searchDql);
        
        //==============================================================
        // Here an array is built consisting of the date ranges
        // specific to the current view.  This array is then
        // used to build the calendar display.
        //==============================================================
        $interval = new DateInterval("P1D");
        $period = new DatePeriod($startDate, $interval, $endDate); // endDate has already been extended +1 days
        $days = array();
        foreach ($period as $date) {
            $days[$date->format('Y-m-d')] = array();
        }
        $days = ($sort == 'DESC') ? array_reverse($days) : $days;
        
        foreach ($events as $event) {
            $event = $event->getoldArray(); // convert from Doctrine Entity
            // check access for event
            if ((!SecurityUtil::checkPermission('PostCalendar::Event', "$event[title]::$event[eid]", ACCESS_OVERVIEW))
                    || (!CategoryUtil::hasCategoryAccess($event['categories'], 'PostCalendar'))) {
                continue;
            }
            $event = $this->formateventarrayfordisplay($event);
            $occurances = $this->getEventOccurances($event, true);
            foreach ($occurances as $date) {
                if (isset($days[$date])) {
                    $days[$date][] = $event;
                }
            }   
        }
        return $days;
    }

    /**
     * write an event to the DB
     * @param $args array of event data
     * @return bool true on success : false on failure;
     */
    public function writeEvent($args)
    {
        $eventdata = $args['eventdata'];
        if (!isset($eventdata['is_update'])) {
            $eventdata['is_update'] = false;
        }

        if ($eventdata['is_update']) {
            unset($eventdata['is_update']);
            $event = $this->entityManager->getRepository('PostCalendar_Entity_CalendarEvent')->find($eventdata['eid']);
        } else { //new event
            unset($eventdata['eid']); //be sure that eid is not set on insert op to autoincrement value
            unset($eventdata['is_update']);
            $eventdata['time'] = date("Y-m-d H:i:s"); //current date for timestamp on event
            $event = new PostCalendar_Entity_CalendarEvent();
        }
        try {
            $event->setFromArray($eventdata);
            $this->entityManager->persist($event);
            $this->entityManager->flush();
            $eid = $event->getEid();
        } catch (Exception $e) {
            echo "<pre>";
            var_dump($e->getMessage());
            die;
        }

        if ($eid === false) {
            return false;
        }

        return $eid;
    }

    /**
     * generate information to help build the submit form
     * this is also used on a preview of event function, so $eventdata is passed from that if 'loaded'
     * @param array eventdata
     * @param DateTime instance date
     * @return array form_data : key, val pairs to be assigned to the template, including default event data
     */
    public function buildSubmitForm($args)
    {
        $eventdata = $args['eventdata']; // contains data for editing if loaded
        $form_data = array();

        // get event default values
        $eventDefaults = $this->getVar('pcEventDefaults');
        
        // format date information
        if ((!isset($eventdata['endDate'])) || empty($eventdata['endDate'])) {
            $eventdata['endvalue'] = $args['date']->format(_SETTING_DATE_FORMAT);
            $eventdata['endDate'] = $args['date']->format('Y-m-d');
        } else {
            $eventdata['endvalue'] = PostCalendar_Util::getDate(array(
                'date' => $eventdata['endDate']))->format(_SETTING_DATE_FORMAT);
            $eventdata['endDate'] = PostCalendar_Util::getDate(array(
                'date' => $eventdata['endDate']))->format('Y-m-d');
        }
        if ((!isset($eventdata['eventDate'])) || empty($eventdata['eventDate'])) {
            $eventdata['eventDatevalue'] = $args['date']->format(_SETTING_DATE_FORMAT);
            $eventdata['eventDate'] = $args['date']->format('Y-m-d');
        } else {
            $eventdata['eventDatevalue'] = PostCalendar_Util::getDate(array(
                'date' => $eventdata['eventDate']))->format(_SETTING_DATE_FORMAT);
            $eventdata['eventDate'] = PostCalendar_Util::getDate(array(
                'date' => $eventdata['eventDate']))->format('Y-m-d');
        }

        if ((SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) && (_SETTING_ALLOW_USER_CAL)) {
            $users = DBUtil::selectFieldArray('users', 'uname', null, null, null, 'uid');
            $form_data['users'] = $users;
        }
        $eventdata['aid'] = isset($eventdata['aid']) ? $eventdata['aid'] : UserUtil::getVar('uid'); // set value of user-select box
        $form_data['username_selected'] = UserUtil::getVar('uname', $eventdata['aid']); // for display of username

        $form_data['catregistry'] = CategoryRegistryUtil::getRegisteredModuleCategories('PostCalendar', 'CalendarEvent');
        $form_data['cat_count'] = count($form_data['catregistry']);
        // configure default categories
        $eventdata['categories'] = isset($eventdata['categories']) ? $eventdata['categories'] : $eventDefaults['categories'];

        // All-day event values for radio buttons
        $eventdata['alldayevent'] = isset($eventdata['alldayevent']) ? $eventdata['alldayevent'] : $eventDefaults['alldayevent'];
        $form_data['Selected'] = $this->alldayselect($eventdata['alldayevent']);

        $eventdata['endTime'] = (empty($eventdata['endTime'])) ? $this->computeendtime($eventDefaults) : $eventdata['endTime'];

        $eventdata['startTime'] = (empty($eventdata['startTime'])) ? $eventDefaults['startTime'] : $eventdata['startTime'];

        // hometext
        if (empty($eventdata['HTMLorTextVal'])) {
            $eventdata['HTMLorTextVal'] = 'text'; // default to text
        }

        // create html/text selectbox
        $form_data['EventHTMLorText'] = array(
            'text' => $this->__('Plain text'),
            'html' => $this->__('HTML-formatted'));

        // sharing selectbox
        $form_data['sharingselect'] = $this->sharingselect();

        if (!isset($eventdata['sharing'])) {
            $eventdata['sharing'] = $eventDefaults['sharing'];
        }

        // recur type radio selects
        $form_data['SelectedNoRepeat'] = ((!isset($eventdata['recurrtype'])) || ((int) $eventdata['recurrtype'] == 0)) ? " checked='checked'" : ''; //default
        $form_data['SelectedRepeat']   = ((isset($eventdata['recurrtype']))  && ((int) $eventdata['recurrtype'] == 1)) ? " checked='checked'" : '';
        $form_data['SelectedRepeatOn'] = ((isset($eventdata['recurrtype']))  && ((int) $eventdata['recurrtype'] == 2)) ? " checked='checked'" : '';

        // recur select box arrays
        $in = explode("/", $this->__('Day(s)/Week(s)/Month(s)/Year(s)'));
        $keys = array(
            self::REPEAT_EVERY_DAY,
            self::REPEAT_EVERY_WEEK,
            self::REPEAT_EVERY_MONTH,
            self::REPEAT_EVERY_YEAR);
        $selectarray = array_combine($keys, $in);
        $form_data['repeat_freq_type'] = $selectarray;

        $in = explode("/", $this->__('First/Second/Third/Fourth/Last'));
        $keys = array(
            self::REPEAT_ON_1ST,
            self::REPEAT_ON_2ND,
            self::REPEAT_ON_3RD,
            self::REPEAT_ON_4TH,
            self::REPEAT_ON_LAST);
        $selectarray = array_combine($keys, $in);
        $form_data['repeat_on_num'] = $selectarray;

        $in = explode(" ", $this->__('Sun Mon Tue Wed Thu Fri Sat'));
        $keys = array(
            self::REPEAT_ON_SUN,
            self::REPEAT_ON_MON,
            self::REPEAT_ON_TUE,
            self::REPEAT_ON_WED,
            self::REPEAT_ON_THU,
            self::REPEAT_ON_FRI,
            self::REPEAT_ON_SAT);
        $selectarray = array_combine($keys, $in);
        $form_data['repeat_on_day'] = $selectarray;

        // recur defaults
        if (empty($eventdata['recurrspec']['event_repeat_freq_type']) || $eventdata['recurrspec']['event_repeat_freq_type'] < 1) {
            $eventdata['recurrspec']['event_repeat_freq_type'] = self::REPEAT_EVERY_DAY;
        }
        if (empty($eventdata['recurrspec']['event_repeat_on_num']) || $eventdata['recurrspec']['event_repeat_on_num'] < 1) {
            $eventdata['recurrspec']['event_repeat_on_num'] = self::REPEAT_ON_1ST;
        }
        if (empty($eventdata['recurrspec']['event_repeat_on_day']) || $eventdata['recurrspec']['event_repeat_on_day'] < 1) {
            $eventdata['recurrspec']['event_repeat_on_day'] = self::REPEAT_ON_SUN;
        }

        // endType
        $form_data['SelectedEndOn'] = ((isset($eventdata['endtype']))  && ((int)$eventdata['endtype'] == self::ENDTYPE_ON)) ? " checked='checked'" : '';
        $form_data['SelectedNoEnd'] = ((!isset($eventdata['endtype'])) || ((int)$eventdata['endtype'] == self::ENDTYPE_NONE)) ? " checked='checked'" : ''; //default

        // Assign the content format (determines if scribite is in use)
        $form_data['formattedcontent'] = $this->isformatted(array(
            'func' => 'create'));

        // assign empty values to text fields that don't need changing
        $eventdata['title']     = isset($eventdata['title'])     ? $eventdata['title']     : "";
        $eventdata['hometext']  = isset($eventdata['hometext'])  ? $eventdata['hometext']  : "";
        $eventdata['contname']  = isset($eventdata['contname'])  ? $eventdata['contname']  : $eventDefaults['contname'];
        $eventdata['conttel']   = isset($eventdata['conttel'])   ? $eventdata['conttel']   : $eventDefaults['conttel'];
        $eventdata['contemail'] = isset($eventdata['contemail']) ? $eventdata['contemail'] : $eventDefaults['contemail'];
        $eventdata['website']   = isset($eventdata['website'])   ? $eventdata['website']   : $eventDefaults['website'];
        $eventdata['fee']       = isset($eventdata['fee'])       ? $eventdata['fee']       : $eventDefaults['fee'];

        $eventdata['recurrspec']['event_repeat_freq']    = isset($eventdata['recurrspec']['event_repeat_freq'])    ? $eventdata['recurrspec']['event_repeat_freq']    : "";
        $eventdata['recurrspec']['event_repeat_on_freq'] = isset($eventdata['recurrspec']['event_repeat_on_freq']) ? $eventdata['recurrspec']['event_repeat_on_freq'] : "";

        $eventdata['location']['event_location'] = isset($eventdata['location']['event_location']) ? $eventdata['location']['event_location'] : $eventDefaults['location']['event_location'];
        $eventdata['location']['event_street1']  = isset($eventdata['location']['event_street1'])  ? $eventdata['location']['event_street1']  : $eventDefaults['location']['event_street1'];
        $eventdata['location']['event_street2']  = isset($eventdata['location']['event_street2'])  ? $eventdata['location']['event_street2']  : $eventDefaults['location']['event_street2'];
        $eventdata['location']['event_city']     = isset($eventdata['location']['event_city'])     ? $eventdata['location']['event_city']     : $eventDefaults['location']['event_city'];
        $eventdata['location']['event_state']    = isset($eventdata['location']['event_state'])    ? $eventdata['location']['event_state']    : $eventDefaults['location']['event_state'];
        $eventdata['location']['event_postal']   = isset($eventdata['location']['event_postal'])   ? $eventdata['location']['event_postal']   : $eventDefaults['location']['event_postal'];

        // assign loaded data or default values
        $form_data['loaded_event'] = $eventdata;

        return $form_data;
    }

    /**
     * @desc This function reformats the information in an event array for proper display in detail
     * @param array $event event array as pulled from the DB
     * @return array $event modified array for display
     */
    public function formateventarrayfordisplay($event)
    {
        if ((empty($event)) or (!is_array($event))) {
            return LogUtil::registerArgsError();
        }

        $event['privateicon'] = ($event['sharing'] == CalendarEvent::SHARING_PRIVATE) ? true : false;

        $event['HTMLorTextVal'] = substr($event['hometext'], 1, 4); // HTMLorTextVal needed in edit form
        $event['hometext'] = substr($event['hometext'], 6);
        if ($event['HTMLorTextVal'] == "text") {
            $event['hometext'] = nl2br(strip_tags($event['hometext']));
        }

        // build recurrance sentence for display
        $repeat_freq_type = explode("/", $this->__('Day(s)/Week(s)/Month(s)/Year(s)'));
        $repeat_on_num = explode("/", $this->__('err/First/Second/Third/Fourth/Last'));
        $repeat_on_day = explode(" ", $this->__('Sun Mon Tue Wed Thu Fri Sat'));
        if ($event['recurrtype'] == CalendarEvent::RECURRTYPE_REPEAT) {
            $event['recurr_sentence'] = $this->__f("Event recurs every %s", $event['recurrspec']['event_repeat_freq']);
            $event['recurr_sentence'] .= " " . $repeat_freq_type[$event['recurrspec']['event_repeat_freq_type']];
            $event['recurr_sentence'] .= " " . $this->__("until") . " " . $event['endDate'];
        } elseif ($event['recurrtype'] == CalendarEvent::RECURRTYPE_REPEAT_ON) {
            $event['recurr_sentence'] = $this->__("Event recurs on") . " " . $repeat_on_num[$event['recurrspec']['event_repeat_on_num']];
            $event['recurr_sentence'] .= " " . $repeat_on_day[$event['recurrspec']['event_repeat_on_day']];
            $event['recurr_sentence'] .= " " . $this->__f("of the month, every %s months", $event['recurrspec']['event_repeat_on_freq']);
            $event['recurr_sentence'] .= " " . $this->__("until") . " " . $event['endDate'];
        } else {
            $event['recurr_sentence'] = $this->__("This event does not recur.");
        }

        // build sharing sentence for display
        $event['sharing_sentence'] = ($event['sharing'] == CalendarEvent::SHARING_PRIVATE) ? $this->__('This is a private event.') : $this->__('This is a public event. ');

        $event['endTime'] = $this->computeendtime($event);
        // converts seconds to HH:MM for display  - keep just in case duration is wanted
        $event['duration'] = gmdate("G:i", $event['duration']); // stored in DB as seconds

        // prepare starttime for display HH:MM or HH:MM AP
        $event['sortTime']  = $event['startTime']; // save for sorting later
        $stime = DateTime::createFromFormat('G:i:s', $event['startTime']);
        $event['startTime'] = _SETTING_TIME_24HOUR ? $stime->format('G:i') : $stime->format('g:i a');

        // format endtype for edit form
        $event['endtype'] = (!isset($event['endDate'])) ? (string)self::ENDTYPE_NONE : (string)self::ENDTYPE_ON;

        // compensate for changeover to new categories system
        $lang = ZLanguage::getLanguageCode();
        $event['catname']      = isset($event['categories']['Main']['display_name'][$lang]) ? $event['categories']['Main']['display_name'][$lang] : $event['categories']['Main']['name'];
        $event['catcolor']     = isset($event['categories']['Main']['attributes']['color'])     ? $event['categories']['Main']['attributes']['color']     : '#eeeeee';
        $event['cattextcolor'] = isset($event['categories']['Main']['attributes']['textcolor']) ? $event['categories']['Main']['attributes']['textcolor'] : $this->color_inverse($event['catcolor']);

        // temporarily remove hometext from array
        $hometext = $event['hometext'];
        unset($event['hometext']);
        // format all the values for display
        $event = DataUtil::formatForDisplay($event);
        $event['hometext'] = DataUtil::formatForDisplayHTML($hometext); //add hometext back into array with HTML formatting

        // Check for comments
        if (ModUtil::available('EZComments')) {
            $event['commentcount'] = ModUtil::apiFunc('EZComments', 'user', 'countitems', array(
                'mod' => 'PostCalendar',
                'objectid' => $event['eid'],
                'status' => 0));
        } else {
            $event['commentcount'] = 0;
        }

        return $event;
    }

    /**
     * @desc This function reformats the information in an event array for insert/update in DB
     * @param array $event event array as pulled from the new/edit event form
     * @return array $event modified array for DB insert/update
     */
    public function formateventarrayforDB($event)
    {
        if ((substr($event['endDate'], 0, 4) == '0000') || (substr($event['endDate'], 0, 5) == '-0001')) {
            $event['endDate'] = $event['eventDate'];
        }

        // reformat endTime to duration in seconds
        $event['duration'] = $this->computeduration($event);

        // reformat times from form to 'real' 24-hour format
        $startTime = $event['startTime'];
        unset($event['startTime']); // clears the whole array
        $event['startTime'] = $this->convertstarttime($startTime);

        // if event ADD perms are given to anonymous users...
        if (UserUtil::isLoggedIn()) {
            $event['informant'] = UserUtil::getVar('uid');
        } else {
            $event['informant'] = 1; // 'guest'
        }

        define('PC_ACCESS_ADMIN', SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_DELETE));

        // determine if the event is to be published immediately or not
        if ((bool) $this->getVar('pcAllowDirectSubmit') || (bool) PC_ACCESS_ADMIN || ($event['sharing'] != CalendarEvent::SHARING_GLOBAL)) {
            $event['eventstatus'] = CalendarEvent::APPROVED;
        } else {
            $event['eventstatus'] = CalendarEvent::QUEUED;
        }

        $event['endDate'] = ($event['endtype'] == self::ENDTYPE_ON) ? $event['endDate'] : null;

        if (!isset($event['alldayevent'])) {
            $event['alldayevent'] = false;
        }

        if (empty($event['hometext'])) {
            $event['hometext'] = ':text:' . $this->__(/*!(abbr) not applicable or not available*/'n/a'); // default description
        } else {
            $event['hometext'] = ':' . $event['html_or_text'] . ':' . $event['hometext']; // inserts :text:/:html: before actual content
        }

        if (!isset($event['recurrtype'])) {
            $event['recurrtype'] = CalendarEvent::RECURRTYPE_NONE;
        }

        $event['url'] = isset($event['url']) ? $this->_makeValidURL($event['url']) : '';

        return $event;
    }

    /**
     * @desc This function validates the data that has been submitted in the new/edit event form
     * @param array $submitted_event event array as submitted
     * @return bool $abort default=false. true if data does not validate.
     */
    public function validateformdata($submitted_event)
    {
        // title must be present
        if (empty($submitted_event['title'])) {
            LogUtil::registerError($this->__(/*!This is the field name from templates/event/submit.tpl:22*/"'Title' is a required field.") . '<br />');
            return true;
        }

        // check repeating frequencies
        if ($submitted_event['recurrtype'] == CalendarEvent::RECURRTYPE_REPEAT) {
            if (!is_numeric($submitted_event['recurrspec']['event_repeat_freq'])) {
                LogUtil::registerError($this->__('Error! The repetition frequency must be an integer.'));
                return true;
            }
            if (!isset($submitted_event['recurrspec']['event_repeat_freq']) || $submitted_event['recurrspec']['event_repeat_freq'] < 1 || empty($submitted_event['recurrspec']['event_repeat_freq'])) {
                LogUtil::registerError($this->__('Error! The repetition frequency must be at least 1.'));
                return true;
            }
        } elseif ($submitted_event['recurrtype'] == CalendarEvent::RECURRTYPE_REPEAT_ON) {
            if (!is_numeric($submitted_event['recurrspec']['event_repeat_on_freq'])) {
                LogUtil::registerError($this->__('Error! The repetition frequency must be an integer.'));
                return true;
            }
            if (!isset($submitted_event['recurrspec']['event_repeat_on_freq']) || $submitted_event['recurrspec']['event_repeat_on_freq'] < 1 || empty($submitted_event['recurrspec']['event_repeat_on_freq'])) {
                LogUtil::registerError($this->__('Error! The repetition frequency must be at least 1.'));
                return true;
            }
        }

        // check date validity
        $sdate = strtotime($submitted_event['eventDate']);
        $edate = strtotime($submitted_event['endDate']);

        if (($submitted_event['endtype'] == self::ENDTYPE_ON) && ($edate < $sdate)) {
            LogUtil::registerError($this->__('Error! The selected start date falls after the selected end date.'));
            return true;
        }

        // check time validity
        if ($submitted_event['alldayevent'] == 0) {
            $stime = $this->converttimetoseconds($submitted_event['startTime']);
            $etime = $this->converttimetoseconds($submitted_event['endTime']);
            if ($etime <= $stime) {
                LogUtil::registerError($this->__('Error! The end time must be after the start time.'));
                return true;
            }
        }

        return false;
    }

    /**
     * returns 'improved' url based on input string
     * checks to make sure scheme is present
     * @param string url
     * @return string
     */

    private function _makeValidURL($s)
    {
        if (empty($s)) {
            return '';
        }
        if (!preg_match('|^http[s]?:\/\/|i', $s)) {
            $s = 'http://' . $s;
        }
        return $s;
    }

    /**
     * This function is copied directly from the News module
     * credits to Jorn Wildt, Mark West, Philipp Niethammer or whoever wrote it
     * purpose analyze if the module has an Scribite! editor assigned
     * @param array args - func the function to check
     * @return bool
     */
    public function isformatted($args)
    {
        if (!isset($args['func'])) {
            $args['func'] = 'all';
        }

        if (ModUtil::available('scribite')) {
            $modinfo = ModUtil::getInfo(ModUtil::getIdFromName('scribite'));
            if (version_compare($modinfo['version'], '2.2', '>=')) {
                $apiargs = array(
                    'modulename' => 'PostCalendar'); // parameter handling corrected in 2.2
            } else {
                $apiargs = 'PostCalendar'; // old direct parameter
            }

            $modconfig = ModUtil::apiFunc('scribite', 'user', 'getModuleConfig', $apiargs);
            if (in_array($args['func'], (array) $modconfig['modfuncs']) && $modconfig['modeditor'] != '-') {
                return true;
            }
        }
        return false;
    }

    /**
     * @desc generate the inverse color
     * @author      Jonas John
     * @link        http://www.jonasjohn.de/snippets/php/color-inverse.htm
     * @license     public domain
     * @created     06/13/2006
     * @params      (string) hex color (e.g. #ffffff)
     * @return      (string) hex color (e.g. #000000)
     **/
    public function color_inverse($color)
    {
        $color = str_replace('#', '', $color);
        if (strlen($color) != 6) {
            return '000000';
        }
        $rgb = '';
        for ($x = 0; $x < 3; $x++) {
            $c = 255 - hexdec(substr($color, (2 * $x), 2));
            $c = ($c < 0) ? 0 : dechex($c);
            $rgb .= (strlen($c) < 2) ? '0' . $c : $c;
        }
        return '#' . $rgb;
    }

    /**
     * find all occurances of an event between start and stop dates of event
     * by default, do not include occurances before today.
     * 
     * @param array $event
     * @param mixed $start
     * @param mixed $end
     * @param boolean $includePast
     * @return array dates
     */
    public function getEventOccurances($event, $includePast = false)
    {
        $eventStart = DateTime::createFromFormat('Y-m-d', $event['eventDate']);
        $defaultEnd = clone $eventStart;
        $eventEnd = isset($event['endDate']) ? DateTime::createFromFormat('Y-m-d', $event['endDate']) : $defaultEnd->modify("+2 years");
        $occurances = array();
        switch ($event['recurrtype']) {
            case CalendarEvent::RECURRTYPE_NONE:
                return array($event['eventDate']);
                break;
            case CalendarEvent::RECURRTYPE_REPEAT:
                $rfreq = $event['recurrspec']['event_repeat_freq'];
                $rtype = $event['recurrspec']['event_repeat_freq_type'];
                $interval = DateInterval::createFromDateString("+$rfreq " . $this->rTypes[$rtype]);
                $period = new DatePeriod($eventStart, $interval, $eventEnd->modify("+1 day"));
                break;
            case CalendarEvent::RECURRTYPE_REPEAT_ON:
                $rfreq = $event['recurrspec']['event_repeat_on_freq'];
                $rnum = $event['recurrspec']['event_repeat_on_num'];
                $rday = $event['recurrspec']['event_repeat_on_day'];
                $eventStart->modify("last day of previous month");
                $interval = DateInterval::createFromDateString("{$this->rWeeks[$rnum]} {$this->rDays[$rday]} of next month");
                // not dealing with $rfreq!
                $period = new DatePeriod($eventStart, $interval, $eventEnd, DatePeriod::EXCLUDE_START_DATE);
                break;
        }
        $today = new DateTime();
        foreach ($period as $date) {
            if (($includePast && ($date < $today)) || ($date >= $today)) {
                $occurances[] = $date->format('Y-m-d');
            }
        }
        return $occurances;
    }

    /**
     * @desc take id from locations module and inserts data into correct fields in PostCalendar
     * @since     06/25/2010
     * @params      array $event event array
     * @return      array $event event array (modified)
     * @note        locations ID is discarded and not available to edit later
     **/
    public function correctlocationdata($event)
    {
        if (isset($event['location']['locations_id']) && ((int) $event['location']['locations_id'] > 0)) {
            $locargs = array('locationid' => $event['location']['locations_id']);
            $locObj = ModUtil::apiFunc('Locations','user','getLocationByID',$locargs);

            $event['location']['event_location'] = $locObj['name'];
            $event['location']['event_street1']  = $locObj['street'];
            $event['location']['event_street2']  = $locObj[''];
            $event['location']['event_city']     = $locObj['city'];
            $event['location']['event_state']    = $locObj['state'];
            $event['location']['event_postal']   = $locObj['zip'];

            $event['conttel']   = isset($event['conttel'])   ? $event['conttel']   : $locObj['phone'];
            $event['contemail'] = isset($event['contemail']) ? $event['contemail'] : $locObj['email'];
            $event['website']   = isset($event['website'])   ? $event['website']   : $locObj['url'];
        }
        return $event;
    }
    /**
     * @desc convert time like 3:00 to seconds
     * @since     06/29/2010
     * @params      array $time array(Hour, Minute, Meridian (opt))
     * @return      int seconds
     **/
    public function converttimetoseconds($time)
    {
        if (isset($time['Meridian']) && !empty($time['Meridian'])) {
            if ($time['Meridian'] == "am") {
                $time['Hour'] = $time['Hour'] == 12 ? '00' : $time['Hour'];
            } else {
                $time['Hour'] = $time['Hour'] != 12 ? $time['Hour'] += 12 : $time['Hour'];
            }
        }
        return (60 * 60 * $time['Hour']) + (60 * $time['Minute']);
    }
    /**
     * @desc convert time array to HH:MM:SS
     * @since     06/29/2010
     * @params      array $time array(Hour, Minute, Meridian)
     * @return      string 'HH:MM:SS'
     **/
    public function convertstarttime($time)
    {
        if ((bool) !_SETTING_TIME_24HOUR) {
            if ($time['Meridian'] == "am") {
                $time['Hour'] = $time['Hour'] == 12 ? '00' : $time['Hour'];
            } else {
                $time['Hour'] = $time['Hour'] != 12 ? $time['Hour'] += 12 : $time['Hour'];
            }
        }
        return sprintf('%02d', $time['Hour']) . ':' . sprintf('%02d', $time['Minute']) . ':00';
    }
    /**
     * @desc create event sharing select box
     * @since     06/29/2010
     * @return      array key=>value pairs for selectbox
     **/
    public function sharingselect()
    {
        $data = array();
        if (_SETTING_ALLOW_USER_CAL) {
            $data[CalendarEvent::SHARING_PRIVATE] = $this->__('Private');
        }
        if (SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN) || !_SETTING_ALLOW_USER_CAL) {
            $data[CalendarEvent::SHARING_GLOBAL] = $this->__('Global');
        }
        return $data;
    }
    /**
     * @desc determine which event type is selected and prepare html code
     * @since     06/29/2010
     * @params      bool $alldayevent
     * @return      array key=>value pairs for selectbox
     **/
    public function alldayselect($alldayevent)
    {
        $eventDefaults = $this->getVar('pcEventDefaults');
        $selected = array();
        $selected['allday'] = (((isset($alldayevent)) && ($alldayevent == 1)) || ((!isset($alldayevent)) && ($eventDefaults['alldayevent'] == 1))) ? " checked='checked'" : '';
        $selected['timed']  = (((!isset($alldayevent)) && ($eventDefaults['alldayevent'] == 0)) || ((isset($alldayevent)) && ($alldayevent == 0))) ? " checked='checked'" : ''; //default

        return $selected;
    }
    /**
     * @desc compute endTime from startTime and duration
     * @since     06/30/2010
     * @params      array $event
     * @return      string endTime formatted (HH:MM or HH:MM AP)
     **/
    public function computeendtime($event)
    {
        $stime = DateTime::createFromFormat('G:i:s', $event['startTime']);
        $stime->modify("+" . $event['duration'] . " seconds");
        return _SETTING_TIME_24HOUR ? $stime->format('G:i') : $stime->format('g:i a');
    }
    /**
     * @desc compute duration from startTime and endTime
     * @since     06/30/2010
     * @params      array $event
     * @return      string duration in seconds
     **/
    public function computeduration($event)
    {
        $stime = $this->converttimetoseconds($event['startTime']);
        $etime = $this->converttimetoseconds($event['endTime']);
        return $etime - $stime;
    }

    /**
     * convert categories array to proper filter info
     * @param array $filtercats
     * @return array
     */
    public static function formatCategoryFilter($filtercats)
    {
        if (is_array($filtercats)) {
            $catsarray = is_array($filtercats['categories']) ? $filtercats['categories'] : array('Main' => 0);
            foreach ($catsarray as $propname => $propid) {
                if (is_array($propid)) { // select multiple used
                    foreach ($propid as $int_key => $int_id) {
                        if ($int_id <= 0) {
                            unset($catsarray[$propname][$int_key]); // removes categories set to 'all' (0)
                        }
                        if (empty($catsarray[$propname])) {
                            unset($catsarray[$propname]);
                        }
                    }
                } elseif (strstr($propid, ',')) { // category Zikula.UI.SelectMultiple used
                    $catsarray[$propname] = explode(',', $propid);
                    // no propid should be '0' in this case
                } else { // single selectbox used
                    if ($propid <= 0) {
                        unset($catsarray[$propname]); // removes categories set to 'all' (0)
                    }
                }
            }
        } else {
            $catsarray = array();
        }
        return $catsarray;
    }
} // end class def