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
class PostCalendar_CalendarView_Nav_Month extends PostCalendar_CalendarView_Nav_AbstractItemBase
{
    public function setup()
    {
        $this->viewtype = 'month';
        $this->imageTitleText = $this->view->__('Month View');
        $this->displayText = $this->view->__('Month');
        $this->displayImageOn = 'month_on.gif';
        $this->displayImageOff = 'month.gif';
        $params = array(
            'modname' => 'PostCalendar',
            'src' => $this->selected ? $this->displayImageOn : $this->displayImageOff,
            'alt' => $this->imageTitleText,
            'title' => $this->imageTitleText);
        $this->imageHtml = smarty_function_img($params, $this->view);
        if ($this->useDisplayImage) {
            $this->cssClasses[] = 'postcalendar_nav_img';
        } else {
            if ($this->selected) {
                $this->cssClasses[] = 'postcalendar_nav_text_selected';
            } else {
                $this->cssClasses[] = 'postcalendar_nav_text';
            }
        }
        if ($this->usePopups) {
            $this->cssClasses[] = 'tooltips';
        }
    }
    protected function setUrl()
    {
        $this->url = ModUtil::url('PostCalendar', 'user', 'display', array(
                        'viewtype' => $this->viewtype,
                        'date' => $this->date->format('Ymd'),
                        'pc_username' => $this->userFilter));
    }
    protected function setAnchorTag()
    {
        $class = implode(' ', $this->cssClasses);
        $display = $this->useDisplayImage ? $this->imageHtml : $this->displayText;
        $this->anchorTag = "<a href='" . $this->getUrl() . "' class='$class' title='$this->imageTitleText'>$display</a>";
    }
}