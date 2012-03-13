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
class PostCalendar_CalendarView_Month extends PostCalendar_CalendarView_AbstractDays
{

    protected function setCacheTag()
    {
        $this->cacheTag = $this->date->format('Ym');
    }

    protected function setTemplate()
    {
        $this->template = 'user/view_month.tpl';
    }

    protected function setup()
    {
        $this->viewtype = 'month';
        $this->calendarGraph['first'] = null; // use DateTime obj
        $this->calendarGraph['last'] = null; // use DateTime obj
        $this->calendarGraph['graph'] = null; // use $this->calc
    }

    public function render()
    {
        if (!$this->isCached()) {
            
        }
        return $this->view->fetch($this->template);
    }

}

/*
if ($this->dayDisplay['lastDayOfMonth'] == (($this->firstDayOfWeek + 6) % 7)) {
    $this->dayDisplay['lastDateDisplayed'] = $this->requestedDate->format('t');
} else {
    $this->dayDisplay['lastDateDisplayed'] = $this->requestedDate->format('t') + abs((($this->firstDayOfWeek + 6) % 7) - $this->dayDisplay['lastDayOfMonth']);
}


        switch (_SETTING_FIRST_DAY_WEEK) {
            case self::MONDAY_IS_FIRST:
                $pc_array_pos = 1;
                $first_day = date('w', mktime(0, 0, 0, $the_month, 0, $the_year));
                $week_day = date('w', mktime(0, 0, 0, $the_month, $the_day - 1, $the_year));
                $end_dow = date('w', mktime(0, 0, 0, $the_month, $last_day, $the_year));
                if ($end_dow != 0) {
                    $the_last_day = $last_day + (7 - $end_dow);
                } else { // ==0
                    $the_last_day = $last_day;
                }
                $pc_colclasses[5] = "pcWeekend";
                $pc_colclasses[6] = "pcWeekend";
                break;
            case self::SATURDAY_IS_FIRST:
                $pc_array_pos = 6;
                $first_day = date('w', mktime(0, 0, 0, $the_month, 2, $the_year));
                $week_day = date('w', mktime(0, 0, 0, $the_month, $the_day + 1, $the_year));
                $end_dow = date('w', mktime(0, 0, 0, $the_month, $last_day, $the_year));
                if ($end_dow == 6) {
                    $the_last_day = $last_day + 6;
                } elseif ($end_dow != 5) {
                    $the_last_day = $last_day + (5 - $end_dow);
                } else { // ==5
                    $the_last_day = $last_day;
                }
                $pc_colclasses[0] = "pcWeekend";
                $pc_colclasses[1] = "pcWeekend";
                break;
            case self::SUNDAY_IS_FIRST:
            default:
                $pc_array_pos = 0;
                $first_day = date('w', mktime(0, 0, 0, $the_month, 1, $the_year));
                $week_day = date('w', mktime(0, 0, 0, $the_month, $the_day, $the_year));
                $end_dow = date('w', mktime(0, 0, 0, $the_month, $last_day, $the_year));
                if ($end_dow != 6) {
                    $the_last_day = $last_day + (6 - $end_dow);
                } else { // ==6
                    $the_last_day = $last_day;
                }
                echo "first: $first_day; week: $week_day; end: $end_dow; last: $last_day; theLast: $the_last_day;<br />";
                $pc_colclasses[0] = "pcWeekend";
                $pc_colclasses[6] = "pcWeekend";
                break;
        }
 
 */