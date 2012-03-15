<?php

/**
 * PostCalendar
 * 
 * @license MIT
 * @copyright   Copyright (c) 2012, Craig Heydenburg, Sound Web Development
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */

/**
 * This class is used as a base class for any CalendarView which displays multiple
 * days (e.g. Week, Month, etc) 
 */
abstract class PostCalendar_CalendarView_AbstractDays extends PostCalendar_CalendarView_AbstractCalendarViewBase
{

    const SUNDAY_IS_FIRST = 0;
    const MONDAY_IS_FIRST = 1;
    const SATURDAY_IS_FIRST = 6;
    
    /**
     * Integer representing the admin-selected value for first day of the week [0-6]
     * @var integer
     */
    protected $firstDayOfWeek;
    
    protected $startDate;
    protected $endDate;
    protected $graph;

    /**
     * An array of information needed to create the display of the calendar graph
     * 
     * long and short are arrays of weekdays in desired order
     * firstDayOfMonth is the array position of first day of the month [0-6]
     * lastDayOfMonth is the array position of last day of the month [0-6]
     * lastDateDisplayed is the last day of the calendar graph display counting from
     *     the first day of the actual month [28+]
     * dayOfWeek is the array position of user selected day of the month [0-6]
     * 
     * @var array 
     */
    protected $dayDisplay = array('long' => array(),
        'short' => array(),
        'firstDayOfMonth' => null,
        'lastDayOfMonth' => null,
        'lastDateDisplayed' => null,
        'dayOfWeek' => null,
        'colclass' => array(0 => "pcWeekday",
            1 => "pcWeekday",
            2 => "pcWeekday",
            3 => "pcWeekday",
            4 => "pcWeekday",
            5 => "pcWeekday",
            6 => "pcWeekday"));

    function __construct(Zikula_View $view, $requestedDate, $userFilter, $categoryFilter)
    {
        parent::__construct($view, $requestedDate, $userFilter, $categoryFilter);
        $this->firstDayOfWeek = ModUtil::getVar('PostCalendar', 'pcFirstDayOfWeek');
        $this->setUpDayDisplay();
        $this->setDates();
    }

    /**
     * This function enables the translation of daynames
     * and sets up an array of the names in the desired order based on
     * the selected 'first day of week'
     * It also created a css class for each day based
     */
    protected function setUpDayDisplay()
    {
        $arrayPointer = $this->firstDayOfWeek;
        $shortNames = explode(" ", $this->__(/* !First Letter of each Day of week */'S M T W T F S'));
        $longNames = explode(" ", $this->__('Sunday Monday Tuesday Wednesday Thursday Friday Saturday'));
        for ($i = 0; $i < 7; $i++) {
            if ($arrayPointer >= 7) {
                $arrayPointer = 0;
            }
            $this->dayDisplay['long'][$i] = $longNames[$arrayPointer];
            $this->dayDisplay['short'][$i] = $shortNames[$arrayPointer];
            $arrayPointer++;
        }
        switch ($this->firstDayOfWeek) {
            case self::MONDAY_IS_FIRST:
                $this->dayDisplay['firstDayOfMonth'] = date('w', mktime(0, 0, 0, $this->requestedDate->format('m'), 0, $this->requestedDate->format('Y')));
                $this->dayDisplay['dayOfWeek'] = date('w', mktime(0, 0, 0, $this->requestedDate->format('m'), $this->requestedDate->format('d') - 1, $this->requestedDate->format('Y')));
                $this->dayDisplay['lastDayOfMonth'] = date('w', mktime(0, 0, 0, $this->requestedDate->format('m'), $this->requestedDate->format('t') - 1, $this->requestedDate->format('Y')));
                $this->dayDisplay['colclass'][5] = "pcWeekend";
                $this->dayDisplay['colclass'][6] = "pcWeekend";
                break;
            case self::SATURDAY_IS_FIRST:
                $this->dayDisplay['firstDayOfMonth'] = date('w', mktime(0, 0, 0, $this->requestedDate->format('m'), 2, $this->requestedDate->format('Y')));
                $this->dayDisplay['dayOfWeek'] = date('w', mktime(0, 0, 0, $this->requestedDate->format('m'), $this->requestedDate->format('d') + 1, $this->requestedDate->format('Y')));
                $this->dayDisplay['lastDayOfMonth'] = date('w', mktime(0, 0, 0, $this->requestedDate->format('m'), $this->requestedDate->format('t') + 1, $this->requestedDate->format('Y')));
                $this->dayDisplay['colclass'][0] = "pcWeekend";
                $this->dayDisplay['colclass'][1] = "pcWeekend";
                break;
            case self::SUNDAY_IS_FIRST:
            default:
                $this->dayDisplay['firstDayOfMonth'] = date('w', mktime(0, 0, 0, $this->requestedDate->format('m'), 1, $this->requestedDate->format('Y')));
                $this->dayDisplay['dayOfWeek'] = date('w', mktime(0, 0, 0, $this->requestedDate->format('m'), $this->requestedDate->format('d'), $this->requestedDate->format('Y')));
                $this->dayDisplay['lastDayOfMonth'] = date('w', mktime(0, 0, 0, $this->requestedDate->format('m'), $this->requestedDate->format('t'), $this->requestedDate->format('Y')));
                $this->dayDisplay['colclass'][0] = "pcWeekend";
                $this->dayDisplay['colclass'][6] = "pcWeekend";
                break;
        }
    }
    
    abstract protected function setDates();

}
