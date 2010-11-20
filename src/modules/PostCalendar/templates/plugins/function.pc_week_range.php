<?php
/**
 * @package     PostCalendar
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

/**
 * Get week range
 *
 * @param array $args array with arguments.
 *                    $args['date'] date to use for range building
 *                    $args['sep'] seperate the dates by this string
 *                    $args['format'] format all dates like this
 *                    $args['format1'] format date 1 like this
 *                    $args['format2'] format date 2 like this
 * @param Smarty $smarty the Smarty instance
 * @return unknown
 */
function smarty_function_pc_week_range($args, &$smarty)
{
    //setlocale(LC_TIME, ZLanguage::getLocale()); //setlocale(LC_TIME, _PC_LOCALE);
    if (!isset($args['date'])) {
        //not sure these three lines are needed with call to getDate here
        $jumpday   = FormUtil::getPassedValue('jumpday');
        $jumpmonth = FormUtil::getPassedValue('jumpmonth');
        $jumpyear  = FormUtil::getPassedValue('jumpyear');
        $Date      = FormUtil::getPassedValue('Date');
        $jumpargs  = array(
            'Date' => $Date,
            'jumpday' => $jumpday,
            'jumpmonth' => $jumpmonth,
            'jumpyear' => $jumpyear);
        $args['date'] = PostCalendar_Util::getDate($jumpargs);
    }

    if (!isset($args['sep'])) {
        $args['sep'] = ' - ';
    }

    if (!isset($args['format'])) {
        if (!isset($args['format1'])) {
            $args['format1'] = _SETTING_DATE_FORMAT;
        }
        if (!isset($args['format2'])) {
            $args['format2'] = _SETTING_DATE_FORMAT;
        }
    } else {
        $args['format1'] = $args['format'];
        $args['format2'] = $args['format'];
    }

    $y = substr($args['date'], 0, 4);
    $m = substr($args['date'], 4, 2);
    $d = substr($args['date'], 6, 2);

    // get the week date range for the supplied $date
    $dow = date('w', mktime(0, 0, 0, $m, $d, $y));
    if (_SETTING_FIRST_DAY_WEEK == 0) {
        $firstDay = DateUtil::strftime($args['format1'], mktime(0, 0, 0, $m, ($d - $dow), $y));
        $lastDay = DateUtil::strftime($args['format2'], mktime(0, 0, 0, $m, ($d + (6 - $dow)), $y));
    } elseif (_SETTING_FIRST_DAY_WEEK == 1) {
        $sub = ($dow == 0 ? 6 : $dow - 1);
        $firstDay = DateUtil::strftime($args['format1'], mktime(0, 0, 0, $m, ($d - $sub), $y));
        $lastDay = DateUtil::strftime($args['format2'], mktime(0, 0, 0, $m, ($d + (6 - $sub)), $y));
    } elseif (_SETTING_FIRST_DAY_WEEK == 6) {
        $sub = ($dow == 6 ? 0 : $dow + 1);
        $firstDay = DateUtil::strftime($args['format1'], mktime(0, 0, 0, $m, ($d - $sub), $y));
        $lastDay = DateUtil::strftime($args['format2'], mktime(0, 0, 0, $m, ($d + (6 - $sub)), $y));
    }

    $ret_val = $firstDay . $args['sep'] . $lastDay;

    if (isset($args['assign'])) {
        $smarty->assign($args['assign'], $ret_val);
        return;
    } else {
        return $ret_val;
    }
}