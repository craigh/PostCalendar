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

namespace Zikula\PostCalendarModule\CalendarView;

use CategoryRegistryUtil;
use SecurityUtil;
use UserUtil;
use LogUtil;
use ModUtil;
use CategoryUtil;
use DateTime;
use DataUtil;
use ZLanguage;
use DateInterval;
use DatePeriod;

/**
 * This class is used as a base class for any CalendarView which displays multiple
 * days (e.g. Week, Month, etc) 
 */
abstract class AbstractDays extends AbstractCalendarViewBase
{
    /**
     * @abstract
     * AbstractDays
     * 
     * An abstract class to construct Calendar View objects containing multiple days.
     */

    /**
     * Integer representing the admin-selected value for first day of the week [0-6]
     * @var integer
     */
    protected $firstDayOfWeek;

    /**
     * Start Date of range for Calendar View
     * @var DateTime object
     */
    protected $startDate;

    /**
     * End Date of range for Calendar View
     * @var Date Time
     */
    protected $endDate;

    /**
     * Array of dates to create the Calendar View
     * @var array
     */
    protected $dateGraph = array();

    /**
     * An array of information needed to create the display of the calendar graph
     * 
     * long and short are arrays of weekdays in desired order
     * firstDayOfMonth is the array position of first day of the month [0-6]
     * dayOfWeek is the array position of user selected day of the month [0-6]
     * colclass is used to style the day columns in the template
     * 
     * @var array 
     */
    protected $dayDisplay = array('long' => array(),
        'short' => array(),
        'firstDayOfMonth' => null,
        'dayOfWeek' => null,
        'colclass' => array(0 => "pcWeekday",
            1 => "pcWeekday",
            2 => "pcWeekday",
            3 => "pcWeekday",
            4 => "pcWeekday",
            5 => "pcWeekday",
            6 => "pcWeekday"));

    /**
     * Constructor
     * 
     * @param Zikula_View $view
     * @param DateTime $requestedDate
     * @param integer $userFilter
     * @param array $categoryFilter 
     */
    function __construct(\Zikula_View $view, $requestedDate, $userFilter, $categoryFilter)
    {
        $this->firstDayOfWeek = ModUtil::getVar('ZikulaPostCalendarModule', 'pcFirstDayOfWeek');
        parent::__construct($view, $requestedDate, $userFilter, $categoryFilter);
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
        // clone DateTime objects for later modification
        $firstClone = clone $this->requestedDate;
        $firstClone->modify("first day of this month");
        $dayClone = clone $this->requestedDate;
        switch ($this->firstDayOfWeek) {
            case self::MONDAY_IS_FIRST:
                $this->dayDisplay['firstDayOfMonth'] = $firstClone->modify("-1 day")->format("w");
                $this->dayDisplay['dayOfWeek'] = $dayClone->modify("-1 day")->format("w");
                $this->dayDisplay['colclass'][5] = "pcWeekend";
                $this->dayDisplay['colclass'][6] = "pcWeekend";
                break;
            case self::SATURDAY_IS_FIRST:
                $this->dayDisplay['firstDayOfMonth'] = $firstClone->modify("+1 day")->format("w");
                $this->dayDisplay['dayOfWeek'] = $dayClone->modify("+1 day")->format("w");
                $this->dayDisplay['colclass'][0] = "pcWeekend";
                $this->dayDisplay['colclass'][1] = "pcWeekend";
                break;
            case self::SUNDAY_IS_FIRST:
            default:
                $this->dayDisplay['firstDayOfMonth'] = $firstClone->format("w");
                $this->dayDisplay['dayOfWeek'] = $this->requestedDate->format('w');
                $this->dayDisplay['colclass'][0] = "pcWeekend";
                $this->dayDisplay['colclass'][6] = "pcWeekend";
                break;
        }
    }

    /**
     * Set startDate, endDate and dateGraph 
     */
    abstract protected function setDates();
}
