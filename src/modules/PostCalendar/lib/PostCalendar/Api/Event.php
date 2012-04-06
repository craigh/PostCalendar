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
            $userFilter = ($this->getVar('pcAllowUserCalendar')) ? EventRepo::FILTER_ALL : EventRepo::FILTER_GLOBAL;
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
                ->getEventCollection($eventstatus, $startDate, $endDate, $userFilter, $userid, $filtercats, $searchDql);
        
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
            $occurances = $this->getEventOccurances($event, true);
            foreach ($occurances as $date) {
                if (isset($days[$date])) {
                    $days[$date][] = $this->formateventarrayfordisplay(array(
                        'event' => $event,
                        'currentDate' => $date));
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
            $eventdata['endDate'] = clone $args['date'];
            $eventdata['endDate']->modify("+1 day");
        } else {
            $eventdata['endDate'] = PostCalendar_Util::getDate(array('date' => $eventdata['endDate']));
        }
        if ((!isset($eventdata['eventStart'])) || empty($eventdata['eventStart'])) {
            $eventdata['eventStart'] = clone $args['date'];
            $startTimeParts = explode(":", $eventDefaults['startTime']);
            $eventdata['eventStart']->setTime($startTimeParts[0], $startTimeParts[1]);
        } else {
            $eventdata['eventStart'] = PostCalendar_Util::getDate(array('date' => $eventdata['eventStart']));
        }
        if ((!isset($eventdata['eventEnd'])) || empty($eventdata['eventEnd'])) {
            $eventdata['eventEnd'] = clone $eventdata['eventStart'];
            $eventdata['eventEnd']->modify("+" . $eventDefaults['duration'] . " seconds");
        } else {
            $eventdata['eventEnd'] = PostCalendar_Util::getDate(array('date' => $eventdata['eventEnd']));
        }

        if ((SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN)) && ($this->getVar('pcAllowUserCalendar'))) {
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

        // hometext
        $eventdata['HTMLorTextVal'] = (!empty($eventdata['HTMLorTextVal'])) ? $eventdata['HTMLorTextVal'] : 'text'; // default to text

        // sharing selectbox
        $form_data['sharingselect'] = $this->sharingselect();

        $eventdata['sharing'] = (isset($eventdata['sharing'])) ? $eventdata['sharing'] : $eventDefaults['sharing'];

        // recur type radio selects
        $eventdata['repeats'] = !((!isset($eventdata['recurrtype'])) || ((int)$eventdata['recurrtype'] == CalendarEvent::RECURRTYPE_NONE) || ((int)$eventdata['recurrtype'] == CalendarEvent::RECURRTYPE_CONTINUOUS)); //default
        $form_data['SelectedRepeat']   = ((isset($eventdata['recurrtype']))  && ((int)$eventdata['recurrtype'] == CalendarEvent::RECURRTYPE_REPEAT)) ? " checked='checked'" : '';
        $form_data['SelectedRepeatOn'] = ((isset($eventdata['recurrtype']))  && ((int)$eventdata['recurrtype'] == CalendarEvent::RECURRTYPE_REPEAT_ON)) ? " checked='checked'" : '';

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

        $in = explode("/", $this->__('Sunday/Monday/Tuesday/Wednesday/Thursday/Friday/Saturday'));
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

        $eventdata['recurrspec']['event_repeat_freq']    = isset($eventdata['recurrspec']['event_repeat_freq'])    ? $eventdata['recurrspec']['event_repeat_freq']    : "1";
        $eventdata['recurrspec']['event_repeat_on_freq'] = isset($eventdata['recurrspec']['event_repeat_on_freq']) ? $eventdata['recurrspec']['event_repeat_on_freq'] : "1";

        $eventdata['location']['event_location'] = isset($eventdata['location']['event_location']) ? $eventdata['location']['event_location'] : $eventDefaults['location']['event_location'];
        $eventdata['location']['event_street1']  = isset($eventdata['location']['event_street1'])  ? $eventdata['location']['event_street1']  : $eventDefaults['location']['event_street1'];
        $eventdata['location']['event_street2']  = isset($eventdata['location']['event_street2'])  ? $eventdata['location']['event_street2']  : $eventDefaults['location']['event_street2'];
        $eventdata['location']['event_city']     = isset($eventdata['location']['event_city'])     ? $eventdata['location']['event_city']     : $eventDefaults['location']['event_city'];
        $eventdata['location']['event_state']    = isset($eventdata['location']['event_state'])    ? $eventdata['location']['event_state']    : $eventDefaults['location']['event_state'];
        $eventdata['location']['event_postal']   = isset($eventdata['location']['event_postal'])   ? $eventdata['location']['event_postal']   : $eventDefaults['location']['event_postal'];

        $eventdata['haslocation'] = !empty($eventdata['location']['event_location']) || !empty($eventdata['location']['event_street1']) || !empty($eventdata['location']['event_street2']) || !empty($eventdata['location']['event_city']) || !empty($eventdata['location']['event_state']) || !empty($eventdata['location']['event_postal']);
        $eventdata['hascontact'] = !empty($eventdata['contname']) || !empty($eventdata['conttel']) || !empty($eventdata['contemail']) || !empty($eventdata['website']);
        
        // assign loaded data or default values
        $form_data['loaded_event'] = $eventdata;

        return $form_data;
    }

    /**
     * @desc This function reformats the information in an event array for proper display in detail
     * @param array $event event array as pulled from the DB
     * @param string $currentDate the date the event is being displayed upon (optional, default NULL)
     * @return array $event modified array for display
     */
    public function formateventarrayfordisplay($params)
    {
        if ((empty($params['event'])) or (!is_array($params['event']))) {
            return LogUtil::registerArgsError();
        } else {
            $event = $params['event'];
        }
        $currentDate = isset($params['currentDate']) ? DateTime::createFromFormat('Y-m-d', $params['currentDate']) : null;

        // build recurrance sentence for display
        $repeat_freq_type = explode("/", $this->__('Day(s)/Week(s)/Month(s)/Year(s)'));
        $repeat_on_num = explode("/", $this->__('err/First/Second/Third/Fourth/Last'));
        $repeat_on_day = explode("/", $this->__('Sunday/Monday/Tuesday/Wednesday/Thursday/Friday/Saturday'));
        $formats = $this->getVar('pcDateFormats');
        $timeFormat = $this->getVar('pcTime24Hours') ? 'G:i' : 'g:i a';

        switch ($event['recurrtype']) {
            case CalendarEvent::RECURRTYPE_REPEAT:
                $event['recurr_sentence'] = $this->__f("Event recurs every %s", $event['recurrspec']['event_repeat_freq']);
                $event['recurr_sentence'] .= " " . $repeat_freq_type[$event['recurrspec']['event_repeat_freq_type']];
                $event['recurr_sentence'] .= " " . $this->__("until") . " " . $event['endDate']->format($formats['date']);
                // modify start date to current date for display
                if (isset($currentDate)) {
                    $event['eventStart']->setDate($currentDate->format('Y'), $currentDate->format('m'), $currentDate->format('d'));
                }
                break;
            case CalendarEvent::RECURRTYPE_REPEAT_ON:
                $event['recurr_sentence'] = $this->__("Event recurs on") . " " . $repeat_on_num[$event['recurrspec']['event_repeat_on_num']];
                $event['recurr_sentence'] .= " " . $repeat_on_day[$event['recurrspec']['event_repeat_on_day']];
                $event['recurr_sentence'] .= " " . $this->__f("of the month, every %s months", $event['recurrspec']['event_repeat_on_freq']);
                $event['recurr_sentence'] .= " " . $this->__("until") . " " . $event['endDate']->format($formats['date']);
                // modify start date to current date for display
                if (isset($currentDate)) {
                    $event['eventStart']->setDate($currentDate->format('Y'), $currentDate->format('m'), $currentDate->format('d'));
                }
                break;
            case CalendarEvent::RECURRTYPE_CONTINUOUS:
                $dateTimeFormat = $event['alldayevent'] ? $formats['date'] : $formats['date'] . " @ " . $timeFormat;
                $event['recurr_sentence'] = $this->__("Continuous, multi-day event, beginning") . " " . $event['eventStart']->format($dateTimeFormat);
                $event['recurr_sentence'] .= " " . $this->__("and ending") . " " . $event['eventEnd']->format($dateTimeFormat);
                // modify event start and end dates if event is continuous 
                if (isset($currentDate)) {
                    if ((int)$event['eventStart']->format('Ymd') == (int)$currentDate->format('Ymd')) {
                        $end = clone $event['eventEnd'];
                        $event['eventEnd'] = $end->setTime(23, 59); // last minute of the day
                    } else if ((int)$event['eventEnd']->format('Ymd') == (int)$currentDate->format('Ymd')) {
                        $start = clone $event['eventStart'];
                        $event['eventStart'] = $start->setTime(0, 0); // first minute of the day
                    } else {
                        $event['alldayevent'] = true;
                    }
                }
                break;
            default:
                $event['recurr_sentence'] = $this->__("This event does not recur.");
        }

        // build sharing sentence for display
        $event['sharing_sentence'] = ($event['sharing'] == CalendarEvent::SHARING_PRIVATE) ? $this->__('This is a private event.') : $this->__('This is a public event.');

        $eventStart = clone $event['eventStart'];
        $eventEnd = clone $event['eventEnd'];
        $event['endTime'] = $eventEnd->format($timeFormat);
        $event['duration'] = $eventEnd->diff($eventStart)->format('%d days %h:%I hours'); // no translation?

        // prepare starttime for display HH:MM or HH:MM AP
        $event['sortTime']  = $eventStart->format('G:i'); // save for sorting later
        $event['startTime'] = $eventStart->format($timeFormat);

        // compensate for changeover to new categories system
        $lang = ZLanguage::getLanguageCode();
        $event['catname']      = isset($event['categories']['Main']['display_name'][$lang]) ? $event['categories']['Main']['display_name'][$lang] : $event['categories']['Main']['name'];
        $event['catcolor']     = isset($event['categories']['Main']['attributes']['color'])     ? $event['categories']['Main']['attributes']['color']     : '#eeeeee';
        $event['cattextcolor'] = isset($event['categories']['Main']['attributes']['textcolor']) ? $event['categories']['Main']['attributes']['textcolor'] : $this->color_inverse($event['catcolor']);

        // format some strings for display
        $event['hometext'] = DataUtil::formatForDisplayHTML($event['hometext']);
        $event['title'] = DataUtil::formatForDisplay($event['title']);
        $event['location'] = DataUtil::formatForDisplay($event['location']);
        $event['conttel'] = DataUtil::formatForDisplay($event['conttel']);
        $event['contname'] = DataUtil::formatForDisplay($event['contname']);
        $event['contemail'] = DataUtil::formatForDisplay($event['contemail']);
        $event['website'] = DataUtil::formatForDisplay($event['website']);
        
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
        // if event ADD perms are given to anonymous users, register informant as uid = 1 (guest)
        $event['informant'] = (UserUtil::isLoggedIn()) ? UserUtil::getVar('uid') : 1;

        define('PC_ACCESS_DELETE', SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_DELETE));

        // determine if the event is to be published immediately or not
        if ((bool) $this->getVar('pcAllowDirectSubmit') || (bool) PC_ACCESS_DELETE || ($event['sharing'] != CalendarEvent::SHARING_GLOBAL)) {
            $event['eventstatus'] = CalendarEvent::APPROVED;
        } else {
            $event['eventstatus'] = CalendarEvent::QUEUED;
        }

        $event['endDate'] = ($event['recurrtype'] <> CalendarEvent::RECURRTYPE_NONE) ? $event['endDate'] : null;
        // if event is continuous and more than one day:
        if ((int)$event['eventEnd']->diff($event['eventStart'])->format('%d') > 0) {
            $event['recurrtype'] = CalendarEvent::RECURRTYPE_CONTINUOUS;
        }
        
        if (empty($event['hometext'])) {
            $event['hometext'] = ':text:' . $this->__(/*!(abbr) not applicable or not available*/'n/a'); // default description
        } else {
            $event['hometext'] = ':' . $event['html_or_text'] . ':' . $event['hometext']; // inserts :text:/:html: before actual content
        }

        $event['url'] = (isset($event['url'])) ? $this->_makeValidURL($event['url']) : '';

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

        // for recurring events, make sure the end of recurrance is after the eventStart
        if (($submitted_event['recurrtype'] <> CalendarEvent::RECURRTYPE_NONE) && ($submitted_event['endDate'] < $submitted_event['eventStart'])) {
            LogUtil::registerError($this->__('Error! The repeat end date must be after the event start date.'));
            return true;
        }

        // check time validity if not allday event
        if ($submitted_event['eventEnd'] <= $submitted_event['eventStart']) {
            LogUtil::registerError($this->__('Error! The event cannot end before it begins.'));
            return true;
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
        $eventStart = clone $event['eventStart'];
        $defaultEnd = clone $event['eventStart'];
        $eventEnd = isset($event['endDate']) ? $event['endDate'] : $defaultEnd->modify("+2 years");
        $occurances = array();
        switch ($event['recurrtype']) {
            case CalendarEvent::RECURRTYPE_NONE:
                return array($eventStart->format('Y-m-d'));
                break;
            case CalendarEvent::RECURRTYPE_REPEAT:
                $rfreq = $event['recurrspec']['event_repeat_freq'];
                $rtype = $event['recurrspec']['event_repeat_freq_type'];
                $interval = DateInterval::createFromDateString("+$rfreq " . $this->rTypes[$rtype]);
                $period = new DatePeriod($eventStart, $interval, $eventEnd->modify("+1 day"));
                break;
            case CalendarEvent::RECURRTYPE_REPEAT_ON:
                $rnum = $event['recurrspec']['event_repeat_on_num'];
                $rday = $event['recurrspec']['event_repeat_on_day'];
                $eventStart->modify("last day of previous month");
                $interval = DateInterval::createFromDateString("{$this->rWeeks[$rnum]} {$this->rDays[$rday]} of next month");
                $period = new DatePeriod($eventStart, $interval, $eventEnd->modify("+1 day"), DatePeriod::EXCLUDE_START_DATE);
                break;
            case CalendarEvent::RECURRTYPE_CONTINUOUS:
                $interval = DateInterval::createFromDateString("+1 day");
                $eventEnd = clone $event['eventEnd'];
                $period = new DatePeriod($eventStart, $interval, $eventEnd);
                break;
        }
        $rfreq = $event['recurrspec']['event_repeat_on_freq'];
        $today = new DateTime();
        $count = 0;
        foreach ($period as $date) {
            if (($includePast && ($date < $today)) || ($date >= $today)) {
                if (($event['recurrtype'] <> CalendarEvent::RECURRTYPE_REPEAT_ON) 
                        || (($event['recurrtype'] == CalendarEvent::RECURRTYPE_REPEAT_ON) && (($count == 0) || ($rfreq == $count)))) {
                    $occurances[] = $date->format('Y-m-d');
                    $count = 0;
                }
                $count++;
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
     * @desc convert time array to desired format
     *      WARNING: uses current date for calculations be careful with comparisons
     * @param   array $time array(Hour, Minute, Meridian)
     * @param   string $format desired output format
     * @return  string
     **/    
    public function convertTimeArray($time, $format = 'G:i')
    {
        $timeString = "{$time['Hour']}:{$time['Minute']}";
        $stringFormat = 'G:i';
        if (isset($time['Meridian']) && !empty($time['Meridian'])) {
            $stringFormat = 'g:i a';
            $timeString .= " {$time['Meridian']}";
        }
        $newTime = DateTime::createFromFormat($stringFormat, $timeString);
        return $newTime->format($format);
    }
    /**
     * @desc create event sharing select box
     * @since     06/29/2010
     * @return      array key=>value pairs for selectbox
     **/
    public function sharingselect()
    {
        $data = array();
        $allowUserCal = $this->getVar('pcAllowUserCalendar');
        if ($allowUserCal) {
            $data[CalendarEvent::SHARING_PRIVATE] = $this->__('Private');
        }
        if (SecurityUtil::checkPermission('PostCalendar::', '::', ACCESS_ADMIN) || !$allowUserCal) {
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
        $stime = DateTime::createFromFormat('G:i', $event['startTime']);
        $stime->modify("+" . $event['duration'] . " seconds");
        return $this->getVar('pcTime24Hours') ? $stime->format('G:i') : $stime->format('g:i a');
    }
    /**
     * @desc compute duration from startTime and endTime
     * @since     06/30/2010
     * @params      array $event
     * @return      string duration in seconds
     **/
    public function computeduration($event)
    {
        $stime = $this->convertTimeArray($event['startTime'], 'U');
        $etime = $this->convertTimeArray($event['endTime'], 'U');
        return $etime - $stime;
    }

    /**
     * convert categories array to proper filter info
     * @param array $filtercats
     * @return array
     */
    public static function formatCategoryFilter($filtercats)
    {
        $selectedCategories = array();
        if (isset($filtercats) && is_array($filtercats)) {
            foreach ($filtercats as $propid) {
                if (is_array($propid)) { // select multiple used
                    foreach ($propid as $id) {
                        if ($id > 0) {
                            $selectedCategories[] = $id;
                        }
                    }
                } elseif (strstr($propid, ',')) { // category Zikula.UI.SelectMultiple used
                    $ids = explode(',', $propid);
                    // no propid should be '0' in this case
                    foreach ($ids as $id) {
                        $selectedCategories[] = $id;
                    }
                } else { // single selectbox used
                    if ($propid > 0) {
                        $selectedCategories[] = $propid;
                    }
                }
            }
        }
        return $selectedCategories;
    }
} // end class def